<?php
session_start();

// Single hidden URL for both staff and administrators.
// Role is taken from the account, not from the query string.

header('Cache-Control: no-cache, no-store, must-revalidate, private');
header('Pragma: no-cache');
header('Expires: 0');

// If accessing with staff access key or staff/admin intent, redirect to the obfuscated URL
if (isset($_GET['access']) || isset($_GET['staff']) || (isset($_GET['role']) && in_array(strtolower($_GET['role']), ['staff', 'admin']))) {
    header('Location: /sdn/pages/portal@admin$staff_2026.php');
    exit;
}

if (isset($_GET['logout']) || isset($_GET['force'])) {
    $_SESSION = array();
    session_destroy();
    session_start();
    header('Location: /sdn/pages/login.php');
    exit;
}

if (!empty($_SESSION['user'])) {
    $role = strtolower(trim($_SESSION['role'] ?? ''));
    if ($role === 'admin') {
        header('Location: /sdn/pages/admin-dashboard.php');
        exit;
    } elseif ($role === 'staff' || $role === 'doctor' || $role === 'nurse') {
        header('Location: /sdn/pages/staff-dashboard.php');
        exit;
    } elseif ($role === 'patient') {
        header('Location: /sdn/pages/patient-dashboard.php');
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
            // Check users table first (by username)
            $stmt = $pdo->prepare("
                SELECT id, username, password, role, status, full_name, department
                FROM users
                WHERE username = :input
                LIMIT 1
            ");
            $stmt->execute([':input' => $input]);
            $user = $stmt->fetch();

            if ($user && in_array(strtolower($user['role']), ['admin', 'staff', 'doctor', 'nurse'])) {
                $error = 'Access Denied: Staff and Administrators must sign in through the designated secure staff URL.';
            } elseif ($user && $user['role'] === 'patient') {
                if ($user['status'] !== 'active') {
                    $error = 'Your patient account is inactive or not yet verified. Please check your email.';
                } elseif (!password_verify($password, $user['password'])) {
                    $error = 'Access Denied: Invalid email/username or password.';
                } else {
                    $pdo->prepare("UPDATE users SET last_login = NOW(), login_count = login_count + 1 WHERE id = :id")
                        ->execute([':id' => $user['id']]);

                    // Retrieve linked patient record
                    $patStmt = $pdo->prepare("SELECT * FROM patients WHERE id = :pid OR user_id = :uid LIMIT 1");
                    $patStmt->execute([':pid' => $user['patient_id'] ?? null, ':uid' => $user['id']]);
                    $pat = $patStmt->fetch();

                    session_regenerate_id(true);
                    $_SESSION['patient_logged_in'] = true;
                    $_SESSION['patient_id']        = $pat['patient_id'] ?? $user['username'];
                    $_SESSION['patient_db_id']     = $pat['id'] ?? ($user['patient_id'] ?? null);
                    $_SESSION['patient_name']      = $pat['full_name'] ?? ($user['full_name'] ?: $user['username']);
                    $_SESSION['patient_email']     = $pat['email'] ?? ($user['email'] ?? $input);
                    $_SESSION['user_id']           = $user['id'];
                    $_SESSION['role']              = 'patient';
                    $_SESSION['user']              = $user['username'];

                    $redirect = !empty($_GET['redirect']) ? $_GET['redirect'] : '/sdn/pages/patient-dashboard.php';
                    header("Location: $redirect");
                    exit;
                }
            } else {
                // Check direct patients table as fallback
                $pStmt = $pdo->prepare("SELECT * FROM patients WHERE email = :input OR patient_id = :input LIMIT 1");
                $pStmt->execute([':input' => $input]);
                $patient = $pStmt->fetch();

                if ($patient && !empty($patient['password']) && password_verify($password, $patient['password'])) {
                    if (isset($patient['email_verified']) && (int)$patient['email_verified'] === 0) {
                        $error = 'Please verify your email address before signing in.';
                    } else {
                        // Link or create user record
                        try {
                            $checkU = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
                            $checkU->execute([$patient['email']]);
                            $existingU = $checkU->fetch();
                            if ($existingU) {
                                $uid = $existingU['id'];
                            } else {
                                $uIns = $pdo->prepare("
                                    INSERT INTO users (username, email, password, role, status, patient_id, full_name, created_at)
                                    VALUES (?, ?, ?, 'patient', 'active', ?, ?, NOW())
                                ");
                                $uIns->execute([
                                    $patient['patient_id'] ?: 'patient_' . $patient['id'],
                                    $patient['email'],
                                    $patient['password'],
                                    $patient['id'],
                                    $patient['full_name']
                                ]);
                                $uid = (int)$pdo->lastInsertId();
                            }
                            $pdo->prepare("UPDATE patients SET user_id = ? WHERE id = ?")->execute([$uid, $patient['id']]);
                        } catch (Exception $e) {
                            $uid = null;
                        }

                        session_regenerate_id(true);
                        $_SESSION['patient_logged_in'] = true;
                        $_SESSION['patient_id']        = $patient['patient_id'];
                        $_SESSION['patient_db_id']     = $patient['id'];
                        $_SESSION['patient_name']      = $patient['full_name'];
                        $_SESSION['patient_email']     = $patient['email'];
                        $_SESSION['user_id']           = $uid;
                        $_SESSION['role']              = 'patient';
                        $_SESSION['user']              = $patient['patient_id'];

                        $redirect = !empty($_GET['redirect']) ? $_GET['redirect'] : '/sdn/pages/patient-dashboard.php';
                        header("Location: $redirect");
                        exit;
                    }
                } else {
                    $error = 'Access Denied: Invalid credentials.';
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
    <title>Portal Sign In | LINGKOD</title>
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
        padding: 2.5rem 6vw;
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
          linear-gradient(105deg, rgba(5, 42, 18, 0.66) 0%, rgba(7, 48, 20, 0.52) 50%, rgba(3, 30, 12, 0.7) 100%);
      }
      .login-layout {
        position: relative;
        z-index: 2;
        width: min(1040px, 100%);
        display: grid;
        grid-template-columns: minmax(0, 1fr) 400px;
        gap: clamp(2rem, 6vw, 5.5rem);
        align-items: center;
      }
      .brand {
        color: #f4faf6;
        max-width: 440px;
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
        max-width: 34ch;
        font-size: 1rem;
        line-height: 1.6;
        color: rgba(244, 250, 246, 0.82);
      }
      .brand-points {
        display: flex;
        flex-direction: column;
        gap: 0.7rem;
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
        padding: 1.35rem 1.45rem 1.25rem;
        border-radius: 16px;
        color: #123322;
        background: linear-gradient(165deg, rgba(255, 255, 255, 0.58) 0%, rgba(226, 240, 230, 0.42) 100%);
        border: 1px solid rgba(255, 255, 255, 0.5);
        box-shadow:
          inset 0 1px 0 rgba(255, 255, 255, 0.55),
          0 18px 40px rgba(6, 22, 12, 0.18);
        backdrop-filter: blur(18px) saturate(140%);
        -webkit-backdrop-filter: blur(18px) saturate(140%);
      }
      .panel-head {
        display: flex;
        align-items: center;
        gap: 0.85rem;
        margin-bottom: 1.25rem;
        padding-bottom: 1.15rem;
        border-bottom: 1px solid rgba(18, 51, 34, 0.12);
      }
      .panel-seal {
        width: 48px;
        height: 48px;
        object-fit: contain;
        flex-shrink: 0;
      }
      .panel-head h2 {
        margin: 0;
        font-size: 1.15rem;
        font-weight: 650;
        letter-spacing: -0.02em;
        color: #123322;
      }
      .panel-head p {
        margin: 0.15rem 0 0;
        font-size: 0.82rem;
        color: #3c5346;
      }
      .error-alert {
        display: flex;
        align-items: flex-start;
        gap: 0.5rem;
        background: #fbf6f6;
        border: 1px solid #eadcdc;
        color: #7a3e3e;
        padding: 0.7rem 0.8rem;
        border-radius: 10px;
        margin-bottom: 1rem;
        font-size: 0.82rem;
        line-height: 1.4;
      }
      .input-group { margin-bottom: 0.9rem; }
      label {
        display: block;
        margin-bottom: 0.38rem;
        font-size: 0.75rem;
        font-weight: 600;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        color: #3e5648;
      }
      .input-wrap { position: relative; }
      .input-wrap svg.field-icon {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: #6d8478;
        pointer-events: none;
      }
      input[type="text"], input[type="password"] {
        width: 100%;
        height: 46px;
        padding: 0 2.7rem;
        border: 1px solid #dce6e0;
        border-radius: 10px;
        background: linear-gradient(180deg, #ffffff 0%, #f7faf8 100%);
        color: #163024;
        font: 500 0.94rem 'Inter', sans-serif;
        outline: none;
        box-shadow: inset 0 1px 2px rgba(16, 40, 24, 0.04);
        transition: border-color 0.15s ease, box-shadow 0.15s ease;
      }
      input::placeholder { color: #8b9c92; font-weight: 400; }
      input:focus {
        border-color: #1a8a40;
        box-shadow: 0 0 0 3px rgba(37, 186, 80, 0.16);
        background: #ffffff;
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
        color: #5d7266;
        cursor: pointer;
        display: grid;
        place-items: center;
        padding: 0;
      }
      .btn-toggle-pass:hover { background: #eef5f0; color: #0d6b2e; }
      .btn-submit {
        width: 100%;
        height: 46px;
        margin-top: 0.35rem;
        border: none;
        border-radius: 10px;
        background: linear-gradient(180deg, #2cba58 0%, #0d7a32 100%);
        color: #ffffff;
        font: 600 0.92rem 'Inter', sans-serif;
        letter-spacing: 0.02em;
        cursor: pointer;
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.28), 0 8px 16px rgba(8, 70, 28, 0.22);
      }
      .btn-submit:hover { filter: brightness(1.04); }
      .btn-submit:active { transform: translateY(1px); }
      .panel-note {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.4rem;
        margin: 0.95rem 0 0;
        font-size: 0.78rem;
        font-weight: 600;
        color: #123322;
      }
      @media (max-width: 860px) {
        html, body { overflow: auto; }
        .login-shell { align-items: flex-start; padding: 1.5rem 1rem 2rem; }
        .login-layout { grid-template-columns: 1fr; gap: 1.5rem; }
        .brand { max-width: none; }
        .brand h1 { font-size: 1.7rem; }
        .brand-points { display: none; }
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
        <section class="brand">
          <div class="brand-mark">
            <img src="/sdn/public/assets/v3_lingkod_logo.png" alt="LINGKOD" />
          </div>
          <h1>Digital healthcare</h1>
          <p>Sign in to manage appointments, schedules, and daily hospital operations.</p>
          <ul class="brand-points">
            <li>
              <span class="point-icon">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
              </span>
              Appointments and schedules
            </li>
            <li>
              <span class="point-icon">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
              </span>
              Staff and administrator access
            </li>
          </ul>
        </section>

        <section class="panel" aria-label="Sign in">
          <div class="panel-head">
            <img src="/sdn/public/assets/mmwgh_logo.png" alt="MMWGH" class="panel-seal" />
            <div>
              <h2>Patient Portal Sign In</h2>
              <p>Sign in with your patient account</p>
            </div>
          </div>

          <?php if ($error !== ''): ?>
            <div class="error-alert" role="alert">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
              <span><?= htmlspecialchars($error) ?></span>
            </div>
          <?php endif; ?>

          <form method="POST" action="/sdn/pages/login.php<?= !empty($_GET['redirect']) ? '?redirect=' . urlencode($_GET['redirect']) : '' ?>" autocomplete="off">
            <div class="input-group">
              <label for="loginUser">Email or Patient ID</label>
              <div class="input-wrap">
                <svg class="field-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                  <circle cx="12" cy="7" r="4"></circle>
                </svg>
                <input type="text" id="loginUser" name="loginUser" placeholder="Email or PT-YYYY-NNN" value="<?= htmlspecialchars($postedUser) ?>" required autocomplete="username" />
              </div>
            </div>
            <div class="input-group">
              <label for="loginPass">Password</label>
              <div class="input-wrap">
                <svg class="field-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                  <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                  <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                </svg>
                <input type="password" id="loginPass" name="loginPass" placeholder="Enter your password" required autocomplete="current-password" />
                <button type="button" class="btn-toggle-pass" id="togglePass" aria-label="Show password">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                    <circle cx="12" cy="12" r="3"></circle>
                  </svg>
                </button>
              </div>
            </div>
            <button type="submit" class="btn-submit">Sign In to Patient Portal</button>
          </form>
          <div style="margin-top: 1rem; text-align: center; font-size: 0.85rem; color: #163024;">
            Don't have an account? <a href="/sdn/pages/register.php" style="color: #0d7a32; font-weight: 600; text-decoration: underline;">Create account</a>
          </div>
          <p class="panel-note">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
            Hospital Staff & Administrators must use their secure personnel portal link
          </p>
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

      document.getElementById('togglePass').addEventListener('click', () => {
        const field = document.getElementById('loginPass');
        const show = field.type === 'password';
        field.type = show ? 'text' : 'password';
        document.getElementById('togglePass').setAttribute('aria-label', show ? 'Hide password' : 'Show password');
      });
    </script>
  </body>
</html>
