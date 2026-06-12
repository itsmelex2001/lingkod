<?php
/**
 * api/recover.php
 * Handles forgot password flow (request code, verify, reset)
 */

require_once __DIR__ . '/config/cors.php';
require_once __DIR__ . '/config/db.php';

$method = $_SERVER['REQUEST_METHOD'];
if ($method !== 'POST') respondErr('Method not allowed.', 405);

$body = getBody();
$action = $body['action'] ?? '';

// 1. Request Code
if ($action === 'request_code') {
    $username = trim($body['username'] ?? '');
    $mobile = trim($body['mobile'] ?? '');

    if (!$username || !$mobile) respondErr('Username and Mobile Number are required.');

    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND mobile_number = ? AND status = 'active'");
    $stmt->execute([$username, $mobile]);
    $user = $stmt->fetch();

    if (!$user) {
        // Generic message for security
        respondErr('If these details are correct, a recovery code has been sent.');
    }

    $code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
    $pdo->prepare("UPDATE users SET recovery_code = ? WHERE id = ?")->execute([$code, $user['id']]);

    addLog($pdo, 'info', 'Password Recovery Requested', 'System', "Recovery code generated for $username.");

    // MOCK SMS SENDING
    respondOk([
        'message' => 'Recovery code sent successfully!', 
        'mock_code' => $code // Sending code in response ONLY for simulation purposes
    ]);
}

// 2. Reset Password
if ($action === 'reset_password') {
    $username = trim($body['username'] ?? '');
    $code = trim($body['code'] ?? '');
    $newPassword = $body['new_password'] ?? '';

    if (!$username || !$code || !$newPassword) respondErr('All fields are required.');

    $stmt = $pdo->prepare("SELECT id, recovery_code FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if (!$user || $user['recovery_code'] !== $code) {
        respondErr('Invalid recovery code or username.');
    }

    $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
    $pdo->prepare("UPDATE users SET password = ?, recovery_code = NULL WHERE id = ?")->execute([$hashed, $user['id']]);

    addLog($pdo, 'info', 'Password Reset Successful', 'System', "Password was reset for $username.");

    respondOk(['message' => 'Password reset successfully. You can now log in.']);
}

respondErr("Unknown action '$action'.");
