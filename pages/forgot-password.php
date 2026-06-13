<!doctype html>
<?php
$role = $_GET['role'] ?? '';
$loginUrl = "/pages/login.php" . ($role ? "?role=" . urlencode($role) : "");
?>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Forgot Password | LINKOD</title>
    <link rel="stylesheet" href="/public/css/global.css" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/public/css/forgot-password.css" />
  </head>
  <body>
    <div class="login-box">
      <div style="text-align: center; margin-bottom: 30px;">
        <h2 style="color: #1b5e20; margin: 0 0 10px 0;">Recover Account</h2>
        <p style="color: #666; margin: 0; font-size: 0.9rem;">Enter your details to receive a recovery code.</p>
      </div>

      <div id="step1">
        <div class="form-group">
          <label>Username</label>
          <input type="text" id="recUser" class="form-control" placeholder="Enter username">
        </div>
        <div class="form-group">
          <label>Mobile Number</label>
          <input type="text" id="recMobile" class="form-control" placeholder="Enter registered mobile number">
        </div>
        <button class="btn-primary" onclick="requestCode()">Send Recovery Code</button>
        <div style="text-align: center; margin-top: 15px;">
          <a href="<?= htmlspecialchars($loginUrl) ?>" style="color: #38a169; font-size: 0.85rem; text-decoration: none;">Back to Login</a>
        </div>
      </div>

      <div id="step2" style="display: none;">
        <div class="form-group">
          <label>Recovery Code (Sent via SMS)</label>
          <input type="text" id="recCode" class="form-control" placeholder="6-digit code">
        </div>
        <div class="form-group">
          <label>New Password</label>
          <input type="password" id="recPass" class="form-control" placeholder="Enter new password">
        </div>
        <button class="btn-primary" onclick="resetPassword()">Reset Password</button>
      </div>

    </div>

    <script src="/public/js/forgot-password-inline.js"></script>
  </body>
</html>
