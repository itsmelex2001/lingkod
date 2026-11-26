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

    // Pending approvals are real bookings or inquiries. Account profiles
    // (status leftover as pending, with no visit details) are not appointments.
    $pendingStatusSql = "LOWER(TRIM(COALESCE(status, ''))) IN ('pending', 'for_approval', 'for_processing', 'for approval')";
    $realBookingSql = "(
        (appointment_date IS NOT NULL AND appointment_date <> '0000-00-00')
        OR (department IS NOT NULL AND TRIM(department) <> '')
        OR (chief_complaints IS NOT NULL AND TRIM(chief_complaints) <> '')
        OR (consultation_nature IS NOT NULL AND TRIM(consultation_nature) <> '')
        OR COALESCE(unscheduled_walkin, 0) = 1
        OR COALESCE(inquiry_only, 0) = 1
    )";

    $pendingStmt = $pdo->query("SELECT COUNT(*) FROM patients WHERE $pendingStatusSql AND $realBookingSql");
    $pendingActions = (int)($pendingStmt->fetchColumn() ?? 0);

    $pendingApptStmt = $pdo->query("SELECT COUNT(*) FROM patients WHERE $pendingStatusSql AND $realBookingSql AND COALESCE(inquiry_only, 0) = 0");
    $pendingAppointments = (int)($pendingApptStmt->fetchColumn() ?? 0);

    $pendingInqStmt = $pdo->query("SELECT COUNT(*) FROM patients WHERE $pendingStatusSql AND COALESCE(inquiry_only, 0) = 1");
    $pendingInquiries = (int)($pendingInqStmt->fetchColumn() ?? 0);

    $unassignedStmt = $pdo->query("SELECT COUNT(*) FROM patients WHERE COALESCE(inquiry_only, 0) = 0 AND $realBookingSql AND LOWER(TRIM(COALESCE(status, ''))) NOT IN ('completed', 'cancelled', 'rejected', 'no-show', 'transferred', 'registered') AND (assigned_doctor IS NULL OR TRIM(assigned_doctor) = '' OR LOWER(TRIM(assigned_doctor)) = 'none')");
    $unassignedUpcoming = (int)($unassignedStmt->fetchColumn() ?? 0);

    // Overall Patients
    $weekStmt = $pdo->query("SELECT COUNT(*) FROM patients WHERE YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1)");
    $thisWeek = $weekStmt->fetchColumn() ?? 0;

    $monthStmt = $pdo->query("SELECT COUNT(*) FROM patients WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())");
    $thisMonth = $monthStmt->fetchColumn() ?? 0;

    $yearStmt = $pdo->query("SELECT COUNT(*) FROM patients WHERE YEAR(created_at) = YEAR(CURDATE())");
    $thisYear = $yearStmt->fetchColumn() ?? 0;

    // Recent system logs for overview notifications
    $alerts = getAdminActivity($pdo, 8);

    // Chart Data Generation (Combined + 3 Departments: General Medical, Neurology, Psychiatry)
    $chartData = [];
    $chartLabels = [];
    $deptSeries = [
        'general'    => [],
        'neurology'  => [],
        'psychiatry' => []
    ];
    $chartFilter = $_GET['chart'] ?? 'year';

    $classifyDept = function (?string $raw): string {
        $c = strtolower(trim($raw ?? ''));
        if (str_contains($c, 'psych') || str_contains($c, 'opu')) return 'psychiatry';
        if (str_contains($c, 'neuro')) return 'neurology';
        return 'general';
    };

    if ($chartFilter === 'year') {
        $stmt = $pdo->query("SELECT MONTH(created_at) as m, department, COUNT(*) as c FROM patients WHERE YEAR(created_at) = YEAR(CURDATE()) GROUP BY m, department");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $counts = [];
        $deptCounts = ['general' => [], 'neurology' => [], 'psychiatry' => []];
        foreach ($rows as $r) {
            $m = (int)$r['m'];
            $cnt = (int)$r['c'];
            $dk = $classifyDept($r['department']);
            $counts[$m] = ($counts[$m] ?? 0) + $cnt;
            $deptCounts[$dk][$m] = ($deptCounts[$dk][$m] ?? 0) + $cnt;
        }
        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        for ($i = 1; $i <= 12; $i++) {
            $chartLabels[] = $months[$i - 1];
            $chartData[] = $counts[$i] ?? 0;
            $deptSeries['general'][]    = $deptCounts['general'][$i] ?? 0;
            $deptSeries['neurology'][]  = $deptCounts['neurology'][$i] ?? 0;
            $deptSeries['psychiatry'][] = $deptCounts['psychiatry'][$i] ?? 0;
        }
    } elseif ($chartFilter === 'month') {
        $stmt = $pdo->query("SELECT DAY(created_at) as d, department, COUNT(*) as c FROM patients WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE()) GROUP BY d, department");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $counts = [];
        $deptCounts = ['general' => [], 'neurology' => [], 'psychiatry' => []];
        foreach ($rows as $r) {
            $d = (int)$r['d'];
            $cnt = (int)$r['c'];
            $dk = $classifyDept($r['department']);
            $counts[$d] = ($counts[$d] ?? 0) + $cnt;
            $deptCounts[$dk][$d] = ($deptCounts[$dk][$d] ?? 0) + $cnt;
        }
        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, date('m'), date('Y'));
        for ($i = 1; $i <= $daysInMonth; $i++) {
            $chartLabels[] = $i;
            $chartData[] = $counts[$i] ?? 0;
            $deptSeries['general'][]    = $deptCounts['general'][$i] ?? 0;
            $deptSeries['neurology'][]  = $deptCounts['neurology'][$i] ?? 0;
            $deptSeries['psychiatry'][] = $deptCounts['psychiatry'][$i] ?? 0;
        }
    } elseif ($chartFilter === 'week') {
        $stmt = $pdo->query("SELECT DATE(created_at) as d, department, COUNT(*) as c FROM patients WHERE YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1) GROUP BY d, department");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $counts = [];
        $deptCounts = ['general' => [], 'neurology' => [], 'psychiatry' => []];
        foreach ($rows as $r) {
            $d = $r['d'];
            $cnt = (int)$r['c'];
            $dk = $classifyDept($r['department']);
            $counts[$d] = ($counts[$d] ?? 0) + $cnt;
            $deptCounts[$dk][$d] = ($deptCounts[$dk][$d] ?? 0) + $cnt;
        }
        
        $monday = date('Y-m-d', strtotime('monday this week'));
        $days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
        for ($i = 0; $i < 7; $i++) {
            $currentDate = date('Y-m-d', strtotime($monday . " +$i days"));
            $chartLabels[] = $days[$i];
            $chartData[] = $counts[$currentDate] ?? 0;
            $deptSeries['general'][]    = $deptCounts['general'][$currentDate] ?? 0;
            $deptSeries['neurology'][]  = $deptCounts['neurology'][$currentDate] ?? 0;
            $deptSeries['psychiatry'][] = $deptCounts['psychiatry'][$currentDate] ?? 0;
        }
    }

    // Logs for Audit view
    $auditLogs = getAdminActivity($pdo, 50);

    // Today's OPD summary (scheduled for today)
    $todaySummary = ['total' => 0, 'waiting' => 0, 'ongoing' => 0, 'completed' => 0];
    try {
        $tsStmt = $pdo->query("
            SELECT LOWER(COALESCE(status, '')) AS st, COUNT(*) AS cnt
            FROM patients
            WHERE appointment_date = CURDATE() AND COALESCE(inquiry_only, 0) = 0
            GROUP BY st
        ");
        foreach ($tsStmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $st  = $r['st'];
            $cnt = (int)$r['cnt'];
            if (in_array($st, ['cancelled', 'rejected', 'cancelled_by_patient', 'no-show'], true)) continue;
            $todaySummary['total'] += $cnt;
            if (in_array($st, ['pending', 'for_approval', 'for_processing', 'approved', 'confirmed', 'arrived', 'checked in', 'rescheduled'], true)) {
                $todaySummary['waiting'] += $cnt;
            } elseif (in_array($st, ['in_clinic', 'ongoing', 'in_consultation', 'in consultation'], true)) {
                $todaySummary['ongoing'] += $cnt;
            } elseif ($st === 'completed') {
                $todaySummary['completed'] += $cnt;
            }
        }
    } catch (Throwable $ignore) {}

    // Live Today's Department Capacity vs Scheduling Rules
    $deptLimits = [
        'general'    => ['name' => 'General Medical', 'limit' => 60],
        'neurology'  => ['name' => 'Neurology',       'limit' => 30],
        'psychiatry' => ['name' => 'Psychiatry',      'limit' => 40],
    ];
    try {
        $ruleRows = $pdo->query("SELECT department_code, display_name, daily_capacity_limit FROM department_scheduling_rules")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($ruleRows as $rr) {
            $code = strtolower(trim((string)$rr['department_code']));
            if (isset($deptLimits[$code])) {
                $deptLimits[$code]['name']  = $rr['display_name'] ?: $deptLimits[$code]['name'];
                $deptLimits[$code]['limit'] = max(1, (int)$rr['daily_capacity_limit']);
            }
        }
    } catch (Throwable $ignore) {}

    $bookedTodayByDept = ['general' => 0, 'neurology' => 0, 'psychiatry' => 0];
    try {
        $bkStmt = $pdo->query("
            SELECT department, COUNT(*) AS cnt
            FROM patients
            WHERE appointment_date = CURDATE()
              AND COALESCE(inquiry_only, 0) = 0
              AND LOWER(COALESCE(status, '')) NOT IN ('cancelled', 'rejected', 'cancelled_by_patient', 'no-show')
            GROUP BY department
        ");
        foreach ($bkStmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $dk = $classifyDept($r['department']);
            $bookedTodayByDept[$dk] = ($bookedTodayByDept[$dk] ?? 0) + (int)$r['cnt'];
        }
    } catch (Throwable $ignore) {}

    $deptCapacityToday = [];
    foreach ($deptLimits as $code => $meta) {
        $booked = $bookedTodayByDept[$code] ?? 0;
        $limit  = $meta['limit'];
        $deptCapacityToday[] = [
            'code'    => $code,
            'name'    => $meta['name'],
            'booked'  => $booked,
            'limit'   => $limit,
            'percent' => min(100, (int)round(($booked / max(1, $limit)) * 100)),
        ];
    }

    respondOk([
        'chart_labels' => $chartLabels,
        'chart_data' => $chartData,
        'chart_datasets' => $deptSeries,
        'active_staff' => $staffStats['active_staff'] ?? 0,
        'todays_patients' => $todaysPatients,
        'today_summary' => $todaySummary,
        'department_capacity_today' => $deptCapacityToday,
        'pending_actions' => $pendingActions,
        'pending_appointments' => $pendingAppointments,
        'pending_inquiries' => $pendingInquiries,
        'unassigned_upcoming' => $unassignedUpcoming,
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
