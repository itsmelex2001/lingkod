<?php
require_once __DIR__ . '/api/config/db.php';
$stmt = $pdo->prepare("SELECT medical_history_diagnosis, medical_history_treatment, medical_history_prescription FROM patients WHERE patient_id = 'PT-2026-012'");
$stmt->execute();
var_dump($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
