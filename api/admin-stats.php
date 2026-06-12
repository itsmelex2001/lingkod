<?php
/**
 * api/admin-stats.php
 *
 * GET - returns all admin dashboard stats
 */
session_start();
require_once __DIR__ . '/config/cors.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/admin-helpers.php';

if (empty($_SESSION['user']) || $_SESSION['role'] !== 'admin') {
    respondErr('Unauthorized', 403);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $staffStats = getStaffStats($pdo);
    $doctorStats = getDoctorStats($pdo);
    
    // For "Today's Patients"
    $todayStmt = $pdo->query("SELECT COUNT(*) FROM patients WHERE DATE(created_at) = CURDATE()");
    $todaysPatients = $todayStmt->fetchColumn() ?? 0;

    // For "Pending Actions" (pending registrations)
    $pendingStmt = $pdo->query("SELECT COUNT(*) FROM patients WHERE status = 'pending'");
    $pendingActions = $pendingStmt->fetchColumn() ?? 0;

    // Recent system logs for overview
    $alerts = getAdminActivity($pdo, 5);

    // Logs for Audit view
    $auditLogs = getAdminActivity($pdo, 50);

    respondOk([
        'active_staff' => $staffStats['active_staff'] ?? 0,
        'todays_patients' => $todaysPatients,
        'pending_actions' => $pendingActions,
        'alerts' => $alerts,
        'audit_logs' => $auditLogs,
        'staff_stats' => $staffStats,
        'doctor_stats' => $doctorStats
    ]);
}
respondErr('Method not allowed.', 405);
