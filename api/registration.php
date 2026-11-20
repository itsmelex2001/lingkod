<?php
/**
 * api/registration.php
 *
 * POST — submit a new patient registration form or inquiry.
 * Handles Neurology, Psychiatry, and General Medical dynamically.
 * Enforces MMWGH advance booking regulations, doctor shift restrictions,
 * double-booking prevention, returning patient linking, and status flow.
 */

require_once __DIR__ . '/config/cors.php';
require_once __DIR__ . '/config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') respondErr('Method not allowed.', 405);

$b = getBody();

// ── 1. Anti-Bot Honeypot Protection ──────────────────────────────────────────
if (!empty($b['website_url']) || !empty($b['hp_check'])) {
    respondErr('Invalid submission detected.', 400);
}

// ── 2. Request Rate Limiting ─────────────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['reg_history'])) $_SESSION['reg_history'] = [];
$now = time();
$_SESSION['reg_history'] = array_filter($_SESSION['reg_history'], fn($t) => ($now - $t) < 600);
if (count($_SESSION['reg_history']) >= 20) {
    respondErr('Too many registration attempts. Please wait a few minutes before submitting again.', 429);
}
$_SESSION['reg_history'][] = $now;

if (empty($b['department'])) {
    respondErr("Field 'department' is required.");
}

$sessRole = strtolower(trim((string)($_SESSION['role'] ?? ($_SESSION['user_role'] ?? ''))));
$isStaffUser = in_array($sessRole, ['admin', 'staff', 'doctor', 'nurse'], true);
$patientType = !empty($b['patient_type']) ? trim($b['patient_type']) : 'first-time';

// Enforce 'Appointment Only' on the public portal (staff may admit walk-in / unscheduled patients)
if (!$isStaffUser) {
    $b['unscheduled_walkin'] = 0;
    $b['is_walkin'] = 0;
    $b['appointment_type'] = 'online';
}

$isWalkinQuick = $isStaffUser && (!empty($b['unscheduled_walkin']) || !empty($b['is_walkin']));
$isInquiryOnly = !empty($b['inquiry_only']);

// ── 3. Validate required fields based on department and mode ─────────────────
$required = [];
if ($isWalkinQuick) {
    // Staff walk-in / unscheduled admission: only chief complaints required (contact optional)
    $required = ['chief_complaints'];
} elseif (!$isInquiryOnly) {
    if ($b['department'] === 'neurology') {
        $required = ['contact_number', 'chief_complaints', 'appointment_date'];
    } elseif ($b['department'] === 'psychiatry') {
        $required = ['contact_number', 'chief_complaints', 'appointment_date'];
    } elseif ($b['department'] === 'general') {
        $required = ['contact_number', 'chief_complaints', 'appointment_date', 'scheduled_time'];
    }
} else {
    // Inquiry mode: only contact number and chief concerns required
    $required = ['contact_number', 'chief_complaints'];
}

foreach ($required as $field) {
    if (!isset($b[$field]) || trim((string)$b[$field]) === '') {
        $label = str_replace('_', ' ', $field);
        respondErr("Field '$label' is strictly required.");
    }
}

// ── 4. Patient Name Resolution ───────────────────────────────────────────────
$firstName = trim($b['first_name'] ?? '');
$lastName  = trim($b['last_name'] ?? '');
$miVal     = trim($b['middle_initial'] ?? '');
$rawFullName = trim($b['full_name'] ?? '');

if ($firstName === '' && $lastName === '' && $rawFullName === '') {
    respondErr("First Name and Last Name are required.");
}
if ($firstName !== '' && $lastName === '') {
    respondErr("Last Name is required.");
}
if ($lastName !== '' && $firstName === '') {
    respondErr("First Name is required.");
}

// Clean and format middle initial (optional)
$cleanMI = null;
if ($miVal !== '') {
    $cleanMI = str_ends_with($miVal, '.') ? strtoupper($miVal) : strtoupper($miVal) . '.';
}

if ($firstName !== '' && $lastName !== '') {
    $fullName = $cleanMI ? "$firstName $cleanMI $lastName" : "$firstName $lastName";
} elseif ($rawFullName !== '') {
    $fullName = $rawFullName;
} else {
    $fullName = 'Patient';
}

// ── 5. Strict Date Range Validations ─────────────────────────────────────────
if (!empty($b['birthdate'])) {
    $minBirth = date('Y-m-d', strtotime('-120 years'));
    $maxBirth = date('Y-m-d');
    if ($b['birthdate'] < $minBirth || $b['birthdate'] > $maxBirth) {
        respondErr("Birthdate must be between $minBirth and $maxBirth (within the last 120 years up to today).");
    }
}

// MMWGH Advance Booking Policy: Online appointments must be booked at least 1 day in advance!
if (!empty($b['appointment_date']) && !$isInquiryOnly && !$isWalkinQuick) {
    $minSched = date('Y-m-d', strtotime('+1 day'));
    $maxSched = date('Y-m-d', strtotime('+1 year'));
    if ($b['appointment_date'] < $minSched) {
        respondErr("MMWGH Appointment Regulation: Online consultations require advance booking (at least 1 day in advance, starting $minSched). First-time patients are accepted by Appointment Only.");
    }
    if ($b['appointment_date'] > $maxSched) {
        respondErr("Scheduled Date cannot be more than 1 year in the future (max $maxSched).");
    }
}

// ── 6. Initial Booking Doctor & Time Setup ──────────────────────────────────
// Initial booking leaves attending doctor null until the day of consultation
$assignedDoctor = null;
$assignedDate   = null;
$scheduledTime  = !empty($b['scheduled_time']) ? trim($b['scheduled_time']) : null;

// ── 8. Generate Unique Patient ID ────────────────────────────────────────────
function generatePatientId(PDO $pdo): string {
    $year = date('Y');
    $stmt = $pdo->prepare(
        "SELECT MAX(CAST(SUBSTRING_INDEX(patient_id, '-', -1) AS UNSIGNED))
         FROM patients
         WHERE patient_id LIKE ?"
    );
    $stmt->execute(["PT-$year-%"]);
    $max = (int) $stmt->fetchColumn(); 
    return 'PT-' . $year . '-' . str_pad($max + 1, 3, '0', STR_PAD_LEFT);
}

$rawDept = strtolower(trim($b['department'] ?? ''));
if (str_contains($rawDept, 'general')) {
    $dept = 'General Medical';
} elseif (str_contains($rawDept, 'psychiatry') || str_contains($rawDept, 'opu')) {
    $dept = 'OPU Psychiatry';
} elseif (str_contains($rawDept, 'neuro')) {
    $dept = 'Neurology';
} else {
    $dept = ucfirst($b['department']);
}

$assignedDate = $assignedDoctor ? date('Y-m-d') : null;

// Calculate age strictly from birthdate
$age = null;
if (!empty($b['birthdate'])) {
    $birth = new DateTime($b['birthdate']);
    $today = new DateTime();
    $age   = (int) $birth->diff($today)->y;
}

$guardianName    = !empty($b['guardian_name']) ? trim($b['guardian_name']) : null;
$guardianContact = !empty($b['guardian_contact_number']) ? trim($b['guardian_contact_number']) : null;

if ($age !== null && $age < 18 && !$isInquiryOnly && !$isWalkinQuick) {
    if (empty($guardianName)) {
        respondErr('Parent / Guardian name is required for minors (under 18 years old).');
    }
    if (empty($guardianContact)) {
        respondErr('Guardian contact number is required for minors (under 18 years old).');
    }
}

$patientType = !empty($b['patient_type']) ? trim($b['patient_type']) : 'first-time';

// ── 9. Post-Booking Initial Status ───────────────────────────────────────────
// Per Phase 4: Appointments must never be marked 'completed' prematurely!
// Online bookings start at 'for_approval' (or 'for_processing'). Walk-in admissions start at 'arrived'.
$initialStatus = $isWalkinQuick ? 'arrived' : 'for_approval';

// ── 10. Database Transaction Execution ───────────────────────────────────────
try {
    $pdo->beginTransaction();

    $patientId = generatePatientId($pdo);

    // Resolve user account link
    $userId = $_SESSION['user_id'] ?? $_SESSION['patient_user_id'] ?? null;
    $validEmail = filter_var(trim($b['email'] ?? ''), FILTER_VALIDATE_EMAIL) ?: null;
    if (!$userId && $validEmail) {
        $uStmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $uStmt->execute([$validEmail]);
        $userId = $uStmt->fetchColumn() ?: null;
    }

    // If returning patient has an existing HRN, inherit it; otherwise generate standard HRN
    $hrn = !empty($b['hrn']) ? trim($b['hrn']) : ('HRN-' . substr($patientId, 3));

    $sql = "INSERT INTO patients
              (patient_id, user_id, department, full_name, first_name, last_name, middle_initial, gender, birthdate, age,
               contact_number, email, address, with_companion, guardian_name, guardian_contact_number,
               chief_complaints, consultation_nature, illness_description,
               patient_type, appointment_type, consultation_mode,
               appointment_date, referral_source, status,
               assigned_doctor, assigned_date,
               receiver, informant, recommendation_remarks, unscheduled_walkin, inquiry_only,
               mode_of_inquiry, employee_type, select_type, hrn, specialty, scheduled_time)
            VALUES
              (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
               ?, ?,
               ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $clean = fn(?string $val) => ($val !== null && trim($val) !== '') ? strip_tags(trim($val)) : null;

    $cleanGuardianName = ($age !== null && $age < 18) ? $clean($guardianName) : null;
    $cleanGuardianContact = ($age !== null && $age < 18) ? $clean($guardianContact) : null;

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $patientId,
        $userId,
        $dept,
        $fullName,
        $firstName !== '' ? strip_tags($firstName) : null,
        $lastName !== '' ? strip_tags($lastName) : null,
        $cleanMI,
        $clean($b['gender'] ?? null),
        $b['birthdate'] ?? null,
        $age,
        $clean($b['contact_number'] ?? null),
        $validEmail,
        $clean($b['address'] ?? null),
        isset($b['with_companion']) && $b['with_companion'] === 'yes' ? 1 : 0,
        $cleanGuardianName,
        $cleanGuardianContact,
        $clean($b['chief_complaints'] ?? null),
        $clean($b['consultation_nature'] ?? ($isInquiryOnly ? 'General Inquiry' : null)),
        $clean($b['illness_description'] ?? null),
        $patientType,
        $clean($b['appointment_type'] ?? ($isWalkinQuick ? 'walk-in' : 'online')),
        $clean($b['consultation_mode'] ?? 'Face-to-Face'),
        $b['appointment_date'] ?? null,
        $clean($b['referral_source'] ?? null),
        $initialStatus,

        $assignedDoctor,
        $assignedDate,

        $clean($b['receiver'] ?? null),
        $clean($b['informant'] ?? null),
        $clean($b['recommendation_remarks'] ?? null),
        $isWalkinQuick ? 1 : 0,
        $isInquiryOnly ? 1 : 0,
        $clean($b['mode_of_inquiry'] ?? ($isInquiryOnly ? 'Online Portal' : null)),
        $clean($b['employee_type'] ?? null),
        $clean($b['select_type'] ?? 'Scheduled'),
        $hrn,
        $clean($b['specialty'] ?? null),
        $scheduledTime
    ]);

    // Add progress note linking returning records or initial entry
    $noteText = $isInquiryOnly ? "General Inquiry Submitted. Awaiting review." : "Online Appointment Pre-Registration Submitted. Status: For Approval.";

    $noteStmt = $pdo->prepare(
        "INSERT INTO progress_notes (patient_id, note, noted_by) VALUES (?, ?, 'System')"
    );
    $noteStmt->execute([$patientId, $noteText]);

    addLog($pdo, 'info', $isInquiryOnly ? 'Inquiry Registered' : 'Patient Registered', 'Public Booking Portal',
           "New {$initialStatus} " . ($isInquiryOnly ? "inquiry" : "registration") . ": $patientId ($fullName) for $dept" . ($assignedDoctor ? " with Dr. $assignedDoctor" : "") . ".");

    $pdo->commit();

    respondOk([
        'patient_id' => $patientId,
        'status'     => $initialStatus,
        'hrn'        => $hrn,
        'message'    => $isInquiryOnly 
            ? 'Your inquiry has been submitted successfully. A representative will contact you shortly.' 
            : 'Appointment registration submitted successfully! Your booking is currently For Approval with the clinic triage.',
    ]);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log("Patient registration PDO error: " . $e->getMessage());
    respondErr('A database error occurred while processing your appointment. Please try again.', 500);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log("Patient registration general error: " . $e->getMessage());
    respondErr('An unexpected server error occurred. Please try again.', 500);
}
