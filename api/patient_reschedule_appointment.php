<?php
/**
 * Patient reschedule. Allowed only while staff has not approved the visit.
 */
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/config/cors.php';
require_once __DIR__ . '/config/db.php';

if (empty($_SESSION['patient_logged_in']) || (empty($_SESSION['patient_id']) && empty($_SESSION['patient_email']))) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Please sign in again.']);
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

$patientId = $_SESSION['patient_id'] ?? '';
$patientEmail = $_SESSION['patient_email'] ?? '';
$patientName = strtolower(trim($_SESSION['patient_name'] ?? ''));
$userId = $_SESSION['user_id'] ?? $_SESSION['patient_user_id'] ?? null;

try {
    $stmt = $pdo->prepare("
        SELECT id, patient_id, full_name, department, status, appointment_date
        FROM patients
        WHERE id = :id AND (
            (:uid IS NOT NULL AND user_id = :uid)
            OR (:pid != '' AND patient_id = :pid)
            OR (:email != '' AND email = :email)
            OR (:pname != '' AND LOWER(TRIM(full_name)) = :pname)
        )
        LIMIT 1
    ");
    $stmt->execute([
        ':id'    => $recordId,
        ':uid'   => $userId,
        ':pid'   => $patientId,
        ':email' => $patientEmail,
        ':pname' => $patientName
    ]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'That visit was not found on your account.']);
        exit;
    }

    $status = strtolower(trim($row['status'] ?? ''));
    if (!in_array($status, ['pending', 'for_approval', 'for_processing'], true)) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'This visit is already approved, so it can no longer be rescheduled here.']);
        exit;
    }

    $upd = $pdo->prepare("
        UPDATE patients
        SET appointment_date = ?, scheduled_time = ?, updated_at = NOW()
        WHERE id = ?
    ");
    $upd->execute([$newDate, $newTime, $recordId]);

    try {
        $end = date('H:i:s', strtotime($newTime . ' +30 minutes'));
        $pdo->prepare("
            UPDATE appointments
            SET appointment_date = ?, start_time = ?, end_time = ?
            WHERE patient_id = ? AND appointment_date = ?
        ")->execute([$newDate, $newTime, $end, $row['patient_id'], $row['appointment_date']]);
    } catch (Exception $e) {
        // The portal list reads the patients row. The schedule mirror is optional.
    }

    echo json_encode([
        'success' => true,
        'message' => 'Your visit is rescheduled and still waiting for staff approval.'
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    error_log('Patient reschedule error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Could not reschedule this visit.']);
}
