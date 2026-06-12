<?php
$data = array(
    'action' => 'save',
    'patient_id' => 'PT-2026-012',
    'diagnosis' => 'test diag',
    'treatment' => 'test treat',
    'prescription' => 'test presc'
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
if ($result === FALSE) { /* Handle error */ }

var_dump($result);
?>
