<?php
/**
 * api/config/db.php
 * Dual-driver PDO connection (MySQL for local XAMPP, PostgreSQL for Supabase/Vercel).
 * Reads Vercel/system environment variables first, falling back to local .env.
 */

// ── Load local .env without overwriting existing platform env vars ───────────
$envFile = __DIR__ . '/../../.env';
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = array_map('trim', explode('=', $line, 2));
        $value = trim($value, "\"'");
        if (getenv($key) === false && !array_key_exists($key, $_ENV)) {
            $_ENV[$key] = $value;
            putenv("$key=$value");
        }
    }
}

if (!function_exists('envValue')) {
    function envValue(string $key, ?string $default = null): ?string
    {
        $val = getenv($key);
        if ($val !== false && $val !== '') {
            return $val;
        }
        if (isset($_ENV[$key]) && $_ENV[$key] !== '') {
            return (string) $_ENV[$key];
        }
        if (isset($_SERVER[$key]) && $_SERVER[$key] !== '') {
            return (string) $_SERVER[$key];
        }
        return $default;
    }
}

// ── Parse DATABASE_URL (Supabase URI) or individual DB_* variables ───────────
$databaseUrl = envValue('DATABASE_URL');
$parsedDriver = strtolower((string) envValue('DB_DRIVER', 'mysql'));
$parsedHost   = (string) envValue('DB_HOST', 'localhost');
$parsedPort   = (string) envValue('DB_PORT', '');
$parsedName   = (string) envValue('DB_NAME', 'scheduling_db');
$parsedUser   = (string) envValue('DB_USER', 'root');
$parsedPass   = (string) envValue('DB_PASS', '');
$parsedSsl    = (string) envValue('DB_SSLMODE', 'require');

if ($databaseUrl) {
    $parts = parse_url($databaseUrl);
    if (is_array($parts)) {
        $scheme = strtolower($parts['scheme'] ?? '');
        if ($scheme === 'postgres' || $scheme === 'postgresql') {
            $parsedDriver = 'pgsql';
        } elseif ($scheme === 'mysql') {
            $parsedDriver = 'mysql';
        }
        if (!empty($parts['host'])) $parsedHost = $parts['host'];
        if (!empty($parts['port'])) $parsedPort = (string) $parts['port'];
        if (!empty($parts['path'])) $parsedName = ltrim($parts['path'], '/');
        if (isset($parts['user']))  $parsedUser = rawurldecode($parts['user']);
        if (isset($parts['pass']))  $parsedPass = rawurldecode($parts['pass']);
        if (!empty($parts['query'])) {
            parse_str($parts['query'], $qParams);
            if (!empty($qParams['sslmode'])) {
                $parsedSsl = (string) $qParams['sslmode'];
            }
        }
    }
}

if (!defined('DB_DRIVER'))  define('DB_DRIVER', $parsedDriver === 'pgsql' ? 'pgsql' : 'mysql');
if (!defined('DB_HOST'))    define('DB_HOST', $parsedHost);
if (!defined('DB_PORT'))    define('DB_PORT', $parsedPort !== '' ? $parsedPort : (DB_DRIVER === 'pgsql' ? '6543' : '3306'));
if (!defined('DB_NAME'))    define('DB_NAME', $parsedName);
if (!defined('DB_USER'))    define('DB_USER', $parsedUser);
if (!defined('DB_PASS'))    define('DB_PASS', $parsedPass);
if (!defined('DB_SSLMODE')) define('DB_SSLMODE', $parsedSsl);

// ── Cross-Dialect SQL Helpers (MySQL + PostgreSQL) ───────────────────────────
if (!function_exists('dbDriver')) {
    function dbDriver(): string
    {
        return DB_DRIVER;
    }

    function dbIsPgsql(): bool
    {
        return DB_DRIVER === 'pgsql';
    }

    function dbQuoteIdent(string $ident): string
    {
        return dbIsPgsql() ? '"' . str_replace('"', '""', $ident) . '"' : '`' . str_replace('`', '``', $ident) . '`';
    }

    function dbDateFormat(string $expr, string $mysqlFormat): string
    {
        if (!dbIsPgsql()) {
            return "DATE_FORMAT($expr, '$mysqlFormat')";
        }
        $pgFormat = strtr($mysqlFormat, [
            '%Y' => 'YYYY',
            '%y' => 'YY',
            '%m' => 'MM',
            '%b' => 'Mon',
            '%M' => 'FMMonth',
            '%d' => 'DD',
            '%e' => 'FMDD',
            '%H' => 'HH24',
            '%h' => 'HH12',
            '%i' => 'MI',
            '%s' => 'SS',
            '%p' => 'AM',
            '%W' => 'FMDay',
            '%a' => 'Dy',
        ]);
        return "TO_CHAR($expr, '$pgFormat')";
    }

    function dbDateSubDays(int $days): string
    {
        $days = max(0, $days);
        return dbIsPgsql()
            ? "(CURRENT_DATE - INTERVAL '$days days')"
            : "DATE_SUB(CURDATE(), INTERVAL $days DAY)";
    }

    function dbDateAddDays(int $days): string
    {
        $days = max(0, $days);
        return dbIsPgsql()
            ? "(CURRENT_DATE + INTERVAL '$days days')"
            : "DATE_ADD(CURDATE(), INTERVAL $days DAY)";
    }

    function dbNowSubInterval(int $amount, string $unit): string
    {
        $amount = max(0, $amount);
        $u = strtoupper($unit);
        $allowed = ['SECOND', 'MINUTE', 'HOUR', 'DAY', 'MONTH', 'YEAR'];
        if (!in_array($u, $allowed, true)) {
            $u = 'DAY';
        }
        return dbIsPgsql()
            ? "(CURRENT_TIMESTAMP - INTERVAL '$amount " . strtolower($u) . "s')"
            : "DATE_SUB(NOW(), INTERVAL $amount $u)";
    }

    function dbTableColumns(PDO $pdo, string $table): array
    {
        if (dbIsPgsql()) {
            $stmt = $pdo->prepare(
                "SELECT column_name FROM information_schema.columns WHERE table_schema = 'public' AND table_name = ?"
            );
            $stmt->execute([$table]);
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        }
        return $pdo->query("SHOW COLUMNS FROM `" . str_replace('`', '', $table) . "`")->fetchAll(PDO::FETCH_COLUMN);
    }

    function dbTableExists(PDO $pdo, string $table): bool
    {
        if (dbIsPgsql()) {
            $stmt = $pdo->prepare(
                "SELECT 1 FROM information_schema.tables WHERE table_schema = 'public' AND table_name = ? LIMIT 1"
            );
            $stmt->execute([$table]);
            return (bool) $stmt->fetchColumn();
        }
        $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$table]);
        return (bool) $stmt->fetchColumn();
    }

    function dbNextPatientId(PDO $pdo): string
    {
        $year = date('Y');
        $stmt = $pdo->prepare('SELECT patient_id FROM patients WHERE patient_id LIKE ?');
        $stmt->execute(["PT-$year-%"]);
        $max = 0;
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $pid) {
            if (preg_match('/^PT-' . preg_quote($year, '/') . '-(\d+)$/', (string) $pid, $m)) {
                $n = (int) $m[1];
                if ($n > $max) {
                    $max = $n;
                }
            }
        }
        return 'PT-' . $year . '-' . str_pad((string) ($max + 1), 3, '0', STR_PAD_LEFT);
    }
}

if (!function_exists('createPdoConnection')) {
    function createPdoConnection(): PDO
    {
        if (DB_DRIVER === 'pgsql') {
            $dsn = sprintf(
                'pgsql:host=%s;port=%s;dbname=%s;sslmode=%s',
                DB_HOST,
                DB_PORT,
                DB_NAME,
                DB_SSLMODE
            );
            // PgBouncer transaction pooler (Supabase port 6543) requires emulated prepares
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => true,
            ]);
            $pdo->exec("SET TIME ZONE 'Asia/Manila'");
            return $pdo;
        }

        return new PDO(
            'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4',
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
    // Auto-start local XAMPP MySQL service only on local Windows MySQL
    $mysqldPath = 'C:/xampp1/mysql/bin/mysqld.exe';
    $myIniPath  = 'C:/xampp1/mysql/bin/my.ini';
    if (DB_DRIVER === 'mysql' && str_contains($e->getMessage(), '2002') && file_exists($mysqldPath) && function_exists('popen')) {
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
        error_log('Database connection error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Internal database error. Please contact the administrator.']);
        exit;
    }
}

// Register Postgres-backed session handler when running on Supabase/Vercel
require_once __DIR__ . '/session_handler.php';
initCloudSessionHandler($pdo);

// ── Self-healing Schema Check (Local MySQL only; Supabase uses database/supabase_schema.sql) ──
if (!dbIsPgsql()) {
    try {
        $lockFile = sys_get_temp_dir() . '/sdn_schema_v10.lock';
        if (!file_exists($lockFile) && isset($pdo)) {
            $execSafe = static function (PDO $pdo, string $sql): void {
                try {
                    $pdo->exec($sql);
                } catch (Throwable $e) {
                    error_log('Schema migration warning: ' . $e->getMessage());
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
            if (!in_array('teleconsult_platform', $pColsEarly, true)) {
                $execSafe($pdo, "ALTER TABLE `patients` ADD COLUMN `teleconsult_platform` VARCHAR(50) NULL");
            }
            if (!in_array('teleconsult_link', $pColsEarly, true)) {
                $execSafe($pdo, "ALTER TABLE `patients` ADD COLUMN `teleconsult_link` VARCHAR(500) NULL");
            }
            if (!in_array('teleconsult_notes', $pColsEarly, true)) {
                $execSafe($pdo, "ALTER TABLE `patients` ADD COLUMN `teleconsult_notes` VARCHAR(255) NULL");
            }

            $pColsVerified = $pdo->query("SHOW COLUMNS FROM `patients`")->fetchAll(PDO::FETCH_COLUMN);
            $requiredNewCols = ['rescheduled_by_staff', 'rescheduled_from_date', 'rescheduled_at', 'teleconsult_platform', 'teleconsult_link', 'teleconsult_notes'];
            if (count(array_intersect($requiredNewCols, $pColsVerified)) === count($requiredNewCols)) {
                @touch($lockFile);
            }
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
    } catch (Throwable $ignore) {
        // Continue gracefully
    }
}

// Normalize registered-only account profiles (PostgreSQL + MySQL safe)
try {
    $emptyDateClause = dbIsPgsql()
        ? 'appointment_date IS NULL'
        : "(appointment_date IS NULL OR appointment_date = '0000-00-00')";
    @$pdo->exec("
        UPDATE patients
        SET status = 'registered'
        WHERE LOWER(TRIM(COALESCE(status, ''))) IN ('pending', 'for_approval', 'for_processing', 'for approval')
          AND $emptyDateClause
          AND (department IS NULL OR TRIM(department) = '')
          AND (chief_complaints IS NULL OR TRIM(chief_complaints) = '')
          AND (consultation_nature IS NULL OR TRIM(consultation_nature) = '')
          AND COALESCE(unscheduled_walkin, 0) = 0
          AND COALESCE(inquiry_only, 0) = 0
    ");
} catch (Throwable $ignore) {
    // Continue gracefully
}
