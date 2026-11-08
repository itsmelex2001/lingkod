<?php
/**
 * api/super-admin/maintenance-middleware.php
 * Included in public/admin/staff endpoints or page headers.
 * If maintenance mode is active, allows super_admin and denies everyone else.
 */

function enforceMaintenanceMode() {
    $stateFile = __DIR__ . '/system-state.json';
    if (!file_exists($stateFile)) return;

    $state = json_decode(file_get_contents($stateFile), true);
    if (empty($state['maintenance_mode'])) return;

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $isSuperAdmin = !empty($_SESSION['role']) && strtolower(trim($_SESSION['role'])) === 'super_admin';
    if ($isSuperAdmin) return; // Super Admins bypass maintenance

    // If an API request, return JSON 503
    $isApi = str_contains($_SERVER['REQUEST_URI'] ?? '', '/api/');
    if ($isApi) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(503);
        echo json_encode([
            'success' => false,
            'maintenance' => true,
            'message' => $state['maintenance_message'] ?? 'System Under Maintenance.'
        ]);
        exit;
    }

    // Render Clean Maintenance Page
    http_response_code(503);
    $msg = htmlspecialchars($state['maintenance_message'] ?? 'The system is undergoing maintenance. Please try again soon.');
    echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Under Maintenance | MMWGH LINGKOD</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { margin:0; padding:0; background:#f8fafc; font-family:'Inter',sans-serif; display:flex; align-items:center; justify-content:center; min-height:100vh; color:#0f172a; }
        .card { background:#fff; border:1px solid #e2e8f0; border-radius:16px; padding:40px; max-width:480px; text-align:center; box-shadow:0 10px 25px -5px rgba(15,23,42,0.08); }
        .icon { width:56px; height:56px; border-radius:50%; background:#fef3c7; color:#d97706; display:inline-flex; align-items:center; justify-content:center; margin-bottom:16px; font-size:1.6rem; }
        h1 { font-size:1.35rem; margin:0 0 10px; }
        p { color:#64748b; font-size:0.92rem; line-height:1.5; margin:0 0 20px; }
        .badge { display:inline-block; font-size:0.75rem; font-weight:700; color:#15803d; background:#f0fdf4; border:1px solid #bbf7d0; padding:5px 12px; border-radius:999px; text-transform:uppercase; }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon">⚠️</div>
        <h1>System Under Maintenance</h1>
        <p>{$msg}</p>
        <span class="badge">MMWGH LINGKOD Operations</span>
    </div>
</body>
</html>
HTML;
    exit;
}

