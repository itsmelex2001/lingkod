<?php
session_start();

$roleParam = $_GET['role'] ?? '';

// Prevent cache to avoid showing dashboard if user goes back after logout
header('Cache-Control: no-cache, no-store, must-revalidate, private');
header('Pragma: no-cache');
header('Expires: 0');

// If user is logged in, redirect to appropriate dashboard
if (!empty($_SESSION['user'])) {
    $dest = strtolower(trim($_SESSION['role'] ?? '')) === 'admin'
        ? '/sdn/pages/admin-dashboard.php'
        : '/sdn/pages/staff-dashboard.php';
    header("Location: $dest");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/../api/config/db.php';

    $input    = trim($_POST['loginUser'] ?? '');
    $password = $_POST['loginPass'] ?? '';

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
                $error = 'Access Denied: Invalid username or password.';
            } elseif ($user['status'] !== 'active') {
                $error = 'Your account is inactive. Please contact the administrator.';
            } else {
                $storedPass  = $user['password'];
                $isHashed    = str_starts_with($storedPass, '$2y$');
                $passwordOk  = false;

                if ($isHashed) {
                    // Modern bcrypt hash
                    $passwordOk = password_verify($password, $storedPass);

                    // Support admin password variations (with or without trailing period, and legacy fallback)
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
                    // Plain text or MD5 — check then auto-upgrade
                    $passwordOk = ($storedPass === $password)
                               || ($storedPass === md5($password));

                    if ($passwordOk) {
                        // ✅ Upgrade to bcrypt on the spot
                        $newHash = password_hash($password, PASSWORD_BCRYPT);
                        $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")
                            ->execute([$newHash, $user['id']]);
                    }
                }

                if (!$passwordOk) {
                    $error = 'Access Denied: Invalid username or password.';
                } elseif ($roleParam === 'admin' && strtolower(trim($user['role'])) !== 'admin') {
                    $error = 'Access Denied: Please use the Medical Staff Portal for your account.';
                } elseif ($roleParam === 'staff' && strtolower(trim($user['role'])) !== 'staff') {
                    $error = 'Access Denied: Please use the Administrator Portal for your account.';
                } else {
                    // ✅ Valid login
                    $pdo->prepare("
                        UPDATE users
                        SET last_login = NOW(), login_count = login_count + 1
                        WHERE id = :id
                    ")->execute([':id' => $user['id']]);

                    session_regenerate_id(true);
                    $_SESSION['user']       = $user['username'];
                    $_SESSION['user_id']    = $user['id'];
                    $_SESSION['username']   = $user['username'];
                    $_SESSION['full_name']  = $user['full_name'] ?: $user['username'];
                    $_SESSION['role']       = $user['role'];
                    $_SESSION['department'] = $user['department'] ?? '';

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
    <title>Login | LINKOD</title>
    <!-- Preconnect to Google Fonts for performance -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@400;500;700;800&display=swap" rel="stylesheet">
    
    <!-- Compiled Svelte CSS -->
    <link rel="stylesheet" crossorigin href="/sdn/dist/assets/main.css">
    <!-- Injected Minimalist Redesign CSS Overrides -->
    <style>
      .login-wrapper {
        justify-content: flex-end !important;
        padding-right: 12% !important;
      }

      .login-overlay {
        background: linear-gradient(135deg, rgba(26, 59, 46, 0.75) 0%, rgba(28, 63, 52, 0.75) 100%) !important;
      }
      @media (max-width: 960px) {
        .login-wrapper {
          justify-content: center !important;
          padding-right: 0 !important;
        }
      }
      .login-card-container {
        background: rgba(255, 255, 255, 0.35) !important; /* Much more transparent glassmorphism */
        backdrop-filter: blur(14px) !important;
        -webkit-backdrop-filter: blur(14px) !important;
        width: 100% !important;
        max-width: 440px !important;
        padding: 3rem 2.5rem !important;
        border-radius: 20px !important;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.3) !important;
        border: 1px solid rgba(255, 255, 255, 0.8) !important;
      }
      .brand-showcase h1 {
        font-size: clamp(4.5rem, 8vw, 7.5rem) !important;
        margin-bottom: 0px !important;
        font-family: 'Inter', sans-serif !important;
      }
      .linkod-brush {
        width: 700px !important;
        margin-top: -10px !important;
        margin-bottom: 15px !important;
      }
      .brand-showcase p {
        font-size: 2.2rem !important;
      }
      .form-header h2 {
        font-family: 'Inter', sans-serif !important;
        font-size: 1.6rem !important;
        color: #ffffff !important;
        font-weight: 800 !important;
        letter-spacing: -0.02em !important;
      }
      .form-header p {
        color: #e2e8f0 !important;
        font-weight: 500 !important;
        font-size: 0.85rem !important;
      }
      .form-logo {
        width: 75px !important;
        margin-bottom: 1rem !important;
      }
      input[type="text"], input[type="password"] {
        border: 1.5px solid rgba(255,255,255,0.7) !important;
        border-radius: 10px !important;
        background-color: transparent !important;
        padding: 0.9rem 2.5rem 0.9rem 2.8rem !important; /* space for left and right icons */
        transition: all 0.3s ease !important;
        color: #ffffff !important;
        font-size: 0.95rem !important;
        background-repeat: no-repeat !important;
        background-position: 12px center !important;
        background-size: 18px !important;
      }
      input[type="text"] {
        /* User icon */
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23e2e8f0' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2'/%3E%3Ccircle cx='12' cy='7' r='4'/%3E%3C/svg%3E") !important;
      }
      input[type="password"] {
        /* Lock icon */
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23e2e8f0' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Crect x='3' y='11' width='18' height='11' rx='2' ry='2'/%3E%3Cpath d='M7 11V7a5 5 0 0 1 10 0v4'/%3E%3C/svg%3E") !important;
      }
      input[type="text"]::placeholder, input[type="password"]::placeholder {
        color: #cbd5e1 !important;
        opacity: 0.8 !important;
      }
      input[type="text"]:focus, input[type="password"]:focus {
        border-color: #10b981 !important;
        background-color: rgba(255,255,255,0.3) !important;
        box-shadow: 0 4px 15px rgba(16, 185, 129, 0.1) !important;
      }
      .btn-submit {
        background: #115e29 !important;
        border-radius: 10px !important;
        font-family: 'Inter', sans-serif !important;
        font-weight: 700 !important;
        font-size: 1rem !important;
        text-transform: uppercase !important;
        letter-spacing: 0.05em !important;
        box-shadow: 0 8px 15px rgba(17, 94, 41, 0.25) !important;
        padding: 1rem !important;
        margin-top: 1.5rem !important;
        border: none !important;
        color: white !important;
      }
      .btn-submit:hover {
        transform: translateY(-2px) !important;
        box-shadow: 0 12px 20px rgba(17, 94, 41, 0.4) !important;
        background: #0d4a20 !important;
      }
      .btn-back {
        color: #e2e8f0 !important;
        background: transparent !important;
        font-weight: 500 !important;
        font-size: 0.9rem !important;
      }
      .btn-back:hover {
        color: #ffffff !important;
        background: transparent !important;
        transform: translateX(-3px) !important;
      }
      .forgot-link {
        color: #86efac !important;
        font-weight: 700 !important;
        font-size: 0.8rem !important;
      }
      .forgot-link:hover {
        color: #4ade80 !important;
        text-decoration: underline !important;
      }
      .forgot-row {
        display: none !important;
      }
      /* Ensure labels are slightly darker for readability on the gray glass */
      label {
        color: #f1f5f9 !important;
        font-weight: 600 !important;
      }
      .btn-toggle-pass {
        color: #e2e8f0 !important;
      }
      .btn-toggle-pass:hover {
        color: #ffffff !important;
      }
    </style>
  </head>
  <body>
    <!-- Svelte Mount Point -->
    <div id="login-app" 
         data-error="<?= htmlspecialchars($error) ?>" 
         data-input="<?= htmlspecialchars($_POST['loginUser'] ?? '') ?>"
         data-role="<?= htmlspecialchars($roleParam) ?>"></div>
    
    <!-- Compiled Svelte JS -->
    <script type="module" crossorigin src="/sdn/dist/assets/main.js"></script>
  </body>
</html>
