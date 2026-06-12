<?php
/**
 * api/progress_notes.php
 *
 * GET  ?patient_id=PT-2026-XXX   → list all notes for a patient
 * POST {patient_id, note, noted_by} → add a progress note
 */

session_start();
require_once __DIR__ . '/config/cors.php';
require_once __DIR__ . '/config/db.php';

requireAuth();

$method = $_SERVER['REQUEST_METHOD'];

// ── GET: List notes for a patient ────────────────────────────────────────────
if ($method === 'GET') {
    $patientId = trim($_GET['patient_id'] ?? '');
    if ($patientId === '') respondErr('patient_id is required.');

    // Verify patient exists
    $check = $pdo->prepare("SELECT patient_id FROM patients WHERE patient_id = ?");
    $check->execute([$patientId]);
    if (!$check->fetch()) respondErr("Patient $patientId not found.", 404);

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
    $notedBy   = trim($body['noted_by']   ?? '') ?: sessionUser() ?: 'Admin';

    if ($patientId === '') respondErr('patient_id is required.');
    if ($note      === '') respondErr('note cannot be empty.');
    if (mb_strlen($note) > 5000) respondErr('Note is too long (max 5000 characters).');

    // Verify patient exists
    $check = $pdo->prepare("SELECT full_name FROM patients WHERE patient_id = ?");
    $check->execute([$patientId]);
    $patient = $check->fetch();
    if (!$patient) respondErr("Patient $patientId not found.", 404);

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
