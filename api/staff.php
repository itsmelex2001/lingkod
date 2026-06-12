<?php
/**
 * api/staff.php
 *
 * GET                               → list all staff (admin only) OR list approved patients (staff)
 * GET  ?patient_id=PT-2026-001      → fetch progress notes for one patient
 * POST {action:'update', patient_id, note}
 * POST {action:'assign', patient_id, doctor, assigned_date, notify_method}
 * POST {action:'remind', patient_id, interval, method}
 * POST {action:'create', username, department}        (admin only)
 * POST {action:'toggle', user_id, status}             (admin only)
 * DELETE ?user_id=1                                    (admin only - remove staff)
 */

session_start();
require_once __DIR__ . '/config/cors.php';
require_once __DIR__ . '/config/db.php';

requireAuth();

$method = $_SERVER['REQUEST_METHOD'];
$actor  = sessionUser();
$role   = $_SESSION['role'] ?? 'staff';

// ── GET: list staff ────────────────────────────────────────────────────────
if ($method === 'GET') {

    // Get all staff (for both admin and staff portals), excluding current user
    if (!isset($_GET['patient_id'])) {
        $stmt = $pdo->prepare(
            "SELECT id, username, full_name, role, department, created_at, status, 
                    CASE WHEN role = 'admin' THEN 'admin' ELSE COALESCE(department, 'Unassigned') END AS dept
             FROM users
             WHERE username != ?
             ORDER BY role DESC, username ASC"
        );
        $stmt->execute([$actor]);
        respondOk(['data' => $stmt->fetchAll()]);
    }

    // Fetch patient progress notes (staff or admin)
    $patientId = $_GET['patient_id'] ?? null;
    if ($patientId) {
        $stmt = $pdo->prepare(
            "SELECT id, note, noted_by,
                    DATE_FORMAT(created_at,'%M %d, %Y %h:%i %p') AS created_at
             FROM progress_notes
             WHERE patient_id = ?
             ORDER BY created_at ASC"
        );
        $stmt->execute([$patientId]);
        respondOk(['data' => $stmt->fetchAll()]);
    }
}

// ── POST: staff CRUD or patient actions ──────────────────────────────────────
if ($method === 'POST') {
    $b      = getBody();
    $action = $b['action'] ?? '';

    // ── ADMIN: Create staff account ──────────────────────────────────────────
    if ($action === 'create' && $role === 'admin') {
        $fullName   = trim($b['full_name'] ?? '');
        $username   = trim($b['username'] ?? '');
        $password   = $b['password'] ?? '';
        $mobile     = trim($b['mobile_number'] ?? '');
        $department = trim($b['department'] ?? '');

        if ($fullName === '') respondErr('Full Name is required.');
        if ($username === '') respondErr('Username is required.');
        if ($password === '') respondErr('Password is required.');
        if ($mobile === '') respondErr('Mobile Number is required.');
        if ($department === '') respondErr('Department is required.');

        // Check if username exists
        $check = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $check->execute([$username]);
        if ($check->fetch()) respondErr('Username already exists.', 400);

        // Check staff limit per department
        $deptCheck = $pdo->prepare("SELECT COUNT(*) FROM users WHERE department = ? AND role = 'staff' AND status = 'active'");
        $deptCheck->execute([$department]);
        if ($deptCheck->fetchColumn() >= 3) {
            respondErr("Cannot add more staff. $department already has 3 active staff members.");
        }

        // Hash provided password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Create staff account
        $stmt = $pdo->prepare(
            "INSERT INTO users (username, full_name, mobile_number, password, role, department) 
             VALUES (?, ?, ?, ?, 'staff', ?)"
        );
        $stmt->execute([$username, $fullName, $mobile, $hashedPassword, $department]);

        addLog($pdo, 'info', 'Staff Account Created', $actor,
               "New staff account '$username' ($fullName) created for $department.");

        respondOk([
            'message' => 'Staff account created successfully.',
            'username' => $username
        ]);
    }

    // ── ADMIN: Toggle staff status ───────────────────────────────────────────
    if ($action === 'toggle' && $role === 'admin') {
        $userId = $b['user_id'] ?? '';
        $status = $b['status'] ?? '';

        if ($userId === '') respondErr('user_id is required.');
        if (!in_array($status, ['active', 'inactive'])) {
            respondErr('status must be "active" or "inactive".');
        }

        // Prevent deactivating yourself
        $check = $pdo->prepare("SELECT username FROM users WHERE id = ?");
        $check->execute([$userId]);
        $staffUser = $check->fetch();
        if (!$staffUser) respondErr('Staff member not found.', 404);

        if ($staffUser['username'] === $actor) {
            respondErr('You cannot deactivate your own account.', 403);
        }

        // Update user status in users table
        $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
        $stmt->execute([$status, $userId]);

        addLog($pdo, 'info', 'Staff Status Changed', $actor,
               "Staff '{$staffUser['username']}' status changed to $status.");

        respondOk(['message' => "Staff member marked as $status."]);
    }

    // ── ADMIN: Delete staff account ──────────────────────────────────────────
    if ($action === 'delete' && $role === 'admin') {
        $userId = $b['user_id'] ?? '';
        if ($userId === '') respondErr('user_id is required.');

        $check = $pdo->prepare("SELECT username FROM users WHERE id = ?");
        $check->execute([$userId]);
        $staffUser = $check->fetch();
        if (!$staffUser) respondErr('Staff member not found.', 404);

        if ($staffUser['username'] === $actor) {
            respondErr('You cannot delete your own account.', 403);
        }

        $del = $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'staff'");
        $del->execute([$userId]);

        addLog($pdo, 'warning', 'Staff Account Deleted', $actor,
               "Staff account '{$staffUser['username']}' has been deleted.");

        respondOk(['message' => 'Staff account deleted.']);
    }

    // ── Patient actions (staff or admin) ─────────────────────────────────────

    $patientId = $b['patient_id'] ?? '';

    if ($patientId === '') respondErr('patient_id is required.');

    // Verify patient exists
    $check = $pdo->prepare("SELECT full_name, appointment_date, status FROM patients WHERE patient_id = ?");
    $check->execute([$patientId]);
    $patient = $check->fetch();
    if (!$patient) respondErr("Patient $patientId not found.", 404);

    // Guard: cannot act on rejected or pending patients
    if ($patient['status'] !== 'approved') {
        respondErr("Action denied: patient is not approved (status: {$patient['status']}).", 403);
    }

    $name = $patient['full_name'];

    // ── UPDATE: add progress note ────────────────────────────────────────────
    if ($action === 'update') {
        $note = trim($b['note'] ?? '');
        if ($note === '') respondErr('Note text is required.');

        $stmt = $pdo->prepare(
            "INSERT INTO progress_notes (patient_id, note, noted_by) VALUES (?, ?, ?)"
        );
        $stmt->execute([$patientId, $note, $actor]);

        addLog($pdo, 'info', 'Progress Note Added', $actor,
               "New progress note added for $patientId ($name).");

        respondOk(['message' => 'Progress note saved.']);
    }

    // ── ASSIGN: set attending doctor ─────────────────────────────────────────
    if ($action === 'assign') {
        $doctor       = trim($b['doctor']        ?? '');
        $assignedDate = trim($b['assigned_date'] ?? '');
        $notifyMethod = trim($b['notify_method'] ?? 'Email');

        if ($doctor === '' || $assignedDate === '') {
            respondErr('doctor and assigned_date are required.');
        }

        $upd = $pdo->prepare(
            "UPDATE patients SET assigned_doctor = ?, assigned_date = ?, updated_at = NOW()
             WHERE patient_id = ?"
        );
        $upd->execute([$doctor, $assignedDate, $patientId]);

        $prefDate = $patient['appointment_date'];
        $mismatch = $prefDate !== $assignedDate;

        if ($mismatch) {
            addLog($pdo, 'warning', 'Schedule Mismatch', $actor,
                   "$name preferred $prefDate but assigned $assignedDate. Notification via $notifyMethod.");
        } else {
            addLog($pdo, 'info', 'Doctor Assigned', $actor,
                   "$doctor assigned to $patientId ($name) on $assignedDate.");
        }

        respondOk([
            'mismatch'      => $mismatch,
            'pref_date'     => $prefDate,
            'assigned_date' => $assignedDate,
            'notify_method' => $notifyMethod,
            'message'       => $mismatch
                ? "Mismatch detected. Notification will be sent via $notifyMethod."
                : "$doctor assigned on the patient's preferred date.",
        ]);
    }

    // ── REMIND: schedule patient reminder ────────────────────────────────────
    if ($action === 'remind') {
        $interval     = $b['interval'] ?? '3 days';
        $remindMethod = $b['method']   ?? 'Email';

        // Fetch assigned date
        $dateStmt = $pdo->prepare("SELECT assigned_date FROM patients WHERE patient_id = ?");
        $dateStmt->execute([$patientId]);
        $row = $dateStmt->fetch();
        $assignedDate = $row['assigned_date'] ?? null;

        if (!$assignedDate) {
            respondErr('No assigned date found. Please assign a doctor first.');
        }

        // Calculate reminder dispatch date
        $days       = (int) filter_var($interval, FILTER_SANITIZE_NUMBER_INT);
        $dispatchDt = (new DateTime($assignedDate))->modify("-{$days} days");
        $dispatchStr = $dispatchDt->format('F j, Y');

        addLog($pdo, 'info', 'Reminder Scheduled', $actor,
               "Reminder for $patientId ($name) set via $remindMethod on $dispatchStr ($interval before appointment).");

        respondOk([
            'dispatch_date' => $dispatchStr,
            'interval'      => $interval,
            'method'        => $remindMethod,
            'message'       => "Reminder scheduled via $remindMethod on $dispatchStr.",
        ]);
    }

    respondErr("Unknown action '$action'.");
}

// ── DELETE: remove staff (admin only) ──────────────────────────────────────────
if ($method === 'DELETE' && $role === 'admin') {
    $userId = $_GET['user_id'] ?? '';
    if ($userId === '') respondErr('user_id is required.');

    $check = $pdo->prepare("SELECT username FROM users WHERE id = ?");
    $check->execute([$userId]);
    $staffUser = $check->fetch();
    if (!$staffUser) respondErr('Staff member not found.', 404);

    if ($staffUser['username'] === $actor) {
        respondErr('You cannot delete your own account.', 403);
    }

    $del = $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'staff'");
    $result = $del->execute([$userId]);

    if ($del->rowCount() === 0) {
        respondErr('Could not delete staff account.', 400);
    }

    addLog($pdo, 'warning', 'Staff Account Deleted', $actor,
           "Staff account '{$staffUser['username']}' has been permanently deleted.");

    respondOk(['message' => 'Staff account deleted.']);
}

respondErr('Method not allowed.', 405);
