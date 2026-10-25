<?php
/**
 * api/patients.php
 *
 * GET                                     → list all patients (admin only)
 * GET ?id=PT-2026-XXX                     → single patient full detail
 * POST {action:'approve', patient_id}     → approve a patient
 * POST {action:'reject',  patient_id}     → reject a patient
 * POST {action:'assign',  patient_id, doctor} → assign a doctor
 * POST {action:'no-show', patient_id}     → mark patient as no-show
 * POST {action:'completed', patient_id}   → mark patient as completed
 * POST {action:'reschedule', patient_id, date} → reschedule appointment
 * POST {action:'referral', patient_id, department} → refer to another department
 */

session_start();
require_once __DIR__ . '/config/cors.php';
require_once __DIR__ . '/config/db.php';

requireAuth();

$method = $_SERVER['REQUEST_METHOD'];

// ── GET ─────────────────────────────────────────────────────────────────────
if ($method === 'GET') {

    // Single patient by ?id=
    if (!empty($_GET['id'])) {
        $id   = trim($_GET['id']);
        $stmt = $pdo->prepare(
            "SELECT patient_id, department, full_name, gender, birthdate, age,
                    contact_number, email, address, with_companion,
                    chief_complaints, consultation_nature, illness_description,
                    patient_type, appointment_type, consultation_mode,
                    appointment_date, referral_source, status,
                    assigned_doctor, assigned_date, created_at, updated_at,
                    receiver, informant, recommendation_remarks, unscheduled_walkin, inquiry_only,
                    mode_of_inquiry, employee_type, select_type, hrn, specialty, scheduled_time,
                    medical_history_diagnosis, medical_history_treatment, medical_history_prescription,
                    active_medications, next_visit_date, reminder_status, reminder_sent_at
             FROM patients
             WHERE patient_id = ?
             ORDER BY id DESC LIMIT 1"
        );
        $stmt->execute([$id]);
        $patient = $stmt->fetch();

        if (!$patient) respondErr("Patient $id not found.", 404);

        $noteStmt = $pdo->prepare("SELECT note, noted_by, created_at FROM progress_notes WHERE patient_id = ? ORDER BY created_at DESC LIMIT 1");
        $noteStmt->execute([$id]);
        $patient['latest_progress_note'] = $noteStmt->fetch() ?: null;

        respondOk(['data' => $patient]);
    }

    // All patients (list view)
    $role = $_SESSION['role'] ?? 'staff';
    $username = $_SESSION['user'] ?? '';
    $dept = $_SESSION['department'] ?? '';
    $filter = $_GET['filter'] ?? 'all';

    // Dynamically fetch department if missing or to ensure it's up to date
    if ($role === 'staff' && $username) {
        $stmtDept = $pdo->prepare("SELECT department FROM users WHERE username = ?");
        $stmtDept->execute([$username]);
        $fetchedDept = $stmtDept->fetchColumn();
        if ($fetchedDept) $dept = $fetchedDept;
    }
    
    if ($role === 'admin') {
        $filterSql = "";
        if ($filter === 'week') {
            $filterSql = "AND YEARWEEK(p.created_at, 1) = YEARWEEK(CURDATE(), 1)";
        } elseif ($filter === 'month') {
            $filterSql = "AND MONTH(p.created_at) = MONTH(CURDATE()) AND YEAR(p.created_at) = YEAR(CURDATE())";
        } elseif ($filter === 'year') {
            $filterSql = "AND YEAR(p.created_at) = YEAR(CURDATE())";
        }

        $stmt = $pdo->prepare(
            "SELECT p.patient_id, p.department, p.full_name, p.gender, p.birthdate, p.age, p.contact_number, p.email,
                    p.address, p.chief_complaints, p.consultation_nature, p.illness_description,
                    p.patient_type, p.appointment_type, p.consultation_mode, p.appointment_date,
                    p.status, p.assigned_doctor, p.unscheduled_walkin, p.inquiry_only, p.mode_of_inquiry,
                    p.recommendation_remarks, p.receiver, p.hrn, p.scheduled_time,
                    p.reminder_status, p.reminder_sent_at, p.created_at,
                    d.id AS doctor_id, d.status AS doctor_status
             FROM patients p
             LEFT JOIN doctors d ON (p.assigned_doctor = d.name OR p.assigned_doctor = CONCAT('Dr. ', d.name) OR REPLACE(p.assigned_doctor, 'Dr. ', '') = REPLACE(d.name, 'Dr. ', ''))
             WHERE 1=1 $filterSql
             ORDER BY p.created_at DESC"
        );
        $stmt->execute();
        respondOk(['data' => $stmt->fetchAll()]);
    }

    $whereClauses = [];
    $params = [];
    
    if (!empty($dept)) {
        if (stripos($dept, 'general') !== false) {
            $whereClauses[] = "(LOWER(p.department) LIKE '%general%')";
        } elseif (stripos($dept, 'psychiatry') !== false || stripos($dept, 'opu') !== false) {
            $whereClauses[] = "(LOWER(p.department) LIKE '%psychiatry%' OR LOWER(p.department) LIKE '%opu%')";
        } elseif (stripos($dept, 'neuro') !== false) {
            $whereClauses[] = "(LOWER(p.department) LIKE '%neuro%')";
        } else {
            $whereClauses[] = "LOWER(p.department) = LOWER(?)";
            $params[] = $dept;
        }
    }
    
    if ($filter === 'week') {
        $whereClauses[] = "YEARWEEK(p.created_at, 1) = YEARWEEK(CURDATE(), 1)";
    } elseif ($filter === 'month') {
        $whereClauses[] = "MONTH(p.created_at) = MONTH(CURDATE()) AND YEAR(p.created_at) = YEAR(CURDATE())";
    } elseif ($filter === 'year') {
        $whereClauses[] = "YEAR(p.created_at) = YEAR(CURDATE())";
    }
    
    $whereSql = count($whereClauses) > 0 ? "WHERE " . implode(" AND ", $whereClauses) : "";
    
    $stmt = $pdo->prepare(
        "SELECT p.patient_id, p.department, p.full_name, p.gender, p.birthdate, p.age, p.contact_number, p.email,
                p.address, p.chief_complaints, p.consultation_nature, p.illness_description,
                p.patient_type, p.appointment_type, p.consultation_mode, p.appointment_date,
                p.status, p.assigned_doctor, p.unscheduled_walkin, p.inquiry_only, p.mode_of_inquiry,
                p.recommendation_remarks, p.receiver, p.hrn, p.scheduled_time, p.referral_source,
                p.reminder_status, p.reminder_sent_at, p.created_at,
                d.id AS doctor_id, d.status AS doctor_status
         FROM patients p
         LEFT JOIN doctors d ON (p.assigned_doctor = d.name OR p.assigned_doctor = CONCAT('Dr. ', d.name) OR REPLACE(p.assigned_doctor, 'Dr. ', '') = REPLACE(d.name, 'Dr. ', ''))
         $whereSql
         ORDER BY p.created_at DESC"
    );
    $stmt->execute($params);
    respondOk(['data' => $stmt->fetchAll()]);
}

// ── POST ────────────────────────────────────────────────────────────────────
if ($method === 'POST') {
    $body      = getBody();
    $action    = $body['action']     ?? '';
    $patientId = $body['patient_id'] ?? '';
    $actor     = sessionUser();

    // CSRF verification if security token header is supplied
    $csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($body['csrf_token'] ?? null);
    if ($csrfToken && !verifyCsrfToken($csrfToken)) {
        respondErr('Security check failed: Invalid or expired CSRF token. Please refresh the page.', 403);
    }

    if ($patientId === '') respondErr('patient_id is required.');

    // Verify patient exists
    $check = $pdo->prepare("SELECT full_name, department, status, inquiry_only, consultation_nature FROM patients WHERE patient_id = ?");
    $check->execute([$patientId]);
    $patient = $check->fetch();
    if (!$patient) respondErr("Patient $patientId not found.", 404);

    $role = $_SESSION['role'] ?? 'staff';
    $username = $_SESSION['user'] ?? '';
    $staffDept = $_SESSION['department'] ?? '';

    if ($role === 'staff' && $username) {
        $stmtDept = $pdo->prepare("SELECT department FROM users WHERE username = ?");
        $stmtDept->execute([$username]);
        $fetchedDept = $stmtDept->fetchColumn();
        if ($fetchedDept) $staffDept = $fetchedDept;
    }

function isDeptMatch($d1, $d2): bool {
    if (!$d1 || !$d2) return false;
    $a = strtolower(trim($d1));
    $b = strtolower(trim($d2));
    if ($a === $b) return true;
    if ((str_contains($a, 'general') || str_contains($a, 'general management')) && (str_contains($b, 'general') || str_contains($b, 'general management'))) return true;
    if ((str_contains($a, 'psychiatry') || str_contains($a, 'opu')) && (str_contains($b, 'psychiatry') || str_contains($b, 'opu'))) return true;
    if (str_contains($a, 'neuro') && str_contains($b, 'neuro')) return true;
    return false;
}

if ($role !== 'admin' && !empty($staffDept) && !isDeptMatch($patient['department'], $staffDept)) {
    respondErr("Unauthorized: This patient is registered to another department.", 403);
}

    $name = $patient['full_name'];
    $dept = $patient['department'];

    // ── 1. Approve / Accept ──────────────────────────────────────────────
    if ($action === 'approve' || $action === 'accept') {
        if (!in_array($patient['status'], ['pending', 'for_approval', 'for_processing'])) {
            respondErr("Patient is already {$patient['status']}.");
        }
        $upd = $pdo->prepare(
            "UPDATE patients SET status = 'approved', updated_at = NOW() WHERE patient_id = ?"
        );
        $upd->execute([$patientId]);
        addLog($pdo, 'info', 'Patient Approved', $actor,
               "Patient $patientId ($name) was approved and scheduled for $dept.");
        respondOk(['message' => "$name has been approved and scheduled."]);
    }

    // ── 2. Arrived / Check-in (Step 1 of Facility Arrival) ───────────────
    if ($action === 'arrived' || $action === 'check_in') {
        if (!in_array($patient['status'], ['approved', 'for_approval', 'for_processing', 'pending'])) {
            respondErr("Cannot check in patient in '{$patient['status']}' status.");
        }
        $upd = $pdo->prepare(
            "UPDATE patients SET status = 'arrived', updated_at = NOW() WHERE patient_id = ?"
        );
        $upd->execute([$patientId]);
        addLog($pdo, 'info', 'Patient Arrived', $actor,
               "Patient $patientId ($name) arrived at facility (Checked-In at Reception/Triage).");
        respondOk(['message' => "$name has arrived at the facility and is checked in for triage."]);
    }

    // ── 3. Ongoing / In Consultation (Step 3 of Clinical Workflow) ──────
    if ($action === 'ongoing' || $action === 'start_consultation') {
        if (!in_array($patient['status'], ['arrived', 'approved'])) {
            respondErr("Patient must be checked in (Arrived) before starting consultation.");
        }
        $upd = $pdo->prepare(
            "UPDATE patients SET status = 'ongoing', updated_at = NOW() WHERE patient_id = ?"
        );
        $upd->execute([$patientId]);
        $doc = $patient['assigned_doctor'] ?: 'Attending Physician';
        addLog($pdo, 'info', 'Consultation Ongoing', $actor,
               "Patient $patientId ($name) consultation in progress with $doc.");
        respondOk(['message' => "Consultation for $name is now Ongoing."]);
    }

    // ── 4. Reject ─────────────────────────────────────────────────────────
    if ($action === 'reject') {
        if (in_array($patient['status'], ['completed', 'rejected', 'transferred'])) {
            respondErr("Cannot reject patient in '{$patient['status']}' status.");
        }
        $upd = $pdo->prepare(
            "UPDATE patients SET status = 'rejected', updated_at = NOW() WHERE patient_id = ?"
        );
        $upd->execute([$patientId]);

        // Release slot if a doctor was assigned
        if (!empty($patient['assigned_doctor'])) {
            $pdo->prepare("UPDATE doctors SET booked_slots = GREATEST(0, booked_slots - 1) WHERE name = ?")->execute([$patient['assigned_doctor']]);
        }

        addLog($pdo, 'info', 'Patient Rejected', $actor,
               "Patient $patientId ($name) registration was rejected.");
        respondOk(['message' => "{$name}'s registration was rejected."]);
    }

    // ── 5. Assign Doctor ──────────────────────────────────────────────────
    if ($action === 'assign') {
        $doctor = trim($body['doctor'] ?? '');
        if ($doctor === '') respondErr('doctor name is required.');

        $docCheck = $pdo->prepare(
            "SELECT name, status FROM doctors WHERE name = ? LIMIT 1"
        );
        $docCheck->execute([$doctor]);
        $doc = $docCheck->fetch();

        if (!$doc) respondErr("Doctor '$doctor' not found.");
        if ($doc['status'] === 'leave') respondErr("$doctor is currently on leave.");

        $upd = $pdo->prepare(
            "UPDATE patients
             SET assigned_doctor = ?, assigned_date = CURDATE(), updated_at = NOW()
             WHERE patient_id = ?"
        );
        $upd->execute([$doctor, $patientId]);

        $pdo->prepare("UPDATE doctors SET booked_slots = booked_slots + 1 WHERE name = ? AND booked_slots < total_slots")->execute([$doctor]);

        addLog($pdo, 'info', 'Doctor Assigned', $actor,
               "Dr. $doctor assigned to patient $patientId ($name).");

        respondOk(['message' => "$doctor has been assigned to $name."]);
    }

    // ── 6. No-Show ────────────────────────────────────────────────────────
    if ($action === 'no-show') {
        $upd = $pdo->prepare(
            "UPDATE patients SET status = 'no-show', updated_at = NOW() WHERE patient_id = ?"
        );
        $upd->execute([$patientId]);

        if (!empty($patient['assigned_doctor'])) {
            $pdo->prepare("UPDATE doctors SET booked_slots = GREATEST(0, booked_slots - 1) WHERE name = ?")->execute([$patient['assigned_doctor']]);
        }

        addLog($pdo, 'info', 'Patient No-Show', $actor,
               "Patient $patientId ($name) marked as no-show.");
        respondOk(['message' => "$name marked as no-show."]);
    }

    // ── 7. Completed (Final Step: Officially signed off by Doctor OR Inquiry Resolved) ────────
    if ($action === 'completed') {
        $isInquiry = !empty($patient['inquiry_only']) || ($patient['consultation_nature'] ?? '') === 'General Inquiry';
        if (!$isInquiry && !in_array($patient['status'], ['ongoing', 'arrived', 'approved'])) {
            respondErr("Cannot complete patient visit in current status '{$patient['status']}'. Patient must be actively ongoing in consultation.");
        }

        $diag = trim($body['diagnosis'] ?? '');
        $rx   = trim($body['prescription'] ?? '');
        $plan = trim($body['treatment_plan'] ?? '');
        $note = trim($body['note'] ?? '');

        if ($diag !== '' || $rx !== '' || $plan !== '') {
            $upd = $pdo->prepare(
                "UPDATE patients 
                 SET status = 'completed', 
                     reminder_status = CASE 
                         WHEN reminder_status = 'Sent' THEN 'Sent' 
                         ELSE 'Completed' 
                     END,
                     medical_history_diagnosis = COALESCE(?, medical_history_diagnosis),
                     medical_history_treatment = COALESCE(?, medical_history_treatment),
                     medical_history_prescription = COALESCE(?, medical_history_prescription),
                     medical_history_recorded_by = ?,
                     medical_history_date = NOW(),
                     updated_at = NOW() 
                 WHERE patient_id = ?"
            );
            $upd->execute([$diag ?: null, $plan ?: null, $rx ?: null, $actor, $patientId]);
        } else {
            $upd = $pdo->prepare(
                "UPDATE patients 
                 SET status = 'completed', 
                     reminder_status = CASE 
                         WHEN reminder_status = 'Sent' THEN 'Sent' 
                         ELSE 'Completed' 
                     END,
                     updated_at = NOW() 
                 WHERE patient_id = ?"
            );
            $upd->execute([$patientId]);
        }

        if ($isInquiry) {
            $resolveNote = $note ?: ($diag ?: 'Inquiry addressed and resolved.');
            $noteStmt = $pdo->prepare("INSERT INTO progress_notes (patient_id, note, noted_by) VALUES (?, ?, ?)");
            $noteStmt->execute([$patientId, "Inquiry Resolved: " . $resolveNote, $actor]);
        }

        addLog($pdo, 'info', $isInquiry ? 'Inquiry Resolved' : 'Patient Completed', $actor,
               $isInquiry 
                   ? "Inquiry $patientId ($name) was resolved and marked completed."
                   : "Patient $patientId ($name) medical consultation was fully executed, officially signed off, and completed.");
        respondOk(['message' => $isInquiry ? "Inquiry for $name has been resolved and marked completed." : "Medical service for $name has been completed and signed off."]);
    }

    // ── Reschedule ───────────────────────────────────────────────────────
    if ($action === 'reschedule') {
        $newDate = trim($body['date'] ?? '');
        if ($newDate === '') respondErr('date is required.');
        $upd = $pdo->prepare(
            "UPDATE patients SET appointment_date = ?, updated_at = NOW() WHERE patient_id = ?"
        );
        $upd->execute([$newDate, $patientId]);
        addLog($pdo, 'info', 'Patient Rescheduled', $actor,
               "Patient $patientId ($name) rescheduled to $newDate.");
        respondOk(['message' => "$name rescheduled to $newDate."]);
    }

    // ── Referral / Transfer ──────────────────────────────────────────────
    if ($action === 'referral') {
        $newDept = trim($body['department'] ?? '');
        if ($newDept === '') respondErr('department is required.');

        $oldDept = $patient['department'];
        $cleanOld = strtolower(trim(str_replace(['opu ', 'opu'], '', $oldDept)));
        $cleanNew = strtolower(trim(str_replace(['opu ', 'opu'], '', $newDept)));
        if ($cleanOld === $cleanNew || strcasecmp(trim($oldDept), trim($newDept)) === 0) {
            respondErr("Logic Loop Prevented: Cannot transfer patient to their current department ('{$oldDept}'). Please select a different receiving department.", 422);
        }

        $patientName = $patient['full_name'];

        // 1. Mark existing record in origin department as 'transferred' (do NOT hard-delete!)
        $upd = $pdo->prepare(
            "UPDATE patients SET status = 'transferred', updated_at = NOW() WHERE patient_id = ?"
        );
        $upd->execute([$patientId]);

        // 2. Generate new sequential patient ID for target department
        $year = date('Y');
        $seqStmt = $pdo->query("SELECT MAX(CAST(SUBSTRING(patient_id, 9) AS UNSIGNED)) as max_seq FROM patients WHERE patient_id LIKE 'PT-$year-%'");
        $maxSeq = (int)($seqStmt->fetchColumn() ?: 0);
        $newSeq = str_pad($maxSeq + 1, 3, '0', STR_PAD_LEFT);
        $newPatientId = "PT-$year-$newSeq";

        $ins = $pdo->prepare(
            "INSERT INTO patients (
                patient_id, department, full_name, gender, birthdate, age, contact_number, email, address,
                with_companion, chief_complaints, consultation_nature, illness_description,
                patient_type, appointment_type, consultation_mode, appointment_date,
                referral_source, status, assigned_doctor, unscheduled_walkin, hrn, specialty, scheduled_time,
                active_medications, next_visit_date, medical_history_diagnosis, medical_history_treatment, medical_history_prescription
            ) SELECT
                ?, ?, full_name, gender, birthdate, age, contact_number, email, address,
                with_companion, chief_complaints, consultation_nature, illness_description,
                patient_type, appointment_type, consultation_mode, CURDATE(),
                ?, 'pending', NULL, unscheduled_walkin, hrn, specialty, scheduled_time,
                active_medications, next_visit_date, medical_history_diagnosis, medical_history_treatment, medical_history_prescription
            FROM patients WHERE patient_id = ?"
        );
        $ins->execute([$newPatientId, $newDept, $oldDept, $patientId]);

        addLog($pdo, 'info', 'Patient Transferred', $actor,
               "Patient $patientName ($patientId) transferred from $oldDept to $newDept (New ID: $newPatientId). Origin record marked as Transferred.");

        respondOk(['message' => "$patientName transferred to $newDept successfully.", 'new_patient_id' => $newPatientId]);
    }

    // ── Send Manual SMS Appointment Reminder ─────────────────────────────
    if ($action === 'send_sms' || $action === 'send_reminder') {
        require_once __DIR__ . '/services/SmsService.php';
        $smsService = new SmsService($pdo);
        $result = $smsService->sendAppointmentReminder($patientId, $actor);

        if ($result['success']) {
            $dest = $result['recipient'] ?? '';
            respondOk([
                'message' => "SMS appointment reminder sent successfully to {$name}" . ($dest ? " ($dest)" : "") . "!",
                'reminder_status' => 'Sent',
                'reminder_sent_at' => $result['sent_at'] ?? date('Y-m-d H:i:s'),
                'details' => $result
            ]);
        } else {
            $err = $result['error'] ?? 'Failed to send SMS reminder.';
            respondErr($err, 400);
        }
    }

    // ── Hard deletion disabled per clinical data retention requirement ──
    if ($action === 'delete') {
        respondErr("Hard deletion of patient records is disabled to preserve patient clinical history.", 400);
    }

    respondErr("Unknown action '$action'.");
}

respondErr('Method not allowed.', 405);
