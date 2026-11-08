<?php
declare(strict_types=1);

/**
 * api/services/EmailService.php
 * Automated Email Notification Service for MMWGH LINGKOD.
 * 
 * CRITICAL PRIVACY RULE:
 * Because this is a mental wellness and general hospital, we strictly comply with data privacy laws.
 * NEVER include department name, doctor's name, chief complaint, or diagnosis in any notification.
 * Content is strictly logistical (Patient Name, Date, Time, Reference ID, Outpatient Triage instructions).
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class EmailService
{
    /**
     * Build configured PHPMailer instance using SMTP credentials
     */
    private static function getMailer(): PHPMailer
    {
        $mail = new PHPMailer(true);
        $mail->isSMTP();

        $envFile = __DIR__ . '/../../.env';
        if (file_exists($envFile)) {
            foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                $line = trim($line);
                if ($line === '' || str_starts_with($line, '#')) continue;
                [$k, $v] = array_map('trim', explode('=', $line, 2));
                if (!isset($_ENV[$k])) {
                    $_ENV[$k] = $v;
                    putenv("$k=$v");
                }
            }
        }

        $mail->Host       = $_ENV['SMTP_HOST'] ?? 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = $_ENV['SMTP_USER'] ?? '';
        $mail->Password   = $_ENV['SMTP_PASS'] ?? '';
        $secure           = strtolower($_ENV['SMTP_SECURE'] ?? 'tls');
        $mail->SMTPSecure = ($secure === 'ssl' || $secure === 'smtps')
            ? PHPMailer::ENCRYPTION_SMTPS
            : PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = (int)($_ENV['SMTP_PORT'] ?? 587);
        $mail->CharSet    = 'UTF-8';

        $fromEmail = $_ENV['SMTP_FROM_EMAIL'] ?? ($mail->Username ?: 'no-reply@mmwgh.gov.ph');
        $fromName  = trim($_ENV['SMTP_FROM_NAME'] ?? 'MMWGH LINGKOD Hospital Portal', '"');
        $mail->setFrom($fromEmail, $fromName);

        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer'       => false,
                'verify_peer_name'  => false,
                'allow_self_signed' => true,
            ],
        ];

        return $mail;
    }

    /**
     * Send logistical notification email
     *
     * @param string $toEmail Recipient email address
     * @param string $patientName Patient full name
     * @param string $date Formatted date (e.g. 'Nov 12, 2026')
     * @param string $time Formatted arrival time (e.g. '8:00 AM')
     * @param string $referenceId Appointment reference ID (e.g. 'PT-2026-085')
     * @param string $type Template variant ('confirmed' | '1day' | 'dayof')
     * @return bool True if sent successfully, false otherwise
     */
    public static function sendAppointmentEmail(
        string $toEmail,
        string $patientName,
        string $date,
        string $time,
        string $referenceId,
        string $type = 'confirmed'
    ): bool {
        if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            error_log("[EmailService] Invalid recipient email address: {$toEmail}");
            return false;
        }

        $config = match ($type) {
            '1day' => [
                'subject'  => 'Appointment Reminder for Tomorrow - MMWGH LINGKOD',
                'badge'    => 'SCHEDULED VISIT TOMORROW',
                'badge_bg' => '#fef3c7',
                'badge_fg' => '#b45309',
                'headline' => 'Reminder: Your Hospital Visit is Tomorrow',
                'intro'    => 'This is an automated reminder regarding your upcoming hospital appointment scheduled for tomorrow.',
            ],
            'dayof' => [
                'subject'  => 'Appointment Notice Today - MMWGH LINGKOD',
                'badge'    => 'VISIT NOTICE TODAY',
                'badge_bg' => '#fee2e2',
                'badge_fg' => '#b91c1c',
                'headline' => 'Notice: Your Hospital Visit is Scheduled Today',
                'intro'    => 'Your confirmed hospital visit is scheduled within the next 2 hours. Please prepare for your arrival.',
            ],
            default => [
                'subject'  => 'Appointment Confirmed - MMWGH LINGKOD',
                'badge'    => 'BOOKING CONFIRMED',
                'badge_bg' => '#dcfce7',
                'badge_fg' => '#15803d',
                'headline' => 'Your Hospital Appointment is Confirmed',
                'intro'    => 'Your appointment schedule has been reviewed and confirmed by hospital outpatient administration.',
            ],
        };

        $safeName = htmlspecialchars($patientName, ENT_QUOTES, 'UTF-8');
        $safeDate = htmlspecialchars($date, ENT_QUOTES, 'UTF-8');
        $safeTime = htmlspecialchars($time, ENT_QUOTES, 'UTF-8');
        $safeRef  = htmlspecialchars($referenceId, ENT_QUOTES, 'UTF-8');

        $baseUrl  = rtrim($_ENV['APP_URL'] ?? 'http://localhost/sdn', '/');
        $logoUrl  = $baseUrl . '/public/assets/v3_lingkod_logo_green.png';

        $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{$config['subject']}</title>
</head>
<body style="margin:0; padding:0; background-color:#f1f5f9; font-family:-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color:#0f172a;">
  <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color:#f1f5f9; padding:32px 12px;">
    <tr>
      <td align="center">
        <table role="presentation" width="100%" style="max-width:580px; background:#ffffff; border-radius:14px; overflow:hidden; border:1px solid #e2e8f0; box-shadow:0 6px 18px rgba(15,23,42,0.06);" cellspacing="0" cellpadding="0">
          
          <!-- Brand Header -->
          <tr>
            <td style="background-color:#15803d; padding:22px 28px;">
              <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                <tr>
                  <td style="vertical-align:middle;">
                    <img src="{$logoUrl}" alt="MMWGH LINGKOD" width="130" style="display:block; border:0; max-height:42px; width:auto; filter:brightness(0) invert(1);" />
                  </td>
                  <td align="right" style="vertical-align:middle; color:#dcfce7; font-size:12px; font-weight:700; letter-spacing:0.4px;">
                    MMWGH LINGKOD
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <!-- Main Content -->
          <tr>
            <td style="padding:28px 28px 24px 28px;">
              <span style="display:inline-block; background-color:{$config['badge_bg']}; color:{$config['badge_fg']}; font-size:11px; font-weight:800; letter-spacing:0.7px; padding:4px 10px; border-radius:999px; text-transform:uppercase; margin-bottom:12px;">
                {$config['badge']}
              </span>

              <h1 style="margin:0 0 10px 0; font-size:20px; font-weight:800; color:#0f172a; line-height:1.3;">
                {$config['headline']}
              </h1>

              <p style="margin:0 0 20px 0; font-size:14px; color:#475569; line-height:1.5;">
                Hello <strong>{$safeName}</strong>,<br>{$config['intro']}
              </p>

              <!-- Logistical Details Card -->
              <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; margin-bottom:22px;">
                <tr>
                  <td style="padding:12px 16px; border-bottom:1px solid #e2e8f0; width:42%; font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:0.5px;">
                    Reference ID
                  </td>
                  <td style="padding:12px 16px; border-bottom:1px solid #e2e8f0; font-size:14px; font-weight:800; color:#0f172a; font-family:Consolas, Monaco, monospace;">
                    {$safeRef}
                  </td>
                </tr>
                <tr>
                  <td style="padding:12px 16px; border-bottom:1px solid #e2e8f0; font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:0.5px;">
                    Scheduled Date
                  </td>
                  <td style="padding:12px 16px; border-bottom:1px solid #e2e8f0; font-size:14px; font-weight:700; color:#0f172a;">
                    {$safeDate}
                  </td>
                </tr>
                <tr>
                  <td style="padding:12px 16px; font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:0.5px;">
                    Clinic Arrival Time
                  </td>
                  <td style="padding:12px 16px; font-size:14px; font-weight:700; color:#0f172a;">
                    {$safeTime}
                  </td>
                </tr>
              </table>

              <!-- Outpatient Triage Instructions -->
              <div style="background:#eff6ff; border-left:4px solid #2563eb; padding:14px 16px; border-radius:6px; margin-bottom:22px;">
                <div style="font-size:12px; font-weight:800; color:#1e40af; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:4px;">
                  Arrival Instructions
                </div>
                <p style="margin:0; font-size:13px; color:#1e3a8a; line-height:1.5;">
                  Upon arrival at Mariveles Mental Wellness and General Hospital, please proceed directly to the <strong>Outpatient Triage Desk</strong>. Present your <strong>Reference ID: {$safeRef}</strong> and a valid ID to obtain your queue priority number.
                </p>
              </div>

              <!-- Confidentiality Notice -->
              <div style="background:#f8fafc; border:1px dashed #cbd5e1; padding:12px 14px; border-radius:6px;">
                <p style="margin:0; font-size:11px; color:#64748b; line-height:1.5;">
                  <strong>Data Privacy Notice:</strong> In compliance with healthcare data privacy laws, clinical details, assigned physicians, and medical consultation notes are kept confidential and are viewable only within your authenticated portal account. This is an automated message; please do not reply.
                </p>
              </div>

            </td>
          </tr>

          <!-- Footer -->
          <tr>
            <td style="background:#f8fafc; border-top:1px solid #e2e8f0; padding:16px 28px; text-align:center; font-size:11px; color:#94a3b8; line-height:1.5;">
              Mariveles Mental Wellness and General Hospital &bull; LINGKOD Patient Support<br>
              P. Monroe St., Brgy. Poblacion, Mariveles, Bataan, Philippines
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>
</body>
</html>
HTML;

        try {
            $mail = self::getMailer();
            $mail->addAddress($toEmail, $patientName);
            $mail->Subject = $config['subject'];
            $mail->isHTML(true);
            $mail->Body    = $html;
            $mail->AltBody = "MMWGH LINGKOD - {$config['headline']}\nPatient: {$patientName}\nDate: {$date}\nTime: {$time}\nReference ID: {$referenceId}\nPlease proceed to the Outpatient Triage Desk upon arrival.";

            $mail->send();
            return true;
        } catch (\Throwable $e) {
            error_log("[EmailService] Failed to send email to {$toEmail}: " . $e->getMessage());
            return false;
        }
    }
}

