<?php
/**
 * cron/send_reminders.php
 * Automated Daily Scheduled Task (CRON Job) for MMWGH Linkod.
 *
 * Scans the database for patients with 'Approved' status whose scheduled dates
 * are exactly 2 to 3 days in the future, dispatches formatted SMS appointment
 * reminders via the integrated third-party SMS service, and updates database rows to 'Sent'.
 *
 * USAGE:
 *  CLI / Windows Task Scheduler:
 *    php c:\xampp1\htdocs\sdn\cron\send_reminders.php
 *    php c:\xampp1\htdocs\sdn\cron\send_reminders.php --force
 *    php c:\xampp1\htdocs\sdn\cron\send_reminders.php --dry-run
 *
 *  HTTP / Web Cron:
 *    http://localhost/sdn/cron/send_reminders.php
 *    http://localhost/sdn/cron/send_reminders.php?token=mmwgh_cron_secret_2026
 */

// Prevent script execution timeout on large batches
set_time_limit(300);
date_default_timezone_set('Asia/Manila');

require_once __DIR__ . '/../api/config/db.php';
require_once __DIR__ . '/../api/services/SmsService.php';

$isCli = (php_sapi_name() === 'cli');

// Determine parameters
$force = false;
$dryRun = false;

if ($isCli) {
    global $argv;
    $force = in_array('--force', $argv ?? []);
    $dryRun = in_array('--dry-run', $argv ?? []);
} else {
    $force = !empty($_GET['force']);
    $dryRun = !empty($_GET['dry_run']);
    
    // Web security token check (optional for local, configurable in production)
    $cronSecret = $_ENV['CRON_SECRET'] ?? getenv('CRON_SECRET') ?: 'mmwgh_cron_secret_2026';
    $providedToken = $_GET['token'] ?? '';
    
    // Allow local requests (localhost / 127.0.0.1) or valid token
    $remoteIp = $_SERVER['REMOTE_ADDR'] ?? '';
    $isLocal = in_array($remoteIp, ['127.0.0.1', '::1', 'localhost']);
    
    if (!$isLocal && $providedToken !== $cronSecret) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Unauthorized cron access. Invalid token.']);
        exit;
    }
}

$smsService = new SmsService($pdo);

// ── DRY-RUN PREVIEW MODE ─────────────────────────────────────────────────────
if ($dryRun) {
    $duePatients = $smsService->queryDueAppointments(2, 3, !$force);
    $report = [
        'mode'             => 'DRY-RUN (Preview Only)',
        'timestamp'        => date('Y-m-d H:i:s'),
        'target_date_min'  => date('Y-m-d', strtotime('+2 days')),
        'target_date_max'  => date('Y-m-d', strtotime('+3 days')),
        'total_found'      => count($duePatients),
        'patients'         => $duePatients
    ];

    if ($isCli) {
        echo "=== MMWGH LINKOD SMS CRON [DRY-RUN PREVIEW] ===\n";
        echo "Target Date Range: {$report['target_date_min']} to {$report['target_date_max']}\n";
        echo "Found: " . count($duePatients) . " eligible patients.\n\n";
        foreach ($duePatients as $idx => $p) {
            echo "[" . ($idx + 1) . "] {$p['patient_id']} - {$p['full_name']} | Date: {$p['appointment_date']} {$p['scheduled_time']} | Phone: {$p['contact_number']} | Status: {$p['status']}\n";
        }
        echo "\nNo SMS messages were dispatched (dry-run mode).\n";
        exit(0);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'dry_run' => true, 'report' => $report], JSON_PRETTY_PRINT);
        exit;
    }
}

// ── LIVE / SIMULATED CRON RUN ───────────────────────────────────────────────
if ($isCli) {
    echo "====================================================================\n";
    echo "  MMWGH LINKOD - AUTOMATED SMS APPOINTMENT REMINDER CRON TASK\n";
    echo "  Execution Time: " . date('Y-m-d H:i:s') . "\n";
    echo "====================================================================\n";
    echo "Querying database for Approved appointments 2 to 3 days in the future...\n";
    echo "Window: " . date('Y-m-d', strtotime('+2 days')) . " to " . date('Y-m-d', strtotime('+3 days')) . "\n\n";
}

$results = $smsService->runDailyReminders('Daily CRON Job', $force);

if ($isCli) {
    echo "Eligible Approved Patients Found: {$results['total_found']}\n";
    echo "--------------------------------------------------------------------\n";

    if ($results['total_found'] === 0) {
        echo "No upcoming approved appointments requiring SMS reminders today.\n";
    } else {
        foreach ($results['processed'] as $i => $item) {
            $num = $i + 1;
            $statusTag = $item['status'] === 'Sent' ? "[SENT]" : "[FAILED]";
            echo "{$statusTag} #{$num} {$item['patient_id']} - {$item['full_name']}\n";
            echo "       Contact: {$item['contact_number']} | Date: {$item['appointment_date']} at {$item['scheduled_time']}\n";
            echo "       Result:  {$item['details']}\n\n";
        }
    }

    echo "====================================================================\n";
    echo "SUMMARY:\n";
    echo "  - Total Eligible Found : {$results['total_found']}\n";
    echo "  - Successfully Sent    : {$results['sent_count']}\n";
    echo "  - Failed Deliveries    : {$results['failed_count']}\n";
    echo "  - Execution Duration   : {$results['duration_seconds']}s\n";
    echo "  - Dispatch Provider    : {$results['execution_mode']}\n";
    echo "====================================================================\n";
    exit(0);
} else {
    header('Content-Type: application/json');
    echo json_encode([
        'success'   => true,
        'message'   => "Automated SMS appointment reminder CRON task completed.",
        'summary'   => "Sent: {$results['sent_count']}, Failed: {$results['failed_count']}, Total Found: {$results['total_found']}",
        'execution' => $results
    ], JSON_PRETTY_PRINT);
    exit;
}
