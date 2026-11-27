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
require_once __DIR__ . '/config/settings_helper.php';

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
    ensurePatientOtpColumns($pdo);

    // Persistent brute-force rate limiting (protects system-generated usernames & emails)
    $clientIp = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $now = time();
    $windowStart = $now - 900; // 15 minutes
    $identBucket = 'pt_login_id:' . hash('sha256', $clientIp . '|' . strtolower($username));
    $ipBucket    = 'pt_login_ip:' . hash('sha256', $clientIp);
    try {
        if (!dbIsPgsql()) {
            @$pdo->exec("
                CREATE TABLE IF NOT EXISTS `auth_rate_limits` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `bucket_key` VARCHAR(191) NOT NULL,
                    `created_at` INT UNSIGNED NOT NULL,
                    INDEX `idx_bucket_time` (`bucket_key`, `created_at`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
        }
        $pdo->prepare("DELETE FROM auth_rate_limits WHERE created_at < ?")->execute([$windowStart]);
        $cIdent = $pdo->prepare("SELECT COUNT(*) FROM auth_rate_limits WHERE bucket_key = ? AND created_at >= ?");
        $cIdent->execute([$identBucket, $windowStart]);
        $cIp = $pdo->prepare("SELECT COUNT(*) FROM auth_rate_limits WHERE bucket_key = ? AND created_at >= ?");
        $cIp->execute([$ipBucket, $windowStart]);
        if ((int)$cIdent->fetchColumn() >= 10 || (int)$cIp->fetchColumn() >= 25) {
            http_response_code(429);
            echo json_encode([
                'success' => false,
                'message' => 'Too many failed sign-in attempts. Please wait 15 minutes before trying again.'
            ]);
            exit;
        }
    } catch (Throwable $ignoreRl) {}

    $recordLoginFailure = static function () use ($pdo, $identBucket, $ipBucket, $now): void {
        try {
            $ins = $pdo->prepare("INSERT INTO auth_rate_limits (bucket_key, created_at) VALUES (?, ?)");
            $ins->execute([$identBucket, $now]);
            $ins->execute([$ipBucket, $now]);
        } catch (Throwable $ignore) {}
    };

    $stmt = $pdo->prepare("
        SELECT id, patient_id, full_name, email, username, password, is_verified, status,
               COALESCE(account_status, 'active') AS account_status,
               COALESCE(must_change_password, 0) AS must_change_password,
               otp_code, otp_hash, otp_expires_at, otp_attempts, is_otp_enabled
        FROM patients
        WHERE (LOWER(username) = LOWER(:ident_user) OR LOWER(email) = LOWER(:ident_email))
          AND COALESCE(appointment_recipient, 'Patient (Me)') NOT IN ('Other Patient', 'Someone Else / Dependent')
          AND password IS NOT NULL AND password <> ''
        ORDER BY is_verified DESC, id ASC
        LIMIT 1
    ");
    $stmt->execute([':ident_user' => $username, ':ident_email' => $username]);
    $patient = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$patient || empty($patient['password']) || !password_verify($password, $patient['password'])) {
        $recordLoginFailure();
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid username, email, or password.'
        ]);
        exit;
    }

    // Clear failed-attempt bucket for this IP+identifier upon valid credentials
    try {
        $pdo->prepare("DELETE FROM auth_rate_limits WHERE bucket_key = ?")->execute([$identBucket]);
    } catch (Throwable $ignoreClear) {}

    if (strtolower((string)($patient['account_status'] ?? 'active')) === 'suspended') {
        http_response_code(403);
        echo json_encode([
            'success'   => false,
            'suspended' => true,
            'message'   => 'This patient account has been suspended. Please contact the hospital administration desk.'
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

        $otpExpiryMin  = max(1, (int)getSettingValue($pdo, 'otp_expiry_minutes', 10));
        $otpTtlSeconds = $otpExpiryMin * 60;
        $otp = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $otpHash = password_hash($otp, PASSWORD_BCRYPT, ['cost' => 10]);

        $otpExpiresAt = date('Y-m-d H:i:s', time() + $otpTtlSeconds);
        $pdo->prepare("UPDATE patients SET otp_code = ?, otp_hash = ?, otp_expires_at = ?, otp_attempts = 0 WHERE id = ?")
            ->execute([$otpHash, $otpHash, $otpExpiresAt, $patient['id']]);

        $_SESSION['pending_otp_patient_id'] = (int)$patient['id'];
        $_SESSION['pending_otp_mode']       = 'login';
        $_SESSION['pending_otp_remember']   = $remember ? 1 : 0;
        $_SESSION['pending_otp_sent_at']    = time();
        $_SESSION['pending_otp_expires_at'] = time() + $otpTtlSeconds;

        flushJsonAndContinue([
            'success'         => false,
            'otp_required'    => true,
            'otp_mode'        => 'verify_account',
            'email_sent'      => true,
            'masked_email'    => maskEmail($patient['email']),
            'expires_in'      => $otpTtlSeconds,
            'resend_cooldown' => 60,
            'redirect'        => '/sdn/pages/verify-otp.php',
            'message'         => 'A 6-digit verification code has been sent to ' . maskEmail($patient['email']) . '.'
        ]);

        sendPatientOtpEmail($patient['email'], $patient['full_name'] ?: 'Patient', $otp, $otpExpiryMin);
        exit;
    }

    // Two-Step Verification (Email OTP) when enabled by the patient
    if ((int)($patient['is_otp_enabled'] ?? 0) === 1) {
        if (empty($patient['email']) || !filter_var($patient['email'], FILTER_VALIDATE_EMAIL)) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'message' => 'Two-step verification is enabled, but no valid email is registered on your account.'
            ]);
            exit;
        }

        $otpExpiryMin  = max(1, (int)getSettingValue($pdo, 'otp_expiry_minutes', 10));
        $otpTtlSeconds = $otpExpiryMin * 60;
        $otp = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $otpHash = password_hash($otp, PASSWORD_BCRYPT, ['cost' => 10]);

        $otpExpiresAt = date('Y-m-d H:i:s', time() + $otpTtlSeconds);
        $pdo->prepare("UPDATE patients SET otp_code = ?, otp_hash = ?, otp_expires_at = ?, otp_attempts = 0 WHERE id = ?")
            ->execute([$otpHash, $otpHash, $otpExpiresAt, $patient['id']]);

        $_SESSION['pending_otp_patient_id'] = (int)$patient['id'];
        $_SESSION['pending_otp_mode']       = 'login_2fa';
        $_SESSION['pending_otp_remember']   = $remember ? 1 : 0;
        $_SESSION['pending_otp_sent_at']    = time();
        $_SESSION['pending_otp_expires_at'] = time() + $otpTtlSeconds;

        flushJsonAndContinue([
            'success'         => false,
            'otp_required'    => true,
            'otp_mode'        => 'login_2fa',
            'email_sent'      => true,
            'masked_email'    => maskEmail($patient['email']),
            'expires_in'      => $otpTtlSeconds,
            'resend_cooldown' => 60,
            'message'         => 'A 6-digit verification code has been sent to ' . maskEmail($patient['email']) . '.'
        ]);

        sendPatientOtpEmail($patient['email'], $patient['full_name'] ?: 'Patient', $otp, $otpExpiryMin);
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
