<?php
/**
 * api/super-admin/backup.php
 * Endpoint for Database Management & Backups:
 * - GET ?action=info: Returns last backup timestamp, total tables, and estimated DB size.
 * - GET ?action=download: Generates a complete, sanitized MySQL SQL dump and forces file download.
 */

require_once __DIR__ . '/auth-guard.php';
verifySuperAdminSession(false); // false allows browser direct download redirect

require_once __DIR__ . '/../config/db.php';

$action = $_GET['action'] ?? 'info';
$backupMetaFile = __DIR__ . '/backup-meta.json';

function getBackupMeta(string $file): array {
    if (!file_exists($file)) {
        return ['last_backup' => null, 'total_backups' => 0];
    }
    $d = json_decode(file_get_contents($file), true);
    return is_array($d) ? $d : ['last_backup' => null, 'total_backups' => 0];
}

// 1. INFO ACTION
if ($action === 'info') {
    header('Content-Type: application/json; charset=utf-8');
    try {
        $meta = getBackupMeta($backupMetaFile);
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        
        // Calculate estimated size
        $stmt = $pdo->query("
            SELECT SUM(data_length + index_length) AS size_bytes 
            FROM information_schema.TABLES 
            WHERE table_schema = '" . DB_NAME . "'
        ");
        $sizeBytes = (float)$stmt->fetchColumn();
        $sizeMb = round($sizeBytes / (1024 * 1024), 2);

        echo json_encode([
            'success' => true,
            'database' => DB_NAME,
            'tables_count' => count($tables),
            'database_size_mb' => $sizeMb,
            'last_backup' => $meta['last_backup'] ?? 'No backups generated yet',
            'total_backups' => $meta['total_backups'] ?? 0
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// 2. DOWNLOAD ACTION (SQL DUMP GENERATOR)
if ($action === 'download') {
    try {
        set_time_limit(300);
        ini_set('memory_limit', '512M');

        $timestamp = date('Y-m-d_H-i-s');
        $filename = "mmwgh_lingkod_backup_{$timestamp}.sql";

        header('Content-Type: application/octet-stream');
        header("Content-Disposition: attachment; filename=\"{$filename}\"");
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');

        fwrite($output, "-- ============================================================\n");
        fwrite($output, "-- MMWGH LINGKOD - SUPER ADMIN DATABASE BACKUP\n");
        fwrite($output, "-- Database: " . DB_NAME . "\n");
        fwrite($output, "-- Generated: " . date('Y-m-d H:i:s') . "\n");
        fwrite($output, "-- Operator: " . ($_SESSION['user'] ?? 'Super Admin') . "\n");
        fwrite($output, "-- ============================================================\n\n");
        fwrite($output, "SET FOREIGN_KEY_CHECKS=0;\n");
        fwrite($output, "SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\n");
        fwrite($output, "START TRANSACTION;\n");
        fwrite($output, "SET time_zone = '+00:00';\n\n");

        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

        foreach ($tables as $table) {
            // Drop & Create statement
            fwrite($output, "\n-- Table structure for `{$table}`\n");
            fwrite($output, "DROP TABLE IF EXISTS `{$table}`;\n");
            $createTableStmt = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch(PDO::FETCH_ASSOC);
            fwrite($output, $createTableStmt['Create Table'] . ";\n\n");

            // Dump data
            $rowsStmt = $pdo->query("SELECT * FROM `{$table}`");
            $rows = $rowsStmt->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($rows)) {
                fwrite($output, "-- Dumping data for table `{$table}`\n");
                $cols = array_keys($rows[0]);
                $colNames = implode('`, `', $cols);

                foreach (array_chunk($rows, 100) as $chunk) {
                    $valSets = [];
                    foreach ($chunk as $row) {
                        $escapedVals = array_map(function ($val) use ($pdo) {
                            if ($val === null) return 'NULL';
                            return $pdo->quote($val);
                        }, array_values($row));
                        $valSets[] = '(' . implode(', ', $escapedVals) . ')';
                    }
                    fwrite($output, "INSERT INTO `{$table}` (`{$colNames}`) VALUES\n" . implode(",\n", $valSets) . ";\n");
                }
                fwrite($output, "\n");
            }
        }

        fwrite($output, "SET FOREIGN_KEY_CHECKS=1;\n");
        fwrite($output, "COMMIT;\n");
        fclose($output);

        // Update backup meta
        $meta = getBackupMeta($backupMetaFile);
        $meta['last_backup'] = date('Y-m-d H:i:s');
        $meta['total_backups'] = ($meta['total_backups'] ?? 0) + 1;
        file_put_contents($backupMetaFile, json_encode($meta, JSON_PRETTY_PRINT));
        exit;
    } catch (Exception $e) {
        header('Content-Type: text/plain');
        echo "Error generating database backup: " . $e->getMessage();
        exit;
    }
}

