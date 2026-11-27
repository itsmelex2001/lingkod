<?php
/**
 * api/progress_notes.php
 *
 * GET  ?patient_id=PT-2026-XXX   → list all notes for a patient
 * POST {patient_id, note, noted_by} → add a progress note
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/config/cors.php';
require_once __DIR__ . '/config/db.php';

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
        respondErr("Patient $patientId not found.", 404);
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

// ── GET: List notes for a patient ────────────────────────────────────────────
if ($method === 'GET') {
    $patientId = trim($_GET['patient_id'] ?? '');
    if ($patientId === '') respondErr('patient_id is required.');

    $assertPatientDepartmentAccess($pdo, $patientId);

    $stmt = $pdo->prepare(
        "SELECT id, patient_id, note, noted_by, created_at
         FROM progress_notes
         WHERE patient_id = ?
         ORDER BY created_at DESC"
    );
    $stmt->execute([$patientId]);
    respondOk(['data' => $stmt->fetchAll()]);
}

// ── POST: Add a progress note ─────────────────────────────────────────────────
if ($method === 'POST') {
    $body      = getBody();
    $patientId = trim($body['patient_id'] ?? '');
    $note      = trim($body['note']       ?? '');
    // Always derive author from verified server session; never trust client-supplied noted_by
    $notedBy   = trim((string)($_SESSION['full_name'] ?? '')) ?: sessionUser();

    if ($patientId === '') respondErr('patient_id is required.');
    if ($note      === '') respondErr('note cannot be empty.');
    if (mb_strlen($note) > 5000) respondErr('Note is too long (max 5000 characters).');

    $patient = $assertPatientDepartmentAccess($pdo, $patientId);

    $ins = $pdo->prepare(
        "INSERT INTO progress_notes (patient_id, note, noted_by) VALUES (?, ?, ?)"
    );
    $ins->execute([$patientId, $note, $notedBy]);

    addLog($pdo, 'info', 'Progress Note Added', $notedBy,
           "Note added for patient $patientId ({$patient['full_name']}).");

    respondOk([
        'message' => 'Note added successfully.',
        'data'    => [
            'id'         => $pdo->lastInsertId(),
            'patient_id' => $patientId,
            'note'       => $note,
            'noted_by'   => $notedBy,
            'created_at' => date('Y-m-d H:i:s'),
        ],
    ]);
}

respondErr('Method not allowed.', 405);
