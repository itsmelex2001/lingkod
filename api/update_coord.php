<?php
require_once 'config/db.php';

header('Content-Type: application/json');

if (isset($_GET['id']) && isset($_GET['lat']) && isset($_GET['lng'])) {
    $id = (int)$_GET['id'];
    $lat = (float)$_GET['lat'];
    $lng = (float)$_GET['lng'];
    
    $stmt = $pdo->prepare("UPDATE map_locations SET y_coordinate = ?, x_coordinate = ? WHERE id = ?");
    $success = $stmt->execute([$lat, $lng, $id]);
    
    echo json_encode(['success' => $success]);
} else {
    echo json_encode(['success' => false, 'error' => 'Missing parameters']);
}
