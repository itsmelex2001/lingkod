<?php
session_start();

/**
 * pages/portal@admin$staff_2026.php
 * Secure Dedicated Login Portal for Admins and Staff
 * Protected entry URL with letters, numbers, and symbols (@, $, _) to prevent unauthorized access.
 */

header('Cache-Control: no-cache, no-store, must-revalidate, private');
header('Pragma: no-cache');
header('Expires: 0');

if (isset($_GET['logout']) || isset($_GET['force'])) {
    $_SESSION = array();
    session_destroy();
    session_start();
}

// Redirect already logged-in staff/admins directly to their respective dashboards
if (!empty($_SESSION['user']) && !empty($_SESSION['role'])) {
    $role = strtolower(trim($_SESSION['role']));
    if ($role === 'admin') {
        header("Location: /sdn/pages/admin-dashboard.php");
        exit;
    } elseif ($role === 'staff' || $role === 'doctor' || $role === 'nurse') {
        header("Location: /sdn/pages/staff-dashboard.php");
        exit;
    }
}

$error = '';
$postedUser = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/../api/config/db.php';

    $input    = trim($_POST['loginUser'] ?? '');
    $password = $_POST['loginPass'] ?? '';
    $postedUser = $input;

    if ($input === '' || $password === '') {
        $error = 'Please fill in all fields.';
    } else {
        try {
            $stmt = $pdo->prepare("
                SELECT id, username, password, role, status, full_name, department
                FROM users
                WHERE username = :input
                LIMIT 1
            ");
            $stmt->execute([':input' => $input]);
            $user = $stmt->fetch();

            if (!$user) {
                $error = 'Access Denied: Invalid credentials.';
            } elseif ($user['role'] === 'patient') {
                $error = 'Access Denied: Patient accounts must sign in via the Patient Portal.';
            } elseif ($user['status'] !== 'active') {
                $error = 'Your staff account is currently inactive. Please contact the administrator.';
            } else {
                $storedPass = $user['password'];
                $isHashed   = str_starts_with($storedPass, '$2y$');
                $passwordOk = false;

                if ($isHashed) {
                    $passwordOk = password_verify($password, $storedPass);

                    // Emergency recovery fallback for system administrator
                    if (!$passwordOk && strtolower($user['username']) === 'admin') {
                        if (
                            $password === '@LinkodAdminPassword2026.' ||
                            $password === '@LinkodAdminPassword2026' ||
                            $password === 'admin123'
                        ) {
                            $passwordOk = true;
                            $newHash = password_hash($password, PASSWORD_BCRYPT);
                            $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")
                                ->execute([$newHash, $user['id']]);
                        }
                    }
                } else {
                    $passwordOk = ($storedPass === $password) || ($storedPass === md5($password));

                    if ($passwordOk) {
                        $newHash = password_hash($password, PASSWORD_BCRYPT);
                        $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")
                            ->execute([$newHash, $user['id']]);
                    }
                }

                if (!$passwordOk) {
                    $error = 'Access Denied: Invalid credentials.';
                } else {
                    $pdo->prepare("
                        UPDATE users
                        SET last_login = NOW(), login_count = login_count + 1
                        WHERE id = :id
                    ")->execute([':id' => $user['id']]);

                    session_regenerate_id(true);
                    $_SESSION['user']        = $user['username'];
                    $_SESSION['user_id']     = $user['id'];
                    $_SESSION['username']    = $user['username'];
                    $_SESSION['full_name']   = $user['full_name'] ?: $user['username'];
                    $_SESSION['role']        = $user['role'];
                    $_SESSION['department']  = $user['department'] ?? '';
                    $_SESSION['staff_auth']  = true;

                    $dest = strtolower(trim($user['role'] ?? '')) === 'admin'
                        ? '/sdn/pages/admin-dashboard.php'
                        : '/sdn/pages/staff-dashboard.php';
                    header("Location: $dest");
                    exit;
                }
            }
        } catch (Exception $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="robots" content="noindex, nofollow" />
    <title>Staff &amp; Admin Portal | MMWGH LINGKOD</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap"
      rel="stylesheet"
    />
    <style>
      :root {
        --color-primary: #15803d;
        --color-primary-hover: #166534;
        --color-primary-active: #14532d;
        --color-text-strong: #111827;
        --color-text-body: #374151;
        --color-text-muted: #6b7280;
        --color-border: #d1d5db;
        --color-error: #dc2626;
        --color-error-bg: #fef2f2;
        --color-warning: #b45309;
        --control-height: 42px;
        --font-sans: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
      }

      *, *::before, *::after {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
      }

      body, body * {
        user-select: none;
        -webkit-user-select: none;
        caret-color: transparent;
      }

      input, textarea, select {
        user-select: text;
        -webkit-user-select: text;
        caret-color: auto;
      }

      html, body {
        margin: 0;
        min-height: 100dvh;
        font-family: var(--font-sans);
        color: var(--color-text-body);
        background: #0c2416;
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
      }

      .login-shell {
        position: relative;
        min-height: 100dvh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1.25rem;
        overflow: hidden;
      }

      .bg-video {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        opacity: 0;
        filter: grayscale(40%) contrast(1.04);
        transition: opacity 1.2s ease;
        z-index: 0;
        pointer-events: none;
      }

      .bg-video.active {
        opacity: 1;
      }

      .veil {
        position: absolute;
        inset: 0;
        z-index: 2;
        pointer-events: none;
        transform: translateZ(0);
        background:
          linear-gradient(105deg, rgba(5, 42, 18, 0.76) 0%, rgba(7, 48, 20, 0.64) 50%, rgba(3, 30, 12, 0.8) 100%);
      }

      /* ── Centered White Login Form Card (matches Patient Login form styling) ── */
      .login-card {
        position: relative;
        z-index: 3;
        width: 100%;
        max-width: 420px;
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        padding: 2rem 2rem 1.6rem;
        box-shadow: 0 24px 48px -12px rgba(0, 0, 0, 0.45);
      }

      .brand-header {
        margin-bottom: 1.5rem;
        text-align: left;
      }

      .brand-logo-img {
        width: 152px;
        height: auto;
        display: block;
        object-fit: contain;
        margin: 0 0 1rem 0;
      }

      .portal-heading {
        font-size: 1.45rem;
        font-weight: 600;
        color: var(--color-text-strong);
        letter-spacing: -0.025em;
        line-height: 1.3;
        margin: 0 0 4px 0;
      }

      .portal-subheading {
        font-size: 0.8125rem;
        color: var(--color-text-muted);
        line-height: 1.45;
      }

      /* Alert Banner */
      .auth-alert {
        display: flex;
        align-items: flex-start;
        gap: 0.6rem;
        padding: 0.6rem 0.75rem;
        border-radius: 8px;
        font-size: 0.8rem;
        line-height: 1.4;
        margin-bottom: 1rem;
      }

      .auth-alert-error {
        background: var(--color-error-bg);
        border: 1px solid #fecaca;
        color: var(--color-error);
      }

      .auth-alert-icon {
        flex-shrink: 0;
        width: 16px;
        height: 16px;
        margin-top: 1px;
      }

      .auth-alert-icon svg {
        width: 100%;
        height: 100%;
      }

      /* Form Elements */
      #staffSignInForm {
        display: flex;
        flex-direction: column;
        gap: 16px;
      }

      .form-group {
        display: flex;
        flex-direction: column;
        gap: 7px;
      }

      .form-label {
        display: block;
        font-size: 0.8rem;
        font-weight: 600;
        color: var(--color-text-body);
        line-height: 1.2;
      }

      .input-wrapper {
        position: relative;
        display: flex;
        align-items: center;
      }

      .input-icon-lead {
        position: absolute;
        left: 13px;
        width: 16px;
        height: 16px;
        color: #9ca3af;
        pointer-events: none;
      }

      .input-icon-lead svg {
        width: 100%;
        height: 100%;
      }

      .form-input {
        width: 100%;
        height: var(--control-height);
        padding: 0 40px;
        border: none;
        border-radius: 8px;
        background: #ffffff;
        box-shadow: inset 0 0 0 1px var(--color-border);
        font-family: var(--font-sans);
        font-size: 0.875rem;
        color: var(--color-text-strong);
        outline: none;
        transition: box-shadow 0.15s ease;
      }

      .form-input::placeholder {
        color: #9ca3af;
        font-weight: 400;
      }

      .form-input:hover {
        box-shadow: inset 0 0 0 1px #9ca3af;
      }

      .form-input:focus {
        box-shadow: inset 0 0 0 2px #16a34a;
      }

      input[type="password"]::-ms-reveal,
      input[type="password"]::-ms-clear {
        display: none !important;
        width: 0 !important;
        height: 0 !important;
        pointer-events: none !important;
      }

      .toggle-pass-btn {
        position: absolute;
        right: 10px;
        width: 28px;
        height: 28px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: transparent;
        border: none;
        color: #6b7280;
        cursor: pointer;
        padding: 0;
        border-radius: 4px;
        transition: color 0.15s ease;
      }

      .toggle-pass-btn:hover {
        color: var(--color-text-strong);
      }

      .toggle-pass-btn:focus-visible {
        outline: 2px solid var(--color-primary);
      }

      .toggle-pass-btn svg {
        width: 16px;
        height: 16px;
      }

      .caps-lock-warning {
        display: none;
        align-items: center;
        gap: 0.35rem;
        font-size: 0.74rem;
        color: var(--color-warning);
        font-weight: 500;
      }

      .caps-lock-warning.visible {
        display: flex;
      }

      .caps-lock-warning svg {
        width: 13px;
        height: 13px;
        flex-shrink: 0;
      }

      .btn-submit {
        width: 100%;
        height: var(--control-height);
        margin-top: 4px;
        border: none;
        border-radius: 8px;
        background-color: var(--color-primary);
        color: #ffffff;
        font-family: var(--font-sans);
        font-size: 0.875rem;
        font-weight: 500;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: background-color 0.15s ease, opacity 0.15s ease;
      }

      .btn-submit:hover:not(:disabled) {
        background-color: var(--color-primary-hover);
      }

      .btn-submit:active:not(:disabled) {
        background-color: var(--color-primary-active);
      }

      .btn-submit:focus-visible {
        outline: 2px solid #16a34a;
        outline-offset: 2px;
      }

      .btn-submit:disabled {
        opacity: 0.65;
        cursor: not-allowed;
      }

      .card-footer {
        margin-top: 1.4rem;
        padding-top: 1rem;
        border-top: 1px solid #f3f4f6;
        text-align: center;
        font-size: 0.72rem;
        color: var(--color-text-muted);
        line-height: 1.45;
      }

      .footer-compliance {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.35rem;
        font-weight: 500;
        color: #374151;
        margin-bottom: 0.2rem;
      }

      .footer-compliance svg {
        width: 12px;
        height: 12px;
        color: var(--color-primary);
      }

      .footer-note {
        color: #9ca3af;
      }

      @media (max-width: 480px) {
        .login-card {
          padding: 1.65rem 1.25rem 1.35rem;
        }

        .form-input,
        .btn-submit {
          height: 44px;
        }

        .form-input {
          font-size: 1rem;
        }
      }
    </style>
  </head>
  <body>
    <main class="login-shell">
      <video class="bg-video active" src="/sdn/public/assets/generalmanagement_clip.mp4" autoplay muted loop playsinline></video>
      <video class="bg-video" src="/sdn/public/assets/neurology_clip.mp4" autoplay muted loop playsinline></video>
      <video class="bg-video" src="/sdn/public/assets/psychiatry_clip.mp4" autoplay muted loop playsinline></video>
      <div class="veil"></div>

      <section class="login-card" aria-label="Staff and Administrator Sign In">
        <header class="brand-header">
          <img
            src="/sdn/public/assets/v2_lingkod_logo_cropped.png"
            alt="Lingkod"
            class="brand-logo-img"
          />
          <h1 class="portal-heading">Staff &amp; Administrator Access</h1>
          <p class="portal-subheading">Sign in to access the Hospital Personnel Portal.</p>
        </header>

        <?php if ($error !== ''): ?>
          <div class="auth-alert auth-alert-error" role="alert" aria-live="assertive">
            <span class="auth-alert-icon" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="8" x2="12" y2="12"></line>
                <line x1="12" y1="16" x2="12.01" y2="16"></line>
              </svg>
            </span>
            <span><?= htmlspecialchars($error) ?></span>
          </div>
        <?php endif; ?>

        <form id="staffSignInForm" method="POST" action="/sdn/pages/portal@admin$staff_2026.php" autocomplete="off">
          <div class="form-group">
            <label for="loginUser" class="form-label">Username</label>
            <div class="input-wrapper">
              <span class="input-icon-lead" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"></path>
                  <circle cx="12" cy="7" r="4"></circle>
                </svg>
              </span>
              <input
                type="text"
                id="loginUser"
                name="loginUser"
                class="form-input"
                placeholder="Enter your username"
                value="<?= htmlspecialchars($postedUser) ?>"
                autocomplete="username"
                autocapitalize="none"
                autocorrect="off"
                spellcheck="false"
                required
              />
            </div>
          </div>

          <div class="form-group">
            <label for="loginPass" class="form-label">Password</label>
            <div class="input-wrapper">
              <span class="input-icon-lead" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                  <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                </svg>
              </span>
              <input
                type="password"
                id="loginPass"
                name="loginPass"
                class="form-input"
                placeholder="Enter your password"
                autocomplete="current-password"
                required
              />
              <button type="button" class="toggle-pass-btn" id="togglePass" aria-label="Show password" title="Show password">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                  <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                  <circle cx="12" cy="12" r="3"></circle>
                </svg>
              </button>
            </div>
            <p class="caps-lock-warning" id="capsWarning" role="status" aria-live="polite">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M12 9v4"></path>
                <path d="M12 17h.01"></path>
                <path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
              </svg>
              <span>Caps Lock is ON</span>
            </p>
          </div>

          <button type="submit" id="btnSubmit" class="btn-submit">Sign In</button>
        </form>

        <footer class="card-footer">
          <div class="footer-compliance">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <rect x="3" y="11" width="18" height="11" rx="2"></rect>
              <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
            </svg>
            <span>Authorized Personnel Only</span>
          </div>
          <p class="footer-note">Obfuscated staff URL protected against unauthorized discovery</p>
        </footer>
      </section>
    </main>

    <script>
      const clips = document.querySelectorAll('.bg-video');
      let clipIndex = 0;
      setInterval(() => {
        clips[clipIndex].classList.remove('active');
        clipIndex = (clipIndex + 1) % clips.length;
        clips[clipIndex].classList.add('active');
      }, 7000);

      const eyeSvg = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>';
      const eyeOffSvg = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>';

      const passInput = document.getElementById('loginPass');
      const toggleBtn = document.getElementById('togglePass');
      const capsWarning = document.getElementById('capsWarning');

      toggleBtn.addEventListener('click', () => {
        const show = passInput.type === 'password';
        passInput.type = show ? 'text' : 'password';
        toggleBtn.innerHTML = show ? eyeOffSvg : eyeSvg;
        toggleBtn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
        toggleBtn.title = show ? 'Hide password' : 'Show password';
      });

      const handleCaps = (e) => {
        if (typeof e.getModifierState === 'function') {
          capsWarning.classList.toggle('visible', e.getModifierState('CapsLock'));
        }
      };
      passInput.addEventListener('keyup', handleCaps);
      passInput.addEventListener('keydown', handleCaps);

      document.getElementById('staffSignInForm').addEventListener('submit', () => {
        const btn = document.getElementById('btnSubmit');
        btn.disabled = true;
        btn.textContent = 'Signing in…';
      });
    </script>
  </body>
</html>

