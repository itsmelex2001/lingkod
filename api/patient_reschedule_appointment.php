<?php
/**
 * Patient reschedule. Allowed only while staff has not approved the visit.
 */
date_default_timezone_set('Asia/Manila');
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/config/cors.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/patient_remember.php';
require_once __DIR__ . '/config/settings_helper.php';

if (empty($_SESSION['patient_logged_in']) && !empty($_COOKIE['lingkod_patient_remember']) && isset($pdo)) {
    tryAutoLoginPatientFromCookie($pdo);
}

if (empty($_SESSION['patient_logged_in']) || (empty($_SESSION['patient_id']) && empty($_SESSION['patient_email']))) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Your patient session expired. Please sign in to the Patient Portal again.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'POST required.']);
    exit;
}

$body = json_decode(file_get_contents('php://input'), true) ?: [];
$recordId = (int)($body['id'] ?? 0);
$newDate = trim($body['appointment_date'] ?? '');
$newTime = trim($body['scheduled_time'] ?? '');
$allowedTimes = ['08:00:00', '10:00:00', '13:00:00', '15:00:00'];

if ($recordId <= 0 || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $newDate) || !in_array($newTime, $allowedTimes, true)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Choose a valid date and clinic session.']);
    exit;
}

$minDate = date('Y-m-d', strtotime('+1 day'));
if ($newDate < $minDate || (int)date('w', strtotime($newDate)) === 0) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Pick a Monday to Saturday date at least one day ahead.']);
    exit;
}

$patientId = trim((string)($_SESSION['patient_id'] ?? ''));
$patientEmail = trim(strtolower((string)($_SESSION['patient_email'] ?? '')));
$hasStaffSession = !empty($_SESSION['user'])
    && in_array(strtolower(trim((string)($_SESSION['role'] ?? ''))), ['admin', 'superadmin', 'staff'], true);
$userId = $_SESSION['patient_user_id'] ?? ($hasStaffSession ? null : ($_SESSION['user_id'] ?? null));

try {
    $ownerClauses = [];
    $ownerParams  = [$recordId];
    if ($userId !== null && $userId !== '') {
        $ownerClauses[] = 'user_id = ?';
        $ownerParams[]  = $userId;
    }
    if ($patientId !== '') {
        $ownerClauses[] = 'patient_id = ?';
        $ownerParams[]  = $patientId;
    }
    if ($patientEmail !== '') {
        $ownerClauses[] = 'LOWER(TRIM(email)) = ?';
        $ownerParams[]  = $patientEmail;
    }
    if (empty($ownerClauses)) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Please sign in again.']);
        exit;
    }

    $stmt = $pdo->prepare("
        SELECT id, patient_id, reference_no, full_name, department, status, appointment_date
        FROM patients
        WHERE id = ? AND (" . implode(' OR ', $ownerClauses) . ")
        LIMIT 1
    ");
    $stmt->execute($ownerParams);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'That visit was not found on your account.']);
        exit;
    }

    $status = strtolower(trim($row['status'] ?? ''));
    if (!in_array($status, ['pending', 'for_approval', 'for approval', 'for_processing'], true)) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'This visit is already approved, so it can no longer be rescheduled here.']);
        exit;
    }

    // 1. Validate against hospital clinic hours, holidays, and department capacity rules
    $deptRaw = trim((string)($row['department'] ?? 'General Medical'));
    $ruleErr = validateBookingAgainstRules($pdo, $deptRaw, $newDate, $newTime);
    if ($ruleErr !== null) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => $ruleErr]);
        exit;
    }

    // 2. Validate against department doctors' available status, time blocks, and daily slot quota
    $deptLower = strtolower($deptRaw);
    if (str_contains($deptLower, 'psych') || str_contains($deptLower, 'opu')) {
        $docWhere = "(LOWER(department) LIKE '%psych%' OR LOWER(department) LIKE '%opu%')";
        $deptDisplay = 'Psychiatry';
    } elseif (str_contains($deptLower, 'neuro')) {
        $docWhere = "LOWER(department) LIKE '%neuro%'";
        $deptDisplay = 'Neurology';
    } else {
        $docWhere = "(LOWER(department) LIKE '%general%' OR LOWER(department) LIKE '%medical%')";
        $deptDisplay = 'General Medical';
    }

    $docRows = $pdo->query("SELECT name, status, schedule_start, schedule_end, total_slots FROM doctors WHERE {$docWhere}")->fetchAll(PDO::FETCH_ASSOC);
    if (!empty($docRows)) {
        $toMins = static function (?string $t, int $fallback): int {
            if (!$t || !preg_match('/^(\d{1,2}):(\d{2})/', trim($t), $m)) return $fallback;
            return ((int)$m[1]) * 60 + (int)$m[2];
        };
        $sessionStartMin = $toMins($newTime, 480);
        $sessionEndMin   = $sessionStartMin + 120; // 2-hour clinic session window
        $sessionLabels   = [
            '08:00:00' => '08:00 AM – 10:00 AM',
            '10:00:00' => '10:00 AM – 12:00 PM',
            '13:00:00' => '01:00 PM – 03:00 PM',
            '15:00:00' => '03:00 PM – 05:00 PM',
        ];
        $reqWindowLabel = $sessionLabels[$newTime] ?? $newTime;
        $dateFormatted  = date('M j, Y', strtotime($newDate));

        $activeDocs = [];
        $matchingSlotDocs = [];
        $availableWindows = [];

        foreach ($docRows as $d) {
            $dSt = strtolower(trim((string)($d['status'] ?? '')));
            $isDocIn = in_array($dSt, ['in', 'available', 'in_clinic', ''], true);
            if (!$isDocIn) continue;
            $activeDocs[] = $d;

            $dStart = $toMins($d['schedule_start'] ?? null, 480);
            $dEnd   = $toMins($d['schedule_end'] ?? null, 1020);
            $winStr = date('g:i A', strtotime($d['schedule_start'] ?: '08:00:00')) . ' – ' . date('g:i A', strtotime($d['schedule_end'] ?: '17:00:00'));
            $availableWindows[$winStr] = true;

            // Doctor covers the requested session block if their shift overlaps the requested 2-hour block
            if ($dStart < $sessionEndMin && $dEnd > $sessionStartMin) {
                $totalSlots = max(1, (int)($d['total_slots'] ?? 20));
                $cleanDocName = trim(preg_replace('/^Dr\.?\s*/i', '', (string)($d['name'] ?? '')));
                $cntStmt = $pdo->prepare("
                    SELECT COUNT(*)
                    FROM patients
                    WHERE id <> ?
                      AND appointment_date = ?
                      AND LOWER(COALESCE(status, '')) NOT IN ('cancelled', 'rejected', 'no-show', 'transferred', 'cancelled_by_patient')
                      AND (
                        LOWER(TRIM(COALESCE(assigned_doctor, ''))) = LOWER(?)
                        OR LOWER(TRIM(COALESCE(assigned_doctor, ''))) = LOWER(?)
                      )
                ");
                $cntStmt->execute([$recordId, $newDate, (string)$d['name'], $cleanDocName]);
                $booked = (int)$cntStmt->fetchColumn();
                if ($booked < $totalSlots) {
                    $matchingSlotDocs[] = $d;
                }
            }
        }

        if (empty($activeDocs)) {
            http_response_code(422);
            echo json_encode([
                'success' => false,
                'message' => "No {$deptDisplay} physician is currently on duty for {$dateFormatted}. Please choose another date or contact the clinic desk."
            ]);
            exit;
        }
        if (empty($matchingSlotDocs)) {
            $winHint = !empty($availableWindows) ? ' Available doctor hours: ' . implode(', ', array_keys($availableWindows)) . '.' : '';
            http_response_code(422);
            echo json_encode([
                'success' => false,
                'message' => "No {$deptDisplay} physician is available for {$reqWindowLabel} on {$dateFormatted}.{$winHint}"
            ]);
            exit;
        }
    }

    $slotLabel = ($newTime === '13:00:00' || $newTime === '15:00:00')
        ? 'Afternoon (1:00 PM - 5:00 PM)'
        : 'Morning (8:00 AM - 12:00 PM)';
    $oldDate = trim((string)($row['appointment_date'] ?? ''));
    $fromDateVal = ($oldDate !== '' && $oldDate !== $newDate) ? $oldDate : null;

    $upd = $pdo->prepare("
        UPDATE patients
        SET appointment_date = ?,
            scheduled_time = ?,
            preferred_time_slot = ?,
            rescheduled_by_staff = 1,
            rescheduled_from_date = COALESCE(?, rescheduled_from_date),
            rescheduled_at = NOW(),
            updated_at = NOW()
        WHERE id = ?
    ");
    $upd->execute([$newDate, $newTime, $slotLabel, $fromDateVal, $recordId]);

    try {
        $end = date('H:i:s', strtotime($newTime . ' +30 minutes'));
        $refNo = trim((string)($row['reference_no'] ?? ''));
        if ($refNo !== '') {
            $pdo->prepare("
                UPDATE appointments
                SET appointment_date = ?, start_time = ?, end_time = ?, preferred_time_slot = ?
                WHERE reference_no = ? OR (patient_id = ? AND appointment_date = ?)
            ")->execute([$newDate, $newTime, $end, $slotLabel, $refNo, $row['patient_id'], $row['appointment_date']]);
        } else {
            $pdo->prepare("
                UPDATE appointments
                SET appointment_date = ?, start_time = ?, end_time = ?, preferred_time_slot = ?
                WHERE patient_id = ? AND appointment_date = ?
            ")->execute([$newDate, $newTime, $end, $slotLabel, $row['patient_id'], $row['appointment_date']]);
        }
    } catch (Throwable $e) {
        // The portal list reads the patients row. The schedule mirror is optional.
    }

    echo json_encode([
        'success' => true,
        'message' => 'Your visit is rescheduled and still waiting for staff approval.'
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    error_log('Patient reschedule error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Could not reschedule this visit.']);
}
