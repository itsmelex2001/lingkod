<?php
/**
 * api/patient_forgot_password.php
 * Handles Forgot Password requests for the Patient Portal:
 * - Validates email format
 * - Looks up patient in `patients` table
 * - Generates secure reset token & 1-hour expiration
 * - Stores reset_token in database
 * - Sends a basic plain text email to the patient's Gmail with the password reset link
 */

session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/config/cors.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/patient_otp.php';
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

$email = trim($input['email'] ?? '');

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'message' => 'Please enter a valid email address.'
    ]);
    exit;
}

try {
    // 1. Look up patient by email
    $stmt = $pdo->prepare("
        SELECT id, patient_id, full_name, email, is_verified 
        FROM patients 
        WHERE email = :email 
        LIMIT 1
    ");
    $stmt->execute([':email' => $email]);
    $patient = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$patient) {
        // Return clear feedback to patient
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'No patient account was found registered with this email address.'
        ]);
        exit;
    }

    // 2. Generate secure reset token (64 hex characters) and 1 hour expiration
    $token = bin2hex(random_bytes(32));
    $expiresAt = date('Y-m-d H:i:s', time() + 3600); // 1 hour

    // 3. Store in database
    $updateStmt = $pdo->prepare("
        UPDATE patients 
        SET reset_token = :token, 
            reset_token_expires_at = :expires,
            updated_at = NOW() 
        WHERE id = :id
    ");
    $updateStmt->execute([
        ':token'   => $token,
        ':expires' => $expiresAt,
        ':id'      => $patient['id']
    ]);

    // 4. Construct reset URL
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $resetUrl = "{$protocol}://{$host}/sdn/pages/patient-reset-password.php?token=" . urlencode($token);

    // 5. Build basic plain text email
    $patientName = !empty($patient['full_name']) ? $patient['full_name'] : 'Patient';

    $subject = 'MMWGH Patient Portal - Password Reset';

    $textBody = "Hello {$patientName},\r\n\r\n"
              . "We received a request to reset the password for your MMWGH Patient Portal account ({$patient['email']}).\r\n\r\n"
              . "To choose a new password, click the link below (or copy and paste it into your browser):\r\n"
              . "{$resetUrl}\r\n\r\n"
              . "Note: This password reset link will expire in 1 hour.\r\n\r\n"
              . "If you did not request a password reset, you can safely ignore this email. Your current password will remain unchanged.\r\n\r\n"
              . "Thank you,\r\n"
              . "Mariveles Mental Wellness and General Hospital\r\n"
              . "LINGKOD Patient Portal Support";

    flushJsonAndContinue([
        'success'   => true,
        'message'   => 'A password reset link has been sent to your email address (' . htmlspecialchars($patient['email']) . '). Please check your Gmail inbox or spam folder.',
        'emailSent' => true
    ]);

    // 6. Send email via PHPMailer / Gmail SMTP (After Response Flushed)
    $smtpUser = trim($_ENV['SMTP_USER'] ?? '');
    $smtpPass = trim($_ENV['SMTP_PASS'] ?? '');

    if (!empty($smtpUser) && !empty($smtpPass) && class_exists(PHPMailer::class)) {
        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Timeout    = 8;
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

            // Basic Plain Text email as requested
            $mail->isHTML(false);
            $mail->Subject = $subject;
            $mail->Body    = $textBody;

            $mail->send();
            $mailSent = true;
        } catch (Exception $e) {
            $mailError = $e->getMessage();
            error_log("Patient forgot password email error: " . $mailError);
        }
    } else {
        // Fallback to PHP native mail()
        $headers  = "From: no-reply@mmwgh.gov.ph\r\n"
                  . "Reply-To: no-reply@mmwgh.gov.ph\r\n"
                  . "Content-Type: text/plain; charset=UTF-8\r\n";
        @mail($patient['email'], $subject, $textBody, $headers);
    }

} catch (PDOException $e) {
    http_response_code(500);
    error_log("Forgot password DB error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'A database error occurred. Please try again later.']);
}

