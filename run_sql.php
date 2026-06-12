<?php
require_once __DIR__ . '/api/config/db.php';
$sql = file_get_contents(__DIR__ . '/patient_history_updates.sql');
try {
    $pdo->exec($sql);
    echo "SUCCESS";
} catch(Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
