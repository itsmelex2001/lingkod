<?php
require 'api/config/db.php';
$res = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
foreach($res as $t){
    echo "\nTable: $t\n";
    $desc = $pdo->query("DESCRIBE $t")->fetchAll(PDO::FETCH_ASSOC);
    foreach($desc as $c){
        echo "- {$c['Field']} ({$c['Type']})\n";
    }
}
