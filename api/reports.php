<?php
/**
 * api/reports.php
 *
 * GET - returns live analytics data for the admin and staff portals
 */
session_start();
require_once __DIR__ . '/config/cors.php';
require_once __DIR__ . '/config/db.php';

requireAuth();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // 1. Total Consults (all time)
    $stmt1 = $pdo->query("SELECT COUNT(*) FROM patients");
    $totalConsults = $stmt1->fetchColumn() ?? 0;

    // 2. Attendance Rate (approved vs pending/rejected)
    // For demo purposes, we'll calculate it as (approved / total) * 100
    $stmt2 = $pdo->query("SELECT COUNT(*) FROM patients WHERE status = 'approved'");
    $approved = $stmt2->fetchColumn() ?? 0;
    $attendanceRate = $totalConsults > 0 ? round(($approved / $totalConsults) * 100, 1) : 0;

    // 3. No-Show Rate
    // For demo purposes, we'll calculate it as (rejected / total) * 100
    $stmt3 = $pdo->query("SELECT COUNT(*) FROM patients WHERE status = 'rejected'");
    $rejected = $stmt3->fetchColumn() ?? 0;
    $noShowRate = $totalConsults > 0 ? round(($rejected / $totalConsults) * 100, 1) : 0;

    // 4. Total Patients Today
    $stmt4 = $pdo->query("SELECT COUNT(*) FROM patients WHERE DATE(created_at) = CURDATE()");
    $totalToday = $stmt4->fetchColumn() ?? 0;

    respondOk([
        'total_consults' => number_format($totalConsults),
        'attendance_rate' => $attendanceRate . '%',
        'no_show_rate' => $noShowRate . '%',
        'total_patients_today' => $totalToday,
        'shift_coverage' => '100%' // Dummy value for demo
    ]);
}

respondErr('Method not allowed.', 405);
