<?php
session_start();

$portal = strtolower(trim((string)($_GET['portal'] ?? '')));
$referer = (string)($_SERVER['HTTP_REFERER'] ?? '');
$sessionRole = strtolower(trim((string)($_SESSION['role'] ?? '')));

$isPatientPortal = ($portal === 'patient')
    || (
        $portal === ''
        && !in_array($sessionRole, ['admin', 'staff', 'doctor', 'nurse'], true)
        && !empty($_SESSION['patient_logged_in'])
        && str_contains($referer, 'patient-')
    );

$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
}
session_destroy();

header('Cache-Control: no-store, no-cache, must-revalidate, private');
header('Pragma: no-cache');

if ($isPatientPortal) {
    header('Location: /sdn/pages/patient-login.php');
} else {
    header('Location: /sdn/pages/portal@admin$staff_2026.php?logout=1');
}
exit;
