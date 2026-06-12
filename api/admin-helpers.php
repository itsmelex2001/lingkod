<?php
/**
 * admin-helpers.php
 * 
 * Helper functions for admin operations
 * Include this in your admin pages to access common utilities
 */

require_once __DIR__ . '/config/db.php';

/**
 * Get staff summary statistics
 */
function getStaffStats($pdo) {
    $stats = [];
    
    try {
        $result = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'staff'");
        $stats['total_staff'] = $result->fetchColumn() ?? 0;
        
        $result = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'staff' AND status = 'active'");
        $stats['active_staff'] = $result->fetchColumn() ?? 0;
        
        $result = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'staff' AND status = 'inactive'");
        $stats['inactive_staff'] = $result->fetchColumn() ?? 0;
        
        $result = $pdo->query("SELECT department, COUNT(*) as count FROM users WHERE role = 'staff' GROUP BY department");
        $stats['by_department'] = $result->fetchAll(PDO::FETCH_KEY_PAIR) ?? [];
        
    } catch (Exception $e) {
        error_log("Error getting staff stats: " . $e->getMessage());
    }
    
    return $stats;
}

/**
 * Get doctor summary statistics
 */
function getDoctorStats($pdo) {
    $stats = [];
    
    try {
        $result = $pdo->query("SELECT COUNT(*) FROM doctors");
        $stats['total_doctors'] = $result->fetchColumn() ?? 0;
        
        $result = $pdo->query("SELECT COUNT(*) FROM doctors WHERE status = 'available'");
        $stats['available_doctors'] = $result->fetchColumn() ?? 0;
        
        $result = $pdo->query("SELECT COUNT(*) FROM doctors WHERE status = 'leave'");
        $stats['doctors_on_leave'] = $result->fetchColumn() ?? 0;
        
        $result = $pdo->query("SELECT COUNT(*) FROM doctors WHERE status = 'booked'");
        $stats['fully_booked'] = $result->fetchColumn() ?? 0;
        
        $result = $pdo->query("SELECT department, COUNT(*) as count FROM doctors GROUP BY department");
        $stats['by_department'] = $result->fetchAll(PDO::FETCH_KEY_PAIR) ?? [];
        
        // Average patient load
        $result = $pdo->query("SELECT AVG(ROUND((booked_slots/total_slots)*100, 1)) FROM doctors WHERE total_slots > 0");
        $stats['avg_load_percent'] = round($result->fetchColumn() ?? 0, 1);
        
    } catch (Exception $e) {
        error_log("Error getting doctor stats: " . $e->getMessage());
    }
    
    return $stats;
}

/**
 * Get login activity (last N logins)
 */
function getRecentLogins($pdo, $limit = 10) {
    try {
        $stmt = $pdo->prepare(
            "SELECT username, role, login_time, status, ip_address 
             FROM login_history 
             WHERE status = 'success'
             ORDER BY login_time DESC 
             LIMIT ?"
        );
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Error getting recent logins: " . $e->getMessage());
        return [];
    }
}

/**
 * Get failed login attempts (last N)
 */
function getFailedLogins($pdo, $limit = 10) {
    try {
        $stmt = $pdo->prepare(
            "SELECT username, login_time, ip_address 
             FROM login_history 
             WHERE status = 'failed'
             ORDER BY login_time DESC 
             LIMIT ?"
        );
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Error getting failed logins: " . $e->getMessage());
        return [];
    }
}

/**
 * Get admin activity (last N actions)
 */
function getAdminActivity($pdo, $limit = 20) {
    try {
        $stmt = $pdo->prepare(
            "SELECT action, user, description, created_at 
             FROM system_logs 
             ORDER BY created_at DESC 
             LIMIT ?"
        );
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Error getting admin activity: " . $e->getMessage());
        return [];
    }
}

/**
 * Check if username exists
 */
function usernameExists($pdo, $username) {
    try {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        return $stmt->fetch() !== false;
    } catch (Exception $e) {
        error_log("Error checking username: " . $e->getMessage());
        return false;
    }
}

/**
 * Reset staff password
 */
function resetStaffPassword($pdo, $userId) {
    try {
        $tempPassword = bin2hex(random_bytes(8));
        $hashedPassword = password_hash($tempPassword, PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$hashedPassword, $userId]);
        
        return $tempPassword;
    } catch (Exception $e) {
        error_log("Error resetting password: " . $e->getMessage());
        return false;
    }
}

/**
 * Get doctor's patient load details
 */
function getDoctorLoad($pdo, $doctorId) {
    try {
        $stmt = $pdo->prepare(
            "SELECT 
                id, name, specialization, department,
                total_slots, booked_slots, 
                ROUND((booked_slots/total_slots)*100, 1) as load_percent,
                status
             FROM doctors 
             WHERE id = ?"
        );
        $stmt->execute([$doctorId]);
        return $stmt->fetch();
    } catch (Exception $e) {
        error_log("Error getting doctor load: " . $e->getMessage());
        return null;
    }
}

/**
 * Get all staff members with details
 */
function getAllStaff($pdo, $status = null) {
    try {
        $sql = "SELECT id, username, role, department, status, created_at FROM users WHERE role = 'staff'";
        if ($status) {
            $sql .= " AND status = '" . $pdo->quote($status) . "'";
        }
        $sql .= " ORDER BY department, username";
        
        return $pdo->query($sql)->fetchAll();
    } catch (Exception $e) {
        error_log("Error getting staff: " . $e->getMessage());
        return [];
    }
}

/**
 * Get all doctors with details
 */
function getAllDoctors($pdo, $department = null) {
    try {
        $sql = "SELECT * FROM doctors WHERE 1=1";
        if ($department) {
            $sql .= " AND department = '" . $pdo->quote($department) . "'";
        }
        $sql .= " ORDER BY department, name";
        
        return $pdo->query($sql)->fetchAll();
    } catch (Exception $e) {
        error_log("Error getting doctors: " . $e->getMessage());
        return [];
    }
}

/**
 * Check if admin account exists
 */
function adminExists($pdo) {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'");
        return $stmt->fetchColumn() > 0;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Get system health status
 */
function getSystemHealth($pdo) {
    $health = [
        'database' => false,
        'tables' => [],
        'records' => []
    ];
    
    try {
        // Database connection check
        $pdo->query("SELECT 1");
        $health['database'] = true;
        
        // Check critical tables
        foreach (['users', 'doctors', 'login_history'] as $table) {
            $result = $pdo->query("SHOW TABLES LIKE '$table'");
            $health['tables'][$table] = $result->fetch() !== false;
        }
        
        // Record counts
        $health['records']['users'] = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn() ?? 0;
        $health['records']['doctors'] = $pdo->query("SELECT COUNT(*) FROM doctors")->fetchColumn() ?? 0;
        $health['records']['logins'] = $pdo->query("SELECT COUNT(*) FROM login_history")->fetchColumn() ?? 0;
        
    } catch (Exception $e) {
        error_log("Error checking system health: " . $e->getMessage());
    }
    
    return $health;
}

/**
 * Generate admin activity report
 */
function generateActivityReport($pdo, $days = 7) {
    try {
        $date = date('Y-m-d H:i:s', strtotime("-$days days"));
        
        $stmt = $pdo->prepare(
            "SELECT 
                action, COUNT(*) as count, user as actor
             FROM system_logs 
             WHERE created_at >= ?
             GROUP BY action, user
             ORDER BY count DESC"
        );
        $stmt->execute([$date]);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Error generating report: " . $e->getMessage());
        return [];
    }
}
