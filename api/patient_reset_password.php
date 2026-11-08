<?php
/**
 * api/patient_reset_password.php
 * Handles validating tokens and resetting patient passwords:
 * - GET: Checks if a reset_token is valid and not expired
 * - POST: Validates new password & updates `patients.password` with a secure hash
 * - Clears the reset_token after successful password change
 * - Sends a basic plain text confirmation email to the patient
 */

session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/config/cors.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Load environment variables from .env if present
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($key, $val) = explode('=', $line, 2);
            $key = trim($key);
            $val = trim($val, " \t\n\r\0\x0B\"'");
            if (!array_key_exists($key, $_ENV)) {
                $_ENV[$key] = $val;
            }
        }
    }
}

// ── GET: Check token validity ───────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $token = trim($_GET['token'] ?? '');
    if ($token === '') {
        http_response_code(400);
        echo json_encode(['valid' => false, 'message' => 'No reset token provided.']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("
            SELECT id, patient_id, full_name, email, reset_token_expires_at
            FROM patients
            WHERE reset_token = :token
            LIMIT 1
        ");
        $stmt->execute([':token' => $token]);
        $patient = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$patient) {
            http_response_code(404);
            echo json_encode(['valid' => false, 'message' => 'This password reset link is invalid or has already been used.']);
            exit;
        }

        if (!empty($patient['reset_token_expires_at']) && strtotime($patient['reset_token_expires_at']) < time()) {
            http_response_code(410);
            echo json_encode(['valid' => false, 'message' => 'This password reset link has expired. Please request a new one.']);
            exit;
        }

        echo json_encode([
            'valid'        => true,
            'message'      => 'Token is valid.',
            'patient_name' => $patient['full_name'],
            'email'        => $patient['email']
        ]);
        exit;

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['valid' => false, 'message' => 'Database error.']);
        exit;
    }
}

// ── POST: Execute password change ───────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw = file_get_contents('php://input');
    $input = json_decode($raw, true);
    if (!is_array($input)) {
        $input = $_POST;
    }

    $token           = trim($input['token'] ?? '');
    $newPassword     = (string)($input['new_password'] ?? '');
    $confirmPassword = (string)($input['confirm_password'] ?? '');

    if ($token === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing password reset token.']);
        exit;
    }

    if (strlen($newPassword) < 8) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'New password must be at least 8 characters long.']);
        exit;
    }

    if ($newPassword !== $confirmPassword) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Password confirmation does not match.']);
        exit;
    }

    try {
        // Find patient by token
        $stmt = $pdo->prepare("
            SELECT id, patient_id, full_name, email, reset_token_expires_at
            FROM patients
            WHERE reset_token = :token
            LIMIT 1
        ");
        $stmt->execute([':token' => $token]);
        $patient = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$patient) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'This password reset link is invalid or has already been used.']);
            exit;
        }

        if (!empty($patient['reset_token_expires_at']) && strtotime($patient['reset_token_expires_at']) < time()) {
            http_response_code(410);
            echo json_encode(['success' => false, 'message' => 'This password reset link has expired. Please request a new one.']);
            exit;
        }

        // Hash new password securely
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

        // Update database: set new password, clear reset_token
        $updateStmt = $pdo->prepare("
            UPDATE patients
            SET password = :password,
                reset_token = NULL,
                reset_token_expires_at = NULL,
                updated_at = NOW()
            WHERE id = :id
        ");
        $updateStmt->execute([
            ':password' => $hashedPassword,
            ':id'       => $patient['id']
        ]);

        // Send basic text confirmation email
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $loginUrl = "{$protocol}://{$host}/sdn/pages/patient-login.php";

        $patientName = !empty($patient['full_name']) ? $patient['full_name'] : 'Patient';
        $subject = 'MMWGH Patient Portal - Password Changed Successfully';
        $textBody = "Hello {$patientName},\r\n\r\n"
                  . "Your MMWGH Patient Portal account password has been successfully updated.\r\n\r\n"
                  . "You can now sign in to your account with your new password:\r\n"
                  . "{$loginUrl}\r\n\r\n"
                  . "If you did not perform this password change, please contact hospital support immediately at info@mmwgh.gov.ph.\r\n\r\n"
                  . "Thank you,\r\n"
                  . "Mariveles Mental Wellness and General Hospital\r\n"
                  . "LINGKOD Patient Portal";

        $smtpUser = trim($_ENV['SMTP_USER'] ?? '');
        $smtpPass = trim($_ENV['SMTP_PASS'] ?? '');
        if (!empty($smtpUser) && !empty($smtpPass) && class_exists(PHPMailer::class)) {
            try {
                $mail = new PHPMailer(true);
                $mail->isSMTP();
                $mail->Host       = !empty($_ENV['SMTP_HOST']) ? $_ENV['SMTP_HOST'] : 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = $smtpUser;
                $mail->Password   = $smtpPass;
                $mail->SMTPSecure = !empty($_ENV['SMTP_SECURE']) ? $_ENV['SMTP_SECURE'] : PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = !empty($_ENV['SMTP_PORT']) ? (int)$_ENV['SMTP_PORT'] : 587;
                $mail->CharSet    = 'UTF-8';

                $fromEmail = !empty($_ENV['SMTP_FROM_EMAIL']) ? $_ENV['SMTP_FROM_EMAIL'] : $smtpUser;
                $fromName  = !empty($_ENV['SMTP_FROM_NAME']) ? trim($_ENV['SMTP_FROM_NAME'], '"') : 'MMWGH LINGKOD Portal';
                $mail->setFrom($fromEmail, $fromName);
                $mail->addAddress($patient['email'], $patientName);

                $mail->isHTML(false);
                $mail->Subject = $subject;
                $mail->Body    = $textBody;
                $mail->send();
            } catch (Exception $e) {
                error_log("Password reset confirmation email error: " . $e->getMessage());
            }
        }

        echo json_encode([
            'success'  => true,
            'message'  => 'Your password has been successfully updated! You can now sign in.',
            'redirect' => '/sdn/pages/patient-login.php'
        ]);
        exit;

    } catch (PDOException $e) {
        http_response_code(500);
        error_log("Reset password DB error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'A database error occurred.']);
        exit;
    }
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method not allowed.']);

