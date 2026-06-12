<?php
/**
 * api/patients.php
 *
 * GET                                     → list all patients (admin only)
 * GET ?id=PT-2026-XXX                     → single patient full detail
 * POST {action:'approve', patient_id}     → approve a patient
 * POST {action:'reject',  patient_id}     → reject a patient
 * POST {action:'assign',  patient_id, doctor} → assign a doctor
 */

session_start();
require_once __DIR__ . '/config/cors.php';
require_once __DIR__ . '/config/db.php';

requireAuth();

$method = $_SERVER['REQUEST_METHOD'];

// ── GET ─────────────────────────────────────────────────────────────────────
if ($method === 'GET') {

    // Single patient by ?id=
    if (!empty($_GET['id'])) {
        $id   = trim($_GET['id']);
        $stmt = $pdo->prepare(
            "SELECT patient_id, department, full_name, gender, birthdate, age,
                    contact_number, email, address, with_companion,
                    chief_complaints, consultation_nature, illness_description,
                    patient_type, appointment_type, consultation_mode,
                    appointment_date, referral_source, status,
                    assigned_doctor, assigned_date, created_at, updated_at
             FROM patients
             WHERE patient_id = ?"
        );
        $stmt->execute([$id]);
        $patient = $stmt->fetch();

        if (!$patient) respondErr("Patient $id not found.", 404);
        respondOk(['data' => $patient]);
    }

    // All patients (list view)
    $role = $_SESSION['role'] ?? 'staff';
    $username = $_SESSION['user'] ?? '';
    $dept = $_SESSION['department'] ?? '';

    // Dynamically fetch department if missing or to ensure it's up to date
    if ($role === 'staff' && $username) {
        $stmtDept = $pdo->prepare("SELECT department FROM users WHERE username = ?");
        $stmtDept->execute([$username]);
        $fetchedDept = $stmtDept->fetchColumn();
        if ($fetchedDept) $dept = $fetchedDept;
    }
    
    if ($role === 'admin' || empty($dept)) {
        $stmt = $pdo->query(
            "SELECT patient_id, department, full_name, gender, patient_type,
                    appointment_date, status, assigned_doctor, created_at
             FROM patients
             ORDER BY created_at DESC"
        );
        respondOk(['data' => $stmt->fetchAll()]);
    } else {
        $stmt = $pdo->prepare(
            "SELECT patient_id, department, full_name, gender, patient_type,
                    appointment_date, status, assigned_doctor, created_at
             FROM patients
             WHERE LOWER(department) = LOWER(?)
             ORDER BY created_at DESC"
        );
        $stmt->execute([$dept]);
        respondOk(['data' => $stmt->fetchAll()]);
    }
}

// ── POST ────────────────────────────────────────────────────────────────────
if ($method === 'POST') {
    $body      = getBody();
    $action    = $body['action']     ?? '';
    $patientId = $body['patient_id'] ?? '';
    $actor     = sessionUser();

    if ($patientId === '') respondErr('patient_id is required.');

    // Verify patient exists
    $check = $pdo->prepare("SELECT full_name, department, status FROM patients WHERE patient_id = ?");
    $check->execute([$patientId]);
    $patient = $check->fetch();
    if (!$patient) respondErr("Patient $patientId not found.", 404);

    $role = $_SESSION['role'] ?? 'staff';
    $username = $_SESSION['user'] ?? '';
    $staffDept = $_SESSION['department'] ?? '';

    if ($role === 'staff' && $username) {
        $stmtDept = $pdo->prepare("SELECT department FROM users WHERE username = ?");
        $stmtDept->execute([$username]);
        $fetchedDept = $stmtDept->fetchColumn();
        if ($fetchedDept) $staffDept = $fetchedDept;
    }

    if ($role !== 'admin' && !empty($staffDept) && strtolower($patient['department']) !== strtolower($staffDept)) {
        respondErr("Unauthorized: This patient is registered to another department.", 403);
    }

    $name = $patient['full_name'];
    $dept = $patient['department'];

    // ── Approve ──────────────────────────────────────────────────────────
    if ($action === 'approve') {
        if ($patient['status'] !== 'pending') {
            respondErr("Patient is already {$patient['status']}.");
        }
        $upd = $pdo->prepare(
            "UPDATE patients SET status = 'approved', updated_at = NOW() WHERE patient_id = ?"
        );
        $upd->execute([$patientId]);
        addLog($pdo, 'info', 'Patient Approved', $actor,
               "Patient $patientId ($name) was approved for $dept.");
        respondOk(['message' => "$name has been approved."]);
    }

    // ── Reject ───────────────────────────────────────────────────────────
    if ($action === 'reject') {
        if ($patient['status'] !== 'pending') {
            respondErr("Patient is already {$patient['status']}.");
        }
        $upd = $pdo->prepare(
            "UPDATE patients SET status = 'rejected', updated_at = NOW() WHERE patient_id = ?"
        );
        $upd->execute([$patientId]);
        addLog($pdo, 'info', 'Patient Rejected', $actor,
               "Patient $patientId ($name) registration was rejected.");
        respondOk(['message' => "{$name}'s registration was rejected."]);
    }

    // ── Assign Doctor ────────────────────────────────────────────────────
    if ($action === 'assign') {
        $doctor = trim($body['doctor'] ?? '');
        if ($doctor === '') respondErr('doctor name is required.');

        // Verify doctor exists and is not on leave
        $docCheck = $pdo->prepare(
            "SELECT name, status FROM doctors WHERE name = ? LIMIT 1"
        );
        $docCheck->execute([$doctor]);
        $doc = $docCheck->fetch();

        if (!$doc) respondErr("Doctor '$doctor' not found.");
        if ($doc['status'] === 'leave') respondErr("$doctor is currently on leave.");

        $upd = $pdo->prepare(
            "UPDATE patients
             SET assigned_doctor = ?, assigned_date = CURDATE(), updated_at = NOW()
             WHERE patient_id = ?"
        );
        $upd->execute([$doctor, $patientId]);

        addLog($pdo, 'info', 'Doctor Assigned', $actor,
               "Dr. $doctor assigned to patient $patientId ($name).");

        respondOk(['message' => "$doctor has been assigned to $name."]);
    }

    respondErr("Unknown action '$action'.");
}

respondErr('Method not allowed.', 405);
