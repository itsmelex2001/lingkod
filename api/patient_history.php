<?php
/**
 * api/patient_history.php
 *
 * GET ?patient_id=XXX    +' Returns history records for a specific patient
 * POST                   +' Adds a new history record
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/config/cors.php';
require_once __DIR__ . '/config/db.php';

header('Content-Type: application/json; charset=utf-8');

requireAuth();
$role = strtolower(trim((string)($_SESSION['role'] ?? '')));
if (!in_array($role, ['admin', 'superadmin', 'staff'], true)) {
    respondErr('Forbidden. Clinical staff or administrator access required.', 403);
}

$normalizeDeptBucket = static function (?string $dept): string {
    $d = strtolower(trim((string)$dept));
    if ($d === '' || str_contains($d, 'triage')) return '';
    if (str_contains($d, 'psych') || str_contains($d, 'opu')) return 'psychiatry';
    if (str_contains($d, 'neuro')) return 'neurology';
    if (str_contains($d, 'general') || str_contains($d, 'medical') || str_contains($d, 'cardio')) return 'general';
    return $d;
};

$assertPatientDepartmentAccess = static function (PDO $pdo, string $patientId) use ($role, $normalizeDeptBucket): array {
    $check = $pdo->prepare("SELECT patient_id, full_name, department FROM patients WHERE patient_id = ? LIMIT 1");
    $check->execute([$patientId]);
    $patient = $check->fetch(PDO::FETCH_ASSOC);
    if (!$patient) {
        respondErr('Patient record not found.', 404);
    }
    if ($role === 'staff') {
        $staffDept = trim((string)($_SESSION['department'] ?? ''));
        $username = sessionUser();
        if ($username !== '' && $username !== 'System') {
            $uStmt = $pdo->prepare("SELECT department FROM users WHERE username = ? LIMIT 1");
            $uStmt->execute([$username]);
            $dbDept = trim((string)($uStmt->fetchColumn() ?: ''));
            if ($dbDept !== '') $staffDept = $dbDept;
        }
        $staffBucket = $normalizeDeptBucket($staffDept);
        $patientBucket = $normalizeDeptBucket($patient['department'] ?? '');
        if ($staffBucket === '' || ($patientBucket !== '' && $patientBucket !== $staffBucket)) {
            respondErr('Forbidden. This patient belongs to another department.', 403);
        }
    }
    return $patient;
};

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $patient_id = trim((string)($_GET['patient_id'] ?? ''));

    if ($patient_id === '') {
        respondErr('Patient ID is required.', 400);
    }

    $assertPatientDepartmentAccess($pdo, $patient_id);

    try {
        $stmt = $pdo->prepare("
            SELECT id, record_date, diagnosis, treatment, prescription, remarks, recorded_by, created_at
            FROM patient_medical_history
            WHERE patient_id = ?
            ORDER BY record_date DESC, created_at DESC
        ");
        $stmt->execute([$patient_id]);
        $history = $stmt->fetchAll();

        echo json_encode(['success' => true, 'data' => $history]);
    } catch (PDOException $e) {
        error_log('[patient_history.php GET] ' . $e->getMessage());
        respondErr('Database error.', 500);
    }
    exit;
}

if ($method === 'POST') {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);

    if (!is_array($data)) {
        respondErr('Invalid JSON payload.', 400);
    }

    $patient_id   = trim((string)($data['patient_id'] ?? ''));
    $record_date  = trim((string)($data['record_date'] ?? date('Y-m-d')));
    $diagnosis    = trim((string)($data['diagnosis'] ?? ''));
    $treatment    = trim((string)($data['treatment'] ?? ''));
    $prescription = trim((string)($data['prescription'] ?? ''));
    $remarks      = trim((string)($data['remarks'] ?? ''));

    if ($patient_id === '' || $diagnosis === '') {
        respondErr('Patient ID and Diagnosis are required.', 422);
    }
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $record_date)) {
        respondErr('Invalid record_date format (YYYY-MM-DD required).', 422);
    }

    $assertPatientDepartmentAccess($pdo, $patient_id);

    $recorded_by = trim((string)($_SESSION['full_name'] ?? '')) ?: sessionUser();

    try {
        $stmt = $pdo->prepare("
            INSERT INTO patient_medical_history
            (patient_id, record_date, diagnosis, treatment, prescription, remarks, recorded_by)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $patient_id,
            $record_date,
            $diagnosis,
            $treatment,
            $prescription,
            $remarks,
            $recorded_by
        ]);

        // Also log this in progress_notes as a brief update
        $noteStmt = $pdo->prepare("
            INSERT INTO progress_notes (patient_id, note, noted_by)
            VALUES (?, ?, ?)
        ");
        $briefNote = "Added Medical History: " . mb_substr($diagnosis, 0, 50);
        $noteStmt->execute([$patient_id, $briefNote, $recorded_by]);

        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        error_log('[patient_history.php POST] ' . $e->getMessage());
        respondErr('Database error while saving medical history.', 500);
    }
    exit;
}

respondErr('Method not allowed.', 405);
