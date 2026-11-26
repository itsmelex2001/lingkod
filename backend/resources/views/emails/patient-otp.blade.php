<!DOCTYPE html>
<html lang="en">
<body style="margin:0;padding:32px 16px;background:#f4f7f5;font-family:Inter,Arial,sans-serif;color:#111827;">
  <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:480px;margin:0 auto;background:#ffffff;border:1px solid #e5e7eb;border-radius:12px;padding:32px;">
    <tr>
      <td>
        <p style="margin:0 0 8px;font-size:13px;font-weight:600;letter-spacing:0.06em;text-transform:uppercase;color:#14532d;">Lingkod Patient Portal</p>
        <h1 style="margin:0 0 12px;font-size:20px;font-weight:600;color:#111827;">Two-Step Verification</h1>
        <p style="margin:0 0 24px;font-size:14px;line-height:1.6;color:#4b5563;">
          Hello {{ $patientName }}, enter the code below to finish signing in. It expires in {{ $expiresInMinutes }} minutes.
        </p>
        <div style="padding:16px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;text-align:center;">
          <span style="font-family:monospace;font-size:28px;font-weight:700;letter-spacing:0.3em;color:#14532d;">{{ $otp }}</span>
        </div>
        <p style="margin:24px 0 0;font-size:12px;line-height:1.5;color:#6b7280;">
          If you did not try to sign in, ignore this email and change your password.
        </p>
      </td>
    </tr>
  </table>
</body>
</html>
