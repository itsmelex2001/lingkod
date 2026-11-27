<?php
/**
 * api/patient_book_appointment.php
 * Handles 4-step appointment booking submissions (logged-in patients or direct bookings):
 * - Validates department, personal, medical, and appointment details
 * - Generates unique PT-YYYY-XXX ID and APT-YYYY-XXXX reference number
 * - Links to existing patient account if matching email/session exists
 * - Inserts into `patients` (status 'for_approval') and mirrors to `appointments`
 * - Records initial audit note into `progress_notes` and `system_logs`
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/config/cors.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/settings_helper.php';

function isPatientFirstTime(PDO $pdo, string $sessionPatientId, string $email, string $firstName, string $lastName, string $birthdate, bool $isOtherPatient): bool {
    $baseWhere = "COALESCE(inquiry_only, 0) = 0
        AND LOWER(COALESCE(status, '')) IN ('completed', 'attended')";

    $firstName = trim($firstName);
    $lastName  = trim($lastName);
    $birthdate = trim($birthdate);
    $email     = trim(strtolower($email));
    $sessionPatientId = trim($sessionPatientId);

    $conditions = [];
    $params     = [];

    if ($firstName !== '' && $lastName !== '') {
        $simpleFull = trim("$firstName $lastName");
        $nameSql = "((LOWER(TRIM(COALESCE(first_name, ''))) = LOWER(?) AND LOWER(TRIM(COALESCE(last_name, ''))) = LOWER(?)) OR LOWER(TRIM(COALESCE(full_name, ''))) = LOWER(?))";
        $nameParams = [$firstName, $lastName, $simpleFull];
        if ($birthdate !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $birthdate)) {
            $nameSql = "($nameSql AND (birthdate IS NULL OR birthdate = ?))";
            $nameParams[] = $birthdate;
        }
        $conditions[] = $nameSql;
        array_push($params, ...$nameParams);
    }

    if (!$isOtherPatient && ($sessionPatientId !== '' || $email !== '')) {
        $acctClauses = [];
        $acctParams  = [];
        if ($sessionPatientId !== '') {
            $acctClauses[] = 'patient_id = ?';
            $acctParams[]  = $sessionPatientId;
        }
        if ($email !== '') {
            $acctClauses[] = 'LOWER(TRIM(email)) = LOWER(?)';
            $acctParams[]  = $email;
        }
        $conditions[] = "(COALESCE(appointment_recipient, 'Patient (Me)') NOT IN ('Other Patient', 'Someone Else / Dependent') AND (" . implode(' OR ', $acctClauses) . '))';
        array_push($params, ...$acctParams);
    }

    if (empty($conditions)) {
        return true;
    }

    $sql = "SELECT COUNT(*) FROM patients WHERE ($baseWhere) AND (" . implode(' OR ', $conditions) . ')';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return ((int)$stmt->fetchColumn() === 0);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['action'] ?? '') === 'check_patient_status') {
    $rawRecipient = trim((string)($_GET['recipient'] ?? $_GET['booking_for'] ?? 'Patient (Me)'));
    $isOther = in_array(strtolower($rawRecipient), ['other patient', 'someone else / dependent', 'someone_else'], true);
    $qSessionPid = trim((string)($_SESSION['patient_id'] ?? ''));
    $qEmail      = trim(strtolower((string)($_GET['email'] ?? ($_SESSION['patient_email'] ?? ''))));
    $qFirst      = trim((string)($_GET['first_name'] ?? ''));
    $qLast       = trim((string)($_GET['last_name'] ?? ''));
    $qBirth      = trim((string)($_GET['birthdate'] ?? ''));

    $firstTime = isPatientFirstTime($pdo, $qSessionPid, $qEmail, $qFirst, $qLast, $qBirth, $isOther);
    echo json_encode([
        'success'       => true,
        'is_first_time' => $firstTime,
        'patient_type'  => $firstTime ? 'first-time' : 'returning',
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

$sessionPatientId = trim((string)($_SESSION['patient_id'] ?? ''));
$sessionEmail     = trim((string)($_SESSION['patient_email'] ?? ''));

// Parse input data
$raw = file_get_contents('php://input');
$b = json_decode($raw, true);
if (!is_array($b)) {
    $b = $_POST;
}

// Extract and sanitize fields
$department     = trim((string)($b['department'] ?? ''));
$inquiryOnly    = !empty($b['inquiry_only']) ? 1 : 0;
$firstName      = trim((string)($b['first_name'] ?? ''));
$lastName       = trim((string)($b['last_name'] ?? ''));
$middleInitial  = trim((string)($b['middle_initial'] ?? ''));
$gender         = trim((string)($b['gender'] ?? ''));
$birthdate      = trim((string)($b['birthdate'] ?? ''));
$contactNumber  = trim((string)($b['contact_number'] ?? ''));
$email          = trim(strtolower((string)($b['email'] ?? $sessionEmail)));
$address        = trim((string)($b['address'] ?? ''));

$rawRecipient   = trim((string)($b['appointment_recipient'] ?? $b['booking_for'] ?? 'Patient (Me)'));
$isOtherPatient = in_array(strtolower($rawRecipient), ['other patient', 'someone else / dependent', 'someone_else'], true)
    || !empty($b['booking_for_someone_else']);
$appointmentRecipient  = $isOtherPatient ? 'Other Patient' : 'Patient (Me)';
$relationshipToPatient = trim((string)($b['relationship_to_patient'] ?? ''));
$withCompanion         = (!empty($b['with_companion']) && in_array(strtolower((string)$b['with_companion']), ['1', 'true', 'yes'], true)) ? 1 : 0;

// Server-side identity guard: for signed-in Patient (Me) bookings, enforce verified owner identity
// so client-side edits cannot decouple email or attach another person's name/DOB to the owner's HRN.
$isSignedInPatient = !empty($_SESSION['patient_logged_in']) && ($sessionPatientId !== '' || $sessionEmail !== '');
if ($isSignedInPatient && !$isOtherPatient) {
    $ownerProfile = null;
    try {
        $sessUid = $_SESSION['user_id'] ?? $_SESSION['patient_user_id'] ?? null;
        if ($sessUid) {
            $uOwnerStmt = $pdo->prepare("SELECT patient_id FROM users WHERE id = ? LIMIT 1");
            $uOwnerStmt->execute([$sessUid]);
            $ownerRowId = $uOwnerStmt->fetchColumn();
            if ($ownerRowId && is_numeric($ownerRowId)) {
                $stmtOwner = $pdo->prepare("
                    SELECT first_name, last_name, middle_initial, full_name, gender, birthdate, email
                    FROM patients
                    WHERE id = ?
                    LIMIT 1
                ");
                $stmtOwner->execute([(int)$ownerRowId]);
                $ownerProfile = $stmtOwner->fetch(PDO::FETCH_ASSOC) ?: null;
            }
        }
        if (!$ownerProfile) {
            $stmtOwner = $pdo->prepare("
                SELECT first_name, last_name, middle_initial, full_name, gender, birthdate, email
                FROM patients
                WHERE ((patient_id != '' AND patient_id = :pid) OR (email != '' AND LOWER(TRIM(email)) = LOWER(:pemail)))
                  AND COALESCE(appointment_recipient, 'Patient (Me)') NOT IN ('Other Patient', 'Someone Else / Dependent')
                ORDER BY (password IS NOT NULL AND password <> '') DESC, id ASC
                LIMIT 1
            ");
            $stmtOwner->execute([':pid' => $sessionPatientId, ':pemail' => $sessionEmail]);
            $ownerProfile = $stmtOwner->fetch(PDO::FETCH_ASSOC) ?: null;
        }
    } catch (Throwable $ignoreOwner) {
        $ownerProfile = null;
    }

    if ($ownerProfile) {
        $oFirst = trim((string)($ownerProfile['first_name'] ?? ''));
        $oLast  = trim((string)($ownerProfile['last_name'] ?? ''));
        $oMi    = trim((string)($ownerProfile['middle_initial'] ?? ''));
        if ($oFirst === '' && $oLast === '' && !empty($ownerProfile['full_name'])) {
            $parts = preg_split('/\s+/', trim((string)$ownerProfile['full_name'])) ?: [];
            if (count($parts) === 1) {
                $oFirst = $parts[0];
            } elseif (count($parts) === 2) {
                $oFirst = $parts[0];
                $oLast  = $parts[1];
            } elseif (count($parts) >= 3) {
                $oFirst = $parts[0];
                $oLast  = end($parts);
                $oMi    = $parts[1];
            }
        }
        if ($oFirst !== '') $firstName = $oFirst;
        if ($oLast !== '') $lastName = $oLast;
        if ($oFirst !== '' && $oLast !== '') $middleInitial = $oMi;
        $oGender = trim((string)($ownerProfile['gender'] ?? ''));
        if ($oGender !== '') $gender = $oGender;
        $oBirth = trim((string)($ownerProfile['birthdate'] ?? ''));
        if ($oBirth !== '') $birthdate = $oBirth;
        $oEmail = trim(strtolower((string)($ownerProfile['email'] ?? $sessionEmail)));
        if ($oEmail !== '') $email = $oEmail;
    } elseif ($sessionEmail !== '') {
        $email = strtolower($sessionEmail);
    }
}

$chiefComplaints          = trim((string)($b['chief_complaints'] ?? ''));
$existingConditions       = trim((string)($b['existing_medical_conditions'] ?? ''));
$currentMedications       = trim((string)($b['current_medications'] ?? ''));
$consultationNature       = trim((string)($b['consultation_nature'] ?? ($inquiryOnly ? 'General Inquiry' : 'Routine Consultation')));
$illnessDescriptionInput  = trim((string)($b['illness_description'] ?? ''));
$referralSource           = trim((string)($b['referral_source'] ?? ''));

$illnessParts = [];
if ($existingConditions !== '') {
    $illnessParts[] = 'Existing Conditions: ' . $existingConditions;
}
if ($currentMedications !== '') {
    $illnessParts[] = 'Current Medications: ' . $currentMedications;
}
$illnessDescription = $illnessDescriptionInput !== ''
    ? $illnessDescriptionInput
    : implode(' | ', $illnessParts);

$consultationMode  = trim((string)($b['consultation_mode'] ?? 'Face-to-Face'));
if ($consultationMode === '' || strcasecmp($consultationMode, 'In-Person') === 0 || strcasecmp($consultationMode, 'Face-to-Face') === 0) {
    $consultationMode = 'Face-to-Face';
} elseif (strcasecmp($consultationMode, 'Online Teleconsultation') === 0) {
    $consultationMode = 'Teleconsultation';
}

$appointmentDate   = trim((string)($b['appointment_date'] ?? ''));
$preferredTimeSlot = trim((string)($b['preferred_time_slot'] ?? ''));
$scheduledTime     = trim((string)($b['scheduled_time'] ?? ''));

if ($preferredTimeSlot !== '' && $scheduledTime === '') {
    $scheduledTime = stripos($preferredTimeSlot, 'afternoon') !== false ? '13:00:00' : '08:00:00';
}
if ($scheduledTime === '') {
    $scheduledTime = '08:00:00';
}
if (strpos($scheduledTime, '-') !== false) {
    $timeStart = trim(explode('-', $scheduledTime)[0]);
    $parsedTs = strtotime($timeStart);
    if ($parsedTs !== false) {
        $scheduledTime = date('H:i:s', $parsedTs);
    }
}
if ($preferredTimeSlot === '' && !$inquiryOnly) {
    $reqHour = (int)date('H', strtotime($scheduledTime));
    $preferredTimeSlot = ($reqHour < 12) ? 'Morning (8:00 AM - 12:00 PM)' : 'Afternoon (1:00 PM - 5:00 PM)';
}

// Validation
$deptCheck = strtolower($department);
if ($department === '' || $deptCheck === 'null' || $deptCheck === 'unassigned' || $deptCheck === 'triage pending') {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Please select a department.']);
    exit;
}

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

if ($chiefComplaints === '') {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Please enter your inquiry question or chief complaint.']);
    exit;
}

if (!$inquiryOnly) {
    if ($appointmentDate === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $appointmentDate)) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Please select a valid preferred date.']);
        exit;
    }

    $ruleErr = validateBookingAgainstRules($pdo, $department, $appointmentDate, $scheduledTime);
    if ($ruleErr !== null) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => $ruleErr]);
        exit;
    }
} else {
    if ($appointmentDate === '') {
        $appointmentDate = date('Y-m-d', strtotime('+1 day'));
    }
}

function buildIdentityCondition(string $sessionPatientId, string $email): array {
    $clauses = [];
    $params  = [];
    if ($sessionPatientId !== '') {
        $clauses[] = 'patient_id = ?';
        $params[]  = $sessionPatientId;
    }
    if ($email !== '') {
        $clauses[] = 'email = ?';
        $params[]  = $email;
    }
    return [implode(' OR ', $clauses), $params];
}

// Determine patient_type (first-time vs returning)
$isFirstTimePatient = isPatientFirstTime($pdo, $sessionPatientId, $email, $firstName, $lastName, $birthdate, $isOtherPatient);

if (!$inquiryOnly && $isFirstTimePatient && strcasecmp($consultationMode, 'Teleconsultation') === 0) {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'message' => 'First-time patients are required to have an initial Face-to-Face consultation. Teleconsultation is only available for returning patients.'
    ]);
    exit;
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
} elseif (isset($b['age']) && is_numeric($b['age'])) {
    $age = (int)$b['age'];
}

// Retrieve existing patient record by email/session (works even before post-booking credentials are issued)
$existingAccount = null;
if (!$isOtherPatient && ($sessionPatientId !== '' || $email !== '')) {
    [$idSql, $idParams] = buildIdentityCondition($sessionPatientId, $email);
    $stmtExisting = $pdo->prepare("
        SELECT id, patient_id, user_id, hrn, username, password, is_verified
        FROM patients
        WHERE ($idSql)
          AND COALESCE(appointment_recipient, 'Patient (Me)') NOT IN ('Other Patient', 'Someone Else / Dependent')
        ORDER BY (password IS NOT NULL AND password <> '') DESC, id ASC
        LIMIT 1
    ");
    $stmtExisting->execute($idParams);
    $existingAccount = $stmtExisting->fetch(PDO::FETCH_ASSOC);
}

$originalPatientId = !empty($existingAccount['patient_id']) ? $existingAccount['patient_id'] : null;
$userPassword = $existingAccount['password'] ?? null;
$username     = $existingAccount['username'] ?? null;
$isVerified   = !empty($existingAccount['is_verified']) ? 1 : 0;
$hrn          = !empty($existingAccount['hrn']) ? $existingAccount['hrn'] : null;

// Normalize department name
$rawDept = strtolower($department);
if (str_contains($rawDept, 'general') || str_contains($rawDept, 'medical')) {
    $deptName = 'General Medical';
} elseif (str_contains($rawDept, 'psychiatry') || str_contains($rawDept, 'opu')) {
    $deptName = 'OPU Psychiatry';
} elseif (str_contains($rawDept, 'neuro')) {
    $deptName = 'Neurology';
} else {
    $deptName = $department;
}

// Anti-Double Booking / Same-Day Time Slot Conflict Validation (when booking for Patient (Me))
if (!$inquiryOnly && !$isOtherPatient && $appointmentDate !== '' && ($sessionPatientId !== '' || $email !== '')) {
    $reqHour = (int)date('H', strtotime($scheduledTime));
    $reqPeriod = ($reqHour < 12) ? 'Morning' : 'Afternoon';

    [$idSql, $idParams] = buildIdentityCondition($sessionPatientId, $email);
    $stmtConflict = $pdo->prepare("
        SELECT id, scheduled_time, preferred_time_slot, status
        FROM patients
        WHERE ($idSql)
          AND appointment_date = ?
          AND LOWER(status) NOT IN ('cancelled', 'rejected', 'cancelled_by_patient', 'no-show', 'transferred')
    ");
    $stmtConflict->execute(array_merge($idParams, [$appointmentDate]));
    $existingDayBookings = $stmtConflict->fetchAll(PDO::FETCH_ASSOC);

    foreach ($existingDayBookings as $eb) {
        $ebSlot = (string)($eb['preferred_time_slot'] ?? '');
        if ($ebSlot !== '') {
            $ebPeriod = stripos($ebSlot, 'afternoon') !== false ? 'Afternoon' : 'Morning';
        } else {
            $ebTime = $eb['scheduled_time'] ?: '08:00:00';
            $ebHour = (int)date('H', strtotime($ebTime));
            $ebPeriod = ($ebHour < 12) ? 'Morning' : 'Afternoon';
        }

        if ($ebPeriod === $reqPeriod) {
            http_response_code(409);
            echo json_encode([
                'success' => false,
                'error'   => 'Conflict',
                'message' => 'You already have an appointment scheduled for this time slot. Please choose another time slot or date.'
            ]);
            exit;
        }
    }
}

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

    $userId = $_SESSION['user_id'] ?? $_SESSION['patient_user_id'] ?? ($existingAccount['user_id'] ?? null);
    if (!$userId && $email !== '') {
        $uStmt = $pdo->prepare("SELECT id FROM users WHERE LOWER(email) = LOWER(?) LIMIT 1");
        $uStmt->execute([$email]);
        $userId = $uStmt->fetchColumn() ?: null;
    }

    $sql = "INSERT INTO patients (
        patient_id, user_id, department, full_name, first_name, last_name, middle_initial,
        gender, birthdate, age, contact_number, email, username, password, is_verified,
        address, with_companion, relationship_to_patient, appointment_recipient,
        chief_complaints, consultation_nature, illness_description,
        existing_medical_conditions, current_medications,
        patient_type, appointment_type, consultation_mode, appointment_date, scheduled_time, preferred_time_slot,
        referral_source, status, unscheduled_walkin, inquiry_only, mode_of_inquiry,
        select_type, hrn, specialty
    ) VALUES (
        ?, ?, ?, ?, ?, ?, ?,
        ?, ?, ?, ?, ?, ?, ?, ?,
        ?, ?, ?, ?,
        ?, ?, ?,
        ?, ?,
        ?, ?, ?, ?, ?, ?,
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
        $appointmentRecipient,
        $chiefComplaints,
        $consultationNature,
        $illnessDescription ?: null,
        $existingConditions ?: null,
        $currentMedications ?: null,
        $isFirstTimePatient ? 'first-time' : 'returning',
        'online',
        $consultationMode,
        $appointmentDate,
        $scheduledTime,
        $preferredTimeSlot ?: null,
        $referralSource !== '' ? $referralSource : null,
        'for_approval',
        0,
        $inquiryOnly,
        $inquiryOnly ? 'Online Portal' : null,
        'Scheduled',
        $hrn,
        $deptName . ' Consultation'
    ]);

    $insertedPatientRowId = (int)$pdo->lastInsertId();
    $prefix = $inquiryOnly ? 'INQ' : 'APT';
    $referenceNo = sprintf('%s-%s-%04d', $prefix, date('Y'), $insertedPatientRowId);

    try {
        if (in_array('reference_no', dbTableColumns($pdo, 'patients'), true)) {
            $pdo->prepare("UPDATE patients SET reference_no = ? WHERE id = ?")
                ->execute([$referenceNo, $insertedPatientRowId]);
        }
    } catch (Throwable $ignoreRefCol) {}

    // Initial Progress Note
    $slotNoteLabel = $preferredTimeSlot ?: date('h:i A', strtotime($scheduledTime));
    $noteText = $inquiryOnly
        ? "Online Department Inquiry submitted (Ref: {$referenceNo}). Status: For Approval."
        : "Online Appointment Booked ({$deptName} on {$appointmentDate} • {$slotNoteLabel}, Mode: {$consultationMode}, Recipient: {$appointmentRecipient}, Ref: {$referenceNo}). Status: For Approval.";

    try {
        $stmtNote = $pdo->prepare("
            INSERT INTO progress_notes (patient_id, note, noted_by, created_at)
            VALUES (?, ?, 'Patient Portal', NOW())
        ");
        $stmtNote->execute([$newPatientId, $noteText]);
    } catch (Throwable $ignoreNote) {}

    // Mirror booking into appointments table
    if (!$inquiryOnly && $appointmentDate !== '') {
        try {
            $start = date('H:i:s', strtotime($scheduledTime));
            $end = stripos($preferredTimeSlot, 'afternoon') !== false ? '17:00:00' : '12:00:00';
            $pdo->prepare("
                INSERT INTO appointments (patient_id, department, appointment_date, start_time, end_time, status, notes, relationship_to_patient, consultation_mode, preferred_time_slot, reference_no)
                VALUES (?, ?, ?, ?, ?, 'scheduled', ?, ?, ?, ?, ?)
            ")->execute([
                $newPatientId,
                $deptName,
                $appointmentDate,
                $start,
                $end,
                'Booked from appointment registration form. Waiting for department approval.',
                $relationshipToPatient ?: null,
                $consultationMode,
                $preferredTimeSlot ?: null,
                $referenceNo
            ]);
        } catch (Throwable $e) {
            // Fallback insert if some optional columns don't exist on appointments
            try {
                $start = date('H:i:s', strtotime($scheduledTime));
                $end = date('H:i:s', strtotime($scheduledTime . ' +30 minutes'));
                $pdo->prepare("
                    INSERT INTO appointments (patient_id, department, appointment_date, start_time, end_time, status, notes)
                    VALUES (?, ?, ?, ?, ?, 'scheduled', ?)
                ")->execute([
                    $newPatientId,
                    $deptName,
                    $appointmentDate,
                    $start,
                    $end,
                    'Booked from appointment registration form. Waiting for department approval.'
                ]);
            } catch (Throwable $ignoreAppt) {}
        }
    }

    // System log
    try {
        $uCol = dbQuoteIdent('user');
        $stmtLog = $pdo->prepare("
            INSERT INTO system_logs (level, action, {$uCol}, details, created_at)
            VALUES ('info', ?, ?, ?, CURRENT_TIMESTAMP)
        ");
        $stmtLog->execute([
            'PATIENT_BOOK_APPOINTMENT',
            substr($email, 0, 60),
            "Patient {$fullName} (Ref: {$referenceNo}) booked {$deptName} appointment for {$appointmentDate} ({$slotNoteLabel})"
        ]);
    } catch (Throwable $e) {
        // Fallback for older system_logs schemas
        try {
            $pdo->prepare("
                INSERT INTO system_logs (action, description, performed_by, created_at)
                VALUES (?, ?, ?, CURRENT_TIMESTAMP)
            ")->execute([
                'PATIENT_BOOK_APPOINTMENT',
                "Patient {$fullName} (Ref: {$referenceNo}) booked {$deptName} appointment for {$appointmentDate}",
                $email
            ]);
        } catch (Throwable $ignoreLog) {}
    }

    $pdo->commit();

    $accountPatientId = '';
    if ($userId) {
        try {
            if (in_array('account_patient_id', dbTableColumns($pdo, 'users'), true)) {
                $apStmt = $pdo->prepare("SELECT account_patient_id FROM users WHERE id = ? LIMIT 1");
                $apStmt->execute([$userId]);
                $accountPatientId = trim((string)($apStmt->fetchColumn() ?: ''));
            }
        } catch (Throwable $ignoreAcct) {}
    }

    echo json_encode([
        'success'             => true,
        'message'             => $inquiryOnly ? 'Inquiry submitted successfully!' : 'Appointment booked successfully!',
        'patient_id'          => $accountPatientId !== '' ? $accountPatientId : ($originalPatientId ?: $newPatientId),
        'reference_no'        => $referenceNo,
        'booking_code'        => $newPatientId,
        'department'          => $deptName,
        'appointment_date'    => $appointmentDate,
        'preferred_time_slot' => $preferredTimeSlot,
        'scheduled_time'      => date('h:i A', strtotime($scheduledTime)),
        'redirect'            => '/sdn/pages/patient-dashboard.php'
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
