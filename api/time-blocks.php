<?php
/**
 * api/time-blocks.php
 * Endpoint: GET /sdn/api/time-blocks.php?target_date=YYYY-MM-DD[&department=...]
 *
 * Serves Phase 3 appointment booking time blocks with:
 * - Patient session authentication guard
 * - Sunday closure empty array check
 * - Department-specific and clinic schedule templates matching Clinic Schedule Calendar
 * - Anti-double booking conflict check
 * - Maximum capacity limit check (20 slots per block)
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

// 1. Authentication & Initialization
$patientId    = trim((string)($_SESSION['patient_id'] ?? ''));
$patientEmail = trim((string)($_SESSION['patient_email'] ?? ''));

if ($patientId === '' && $patientEmail === '') {
    http_response_code(401);
    echo json_encode([
        'error'   => 'Unauthorized. Please log in as a patient.',
        'message' => 'Unauthorized. Please log in as a patient.'
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'error'   => 'Method not allowed.',
        'message' => 'Only GET requests are allowed.'
    ]);
    exit;
}

$rawDate = trim($_GET['target_date'] ?? $_GET['date'] ?? '');
// Normalize MM/DD/YYYY to YYYY-MM-DD if entered via localized date format
if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $rawDate, $m)) {
    $rawDate = sprintf('%04d-%02d-%02d', $m[3], $m[1], $m[2]);
}

if ($rawDate === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawDate)) {
    http_response_code(400);
    echo json_encode([
        'error'   => 'Invalid target_date parameter.',
        'message' => 'Valid target_date parameter required (format: YYYY-MM-DD).'
    ]);
    exit;
}

$parsedDate = DateTime::createFromFormat('Y-m-d', $rawDate);
if (!$parsedDate || $parsedDate->format('Y-m-d') !== $rawDate) {
    http_response_code(400);
    echo json_encode([
        'error'   => 'Invalid date format.',
        'message' => 'target_date must be a valid calendar date in YYYY-MM-DD format.'
    ]);
    exit;
}

$targetDate = $rawDate;
$dow = (int)$parsedDate->format('w'); // 0 = Sun, 1 = Mon ... 6 = Sat

// 2. Advance Booking Date Bounds (At least 1 day in advance, up to 1 year ahead)
$minDate = date('Y-m-d', strtotime('+1 day'));
$maxDate = date('Y-m-d', strtotime('+1 year'));

if ($targetDate < $minDate || $targetDate > $maxDate) {
    echo json_encode([]);
    exit;
}

// 3. Sunday Rule (Empty State)
if ($dow === 0) {
    echo json_encode([]);
    exit;
}

// 3. Fetch Base Time Blocks (Matching Clinic Schedule Calendar)
$rawDept = trim($_GET['department'] ?? $_GET['dept'] ?? '');
$cleanDept = strtolower($rawDept);

// Department templates from Clinic Schedule Calendar
$deptTemplates = [
    'general' => [
        [
            'start'   => '08:00:00',
            'end'     => '10:00:00',
            'session' => 'Morning Session',
            'service' => 'General Medical Consultation',
            'days'    => [1, 2, 3, 4, 5, 6],
        ],
        [
            'start'   => '10:00:00',
            'end'     => '12:00:00',
            'session' => 'Morning Session',
            'service' => 'Physical Examination & Preventive Healthcare',
            'days'    => [1, 2, 3, 4, 5],
        ],
        [
            'start'   => '13:00:00',
            'end'     => '16:00:00',
            'session' => 'Afternoon Session',
            'service' => 'Chronic Disease Management & Lab Requests',
            'days'    => [1, 3, 5],
        ],
    ],
    'neuro' => [
        [
            'start'   => '08:30:00',
            'end'     => '11:30:00',
            'session' => 'Morning Session',
            'service' => 'Neurology Consultation & Headache Management',
            'days'    => [1, 2, 4, 5],
        ],
        [
            'start'   => '13:30:00',
            'end'     => '16:30:00',
            'session' => 'Afternoon Session',
            'service' => 'Stroke, Epilepsy & Neurological Diagnostic Testing',
            'days'    => [2, 3, 4, 6],
        ],
    ],
    'psych' => [
        [
            'start'   => '08:00:00',
            'end'     => '11:30:00',
            'session' => 'Morning Session',
            'service' => 'Psychotherapy & Psychological Assessment',
            'days'    => [1, 2, 3, 4, 5],
        ],
        [
            'start'   => '13:00:00',
            'end'     => '17:00:00',
            'session' => 'Afternoon Session',
            'service' => 'Child & Adolescent Psychology / Rehabilitation',
            'days'    => [1, 2, 3, 4, 5, 6],
        ],
    ],
];

// Determine matching templates based on department
$chosenDeptKey = null;
if (str_contains($cleanDept, 'general') || str_contains($cleanDept, 'medical')) {
    $chosenDeptKey = 'general';
} elseif (str_contains($cleanDept, 'neuro')) {
    $chosenDeptKey = 'neuro';
} elseif (str_contains($cleanDept, 'psych') || str_contains($cleanDept, 'opu')) {
    $chosenDeptKey = 'psych';
}

$baseBlocks = [];
$blockId = 1;

if ($chosenDeptKey && isset($deptTemplates[$chosenDeptKey])) {
    foreach ($deptTemplates[$chosenDeptKey] as $tpl) {
        if (in_array($dow, $tpl['days'], true)) {
            $start12 = date('h:i A', strtotime($tpl['start']));
            $end12   = date('h:i A', strtotime($tpl['end']));
            $baseBlocks[] = [
                'id'               => $blockId++,
                'time_block_value' => "{$tpl['start']}-{$tpl['end']}",
                'start_time'       => $tpl['start'],
                'end_time'         => $tpl['end'],
                'label'            => "{$start12} - {$end12} ({$tpl['session']})",
                'service'          => $tpl['service'],
            ];
        }
    }
}

// Fallback to standard hospital consultation shifts if no department specified or department has no specific shift
if (empty($baseBlocks)) {
    $standardHospitalShifts = [
        ['start' => '08:00:00', 'end' => '10:00:00', 'label' => '08:00 AM - 10:00 AM (Morning Session)'],
        ['start' => '10:00:00', 'end' => '12:00:00', 'label' => '10:00 AM - 12:00 PM (Morning Session)'],
        ['start' => '13:00:00', 'end' => '15:00:00', 'label' => '01:00 PM - 03:00 PM (Afternoon Session)'],
        ['start' => '15:00:00', 'end' => '17:00:00', 'label' => '03:00 PM - 05:00 PM (Afternoon Session)'],
    ];

    foreach ($standardHospitalShifts as $shift) {
        $baseBlocks[] = [
            'id'               => $blockId++,
            'time_block_value' => "{$shift['start']}-{$shift['end']}",
            'start_time'       => $shift['start'],
            'end_time'         => $shift['end'],
            'label'            => $shift['label'],
            'service'          => 'General Consultation',
        ];
    }
}

// Helper to determine if an appointment scheduled_time belongs to a given time block
function appointmentBelongsToBlock(string $scheduledTime, array $block): bool {
    $scheduledTime = trim($scheduledTime);
    if ($scheduledTime === '') {
        return false;
    }

    if (strcasecmp($scheduledTime, $block['time_block_value']) === 0) {
        return true;
    }

    $firstPart = trim(explode('-', $scheduledTime)[0]);
    $ts = strtotime($firstPart);
    if ($ts === false) {
        return false;
    }

    $timeHms = date('H:i:s', $ts);
    return ($timeHms >= $block['start_time'] && $timeHms < $block['end_time']);
}

const MAX_BLOCK_CAPACITY = 20;

try {
    require_once __DIR__ . '/config/cors.php';
    require_once __DIR__ . '/config/db.php';

    // Booked slots live on patients.scheduled_time. The appointments table
    // stores doctor clinic windows (start_time/end_time) and has no patient slot column.
    $table = 'patients';

    // 4. Anti-Double Booking Logic (Session-Based Conflict Check)
    $wherePatient = [];
    $paramsPatient = [':target_date' => $targetDate];

    if ($patientId !== '') {
        $wherePatient[] = "patient_id = :pid";
        $paramsPatient[':pid'] = $patientId;
    }
    if ($patientEmail !== '') {
        $wherePatient[] = "email = :pemail";
        $paramsPatient[':pemail'] = $patientEmail;
    }

    $patientBookings = [];
    if (!empty($wherePatient)) {
        $patientCondition = '(' . implode(' OR ', $wherePatient) . ')';
        $sqlPatient = "
            SELECT scheduled_time, status
            FROM {$table}
            WHERE {$patientCondition}
              AND appointment_date = :target_date
              AND LOWER(status) NOT IN ('cancelled', 'rejected', 'cancelled_by_patient', 'no-show')
        ";
        $stmtPatient = $pdo->prepare($sqlPatient);
        $stmtPatient->execute($paramsPatient);
        $patientBookings = $stmtPatient->fetchAll(PDO::FETCH_ASSOC);
    }

    // 5. Capacity Limit Check: fetch all active appointments on target_date
    $stmtCapacity = $pdo->prepare("
        SELECT scheduled_time
        FROM {$table}
        WHERE appointment_date = :target_date
          AND LOWER(status) NOT IN ('cancelled', 'rejected', 'cancelled_by_patient', 'no-show')
    ");
    $stmtCapacity->execute([
        ':target_date' => $targetDate,
    ]);
    $allDayBookings = $stmtCapacity->fetchAll(PDO::FETCH_ASSOC);

    // 6. Assemble JSON Response Structure
    $response = [];

    foreach ($baseBlocks as $block) {
        $label = $block['label'];
        $isAvailable = true;
        $alreadyBooked = false;

        // Check if current patient already booked this block
        foreach ($patientBookings as $pb) {
            if (appointmentBelongsToBlock($pb['scheduled_time'] ?? '', $block)) {
                $alreadyBooked = true;
                $isAvailable   = false;
                $label        .= ' (Already Booked)';
                break;
            }
        }

        // Check capacity limit for remaining open blocks
        if ($isAvailable) {
            $appointmentCount = 0;
            foreach ($allDayBookings as $b) {
                if (appointmentBelongsToBlock($b['scheduled_time'] ?? '', $block)) {
                    $appointmentCount++;
                }
            }

            if ($appointmentCount >= MAX_BLOCK_CAPACITY) {
                $isAvailable = false;
                $label      .= ' (Fully Booked)';
            }
        }

        $response[] = [
            'id'               => (int)$block['id'],
            'time_block_value' => $block['time_block_value'],
            'label'            => $label,
            'is_available'     => $isAvailable,
            'value'            => $block['time_block_value'],
            'already_booked'   => $alreadyBooked,
        ];
    }

    echo json_encode($response);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'error'   => 'Database error.',
        'message' => 'A database error occurred while retrieving time blocks.',
        'detail'  => $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error'   => 'Server error.',
        'message' => 'An unexpected server error occurred.',
        'detail'  => $e->getMessage()
    ]);
}
