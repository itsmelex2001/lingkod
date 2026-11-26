<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemBackupLog;
use App\Services\SettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SystemBackupController extends Controller
{
    public function __construct(private readonly SettingsService $settings) {}

    public function index(): JsonResponse
    {
        $stats = $this->collectDatabaseStats();
        $logs = SystemBackupLog::orderByDesc('id')->limit(20)->get();

        return response()->json([
            'success' => true,
            'data' => [
                'database' => $stats['database'],
                'tables_count' => $stats['tables_count'],
                'database_size_mb' => $stats['database_size_mb'],
                'last_backup' => $logs->first()?->created_at?->toIso8601String(),
                'total_backups' => SystemBackupLog::where('status', 'completed')->count(),
                'history' => $logs,
            ],
        ]);
    }

    public function trigger(Request $request): JsonResponse
    {
        $timestamp = now()->format('Y-m-d_H-i-s');
        $filename = "lingkod_backup_{$timestamp}.sql";
        $relativePath = "backups/{$filename}";
        $actorId = $request->user()?->id ?? ($_SESSION['user_id'] ?? null);
        $actorName = $request->user()?->name ?? $request->user()?->email ?? ($_SESSION['user'] ?? 'admin');

        try {
            [$sql, $tablesCount] = $this->buildSqlDump($actorName);
            Storage::disk('local')->put($relativePath, $sql);
            $sizeBytes = strlen($sql);

            $log = SystemBackupLog::create([
                'filename' => $filename,
                'disk_path' => $relativePath,
                'size_bytes' => $sizeBytes,
                'tables_count' => $tablesCount,
                'trigger_type' => 'manual',
                'status' => 'completed',
                'triggered_by' => $actorId,
            ]);

            $this->settings->writeAuditLog('info', 'Database Backup Created', $actorName, "Created backup {$filename} ({$tablesCount} tables).");

            return response()->json([
                'success' => true,
                'message' => 'Database backup generated successfully.',
                'data' => $log,
            ], 201);
        } catch (\Throwable $e) {
            SystemBackupLog::create([
                'filename' => $filename,
                'disk_path' => null,
                'size_bytes' => 0,
                'tables_count' => 0,
                'trigger_type' => 'manual',
                'status' => 'failed',
                'error_message' => substr($e->getMessage(), 0, 500),
                'triggered_by' => $actorId,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Backup failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function download(SystemBackupLog $backup): BinaryFileResponse|JsonResponse
    {
        if (!$backup->disk_path || !Storage::disk('local')->exists($backup->disk_path)) {
            return response()->json(['success' => false, 'message' => 'Backup file not found on disk.'], 404);
        }

        $fullPath = Storage::disk('local')->path($backup->disk_path);

        return response()->download($fullPath, basename($backup->filename), [
            'Content-Type' => 'application/sql',
        ]);
    }

    private function collectDatabaseStats(): array
    {
        $driver = DB::getDriverName();
        $dbName = DB::getDatabaseName();

        if ($driver === 'mysql') {
            $tables = DB::select('SHOW TABLES');
            $sizeRow = DB::selectOne(
                'SELECT SUM(data_length + index_length) AS size_bytes FROM information_schema.TABLES WHERE table_schema = ?',
                [$dbName]
            );
            $sizeMb = round(((float) ($sizeRow->size_bytes ?? 0)) / (1024 * 1024), 2);
            return [
                'database' => $dbName,
                'tables_count' => count($tables),
                'database_size_mb' => $sizeMb,
            ];
        }

        $tables = DB::select("SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%'");
        return [
            'database' => $dbName,
            'tables_count' => count($tables),
            'database_size_mb' => 0.0,
        ];
    }

    private function buildSqlDump(string $operator): array
    {
        $pdo = DB::getPdo();
        $driver = DB::getDriverName();
        $dbName = DB::getDatabaseName();

        $lines = [
            '-- ============================================================',
            '-- MMWGH LINGKOD - SYSTEM ADMIN DATABASE BACKUP',
            '-- Database: ' . $dbName,
            '-- Generated: ' . now()->toDateTimeString(),
            '-- Operator: ' . $operator,
            '-- ============================================================',
            '',
        ];

        if ($driver === 'mysql') {
            $lines[] = 'SET FOREIGN_KEY_CHECKS=0;';
            $tables = $pdo->query('SHOW TABLES')->fetchAll(\PDO::FETCH_COLUMN);
        } else {
            $tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'")->fetchAll(\PDO::FETCH_COLUMN);
        }

        foreach ($tables as $table) {
            $safeTable = str_replace('`', '', (string) $table);
            $lines[] = "-- Table: `{$safeTable}`";
            $lines[] = "DROP TABLE IF EXISTS `{$safeTable}`;";

            if ($driver === 'mysql') {
                $create = $pdo->query("SHOW CREATE TABLE `{$safeTable}`")->fetch(\PDO::FETCH_ASSOC);
                $lines[] = ($create['Create Table'] ?? '') . ';';
            } else {
                $stmt = $pdo->prepare("SELECT sql FROM sqlite_master WHERE type='table' AND name = ?");
                $stmt->execute([$safeTable]);
                $ddl = $stmt->fetchColumn();
                if ($ddl) $lines[] = $ddl . ';';
            }

            $rows = $pdo->query("SELECT * FROM `{$safeTable}`")->fetchAll(\PDO::FETCH_ASSOC);
            if (!empty($rows)) {
                $cols = '`' . implode('`, `', array_keys($rows[0])) . '`';
                foreach (array_chunk($rows, 100) as $chunk) {
                    $sets = [];
                    foreach ($chunk as $row) {
                        $vals = array_map(fn ($v) => $v === null ? 'NULL' : $pdo->quote((string) $v), array_values($row));
                        $sets[] = '(' . implode(', ', $vals) . ')';
                    }
                    $lines[] = "INSERT INTO `{$safeTable}` ({$cols}) VALUES\n" . implode(",\n", $sets) . ';';
                }
            }
            $lines[] = '';
        }

        if ($driver === 'mysql') {
            $lines[] = 'SET FOREIGN_KEY_CHECKS=1;';
        }

        return [implode("\n", $lines) . "\n", count($tables)];
    }
}
