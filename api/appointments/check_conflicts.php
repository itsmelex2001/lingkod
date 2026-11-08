<?php
/**
 * api/appointments/check_conflicts.php
 * Endpoint: GET /api/appointments/check_conflicts.php?date=YYYY-MM-DD
 * 
 * Returns existing bookings and disabled time periods (Morning / Afternoon)
 * for the authenticated patient on a given date.
 */

session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/db.php';

$date = trim($_GET['date'] ?? $_GET['scheduled_date'] ?? '');
if ($date === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Valid date parameter required (YYYY-MM-DD).']);
    exit;
}

$sessionPatientId = $_SESSION['patient_id'] ?? '';
$sessionEmail     = $_SESSION['patient_email'] ?? '';
$patientId        = trim($_GET['patient_id'] ?? $sessionPatientId);
$email            = trim($_GET['email'] ?? $sessionEmail);

if ($patientId === '' && $email === '') {
    echo json_encode([
        'success'           => true,
        'date'              => $date,
        'disabled_periods'  => [],
        'disabled_times'    => [],
        'existing_bookings' => []
    ]);
    exit;
}

$stmt = $pdo->prepare("
    SELECT id, patient_id, department, appointment_date, scheduled_time, status
    FROM patients
    WHERE (patient_id = :pid OR (email = :email AND email != ''))
      AND appointment_date = :adate
      AND LOWER(status) NOT IN ('cancelled', 'rejected', 'cancelled_by_patient', 'no-show')
");
$stmt->execute([
    ':pid'   => $patientId,
    ':email' => $email,
    ':adate' => $date
]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$disabledPeriods = [];
$disabledTimes = [];

foreach ($rows as $r) {
    $time = $r['scheduled_time'] ?: '08:00:00';
    $hour = (int)date('H', strtotime($time));
    $period = ($hour < 12) ? 'Morning' : 'Afternoon';

    if (!in_array($period, $disabledPeriods, true)) {
        $disabledPeriods[] = $period;
    }

    if ($period === 'Morning') {
        $disabledTimes[] = '08:00:00';
        $disabledTimes[] = '10:00:00';
    } else {
        $disabledTimes[] = '13:00:00';
        $disabledTimes[] = '15:00:00';
    }
}

$disabledTimes = array_values(array_unique($disabledTimes));

echo json_encode([
    'success'           => true,
    'date'              => $date,
    'has_conflict'      => count($disabledPeriods) > 0,
    'disabled_periods'  => $disabledPeriods,
    'disabled_times'    => $disabledTimes,
    'can_book'          => count($disabledPeriods) < 2,
    'notice'            => count($disabledPeriods) === 1
        ? "You already have a {$disabledPeriods[0]} appointment on this date. Your second booking must be in the " . ($disabledPeriods[0] === 'Morning' ? 'Afternoon' : 'Morning') . "."
        : (count($disabledPeriods) >= 2 ? "You have reached the maximum daily bookings (Morning & Afternoon) for this date." : "")
]);

