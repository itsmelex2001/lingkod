<?php
/**
 * Patient appointment cancellation.
 * Allowed only while pending/for approval or confirmed (not yet checked in, in consultation, completed, or past).
 */
date_default_timezone_set('Asia/Manila');
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/config/cors.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/patient_remember.php';

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

if ($recordId <= 0) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Invalid appointment selected.']);
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
        SELECT id, patient_id, reference_no, full_name, department, status, appointment_date, scheduled_time, created_at
        FROM patients
        WHERE id = ? AND (" . implode(' OR ', $ownerClauses) . ")
        LIMIT 1
    ");
    $stmt->execute($ownerParams);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'That appointment was not found on your account.']);
        exit;
    }

    $status = strtolower(trim((string)($row['status'] ?? '')));
    $allowedStatuses = [
        'pending', 'for_approval', 'for approval', 'for_processing',
        'approved', 'confirmed', 'scheduled', 'rescheduled',
    ];

    if (in_array($status, ['arrived', 'checked_in', 'checked in'], true)) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'This appointment can no longer be cancelled because you are already checked in.']);
        exit;
    }
    if (in_array($status, ['ongoing', 'in_consultation', 'in consultation'], true)) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'This appointment can no longer be cancelled because the consultation has already started.']);
        exit;
    }
    if ($status === 'completed') {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'Completed appointments cannot be cancelled.']);
        exit;
    }
    if (in_array($status, ['cancelled', 'cancelled_by_patient', 'rejected', 'declined'], true)) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'This appointment is already cancelled.']);
        exit;
    }
    if (!in_array($status, $allowedStatuses, true)) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'This appointment can no longer be cancelled.']);
        exit;
    }

    $now = new DateTime('now', new DateTimeZone('Asia/Manila'));
    $apptDate = !empty($row['appointment_date']) ? $row['appointment_date'] : substr((string)($row['created_at'] ?? $now->format('Y-m-d')), 0, 10);
    $timeIso  = !empty($row['scheduled_time']) ? $row['scheduled_time'] : '08:00:00';
    $apptDateTime = new DateTime($apptDate . ' ' . $timeIso, new DateTimeZone('Asia/Manila'));

    if ($apptDateTime < $now) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'Past appointments can no longer be cancelled.']);
        exit;
    }

    $upd = $pdo->prepare("
        UPDATE patients
        SET status = 'cancelled',
            updated_at = NOW()
        WHERE id = ?
    ");
    $upd->execute([$recordId]);

    try {
        $refNo = trim((string)($row['reference_no'] ?? ''));
        if ($refNo !== '') {
            $pdo->prepare("
                UPDATE appointments
                SET status = 'cancelled'
                WHERE reference_no = ? OR (patient_id = ? AND appointment_date = ?)
            ")->execute([$refNo, $row['patient_id'], $row['appointment_date']]);
        } else {
            $pdo->prepare("
                UPDATE appointments
                SET status = 'cancelled'
                WHERE patient_id = ? AND appointment_date = ?
            ")->execute([$row['patient_id'], $row['appointment_date']]);
        }
    } catch (Throwable $e) {
        // The portal list reads the patients row. The schedule mirror is optional.
    }

    $actor = trim((string)($row['full_name'] ?? '')) ?: ($patientId ?: $patientEmail ?: 'Patient');
    $refLabel = trim((string)($row['reference_no'] ?? '')) ?: ($row['patient_id'] ?: ('APT-' . $recordId));
    try {
        addLog(
            $pdo,
            'info',
            'Patient Cancelled Appointment',
            $actor,
            "Patient cancelled appointment {$refLabel} (was '{$row['status']}')."
        );
    } catch (Throwable $ignoreLog) {}

    echo json_encode([
        'success' => true,
        'message' => 'Your appointment has been cancelled.'
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    error_log('Patient cancel appointment error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Could not cancel this appointment.']);
}
