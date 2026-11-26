<?php
declare(strict_types=1);

/**
 * api/config/settings_helper.php
 * Shared runtime reader & rule enforcer for LINGKOD System Settings.
 * Used by booking, registration, OTP auth, session timeout guards, footer/modals, and backups.
 */

function defaultRuntimeSettings(): array {
    return [
        'hospital_name'                   => 'Mariveles Mental Wellness and General Hospital',
        'hospital_tagline'                => 'LINGKOD Outpatient Scheduling & Patient Portal',
        'contact_phone'                   => '(047) 935-4645',
        'emergency_hotline'               => '(047) 935-4138',
        'contact_email'                   => 'support@mmwgh.gov.ph',
        'hospital_address'                => 'P. Monroe St., Poblacion, Mariveles, Bataan',
        'clinic_hours'                    => [
            'monday'    => ['is_open' => true,  'open' => '08:00', 'close' => '17:00'],
            'tuesday'   => ['is_open' => true,  'open' => '08:00', 'close' => '17:00'],
            'wednesday' => ['is_open' => true,  'open' => '08:00', 'close' => '17:00'],
            'thursday'  => ['is_open' => true,  'open' => '08:00', 'close' => '17:00'],
            'friday'    => ['is_open' => true,  'open' => '08:00', 'close' => '17:00'],
            'saturday'  => ['is_open' => false, 'open' => '08:00', 'close' => '12:00'],
            'sunday'    => ['is_open' => false, 'open' => '08:00', 'close' => '12:00'],
        ],
        'hospital_holidays'               => [],
        'qr_code_expiry_hours'            => 48,
        'otp_provider'                    => 'smtp',
        'otp_api_url'                     => '',
        'otp_api_key'                     => '',
        'otp_sender_id'                   => 'LINGKOD',
        'otp_expiry_minutes'              => 10,
        'otp_max_attempts'                => 5,
        'username_min_length'             => 5,
        'username_max_length'             => 30,
        'username_pattern'                => 'alphanumeric_underscore',
        'username_prefix'                 => '',
        'session_timeout_minutes'         => 30,
        'patient_session_timeout_minutes' => 60,
        'auto_backup_enabled'             => false,
        'auto_backup_frequency'           => 'daily',
    ];
}

function getRuntimeSettings(?PDO $pdo): array {
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }

    $settings = defaultRuntimeSettings();
    if (!$pdo) {
        return $settings;
    }

    try {
        $stmt = $pdo->query('SELECT setting_key, setting_value, value_type FROM system_settings');
        if ($stmt) {
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $key  = (string)$row['setting_key'];
                $val  = $row['setting_value'];
                $type = (string)($row['value_type'] ?? 'string');
                if ($val === null || $val === '') {
                    continue;
                }
                $settings[$key] = match ($type) {
                    'integer'   => (int)$val,
                    'boolean'   => filter_var($val, FILTER_VALIDATE_BOOLEAN),
                    'json'      => json_decode((string)$val, true) ?: ($settings[$key] ?? []),
                    'encrypted' => (string)$val,
                    default     => (string)$val,
                };
            }
        }
    } catch (Throwable $ignore) {
        // Fallback to defaults if table is not yet initialized
    }

    $cache = $settings;
    return $cache;
}

function getSettingValue(?PDO $pdo, string $key, mixed $default = null): mixed {
    $all = getRuntimeSettings($pdo);
    return array_key_exists($key, $all) ? $all[$key] : $default;
}

function normalizeDepartmentCode(string $rawDept): string {
    $d = strtolower(trim($rawDept));
    if (str_contains($d, 'neuro')) return 'neurology';
    if (str_contains($d, 'psych') || str_contains($d, 'opu')) return 'psychiatry';
    if (str_contains($d, 'general') || str_contains($d, 'medical')) return 'general';
    return $d;
}

function getDepartmentRule(?PDO $pdo, string $rawDept): ?array {
    if (!$pdo) return null;
    $code = normalizeDepartmentCode($rawDept);
    if ($code === '') return null;

    try {
        $stmt = $pdo->prepare('SELECT * FROM department_scheduling_rules WHERE department_code = ? LIMIT 1');
        $stmt->execute([$code]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    } catch (Throwable $ignore) {
        return null;
    }
}

function getQrExpiryHours(?PDO $pdo, string $rawDept = ''): int {
    $defaultHours = max(1, (int)getSettingValue($pdo, 'qr_code_expiry_hours', 48));
    if ($rawDept !== '') {
        $rule = getDepartmentRule($pdo, $rawDept);
        if ($rule && !empty($rule['qr_expiry_hours_override'])) {
            return max(1, (int)$rule['qr_expiry_hours_override']);
        }
    }
    return $defaultHours;
}

/**
 * Enforce patient username rules configured in Admin Settings -> Authentication & OTP.
 * Returns null if valid, or a human-readable validation error message.
 */
function validatePatientUsername(?PDO $pdo, string $username): ?string {
    $s       = getRuntimeSettings($pdo);
    $minLen  = max(3, (int)($s['username_min_length'] ?? 5));
    $maxLen  = max($minLen, (int)($s['username_max_length'] ?? 30));
    $pattern = (string)($s['username_pattern'] ?? 'alphanumeric_dash');
    $prefix  = trim((string)($s['username_prefix'] ?? ''));

    $len = strlen($username);
    if ($len < $minLen || $len > $maxLen) {
        return "Username must be between {$minLen} and {$maxLen} characters.";
    }

    if ($pattern === 'patient_id_prefix') {
        $reqPrefix = $prefix !== '' ? $prefix : 'PT';
        if (!str_starts_with(strtolower($username), strtolower($reqPrefix))) {
            return "Username must start with the required prefix '{$reqPrefix}'.";
        }
    } elseif ($prefix !== '' && !str_starts_with(strtolower($username), strtolower($prefix))) {
        return "Username must start with the required prefix '{$prefix}'.";
    }

    $regex = match ($pattern) {
        'alphanumeric'            => '/^[A-Za-z0-9]+$/',
        'alphanumeric_underscore' => '/^[A-Za-z0-9_]+$/',
        default                   => '/^[A-Za-z0-9_-]+$/',
    };

    if (!preg_match($regex, $username)) {
        $label = match ($pattern) {
            'alphanumeric'            => 'letters and numbers only',
            'alphanumeric_underscore' => 'letters, numbers, and underscores only',
            default                   => 'letters, numbers, underscores, and hyphens only',
        };
        return "Username may contain {$label}.";
    }

    return null;
}

/**
 * Enforce General clinic hours & Department Scheduling Rules on online bookings.
 * Returns null if allowed, or an error message explaining why the booking is blocked.
 */
function validateBookingAgainstRules(?PDO $pdo, string $rawDept, string $appointmentDate, string $scheduledTime = '08:00:00'): ?string {
    if (!$pdo || $appointmentDate === '') {
        return null;
    }

    date_default_timezone_set('Asia/Manila');
    $s    = getRuntimeSettings($pdo);
    $rule = getDepartmentRule($pdo, $rawDept);

    if ($rule && empty($rule['is_active'])) {
        $name = $rule['display_name'] ?: ucfirst($rawDept);
        return "{$name} online booking is currently paused by the administrator.";
    }

    $ts = strtotime($appointmentDate);
    if ($ts === false) {
        return 'Please select a valid appointment date.';
    }

    // 1. Check configured hospital holidays / closed dates
    $holidays = is_array($s['hospital_holidays'] ?? null) ? $s['hospital_holidays'] : [];
    foreach ($holidays as $h) {
        if (is_array($h) && ($h['date'] ?? '') === $appointmentDate) {
            $label = trim((string)($h['label'] ?? 'Holiday'));
            return "Outpatient clinics are closed on {$appointmentDate} ({$label}). Please choose another date.";
        }
    }

    // 2. Check weekly outpatient clinic hours (open/closed days)
    $dayKey = strtolower(date('l', $ts));
    $hours  = is_array($s['clinic_hours'] ?? null) ? $s['clinic_hours'] : [];
    if (isset($hours[$dayKey]) && empty($hours[$dayKey]['is_open'])) {
        $dayLabel = ucfirst($dayKey);
        return "Outpatient clinics are closed on {$dayLabel}s. Please choose an open clinic day.";
    }

    // 2. Check advance / same-day booking & cutoff time
    $today   = date('Y-m-d');
    $maxDate = date('Y-m-d', strtotime('+1 year'));
    if ($appointmentDate < $today) {
        return 'Appointment date cannot be in the past.';
    }
    if ($appointmentDate > $maxDate) {
        return "Appointments cannot be scheduled more than 1 year in advance (latest: {$maxDate}).";
    }

    $allowSameDay = $rule ? !empty($rule['allow_same_day_booking']) : false;
    if ($appointmentDate === $today) {
        if (!$allowSameDay) {
            $nextDay = date('Y-m-d', strtotime('+1 day'));
            return "Same-day online booking is disabled for this department. Earliest available date: {$nextDay}.";
        }
        $cutoff = trim((string)($rule['booking_cutoff_time'] ?? '16:00'));
        if ($cutoff !== '' && date('H:i') > $cutoff) {
            return "Same-day booking cut-off ({$cutoff}) has passed for today. Please select the next open clinic day.";
        }
    }

    // 3. Enforce department daily / AM / PM capacity limits
    if ($rule) {
        $deptCode = $rule['department_code'];
        $deptLike = match ($deptCode) {
            'general'    => ['general', 'general medical'],
            'neurology'  => ['neurology'],
            'psychiatry' => ['psychiatry', 'opu psychiatry'],
            default      => [$deptCode],
        };
        $placeholders = implode(',', array_fill(0, count($deptLike), '?'));

        try {
            $sql = "SELECT
                        COUNT(*) AS total_cnt,
                        SUM(CASE WHEN HOUR(COALESCE(scheduled_time, '08:00:00')) < 12 THEN 1 ELSE 0 END) AS am_cnt,
                        SUM(CASE WHEN HOUR(COALESCE(scheduled_time, '08:00:00')) >= 12 THEN 1 ELSE 0 END) AS pm_cnt
                    FROM patients
                    WHERE appointment_date = ?
                      AND COALESCE(inquiry_only, 0) = 0
                      AND LOWER(COALESCE(department, '')) IN ({$placeholders})
                      AND LOWER(COALESCE(status, '')) NOT IN ('cancelled', 'rejected', 'cancelled_by_patient', 'no-show')";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(array_merge([$appointmentDate], $deptLike));
            $counts = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['total_cnt' => 0, 'am_cnt' => 0, 'pm_cnt' => 0];

            $dailyCap = (int)$rule['daily_capacity_limit'];
            if ($dailyCap > 0 && (int)$counts['total_cnt'] >= $dailyCap) {
                return "{$rule['display_name']} has reached its daily booking capacity ({$dailyCap}) for {$appointmentDate}. Please pick another date.";
            }

            $reqHour = (int)date('H', strtotime($scheduledTime ?: '08:00:00'));
            $isAm    = $reqHour < 12;
            $amCap   = $rule['am_slot_limit'] !== null ? (int)$rule['am_slot_limit'] : null;
            $pmCap   = $rule['pm_slot_limit'] !== null ? (int)$rule['pm_slot_limit'] : null;

            if ($isAm && $amCap !== null && (int)$counts['am_cnt'] >= $amCap) {
                return "{$rule['display_name']} morning (AM) slots are full ({$amCap}) for {$appointmentDate}. Try an afternoon slot or another date.";
            }
            if (!$isAm && $pmCap !== null && (int)$counts['pm_cnt'] >= $pmCap) {
                return "{$rule['display_name']} afternoon (PM) slots are full ({$pmCap}) for {$appointmentDate}. Try a morning slot or another date.";
            }
        } catch (Throwable $ignore) {
            // Do not block booking if legacy column differs
        }
    }

    return null;
}

/**
 * Check idle timeout for PHP portal pages (admin/staff or patient).
 * Returns true if session expired and was cleared.
 */
function enforcePortalSessionTimeout(?PDO $pdo, string $portal = 'admin'): bool {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $key     = ($portal === 'patient') ? 'patient_session_timeout_minutes' : 'session_timeout_minutes';
    $default = ($portal === 'patient') ? 60 : 30;
    $minutes = max(5, (int)getSettingValue($pdo, $key, $default));
    $ttl     = $minutes * 60;

    $tsKey = ($portal === 'patient') ? 'patient_last_activity' : 'last_activity';
    if (isset($_SESSION[$tsKey]) && (time() - (int)$_SESSION[$tsKey] > $ttl)) {
        session_unset();
        session_destroy();
        return true;
    }
    $_SESSION[$tsKey] = time();
    return false;
}
