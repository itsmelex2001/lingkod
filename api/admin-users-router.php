<?php
declare(strict_types=1);

/**
 * api/admin-users-router.php
 * Self-healing XAMPP MySQL router for Admin User Management (Staff & Patients).
 * Mirrors Laravel Admin\UserManagementController endpoints under /api/admin/users/*.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/cors.php';
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/patient_otp.php';

// ── Route Resolution ─────────────────────────────────────────────────────────
$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
$route  = $_GET['route'] ?? ($_SERVER['PATH_INFO'] ?? '');
if ($route === '') {
    $uriPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
    $route   = preg_replace('#^/sdn/api(?:/admin-users-router\.php)?#', '', $uriPath) ?? '';
}
$route = '/' . ltrim(rtrim($route, '/'), '/');

// ── Admin / Superadmin Guard ─────────────────────────────────────────────────
requireAuth();
$sessionRole = strtolower(trim((string)($_SESSION['role'] ?? '')));
if (!in_array($sessionRole, ['admin', 'superadmin'], true)) {
    respondErr('Forbidden. System Administrator access required.', 403);
}
$actorName = sessionUser();

// ── Self-Healing Schema Helpers ──────────────────────────────────────────────
ensurePatientOtpColumns($pdo);

$staffRoles = ['superadmin', 'admin', 'staff'];

function generateReadableTempPassword(string $prefix): string {
    $digits = str_pad((string)random_int(0, 9999), 4, '0', STR_PAD_LEFT);
    $chars  = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $suffix = $chars[random_int(0, strlen($chars) - 1)] . $chars[random_int(0, strlen($chars) - 1)];
    return "{$prefix}-{$digits}-{$suffix}";
}

function revokePatientTokens(PDO $pdo, int $patientId): void {
    try {
        $pdo->prepare("DELETE FROM patient_remember_tokens WHERE patient_db_id = ?")->execute([$patientId]);
    } catch (Throwable $ignore) {}
}

function writeUserAudit(PDO $pdo, string $actor, string $action, string $details): void {
    try {
        $pdo->prepare("INSERT INTO audit_logs (user, action, details) VALUES (?, ?, ?)")
            ->execute([$actor, $action, $details]);
    } catch (Throwable $ignore) {}
}

function findPatientPortalRow(PDO $pdo, int $id): ?array {
    $st = $pdo->prepare("SELECT * FROM patients WHERE id = ? AND username IS NOT NULL AND username <> '' LIMIT 1");
    $st->execute([$id]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

// ── GET /admin/users/staff ───────────────────────────────────────────────────
if ($method === 'GET' && $route === '/admin/users/staff') {
    $search = strtolower(trim((string)($_GET['search'] ?? '')));
    $dept   = strtolower(trim((string)($_GET['department'] ?? '')));
    $status = strtolower(trim((string)($_GET['status'] ?? '')));

    $where  = ["LOWER(role) IN ('superadmin', 'admin', 'staff')"];
    $params = [];

    if ($status !== '' && $status !== 'all') {
        $where[]  = "LOWER(COALESCE(status, 'active')) = ?";
        $params[] = $status;
    }

    if ($dept !== '' && $dept !== 'all') {
        if (str_contains($dept, 'psych') || str_contains($dept, 'opu')) {
            $where[] = "(LOWER(COALESCE(department, '')) LIKE '%psych%' OR LOWER(COALESCE(department, '')) LIKE '%opu%')";
        } elseif (str_contains($dept, 'neuro')) {
            $where[] = "LOWER(COALESCE(department, '')) LIKE '%neuro%'";
        } elseif (str_contains($dept, 'general')) {
            $where[] = "(LOWER(COALESCE(department, '')) LIKE '%general%' OR LOWER(COALESCE(department, '')) LIKE '%medical%')";
        } else {
            $where[]  = "LOWER(COALESCE(department, '')) = ?";
            $params[] = $dept;
        }
    }

    if ($search !== '') {
        $like     = '%' . $search . '%';
        $where[]  = "(LOWER(COALESCE(full_name, '')) LIKE ? OR LOWER(COALESCE(username, '')) LIKE ?)";
        $params[] = $like;
        $params[] = $like;
    }

    $sql = "SELECT id, username, full_name, role, department, status, profile_image, created_at
            FROM users
            WHERE " . implode(' AND ', $where) . "
            ORDER BY FIELD(LOWER(role), 'superadmin', 'admin', 'staff'), department ASC, full_name ASC";
    $st = $pdo->prepare($sql);
    $st->execute($params);

    $rows = [];
    foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $u) {
        $rows[] = [
            'id'            => (int)$u['id'],
            'full_name'     => trim((string)($u['full_name'] ?: $u['username'])),
            'username'      => (string)$u['username'],
            'role'          => strtolower((string)($u['role'] ?: 'staff')),
            'department'    => trim((string)($u['department'] ?: 'Unassigned')),
            'status'        => strtolower((string)($u['status'] ?: 'active')),
            'profile_image' => $u['profile_image'] ?? null,
            'created_at'    => $u['created_at'] ?? null,
        ];
    }

    respondOk(['data' => $rows]);
}

// ── POST /admin/users/staff ──────────────────────────────────────────────────
if ($method === 'POST' && $route === '/admin/users/staff') {
    $b          = getBody();
    $fullName   = trim((string)($b['full_name'] ?? ''));
    $username   = trim((string)($b['username'] ?? ''));
    $role       = strtolower(trim((string)($b['role'] ?? 'staff')));
    $department = trim((string)($b['department'] ?? ''));
    $password   = (string)($b['password'] ?? '');

    $errors = [];
    if ($fullName === '') $errors['full_name'] = ['Full name is required.'];
    if (!preg_match('/^[a-zA-Z0-9_]{4,30}$/', $username)) {
        $errors['username'] = ['Username must be 4–30 characters (letters, numbers, underscores).'];
    }
    if (!in_array($role, $staffRoles, true)) {
        $errors['role'] = ['Invalid role selected.'];
    }
    if ($department === '') $errors['department'] = ['Department is required.'];
    if (strlen($password) < 6) $errors['password'] = ['Password must be at least 6 characters.'];

    if ($errors) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Validation failed.', 'errors' => $errors]);
        exit;
    }

    $dup = $pdo->prepare("SELECT id FROM users WHERE LOWER(username) = LOWER(?) LIMIT 1");
    $dup->execute([$username]);
    if ($dup->fetch()) {
        http_response_code(422);
        echo json_encode([
            'success' => false,
            'message' => 'Username already exists.',
            'errors'  => ['username' => ['Username already exists.']],
        ]);
        exit;
    }

    $hash = password_hash($password, PASSWORD_BCRYPT);
    $ins  = $pdo->prepare(
        "INSERT INTO users (full_name, username, password, role, department, status) VALUES (?, ?, ?, ?, ?, 'active')"
    );
    $ins->execute([$fullName, $username, $hash, $role, $department]);
    $newId = (int)$pdo->lastInsertId();

    writeUserAudit($pdo, $actorName, 'STAFF_CREATE', "Created {$role} account {$username} ({$department})");

    http_response_code(201);
    echo json_encode([
        'success' => true,
        'message' => 'Staff account created.',
        'data'    => [
            'id'         => $newId,
            'full_name'  => $fullName,
            'username'   => $username,
            'role'       => $role,
            'department' => $department,
            'status'     => 'active',
        ],
    ]);
    exit;
}

// ── PUT /admin/users/staff/{id} ──────────────────────────────────────────────
if ($method === 'PUT' && preg_match('#^/admin/users/staff/(\d+)$#', $route, $m)) {
    $id = (int)$m[1];
    $st = $pdo->prepare("SELECT * FROM users WHERE id = ? AND LOWER(role) IN ('superadmin', 'admin', 'staff') LIMIT 1");
    $st->execute([$id]);
    $existing = $st->fetch(PDO::FETCH_ASSOC);
    if (!$existing) {
        respondErr('Staff member not found.', 404);
    }

    $b          = getBody();
    $fullName   = array_key_exists('full_name', $b) ? trim((string)$b['full_name']) : (string)$existing['full_name'];
    $role       = array_key_exists('role', $b) ? strtolower(trim((string)$b['role'])) : strtolower((string)$existing['role']);
    $department = array_key_exists('department', $b) ? trim((string)$b['department']) : (string)$existing['department'];
    $status     = array_key_exists('status', $b) ? strtolower(trim((string)$b['status'])) : strtolower((string)($existing['status'] ?: 'active'));

    if ($fullName === '' || !in_array($role, $staffRoles, true) || $department === '' || !in_array($status, ['active', 'inactive'], true)) {
        respondErr('Invalid staff details provided.', 422);
    }

    if (strtolower((string)$existing['role']) === 'superadmin' && ($role !== 'superadmin' || $status !== 'active')) {
        $cnt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE LOWER(role) = 'superadmin' AND id <> ? AND LOWER(COALESCE(status, 'active')) = 'active'");
        $cnt->execute([$id]);
        if ((int)$cnt->fetchColumn() === 0) {
            respondErr('Cannot demote or disable the only active Super Administrator.', 422);
        }
    }

    $upd = $pdo->prepare("UPDATE users SET full_name = ?, role = ?, department = ?, status = ? WHERE id = ?");
    $upd->execute([$fullName, $role, $department, $status, $id]);

    writeUserAudit($pdo, $actorName, 'STAFF_UPDATE', "Updated staff #{$id} ({$existing['username']}, role={$role}, status={$status})");

    respondOk(['message' => 'Staff account updated.']);
}

// ── POST /admin/users/staff/{id}/reset-password ──────────────────────────────
if ($method === 'POST' && preg_match('#^/admin/users/staff/(\d+)/reset-password$#', $route, $m)) {
    $id = (int)$m[1];
    $st = $pdo->prepare("SELECT id, username FROM users WHERE id = ? AND LOWER(role) IN ('superadmin', 'admin', 'staff') LIMIT 1");
    $st->execute([$id]);
    $existing = $st->fetch(PDO::FETCH_ASSOC);
    if (!$existing) {
        respondErr('Staff member not found.', 404);
    }

    $tempPassword = generateReadableTempPassword('STF');
    $hash = password_hash($tempPassword, PASSWORD_BCRYPT);
    $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$hash, $id]);

    writeUserAudit($pdo, $actorName, 'STAFF_PASSWORD_RESET', "Reset password for staff #{$id} ({$existing['username']})");

    respondOk([
        'message'            => 'Staff password has been reset.',
        'temporary_password' => $tempPassword,
    ]);
}

// ── GET /admin/users/patients ────────────────────────────────────────────────
if ($method === 'GET' && $route === '/admin/users/patients') {
    $search = strtolower(trim((string)($_GET['search'] ?? '')));

    $pCols = $pdo->query("SHOW COLUMNS FROM patients")->fetchAll(PDO::FETCH_COLUMN);
    $hasRecipient = in_array('appointment_recipient', $pCols, true);

    $where  = ["username IS NOT NULL", "username <> ''", "password IS NOT NULL", "password <> ''"];
    $params = [];

    if ($hasRecipient) {
        $where[] = "COALESCE(appointment_recipient, 'Patient (Me)') NOT IN ('Other Patient', 'Someone Else / Dependent')";
    }
    if ($search !== '') {
        $like     = '%' . $search . '%';
        $where[]  = "(LOWER(COALESCE(full_name, '')) LIKE ? OR LOWER(COALESCE(username, '')) LIKE ?)";
        $params[] = $like;
        $params[] = $like;
    }

    $sql = "SELECT id, patient_id, full_name, username, email, is_verified,
                   COALESCE(account_status, 'active') AS account_status,
                   COALESCE(must_change_password, 0) AS must_change_password,
                   created_at
            FROM patients
            WHERE " . implode(' AND ', $where) . "
            ORDER BY is_verified DESC, id ASC
            LIMIT 500";
    $st = $pdo->prepare($sql);
    $st->execute($params);

    $seen = [];
    $rows = [];
    foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $p) {
        $key = strtolower(trim((string)$p['username']));
        if ($key === '' || isset($seen[$key])) continue;
        $seen[$key] = true;
        $rows[] = [
            'id'                   => (int)$p['id'],
            'patient_id'           => (string)($p['patient_id'] ?: ('PT-' . $p['id'])),
            'full_name'            => trim((string)($p['full_name'] ?: 'Unnamed Patient')),
            'username'             => (string)$p['username'],
            'email'                => (string)($p['email'] ?? ''),
            'is_verified'          => ((int)($p['is_verified'] ?? 0) === 1),
            'account_status'       => strtolower((string)($p['account_status'] ?: 'active')),
            'must_change_password' => ((int)($p['must_change_password'] ?? 0) === 1),
            'created_at'           => $p['created_at'] ?? null,
        ];
    }

    respondOk(['data' => $rows]);
}

// ── POST /admin/users/patients/{id}/temp-password ────────────────────────────
if ($method === 'POST' && preg_match('#^/admin/users/patients/(\d+)/temp-password$#', $route, $m)) {
    $id = (int)$m[1];
    $patient = findPatientPortalRow($pdo, $id);
    if (!$patient) {
        respondErr('Patient account not found.', 404);
    }

    $tempPassword = generateReadableTempPassword('LKD');
    $hash = password_hash($tempPassword, PASSWORD_BCRYPT, ['cost' => 11]);
    $pdo->prepare("UPDATE patients SET password = ?, must_change_password = 1 WHERE id = ?")
        ->execute([$hash, $id]);

    revokePatientTokens($pdo, $id);
    writeUserAudit($pdo, $actorName, 'PATIENT_TEMP_PASSWORD', "Generated temporary password for patient #{$id} ({$patient['username']})");

    respondOk([
        'message'            => 'Temporary password generated. Provide it directly to the patient.',
        'temporary_password' => $tempPassword,
    ]);
}

// ── PATCH /admin/users/patients/{id}/email ───────────────────────────────────
if ($method === 'PATCH' && preg_match('#^/admin/users/patients/(\d+)/email$#', $route, $m)) {
    $id = (int)$m[1];
    $patient = findPatientPortalRow($pdo, $id);
    if (!$patient) {
        respondErr('Patient account not found.', 404);
    }

    $b        = getBody();
    $newEmail = strtolower(trim((string)($b['email'] ?? '')));
    if ($newEmail === '' || !filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
        http_response_code(422);
        echo json_encode([
            'success' => false,
            'message' => 'Please enter a valid email address.',
            'errors'  => ['email' => ['Please enter a valid email address.']],
        ]);
        exit;
    }

    $dup = $pdo->prepare(
        "SELECT id FROM patients
         WHERE LOWER(email) = ? AND id <> ?
           AND username IS NOT NULL AND username <> ''
           AND LOWER(username) <> LOWER(?)
         LIMIT 1"
    );
    $dup->execute([$newEmail, $id, (string)$patient['username']]);
    if ($dup->fetch()) {
        http_response_code(422);
        echo json_encode([
            'success' => false,
            'message' => 'That email address is already registered to another patient account.',
            'errors'  => ['email' => ['Email address is already in use by another account.']],
        ]);
        exit;
    }

    $pdo->prepare(
        "UPDATE patients
         SET email = ?, is_verified = 0, otp_code = NULL, otp_hash = NULL, otp_expires_at = NULL, otp_attempts = 0
         WHERE id = ?"
    )->execute([$newEmail, $id]);

    revokePatientTokens($pdo, $id);
    writeUserAudit($pdo, $actorName, 'PATIENT_EMAIL_UPDATE', "Updated email for patient #{$id} ({$patient['username']}); OTP re-verification required on next login");

    respondOk([
        'message' => 'Email updated. Patient must re-verify via OTP on next login.',
        'data'    => [
            'id'          => $id,
            'email'       => $newEmail,
            'is_verified' => false,
        ],
    ]);
}

// ── PATCH /admin/users/patients/{id}/status ──────────────────────────────────
if ($method === 'PATCH' && preg_match('#^/admin/users/patients/(\d+)/status$#', $route, $m)) {
    $id = (int)$m[1];
    $patient = findPatientPortalRow($pdo, $id);
    if (!$patient) {
        respondErr('Patient account not found.', 404);
    }

    $b       = getBody();
    $current = strtolower((string)($patient['account_status'] ?? 'active'));
    $next    = isset($b['account_status'])
        ? strtolower(trim((string)$b['account_status']))
        : ($current === 'suspended' ? 'active' : 'suspended');

    if (!in_array($next, ['active', 'suspended'], true)) {
        respondErr('Invalid account status.', 422);
    }

    $pdo->prepare("UPDATE patients SET account_status = ? WHERE id = ?")->execute([$next, $id]);
    if ($next === 'suspended') {
        revokePatientTokens($pdo, $id);
    }

    writeUserAudit($pdo, $actorName, 'PATIENT_ACCOUNT_STATUS', "Set patient #{$id} ({$patient['username']}) account_status={$next}");

    respondOk([
        'message' => $next === 'suspended' ? 'Patient account suspended.' : 'Patient account restored.',
        'data'    => [
            'id'             => $id,
            'account_status' => $next,
        ],
    ]);
}

respondErr('User management endpoint not found.', 404);
