<?php
/**
 * api/registration.php
 *
 * POST — submit a new patient registration form.
 * Handles Neurology, Psychiatry, and General Management dynamically.
 */

require_once __DIR__ . '/config/cors.php';
require_once __DIR__ . '/config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') respondErr('Method not allowed.', 405);

$b = getBody();

if (empty($b['department'])) {
    respondErr("Field 'department' is required.");
}

// ── Validate required fields based on department ─────────────────────────────
$required = [];
if ($b['department'] === 'neurology') {
    $required = ['full_name','gender','birthdate','contact_number',
                 'address','chief_complaints','consultation_nature',
                 'patient_type','appointment_type','consultation_mode','appointment_date'];
} elseif ($b['department'] === 'psychiatry') {
    $required = ['full_name','contact_number','appointment_date','appointment_type','consultation_mode','chief_complaints'];
} elseif ($b['department'] === 'general') {
    $required = ['full_name','contact_number','appointment_date','scheduled_time','chief_complaints'];
}

foreach ($required as $field) {
    if (empty($b[$field]) && !isset($b['inquiry_only'])) {
        respondErr("Field '$field' is required.");
    }
}

// ── Generate unique patient ID ───────────────────────────────────────────────
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

$deptMap = [
    'psychiatry' => 'OPU Psychiatry',
    'general'    => 'General Management',
    'neurology'  => 'Neurology',
];
$dept = $deptMap[$b['department']] ?? $b['department'];

// ── Calculate age from birthdate if not provided ─────────────────────────────
$age = !empty($b['age']) ? (int)$b['age'] : null;
if ($age === null && !empty($b['birthdate'])) {
    $birth = new DateTime($b['birthdate']);
    $today = new DateTime();
    $age   = (int) $birth->diff($today)->y;
}

// ── Insert ───────────────────────────────────────────────────────────────────
try {
    $patientId = generatePatientId($pdo);

    $sql = "INSERT INTO patients
              (patient_id, department, full_name, gender, birthdate, age,
               contact_number, email, address, with_companion,
               chief_complaints, consultation_nature, illness_description,
               patient_type, appointment_type, consultation_mode,
               appointment_date, referral_source, status,
               
               receiver, informant, recommendation_remarks, unscheduled_walkin, inquiry_only,
               mode_of_inquiry, employee_type, select_type, hrn, specialty, scheduled_time)
            VALUES
              (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending',
               ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $patientId,
        $dept,
        trim($b['full_name']),
        $b['gender'] ?? null,
        $b['birthdate'] ?? null,
        $age,
        $b['contact_number'] ?? null,
        $b['email'] ?? null,
        $b['address'] ?? null,
        isset($b['with_companion']) && $b['with_companion'] === 'yes' ? 1 : 0,
        $b['chief_complaints'] ?? null,
        $b['consultation_nature'] ?? null,
        $b['illness_description'] ?? null,
        $b['patient_type'] ?? null,
        $b['appointment_type'] ?? null,
        $b['consultation_mode'] ?? null,
        $b['appointment_date'] ?? null,
        $b['referral_source'] ?? null,

        // New fields
        $b['receiver'] ?? null,
        $b['informant'] ?? null,
        $b['recommendation_remarks'] ?? null,
        !empty($b['unscheduled_walkin']) ? 1 : 0,
        !empty($b['inquiry_only']) ? 1 : 0,
        $b['mode_of_inquiry'] ?? null,
        $b['employee_type'] ?? null,
        $b['select_type'] ?? null,
        $b['hrn'] ?? null,
        $b['specialty'] ?? null,
        $b['scheduled_time'] ?? null
    ]);

    $noteStmt = $pdo->prepare(
        "INSERT INTO progress_notes (patient_id, note, noted_by) VALUES (?, 'Pending Initial Assessment', 'System')"
    );
    $noteStmt->execute([$patientId]);

    addLog($pdo, 'info', 'Patient Registered', 'Public Form',
           "New registration: $patientId ({$b['full_name']}) for $dept.");

    respondOk([
        'patient_id' => $patientId,
        'message'    => 'Registration submitted successfully.',
    ]);

} catch (PDOException $e) {
    respondErr('Database error: ' . $e->getMessage(), 500);
} catch (Exception $e) {
    respondErr('Server error: ' . $e->getMessage(), 500);
}
