<?php
/**
 * api/super-admin/overview.php
 * Endpoint for:
 * 1. Health checks (Database, SMTP Email service, Storage)
 * 2. System Maintenance Mode status & toggle
 * 3. Quick system telemetry (Total Admins, Staff, Patients, Active Sessions)
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/auth-guard.php';
verifySuperAdminSession(true);

require_once __DIR__ . '/../config/db.php';

$configFile = __DIR__ . '/system-state.json';

function getSystemState(string $file): array {
    $defaults = [
        'maintenance_mode' => false,
        'maintenance_message' => 'The system is undergoing scheduled maintenance. Please try again shortly.',
        'maintenance_updated_at' => null,
        'maintenance_updated_by' => null,
        'smtp_configured' => false
    ];

    if (!file_exists($file)) {
        file_put_contents($file, json_encode($defaults, JSON_PRETTY_PRINT));
        return $defaults;
    }

    $data = json_decode(file_get_contents($file), true);
    return is_array($data) ? array_merge($defaults, $data) : $defaults;
}

function saveSystemState(string $file, array $state): bool {
    return file_put_contents($file, json_encode($state, JSON_PRETTY_PRINT)) !== false;
}

$method = $_SERVER['REQUEST_METHOD'];

// Handle POST: Toggle Maintenance Mode
if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $action = $input['action'] ?? '';

    if ($action === 'toggle_maintenance') {
        $state = getSystemState($configFile);
        $enable = !empty($input['enable']);
        $message = trim($input['message'] ?? $state['maintenance_message']);

        $state['maintenance_mode'] = $enable;
        if ($message !== '') {
            $state['maintenance_message'] = $message;
        }
        $state['maintenance_updated_at'] = date('Y-m-d H:i:s');
        $state['maintenance_updated_by'] = $_SESSION['user'] ?? 'super_admin';

        saveSystemState($configFile, $state);

        echo json_encode([
            'success' => true,
            'message' => 'Maintenance mode ' . ($enable ? 'activated' : 'deactivated') . ' successfully.',
            'state' => $state
        ]);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Invalid action.']);
    exit;
}

// Handle GET: Telemetry and Health Status
$state = getSystemState($configFile);

// 1. Health Checks
$health = [
    'database' => [
        'status' => 'online',
        'label' => 'MySQL Database Engine',
        'latency_ms' => 0
    ],
    'smtp' => [
        'status' => 'unknown',
        'label' => 'SMTP Notification Service',
        'message' => 'Configuration check pending'
    ],
    'disk' => [
        'status' => 'online',
        'label' => 'Storage & File Permissions',
        'free_gb' => 0
    ]
];

// Check Database Ping & Latency
try {
    $start = microtime(true);
    $pdo->query("SELECT 1");
    $health['database']['latency_ms'] = round((microtime(true) - $start) * 1000, 2);
    $health['database']['status'] = 'online';
} catch (Exception $e) {
    $health['database']['status'] = 'offline';
    $health['database']['error'] = $e->getMessage();
}

// Check SMTP Configuration
$mailHost = $_ENV['MAIL_HOST'] ?? getenv('MAIL_HOST');
$mailUser = $_ENV['MAIL_USERNAME'] ?? getenv('MAIL_USERNAME');
if (!empty($mailHost) && !empty($mailUser)) {
    $health['smtp']['status'] = 'online';
    $health['smtp']['message'] = 'Configured (' . htmlspecialchars($mailHost) . ')';
} else {
    $health['smtp']['status'] = 'warning';
    $health['smtp']['message'] = 'Unconfigured';
}

// Check Disk Space
$freeBytes = @disk_free_space(__DIR__);
if ($freeBytes !== false) {
    $health['disk']['free_gb'] = round($freeBytes / (1024 * 1024 * 1024), 2);
    $health['disk']['status'] = $health['disk']['free_gb'] > 2 ? 'online' : 'warning';
}

// 2. Metrics Telemetry
$metrics = [
    'total_admins' => 0,
    'total_staff' => 0,
    'total_patients' => 0,
    'today_appointments' => 0
];

try {
    // Count Admins
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE LOWER(role) = 'admin'");
    $metrics['total_admins'] = (int)$stmt->fetchColumn();

    // Count Staff
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE LOWER(role) IN ('staff', 'doctor', 'nurse')");
    $metrics['total_staff'] = (int)$stmt->fetchColumn();

    // Count Patients
    $stmt = $pdo->query("SELECT COUNT(*) FROM patients");
    $metrics['total_patients'] = (int)$stmt->fetchColumn();

    // Today's appointments
    $today = date('Y-m-d');
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE DATE(appointment_date) = :today");
    $stmt->execute(['today' => $today]);
    $metrics['today_appointments'] = (int)$stmt->fetchColumn();
} catch (Exception $e) {
    // Graceful fallback if tables differ
}

echo json_encode([
    'success' => true,
    'maintenance' => $state,
    'health' => $health,
    'metrics' => $metrics,
    'timestamp' => date('Y-m-d H:i:s')
]);

