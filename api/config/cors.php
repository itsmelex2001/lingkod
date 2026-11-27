<?php
/**
 * api/config/cors.php
 * Sets JSON + CORS headers and provides shared helper functions.
 * Include this at the top of every API file.
 */

// Secure session cookie settings before session starts (compatible with local HTTP XAMPP)
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_samesite', 'Lax');
    // Enable secure cookies only when running over HTTPS (keeps local HTTP XAMPP fully accessible)
    $isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    if ($isHttps) {
        ini_set('session.cookie_secure', '1');
    }
}

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

// Explicit origin allowlist (same-origin host + optional CORS_ALLOWED_ORIGINS; no wildcard '*' on authenticated APIs)
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$httpHost = $_SERVER['HTTP_HOST'] ?? '';
if ($origin !== '') {
    $parsedHost = parse_url($origin, PHP_URL_HOST);
    $parsedPort = parse_url($origin, PHP_URL_PORT);
    $originHostPort = $parsedHost . ($parsedPort ? ':' . $parsedPort : '');
    $envOrigins = array_filter(array_map('trim', explode(',', $_ENV['CORS_ALLOWED_ORIGINS'] ?? '')));
    $devOrigins = ['http://localhost:5173', 'http://127.0.0.1:5173', 'http://localhost', 'http://127.0.0.1'];
    $allowed = array_unique(array_merge($envOrigins, $devOrigins));

    if ($originHostPort === $httpHost || in_array($origin, $allowed, true)) {
        header("Access-Control-Allow-Origin: $origin");
        header('Access-Control-Allow-Credentials: true');
        header('Vary: Origin');
    }
}

header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-CSRF-Token');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

// Respond immediately to OPTIONS pre-flight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// ── Response helpers ─────────────────────────────────────────────────────────

/** Send a JSON success response and exit. */
function respondOk(array $data = []): void {
    echo json_encode(array_merge(['success' => true], $data));
    exit;
}

/** Send a JSON error response and exit. */
function respondErr(string $message, int $code = 400): void {
    http_response_code($code);
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}

// ── Auth guard ───────────────────────────────────────────────────────────────

/** Abort with 401 if no active session exists or if session has timed out. */
function requireAuth(): void {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['user'])) {
        respondErr('Unauthorised. Please log in.', 401);
    }

    // Dynamic Inactivity Timeout from Admin Settings (default 30 minutes)
    $timeoutMinutes = 30;
    if (isset($GLOBALS['pdo']) && $GLOBALS['pdo'] instanceof PDO) {
        if (!function_exists('getSettingValue') && file_exists(__DIR__ . '/settings_helper.php')) {
            require_once __DIR__ . '/settings_helper.php';
        }
        if (function_exists('getSettingValue')) {
            $timeoutMinutes = max(5, (int)getSettingValue($GLOBALS['pdo'], 'session_timeout_minutes', 30));
        }
    }
    $timeoutSeconds = $timeoutMinutes * 60;
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeoutSeconds)) {
        session_unset();
        session_destroy();
        respondErr('Session has expired due to inactivity. Please log in again.', 401);
    }
    $_SESSION['last_activity'] = time();
}

/** Abort with 403 if active user does not have required role. */
function requireRole(string $requiredRole): void {
    requireAuth();
    if (($_SESSION['role'] ?? '') !== $requiredRole) {
        respondErr('Access denied. Administrator privileges required.', 403);
    }
}

/** Get or initialize CSRF token for the active session. */
function getCsrfToken(): string {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/** Verify CSRF token securely. */
function verifyCsrfToken(?string $token): bool {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['csrf_token']) || empty($token)) return false;
    return hash_equals($_SESSION['csrf_token'], $token);
}

/** Sanitize general string input to prevent XSS. */
function sanitizeInput(?string $value): string {
    if ($value === null) return '';
    return htmlspecialchars(trim(strip_tags($value)), ENT_QUOTES, 'UTF-8');
}

/** Return current session username or 'System'. */
function sessionUser(): string {
    if (session_status() === PHP_SESSION_NONE) session_start();
    return $_SESSION['user'] ?? 'System';
}

// ── Request body ─────────────────────────────────────────────────────────────

/** Decode JSON request body; returns empty array on failure. */
function getBody(): array {
    $raw = file_get_contents('php://input');
    return json_decode($raw, true) ?? [];
}

// ── Audit log ────────────────────────────────────────────────────────────────

/** Insert a row into system_logs. */
function addLog(PDO $pdo, string $level, string $action, string $user, string $details): void {
    $userCol = function_exists('dbQuoteIdent') ? dbQuoteIdent('user') : '`user`';
    $stmt = $pdo->prepare(
        "INSERT INTO system_logs (level, action, {$userCol}, details) VALUES (?, ?, ?, ?)"
    );
    $stmt->execute([$level, $action, $user, $details]);
}

/** Validate and persist a base64 image data URL under /public/uploads/avatars/. */
function saveUploadedAvatar(?string $imageData, string $prefix, $entityId): ?string {
    if (empty($imageData) || !is_string($imageData)) {
        return null;
    }
    if (!preg_match('/^data:image\/(png|jpeg|jpg|webp);base64,(.*)$/is', trim($imageData), $matches)) {
        respondErr('Invalid image format. Please use PNG, JPG, or WEBP.', 400);
    }
    $ext = strtolower($matches[1]);
    if ($ext === 'jpeg') $ext = 'jpg';

    $decoded = base64_decode($matches[2], true);
    if ($decoded === false || strlen($decoded) === 0) {
        respondErr('Uploaded image could not be decoded.', 400);
    }
    if (strlen($decoded) > 5 * 1024 * 1024) {
        respondErr('Profile image must not exceed 5MB.', 400);
    }
    if (@getimagesizefromstring($decoded) === false) {
        respondErr('Uploaded file is not a valid image.', 400);
    }

    $safePrefix = preg_replace('/[^a-z0-9_-]/i', '', $prefix) ?: 'avatar';
    $safeId     = preg_replace('/[^a-z0-9_-]/i', '', (string)$entityId) ?: '0';
    $filename   = "{$safePrefix}_{$safeId}_" . time() . '_' . bin2hex(random_bytes(3)) . ".{$ext}";

    // Use Supabase Storage in cloud / serverless deployments (ephemeral filesystem)
    require_once __DIR__ . '/supabase_storage.php';
    if (supabaseStorageConfigured()) {
        $mime = $ext === 'png' ? 'image/png' : ($ext === 'webp' ? 'image/webp' : 'image/jpeg');
        $cloudUrl = supabaseUploadAvatar($decoded, $filename, $mime);
        if ($cloudUrl === null) {
            respondErr('Failed to upload profile image to cloud storage.', 500);
        }
        return $cloudUrl;
    }

    $uploadDir = dirname(__DIR__, 2) . '/public/uploads/avatars/';
    if (!is_dir($uploadDir)) {
        @mkdir($uploadDir, 0777, true);
    }
    if (@file_put_contents($uploadDir . $filename, $decoded) === false) {
        respondErr('Failed to save profile image.', 500);
    }
    return '/sdn/public/uploads/avatars/' . $filename;
}

/** Delete an existing avatar from Supabase Storage or local /public/uploads/avatars/. */
function deleteLocalAvatar(?string $url): void {
    if (empty($url)) {
        return;
    }
    require_once __DIR__ . '/supabase_storage.php';
    if (supabaseStorageConfigured() && str_contains($url, '/storage/v1/object/public/')) {
        supabaseDeleteAvatar($url);
        return;
    }
    if (!str_starts_with($url, '/sdn/public/uploads/avatars/')) {
        return;
    }
    $filename = basename($url);
    $fullPath = dirname(__DIR__, 2) . '/public/uploads/avatars/' . $filename;
    if (is_file($fullPath)) {
        @unlink($fullPath);
    }
}
