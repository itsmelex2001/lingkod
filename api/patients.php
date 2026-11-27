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

date_default_timezone_set('Asia/Manila');
session_start();
require_once __DIR__ . '/config/cors.php';
require_once __DIR__ . '/config/db.php';

requireAuth();

$authRole = strtolower(trim((string)($_SESSION['role'] ?? '')));
if (!in_array($authRole, ['admin', 'superadmin', 'staff'], true)) {
    respondErr('Forbidden. Clinical staff or administrator access required.', 403);
}

$method = $_SERVER['REQUEST_METHOD'];

// ── GET ─────────────────────────────────────────────────────────────────────
if ($method === 'GET') {
    $stripSensitiveFields = static function (array $row): array {
        static $sensitive = [
            'password', 'otp_code', 'otp_hash', 'otp_expires_at', 'otp_attempts',
            'reset_token', 'reset_token_expires_at', 'verification_token', 'remember_token',
        ];
        foreach ($sensitive as $col) {
            unset($row[$col]);
        }
        return $row;
    };

    $normalizeDeptBucket = static function (?string $dept): string {
        $d = strtolower(trim((string)$dept));
        if ($d === '' || str_contains($d, 'triage')) return '';
        if (str_contains($d, 'psych') || str_contains($d, 'opu')) return 'psychiatry';
        if (str_contains($d, 'neuro')) return 'neurology';
        if (str_contains($d, 'general') || str_contains($d, 'medical') || str_contains($d, 'cardio')) return 'general';
        return $d;
    };

    $resolveStaffDeptBucket = static function (PDO $pdo) use ($authRole, $normalizeDeptBucket): string {
        if ($authRole !== 'staff') return '';
        $dept = trim((string)($_SESSION['department'] ?? ''));
        $username = (string)($_SESSION['user'] ?? '');
        if ($username !== '') {
            try {
                $stmtDept = $pdo->prepare("SELECT department FROM users WHERE username = ? LIMIT 1");
                $stmtDept->execute([$username]);
                $fetchedDept = trim((string)($stmtDept->fetchColumn() ?: ''));
                if ($fetchedDept !== '') $dept = $fetchedDept;
            } catch (Throwable $ignore) {}
        }
        return $normalizeDeptBucket($dept);
    };

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

        // Normalize patient name (also stripping optional single-letter middle initial)
        // so same-person variants ("Alex Gallardo" vs "Alex B. Gallardo") link cleanly,
        // while dependents ("Andrea Anque") sharing an account owner's email/user_id stay isolated.
        $normalizePersonName = static function (array $r): string {
            $raw = trim((string)($r['full_name'] ?? ''));
            if ($raw === '') {
                $raw = trim(implode(' ', array_filter([
                    trim((string)($r['first_name'] ?? '')),
                    trim((string)($r['last_name'] ?? '')),
                ])));
            }
            $clean = strtolower(preg_replace('/\s+/', ' ', $raw));
            $parts = $clean !== '' ? explode(' ', $clean) : [];
            if (count($parts) >= 3) {
                $filtered = [$parts[0]];
                for ($i = 1; $i < count($parts) - 1; $i++) {
                    if (!preg_match('/^[a-z]\.?$/', $parts[$i])) {
                        $filtered[] = $parts[$i];
                    }
                }
                $filtered[] = end($parts);
                return implode(' ', $filtered);
            }
            return $clean;
        };

        $buildPersonKeys = static function (array $r) use ($normalizePersonName): array {
            $nameKey = $normalizePersonName($r);
            $birth   = trim((string)($r['birthdate'] ?? ''));
            $email   = strtolower(trim((string)($r['email'] ?? '')));
            $keys = [];
            if ($nameKey !== '') {
                if ($birth !== '' && $birth !== '0000-00-00') {
                    $keys[] = 'nb:' . $nameKey . '|' . $birth;
                }
                if ($email !== '') {
                    $keys[] = 'ne:' . $nameKey . '|' . $email;
                }
                if (empty($keys)) {
                    $keys[] = 'n:' . $nameKey;
                }
            } else {
                if (!empty($r['hrn']))   $keys[] = 'h:' . strtolower(trim((string)$r['hrn']));
                if (!empty($r['email'])) $keys[] = 'e:' . $email;
            }
            return $keys;
        };

        // Group records by person-scoped identity (never unioning different people on shared email/user_id)
        $byIdentity = [];
        foreach ($allRecords as $rec) {
            foreach ($buildPersonKeys($rec) as $k) {
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

            $rowNameKey = $normalizePersonName($row);
            $matchedMap = [];
            foreach ($buildPersonKeys($row) as $lk) {
                if (!empty($byIdentity[$lk])) {
                    foreach ($byIdentity[$lk] as $cid => $crec) {
                        $cNameKey = $normalizePersonName($crec);
                        if ($rowNameKey !== '' && $cNameKey !== '' && $cNameKey !== $rowNameKey) {
                            continue;
                        }
                        $matchedMap[$cid] = $crec;
                    }
                }
            }

            // Sort matched records by id ASC (chronological booking order)
            ksort($matchedMap);
            $earliestRec = !empty($matchedMap) ? reset($matchedMap) : null;
            $canonicalPid = ($earliestRec && !empty($earliestRec['patient_id']))
                ? $earliestRec['patient_id']
                : $rowPid;

            $priorList = [];
            $completedVisitsCount = 0;
            foreach ($matchedMap as $cid => $crec) {
                $crecStatus = strtolower(trim($crec['status'] ?? ''));
                if ($crecStatus === 'completed') {
                    $completedVisitsCount++;
                }
                if ($cid < $rowId && $crec['patient_id'] !== $rowPid && $crecStatus === 'completed') {
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

            $row['canonical_patient_id']      = $canonicalPid;
            $row['visit_count']               = count($matchedMap) ?: 1;
            $row['completed_visit_count']     = $completedVisitsCount;
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

    // Search patients by name (or ID) for QR Scanner / quick lookup (demographics only; no auth secrets or clinical notes)
    if (isset($_GET['q']) && trim((string)$_GET['q']) !== '') {
        $q = trim((string)$_GET['q']);
        $like = '%' . $q . '%';
        try {
            $stmt = $pdo->prepare(
                "SELECT id, user_id, patient_id, reference_no, hrn, full_name, first_name, last_name, middle_initial,
                        gender, birthdate, age, contact_number, email, address, department, status,
                        appointment_date, preferred_time_slot, consultation_mode, patient_type, profile_image, created_at
                 FROM patients
                 WHERE LOWER(COALESCE(full_name, '')) LIKE LOWER(?)
                    OR LOWER(CONCAT_WS(' ', first_name, middle_initial, last_name)) LIKE LOWER(?)
                    OR LOWER(COALESCE(patient_id, '')) = LOWER(?)
                 ORDER BY id DESC
                 LIMIT 60"
            );
            $stmt->execute([$like, $like, $q]);
            $rawRows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            error_log("Error searching patients for '$q': " . $e->getMessage());
            $rawRows = [];
        }

        // Deduplicate by person-scoped identity (keep latest record, preserve non-empty profile_image)
        $byKey = [];
        foreach ($rawRows as $r) {
            $r = $stripSensitiveFields($r);
            $nameKey = strtolower(preg_replace('/\s+/', ' ', trim((string)($r['full_name'] ?? ''))));
            $birthKey = trim((string)($r['birthdate'] ?? ''));
            $emailKey = strtolower(trim((string)($r['email'] ?? '')));
            $key = ($nameKey !== '' && $birthKey !== '' && $birthKey !== '0000-00-00')
                ? 'nb:' . $nameKey . '|' . $birthKey
                : (($nameKey !== '' && $emailKey !== '')
                    ? 'ne:' . $nameKey . '|' . $emailKey
                    : 'id:' . ($r['id'] ?? '0'));

            if (!isset($byKey[$key])) {
                $byKey[$key] = $r;
            } elseif (empty($byKey[$key]['profile_image']) && !empty($r['profile_image'])) {
                $byKey[$key]['profile_image'] = $r['profile_image'];
            }
        }

        $matches = array_values(array_slice($byKey, 0, 20));
        respondOk(['data' => $matches]);
    }

    // Single patient by ?id=
    if (!empty($_GET['id'])) {
        $id = trim($_GET['id']);
        $staffDeptBucket = $resolveStaffDeptBucket($pdo);
        try {
            $stmt = $pdo->prepare(
                "SELECT * FROM patients
                 WHERE patient_id = ? OR reference_no = ? OR CAST(id AS CHAR) = ?
                 ORDER BY id DESC LIMIT 1"
            );
            $stmt->execute([$id, $id, $id]);
            $patient = $stmt->fetch();
            if (!$patient && stripos($id, 'PT-') === 0) {
                $uStmt = $pdo->prepare("SELECT id, patient_id FROM users WHERE account_patient_id = ? LIMIT 1");
                $uStmt->execute([$id]);
                $uRow = $uStmt->fetch();
                if ($uRow) {
                    $fbStmt = $pdo->prepare("SELECT * FROM patients WHERE user_id = ? OR id = ? ORDER BY id DESC LIMIT 1");
                    $fbStmt->execute([$uRow['id'], (int)($uRow['patient_id'] ?? 0)]);
                    $patient = $fbStmt->fetch();
                }
            }
        } catch (Throwable $e) {
            error_log("Error fetching patient $id: " . $e->getMessage());
            $patient = null;
        }

        if (!$patient) respondErr("Patient $id not found.", 404);

        // Enforce department isolation for clinical staff (allow neutral unbooked 'registered' profile stubs for QR intake)
        if ($authRole === 'staff') {
            $patientDeptBucket = $normalizeDeptBucket($patient['department'] ?? '');
            $isUnbookedProfile = strtolower(trim((string)($patient['status'] ?? ''))) === 'registered' && $patientDeptBucket === '';
            if (!$isUnbookedProfile && ($staffDeptBucket === '' || ($patientDeptBucket !== '' && $patientDeptBucket !== $staffDeptBucket))) {
                respondErr('Forbidden. This patient record belongs to another department.', 403);
            }
        }

        $patient = $stripSensitiveFields($patient);

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
            // Scope prior clinical history to the staff user's own department
            if ($authRole === 'staff' && !empty($patient['previous_appointments']) && is_array($patient['previous_appointments'])) {
                $patient['previous_appointments'] = array_values(array_filter(
                    $patient['previous_appointments'],
                    static fn(array $prev): bool => $normalizeDeptBucket($prev['department'] ?? '') === $staffDeptBucket
                ));
            }
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
    
    $bookedOnlySql = "LOWER(COALESCE(p.status, '')) <> 'registered' AND (p.appointment_date IS NOT NULL OR (p.department IS NOT NULL AND p.department <> '') OR (p.chief_complaints IS NOT NULL AND p.chief_complaints <> '') OR (p.consultation_nature IS NOT NULL AND p.consultation_nature <> '') OR p.unscheduled_walkin = 1)";

    date_default_timezone_set('Asia/Manila');
    $pCreatedDateExpr = dbIsPgsql() ? 'CAST(p.created_at AS DATE)' : 'DATE(p.created_at)';
    $pWeekStart = date('Y-m-d', strtotime('monday this week'));
    $pWeekEnd   = date('Y-m-d', strtotime($pWeekStart . ' +6 days'));
    $pMonthStart = date('Y-m-01');
    $pMonthEnd   = date('Y-m-t');
    $pYearStart  = date('Y-01-01');
    $pYearEnd    = date('Y-12-31');

    if (in_array(strtolower((string)$role), ['admin', 'superadmin'], true)) {
        $filterSql = "AND $bookedOnlySql";
        if ($filter === 'week') {
            $filterSql .= " AND {$pCreatedDateExpr} BETWEEN '{$pWeekStart}' AND '{$pWeekEnd}'";
        } elseif ($filter === 'month') {
            $filterSql .= " AND {$pCreatedDateExpr} BETWEEN '{$pMonthStart}' AND '{$pMonthEnd}'";
        } elseif ($filter === 'year') {
            $filterSql .= " AND {$pCreatedDateExpr} BETWEEN '{$pYearStart}' AND '{$pYearEnd}'";
        }

        $hasRefNoCol = false;
        try {
            $cols = dbTableColumns($pdo, 'patients');
            $hasRefNoCol = in_array('reference_no', $cols, true);
            if (!dbIsPgsql()) {
                if (!in_array('vitals_json', $cols, true)) {
                    @$pdo->exec("ALTER TABLE patients ADD COLUMN vitals_json TEXT NULL");
                }
                if (!in_array('follow_up_notes', $cols, true)) {
                    @$pdo->exec("ALTER TABLE patients ADD COLUMN follow_up_notes TEXT NULL");
                }
                $docCols = $pdo->query("SHOW COLUMNS FROM doctors")->fetchAll(PDO::FETCH_COLUMN);
                if (!in_array('title', $docCols, true)) {
                    @$pdo->exec("ALTER TABLE doctors ADD COLUMN title VARCHAR(80) NULL");
                }
                if (!in_array('license_number', $docCols, true)) {
                    @$pdo->exec("ALTER TABLE doctors ADD COLUMN license_number VARCHAR(100) NULL");
                }
            }
        } catch (Throwable $ignoreRef) {}
        $refSelect = $hasRefNoCol ? 'p.reference_no,' : 'NULL AS reference_no,';

        $stmt = $pdo->prepare(
            "SELECT p.id, p.user_id, p.patient_id, $refSelect p.department, p.full_name, p.first_name, p.last_name, p.middle_initial,
                    p.gender, p.birthdate, p.age, p.contact_number, p.email,
                    p.address, p.with_companion, p.relationship_to_patient, p.appointment_recipient,
                    p.chief_complaints, p.consultation_nature, p.illness_description,
                    p.existing_medical_conditions, p.current_medications, p.referral_source,
                    p.patient_type, p.appointment_type, p.consultation_mode, p.appointment_date,
                    p.status, p.assigned_doctor, p.unscheduled_walkin, p.inquiry_only, p.mode_of_inquiry,
                    p.recommendation_remarks, p.receiver, p.hrn, p.scheduled_time, p.preferred_time_slot,
                    p.medical_history_diagnosis, p.medical_history_treatment, p.medical_history_prescription, p.active_medications,
                    p.medical_history_recorded_by, p.medical_history_date,
                    p.next_visit_date, p.vitals_json, p.follow_up_notes,
                    p.rescheduled_by_staff, p.rescheduled_from_date, p.rescheduled_at,
                    p.teleconsult_platform, p.teleconsult_link, p.teleconsult_notes,
                    p.reminder_status, p.reminder_sent_at, p.created_at,
                    d.id AS doctor_id, d.status AS doctor_status, d.title AS doctor_title, d.license_number AS doctor_license
             FROM patients p
             LEFT JOIN doctors d ON (p.assigned_doctor = d.name OR p.assigned_doctor = CONCAT('Dr. ', d.name) OR REPLACE(p.assigned_doctor, 'Dr. ', '') = REPLACE(d.name, 'Dr. ', ''))
             WHERE 1=1 $filterSql
             ORDER BY p.created_at DESC"
        );
        $stmt->execute();
        $rows = $stmt->fetchAll();
        $attachPreviousHistory($pdo, $rows, false);

        if (!empty($_GET['deduplicate'])) {
            $totalEncounters = count($rows);
            $grouped = [];
            $keyAliases = [];
            $backfillFields = ['hrn', 'gender', 'age', 'birthdate', 'contact_number', 'email', 'address'];

            foreach ($rows as $row) {
                $nameNorm  = strtolower(preg_replace('/\s+/', ' ', trim((string)($row['full_name'] ?? ''))));
                $birthVal  = trim((string)($row['birthdate'] ?? ''));
                $emailNorm = strtolower(trim((string)($row['email'] ?? '')));
                $genderVal = strtolower(trim((string)($row['gender'] ?? '')));

                $candidates = [];
                if ($nameNorm !== '' && $birthVal !== '' && $birthVal !== '0000-00-00') {
                    $candidates[] = 'nb:' . $nameNorm . '|' . $birthVal;
                }
                if ($nameNorm !== '' && $emailNorm !== '') {
                    $candidates[] = 'ne:' . $nameNorm . '|' . $emailNorm;
                }
                if (empty($candidates)) {
                    if ($nameNorm !== '' && $genderVal !== '') {
                        $candidates[] = 'ng:' . $nameNorm . '|' . $genderVal;
                    } elseif ($nameNorm !== '') {
                        $candidates[] = 'n:' . $nameNorm;
                    } else {
                        $candidates[] = 'id:' . ($row['id'] ?? $row['patient_id'] ?? uniqid('', true));
                    }
                }

                $key = null;
                foreach ($candidates as $cand) {
                    if (isset($keyAliases[$cand])) {
                        $key = $keyAliases[$cand];
                        break;
                    }
                }
                if ($key === null) {
                    $key = $candidates[0];
                }
                foreach ($candidates as $cand) {
                    $keyAliases[$cand] = $key;
                }

                $rowDateRaw = !empty($row['appointment_date']) && $row['appointment_date'] !== '0000-00-00'
                    ? $row['appointment_date']
                    : substr((string)($row['created_at'] ?? ''), 0, 10);

                $approvalStatuses = ['pending', 'for_approval', 'for_processing', 'for approval'];
                $rowStatus = strtolower(trim((string)($row['status'] ?? '')));
                $rowIsPending = in_array($rowStatus, $approvalStatuses, true);

                if (!isset($grouped[$key])) {
                    $row['total_visits']     = 1;
                    $row['completed_visits'] = $rowStatus === 'completed' ? 1 : 0;
                    $row['last_visit_date']  = $rowDateRaw !== '' ? date('M j, Y', strtotime($rowDateRaw)) : null;
                    $row['last_visit_raw']   = $rowDateRaw ?: null;
                    $row['has_pending_approval'] = $rowIsPending ? 1 : 0;
                    if ($rowIsPending) {
                        $row['pending_patient_id'] = $row['patient_id'] ?? $row['id'] ?? null;
                        $row['pending_status'] = $row['status'];
                        $row['pending_department'] = $row['department'] ?? null;
                        $row['pending_appointment_date'] = $rowDateRaw ?: null;
                        $row['pending_inquiry_only'] = $row['inquiry_only'] ?? 0;
                    }
                    $grouped[$key] = $row;
                } else {
                    $grouped[$key]['total_visits']++;
                    if ($rowStatus === 'completed') {
                        $grouped[$key]['completed_visits']++;
                    }
                    // A newer visit can hide an older booking that still needs approval.
                    if ($rowIsPending && empty($grouped[$key]['pending_patient_id'])) {
                        $grouped[$key]['has_pending_approval'] = 1;
                        $grouped[$key]['pending_patient_id'] = $row['patient_id'] ?? $row['id'] ?? null;
                        $grouped[$key]['pending_status'] = $row['status'];
                        $grouped[$key]['pending_department'] = $row['department'] ?? null;
                        $grouped[$key]['pending_appointment_date'] = $rowDateRaw ?: null;
                        $grouped[$key]['pending_inquiry_only'] = $row['inquiry_only'] ?? 0;
                    }
                    // Preserve canonical earliest PT-... ID
                    if (!empty($row['canonical_patient_id'])) {
                        $grouped[$key]['canonical_patient_id'] = $row['canonical_patient_id'];
                    }
                    // Backfill missing demographics from earlier rows
                    foreach ($backfillFields as $f) {
                        $cur = trim((string)($grouped[$key][$f] ?? ''));
                        $alt = trim((string)($row[$f] ?? ''));
                        if (($cur === '' || $cur === '0000-00-00') && $alt !== '' && $alt !== '0000-00-00') {
                            $grouped[$key][$f] = $row[$f];
                        }
                    }
                }
            }

            $deduped = array_values($grouped);
            $returningCount = 0;
            foreach ($deduped as $d) {
                if ((int)($d['total_visits'] ?? 1) >= 2) {
                    $returningCount++;
                }
            }

            respondOk([
                'data'    => $deduped,
                'summary' => [
                    'unique_patients'    => count($deduped),
                    'returning_patients' => $returningCount,
                    'total_encounters'   => $totalEncounters,
                ],
            ]);
        }

        respondOk(['data' => $rows]);
    }

    $whereClauses = [$bookedOnlySql];
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
        $whereClauses[] = "{$pCreatedDateExpr} BETWEEN '{$pWeekStart}' AND '{$pWeekEnd}'";
    } elseif ($filter === 'month') {
        $whereClauses[] = "{$pCreatedDateExpr} BETWEEN '{$pMonthStart}' AND '{$pMonthEnd}'";
    } elseif ($filter === 'year') {
        $whereClauses[] = "{$pCreatedDateExpr} BETWEEN '{$pYearStart}' AND '{$pYearEnd}'";
    }
    
    $whereSql = count($whereClauses) > 0 ? "WHERE " . implode(" AND ", $whereClauses) : "";
    
    $hasRefNoCol = false;
    try {
        $cols = dbTableColumns($pdo, 'patients');
        $hasRefNoCol = in_array('reference_no', $cols, true);
        if (!dbIsPgsql()) {
            if (!in_array('vitals_json', $cols, true)) {
                @$pdo->exec("ALTER TABLE patients ADD COLUMN vitals_json TEXT NULL");
            }
            if (!in_array('follow_up_notes', $cols, true)) {
                @$pdo->exec("ALTER TABLE patients ADD COLUMN follow_up_notes TEXT NULL");
            }
            $docCols = $pdo->query("SHOW COLUMNS FROM doctors")->fetchAll(PDO::FETCH_COLUMN);
            if (!in_array('title', $docCols, true)) {
                @$pdo->exec("ALTER TABLE doctors ADD COLUMN title VARCHAR(80) NULL");
            }
            if (!in_array('license_number', $docCols, true)) {
                @$pdo->exec("ALTER TABLE doctors ADD COLUMN license_number VARCHAR(100) NULL");
            }
        }
    } catch (Throwable $ignoreRef) {}
    $refSelect = $hasRefNoCol ? 'p.reference_no,' : 'NULL AS reference_no,';

    $stmt = $pdo->prepare(
        "SELECT p.id, p.user_id, p.patient_id, $refSelect p.department, p.full_name, p.first_name, p.last_name, p.middle_initial,
                p.gender, p.birthdate, p.age, p.contact_number, p.email,
                p.address, p.with_companion, p.relationship_to_patient, p.appointment_recipient,
                p.chief_complaints, p.consultation_nature, p.illness_description,
                p.existing_medical_conditions, p.current_medications,
                p.patient_type, p.appointment_type, p.consultation_mode, p.appointment_date,
                p.status, p.assigned_doctor, p.unscheduled_walkin, p.inquiry_only, p.mode_of_inquiry,
                p.recommendation_remarks, p.receiver, p.hrn, p.scheduled_time, p.preferred_time_slot, p.referral_source,
                p.medical_history_diagnosis, p.medical_history_treatment, p.medical_history_prescription, p.active_medications,
                p.medical_history_recorded_by, p.medical_history_date,
                p.next_visit_date, p.vitals_json, p.follow_up_notes,
                p.rescheduled_by_staff, p.rescheduled_from_date, p.rescheduled_at,
                p.teleconsult_platform, p.teleconsult_link, p.teleconsult_notes,
                p.reminder_status, p.reminder_sent_at, p.created_at,
                d.id AS doctor_id, d.status AS doctor_status, d.title AS doctor_title, d.license_number AS doctor_license
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

    // Mandatory CSRF verification on all state-changing staff/admin actions
    $csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($body['csrf_token'] ?? null);
    if (!$csrfToken || !verifyCsrfToken((string)$csrfToken)) {
        respondErr('Security check failed: Missing or invalid CSRF token. Please refresh the page.', 403);
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
    if (str_contains($a, 'management') || str_contains($b, 'management')) return true;
    if ($a === $b) return true;
    if ((str_contains($a, 'general') || str_contains($a, 'general medical')) && (str_contains($b, 'general') || str_contains($b, 'general medical'))) return true;
    if ((str_contains($a, 'psychiatry') || str_contains($a, 'opu')) && (str_contains($b, 'psychiatry') || str_contains($b, 'opu'))) return true;
    if (str_contains($a, 'neuro') && str_contains($b, 'neuro')) return true;
    return false;
}

// Allow staff full access to queue workflow actions for records visible in their dashboard
$unrestrictedStaffActions = [
    'assign_department', 'update_department', 'assign', 'reschedule_assign',
    'approve', 'accept', 'completed', 'referral',
    'ongoing', 'start_consultation', 'arrived', 'check_in', 'cancel', 'reject'
];
if (!in_array($action, $unrestrictedStaffActions, true)) {
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

    function findDoctorByName(PDO $pdo, string $doctor): ?array {
        $stmt = $pdo->prepare(
            "SELECT id, name, status
             FROM doctors
             WHERE name = ?
                OR REPLACE(name, 'Dr. ', '') = REPLACE(?, 'Dr. ', '')
                OR TRIM(REPLACE(REPLACE(LOWER(name), 'dr. ', ''), ', md', '')) = TRIM(REPLACE(REPLACE(LOWER(?), 'dr. ', ''), ', md', ''))
             LIMIT 1"
        );
        $stmt->execute([$doctor, $doctor, $doctor]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    $extractTeleconsult = static function (array $b): array {
        $hasAny = array_key_exists('teleconsult_platform', $b)
            || array_key_exists('teleconsult_link', $b)
            || array_key_exists('teleconsult_notes', $b);
        if (!$hasAny) {
            return ['provided' => false, 'platform' => null, 'link' => null, 'notes' => null];
        }
        $platform = substr(trim((string)($b['teleconsult_platform'] ?? '')), 0, 50);
        $link     = substr(trim((string)($b['teleconsult_link'] ?? '')), 0, 500);
        $notes    = substr(trim((string)($b['teleconsult_notes'] ?? '')), 0, 255);
        if ($link !== '') {
            $scheme = strtolower((string)(parse_url($link, PHP_URL_SCHEME) ?? ''));
            if (!in_array($scheme, ['http', 'https'], true) || !filter_var($link, FILTER_VALIDATE_URL)) {
                respondErr('Please enter a valid https:// teleconsultation meeting link.', 422);
            }
        }
        return [
            'provided' => true,
            'platform' => $platform !== '' ? $platform : null,
            'link'     => $link !== '' ? $link : null,
            'notes'    => $notes !== '' ? $notes : null,
        ];
    };

    // ── 1. Approve / Accept ──────────────────────────────────────────────
    if ($action === 'approve' || $action === 'accept') {
        $currSt = strtolower(trim($patient['status'] ?? ''));
        if (!in_array($currSt, ['pending', 'for_approval', 'for_processing', 'for approval'], true)) {
            respondErr("Patient is already {$patient['status']}.");
        }

        $tc = $extractTeleconsult($body);

        $isInquiry = !empty($patient['inquiry_only'])
            || strcasecmp(trim($patient['consultation_nature'] ?? ''), 'General Inquiry') === 0
            || strcasecmp(trim($patient['appointment_type'] ?? ''), 'inquiry') === 0
            || str_starts_with($patientId, 'INQ-');

        $providedDoctor = trim($body['assigned_doctor'] ?? ($body['doctor'] ?? ''));
        $effectiveDoctor = trim((string)($patient['assigned_doctor'] ?? ''));

        if ($providedDoctor !== '' && strcasecmp($providedDoctor, 'None') !== 0) {
            $docRow = findDoctorByName($pdo, $providedDoctor);
            if (!$docRow) {
                respondErr("Doctor '$providedDoctor' not found.", 422);
            }
            $docSt = strtolower(trim($docRow['status'] ?? ''));
            if (in_array($docSt, ['leave', 'out', 'on_break', 'emergency'], true)) {
                respondErr("{$docRow['name']} is currently unavailable ($docSt). Please choose an available physician or reschedule the patient.", 422);
            }
            $effectiveDoctor = $docRow['name'];
        }

        if (!$isInquiry && ($effectiveDoctor === '' || strcasecmp($effectiveDoctor, 'None') === 0)) {
            respondErr('Please assign an attending physician based on available time slots before approving this appointment.', 422);
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
                SET status = 'approved',
                    department = ?,
                    specialty = ?,
                    assigned_doctor = CASE WHEN ? <> '' THEN ? ELSE assigned_doctor END,
                    assigned_date = CASE WHEN ? <> '' AND assigned_date IS NULL THEN CURRENT_DATE ELSE assigned_date END,
                    updated_at = CURRENT_TIMESTAMP 
                WHERE patient_id = ?
            ");
            $upd->execute([$cleanDept, $cleanDept . ' Consultation', $effectiveDoctor, $effectiveDoctor, $effectiveDoctor, $patientId]);
        } else {
            $upd = $pdo->prepare(
                "UPDATE patients
                 SET status = 'approved',
                     assigned_doctor = CASE WHEN ? <> '' THEN ? ELSE assigned_doctor END,
                     assigned_date = CASE WHEN ? <> '' AND assigned_date IS NULL THEN CURRENT_DATE ELSE assigned_date END,
                     updated_at = CURRENT_TIMESTAMP
                 WHERE patient_id = ?"
            );
            $upd->execute([$effectiveDoctor, $effectiveDoctor, $effectiveDoctor, $patientId]);
        }
        if ($tc['provided']) {
            $pdo->prepare("UPDATE patients SET teleconsult_platform = ?, teleconsult_link = ?, teleconsult_notes = ? WHERE patient_id = ?")
                ->execute([$tc['platform'], $tc['link'], $tc['notes'], $patientId]);
        }
        addLog($pdo, 'info', 'Patient Approved', $actor,
               "Patient $patientId ($name) was approved and scheduled for $dept" . ($effectiveDoctor ? " with $effectiveDoctor." : "."));

        // Dispatch Confirmation Email & SMS (Compliant with Healthcare Privacy Laws)
        $dateFormatted = !empty($patient['appointment_date'])
            ? date('M j, Y', strtotime($patient['appointment_date']))
            : 'Scheduled Clinic Day';
        $timeFormatted = !empty($patient['scheduled_time'])
            ? date('g:i A', strtotime($patient['scheduled_time']))
            : '8:00 AM';
        $consultMode = trim((string)($patient['consultation_mode'] ?? 'Face-to-Face')) ?: 'Face-to-Face';

        if (!empty($patient['email'])) {
            try {
                require_once __DIR__ . '/services/EmailService.php';
                EmailService::sendAppointmentEmail(
                    $patient['email'],
                    $name,
                    $dateFormatted,
                    $timeFormatted,
                    $patientId,
                    'confirmed',
                    $consultMode
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
                    $patientId,
                    $consultMode
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

    // ── 2B. Save Vital Signs (Triage / Pre-Consultation) ─────────────────
    if ($action === 'save_vitals') {
        $vitals = is_array($body['vitals'] ?? null) ? $body['vitals'] : [
            'bp'          => trim((string)($body['bp'] ?? '')),
            'temp'        => trim((string)($body['temp'] ?? '')),
            'pulse'       => trim((string)($body['pulse'] ?? '')),
            'rr'          => trim((string)($body['rr'] ?? '')),
            'spo2'        => trim((string)($body['spo2'] ?? '')),
            'weight'      => trim((string)($body['weight'] ?? '')),
            'height'      => trim((string)($body['height'] ?? '')),
        ];
        $vitals['recorded_by'] = $actor;
        $vitals['recorded_at'] = date('Y-m-d H:i:s');
        $vitalsJson = json_encode($vitals, JSON_UNESCAPED_UNICODE);

        $pdo->prepare("UPDATE patients SET vitals_json = ?, updated_at = NOW() WHERE patient_id = ?")
            ->execute([$vitalsJson, $patientId]);
        addLog($pdo, 'info', 'Vitals Recorded', $actor, "Vital signs recorded for patient $patientId ($name).");
        respondOk(['message' => "Vital signs saved for $name.", 'vitals' => $vitals]);
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

        $doc = findDoctorByName($pdo, $doctor);

        if (!$doc) respondErr("Doctor '$doctor' not found.");
        $docSt = strtolower(trim($doc['status'] ?? ''));
        if (in_array($docSt, ['leave', 'out', 'on_break', 'emergency'], true)) {
            respondErr("{$doc['name']} is currently unavailable ($docSt). Please choose another physician or reschedule the patient.", 422);
        }

        $canonicalDoc = $doc['name'];
        $upd = $pdo->prepare(
            "UPDATE patients
             SET assigned_doctor = ?, assigned_date = CURRENT_DATE, updated_at = CURRENT_TIMESTAMP
             WHERE patient_id = ?"
        );
        $upd->execute([$canonicalDoc, $patientId]);

        addLog($pdo, 'info', 'Doctor Assigned', $actor,
               "$canonicalDoc assigned to patient $patientId ($name).");

        respondOk(['message' => "$canonicalDoc has been assigned to $name.", 'assigned_doctor' => $canonicalDoc]);
    }

    // ── 5B. Reschedule & Assign Physician (After Calling Patient) ────────
    if ($action === 'reschedule_assign') {
        $currSt = strtolower(trim($patient['status'] ?? ''));
        if (in_array($currSt, ['completed', 'rejected', 'cancelled', 'transferred'], true)) {
            respondErr("Cannot reschedule or reassign patient in '{$patient['status']}' status.", 422);
        }

        $doctor = trim($body['doctor'] ?? '');
        $newDate = trim($body['appointment_date'] ?? '');
        $rawSlot = trim($body['preferred_time_slot'] ?? '');
        $approveNow = !empty($body['approve_now']);
        $tc = $extractTeleconsult($body);

        if ($doctor === '') respondErr('Attending physician is required.', 422);
        if ($newDate === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $newDate)) {
            respondErr('A valid appointment date (YYYY-MM-DD) is required.', 422);
        }
        if ($newDate < date('Y-m-d')) {
            respondErr('Appointment date cannot be in the past.', 422);
        }
        if ((int)date('w', strtotime($newDate)) === 0) {
            respondErr('Hospital clinics are closed on Sundays. Please pick a Monday to Saturday date.', 422);
        }

        $doc = findDoctorByName($pdo, $doctor);
        if (!$doc) respondErr("Doctor '$doctor' not found.", 422);
        $docSt = strtolower(trim($doc['status'] ?? ''));
        if (in_array($docSt, ['leave', 'out', 'on_break', 'emergency'], true)) {
            respondErr("{$doc['name']} is currently unavailable ($docSt). Please select an available physician.", 422);
        }

        $isMorning = stripos($rawSlot, 'morning') !== false || stripos($rawSlot, '8:00') !== false;
        $isAfternoon = !$isMorning && (stripos($rawSlot, 'afternoon') !== false || stripos($rawSlot, '1:00') !== false || stripos($rawSlot, '13:00') !== false);
        if (!$isAfternoon && !$isMorning) {
            respondErr('Please select either Morning or Afternoon time block.', 422);
        }

        $slotLabel = $isAfternoon ? 'Afternoon (1:00 PM - 5:00 PM)' : 'Morning (8:00 AM - 12:00 PM)';
        $scheduledTime = $isAfternoon ? '13:00:00' : '08:00:00';
        $endTime = $isAfternoon ? '17:00:00' : '12:00:00';
        $canonicalDoc = $doc['name'];

        $oldDate = trim((string)($patient['appointment_date'] ?? ''));
        $oldSlot = trim((string)($patient['preferred_time_slot'] ?? ''));
        $isRescheduled = ($oldDate !== '' && $oldDate !== $newDate) || ($oldSlot !== '' && strcasecmp($oldSlot, $slotLabel) !== 0);
        $fromDateVal = ($oldDate !== '' && $oldDate !== $newDate) ? $oldDate : ($patient['rescheduled_from_date'] ?? ($oldDate !== '' ? $oldDate : null));
        $reschedSql = $isRescheduled
            ? ", rescheduled_by_staff = 1, rescheduled_from_date = ?, rescheduled_at = NOW()"
            : "";

        $willApprove = $approveNow && in_array($currSt, ['pending', 'for_approval', 'for_processing', 'for approval'], true);
        if ($willApprove) {
            $upd = $pdo->prepare(
                "UPDATE patients
                 SET assigned_doctor = ?,
                     assigned_date = CURRENT_DATE,
                     appointment_date = ?,
                     preferred_time_slot = ?,
                     scheduled_time = ?,
                     status = 'approved'{$reschedSql},
                     updated_at = CURRENT_TIMESTAMP
                 WHERE patient_id = ?"
            );
            $params = [$canonicalDoc, $newDate, $slotLabel, $scheduledTime];
            if ($isRescheduled) $params[] = $fromDateVal;
            $params[] = $patientId;
            $upd->execute($params);
        } else {
            $upd = $pdo->prepare(
                "UPDATE patients
                 SET assigned_doctor = ?,
                     assigned_date = CURRENT_DATE,
                     appointment_date = ?,
                     preferred_time_slot = ?,
                     scheduled_time = ?{$reschedSql},
                     updated_at = CURRENT_TIMESTAMP
                 WHERE patient_id = ?"
            );
            $params = [$canonicalDoc, $newDate, $slotLabel, $scheduledTime];
            if ($isRescheduled) $params[] = $fromDateVal;
            $params[] = $patientId;
            $upd->execute($params);
        }

        $refNo = trim((string)($patient['reference_no'] ?? ''));
        if ($tc['provided']) {
            $pdo->prepare("UPDATE patients SET teleconsult_platform = ?, teleconsult_link = ?, teleconsult_notes = ? WHERE patient_id = ?")
                ->execute([$tc['platform'], $tc['link'], $tc['notes'], $patientId]);
        }
        try {
            if ($refNo !== '') {
                $apptUpd = $pdo->prepare(
                    "UPDATE appointments
                     SET appointment_date = ?, start_time = ?, end_time = ?, preferred_time_slot = ?
                     WHERE reference_no = ? OR patient_id = ?"
                );
                $apptUpd->execute([$newDate, $scheduledTime, $endTime, $slotLabel, $refNo, $patientId]);
            } else {
                $apptUpd = $pdo->prepare(
                    "UPDATE appointments
                     SET appointment_date = ?, start_time = ?, end_time = ?, preferred_time_slot = ?
                     WHERE patient_id = ?"
                );
                $apptUpd->execute([$newDate, $scheduledTime, $endTime, $slotLabel, $patientId]);
            }
        } catch (\Throwable $ignoreApptUpd) {}

        $logTitle = $willApprove ? 'Schedule Updated & Approved' : 'Schedule & Physician Updated';
        addLog($pdo, 'info', $logTitle, $actor,
               "Staff coordinated new schedule for $patientId ($name): $newDate ($slotLabel) with $canonicalDoc" . ($willApprove ? ' [Approved]' : '') . '.');

        if ($willApprove) {
            $dateFormatted = date('M j, Y', strtotime($newDate));
            $timeFormatted = date('g:i A', strtotime($scheduledTime));
            $consultMode = trim((string)($patient['consultation_mode'] ?? 'Face-to-Face')) ?: 'Face-to-Face';
            if (!empty($patient['email'])) {
                try {
                    require_once __DIR__ . '/services/EmailService.php';
                    EmailService::sendAppointmentEmail($patient['email'], $name, $dateFormatted, $timeFormatted, $patientId, 'confirmed', $consultMode);
                } catch (\Throwable $e) {
                    error_log("[patients.php reschedule_assign] Email error: " . $e->getMessage());
                }
            }
            if (!empty($patient['contact_number'])) {
                try {
                    require_once __DIR__ . '/services/SmsService.php';
                    SmsService::sendConfirmationSms($patient['contact_number'], $dateFormatted, $timeFormatted, $patientId, $consultMode);
                } catch (\Throwable $e) {
                    error_log("[patients.php reschedule_assign] SMS error: " . $e->getMessage());
                }
            }
        }

        respondOk([
            'message' => $willApprove
                ? "Schedule updated to $newDate ($slotLabel), assigned $canonicalDoc, and appointment approved."
                : "Schedule updated to $newDate ($slotLabel) with $canonicalDoc.",
            'assigned_doctor'      => $canonicalDoc,
            'appointment_date'     => $newDate,
            'preferred_time_slot'  => $slotLabel,
            'teleconsult_platform' => $tc['provided'] ? $tc['platform'] : ($patient['teleconsult_platform'] ?? null),
            'teleconsult_link'     => $tc['provided'] ? $tc['link'] : ($patient['teleconsult_link'] ?? null),
            'teleconsult_notes'    => $tc['provided'] ? $tc['notes'] : ($patient['teleconsult_notes'] ?? null),
        ]);
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
        $nextVisit    = trim($body['next_visit_date'] ?? '');
        $followUpNote = trim($body['follow_up_notes'] ?? '');
        $vitalsInput  = is_array($body['vitals'] ?? null) ? $body['vitals'] : null;
        $medicinesArr = is_array($body['medicines'] ?? null) ? $body['medicines'] : [];

        $vitalsJson = null;
        if ($vitalsInput) {
            $hasAnyVital = false;
            foreach (['bp', 'temp', 'pulse', 'rr', 'spo2', 'weight', 'height'] as $vk) {
                if (trim((string)($vitalsInput[$vk] ?? '')) !== '') {
                    $hasAnyVital = true;
                    break;
                }
            }
            if ($hasAnyVital) {
                $vitalsInput['recorded_by'] = $actor;
                $vitalsInput['recorded_at'] = date('Y-m-d H:i:s');
                $vitalsJson = json_encode($vitalsInput, JSON_UNESCAPED_UNICODE);
            }
        }

        // Build structured active_medications JSON (supports multi-select medicines array or single medicine)
        $activeMedsJson = null;
        if (!empty($medicinesArr)) {
            $cleanMeds = [];
            foreach ($medicinesArr as $mItem) {
                if (!is_array($mItem)) continue;
                $mn = trim((string)($mItem['medicine_name'] ?? ($mItem['name'] ?? '')));
                if ($mn === '') continue;
                $cleanMeds[] = [
                    'medicine_name' => $mn,
                    'dosage'        => trim((string)($mItem['dosage'] ?? '')) ?: 'Take as directed by physician',
                    'quantity'      => trim((string)($mItem['quantity'] ?? '')) ?: 'Prescribed supply',
                    'diagnosis'     => $diag ?: 'Clinical Consultation',
                    'recorded_by'   => $actor,
                    'prescribed_at' => date('Y-m-d H:i:s')
                ];
            }
            if (!empty($cleanMeds)) {
                $activeMedsJson = json_encode(count($cleanMeds) === 1 ? $cleanMeds[0] : $cleanMeds, JSON_UNESCAPED_UNICODE);
            }
        } elseif ($medName !== '' || $rx !== '') {
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

        $encodedAt = date('Y-m-d H:i:s');
        $cleanNextVisit = null;
        if ($nextVisit !== '') {
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $nextVisit)) {
                respondErr('Follow-up date must be a valid date (YYYY-MM-DD).', 422);
            }
            if ($nextVisit <= date('Y-m-d')) {
                respondErr('Follow-up date must be a future date (starting tomorrow).', 422);
            }
            if ((int)date('w', strtotime($nextVisit)) === 0) {
                respondErr('Hospital clinics are closed on Sundays. Please pick a Monday to Saturday follow-up date.', 422);
            }
            $cleanNextVisit = $nextVisit;
        }

        if ($diag !== '' || $rx !== '' || $plan !== '' || $activeMedsJson !== null || $cleanNextVisit !== null || $followUpNote !== '' || $vitalsJson !== null) {
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
                     next_visit_date = COALESCE(?, next_visit_date),
                     follow_up_notes = COALESCE(?, follow_up_notes),
                     vitals_json = COALESCE(?, vitals_json),
                     medical_history_recorded_by = ?,
                     medical_history_date = ?,
                     updated_at = ? 
                 WHERE patient_id = ?"
            );
            $upd->execute([
                $diag ?: null,
                $plan ?: null,
                $rx ?: null,
                $activeMedsJson,
                $cleanNextVisit,
                $followUpNote ?: null,
                $vitalsJson,
                $actor,
                $encodedAt,
                $encodedAt,
                $patientId
            ]);
        } else {
            $upd = $pdo->prepare(
                "UPDATE patients 
                 SET status = 'completed', 
                     reminder_status = CASE 
                         WHEN reminder_status = 'Sent' THEN 'Sent' 
                         ELSE 'Completed' 
                     END,
                     medical_history_recorded_by = COALESCE(medical_history_recorded_by, ?),
                     medical_history_date = COALESCE(medical_history_date, ?),
                     updated_at = ? 
                 WHERE patient_id = ?"
            );
            $upd->execute([$actor, $encodedAt, $encodedAt, $patientId]);
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
        respondOk([
            'message' => $isInquiry ? "Inquiry for $name has been resolved and marked completed." : "Medical service for $name has been completed and signed off.",
            'data'    => [
                'patient_id'                  => $patientId,
                'medical_history_date'        => $encodedAt,
                'medical_history_recorded_by' => $actor,
                'next_visit_date'             => $cleanNextVisit,
                'follow_up_notes'             => $followUpNote ?: null,
            ],
        ]);
    }

    // ── Reschedule ───────────────────────────────────────────────────────
    if ($action === 'reschedule') {
        $newDate = trim($body['date'] ?? '');
        if ($newDate === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $newDate)) {
            respondErr('A valid appointment date (YYYY-MM-DD) is required.', 422);
        }
        if ($newDate < date('Y-m-d')) {
            respondErr('Cannot reschedule an appointment to a past date.', 422);
        }
        if ((int)date('w', strtotime($newDate)) === 0) {
            respondErr('Hospital clinics are closed on Sundays. Please pick a Monday to Saturday date.', 422);
        }
        $oldDate = trim((string)($patient['appointment_date'] ?? ''));
        $fromDateVal = ($oldDate !== '' && $oldDate !== $newDate) ? $oldDate : ($patient['rescheduled_from_date'] ?? null);
        $upd = $pdo->prepare(
            "UPDATE patients
             SET appointment_date = ?,
                 rescheduled_by_staff = 1,
                 rescheduled_from_date = ?,
                 rescheduled_at = NOW(),
                 updated_at = NOW()
             WHERE patient_id = ?"
        );
        $upd->execute([$newDate, $fromDateVal, $patientId]);
        try {
            $refNo = trim((string)($patient['reference_no'] ?? ''));
            if ($refNo !== '') {
                $pdo->prepare("UPDATE appointments SET appointment_date = ? WHERE reference_no = ? OR patient_id = ?")->execute([$newDate, $refNo, $patientId]);
            } else {
                $pdo->prepare("UPDATE appointments SET appointment_date = ? WHERE patient_id = ?")->execute([$newDate, $patientId]);
            }
        } catch (\Throwable $ignoreApptResched) {}
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
        $newPatientId = dbNextPatientId($pdo);

        $ins = $pdo->prepare(
            "INSERT INTO patients (
                patient_id, user_id, department, full_name, first_name, last_name, middle_initial,
                gender, birthdate, age, contact_number, email, address,
                with_companion, relationship_to_patient, appointment_recipient,
                chief_complaints, consultation_nature, illness_description,
                existing_medical_conditions, current_medications,
                patient_type, appointment_type, consultation_mode, appointment_date,
                referral_source, status, assigned_doctor, unscheduled_walkin, hrn, specialty, scheduled_time, preferred_time_slot,
                active_medications, next_visit_date, medical_history_diagnosis, medical_history_treatment, medical_history_prescription
            ) SELECT
                ?, user_id, ?, full_name, first_name, last_name, middle_initial,
                gender, birthdate, age, contact_number, email, address,
                with_companion, relationship_to_patient, appointment_recipient,
                chief_complaints, consultation_nature, illness_description,
                existing_medical_conditions, current_medications,
                patient_type, appointment_type, consultation_mode, COALESCE(appointment_date, CURRENT_DATE),
                ?, 'for_approval', NULL, unscheduled_walkin, hrn, specialty, scheduled_time, preferred_time_slot,
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
