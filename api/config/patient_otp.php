<?php
/**
 * api/config/patient_otp.php
 * Shared helpers for patient email OTP verification.
 */

date_default_timezone_set('Asia/Manila');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function ensurePatientOtpColumns(PDO $pdo): void {
    static $checked = false;
    if ($checked) return;
    $checked = true;
    try {
        $cols = $pdo->query("SHOW COLUMNS FROM patients")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('otp_code', $cols, true)) {
            $pdo->exec("ALTER TABLE patients ADD COLUMN otp_code VARCHAR(255) NULL");
        }
        if (!in_array('otp_hash', $cols, true)) {
            $pdo->exec("ALTER TABLE patients ADD COLUMN otp_hash VARCHAR(255) NULL");
        }
        if (!in_array('otp_expires_at', $cols, true)) {
            $pdo->exec("ALTER TABLE patients ADD COLUMN otp_expires_at DATETIME NULL");
        }
        if (!in_array('otp_attempts', $cols, true)) {
            $pdo->exec("ALTER TABLE patients ADD COLUMN otp_attempts TINYINT UNSIGNED NOT NULL DEFAULT 0");
        }
        if (!in_array('is_otp_enabled', $cols, true)) {
            $pdo->exec("ALTER TABLE patients ADD COLUMN is_otp_enabled TINYINT(1) NOT NULL DEFAULT 0");
        }
        if (!in_array('account_status', $cols, true)) {
            $pdo->exec("ALTER TABLE patients ADD COLUMN account_status VARCHAR(20) NOT NULL DEFAULT 'active'");
        }
        if (!in_array('must_change_password', $cols, true)) {
            $pdo->exec("ALTER TABLE patients ADD COLUMN must_change_password TINYINT(1) NOT NULL DEFAULT 0");
        }
    } catch (Throwable $e) {
        error_log("ensurePatientOtpColumns: " . $e->getMessage());
    }
}

function maskEmail(string $email): string {
    $parts = explode('@', $email, 2);
    if (count($parts) !== 2) return $email;
    $local = $parts[0];
    $visible = substr($local, 0, min(2, strlen($local)));
    return $visible . str_repeat('*', max(2, strlen($local) - strlen($visible))) . '@' . $parts[1];
}

function loadEnvVars(): void {
    $envFile = dirname(__DIR__, 2) . '/.env';
    if (!file_exists($envFile)) return;
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (strpos(trim($line), '#') === 0 || strpos($line, '=') === false) continue;
        list($key, $val) = explode('=', $line, 2);
        $key = trim($key);
        $val = trim($val, " \t\n\r\0\x0B\"'");
        if (!array_key_exists($key, $_ENV)) $_ENV[$key] = $val;
    }
}

function sendPatientOtpEmail(string $email, string $fullName, string $otp, int $expiresMinutes = 5): bool {
    loadEnvVars();
    $autoloadPath = dirname(__DIR__, 2) . '/vendor/autoload.php';
    if (file_exists($autoloadPath)) {
        require_once $autoloadPath;
    }

    $sent = false;
    if (class_exists(PHPMailer::class)) {
        $mail = new PHPMailer(true);
        try {
            $smtpHost   = !empty($_ENV['SMTP_HOST']) ? $_ENV['SMTP_HOST'] : 'smtp.gmail.com';
            $smtpPort   = !empty($_ENV['SMTP_PORT']) ? (int)$_ENV['SMTP_PORT'] : 587;
            $smtpUser   = trim($_ENV['SMTP_USER'] ?? '');
            $smtpPass   = trim($_ENV['SMTP_PASS'] ?? '');
            $smtpSecure = strtolower(trim($_ENV['SMTP_SECURE'] ?? 'tls'));

            if ($smtpUser !== '' && $smtpPass !== '') {
                $mail->isSMTP();
                $mail->Timeout    = 8;
                $mail->Host       = $smtpHost;
                $mail->SMTPAuth   = true;
                $mail->Username   = $smtpUser;
                $mail->Password   = $smtpPass;
                $mail->Port       = $smtpPort;
                $mail->SMTPSecure = ($smtpSecure === 'ssl') ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
                $mail->SMTPOptions = ['ssl' => ['verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true]];
                $fromEmail = !empty($_ENV['SMTP_FROM_EMAIL']) ? $_ENV['SMTP_FROM_EMAIL'] : $smtpUser;
                $fromName  = !empty($_ENV['SMTP_FROM_NAME']) ? trim($_ENV['SMTP_FROM_NAME'], "\"'") : 'MMWGH LINGKOD Portal';
                $mail->setFrom($fromEmail, $fromName);
            } else {
                $mail->isMail();
                $mail->setFrom('no-reply@mmwgh.gov.ph', 'MMWGH LINGKOD Portal');
            }

            $mail->addAddress($email, $fullName);
            $mail->isHTML(true);
            $mail->Subject = 'Your Lingkod Verification Code';
            $safeName = htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8');
            $safeOtp  = htmlspecialchars($otp, ENT_QUOTES, 'UTF-8');
            $mail->Body = "
                <div style='font-family:Inter,Arial,sans-serif;max-width:480px;margin:0 auto;padding:28px;border:1px solid #e5e7eb;border-radius:12px;background:#ffffff;'>
                  <p style='margin:0 0 6px;font-size:12px;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;color:#14532d;'>Lingkod Patient Portal</p>
                  <h2 style='margin:0 0 12px;color:#111827;font-size:20px;font-weight:700;'>Two-Step Verification</h2>
                  <p style='margin:0 0 20px;color:#4b5563;font-size:14px;line-height:1.6;'>Hello {$safeName}, enter this 6-digit code to continue signing in:</p>
                  <div style='font-family:monospace;font-size:28px;font-weight:700;letter-spacing:8px;color:#14532d;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:16px;text-align:center;margin:0 0 20px;'>{$safeOtp}</div>
                  <p style='margin:0;color:#6b7280;font-size:12px;line-height:1.5;'>This code expires in {$expiresMinutes} minutes. If you did not try to sign in, ignore this email.</p>
                </div>
            ";
            $mail->AltBody = "Hello {$fullName},\n\nYour Lingkod verification code is: {$otp}\nThis code expires in {$expiresMinutes} minutes.";
            @$mail->send();
            $sent = true;
        } catch (Exception $e) {
            error_log("OTP PHPMailer error: " . $e->getMessage());
        }
    }

    return $sent;
}

function flushJsonAndContinue(array $payload): void {
    ignore_user_abort(true);
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }
    $body = json_encode($payload);
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    header('Content-Type: application/json; charset=utf-8');
    header('Connection: close');
    header('Content-Length: ' . strlen($body));
    echo $body;
    flush();
    if (function_exists('fastcgi_finish_request')) {
        fastcgi_finish_request();
    }
}

function completePatientLogin(PDO $pdo, array $patient, bool $remember): void {
    $userId = null;
    $accountPatientId = '';
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

        try {
            $hasCol = (bool)$pdo->query("SHOW COLUMNS FROM users LIKE 'account_patient_id'")->fetch();
            if ($hasCol && $userId) {
                $apStmt = $pdo->prepare("SELECT account_patient_id FROM users WHERE id = ? LIMIT 1");
                $apStmt->execute([$userId]);
                $accountPatientId = trim((string)($apStmt->fetchColumn() ?: ''));
                if ($accountPatientId === '') {
                    $accountPatientId = sprintf('PT-%s-%04d', date('Y'), (int)$userId);
                    $pdo->prepare("UPDATE users SET account_patient_id = ? WHERE id = ? AND (account_patient_id IS NULL OR account_patient_id = '')")
                        ->execute([$accountPatientId, $userId]);
                }
            }
        } catch (Throwable $ignoreAcctCol) {}
    } catch (Throwable $ignoreUser) {}

    $sessionPatient = $patient;
    if ($accountPatientId !== '') {
        $sessionPatient['patient_id'] = $accountPatientId;
    }
    setPatientLoginSession($sessionPatient, $userId ? (int)$userId : null);

    try {
        if ($remember) {
            issuePatientRememberCookies($pdo, $patient);
        } else {
            clearPatientRememberCookies($pdo, true);
        }
    } catch (Throwable $ignoreRemember) {}
}
