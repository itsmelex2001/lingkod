<?php
/**
 * api/settings.php
 *
 * GET  - Fetch all system settings (Admin only)
 * POST - Update a system setting (action: 'update', setting_key, setting_value)
 */

session_start();
require_once __DIR__ . '/config/cors.php';
require_once __DIR__ . '/config/db.php';
requireAuth();

$method = $_SERVER['REQUEST_METHOD'];
$actor  = sessionUser();
$role   = $_SESSION['role'] ?? 'staff';

if ($role !== 'admin') {
    respondErr('Unauthorized access.', 403);
}

if ($method === 'GET') {
    $stmt = $pdo->query("SELECT setting_key, setting_value, description, updated_at FROM system_settings");
    respondOk(['data' => $stmt->fetchAll()]);
}

if ($method === 'POST') {
    $b = getBody();
    $action = $b['action'] ?? '';

    if ($action === 'update') {
        $key = trim($b['setting_key'] ?? '');
        $val = trim($b['setting_value'] ?? '');

        if (!$key) respondErr('setting_key is required.');

        $check = $pdo->prepare("SELECT id FROM system_settings WHERE setting_key = ?");
        $check->execute([$key]);
        if ($check->fetch()) {
            $stmt = $pdo->prepare("UPDATE system_settings SET setting_value = ? WHERE setting_key = ?");
            $stmt->execute([$val, $key]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?)");
            $stmt->execute([$key, $val]);
        }

        addLog($pdo, 'info', 'System Setting Updated', $actor, "Setting '$key' was changed to '$val'.");
        respondOk(['message' => 'Setting updated successfully.']);
    }

    respondErr('Invalid action.');
}

respondErr('Method not allowed.', 405);
