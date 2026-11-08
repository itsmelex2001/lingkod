<?php
declare(strict_types=1);

/**
 * cron_reminders.php
 * Automated Scheduled Task (CRON Job) for MMWGH LINGKOD.
 * Dispatches logistical Email & SMS reminders for upcoming approved visits.
 *
 * CRITICAL PRIVACY RULE:
 * Never include department name, doctor's name, chief complaint, or diagnosis.
 * Only logistical info: Patient Name, Scheduled Date, Clinic Time, Reference ID, Outpatient Triage.
 *
 * USAGE:
 *   CLI: php c:\xampp1\htdocs\sdn\cron_reminders.php
 *   Web: http://localhost/sdn/cron_reminders.php?token=mmwgh_cron_secret_2026
 */

date_default_timezone_set('Asia/Manila');

$isCli = (php_sapi_name() === 'cli');

if (!$isCli) {
    header('Content-Type: application/json; charset=UTF-8');
    $secret = $_ENV['CRON_SECRET'] ?? getenv('CRON_SECRET') ?: 'mmwgh_cron_secret_2026';
    $token  = $_GET['token'] ?? '';
    $ip     = $_SERVER['REMOTE_ADDR'] ?? '';
    $isLocal = in_array($ip, ['127.0.0.1', '::1', 'localhost'], true);

    if (!$isLocal && $token !== $secret) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Forbidden: Invalid cron secret token.']);
        exit;
    }
}

require_once __DIR__ . '/api/config/db.php';
require_once __DIR__ . '/api/services/EmailService.php';
require_once __DIR__ . '/api/services/SmsService.php';

$output = [
    'timestamp' => date('Y-m-d H:i:s'),
    'scenario_1_day_before' => ['found' => 0, 'emails_sent' => 0, 'sms_sent' => 0],
    'scenario_2_day_of'     => ['found' => 0, 'emails_sent' => 0, 'sms_sent' => 0],
    'errors'                => []
];

if ($isCli) {
    echo "====================================================================\n";
    echo "  MMWGH LINGKOD - AUTOMATED REMINDER CRON TASK\n";
    echo "  Execution Timestamp: " . $output['timestamp'] . "\n";
    echo "====================================================================\n\n";
}

// ─────────────────────────────────────────────────────────────────────────────
// SCENARIO 1: The 1-Day Reminder
// Approved visits happening tomorrow (CURDATE() + INTERVAL 1 DAY)
// where `1_day_reminder_sent` = 0
// ─────────────────────────────────────────────────────────────────────────────
try {
    $stmt1 = $pdo->prepare("
        SELECT id, patient_id, full_name, email, contact_number,
               appointment_date, scheduled_time
        FROM patients
        WHERE status = 'approved'
          AND appointment_date = DATE_ADD(CURDATE(), INTERVAL 1 DAY)
          AND `1_day_reminder_sent` = 0
        ORDER BY scheduled_time ASC
        LIMIT 200
    ");
    $stmt1->execute();
    $tomorrowRows = $stmt1->fetchAll(PDO::FETCH_ASSOC);

    $output['scenario_1_day_before']['found'] = count($tomorrowRows);
    if ($isCli) {
        echo "[Scenario 1] Found " . count($tomorrowRows) . " appointment(s) scheduled for tomorrow.\n";
    }

    $upd1 = $pdo->prepare("
        UPDATE patients
        SET `1_day_reminder_sent` = 1,
            `reminder_status`     = '1-Day Sent',
            `reminder_sent_at`    = NOW(),
            `updated_at`          = NOW()
        WHERE id = ?
    ");

    foreach ($tomorrowRows as $row) {
        $dateFormatted = date('M j, Y', strtotime($row['appointment_date']));
        $timeFormatted = !empty($row['scheduled_time']) 
            ? date('g:i A', strtotime($row['scheduled_time'])) 
            : '8:00 AM';
        $refId         = $row['patient_id'];

        $emailOk = false;
        $smsOk   = false;

        // 1. Email (PHPMailer)
        if (!empty($row['email'])) {
            try {
                $emailOk = EmailService::sendAppointmentEmail(
                    $row['email'],
                    $row['full_name'],
                    $dateFormatted,
                    $timeFormatted,
                    $refId,
                    '1day'
                );
                if ($emailOk) $output['scenario_1_day_before']['emails_sent']++;
            } catch (\Throwable $e) {
                error_log("[Cron 1-Day Email] ID {$row['id']}: " . $e->getMessage());
                $output['errors'][] = "Scenario 1 Email ID {$row['id']}: " . $e->getMessage();
            }
        }

        // 2. SMS (Semaphore)
        if (!empty($row['contact_number'])) {
            try {
                $smsOk = SmsService::sendOneDayReminderSms(
                    $row['contact_number'],
                    $dateFormatted,
                    $timeFormatted,
                    $refId
                );
                if ($smsOk) $output['scenario_1_day_before']['sms_sent']++;
            } catch (\Throwable $e) {
                error_log("[Cron 1-Day SMS] ID {$row['id']}: " . $e->getMessage());
                $output['errors'][] = "Scenario 1 SMS ID {$row['id']}: " . $e->getMessage();
            }
        }

        // Mark flag to true
        $upd1->execute([$row['id']]);

        if ($isCli) {
            echo "  -> [1-Day] {$refId} ({$row['full_name']}) | Email: " . ($emailOk ? 'Sent' : 'Skipped/Fail') . " | SMS: " . ($smsOk ? 'Sent' : 'Skipped/Fail') . "\n";
        }
    }
} catch (\Throwable $e) {
    error_log("[Cron] Scenario 1 error: " . $e->getMessage());
    $output['errors'][] = "Scenario 1 query failure: " . $e->getMessage();
    if ($isCli) echo "[Scenario 1 Error] " . $e->getMessage() . "\n";
}

// ─────────────────────────────────────────────────────────────────────────────
// SCENARIO 2: The Day-Of / 2-Hours-Before Reminder
// Approved visits happening today (CURDATE()) where scheduled_time is within
// the next 2 hours (between current time and NOW() + 2 hours 15 mins),
// and `day_of_reminder_sent` = 0
// ─────────────────────────────────────────────────────────────────────────────
try {
    $stmt2 = $pdo->prepare("
        SELECT id, patient_id, full_name, email, contact_number,
               appointment_date, scheduled_time
        FROM patients
        WHERE status = 'approved'
          AND appointment_date = CURDATE()
          AND `day_of_reminder_sent` = 0
          AND scheduled_time IS NOT NULL
          AND scheduled_time BETWEEN CURTIME() AND ADDTIME(CURTIME(), '02:15:00')
        ORDER BY scheduled_time ASC
        LIMIT 200
    ");
    $stmt2->execute();
    $dayOfRows = $stmt2->fetchAll(PDO::FETCH_ASSOC);

    $output['scenario_2_day_of']['found'] = count($dayOfRows);
    if ($isCli) {
        echo "\n[Scenario 2] Found " . count($dayOfRows) . " appointment(s) due today within ~2 hours.\n";
    }

    $upd2 = $pdo->prepare("
        UPDATE patients
        SET `day_of_reminder_sent` = 1,
            `reminder_status`      = 'Day-Of Sent',
            `reminder_sent_at`     = NOW(),
            `updated_at`           = NOW()
        WHERE id = ?
    ");

    foreach ($dayOfRows as $row) {
        $dateFormatted = date('M j, Y', strtotime($row['appointment_date']));
        $timeFormatted = !empty($row['scheduled_time']) 
            ? date('g:i A', strtotime($row['scheduled_time'])) 
            : '8:00 AM';
        $refId         = $row['patient_id'];

        $emailOk = false;
        $smsOk   = false;

        // 1. Email (PHPMailer)
        if (!empty($row['email'])) {
            try {
                $emailOk = EmailService::sendAppointmentEmail(
                    $row['email'],
                    $row['full_name'],
                    $dateFormatted,
                    $timeFormatted,
                    $refId,
                    'dayof'
                );
                if ($emailOk) $output['scenario_2_day_of']['emails_sent']++;
            } catch (\Throwable $e) {
                error_log("[Cron Day-Of Email] ID {$row['id']}: " . $e->getMessage());
                $output['errors'][] = "Scenario 2 Email ID {$row['id']}: " . $e->getMessage();
            }
        }

        // 2. SMS (Semaphore)
        if (!empty($row['contact_number'])) {
            try {
                $smsOk = SmsService::sendDayOfReminderSms(
                    $row['contact_number'],
                    $timeFormatted,
                    $refId
                );
                if ($smsOk) $output['scenario_2_day_of']['sms_sent']++;
            } catch (\Throwable $e) {
                error_log("[Cron Day-Of SMS] ID {$row['id']}: " . $e->getMessage());
                $output['errors'][] = "Scenario 2 SMS ID {$row['id']}: " . $e->getMessage();
            }
        }

        // Mark flag to true
        $upd2->execute([$row['id']]);

        if ($isCli) {
            echo "  -> [Day-Of] {$refId} ({$row['full_name']}) at {$timeFormatted} | Email: " . ($emailOk ? 'Sent' : 'Skipped/Fail') . " | SMS: " . ($smsOk ? 'Sent' : 'Skipped/Fail') . "\n";
        }
    }
} catch (\Throwable $e) {
    error_log("[Cron] Scenario 2 error: " . $e->getMessage());
    $output['errors'][] = "Scenario 2 query failure: " . $e->getMessage();
    if ($isCli) echo "[Scenario 2 Error] " . $e->getMessage() . "\n";
}

if ($isCli) {
    echo "\n====================================================================\n";
    echo "  CRON EXECUTION SUMMARY\n";
    echo "  1-Day Before: " . $output['scenario_1_day_before']['found'] . " found, " . $output['scenario_1_day_before']['emails_sent'] . " emails, " . $output['scenario_1_day_before']['sms_sent'] . " SMS\n";
    echo "  Day-Of 2-Hrs: " . $output['scenario_2_day_of']['found'] . " found, " . $output['scenario_2_day_of']['emails_sent'] . " emails, " . $output['scenario_2_day_of']['sms_sent'] . " SMS\n";
    echo "====================================================================\n";
} else {
    echo json_encode(['success' => true, 'report' => $output], JSON_PRETTY_PRINT);
}

