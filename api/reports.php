<?php
/**
 * api/reports.php
 *
 * Comprehensive Admin Consolidated Reporting & Analytics Engine.
 * Pulls and consolidates patient records from all 3 core hospital departments:
 *   - General Medical
 *   - Neurology
 *   - OPU Psychiatry
 *
 * Supports:
 *   - Consolidated shared KPI metrics
 *   - Department-level comparative breakdowns
 *   - Daily statistical report aggregation
 *   - Monthly statistical report aggregation
 *   - CSV export / printable records
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/config/cors.php';
require_once __DIR__ . '/config/db.php';

requireAuth();

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method !== 'GET') {
    respondErr('Method not allowed.', 405);
}

// Ensure caller is Admin or authorized staff
$role = $_SESSION['role'] ?? 'staff';

// Standardized Department Normalizer
function normalizeDepartmentName(?string $raw): string {
    $clean = strtolower(trim($raw ?? ''));
    if (strpos($clean, 'psych') !== false || strpos($clean, 'opu') !== false) {
        return 'Psychiatry';
    }
    if (strpos($clean, 'neuro') !== false) {
        return 'Neurology';
    }
    if (strpos($clean, 'general') !== false || strpos($clean, 'cardio') !== false) {
        return 'General Medical';
    }
    return 'General Medical';
}

$range      = trim($_GET['range'] ?? 'all'); // 'daily', 'monthly', 'all'
$deptFilter = trim($_GET['department'] ?? 'all');
$startDate  = trim($_GET['start_date'] ?? '');
$endDate    = trim($_GET['end_date'] ?? '');
$exportCsv  = !empty($_GET['export_csv']);

// Base Date Filter Clause
$whereParts = ["1=1"];
$params = [];

if ($range === 'daily') {
    $whereParts[] = "COALESCE(appointment_date, DATE(created_at)) = CURDATE()";
} elseif ($range === 'monthly') {
    $whereParts[] = "DATE_FORMAT(COALESCE(appointment_date, DATE(created_at)), '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')";
}

if ($startDate !== '') {
    $whereParts[] = "COALESCE(appointment_date, DATE(created_at)) >= ?";
    $params[] = $startDate;
}
if ($endDate !== '') {
    $whereParts[] = "COALESCE(appointment_date, DATE(created_at)) <= ?";
    $params[] = $endDate;
}

if ($deptFilter !== '' && $deptFilter !== 'all') {
    $cleanDept = strtolower($deptFilter);
    if (strpos($cleanDept, 'psych') !== false) {
        $whereParts[] = "LOWER(department) LIKE '%psych%'";
    } elseif (strpos($cleanDept, 'neuro') !== false) {
        $whereParts[] = "LOWER(department) LIKE '%neuro%'";
    } else {
        $whereParts[] = "(LOWER(department) LIKE '%general%' OR LOWER(department) LIKE '%cardio%')";
    }
}

$whereClause = implode(" AND ", $whereParts);

// ── 1. Fetch Consolidated Patient Records ────────────────────────────────────
$patientSql = "SELECT id, patient_id, full_name, department, status, appointment_date, 
                      scheduled_time, patient_type, appointment_type, unscheduled_walkin,
                      assigned_doctor, specialty, chief_complaints,
                      medical_history_diagnosis, medical_history_treatment,
                      reminder_status, reminder_sent_at, created_at,
                      COALESCE(appointment_date, DATE(created_at)) AS record_date
               FROM patients
               WHERE $whereClause
               ORDER BY record_date DESC, id DESC";

$stmt = $pdo->prepare($patientSql);
$stmt->execute($params);
$allPatients = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ── 2. Calculate Consolidated Shared Statistics ──────────────────────────────
$totalPatients = count($allPatients);
$completedCount = 0;
$walkinCount = 0;
$onlineCount = 0;
$noshowCount = 0;
$ongoingCount = 0;
$forApprovalCount = 0;
$approvedCount = 0;

$deptStats = [
    'General Medical' => [
        'name' => 'General Medical',
        'total' => 0,
        'completed' => 0,
        'walkin' => 0,
        'online' => 0,
        'noshow' => 0,
        'ongoing' => 0,
        'for_approval' => 0,
        'completion_rate' => 0.0
    ],
    'Neurology' => [
        'name' => 'Neurology',
        'total' => 0,
        'completed' => 0,
        'walkin' => 0,
        'online' => 0,
        'noshow' => 0,
        'ongoing' => 0,
        'for_approval' => 0,
        'completion_rate' => 0.0
    ],
    'Psychiatry' => [
        'name' => 'Psychiatry',
        'total' => 0,
        'completed' => 0,
        'walkin' => 0,
        'online' => 0,
        'noshow' => 0,
        'ongoing' => 0,
        'for_approval' => 0,
        'completion_rate' => 0.0
    ]
];

$dailyAggregation = [];
$monthlyAggregation = [];

foreach ($allPatients as &$p) {
    $normDept = normalizeDepartmentName($p['department']);
    $p['normalized_department'] = $normDept;
    $isWalkin = !empty($p['unscheduled_walkin']) || strtolower($p['appointment_type'] ?? '') === 'walk-in';
    $p['is_walkin'] = $isWalkin ? 1 : 0;

    $st = strtolower($p['status'] ?? 'pending');
    $recDate = $p['record_date'] ?: date('Y-m-d');
    $monthKey = substr($recDate, 0, 7);

    // Global totals
    if ($st === 'completed') {
        $completedCount++;
    } elseif ($st === 'no-show' || $st === 'rejected') {
        $noshowCount++;
    } elseif ($st === 'ongoing' || $st === 'arrived') {
        $ongoingCount++;
    } elseif ($st === 'approved') {
        $approvedCount++;
        $ongoingCount++;
    } else {
        $forApprovalCount++;
    }

    if ($isWalkin) {
        $walkinCount++;
    } else {
        $onlineCount++;
    }

    // Department-level totals
    if (isset($deptStats[$normDept])) {
        $deptStats[$normDept]['total']++;
        if ($isWalkin) $deptStats[$normDept]['walkin']++;
        else $deptStats[$normDept]['online']++;

        if ($st === 'completed') $deptStats[$normDept]['completed']++;
        elseif ($st === 'no-show' || $st === 'rejected') $deptStats[$normDept]['noshow']++;
        elseif ($st === 'ongoing' || $st === 'arrived' || $st === 'approved') $deptStats[$normDept]['ongoing']++;
        elseif ($st === 'for_approval' || $st === 'for_processing' || $st === 'pending') $deptStats[$normDept]['for_approval']++;
    }

    // Daily breakdown
    $dailyKey = $recDate . '___' . $normDept;
    if (!isset($dailyAggregation[$dailyKey])) {
        $dailyAggregation[$dailyKey] = [
            'date'       => $recDate,
            'department' => $normDept,
            'total'      => 0,
            'online'     => 0,
            'walkin'     => 0,
            'completed'  => 0,
            'ongoing'    => 0,
            'noshow'     => 0
        ];
    }
    $dailyAggregation[$dailyKey]['total']++;
    if ($isWalkin) $dailyAggregation[$dailyKey]['walkin']++;
    else $dailyAggregation[$dailyKey]['online']++;
    if ($st === 'completed') $dailyAggregation[$dailyKey]['completed']++;
    elseif ($st === 'no-show' || $st === 'rejected') $dailyAggregation[$dailyKey]['noshow']++;
    elseif ($st === 'ongoing' || $st === 'arrived' || $st === 'approved') $dailyAggregation[$dailyKey]['ongoing']++;

    // Monthly breakdown
    $monthlyKey = $monthKey . '___' . $normDept;
    if (!isset($monthlyAggregation[$monthlyKey])) {
        $monthlyAggregation[$monthlyKey] = [
            'month'      => $monthKey,
            'department' => $normDept,
            'total'      => 0,
            'online'     => 0,
            'walkin'     => 0,
            'completed'  => 0,
            'ongoing'    => 0,
            'noshow'     => 0
        ];
    }
    $monthlyAggregation[$monthlyKey]['total']++;
    if ($isWalkin) $monthlyAggregation[$monthlyKey]['walkin']++;
    else $monthlyAggregation[$monthlyKey]['online']++;
    if ($st === 'completed') $monthlyAggregation[$monthlyKey]['completed']++;
    elseif ($st === 'no-show' || $st === 'rejected') $monthlyAggregation[$monthlyKey]['noshow']++;
    elseif ($st === 'ongoing' || $st === 'arrived' || $st === 'approved') $monthlyAggregation[$monthlyKey]['ongoing']++;
}
unset($p);

// Calculate Department Completion Rates
foreach ($deptStats as &$ds) {
    $ds['completion_rate'] = $ds['total'] > 0 ? round(($ds['completed'] / $ds['total']) * 100, 1) : 0.0;
}
unset($ds);

$completionRate = $totalPatients > 0 ? round(($completedCount / $totalPatients) * 100, 1) : 0.0;
$noshowRate = $totalPatients > 0 ? round(($noshowCount / $totalPatients) * 100, 1) : 0.0;
$walkinRatio = $totalPatients > 0 ? round(($walkinCount / $totalPatients) * 100, 1) : 0.0;

// Sort aggregations descending by date
krsort($dailyAggregation);
krsort($monthlyAggregation);

// Add calculated rates to daily and monthly tables
$dailyList = array_values($dailyAggregation);
foreach ($dailyList as &$d) {
    $d['completion_rate'] = $d['total'] > 0 ? round(($d['completed'] / $d['total']) * 100, 1) : 0.0;
    $dateObj = DateTime::createFromFormat('Y-m-d', $d['date']);
    $d['formatted_date'] = $dateObj ? $dateObj->format('M d, Y') : $d['date'];
}
unset($d);

$monthlyList = array_values($monthlyAggregation);
foreach ($monthlyList as &$m) {
    $m['completion_rate'] = $m['total'] > 0 ? round(($m['completed'] / $m['total']) * 100, 1) : 0.0;
    $dateObj = DateTime::createFromFormat('Y-m', $m['month']);
    $m['month_label'] = $dateObj ? $dateObj->format('F Y') : $m['month'];
}
unset($m);

// ── 3. CSV Export Handler ───────────────────────────────────────────────────
if ($exportCsv) {
    if (ob_get_length()) {
        ob_end_clean();
    }
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="MMWGH_All_Time_Report_' . date('Ymd_His') . '.csv"');
    $out = fopen('php://output', 'w');
    
    // Header row
    fputcsv($out, [
        'Patient ID', 'Full Name', 'Department', 'Booking Type', 'Appointment Date',
        'Scheduled Time', 'Status', 'Doctor Assigned', 'Diagnosis', 'Treatment'
    ]);

    foreach ($allPatients as $row) {
        $normDept = $row['normalized_department'] ?? normalizeDepartmentName($row['department']);
        $isWalkin = !empty($row['is_walkin']);
        $recDate = $row['record_date'] ?: date('Y-m-d');
        fputcsv($out, [
            $row['patient_id'],
            $row['full_name'],
            $normDept,
            $isWalkin ? 'Walk-in' : 'Online Booker',
            $recDate,
            $row['scheduled_time'] ?? 'N/A',
            strtoupper($row['status']),
            $row['assigned_doctor'] ?? 'Unassigned',
            $row['medical_history_diagnosis'] ?? '',
            $row['medical_history_treatment'] ?? ''
        ]);
    }
    fclose($out);
    exit;
}

// ── 4. Standard JSON Response ───────────────────────────────────────────────
$payload = [
    'summary' => [
        'total_patients'       => $totalPatients,
        'total_completed'      => $completedCount,
        'total_walkin'         => $walkinCount,
        'total_online'         => $onlineCount,
        'total_noshow'         => $noshowCount,
        'total_ongoing'        => $ongoingCount,
        'total_approved'       => $approvedCount,
        'total_for_approval'   => $forApprovalCount,
        'completion_rate'      => $completionRate,
        'completion_rate_text' => $completionRate . '%',
        'noshow_rate'          => $noshowRate,
        'noshow_rate_text'     => $noshowRate . '%',
        'walkin_ratio'         => $walkinRatio,
        'walkin_ratio_text'    => $walkinRatio . '%'
    ],
    'departments'        => array_values($deptStats),
    'daily_reports'      => $dailyList,
    'monthly_reports'    => $monthlyList,
    'recent_records'     => array_slice($allPatients, 0, 100)
];

respondOk(array_merge(['data' => $payload], $payload));
