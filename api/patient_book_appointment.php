<?php
/**
 * api/patient_book_appointment.php
 * Handles appointment booking for authenticated patients:
 * - Validates patient session
 * - Validates department, personal, medical, and appointment details
 * - Generates unique PT-YYYY-XXX patient/appointment ID
 * - Links to patient's account (email, HRN, credentials)
 * - Inserts into `patients` table with status 'for_approval'
 * - Records initial audit note into `progress_notes` and `system_logs`
 */

session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/config/cors.php';
require_once __DIR__ . '/config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

// 1. Authenticate patient session
if (empty($_SESSION['patient_logged_in']) || (empty($_SESSION['patient_id']) && empty($_SESSION['patient_email']))) {
    http_response_code(401);
    echo json_encode([
        'success'  => false,
        'message'  => 'Session expired. Please sign in again to book an appointment.',
        'redirect' => '/sdn/pages/patient-login.php'
    ]);
    exit;
}

$sessionPatientId = $_SESSION['patient_id'] ?? '';
$sessionEmail     = $_SESSION['patient_email'] ?? '';

// 2. Parse input data
$raw = file_get_contents('php://input');
$b = json_decode($raw, true);
if (!is_array($b)) {
    $b = $_POST;
}

// 3. Extract and sanitize fields
$department     = trim($b['department'] ?? '');
$inquiryOnly    = !empty($b['inquiry_only']) ? 1 : 0;
$firstName      = trim($b['first_name'] ?? '');
$lastName       = trim($b['last_name'] ?? '');
$middleInitial  = trim($b['middle_initial'] ?? '');
$gender         = trim($b['gender'] ?? '');
$birthdate      = trim($b['birthdate'] ?? '');
$contactNumber  = trim($b['contact_number'] ?? '');
$email          = trim(strtolower($b['email'] ?? $sessionEmail));
$address        = trim($b['address'] ?? '');
$bookingFor     = trim($b['booking_for'] ?? 'Myself');
$isDependent    = ($bookingFor === 'Someone Else / Dependent' || $bookingFor === 'someone_else' || !empty($b['booking_for_someone_else']));
$relationshipToPatient = trim($b['relationship_to_patient'] ?? '');
$withCompanion  = $isDependent ? 1 : (!empty($b['with_companion']) && in_array(strtolower((string)$b['with_companion']), ['1', 'true', 'yes'], true) ? 1 : 0);

$chiefComplaints    = trim($b['chief_complaints'] ?? '');
$consultationNature = trim($b['consultation_nature'] ?? ($inquiryOnly ? 'General Inquiry' : 'Routine Consultation'));
$illnessDescription = trim($b['illness_description'] ?? '');
$referralSource     = trim($b['referral_source'] ?? 'Self-Referral');

$consultationMode = trim($b['consultation_mode'] ?? 'Face-to-Face');
$appointmentDate  = trim($b['appointment_date'] ?? '');
$scheduledTime    = trim($b['scheduled_time'] ?? '08:00:00');
if (strpos($scheduledTime, '-') !== false) {
    $timeStart = trim(explode('-', $scheduledTime)[0]);
    $parsedTs = strtotime($timeStart);
    if ($parsedTs !== false) {
        $scheduledTime = date('H:i:s', $parsedTs);
    }
}

// 4. Strict Validation
if ($firstName === '' || $lastName === '') {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'First Name and Last Name are required.']);
    exit;
}

if ($gender === '') {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Please select a gender.']);
    exit;
}

if ($contactNumber === '') {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Contact number is required.']);
    exit;
}

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'A valid email address is required.']);
    exit;
}

if ($address === '') {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Home address is required.']);
    exit;
}

if ($isDependent && $relationshipToPatient === '') {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Please select your relationship to the patient.']);
    exit;
}

// If not inquiry-only, require medical complaints and future appointment date
if (!$inquiryOnly) {
    if ($chiefComplaints === '') {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Chief complaints / reason for consultation is required.']);
        exit;
    }

    if ($appointmentDate === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $appointmentDate)) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Please select a valid appointment date.']);
        exit;
    }

    $minDate = date('Y-m-d', strtotime('+1 day'));
    $maxDate = date('Y-m-d', strtotime('+1 year'));
    if ($appointmentDate < $minDate) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => "Appointments must be scheduled at least 1 day in advance (earliest available: $minDate)."]);
        exit;
    }
    if ($appointmentDate > $maxDate) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => "Appointments cannot be scheduled more than 1 year in advance (latest available: $maxDate)."]);
        exit;
    }

    // Check Sunday closure
    if ((int)date('w', strtotime($appointmentDate)) === 0) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'The hospital outpatient clinics are closed on Sundays. Please select a Monday to Saturday date.']);
        exit;
    }

    // First-time patient restriction: initial consultation MUST be Face-to-Face
    $stmtPrior = $pdo->prepare("
        SELECT COUNT(*) 
        FROM patients 
        WHERE (patient_id = :pid OR (email = :pemail AND email != ''))
          AND appointment_date IS NOT NULL
          AND LOWER(status) NOT IN ('cancelled', 'rejected', 'cancelled_by_patient')
    ");
    $stmtPrior->execute([
        ':pid'    => $sessionPatientId,
        ':pemail' => $sessionEmail
    ]);
    $isFirstTimePatient = ((int)$stmtPrior->fetchColumn() === 0);

    if ($isFirstTimePatient && strtolower($consultationMode) !== 'face-to-face') {
        http_response_code(422);
        echo json_encode([
            'success' => false,
            'message' => 'First-time patients are required to attend an initial Face-to-Face consultation at the hospital. Online teleconsultation is available for returning follow-up patients only.'
        ]);
        exit;
    }
} else {
    // In inquiry mode, if no date provided, set tentative date
    if ($appointmentDate === '') {
        $appointmentDate = date('Y-m-d', strtotime('+1 day'));
    }
    if ($chiefComplaints === '') {
        $chiefComplaints = 'General inquiry regarding department services and consultation schedule.';
    }
}

// Clean and format middle initial
$cleanMI = null;
if ($middleInitial !== '') {
    $cleanMI = str_ends_with($middleInitial, '.') ? strtoupper($middleInitial) : strtoupper($middleInitial) . '.';
}
$fullName = $cleanMI ? "$firstName $cleanMI $lastName" : "$firstName $lastName";

// Calculate age
$age = null;
if ($birthdate !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $birthdate)) {
    $birthObj = new DateTime($birthdate);
    $todayObj = new DateTime();
    $age = $todayObj->diff($birthObj)->y;
}

// 5. Retrieve existing patient account credentials and HRN
$stmtExisting = $pdo->prepare("
    SELECT hrn, username, password, is_verified 
    FROM patients 
    WHERE (patient_id = ? OR email = ?) AND password IS NOT NULL 
    ORDER BY id DESC 
    LIMIT 1
");
$stmtExisting->execute([$sessionPatientId, $sessionEmail]);
$existingAccount = $stmtExisting->fetch(PDO::FETCH_ASSOC);

$userPassword = $existingAccount['password'] ?? null;
$username     = $existingAccount['username'] ?? null;
$isVerified   = !empty($existingAccount['is_verified']) ? 1 : 1;
$hrn          = !empty($existingAccount['hrn']) ? $existingAccount['hrn'] : null;

// Normalize department name (optional, staff will triage if unassigned)
$rawDept = strtolower($department);
if ($department === '' || $rawDept === 'null' || $rawDept === 'unassigned') {
    $deptName = 'Triage Pending';
} elseif (str_contains($rawDept, 'general') || str_contains($rawDept, 'medical')) {
    $deptName = 'General Medical';
} elseif (str_contains($rawDept, 'psychiatry') || str_contains($rawDept, 'opu')) {
    $deptName = 'OPU Psychiatry';
} elseif (str_contains($rawDept, 'neuro')) {
    $deptName = 'Neurology';
} else {
    $deptName = $department;
}

// Anti-Double Booking / Same-Day Time Conflict Validation
if (!$inquiryOnly && $appointmentDate !== '') {
    $reqHour = (int)date('H', strtotime($scheduledTime));
    $reqPeriod = ($reqHour < 12) ? 'Morning' : 'Afternoon';

    $stmtConflict = $pdo->prepare("
        SELECT id, scheduled_time, status
        FROM patients
        WHERE (patient_id = :pid OR email = :pemail)
          AND appointment_date = :adate
          AND LOWER(status) NOT IN ('cancelled', 'rejected', 'cancelled_by_patient', 'no-show')
    ");
    $stmtConflict->execute([
        ':pid'    => $sessionPatientId,
        ':pemail' => $sessionEmail,
        ':adate'  => $appointmentDate
    ]);
    $existingDayBookings = $stmtConflict->fetchAll(PDO::FETCH_ASSOC);

    foreach ($existingDayBookings as $eb) {
        $ebTime = $eb['scheduled_time'] ?: '08:00:00';
        $ebHour = (int)date('H', strtotime($ebTime));
        $ebPeriod = ($ebHour < 12) ? 'Morning' : 'Afternoon';

        if ($ebPeriod === $reqPeriod) {
            http_response_code(409);
            echo json_encode([
                'success' => false,
                'error'   => 'Conflict',
                'message' => 'You already have an appointment scheduled for this time. Please select a different time (e.g., Afternoon) for your second consultation.'
            ]);
            exit;
        }
    }
}

// Function to generate new sequential PT-YYYY-XXX ID
function generateNewPatientId(PDO $pdo): string {
    $year = date('Y');
    $stmt = $pdo->prepare("
        SELECT MAX(CAST(SUBSTRING_INDEX(patient_id, '-', -1) AS UNSIGNED))
        FROM patients
        WHERE patient_id LIKE ?
    ");
    $stmt->execute(["PT-$year-%"]);
    $max = (int)$stmt->fetchColumn();
    return 'PT-' . $year . '-' . str_pad($max + 1, 3, '0', STR_PAD_LEFT);
}

try {
    $pdo->beginTransaction();

    $newPatientId = generateNewPatientId($pdo);

    if (!$hrn) {
        $hrn = 'HRN-' . substr($newPatientId, 3);
    }

    $userId = $_SESSION['user_id'] ?? $_SESSION['patient_user_id'] ?? null;
    if (!$userId && $email !== '') {
        $uStmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $uStmt->execute([$email]);
        $userId = $uStmt->fetchColumn() ?: null;
    }

    $sql = "INSERT INTO patients (
        patient_id, user_id, department, full_name, first_name, last_name, middle_initial,
        gender, birthdate, age, contact_number, email, username, password, is_verified,
        address, with_companion, relationship_to_patient, chief_complaints, consultation_nature, illness_description,
        patient_type, appointment_type, consultation_mode, appointment_date, scheduled_time,
        referral_source, status, unscheduled_walkin, inquiry_only, mode_of_inquiry,
        select_type, hrn, specialty
    ) VALUES (
        ?, ?, ?, ?, ?, ?, ?,
        ?, ?, ?, ?, ?, ?, ?, ?,
        ?, ?, ?, ?, ?, ?,
        ?, ?, ?, ?, ?,
        ?, ?, ?, ?, ?,
        ?, ?, ?
    )";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $newPatientId,
        $userId,
        $deptName,
        $fullName,
        $firstName,
        $lastName,
        $cleanMI,
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
        $relationshipToPatient ?: null,
        $chiefComplaints,
        $consultationNature,
        $illnessDescription ?: null,
        !empty($isFirstTimePatient) ? 'first-time' : 'returning',
        'online',
        $consultationMode,
        $appointmentDate,
        $scheduledTime,
        $referralSource,
        'for_approval',
        0,
        $inquiryOnly,
        $inquiryOnly ? 'Online Portal' : null,
        'Scheduled',
        $hrn,
        $deptName . ' Consultation'
    ]);

    // Initial Progress Note
    $noteText = $inquiryOnly
        ? "Online Department Inquiry submitted via Patient Portal. Status: For Approval."
        : "Online Appointment Booked via Patient Portal ({$deptName} on {$appointmentDate} at " . date('h:i A', strtotime($scheduledTime)) . "). Status: For Approval." . ($isDependent && $relationshipToPatient ? " [Booked for Dependent - Relationship: {$relationshipToPatient}]" : "");

    $stmtNote = $pdo->prepare("
        INSERT INTO progress_notes (patient_id, note, noted_by, created_at)
        VALUES (?, ?, 'Patient Portal', NOW())
    ");
    $stmtNote->execute([$newPatientId, $noteText]);

    // Mirror the booking into the shared appointments table so staff and admin
    // schedules stay tied to the same patient record the portal just created.
    if (!$inquiryOnly && $appointmentDate !== '') {
        try {
            $start = date('H:i:s', strtotime($scheduledTime));
            $end = date('H:i:s', strtotime($scheduledTime . ' +30 minutes'));
            $pdo->prepare("
                INSERT INTO appointments (patient_id, department, appointment_date, start_time, end_time, status, notes, relationship_to_patient)
                VALUES (?, ?, ?, ?, ?, 'scheduled', ?, ?)
            ")->execute([
                $newPatientId,
                $deptName,
                $appointmentDate,
                $start,
                $end,
                'Booked from the patient portal. Waiting for department approval.',
                $relationshipToPatient ?: null
            ]);
        } catch (Exception $e) {
            // The patients row is the queue source of truth if this table is absent.
        }
    }

    // System log
    try {
        $stmtLog = $pdo->prepare("
            INSERT INTO system_logs (action, description, performed_by, created_at)
            VALUES (?, ?, ?, NOW())
        ");
        $stmtLog->execute([
            'PATIENT_BOOK_APPOINTMENT',
            "Patient {$fullName} ({$newPatientId}) booked {$deptName} appointment for {$appointmentDate}",
            $email
        ]);
    } catch (Exception $e) {
        // Non-critical log failure ignored
    }

    $pdo->commit();

    echo json_encode([
        'success'          => true,
        'message'          => $inquiryOnly ? 'Inquiry submitted successfully!' : 'Appointment booked successfully!',
        'patient_id'       => $newPatientId,
        'department'       => $deptName,
        'appointment_date' => $appointmentDate,
        'scheduled_time'   => date('h:i A', strtotime($scheduledTime)),
        'redirect'         => '/sdn/pages/patient-dashboard.php'
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    error_log("Patient appointment booking DB error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to book appointment: ' . $e->getMessage()
    ]);
}

