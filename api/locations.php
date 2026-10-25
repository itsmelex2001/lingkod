<?php
require_once 'config/cors.php';
require_once 'config/db.php';

header('Content-Type: application/json');

try {
    $stmt = $pdo->prepare("SELECT id, name, description, x_coordinate, y_coordinate FROM map_locations ORDER BY id ASC");
    $stmt->execute();
    $locations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $locations
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error. Make sure to run map_locations.sql'
    ]);
}
