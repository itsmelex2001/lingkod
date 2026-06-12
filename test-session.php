<?php
// Test session behavior after logout
session_start();
echo 'Session ID: ' . session_id() . PHP_EOL;
echo 'Session user: ' . (['user'] ?? 'NOT SET') . PHP_EOL;
echo 'Session role: ' . (['role'] ?? 'NOT SET') . PHP_EOL;
?>
