<?php
require_once __DIR__ . '/api/config/db.php';
$stmt = $pdo->query("SELECT id, patient_id, full_name, medical_history_diagnosis, medical_history_treatment FROM patients WHERE full_name LIKE '%Janri%'");
var_dump($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
