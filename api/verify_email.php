<?php
/**
 * api/verify_email.php
 * Handles email verification links:
 * - Validates the token from the clicked link
 * - Checks expiration (24h)
 * - Updates the database record to is_verified = 1 and sets verified_at = NOW()
 * - Sends a "Verification Successful" notification email to the patient
 * - Renders a professional, centered Account Verified notification screen with button to proceed to Patient Portal
 */

session_start();
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

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

$token = trim($_GET['token'] ?? '');
$isJson = isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json');

$state = 'invalid';
$patient = null;
$message = '';

if ($token === '') {
    $state = 'invalid';
    $message = 'No verification token provided.';
} else {
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
            $state = 'invalid';
            $message = 'This verification link is invalid or has expired.';
        } elseif ((int)$patient['is_verified'] === 1) {
            $state = 'already_verified';
            $message = 'Your patient account has already been verified and is active!';
        } elseif (!empty($patient['token_expires_at']) && strtotime($patient['token_expires_at']) < time()) {
            $state = 'expired';
            $message = 'This verification link has expired (valid for 24 hours).';
        } else {
            // Valid token! Activate account
            $update = $pdo->prepare("
                UPDATE patients SET
                    is_verified = 1,
                    verified_at = IFNULL(verified_at, NOW()),
                    updated_at = NOW()
                WHERE id = :id
            ");
            $update->execute([':id' => $patient['id']]);

            $patient['is_verified'] = 1;

            if (function_exists('addLog')) {
                addLog($pdo, 'info', 'Patient Account Verified', $patient['email'], "Patient {$patient['patient_id']} ({$patient['full_name']}) verified their account via email token.");
            }

            $state = 'success';
            $message = 'Your account has been successfully verified! You may now sign in to the Patient Portal.';

            // Send "Account Verified Successfully" confirmation email to the patient
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $portalUrl = "{$protocol}://{$host}/sdn/pages/patient-login.php";

            $smtpUser = trim($_ENV['SMTP_USER'] ?? '');
            $smtpPass = trim($_ENV['SMTP_PASS'] ?? '');
            if (!empty($smtpUser) && !empty($smtpPass) && class_exists(PHPMailer::class)) {
                try {
                    $confirmMail = new PHPMailer(true);
                    $confirmMail->isSMTP();
                    $confirmMail->Host       = !empty($_ENV['SMTP_HOST']) ? $_ENV['SMTP_HOST'] : 'smtp.gmail.com';
                    $confirmMail->SMTPAuth   = true;
                    $confirmMail->Username   = $smtpUser;
                    $confirmMail->Password   = $smtpPass;
                    $confirmMail->Port       = !empty($_ENV['SMTP_PORT']) ? (int)$_ENV['SMTP_PORT'] : 587;
                    $confirmMail->SMTPSecure = (strtolower(trim($_ENV['SMTP_SECURE'] ?? 'tls')) === 'ssl') ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
                    $confirmMail->SMTPOptions = [
                        'ssl' => ['verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true]
                    ];
                    $fromEmail = !empty($_ENV['SMTP_FROM_EMAIL']) ? $_ENV['SMTP_FROM_EMAIL'] : $smtpUser;
                    $fromName  = !empty($_ENV['SMTP_FROM_NAME']) ? $_ENV['SMTP_FROM_NAME'] : 'MMWGH LINGKOD Portal';
                    $confirmMail->setFrom($fromEmail, $fromName);
                    $confirmMail->addAddress($patient['email'], $patient['full_name']);

                    // Attach email-optimized logo via standard inline CID
                    $logoPath = dirname(__DIR__) . '/public/assets/v3_lingkod_logo_email.png';
                    if (is_file($logoPath)) {
                        $confirmMail->addEmbeddedImage($logoPath, 'lingkod_logo', 'lingkod.png');
                    }

                    $confirmMail->isHTML(true);
                    $confirmMail->Subject = 'Account Verified - Welcome to MMWGH LINGKOD Patient Portal';
                    $confirmMail->Body = "
                    <!DOCTYPE html>
                    <html>
                    <head>
                      <meta charset='utf-8'>
                      <style>
                        body { font-family: 'Segoe UI', Arial, sans-serif; background-color: #f4f6f8; margin: 0; padding: 20px; color: #333; }
                        .card { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 8px 30px rgba(0,0,0,0.08); border: 1px solid #e5e7eb; }
                        .header { background: linear-gradient(90deg, #25ba50 0%, #1b8d3b 100%); padding: 20px 24px; color: #ffffff; }
                        .body { padding: 32px 28px; line-height: 1.6; }
                        .badge { display: inline-block; background: #ecfdf5; color: #047857; font-weight: 700; padding: 6px 14px; border-radius: 999px; margin-bottom: 16px; font-size: 13px; border: 1px solid #a7f3d0; }
                        .btn { display: inline-block; background: #15803d; color: #ffffff !important; text-decoration: none; padding: 14px 38px; border-radius: 9999px; font-weight: 700; font-size: 15px; margin: 20px 0; box-shadow: 0 4px 14px rgba(21,128,61,0.35); }
                        .footer { text-align: center; padding: 20px; font-size: 12px; color: #9ca3af; border-top: 1px solid #f3f4f6; }
                      </style>
                    </head>
                    <body>
                      <div class='card'>
                        <div class='header' style='display:flex; align-items:center; gap:12px;'>
                          <img src='cid:lingkod_logo' alt='LINGKOD' width='54' height='54' style='display:inline-block; border:0; width:54px; height:54px; vertical-align:middle; margin-right:12px;'>
                          <div>
                            <div style='font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; opacity: 0.95; line-height: 1.2;'>Mariveles Mental Wellness and General Hospital</div>
                            <div style='font-size: 20px; font-weight: 800; letter-spacing: 0.8px; margin-top: 4px; line-height: 1;'>LINGKOD</div>
                          </div>
                        </div>
                        <div class='body'>
                          <div class='badge'>Status: Verified (Active)</div>
                          <h2 style='color: #111827; margin-top: 0;'>Hello, " . htmlspecialchars($patient['full_name']) . "!</h2>
                          <p style='color: #4b5563; font-size: 15px;'>Your email address has been verified and your MMWGH LINGKOD Patient Account is now fully active.</p>
                          <p style='color: #4b5563; font-size: 15px;'>You can now access your patient portal to book hospital appointments and manage consultations:</p>
                          <div style='text-align: center;'>
                            <a href='{$portalUrl}' class='btn' target='_blank'>Sign In to Patient Portal</a>
                          </div>
                          <p style='font-size: 13px; color: #6b7280; margin-top: 20px;'>Patient ID: <strong>" . htmlspecialchars($patient['patient_id']) . "</strong></p>
                        </div>
                        <div class='footer'>
                          &copy; " . date('Y') . " Mariveles Mental Wellness and General Hospital. All rights reserved.
                        </div>
                      </div>
                    </body>
                    </html>
                    ";
                    $confirmMail->AltBody = "Hello {$patient['full_name']},\n\nYour MMWGH LINGKOD account is now verified! You can sign in at: {$portalUrl}\nPatient ID: {$patient['patient_id']}";
                    @$confirmMail->send();
                } catch (Exception $e) {
                    error_log("Verification confirmation email error: " . $e->getMessage());
                }
            }
        }
    } catch (PDOException $e) {
        error_log("Verification error: " . $e->getMessage());
        $state = 'error';
        $message = 'A server error occurred during verification. Please try again later.';
    }
}

if ($isJson) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => ($state === 'success' || $state === 'already_verified'),
        'state'   => $state,
        'message' => $message,
        'patient' => $patient ? [
            'patient_id' => $patient['patient_id'],
            'full_name'  => $patient['full_name'],
            'email'      => $patient['email']
        ] : null
    ]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Account Verification | MMWGH LINGKOD</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@500;600;700;800&display=swap" rel="stylesheet">
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }

    html, body {
      min-height: 100vh;
      width: 100%;
      background: #f4f6f8;
      font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
      color: #1f2937;
      display: flex;
      flex-direction: column;
    }

    .top-bar {
      height: 76px;
      width: 100%;
      background: linear-gradient(90deg, #25ba50 0%, #7ed957 50%, #047023 100%);
      padding: 0 2rem;
      display: flex;
      align-items: center;
      gap: 12px;
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.4);
      flex-shrink: 0;
      z-index: 100;
    }

    .top-bar-link {
      display: flex;
      align-items: center;
      gap: 12px;
      text-decoration: none;
      height: 100%;
    }

    .top-bar img {
      height: 64px;
      width: auto;
      object-fit: contain;
      flex-shrink: 0;
    }

    .top-title {
      display: flex;
      flex-direction: column;
      justify-content: center;
      text-align: left;
      gap: 2px;
      color: #ffffff;
    }

    .top-title .hosp {
      font-size: 0.92rem;
      font-weight: 700;
      color: #ffffff;
      line-height: 1.16;
      letter-spacing: -0.1px;
      white-space: nowrap;
    }

    .top-title .brand {
      font-family: 'Outfit', sans-serif;
      font-size: 1.25rem;
      font-weight: 800;
      color: #ffffff;
      letter-spacing: 0.5px;
      line-height: 1;
    }

    .content-wrap {
      flex: 1;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 2.5rem 1.25rem;
      width: 100%;
    }

    .card {
      background: #ffffff;
      max-width: 480px;
      width: 100%;
      margin: 0 auto;
      border-radius: 20px;
      padding: 2.75rem 2.25rem;
      box-shadow: 0 14px 40px rgba(0, 0, 0, 0.08);
      text-align: center;
      border: 1px solid #e5e7eb;
    }

    .icon-badge {
      width: 76px;
      height: 76px;
      border-radius: 50%;
      margin: 0 auto 1.35rem;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .icon-success {
      background: #ecfdf5;
      color: #059669;
      box-shadow: 0 0 0 8px rgba(16, 185, 129, 0.12);
    }

    .icon-error {
      background: #fef2f2;
      color: #dc2626;
      box-shadow: 0 0 0 8px rgba(239, 68, 68, 0.12);
    }

    h1 {
      font-family: 'Outfit', sans-serif;
      font-size: 1.65rem;
      font-weight: 700;
      color: #111827;
      margin-bottom: 0.6rem;
      letter-spacing: -0.02em;
    }

    p {
      color: #4b5563;
      font-size: 0.95rem;
      line-height: 1.55;
      margin-bottom: 1.5rem;
    }

    .info-box {
      background: #f9fafb;
      border: 1px solid #e5e7eb;
      border-radius: 12px;
      padding: 1rem 1.15rem;
      margin-bottom: 1.75rem;
      text-align: left;
      font-size: 0.88rem;
    }

    .info-row {
      display: flex;
      justify-content: space-between;
      padding: 0.4rem 0;
      border-bottom: 1px dashed #e5e7eb;
    }

    .info-row:last-child {
      border-bottom: none;
    }

    .info-row span:first-child {
      color: #6b7280;
      font-weight: 500;
    }

    .info-row span:last-child {
      color: #111827;
      font-weight: 600;
    }

    .btn-action-primary {
      display: inline-block;
      width: 100%;
      background: #15803d;
      color: #ffffff !important;
      text-decoration: none;
      font-weight: 600;
      font-size: 0.98rem;
      padding: 0.85rem 1.75rem;
      border-radius: 9999px;
      box-shadow: 0 4px 14px rgba(21, 128, 61, 0.28);
      transition: all 0.2s ease;
    }

    .btn-action-primary:hover {
      background: #166534;
      transform: translateY(-1px);
      box-shadow: 0 6px 18px rgba(21, 128, 61, 0.35);
    }

    .footer {
      text-align: center;
      padding: 1.25rem;
      font-size: 0.82rem;
      color: #6b7280;
    }

    @media (max-width: 600px) {
      .top-bar { height: 64px; padding: 0 1rem; }
      .top-bar img { height: 48px; }
      .top-title .hosp { font-size: 0.78rem; }
      .top-title .brand { font-size: 1.05rem; }
      .card { padding: 2rem 1.25rem; }
    }
  </style>
</head>
<body>

  <!-- Top Navigation Bar -->
  <header class="top-bar">
    <a href="/sdn/index.php" class="top-bar-link">
      <img src="/sdn/public/assets/v3_lingkod_logo.png" alt="LINGKOD Logo" />
      <div class="top-title">
        <div class="hosp">Mariveles Mental Wellness and General Hospital</div>
        <div class="brand">LINGKOD</div>
      </div>
    </a>
  </header>

  <!-- Centered Card Container -->
  <main class="content-wrap">
    <div class="card">

      <?php if ($state === 'success' || $state === 'already_verified'): ?>
        <div class="icon-badge icon-success">
          <svg width="38" height="38" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="20 6 9 17 4 12"></polyline>
          </svg>
        </div>

        <h1>Account Verified!</h1>
        <p><?= htmlspecialchars($message) ?></p>

        <?php if ($patient): ?>
          <div class="info-box">
            <div class="info-row">
              <span>Patient Name</span>
              <span><?= htmlspecialchars($patient['full_name']) ?></span>
            </div>
            <div class="info-row">
              <span>Patient ID</span>
              <span><?= htmlspecialchars($patient['patient_id']) ?></span>
            </div>
            <div class="info-row">
              <span>Email</span>
              <span><?= htmlspecialchars($patient['email']) ?></span>
            </div>
            <div class="info-row">
              <span>Account Status</span>
              <span style="color: #059669; font-weight: 700;">Verified (Active)</span>
            </div>
          </div>
        <?php endif; ?>

        <a href="/sdn/pages/patient-login.php" target="_blank" rel="noopener noreferrer" class="btn-action-primary">Proceed to Patient Portal Sign In</a>

      <?php else: ?>
        <div class="icon-badge icon-error">
          <svg width="38" height="38" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="15" y1="9" x2="9" y2="15"></line>
            <line x1="9" y1="9" x2="15" y2="15"></line>
          </svg>
        </div>

        <h1>Invalid Verification Link</h1>
        <p><?= htmlspecialchars($message) ?></p>

        <a href="/sdn/pages/patient-login.php" target="_blank" rel="noopener noreferrer" class="btn-action-primary">Go to Patient Portal Sign In</a>
      <?php endif; ?>

    </div>
  </main>

  <footer class="footer">
    &copy; <?= date('Y') ?> Mariveles Mental Wellness and General Hospital. All rights reserved.
  </footer>

</body>
</html>
