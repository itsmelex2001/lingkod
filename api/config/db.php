<?php
/**
 * api/config/db.php
 * PDO database connection — reads credentials from the .env file.
 * Never hardcode passwords here; edit .env instead.
 */

// ── Load .env ────────────────────────────────────────────────────────────────
$envFile = __DIR__ . '/../../.env';   // sdn/api/config/ → up 2 levels → sdn/.env

if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) continue;
        [$key, $value] = array_map('trim', explode('=', $line, 2));
        $_ENV[$key] = $value;
        putenv("$key=$value");
    }
}

// ── Connect (falls back to XAMPP defaults if .env is missing) ────────────────
define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
define('DB_NAME', $_ENV['DB_NAME'] ?? 'scheduling_db');
define('DB_USER', $_ENV['DB_USER'] ?? 'root');
define('DB_PASS', $_ENV['DB_PASS'] ?? '');

if (!function_exists('createPdoConnection')) {
    function createPdoConnection(): PDO {
        return new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]
        );
    }
}

try {
    $pdo = createPdoConnection();
} catch (PDOException $e) {
    // Auto-start local XAMPP MySQL service if connection is refused (SQLSTATE[HY000] [2002])
    $mysqldPath = 'C:/xampp1/mysql/bin/mysqld.exe';
    $myIniPath  = 'C:/xampp1/mysql/bin/my.ini';
    if (str_contains($e->getMessage(), '2002') && file_exists($mysqldPath) && function_exists('popen')) {
        @pclose(@popen('start /B "" "' . $mysqldPath . '" --defaults-file="' . $myIniPath . '" --standalone', 'r'));
        usleep(2500000);
        try {
            $pdo = createPdoConnection();
        } catch (PDOException $retryException) {
            $e = $retryException;
        }
    }

    if (!isset($pdo)) {
        http_response_code(500);
        header('Content-Type: application/json');
        error_log("Database connection error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Internal database error. Please contact the administrator.']);
        exit;
    }
}

// ── Self-healing Schema Check ────────────────────────────────────────────────
try {
    $lockFile = sys_get_temp_dir() . '/sdn_schema_v8.lock';
    if (!file_exists($lockFile) && isset($pdo)) {
        $execSafe = static function (PDO $pdo, string $sql): void {
            try {
                $pdo->exec($sql);
            } catch (Throwable $e) {
                // Ignore individual idempotent DDL errors so subsequent migrations still run
            }
        };

        $pColsEarly = $pdo->query("SHOW COLUMNS FROM `patients`")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('rescheduled_by_staff', $pColsEarly, true)) {
            $execSafe($pdo, "ALTER TABLE `patients` ADD COLUMN `rescheduled_by_staff` TINYINT(1) NOT NULL DEFAULT 0");
        }
        if (!in_array('rescheduled_from_date', $pColsEarly, true)) {
            $execSafe($pdo, "ALTER TABLE `patients` ADD COLUMN `rescheduled_from_date` DATE NULL");
        }
        if (!in_array('rescheduled_at', $pColsEarly, true)) {
            $execSafe($pdo, "ALTER TABLE `patients` ADD COLUMN `rescheduled_at` DATETIME NULL");
        }

        @touch($lockFile);
        $uCols = $pdo->query("SHOW COLUMNS FROM `users`")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('email', $uCols, true)) {
            @$pdo->exec("ALTER TABLE `users` ADD COLUMN `email` VARCHAR(120) NULL UNIQUE AFTER `username`");
        }
        if (!in_array('patient_id', $uCols, true)) {
            @$pdo->exec("ALTER TABLE `users` ADD COLUMN `patient_id` INT NULL AFTER `department`");
        }
        if (!in_array('profile_image', $uCols, true)) {
            @$pdo->exec("ALTER TABLE `users` ADD COLUMN `profile_image` VARCHAR(255) NULL AFTER `department`");
        }

        $pCols = $pdo->query("SHOW COLUMNS FROM `patients`")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('user_id', $pCols, true)) {
            @$pdo->exec("ALTER TABLE `patients` ADD COLUMN `user_id` INT NULL AFTER `id`");
        }
        if (!in_array('reference_no', $pCols, true)) {
            @$pdo->exec("ALTER TABLE `patients` ADD COLUMN `reference_no` VARCHAR(20) NULL AFTER `patient_id`");
        }
        if (!in_array('first_name', $pCols, true)) {
            @$pdo->exec("ALTER TABLE `patients` ADD COLUMN `first_name` VARCHAR(60) NULL AFTER `full_name`");
        }
        if (!in_array('last_name', $pCols, true)) {
            @$pdo->exec("ALTER TABLE `patients` ADD COLUMN `last_name` VARCHAR(60) NULL AFTER `first_name`");
        }
        if (!in_array('middle_initial', $pCols, true)) {
            @$pdo->exec("ALTER TABLE `patients` ADD COLUMN `middle_initial` VARCHAR(10) NULL AFTER `last_name`");
        }
        if (!in_array('username', $pCols, true)) {
            @$pdo->exec("ALTER TABLE `patients` ADD COLUMN `username` VARCHAR(80) NULL AFTER `email`");
        }
        if (!in_array('password', $pCols, true)) {
            @$pdo->exec("ALTER TABLE `patients` ADD COLUMN `password` VARCHAR(255) NULL AFTER `email`");
        }
        if (!in_array('is_verified', $pCols, true)) {
            @$pdo->exec("ALTER TABLE `patients` ADD COLUMN `is_verified` TINYINT(1) NOT NULL DEFAULT 1 AFTER `password`");
        }
        if (!in_array('email_verified', $pCols, true)) {
            @$pdo->exec("ALTER TABLE `patients` ADD COLUMN `email_verified` TINYINT(1) NOT NULL DEFAULT 0 AFTER `password`");
        }
        if (!in_array('hrn', $pCols, true)) {
            @$pdo->exec("ALTER TABLE `patients` ADD COLUMN `hrn` VARCHAR(50) NULL AFTER `patient_id`");
        }
        if (!in_array('receiver', $pCols, true)) {
            @$pdo->exec("ALTER TABLE `patients` ADD COLUMN `receiver` VARCHAR(100) NULL AFTER `department`");
        }
        if (!in_array('informant', $pCols, true)) {
            @$pdo->exec("ALTER TABLE `patients` ADD COLUMN `informant` VARCHAR(100) NULL AFTER `contact_number`");
        }
        if (!in_array('with_companion', $pCols, true)) {
            @$pdo->exec("ALTER TABLE `patients` ADD COLUMN `with_companion` TINYINT(1) NOT NULL DEFAULT 0 AFTER `address`");
        }
        if (!in_array('relationship_to_patient', $pCols, true)) {
            @$pdo->exec("ALTER TABLE `patients` ADD COLUMN `relationship_to_patient` VARCHAR(50) NULL AFTER `with_companion`");
        }
        if (!in_array('appointment_recipient', $pCols, true)) {
            @$pdo->exec("ALTER TABLE `patients` ADD COLUMN `appointment_recipient` VARCHAR(50) NULL DEFAULT 'Patient (Me)' AFTER `relationship_to_patient`");
        }
        if (!in_array('existing_medical_conditions', $pCols, true)) {
            @$pdo->exec("ALTER TABLE `patients` ADD COLUMN `existing_medical_conditions` TEXT NULL AFTER `illness_description`");
        }
        if (!in_array('current_medications', $pCols, true)) {
            @$pdo->exec("ALTER TABLE `patients` ADD COLUMN `current_medications` TEXT NULL AFTER `existing_medical_conditions`");
        }
        if (!in_array('referral_source', $pCols, true)) {
            @$pdo->exec("ALTER TABLE `patients` ADD COLUMN `referral_source` VARCHAR(150) NULL");
        }
        if (!in_array('recommendation_remarks', $pCols, true)) {
            @$pdo->exec("ALTER TABLE `patients` ADD COLUMN `recommendation_remarks` TEXT NULL AFTER `illness_description`");
        }
        if (!in_array('unscheduled_walkin', $pCols, true)) {
            @$pdo->exec("ALTER TABLE `patients` ADD COLUMN `unscheduled_walkin` TINYINT(1) NOT NULL DEFAULT 0 AFTER `recommendation_remarks`");
        }
        if (!in_array('inquiry_only', $pCols, true)) {
            @$pdo->exec("ALTER TABLE `patients` ADD COLUMN `inquiry_only` TINYINT(1) NOT NULL DEFAULT 0 AFTER `unscheduled_walkin`");
        }
        if (!in_array('mode_of_inquiry', $pCols, true)) {
            @$pdo->exec("ALTER TABLE `patients` ADD COLUMN `mode_of_inquiry` VARCHAR(50) NULL AFTER `inquiry_only`");
        }
        if (!in_array('employee_type', $pCols, true)) {
            @$pdo->exec("ALTER TABLE `patients` ADD COLUMN `employee_type` VARCHAR(50) NULL AFTER `mode_of_inquiry`");
        }
        if (!in_array('select_type', $pCols, true)) {
            @$pdo->exec("ALTER TABLE `patients` ADD COLUMN `select_type` VARCHAR(50) NULL AFTER `employee_type`");
        }
        if (!in_array('specialty', $pCols, true)) {
            @$pdo->exec("ALTER TABLE `patients` ADD COLUMN `specialty` VARCHAR(100) NULL AFTER `assigned_doctor`");
        }
        if (!in_array('scheduled_time', $pCols, true)) {
            @$pdo->exec("ALTER TABLE `patients` ADD COLUMN `scheduled_time` TIME NULL AFTER `appointment_date`");
        }
        if (!in_array('preferred_time_slot', $pCols, true)) {
            @$pdo->exec("ALTER TABLE `patients` ADD COLUMN `preferred_time_slot` VARCHAR(60) NULL AFTER `scheduled_time`");
        }
        if (!in_array('medical_history_diagnosis', $pCols, true)) {
            @$pdo->exec("ALTER TABLE `patients` ADD COLUMN `medical_history_diagnosis` TEXT NULL");
        }
        if (!in_array('medical_history_treatment', $pCols, true)) {
            @$pdo->exec("ALTER TABLE `patients` ADD COLUMN `medical_history_treatment` TEXT NULL");
        }
        if (!in_array('medical_history_prescription', $pCols, true)) {
            @$pdo->exec("ALTER TABLE `patients` ADD COLUMN `medical_history_prescription` TEXT NULL");
        }
        if (!in_array('medical_history_recorded_by', $pCols, true)) {
            @$pdo->exec("ALTER TABLE `patients` ADD COLUMN `medical_history_recorded_by` VARCHAR(100) NULL");
        }
        if (!in_array('medical_history_date', $pCols, true)) {
            @$pdo->exec("ALTER TABLE `patients` ADD COLUMN `medical_history_date` DATETIME NULL");
        }
        if (!in_array('active_medications', $pCols, true)) {
            @$pdo->exec("ALTER TABLE `patients` ADD COLUMN `active_medications` TEXT NULL");
        }
        if (!in_array('next_visit_date', $pCols, true)) {
            @$pdo->exec("ALTER TABLE `patients` ADD COLUMN `next_visit_date` DATE NULL");
        }
        if (!in_array('reminder_status', $pCols, true)) {
            @$pdo->exec("ALTER TABLE `patients` ADD COLUMN `reminder_status` VARCHAR(50) NULL");
        }
        if (!in_array('reminder_sent_at', $pCols, true)) {
            @$pdo->exec("ALTER TABLE `patients` ADD COLUMN `reminder_sent_at` DATETIME NULL");
        }
        if (!in_array('1_day_reminder_sent', $pCols, true)) {
            @$pdo->exec("ALTER TABLE `patients` ADD COLUMN `1_day_reminder_sent` TINYINT(1) NOT NULL DEFAULT 0");
        }
        if (!in_array('day_of_reminder_sent', $pCols, true)) {
            @$pdo->exec("ALTER TABLE `patients` ADD COLUMN `day_of_reminder_sent` TINYINT(1) NOT NULL DEFAULT 0");
        }
        @$pdo->exec("ALTER TABLE `patients` MODIFY COLUMN `status` VARCHAR(40) NOT NULL DEFAULT 'for_approval'");

        $hasAppointments = $pdo->query("SHOW TABLES LIKE 'appointments'")->fetchColumn();
        if ($hasAppointments) {
            $aCols = $pdo->query("SHOW COLUMNS FROM `appointments`")->fetchAll(PDO::FETCH_COLUMN);
            if (!in_array('reference_no', $aCols, true)) {
                @$pdo->exec("ALTER TABLE `appointments` ADD COLUMN `reference_no` VARCHAR(20) NULL AFTER `id`");
            }
            if (!in_array('relationship_to_patient', $aCols, true)) {
                @$pdo->exec("ALTER TABLE `appointments` ADD COLUMN `relationship_to_patient` VARCHAR(50) NULL");
            }
            if (!in_array('consultation_mode', $aCols, true)) {
                @$pdo->exec("ALTER TABLE `appointments` ADD COLUMN `consultation_mode` VARCHAR(50) NULL");
            }
            if (!in_array('preferred_time_slot', $aCols, true)) {
                @$pdo->exec("ALTER TABLE `appointments` ADD COLUMN `preferred_time_slot` VARCHAR(60) NULL");
            }
        }

        @$pdo->exec("
            CREATE TABLE IF NOT EXISTS `progress_notes` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `patient_id` VARCHAR(50) NOT NULL,
                `note` TEXT NOT NULL,
                `noted_by` VARCHAR(100) NOT NULL,
                `blood_pressure` VARCHAR(20) DEFAULT NULL,
                `temperature` DECIMAL(5,2) DEFAULT NULL,
                `heart_rate` INT DEFAULT NULL,
                `weight` DECIMAL(5,2) DEFAULT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX `idx_progress_patient` (`patient_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $dCols = $pdo->query("SHOW COLUMNS FROM `doctors`")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('total_slots', $dCols, true)) {
            @$pdo->exec("ALTER TABLE `doctors` ADD COLUMN `total_slots` INT NOT NULL DEFAULT 20");
        }
        if (!in_array('profile_image', $dCols, true)) {
            @$pdo->exec("ALTER TABLE `doctors` ADD COLUMN `profile_image` VARCHAR(255) NULL");
        }
        @$pdo->exec("ALTER TABLE `doctors` MODIFY COLUMN `status` VARCHAR(30) NOT NULL DEFAULT 'in'");
    }

    // Runs even after the schema lock. Account profiles with no visit were
    // stored as pending and counted as approvals.
    @$pdo->exec("
        UPDATE `patients`
        SET `status` = 'registered'
        WHERE LOWER(TRIM(COALESCE(`status`, ''))) IN ('pending', 'for_approval', 'for_processing', 'for approval')
          AND (`appointment_date` IS NULL OR `appointment_date` = '0000-00-00')
          AND (`department` IS NULL OR TRIM(`department`) = '')
          AND (`chief_complaints` IS NULL OR TRIM(`chief_complaints`) = '')
          AND (`consultation_nature` IS NULL OR TRIM(`consultation_nature`) = '')
          AND COALESCE(`unscheduled_walkin`, 0) = 0
          AND COALESCE(`inquiry_only`, 0) = 0
    ");
} catch (Throwable $ignore) {
    // Continue gracefully
}
