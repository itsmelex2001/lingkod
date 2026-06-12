<?php
/**
 * setup.php — C.O.R.E. SDN First-time Setup
 *
 * Run this ONCE in your browser: http://localhost/sdn/setup.php
 * It seeds the default admin and staff accounts with properly hashed passwords.
 * DELETE or RENAME this file after running it for security.
 */

require_once 'api/config/db.php';

$users = [
    ['admin', 'admin123', 'admin'],
    ['staff', 'staff123', 'staff'],
];

$seeded = [];
$skipped = [];

foreach ($users as [$username, $password, $role]) {
    // Check if user already exists
    $check = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $check->execute([$username]);
    if ($check->fetch()) {
        $skipped[] = $username;
        continue;
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
    $stmt->execute([$username, $hash, $role]);
    $seeded[] = $username;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>SDN Setup</title>
  <style>
    body { font-family: 'Segoe UI', sans-serif; background: #f0faf5; display:flex; align-items:center; justify-content:center; min-height:100vh; margin:0; }
    .box { background:#fff; border-radius:12px; padding:40px; max-width:480px; width:90%; box-shadow:0 8px 30px rgba(0,0,0,.1); border-top:4px solid #2ecc71; }
    h2 { color:#1b5e20; margin-bottom:1rem; }
    .tag { display:inline-block; padding:3px 10px; border-radius:20px; font-size:.8rem; font-weight:600; margin:3px; }
    .seeded  { background:#e6f9ef; color:#1a7a40; }
    .skipped { background:#fff8e6; color:#9a5c00; }
    .warn { background:#fdecea; color:#b03028; padding:12px 16px; border-radius:8px; margin-top:1.2rem; font-size:.9rem; line-height:1.6; }
    .creds { background:#f5faf7; border:1px solid #d0e8d8; border-radius:8px; padding:14px; margin-top:1rem; font-size:.9rem; line-height:1.9; }
    code { background:#e0ece5; padding:2px 6px; border-radius:4px; font-family:monospace; }
  </style>
</head>
<body>
<div class="box">
  <h2>✅ SDN Setup Complete</h2>

  <?php if (!empty($seeded)): ?>
    <p><strong>Users created:</strong><br>
    <?php foreach ($seeded as $u): ?>
      <span class="tag seeded"><?= htmlspecialchars($u) ?></span>
    <?php endforeach; ?></p>
  <?php endif; ?>

  <?php if (!empty($skipped)): ?>
    <p><strong>Already existed (skipped):</strong><br>
    <?php foreach ($skipped as $u): ?>
      <span class="tag skipped"><?= htmlspecialchars($u) ?></span>
    <?php endforeach; ?></p>
  <?php endif; ?>

  <div class="creds">
    <strong>Default Login Credentials:</strong><br>
    Admin &nbsp;→ username: <code>admin</code> &nbsp;/ password: <code>admin123</code><br>
    Staff &nbsp;&nbsp;→ username: <code>staff</code> &nbsp;/ password: <code>staff123</code>
  </div>

  <div class="warn">
    ⚠️ <strong>Security:</strong> Delete or rename <code>setup.php</code> after setup to prevent unauthorized re-use.
    <br><br>
    <a href="index.html" style="color:#1b5e20;font-weight:600;">→ Go to Homepage</a>
  </div>
</div>
</body>
</html>
