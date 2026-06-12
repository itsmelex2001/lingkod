<?php
/**
 * api/config/cors.php
 * Sets JSON + CORS headers and provides shared helper functions.
 * Include this at the top of every API file.
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
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

/** Abort with 401 if no active session exists. */
function requireAuth(): void {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['user'])) {
        respondErr('Unauthorised. Please log in.', 401);
    }
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
