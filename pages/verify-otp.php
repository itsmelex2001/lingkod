<?php
session_start();
require_once __DIR__ . '/../api/config/db.php';
require_once __DIR__ . '/../api/config/patient_otp.php';

$token = trim($_GET['token'] ?? '');
$maskedEmail = '';
$patientName = '';

try {
    ensurePatientOtpColumns($pdo);

    if ($token !== '') {
        $stmt = $pdo->prepare("
            SELECT id, patient_id, full_name, email, is_verified, token_expires_at, otp_hash, otp_expires_at
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

        $_SESSION['pending_otp_patient_id'] = (int)$patient['id'];
        $_SESSION['pending_otp_mode']       = 'verify_only';

        if (empty($patient['otp_hash']) || empty($patient['otp_expires_at']) || strtotime($patient['otp_expires_at']) < time()) {
            $otp = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $otpHash = password_hash($otp, PASSWORD_BCRYPT, ['cost' => 10]);
            $otpExpires = date('Y-m-d H:i:s', strtotime('+10 minutes'));
            $pdo->prepare("UPDATE patients SET otp_hash = ?, otp_expires_at = ?, otp_attempts = 0 WHERE id = ?")
                ->execute([$otpHash, $otpExpires, $patient['id']]);
            $_SESSION['pending_otp_sent_at'] = time();
            sendPatientOtpEmail($patient['email'], $patient['full_name'] ?: 'Patient', $otp);
        }

        $maskedEmail = maskEmail($patient['email']);
        $patientName = $patient['full_name'] ?? '';
    } elseif (!empty($_SESSION['pending_otp_patient_id'])) {
        $stmt = $pdo->prepare("SELECT id, full_name, email, is_verified FROM patients WHERE id = ? LIMIT 1");
        $stmt->execute([(int)$_SESSION['pending_otp_patient_id']]);
        $patient = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$patient) {
            unset($_SESSION['pending_otp_patient_id'], $_SESSION['pending_otp_mode'], $_SESSION['pending_otp_sent_at']);
            header('Location: /sdn/pages/patient-login.php');
            exit;
        }
        if ((int)$patient['is_verified'] === 1) {
            unset($_SESSION['pending_otp_patient_id'], $_SESSION['pending_otp_mode'], $_SESSION['pending_otp_sent_at']);
            header('Location: /sdn/pages/patient-login.php?verified=1');
            exit;
        }

        $maskedEmail = maskEmail($patient['email']);
        $patientName = $patient['full_name'] ?? '';
    } else {
        header('Location: /sdn/pages/patient-login.php');
        exit;
    }
} catch (Throwable $e) {
    error_log('verify-otp.php error: ' . $e->getMessage());
    header('Location: /sdn/pages/patient-login.php?verify_error=1');
    exit;
}
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Verify Account | MMWGH LINGKOD</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <style>
      *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
      body {
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        background: #f9fafb;
        color: #111827;
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1.5rem;
        -webkit-font-smoothing: antialiased;
      }
      .verify-card {
        width: 100%;
        max-width: 420px;
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 2rem;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
      }
      .verify-logo {
        display: block;
        width: 148px;
        height: auto;
        margin: 0 auto 1.25rem;
        object-fit: contain;
      }
      .verify-title {
        font-size: 1.5rem;
        font-weight: 600;
        color: #111827;
        letter-spacing: -0.025em;
        text-align: center;
        margin-bottom: 0.4rem;
      }
      .verify-sub {
        font-size: 0.875rem;
        color: #6b7280;
        text-align: center;
        line-height: 1.5;
        margin-bottom: 1.5rem;
      }
      .verify-sub strong {
        color: #111827;
        font-weight: 600;
      }
      .otp-label {
        display: block;
        font-size: 0.78rem;
        font-weight: 600;
        color: #374151;
        margin-bottom: 0.5rem;
      }
      .otp-input {
        width: 100%;
        height: 46px;
        border: none;
        border-radius: 8px;
        box-shadow: inset 0 0 0 1px #d1d5db;
        background: #ffffff;
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        font-size: 1.35rem;
        font-weight: 700;
        letter-spacing: 0.4em;
        text-align: center;
        color: #111827;
        outline: none;
        transition: box-shadow 0.15s ease;
      }
      .otp-input:focus {
        box-shadow: inset 0 0 0 2px #16a34a;
      }
      .otp-error {
        display: none;
        margin-top: 0.65rem;
        padding: 0.6rem 0.75rem;
        border-radius: 6px;
        background: #fef2f2;
        color: #b91c1c;
        box-shadow: inset 0 0 0 1px rgba(220, 38, 38, 0.2);
        font-size: 0.8125rem;
        line-height: 1.4;
      }
      .otp-status {
        display: none;
        margin-top: 0.65rem;
        padding: 0.6rem 0.75rem;
        border-radius: 6px;
        background: #f0fdf4;
        color: #15803d;
        box-shadow: inset 0 0 0 1px rgba(22, 163, 74, 0.2);
        font-size: 0.8125rem;
        line-height: 1.4;
      }
      .btn-verify {
        width: 100%;
        height: 42px;
        margin-top: 1.15rem;
        border: none;
        border-radius: 8px;
        background: #15803d;
        color: #ffffff;
        font-family: inherit;
        font-size: 0.875rem;
        font-weight: 500;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        transition: background-color 0.15s ease;
      }
      .btn-verify:hover:not(:disabled) { background: #166534; }
      .btn-verify:disabled { opacity: 0.65; cursor: not-allowed; }
      .verify-actions {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-top: 1rem;
        font-size: 0.875rem;
      }
      .link-btn {
        background: none;
        border: none;
        padding: 0;
        font-family: inherit;
        font-size: 0.875rem;
        font-weight: 500;
        color: #15803d;
        cursor: pointer;
        text-decoration: none;
      }
      .link-btn:hover:not(:disabled) { color: #166534; text-decoration: underline; }
      .link-btn:disabled { color: #9ca3af; cursor: not-allowed; text-decoration: none; }
      .link-muted {
        color: #6b7280;
        font-size: 0.875rem;
        font-weight: 500;
        text-decoration: none;
      }
      .link-muted:hover { color: #111827; text-decoration: underline; }
      .spinner {
        width: 15px;
        height: 15px;
        border: 2px solid rgba(255, 255, 255, 0.35);
        border-top-color: #ffffff;
        border-radius: 50%;
        animation: spin 0.7s linear infinite;
      }
      @keyframes spin { to { transform: rotate(360deg); } }
      .success-icon {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        background: #f0fdf4;
        color: #15803d;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1rem;
        box-shadow: inset 0 0 0 1px rgba(22, 163, 74, 0.2);
      }
      .btn-signin-link {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        height: 42px;
        margin-top: 1.25rem;
        border-radius: 8px;
        background: #15803d;
        color: #ffffff;
        font-size: 0.875rem;
        font-weight: 500;
        text-decoration: none;
        transition: background-color 0.15s ease;
      }
      .btn-signin-link:hover { background: #166534; }
    </style>
  </head>
  <body>
    <main class="verify-card">
      <img src="/sdn/public/assets/v2_lingkod_logo_cropped.png" alt="Lingkod" class="verify-logo" />

      <div id="otpFormContainer">
        <h1 class="verify-title">Verify Your Account</h1>
        <p class="verify-sub">
          Enter the 6-digit verification code sent to <strong><?= htmlspecialchars($maskedEmail) ?></strong>.
        </p>

        <form id="verifyOtpForm" novalidate onsubmit="submitOtpVerification(event)">
          <label for="otpCodeInput" class="otp-label">6-Digit Verification Code</label>
          <input
            type="text"
            id="otpCodeInput"
            class="otp-input"
            placeholder="000000"
            maxlength="6"
            inputmode="numeric"
            autocomplete="one-time-code"
            autofocus
            required
          />
          <div id="otpErrorBox" class="otp-error" role="alert"></div>
          <div id="otpStatusBox" class="otp-status" role="status"></div>

          <button type="submit" id="btnVerifySubmit" class="btn-verify">
            <span>Verify Account</span>
          </button>
        </form>

        <div class="verify-actions">
          <button type="button" id="btnResendCode" class="link-btn" onclick="resendCode()">Resend Code</button>
          <a href="/sdn/pages/patient-login.php" class="link-muted">Back to Sign In</a>
        </div>
      </div>

      <div id="otpSuccessContainer" style="display: none; text-align: center;">
        <div class="success-icon" aria-hidden="true">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="20 6 9 17 4 12"></polyline>
          </svg>
        </div>
        <h1 class="verify-title">Account Verified!</h1>
        <p class="verify-sub" style="margin-bottom: 0;">
          Your account has been verified. You may now sign in to the Patient Portal.
        </p>
        <a href="/sdn/pages/patient-login.php?verified=1" class="btn-signin-link">
          Proceed to Patient Portal Sign In
        </a>
      </div>
    </main>

    <script>
      const otpInput = document.getElementById('otpCodeInput');
      if (otpInput) {
        otpInput.addEventListener('input', () => {
          otpInput.value = otpInput.value.replace(/\D/g, '').slice(0, 6);
        });
      }

      async function submitOtpVerification(e) {
        e.preventDefault();
        const errBox = document.getElementById('otpErrorBox');
        const statusBox = document.getElementById('otpStatusBox');
        const btn = document.getElementById('btnVerifySubmit');
        const otp = (otpInput?.value || '').replace(/\D/g, '');

        errBox.style.display = 'none';
        statusBox.style.display = 'none';

        if (otp.length !== 6) {
          errBox.textContent = 'Please enter the 6-digit verification code.';
          errBox.style.display = 'block';
          otpInput?.focus();
          return;
        }

        btn.disabled = true;
        btn.innerHTML = '<span class="spinner" aria-hidden="true"></span><span>Verifying...</span>';

        try {
          const res = await fetch('/sdn/api/patient_verify_otp.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'verify', otp })
          });
          const data = await res.json();

          if (data.success) {
            if (data.verified_only) {
              document.getElementById('otpFormContainer').style.display = 'none';
              document.getElementById('otpSuccessContainer').style.display = 'block';
            } else {
              window.location.href = data.redirect || '/sdn/pages/patient-dashboard.php';
            }
          } else {
            errBox.textContent = data.message || 'Invalid verification code.';
            errBox.style.display = 'block';
            otpInput?.focus();
          }
        } catch (err) {
          errBox.textContent = 'Unable to verify code. Please try again.';
          errBox.style.display = 'block';
        } finally {
          btn.disabled = false;
          btn.innerHTML = '<span>Verify Account</span>';
        }
      }

      async function resendCode() {
        const errBox = document.getElementById('otpErrorBox');
        const statusBox = document.getElementById('otpStatusBox');
        const btn = document.getElementById('btnResendCode');

        errBox.style.display = 'none';
        statusBox.style.display = 'none';
        btn.disabled = true;

        try {
          const res = await fetch('/sdn/api/patient_verify_otp.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'resend' })
          });
          const data = await res.json();
          if (data.success) {
            statusBox.textContent = data.message || 'A new verification code was sent.';
            statusBox.style.display = 'block';
          } else {
            errBox.textContent = data.message || 'Could not resend code.';
            errBox.style.display = 'block';
          }
        } catch (err) {
          errBox.textContent = 'Network error while resending code.';
          errBox.style.display = 'block';
        } finally {
          let left = 30;
          btn.textContent = `Resend in ${left}s`;
          const timer = setInterval(() => {
            left--;
            if (left <= 0) {
              clearInterval(timer);
              btn.disabled = false;
              btn.textContent = 'Resend Code';
            } else {
              btn.textContent = `Resend in ${left}s`;
            }
          }, 1000);
        }
      }
    </script>
  </body>
</html>
