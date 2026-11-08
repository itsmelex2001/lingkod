<?php
/**
 * api/patient_logout.php
 * Clears patient session and redirects to patient login.
 */
session_start();

unset($_SESSION['patient_logged_in']);
unset($_SESSION['patient_id']);
unset($_SESSION['patient_db_id']);
unset($_SESSION['patient_name']);
unset($_SESSION['patient_email']);

if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'redirect' => '/sdn/pages/patient-login.php']);
    exit;
}

header('Location: /sdn/pages/patient-login.php');
exit;

