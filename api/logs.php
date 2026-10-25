<?php
/**
 * api/logs.php
 *
 * GET                      → fetch all system logs (newest first)
 * GET  ?action=export      → return logs as CSV attachment
 * DELETE / POST {action:'clear'} → delete all logs (admin only)
 */

session_start();
require_once __DIR__ . '/config/cors.php';
require_once __DIR__ . '/config/db.php';

requireRole('admin');

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? (getBody()['action'] ?? '');

// ── GET: list logs ───────────────────────────────────────────────────────────
if ($method === 'GET' && $action !== 'export') {
    $stmt = $pdo->query("
        SELECT 
            l.id, 
            l.level, 
            DATE_FORMAT(l.created_at,'%Y-%m-%d %H:%i') AS timestamp,
            l.action, 
            COALESCE(
                IF(u.role = 'admin', 'admin', CONCAT('staff: ', u.username)),
                l.user
            ) AS user, 
            l.details
        FROM system_logs l
        LEFT JOIN users u ON l.user = u.id
        ORDER BY l.created_at DESC
    ");
    respondOk(['data' => $stmt->fetchAll()]);
}

// ── GET ?action=export: download as CSV ─────────────────────────────────────
if ($method === 'GET' && $action === 'export') {
    $stmt = $pdo->query("
        SELECT 
            l.level, 
            DATE_FORMAT(l.created_at,'%Y-%m-%d %H:%i') AS timestamp,
            l.action, 
            COALESCE(
                IF(u.role = 'admin', 'admin', CONCAT('staff: ', u.username)),
                l.user
            ) AS user, 
            l.details
        FROM system_logs l
        LEFT JOIN users u ON l.user = u.id
        ORDER BY l.created_at DESC
    ");
    $rows = $stmt->fetchAll();

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="system_logs_' . date('Ymd_His') . '.csv"');

    $out = fopen('php://output', 'w');
    fputcsv($out, ['Level', 'Timestamp', 'Action', 'User', 'Details']);
    foreach ($rows as $row) fputcsv($out, $row);
    fclose($out);
    exit;
}

// ── DELETE or POST {action:'clear'}: clear all logs (admin only) ─────────────
if ($method === 'DELETE' || ($method === 'POST' && $action === 'clear')) {
    if (($_SESSION['role'] ?? '') !== 'admin') {
        respondErr('Access denied. Administrator privileges required.', 403);
    }

    $pdo->exec("DELETE FROM system_logs");

    // Re-insert one record so the log isn't empty
    addLog($pdo, 'info', 'Logs Cleared', sessionUser(),
           sessionUser() . ' cleared all system logs.');

    respondOk(['message' => 'All logs cleared.']);
}

respondErr('Method not allowed.', 405);
