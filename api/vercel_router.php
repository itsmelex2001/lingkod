<?php
/**
 * api/vercel_router.php
 * Single Serverless PHP Front Controller for Vercel (vercel-php).
 *
 * Keeps all 150+ PHP pages and API endpoints inside 1 Serverless Function
 * (staying within Vercel Hobby's 12-function limit), initializes the Supabase
 * PostgreSQL DB-backed session handler before any session_start(), preserves
 * legacy /sdn/* URLs, and replicates .htaccess API rewrites + file blocks.
 */

$projectRoot = realpath(__DIR__ . '/..');
if ($projectRoot === false) {
    http_response_code(500);
    echo 'Application root not found.';
    exit;
}

// 1. Initialize PDO + Supabase-backed SessionHandler BEFORE target scripts call session_start()
require_once __DIR__ . '/config/db.php';

// 2. Resolve requested path (preferring explicit __route from vercel.json)
$rawPath = isset($_GET['__route']) && is_string($_GET['__route'])
    ? $_GET['__route']
    : (string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
unset($_GET['__route']);

$path = '/' . ltrim(rawurldecode($rawPath), '/');

// Strip optional legacy /sdn prefix so all /sdn/... links work at Vercel root
if ($path === '/sdn' || $path === '/sdn/') {
    $path = '/';
} elseif (str_starts_with($path, '/sdn/')) {
    $path = substr($path, 4);
}

// Normalize trailing slash (except root)
if ($path !== '/' && str_ends_with($path, '/')) {
    $path = rtrim($path, '/');
}

// 3. Security blocks (mirrors .htaccess FilesMatch restrictions)
if (
    str_contains($path, '..')
    || str_contains($path, "\0")
    || preg_match('#(^|/)\.#', $path)
    || preg_match('#\.(sql|bak|log|ini|zip|rar|tar|gz|exe|cs|ps1|env)$#i', $path)
    || preg_match('#^/api/(config|services)/#i', $path)
    || preg_match('#^/(check-users|check_db|check_depts|dump_schema|run_sql|setup|test.*|migrate_status|fix_.*)\.php$#i', $path)
    || $path === '/api/vercel_router.php'
) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Forbidden';
    exit;
}

// 4. Replicate .htaccess rewrite rules & Cron alias
$targetRel = null;
if ($path === '/' || $path === '/index.php') {
    $targetRel = 'index.php';
} elseif ($path === '/api/cron-reminders' || $path === '/cron-reminders' || $path === '/cron_reminders.php') {
    $targetRel = 'cron_reminders.php';
} elseif (preg_match('#^/api/admin/(settings|faqs|system)(/.*)?$#', $path, $m)) {
    $_GET['route'] = '/admin/' . $m[1] . ($m[2] ?? '');
    $targetRel = 'api/admin-settings-router.php';
} elseif (preg_match('#^/api/admin/users(/.*)?$#', $path, $m)) {
    $_GET['route'] = '/admin/users' . ($m[1] ?? '');
    $targetRel = 'api/admin-users-router.php';
} elseif ($path === '/api/public/chatbot-faqs') {
    $_GET['route'] = '/public/chatbot-faqs';
    $targetRel = 'api/admin-settings-router.php';
} elseif (str_ends_with(strtolower($path), '.php')) {
    $targetRel = ltrim($path, '/');
} else {
    // Allow extensionless page routes if a matching .php exists
    $candidate = ltrim($path, '/') . '.php';
    if (is_file($projectRoot . DIRECTORY_SEPARATOR . $candidate)) {
        $targetRel = $candidate;
    }
}

if ($targetRel === null) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Not Found';
    exit;
}

$targetAbs = realpath($projectRoot . DIRECTORY_SEPARATOR . $targetRel);
$rootPrefix = rtrim($projectRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

if (
    $targetAbs === false
    || !is_file($targetAbs)
    || !str_starts_with($targetAbs, $rootPrefix)
    || !str_ends_with(strtolower($targetAbs), '.php')
) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Not Found';
    exit;
}

// 5. Align script server globals & working directory, then execute target script
$_SERVER['SCRIPT_FILENAME'] = $targetAbs;
$_SERVER['SCRIPT_NAME']     = '/' . str_replace('\\', '/', $targetRel);
$_SERVER['PHP_SELF']        = $_SERVER['SCRIPT_NAME'];
chdir(dirname($targetAbs));

require $targetAbs;
