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
    <title>Staff & Admin Portal Sign In | LINGKOD</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
      *, *::before, *::after { box-sizing: border-box; }
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
        height: 100%;
        overflow: hidden;
        font-family: 'Inter', sans-serif;
        color: #163024;
        background: #0c2416;
      }
      .login-shell {
        position: relative;
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1rem;
      }
      .bg-video {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        opacity: 0;
        transition: opacity 1.2s ease;
        z-index: 0;
      }
      .bg-video.active { opacity: 1; }
      .veil {
        position: absolute;
        inset: 0;
        z-index: 1;
        background:
          linear-gradient(105deg, rgba(5, 42, 18, 0.72) 0%, rgba(7, 48, 20, 0.58) 50%, rgba(3, 30, 12, 0.76) 100%);
      }
      .login-layout {
        position: relative;
        z-index: 2;
        width: 100%;
        max-width: 28rem;
        display: flex;
        justify-content: center;
      }
      .brand {
        color: #f4faf6;
        max-width: 460px;
      }
      .brand-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.35rem 0.75rem;
        border-radius: 9999px;
        background: rgba(34, 197, 94, 0.15);
        border: 1px solid rgba(34, 197, 94, 0.35);
        color: #86efac;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-bottom: 1.25rem;
      }
      .brand-mark {
        display: flex;
        align-items: center;
        margin-bottom: -2.4rem;
      }
      .brand-mark img {
        width: min(330px, 72vw);
        height: auto;
        display: block;
        background: none;
        border: 0;
        box-shadow: none;
      }
      .brand h1 {
        margin: 0 0 0.75rem;
        font-size: clamp(2.1rem, 3.4vw, 3rem);
        font-weight: 600;
        letter-spacing: -0.035em;
        line-height: 1.12;
      }
      .brand p {
        margin: 0;
        max-width: 36ch;
        font-size: 1rem;
        line-height: 1.6;
        color: rgba(244, 250, 246, 0.85);
      }
      .brand-points {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
        margin: 1.75rem 0 0;
        padding: 0;
        list-style: none;
      }
      .brand-points li {
        display: flex;
        align-items: center;
        gap: 0.7rem;
        font-size: 0.92rem;
        color: rgba(244, 250, 246, 0.9);
      }
      .point-icon {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        display: grid;
        place-items: center;
        background: rgba(255, 255, 255, 0.12);
        border: 1px solid rgba(255, 255, 255, 0.16);
        color: #d8f5df;
        flex-shrink: 0;
      }
      .panel {
        width: 100%;
        padding: 1.75rem 1.85rem 1.5rem;
        border-radius: 1rem;
        color: #ffffff;
        background: rgba(8, 32, 18, 0.48);
        border: 1px solid rgba(255, 255, 255, 0.24);
        box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.36);
        backdrop-filter: blur(24px) saturate(140%);
        -webkit-backdrop-filter: blur(24px) saturate(140%);
      }
      .card-brand-top {
        display: flex;
        justify-content: center;
        margin-bottom: 0.4rem;
      }
      .card-brand-top img {
        height: 88px;
        width: auto;
        max-width: 240px;
        object-fit: contain;
        display: block;
        filter: drop-shadow(0 2px 10px rgba(0, 0, 0, 0.25));
      }
      .panel-head {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        gap: 0.2rem;
        margin-bottom: 1.2rem;
        padding-bottom: 0.95rem;
        border-bottom: 1px solid rgba(255, 255, 255, 0.18);
      }
      .panel-seal {
        width: 48px;
        height: 48px;
        object-fit: contain;
        flex-shrink: 0;
      }
      .panel-head h2 {
        margin: 0;
        font-size: 1.25rem;
        font-weight: 600;
        letter-spacing: -0.02em;
        color: #ffffff;
      }
      .panel-head p {
        margin: 0.15rem 0 0;
        font-size: 0.82rem;
        color: rgba(229, 231, 235, 0.85);
      }
      .error-alert {
        display: flex;
        align-items: flex-start;
        gap: 0.5rem;
        background: rgba(220, 38, 38, 0.18);
        border: 1px solid rgba(248, 113, 113, 0.35);
        color: #fecaca;
        padding: 0.75rem 0.85rem;
        border-radius: 10px;
        margin-bottom: 1rem;
        font-size: 0.84rem;
        line-height: 1.4;
      }
      .input-group { margin-bottom: 0.95rem; }
      label {
        display: block;
        margin-bottom: 0.38rem;
        font-size: 0.75rem;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        color: rgba(243, 244, 246, 0.9);
      }
      .input-wrap { position: relative; }
      .input-wrap svg.field-icon {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: rgba(229, 231, 235, 0.75);
        pointer-events: none;
      }
      input[type="text"], input[type="password"] {
        width: 100%;
        height: 46px;
        padding: 0 2.7rem;
        border: 1px solid rgba(255, 255, 255, 0.22);
        border-radius: 10px;
        background: rgba(255, 255, 255, 0.1);
        color: #ffffff;
        font: 500 0.94rem 'Inter', sans-serif;
        outline: none;
        box-shadow: none;
        transition: border-color 0.15s ease, background-color 0.15s ease, box-shadow 0.15s ease;
      }
      input::placeholder { color: #e5e7eb; font-weight: 400; }
      input:focus {
        border-color: rgba(255, 255, 255, 0.35);
        background: rgba(255, 255, 255, 0.1);
        box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.14);
      }
      input[type="password"]::-ms-reveal,
      input[type="password"]::-ms-clear {
        display: none !important;
        width: 0 !important;
        height: 0 !important;
        pointer-events: none !important;
      }
      .btn-toggle-pass {
        position: absolute;
        right: 6px;
        top: 50%;
        transform: translateY(-50%);
        width: 32px;
        height: 32px;
        border: none;
        border-radius: 8px;
        background: transparent;
        color: rgba(229, 231, 235, 0.75);
        cursor: pointer;
        display: grid;
        place-items: center;
        padding: 0;
      }
      .btn-toggle-pass:hover { background: rgba(255, 255, 255, 0.12); color: #ffffff; }
      .btn-submit {
        width: 100%;
        height: 48px;
        margin-top: 0.5rem;
        border: none;
        border-radius: 10px;
        background: linear-gradient(180deg, #15803d 0%, #166534 100%);
        color: #ffffff;
        font: 600 0.95rem 'Inter', sans-serif;
        letter-spacing: 0.02em;
        cursor: pointer;
        box-shadow: 0 8px 16px rgba(21, 128, 61, 0.25);
        transition: filter 0.15s ease, transform 0.1s ease;
      }
      .btn-submit:hover { filter: brightness(1.08); }
      .btn-submit:active { transform: translateY(1px); }
      .panel-note {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.4rem;
        margin: 1.1rem 0 0;
        font-size: 0.78rem;
        font-weight: 600;
        color: rgba(243, 244, 246, 0.9);
      }
      .secure-pill {
        display: flex;
        align-items: center;
        gap: 0.4rem;
        font-size: 0.72rem;
        color: rgba(209, 213, 219, 0.78);
        margin-top: 0.5rem;
        justify-content: center;
      }
      @media (max-width: 860px) {
        html, body { overflow: auto; }
        .login-shell { align-items: center; padding: 1rem; }
      }
    </style>
  </head>
  <body>
    <main class="login-shell">
      <video class="bg-video active" src="/sdn/public/assets/generalmanagement_clip.mp4" autoplay muted loop playsinline></video>
      <video class="bg-video" src="/sdn/public/assets/neurology_clip.mp4" autoplay muted loop playsinline></video>
      <video class="bg-video" src="/sdn/public/assets/psychiatry_clip.mp4" autoplay muted loop playsinline></video>
      <div class="veil"></div>

      <div class="login-layout">
        <section class="panel" aria-label="Sign in">
          <div class="card-brand-top">
            <img src="/sdn/public/assets/v3_lingkod_logo.png" alt="LINGKOD" />
          </div>
          <div class="panel-head">
            <div>
              <h2>Staff &amp; Administrator Access</h2>
              <p>Hospital Personnel Portal</p>
            </div>
          </div>

          <?php if ($error !== ''): ?>
            <div class="error-alert" role="alert">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
              <span><?= htmlspecialchars($error) ?></span>
            </div>
          <?php endif; ?>

          <form method="POST" action="/sdn/pages/portal@admin$staff_2026.php" autocomplete="off">
            <div class="input-group">
              <label for="loginUser">Username</label>
              <div class="input-wrap">
                <svg class="field-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                  <circle cx="12" cy="7" r="4"></circle>
                </svg>
                <input type="text" id="loginUser" name="loginUser" placeholder="Enter Username" value="<?= htmlspecialchars($postedUser) ?>" required autocomplete="username" />
              </div>
            </div>
            <div class="input-group">
              <label for="loginPass">Password</label>
              <div class="input-wrap">
                <svg class="field-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                  <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                  <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                </svg>
                <input type="password" id="loginPass" name="loginPass" placeholder="Enter password" required autocomplete="current-password" />
                <button type="button" class="btn-toggle-pass" id="togglePass" aria-label="Show password">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                    <circle cx="12" cy="12" r="3"></circle>
                  </svg>
                </button>
              </div>
            </div>
            <button type="submit" class="btn-submit">Sign in</button>
          </form>
          <p class="panel-note">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
            Authorized Personnel Only
          </p>
          <div class="secure-pill">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
            Obfuscated staff URL protected against unauthorized discovery
          </div>
        </section>
      </div>
    </main>
    <script>
      const clips = document.querySelectorAll('.bg-video');
      let clipIndex = 0;
      setInterval(() => {
        clips[clipIndex].classList.remove('active');
        clipIndex = (clipIndex + 1) % clips.length;
        clips[clipIndex].classList.add('active');
      }, 7000);

      const eyeSvg = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>';
      const eyeOffSvg = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>';

      document.getElementById('togglePass').addEventListener('click', () => {
        const field = document.getElementById('loginPass');
        const show = field.type === 'password';
        field.type = show ? 'text' : 'password';
        document.getElementById('togglePass').innerHTML = show ? eyeOffSvg : eyeSvg;
        document.getElementById('togglePass').setAttribute('aria-label', show ? 'Hide password' : 'Show password');
      });
    </script>
  </body>
</html>

