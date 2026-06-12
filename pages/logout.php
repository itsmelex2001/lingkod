<?php
session_start();
$_SESSION = array();
session_destroy();
header("Location: /sdn/index.php");
exit;
