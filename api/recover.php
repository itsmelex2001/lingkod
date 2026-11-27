<?php
/**
 * api/recover.php
 * Handles forgot password flow (request code, verify, reset)
 */

require_once __DIR__ . '/config/cors.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/services/SmsService.php';

$method = $_SERVER['REQUEST_METHOD'];
if ($method !== 'POST') respondErr('Method not allowed.', 405);

$body = getBody();
$action = $body['action'] ?? '';

// Ensure schema columns for hashed expiring recovery codes & server-side IP rate limiting
if (!dbIsPgsql()) {
    try {
        $uCols = $pdo->query("SHOW COLUMNS FROM `users`")->fetchAll(PDO::FETCH_COLUMN);
        @$pdo->exec("ALTER TABLE `users` MODIFY COLUMN `recovery_code` VARCHAR(255) NULL");
        if (!in_array('recovery_code_expires_at', $uCols, true)) {
            @$pdo->exec("ALTER TABLE `users` ADD COLUMN `recovery_code_expires_at` DATETIME NULL");
        }
        if (!in_array('recovery_attempts', $uCols, true)) {
            @$pdo->exec("ALTER TABLE `users` ADD COLUMN `recovery_attempts` TINYINT UNSIGNED NOT NULL DEFAULT 0");
        }
        @$pdo->exec("
            CREATE TABLE IF NOT EXISTS `auth_rate_limits` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `bucket_key` VARCHAR(191) NOT NULL,
                `created_at` INT UNSIGNED NOT NULL,
                INDEX `idx_bucket_time` (`bucket_key`, `created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    } catch (Throwable $ignoreSchema) {}
}

// Persistent IP-based rate limiting: max 5 attempts per 15 minutes (not bypassable via cookie reset)
$clientIp = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$now = time();
$windowStart = $now - 900;
$bucketKey = 'recover:' . hash('sha256', $clientIp);
try {
    $pdo->prepare("DELETE FROM auth_rate_limits WHERE created_at < ?")->execute([$windowStart]);
    $cntStmt = $pdo->prepare("SELECT COUNT(*) FROM auth_rate_limits WHERE bucket_key = ? AND created_at >= ?");
    $cntStmt->execute([$bucketKey, $windowStart]);
    if ((int)$cntStmt->fetchColumn() >= 5) {
        respondErr('Too many recovery attempts. Please wait 15 minutes before trying again.', 429);
    }
    $pdo->prepare("INSERT INTO auth_rate_limits (bucket_key, created_at) VALUES (?, ?)")->execute([$bucketKey, $now]);
} catch (Throwable $ignoreRl) {}

// 1. Request Code
if ($action === 'request_code') {
    $username = trim($body['username'] ?? '');
    $mobile = trim($body['mobile'] ?? '');

    if (!$username || !$mobile) respondErr('Username and Mobile Number are required.', 422);

    $stmt = $pdo->prepare("SELECT id, mobile_number FROM users WHERE username = ? AND mobile_number = ? AND status = 'active' LIMIT 1");
    $stmt->execute([$username, $mobile]);
    $user = $stmt->fetch();

    $genericMsg = 'If these details match an active account, a recovery code has been sent to the registered mobile number.';
    if (!$user) {
        respondOk(['message' => $genericMsg]);
    }

    $code = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $codeHash = password_hash($code, PASSWORD_DEFAULT);
    $codeExpiresAt = date('Y-m-d H:i:s', time() + 600);
    $pdo->prepare("
        UPDATE users
        SET recovery_code = ?,
            recovery_code_expires_at = ?,
            recovery_attempts = 0
        WHERE id = ?
    ")->execute([$codeHash, $codeExpiresAt, $user['id']]);

    addLog($pdo, 'info', 'Password Recovery Requested', 'System', "Recovery code generated for $username.");

    try {
        SmsService::sendSms($mobile, "LINGKOD MMWGH: Your recovery code is {$code}. Valid for 10 minutes. Do not share.");
    } catch (Throwable $e) {
        error_log('[recover.php] SMS send error: ' . $e->getMessage());
    }

    respondOk(['message' => $genericMsg]);
}

// 2. Reset Password
if ($action === 'reset_password') {
    $username = trim($body['username'] ?? '');
    $code = trim($body['code'] ?? '');
    $newPassword = (string)($body['new_password'] ?? '');

    if (!$username || !$code || !$newPassword) respondErr('All fields are required.', 422);

    $strongPass = strlen($newPassword) >= 8
        && preg_match('/[A-Z]/', $newPassword)
        && preg_match('/[a-z]/', $newPassword)
        && preg_match('/[0-9]/', $newPassword)
        && preg_match('/[^A-Za-z0-9]/', $newPassword);
    if (!$strongPass) {
        respondErr('Password must be at least 8 characters and include uppercase, lowercase, number, and special character.', 422);
    }

    $stmt = $pdo->prepare("
        SELECT id, recovery_code, recovery_code_expires_at, recovery_attempts
        FROM users
        WHERE username = ? AND status = 'active'
        LIMIT 1
    ");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    $isExpired = !$user
        || empty($user['recovery_code'])
        || empty($user['recovery_code_expires_at'])
        || strtotime((string)$user['recovery_code_expires_at']) < time();

    if ($isExpired || (int)($user['recovery_attempts'] ?? 0) >= 5) {
        if ($user) {
            $pdo->prepare("UPDATE users SET recovery_code = NULL, recovery_code_expires_at = NULL, recovery_attempts = 0 WHERE id = ?")
                ->execute([$user['id']]);
        }
        respondErr('Invalid or expired recovery code. Please request a new code.', 422);
    }

    if (!password_verify($code, (string)$user['recovery_code'])) {
        $nextAttempts = (int)($user['recovery_attempts'] ?? 0) + 1;
        if ($nextAttempts >= 5) {
            $pdo->prepare("UPDATE users SET recovery_code = NULL, recovery_code_expires_at = NULL, recovery_attempts = 0 WHERE id = ?")
                ->execute([$user['id']]);
        } else {
            $pdo->prepare("UPDATE users SET recovery_attempts = ? WHERE id = ?")
                ->execute([$nextAttempts, $user['id']]);
        }
        respondErr('Invalid recovery code or username.', 422);
    }

    $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
    $pdo->prepare("
        UPDATE users
        SET password = ?, recovery_code = NULL, recovery_code_expires_at = NULL, recovery_attempts = 0
        WHERE id = ?
    ")->execute([$hashed, $user['id']]);

    addLog($pdo, 'info', 'Password Reset Successful', 'System', "Password was reset for $username.");

    respondOk(['message' => 'Password reset successfully. You can now log in.']);
}

respondErr("Unknown action '$action'.");
