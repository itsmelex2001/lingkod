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
            "SELECT patient_id, full_name, medical_history_diagnosis as diagnosis, 
                    medical_history_treatment as treatment, medical_history_prescription as prescription,
                    medical_history_recorded_by as recorded_by, medical_history_date as date
             FROM patients
             WHERE status = 'approved'
             ORDER BY created_at DESC"
        );
        respondOk(['data' => $stmt->fetchAll()]);
    } else {
        $stmt = $pdo->prepare(
            "SELECT patient_id, full_name, medical_history_diagnosis as diagnosis, 
                    medical_history_treatment as treatment, medical_history_prescription as prescription,
                    medical_history_recorded_by as recorded_by, medical_history_date as date
             FROM patients
             WHERE LOWER(department) = LOWER(?) AND status = 'approved'
             ORDER BY created_at DESC"
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

    if (empty($patientId)) respondErr('patient_id is required.');
    if (empty($diagnosis)) respondErr('Diagnosis is required.');
    if (empty($treatment)) respondErr('Treatment is required.');

    try {
        $upd = $pdo->prepare("UPDATE patients SET 
            medical_history_diagnosis = ?, 
            medical_history_treatment = ?, 
            medical_history_prescription = ?, 
            medical_history_recorded_by = ?, 
            medical_history_date = NOW() 
            WHERE patient_id = ?");
        
        $upd->execute([$diagnosis, $treatment, $prescription, $actor, $patientId]);

        addLog($pdo, 'info', 'Medical History Updated', $actor, "Updated medical history for patient $patientId.");
        respondOk(['message' => "Medical history saved successfully."]);
    } catch(PDOException $e) {
        respondErr('Database error: ' . $e->getMessage());
    }
}

respondErr('Method not allowed.', 405);
