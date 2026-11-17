<?php
/**
 * api/patient_verify_otp.php
 * Verifies or resends the 6-digit email OTP for first-time patient login.
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

$pendingId = (int)($_SESSION['pending_otp_patient_id'] ?? 0);
if ($pendingId <= 0) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Your verification session has expired. Please sign in again.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) $input = $_POST;

$action = trim((string)($input['action'] ?? 'verify'));

try {
    ensurePatientOtpColumns($pdo);

    $stmt = $pdo->prepare("
        SELECT id, patient_id, full_name, email, username, password, is_verified, status, otp_hash, otp_expires_at, otp_attempts
        FROM patients
        WHERE id = ?
        LIMIT 1
    ");
    $stmt->execute([$pendingId]);
    $patient = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$patient) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Account not found.']);
        exit;
    }

    if ($action === 'resend') {
        $lastSent = (int)($_SESSION['pending_otp_sent_at'] ?? 0);
        if (time() - $lastSent < 30) {
            $wait = 30 - (time() - $lastSent);
            http_response_code(429);
            echo json_encode(['success' => false, 'message' => "Please wait {$wait}s before requesting another code."]);
            exit;
        }
        $otp = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $otpHash = password_hash($otp, PASSWORD_BCRYPT, ['cost' => 10]);
        $otpExpires = date('Y-m-d H:i:s', strtotime('+10 minutes'));
        $pdo->prepare("UPDATE patients SET otp_hash = ?, otp_expires_at = ?, otp_attempts = 0 WHERE id = ?")
            ->execute([$otpHash, $otpExpires, $pendingId]);
        $_SESSION['pending_otp_sent_at'] = time();
        flushJsonAndContinue(['success' => true, 'message' => 'A new verification code was sent to ' . maskEmail($patient['email']) . '.']);
        sendPatientOtpEmail($patient['email'], $patient['full_name'] ?: 'Patient', $otp);
        exit;
    }

    $otpInput = preg_replace('/\D/', '', (string)($input['otp'] ?? ''));
    if (strlen($otpInput) !== 6) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Please enter the 6-digit verification code.']);
        exit;
    }

    if (empty($patient['otp_hash']) || empty($patient['otp_expires_at']) || strtotime($patient['otp_expires_at']) < time()) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Your verification code has expired. Please request a new code.']);
        exit;
    }

    if ((int)$patient['otp_attempts'] >= 5) {
        $pdo->prepare("UPDATE patients SET otp_hash = NULL, otp_expires_at = NULL WHERE id = ?")->execute([$pendingId]);
        http_response_code(429);
        echo json_encode(['success' => false, 'message' => 'Too many failed attempts. Please request a new verification code.']);
        exit;
    }

    if (!password_verify($otpInput, $patient['otp_hash'])) {
        $pdo->prepare("UPDATE patients SET otp_attempts = otp_attempts + 1 WHERE id = ?")->execute([$pendingId]);
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Invalid verification code. Please try again.']);
        exit;
    }

    $pdo->prepare("UPDATE patients SET is_verified = 1, otp_hash = NULL, otp_expires_at = NULL, otp_attempts = 0, updated_at = NOW() WHERE id = ?")
        ->execute([$pendingId]);

    $remember = !empty($_SESSION['pending_otp_remember']);
    unset($_SESSION['pending_otp_patient_id'], $_SESSION['pending_otp_remember'], $_SESSION['pending_otp_sent_at']);

    $patient['is_verified'] = 1;
    completePatientLogin($pdo, $patient, $remember);

    echo json_encode([
        'success'  => true,
        'message'  => 'Account verified! Redirecting to your portal...',
        'redirect' => '/sdn/pages/patient-dashboard.php'
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    error_log("Patient OTP error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'A server error occurred during verification.']);
}
