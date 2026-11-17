<?php
/**
 * api/verify_email.php
 * Validates the email verification link token and redirects to the dedicated
 * 6-digit OTP verification page (never auto-verifies without OTP).
 */

session_start();
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/patient_otp.php';

$token = trim($_GET['token'] ?? '');

if ($token === '') {
    header('Location: /sdn/pages/patient-login.php?verify_error=1');
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT id, patient_id, full_name, email, is_verified, token_expires_at
        FROM patients
        WHERE verification_token = :token
        LIMIT 1
    ");
    $stmt->execute([':token' => $token]);
    $patient = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$patient) {
        header('Location: /sdn/pages/patient-login.php?verify_error=1');
        exit;
    }

    if ((int)$patient['is_verified'] === 1) {
        header('Location: /sdn/pages/patient-login.php?verified=1');
        exit;
    }

    if (!empty($patient['token_expires_at']) && strtotime($patient['token_expires_at']) < time()) {
        header('Location: /sdn/pages/patient-login.php?verify_error=1');
        exit;
    }

    ensurePatientOtpColumns($pdo);
    $_SESSION['pending_otp_patient_id'] = (int)$patient['id'];
    $_SESSION['pending_otp_mode']       = 'verify_only';

    header('Location: /sdn/pages/verify-otp.php?token=' . urlencode($token));
    exit;
} catch (Throwable $e) {
    error_log('verify_email.php error: ' . $e->getMessage());
    header('Location: /sdn/pages/patient-login.php?verify_error=1');
    exit;
}
