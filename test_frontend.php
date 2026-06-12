<?php
$data = array(
    'patient_id' => 'PT-2026-012',
    'diagnosis' => 'd3 frontend test',
    'treatment' => 'treat frontend test',
    'prescription' => ''
);

$options = array(
    'http' => array(
        'header'  => "Content-type: application/json\r\nCookie: PHPSESSID=your_session_id\r\n",
        'method'  => 'POST',
        'content' => json_encode($data)
    )
);

$context  = stream_context_create($options);
$result = file_get_contents('http://localhost/sdn/api/history.php', false, $context);

echo "Result from API: " . $result . "\n";

require_once __DIR__ . '/api/config/db.php';
$stmt = $pdo->query("SELECT medical_history_diagnosis FROM patients WHERE patient_id = 'PT-2026-012'");
$row = $stmt->fetch(PDO::FETCH_ASSOC);
echo "DB Value now: " . var_export($row['medical_history_diagnosis'], true) . "\n";
?>
