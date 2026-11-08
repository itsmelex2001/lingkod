<?php
session_start();
require_once __DIR__ . '/../api/config/db.php';

$token = trim($_GET['token'] ?? '');
$tokenValid = false;
$patientInfo = null;
$errorMessage = '';

if ($token === '') {
    $errorMessage = 'No password reset token was provided in the link.';
} else {
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
            $errorMessage = 'This password reset link is invalid or has already been used.';
        } elseif (!empty($patient['reset_token_expires_at']) && strtotime($patient['reset_token_expires_at']) < time()) {
            $errorMessage = 'This password reset link has expired (valid for 1 hour). Please request a new one.';
        } else {
            $tokenValid = true;
            $patientInfo = $patient;
        }
    } catch (PDOException $e) {
        $errorMessage = 'A database error occurred. Please try again later.';
    }
}
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Reset Password | MMWGH Patient Portal</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/sdn/public/css/global.css?v=<?= time() ?>" />

    <style>
      *, *::before, *::after {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
      }

      html, body {
        width: 100%;
        height: 100%;
        overflow: hidden !important;
        scrollbar-width: none !important;
        -ms-overflow-style: none !important;
      }
      html::-webkit-scrollbar, body::-webkit-scrollbar {
        display: none !important;
        width: 0 !important;
        height: 0 !important;
      }

      body {
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        background-color: #071a10;
        color: #ffffff;
        position: fixed;
        inset: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1.5rem;
      }

      /* ── Background Image Framing ── */
      .portal-bg-canvas {
        position: fixed;
        inset: 0;
        z-index: 0;
        background-image: url('/sdn/public/assets/mmwghicon_bg.jpg');
        background-size: cover;
        background-position: left center;
        background-repeat: no-repeat;
        transform-origin: 8% 44%;
        transform: scale(1.30);
        pointer-events: none;
      }

      /* ── Background Shading Overlay ── */
      .portal-bg-overlay {
        position: fixed;
        inset: 0;
        z-index: 1;
        background:
          linear-gradient(to bottom, rgba(12, 22, 16, 0.42) 0%, rgba(12, 22, 16, 0) 24%),
          linear-gradient(to top, rgba(14, 115, 45, 0.88) 0%, rgba(16, 128, 52, 0.50) 22%, rgba(16, 128, 52, 0) 55%),
          radial-gradient(ellipse 75% 70% at 0% 100%, rgba(18, 148, 58, 0.94) 0%, rgba(18, 142, 56, 0.78) 32%, rgba(16, 130, 50, 0.42) 58%, transparent 78%),
          radial-gradient(ellipse 75% 70% at 100% 100%, rgba(18, 148, 58, 0.94) 0%, rgba(18, 142, 56, 0.78) 32%, rgba(16, 130, 50, 0.42) 58%, transparent 78%);
        pointer-events: none;
      }

      /* ── Main Container (Balanced Frosted Glass with Strong Contrast) ── */
      .login-container {
        position: relative;
        z-index: 5;
        width: 100%;
        max-width: 440px;
        border-radius: 28px;
        border: 1.5px solid rgba(255, 255, 255, 0.65);
        background: linear-gradient(
          150deg,
          rgba(12, 32, 20, 0.62) 0%,
          rgba(8, 22, 14, 0.70) 100%
        );
        backdrop-filter: blur(14px) saturate(130%);
        -webkit-backdrop-filter: blur(14px) saturate(130%);
        box-shadow:
          0 24px 60px rgba(0, 0, 0, 0.52),
          inset 0 1px 1px rgba(255, 255, 255, 0.35);
        padding: 2.2rem 2.4rem 2rem;
        box-sizing: border-box;
      }

      .login-card {
        position: relative;
        width: 100%;
      }

      /* ── Header ── */
      .card-header {
        text-align: center;
        margin-bottom: 1.4rem;
      }

      .logo-emblem {
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 0.65rem;
      }
      .logo-emblem img {
        width: 155px;
        height: auto;
        max-height: 112px;
        object-fit: contain;
        display: block;
        filter:
          brightness(1.22)
          drop-shadow(0 2px 8px rgba(0, 0, 0, 0.32));
      }

      .portal-title {
        font-family: 'Outfit', sans-serif;
        font-size: 1.6rem;
        font-weight: 800;
        letter-spacing: 0.02em;
        color: #ffffff;
        text-shadow: 0 2px 8px rgba(0, 0, 0, 0.65);
        margin: 0 0 4px;
        line-height: 1.2;
      }

      .portal-subtitle {
        font-size: 0.86rem;
        color: #ffffff;
        opacity: 0.98;
        text-shadow: 0 1px 4px rgba(0, 0, 0, 0.65);
        font-weight: 500;
        letter-spacing: 0.01em;
        margin-bottom: 0.5rem;
      }

      .account-pill {
        display: inline-block;
        background: rgba(14, 164, 63, 0.28);
        border: 1px solid rgba(74, 222, 128, 0.55);
        border-radius: 20px;
        padding: 5px 14px;
        font-size: 0.82rem;
        font-weight: 600;
        color: #86efac;
        margin-top: 6px;
        text-shadow: 0 1px 3px rgba(0, 0, 0, 0.5);
      }

      /* ── Form Styles ── */
      .form-group {
        margin-bottom: 1.15rem;
      }

      .form-label {
        display: block;
        font-size: 0.86rem;
        font-weight: 700;
        color: #ffffff;
        text-shadow: 0 1px 3px rgba(0, 0, 0, 0.6);
        margin-bottom: 0.45rem;
        letter-spacing: 0.01em;
      }

      .input-wrapper {
        position: relative;
        display: flex;
        align-items: center;
      }

      .input-icon {
        position: absolute;
        left: 14px;
        width: 18px;
        height: 18px;
        color: #475569;
        pointer-events: none;
        transition: color 0.2s ease;
      }

      .form-control {
        width: 100%;
        height: 48px;
        padding: 0 2.6rem 0 2.6rem;
        background: rgba(248, 250, 252, 0.92);
        border: 1.5px solid rgba(255, 255, 255, 0.85);
        border-radius: 12px;
        color: #0f172a;
        font-family: inherit;
        font-size: 0.94rem;
        font-weight: 600;
        outline: none;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.12);
        transition: all 0.2s ease;
      }
      .form-control::placeholder {
        color: #64748b;
        font-size: 0.88rem;
        font-weight: 500;
      }
      .form-control:focus {
        background: #ffffff;
        border-color: #10b445;
        box-shadow: 0 0 0 3px rgba(16, 180, 69, 0.28);
      }
      .input-wrapper:focus-within .input-icon {
        color: #0d9639;
      }

      .btn-toggle-eye {
        position: absolute;
        right: 12px;
        background: transparent;
        border: none;
        color: #64748b;
        cursor: pointer;
        padding: 4px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: color 0.2s ease;
      }
      .btn-toggle-eye:hover {
        color: #0f172a;
      }

      /* ── Submit Button ── */
      .btn-portal-submit {
        width: 100%;
        height: 48px;
        border-radius: 14px;
        border: none;
        background: #0ea43f;
        color: #ffffff;
        font-family: inherit;
        font-size: 1.05rem;
        font-weight: 700;
        letter-spacing: 0.01em;
        cursor: pointer;
        box-shadow: 0 4px 18px rgba(14, 164, 63, 0.45);
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-top: 1.4rem;
        text-shadow: 0 1px 2px rgba(0, 0, 0, 0.25);
      }
        border: none;
        background: #0ea43f;
        color: #ffffff;
        font-family: inherit;
        font-size: 1.05rem;
        font-weight: 700;
        letter-spacing: 0.01em;
        cursor: pointer;
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.28);
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-top: 1.4rem;
      }
      .btn-portal-submit:hover:not(:disabled) {
        background: #0d9639;
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.35);
        transform: translateY(-1px);
      }
      .btn-portal-submit:disabled {
        opacity: 0.65;
        cursor: not-allowed;
      }

      .btn-secondary-link {
        display: block;
        text-align: center;
        margin-top: 1rem;
        color: #ffffff;
        text-decoration: none;
        font-size: 0.84rem;
        font-weight: 600;
        text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
      }
      .btn-secondary-link:hover {
        text-decoration: underline;
      }

      /* ── Status Banner ── */
      .status-box {
        padding: 12px 14px;
        border-radius: 12px;
        margin-bottom: 1.25rem;
        font-size: 0.86rem;
        line-height: 1.45;
        display: flex;
        align-items: center;
        gap: 10px;
      }
      .status-box.error {
        background: rgba(239, 68, 68, 0.22);
        border: 1px solid rgba(248, 113, 113, 0.45);
        color: #fecaca;
      }
      .status-box.success {
        background: rgba(16, 185, 129, 0.22);
        border: 1px solid rgba(52, 211, 153, 0.45);
        color: #a7f3d0;
      }

      /* ── Expired / Error View ── */
      .error-card-state {
        text-align: center;
        padding: 1rem 0;
      }
      .error-icon-circle {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background: rgba(239, 68, 68, 0.2);
        border: 1px solid rgba(248, 113, 113, 0.4);
        color: #f87171;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1.2rem;
      }

      .success-icon-circle {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background: rgba(16, 185, 129, 0.2);
        border: 1px solid rgba(52, 211, 153, 0.4);
        color: #34d399;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1.2rem;
      }

      @media (max-width: 640px) {
        .login-container { border-radius: 22px; padding: 1.8rem 1.4rem; }
        .portal-title { font-size: 1.45rem; }
      }
    </style>
  </head>
  <body>
    <!-- Background Canvas & Overlay -->
    <div class="portal-bg-canvas"></div>
    <div class="portal-bg-overlay"></div>

    <main class="login-container">
      <div class="login-card">

        <!-- Header -->
        <div class="card-header">
          <div class="logo-emblem">
            <img src="/sdn/public/assets/v3_lingkod_logo_clean.png" alt="LINKod Logo" />
          </div>
          <h1 class="portal-title">RESET PASSWORD</h1>
          <p class="portal-subtitle">Mariveles Mental Wellness and General Hospital</p>
          <?php if ($tokenValid && $patientInfo): ?>
            <div class="account-pill">
              For: <?= htmlspecialchars($patientInfo['email']) ?>
            </div>
          <?php endif; ?>
        </div>

        <?php if (!$tokenValid): ?>
          <!-- Invalid or Expired Token State -->
          <div class="error-card-state">
            <div class="error-icon-circle">
              <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="8" x2="12" y2="12"></line>
                <line x1="12" y1="16" x2="12.01" y2="16"></line>
              </svg>
            </div>
            <p style="font-size: 0.92rem; line-height: 1.5; margin-bottom: 1.5rem; color: #ffffff;">
              <?= htmlspecialchars($errorMessage) ?>
            </p>
            <a href="/sdn/pages/patient-login.php?action=forgot" class="btn-portal-submit" style="text-decoration: none;">
              Request New Reset Link
            </a>
            <a href="/sdn/pages/patient-login.php" class="btn-secondary-link">
              Back to Patient Login
            </a>
          </div>

        <?php else: ?>
          <!-- Active Reset Form -->
          <div id="resetStatus" style="display: none;"></div>

          <form id="resetPasswordForm" onsubmit="handlePasswordReset(event)">
            <input type="hidden" id="resetTokenInput" value="<?= htmlspecialchars($token) ?>" />

            <!-- New Password -->
            <div class="form-group">
              <label class="form-label" for="newPassword">New Password</label>
              <div class="input-wrapper">
                <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                  <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                </svg>
                <input
                  type="password"
                  id="newPassword"
                  class="form-control"
                  placeholder="At least 8 characters"
                  required
                  minlength="8"
                  autocomplete="new-password"
                />
                <button type="button" class="btn-toggle-eye" onclick="togglePass('newPassword', this)" title="Show/Hide Password">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                    <circle cx="12" cy="12" r="3"></circle>
                  </svg>
                </button>
              </div>
            </div>

            <!-- Confirm New Password -->
            <div class="form-group">
              <label class="form-label" for="confirmPassword">Confirm New Password</label>
              <div class="input-wrapper">
                <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                  <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                </svg>
                <input
                  type="password"
                  id="confirmPassword"
                  class="form-control"
                  placeholder="Re-enter your new password"
                  required
                  minlength="8"
                  autocomplete="new-password"
                />
                <button type="button" class="btn-toggle-eye" onclick="togglePass('confirmPassword', this)" title="Show/Hide Password">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                    <circle cx="12" cy="12" r="3"></circle>
                  </svg>
                </button>
              </div>
            </div>

            <button type="submit" id="submitResetBtn" class="btn-portal-submit">
              Update Password
            </button>

            <a href="/sdn/pages/patient-login.php" class="btn-secondary-link">
              Cancel and Return to Sign In
            </a>
          </form>

          <!-- Success State Container (shown after submission) -->
          <div id="resetSuccessState" style="display: none;" class="error-card-state">
            <div class="success-icon-circle">
              <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="20 6 9 17 4 12"></polyline>
              </svg>
            </div>
            <h3 style="font-size: 1.25rem; font-weight: 700; margin-bottom: 0.5rem; color: #ffffff;">Password Changed!</h3>
            <p style="font-size: 0.88rem; line-height: 1.5; margin-bottom: 1.5rem; color: #e2e8f0;">
              Your password has been successfully updated. You can now sign in to your MMWGH Patient Portal account.
            </p>
            <a href="/sdn/pages/patient-login.php" class="btn-portal-submit" style="text-decoration: none;">
              Sign In to Patient Portal
            </a>
          </div>
        <?php endif; ?>

      </div>
    </main>

    <script>
      function togglePass(inputId, btn) {
        const input = document.getElementById(inputId);
        const svg = btn.querySelector('svg');
        if (input.type === 'password') {
          input.type = 'text';
          svg.innerHTML = `
            <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
            <line x1="1" y1="1" x2="23" y2="23"></line>
          `;
        } else {
          input.type = 'password';
          svg.innerHTML = `
            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
            <circle cx="12" cy="12" r="3"></circle>
          `;
        }
      }

      async function handlePasswordReset(e) {
        e.preventDefault();
        const token = document.getElementById('resetTokenInput').value;
        const newPass = document.getElementById('newPassword').value;
        const confirmPass = document.getElementById('confirmPassword').value;
        const btn = document.getElementById('submitResetBtn');
        const statusBox = document.getElementById('resetStatus');

        statusBox.style.display = 'none';

        if (newPass.length < 8) {
          statusBox.className = 'status-box error';
          statusBox.textContent = 'New password must be at least 8 characters long.';
          statusBox.style.display = 'block';
          return;
        }

        if (newPass !== confirmPass) {
          statusBox.className = 'status-box error';
          statusBox.textContent = 'Passwords do not match. Please re-enter.';
          statusBox.style.display = 'block';
          return;
        }

        btn.disabled = true;
        btn.textContent = 'Updating Password...';

        try {
          const res = await fetch('/sdn/api/patient_reset_password.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
              token: token,
              new_password: newPass,
              confirm_password: confirmPass
            })
          });

          const data = await res.json();

          if (data.success) {
            document.getElementById('resetPasswordForm').style.display = 'none';
            document.getElementById('resetSuccessState').style.display = 'block';
          } else {
            statusBox.className = 'status-box error';
            statusBox.textContent = data.message || 'Failed to reset password.';
            statusBox.style.display = 'block';
            btn.disabled = false;
            btn.textContent = 'Update Password';
          }
        } catch (err) {
          statusBox.className = 'status-box error';
          statusBox.textContent = 'A network error occurred. Please try again.';
          statusBox.style.display = 'block';
          btn.disabled = false;
          btn.textContent = 'Update Password';
        }
      }
    </script>
  </body>
</html>

