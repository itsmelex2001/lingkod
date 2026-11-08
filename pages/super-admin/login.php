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
                    $pdo->prepare("
                        INSERT INTO users (username, full_name, password, role, status, department, created_at)
                        VALUES ('superadmin', 'Master Administrator', :p, 'super_admin', 'active', 'Hospital Executive Board', NOW())
                    ")->execute(['p' => $hash]);

                    $user = [
                        'id' => $pdo->lastInsertId(),
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
    <title>Super Admin Login | MMWGH LINGKOD</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Outfit:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/sdn/public/css/super-admin/super-admin.css?v=<?= time() ?>">
    <style>
        .sa-login-container {
            display: flex;
            min-height: 100vh;
            width: 100vw;
            background: #ffffff;
        }

        .sa-login-hero {
            flex: 1.1;
            background: #15803d;
            color: #ffffff;
            padding: 56px 64px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            overflow: hidden;
        }

        .sa-login-hero::before {
            content: '';
            position: absolute;
            top: -100px;
            right: -100px;
            width: 400px;
            height: 400px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.05);
            pointer-events: none;
        }

        .sa-hero-header {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .sa-hero-logo {
            width: 52px;
            height: 52px;
            border-radius: 12px;
            background: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 6px;
            box-sizing: border-box;
        }

        .sa-hero-logo img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        .sa-hero-content {
            max-width: 520px;
            margin: 40px 0;
        }

        .sa-hero-title {
            font-family: 'Outfit', sans-serif;
            font-size: 2.3rem;
            font-weight: 800;
            line-height: 1.15;
            letter-spacing: -0.02em;
            margin: 0 0 16px 0;
        }

        .sa-hero-subtitle {
            font-size: 1.05rem;
            line-height: 1.5;
            color: #dcfce7;
            margin: 0 0 24px 0;
            font-weight: 500;
        }

        .sa-hero-features {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .sa-hero-feature-item {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 0.92rem;
            color: #f0fdf4;
        }

        .sa-hero-feature-item svg {
            color: #86efac;
            flex-shrink: 0;
        }

        .sa-hero-footer {
            font-size: 0.8rem;
            color: #bbf7d0;
            line-height: 1.5;
        }

        .sa-login-form-panel {
            flex: 0.9;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 48px 36px;
            background: #f8fafc;
        }

        .sa-form-box {
            width: 100%;
            max-width: 400px;
            background: #ffffff;
            padding: 36px 32px;
            border-radius: 18px;
            border: 1px solid var(--sa-border);
            box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.05);
        }

        .sa-form-header {
            margin-bottom: 24px;
        }

        .sa-form-header h2 {
            font-family: 'Outfit', sans-serif;
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--sa-text);
            margin: 0 0 6px 0;
        }

        .sa-form-header p {
            font-size: 0.86rem;
            color: var(--sa-muted);
            margin: 0;
        }

        .sa-alert-error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #dc2626;
            padding: 12px 14px;
            border-radius: 10px;
            font-size: 0.85rem;
            margin-bottom: 18px;
            font-weight: 500;
        }

        @media (max-width: 900px) {
            .sa-login-container {
                flex-direction: column;
            }
            .sa-login-hero {
                padding: 32px 24px;
            }
            .sa-login-form-panel {
                padding: 32px 20px;
            }
        }
    </style>
</head>
<body class="sa-body">
    <div class="sa-login-container">
        <!-- Hero Section with Institutional Expanded Acronym -->
        <div class="sa-login-hero">
            <div class="sa-hero-header">
                <div class="sa-hero-logo">
                    <img src="/sdn/public/assets/mmwgh_logo.png" alt="MMWGH Logo">
                </div>
                <div>
                    <h3 style="margin:0; font-family:'Outfit', sans-serif; font-size:1.15rem; font-weight:800; letter-spacing:0.02em;">MMWGH LINGKOD</h3>
                    <p style="margin:2px 0 0; font-size:0.75rem; color:#bbf7d0; text-transform:uppercase; letter-spacing:0.06em; font-weight:700;">Executive Super Admin Gateway</p>
                </div>
            </div>

            <div class="sa-hero-content">
                <h1 class="sa-hero-title">
                    MMWGH LINGKOD
                </h1>
                <p class="sa-hero-subtitle">
                    Localized Integrated Network for Governance, Knowledge, and Operation in Digital Healthcare
                </p>

                <div class="sa-hero-features">
                    <div class="sa-hero-feature-item">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                        <span>Role-Based Access Control &amp; Granular Permissions</span>
                    </div>
                    <div class="sa-hero-feature-item">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M18.36 6.64a9 9 0 1 1-12.73 0"></path><line x1="12" y1="2" x2="12" y2="12"></line></svg>
                        <span>Full Hospital Maintenance Lockdown Control</span>
                    </div>
                    <div class="sa-hero-feature-item">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><ellipse cx="12" cy="5" rx="9" ry="3"></ellipse><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"></path><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"></path></svg>
                        <span>Automated SQL Database Dump &amp; Archival Engine</span>
                    </div>
                </div>
            </div>

            <div class="sa-hero-footer">
                Mariveles Mental Wellness and General Hospital &bull; Department of Health &bull; Republic of the Philippines
            </div>
        </div>

        <!-- Login Form Panel -->
        <div class="sa-login-form-panel">
            <div class="sa-form-box">
                <div class="sa-form-header">
                    <h2>Super Admin Sign In</h2>
                    <p>Enter your executive credentials to access the master control portal.</p>
                </div>

                <?php if ($error): ?>
                    <div class="sa-alert-error">
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

                    <button type="submit" class="sa-btn sa-btn-primary" style="width:100%; padding:12px; font-size:0.95rem; margin-top:10px;">
                        Authenticate &amp; Enter Portal
                    </button>
                </form>
            </div>
        </div>
    </div>

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

