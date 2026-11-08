<?php
/**
 * api/patient_dashboard_data.php
 * Patient Portal Dashboard & Medications API
 * - GET: Returns patient profile, upcoming/past/cancelled appointments (with diagnosis & previous appointment info),
 *        dynamically extracted diagnosed medications (medicine name, dosage, daily frequency, quantity, doctor, diagnosis),
 *        and live notifications.
 * - POST: Supports password change (`action: 'change_password'`) and profile/preferences updates.
 */

session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/config/cors.php';
require_once __DIR__ . '/config/db.php';

// Access guard
if (empty($_SESSION['patient_logged_in']) || (empty($_SESSION['patient_id']) && empty($_SESSION['patient_email']))) {
    http_response_code(401);
    echo json_encode([
        'success'  => false,
        'message'  => 'Unauthorized. Please sign in to the patient portal.',
        'redirect' => '/sdn/pages/patient-login.php'
    ]);
    exit;
}

$patientId    = $_SESSION['patient_id'] ?? '';
$patientEmail = $_SESSION['patient_email'] ?? '';
$userId       = $_SESSION['user_id'] ?? $_SESSION['patient_user_id'] ?? null;

function formatDeptName(string $dept): string {
    $d = strtolower($dept);
    if (str_contains($d, 'psych') || str_contains($d, 'opu')) return 'Psychiatry';
    if (str_contains($d, 'neuro')) return 'Neurology';
    return 'General Medical';
}

function parseDailyScheduleLabel(string $dosageStr): string {
    $s = strtolower($dosageStr);
    if (preg_match('/\bqid\b|4\s*x|four\s*times|q6h/i', $s)) {
        return '4 times per day (Every 6 hours)';
    }
    if (preg_match('/\btid\b|3\s*x|three\s*times|q8h/i', $s)) {
        return '3 times per day (Morning, Noon, Evening)';
    }
    if (preg_match('/\bbid\b|2\s*x|twice/i', $s)) {
        return '2 times per day (Morning & Evening)';
    }
    if (preg_match('/\bod\b|once\s*daily|1\s*x|bedtime|morning/i', $s)) {
        if (str_contains($s, 'bedtime') || str_contains($s, 'night')) {
            return 'Once per day (At bedtime)';
        }
        if (str_contains($s, 'morning') || str_contains($s, 'breakfast')) {
            return 'Once per day (In the morning)';
        }
        return 'Once per day (Daily)';
    }
    if (preg_match('/\bprn\b|as\s*needed/i', $s)) {
        return 'As needed (PRN) per physician instructions';
    }
    return $dosageStr !== '' ? $dosageStr : 'As directed by attending physician';
}

function estimateDaysSupply(string $qtyStr, string $dosageStr): int {
    if (preg_match('/x\s*(\d+)\s*day/i', $dosageStr, $m)) {
        return max(1, (int)$m[1]);
    }
    $qtyNum = 0;
    if (preg_match('/(\d+)/', $qtyStr, $qm)) {
        $qtyNum = (int)$qm[1];
    }
    if ($qtyNum <= 0) return 14;

    $perDay = 1;
    if (preg_match('/\bqid\b|4\s*x|four/i', $dosageStr)) $perDay = 4;
    elseif (preg_match('/\btid\b|3\s*x|three/i', $dosageStr)) $perDay = 3;
    elseif (preg_match('/\bbid\b|2\s*x|twice/i', $dosageStr)) $perDay = 2;

    return max(1, (int)ceil($qtyNum / $perDay));
}

// ── Handle POST actions (Change Password / Update Preferences) ──────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw = file_get_contents('php://input');
    $body = json_decode($raw, true);
    if (!is_array($body)) $body = $_POST;

    $action = $body['action'] ?? '';

    if ($action === 'change_password') {
        $currentPass = $body['current_password'] ?? '';
        $newPass     = $body['new_password'] ?? '';
        $confirmPass = $body['confirm_password'] ?? '';

        if (strlen($newPass) < 6) {
            echo json_encode(['success' => false, 'message' => 'New password must be at least 6 characters long.']);
            exit;
        }
        if ($newPass !== $confirmPass) {
            echo json_encode(['success' => false, 'message' => 'New password and confirmation do not match.']);
            exit;
        }

        try {
            $stmtAcc = $pdo->prepare("
                SELECT id, password 
                FROM patients 
                WHERE ((:uid IS NOT NULL AND user_id = :uid) OR (:pid != '' AND patient_id = :pid) OR (:pemail != '' AND email = :pemail))
                  AND password IS NOT NULL
                ORDER BY id DESC LIMIT 1
            ");
            $stmtAcc->execute([':uid' => $userId, ':pid' => $patientId, ':pemail' => $patientEmail]);
            $acc = $stmtAcc->fetch(PDO::FETCH_ASSOC);

            if ($acc && !empty($acc['password']) && $currentPass !== '') {
                if (!password_verify($currentPass, $acc['password']) && $currentPass !== $acc['password']) {
                    echo json_encode(['success' => false, 'message' => 'Current password is incorrect.']);
                    exit;
                }
            }

            $hashed = password_hash($newPass, PASSWORD_DEFAULT);
            $upd = $pdo->prepare("
                UPDATE patients 
                SET password = :pw 
                WHERE (:uid IS NOT NULL AND user_id = :uid)
                   OR (:pid != '' AND patient_id = :pid)
                   OR (:pemail != '' AND email = :pemail)
            ");
            $upd->execute([':pw' => $hashed, ':uid' => $userId, ':pid' => $patientId, ':pemail' => $patientEmail]);

            echo json_encode(['success' => true, 'message' => 'Password updated successfully.']);
            exit;
        } catch (Throwable $e) {
            echo json_encode(['success' => false, 'message' => 'Could not update password.']);
            exit;
        }
    }

    echo json_encode(['success' => false, 'message' => 'Unsupported action.']);
    exit;
}

try {
    // 0. Auto-link appointments for patient across their identifiers
    if ($userId) {
        $linkClauses = [];
        $linkParams  = [$userId];
        if ($patientEmail !== '') {
            $linkClauses[] = "email = ?";
            $linkParams[]  = $patientEmail;
        }
        if ($patientId !== '') {
            $linkClauses[] = "patient_id = ?";
            $linkParams[]  = $patientId;
        }
        if (!empty($_SESSION['patient_name'])) {
            $linkClauses[] = "LOWER(TRIM(full_name)) = LOWER(TRIM(?))";
            $linkParams[]  = $_SESSION['patient_name'];
        }
        if (!empty($linkClauses)) {
            try {
                $linkSql = "UPDATE patients SET user_id = ? WHERE user_id IS NULL AND (" . implode(' OR ', $linkClauses) . ")";
                $pdo->prepare($linkSql)->execute($linkParams);
            } catch (Throwable $e) {}
        }
    }

    // 1. Profile information - match latest record across user_id, patient_id, email, or full name
    $profileClauses = [];
    $profileParams  = [];
    if ($userId) {
        $profileClauses[] = "user_id = ?";
        $profileParams[]  = $userId;
    }
    if ($patientId !== '') {
        $profileClauses[] = "patient_id = ?";
        $profileParams[]  = $patientId;
    }
    if ($patientEmail !== '') {
        $profileClauses[] = "email = ?";
        $profileParams[]  = $patientEmail;
    }
    if (!empty($_SESSION['patient_name'])) {
        $profileClauses[] = "LOWER(TRIM(full_name)) = LOWER(TRIM(?))";
        $profileParams[]  = $_SESSION['patient_name'];
    }

    $profileSql = !empty($profileClauses) ? implode(' OR ', $profileClauses) : "1=0";
    $stmtProfile = $pdo->prepare("
        SELECT id, patient_id, hrn, full_name, gender, age, birthdate, email, contact_number, address
        FROM patients
        WHERE $profileSql
        ORDER BY id DESC
        LIMIT 1
    ");
    $stmtProfile->execute($profileParams);
    $profile = $stmtProfile->fetch(PDO::FETCH_ASSOC);

    if (!$profile) {
        $profile = [
            'patient_id'     => $patientId ?: 'PT-2026-001',
            'hrn'            => 'HRN-' . substr($patientId ?: 'PT-2026-001', 3),
            'full_name'      => $_SESSION['patient_name'] ?? 'Patient',
            'email'          => $patientEmail,
            'contact_number' => '',
            'gender'         => '',
            'birthdate'      => '',
            'address'        => ''
        ];
    }

    // Keep session in sync with the latest patient_id and name
    if (!empty($profile['patient_id'])) {
        $_SESSION['patient_id'] = $profile['patient_id'];
    }
    if (!empty($profile['full_name'])) {
        $_SESSION['patient_name'] = $profile['full_name'];
    }

    // 2. Appointments query - fetch all bookings linked to this patient
    $apptClauses = [];
    $apptParams  = [];

    if ($userId) {
        $apptClauses[] = "user_id = ?";
        $apptParams[]  = $userId;
    }
    if (!empty($profile['patient_id'])) {
        $apptClauses[] = "patient_id = ?";
        $apptParams[]  = $profile['patient_id'];
    }
    if ($patientId !== '' && $patientId !== ($profile['patient_id'] ?? '')) {
        $apptClauses[] = "patient_id = ?";
        $apptParams[]  = $patientId;
    }
    if (!empty($profile['email'])) {
        $apptClauses[] = "email = ?";
        $apptParams[]  = $profile['email'];
    } elseif ($patientEmail !== '') {
        $apptClauses[] = "email = ?";
        $apptParams[]  = $patientEmail;
    }
    if (!empty($profile['hrn'])) {
        $apptClauses[] = "hrn = ?";
        $apptParams[]  = $profile['hrn'];
    }
    if (!empty($profile['full_name'])) {
        $apptClauses[] = "LOWER(TRIM(full_name)) = LOWER(TRIM(?))";
        $apptParams[]  = $profile['full_name'];
    } elseif (!empty($_SESSION['patient_name'])) {
        $apptClauses[] = "LOWER(TRIM(full_name)) = LOWER(TRIM(?))";
        $apptParams[]  = $_SESSION['patient_name'];
    }
    if (!empty($profile['contact_number'])) {
        $cleanPhone = preg_replace('/[^0-9]/', '', $profile['contact_number']);
        if (strlen($cleanPhone) >= 7) {
            $apptClauses[] = "REPLACE(REPLACE(REPLACE(contact_number, '+', ''), ' ', ''), '-', '') LIKE ?";
            $apptParams[]  = '%' . substr($cleanPhone, -9);
        }
    }

    $apptSql = !empty($apptClauses) ? implode(' OR ', $apptClauses) : "1=0";
    $stmtAppts = $pdo->prepare("
        SELECT 
            id,
            patient_id,
            hrn,
            user_id,
            department,
            appointment_date,
            scheduled_time,
            status,
            assigned_doctor,
            consultation_mode,
            appointment_type,
            chief_complaints,
            consultation_nature,
            medical_history_diagnosis,
            medical_history_treatment,
            medical_history_prescription,
            active_medications,
            medical_history_date,
            next_visit_date,
            created_at,
            updated_at
        FROM patients
        WHERE $apptSql
        ORDER BY id ASC
    ");
    $stmtAppts->execute($apptParams);
    $chronologicalAppts = $stmtAppts->fetchAll(PDO::FETCH_ASSOC);

    // Attach previous appointment info to each booking in chronological sequence
    $prevApptMeta = null;
    foreach ($chronologicalAppts as &$cRow) {
        $cRow['_prev_date'] = $prevApptMeta ? $prevApptMeta['date'] : null;
        $cRow['_prev_time'] = $prevApptMeta ? $prevApptMeta['time'] : null;
        $cRow['_prev_dept'] = $prevApptMeta ? $prevApptMeta['dept'] : null;
        $cRow['_prev_id']   = $prevApptMeta ? $prevApptMeta['patient_id'] : null;

        $rDate = !empty($cRow['appointment_date']) ? date('M j, Y', strtotime($cRow['appointment_date'])) : date('M j, Y', strtotime($cRow['created_at']));
        $rTime = !empty($cRow['scheduled_time']) ? date('g:i A', strtotime($cRow['scheduled_time'])) : '8:00 AM';
        $prevApptMeta = [
            'date'       => $rDate,
            'time'       => $rTime,
            'dept'       => formatDeptName($cRow['department'] ?? ''),
            'patient_id' => $cRow['patient_id']
        ];
    }
    unset($cRow);

    // Sort appointments: upcoming first by date ASC, then past by date DESC
    $appointments = $chronologicalAppts;
    usort($appointments, function($a, $b) {
        $today = date('Y-m-d');
        $da = $a['appointment_date'] ?: $today;
        $db = $b['appointment_date'] ?: $today;
        $aFuture = $da >= $today ? 0 : 1;
        $bFuture = $db >= $today ? 0 : 1;
        if ($aFuture !== $bFuture) return $aFuture <=> $bFuture;
        if ($aFuture === 0) return strcmp($da, $db) ?: ($b['id'] <=> $a['id']);
        return strcmp($db, $da) ?: ($b['id'] <=> $a['id']);
    });

    $today         = date('Y-m-d');
    $upcoming      = [];
    $past          = [];
    $cancelled     = [];
    $medications   = [];
    $notifications = [];

    foreach ($appointments as $appt) {
        $statusLower = strtolower(trim($appt['status'] ?? 'pending'));
        $apptDate    = $appt['appointment_date'] ?: substr($appt['created_at'] ?? $today, 0, 10);
        $deptLabel   = formatDeptName($appt['department'] ?? '');
        $rawDoc      = trim($appt['assigned_doctor'] ?? '');
        $doctor      = ($rawDoc !== '' && strtolower($rawDoc) !== 'none')
            ? (str_starts_with($rawDoc, 'Dr.') ? $rawDoc : 'Dr. ' . $rawDoc)
            : 'Attending Physician';
        $timeLabel   = !empty($appt['scheduled_time']) ? date('g:i A', strtotime($appt['scheduled_time'])) : '8:00 AM';

        $isWaiting   = in_array($statusLower, ['pending', 'for_approval', 'for approval', 'for_processing'], true);
        $isConfirmed = in_array($statusLower, ['approved', 'confirmed', 'arrived', 'ongoing'], true);
        $isCancelled = in_array($statusLower, ['cancelled', 'rejected', 'no-show'], true);

        if ($statusLower === 'ongoing') {
            $displayStatus = 'In Consultation';
        } elseif ($statusLower === 'arrived') {
            $displayStatus = 'Checked In';
        } elseif ($isConfirmed) {
            $displayStatus = 'Confirmed';
        } elseif ($isWaiting) {
            $displayStatus = 'For approval';
        } elseif ($statusLower === 'completed') {
            $displayStatus = 'Completed';
        } else {
            $displayStatus = ucwords(str_replace('_', ' ', $statusLower));
        }

        $diagText = trim($appt['medical_history_diagnosis'] ?? '');
        $treatText = trim($appt['medical_history_treatment'] ?? '');
        $rxText = trim($appt['medical_history_prescription'] ?? '');

        $item = [
            'id'                        => (int)$appt['id'],
            'patient_code'              => $appt['patient_id'] ?: ('PT-' . $appt['id']),
            'date'                      => date('M j, Y', strtotime($apptDate)),
            'date_raw'                  => $apptDate,
            'date_iso'                  => $apptDate,
            'month'                     => strtoupper(date('M', strtotime($apptDate))),
            'day'                       => date('j', strtotime($apptDate)),
            'time'                      => $timeLabel,
            'time_label'                => $timeLabel,
            'time_iso'                  => $appt['scheduled_time'] ?: '08:00:00',
            'title'                     => $deptLabel . ' Consultation',
            'detail'                    => $doctor . ' • ' . $timeLabel,
            'department'                => $deptLabel,
            'doctor'                    => $doctor,
            'mode'                      => $appt['consultation_mode'] ?: 'Face-to-Face',
            'consult_mode'              => $appt['consultation_mode'] ?: 'Face-to-Face',
            'chief_complaints'          => $appt['chief_complaints'] ?: 'General consultation',
            'diagnosis'                 => $diagText,
            'treatment'                 => $treatText,
            'prescription'              => $rxText,
            'previous_appointment_date' => $appt['_prev_date'] ?? null,
            'previous_appointment_time' => $appt['_prev_time'] ?? null,
            'previous_department'       => $appt['_prev_dept'] ?? null,
            'previous_patient_id'       => $appt['_prev_id'] ?? null,
            'status'                    => $displayStatus,
            'status_raw'                => $statusLower,
            'can_reschedule'            => $isWaiting && !$isCancelled && $apptDate >= $today
        ];

        if ($isCancelled) {
            $cancelled[] = $item;
        } elseif ($statusLower === 'completed' || $apptDate < $today) {
            $past[] = $item;
        } else {
            $upcoming[] = $item;
        }

        // Build notifications from real events
        if ($isConfirmed) {
            $notifications[] = [
                'id'    => 'notif-confirm-' . $appt['id'],
                'title' => $deptLabel . ' Visit ' . $displayStatus,
                'text'  => "{$doctor} on " . date('M j, Y', strtotime($apptDate)) . " at {$timeLabel} ({$item['patient_code']}).",
                'time'  => 'Active Schedule',
                'view'  => 'appointments',
                'warn'  => false
            ];
        } elseif ($isWaiting) {
            $notifications[] = [
                'id'    => 'notif-wait-' . $appt['id'],
                'title' => 'Appointment Awaiting Approval',
                'text'  => "{$deptLabel} visit on " . date('M j, Y', strtotime($apptDate)) . " at {$timeLabel} is pending staff verification.",
                'time'  => 'Pending Review',
                'view'  => 'appointments',
                'warn'  => true
            ];
        }

        // Extract diagnosed medications from completed/diagnosed records
        $rawActiveMeds = trim($appt['active_medications'] ?? '');
        if ($rawActiveMeds !== '' || $rxText !== '') {
            $medName   = '';
            $medDosage = '';
            $medQty    = '';
            $medDiag   = $diagText ?: 'Clinical Consultation';
            $medTreat  = $treatText;

            if ($rawActiveMeds !== '' && str_starts_with($rawActiveMeds, '{')) {
                $decoded = json_decode($rawActiveMeds, true);
                if (is_array($decoded)) {
                    $medName   = trim($decoded['medicine_name'] ?? '');
                    $medDosage = trim($decoded['dosage'] ?? '');
                    $medQty    = trim($decoded['quantity'] ?? '');
                    if (!empty($decoded['diagnosis'])) $medDiag = trim($decoded['diagnosis']);
                    if (!empty($decoded['treatment'])) $medTreat = trim($decoded['treatment']);
                }
            }

            if ($medName === '' && $rxText !== '') {
                $lines = preg_split('/\r\n|\r|\n/', $rxText);
                $firstLine = trim($lines[0] ?? '');
                $firstLine = preg_replace('/^Rx:\s*/i', '', $firstLine);
                if (preg_match('/^(.*?)\s+#([0-9].*)$/', $firstLine, $m)) {
                    $medName = trim($m[1]);
                    $medQty  = trim($m[2]);
                } else {
                    $medName = $firstLine;
                }
                foreach ($lines as $ln) {
                    if (preg_match('/^Sig:\s*(.+)$/i', trim($ln), $sm)) {
                        $medDosage = trim($sm[1]);
                    }
                }
                if ($medDosage === '' && count($lines) > 1) {
                    $medDosage = trim($lines[1]);
                }
            }

            if ($medName === '' && $rawActiveMeds !== '' && !str_starts_with($rawActiveMeds, '{')) {
                $medName = $rawActiveMeds;
            }

            if ($medName !== '' && strtolower($medName) !== 'none' && strtolower($medName) !== 'n/a') {
                $dailyFreq = parseDailyScheduleLabel($medDosage);
                $totalDays = estimateDaysSupply($medQty, $medDosage);
                $prescribedDateRaw = $appt['medical_history_date'] ?: ($appt['updated_at'] ?: $apptDate);
                $elapsedDays = max(0, (int)floor((time() - strtotime($prescribedDateRaw)) / 86400));
                $daysLeft = max(1, $totalDays - $elapsedDays);
                $pct = min(100, max(10, (int)round(($daysLeft / max(1, $totalDays)) * 100)));

                $medications[] = [
                    'id'              => (int)$appt['id'],
                    'patient_code'    => $item['patient_code'],
                    'name'            => $medName,
                    'dosage'          => $medDosage ?: 'Take as directed by physician',
                    'frequency'       => $dailyFreq,
                    'quantity'        => $medQty ?: 'As prescribed',
                    'amount'          => $medQty ?: 'Prescribed course',
                    'diagnosis'       => $medDiag,
                    'treatment'       => $medTreat,
                    'prescription'    => $rxText,
                    'doctor'          => "{$doctor} ({$deptLabel})",
                    'department'      => $deptLabel,
                    'prescribed_date' => date('M j, Y', strtotime($prescribedDateRaw)),
                    'days_left'       => $daysLeft,
                    'total_days'      => $totalDays,
                    'pct'             => $pct,
                    'status'          => 'Active'
                ];

                $notifications[] = [
                    'id'    => 'notif-med-' . $appt['id'],
                    'title' => 'Medication Prescribed: ' . $medName,
                    'text'  => "Diagnosed with {$medDiag}. Dosage: " . ($medDosage ?: $dailyFreq) . ".",
                    'time'  => date('M j, Y', strtotime($prescribedDateRaw)),
                    'view'  => 'medications',
                    'warn'  => false
                ];
            }
        }
    }

    // Sort upcoming so nearest future active/confirmed visit is at index 0 (Next Appointment)
    usort($upcoming, function($a, $b) use ($today) {
        $da = $a['date_raw'] ?: $today;
        $db = $b['date_raw'] ?: $today;
        $aFuture = ($da >= $today) ? 0 : 1;
        $bFuture = ($db >= $today) ? 0 : 1;
        if ($aFuture !== $bFuture) return $aFuture <=> $bFuture;
        if ($aFuture === 0) return strcmp($da, $db) ?: ($b['id'] <=> $a['id']);
        return strcmp($db, $da) ?: ($b['id'] <=> $a['id']);
    });

    $departments = [
        [
            'id'     => 'general_medicine',
            'name'   => 'General Medical',
            'desc'   => 'Primary care, internal medicine & outpatient check-ups',
            'action' => 'Book General Medical'
        ],
        [
            'id'     => 'neurology',
            'name'   => 'Neurology',
            'desc'   => 'Brain, nervous system, seizure & headache specialists',
            'action' => 'Book Neurology'
        ],
        [
            'id'     => 'psychiatry',
            'name'   => 'Psychiatry',
            'desc'   => 'Mental wellness, counseling & behavioral health',
            'action' => 'Book Psychiatry'
        ]
    ];

    echo json_encode([
        'success'       => true,
        'profile'       => [
            'patient_id'     => $profile['patient_id'],
            'hrn'            => $profile['hrn'] ?? ('HRN-' . substr($profile['patient_id'] ?? 'PT-2026-001', 3)),
            'full_name'      => $profile['full_name'],
            'email'          => $profile['email'],
            'contact_number' => $profile['contact_number'] ?? '',
            'gender'         => $profile['gender'] ?? '',
            'birthdate'      => $profile['birthdate'] ?? '',
            'address'        => $profile['address'] ?? '',
            'total_visits'   => count($chronologicalAppts)
        ],
        'stats'         => [
            'upcoming_count'  => count($upcoming),
            'past_count'      => count($past),
            'cancelled_count' => count($cancelled),
            'active_meds'     => count($medications),
            'total_visits'    => count($chronologicalAppts)
        ],
        'departments'   => $departments,
        'upcoming'      => $upcoming,
        'past'          => $past,
        'cancelled'     => $cancelled,
        'medications'   => $medications,
        'notifications' => array_slice($notifications, 0, 8)
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    error_log("Patient dashboard API error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Unable to retrieve appointment schedules.'
    ]);
}
