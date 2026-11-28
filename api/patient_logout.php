<?php
/**
 * api/patient_logout.php
 * Clears patient session and redirects to patient login.
 */
session_start();

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/patient_remember.php';

clearPatientRememberCookies($pdo ?? null, false);

$hasStaffOrAdmin = !empty($_SESSION['user'])
    && in_array(strtolower(trim((string)($_SESSION['role'] ?? ''))), ['admin', 'superadmin', 'staff'], true);

unset($_SESSION['patient_logged_in']);
unset($_SESSION['patient_id']);
unset($_SESSION['patient_db_id']);
unset($_SESSION['patient_name']);
unset($_SESSION['patient_email']);
unset($_SESSION['patient_user_id']);
if (!$hasStaffOrAdmin) {
    unset($_SESSION['user_id']);
}

if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'redirect' => '/sdn/pages/patient-login.php']);
    exit;
}

header('Location: /sdn/pages/patient-login.php');
exit;

