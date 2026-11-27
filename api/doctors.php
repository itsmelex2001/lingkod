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
    if (!in_array(strtolower((string)($_SESSION['role'] ?? 'staff')), ['admin', 'superadmin'], true)) {
        respondErr('Access denied. Admin only.', 403);
    }
    $isAdmin = true;
    $actor = sessionUser();
}

$dept = $_GET['department'] ?? null;
$mode = $_GET['mode']       ?? null;

// Ensure profile_image, license_number, and title columns exist (local MySQL only)
if (!dbIsPgsql()) {
    try {
        $docCols = $pdo->query("SHOW COLUMNS FROM doctors")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('profile_image', $docCols, true)) {
            @$pdo->exec("ALTER TABLE doctors ADD COLUMN profile_image VARCHAR(255) NULL");
        }
        if (!in_array('license_number', $docCols, true)) {
            @$pdo->exec("ALTER TABLE doctors ADD COLUMN license_number VARCHAR(100) NULL");
        }
        if (!in_array('title', $docCols, true)) {
            @$pdo->exec("ALTER TABLE doctors ADD COLUMN title VARCHAR(80) NULL");
        }
    } catch (Throwable $ignore) {}
}

// ── GET ──────────────────────────────────────────────────────────────────────
if ($method === 'GET') {
    
    // Admin: get all doctors with full details + today's live booking load
    if ($mode === 'manage') {
        date_default_timezone_set('Asia/Manila');
        $today = date('Y-m-d');
        $stmt = $pdo->query(
            "SELECT id, name, title, license_number, specialization, department, schedule_start, schedule_end,
                    total_slots, status, schedule_date, profile_image
             FROM doctors
             ORDER BY department, name ASC"
        );
        $data = $stmt->fetchAll();

        $normalizeDocKey = static function (?string $raw): string {
            $clean = strtolower(trim((string)$raw));
            $clean = preg_replace('/^dr\.?\s+/i', '', $clean);
            $clean = preg_replace('/,\s*(md|fppa|fpna|dpbp|m\.d\.).*$/i', '', $clean);
            return trim(preg_replace('/\s+/', ' ', (string)$clean));
        };

        $bookedByDoc = [];
        try {
            $pCols = dbTableColumns($pdo, 'patients');
            $inqSql = in_array('inquiry_only', $pCols, true) ? ' AND COALESCE(inquiry_only, 0) = 0' : '';
            $bStmt = $pdo->prepare(
                "SELECT assigned_doctor, COUNT(*) AS cnt
                 FROM patients
                 WHERE CAST(appointment_date AS DATE) = ?
                   $inqSql
                   AND LOWER(COALESCE(status, '')) NOT IN ('cancelled', 'rejected', 'cancelled_by_patient', 'no-show', 'registered')
                   AND TRIM(COALESCE(assigned_doctor, '')) <> ''
                   AND LOWER(TRIM(assigned_doctor)) <> 'none'
                 GROUP BY assigned_doctor"
            );
            $bStmt->execute([$today]);
            foreach ($bStmt->fetchAll(PDO::FETCH_ASSOC) as $bRow) {
                $k = $normalizeDocKey($bRow['assigned_doctor'] ?? '');
                if ($k !== '') {
                    $bookedByDoc[$k] = ($bookedByDoc[$k] ?? 0) + (int)$bRow['cnt'];
                }
            }
        } catch (Throwable $ignoreLoad) {}

        $onDuty = 0;
        $offDuty = 0;
        $totalBookedToday = 0;
        $totalCapacityOnDuty = 0;

        foreach ($data as &$doc) {
            $slots = isset($doc['total_slots']) ? max(1, (int)$doc['total_slots']) : 20;
            $key = $normalizeDocKey($doc['name'] ?? '');
            $booked = $key !== '' ? (int)($bookedByDoc[$key] ?? 0) : 0;
            $stRaw = strtolower(trim((string)($doc['status'] ?? 'in')));
            $isIn = in_array($stRaw, ['in', 'available', 'active', 'on duty', 'in_clinic'], true);

            if ($isIn) {
                $onDuty++;
                $totalCapacityOnDuty += $slots;
            } else {
                $offDuty++;
            }
            $totalBookedToday += $booked;

            $doc['total_slots']  = $slots;
            $doc['booked_today'] = $booked;
            $doc['fill_percent'] = min(100, (int)round(($booked / $slots) * 100));
        }
        unset($doc);

        respondOk([
            'data'    => $data,
            'summary' => [
                'total_doctors'  => count($data),
                'on_duty'        => $onDuty,
                'off_duty'       => $offDuty,
                'booked_today'   => $totalBookedToday,
                'total_capacity' => $totalCapacityOnDuty,
            ],
        ]);
    }

    // Assign-doctor dropdown (admin patient profile)
    if ($mode === 'assign') {
        $stmt = $pdo->query(
            "SELECT id, name, title, license_number, specialization, department, status, profile_image
             FROM doctors
             ORDER BY department, name ASC"
        );
        respondOk(['data' => $stmt->fetchAll()]);
    }

    // Schedule view (public)
    $startFmt = dbIsPgsql() ? "TO_CHAR(schedule_start, 'HH12:MI AM')" : "TIME_FORMAT(schedule_start,'%h:%i %p')";
    $endFmt   = dbIsPgsql() ? "TO_CHAR(schedule_end, 'HH12:MI AM')"   : "TIME_FORMAT(schedule_end,  '%h:%i %p')";
    if ($dept) {
        $stmt = $pdo->prepare(
            "SELECT id, name, title, license_number, specialization, department,
                    {$startFmt} AS schedule_start,
                    {$endFmt} AS schedule_end,
                    status, schedule_date, profile_image
             FROM doctors
             WHERE (LOWER(department) = LOWER(?) OR LOWER(department) LIKE CONCAT('%', LOWER(?), '%'))
             ORDER BY schedule_start ASC"
        );
        $stmt->execute([$dept, $dept]);
    } else {
        $stmt = $pdo->query(
            "SELECT id, name, title, license_number, specialization, department,
                    {$startFmt} AS schedule_start,
                    {$endFmt} AS schedule_end,
                    status, schedule_date, profile_image
             FROM doctors
             ORDER BY department, schedule_start ASC"
        );
    }

    $doctors = $stmt->fetchAll();

    foreach ($doctors as &$doc) {
        $doc['fill_percent'] = 0;
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
        $title          = trim($b['title'] ?? '');
        $licenseNumber  = trim($b['license_number'] ?? '');
        $specialization = trim($b['specialization'] ?? '');
        $departmentRaw  = trim($b['department'] ?? '');
        $deptMap = [
            'psychiatry'         => 'psychiatry',
            'opu psychiatry'     => 'psychiatry',
            'neurology'          => 'neurology',
            'general'            => 'general',
            'general medical'    => 'general',
            'general medicine'   => 'general',
            'general management' => 'general'
        ];
        $department = $deptMap[strtolower($departmentRaw)] ?? strtolower($departmentRaw);
        $totalSlots = isset($b['total_slots']) && $b['total_slots'] !== '' ? (int) $b['total_slots'] : 20;
        $statusRaw  = strtolower(trim($b['status'] ?? 'in'));
        $status     = in_array($statusRaw, ['in', 'out'], true) ? $statusRaw : 'in';

        if ($name === '' || $specialization === '' || $department === '') {
            respondErr('Please fill in all required fields.');
        }

        if ($totalSlots < 1 || $totalSlots > 100) {
            respondErr('Total daily slots must be between 1 and 100.');
        }

        if (!in_array($department, ['psychiatry', 'neurology', 'general'])) {
            respondErr('Invalid department. Must be psychiatry, neurology, or general.');
        }

        $stmt = $pdo->prepare(
            "INSERT INTO doctors (name, title, license_number, specialization, department, total_slots, status, schedule_date)
             VALUES (?, ?, ?, ?, ?, ?, ?, CURRENT_DATE)"
        );
        $stmt->execute([$name, $title !== '' ? $title : null, $licenseNumber !== '' ? $licenseNumber : null, $specialization, $department, $totalSlots, $status]);
        $newDoctorId = $pdo->lastInsertId();

        $profileImageUrl = null;
        if (!empty($b['image_data']) && is_string($b['image_data'])) {
            $profileImageUrl = saveUploadedAvatar($b['image_data'], 'doctor', $newDoctorId);
            if ($profileImageUrl !== null) {
                $pdo->prepare("UPDATE doctors SET profile_image = ? WHERE id = ?")->execute([$profileImageUrl, $newDoctorId]);
            }
        }

        addLog($pdo, 'info', 'Doctor Added', $actor,
               "New doctor '$name' ($specialization) added to $department department.");

        respondOk(['message' => "Doctor '$name' added successfully.", 'id' => $newDoctorId, 'profile_image' => $profileImageUrl]);
    }

    // ── UPDATE doctor ────────────────────────────────────────────────────────
    if ($action === 'update') {
        $id = $b['id'] ?? '';
        if ($id === '') respondErr('id is required.');

        $check = $pdo->prepare("SELECT name, profile_image FROM doctors WHERE id = ?");
        $check->execute([$id]);
        $existingDoc = $check->fetch();
        if (!$existingDoc) respondErr('Doctor not found.', 404);

        $updates = [];
        $params  = [];

        foreach (['name', 'title', 'license_number', 'specialization', 'department', 'total_slots', 'status'] as $field) {
            if (isset($b[$field])) {
                if ($field === 'department') {
                    $deptMap = [
                        'psychiatry'         => 'psychiatry',
                        'opu psychiatry'     => 'psychiatry',
                        'neurology'          => 'neurology',
                        'general'            => 'general',
                        'general medical'    => 'general',
                        'general medicine'   => 'general',
                        'general management' => 'general'
                    ];
                    $val = $deptMap[strtolower(trim($b[$field]))] ?? strtolower(trim($b[$field]));
                    if (!in_array($val, ['psychiatry', 'neurology', 'general'])) {
                        respondErr('Invalid department. Must be psychiatry, neurology, or general.');
                    }
                } elseif ($field === 'specialization') {
                    $val = trim($b['specialization']);
                    if ($val === '') {
                        respondErr('Specialization cannot be empty.');
                    }
                } elseif ($field === 'total_slots') {
                    $val = (int) $b['total_slots'];
                    if ($val < 1 || $val > 100) {
                        respondErr('Total daily slots must be between 1 and 100.');
                    }
                } elseif ($field === 'status') {
                    $val = strtolower(trim($b[$field]));
                    $validStatuses = ['in', 'out', 'available', 'booked', 'leave', 'in_clinic', 'on_break', 'emergency', 'active'];
                    if (!in_array($val, $validStatuses)) {
                        respondErr("Invalid status '$val'.");
                    }
                } else {
                    $val = trim($b[$field]);
                }
                $updates[] = "$field = ?";
                $params[] = $val;
            }
        }

        $removeImage = !empty($b['remove_image']);
        $newImageUrl = null;
        if ($removeImage) {
            deleteLocalAvatar($existingDoc['profile_image'] ?? null);
            $updates[] = "profile_image = NULL";
        } elseif (!empty($b['image_data']) && is_string($b['image_data'])) {
            $newImageUrl = saveUploadedAvatar($b['image_data'], 'doctor', $id);
            if ($newImageUrl !== null) {
                deleteLocalAvatar($existingDoc['profile_image'] ?? null);
                $updates[] = "profile_image = ?";
                $params[] = $newImageUrl;
            }
        }

        if (empty($updates)) respondErr('No fields to update.');

        $params[] = $id;
        $sql = "UPDATE doctors SET " . implode(', ', $updates) . " WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        addLog($pdo, 'info', 'Doctor Updated', $actor,
               "Doctor ID $id has been updated.");

        respondOk(['message' => 'Doctor updated successfully.', 'profile_image' => $removeImage ? null : ($newImageUrl ?? ($existingDoc['profile_image'] ?? null))]);
    }

    // ── TOGGLE LEAVE ─────────────────────────────────────────────────────────
    if ($action === 'toggle_leave') {
        $id     = $b['id'] ?? '';
        $status = strtolower(trim($b['status'] ?? ''));

        if ($id === '') respondErr('id is required.');
        if (!in_array($status, ['in', 'out', 'available', 'leave', 'booked'])) {
            respondErr('Invalid status.');
        }

        $check = $pdo->prepare("SELECT name, status FROM doctors WHERE id = ?");
        $check->execute([$id]);
        $doctor = $check->fetch();
        if (!$doctor) respondErr('Doctor not found.', 404);

        $stmt = $pdo->prepare("UPDATE doctors SET status = ? WHERE id = ?");
        $stmt->execute([$status, $id]);

        $action_txt = ($status === 'out' || $status === 'leave') ? 'marked OUT' : 'marked IN';
        addLog($pdo, 'info', 'Doctor Status Updated', $actor,
               "Doctor '{$doctor['name']}' $action_txt.");

        respondOk(['message' => "Doctor's status updated to " . strtoupper($status) . "."]);
    }

    respondErr("Unknown action '$action'.");
}

// ── DELETE: remove doctor (admin only) ────────────────────────────────────────
if ($method === 'DELETE' && $isAdmin) {
    $doctorId = $_GET['doctor_id'] ?? '';
    if ($doctorId === '') respondErr('doctor_id is required.');

    $check = $pdo->prepare("SELECT name, profile_image FROM doctors WHERE id = ?");
    $check->execute([$doctorId]);
    $doctor = $check->fetch();
    if (!$doctor) respondErr('Doctor not found.', 404);

    deleteLocalAvatar($doctor['profile_image'] ?? null);
    $del = $pdo->prepare("DELETE FROM doctors WHERE id = ?");
    $del->execute([$doctorId]);

    addLog($pdo, 'info', 'Doctor Deleted', $actor,
           "Doctor '{$doctor['name']}' has been removed from the system.");

    respondOk(['message' => 'Doctor deleted.']);
}
