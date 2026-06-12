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
