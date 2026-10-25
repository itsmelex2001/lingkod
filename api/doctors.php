<?php
/**
 * api/doctors.php
 *
 * GET                         → all doctors for today (schedules page — public)
 * GET  ?department=psychiatry → filtered by department
 * GET  ?mode=assign           → all doctors for assign-doctor dropdown
 * GET  ?mode=manage           → all doctors for admin (with all fields)
 *
 * POST {action:'create', name, specialization, department, schedule_start, schedule_end, total_slots}
 * POST {action:'update', id, ...fields}
 * POST {action:'toggle_leave', id, status}
 * DELETE ?doctor_id=1
 */

session_start();
require_once __DIR__ . '/config/cors.php';
require_once __DIR__ . '/config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET' && $_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    respondErr('Method not allowed.', 405);
}

// Require auth for admin operations
$method = $_SERVER['REQUEST_METHOD'];
$isAdmin = false;
if ($method === 'POST' || $method === 'DELETE') {
    requireAuth();
    if (($_SESSION['role'] ?? 'staff') !== 'admin') {
        respondErr('Access denied. Admin only.', 403);
    }
    $isAdmin = true;
    $actor = sessionUser();
}

$dept = $_GET['department'] ?? null;
$mode = $_GET['mode']       ?? null;

// ── GET ──────────────────────────────────────────────────────────────────────
if ($method === 'GET') {
    
    // Admin: get all doctors with full details
    if ($mode === 'manage') {
        $stmt = $pdo->query(
            "SELECT id, name, specialization, department, schedule_start, schedule_end,
                    total_slots, booked_slots, status, schedule_date
             FROM doctors
             ORDER BY department, name ASC"
        );
        $data = $stmt->fetchAll();
        
        foreach ($data as &$doc) {
            $doc['fill_percent'] = $doc['total_slots'] > 0
                ? round(($doc['booked_slots'] / $doc['total_slots']) * 100)
                : 0;
        }
        unset($doc);
        
        respondOk(['data' => $data]);
    }

    // Assign-doctor dropdown (admin patient profile)
    if ($mode === 'assign') {
        $stmt = $pdo->query(
            "SELECT id, name, specialization, department, status
             FROM doctors
             ORDER BY department, name ASC"
        );
        respondOk(['data' => $stmt->fetchAll()]);
    }

    // Schedule view (public)
    if ($dept) {
        $stmt = $pdo->prepare(
            "SELECT id, name, specialization, department,
                    TIME_FORMAT(schedule_start,'%h:%i %p') AS schedule_start,
                    TIME_FORMAT(schedule_end,  '%h:%i %p') AS schedule_end,
                    total_slots, booked_slots, status, schedule_date
             FROM doctors
             WHERE (LOWER(department) = LOWER(?) OR LOWER(department) LIKE CONCAT('%', LOWER(?), '%'))
             ORDER BY schedule_start ASC"
        );
        $stmt->execute([$dept, $dept]);
    } else {
        $stmt = $pdo->query(
            "SELECT id, name, specialization, department,
                    TIME_FORMAT(schedule_start,'%h:%i %p') AS schedule_start,
                    TIME_FORMAT(schedule_end,  '%h:%i %p') AS schedule_end,
                    total_slots, booked_slots, status, schedule_date
             FROM doctors
             ORDER BY department, schedule_start ASC"
        );
    }

    $doctors = $stmt->fetchAll();

    // Compute slot fill percentage
    foreach ($doctors as &$doc) {
        $doc['booked_slots'] = (int) $doc['booked_slots'];
        $doc['total_slots']  = (int) $doc['total_slots'];
        $doc['fill_percent'] = $doc['total_slots'] > 0
            ? round(($doc['booked_slots'] / $doc['total_slots']) * 100)
            : 0;
    }
    unset($doc);

    respondOk(['data' => $doctors]);
}

// ── POST: admin only ─────────────────────────────────────────────────────────
if ($method === 'POST' && $isAdmin) {
    $b      = getBody();
    $action = $b['action'] ?? '';

    $allowedSpecializations = [
        'Internal Medicine',
        'Family Medicine & General Practice',
        'Family Medicine & Routine Check-ups',
        'Routine Check-ups & Preventative Care',
        'Preventative Care & Routine Check-ups',
        'Chronic Disease Management',
        'Pediatric Care',
        'General Surgery & Minor Procedures',
        'Clinical Neurology',
        'Headache & Migraine Management',
        'Stroke Rehabilitation',
        'Epilepsy & Seizure Disorders',
        'Movement Disorders',
        'Neuromuscular Medicine',
        'Adult Psychiatry',
        'Child & Adolescent Psychiatry',
        'Substance Abuse & Addiction Treatment',
        'Anxiety & Depression Management',
        'Geriatric Psychiatry',
        'Clinical Neuropsychiatry'
    ];

    // ── CREATE doctor ────────────────────────────────────────────────────────
    if ($action === 'create') {
        $name           = trim($b['name'] ?? '');
        $specialization = trim($b['specialization'] ?? '');
        $departmentRaw = trim($b['department'] ?? '');
        $deptMap = [
            'psychiatry'         => 'psychiatry',
            'opu psychiatry'     => 'psychiatry',
            'neurology'          => 'neurology',
            'general'            => 'general',
            'general medicine'   => 'general',
            'general management' => 'general'
        ];
        $department = $deptMap[strtolower($departmentRaw)] ?? strtolower($departmentRaw);
        $schedStart     = trim($b['schedule_start'] ?? '');
        $schedEnd       = trim($b['schedule_end'] ?? '');
        $totalSlots     = (int) ($b['total_slots'] ?? 20);

        if ($name === '' || $specialization === '' || $department === '') {
            respondErr('name, specialization, and department are required.');
        }

        if (!in_array($department, ['psychiatry', 'neurology', 'general'])) {
            respondErr('Invalid department. Must be psychiatry, neurology, or general.');
        }

        $matchedSpec = false;
        foreach ($allowedSpecializations as $as) {
            if (strcasecmp($as, $specialization) === 0) {
                $specialization = $as; // Canonical case
                $matchedSpec = true;
                break;
            }
        }
        if (!$matchedSpec) {
            respondErr("Invalid clinical specialization '$specialization'. Only approved hospital medical specializations are permitted (arbitrary or non-medical fields such as Veterinarian/VET are strictly prohibited).");
        }

        $stmt = $pdo->prepare(
            "INSERT INTO doctors (name, specialization, department, schedule_start, schedule_end, total_slots, status, schedule_date)
             VALUES (?, ?, ?, ?, ?, ?, 'available', CURDATE())"
        );
        $stmt->execute([$name, $specialization, $department, $schedStart ?: null, $schedEnd ?: null, $totalSlots]);

        addLog($pdo, 'info', 'Doctor Added', $actor,
               "New doctor '$name' ($specialization) added to $department department.");

        respondOk(['message' => "Doctor '$name' added successfully.", 'id' => $pdo->lastInsertId()]);
    }

    // ── UPDATE doctor ────────────────────────────────────────────────────────
    if ($action === 'update') {
        $id = $b['id'] ?? '';
        if ($id === '') respondErr('id is required.');

        $check = $pdo->prepare("SELECT name FROM doctors WHERE id = ?");
        $check->execute([$id]);
        if (!$check->fetch()) respondErr('Doctor not found.', 404);

        $updates = [];
        $params  = [];

        foreach (['name', 'specialization', 'department', 'schedule_start', 'schedule_end', 'total_slots', 'status'] as $field) {
            if (isset($b[$field])) {
                if ($field === 'total_slots') {
                    $val = (int) $b[$field];
                } elseif ($field === 'department') {
                    $deptMap = [
                        'psychiatry'         => 'psychiatry',
                        'opu psychiatry'     => 'psychiatry',
                        'neurology'          => 'neurology',
                        'general'            => 'general',
                        'general medicine'   => 'general',
                        'general management' => 'general'
                    ];
                    $val = $deptMap[strtolower(trim($b[$field]))] ?? strtolower(trim($b[$field]));
                    if (!in_array($val, ['psychiatry', 'neurology', 'general'])) {
                        respondErr('Invalid department. Must be psychiatry, neurology, or general.');
                    }
                } elseif ($field === 'specialization') {
                    $matchedSpec = false;
                    foreach ($allowedSpecializations as $as) {
                        if (strcasecmp($as, trim($b['specialization'])) === 0) {
                            $val = $as;
                            $matchedSpec = true;
                            break;
                        }
                    }
                    if (!$matchedSpec) {
                        respondErr("Invalid clinical specialization '{$b['specialization']}'. Only approved hospital medical specializations are permitted.");
                    }
                } elseif ($field === 'status') {
                    $val = strtolower(trim($b[$field]));
                    $validStatuses = ['available', 'booked', 'leave', 'in_clinic', 'on_break', 'emergency'];
                    if (!in_array($val, $validStatuses)) {
                        respondErr("Invalid status '$val'. Allowed statuses: " . implode(', ', $validStatuses));
                    }
                } else {
                    $val = trim($b[$field]);
                }
                $updates[] = "$field = ?";
                $params[] = $val;
            }
        }

        if (empty($updates)) respondErr('No fields to update.');

        $params[] = $id;
        $sql = "UPDATE doctors SET " . implode(', ', $updates) . " WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        addLog($pdo, 'info', 'Doctor Updated', $actor,
               "Doctor ID $id has been updated.");

        respondOk(['message' => 'Doctor updated successfully.']);
    }

    // ── TOGGLE LEAVE ─────────────────────────────────────────────────────────
    if ($action === 'toggle_leave') {
        $id     = $b['id'] ?? '';
        $status = $b['status'] ?? '';

        if ($id === '') respondErr('id is required.');
        if (!in_array($status, ['available', 'leave', 'booked'])) {
            respondErr('Invalid status.');
        }

        $check = $pdo->prepare("SELECT name, status FROM doctors WHERE id = ?");
        $check->execute([$id]);
        $doctor = $check->fetch();
        if (!$doctor) respondErr('Doctor not found.', 404);

        $stmt = $pdo->prepare("UPDATE doctors SET status = ? WHERE id = ?");
        $stmt->execute([$status, $id]);

        $action_txt = $status === 'leave' ? 'marked on leave' : 'marked as ' . $status;
        addLog($pdo, 'info', 'Doctor Leave Status', $actor,
               "Doctor '{$doctor['name']}' $action_txt.");

        respondOk(['message' => "Doctor's status updated to $status."]);
    }

    respondErr("Unknown action '$action'.");
}

// ── DELETE: remove doctor (admin only) ────────────────────────────────────────
if ($method === 'DELETE' && $isAdmin) {
    $doctorId = $_GET['doctor_id'] ?? '';
    if ($doctorId === '') respondErr('doctor_id is required.');

    $check = $pdo->prepare("SELECT name FROM doctors WHERE id = ?");
    $check->execute([$doctorId]);
    $doctor = $check->fetch();
    if (!$doctor) respondErr('Doctor not found.', 404);

    $del = $pdo->prepare("DELETE FROM doctors WHERE id = ?");
    $del->execute([$doctorId]);

    addLog($pdo, 'info', 'Doctor Deleted', $actor,
           "Doctor '{$doctor['name']}' has been removed from the system.");

    respondOk(['message' => 'Doctor deleted.']);
}
