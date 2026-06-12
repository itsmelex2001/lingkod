<?php
require_once __DIR__ . '/api/config/db.php';
$stmt = $pdo->query("SELECT * FROM patient_medical_history");
$pmh = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "patient_medical_history rows: " . count($pmh) . "\n";
?>
