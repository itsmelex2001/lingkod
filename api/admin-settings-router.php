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
            'answer'    => sanitizeFaqAnswerHtml((string)($row['answer_html'] ?? '')),
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

function sanitizeFaqAnswerHtml(string $html): string {
    $noScripts = preg_replace('#<(script|style)\b[^>]*>.*?</\1>#is', '', $html) ?? $html;
    if (!class_exists('DOMDocument')) {
        return htmlspecialchars(strip_tags($noScripts), ENT_QUOTES, 'UTF-8');
    }

    $allowed = ['strong', 'b', 'em', 'br'];
    $dom = new DOMDocument();
    $prev = libxml_use_internal_errors(true);
    $dom->loadHTML('<?xml encoding="utf-8" ?><div>' . $noScripts . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();
    libxml_use_internal_errors($prev);

    $root = $dom->getElementsByTagName('div')->item(0);
    if (!$root) {
        return htmlspecialchars(strip_tags($noScripts), ENT_QUOTES, 'UTF-8');
    }

    $walk = static function (DOMNode $node) use (&$walk, $allowed): void {
        for ($i = $node->childNodes->length - 1; $i >= 0; $i--) {
            $child = $node->childNodes->item($i);
            if ($child instanceof DOMElement) {
                $tag = strtolower($child->tagName);
                if (!in_array($tag, $allowed, true)) {
                    $text = $child->ownerDocument->createTextNode($child->textContent);
                    $node->replaceChild($text, $child);
                } else {
                    while ($child->attributes->length > 0) {
                        $child->removeAttributeNode($child->attributes->item(0));
                    }
                    $walk($child);
                }
            }
        }
    };
    $walk($root);

    $inner = '';
    foreach ($root->childNodes as $child) {
        $inner .= $dom->saveHTML($child);
    }
    return trim($inner);
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

    $safeHtml  = sanitizeFaqAnswerHtml($answer);

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
    $upsertSql = dbIsPgsql()
        ? "INSERT INTO system_settings (group_key, setting_key, setting_value, value_type, updated_by)
           VALUES (?, ?, ?, ?, ?)
           ON CONFLICT (setting_key) DO UPDATE SET
              group_key = EXCLUDED.group_key,
              setting_value = EXCLUDED.setting_value,
              value_type = EXCLUDED.value_type,
              updated_by = EXCLUDED.updated_by"
        : "INSERT INTO system_settings (group_key, setting_key, setting_value, value_type, updated_by)
           VALUES (?, ?, ?, ?, ?)
           ON DUPLICATE KEY UPDATE
              group_key = VALUES(group_key),
              setting_value = VALUES(setting_value),
              value_type = VALUES(value_type),
              updated_by = VALUES(updated_by)";
    $stmt = $pdo->prepare($upsertSql);
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
    if (!dbIsPgsql()) {
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
    }

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

    $insSettingSql = dbIsPgsql()
        ? "INSERT INTO system_settings (group_key, setting_key, setting_value, value_type) VALUES (?, ?, ?, ?) ON CONFLICT (setting_key) DO NOTHING"
        : "INSERT IGNORE INTO system_settings (group_key, setting_key, setting_value, value_type) VALUES (?, ?, ?, ?)";
    $insSetting = $pdo->prepare($insSettingSql);
    foreach ($defaults as $row) {
        $insSetting->execute($row);
    }

    if ((int)$pdo->query('SELECT COUNT(*) FROM department_scheduling_rules')->fetchColumn() === 0) {
        $insDeptSql = dbIsPgsql()
            ? "INSERT INTO department_scheduling_rules
               (department_code, display_name, daily_capacity_limit, am_slot_limit, pm_slot_limit, booking_cutoff_time, allow_same_day_booking, is_active)
               VALUES (?, ?, ?, ?, ?, ?, 1, 1) ON CONFLICT (department_code) DO NOTHING"
            : "INSERT IGNORE INTO department_scheduling_rules
               (department_code, display_name, daily_capacity_limit, am_slot_limit, pm_slot_limit, booking_cutoff_time, allow_same_day_booking, is_active)
               VALUES (?, ?, ?, ?, ?, ?, 1, 1)";
        $insDept = $pdo->prepare($insDeptSql);
        $insDept->execute(['general', 'General Medical', 60, 35, 25, '15:00']);
        $insDept->execute(['neurology', 'Neurology', 30, 15, 15, '14:00']);
        $insDept->execute(['psychiatry', 'Psychiatry', 40, 20, 20, '15:00']);
    }

    $faqSyncLock = sys_get_temp_dir() . '/sdn_chatbot_faqs_v2.lock';
    $faqCount = (int)$pdo->query('SELECT COUNT(*) FROM chatbot_faqs')->fetchColumn();
    if ($faqCount === 0 || !file_exists($faqSyncLock)) {
        $insFaqSql = dbIsPgsql()
            ? "INSERT INTO chatbot_faqs (slug, locale, persona, question, answer_html, keywords_json, follow_ups_json, sort_order, is_active)
               VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1) ON CONFLICT (slug, locale) DO NOTHING"
            : "INSERT IGNORE INTO chatbot_faqs (slug, locale, persona, question, answer_html, keywords_json, follow_ups_json, sort_order, is_active)
               VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)";
        $insFaq = $pdo->prepare($insFaqSql);
        // Update unedited legacy default seeds (updated_by IS NULL) without touching admin-authored entries
        $updFaq = $pdo->prepare(
            "UPDATE chatbot_faqs
             SET persona = ?, question = ?, answer_html = ?, keywords_json = ?, follow_ups_json = ?, sort_order = ?
             WHERE slug = ? AND locale = ? AND updated_by IS NULL"
        );
        $seedFaqs = [
            [
                'request-appointment', 'en', 'both',
                'How do I book an appointment?',
                'Click <strong>Book Appointment</strong>, review and accept the Data Privacy notice, then complete the 4-step form (Personal Info, Medical Info, Appointment Details, and Confirmation). Online appointments can be booked <strong>Monday to Saturday</strong> at least 1 day ahead for Morning or Afternoon sessions. Your request starts as <strong>For Approval</strong> while clinic staff verifies physician availability.',
                ['book', 'request', 'appointment', 'schedule', 'reserve', 'slot', 'register'],
                [
                    'How does Teleconsultation work for returning patients?',
                    'Can I book for a family member or just ask an inquiry?',
                    'How do I track or reschedule my appointment?',
                ],
                1,
            ],
            [
                'teleconsultation-flow', 'en', 'both',
                'How does Teleconsultation work for returning patients?',
                'First-time patients are required to complete an initial <strong>Face-to-Face</strong> visit. Once you have at least one <strong>completed</strong> hospital consultation, <strong>Teleconsultation</strong> unlocks in Step 3 of booking. After staff approves your visit, your session link (e.g., Google Meet/Zoom) or clinic call instructions appear directly on your appointment card in the <strong>Patient Portal</strong>.',
                ['teleconsult', 'teleconsultation', 'online', 'video', 'returning', 'remote', 'google meet', 'zoom'],
                [
                    'How do I book an appointment?',
                    'What should I prepare for my consultation?',
                    'How do I track or reschedule my appointment?',
                ],
                2,
            ],
            [
                'hospital-hours', 'en', 'both',
                'What departments and clinic hours are available?',
                'MMWGH Outpatient Clinics serve <strong>General Medical</strong>, <strong>Neurology</strong>, and <strong>Psychiatry</strong> from <strong>Monday to Saturday, 8:00 AM – 5:00 PM</strong> (Morning: 8:00 AM – 12:00 PM; Afternoon: 1:00 PM – 5:00 PM; closed Sundays). For urgent emergencies, our <strong>Emergency Triage</strong> is open 24/7.',
                ['department', 'hours', 'operating', 'open', 'opd', 'general medical', 'neurology', 'psychiatry', 'emergency'],
                [
                    'How do I check doctor schedules and open slots?',
                    'Can I book for a family member or just ask an inquiry?',
                    'Where is MMWGH and how can I contact the clinic?',
                ],
                3,
            ],
            [
                'portal-status', 'en', 'both',
                'How do I track or reschedule my appointment?',
                'Sign in to the <strong>Patient Portal</strong> using your username or email and password. You can check real-time status (<strong>For Approval</strong>, <strong>Confirmed</strong>, <strong>Checked In</strong>, <strong>Completed</strong>), download your <strong>Appointment/Teleconsultation Slip</strong> and <strong>Digital Patient ID</strong>, and reschedule directly while your visit is still <strong>For Approval</strong>.',
                ['portal', 'track', 'status', 'reschedule', 'slip', 'qr', 'login', 'account'],
                [
                    'What should I prepare for my consultation?',
                    'How does Teleconsultation work for returning patients?',
                    'How is my medical data protected?',
                ],
                4,
            ],
            [
                'consultation-requirements', 'en', 'both',
                'What should I prepare for my consultation?',
                'For <strong>Face-to-Face</strong> visits, bring a valid ID, your printable <strong>Appointment Slip</strong> or <strong>Digital Patient ID (QR)</strong> from the Patient Portal, and any prior prescriptions or lab results. For <strong>Teleconsultation</strong>, keep your active mobile number reachable and open your Patient Portal 5–10 minutes before your scheduled block.',
                ['bring', 'prepare', 'requirements', 'id', 'slip', 'triage', 'documents', 'prescription'],
                [
                    'How does Teleconsultation work for returning patients?',
                    'Where is MMWGH and how can I contact the clinic?',
                    'How is my medical data protected?',
                ],
                5,
            ],
            [
                'schedules', 'en', 'both',
                'How do I check doctor schedules and open slots?',
                'Open <strong>Clinic Schedules</strong> from the homepage or Patient Portal. Filter the monthly calendar by <strong>All Clinics</strong>, <strong>General Medical</strong>, <strong>Neurology</strong>, or <strong>Psychiatry</strong>, and select any open date to view exact consultation hours before booking.',
                ['schedule', 'doctor', 'availability', 'calendar', 'slots', 'physician', 'clinic schedules'],
                [
                    'What departments and clinic hours are available?',
                    'How do I book an appointment?',
                    'Can I book for a family member or just ask an inquiry?',
                ],
                6,
            ],
            [
                'other-patient-inquiry', 'en', 'both',
                'Can I book for a family member or just ask an inquiry?',
                'Yes. In Step 1 of <strong>Book Appointment</strong>, signed-in patients can book under their locked <strong>Verified Profile (Patient - Me)</strong> or select <strong>Other Patient</strong> for a spouse, child, or dependent. If you only have a question without reserving a clinic slot, check <strong>Inquiry Only</strong> and staff will contact you.',
                ['family', 'dependent', 'other patient', 'someone else', 'inquiry', 'question'],
                [
                    'How do I book an appointment?',
                    'How do I track or reschedule my appointment?',
                    'Where is MMWGH and how can I contact the clinic?',
                ],
                7,
            ],
            [
                'sdn-facility', 'en', 'both',
                'Where is MMWGH and how can I contact the clinic?',
                '<strong>Mariveles Mental Wellness and General Hospital</strong> is located at <strong>P. Monroe St., Poblacion, Mariveles, Bataan</strong>. Open the <strong>Location Map</strong> on the homepage to view directions and partner SDN facilities, or call OPU-Medical at <strong>(047) 935-4645</strong> for schedule assistance and verified record corrections.',
                ['where', 'location', 'address', 'map', 'contact', 'phone', 'hotline', 'sdn', 'mmwgh'],
                [
                    'What departments and clinic hours are available?',
                    'What should I prepare for my consultation?',
                    'How do I book an appointment?',
                ],
                8,
            ],
            [
                'dpa-consent', 'en', 'both',
                'How is my medical data protected?',
                'LINGKOD complies with <strong>Republic Act 10173 (Data Privacy Act of 2012)</strong>. Clinical notes and diagnoses are restricted to your attending department, your verified hospital identity is protected from unauthorized edits, and you can enable <strong>Two-Step Verification (Email OTP)</strong> anytime in <strong>Patient Portal &gt; Account Settings</strong>.',
                ['privacy', 'dpa', 'consent', 'data', 'security', 'otp', 'two-step', 'protected', '10173'],
                [
                    'How do I track or reschedule my appointment?',
                    'How do I book an appointment?',
                    'Where is MMWGH and how can I contact the clinic?',
                ],
                9,
            ],
            [
                'request-appointment', 'tl', 'both',
                'Paano ako mag-book ng appointment?',
                'Pindutin ang <strong>Book Appointment</strong>, tanggapin ang Data Privacy notice, at sagutan ang 4-step form (Personal Info, Medical Info, Appointment Details, at Confirmation). Maaaring mag-book mula <strong>Lunes hanggang Sabado</strong> (hindi bababa sa 1 araw bago ang konsulta) para sa Morning o Afternoon session. Magsisimula ito bilang <strong>For Approval</strong> habang bineberipika ng staff ang iskedyul ng doktor.',
                ['book', 'request', 'appointment', 'iskedyul', 'magpa-schedule', 'slot'],
                [
                    'Paano gumagana ang Teleconsultation para sa returning patients?',
                    'Maaari ba akong mag-book para sa kapamilya o magtanong lang (Inquiry)?',
                    'Paano ko masusuri o mare-reschedule ang aking appointment?',
                ],
                1,
            ],
            [
                'teleconsultation-flow', 'tl', 'both',
                'Paano gumagana ang Teleconsultation para sa returning patients?',
                'Ang mga first-time patients ay kinakailangan munang mag-<strong>Face-to-Face</strong> na konsultasyon. Kapag may kahit isang <strong>completed</strong> visit ka na, bubukas ang <strong>Teleconsultation</strong> sa Step 3 ng booking. Kapag naaprubahan ng staff, makikita ang iyong <strong>meeting link</strong> (Google Meet/Zoom) o tagubilin sa tawag nang direkta sa iyong <strong>Patient Portal</strong>.',
                ['teleconsult', 'teleconsultation', 'online', 'video', 'returning', 'remote'],
                [
                    'Paano ako mag-book ng appointment?',
                    'Ano ang dapat kong ihanda para sa aking konsultasyon?',
                    'Paano ko masusuri o mare-reschedule ang aking appointment?',
                ],
                2,
            ],
            [
                'hospital-hours', 'tl', 'both',
                'Anong mga departamento at oras ng klinika ang bukas?',
                'Bukas ang MMWGH Outpatient Clinics para sa <strong>General Medical</strong>, <strong>Neurology</strong>, at <strong>Psychiatry</strong> mula <strong>Lunes hanggang Sabado, 8:00 AM – 5:00 PM</strong> (Morning: 8:00 AM – 12:00 PM; Afternoon: 1:00 PM – 5:00 PM; sarado tuwing Linggo). Para sa agarang emergency, bukas 24/7 ang <strong>Emergency Triage</strong>.',
                ['departamento', 'oras', 'operasyon', 'bukas', 'general medical', 'neurology', 'psychiatry', 'emergency'],
                [
                    'Paano ko makikita ang iskedyul ng mga doktor?',
                    'Maaari ba akong mag-book para sa kapamilya o magtanong lang (Inquiry)?',
                    'Saan matatagpuan ang MMWGH at paano makikipag-ugnayan?',
                ],
                3,
            ],
            [
                'portal-status', 'tl', 'both',
                'Paano ko masusuri o mare-reschedule ang aking appointment?',
                'Mag-sign in sa <strong>Patient Portal</strong> gamit ang iyong username o email at password. Makikita rito ang status (<strong>For Approval</strong>, <strong>Confirmed</strong>, <strong>Checked In</strong>, <strong>Completed</strong>), nada-download ang <strong>Appointment/Teleconsultation Slip</strong> at <strong>Digital Patient ID</strong>, at maaaring mag-reschedule habang <strong>For Approval</strong> pa ang iyong iskedyul.',
                ['portal', 'status', 'reschedule', 'slip', 'qr', 'login', 'account'],
                [
                    'Ano ang dapat kong ihanda para sa aking konsultasyon?',
                    'Paano gumagana ang Teleconsultation para sa returning patients?',
                    'Paano pinoprotektahan ang aking medical data?',
                ],
                4,
            ],
            [
                'consultation-requirements', 'tl', 'both',
                'Ano ang dapat kong ihanda para sa aking konsultasyon?',
                'Para sa <strong>Face-to-Face</strong> na konsultasyon, magdala ng valid ID, printable <strong>Appointment Slip</strong> o <strong>Digital Patient ID (QR)</strong> mula sa Patient Portal, at mga nakaraang reseta o lab results. Para sa <strong>Teleconsultation</strong>, panatilihing bukas ang iyong cellphone at tingnan ang Patient Portal 5–10 minuto bago ang iyong iskedyul.',
                ['dalhin', 'ihanda', 'dokumento', 'id', 'slip', 'reseta', 'lab'],
                [
                    'Paano gumagana ang Teleconsultation para sa returning patients?',
                    'Saan matatagpuan ang MMWGH at paano makikipag-ugnayan?',
                    'Paano pinoprotektahan ang aking medical data?',
                ],
                5,
            ],
            [
                'schedules', 'tl', 'both',
                'Paano ko makikita ang iskedyul ng mga doktor?',
                'Buksan ang <strong>Clinic Schedules</strong> sa homepage o Patient Portal. Maaari mong i-filter ang buwanang kalendaryo sa <strong>General Medical</strong>, <strong>Neurology</strong>, o <strong>Psychiatry</strong> at i-click ang bukas na petsa upang makita ang oras ng mga doktor.',
                ['iskedyul', 'doktor', 'calendar', 'slots', 'availability'],
                [
                    'Anong mga departamento at oras ng klinika ang bukas?',
                    'Paano ako mag-book ng appointment?',
                    'Maaari ba akong mag-book para sa kapamilya o magtanong lang (Inquiry)?',
                ],
                6,
            ],
            [
                'other-patient-inquiry', 'tl', 'both',
                'Maaari ba akong mag-book para sa kapamilya o magtanong lang (Inquiry)?',
                'Oo. Sa Step 1 ng <strong>Book Appointment</strong>, maaaring gamitin ng signed-in patient ang kanyang naka-lock na <strong>Verified Profile (Patient - Me)</strong> o piliin ang <strong>Other Patient</strong> para sa asawa, anak, o kaanak. Kung magtatanong lamang nang walang clinic slot, i-check ang <strong>Inquiry Only</strong>.',
                ['kapamilya', 'dependent', 'other patient', 'inquiry', 'magtanong'],
                [
                    'Paano ako mag-book ng appointment?',
                    'Paano ko masusuri o mare-reschedule ang aking appointment?',
                    'Saan matatagpuan ang MMWGH at paano makikipag-ugnayan?',
                ],
                7,
            ],
            [
                'sdn-facility', 'tl', 'both',
                'Saan matatagpuan ang MMWGH at paano makikipag-ugnayan?',
                'Matatagpuan ang <strong>Mariveles Mental Wellness and General Hospital</strong> sa <strong>P. Monroe St., Poblacion, Mariveles, Bataan</strong>. Tingnan ang <strong>Location Map</strong> sa homepage para sa direksyon at partner SDN facilities, o tumawag sa OPU-Medical sa <strong>(047) 935-4645</strong>.',
                ['saan', 'lokasyon', 'mapa', 'contact', 'telepono', 'hotline', 'mmwgh'],
                [
                    'Anong mga departamento at oras ng klinika ang bukas?',
                    'Ano ang dapat kong ihanda para sa aking konsultasyon?',
                    'Paano ako mag-book ng appointment?',
                ],
                8,
            ],
            [
                'dpa-consent', 'tl', 'both',
                'Paano pinoprotektahan ang aking medical data?',
                'Sumusunod ang LINGKOD sa <strong>Republic Act 10173 (Data Privacy Act of 2012)</strong>. Ang iyong mga clinical notes ay nakahiwalay sa bawat departamento, protektado ang iyong verified hospital profile, at maaari kang mag-enable ng <strong>Two-Step Verification (Email OTP)</strong> sa <strong>Patient Portal &gt; Account Settings</strong>.',
                ['privacy', 'dpa', 'consent', 'datos', 'seguridad', 'otp', '10173'],
                [
                    'Paano ko masusuri o mare-reschedule ang aking appointment?',
                    'Paano ako mag-book ng appointment?',
                    'Saan matatagpuan ang MMWGH at paano makikipag-ugnayan?',
                ],
                9,
            ],
        ];
        foreach ($seedFaqs as $f) {
            $kwJson = json_encode($f[5]);
            $fuJson = json_encode($f[6]);
            $insFaq->execute([$f[0], $f[1], $f[2], $f[3], $f[4], $kwJson, $fuJson, $f[7]]);
            $updFaq->execute([$f[2], $f[3], $f[4], $kwJson, $fuJson, $f[7], $f[0], $f[1]]);
        }
        @file_put_contents($faqSyncLock, '1');
    }
}
