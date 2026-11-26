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
require_once __DIR__ . '/config/settings_helper.php';

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
        SELECT id, patient_id, full_name, email, username, password, is_verified, status, otp_code, otp_hash, otp_expires_at, otp_attempts, is_otp_enabled,
               TIMESTAMPDIFF(SECOND, NOW(), otp_expires_at) AS otp_seconds_left
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

    $mode = $_SESSION['pending_otp_mode'] ?? 'login';
    $cfgExpiryMin   = max(1, (int)getSettingValue($pdo, 'otp_expiry_minutes', 10));
    $cfgMaxAttempts = max(1, (int)getSettingValue($pdo, 'otp_max_attempts', 5));
    $expiresMinutes = ($mode === 'verify_only') ? max(30, $cfgExpiryMin) : $cfgExpiryMin;
    $expiresSeconds = $expiresMinutes * 60;
    $resendCooldown = 60;

    if ($action === 'resend') {
        $lastSent = (int)($_SESSION['pending_otp_sent_at'] ?? 0);
        if (time() - $lastSent < $resendCooldown) {
            $wait = $resendCooldown - (time() - $lastSent);
            http_response_code(429);
            echo json_encode([
                'success'     => false,
                'retry_after' => $wait,
                'message'     => "Please wait {$wait}s before requesting another code."
            ]);
            exit;
        }
        $otp = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $otpHash = password_hash($otp, PASSWORD_BCRYPT, ['cost' => 10]);
        $pdo->prepare("UPDATE patients SET otp_code = ?, otp_hash = ?, otp_expires_at = DATE_ADD(NOW(), INTERVAL ? SECOND), otp_attempts = 0 WHERE id = ?")
            ->execute([$otpHash, $otpHash, $expiresSeconds, $pendingId]);
        $_SESSION['pending_otp_sent_at']    = time();
        $_SESSION['pending_otp_expires_at'] = time() + $expiresSeconds;
        flushJsonAndContinue([
            'success'         => true,
            'resend_cooldown' => $resendCooldown,
            'expires_in'      => $expiresSeconds,
            'message'         => 'A new verification code was sent to ' . maskEmail($patient['email']) . '.'
        ]);
        sendPatientOtpEmail($patient['email'], $patient['full_name'] ?: 'Patient', $otp, $expiresMinutes);
        exit;
    }

    $otpInput = preg_replace('/\D/', '', (string)($input['otp'] ?? ''));
    if (strlen($otpInput) !== 6) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Please enter the 6-digit verification code.']);
        exit;
    }

    $storedHash = $patient['otp_hash'] ?: ($patient['otp_code'] ?? '');
    $dbSecondsLeft = isset($patient['otp_seconds_left']) ? (int)$patient['otp_seconds_left'] : 0;
    $sessionExpired = !empty($_SESSION['pending_otp_expires_at']) && time() >= (int)$_SESSION['pending_otp_expires_at'];

    if ($storedHash === '' || empty($patient['otp_expires_at']) || $dbSecondsLeft <= 0 || $sessionExpired) {
        $pdo->prepare("UPDATE patients SET otp_code = NULL, otp_hash = NULL, otp_expires_at = NULL WHERE id = ?")->execute([$pendingId]);
        unset($_SESSION['pending_otp_expires_at']);
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'expired' => true,
            'message' => 'Your verification code has expired. Please click Resend Code to request a new one.'
        ]);
        exit;
    }

    if ((int)$patient['otp_attempts'] >= $cfgMaxAttempts) {
        $pdo->prepare("UPDATE patients SET otp_code = NULL, otp_hash = NULL, otp_expires_at = NULL WHERE id = ?")->execute([$pendingId]);
        http_response_code(429);
        echo json_encode(['success' => false, 'message' => 'Too many failed attempts. Please request a new verification code.']);
        exit;
    }

    if (!password_verify($otpInput, $storedHash)) {
        $pdo->prepare("UPDATE patients SET otp_attempts = otp_attempts + 1 WHERE id = ?")->execute([$pendingId]);
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Invalid verification code. Please check your latest email or click Resend Code.']);
        exit;
    }

    if ($mode === 'login_2fa') {
        $pdo->prepare("UPDATE patients SET otp_code = NULL, otp_hash = NULL, otp_expires_at = NULL, otp_attempts = 0, updated_at = NOW() WHERE id = ?")
            ->execute([$pendingId]);
    } else {
        $pdo->prepare("UPDATE patients SET is_verified = 1, verified_at = IFNULL(verified_at, NOW()), otp_code = NULL, otp_hash = NULL, otp_expires_at = NULL, otp_attempts = 0, updated_at = NOW() WHERE id = ?")
            ->execute([$pendingId]);
    }

    $remember = !empty($_SESSION['pending_otp_remember']);
    unset($_SESSION['pending_otp_patient_id'], $_SESSION['pending_otp_remember'], $_SESSION['pending_otp_sent_at'], $_SESSION['pending_otp_expires_at'], $_SESSION['pending_otp_mode']);

    if ($mode === 'verify_only') {
        echo json_encode([
            'success'       => true,
            'verified_only' => true,
            'message'       => 'Account verified! You may now sign in to the Patient Portal.',
            'redirect'      => '/sdn/pages/patient-login.php?verified=1'
        ]);
        exit;
    }

    $patient['is_verified'] = 1;
    completePatientLogin($pdo, $patient, $remember);

    echo json_encode([
        'success'  => true,
        'message'  => $mode === 'login_2fa' ? 'Verification successful! Redirecting to your portal...' : 'Account verified! Redirecting to your portal...',
        'redirect' => '/sdn/pages/patient-dashboard.php'
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    error_log("Patient OTP error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'A server error occurred during verification.']);
}
