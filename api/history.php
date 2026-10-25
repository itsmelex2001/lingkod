<?php
/**
 * api/history.php
 *
 * GET  -> fetch all history records
 * POST -> add or edit history
 */

session_start();
require_once __DIR__ . '/config/cors.php';
require_once __DIR__ . '/config/db.php';

requireAuth();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Fetch patients and their medical history
    $role = $_SESSION['role'] ?? 'staff';
    $username = $_SESSION['user'] ?? '';
    $dept = $_SESSION['department'] ?? '';

    // Dynamically fetch department if missing
    if ($role === 'staff' && $username) {
        $stmtDept = $pdo->prepare("SELECT department FROM users WHERE username = ?");
        $stmtDept->execute([$username]);
        $fetchedDept = $stmtDept->fetchColumn();
        if ($fetchedDept) $dept = $fetchedDept;
    }

    if ($role === 'admin' || empty($dept)) {
        $stmt = $pdo->query(
            "SELECT p.patient_id, p.full_name, p.department, p.contact_number, p.email, p.chief_complaints,
                    IF('$role' = 'admin', '[REDACTED FOR PATIENT PRIVACY]', p.medical_history_diagnosis) as diagnosis, 
                    IF('$role' = 'admin', '[REDACTED FOR PATIENT PRIVACY]', p.medical_history_treatment) as treatment, 
                    IF('$role' = 'admin', '[REDACTED FOR PATIENT PRIVACY]', p.medical_history_prescription) as prescription,
                    IF('$role' = 'admin', '[REDACTED FOR PATIENT PRIVACY]', p.active_medications) as active_medications,
                    p.next_visit_date,
                    p.medical_history_recorded_by as recorded_by, p.medical_history_date as date,
                    pn.blood_pressure, pn.temperature, pn.heart_rate, pn.weight
             FROM patients p
             LEFT JOIN (
                 SELECT pn1.* FROM progress_notes pn1
                 INNER JOIN (
                     SELECT patient_id, MAX(id) as max_id
                     FROM progress_notes
                     GROUP BY patient_id
                 ) pn2 ON pn1.id = pn2.max_id
             ) pn ON p.patient_id = pn.patient_id
             WHERE p.status IN ('approved', 'completed')
             ORDER BY p.created_at DESC"
        );
        respondOk(['data' => $stmt->fetchAll()]);
    } else {
        $stmt = $pdo->prepare(
            "SELECT p.patient_id, p.full_name, p.department, p.contact_number, p.email, p.chief_complaints,
                    p.medical_history_diagnosis as diagnosis, 
                    p.medical_history_treatment as treatment, 
                    p.medical_history_prescription as prescription,
                    p.active_medications, p.next_visit_date,
                    p.medical_history_recorded_by as recorded_by, p.medical_history_date as date,
                    pn.blood_pressure, pn.temperature, pn.heart_rate, pn.weight
             FROM patients p
             LEFT JOIN (
                 SELECT pn1.* FROM progress_notes pn1
                 INNER JOIN (
                     SELECT patient_id, MAX(id) as max_id
                     FROM progress_notes
                     GROUP BY patient_id
                 ) pn2 ON pn1.id = pn2.max_id
             ) pn ON p.patient_id = pn.patient_id
             WHERE LOWER(p.department) = LOWER(?) AND p.status IN ('approved', 'completed')
             ORDER BY p.created_at DESC"
        );
        $stmt->execute([$dept]);
        respondOk(['data' => $stmt->fetchAll()]);
    }
}

if ($method === 'POST') {
    $body = getBody();
    $patientId = $body['patient_id'] ?? '';
    $diagnosis = $body['diagnosis'] ?? '';
    $treatment = $body['treatment'] ?? '';
    $prescription = $body['prescription'] ?? '';
    $actor = sessionUser();

    $action = $body['action'] ?? '';

    if ($action === 'delete') {
        if (empty($patientId)) respondErr('patient_id is required.');
        try {
            $del = $pdo->prepare("DELETE FROM patients WHERE patient_id = ?");
            $del->execute([$patientId]);

            addLog($pdo, 'info', 'Patient Deleted', $actor, "Deleted patient $patientId from the system.");
            respondOk(['message' => "Patient and medical history deleted successfully."]);
        } catch(PDOException $e) {
            respondErr('Database error: ' . $e->getMessage());
        }
    }

    $activeMeds = $body['active_medications'] ?? null;
    $nextVisit = $body['next_visit_date'] ?? null;
    if ($nextVisit === '') $nextVisit = null;
    $bp = $body['blood_pressure'] ?? null;
    $temp = $body['temperature'] ?? null;
    $hr = $body['heart_rate'] ?? null;
    $weight = $body['weight'] ?? null;

    if (empty($patientId)) respondErr('patient_id is required.');
    if (empty($diagnosis)) respondErr('Diagnosis is required.');
    if (empty($treatment)) respondErr('Treatment is required.');

    try {
        $upd = $pdo->prepare("UPDATE patients SET 
            medical_history_diagnosis = ?, 
            medical_history_treatment = ?, 
            medical_history_prescription = ?, 
            active_medications = ?,
            next_visit_date = ?,
            medical_history_recorded_by = ?, 
            medical_history_date = NOW() 
            WHERE patient_id = ?");
        
        $upd->execute([$diagnosis, $treatment, $prescription, $activeMeds, $nextVisit, $actor, $patientId]);

        if ($bp || $temp || $hr || $weight) {
            $n_stmt = $pdo->prepare("INSERT INTO progress_notes (patient_id, noted_by, blood_pressure, temperature, heart_rate, weight, note) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $n_stmt->execute([$patientId, $actor, $bp, $temp, $hr, $weight, 'Vitals recorded from Triage']);
        }

        if ($nextVisit) {
            // Update the existing patient's appointment date and follow-up without creating a duplicate patient
            $updFollowup = $pdo->prepare("UPDATE patients SET appointment_date = ?, next_visit_date = ?, updated_at = NOW() WHERE patient_id = ?");
            $updFollowup->execute([$nextVisit, $nextVisit, $patientId]);

            $noteStmt = $pdo->prepare("INSERT INTO progress_notes (patient_id, note, noted_by) VALUES (?, 'Follow-Up Scheduled by Staff', ?)");
            $noteStmt->execute([$patientId, $actor]);
        }

        addLog($pdo, 'info', 'Medical History Updated', $actor, "Updated medical history and triage vitals for patient $patientId.");
        respondOk(['message' => "Medical history saved successfully."]);
    } catch(PDOException $e) {
        respondErr('Database error: ' . $e->getMessage());
    }
}

respondErr('Method not allowed.', 405);
