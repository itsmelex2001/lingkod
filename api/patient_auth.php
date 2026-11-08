<?php
/**
 * api/patient_auth.php
 * Handles Patient Portal login verification:
 * - Checks credentials against patients table
 * - Strictly verifies is_verified == 1
 */

session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/config/cors.php';
require_once __DIR__ . '/config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

$raw = file_get_contents('php://input');
$input = json_decode($raw, true);
if (!is_array($input)) {
    $input = $_POST;
}

$email = trim($input['email'] ?? ($input['username'] ?? ''));
$password = (string)($input['password'] ?? '');

if ($email === '' || $password === '') {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Email and password are required.']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT id, patient_id, full_name, email, username, password, is_verified, status
        FROM patients
        WHERE (email = :login1 OR (username IS NOT NULL AND username = :login2))
        LIMIT 1
    ");
    $stmt->execute([':login1' => $email, ':login2' => $email]);
    $patient = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$patient || empty($patient['password'])) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid email or password.'
        ]);
        exit;
    }

    if (!password_verify($password, $patient['password'])) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid email or password.'
        ]);
        exit;
    }

    // Check verification status
    if ((int)$patient['is_verified'] !== 1) {
        http_response_code(403);
        echo json_encode([
            'success'     => false,
            'unverified'  => true,
            'message'     => 'Your account is not verified yet. Please check your email to activate your account.'
        ]);
        exit;
    }

    // Login successful
    // Find or create linked user in users table
    $userId = null;
    try {
        $uStmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR patient_id = ? LIMIT 1");
        $uStmt->execute([$patient['email'], $patient['id']]);
        $userId = $uStmt->fetchColumn() ?: null;
        if (!$userId) {
            $insU = $pdo->prepare("INSERT INTO users (username, email, password, role, status, patient_id) VALUES (?, ?, ?, 'patient', 'active', ?)");
            $insU->execute([$patient['username'] ?: $patient['patient_id'], $patient['email'], $patient['password'], $patient['id']]);
            $userId = $pdo->lastInsertId();
        }
        $pdo->prepare("UPDATE patients SET user_id = ? WHERE id = ? AND user_id IS NULL")->execute([$userId, $patient['id']]);
    } catch (Throwable $ignoreUser) {}

    $_SESSION['patient_logged_in'] = true;
    $_SESSION['patient_id']        = $patient['patient_id'];
    $_SESSION['patient_db_id']     = $patient['id'];
    $_SESSION['patient_name']      = $patient['full_name'];
    $_SESSION['patient_email']     = $patient['email'];
    $_SESSION['user_id']           = $userId;
    $_SESSION['patient_user_id']   = $userId;
    $_SESSION['user_role']         = 'patient';
    $_SESSION['role']              = 'patient';

    echo json_encode([
        'success'    => true,
        'message'    => 'Login successful! Welcome back, ' . $patient['full_name'],
        'patient_id' => $patient['patient_id'],
        'name'       => $patient['full_name'],
        'redirect'   => '/sdn/pages/patient-dashboard.php'
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    error_log("Patient login DB error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'A database error occurred.']);
}
