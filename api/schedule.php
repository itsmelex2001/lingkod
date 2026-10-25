<?php
/**
 * api/schedule.php
 *
 * GET  - Fetch master schedule
 */

session_start();
require_once __DIR__ . '/config/cors.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/db.php';

$method = $_SERVER['REQUEST_METHOD'];
$actor  = $_SESSION['user'] ?? null;
$role   = $_SESSION['role'] ?? 'public';

  if ($method === 'GET') {
      // Dynamically fetch department for staff
      $staffDept = '';
      if ($role === 'staff' && !empty($_SESSION['user'])) {
          $stmtDept = $pdo->prepare("SELECT department FROM users WHERE username = ?");
          $stmtDept->execute([$_SESSION['user']]);
          $staffDept = $stmtDept->fetchColumn();
      }

      if ($role === 'admin' || $role === 'public' || empty($staffDept)) {
          $stmt = $pdo->query(
              "SELECT id, name, department, specialization, status, total_slots, booked_slots, schedule_date, schedule_start, schedule_end
               FROM doctors
               ORDER BY department, name"
          );
          respondOk(['data' => $stmt->fetchAll()]);
      } else {
          $whereDept = "LOWER(department) = LOWER(?)";
          $params = [$staffDept];
          if (stripos($staffDept, 'general') !== false) {
              $whereDept = "LOWER(department) LIKE '%general%'";
              $params = [];
          } elseif (stripos($staffDept, 'psychiatry') !== false || stripos($staffDept, 'opu') !== false) {
              $whereDept = "(LOWER(department) LIKE '%psychiatry%' OR LOWER(department) LIKE '%opu%')";
              $params = [];
          } elseif (stripos($staffDept, 'neuro') !== false) {
              $whereDept = "LOWER(department) LIKE '%neuro%'";
              $params = [];
          }
          $stmt = $pdo->prepare(
              "SELECT id, name, department, specialization, status, total_slots, booked_slots, schedule_date, schedule_start, schedule_end
               FROM doctors
               WHERE $whereDept
               ORDER BY department, name"
          );
          $stmt->execute($params);
          respondOk(['data' => $stmt->fetchAll()]);
      }
  }

if ($method === 'POST') {
    requireAuth();
    $b = getBody();
    $action = $b['action'] ?? '';

    // Staff or admin can update doctor status
    if ($action === 'set_status') {
        if ($role !== 'staff' && $role !== 'admin') respondErr('Unauthorized', 403);
        $status = $b['status'] ?? 'leave';
        $doctorId = $b['doctor_id'] ?? null;
        $doctorName = trim($b['doctor_name'] ?? '');

        // Valid statuses: in_clinic, on_break, emergency, leave, available, booked
        $validStatuses = ['in_clinic', 'on_break', 'emergency', 'leave', 'available', 'booked'];
        if (!in_array($status, $validStatuses)) {
            respondErr('Invalid doctor status.');
        }

        if (!empty($doctorId)) {
            $stmt = $pdo->prepare("UPDATE doctors SET status = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$status, $doctorId]);
            respondOk(['message' => "Doctor status updated to $status"]);
        } elseif (!empty($doctorName)) {
            $cleanDoc = trim(str_replace('Dr. ', '', $doctorName));
            $stmt = $pdo->prepare("UPDATE doctors SET status = ?, updated_at = NOW() WHERE name = ? OR name = ? OR REPLACE(name, 'Dr. ', '') = ?");
            $stmt->execute([$status, $doctorName, "Dr. $cleanDoc", $cleanDoc]);
            respondOk(['message' => "Doctor status updated to $status"]);
        } else {
            $staffDept = '';
            if ($role === 'staff') {
                $stmtDept = $pdo->prepare("SELECT department FROM users WHERE username = ?");
                $stmtDept->execute([$actor]);
                $staffDept = $stmtDept->fetchColumn();
            }

            // update all doctors in this department
            if ($staffDept) {
                if (stripos($staffDept, 'general') !== false) {
                    $stmt = $pdo->prepare("UPDATE doctors SET status = ?, updated_at = NOW() WHERE LOWER(department) LIKE '%general%'");
                    $stmt->execute([$status]);
                } elseif (stripos($staffDept, 'psychiatry') !== false || stripos($staffDept, 'opu') !== false) {
                    $stmt = $pdo->prepare("UPDATE doctors SET status = ?, updated_at = NOW() WHERE LOWER(department) LIKE '%psychiatry%' OR LOWER(department) LIKE '%opu%'");
                    $stmt->execute([$status]);
                } elseif (stripos($staffDept, 'neuro') !== false) {
                    $stmt = $pdo->prepare("UPDATE doctors SET status = ?, updated_at = NOW() WHERE LOWER(department) LIKE '%neuro%'");
                    $stmt->execute([$status]);
                } else {
                    $stmt = $pdo->prepare("UPDATE doctors SET status = ?, updated_at = NOW() WHERE LOWER(department) = LOWER(?)");
                    $stmt->execute([$status, $staffDept]);
                }
            }
            respondOk(['message' => "Doctor status set to $status"]);
        }
    }

    if ($role !== 'admin') respondErr('Unauthorized', 403);
    
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
    
    if ($action === 'override') {
        $doctorId = $b['doctor_id'] ?? '';
        if (!$doctorId) respondErr('Doctor ID is required.');
        $stmt = $pdo->prepare("UPDATE doctors SET status = 'leave' WHERE id = ?");
        $stmt->execute([$doctorId]);
        
        addLog($pdo, 'warning', 'Absence Override', $actor, "Admin forced leave status for doctor ID $doctorId.");
        respondOk(['message' => 'Doctor marked as absent.']);
    }
    
    if ($action === 'restore') {
        $doctorId = $b['doctor_id'] ?? '';
        if (!$doctorId) respondErr('Doctor ID is required.');
        $stmt = $pdo->prepare("UPDATE doctors SET status = 'available' WHERE id = ?");
        $stmt->execute([$doctorId]);
        
        addLog($pdo, 'info', 'Absence Override Removed', $actor, "Admin restored availability for doctor ID $doctorId.");
        respondOk(['message' => 'Doctor marked as available.']);
    }
    
    respondErr('Invalid action.');
}

respondErr('Method not allowed.', 405);
