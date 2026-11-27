<?php
declare(strict_types=1);

/**
 * api/approve_appointment.php
 * Action-driven endpoint called when staff approves an appointment.
 * Updates database status to 'approved' and dispatches logistical Email and SMS.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/config/cors.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/services/EmailService.php';
require_once __DIR__ . '/services/SmsService.php';

requireAuth();

$role = strtolower(trim((string)($_SESSION['role'] ?? '')));
if (!in_array($role, ['admin', 'superadmin', 'staff'], true)) {
    respondErr('Forbidden. Staff or administrator privileges required.', 403);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respondErr('Method not allowed.', 405);
}

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
if (!is_array($input)) {
    $input = [];
}

$csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($input['csrf_token'] ?? null);
if (!$csrfToken || !verifyCsrfToken((string)$csrfToken)) {
    respondErr('Security check failed: Missing or invalid CSRF token.', 403);
}

$patientId = trim((string)($input['patient_id'] ?? ''));

if ($patientId === '') {
    respondErr('patient_id is required.', 400);
}

$normalizeDeptBucket = static function (string $dept): string {
    $d = strtolower(trim($dept));
    if (str_contains($d, 'psych') || str_contains($d, 'opu')) return 'psychiatry';
    if (str_contains($d, 'neuro')) return 'neurology';
    if (str_contains($d, 'general') || str_contains($d, 'medical') || str_contains($d, 'cardio')) return 'general';
    return $d;
};

try {
    $staffDeptBucket = '';
    if ($role === 'staff') {
        $staffDept = trim((string)($_SESSION['department'] ?? ''));
        $username = sessionUser();
        if ($username !== '' && $username !== 'System') {
            $uStmt = $pdo->prepare("SELECT department FROM users WHERE username = ? LIMIT 1");
            $uStmt->execute([$username]);
            $dbDept = trim((string)($uStmt->fetchColumn() ?: ''));
            if ($dbDept !== '') $staffDept = $dbDept;
        }
        $staffDeptBucket = $normalizeDeptBucket($staffDept);
    }

    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        SELECT id, patient_id, full_name, email, contact_number,
               appointment_date, scheduled_time, status, department, consultation_mode
        FROM patients
        WHERE patient_id = ?
        LIMIT 1
        FOR UPDATE
    ");
    $stmt->execute([$patientId]);
    $appt = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$appt) {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => "Appointment {$patientId} not found."]);
        exit;
    }

    if ($role === 'staff') {
        $apptBucket = $normalizeDeptBucket((string)($appt['department'] ?? ''));
        if ($staffDeptBucket === '' || ($apptBucket !== '' && $apptBucket !== $staffDeptBucket)) {
            $pdo->rollBack();
            respondErr('Forbidden. This patient belongs to another department.', 403);
        }
    }

    if ($appt['status'] === 'approved') {
        $pdo->rollBack();
        echo json_encode(['success' => true, 'message' => 'Appointment is already approved.']);
        exit;
    }

    $newDept = ($role === 'admin' || $role === 'superadmin') ? trim((string)($input['department'] ?? '')) : '';
    if ($newDept !== '' && strtolower($newDept) !== 'triage pending') {
        $d = strtolower($newDept);
        if (str_contains($d, 'general') || str_contains($d, 'medical')) {
            $cleanDept = 'General Medical';
        } elseif (str_contains($d, 'psych') || str_contains($d, 'opu')) {
            $cleanDept = 'OPU Psychiatry';
        } elseif (str_contains($d, 'neuro')) {
            $cleanDept = 'Neurology';
        } else {
            $cleanDept = ucwords($newDept);
        }

        $upd = $pdo->prepare("
            UPDATE patients
            SET status = 'approved',
                department = ?,
                specialty = ?,
                updated_at = NOW()
            WHERE id = ?
        ");
        $upd->execute([$cleanDept, $cleanDept . ' Consultation', $appt['id']]);

        try {
            $pdo->prepare("UPDATE appointments SET department = ? WHERE patient_id = ?")->execute([$cleanDept, $appt['patient_id']]);
        } catch (\Throwable $e) {}
    } else {
        $upd = $pdo->prepare("
            UPDATE patients
            SET status = 'approved',
                updated_at = NOW()
            WHERE id = ?
        ");
        $upd->execute([$appt['id']]);
    }

    $pdo->commit();

    // Format logistical fields
    $dateFormatted = !empty($appt['appointment_date'])
        ? date('M j, Y', strtotime($appt['appointment_date']))
        : 'Scheduled Clinic Day';
    $timeFormatted = !empty($appt['scheduled_time'])
        ? date('g:i A', strtotime($appt['scheduled_time']))
        : '8:00 AM';
    $refId = $appt['patient_id'];

    $emailSent = false;
    $smsSent   = false;

    $consultMode = trim((string)($appt['consultation_mode'] ?? 'Face-to-Face')) ?: 'Face-to-Face';

    // Dispatch Email
    if (!empty($appt['email'])) {
        try {
            $emailSent = EmailService::sendAppointmentEmail(
                $appt['email'],
                $appt['full_name'],
                $dateFormatted,
                $timeFormatted,
                $refId,
                'confirmed',
                $consultMode
            );
        } catch (\Throwable $e) {
            error_log("[approve_appointment] Email error: " . $e->getMessage());
        }
    }

    // Dispatch SMS
    if (!empty($appt['contact_number'])) {
        try {
            $smsSent = SmsService::sendConfirmationSms(
                $appt['contact_number'],
                $dateFormatted,
                $timeFormatted,
                $refId,
                $consultMode
            );
        } catch (\Throwable $e) {
            error_log("[approve_appointment] SMS error: " . $e->getMessage());
        }
    }

    echo json_encode([
        'success'    => true,
        'message'    => "Appointment {$refId} approved successfully.",
        'patient_id' => $refId,
        'email_sent' => $emailSent,
        'sms_sent'   => $smsSent,
    ]);

} catch (\Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    error_log("[approve_appointment] Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Internal server error while approving appointment.']);
}

