<?php
/**
 * api/registration.php
 *
 * POST — submit a new patient registration form or inquiry.
 * Handles Neurology, Psychiatry, and General Management dynamically.
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

$isWalkinQuick = !empty($b['unscheduled_walkin']) || !empty($b['is_walkin']);
$isInquiryOnly = !empty($b['inquiry_only']);

// ── 3. Validate required fields based on department and mode ─────────────────
$required = [];
if (!$isInquiryOnly && !$isWalkinQuick) {
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
        respondErr("MMWGH Appointment Regulation: Online consultations require advance booking (at least 1 day in advance, starting $minSched). For same-day immediate consultations, please proceed directly to the hospital triage as a Walk-in.");
    }
    if ($b['appointment_date'] > $maxSched) {
        respondErr("Scheduled Date cannot be more than 1 year in the future (max $maxSched).");
    }
}

// ── 6. Doctor Shift End & Time Restriction Check ─────────────────────────────
$assignedDoctor = !empty($b['assigned_doctor']) ? trim($b['assigned_doctor']) : (!empty($b['doctor']) ? trim($b['doctor']) : null);
$scheduledTime  = !empty($b['scheduled_time']) ? trim($b['scheduled_time']) : null;

if ($assignedDoctor && $scheduledTime && !$isInquiryOnly) {
    $docStmt = $pdo->prepare("SELECT name, schedule_start, schedule_end, total_slots, booked_slots, status FROM doctors WHERE name = ? LIMIT 1");
    $docStmt->execute([$assignedDoctor]);
    $docInfo = $docStmt->fetch(PDO::FETCH_ASSOC);

    if ($docInfo && !empty($docInfo['schedule_start']) && !empty($docInfo['schedule_end'])) {
        $startTime = substr($docInfo['schedule_start'], 0, 5);
        $endTime   = substr($docInfo['schedule_end'], 0, 5);
        $reqTime   = substr($scheduledTime, 0, 5);

        // Calculate 30-minute cutoff prior to shift end
        $endParts = explode(':', $endTime);
        $endMinTotal = ((int)$endParts[0] * 60) + (int)$endParts[1] - 30;
        $cutoffTime = sprintf('%02d:%02d', floor($endMinTotal / 60), $endMinTotal % 60);

        if ($reqTime < $startTime) {
            respondErr("Cannot schedule appointment: {$reqTime} is earlier than Dr. {$docInfo['name']}'s shift start time ({$startTime}).");
        }
        if ($reqTime > $cutoffTime) {
            respondErr("Strict Booking Restriction: Appointments cannot be booked within 30 minutes of shift end or outside working hours. Dr. {$docInfo['name']}'s shift ends at {$endTime} (Last available booking slot: {$cutoffTime}).");
        }
    }
}

// ── 7. Double-Booking Prevention Protocol ─────────────────────────────────────
// Ensure no two patients can claim the exact same doctor + date + time slot
if ($assignedDoctor && !empty($b['appointment_date']) && $scheduledTime && !$isInquiryOnly) {
    $doubleStmt = $pdo->prepare(
        "SELECT COUNT(*) FROM patients
         WHERE assigned_doctor = ?
           AND appointment_date = ?
           AND scheduled_time = ?
           AND status NOT IN ('rejected', 'cancelled', 'no-show')"
    );
    $doubleStmt->execute([$assignedDoctor, $b['appointment_date'], $scheduledTime]);
    $existingCount = (int) $doubleStmt->fetchColumn();

    if ($existingCount > 0) {
        $formattedTime = date('h:i A', strtotime($scheduledTime));
        respondErr("Double-Booking Conflict: The requested slot ({$formattedTime}) on {$b['appointment_date']} with {$assignedDoctor} has already been reserved by another patient. Please choose an alternative time slot or doctor.", 409);
    }
}

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
    $dept = 'General Management';
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

// Check returning patient verification
$verifiedPatientId = !empty($b['verified_patient_id']) ? trim($b['verified_patient_id']) : null;
$patientType = !empty($b['patient_type']) ? trim($b['patient_type']) : ($verifiedPatientId ? 'returning' : 'first-time');

// ── 9. Post-Booking Initial Status ───────────────────────────────────────────
// Per Phase 4: Appointments must never be marked 'completed' prematurely!
// Online bookings start at 'for_approval' (or 'for_processing'). Walk-in admissions start at 'arrived'.
$initialStatus = $isWalkinQuick ? 'arrived' : 'for_approval';

// ── 10. Database Transaction Execution ───────────────────────────────────────
try {
    $pdo->beginTransaction();

    $patientId = generatePatientId($pdo);

    // If returning patient has an existing HRN, inherit it; otherwise generate standard HRN
    $hrn = !empty($b['hrn']) ? trim($b['hrn']) : ('HRN-' . substr($patientId, 3));

    $sql = "INSERT INTO patients
              (patient_id, department, full_name, first_name, last_name, middle_initial, gender, birthdate, age,
               contact_number, email, address, with_companion,
               chief_complaints, consultation_nature, illness_description,
               patient_type, appointment_type, consultation_mode,
               appointment_date, referral_source, status,
               assigned_doctor, assigned_date,
               receiver, informant, recommendation_remarks, unscheduled_walkin, inquiry_only,
               mode_of_inquiry, employee_type, select_type, hrn, specialty, scheduled_time)
            VALUES
              (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
               ?, ?,
               ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $clean = fn(?string $val) => ($val !== null && trim($val) !== '') ? strip_tags(trim($val)) : null;

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $patientId,
        $dept,
        $fullName,
        $firstName !== '' ? strip_tags($firstName) : null,
        $lastName !== '' ? strip_tags($lastName) : null,
        $cleanMI,
        $clean($b['gender'] ?? null),
        $b['birthdate'] ?? null,
        $age,
        $clean($b['contact_number'] ?? null),
        filter_var(trim($b['email'] ?? ''), FILTER_VALIDATE_EMAIL) ?: null,
        $clean($b['address'] ?? null),
        isset($b['with_companion']) && $b['with_companion'] === 'yes' ? 1 : 0,
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

    // Update doctor's booked slots if doctor assigned and not inquiry
    if ($assignedDoctor && !$isInquiryOnly) {
        $docStmt = $pdo->prepare("UPDATE doctors SET booked_slots = booked_slots + 1 WHERE name = ? AND booked_slots < total_slots");
        $docStmt->execute([$assignedDoctor]);
    }

    // Add progress note linking returning records or initial entry
    $noteText = $verifiedPatientId 
        ? "Returning Patient Check-up Registered (Synced to Previous Master Record $verifiedPatientId). Status: For Approval."
        : ($isInquiryOnly ? "General Inquiry Submitted. Awaiting review." : "Online Appointment Pre-Registration Submitted. Status: For Approval.");

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
