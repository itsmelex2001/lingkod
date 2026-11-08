<?php
/**
 * api/super-admin/audit.php
 * Endpoint for Master Audit Trail:
 * - Paginated system logs across all actors (Admin, Staff, Patient, Super Admin)
 * - Filtering by Date Range, Role (Admin/Staff/Patient/System), and Action Type
 * - Returns IP addresses, timestamps, actor usernames, and details.
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/auth-guard.php';
verifySuperAdminSession(true);

require_once __DIR__ . '/../config/db.php';

$page = max(1, (int)($_GET['page'] ?? 1));
$limit = min(50, max(10, (int)($_GET['limit'] ?? 20)));
$offset = ($page - 1) * $limit;

$roleFilter = strtolower(trim($_GET['role'] ?? 'all'));
$actionFilter = strtolower(trim($_GET['action_type'] ?? 'all'));
$dateFrom = trim($_GET['date_from'] ?? '');
$dateTo = trim($_GET['date_to'] ?? '');
$search = trim($_GET['search'] ?? '');

$logs = [];
$total = 0;

try {
    // Check if system_logs table exists
    $tableCheck = $pdo->query("SHOW TABLES LIKE 'system_logs'")->fetch();
    
    if (!$tableCheck) {
        // Create system_logs table if not present
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `system_logs` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `level` VARCHAR(20) DEFAULT 'info',
                `action` VARCHAR(100) NOT NULL,
                `user` VARCHAR(100) NOT NULL,
                `role` VARCHAR(50) DEFAULT 'system',
                `details` TEXT,
                `ip_address` VARCHAR(45) DEFAULT '127.0.0.1',
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_created (created_at),
                INDEX idx_user (user)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
    }

    // Build query conditions
    $conditions = [];
    $params = [];

    if ($roleFilter !== 'all') {
        $conditions[] = "LOWER(role) = :role";
        $params['role'] = $roleFilter;
    }

    if ($actionFilter !== 'all') {
        $conditions[] = "LOWER(action) LIKE :action_type";
        $params['action_type'] = "%$actionFilter%";
    }

    if ($dateFrom !== '') {
        $conditions[] = "DATE(created_at) >= :date_from";
        $params['date_from'] = $dateFrom;
    }

    if ($dateTo !== '') {
        $conditions[] = "DATE(created_at) <= :date_to";
        $params['date_to'] = $dateTo;
    }

    if ($search !== '') {
        $conditions[] = "(user LIKE :search OR details LIKE :search OR action LIKE :search OR ip_address LIKE :search)";
        $params['search'] = "%$search%";
    }

    $whereSql = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

    // Total Count
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM system_logs {$whereSql}");
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();

    // Data query
    $querySql = "SELECT * FROM system_logs {$whereSql} ORDER BY created_at DESC LIMIT {$limit} OFFSET {$offset}";
    $dataStmt = $pdo->prepare($querySql);
    $dataStmt->execute($params);
    $logs = $dataStmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $logs,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'total_pages' => ceil($total / $limit)
        ]
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

