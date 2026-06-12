<?php
/**
 * api/leave.php
 *
 * GET  - Fetch all leave requests (Admin) or current user's leave requests (Staff)
 * POST - Create leave request (action: 'create')
 * POST - Approve/Reject leave request (action: 'update_status', Admin only)
 */

session_start();
require_once __DIR__ . '/config/cors.php';
require_once __DIR__ . '/config/db.php';
requireAuth();

$method = $_SERVER['REQUEST_METHOD'];
$actor  = sessionUser();
$role   = $_SESSION['role'] ?? 'staff';
$userId = $_SESSION['user_id'] ?? 0;

if ($method === 'GET') {
    if ($role === 'admin') {
        // Admin sees all leave requests with username
        $stmt = $pdo->query(
            "SELECT l.id, l.start_date, l.end_date, l.reason, l.status, l.created_at, u.username, u.department
             FROM leave_requests l
             JOIN users u ON l.user_id = u.id
             ORDER BY l.created_at DESC"
        );
        respondOk(['data' => $stmt->fetchAll()]);
    } else {
        // Staff sees only their own requests
        $stmt = $pdo->prepare(
            "SELECT id, start_date, end_date, reason, status, created_at
             FROM leave_requests
             WHERE user_id = ?
             ORDER BY created_at DESC"
        );
        $stmt->execute([$userId]);
        respondOk(['data' => $stmt->fetchAll()]);
    }
}

if ($method === 'POST') {
    $b = getBody();
    $action = $b['action'] ?? '';

    if ($action === 'create') {
        $start = trim($b['start_date'] ?? '');
        $end   = trim($b['end_date'] ?? '');
        $reason = trim($b['reason'] ?? '');

        if (!$start || !$end) respondErr('Start and End dates are required.');

        $stmt = $pdo->prepare(
            "INSERT INTO leave_requests (user_id, start_date, end_date, reason)
             VALUES (?, ?, ?, ?)"
        );
        $stmt->execute([$userId, $start, $end, $reason]);

        addLog($pdo, 'info', 'Leave Requested', $actor, "$actor requested leave from $start to $end.");
        respondOk(['message' => 'Leave request submitted successfully.']);
    }

    if ($action === 'update_status' && $role === 'admin') {
        $reqId = $b['request_id'] ?? '';
        $status = $b['status'] ?? ''; // 'approved', 'rejected', or 'pending'

        if (!$reqId || !in_array($status, ['approved', 'rejected', 'pending'])) {
            respondErr('Invalid request data.');
        }

        $stmt = $pdo->prepare("UPDATE leave_requests SET status = ? WHERE id = ?");
        $stmt->execute([$status, $reqId]);

        addLog($pdo, 'info', 'Leave ' . ucfirst($status), $actor, "Leave request #$reqId was $status by admin.");
        respondOk(['message' => "Leave request $status."]);
    }

    respondErr('Invalid action or unauthorized.');
}

respondErr('Method not allowed.', 405);
