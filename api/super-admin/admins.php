<?php
/**
 * api/super-admin/admins.php
 * Role-Based Access Control (RBAC) & Admin Management Endpoint
 * Strictly protected for Super Admin users.
 * 
 * Supports:
 * - GET: List all regular admin accounts with permission metadata
 * - POST (action=create): Create a new admin with hashed password & granular permissions
 * - POST (action=update): Edit admin profile and update permissions
 * - POST (action=toggle_status): Toggle active/inactive
 * - POST (action=delete): Remove regular admin account
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/auth-guard.php';
verifySuperAdminSession(true);

require_once __DIR__ . '/../config/db.php';

// Permissions store path (maps user_id to granular permissions)
$permFile = __DIR__ . '/admin-permissions.json';

function getAdminPermissions(string $file): array {
    if (!file_exists($file)) return [];
    $data = json_decode(file_get_contents($file), true);
    return is_array($data) ? $data : [];
}

function saveAdminPermissions(string $file, array $perms): bool {
    return file_put_contents($file, json_encode($perms, JSON_PRETTY_PRINT)) !== false;
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    try {
        $stmt = $pdo->query("
            SELECT id, username, full_name, role, status, department, created_at, last_login 
            FROM users 
            WHERE LOWER(role) = 'admin'
            ORDER BY id DESC
        ");
        $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $permsMap = getAdminPermissions($permFile);

        foreach ($admins as &$adm) {
            $uId = (string)$adm['id'];
            $adm['permissions'] = $permsMap[$uId] ?? [
                'can_manage_doctors' => true,
                'can_manage_staff' => true,
                'can_export_data' => true,
                'can_verify_patients' => true,
                'can_view_audit' => true
            ];
        }

        echo json_encode(['success' => true, 'data' => $admins]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $action = $input['action'] ?? '';

    // 1. CREATE ADMIN
    if ($action === 'create') {
        $username = trim($input['username'] ?? '');
        $fullName = trim($input['full_name'] ?? '');
        $password = $input['password'] ?? '';
        $permissions = $input['permissions'] ?? [
            'can_manage_doctors' => true,
            'can_manage_staff' => true,
            'can_export_data' => true,
            'can_verify_patients' => true,
            'can_view_audit' => true
        ];

        if ($username === '' || $fullName === '' || $password === '') {
            echo json_encode(['success' => false, 'message' => 'Please fill in all required fields.']);
            exit;
        }

        if (strlen($password) < 6) {
            echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters.']);
            exit;
        }

        // Check if username taken
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = :u LIMIT 1");
        $stmt->execute(['u' => $username]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Username is already taken.']);
            exit;
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("
            INSERT INTO users (username, full_name, password, role, status, department, created_at)
            VALUES (:u, :fn, :p, 'admin', 'active', 'Administration', NOW())
        ");
        $stmt->execute([
            'u' => $username,
            'fn' => $fullName,
            'p' => $hash
        ]);

        $newId = $pdo->lastInsertId();
        $permsMap = getAdminPermissions($permFile);
        $permsMap[(string)$newId] = $permissions;
        saveAdminPermissions($permFile, $permsMap);

        echo json_encode(['success' => true, 'message' => 'Admin account created successfully.']);
        exit;
    }

    // 2. UPDATE ADMIN
    if ($action === 'update') {
        $id = (int)($input['id'] ?? 0);
        $fullName = trim($input['full_name'] ?? '');
        $password = $input['password'] ?? '';
        $permissions = $input['permissions'] ?? null;

        if ($id <= 0 || $fullName === '') {
            echo json_encode(['success' => false, 'message' => 'Invalid parameters.']);
            exit;
        }

        if (!empty($password) && strlen($password) >= 6) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET full_name = :fn, password = :p WHERE id = :id AND LOWER(role) = 'admin'");
            $stmt->execute(['fn' => $fullName, 'p' => $hash, 'id' => $id]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET full_name = :fn WHERE id = :id AND LOWER(role) = 'admin'");
            $stmt->execute(['fn' => $fullName, 'id' => $id]);
        }

        if (is_array($permissions)) {
            $permsMap = getAdminPermissions($permFile);
            $permsMap[(string)$id] = $permissions;
            saveAdminPermissions($permFile, $permsMap);
        }

        echo json_encode(['success' => true, 'message' => 'Admin account updated successfully.']);
        exit;
    }

    // 3. TOGGLE STATUS
    if ($action === 'toggle_status') {
        $id = (int)($input['id'] ?? 0);
        $status = strtolower(trim($input['status'] ?? ''));
        if (!in_array($status, ['active', 'inactive'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid status.']);
            exit;
        }

        $stmt = $pdo->prepare("UPDATE users SET status = :st WHERE id = :id AND LOWER(role) = 'admin'");
        $stmt->execute(['st' => $status, 'id' => $id]);

        echo json_encode(['success' => true, 'message' => 'Status updated.']);
        exit;
    }

    // 4. DELETE ADMIN
    if ($action === 'delete') {
        $id = (int)($input['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid admin ID.']);
            exit;
        }

        $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id AND LOWER(role) = 'admin'");
        $stmt->execute(['id' => $id]);

        $permsMap = getAdminPermissions($permFile);
        unset($permsMap[(string)$id]);
        saveAdminPermissions($permFile, $permsMap);

        echo json_encode(['success' => true, 'message' => 'Admin deleted successfully.']);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Unknown action.']);
    exit;
}

