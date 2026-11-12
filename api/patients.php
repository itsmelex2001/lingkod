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

    // Helper: attach previous appointments & visit count for a single patient or list
    $attachPreviousHistory = function(PDO $pdo, array &$rows, bool $includeFullHistory = false): void {
        if (empty($rows)) return;

        // Fetch lightweight index of all appointments ordered chronologically
        try {
            $allStmt = $pdo->query("SELECT * FROM patients ORDER BY id ASC");
            $allRecords = $allStmt ? $allStmt->fetchAll(PDO::FETCH_ASSOC) : [];
        } catch (Throwable $e) {
            $allRecords = [];
        }

        // Group records by canonical patient identity
        $byIdentity = [];
        foreach ($allRecords as $rec) {
            $keys = [];
            if (!empty($rec['user_id'])) $keys[] = 'u:' . $rec['user_id'];
            if (!empty($rec['hrn']))     $keys[] = 'h:' . strtolower(trim($rec['hrn']));
            if (!empty($rec['email']))   $keys[] = 'e:' . strtolower(trim($rec['email']));
            if (!empty($rec['full_name'])) $keys[] = 'n:' . strtolower(preg_replace('/\s+/', ' ', trim($rec['full_name'])));

            foreach ($keys as $k) {
                $byIdentity[$k][$rec['id']] = $rec;
            }
        }

        foreach ($rows as &$row) {
            $rowId = (int)($row['id'] ?? 0);
            $rowPid = $row['patient_id'] ?? '';
            if (!$rowId && $rowPid) {
                foreach ($allRecords as $ar) {
                    if ($ar['patient_id'] === $rowPid) {
                        $rowId = (int)$ar['id'];
                        break;
                    }
                }
            }

            $lookupKeys = [];
            if (!empty($row['user_id']))   $lookupKeys[] = 'u:' . $row['user_id'];
            if (!empty($row['hrn']))       $lookupKeys[] = 'h:' . strtolower(trim($row['hrn']));
            if (!empty($row['email']))     $lookupKeys[] = 'e:' . strtolower(trim($row['email']));
            if (!empty($row['full_name'])) $lookupKeys[] = 'n:' . strtolower(preg_replace('/\s+/', ' ', trim($row['full_name'])));

            $matchedMap = [];
            foreach ($lookupKeys as $lk) {
                if (!empty($byIdentity[$lk])) {
                    foreach ($byIdentity[$lk] as $cid => $crec) {
                        $matchedMap[$cid] = $crec;
                    }
                }
            }

            // Sort matched records by id ASC (chronological booking order)
            ksort($matchedMap);
            $priorList = [];
            foreach ($matchedMap as $cid => $crec) {
                if ($cid < $rowId && $crec['patient_id'] !== $rowPid) {
                    $timeFormatted = !empty($crec['scheduled_time'])
                        ? date('g:i A', strtotime($crec['scheduled_time']))
                        : '8:00 AM';
                    $dateFormatted = !empty($crec['appointment_date'])
                        ? date('M j, Y', strtotime($crec['appointment_date']))
                        : date('M j, Y', strtotime($crec['created_at']));
                    $priorList[] = [
                        'id'               => (int)$crec['id'],
                        'patient_id'       => $crec['patient_id'],
                        'department'       => $crec['department'],
                        'appointment_date' => $crec['appointment_date'],
                        'date_formatted'   => $dateFormatted,
                        'scheduled_time'   => $crec['scheduled_time'],
                        'time_formatted'   => $timeFormatted,
                        'status'           => $crec['status'],
                        'assigned_doctor'  => $crec['assigned_doctor'],
                        'diagnosis'        => $crec['medical_history_diagnosis'] ?? null,
                        'treatment'        => $crec['medical_history_treatment'] ?? null,
                        'prescription'     => $crec['medical_history_prescription'] ?? null,
                        'medications'      => $crec['active_medications'] ?? null
                    ];
                }
            }

            // Reverse so most recent previous appointment is first
            $priorList = array_reverse($priorList);
            $latestPrev = $priorList[0] ?? null;

            $row['visit_count']               = count($matchedMap) ?: 1;
            $row['previous_appointment_date'] = $latestPrev ? $latestPrev['date_formatted'] : null;
            $row['previous_appointment_raw']  = $latestPrev ? $latestPrev['appointment_date'] : null;
            $row['previous_appointment_time'] = $latestPrev ? $latestPrev['time_formatted'] : null;
            $row['previous_department']       = $latestPrev ? $latestPrev['department'] : null;
            $row['previous_patient_id']       = $latestPrev ? $latestPrev['patient_id'] : null;
            $row['previous_status']           = $latestPrev ? $latestPrev['status'] : null;
            if ($includeFullHistory) {
                $row['previous_appointments'] = $priorList;
            }
        }
        unset($row);
    };

    // Single patient by ?id=
    if (!empty($_GET['id'])) {
        $id = trim($_GET['id']);
        try {
            $stmt = $pdo->prepare(
                "SELECT * FROM patients
                 WHERE patient_id = ? OR CAST(id AS CHAR) = ?
                 ORDER BY id DESC LIMIT 1"
            );
            $stmt->execute([$id, $id]);
            $patient = $stmt->fetch();
        } catch (Throwable $e) {
            error_log("Error fetching patient $id: " . $e->getMessage());
            $patient = null;
        }

        if (!$patient) respondErr("Patient $id not found.", 404);

        try {
            $notePid = $patient['patient_id'] ?? $id;
            $noteStmt = $pdo->prepare("SELECT note, noted_by, created_at FROM progress_notes WHERE patient_id = ? ORDER BY created_at DESC LIMIT 1");
            $noteStmt->execute([$notePid]);
            $patient['latest_progress_note'] = $noteStmt->fetch() ?: null;
        } catch (Throwable $e) {
            $patient['latest_progress_note'] = null;
        }

        try {
            $singleWrap = [$patient];
            $attachPreviousHistory($pdo, $singleWrap, true);
            $patient = $singleWrap[0];
        } catch (Throwable $e) {
            error_log("Error attaching patient history: " . $e->getMessage());
        }

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
            "SELECT p.id, p.user_id, p.patient_id, p.department, p.full_name, p.gender, p.birthdate, p.age, p.contact_number, p.email,
                    p.address, p.chief_complaints, p.consultation_nature, p.illness_description,
                    p.patient_type, p.appointment_type, p.consultation_mode, p.appointment_date,
                    p.status, p.assigned_doctor, p.unscheduled_walkin, p.inquiry_only, p.mode_of_inquiry,
                    p.recommendation_remarks, p.receiver, p.hrn, p.scheduled_time,
                    p.medical_history_diagnosis, p.medical_history_treatment, p.medical_history_prescription, p.active_medications,
                    p.reminder_status, p.reminder_sent_at, p.created_at,
                    d.id AS doctor_id, d.status AS doctor_status
             FROM patients p
             LEFT JOIN doctors d ON (p.assigned_doctor = d.name OR p.assigned_doctor = CONCAT('Dr. ', d.name) OR REPLACE(p.assigned_doctor, 'Dr. ', '') = REPLACE(d.name, 'Dr. ', ''))
             WHERE 1=1 $filterSql
             ORDER BY p.created_at DESC"
        );
        $stmt->execute();
        $rows = $stmt->fetchAll();
        $attachPreviousHistory($pdo, $rows, false);
        respondOk(['data' => $rows]);
    }

    $whereClauses = [];
    $params = [];
    
    if (!empty($dept)) {
        $triagePendingCondition = " OR LOWER(p.department) LIKE '%triage%' OR p.department IS NULL OR p.department = ''";
        if (stripos($dept, 'general') !== false) {
            $whereClauses[] = "(LOWER(p.department) LIKE '%general%'$triagePendingCondition)";
        } elseif (stripos($dept, 'psychiatry') !== false || stripos($dept, 'opu') !== false) {
            $whereClauses[] = "(LOWER(p.department) LIKE '%psychiatry%' OR LOWER(p.department) LIKE '%opu%'$triagePendingCondition)";
        } elseif (stripos($dept, 'neuro') !== false) {
            $whereClauses[] = "(LOWER(p.department) LIKE '%neuro%'$triagePendingCondition)";
        } else {
            $whereClauses[] = "(LOWER(p.department) = LOWER(?)$triagePendingCondition)";
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
        "SELECT p.id, p.user_id, p.patient_id, p.department, p.full_name, p.gender, p.birthdate, p.age, p.contact_number, p.email,
                p.address, p.chief_complaints, p.consultation_nature, p.illness_description,
                p.patient_type, p.appointment_type, p.consultation_mode, p.appointment_date,
                p.status, p.assigned_doctor, p.unscheduled_walkin, p.inquiry_only, p.mode_of_inquiry,
                p.recommendation_remarks, p.receiver, p.hrn, p.scheduled_time, p.referral_source,
                p.medical_history_diagnosis, p.medical_history_treatment, p.medical_history_prescription, p.active_medications,
                p.reminder_status, p.reminder_sent_at, p.created_at,
                d.id AS doctor_id, d.status AS doctor_status
         FROM patients p
         LEFT JOIN doctors d ON (p.assigned_doctor = d.name OR p.assigned_doctor = CONCAT('Dr. ', d.name) OR REPLACE(p.assigned_doctor, 'Dr. ', '') = REPLACE(d.name, 'Dr. ', ''))
         $whereSql
         ORDER BY p.created_at DESC"
    );
    $stmt->execute($params);
    $rows = $stmt->fetchAll();
    $attachPreviousHistory($pdo, $rows, false);
    respondOk(['data' => $rows]);
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
    $check = $pdo->prepare("SELECT * FROM patients WHERE patient_id = ?");
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
    if (!$d1 || !$d2) return true;
    $a = strtolower(trim($d1));
    $b = strtolower(trim($d2));
    if ($a === 'triage pending' || $b === 'triage pending' || str_contains($a, 'triage') || str_contains($b, 'triage') || $a === '' || $b === '') return true;
    if ($a === $b) return true;
    if ((str_contains($a, 'general') || str_contains($a, 'general management') || str_contains($a, 'general medical')) && (str_contains($b, 'general') || str_contains($b, 'general management') || str_contains($b, 'general medical'))) return true;
    if ((str_contains($a, 'psychiatry') || str_contains($a, 'opu')) && (str_contains($b, 'psychiatry') || str_contains($b, 'opu'))) return true;
    if (str_contains($a, 'neuro') && str_contains($b, 'neuro')) return true;
    return false;
}

// Allow staff full access to triage, check-in, start consult, and cancel regardless of initial registration department
if ($action !== 'assign_department' && $action !== 'update_department' && $action !== 'ongoing' && $action !== 'start_consultation' && $action !== 'arrived' && $action !== 'check_in' && $action !== 'cancel' && $action !== 'reject') {
    if ($role !== 'admin' && !empty($staffDept) && !isDeptMatch($patient['department'], $staffDept)) {
        respondErr("Unauthorized: This patient is registered to another department.", 403);
    }
}

    $name = $patient['full_name'];
    $dept = $patient['department'];

    // ── 0. Assign or Correct Department (Triage-led Routing) ─────────────
    if ($action === 'assign_department' || $action === 'update_department') {
        $pStatus = strtolower(trim($patient['status'] ?? ''));
        if ($pStatus === 'completed') {
            respondErr('Cannot modify department for completed appointments. The consultation record is finalized.', 422);
        }
        if (in_array($pStatus, ['cancelled', 'rejected'])) {
            respondErr('Cannot modify department for cancelled appointments.', 422);
        }

        $rawNew = trim($body['department'] ?? '');
        if ($rawNew === '') {
            respondErr('Department is required.');
        }

        $d = strtolower($rawNew);
        if (str_contains($d, 'general') || str_contains($d, 'medical')) {
            $cleanDept = 'General Medical';
        } elseif (str_contains($d, 'psych') || str_contains($d, 'opu')) {
            $cleanDept = 'OPU Psychiatry';
        } elseif (str_contains($d, 'neuro')) {
            $cleanDept = 'Neurology';
        } else {
            $cleanDept = ucwords($rawNew);
        }

        $oldDept = $patient['department'] ?: 'Triage Pending';
        $upd = $pdo->prepare("
            UPDATE patients
            SET department = ?, specialty = ?, updated_at = NOW()
            WHERE patient_id = ?
        ");
        $upd->execute([$cleanDept, $cleanDept . ' Consultation', $patientId]);

        addLog($pdo, 'info', 'Department Assigned/Corrected', $actor,
               "Staff assigned/corrected department from '{$oldDept}' to '{$cleanDept}' for patient $patientId ($name) based on chief complaint.");

        respondOk([
            'message'    => "Department for $name assigned to $cleanDept based on chief complaint.",
            'department' => $cleanDept
        ]);
    }

    // ── 1. Approve / Accept ──────────────────────────────────────────────
    if ($action === 'approve' || $action === 'accept') {
        if (!in_array($patient['status'], ['pending', 'for_approval', 'for_processing'])) {
            respondErr("Patient is already {$patient['status']}.");
        }

        $assignedDept = trim($body['department'] ?? '');
        if ($assignedDept !== '' && strtolower($assignedDept) !== 'triage pending') {
            $d = strtolower($assignedDept);
            if (str_contains($d, 'general') || str_contains($d, 'medical')) {
                $cleanDept = 'General Medical';
            } elseif (str_contains($d, 'psych') || str_contains($d, 'opu')) {
                $cleanDept = 'OPU Psychiatry';
            } elseif (str_contains($d, 'neuro')) {
                $cleanDept = 'Neurology';
            } else {
                $cleanDept = ucwords($assignedDept);
            }
            $dept = $cleanDept;

            $upd = $pdo->prepare("
                UPDATE patients 
                SET status = 'approved', department = ?, specialty = ?, updated_at = NOW() 
                WHERE patient_id = ?
            ");
            $upd->execute([$cleanDept, $cleanDept . ' Consultation', $patientId]);
        } else {
            $upd = $pdo->prepare(
                "UPDATE patients SET status = 'approved', updated_at = NOW() WHERE patient_id = ?"
            );
            $upd->execute([$patientId]);
        }
        addLog($pdo, 'info', 'Patient Approved', $actor,
               "Patient $patientId ($name) was approved and scheduled for $dept.");

        // Dispatch Confirmation Email & SMS (Compliant with Healthcare Privacy Laws)
        $dateFormatted = !empty($patient['appointment_date'])
            ? date('M j, Y', strtotime($patient['appointment_date']))
            : 'Scheduled Clinic Day';
        $timeFormatted = !empty($patient['scheduled_time'])
            ? date('g:i A', strtotime($patient['scheduled_time']))
            : '8:00 AM';

        if (!empty($patient['email'])) {
            try {
                require_once __DIR__ . '/services/EmailService.php';
                EmailService::sendAppointmentEmail(
                    $patient['email'],
                    $name,
                    $dateFormatted,
                    $timeFormatted,
                    $patientId,
                    'confirmed'
                );
            } catch (\Throwable $e) {
                error_log("[patients.php approve] Email error: " . $e->getMessage());
            }
        }

        if (!empty($patient['contact_number'])) {
            try {
                require_once __DIR__ . '/services/SmsService.php';
                SmsService::sendConfirmationSms(
                    $patient['contact_number'],
                    $dateFormatted,
                    $timeFormatted,
                    $patientId
                );
            } catch (\Throwable $e) {
                error_log("[patients.php approve] SMS error: " . $e->getMessage());
            }
        }

        respondOk(['message' => "$name has been approved and scheduled."]);
    }

    // ── 2. Arrived / Check-in (Facility Arrival) ─────────────────────────
    if ($action === 'arrived' || $action === 'check_in') {
        $currSt = strtolower(trim($patient['status'] ?? ''));
        if (!in_array($currSt, ['approved', 'confirmed'], true)) {
            respondErr('Check-in is available after the appointment is confirmed.');
        }
        $upd = $pdo->prepare(
            "UPDATE patients SET status = 'arrived', updated_at = NOW() WHERE patient_id = ?"
        );
        $upd->execute([$patientId]);
        addLog($pdo, 'info', 'Patient Arrived', $actor,
               "Patient $patientId ($name) checked in at facility/triage desk.");
        respondOk(['message' => "$name is checked in and marked as Arrived."]);
    }

    // ── 3. Ongoing / Start Consultation ──────────────────────────────────
    if ($action === 'ongoing' || $action === 'start_consultation') {
        $currSt = strtolower(trim($patient['status'] ?? ''));
        if ($currSt !== 'arrived') {
            respondErr('Start the consultation after the patient is checked in. Requests still waiting for approval cannot be started.');
        }
        $upd = $pdo->prepare(
            "UPDATE patients SET status = 'ongoing', updated_at = NOW() WHERE patient_id = ?"
        );
        $upd->execute([$patientId]);
        $doc = !empty($patient['assigned_doctor']) ? $patient['assigned_doctor'] : 'Attending Physician';
        addLog($pdo, 'info', 'Consultation Ongoing', $actor,
               "Patient $patientId ($name) consultation in progress with $doc.");
        respondOk(['message' => "Consultation for $name is now Ongoing."]);
    }

    // ── 4. Reject / Cancel / Decline ──────────────────────────────────────
    if ($action === 'reject' || $action === 'cancel') {
        $currSt = strtolower(trim($patient['status'] ?? ''));
        if (in_array($currSt, ['completed', 'rejected', 'cancelled', 'transferred'])) {
            respondErr("Cannot decline or cancel patient in '{$patient['status']}' status.");
        }
        $upd = $pdo->prepare(
            "UPDATE patients SET status = 'cancelled', updated_at = NOW() WHERE patient_id = ?"
        );
        $upd->execute([$patientId]);

        addLog($pdo, 'info', 'Patient Cancelled/Declined', $actor,
               "Patient $patientId ($name) appointment was declined/cancelled by staff.");
        respondOk(['message' => "Appointment for $name has been cancelled/declined."]);
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

        addLog($pdo, 'info', 'Patient No-Show', $actor,
               "Patient $patientId ($name) marked as no-show.");
        respondOk(['message' => "$name marked as no-show."]);
    }

    // ── 7. Completed (Final Step: Officially signed off by Doctor OR Inquiry Resolved) ────────
    if ($action === 'completed') {
        $isInquiry = !empty($patient['inquiry_only']) || ($patient['consultation_nature'] ?? '') === 'General Inquiry';
        $completeStatus = strtolower(trim($patient['status'] ?? ''));
        if (!$isInquiry && $completeStatus !== 'ongoing') {
            respondErr('Complete the visit only while the consultation is in progress.');
        }

        $diag         = trim($body['diagnosis'] ?? '');
        $rx           = trim($body['prescription'] ?? '');
        $plan         = trim($body['treatment_plan'] ?? '');
        $note         = trim($body['note'] ?? '');
        $medName      = trim($body['medicine_name'] ?? '');
        $medDosage    = trim($body['dosage'] ?? '');
        $medQuantity  = trim($body['quantity'] ?? '');

        // Build structured active_medications JSON if medicine/prescription provided
        $activeMedsJson = null;
        if ($medName !== '' || $rx !== '') {
            $parsedName = $medName;
            $parsedDose = $medDosage;
            $parsedQty  = $medQuantity;

            if ($parsedName === '' && $rx !== '') {
                $lines = preg_split('/\r\n|\r|\n/', $rx);
                $firstLine = trim($lines[0] ?? '');
                $firstLine = preg_replace('/^Rx:\s*/i', '', $firstLine);
                if (preg_match('/^(.*?)\s+#([0-9].*)$/', $firstLine, $m)) {
                    $parsedName = trim($m[1]);
                    if ($parsedQty === '') $parsedQty = trim($m[2]);
                } else {
                    $parsedName = $firstLine;
                }
                foreach ($lines as $ln) {
                    if (preg_match('/^Sig:\s*(.+)$/i', trim($ln), $sm)) {
                        if ($parsedDose === '') $parsedDose = trim($sm[1]);
                    }
                }
                if ($parsedDose === '' && count($lines) > 1) {
                    $parsedDose = trim($lines[1]);
                }
            }

            if ($parsedName !== '') {
                $medPayload = [
                    'medicine_name' => $parsedName,
                    'dosage'        => $parsedDose ?: 'Take as directed by physician',
                    'quantity'      => $parsedQty ?: 'Prescribed supply',
                    'diagnosis'     => $diag ?: 'Clinical Consultation',
                    'treatment'     => $plan ?: '',
                    'prescription'  => $rx,
                    'recorded_by'   => $actor,
                    'prescribed_at' => date('Y-m-d H:i:s')
                ];
                $activeMedsJson = json_encode($medPayload, JSON_UNESCAPED_UNICODE);
            }
        }

        if ($diag !== '' || $rx !== '' || $plan !== '' || $activeMedsJson !== null) {
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
                     active_medications = COALESCE(?, active_medications),
                     medical_history_recorded_by = ?,
                     medical_history_date = NOW(),
                     updated_at = NOW() 
                 WHERE patient_id = ?"
            );
            $upd->execute([$diag ?: null, $plan ?: null, $rx ?: null, $activeMedsJson, $actor, $patientId]);
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
