<?php
/**
 * api/super-admin/auth-guard.php
 * Strict Super Admin authentication and authorization guard.
 * Blocks all requests without an active, verified super_admin session.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function verifySuperAdminSession(bool $jsonOutput = true): bool {
    $isSuperAdmin = !empty($_SESSION['user']) && 
                    !empty($_SESSION['role']) && 
                    strtolower(trim($_SESSION['role'])) === 'super_admin';

    if (!$isSuperAdmin) {
        if ($jsonOutput) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'message' => 'Unauthorized: Super Admin access required.'
            ]);
            exit;
        } else {
            header('Location: /sdn/pages/super-admin/login.php');
            exit;
        }
    }

    return true;
}

