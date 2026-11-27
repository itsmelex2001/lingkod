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
                if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) continue;
                [$k, $v] = array_map('trim', explode('=', $line, 2));
                if (getenv($k) === false && !isset($_ENV[$k])) {
                    $_ENV[$k] = $v;
                    putenv("$k=$v");
                }
            }
        }

        $envGet = static function (string $key, string $default = ''): string {
            $v = getenv($key);
            if ($v !== false && $v !== '') return trim($v, "\"' ");
            return isset($_ENV[$key]) && $_ENV[$key] !== '' ? trim((string)$_ENV[$key], "\"' ") : $default;
        };

        $mail->Host       = $envGet('SMTP_HOST', 'smtp.gmail.com');
        $mail->SMTPAuth   = true;
        $mail->Username   = $envGet('SMTP_USER', '');
        $mail->Password   = $envGet('SMTP_PASS', '');
        $secure           = strtolower($envGet('SMTP_SECURE', 'tls'));
        $mail->SMTPSecure = ($secure === 'ssl' || $secure === 'smtps')
            ? PHPMailer::ENCRYPTION_SMTPS
            : PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = (int)$envGet('SMTP_PORT', '587');
        $mail->Timeout    = 8;
        $mail->CharSet    = 'UTF-8';

        $fromEmail = $envGet('SMTP_FROM_EMAIL', $mail->Username ?: 'no-reply@mmwgh.gov.ph');
        $fromName  = $envGet('SMTP_FROM_NAME', 'MMWGH LINGKOD Hospital Portal');
        $mail->setFrom($fromEmail, $fromName);

        // Enforce strict TLS peer verification by default (opt-in SMTP_ALLOW_SELF_SIGNED=true only for local dev)
        if (strtolower($envGet('SMTP_ALLOW_SELF_SIGNED', 'false')) === 'true') {
            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer'       => false,
                    'verify_peer_name'  => false,
                    'allow_self_signed' => true,
                ],
            ];
        }

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
        string $type = 'confirmed',
        string $consultationMode = 'Face-to-Face'
    ): bool {
        if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            error_log("[EmailService] Invalid recipient email address: {$toEmail}");
            return false;
        }

        $isTeleconsult = strcasecmp(trim($consultationMode), 'Teleconsultation') === 0;

        $config = match ($type) {
            '1day' => [
                'subject'  => ($isTeleconsult ? 'Teleconsultation Reminder for Tomorrow' : 'Appointment Reminder for Tomorrow') . ' - MMWGH LINGKOD',
                'badge'    => $isTeleconsult ? 'TELECONSULTATION TOMORROW' : 'SCHEDULED VISIT TOMORROW',
                'badge_bg' => '#fef3c7',
                'badge_fg' => '#b45309',
                'headline' => $isTeleconsult ? 'Reminder: Your Teleconsultation is Tomorrow' : 'Reminder: Your Hospital Visit is Tomorrow',
                'intro'    => $isTeleconsult
                    ? 'This is an automated reminder regarding your upcoming online teleconsultation scheduled for tomorrow.'
                    : 'This is an automated reminder regarding your upcoming hospital appointment scheduled for tomorrow.',
            ],
            'dayof' => [
                'subject'  => ($isTeleconsult ? 'Teleconsultation Notice Today' : 'Appointment Notice Today') . ' - MMWGH LINGKOD',
                'badge'    => $isTeleconsult ? 'TELECONSULTATION TODAY' : 'VISIT NOTICE TODAY',
                'badge_bg' => '#fee2e2',
                'badge_fg' => '#b91c1c',
                'headline' => $isTeleconsult ? 'Notice: Your Teleconsultation is Scheduled Today' : 'Notice: Your Hospital Visit is Scheduled Today',
                'intro'    => $isTeleconsult
                    ? 'Your confirmed teleconsultation is scheduled soon. Please sign in to your Patient Portal for session access details.'
                    : 'Your confirmed hospital visit is scheduled within the next 2 hours. Please prepare for your arrival.',
            ],
            default => [
                'subject'  => ($isTeleconsult ? 'Teleconsultation Confirmed' : 'Appointment Confirmed') . ' - MMWGH LINGKOD',
                'badge'    => $isTeleconsult ? 'TELECONSULTATION CONFIRMED' : 'BOOKING CONFIRMED',
                'badge_bg' => '#dcfce7',
                'badge_fg' => '#15803d',
                'headline' => $isTeleconsult ? 'Your Teleconsultation is Confirmed' : 'Your Hospital Appointment is Confirmed',
                'intro'    => $isTeleconsult
                    ? 'Your teleconsultation schedule has been reviewed and confirmed by hospital outpatient administration.'
                    : 'Your appointment schedule has been reviewed and confirmed by hospital outpatient administration.',
            ],
        };

        $timeRowLabel = $isTeleconsult ? 'Teleconsultation Schedule' : 'Clinic Arrival Time';
        $instructionsHeading = $isTeleconsult ? 'Teleconsultation Instructions' : 'Arrival Instructions';
        $modeLabel = $isTeleconsult ? 'Teleconsultation (Online)' : 'Face-to-Face (On-Site)';
        $calloutBg     = $isTeleconsult ? '#eff6ff' : '#f0fdf4';
        $calloutBorder = $isTeleconsult ? '#bfdbfe' : '#bbf7d0';
        $calloutTitle  = $isTeleconsult ? '#1e40af' : '#166534';
        $calloutText   = $isTeleconsult ? '#1e3a8a' : '#14532d';

        $safeName = htmlspecialchars($patientName, ENT_QUOTES, 'UTF-8');
        $safeDate = htmlspecialchars($date, ENT_QUOTES, 'UTF-8');
        $safeTime = htmlspecialchars($time, ENT_QUOTES, 'UTF-8');
        $safeRef  = htmlspecialchars($referenceId, ENT_QUOTES, 'UTF-8');
        $instructionsBody = $isTeleconsult
            ? "This visit will be conducted remotely via <strong>Teleconsultation</strong>. Please sign in to your <strong>MMWGH Patient Portal</strong> using Reference ID <strong>{$safeRef}</strong> to view your meeting link or clinic call instructions, and keep your registered mobile number reachable during your scheduled block."
            : "Upon arrival at Mariveles Mental Wellness and General Hospital, please proceed directly to the <strong>Outpatient Triage Desk</strong>. Present your <strong>Reference ID: {$safeRef}</strong> and a valid ID to obtain your queue priority number.";

        $assetsDir      = realpath(__DIR__ . '/../../public/assets') ?: (__DIR__ . '/../../public/assets');
        $mmwghLogoPath  = $assetsDir . DIRECTORY_SEPARATOR . 'mmwgh_logo.png';
        $lingkodLogoPath = $assetsDir . DIRECTORY_SEPARATOR . 'v3_lingkod_logo_email.png';
        if (!file_exists($lingkodLogoPath)) {
            $lingkodLogoPath = $assetsDir . DIRECTORY_SEPARATOR . 'v3_lingkod_logo_green.png';
        }
        $hasMmwghLogo   = file_exists($mmwghLogoPath);
        $hasLingkodLogo = file_exists($lingkodLogoPath);

        $mmwghLogoHtml = $hasMmwghLogo
            ? '<td width="52" style="vertical-align:middle; padding-right:12px;"><img src="cid:mmwgh_logo" alt="MMWGH Official Seal" width="44" height="44" style="display:block; border:0; width:44px; height:44px; border-radius:50%;" /></td>'
            : '';
        $lingkodLogoHtml = $hasLingkodLogo
            ? '<td align="right" style="vertical-align:middle; padding-left:12px;"><img src="cid:lingkod_logo" alt="LINGKOD" height="30" style="display:block; border:0; height:30px; width:auto;" /></td>'
            : '<td align="right" style="vertical-align:middle; font-size:12px; font-weight:800; color:#15803d; letter-spacing:0.6px;">LINGKOD</td>';

        $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{$config['subject']}</title>
</head>
<body style="margin:0; padding:0; background-color:#f1f5f9; font-family:-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color:#0f172a; -webkit-font-smoothing:antialiased;">
  <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color:#f1f5f9; padding:28px 12px;">
    <tr>
      <td align="center">
        <table role="presentation" width="100%" style="max-width:600px; background-color:#ffffff; border-radius:12px; overflow:hidden; border:1px solid #e2e8f0; border-top:4px solid #15803d;" cellspacing="0" cellpadding="0">

          <!-- Clean White Branded Letterhead (100% visible logos in Gmail/Outlook) -->
          <tr>
            <td style="background-color:#ffffff; padding:20px 28px; border-bottom:1px solid #e2e8f0;">
              <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                <tr>
                  {$mmwghLogoHtml}
                  <td style="vertical-align:middle;">
                    <div style="font-size:14px; font-weight:800; color:#0f172a; line-height:1.25;">
                      Mariveles Mental Wellness and General Hospital
                    </div>
                    <div style="font-size:11.5px; font-weight:600; color:#15803d; margin-top:2px; letter-spacing:0.2px;">
                      LINGKOD Outpatient Portal &middot; Mariveles, Bataan
                    </div>
                  </td>
                  {$lingkodLogoHtml}
                </tr>
              </table>
            </td>
          </tr>

          <!-- Main Content -->
          <tr>
            <td style="padding:28px 28px 24px 28px;">
              <span style="display:inline-block; background-color:{$config['badge_bg']}; color:{$config['badge_fg']}; font-size:11px; font-weight:800; letter-spacing:0.7px; padding:5px 11px; border-radius:999px; text-transform:uppercase; margin-bottom:14px;">
                {$config['badge']}
              </span>

              <h1 style="margin:0 0 10px 0; font-size:21px; font-weight:800; color:#0f172a; line-height:1.3; letter-spacing:-0.2px;">
                {$config['headline']}
              </h1>

              <p style="margin:0 0 22px 0; font-size:14.5px; color:#334155; line-height:1.6;">
                Hello <strong style="color:#0f172a;">{$safeName}</strong>,<br>{$config['intro']}
              </p>

              <!-- Logistical Details Card -->
              <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; margin-bottom:22px;">
                <tr>
                  <td style="padding:13px 16px; border-bottom:1px solid #e2e8f0; width:44%; font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:0.5px;">
                    Reference ID
                  </td>
                  <td style="padding:13px 16px; border-bottom:1px solid #e2e8f0; font-size:14.5px; font-weight:800; color:#15803d; font-family:Consolas, Monaco, 'Courier New', monospace;">
                    {$safeRef}
                  </td>
                </tr>
                <tr>
                  <td style="padding:13px 16px; border-bottom:1px solid #e2e8f0; font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:0.5px;">
                    Scheduled Date
                  </td>
                  <td style="padding:13px 16px; border-bottom:1px solid #e2e8f0; font-size:14.5px; font-weight:700; color:#0f172a;">
                    {$safeDate}
                  </td>
                </tr>
                <tr>
                  <td style="padding:13px 16px; border-bottom:1px solid #e2e8f0; font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:0.5px;">
                    {$timeRowLabel}
                  </td>
                  <td style="padding:13px 16px; border-bottom:1px solid #e2e8f0; font-size:14.5px; font-weight:700; color:#0f172a;">
                    {$safeTime}
                  </td>
                </tr>
                <tr>
                  <td style="padding:13px 16px; font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:0.5px;">
                    Consultation Mode
                  </td>
                  <td style="padding:13px 16px; font-size:14px; font-weight:600; color:#0f172a;">
                    {$modeLabel}
                  </td>
                </tr>
              </table>

              <!-- Consultation / Arrival Instructions -->
              <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color:{$calloutBg}; border:1px solid {$calloutBorder}; border-radius:10px; margin-bottom:20px;">
                <tr>
                  <td style="padding:14px 16px;">
                    <div style="font-size:11.5px; font-weight:800; color:{$calloutTitle}; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:5px;">
                      {$instructionsHeading}
                    </div>
                    <p style="margin:0; font-size:13.5px; color:{$calloutText}; line-height:1.55;">
                      {$instructionsBody}
                    </p>
                  </td>
                </tr>
              </table>

              <!-- Confidentiality Notice -->
              <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color:#f8fafc; border:1px solid #e2e8f0; border-radius:8px;">
                <tr>
                  <td style="padding:12px 14px;">
                    <p style="margin:0; font-size:11.5px; color:#64748b; line-height:1.55;">
                      <strong style="color:#475569;">Data Privacy Notice (R.A. 10173):</strong> Clinical details, department assignments, attending physicians, and consultation notes are kept strictly confidential and are viewable only inside your authenticated Patient Portal. This is an automated message; please do not reply.
                    </p>
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <!-- Footer -->
          <tr>
            <td style="background-color:#f8fafc; border-top:1px solid #e2e8f0; padding:16px 28px; text-align:center; font-size:11.5px; color:#64748b; line-height:1.6;">
              <strong style="color:#334155;">Mariveles Mental Wellness and General Hospital</strong><br>
              P. Monroe St., Brgy. Poblacion, Mariveles, Bataan &middot; OPU-Medical: (047) 935-4645
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>
</body>
</html>
HTML;

        $altInstructions = $isTeleconsult
            ? "Sign in to your MMWGH Patient Portal using Reference ID {$referenceId} to access your teleconsultation link or clinic call instructions."
            : "Please proceed to the Outpatient Triage Desk upon arrival with Reference ID {$referenceId} and a valid ID.";

        try {
            $mail = self::getMailer();
            if ($hasMmwghLogo) {
                $mail->addEmbeddedImage($mmwghLogoPath, 'mmwgh_logo', 'mmwgh_logo.png');
            }
            if ($hasLingkodLogo) {
                $mail->addEmbeddedImage($lingkodLogoPath, 'lingkod_logo', basename($lingkodLogoPath));
            }
            $mail->addAddress($toEmail, $patientName);
            $mail->Subject = $config['subject'];
            $mail->isHTML(true);
            $mail->Body    = $html;
            $mail->AltBody = "MMWGH LINGKOD - {$config['headline']}\nPatient: {$patientName}\nReference ID: {$referenceId}\nDate: {$date}\n{$timeRowLabel}: {$time}\nMode: {$modeLabel}\n{$altInstructions}";

            $mail->send();
            return true;
        } catch (\Throwable $e) {
            error_log("[EmailService] Failed to send email to {$toEmail}: " . $e->getMessage());
            return false;
        }
    }
}

