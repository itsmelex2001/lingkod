<?php
/**
 * api/auth.php
 *
 * GET  ?action=check   → returns current session info
 * GET  ?action=logout  → destroys session
 * POST {action, username, password} → attempts login
 * 
 * NEW: Tracks login/logout in login_history table
 */

session_start();
require_once __DIR__ . '/config/cors.php';
require_once __DIR__ . '/config/db.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? (getBody()['action'] ?? '');
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

// ── CHECK SESSION ────────────────────────────────────────────────────────────
if ($action === 'check') {
    if (!empty($_SESSION['user'])) {
        respondOk(['loggedIn' => true, 'role' => $_SESSION['role'], 'user' => $_SESSION['user']]);
    }
    respondOk(['loggedIn' => false]);
}

// ── LOGOUT ───────────────────────────────────────────────────────────────────
if ($action === 'logout') {
    $user = $_SESSION['user'] ?? 'Unknown';
    
    // Log logout to system_logs
    addLog($pdo, 'info', 'System Logout', $user, "$user logged out.");
    
    // Update login_history to record logout
    $stmt = $pdo->prepare(
        "UPDATE login_history SET logout_time = NOW(), status = 'expired' 
         WHERE username = ? AND logout_time IS NULL 
         ORDER BY login_time DESC LIMIT 1"
    );
    $stmt->execute([$user]);
    
    // Destroy session completely
    session_unset();
    session_destroy();
    
    // Send cache prevention headers
    header('Cache-Control: no-cache, no-store, must-revalidate, private');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    respondOk(['message' => 'Logged out.']);
}

// ── LOGIN ────────────────────────────────────────────────────────────────────
if ($method === 'POST') {
    $body     = getBody();
    $username = trim($body['username'] ?? '');
    $password = $body['password'] ?? '';

    if ($username === '' || $password === '') {
        respondErr('Username and password are required.');
    }

    $stmt = $pdo->prepare("SELECT id, username, password, role, status, full_name, department FROM users WHERE username = ? LIMIT 1");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        
        // Check if account is active
        if ($user['status'] === 'inactive') {
            // Log failed attempt for inactive account
            logLogin($pdo, $user['id'], $username, $user['role'], $ip, 'failed');
            addLog($pdo, 'warning', 'Login Attempt - Inactive Account', $username,
                   "$username attempted login but account is inactive (IP: $ip).");
            respondErr('This account has been deactivated.', 401);
        }
        
        // Log successful login
        logLogin($pdo, $user['id'], $username, $user['role'], $ip, 'success');
        
        // Regenerate session ID to prevent fixation
        session_regenerate_id(true);
        $_SESSION['user'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['department'] = $user['department'];

        // Update user's last_login and login_count
        $updateStmt = $pdo->prepare(
            "UPDATE users SET last_login = NOW(), login_count = login_count + 1 WHERE id = ?"
        );
        $updateStmt->execute([$user['id']]);

        addLog($pdo, 'info', 'System Login', $user['username'],
               "{$user['username']} successfully logged into the portal from $ip.");

        respondOk(['role' => $user['role']]);
    }

    // Log failed login attempt
    logLogin($pdo, null, $username, 'staff', $ip, 'failed');
    
    addLog($pdo, 'warning', 'Failed Login Attempt', 'Unknown',
           "Unrecognized credentials submitted for user '$username' from $ip.");

    respondErr('Invalid username or password.', 401);
}

// ── HELPER FUNCTION: Log login to login_history ────────────────────────────
function logLogin($pdo, $userId, $username, $role, $ipAddress, $status) {
    try {
        $stmt = $pdo->prepare(
            "INSERT INTO login_history (user_id, username, role, login_time, ip_address, status)
             VALUES (?, ?, ?, NOW(), ?, ?)"
        );
        $stmt->execute([$userId, $username, $role, $ipAddress, $status]);
    } catch (Exception $e) {
        // Silently fail - don't interrupt login process
        error_log("Login history error: " . $e->getMessage());
    }
}

respondErr('Bad request.', 400);
