<?php
require 'api/config/db.php';
$stmt = $pdo->query('SELECT id, username, role FROM users');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
$stmt2 = $pdo->query('SELECT id, user FROM system_logs ORDER BY id DESC LIMIT 5');
print_r($stmt2->fetchAll(PDO::FETCH_ASSOC));
