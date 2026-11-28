<?php
session_start();
require_once __DIR__ . '/../api/config/db.php';
require_once __DIR__ . '/../api/config/patient_otp.php';

$token = trim($_GET['token'] ?? '');
$maskedEmail = '';
$patientName = '';
$autoResentOtp = false;

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
            $otpExpires = date('Y-m-d H:i:s', strtotime('+30 minutes'));
            $pdo->prepare("UPDATE patients SET otp_hash = ?, otp_expires_at = ?, otp_attempts = 0 WHERE id = ?")
                ->execute([$otpHash, $otpExpires, $patient['id']]);
            $_SESSION['pending_otp_sent_at'] = time();
            sendPatientOtpEmail($patient['email'], $patient['full_name'] ?: 'Patient', $otp);
            $autoResentOtp = true;
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
  <link rel="icon" type="image/png" href="/sdn/public/assets/mmwgh_logo.png" />
  <link rel="apple-touch-icon" href="/sdn/public/assets/mmwgh_logo.png" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <style>
      :root {
        --bg-page: #f1f5f9;
        --surface: #ffffff;
        --border: #d1d5db;
        --border-input: #cbd5e1;
        --text-primary: #0f172a;
        --text-secondary: #475569;
        --text-muted: #64748b;
        --brand: #15803d;
        --brand-hover: #166534;
        --brand-ring: rgba(21, 128, 61, 0.16);
        --danger: #dc2626;
        --danger-soft: #fef2f2;
      }
      *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
      body {
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        background: var(--bg-page);
        color: var(--text-primary);
        min-height: 100dvh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1.5rem 1rem;
        -webkit-font-smoothing: antialiased;
        caret-color: transparent;
        user-select: none;
      }
      .verify-card {
        width: 100%;
        max-width: 420px;
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 12px;
        padding: 2rem;
        box-shadow: 0 4px 6px -1px rgba(15, 23, 42, 0.06), 0 12px 28px -8px rgba(15, 23, 42, 0.1);
      }
      .verify-logo {
        display: block;
        width: 140px;
        height: auto;
        margin: 0 auto 1.25rem;
        object-fit: contain;
      }
      .verify-title {
        font-size: 1.375rem;
        font-weight: 700;
        color: var(--text-primary);
        letter-spacing: -0.02em;
        text-align: center;
        margin-bottom: 0.4rem;
      }
      .verify-sub {
        font-size: 0.875rem;
        color: var(--text-secondary);
        text-align: center;
        line-height: 1.5;
        margin-bottom: 1.5rem;
      }
      .verify-sub strong {
        color: var(--text-primary);
        font-weight: 600;
        overflow-wrap: anywhere;
      }
      .otp-label {
        display: block;
        font-size: 0.8125rem;
        font-weight: 600;
        color: #334155;
        margin-bottom: 0.5rem;
      }
      .otp-boxes {
        display: grid;
        grid-template-columns: repeat(6, minmax(0, 1fr));
        gap: 8px;
      }
      .otp-digit {
        width: 100%;
        height: 52px;
        border: 1px solid var(--border-input);
        border-radius: 8px;
        background: var(--surface);
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        font-variant-numeric: tabular-nums;
        font-size: 1.4rem;
        font-weight: 700;
        text-align: center;
        color: var(--text-primary);
        outline: none;
        caret-color: transparent;
        user-select: text;
        transition: border-color 0.15s ease, box-shadow 0.15s ease;
      }
      .otp-digit:hover {
        border-color: #94a3b8;
      }
      .otp-digit:focus {
        border-color: var(--brand);
        box-shadow: 0 0 0 3px var(--brand-ring);
      }
      .otp-digit.filled {
        border-color: #94a3b8;
      }
      .otp-boxes.has-error .otp-digit {
        border-color: var(--danger);
        background: var(--danger-soft);
      }
      .otp-boxes.has-error .otp-digit:focus {
        box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.14);
      }
      .otp-error {
        display: none;
        margin-top: 0.75rem;
        padding: 0.6rem 0.75rem;
        border-radius: 8px;
        background: var(--danger-soft);
        color: #b91c1c;
        border: 1px solid #fecaca;
        font-size: 0.8125rem;
        line-height: 1.4;
      }
      .otp-status {
        display: none;
        margin-top: 0.75rem;
        padding: 0.6rem 0.75rem;
        border-radius: 8px;
        background: #f0fdf4;
        color: var(--brand);
        border: 1px solid #bbf7d0;
        font-size: 0.8125rem;
        line-height: 1.4;
      }
      .btn-verify {
        width: 100%;
        height: 44px;
        margin-top: 1.25rem;
        border: none;
        border-radius: 8px;
        background: var(--brand);
        color: #ffffff;
        font-family: inherit;
        font-size: 0.875rem;
        font-weight: 600;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        transition: background-color 0.15s ease;
      }
      .btn-verify:hover:not(:disabled) { background: var(--brand-hover); }
      .btn-verify:focus-visible { outline: 2px solid var(--brand); outline-offset: 2px; }
      .btn-verify:disabled { opacity: 0.65; cursor: not-allowed; }
      .verify-actions {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-top: 1.15rem;
        font-size: 0.875rem;
      }
      .link-btn {
        background: none;
        border: none;
        padding: 4px 0;
        font-family: inherit;
        font-size: 0.875rem;
        font-weight: 500;
        color: var(--brand);
        cursor: pointer;
        text-decoration: none;
      }
      .link-btn:hover:not(:disabled) { color: var(--brand-hover); text-decoration: underline; }
      .link-btn:disabled { color: #94a3b8; cursor: not-allowed; text-decoration: none; }
      .link-muted {
        color: var(--text-muted);
        font-size: 0.875rem;
        font-weight: 500;
        text-decoration: none;
        padding: 4px 0;
      }
      .link-muted:hover { color: var(--text-primary); text-decoration: underline; }
      .spinner {
        width: 15px;
        height: 15px;
        border: 2px solid rgba(255, 255, 255, 0.35);
        border-top-color: #ffffff;
        border-radius: 50%;
        animation: spin 0.7s linear infinite;
      }
      @keyframes spin { to { transform: rotate(360deg); } }
      @keyframes shake {
        0%, 100% { transform: translateX(0); }
        25% { transform: translateX(-4px); }
        50% { transform: translateX(4px); }
        75% { transform: translateX(-2px); }
      }
      .otp-boxes.shake { animation: shake 0.25s ease; }
      @media (prefers-reduced-motion: reduce) {
        .otp-digit, .btn-verify { transition: none; }
        .otp-boxes.shake { animation: none; }
      }
      .success-icon {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        background: #f0fdf4;
        color: var(--brand);
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1rem;
        border: 1px solid #bbf7d0;
      }
      .btn-signin-link {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        height: 44px;
        margin-top: 1.25rem;
        border-radius: 8px;
        background: var(--brand);
        color: #ffffff;
        font-size: 0.875rem;
        font-weight: 600;
        text-decoration: none;
        transition: background-color 0.15s ease;
      }
      .btn-signin-link:hover { background: var(--brand-hover); }
      @media (max-width: 480px) {
        .verify-card { padding: 1.75rem 1.25rem; }
        .otp-boxes { gap: 6px; }
        .otp-digit { height: 48px; font-size: 1.25rem; }
      }
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
          <label for="otpDigit1" class="otp-label">6-Digit Verification Code</label>
          <div id="otpBoxes" class="otp-boxes" role="group" aria-label="6-Digit Verification Code">
            <input type="text" id="otpDigit1" class="otp-digit" inputmode="numeric" pattern="[0-9]*" maxlength="1" autocomplete="one-time-code" aria-label="Digit 1 of 6" autofocus required />
            <input type="text" id="otpDigit2" class="otp-digit" inputmode="numeric" pattern="[0-9]*" maxlength="1" autocomplete="off" aria-label="Digit 2 of 6" required />
            <input type="text" id="otpDigit3" class="otp-digit" inputmode="numeric" pattern="[0-9]*" maxlength="1" autocomplete="off" aria-label="Digit 3 of 6" required />
            <input type="text" id="otpDigit4" class="otp-digit" inputmode="numeric" pattern="[0-9]*" maxlength="1" autocomplete="off" aria-label="Digit 4 of 6" required />
            <input type="text" id="otpDigit5" class="otp-digit" inputmode="numeric" pattern="[0-9]*" maxlength="1" autocomplete="off" aria-label="Digit 5 of 6" required />
            <input type="text" id="otpDigit6" class="otp-digit" inputmode="numeric" pattern="[0-9]*" maxlength="1" autocomplete="off" aria-label="Digit 6 of 6" required />
          </div>
          <div id="otpErrorBox" class="otp-error" role="alert"></div>
          <div id="otpStatusBox" class="otp-status" role="status"<?= $autoResentOtp ? ' style="display:block;"' : '' ?>><?= $autoResentOtp ? 'Your previous code expired. A new 6-digit code was just sent to ' . htmlspecialchars($maskedEmail) . ' — please use the latest email.' : '' ?></div>

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
      const otpBoxesEl = document.getElementById('otpBoxes');
      const otpDigits = Array.from(document.querySelectorAll('.otp-digit'));

      function getOtpValue() {
        return otpDigits.map(el => el.value.replace(/\D/g, '')).join('');
      }

      function syncDigitStyles() {
        otpDigits.forEach(el => el.classList.toggle('filled', el.value !== ''));
      }

      function clearOtpErrorState() {
        otpBoxesEl.classList.remove('has-error', 'shake');
      }

      function triggerOtpErrorState(focusEl) {
        otpBoxesEl.classList.remove('shake');
        void otpBoxesEl.offsetWidth;
        otpBoxesEl.classList.add('has-error', 'shake');
        if (focusEl) focusEl.focus();
      }

      function fillDigitsFrom(startIdx, rawText) {
        const digits = (rawText || '').replace(/\D/g, '').slice(0, otpDigits.length - startIdx);
        if (!digits) return;
        for (let i = 0; i < digits.length; i++) {
          otpDigits[startIdx + i].value = digits[i];
        }
        syncDigitStyles();
        clearOtpErrorState();
        const nextIdx = Math.min(startIdx + digits.length, otpDigits.length - 1);
        otpDigits[nextIdx].focus();
        otpDigits[nextIdx].select();
      }

      otpDigits.forEach((input, idx) => {
        input.addEventListener('focus', () => input.select());

        input.addEventListener('input', (e) => {
          const clean = input.value.replace(/\D/g, '');
          clearOtpErrorState();
          if (clean.length > 1) {
            fillDigitsFrom(idx, clean);
            return;
          }
          input.value = clean;
          syncDigitStyles();
          if (clean && idx < otpDigits.length - 1) {
            otpDigits[idx + 1].focus();
          }
        });

        input.addEventListener('keydown', (e) => {
          if (e.key === 'Backspace') {
            clearOtpErrorState();
            if (input.value) {
              input.value = '';
              syncDigitStyles();
            } else if (idx > 0) {
              otpDigits[idx - 1].value = '';
              syncDigitStyles();
              otpDigits[idx - 1].focus();
            }
            e.preventDefault();
          } else if (e.key === 'ArrowLeft' && idx > 0) {
            e.preventDefault();
            otpDigits[idx - 1].focus();
          } else if (e.key === 'ArrowRight' && idx < otpDigits.length - 1) {
            e.preventDefault();
            otpDigits[idx + 1].focus();
          } else if (e.key === 'Home') {
            e.preventDefault();
            otpDigits[0].focus();
          } else if (e.key === 'End') {
            e.preventDefault();
            otpDigits[otpDigits.length - 1].focus();
          }
        });

        input.addEventListener('paste', (e) => {
          e.preventDefault();
          const pasted = (e.clipboardData || window.clipboardData).getData('text');
          const digits = pasted.replace(/\D/g, '');
          if (!digits) return;
          fillDigitsFrom(digits.length >= otpDigits.length ? 0 : idx, digits);
        });
      });

      async function submitOtpVerification(e) {
        e.preventDefault();
        const errBox = document.getElementById('otpErrorBox');
        const statusBox = document.getElementById('otpStatusBox');
        const btn = document.getElementById('btnVerifySubmit');
        const otp = getOtpValue();

        errBox.style.display = 'none';
        statusBox.style.display = 'none';
        clearOtpErrorState();

        if (otp.length !== 6) {
          errBox.textContent = 'Please enter all 6 digits of your verification code.';
          errBox.style.display = 'block';
          const firstEmpty = otpDigits.find(el => !el.value) || otpDigits[0];
          triggerOtpErrorState(firstEmpty);
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
            triggerOtpErrorState(otpDigits[0]);
            otpDigits[0].select();
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
        clearOtpErrorState();
        btn.disabled = true;

        try {
          const res = await fetch('/sdn/api/patient_verify_otp.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'resend' })
          });
          const data = await res.json();
          if (data.success) {
            otpDigits.forEach(el => { el.value = ''; });
            syncDigitStyles();
            otpDigits[0].focus();
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
          let left = 60;
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
