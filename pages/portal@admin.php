<?php
/**
 * Redirect fallback for portal@admin.php
 */
$qs = !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '';
header('Location: /sdn/pages/portal@admin$staff_2026.php' . $qs);
exit;

