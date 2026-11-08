<?php
/**
 * api/super-admin/archive.php
 * Endpoint for Data Archiving & Retention:
 * - GET: Calculates eligible historical records count (e.g. older than 1, 3, 5 years)
 * - POST: Executes safe database transaction archiving historical completed/cancelled appointments
 *   and audit logs into dedicated archive tables (`appointments_archive`, `system_logs_archive`).
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/auth-guard.php';
verifySuperAdminSession(true);

require_once __DIR__ . '/../config/db.php';

function ensureArchiveTables(PDO $pdo) {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `appointments_archive` LIKE `appointments`;
        CREATE TABLE IF NOT EXISTS `system_logs_archive` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `original_id` INT,
            `level` VARCHAR(20),
            `action` VARCHAR(100),
            `user` VARCHAR(100),
            `role` VARCHAR(50),
            `details` TEXT,
            `ip_address` VARCHAR(45),
            `created_at` TIMESTAMP,
            `archived_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
}

$method = $_SERVER['REQUEST_METHOD'];

// 1. GET: Calculate eligible records
if ($method === 'GET') {
    $years = max(1, (int)($_GET['years'] ?? 5));
    $cutoffDate = date('Y-m-d', strtotime("-{$years} years"));

    try {
        ensureArchiveTables($pdo);

        // Count old appointments
        $stmtAppt = $pdo->prepare("
            SELECT COUNT(*) FROM appointments 
            WHERE DATE(appointment_date) <= :cutoff 
            AND status IN ('completed', 'rejected', 'no-show')
        ");
        $stmtAppt->execute(['cutoff' => $cutoffDate]);
        $oldAppts = (int)$stmtAppt->fetchColumn();

        // Count old logs
        $stmtLogs = $pdo->prepare("SELECT COUNT(*) FROM system_logs WHERE DATE(created_at) <= :cutoff");
        $stmtLogs->execute(['cutoff' => $cutoffDate]);
        $oldLogs = (int)$stmtLogs->fetchColumn();

        // Count existing archived records
        $archivedAppts = (int)$pdo->query("SELECT COUNT(*) FROM appointments_archive")->fetchColumn();
        $archivedLogs = (int)$pdo->query("SELECT COUNT(*) FROM system_logs_archive")->fetchColumn();

        echo json_encode([
            'success' => true,
            'cutoff_date' => $cutoffDate,
            'years' => $years,
            'eligible' => [
                'appointments' => $oldAppts,
                'logs' => $oldLogs,
                'total' => $oldAppts + $oldLogs
            ],
            'existing_archived' => [
                'appointments' => $archivedAppts,
                'logs' => $archivedLogs,
                'total' => $archivedAppts + $archivedLogs
            ]
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// 2. POST: Execute archive operation
if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $years = max(1, (int)($input['years'] ?? 5));
    $cutoffDate = date('Y-m-d', strtotime("-{$years} years"));

    try {
        ensureArchiveTables($pdo);
        $pdo->beginTransaction();

        // Move appointments
        $pdo->prepare("
            INSERT INTO appointments_archive 
            SELECT * FROM appointments 
            WHERE DATE(appointment_date) <= :cutoff 
            AND status IN ('completed', 'rejected', 'no-show')
        ")->execute(['cutoff' => $cutoffDate]);

        $deleteAppt = $pdo->prepare("
            DELETE FROM appointments 
            WHERE DATE(appointment_date) <= :cutoff 
            AND status IN ('completed', 'rejected', 'no-show')
        ");
        $deleteAppt->execute(['cutoff' => $cutoffDate]);
        $archivedApptsCount = $deleteAppt->rowCount();

        // Move logs
        $pdo->prepare("
            INSERT INTO system_logs_archive (`original_id`, `level`, `action`, `user`, `role`, `details`, `ip_address`, `created_at`)
            SELECT `id`, `level`, `action`, `user`, `role`, `details`, `ip_address`, `created_at` 
            FROM system_logs 
            WHERE DATE(created_at) <= :cutoff
        ")->execute(['cutoff' => $cutoffDate]);

        $deleteLogs = $pdo->prepare("DELETE FROM system_logs WHERE DATE(created_at) <= :cutoff");
        $deleteLogs->execute(['cutoff' => $cutoffDate]);
        $archivedLogsCount = $deleteLogs->rowCount();

        // Record audit entry
        $operator = $_SESSION['user'] ?? 'super_admin';
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $stmtAudit = $pdo->prepare("
            INSERT INTO system_logs (`level`, `action`, `user`, `role`, `details`, `ip_address`)
            VALUES ('info', 'Data Archive Executed', :u, 'super_admin', :dt, :ip)
        ");
        $stmtAudit->execute([
            'u' => $operator,
            'dt' => "Archived records older than {$years} years ({$archivedApptsCount} appts, {$archivedLogsCount} logs).",
            'ip' => $ip
        ]);

        $pdo->commit();

        echo json_encode([
            'success' => true,
            'message' => "Successfully archived {$archivedApptsCount} appointments and {$archivedLogsCount} audit logs older than {$years} years.",
            'archived' => [
                'appointments' => $archivedApptsCount,
                'logs' => $archivedLogsCount
            ]
        ]);
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

