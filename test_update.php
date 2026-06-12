<?php
require_once __DIR__ . '/api/config/db.php';
$stmt = $pdo->prepare("UPDATE patients SET medical_history_diagnosis = 'foo' WHERE patient_id = 'PT-2026-012'");
$stmt->execute();
echo "Affected rows: " . $stmt->rowCount() . "\n";
$stmt2 = $pdo->query("SELECT medical_history_diagnosis FROM patients WHERE patient_id = 'PT-2026-012'");
var_dump($stmt2->fetchAll(PDO::FETCH_ASSOC));
?>
