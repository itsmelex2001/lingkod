<?php
session_start();
$wasStaffOrAdmin = !empty($_SESSION['role']) && in_array(strtolower($_SESSION['role']), ['admin', 'staff', 'doctor', 'nurse']);
$_SESSION = array();
session_destroy();
if ($wasStaffOrAdmin) {
    header('Location: /sdn/pages/portal@admin$staff_2026.php?logout=1');
} else {
    header("Location: /sdn/pages/login.php");
}
exit;
