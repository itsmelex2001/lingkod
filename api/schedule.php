<?php
/**
 * api/schedule.php
 *
 * GET  - Fetch master schedule
 */

session_start();
require_once __DIR__ . '/config/cors.php';
require_once __DIR__ . '/config/db.php';
requireAuth();

$method = $_SERVER['REQUEST_METHOD'];
$actor  = sessionUser();
$role   = $_SESSION['role'] ?? 'staff';

  if ($method === 'GET') {
      // Dynamically fetch department for staff
      $staffDept = '';
      if ($role === 'staff' && !empty($_SESSION['user'])) {
          $stmtDept = $pdo->prepare("SELECT department FROM users WHERE username = ?");
          $stmtDept->execute([$_SESSION['user']]);
          $staffDept = $stmtDept->fetchColumn();
      }

      if ($role === 'admin' || empty($staffDept)) {
          $stmt = $pdo->query(
              "SELECT id, name, department, specialization, status, total_slots, booked_slots, schedule_date
               FROM doctors
               ORDER BY department, name"
          );
          respondOk(['data' => $stmt->fetchAll()]);
      } else {
          $stmt = $pdo->prepare(
              "SELECT id, name, department, specialization, status, total_slots, booked_slots, schedule_date
               FROM doctors
               WHERE LOWER(department) = LOWER(?)
               ORDER BY department, name"
          );
          $stmt->execute([$staffDept]);
          respondOk(['data' => $stmt->fetchAll()]);
      }
  }

if ($method === 'POST') {
    if ($role !== 'admin') respondErr('Unauthorized', 403);
    
    $b = getBody();
    $action = $b['action'] ?? '';
    
    if ($action === 'create' || $action === 'edit') {
        $doctorId = $b['doctor_id'] ?? '';
        $sDate = $b['schedule_date'] ?? null;
        $sStart = $b['schedule_start'] ?? null;
        $sEnd = $b['schedule_end'] ?? null;
        
        if (!$doctorId) respondErr('Doctor ID is required.');
        if (!$sDate || !$sStart || !$sEnd) respondErr('Date and Time fields are required.');
        
        $stmt = $pdo->prepare("UPDATE doctors SET schedule_date = ?, schedule_start = ?, schedule_end = ?, status = 'available' WHERE id = ?");
        $stmt->execute([$sDate, $sStart, $sEnd, $doctorId]);
        
        addLog($pdo, 'info', 'Schedule Updated', $actor, "Admin updated schedule dates/times for doctor ID $doctorId.");
        respondOk(['message' => 'Schedule saved successfully.']);
    }
    
    respondErr('Invalid action.');
}

respondErr('Method not allowed.', 405);
