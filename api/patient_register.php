<?php
/**
 * api/patient_register.php
 * Handles New Patient Registration with 3-phase submission:
 * - Hashes password (PASSWORD_BCRYPT)
 * - Saves record with is_verified = 0
 * - Generates secure verification token (bin2hex(random_bytes(32)))
 * - Uses PHPMailer to send email with localhost verification link
 */

header('Content-Type: application/json; charset=utf-8');

// Enable error logging
ini_set('display_errors', 0);
ini_set('log_errors', 1);

require_once __DIR__ . '/config/cors.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/patient_otp.php';

// Load environment variables from .env
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

// Load Composer autoload for PHPMailer
$autoloadPath = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoloadPath)) {
    require_once $autoloadPath;
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

// Read JSON input or fallback to $_POST
$raw = file_get_contents('php://input');
$input = json_decode($raw, true);
if (!is_array($input)) {
    $input = $_POST;
}

// ── 1. Extract & Sanitize Personal Info (Phase 1) ──────────────────────────
$firstName     = trim($input['first_name'] ?? '');
$lastName      = trim($input['last_name'] ?? '');
$middleInitial = trim($input['middle_initial'] ?? '');
$gender        = trim($input['gender'] ?? '');
$birthdate     = trim($input['birthdate'] ?? '');
$age           = isset($input['age']) ? (int)$input['age'] : 0;
$contactNumber = trim($input['contact_number'] ?? '');
$guardianName          = trim($input['guardian_name'] ?? '');
$guardianContactNumber = trim($input['guardian_contact_number'] ?? '');
$email         = trim(strtolower($input['email'] ?? ''));
$address       = trim($input['address'] ?? '');
$bookingFor    = trim($input['booking_for'] ?? 'Myself');
$isDependent   = ($bookingFor === 'Someone Else / Dependent' || $bookingFor === 'someone_else' || !empty($input['booking_for_someone_else']));
$relationshipToPatient = trim($input['relationship_to_patient'] ?? '');
$withCompanion = $isDependent ? 1 : (!empty($input['with_companion']) && in_array(strtolower((string)$input['with_companion']), ['1', 'true', 'yes'], true) ? 1 : 0);

// ── 2. Extract & Sanitize Account Credentials (Phase 2) ───────────────────
$username        = trim((string)($input['username'] ?? ''));
$password        = (string)($input['password'] ?? '');
$confirmPassword = array_key_exists('confirm_password', $input) ? (string)$input['confirm_password'] : null;

// ── 3. Strict Validation ──────────────────────────────────────────────────
$errors = [];

if ($firstName === '') {
    $errors[] = 'First name is required.';
}
if ($lastName === '') {
    $errors[] = 'Last name is required.';
}

// Middle initial is optional
if ($middleInitial !== '' && strlen($middleInitial) > 5) {
    $errors[] = 'Middle initial is too long (maximum 5 characters).';
}

if ($gender === '' || !in_array($gender, ['Male', 'Female', 'Other'], true)) {
    $errors[] = 'Please select a valid gender.';
}

if ($birthdate === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $birthdate)) {
    $errors[] = 'A valid birthdate (YYYY-MM-DD) is required.';
} else {
    $bDate = new DateTime($birthdate);
    $today = new DateTime();
    if ($bDate > $today) {
        $errors[] = 'Birthdate cannot be in the future.';
    } else {
        $calculatedAge = $today->diff($bDate)->y;
        $age = $calculatedAge;

        // Minor / Guardian Validation: Minors (<18) must provide Guardian details
        if ($age < 18) {
            if ($guardianName === '') {
                $errors[] = 'Guardian name is required for patients under 18 years old.';
            }
            if ($guardianContactNumber === '') {
                $errors[] = 'Guardian contact number is required for patients under 18 years old.';
            }
        }
    }
}

if ($contactNumber === '') {
    $errors[] = 'Contact number is required.';
}

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'A valid email address is required.';
}

if ($address === '') {
    $errors[] = 'Home address is required.';
}

if ($isDependent && $relationshipToPatient === '') {
    $errors[] = 'Please select your relationship to the patient.';
}

if ($username === '') {
    $errors[] = 'Username is required.';
} elseif (!preg_match('/^[a-zA-Z0-9._-]{3,30}$/', $username)) {
    $errors[] = 'Username must be 3 to 30 characters and contain only letters, numbers, dots, underscores, or hyphens.';
}

if ($password === '') {
    $errors[] = 'Password is required.';
} elseif (strlen($password) < 8) {
    $errors[] = 'Password must be at least 8 characters.';
} elseif ($confirmPassword !== null && $password !== $confirmPassword) {
    $errors[] = 'Passwords do not match.';
}

if (!empty($errors)) {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'message' => implode(' ', $errors),
        'errors'  => $errors
    ]);
    exit;
}

try {
    // ── 4. Check for Existing Account ─────────────────────────────────────
    $checkStmt = $pdo->prepare("
        SELECT id, patient_id, email, username, contact_number, is_verified
        FROM patients
        WHERE LOWER(email) = :email
           OR (
                contact_number IS NOT NULL AND contact_number <> ''
                AND RIGHT(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(contact_number, '+', ''), ' ', ''), '-', ''), '(', ''), ')', ''), 10) = :phone
           )
    ");
    $phoneDigits = preg_replace('/\D+/', '', $contactNumber);
    $phoneTail = substr($phoneDigits, -10);
    $checkStmt->execute([':email' => $email, ':phone' => $phoneTail]);
    $matches = $checkStmt->fetchAll(PDO::FETCH_ASSOC);

    $emailHit = null;
    $phoneHit = null;
    foreach ($matches as $row) {
        $sameEmail = strtolower((string)$row['email']) === $email;
        $storedDigits = preg_replace('/\D+/', '', (string)($row['contact_number'] ?? ''));
        $samePhone = $phoneTail !== '' && substr($storedDigits, -10) === $phoneTail;
        if ($sameEmail && !$emailHit) $emailHit = $row;
        if ($samePhone && !$phoneHit) $phoneHit = $row;
    }

    $verifiedEmail = $emailHit && (int)$emailHit['is_verified'] === 1;
    $verifiedPhone = $phoneHit && (int)$phoneHit['is_verified'] === 1;
    if ($verifiedEmail || $verifiedPhone) {
        if ($verifiedEmail && $verifiedPhone) {
            $taken = 'This email and phone number are already registered.';
        } elseif ($verifiedEmail) {
            $taken = 'This email is already registered.';
        } else {
            $taken = 'This phone number is already registered.';
        }
        http_response_code(409);
        echo json_encode([
            'success' => false,
            'message' => $taken . ' An account with this information has already been created. Please sign in.',
            'code'    => 'ALREADY_EXISTS'
        ]);
        exit;
    }

    if ($phoneHit && (!$emailHit || (int)$phoneHit['id'] !== (int)$emailHit['id'])) {
        http_response_code(409);
        echo json_encode([
            'success' => false,
            'message' => 'This phone number is already used on an existing account. Please sign in or use a different number.',
            'code'    => 'ALREADY_EXISTS'
        ]);
        exit;
    }

    $existing = $emailHit;
    if ($existing) {
        $patientId = $existing['patient_id'];
        $existingId = $existing['id'];
    } else {
        // Generate formatted Patient ID: PT-YYYY-XXXXX
        $patientId = 'PT-' . date('Y') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));
        $existingId = null;
    }

    // ── 5. Verify Username Availability, Hash User Password & Generate Token ──
    $uCheck = $pdo->prepare("SELECT id FROM patients WHERE LOWER(username) = ? AND (? IS NULL OR id <> ?) LIMIT 1");
    $uCheck->execute([strtolower($username), $existingId, $existingId]);
    if ($uCheck->fetchColumn()) {
        http_response_code(409);
        echo json_encode([
            'success' => false,
            'message' => 'This username is already taken. Please choose a different username.',
            'code'    => 'USERNAME_TAKEN'
        ]);
        exit;
    }

    $passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 11]);
    $token = bin2hex(random_bytes(32)); // 64 hex characters cryptographically secure
    $tokenExpiry = date('Y-m-d H:i:s', strtotime('+24 hours'));

    ensurePatientOtpColumns($pdo);
    $otp = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $otpHash = password_hash($otp, PASSWORD_BCRYPT, ['cost' => 10]);
    $otpExpiry = date('Y-m-d H:i:s', strtotime('+30 minutes'));

    // Normalize full name
    $cleanMI = rtrim($middleInitial, '.');
    $fullName = $cleanMI !== '' ? trim("$firstName {$cleanMI}. $lastName") : trim("$firstName $lastName");

    $cleanGuardianName    = ($age < 18 && $guardianName !== '') ? $guardianName : null;
    $cleanGuardianContact = ($age < 18 && $guardianContactNumber !== '') ? $guardianContactNumber : null;

    if ($existingId) {
        $updateStmt = $pdo->prepare("
            UPDATE patients SET
                first_name              = :first_name,
                last_name               = :last_name,
                middle_initial          = :middle_initial,
                full_name               = :full_name,
                gender                  = :gender,
                birthdate               = :birthdate,
                age                     = :age,
                contact_number          = :contact_number,
                guardian_name           = :guardian_name,
                guardian_contact_number = :guardian_contact_number,
                email                   = :email,
                address                 = :address,
                with_companion          = :with_companion,
                username                = :username,
                password                = :password,
                is_verified             = 0,
                verification_token      = :token,
                token_expires_at        = :token_expires_at,
                otp_hash                = :otp_hash,
                otp_expires_at          = :otp_expires_at,
                otp_attempts            = 0,
                updated_at              = NOW()
            WHERE id = :id
        ");
        $updateStmt->execute([
            ':first_name'              => $firstName,
            ':last_name'               => $lastName,
            ':middle_initial'          => $cleanMI,
            ':full_name'               => $fullName,
            ':gender'                  => $gender,
            ':birthdate'               => $birthdate,
            ':age'                     => $age,
            ':contact_number'          => $contactNumber,
            ':guardian_name'           => $cleanGuardianName,
            ':guardian_contact_number' => $cleanGuardianContact,
            ':email'                   => $email,
            ':address'                 => $address,
            ':with_companion'          => $withCompanion,
            ':username'                => $username,
            ':password'                => $passwordHash,
            ':token'                   => $token,
            ':token_expires_at'        => $tokenExpiry,
            ':otp_hash'                => $otpHash,
            ':otp_expires_at'          => $otpExpiry,
            ':id'                      => $existingId
        ]);
        $recordId = $existingId;
    } else {
        $insertStmt = $pdo->prepare("
            INSERT INTO patients (
                patient_id, full_name, first_name, last_name, middle_initial,
                gender, birthdate, age, contact_number, guardian_name, guardian_contact_number,
                email, address, with_companion, relationship_to_patient, username, password, is_verified,
                verification_token, token_expires_at, otp_hash, otp_expires_at, otp_attempts, status, created_at, updated_at
            ) VALUES (
                :patient_id, :full_name, :first_name, :last_name, :middle_initial,
                :gender, :birthdate, :age, :contact_number, :guardian_name, :guardian_contact_number,
                :email, :address, :with_companion, :relationship_to_patient, :username, :password, 0,
                :token, :token_expires_at, :otp_hash, :otp_expires_at, 0, 'registered', NOW(), NOW()
            )
        ");
        $insertStmt->execute([
            ':patient_id'              => $patientId,
            ':full_name'               => $fullName,
            ':first_name'              => $firstName,
            ':last_name'               => $lastName,
            ':middle_initial'          => $cleanMI,
            ':gender'                  => $gender,
            ':birthdate'               => $birthdate,
            ':age'                     => $age,
            ':contact_number'          => $contactNumber,
            ':guardian_name'           => $cleanGuardianName,
            ':guardian_contact_number' => $cleanGuardianContact,
            ':email'                   => $email,
            ':address'                 => $address,
            ':with_companion'          => $withCompanion,
            ':relationship_to_patient' => $relationshipToPatient ?: null,
            ':username'                => $username,
            ':password'                => $passwordHash,
            ':token'                   => $token,
            ':token_expires_at'        => $tokenExpiry,
            ':otp_hash'                => $otpHash,
            ':otp_expires_at'          => $otpExpiry
        ]);
        $recordId = $pdo->lastInsertId();
    }

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['pending_otp_patient_id'] = (int)$recordId;
    $_SESSION['pending_otp_mode']       = 'verify_only';
    $_SESSION['pending_otp_sent_at']    = time();

    // ── 6. Construct Localhost Verification URL & Flush Fast Response ─────
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $verificationUrl = "{$protocol}://{$host}/sdn/pages/verify-otp.php?token={$token}";

    $domain = substr(strrchr($email, "@"), 1);
    $webmailUrl = 'https://mail.google.com';
    $providerName = 'Gmail';
    if ($domain === 'yahoo.com' || str_ends_with($domain, '.yahoo.com')) {
        $webmailUrl = 'https://mail.yahoo.com';
        $providerName = 'Yahoo Mail';
    } elseif (in_array($domain, ['outlook.com', 'hotmail.com', 'live.com'], true)) {
        $webmailUrl = 'https://outlook.live.com';
        $providerName = 'Outlook';
    }

    flushJsonAndContinue([
        'success'       => true,
        'message'       => "Registration submitted successfully! Please check your {$providerName} inbox to verify your account.",
        'patient_id'    => $patientId,
        'full_name'     => $fullName,
        'email'         => $email,
        'email_sent'    => true,
        'email_domain'  => $domain,
        'provider_name' => $providerName,
        'webmail_url'   => $webmailUrl
    ]);

    // ── 7. Send Verification Email via PHPMailer (After Response Flushed) ─
    $emailSent = false;
    $emailError = null;

    if (class_exists(PHPMailer::class)) {
        $mail = new PHPMailer(true);
        try {
            $smtpHost   = !empty($_ENV['SMTP_HOST']) ? $_ENV['SMTP_HOST'] : 'smtp.gmail.com';
            $smtpPort   = !empty($_ENV['SMTP_PORT']) ? (int)$_ENV['SMTP_PORT'] : 587;
            $smtpUser   = trim($_ENV['SMTP_USER'] ?? '');
            $smtpPass   = trim($_ENV['SMTP_PASS'] ?? '');
            $smtpSecure = strtolower(trim($_ENV['SMTP_SECURE'] ?? 'tls'));

            if (!empty($smtpUser) && !empty($smtpPass)) {
                $mail->isSMTP();
                $mail->Timeout    = 8;
                $mail->Host       = $smtpHost;
                $mail->SMTPAuth   = true;
                $mail->Username   = $smtpUser;
                $mail->Password   = $smtpPass;
                $mail->Port       = $smtpPort;
                if ($smtpSecure === 'ssl') {
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                } else {
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                }
                $mail->SMTPOptions = [
                    'ssl' => [
                        'verify_peer'       => false,
                        'verify_peer_name'  => false,
                        'allow_self_signed' => true
                    ]
                ];
                $fromEmail = !empty($_ENV['SMTP_FROM_EMAIL']) ? $_ENV['SMTP_FROM_EMAIL'] : $smtpUser;
                $fromName  = !empty($_ENV['SMTP_FROM_NAME']) ? trim($_ENV['SMTP_FROM_NAME'], "\"'") : 'MMWGH LINGKOD Portal';
                $mail->setFrom($fromEmail, $fromName);
            } else {
                // Fallback to PHP mail transport
                $mail->isMail();
                $mail->setFrom('no-reply@mmwgh.gov.ph', 'MMWGH LINGKOD Portal');
            }

            $mail->addAddress($email, $fullName);
            $mail->addReplyTo('support@mmwgh.gov.ph', 'MMWGH Support');

            $loginPortalUrl = "{$protocol}://{$host}/sdn/pages/patient-login.php";

            // Attach email-optimized logo via standard inline CID
            $logoPath = dirname(__DIR__) . '/public/assets/v3_lingkod_logo_email.png';
            if (is_file($logoPath)) {
                $mail->addEmbeddedImage($logoPath, 'lingkod_logo', 'lingkod.png');
            }

            $safeName = htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8');
            $safePatientId = htmlspecialchars($patientId, ENT_QUOTES, 'UTF-8');
            $safeEmail = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
            $safeUsername = htmlspecialchars($username, ENT_QUOTES, 'UTF-8');
            $year = date('Y');

            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Verify Your MMWGH LINGKOD Patient Portal Account';

            $mail->Body = "
            <!DOCTYPE html>
            <html>
            <head>
              <meta charset='utf-8'>
              <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            </head>
            <body style='margin:0; padding:28px 12px; background:#e8eeea; font-family:Arial, Helvetica, sans-serif; color:#1a1a1a;'>
              <table role='presentation' width='100%' cellpadding='0' cellspacing='0' style='max-width:600px; margin:0 auto; background:#ffffff; border-radius:20px; overflow:hidden; box-shadow:0 10px 30px rgba(0,0,0,0.08); border:1px solid #e5e7eb;'>
                <tr>
                  <td style='background:#1b8d3b; background-image:linear-gradient(90deg,#25ba50 0%,#1b8d3b 100%); padding:20px 28px;'>
                    <table role='presentation' cellpadding='0' cellspacing='0'>
                      <tr>
                        <td style='vertical-align:middle; padding-right:14px;'>
                          <img src='cid:lingkod_logo' alt='LINGKOD' width='54' height='54' style='display:block; border:0; width:54px; height:54px;'>
                        </td>
                        <td style='vertical-align:middle; color:#ffffff; font-family:Arial, Helvetica, sans-serif;'>
                          <div style='font-size:13px; font-weight:600; line-height:1.2; text-shadow:0 1px 2px rgba(0,0,0,0.2);'>Mariveles Mental Wellness and General Hospital</div>
                          <div style='font-size:18px; font-weight:800; letter-spacing:0.5px; margin-top:2px; text-shadow:0 1px 2px rgba(0,0,0,0.2);'>LINGKOD</div>
                        </td>
                      </tr>
                    </table>
                  </td>
                </tr>
                <tr>
                  <td style='padding:32px 36px 28px; background:#ffffff;'>
                    <div style='font-size:15px; font-weight:700; color:#15803d; margin:0 0 16px;'>{$safePatientId}</div>
                    <div style='font-size:20px; font-weight:700; color:#111827; margin:0 0 14px;'>Hello, {$safeName}!</div>
                    <p style='margin:0 0 14px; font-size:15px; line-height:1.55; color:#374151;'>Thank you for registering with the MMWGH LINGKOD Patient Portal. Use the verification code below to activate your account:</p>
                    <div style='background:#f9fafb; border:1px solid #e5e7eb; border-radius:10px; padding:16px 18px; margin:0 0 20px;'>
                      <div style='font-size:12px; color:#6b7280; text-transform:uppercase; font-weight:700;'>Username</div>
                      <div style='font-size:15px; color:#111827; font-weight:700; font-family:monospace; margin:2px 0 12px;'>{$safeUsername}</div>
                      <div style='font-size:12px; color:#6b7280; text-transform:uppercase; font-weight:700;'>6-Digit Verification OTP</div>
                      <div style='font-size:24px; color:#111827; font-weight:800; font-family:monospace; letter-spacing:5px; margin-top:4px;'>" . htmlspecialchars($otp, ENT_QUOTES, 'UTF-8') . "</div>
                    </div>
                    <p style='margin:0 0 24px; font-size:14px; line-height:1.55; color:#374151;'>Click the button below to open the verification page and enter your 6-digit OTP code before signing in with the username and password you created.</p>
                    <div style='text-align:center; margin:8px 0 24px;'>
                      <a href='{$verificationUrl}' target='_blank' style='display:inline-block; background:#2563eb; color:#ffffff; text-decoration:none; font-weight:700; font-size:15px; padding:14px 34px; border-radius:999px;'>Verify My Account</a>
                    </div>
                    <p style='margin:8px 0 20px; text-align:center; font-size:13px; line-height:1.5; font-style:italic; color:#6b7280;'><strong>Note:</strong> This verification link will expire in 24 hours. If you did not initiate this registration, please disregard this email.</p>
                    <p style='margin:0; text-align:center; font-size:12px; line-height:1.5; color:#9ca3af;'>&copy; {$year} Mariveles Mental Wellness and General Hospital. All Rights Reserved.<br>Mariveles, Bataan, Philippines.</p>
                  </td>
                </tr>
              </table>
            </body>
            </html>
            ";

            $mail->AltBody = "Hello {$fullName},\n\nThank you for registering with the MMWGH LINGKOD Patient Portal.\nPatient ID: {$patientId}\nUsername: {$username}\nEmail: {$email}\n6-Digit Verification OTP: {$otp}\n\nVerify your account: {$verificationUrl}\n\nAfter verifying, sign in with your chosen username and password: {$loginPortalUrl}";

            // Suppress unhandled socket warnings if local MTA is unavailable
            @$mail->send();
            $emailSent = true;
        } catch (Exception $e) {
            $emailError = $e->getMessage();
            error_log("PHPMailer error: " . $emailError);
        }
    } else {
        $emailError = 'PHPMailer class not available';
    }

    // Always log the generated verification link so localhost development and manual testing succeed reliably
    $logDir = __DIR__ . '/../logs';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0777, true);
    }
    $logEntry = sprintf(
        "[%s] Patient: %s (%s) | ID: %s | Verify URL: %s | Email Sent: %s\n",
        date('Y-m-d H:i:s'),
        $fullName,
        $email,
        $patientId,
        $verificationUrl,
        $emailSent ? 'YES' : 'NO (' . ($emailError ?? 'none') . ')'
    );
    @file_put_contents($logDir . '/sent_verification_emails.log', $logEntry, FILE_APPEND);
} catch (PDOException $e) {
    http_response_code(500);
    error_log("Registration database error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'A database error occurred while registering your account. Please try again later.'
    ]);
}

