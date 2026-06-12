<?php
require 'api/config/db.php';
$stmt = $pdo->query("
    SELECT 
        l.id, 
        l.action, 
        COALESCE(
            IF(u.role = 'admin', 'admin', CONCAT('staff: ', u.username)),
            l.user
        ) AS user, 
        l.details
    FROM system_logs l
    LEFT JOIN users u ON l.user = u.id
    ORDER BY l.id DESC LIMIT 5
");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
