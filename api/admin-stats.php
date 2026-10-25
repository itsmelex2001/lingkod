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

    // For "Pending Actions" (pending registrations and inquiries)
    $pendingStmt = $pdo->query("SELECT COUNT(*) FROM patients WHERE status IN ('pending', 'for_approval', 'for_processing')");
    $pendingActions = $pendingStmt->fetchColumn() ?? 0;

    // Overall Patients
    $weekStmt = $pdo->query("SELECT COUNT(*) FROM patients WHERE YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1)");
    $thisWeek = $weekStmt->fetchColumn() ?? 0;

    $monthStmt = $pdo->query("SELECT COUNT(*) FROM patients WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())");
    $thisMonth = $monthStmt->fetchColumn() ?? 0;

    $yearStmt = $pdo->query("SELECT COUNT(*) FROM patients WHERE YEAR(created_at) = YEAR(CURDATE())");
    $thisYear = $yearStmt->fetchColumn() ?? 0;

    // Recent system logs for overview
    $alerts = getAdminActivity($pdo, 5);

    // Chart Data Generation
    $chartData = [];
    $chartLabels = [];
    $chartFilter = $_GET['chart'] ?? 'year';

    if ($chartFilter === 'year') {
        $stmt = $pdo->query("SELECT MONTH(created_at) as m, COUNT(*) as c FROM patients WHERE YEAR(created_at) = YEAR(CURDATE()) GROUP BY m");
        $counts = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        for ($i = 1; $i <= 12; $i++) {
            $chartLabels[] = $months[$i - 1];
            $chartData[] = $counts[$i] ?? 0;
        }
    } elseif ($chartFilter === 'month') {
        $stmt = $pdo->query("SELECT DAY(created_at) as d, COUNT(*) as c FROM patients WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE()) GROUP BY d");
        $counts = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, date('m'), date('Y'));
        for ($i = 1; $i <= $daysInMonth; $i++) {
            $chartLabels[] = $i;
            $chartData[] = $counts[$i] ?? 0;
        }
    } elseif ($chartFilter === 'week') {
        // YEARWEEK(..., 1) means week starts on Monday (MySQL standard). 
        // DAYOFWEEK() returns 1=Sun, 2=Mon... 7=Sat
        // Let's group by DATE(created_at) for the current week
        $stmt = $pdo->query("SELECT DATE(created_at) as d, COUNT(*) as c FROM patients WHERE YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1) GROUP BY d");
        $counts = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        // Generate dates for current week (Monday to Sunday)
        $monday = date('Y-m-d', strtotime('monday this week'));
        $days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
        for ($i = 0; $i < 7; $i++) {
            $currentDate = date('Y-m-d', strtotime($monday . " +$i days"));
            $chartLabels[] = $days[$i];
            $chartData[] = $counts[$currentDate] ?? 0;
        }
    }

    // Logs for Audit view
    $auditLogs = getAdminActivity($pdo, 50);

    respondOk([
        'chart_labels' => $chartLabels,
        'chart_data' => $chartData,
        'active_staff' => $staffStats['active_staff'] ?? 0,
        'todays_patients' => $todaysPatients,
        'pending_actions' => $pendingActions,
        'patients_week' => $thisWeek,
        'patients_month' => $thisMonth,
        'patients_year' => $thisYear,
        'alerts' => $alerts,
        'audit_logs' => $auditLogs,
        'staff_stats' => $staffStats,
        'doctor_stats' => $doctorStats
    ]);
}
respondErr('Method not allowed.', 405);
