<?php
require_once __DIR__ . '/config/cors.php';
require_once __DIR__ . '/config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $filter = $_GET['filter'] ?? 'all';
    $where = "";
    if ($filter === 'week') {
        $where = "WHERE YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1)";
    } elseif ($filter === 'month') {
        $where = "WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())";
    } elseif ($filter === 'year') {
        $where = "WHERE YEAR(created_at) = YEAR(CURDATE())";
    }

    // 1. Appointments booked (total patients registered/booked)
    $stmt1 = $pdo->query("SELECT COUNT(*) FROM patients $where");
    $appointmentsBooked = $stmt1->fetchColumn() ?? 0;

    // 2. Slot schedules available (available slots across all active doctors)
    $stmt2 = $pdo->query("SELECT SUM(GREATEST(0, total_slots - booked_slots)) FROM doctors WHERE status = 'available'");
    $slotsAvailable = (int)($stmt2->fetchColumn() ?? 0);

    // 3. Registered patients (distinct names to represent unique patients)
    $stmt3 = $pdo->query("SELECT COUNT(DISTINCT full_name) FROM patients $where");
    $registeredPatients = $stmt3->fetchColumn() ?? 0;

    echo json_encode([
        'status' => 'success',
        'appointmentsBooked' => number_format($appointmentsBooked),
        'slotsAvailable' => number_format($slotsAvailable),
        'registeredPatients' => number_format($registeredPatients),
        'raw' => [
            'appointmentsBooked' => (int)$appointmentsBooked,
            'slotsAvailable' => (int)$slotsAvailable,
            'registeredPatients' => (int)$registeredPatients
        ]
    ]);
}
