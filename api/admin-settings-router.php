<?php
declare(strict_types=1);

/**
 * api/admin-settings-router.php
 * Self-healing XAMPP MySQL router for System Administrator Settings:
 *   - General Configuration (hospital details, clinic hours, holidays)
 *   - Scheduling Rules (department capacity limits, cut-offs, same-day toggle, QR expiry)
 *   - FAQ Management (Dr. Leo & Nurse Mika bilingual Q&A)
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/cors.php';
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/settings_helper.php';

// ── Route Resolution ─────────────────────────────────────────────────────────
$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
$route  = $_GET['route'] ?? ($_SERVER['PATH_INFO'] ?? '');
if ($route === '') {
    $uriPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
    $route   = preg_replace('#^/sdn/api(?:/admin-settings-router\.php)?#', '', $uriPath) ?? '';
}
$route = '/' . ltrim(rtrim($route, '/'), '/');

// ── Self-Healing Schema + Default Seeding ────────────────────────────────────
ensureAdminSettingsSchema($pdo);

// ── Public Route: Hospital General Config, Holidays & QR Expiry ──────────────
if ($method === 'GET' && $route === '/public/settings') {
    $grouped = fetchGroupedSettings($pdo);
    $deptOverrides = [];
    foreach ($grouped['scheduling']['departments'] as $d) {
        $deptOverrides[$d['department_code']] = [
            'display_name'             => $d['display_name'],
            'is_active'                => $d['is_active'],
            'allow_same_day_booking'   => $d['allow_same_day_booking'],
            'booking_cutoff_time'      => $d['booking_cutoff_time'],
            'qr_expiry_hours_override' => $d['qr_expiry_hours_override'],
        ];
    }
    respondOk([
        'data' => [
            'general'              => $grouped['general'],
            'qr_code_expiry_hours' => $grouped['scheduling']['qr_code_expiry_hours'],
            'departments'          => $deptOverrides,
        ],
    ]);
}

// ── Public Route: FAQ Feed (Dr. Leo & Nurse Mika) ────────────────────────────
if ($method === 'GET' && $route === '/public/chatbot-faqs') {
    $rows = $pdo->query(
        "SELECT slug, locale, persona, question, answer_html, keywords_json, follow_ups_json
         FROM chatbot_faqs
         WHERE is_active = 1
         ORDER BY sort_order ASC, id ASC"
    )->fetchAll();

    $grouped = ['EN' => [], 'TL' => []];
    foreach ($rows as $row) {
        $bucket = strtoupper((string)$row['locale']) === 'TL' ? 'TL' : 'EN';
        $grouped[$bucket][] = [
            'id'        => $row['slug'],
            'persona'   => $row['persona'],
            'question'  => $row['question'],
            'keywords'  => json_decode((string)$row['keywords_json'], true) ?: [],
            'answer'    => $row['answer_html'],
            'followUps' => json_decode((string)$row['follow_ups_json'], true) ?: [],
        ];
    }

    respondOk(['data' => $grouped]);
}

// ── Admin-Only Guard ─────────────────────────────────────────────────────────
requireRole('admin');
$actorId   = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
$actorName = sessionUser();

// ── GET /admin/settings ──────────────────────────────────────────────────────
if ($method === 'GET' && $route === '/admin/settings') {
    respondOk(['data' => fetchGroupedSettings($pdo)]);
}

// ── PUT /admin/settings/general ──────────────────────────────────────────────
if ($method === 'PUT' && $route === '/admin/settings/general') {
    $b = getBody();
    $errors = [];

    $hospitalName = trim((string)($b['hospital_name'] ?? ''));
    $phone        = trim((string)($b['contact_phone'] ?? ''));
    $hotline      = trim((string)($b['emergency_hotline'] ?? ''));
    $email        = trim((string)($b['contact_email'] ?? ''));
    $address      = trim((string)($b['hospital_address'] ?? ''));
    $hours        = is_array($b['clinic_hours'] ?? null) ? $b['clinic_hours'] : [];
    $rawHolidays  = is_array($b['hospital_holidays'] ?? null) ? $b['hospital_holidays'] : [];

    if ($hospitalName === '') $errors['hospital_name'][] = 'Hospital name is required.';
    if ($phone === '') $errors['contact_phone'][] = 'Main landline is required.';
    if ($hotline === '') $errors['emergency_hotline'][] = 'Emergency hotline is required.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['contact_email'][] = 'Valid contact email is required.';
    if ($address === '') $errors['hospital_address'][] = 'Hospital address is required.';

    $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
    $cleanHours = [];
    foreach ($days as $day) {
        $d = is_array($hours[$day] ?? null) ? $hours[$day] : ['is_open' => false, 'open' => '08:00', 'close' => '17:00'];
        $isOpen = !empty($d['is_open']);
        $open   = trim((string)($d['open'] ?? '08:00'));
        $close  = trim((string)($d['close'] ?? '17:00'));
        if (!preg_match('/^\d{2}:\d{2}$/', $open)) $errors["clinic_hours.$day.open"][] = 'Use HH:MM format.';
        if (!preg_match('/^\d{2}:\d{2}$/', $close)) $errors["clinic_hours.$day.close"][] = 'Use HH:MM format.';
        if ($isOpen && $close <= $open) {
            $errors["clinic_hours.$day.close"][] = 'Closing time must be after opening time.';
        }
        $cleanHours[$day] = ['is_open' => $isOpen, 'open' => $open, 'close' => $close];
    }

    $cleanHolidaysByDate = [];
    foreach ($rawHolidays as $idx => $h) {
        if (!is_array($h)) continue;
        $hDate  = trim((string)($h['date'] ?? ''));
        $hLabel = trim((string)($h['label'] ?? ''));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $hDate)) {
            $errors["hospital_holidays.$idx.date"][] = 'Use YYYY-MM-DD format.';
            continue;
        }
        if ($hLabel === '' || strlen($hLabel) > 120) {
            $errors["hospital_holidays.$idx.label"][] = 'Holiday label must be 1–120 characters.';
            continue;
        }
        $cleanHolidaysByDate[$hDate] = ['date' => $hDate, 'label' => $hLabel];
    }
    ksort($cleanHolidaysByDate);
    $cleanHolidays = array_values($cleanHolidaysByDate);

    if ($errors) respondValidationErr($errors);

    $updates = [
        'hospital_name'     => [$hospitalName, 'string'],
        'contact_phone'     => [$phone, 'string'],
        'emergency_hotline' => [$hotline, 'string'],
        'contact_email'     => [$email, 'string'],
        'hospital_address'  => [$address, 'string'],
        'clinic_hours'      => [json_encode($cleanHours), 'json'],
        'hospital_holidays' => [json_encode($cleanHolidays), 'json'],
    ];
    if (array_key_exists('hospital_tagline', $b)) {
        $updates['hospital_tagline'] = [trim((string)$b['hospital_tagline']), 'string'];
    }

    upsertSettingsGroup($pdo, 'general', $updates, $actorId, $actorName);

    respondOk([
        'message' => 'General hospital configuration saved.',
        'data'    => fetchGroupedSettings($pdo)['general'],
    ]);
}

// ── PUT /admin/settings/scheduling ───────────────────────────────────────────
if ($method === 'PUT' && $route === '/admin/settings/scheduling') {
    $b = getBody();
    $errors = [];

    $qrHours = filter_var($b['qr_code_expiry_hours'] ?? null, FILTER_VALIDATE_INT);
    if ($qrHours === false || $qrHours < 1 || $qrHours > 720) {
        $errors['qr_code_expiry_hours'][] = 'QR expiry must be between 1 and 720 hours.';
    }

    $departments = is_array($b['departments'] ?? null) ? $b['departments'] : [];
    if (!$departments) {
        $errors['departments'][] = 'At least one department rule is required.';
    }

    foreach ($departments as $idx => $dept) {
        $code  = trim((string)($dept['department_code'] ?? ''));
        $daily = filter_var($dept['daily_capacity_limit'] ?? null, FILTER_VALIDATE_INT);
        $am    = (!isset($dept['am_slot_limit']) || $dept['am_slot_limit'] === '' || $dept['am_slot_limit'] === null) ? null : filter_var($dept['am_slot_limit'], FILTER_VALIDATE_INT);
        $pm    = (!isset($dept['pm_slot_limit']) || $dept['pm_slot_limit'] === '' || $dept['pm_slot_limit'] === null) ? null : filter_var($dept['pm_slot_limit'], FILTER_VALIDATE_INT);
        $cut   = trim((string)($dept['booking_cutoff_time'] ?? ''));
        $ovr   = (!isset($dept['qr_expiry_hours_override']) || $dept['qr_expiry_hours_override'] === '' || $dept['qr_expiry_hours_override'] === null) ? null : filter_var($dept['qr_expiry_hours_override'], FILTER_VALIDATE_INT);

        if ($code === '') $errors["departments.$idx.department_code"][] = 'Department code is required.';
        if ($daily === false || $daily < 1 || $daily > 500) $errors["departments.$idx.daily_capacity_limit"][] = 'Daily limit must be 1–500.';
        if ($am !== null && ($am === false || $am < 0 || $am > 500)) $errors["departments.$idx.am_slot_limit"][] = 'Invalid AM limit.';
        if ($pm !== null && ($pm === false || $pm < 0 || $pm > 500)) $errors["departments.$idx.pm_slot_limit"][] = 'Invalid PM limit.';
        if (!preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $cut)) $errors["departments.$idx.booking_cutoff_time"][] = 'Use HH:MM format.';
        if ($ovr !== null && ($ovr === false || $ovr < 1 || $ovr > 720)) {
            $errors["departments.$idx.qr_expiry_hours_override"][] = 'QR override must be 1–720 hours.';
        }
    }

    if ($errors) respondValidationErr($errors);

    upsertSettingsGroup($pdo, 'scheduling', [
        'qr_code_expiry_hours' => [(string)$qrHours, 'integer'],
    ], $actorId, $actorName);

    $stmt = $pdo->prepare(
        "UPDATE department_scheduling_rules
         SET daily_capacity_limit = ?, am_slot_limit = ?, pm_slot_limit = ?,
             booking_cutoff_time = ?, qr_expiry_hours_override = ?,
             allow_same_day_booking = ?, is_active = ?, updated_by = ?
         WHERE department_code = ?"
    );

    foreach ($departments as $dept) {
        $dailyCap = (int)$dept['daily_capacity_limit'];
        $am  = (!isset($dept['am_slot_limit']) || $dept['am_slot_limit'] === '' || $dept['am_slot_limit'] === null) ? null : (int)$dept['am_slot_limit'];
        $pm  = (!isset($dept['pm_slot_limit']) || $dept['pm_slot_limit'] === '' || $dept['pm_slot_limit'] === null) ? null : (int)$dept['pm_slot_limit'];
        if ($am !== null && $pm !== null && ($am + $pm) > $dailyCap) {
            $am = (int)ceil($dailyCap / 2);
            $pm = max(0, $dailyCap - $am);
        }
        $ovr = (!isset($dept['qr_expiry_hours_override']) || $dept['qr_expiry_hours_override'] === '' || $dept['qr_expiry_hours_override'] === null) ? null : (int)$dept['qr_expiry_hours_override'];
        $cut = substr(trim((string)$dept['booking_cutoff_time']), 0, 5);
        $stmt->execute([
            $dailyCap,
            $am,
            $pm,
            $cut,
            $ovr,
            !empty($dept['allow_same_day_booking']) ? 1 : 0,
            array_key_exists('is_active', $dept) ? (!empty($dept['is_active']) ? 1 : 0) : 1,
            $actorId,
            trim((string)$dept['department_code']),
        ]);
    }

    respondOk([
        'message' => 'Scheduling rules and department capacities saved.',
        'data'    => fetchGroupedSettings($pdo)['scheduling'],
    ]);
}

// ── FAQ Management Endpoints ─────────────────────────────────────────────────
if ($method === 'GET' && $route === '/admin/faqs') {
    $where  = [];
    $params = [];
    if (!empty($_GET['locale'])) {
        $where[]  = 'locale = ?';
        $params[] = $_GET['locale'];
    }
    if (!empty($_GET['persona'])) {
        $where[]  = 'persona = ?';
        $params[] = $_GET['persona'];
    }
    if (!empty($_GET['search'])) {
        $q        = '%' . trim((string)$_GET['search']) . '%';
        $where[]  = '(question LIKE ? OR answer_html LIKE ? OR slug LIKE ?)';
        $params[] = $q;
        $params[] = $q;
        $params[] = $q;
    }

    $sql = 'SELECT * FROM chatbot_faqs' . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY locale ASC, sort_order ASC, id ASC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = array_map('formatFaqRow', $stmt->fetchAll());
    respondOk(['data' => $rows]);
}

if ($method === 'POST' && $route === '/admin/faqs') {
    $b = getBody();
    [$clean, $errors] = validateFaqPayload($pdo, $b, null);
    if ($errors) respondValidationErr($errors);

    $maxStmt = $pdo->prepare('SELECT COALESCE(MAX(sort_order), 0) FROM chatbot_faqs WHERE locale = ?');
    $maxStmt->execute([$clean['locale']]);
    $sortOrder = $clean['sort_order'] ?? ((int)$maxStmt->fetchColumn() + 1);

    $stmt = $pdo->prepare(
        "INSERT INTO chatbot_faqs (slug, locale, persona, question, answer_html, keywords_json, follow_ups_json, sort_order, is_active, updated_by)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->execute([
        $clean['slug'],
        $clean['locale'],
        $clean['persona'],
        $clean['question'],
        $clean['answer_html'],
        json_encode($clean['keywords']),
        json_encode($clean['follow_ups']),
        $sortOrder,
        $clean['is_active'] ? 1 : 0,
        $actorId,
    ]);

    $id = (int)$pdo->lastInsertId();
    safeAddLog($pdo, 'info', 'FAQ Entry Created', $actorName, "Created FAQ '{$clean['slug']}' ({$clean['locale']}).");

    $row = $pdo->query("SELECT * FROM chatbot_faqs WHERE id = {$id}")->fetch();
    http_response_code(201);
    respondOk(['message' => 'FAQ created.', 'data' => formatFaqRow($row)]);
}

if (($method === 'PATCH' || $method === 'PUT') && $route === '/admin/faqs/reorder') {
    $b = getBody();
    $items = is_array($b['items'] ?? null) ? $b['items'] : [];
    if (!$items) respondValidationErr(['items' => ['Provide at least one FAQ item to reorder.']]);

    $stmt = $pdo->prepare('UPDATE chatbot_faqs SET sort_order = ? WHERE id = ?');
    foreach ($items as $item) {
        if (!isset($item['id'], $item['sort_order'])) continue;
        $stmt->execute([(int)$item['sort_order'], (int)$item['id']]);
    }
    respondOk(['message' => 'FAQ order updated.']);
}

if (preg_match('#^/admin/faqs/(\d+)$#', $route, $m)) {
    $faqId = (int)$m[1];
    $existingStmt = $pdo->prepare('SELECT * FROM chatbot_faqs WHERE id = ?');
    $existingStmt->execute([$faqId]);
    $existing = $existingStmt->fetch();
    if (!$existing) respondErr('FAQ not found.', 404);

    if ($method === 'PUT') {
        $b = getBody();
        [$clean, $errors] = validateFaqPayload($pdo, $b, $faqId);
        if ($errors) respondValidationErr($errors);

        $stmt = $pdo->prepare(
            "UPDATE chatbot_faqs
             SET slug = ?, locale = ?, persona = ?, question = ?, answer_html = ?,
                 keywords_json = ?, follow_ups_json = ?, sort_order = ?, is_active = ?, updated_by = ?
             WHERE id = ?"
        );
        $stmt->execute([
            $clean['slug'],
            $clean['locale'],
            $clean['persona'],
            $clean['question'],
            $clean['answer_html'],
            json_encode($clean['keywords']),
            json_encode($clean['follow_ups']),
            $clean['sort_order'] ?? (int)$existing['sort_order'],
            $clean['is_active'] ? 1 : 0,
            $actorId,
            $faqId,
        ]);

        safeAddLog($pdo, 'info', 'FAQ Entry Updated', $actorName, "Updated FAQ '{$clean['slug']}' ({$clean['locale']}).");
        $updated = $pdo->query("SELECT * FROM chatbot_faqs WHERE id = {$faqId}")->fetch();
        respondOk(['message' => 'FAQ updated.', 'data' => formatFaqRow($updated)]);
    }

    if ($method === 'DELETE') {
        $pdo->prepare('DELETE FROM chatbot_faqs WHERE id = ?')->execute([$faqId]);
        safeAddLog($pdo, 'info', 'FAQ Entry Deleted', $actorName, "Deleted FAQ '{$existing['slug']}' ({$existing['locale']}).");
        respondOk(['message' => 'FAQ deleted.']);
    }
}

respondErr('Settings endpoint not found.', 404);

// ── Helpers ──────────────────────────────────────────────────────────────────

function respondValidationErr(array $errors): void {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'message' => 'Please fix the highlighted validation errors.',
        'errors'  => $errors,
    ]);
    exit;
}

function safeAddLog(PDO $pdo, string $level, string $action, string $user, string $details): void {
    try {
        addLog($pdo, $level, $action, $user, $details);
    } catch (Throwable $ignore) {
        // Do not fail settings save if audit table schema differs
    }
}

function formatFaqRow(array $row): array {
    return [
        'id'              => (int)$row['id'],
        'slug'            => $row['slug'],
        'locale'          => $row['locale'],
        'persona'         => $row['persona'],
        'question'        => $row['question'],
        'answer_html'     => $row['answer_html'],
        'keywords_json'   => json_decode((string)$row['keywords_json'], true) ?: [],
        'follow_ups_json' => json_decode((string)$row['follow_ups_json'], true) ?: [],
        'sort_order'      => (int)$row['sort_order'],
        'is_active'       => (bool)$row['is_active'],
        'updated_at'      => $row['updated_at'] ?? null,
    ];
}

function validateFaqPayload(PDO $pdo, array $b, ?int $ignoreId): array {
    $errors = [];
    $slug   = trim((string)($b['slug'] ?? ''));
    $locale = strtolower(trim((string)($b['locale'] ?? 'en')));
    $persona = trim((string)($b['persona'] ?? 'both'));
    $question = trim((string)($b['question'] ?? ''));
    $answer   = trim((string)($b['answer_html'] ?? ''));
    $keywords = is_array($b['keywords'] ?? null) ? array_values(array_filter(array_map('trim', $b['keywords']))) : [];
    $followUps = is_array($b['follow_ups'] ?? null) ? array_slice(array_values(array_filter(array_map('trim', $b['follow_ups']))), 0, 3) : [];
    $sortOrder = isset($b['sort_order']) && $b['sort_order'] !== '' ? (int)$b['sort_order'] : null;
    $isActive  = array_key_exists('is_active', $b) ? (bool)$b['is_active'] : true;

    if ($slug === '' || !preg_match('/^[a-z0-9-]+$/i', $slug)) {
        $errors['slug'][] = 'Slug must use letters, numbers, and hyphens only.';
    }
    if (!in_array($locale, ['en', 'tl'], true)) {
        $errors['locale'][] = 'Locale must be en or tl.';
    }
    if (!in_array($persona, ['both', 'dr_leo', 'nurse_mika'], true)) {
        $errors['persona'][] = 'Invalid assistant persona.';
    }
    if ($question === '') $errors['question'][] = 'Question is required.';
    if ($answer === '') $errors['answer_html'][] = 'Answer is required.';

    if (!$errors) {
        $dupSql = 'SELECT id FROM chatbot_faqs WHERE slug = ? AND locale = ?' . ($ignoreId ? ' AND id != ?' : '');
        $dupStmt = $pdo->prepare($dupSql);
        $dupParams = $ignoreId ? [$slug, $locale, $ignoreId] : [$slug, $locale];
        $dupStmt->execute($dupParams);
        if ($dupStmt->fetch()) {
            $errors['slug'][] = 'An FAQ with this slug and language already exists.';
        }
    }

    $noScripts = preg_replace('#<(script|style)\b[^>]*>.*?</\1>#is', '', $answer) ?? $answer;
    $safeHtml  = trim(strip_tags($noScripts, '<strong><b><em><br>'));

    return [[
        'slug'        => strtolower($slug),
        'locale'      => $locale,
        'persona'     => $persona,
        'question'    => $question,
        'answer_html' => $safeHtml,
        'keywords'    => $keywords,
        'follow_ups'  => $followUps,
        'sort_order'  => $sortOrder,
        'is_active'   => $isActive,
    ], $errors];
}

function upsertSettingsGroup(PDO $pdo, string $group, array $entries, ?int $actorId, string $actorName): void {
    $stmt = $pdo->prepare(
        "INSERT INTO system_settings (group_key, setting_key, setting_value, value_type, updated_by)
         VALUES (?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE
            group_key = VALUES(group_key),
            setting_value = VALUES(setting_value),
            value_type = VALUES(value_type),
            updated_by = VALUES(updated_by)"
    );
    foreach ($entries as $key => [$val, $type]) {
        $stmt->execute([$group, $key, $val, $type, $actorId]);
    }
    safeAddLog($pdo, 'info', 'System Settings Updated', $actorName, "Updated '{$group}' configuration (" . implode(', ', array_keys($entries)) . ').');
}

function fetchGroupedSettings(PDO $pdo): array {
    $rows = $pdo->query('SELECT group_key, setting_key, setting_value, value_type, updated_at FROM system_settings')->fetchAll();
    $map  = [];
    $lastUpdated = null;
    foreach ($rows as $row) {
        $map[$row['setting_key']] = $row;
        if (!empty($row['updated_at']) && ($lastUpdated === null || $row['updated_at'] > $lastUpdated)) {
            $lastUpdated = $row['updated_at'];
        }
    }

    $val = static function (string $key, mixed $default = '') use ($map) {
        if (!isset($map[$key])) return $default;
        $r = $map[$key];
        $v = $r['setting_value'];
        return match ($r['value_type'] ?? 'string') {
            'integer' => (int)$v,
            'boolean' => filter_var($v, FILTER_VALIDATE_BOOLEAN),
            'json'    => json_decode((string)$v, true) ?? $default,
            default   => $v ?? $default,
        };
    };

    $depts = $pdo->query('SELECT * FROM department_scheduling_rules ORDER BY id ASC')->fetchAll();
    foreach ($depts as &$d) {
        $d['id']                       = (int)$d['id'];
        $d['daily_capacity_limit']     = (int)$d['daily_capacity_limit'];
        $d['am_slot_limit']            = $d['am_slot_limit'] !== null ? (int)$d['am_slot_limit'] : null;
        $d['pm_slot_limit']            = $d['pm_slot_limit'] !== null ? (int)$d['pm_slot_limit'] : null;
        $d['qr_expiry_hours_override'] = $d['qr_expiry_hours_override'] !== null ? (int)$d['qr_expiry_hours_override'] : null;
        $d['allow_same_day_booking']   = (bool)$d['allow_same_day_booking'];
        $d['is_active']                = (bool)$d['is_active'];
    }
    unset($d);

    $defaultHours   = defaultClinicHours();
    $storedHours    = $val('clinic_hours', $defaultHours);
    $storedHolidays = $val('hospital_holidays', []);

    return [
        'general' => [
            'hospital_name'     => (string)$val('hospital_name', 'Mariveles Mental Wellness and General Hospital'),
            'contact_phone'     => (string)$val('contact_phone', '(047) 935-4645'),
            'emergency_hotline' => (string)$val('emergency_hotline', '(047) 935-4138'),
            'contact_email'     => (string)$val('contact_email', 'support@mmwgh.gov.ph'),
            'hospital_address'  => (string)$val('hospital_address', 'P. Monroe St., Poblacion, Mariveles, Bataan'),
            'clinic_hours'      => is_array($storedHours) ? $storedHours : $defaultHours,
            'hospital_holidays' => is_array($storedHolidays) ? $storedHolidays : [],
        ],
        'scheduling' => [
            'qr_code_expiry_hours' => (int)$val('qr_code_expiry_hours', 48),
            'departments'          => $depts,
        ],
        'meta' => [
            'updated_at' => $lastUpdated,
        ],
    ];
}

function defaultClinicHours(): array {
    return [
        'monday'    => ['is_open' => true,  'open' => '08:00', 'close' => '17:00'],
        'tuesday'   => ['is_open' => true,  'open' => '08:00', 'close' => '17:00'],
        'wednesday' => ['is_open' => true,  'open' => '08:00', 'close' => '17:00'],
        'thursday'  => ['is_open' => true,  'open' => '08:00', 'close' => '17:00'],
        'friday'    => ['is_open' => true,  'open' => '08:00', 'close' => '17:00'],
        'saturday'  => ['is_open' => false, 'open' => '08:00', 'close' => '12:00'],
        'sunday'    => ['is_open' => false, 'open' => '08:00', 'close' => '12:00'],
    ];
}

function ensureAdminSettingsSchema(PDO $pdo): void {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `system_settings` (
            `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `group_key` VARCHAR(32) NOT NULL DEFAULT 'general',
            `setting_key` VARCHAR(64) NOT NULL,
            `setting_value` TEXT NULL,
            `value_type` VARCHAR(20) NOT NULL DEFAULT 'string',
            `description` VARCHAR(255) NULL,
            `updated_by` BIGINT UNSIGNED NULL,
            `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `system_settings_setting_key_unique` (`setting_key`),
            KEY `system_settings_group_key_index` (`group_key`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $cols = $pdo->query('SHOW COLUMNS FROM `system_settings`')->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('group_key', $cols, true)) {
        @$pdo->exec("ALTER TABLE `system_settings` ADD COLUMN `group_key` VARCHAR(32) NOT NULL DEFAULT 'general' AFTER `id`");
    }
    if (!in_array('value_type', $cols, true)) {
        @$pdo->exec("ALTER TABLE `system_settings` ADD COLUMN `value_type` VARCHAR(20) NOT NULL DEFAULT 'string' AFTER `setting_value`");
    }
    if (!in_array('updated_by', $cols, true)) {
        @$pdo->exec("ALTER TABLE `system_settings` ADD COLUMN `updated_by` BIGINT UNSIGNED NULL AFTER `description`");
    }

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `department_scheduling_rules` (
            `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `department_code` VARCHAR(32) NOT NULL,
            `display_name` VARCHAR(80) NOT NULL,
            `daily_capacity_limit` SMALLINT UNSIGNED NOT NULL DEFAULT 50,
            `am_slot_limit` SMALLINT UNSIGNED NULL,
            `pm_slot_limit` SMALLINT UNSIGNED NULL,
            `booking_cutoff_time` VARCHAR(8) NOT NULL DEFAULT '16:00',
            `qr_expiry_hours_override` SMALLINT UNSIGNED NULL,
            `allow_same_day_booking` TINYINT(1) NOT NULL DEFAULT 1,
            `is_active` TINYINT(1) NOT NULL DEFAULT 1,
            `updated_by` BIGINT UNSIGNED NULL,
            `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `department_scheduling_rules_department_code_unique` (`department_code`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `chatbot_faqs` (
            `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `slug` VARCHAR(80) NOT NULL,
            `locale` VARCHAR(5) NOT NULL DEFAULT 'en',
            `persona` VARCHAR(20) NOT NULL DEFAULT 'both',
            `question` VARCHAR(255) NOT NULL,
            `answer_html` TEXT NOT NULL,
            `keywords_json` JSON NULL,
            `follow_ups_json` JSON NULL,
            `sort_order` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
            `is_active` TINYINT(1) NOT NULL DEFAULT 1,
            `updated_by` BIGINT UNSIGNED NULL,
            `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `chatbot_faqs_slug_locale_unique` (`slug`, `locale`),
            KEY `chatbot_faqs_locale_persona_is_active_index` (`locale`, `persona`, `is_active`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $defaults = [
        ['general', 'hospital_name', 'Mariveles Mental Wellness and General Hospital', 'string'],
        ['general', 'contact_phone', '(047) 935-4645', 'string'],
        ['general', 'emergency_hotline', '(047) 935-4138', 'string'],
        ['general', 'contact_email', 'support@mmwgh.gov.ph', 'string'],
        ['general', 'hospital_address', 'P. Monroe St., Poblacion, Mariveles, Bataan', 'string'],
        ['general', 'clinic_hours', json_encode(defaultClinicHours()), 'json'],
        ['general', 'hospital_holidays', '[]', 'json'],
        ['scheduling', 'qr_code_expiry_hours', '48', 'integer'],
    ];

    $insSetting = $pdo->prepare(
        "INSERT IGNORE INTO system_settings (group_key, setting_key, setting_value, value_type) VALUES (?, ?, ?, ?)"
    );
    foreach ($defaults as $row) {
        $insSetting->execute($row);
    }

    if ((int)$pdo->query('SELECT COUNT(*) FROM department_scheduling_rules')->fetchColumn() === 0) {
        $insDept = $pdo->prepare(
            "INSERT IGNORE INTO department_scheduling_rules
             (department_code, display_name, daily_capacity_limit, am_slot_limit, pm_slot_limit, booking_cutoff_time, allow_same_day_booking, is_active)
             VALUES (?, ?, ?, ?, ?, ?, 1, 1)"
        );
        $insDept->execute(['general', 'General Medical', 60, 35, 25, '15:00']);
        $insDept->execute(['neurology', 'Neurology', 30, 15, 15, '14:00']);
        $insDept->execute(['psychiatry', 'Psychiatry', 40, 20, 20, '15:00']);
    }

    if ((int)$pdo->query('SELECT COUNT(*) FROM chatbot_faqs')->fetchColumn() === 0) {
        $insFaq = $pdo->prepare(
            "INSERT IGNORE INTO chatbot_faqs (slug, locale, persona, question, answer_html, keywords_json, follow_ups_json, sort_order, is_active)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)"
        );
        $seedFaqs = [
            [
                'request-appointment', 'en', 'both',
                'How do I request an appointment/schedule?',
                "Click <strong>'Book Appointment'</strong> on the homepage, select your desired clinical department, and complete the registration. Our clinic staff will verify your request before confirming your queue slot.",
                ['request', 'appointment', 'schedule', 'book', 'reserve', 'slot'],
                ["What are the hospital's operating hours & doctor schedules?", 'What should I bring for my consultation & EMR triage?'],
                1,
            ],
            [
                'hospital-hours', 'en', 'dr_leo',
                "What are the hospital's operating hours & doctor schedules?",
                'The <strong>Emergency Triage</strong> operates 24/7. <strong>Outpatient (OPD) specialty clinics</strong> run Monday through Friday from <strong>8:00 AM to 5:00 PM</strong>.',
                ['hours', 'operating', 'doctor', 'schedules', 'time', 'open', 'opd'],
                ['How do I request an appointment/schedule?', 'What should I bring for my consultation & EMR triage?'],
                2,
            ],
            [
                'consultation-requirements', 'en', 'nurse_mika',
                'What should I bring for my consultation & EMR triage?',
                'Please present a valid government, company, or student ID, your active phone number, and any prior medical records, lab results, or prescription lists.',
                ['bring', 'consultation', 'triage', 'emr', 'requirements', 'id'],
                ['How do I request an appointment/schedule?', "What are the hospital's operating hours & doctor schedules?"],
                3,
            ],
            [
                'request-appointment', 'tl', 'both',
                'Paano ako mag-request ng appointment/iskedyul?',
                "I-click ang <strong>'Book Appointment'</strong> sa homepage, piliin ang departamento, at kumpletuhin ang form. Makikipag-ugnayan ang aming staff upang kumpirmahin ang inyong iskedyul.",
                ['paano', 'appointment', 'iskedyul', 'mag-book', 'request'],
                ['Ano ang mga oras ng operasyon at iskedyul ng doktor?', 'Ano ang dapat kong dalhin para sa aking konsultasyon at EMR?'],
                1,
            ],
            [
                'hospital-hours', 'tl', 'dr_leo',
                'Ano ang mga oras ng operasyon at iskedyul ng doktor?',
                'Ang <strong>Emergency Triage</strong> ay bukas 24/7. Ang <strong>Outpatient (OPD) clinics</strong> ay bukas Lunes hanggang Biyernes, <strong>8:00 AM hanggang 5:00 PM</strong>.',
                ['oras', 'operasyon', 'doktor', 'iskedyul', 'bukas', 'opd'],
                ['Paano ako mag-request ng appointment/iskedyul?'],
                2,
            ],
            [
                'consultation-requirements', 'tl', 'nurse_mika',
                'Ano ang dapat kong dalhin para sa aking konsultasyon at EMR?',
                'Magdala ng valid ID, aktibong numero ng telepono, at anumang nakaraang medical records, lab results, o reseta.',
                ['dalhin', 'konsultasyon', 'triage', 'emr', 'id', 'reseta'],
                ['Paano ako mag-request ng appointment/iskedyul?'],
                3,
            ],
        ];
        foreach ($seedFaqs as $f) {
            $insFaq->execute([$f[0], $f[1], $f[2], $f[3], $f[4], json_encode($f[5]), json_encode($f[6]), $f[7]]);
        }
    }
}
