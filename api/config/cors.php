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

// Determine allowed origin (default to same-origin host if available)
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$httpHost = $_SERVER['HTTP_HOST'] ?? '';
if (!empty($origin)) {
    $parsedOrigin = parse_url($origin, PHP_URL_HOST);
    $parsedPort = parse_url($origin, PHP_URL_PORT);
    $originHostPort = $parsedOrigin . ($parsedPort ? ':' . $parsedPort : '');
    
    // Allow local XAMPP host access
    if ($originHostPort === $httpHost || in_array($parsedOrigin, ['localhost', '127.0.0.1'])) {
        header("Access-Control-Allow-Origin: $origin");
        header('Access-Control-Allow-Credentials: true');
    }
} else {
    header('Access-Control-Allow-Origin: *');
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

    // Healthcare Inactivity Timeout: 30 minutes (1800 seconds)
    $timeoutSeconds = 1800;
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
    $stmt = $pdo->prepare(
        "INSERT INTO system_logs (level, action, user, details) VALUES (?, ?, ?, ?)"
    );
    $stmt->execute([$level, $action, $user, $details]);
}
