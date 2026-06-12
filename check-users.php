<?php
require_once 'api/config/db.php';

// Try to create users if they don't exist
$users = [
    ['admin', 'admin123', 'admin'],
    ['staff', 'staff123', 'staff']
];

$created = [];
$existing = [];

foreach ($users as [$username, $password, $role]) {
    $check = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $check->execute([$username]);
    
    if ($check->fetch()) {
        $existing[] = $username;
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $insert = $pdo->prepare("INSERT INTO users (username, password, role, status) VALUES (?, ?, ?, 'active')");
        $insert->execute([$username, $hash, $role]);
        $created[] = $username;
    }
}

try {
    $stmt = $pdo->query("SELECT id, username, password, role, status FROM users");
    $allUsers = $stmt->fetchAll();
    echo json_encode([
        'success' => true,
        'created' => $created,
        'existing' => $existing,
        'all_users' => $allUsers,
        'message' => 'Database check complete'
    ], JSON_PRETTY_PRINT);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>

