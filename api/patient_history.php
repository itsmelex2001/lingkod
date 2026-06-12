<?php
/**
 * api/patient_history.php
 *
 * GET ?patient_id=XXX    +' Returns history records for a specific patient
 * POST                   +' Adds a new history record
 */

session_start();
require_once __DIR__ . '/config/cors.php';
require_once __DIR__ . '/config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $patient_id = $_GET['patient_id'] ?? '';
    
    if (!$patient_id) {
        echo json_encode(['success' => false, 'error' => 'Patient ID is required']);
        exit;
    }
    
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
        echo json_encode(['success' => false, 'error' => 'Database error']);
    }
    exit;
}

if ($method === 'POST') {
    // Both Admin and Staff can add history
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    
    if (!$data) {
        echo json_encode(['success' => false, 'error' => 'Invalid JSON']);
        exit;
    }
    
    $patient_id = $data['patient_id'] ?? '';
    $record_date = $data['record_date'] ?? date('Y-m-d');
    $diagnosis = trim($data['diagnosis'] ?? '');
    $treatment = trim($data['treatment'] ?? '');
    $prescription = trim($data['prescription'] ?? '');
    $remarks = trim($data['remarks'] ?? '');
    
    if (!$patient_id || !$diagnosis) {
        echo json_encode(['success' => false, 'error' => 'Patient ID and Diagnosis are required']);
        exit;
    }
    
    // Who is recording?
    $recorded_by = $_SESSION['username'] ?? 'System';
    if ($_SESSION['role'] === 'staff') {
        // Find staff full name if possible, or just use username
        $recorded_by = $_SESSION['username'];
    }
    
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
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

echo json_encode(['success' => false, 'error' => 'Method not allowed']);
