<?php
/**
 * api/patient_verify.php
 *
 * Verification and profile lookup for returning patients (No login credentials needed).
 * Allows patients (or staff) to verify identity via Patient ID / HRN or Full Name + Birthdate,
 * and retrieves their demographic profile so new check-ups sync without re-typing.
 */

require_once __DIR__ . '/config/cors.php';
require_once __DIR__ . '/config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    respondErr('Method not allowed.', 405);
}

// Rate limiting (max 30 lookups per 10 minutes per session)
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['verify_lookups'])) $_SESSION['verify_lookups'] = [];
$now = time();
$_SESSION['verify_lookups'] = array_filter($_SESSION['verify_lookups'], fn($t) => ($now - $t) < 600);
if (count($_SESSION['verify_lookups']) >= 30) {
    respondErr('Too many lookup requests. Please wait a moment before trying again.', 429);
}
$_SESSION['verify_lookups'][] = $now;

$params = $_SERVER['REQUEST_METHOD'] === 'POST' ? getBody() : $_GET;

$patientId = trim($params['patient_id'] ?? '');
$hrn       = trim($params['hrn'] ?? '');
$firstName = trim($params['first_name'] ?? '');
$lastName  = trim($params['last_name'] ?? '');
$fullName  = trim($params['full_name'] ?? $params['name'] ?? '');
$birthdate = trim($params['birthdate'] ?? '');
$contact   = trim($params['contact_number'] ?? '');

if ($fullName !== '' && $lastName === '' && $firstName === '') {
    $parts = preg_split('/\s+/', $fullName);
    if (count($parts) > 1) {
        $lastName = array_pop($parts);
        $firstName = implode(' ', $parts);
    } else {
        $lastName = $fullName;
    }
}

$patient = null;

try {
    // 1. Direct Search by Patient ID
    if ($patientId !== '') {
        $stmt = $pdo->prepare(
            "SELECT patient_id, hrn, first_name, last_name, middle_initial, full_name,
                    gender, birthdate, age, contact_number, email, address,
                    department, patient_type, status, created_at, active_medications
             FROM patients
             WHERE (patient_id = ? OR hrn = ?)
               AND status NOT IN ('rejected', 'cancelled')
             ORDER BY id DESC
             LIMIT 1"
        );
        $stmt->execute([$patientId, $patientId]);
        $patient = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // 2. Search by HRN
    if (!$patient && $hrn !== '') {
        $stmt = $pdo->prepare(
            "SELECT patient_id, hrn, first_name, last_name, middle_initial, full_name,
                    gender, birthdate, age, contact_number, email, address,
                    department, patient_type, status, created_at, active_medications
             FROM patients
             WHERE hrn = ?
               AND status NOT IN ('rejected', 'cancelled')
             ORDER BY id DESC
             LIMIT 1"
        );
        $stmt->execute([$hrn]);
        $patient = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // 3. Search by Name + Birthdate
    if (!$patient && ($lastName !== '' || $fullName !== '') && $birthdate !== '') {
        $searchName = $fullName ?: trim("$firstName $lastName");
        $stmt = $pdo->prepare(
            "SELECT patient_id, hrn, first_name, last_name, middle_initial, full_name,
                    gender, birthdate, age, contact_number, email, address,
                    department, patient_type, status, created_at, active_medications
             FROM patients
             WHERE (
                 LOWER(full_name) = LOWER(?)
                 OR LOWER(full_name) LIKE LOWER(?)
                 OR (LOWER(last_name) = LOWER(?) AND (LOWER(first_name) = LOWER(?) OR ? = ''))
             )
               AND birthdate = ?
               AND status NOT IN ('rejected', 'cancelled')
             ORDER BY id DESC
             LIMIT 1"
        );
        $stmt->execute([
            $searchName,
            "%$searchName%",
            $lastName,
            $firstName,
            $firstName,
            $birthdate
        ]);
        $patient = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    if (!$patient) {
        respondOk([
            'found'   => false,
            'message' => 'No existing patient record matched the provided information. Please verify your details or proceed as a first-time patient.'
        ]);
    }

    // Calculate count of previous visits
    $countStmt = $pdo->prepare(
        "SELECT COUNT(*) FROM patients 
         WHERE (patient_id = ? OR (first_name = ? AND last_name = ? AND birthdate = ?))
           AND status NOT IN ('rejected', 'cancelled')"
    );
    $countStmt->execute([
        $patient['patient_id'],
        $patient['first_name'],
        $patient['last_name'],
        $patient['birthdate']
    ]);
    $visitCount = (int) $countStmt->fetchColumn();

    if (empty($patient['first_name']) && !empty($patient['full_name'])) {
        $nameParts = preg_split('/\s+/', trim($patient['full_name']));
        if (count($nameParts) > 1) {
            $patient['last_name'] = array_pop($nameParts);
            $patient['first_name'] = implode(' ', $nameParts);
        } else {
            $patient['first_name'] = $patient['full_name'];
            $patient['last_name'] = '';
        }
    }

    respondOk([
        'found'       => true,
        'message'     => 'Existing patient record verified successfully.',
        'visit_count' => $visitCount,
        'patient'     => [
            'patient_id'         => $patient['patient_id'],
            'hrn'                => $patient['hrn'] ?: ('HRN-' . substr($patient['patient_id'], 3)),
            'first_name'         => $patient['first_name'] ?: '',
            'last_name'          => $patient['last_name'] ?: '',
            'middle_initial'     => $patient['middle_initial'] ?: '',
            'full_name'          => $patient['full_name'],
            'gender'             => $patient['gender'],
            'birthdate'          => $patient['birthdate'],
            'age'                => $patient['age'],
            'contact_number'     => $patient['contact_number'],
            'email'              => $patient['email'] ?: '',
            'address'            => $patient['address'] ?: '',
            'previous_dept'      => $patient['department'],
            'last_visit'         => $patient['created_at'],
            'active_medications' => $patient['active_medications'] ?: ''
        ]
    ]);

} catch (Exception $e) {
    error_log("Patient verification error: " . $e->getMessage());
    respondErr('Database error during patient verification.', 500);
}
