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
date_default_timezone_set('Asia/Manila');
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

$category = strtolower(trim($_GET['category'] ?? ''));
if ($category !== '') {
    $allowedCategories = ['appointments', 'patients', 'doctors', 'medication', 'diagnosis'];
    if (!in_array($category, $allowedCategories, true)) {
        respondErr('Invalid report category.', 400);
    }

    $today = date('Y-m-d');
    $from  = trim($_GET['from'] ?? $_GET['start_date'] ?? '');
    $to    = trim($_GET['to'] ?? $_GET['end_date'] ?? '');
    $dept  = trim($_GET['department'] ?? '');
    $exportExcel = !empty($_GET['export_excel']);

    $validDate = static function (string $d): bool {
        if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $d, $m)) return false;
        return checkdate((int)$m[2], (int)$m[3], (int)$m[1]);
    };
    $allowFuture = ($category === 'appointments');
    if (($from !== '' && !$validDate($from)) || ($to !== '' && !$validDate($to)) || ($from !== '' && $to !== '' && $from > $to)) {
        respondErr('Select a valid date range (From date cannot be later than To date).', 422);
    }
    if (!$allowFuture && (($from !== '' && $from > $today) || ($to !== '' && $to > $today))) {
        respondErr('Select a valid past or current date range.', 422);
    }

    $buildDateClause = static function (string $expr) use ($from, $to): array {
        $conds = [];
        $params = [];
        if ($from !== '') {
            $conds[] = "$expr >= ?";
            $params[] = $from;
        }
        if ($to !== '') {
            $conds[] = "$expr <= ?";
            $params[] = $to;
        }
        return [$conds ? implode(' AND ', $conds) : '1=1', $params];
    };

    $deptCondSql = '';
    if ($dept !== '' && strtolower($dept) !== 'all') {
        $dLow = strtolower($dept);
        if (str_contains($dLow, 'psych') || str_contains($dLow, 'opu')) {
            $deptCondSql = " AND (LOWER(department) LIKE '%psych%' OR LOWER(department) LIKE '%opu%')";
        } elseif (str_contains($dLow, 'neuro')) {
            $deptCondSql = " AND LOWER(department) LIKE '%neuro%'";
        } else {
            $deptCondSql = " AND (LOWER(department) LIKE '%general%' OR LOWER(department) LIKE '%cardio%' OR LOWER(department) LIKE '%medical%')";
        }
    }

    $pCols = [];
    try {
        $pCols = dbTableColumns($pdo, 'patients');
    } catch (Throwable $ignore) {}
    $hasCol = static fn(string $c): bool => in_array($c, $pCols, true);

    $medDateExpr  = dbIsPgsql() ? "CAST(medical_history_date AS DATE)" : "NULLIF(DATE(medical_history_date), '0000-00-00')";
    $apptDateExpr = dbIsPgsql() ? "CAST(appointment_date AS DATE)"     : "NULLIF(CAST(appointment_date AS DATE), '0000-00-00')";
    $createdDate  = dbIsPgsql() ? "CAST(created_at AS DATE)"           : "DATE(created_at)";
    $dateExpr     = "COALESCE($medDateExpr, $apptDateExpr, $createdDate)";
    $refSel       = $hasCol('reference_no') ? "COALESCE(NULLIF(reference_no, ''), patient_id)" : 'patient_id';
    $inqCondSql   = $hasCol('inquiry_only') ? ' AND COALESCE(inquiry_only, 0) = 0' : '';
    $genderSel    = $hasCol('gender') ? 'gender' : 'NULL';
    $ageSel       = $hasCol('age') ? 'age' : 'NULL';
    $birthSel     = $hasCol('birthdate') ? 'birthdate' : 'NULL';

    $rows = [];
    $columns = [];

    if ($category === 'diagnosis') {
        $columns = [
            ['key' => 'date', 'label' => 'Date'],
            ['key' => 'patient_name', 'label' => 'Patient'],
            ['key' => 'department', 'label' => 'Department'],
            ['key' => 'diagnosis', 'label' => 'Diagnosis'],
            ['key' => 'doctor_name', 'label' => 'Attending Physician'],
        ];
        [$dateWhere, $dateParams] = $buildDateClause($dateExpr);
        $sql = "SELECT id, $dateExpr AS rec_date, full_name, department, medical_history_diagnosis, assigned_doctor
                FROM patients
                WHERE $dateWhere
                  AND TRIM(COALESCE(medical_history_diagnosis, '')) <> ''
                  $deptCondSql
                ORDER BY rec_date DESC, id DESC
                LIMIT 500";
        $st = $pdo->prepare($sql);
        $st->execute($dateParams);
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $doc = trim((string)($r['assigned_doctor'] ?? ''));
            $rows[] = [
                'id'           => (int)$r['id'],
                'date'         => $r['rec_date'] ?: '—',
                'patient_name' => $r['full_name'] ?: '—',
                'department'   => normalizeDepartmentName($r['department'] ?? ''),
                'diagnosis'    => trim((string)$r['medical_history_diagnosis']),
                'doctor_name'  => ($doc !== '' && strcasecmp($doc, 'None') !== 0) ? $doc : 'Unassigned',
            ];
        }
    } elseif ($category === 'appointments') {
        $columns = [
            ['key' => 'date', 'label' => 'Date'],
            ['key' => 'reference_no', 'label' => 'Ref No.'],
            ['key' => 'patient_name', 'label' => 'Patient'],
            ['key' => 'department', 'label' => 'Department'],
            ['key' => 'status', 'label' => 'Status'],
        ];
        $apptOnlyDate = "COALESCE($apptDateExpr, $createdDate)";
        $bookedOnlySql = " AND LOWER(COALESCE(status, '')) <> 'registered'
                           AND ($apptDateExpr IS NOT NULL
                             OR TRIM(COALESCE(department, '')) <> ''
                             OR TRIM(COALESCE(chief_complaints, '')) <> ''
                             OR TRIM(COALESCE(consultation_nature, '')) <> ''
                             OR COALESCE(unscheduled_walkin, 0) = 1)";
        [$dateWhere, $dateParams] = $buildDateClause($apptOnlyDate);
        $sql = "SELECT id, $apptOnlyDate AS appt_date, $refSel AS ref_no, full_name, department, status
                FROM patients
                WHERE $dateWhere
                  $bookedOnlySql
                  $inqCondSql
                  $deptCondSql
                ORDER BY appt_date DESC, id DESC
                LIMIT 500";
        $st = $pdo->prepare($sql);
        $st->execute($dateParams);
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $rows[] = [
                'id'           => (int)$r['id'],
                'date'         => $r['appt_date'] ?: '—',
                'reference_no' => $r['ref_no'] ?: ('APT-' . $r['id']),
                'patient_name' => $r['full_name'] ?: '—',
                'department'   => normalizeDepartmentName($r['department'] ?? ''),
                'status'       => ucwords(str_replace(['_', '-'], ' ', (string)($r['status'] ?? 'Pending'))),
            ];
        }
    } elseif ($category === 'patients') {
        $columns = [
            ['key' => 'patient_id', 'label' => 'Patient ID'],
            ['key' => 'full_name', 'label' => 'Full Name'],
            ['key' => 'gender', 'label' => 'Gender'],
            ['key' => 'age', 'label' => 'Age'],
            ['key' => 'registered_at', 'label' => 'Registered'],
        ];
        $emailSel = $hasCol('email') ? 'email' : 'NULL';
        $userSel  = $hasCol('user_id') ? 'user_id' : 'NULL';
        [$dateWhere, $dateParams] = $buildDateClause($createdDate);
        $sql = "SELECT id, patient_id, full_name, $genderSel AS gender, $ageSel AS age, $birthSel AS birthdate,
                       $emailSel AS email, $userSel AS user_id, $createdDate AS reg_date
                FROM patients
                WHERE $dateWhere
                  $deptCondSql
                ORDER BY created_at ASC, id ASC
                LIMIT 2000";
        $st = $pdo->prepare($sql);
        $st->execute($dateParams);

        // Deduplicate returning patients across birthdate and email keys without splitting
        // signup stubs (no birthdate) from later bookings (with birthdate).
        $uniquePatients = [];
        $keyAliases = [];
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $nameNorm = strtolower(preg_replace('/\s+/', ' ', trim((string)($r['full_name'] ?? ''))));
            $birthVal = trim((string)($r['birthdate'] ?? ''));
            $emailVal = strtolower(trim((string)($r['email'] ?? '')));
            $genderVal = trim((string)($r['gender'] ?? ''));

            $candidates = [];
            if ($nameNorm !== '' && $birthVal !== '' && $birthVal !== '0000-00-00') {
                $candidates[] = 'nb:' . $nameNorm . '|' . $birthVal;
            }
            if ($nameNorm !== '' && $emailVal !== '') {
                $candidates[] = 'ne:' . $nameNorm . '|' . $emailVal;
            }
            if (empty($candidates)) {
                $candidates[] = $nameNorm !== ''
                    ? ('ng:' . $nameNorm . '|' . strtolower($genderVal))
                    : ('id:' . $r['id']);
            }

            $dedupKey = null;
            foreach ($candidates as $cand) {
                if (isset($keyAliases[$cand])) {
                    $dedupKey = $keyAliases[$cand];
                    break;
                }
            }
            if ($dedupKey === null) {
                $dedupKey = $candidates[0];
            }
            foreach ($candidates as $cand) {
                $keyAliases[$cand] = $dedupKey;
            }

            $ageVal = $r['age'];
            if (($ageVal === null || $ageVal === '') && $birthVal !== '' && $birthVal !== '0000-00-00') {
                try {
                    $ageVal = (new DateTime($birthVal))->diff(new DateTime())->y;
                } catch (Throwable $ignore) {}
            }

            if (!isset($uniquePatients[$dedupKey])) {
                $pid = trim((string)($r['patient_id'] ?? ''));
                $uniquePatients[$dedupKey] = [
                    'id'            => (int)$r['id'],
                    'patient_id'    => $pid !== '' ? $pid : ('PT-' . $r['id']),
                    'full_name'     => trim((string)($r['full_name'] ?? '')) ?: '—',
                    'gender'        => $genderVal !== '' ? $genderVal : '—',
                    'age'           => ($ageVal !== null && $ageVal !== '') ? (string)$ageVal : '—',
                    'registered_at' => $r['reg_date'] ?: '—',
                ];
            } else {
                // Fill missing demographics if an earlier row lacked them
                if ($uniquePatients[$dedupKey]['gender'] === '—' && $genderVal !== '') {
                    $uniquePatients[$dedupKey]['gender'] = $genderVal;
                }
                if ($uniquePatients[$dedupKey]['age'] === '—' && $ageVal !== null && $ageVal !== '') {
                    $uniquePatients[$dedupKey]['age'] = (string)$ageVal;
                }
            }
        }

        $rows = array_values($uniquePatients);
        usort($rows, static fn(array $a, array $b): int =>
            strcmp((string)$b['registered_at'], (string)$a['registered_at']) ?: ($b['id'] <=> $a['id'])
        );
        $rows = array_slice($rows, 0, 500);
    } elseif ($category === 'doctors') {
        $columns = [
            ['key' => 'name', 'label' => 'Physician'],
            ['key' => 'department', 'label' => 'Department'],
            ['key' => 'specialization', 'label' => 'Specialization'],
            ['key' => 'consultations_count', 'label' => 'Consultations'],
            ['key' => 'status', 'label' => 'Status'],
        ];
        $consultDate = "COALESCE($medDateExpr, $apptDateExpr)";
        [$dateWhere, $dateParams] = $buildDateClause($consultDate);
        $tallyStmt = $pdo->prepare(
            "SELECT assigned_doctor, COUNT(*) AS cnt
             FROM patients
             WHERE $dateWhere
               $inqCondSql
               $deptCondSql
               AND LOWER(COALESCE(status, '')) NOT IN ('cancelled', 'rejected', 'cancelled_by_patient', 'no-show')
               AND TRIM(COALESCE(assigned_doctor, '')) <> ''
               AND LOWER(TRIM(assigned_doctor)) <> 'none'
             GROUP BY assigned_doctor"
        );
        $tallyStmt->execute($dateParams);
        $docCounts = [];
        foreach ($tallyStmt->fetchAll(PDO::FETCH_ASSOC) as $t) {
            $normKey = strtolower(trim(preg_replace('/^dr\.?\s*/i', '', (string)$t['assigned_doctor'])));
            $docCounts[$normKey] = ($docCounts[$normKey] ?? 0) + (int)$t['cnt'];
        }

        $docSql = "SELECT id, name, specialization, department, status
                   FROM doctors
                   WHERE 1=1 $deptCondSql
                   ORDER BY department ASC, name ASC";
        foreach ($pdo->query($docSql)->fetchAll(PDO::FETCH_ASSOC) as $d) {
            $normKey = strtolower(trim(preg_replace('/^dr\.?\s*/i', '', (string)$d['name'])));
            $stRaw = strtolower(trim((string)($d['status'] ?? 'in')));
            $rows[] = [
                'id'                  => (int)$d['id'],
                'name'                => $d['name'] ?: '—',
                'department'          => normalizeDepartmentName($d['department'] ?? ''),
                'specialization'      => $d['specialization'] ?: '—',
                'consultations_count' => (string)($docCounts[$normKey] ?? 0),
                'status'              => in_array($stRaw, ['in', 'available', 'active', 'on duty'], true) ? 'Available' : 'Unavailable',
            ];
        }
    } elseif ($category === 'medication') {
        $columns = [
            ['key' => 'date', 'label' => 'Date'],
            ['key' => 'patient_name', 'label' => 'Patient'],
            ['key' => 'medication', 'label' => 'Medication'],
            ['key' => 'dosage', 'label' => 'Dosage'],
            ['key' => 'prescribed_by', 'label' => 'Prescribed By'],
        ];
        [$dateWhere, $dateParams] = $buildDateClause($dateExpr);
        $sql = "SELECT id, $dateExpr AS rec_date, full_name, assigned_doctor, active_medications, medical_history_prescription
                FROM patients
                WHERE $dateWhere
                  AND (TRIM(COALESCE(active_medications, '')) <> '' OR TRIM(COALESCE(medical_history_prescription, '')) <> '')
                  $deptCondSql
                ORDER BY rec_date DESC, id DESC
                LIMIT 500";
        $st = $pdo->prepare($sql);
        $st->execute($dateParams);
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $doc = trim((string)($r['assigned_doctor'] ?? ''));
            $prescriber = ($doc !== '' && strcasecmp($doc, 'None') !== 0) ? $doc : 'Attending Physician';
            $rawMeds = trim((string)($r['active_medications'] ?? ''));
            $parsed = false;

            if ($rawMeds !== '' && (str_starts_with($rawMeds, '{') || str_starts_with($rawMeds, '['))) {
                $decoded = json_decode($rawMeds, true);
                if (is_array($decoded)) {
                    $items = isset($decoded['medicine_name']) || isset($decoded['name']) ? [$decoded] : $decoded;
                    foreach ($items as $idx => $m) {
                        $mName = is_array($m) ? trim((string)($m['medicine_name'] ?? $m['name'] ?? '')) : trim((string)$m);
                        if ($mName === '') continue;
                        $mDose = is_array($m) ? trim((string)($m['dosage'] ?? $m['frequency'] ?? '')) : '';
                        $rows[] = [
                            'id'            => $r['id'] . '-' . $idx,
                            'date'          => $r['rec_date'] ?: '—',
                            'patient_name'  => $r['full_name'] ?: '—',
                            'medication'    => $mName,
                            'dosage'        => $mDose !== '' ? $mDose : 'As directed',
                            'prescribed_by' => $prescriber,
                        ];
                        $parsed = true;
                    }
                }
            }

            if (!$parsed) {
                $rx = trim((string)($r['medical_history_prescription'] ?? $rawMeds));
                if ($rx !== '') {
                    $rows[] = [
                        'id'            => (int)$r['id'],
                        'date'          => $r['rec_date'] ?: '—',
                        'patient_name'  => $r['full_name'] ?: '—',
                        'medication'    => $rx,
                        'dosage'        => 'As prescribed',
                        'prescribed_by' => $prescriber,
                    ];
                }
            }
        }
    }

    if ($exportExcel) {
        if (ob_get_length()) ob_end_clean();
        $catTitle = ucfirst($category);
        $deptLabel = ($dept === '' || strtolower($dept) === 'all') ? 'All Departments' : normalizeDepartmentName($dept);
        if ($from !== '' && $to !== '') {
            $rangeLabel = "$from to $to";
            $rangeSlug = "{$from}_to_{$to}";
        } elseif ($from !== '') {
            $rangeLabel = "From $from";
            $rangeSlug = "from_{$from}";
        } elseif ($to !== '') {
            $rangeLabel = "Up to $to";
            $rangeSlug = "to_{$to}";
        } else {
            $rangeLabel = 'All Dates';
            $rangeSlug = 'All_Dates';
        }
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="MMWGH_' . $catTitle . '_Report_' . $rangeSlug . '.csv"');
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, ['MARIVELES MENTAL WELLNESS AND GENERAL HOSPITAL'], ',', '"', '\\');
        fputcsv($out, [strtoupper($catTitle) . ' REPORT'], ',', '"', '\\');
        fputcsv($out, ['Date Range', $rangeLabel, 'Department', $deptLabel, 'Generated', date('M d, Y h:i A')], ',', '"', '\\');
        fputcsv($out, [], ',', '"', '\\');
        fputcsv($out, array_column($columns, 'label'), ',', '"', '\\');
        foreach ($rows as $row) {
            fputcsv($out, array_map(static fn($c) => $row[$c['key']] ?? '', $columns), ',', '"', '\\');
        }
        fclose($out);
        exit;
    }

    respondOk([
        'category' => $category,
        'from'     => $from,
        'to'       => $to,
        'columns'  => $columns,
        'data'     => $rows,
    ]);
}

$range      = trim($_GET['range'] ?? 'today'); // 'today', 'past_7', 'past_30', 'past_month', 'next_7', 'next_30', 'monthly', 'custom', 'all'
$deptFilter = trim($_GET['department'] ?? 'all');
$startDate  = trim($_GET['start_date'] ?? '');
$endDate    = trim($_GET['end_date'] ?? '');
$exportCsv  = !empty($_GET['export_csv']);

$medDateCast     = dbIsPgsql() ? 'CAST(medical_history_date AS DATE)' : 'DATE(medical_history_date)';
$createdDateCast = dbIsPgsql() ? 'CAST(created_at AS DATE)' : 'DATE(created_at)';
$dateExpr = "COALESCE($medDateCast, appointment_date, $createdDateCast)";
$whereParts = ["1=1"];
$params = [];

$todayStr     = date('Y-m-d');
$past7Str     = date('Y-m-d', strtotime('-6 days'));
$past30Str    = date('Y-m-d', strtotime('-29 days'));
$pastMonthStr = date('Y-m-d', strtotime('-1 month'));
$next7Str     = date('Y-m-d', strtotime('+6 days'));
$next30Str    = date('Y-m-d', strtotime('+29 days'));
$monthStart   = date('Y-m-01');
$monthEnd     = date('Y-m-t');

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
    $whereParts[] = "($dateExpr = ? OR appointment_date = ?)";
    $params[] = $todayStr;
    $params[] = $todayStr;
} elseif ($range === 'past_7') {
    $whereParts[] = "$dateExpr BETWEEN ? AND ?";
    $params[] = $past7Str;
    $params[] = $todayStr;
} elseif ($range === 'past_30') {
    $whereParts[] = "$dateExpr BETWEEN ? AND ?";
    $params[] = $past30Str;
    $params[] = $todayStr;
} elseif ($range === 'past_month') {
    $whereParts[] = "$dateExpr BETWEEN ? AND ?";
    $params[] = $pastMonthStr;
    $params[] = $todayStr;
} elseif ($range === 'next_7') {
    $whereParts[] = "COALESCE(appointment_date, $createdDateCast) BETWEEN ? AND ?";
    $params[] = $todayStr;
    $params[] = $next7Str;
} elseif ($range === 'next_30') {
    $whereParts[] = "COALESCE(appointment_date, $createdDateCast) BETWEEN ? AND ?";
    $params[] = $todayStr;
    $params[] = $next30Str;
} elseif ($range === 'monthly') {
    $whereParts[] = "$dateExpr BETWEEN ? AND ?";
    $params[] = $monthStart;
    $params[] = $monthEnd;
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
    $cols = dbTableColumns($pdo, 'patients');
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
                      COALESCE($medDateCast, appointment_date, $createdDateCast) AS record_date
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
