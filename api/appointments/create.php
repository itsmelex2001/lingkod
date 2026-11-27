<?php
/**
 * api/appointments/create.php
 * Endpoint: POST /api/appointments/create (or /api/appointments/create.php)
 * 
 * Business Rules:
 * 1. Triage-led Routing:
 *    - `department_id` / `department` is optional (null or empty string allowed).
 *    - `chief_complaint` is mandatory.
 * 2. Anti-Double Booking (Same-Day Time Conflict Validation):
 *    - A patient can have multiple appointments on the same day, but strictly not at the same time slot (Morning vs Afternoon).
 *    - If conflicting, returns HTTP 409 with custom error message.
 */

session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

$raw = file_get_contents('php://input');
$body = json_decode($raw, true);
if (!is_array($body)) {
    $body = $_POST;
}

// 1. Identify Patient
$sessionPatientId = $_SESSION['patient_id'] ?? '';
$sessionEmail     = $_SESSION['patient_email'] ?? '';
$patientId        = trim($body['patient_id'] ?? $sessionPatientId);
$email            = trim(strtolower($body['email'] ?? $sessionEmail));

if ($patientId === '' && $email === '') {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized. Patient identification is required to book an appointment.'
    ]);
    exit;
}

// Fetch existing patient profile if not supplied in body
$stmtPatient = $pdo->prepare("
    SELECT id, patient_id, hrn, full_name, first_name, last_name, middle_initial, gender,
           birthdate, age, contact_number, email, address, username, password, is_verified
    FROM patients
    WHERE (patient_id = ? OR email = ?) AND full_name IS NOT NULL AND full_name != ''
    ORDER BY id DESC
    LIMIT 1
");
$stmtPatient->execute([$patientId, $email]);
$patientProfile = $stmtPatient->fetch(PDO::FETCH_ASSOC);

$firstName     = trim($body['first_name'] ?? ($patientProfile['first_name'] ?? ''));
$lastName      = trim($body['last_name'] ?? ($patientProfile['last_name'] ?? ''));
$middleInitial = trim($body['middle_initial'] ?? ($patientProfile['middle_initial'] ?? ''));
$fullName      = trim($body['full_name'] ?? ($patientProfile['full_name'] ?? ''));
if ($fullName === '' && $firstName !== '') {
    $fullName = $middleInitial ? "$firstName $middleInitial $lastName" : "$firstName $lastName";
}
$gender        = trim($body['gender'] ?? ($patientProfile['gender'] ?? 'Other'));
$birthdate     = trim($body['birthdate'] ?? ($patientProfile['birthdate'] ?? ''));
$age           = !empty($body['age']) ? (int)$body['age'] : (!empty($patientProfile['age']) ? (int)$patientProfile['age'] : null);
$contactNumber = trim($body['contact_number'] ?? ($patientProfile['contact_number'] ?? ''));
$address       = trim($body['address'] ?? ($patientProfile['address'] ?? ''));
$hrn           = $patientProfile['hrn'] ?? ('HRN-' . substr($patientId ?: 'PT-' . date('Y') . '-001', 3));
$userPassword  = $patientProfile['password'] ?? null;
$username      = $patientProfile['username'] ?? null;
$isVerified    = $patientProfile['is_verified'] ?? 1;

// 2. Department Handling (Optional Triage-led Routing)
$rawDept = trim((string)($body['department_id'] ?? $body['department'] ?? ''));
if ($rawDept === '' || strtolower($rawDept) === 'null' || strtolower($rawDept) === 'unassigned') {
    $deptName = 'Triage Pending';
} else {
    $d = strtolower($rawDept);
    if (str_contains($d, 'general') || str_contains($d, 'medical')) {
        $deptName = 'General Medical';
    } elseif (str_contains($d, 'psych') || str_contains($d, 'opu')) {
        $deptName = 'OPU Psychiatry';
    } elseif (str_contains($d, 'neuro')) {
        $deptName = 'Neurology';
    } else {
        $deptName = ucwords($rawDept);
    }
}

// 3. Chief Complaint (Mandatory)
$chiefComplaint = trim($body['chief_complaint'] ?? $body['chief_complaints'] ?? '');
if ($chiefComplaint === '') {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'error'   => 'Validation Error',
        'message' => 'Chief complaint is required. Please describe your symptoms or reason for visit so our triage staff can appropriately route your care.'
    ]);
    exit;
}

// 4. Appointment Date & Scheduled Time
$scheduledDate = trim($body['scheduled_date'] ?? $body['appointment_date'] ?? '');
if ($scheduledDate === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $scheduledDate)) {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'error'   => 'Validation Error',
        'message' => 'A valid appointment date (YYYY-MM-DD) is required.'
    ]);
    exit;
}

// Cannot book in the past
$today = date('Y-m-d');
if ($scheduledDate < $today) {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'error'   => 'Validation Error',
        'message' => 'Appointment date cannot be in the past.'
    ]);
    exit;
}

// Determine session period helper
function resolveTimePeriod(string $timeStr): string {
    $t = strtolower(trim($timeStr));
    if ($t === 'morning' || str_contains($t, 'morning') || str_contains($t, 'am')) {
        if (!preg_match('/\d{1,2}:\d{2}/', $t)) return 'Morning';
    }
    if ($t === 'afternoon' || str_contains($t, 'afternoon') || str_contains($t, 'pm')) {
        if (!preg_match('/\d{1,2}:\d{2}/', $t)) return 'Afternoon';
    }
    $hour = (int)date('H', strtotime($timeStr));
    return ($hour < 12) ? 'Morning' : 'Afternoon';
}

$scheduledTime = trim($body['scheduled_time'] ?? $body['start_time'] ?? $body['time_slot'] ?? '08:00:00');
if ($scheduledTime === 'Morning')   $scheduledTime = '08:00:00';
if ($scheduledTime === 'Afternoon') $scheduledTime = '13:00:00';

$requestedPeriod = resolveTimePeriod($scheduledTime);

// 5. Anti-Double Booking / Same-Day Time Conflict Validation
// Query all non-cancelled appointments for this patient on the same scheduled date
$stmtCheck = $pdo->prepare("
    SELECT id, patient_id, appointment_date, scheduled_time, status
    FROM patients
    WHERE (patient_id = :pid OR hrn = :hrn OR (email = :email AND email != ''))
      AND appointment_date = :adate
      AND LOWER(status) NOT IN ('cancelled', 'rejected', 'cancelled_by_patient', 'no-show')
");
$stmtCheck->execute([
    ':pid'   => $patientId,
    ':hrn'   => $hrn,
    ':email' => $email,
    ':adate' => $scheduledDate
]);
$existingBookings = $stmtCheck->fetchAll(PDO::FETCH_ASSOC);

foreach ($existingBookings as $eb) {
    $existingTime = $eb['scheduled_time'] ?: '08:00:00';
    $existingPeriod = resolveTimePeriod($existingTime);

    // If both belong to same period (Morning vs Afternoon) or exact matching hour
    if ($existingPeriod === $requestedPeriod) {
        $otherPeriod = ($existingPeriod === 'Morning') ? 'Afternoon' : 'Morning';
        http_response_code(409);
        echo json_encode([
            'success'          => false,
            'error'            => 'Conflict',
            'message'          => "You already have an appointment scheduled for this time. Please select a different time (e.g., {$otherPeriod}) for your second consultation.",
            'conflicting_slot' => $existingPeriod,
            'suggested_slot'   => $otherPeriod,
            'scheduled_date'   => $scheduledDate
        ]);
        exit;
    }
}

// 6. Generate sequential PT ID and insert appointment
function getNextPatientId(PDO $pdo): string {
    return dbNextPatientId($pdo);
}

try {
    $pdo->beginTransaction();

    $newPatientId = getNextPatientId($pdo);
    if (!$hrn) {
        $hrn = 'HRN-' . substr($newPatientId, 3);
    }

    $userId = $_SESSION['user_id'] ?? $_SESSION['patient_user_id'] ?? null;
    $consultationMode = trim($body['consultation_mode'] ?? 'Face-to-Face');
    $consultationNature = trim($body['consultation_nature'] ?? 'Routine Consultation');
    $withCompanion = !empty($body['with_companion']) ? 1 : 0;
    $illnessDescription = trim($body['illness_description'] ?? '');

    $insertSql = "INSERT INTO patients (
        patient_id, user_id, department, full_name, first_name, last_name, middle_initial,
        gender, birthdate, age, contact_number, email, username, password, is_verified,
        address, with_companion, chief_complaints, consultation_nature, illness_description,
        patient_type, appointment_type, consultation_mode, appointment_date, scheduled_time,
        referral_source, status, unscheduled_walkin, inquiry_only, select_type, hrn, specialty
    ) VALUES (
        ?, ?, ?, ?, ?, ?, ?,
        ?, ?, ?, ?, ?, ?, ?, ?,
        ?, ?, ?, ?, ?,
        ?, ?, ?, ?, ?,
        ?, 'for_approval', 0, 0, 'Scheduled', ?, ?
    )";

    $stmtInsert = $pdo->prepare($insertSql);
    $stmtInsert->execute([
        $newPatientId,
        $userId,
        $deptName,
        $fullName ?: 'Patient',
        $firstName,
        $lastName,
        $middleInitial,
        $gender,
        $birthdate ?: null,
        $age,
        $contactNumber,
        $email,
        $username,
        $userPassword,
        $isVerified,
        $address,
        $withCompanion,
        $chiefComplaint,
        $consultationNature,
        $illnessDescription ?: null,
        'returning',
        'online',
        $consultationMode,
        $scheduledDate,
        $scheduledTime,
        trim($body['referral_source'] ?? 'Self-Referral'),
        $hrn,
        ($deptName === 'Triage Pending') ? 'Triage Evaluation' : ($deptName . ' Consultation')
    ]);

    // Mirror to progress notes
    $noteText = "Online appointment booked ({$deptName} on {$scheduledDate} at " . date('h:i A', strtotime($scheduledTime)) . " [{$requestedPeriod}]). Status: For Approval.";
    $stmtNote = $pdo->prepare("INSERT INTO progress_notes (patient_id, note, noted_by, created_at) VALUES (?, ?, 'Patient Portal', NOW())");
    $stmtNote->execute([$newPatientId, $noteText]);

    // Mirror to appointments table if available
    try {
        $startTime = date('H:i:s', strtotime($scheduledTime));
        $endTime = date('H:i:s', strtotime($scheduledTime . ' +2 hours'));
        $pdo->prepare("
            INSERT INTO appointments (patient_id, department, appointment_date, start_time, end_time, status, notes)
            VALUES (?, ?, ?, ?, ?, 'scheduled', ?)
        ")->execute([
            $newPatientId,
            $deptName,
            $scheduledDate,
            $startTime,
            $endTime,
            "Chief Complaint: " . substr($chiefComplaint, 0, 180)
        ]);
    } catch (Exception $e) {
        // Shared table fallback
    }

    $pdo->commit();

    http_response_code(201);
    echo json_encode([
        'success'          => true,
        'message'          => 'Appointment booked successfully and queued for staff review.',
        'patient_id'       => $newPatientId,
        'reference_id'     => $newPatientId,
        'scheduled_date'   => $scheduledDate,
        'scheduled_time'   => $scheduledTime,
        'time_period'      => $requestedPeriod,
        'department'       => $deptName,
        'status'           => 'for_approval'
    ]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => 'Server Error',
        'message' => 'Failed to process appointment booking: ' . $e->getMessage()
    ]);
}

