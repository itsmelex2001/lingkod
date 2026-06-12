<?php
/**
 * api/swaps.php
 *
 * GET  - Fetch all shift swaps (Admin)
 * POST - Approve/Reject a swap (Admin)
 */

session_start();
require_once __DIR__ . '/config/cors.php';
require_once __DIR__ . '/config/db.php';
requireAuth();

$method = $_SERVER['REQUEST_METHOD'];
$actor  = sessionUser();
$role   = $_SESSION['role'] ?? 'staff';

if ($method === 'GET') {
    $stmt = $pdo->query(
        "SELECT s.id, s.department, s.status, s.request_date,
                u1.username as requester, u2.username as target
         FROM shift_swaps s
         JOIN users u1 ON s.requester_id = u1.id
         JOIN users u2 ON s.target_user_id = u2.id
         ORDER BY s.request_date DESC"
    );
    respondOk(['data' => $stmt->fetchAll()]);
}

if ($method === 'POST') {
    if ($role !== 'admin') respondErr('Unauthorized', 403);
    
    $b = getBody();
    $action = $b['action'] ?? '';
    
    if ($action === 'update_status') {
        $id = $b['swap_id'] ?? '';
        $status = $b['status'] ?? '';
        
        if (!$id || !in_array($status, ['approved', 'rejected'])) {
            respondErr('Invalid request');
        }
        
        $stmt = $pdo->prepare("UPDATE shift_swaps SET status = ?, resolved_at = NOW() WHERE id = ?");
        $stmt->execute([$status, $id]);
        
        addLog($pdo, 'info', 'Shift Swap ' . ucfirst($status), $actor, "Shift swap request #$id was $status.");
        respondOk(['message' => "Swap $status."]);
    }
    
    respondErr('Invalid action.');
}

respondErr('Method not allowed.', 405);
