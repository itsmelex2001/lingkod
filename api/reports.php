<?php
/**
 * api/reports.php
 *
 * Operational Reporting & Upcoming Patient Analytics Engine.
 * Pulls and consolidates patient records across:
 *   - General Medical
 *   - Neurology
 *   - OPU Psychiatry
 *
 * Supports upcoming horizons (today, next_7, next_30) as well as monthly/all-time views,
 * Morning vs Afternoon time-block distribution, physician readiness checks, and CSV export.
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

function normalizeDepartmentName(?string $raw): string {
    $clean = strtolower(trim($raw ?? ''));
    if (strpos($clean, 'psych') !== false || strpos($clean, 'opu') !== false) {
        return 'Psychiatry';
    }
    if (strpos($clean, 'neuro') !== false) {
        return 'Neurology';
    }
    return 'General Medical';
}

function resolveTimeBlock(?string $preferredSlot, ?string $scheduledTime): string {
    $pref = strtolower(trim($preferredSlot ?? ''));
    if (strpos($pref, 'morning') !== false) return 'Morning';
    if (strpos($pref, 'afternoon') !== false) return 'Afternoon';
    if (!empty($scheduledTime)) {
        $hour = (int)substr((string)$scheduledTime, 0, 2);
        return $hour >= 12 ? 'Afternoon' : 'Morning';
    }
    return 'Morning';
}

$range      = trim($_GET['range'] ?? 'today'); // 'today', 'past_7', 'past_30', 'past_month', 'next_7', 'next_30', 'monthly', 'custom', 'all'
$deptFilter = trim($_GET['department'] ?? 'all');
$startDate  = trim($_GET['start_date'] ?? '');
$endDate    = trim($_GET['end_date'] ?? '');
$exportCsv  = !empty($_GET['export_csv']);

$dateExpr = "COALESCE(DATE(medical_history_date), appointment_date, DATE(created_at))";
$whereParts = ["1=1"];
$params = [];

if ($startDate !== '' || $endDate !== '') {
    if ($startDate !== '') {
        $whereParts[] = "$dateExpr >= ?";
        $params[] = $startDate;
    }
    if ($endDate !== '') {
        $whereParts[] = "$dateExpr <= ?";
        $params[] = $endDate;
    }
} elseif ($range === 'today' || $range === 'daily') {
    $whereParts[] = "($dateExpr = CURDATE() OR appointment_date = CURDATE())";
} elseif ($range === 'past_7') {
    $whereParts[] = "$dateExpr BETWEEN DATE_SUB(CURDATE(), INTERVAL 6 DAY) AND CURDATE()";
} elseif ($range === 'past_30') {
    $whereParts[] = "$dateExpr BETWEEN DATE_SUB(CURDATE(), INTERVAL 29 DAY) AND CURDATE()";
} elseif ($range === 'past_month') {
    $whereParts[] = "$dateExpr BETWEEN DATE_SUB(CURDATE(), INTERVAL 1 MONTH) AND CURDATE()";
} elseif ($range === 'next_7') {
    $whereParts[] = "COALESCE(appointment_date, DATE(created_at)) BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 6 DAY)";
} elseif ($range === 'next_30') {
    $whereParts[] = "COALESCE(appointment_date, DATE(created_at)) BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 29 DAY)";
} elseif ($range === 'monthly') {
    $whereParts[] = "DATE_FORMAT($dateExpr, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')";
}

if ($deptFilter !== '' && $deptFilter !== 'all') {
    $cleanDept = strtolower($deptFilter);
    if (strpos($cleanDept, 'psych') !== false || strpos($cleanDept, 'opu') !== false) {
        $whereParts[] = "(LOWER(department) LIKE '%psych%' OR LOWER(department) LIKE '%opu%')";
    } elseif (strpos($cleanDept, 'neuro') !== false) {
        $whereParts[] = "LOWER(department) LIKE '%neuro%'";
    } else {
        $whereParts[] = "(LOWER(department) LIKE '%general%' OR LOWER(department) LIKE '%cardio%' OR LOWER(department) LIKE '%medical%')";
    }
}

$whereClause = implode(" AND ", $whereParts);

// Safely build optional column projections
$refColExpr = 'NULL AS reference_no';
$slotColExpr = 'NULL AS preferred_time_slot';
$inqColExpr = '0 AS inquiry_only';
$modeInqColExpr = 'NULL AS mode_of_inquiry';
try {
    $cols = $pdo->query("SHOW COLUMNS FROM patients")->fetchAll(PDO::FETCH_COLUMN);
    if (in_array('reference_no', $cols, true)) $refColExpr = 'reference_no';
    if (in_array('preferred_time_slot', $cols, true)) $slotColExpr = 'preferred_time_slot';
    if (in_array('inquiry_only', $cols, true)) $inqColExpr = 'inquiry_only';
    if (in_array('mode_of_inquiry', $cols, true)) $modeInqColExpr = 'mode_of_inquiry';
} catch (Throwable $ignoreCols) {}

$patientSql = "SELECT id, patient_id, $refColExpr, full_name, contact_number, department, status,
                      appointment_date, scheduled_time, $slotColExpr, patient_type,
                      appointment_type, unscheduled_walkin, $inqColExpr, $modeInqColExpr,
                      assigned_doctor, specialty, chief_complaints,
                      medical_history_diagnosis, medical_history_treatment, medical_history_prescription, active_medications,
                      medical_history_date, reminder_status, reminder_sent_at, created_at,
                      COALESCE(DATE(medical_history_date), appointment_date, DATE(created_at)) AS record_date
               FROM patients
               WHERE $whereClause
               ORDER BY record_date DESC, COALESCE(scheduled_time, '08:00:00') ASC, id DESC";

$stmt = $pdo->prepare($patientSql);
$stmt->execute($params);
$allPatients = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalPatients       = count($allPatients);
$completedCount      = 0;
$walkinCount         = 0;
$onlineCount         = 0;
$noshowCount         = 0;
$ongoingCount        = 0;
$forApprovalCount    = 0;
$approvedCount       = 0;
$needsDoctorCount    = 0;
$confirmedReadyCount = 0;
$inClinicCount       = 0;
$inquiryCount        = 0;
$morningCount        = 0;
$afternoonCount      = 0;
$assignedDoctorCount = 0;
$upcomingTotalCount  = 0;

$deptTemplate = fn(string $name): array => [
    'name'             => $name,
    'total'            => 0,
    'upcoming'         => 0,
    'needs_action'     => 0,
    'needs_approval'   => 0,
    'needs_doctor'     => 0,
    'confirmed_ready'  => 0,
    'completed'        => 0,
    'walkin'           => 0,
    'online'           => 0,
    'noshow'           => 0,
    'ongoing'          => 0,
    'for_approval'     => 0,
    'morning'          => 0,
    'afternoon'        => 0,
    'completion_rate'  => 0.0,
];

$deptStats = [
    'General Medical' => $deptTemplate('General Medical'),
    'Neurology'       => $deptTemplate('Neurology'),
    'Psychiatry'      => $deptTemplate('Psychiatry'),
];

$dailyAggregation   = [];
$monthlyAggregation = [];
$chartBuckets       = [];
$upcomingList       = [];

// Pre-fill upcoming 7-day chart buckets so quiet days remain visible in the timeline
if ($range === 'next_7') {
    for ($i = 0; $i < 7; $i++) {
        $dObj = new DateTime("+$i day");
        $dIso = $dObj->format('Y-m-d');
        $chartBuckets[$dIso] = [
            'key'        => $dIso,
            'label'      => $dObj->format('D M j'),
            'general'    => 0,
            'neurology'  => 0,
            'psychiatry' => 0,
            'total'      => 0,
            'morning'    => 0,
            'afternoon'  => 0,
        ];
    }
}

$useMonthlyChart = ($range === 'all');

foreach ($allPatients as &$p) {
    $normDept = normalizeDepartmentName($p['department']);
    $p['normalized_department'] = $normDept;

    $isInquiry = !empty($p['inquiry_only'])
        || strcasecmp(trim((string)($p['appointment_type'] ?? '')), 'inquiry') === 0
        || strpos((string)($p['patient_id'] ?? ''), 'INQ-') === 0;
    $isWalkin = !$isInquiry && (!empty($p['unscheduled_walkin']) || strtolower($p['appointment_type'] ?? '') === 'walk-in');
    $p['is_walkin'] = $isWalkin ? 1 : 0;

    $st = strtolower(trim($p['status'] ?? 'pending'));
    $recDate = $p['record_date'] ?: date('Y-m-d');
    $monthKey = substr($recDate, 0, 7);
    $timeBlock = resolveTimeBlock($p['preferred_time_slot'] ?? null, $p['scheduled_time'] ?? null);
    $docRaw = trim((string)($p['assigned_doctor'] ?? ''));
    $hasDoctor = $docRaw !== '' && strcasecmp($docRaw, 'None') !== 0;

    $isCancelledOrDone = in_array($st, ['completed', 'no-show', 'rejected', 'cancelled', 'transferred'], true);
    $isWaitingApproval = in_array($st, ['pending', 'for_approval', 'for_processing', 'for approval'], true);
    $isConfirmed       = in_array($st, ['approved', 'confirmed'], true);
    $isInClinic        = in_array($st, ['arrived', 'ongoing'], true);

    $p['time_block'] = $timeBlock;

    if ($isInquiry) {
        $inquiryCount++;
    }

    if ($st === 'completed') {
        $completedCount++;
    } elseif (in_array($st, ['no-show', 'rejected', 'cancelled'], true)) {
        $noshowCount++;
    } elseif ($isInClinic) {
        $ongoingCount++;
        $inClinicCount++;
        $confirmedReadyCount++;
    } elseif ($isConfirmed) {
        $approvedCount++;
        $ongoingCount++;
        $confirmedReadyCount++;
    } else {
        $forApprovalCount++;
    }

    if (!$isCancelledOrDone) {
        $upcomingTotalCount++;
        if (!$isInquiry && !$hasDoctor) {
            $needsDoctorCount++;
        }
        if ($hasDoctor) {
            $assignedDoctorCount++;
        }
        if ($timeBlock === 'Afternoon') {
            $afternoonCount++;
        } else {
            $morningCount++;
        }

        if ($isInquiry) {
            $readinessState = 'inquiry';
            $readinessLabel = 'Inquiry Follow-up';
        } elseif ($isWaitingApproval) {
            $readinessState = $hasDoctor ? 'needs_approval' : 'needs_doctor';
            $readinessLabel = $hasDoctor ? 'Needs Approval' : 'Assign Doctor & Approve';
        } elseif ($isInClinic) {
            $readinessState = 'in_clinic';
            $readinessLabel = $st === 'ongoing' ? 'In Consultation' : 'Checked In';
        } else {
            $readinessState = $hasDoctor ? 'ready' : 'needs_doctor';
            $readinessLabel = $hasDoctor ? 'Ready for Visit' : 'Needs Physician';
        }

        $p['readiness_state'] = $readinessState;
        $p['readiness_label'] = $readinessLabel;
        $upcomingList[] = $p;
    }

    if ($isWalkin) {
        $walkinCount++;
    } elseif (!$isInquiry) {
        $onlineCount++;
    }

    if (isset($deptStats[$normDept])) {
        $deptStats[$normDept]['total']++;
        if ($isWalkin) $deptStats[$normDept]['walkin']++;
        elseif (!$isInquiry) $deptStats[$normDept]['online']++;

        if (!$isCancelledOrDone) {
            $deptStats[$normDept]['upcoming']++;
            if ($timeBlock === 'Afternoon') $deptStats[$normDept]['afternoon']++;
            else $deptStats[$normDept]['morning']++;

            if ($isWaitingApproval || (!$isInquiry && !$hasDoctor)) {
                $deptStats[$normDept]['needs_action']++;
            }
            if ($isWaitingApproval) {
                $deptStats[$normDept]['needs_approval']++;
            }
            if (!$isInquiry && !$hasDoctor) {
                $deptStats[$normDept]['needs_doctor']++;
            }
            if ($isConfirmed || $isInClinic) {
                $deptStats[$normDept]['confirmed_ready']++;
            }
        }

        if ($st === 'completed') $deptStats[$normDept]['completed']++;
        elseif (in_array($st, ['no-show', 'rejected', 'cancelled'], true)) $deptStats[$normDept]['noshow']++;
        elseif ($isInClinic || $isConfirmed) $deptStats[$normDept]['ongoing']++;
        elseif ($isWaitingApproval) $deptStats[$normDept]['for_approval']++;
    }

    // Chart bucket (skip cancelled/transferred when focusing on upcoming load)
    if ($range === 'all' || !$isCancelledOrDone || $st === 'completed') {
        $bKey = $useMonthlyChart ? $monthKey : $recDate;
        if (!isset($chartBuckets[$bKey])) {
            $bLabel = $bKey;
            if ($useMonthlyChart) {
                $mObj = DateTime::createFromFormat('Y-m', $bKey);
                $bLabel = $mObj ? $mObj->format('M Y') : $bKey;
            } else {
                $dObj = DateTime::createFromFormat('Y-m-d', $bKey);
                $bLabel = $dObj ? $dObj->format('D M j') : $bKey;
            }
            $chartBuckets[$bKey] = [
                'key'        => $bKey,
                'label'      => $bLabel,
                'general'    => 0,
                'neurology'  => 0,
                'psychiatry' => 0,
                'total'      => 0,
                'morning'    => 0,
                'afternoon'  => 0,
            ];
        }
        $chartBuckets[$bKey]['total']++;
        if ($normDept === 'Neurology') $chartBuckets[$bKey]['neurology']++;
        elseif ($normDept === 'Psychiatry') $chartBuckets[$bKey]['psychiatry']++;
        else $chartBuckets[$bKey]['general']++;
        if ($timeBlock === 'Afternoon') $chartBuckets[$bKey]['afternoon']++;
        else $chartBuckets[$bKey]['morning']++;
    }

    // Daily breakdown
    $dailyKey = $recDate . '___' . $normDept;
    if (!isset($dailyAggregation[$dailyKey])) {
        $dailyAggregation[$dailyKey] = [
            'date'       => $recDate,
            'department' => $normDept,
            'total'      => 0,
            'upcoming'   => 0,
            'online'     => 0,
            'walkin'     => 0,
            'completed'  => 0,
            'ongoing'    => 0,
            'noshow'     => 0
        ];
    }
    $dailyAggregation[$dailyKey]['total']++;
    if (!$isCancelledOrDone) $dailyAggregation[$dailyKey]['upcoming']++;
    if ($isWalkin) $dailyAggregation[$dailyKey]['walkin']++;
    elseif (!$isInquiry) $dailyAggregation[$dailyKey]['online']++;
    if ($st === 'completed') $dailyAggregation[$dailyKey]['completed']++;
    elseif (in_array($st, ['no-show', 'rejected', 'cancelled'], true)) $dailyAggregation[$dailyKey]['noshow']++;
    elseif ($isInClinic || $isConfirmed) $dailyAggregation[$dailyKey]['ongoing']++;

    // Monthly breakdown
    $monthlyKey = $monthKey . '___' . $normDept;
    if (!isset($monthlyAggregation[$monthlyKey])) {
        $monthlyAggregation[$monthlyKey] = [
            'month'      => $monthKey,
            'department' => $normDept,
            'total'      => 0,
            'upcoming'   => 0,
            'online'     => 0,
            'walkin'     => 0,
            'completed'  => 0,
            'ongoing'    => 0,
            'noshow'     => 0
        ];
    }
    $monthlyAggregation[$monthlyKey]['total']++;
    if (!$isCancelledOrDone) $monthlyAggregation[$monthlyKey]['upcoming']++;
    if ($isWalkin) $monthlyAggregation[$monthlyKey]['walkin']++;
    elseif (!$isInquiry) $monthlyAggregation[$monthlyKey]['online']++;
    if ($st === 'completed') $monthlyAggregation[$monthlyKey]['completed']++;
    elseif (in_array($st, ['no-show', 'rejected', 'cancelled'], true)) $monthlyAggregation[$monthlyKey]['noshow']++;
    elseif ($isInClinic || $isConfirmed) $monthlyAggregation[$monthlyKey]['ongoing']++;
}
unset($p);

foreach ($deptStats as &$ds) {
    $ds['completion_rate'] = $ds['total'] > 0 ? round(($ds['completed'] / $ds['total']) * 100, 1) : 0.0;
}
unset($ds);

$needsActionCount = count(array_filter($upcomingList, function ($item) {
    return in_array($item['readiness_state'] ?? '', ['needs_approval', 'needs_doctor', 'inquiry'], true);
}));

$completionRate = $totalPatients > 0 ? round(($completedCount / $totalPatients) * 100, 1) : 0.0;
$noshowRate     = $totalPatients > 0 ? round(($noshowCount / $totalPatients) * 100, 1) : 0.0;
$walkinRatio    = $totalPatients > 0 ? round(($walkinCount / $totalPatients) * 100, 1) : 0.0;

ksort($chartBuckets);
if ($range === 'next_7') {
    krsort($dailyAggregation);
} else {
    krsort($dailyAggregation);
}
krsort($monthlyAggregation);

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

// CSV Export Handler
if ($exportCsv) {
    if (ob_get_length()) {
        ob_end_clean();
    }
    $qfFilter = trim($_GET['qf'] ?? 'all');
    $qSearch  = strtolower(trim($_GET['q'] ?? ''));

    $exportRows = ($range === 'all' && $qfFilter === 'all') ? $allPatients : $upcomingList;
    if ($qfFilter === 'needs_action') {
        $exportRows = array_values(array_filter($upcomingList, function ($item) {
            return in_array($item['readiness_state'] ?? '', ['needs_approval', 'needs_doctor', 'inquiry'], true);
        }));
    } elseif ($qfFilter === 'ready') {
        $exportRows = array_values(array_filter($upcomingList, function ($item) {
            return in_array($item['readiness_state'] ?? '', ['ready', 'in_clinic'], true);
        }));
    }

    if ($qSearch !== '') {
        $exportRows = array_values(array_filter($exportRows, function ($item) use ($qSearch) {
            return str_contains(strtolower((string)($item['full_name'] ?? '')), $qSearch)
                || str_contains(strtolower((string)($item['patient_id'] ?? '')), $qSearch)
                || str_contains(strtolower((string)($item['assigned_doctor'] ?? '')), $qSearch)
                || str_contains(strtolower((string)($item['normalized_department'] ?? $item['department'] ?? '')), $qSearch);
        }));
    }

    $rangeNames = [
        'today'   => 'Today',
        'next_7'  => 'Next 7 Days',
        'next_30' => 'Next 30 Days',
        'monthly' => 'This Month',
        'all'     => 'All Time'
    ];
    $scopeRangeLabel = $rangeNames[$range] ?? ucfirst(str_replace('_', ' ', $range));
    $scopeDeptLabel  = ($deptFilter === '' || strtolower($deptFilter) === 'all') ? 'All Departments' : normalizeDepartmentName($deptFilter);
    $scopeQfLabel    = $qfFilter === 'needs_action' ? 'Needs Action Only' : ($qfFilter === 'ready' ? 'Ready / In Clinic Only' : 'All Operational Records');

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="MMWGH_Report_' . preg_replace('/[^a-z0-9_]/i', '', $range) . '_' . date('Ymd_His') . '.csv"');
    $out = fopen('php://output', 'w');
    // UTF-8 BOM for clean Excel rendering
    fwrite($out, "\xEF\xBB\xBF");

    fputcsv($out, ['MARIVELES MENTAL WELLNESS AND GENERAL HOSPITAL'], ',', '"', '\\');
    fputcsv($out, ['OPD UPCOMING PATIENT OPERATIONS & ANALYTICS REPORT'], ',', '"', '\\');
    fputcsv($out, ['Period Scope', $scopeRangeLabel, 'Department Scope', $scopeDeptLabel, 'Readiness Filter', $scopeQfLabel, 'Generated', date('M d, Y h:i A')], ',', '"', '\\');
    fputcsv($out, ['Upcoming Total', $upcomingTotalCount, 'Needs Action', $needsActionCount, 'Ready / In Clinic', $confirmedReadyCount, 'Completed', $completedCount, 'No-Show / Cancelled', $noshowCount], ',', '"', '\\');
    fputcsv($out, [], ',', '"', '\\');

    fputcsv($out, [
        'Patient / Ref ID', 'Patient Full Name', 'Contact Number', 'Department',
        'Scheduled Date', 'Time Block', 'Visit Mode', 'Status',
        'Readiness State', 'Attending Physician', 'Clinical Notes / Diagnosis'
    ], ',', '"', '\\');

    foreach ($exportRows as $row) {
        $normDept = $row['normalized_department'] ?? normalizeDepartmentName($row['department']);
        $isInquiry = !empty($row['inquiry_only'])
            || strcasecmp(trim((string)($row['appointment_type'] ?? '')), 'inquiry') === 0
            || strpos((string)($row['patient_id'] ?? ''), 'INQ-') === 0;
        $isWalkin = !$isInquiry && !empty($row['is_walkin']);
        $recDateRaw = $row['record_date'] ?: date('Y-m-d');
        $dObj = DateTime::createFromFormat('Y-m-d', $recDateRaw);
        $recDateFmt = $dObj ? $dObj->format('M d, Y') : $recDateRaw;
        $tb = $row['time_block'] ?? resolveTimeBlock($row['preferred_time_slot'] ?? null, $row['scheduled_time'] ?? null);
        $tbFmt = $tb === 'Afternoon' ? 'Afternoon (1:00 PM - 5:00 PM)' : 'Morning (8:00 AM - 12:00 PM)';
        $modeLabel = $isInquiry ? 'Inquiry' : ($isWalkin ? 'Walk-in' : 'Online Booking');
        $docLabel = $isInquiry ? 'General Inquiry' : ((!empty($row['assigned_doctor']) && strcasecmp((string)$row['assigned_doctor'], 'None') !== 0) ? $row['assigned_doctor'] : 'Unassigned Physician');
        $readinessText = $row['readiness_label'] ?? ucwords(str_replace(['_', '-'], ' ', (string)($row['status'] ?? 'Pending')));
        $notesText = trim((string)($row['medical_history_diagnosis'] ?? ''));
        if ($notesText !== '' && !empty($row['medical_history_treatment'])) {
            $notesText .= ' | ' . trim((string)$row['medical_history_treatment']);
        }

        fputcsv($out, [
            $row['patient_id'],
            $row['full_name'],
            $row['contact_number'] ?? '',
            $normDept,
            $recDateFmt,
            $tbFmt,
            $modeLabel,
            ucwords(str_replace(['_', '-'], ' ', (string)($row['status'] ?? ''))),
            $readinessText,
            $docLabel,
            $notesText
        ], ',', '"', '\\');
    }
    fclose($out);
    exit;
}

// Build Consultation Report Summary: Unique Doctors & Prescribed Medications Tally
$doctorTally = [];
$medicationTally = [];
foreach ($allPatients as $rec) {
    $docName = trim((string)($rec['assigned_doctor'] ?? ''));
    if ($docName !== '' && strcasecmp($docName, 'None') !== 0) {
        $doctorTally[$docName] = ($doctorTally[$docName] ?? 0) + 1;
    }
    $rawMeds = trim((string)($rec['active_medications'] ?? ''));
    if ($rawMeds !== '') {
        $decoded = json_decode($rawMeds, true);
        if (is_array($decoded)) {
            $items = isset($decoded['medicine_name']) ? [$decoded] : $decoded;
            foreach ($items as $mItem) {
                $mName = is_array($mItem) ? trim((string)($mItem['medicine_name'] ?? ($mItem['name'] ?? ''))) : trim((string)$mItem);
                if ($mName !== '') {
                    $medicationTally[$mName] = ($medicationTally[$mName] ?? 0) + 1;
                }
            }
        }
    }
}
arsort($doctorTally);
arsort($medicationTally);
$totalDoctorsActive = count($doctorTally);
$totalMedicationsPrescribed = array_sum($medicationTally);

$payload = [
    'range'   => $range,
    'summary' => [
        'total_patients'               => $totalPatients,
        'total_doctors_active'         => $totalDoctorsActive,
        'total_medications_prescribed' => $totalMedicationsPrescribed,
        'total_upcoming'               => $upcomingTotalCount,
        'total_needs_action'           => $needsActionCount,
        'total_needs_doctor'           => $needsDoctorCount,
        'total_for_approval'           => $forApprovalCount,
        'total_confirmed_ready'        => $confirmedReadyCount,
        'total_in_clinic'              => $inClinicCount,
        'total_completed'              => $completedCount,
        'total_walkin'                 => $walkinCount,
        'total_online'                 => $onlineCount,
        'total_noshow'                 => $noshowCount,
        'total_ongoing'                => $ongoingCount,
        'total_approved'               => $approvedCount,
        'total_inquiries'              => $inquiryCount,
        'morning_count'                => $morningCount,
        'afternoon_count'              => $afternoonCount,
        'assigned_doctor_count'        => $assignedDoctorCount,
        'completion_rate'              => $completionRate,
        'completion_rate_text'         => $completionRate . '%',
        'noshow_rate'                  => $noshowRate,
        'noshow_rate_text'             => $noshowRate . '%',
        'walkin_ratio'                 => $walkinRatio,
        'walkin_ratio_text'            => $walkinRatio . '%'
    ],
    'consultation_report' => [
        'patients_count'     => $totalPatients,
        'completed_count'    => $completedCount,
        'doctors_count'      => $totalDoctorsActive,
        'medications_count'  => $totalMedicationsPrescribed,
        'doctors_breakdown'  => array_map(fn($k, $v) => ['doctor' => $k, 'patients' => $v], array_keys($doctorTally), array_values($doctorTally)),
        'medications_breakdown' => array_map(fn($k, $v) => ['medication' => $k, 'count' => $v], array_keys($medicationTally), array_values($medicationTally))
    ],
    'departments'       => array_values($deptStats),
    'chart_series'      => array_values($chartBuckets),
    'upcoming_patients' => array_slice($upcomingList, 0, 150),
    'daily_reports'     => $dailyList,
    'monthly_reports'   => $monthlyList,
    'recent_records'    => array_slice($allPatients, 0, 150)
];

respondOk(array_merge(['data' => $payload], $payload));
