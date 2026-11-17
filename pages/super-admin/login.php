<?php
session_start();
header('Cache-Control: no-cache, no-store, must-revalidate, private');
header('Pragma: no-cache');
header('Expires: 0');

// If already authenticated as super_admin, redirect to dashboard
if (!empty($_SESSION['user']) && !empty($_SESSION['role']) && strtolower(trim($_SESSION['role'])) === 'super_admin') {
    header("Location: /sdn/pages/super-admin/dashboard.php");
    exit;
}

$error = '';
$postedUser = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/../../api/config/db.php';

    $input = trim($_POST['sa_username'] ?? '');
    $password = $_POST['sa_password'] ?? '';
    $postedUser = $input;

    if ($input === '' || $password === '') {
        $error = 'Please provide both username and password.';
    } else {
        try {
            // Verify from users table with role 'super_admin'
            $stmt = $pdo->prepare("
                SELECT id, username, password, role, status, full_name
                FROM users
                WHERE username = :u AND LOWER(role) = 'super_admin'
                LIMIT 1
            ");
            $stmt->execute(['u' => $input]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            $authenticated = false;

            if ($user) {
                if (password_verify($password, $user['password'])) {
                    $authenticated = true;
                }
            } else {
                // Initial bootstrap fallback: if no super_admin account exists yet, permit default master setup
                $count = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE LOWER(role) = 'super_admin'")->fetchColumn();
                if ($count === 0 && $input === 'superadmin' && $password === 'SuperAdmin@2026') {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $existingStmt = $pdo->prepare("SELECT id FROM users WHERE username = 'superadmin' LIMIT 1");
                    $existingStmt->execute();
                    $existingId = $existingStmt->fetchColumn();

                    if ($existingId) {
                        $pdo->prepare("
                            UPDATE users
                            SET full_name = 'Master Administrator', password = :p, role = 'super_admin', status = 'active', department = 'Hospital Executive Board'
                            WHERE id = :id
                        ")->execute(['p' => $hash, 'id' => $existingId]);
                        $userId = $existingId;
                    } else {
                        $pdo->prepare("
                            INSERT INTO users (username, full_name, password, role, status, department, created_at)
                            VALUES ('superadmin', 'Master Administrator', :p, 'super_admin', 'active', 'Hospital Executive Board', NOW())
                        ")->execute(['p' => $hash]);
                        $userId = $pdo->lastInsertId();
                    }

                    $user = [
                        'id' => $userId,
                        'username' => 'superadmin',
                        'role' => 'super_admin',
                        'full_name' => 'Master Administrator',
                        'status' => 'active'
                    ];
                    $authenticated = true;
                }
            }

            if ($authenticated && $user) {
                if (($user['status'] ?? 'active') !== 'active') {
                    $error = 'This Super Administrator account has been suspended.';
                } else {
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user'] = $user['username'];
                    $_SESSION['role'] = 'super_admin';
                    $_SESSION['full_name'] = $user['full_name'];

                    // Log login to system_logs
                    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
                    try {
                        $pdo->prepare("
                            INSERT INTO system_logs (level, action, user, role, details, ip_address)
                            VALUES ('info', 'Super Admin Login', :u, 'super_admin', 'Successful authentication to Super Admin Portal.', :ip)
                        ")->execute(['u' => $user['username'], 'ip' => $ip]);
                    } catch (Exception $e) {}

                    header("Location: /sdn/pages/super-admin/dashboard.php");
                    exit;
                }
            } else {
                $error = 'Invalid Super Admin credentials.';
            }
        } catch (Exception $e) {
            $error = 'Database authentication error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Master Admin Login | MMWGH LINGKOD</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Outfit:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/sdn/public/css/super-admin/super-admin.css?v=<?= time() ?>">
    <style>
        .sa-login-gateway {
            min-height: 100vh;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            background: linear-gradient(rgba(17, 24, 39, 0.62), rgba(17, 24, 39, 0.72)), url('/sdn/public/assets/bg_login.jpg') center/cover no-repeat;
            box-sizing: border-box;
        }

        .sa-form-box {
            width: 100%;
            max-width: 28rem;
            background: #ffffff;
            padding: 2rem;
            border-radius: 0.75rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            box-sizing: border-box;
        }

        .sa-gateway-logo {
            display: block;
            width: 48px;
            height: 48px;
            object-fit: contain;
            margin: 0 auto 1rem;
        }

        .sa-form-header {
            text-align: center;
            margin-bottom: 1.5rem;
        }

        .sa-form-header h2 {
            font-family: 'Outfit', sans-serif;
            font-size: 1.35rem;
            font-weight: 700;
            color: #111827;
            margin: 0 0 0.35rem 0;
            letter-spacing: -0.02em;
        }

        .sa-form-header p {
            font-size: 0.85rem;
            color: #6b7280;
            margin: 0;
        }

        .sa-alert-error {
            background: #fef2f2;
            color: #b91c1c;
            box-shadow: inset 0 0 0 1px rgba(220, 38, 38, 0.2);
            padding: 0.75rem;
            border-radius: 0.375rem;
            font-size: 0.875rem;
            margin-bottom: 1.25rem;
            font-weight: 500;
        }

        .sa-btn-auth {
            width: 100%;
            padding: 0.625rem 1rem;
            background: #111827;
            color: #ffffff;
            border: none;
            border-radius: 0.5rem;
            font-family: inherit;
            font-size: 0.875rem;
            font-weight: 500;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            cursor: pointer;
            margin-top: 0.5rem;
            transition: background-color 0.15s ease;
        }

        .sa-btn-auth:hover {
            background: #000000;
        }

        .sa-btn-auth:focus-visible {
            outline: 2px solid #111827;
            outline-offset: 2px;
        }
    </style>
</head>
<body class="sa-body">
    <main class="sa-login-gateway">
        <div class="sa-form-box">
            <img src="/sdn/public/assets/mmwgh_logo.png" alt="MMWGH Logo" class="sa-gateway-logo">
            <div class="sa-form-header">
                <h2>Master Admin Sign In</h2>
                <p>Enter your executive credentials to access the master control portal.</p>
            </div>

            <?php if ($error): ?>
                <div class="sa-alert-error" role="alert">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="sa-form-group">
                    <label for="sa_username">Username</label>
                    <input type="text" id="sa_username" name="sa_username" class="sa-input" required autofocus value="<?= htmlspecialchars($postedUser) ?>" placeholder="Enter super admin username">
                </div>

                <div class="sa-form-group">
                    <label for="sa_password">Password</label>
                    <div style="position:relative; display:flex; align-items:center;">
                        <input type="password" id="sa_password" name="sa_password" class="sa-input" required placeholder="Enter password" style="padding-right:42px;">
                        <button type="button" id="togglePassBtn" onclick="togglePasswordVisibility()" title="Show/Hide Password" aria-label="Toggle password visibility" style="position:absolute; right:10px; background:none; border:none; cursor:pointer; padding:6px; color:var(--sa-muted); display:flex; align-items:center; justify-content:center; border-radius:6px; transition:color 0.15s ease;">
                            <svg id="eyeIconShow" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                            <svg id="eyeIconHide" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none;">
                                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                                <line x1="1" y1="1" x2="23" y2="23"></line>
                            </svg>
                        </button>
                    </div>
                </div>

                <button type="submit" class="sa-btn-auth">
                    Authenticate
                </button>
            </form>
        </div>
    </main>

    <script>
        function togglePasswordVisibility() {
            const passInput = document.getElementById('sa_password');
            const eyeShow = document.getElementById('eyeIconShow');
            const eyeHide = document.getElementById('eyeIconHide');
            if (!passInput || !eyeShow || !eyeHide) return;

            if (passInput.type === 'password') {
                passInput.type = 'text';
                eyeShow.style.display = 'none';
                eyeHide.style.display = 'block';
            } else {
                passInput.type = 'password';
                eyeShow.style.display = 'block';
                eyeHide.style.display = 'none';
            }
        }
    </script>
</body>
</html>

