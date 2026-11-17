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
require_once __DIR__ . '/config/patient_remember.php';
require_once __DIR__ . '/config/patient_otp.php';

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

$username = trim($input['username'] ?? ($input['email'] ?? ''));
$password = (string)($input['password'] ?? '');
$remember = !empty($input['remember']);

if ($username === '' || $password === '') {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Username and password are required.']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT id, patient_id, full_name, email, username, password, is_verified, status
        FROM patients
        WHERE username = :username
        LIMIT 1
    ");
    $stmt->execute([':username' => $username]);
    $patient = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$patient || empty($patient['password'])) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid username or password.'
        ]);
        exit;
    }

    if (!password_verify($password, $patient['password'])) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid username or password.'
        ]);
        exit;
    }

    // Check verification status: require 6-digit email OTP on first sign-in
    if ((int)$patient['is_verified'] !== 1) {
        if (empty($patient['email']) || !filter_var($patient['email'], FILTER_VALIDATE_EMAIL)) {
            http_response_code(403);
            echo json_encode([
                'success'    => false,
                'unverified' => true,
                'message'    => 'Your account is not verified yet and has no valid email on file.'
            ]);
            exit;
        }

        ensurePatientOtpColumns($pdo);
        $otp = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $otpHash = password_hash($otp, PASSWORD_BCRYPT, ['cost' => 10]);
        $otpExpires = date('Y-m-d H:i:s', strtotime('+10 minutes'));

        $pdo->prepare("UPDATE patients SET otp_hash = ?, otp_expires_at = ?, otp_attempts = 0 WHERE id = ?")
            ->execute([$otpHash, $otpExpires, $patient['id']]);

        $_SESSION['pending_otp_patient_id'] = (int)$patient['id'];
        $_SESSION['pending_otp_mode']       = 'login';
        $_SESSION['pending_otp_remember']   = $remember ? 1 : 0;
        $_SESSION['pending_otp_sent_at']    = time();

        flushJsonAndContinue([
            'success'      => false,
            'otp_required' => true,
            'email_sent'   => true,
            'masked_email' => maskEmail($patient['email']),
            'redirect'     => '/sdn/pages/verify-otp.php',
            'message'      => 'A 6-digit verification code has been sent to ' . maskEmail($patient['email']) . '.'
        ]);

        sendPatientOtpEmail($patient['email'], $patient['full_name'] ?: 'Patient', $otp);
        exit;
    }

    completePatientLogin($pdo, $patient, $remember);

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
