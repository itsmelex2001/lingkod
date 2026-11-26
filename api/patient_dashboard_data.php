<?php
/**
 * api/patient_dashboard_data.php
 * Patient Portal Dashboard & Medications API
 * - GET: Returns patient profile, upcoming/past/cancelled appointments (with diagnosis & previous appointment info),
 *        dynamically extracted diagnosed medications (medicine name, dosage, daily frequency, quantity, doctor, diagnosis),
 *        and live notifications.
 * - POST: Supports password change (`action: 'change_password'`) and profile/preferences updates.
 */

date_default_timezone_set('Asia/Manila');

session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/config/cors.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/patient_otp.php';

// Access guard
if (empty($_SESSION['patient_logged_in']) || (empty($_SESSION['patient_id']) && empty($_SESSION['patient_email']))) {
    http_response_code(401);
    echo json_encode([
        'success'  => false,
        'message'  => 'Unauthorized. Please sign in to the patient portal.',
        'redirect' => '/sdn/pages/patient-login.php'
    ]);
    exit;
}

$patientId    = $_SESSION['patient_id'] ?? '';
$patientEmail = $_SESSION['patient_email'] ?? '';
$userId       = $_SESSION['user_id'] ?? $_SESSION['patient_user_id'] ?? null;

function buildPatientWhereClause($userId, string $patientId, string $patientEmail): array {
    $clauses = [];
    $params  = [];
    if ($userId !== null && $userId !== '') {
        $clauses[] = 'user_id = ?';
        $params[]  = $userId;
    }
    if ($patientId !== '') {
        $clauses[] = 'patient_id = ?';
        $params[]  = $patientId;
    }
    if ($patientEmail !== '') {
        $clauses[] = 'email = ?';
        $params[]  = $patientEmail;
    }
    return [$clauses ? implode(' OR ', $clauses) : '1 = 0', $params];
}

function formatDeptName(string $dept): string {
    $d = strtolower($dept);
    if (str_contains($d, 'psych') || str_contains($d, 'opu')) return 'Psychiatry';
    if (str_contains($d, 'neuro')) return 'Neurology';
    return 'General Medical';
}

function parseDailyScheduleLabel(string $dosageStr): string {
    $s = strtolower($dosageStr);
    if (preg_match('/\bqid\b|4\s*x|four\s*times|q6h/i', $s)) {
        return '4 times per day (Every 6 hours)';
    }
    if (preg_match('/\btid\b|3\s*x|three\s*times|q8h/i', $s)) {
        return '3 times per day (Morning, Noon, Evening)';
    }
    if (preg_match('/\bbid\b|2\s*x|twice/i', $s)) {
        return '2 times per day (Morning & Evening)';
    }
    if (preg_match('/\bod\b|once\s*daily|1\s*x|bedtime|morning/i', $s)) {
        if (str_contains($s, 'bedtime') || str_contains($s, 'night')) {
            return 'Once per day (At bedtime)';
        }
        if (str_contains($s, 'morning') || str_contains($s, 'breakfast')) {
            return 'Once per day (In the morning)';
        }
        return 'Once per day (Daily)';
    }
    if (preg_match('/\bprn\b|as\s*needed/i', $s)) {
        return 'As needed (PRN) per physician instructions';
    }
    return $dosageStr !== '' ? $dosageStr : 'As directed by attending physician';
}

function estimateDaysSupply(string $qtyStr, string $dosageStr): int {
    if (preg_match('/x\s*(\d+)\s*day/i', $dosageStr, $m)) {
        return max(1, (int)$m[1]);
    }
    $qtyNum = 0;
    if (preg_match('/(\d+)/', $qtyStr, $qm)) {
        $qtyNum = (int)$qm[1];
    }
    if ($qtyNum <= 0) return 14;

    $perDay = 1;
    if (preg_match('/\bqid\b|4\s*x|four/i', $dosageStr)) $perDay = 4;
    elseif (preg_match('/\btid\b|3\s*x|three/i', $dosageStr)) $perDay = 3;
    elseif (preg_match('/\bbid\b|2\s*x|twice/i', $dosageStr)) $perDay = 2;

    return max(1, (int)ceil($qtyNum / $perDay));
}

function ensureNotificationStateTable(PDO $pdo): void {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS patient_notification_states (
            id INT AUTO_INCREMENT PRIMARY KEY,
            patient_key VARCHAR(191) NOT NULL,
            notification_id VARCHAR(120) NOT NULL,
            is_read TINYINT(1) NOT NULL DEFAULT 0,
            is_dismissed TINYINT(1) NOT NULL DEFAULT 0,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_patient_notif (patient_key, notification_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
}

function getNotificationPatientKey(?int $userId, string $patientId, string $patientEmail): string {
    if ($userId !== null && $userId > 0) return 'uid:' . $userId;
    if ($patientEmail !== '') return 'email:' . strtolower(trim($patientEmail));
    if ($patientId !== '') return 'pid:' . strtolower(trim($patientId));
    return '';
}

function getNotificationPatientKeys(?int $userId, array $patientIds, array $emails): array {
    $keys = [];
    if ($userId !== null && $userId > 0) {
        $keys[] = 'uid:' . $userId;
    }
    foreach ($emails as $email) {
        $e = strtolower(trim((string)$email));
        if ($e !== '') $keys[] = 'email:' . $e;
    }
    foreach ($patientIds as $pid) {
        $p = strtolower(trim((string)$pid));
        if ($p !== '') $keys[] = 'pid:' . $p;
    }
    return array_values(array_unique($keys));
}

// ── Handle POST actions (Change Password / Notification States) ───────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw = file_get_contents('php://input');
    $body = json_decode($raw, true);
    if (!is_array($body)) $body = $_POST;

    $action = $body['action'] ?? '';

    if ($action === 'change_password') {
        $currentPass = $body['current_password'] ?? '';
        $newPass     = $body['new_password'] ?? '';
        $confirmPass = $body['confirm_password'] ?? '';

        $hasStrongPass = strlen($newPass) >= 8
            && preg_match('/[A-Z]/', $newPass)
            && preg_match('/[a-z]/', $newPass)
            && preg_match('/[0-9]/', $newPass)
            && preg_match('/[^A-Za-z0-9]/', $newPass);
        if (!$hasStrongPass) {
            echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters and include uppercase, lowercase, number, and special character.']);
            exit;
        }
        if ($newPass !== $confirmPass) {
            echo json_encode(['success' => false, 'message' => 'New password and confirmation do not match.']);
            exit;
        }

        try {
            [$whereSql, $whereParams] = buildPatientWhereClause($userId, $patientId, $patientEmail);
            $stmtAcc = $pdo->prepare("
                SELECT id, password 
                FROM patients 
                WHERE ({$whereSql})
                  AND password IS NOT NULL
                ORDER BY id DESC LIMIT 1
            ");
            $stmtAcc->execute($whereParams);
            $acc = $stmtAcc->fetch(PDO::FETCH_ASSOC);

            if ($acc && !empty($acc['password']) && $currentPass !== '') {
                if (!password_verify($currentPass, $acc['password']) && $currentPass !== $acc['password']) {
                    echo json_encode(['success' => false, 'message' => 'Current password is incorrect.']);
                    exit;
                }
            }

            $hashed = password_hash($newPass, PASSWORD_DEFAULT);
            $upd = $pdo->prepare("
                UPDATE patients 
                SET password = ? 
                WHERE {$whereSql}
            ");
            $upd->execute(array_merge([$hashed], $whereParams));

            echo json_encode(['success' => true, 'message' => 'Password updated successfully.']);
            exit;
        } catch (Throwable $e) {
            echo json_encode(['success' => false, 'message' => 'Could not update password.']);
            exit;
        }
    }

    if ($action === 'update_avatar') {
        try {
            $hasCol = (bool)$pdo->query("SHOW COLUMNS FROM patients LIKE 'profile_image'")->fetch();
            if (!$hasCol) {
                $pdo->exec("ALTER TABLE patients ADD COLUMN profile_image VARCHAR(255) NULL");
            }

            [$whereSql, $whereParams] = buildPatientWhereClause($userId, $patientId, $patientEmail);
            $stmtCur = $pdo->prepare("
                SELECT id, profile_image
                FROM patients
                WHERE {$whereSql}
                ORDER BY id DESC LIMIT 1
            ");
            $stmtCur->execute($whereParams);
            $curRow = $stmtCur->fetch(PDO::FETCH_ASSOC);

            $removeImage = !empty($body['remove_image']);
            $imageData   = $body['image_data'] ?? null;
            $finalPath   = $curRow['profile_image'] ?? null;

            if ($removeImage) {
                deleteLocalAvatar($curRow['profile_image'] ?? null);
                $finalPath = null;
            } elseif (!empty($imageData) && is_string($imageData)) {
                $newPath = saveUploadedAvatar($imageData, 'patient', (int)($curRow['id'] ?? 0));
                if ($newPath === null) {
                    echo json_encode(['success' => false, 'message' => 'Invalid image. Use PNG, JPG, or WEBP up to 5MB.']);
                    exit;
                }
                deleteLocalAvatar($curRow['profile_image'] ?? null);
                $finalPath = $newPath;
            }

            $upd = $pdo->prepare("
                UPDATE patients
                SET profile_image = ?
                WHERE {$whereSql}
            ");
            $upd->execute(array_merge([$finalPath], $whereParams));
            if ($userId) {
                try {
                    if ($pdo->query("SHOW COLUMNS FROM users LIKE 'profile_image'")->fetch()) {
                        $pdo->prepare("UPDATE users SET profile_image = ? WHERE id = ?")->execute([$finalPath, $userId]);
                    }
                } catch (Throwable $ignoreUserImg) {}
            }
            $_SESSION['patient_profile_image'] = $finalPath;

            echo json_encode(['success' => true, 'profile_image' => $finalPath, 'message' => 'Profile photo saved.']);
            exit;
        } catch (Throwable $e) {
            echo json_encode(['success' => false, 'message' => 'Could not update profile photo.']);
            exit;
        }
    }

    if ($action === 'toggle_otp') {
        $enabled = !empty($body['enabled']) ? 1 : 0;
        try {
            ensurePatientOtpColumns($pdo);
            [$whereSql, $whereParams] = buildPatientWhereClause($userId, $patientId, $patientEmail);

            if ($enabled === 1) {
                $stmtEmail = $pdo->prepare("SELECT email FROM patients WHERE ({$whereSql}) AND email IS NOT NULL AND email <> '' ORDER BY id DESC LIMIT 1");
                $stmtEmail->execute($whereParams);
                $regEmail = trim((string)($stmtEmail->fetchColumn() ?: ''));
                if ($regEmail === '' || !filter_var($regEmail, FILTER_VALIDATE_EMAIL)) {
                    http_response_code(422);
                    echo json_encode(['success' => false, 'message' => 'A valid registered email is required to enable two-step verification.']);
                    exit;
                }
            }

            $upd = $pdo->prepare("UPDATE patients SET is_otp_enabled = ? WHERE {$whereSql}");
            $upd->execute(array_merge([$enabled], $whereParams));

            echo json_encode([
                'success'        => true,
                'is_otp_enabled' => $enabled === 1,
                'message'        => $enabled === 1
                    ? 'Email two-step verification enabled.'
                    : 'Email two-step verification disabled.'
            ]);
            exit;
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Could not update two-step verification setting.']);
            exit;
        }
    }

    if ($action === 'mark_notifications_read' || $action === 'clear_notifications') {
        $ids = $body['ids'] ?? [];
        if (!is_array($ids)) $ids = [];
        $ids = array_values(array_unique(array_filter(array_map('strval', $ids), fn($id) => $id !== '')));
        $patientKey = getNotificationPatientKey($userId ? (int)$userId : null, (string)$patientId, (string)$patientEmail);

        if ($patientKey === '') {
            echo json_encode(['success' => false, 'message' => 'Missing patient identity.']);
            exit;
        }

        try {
            ensureNotificationStateTable($pdo);
            if (!empty($ids)) {
                $isDismissed = ($action === 'clear_notifications') ? 1 : 0;
                $stmt = $pdo->prepare("
                    INSERT INTO patient_notification_states (patient_key, notification_id, is_read, is_dismissed)
                    VALUES (:pkey, :nid, 1, :dismissed)
                    ON DUPLICATE KEY UPDATE
                        is_read = 1,
                        is_dismissed = GREATEST(is_dismissed, VALUES(is_dismissed))
                ");
                foreach ($ids as $nid) {
                    $stmt->execute([
                        ':pkey'      => $patientKey,
                        ':nid'       => substr($nid, 0, 120),
                        ':dismissed' => $isDismissed
                    ]);
                }
            }
            echo json_encode(['success' => true]);
            exit;
        } catch (Throwable $e) {
            echo json_encode(['success' => false, 'message' => 'Could not save notification state.']);
            exit;
        }
    }

    echo json_encode(['success' => false, 'message' => 'Unsupported action.']);
    exit;
}

try {
    // 0. Auto-link appointments for patient across their stable identifiers (user_id / email / patient_id)
    if ($userId) {
        $linkClauses = [];
        $linkParams  = [$userId];
        if ($patientEmail !== '') {
            $linkClauses[] = "email = ?";
            $linkParams[]  = $patientEmail;
        }
        if ($patientId !== '') {
            $linkClauses[] = "patient_id = ?";
            $linkParams[]  = $patientId;
        }
        if (!empty($linkClauses)) {
            try {
                $linkSql = "UPDATE patients SET user_id = ? WHERE user_id IS NULL AND (" . implode(' OR ', $linkClauses) . ")";
                $pdo->prepare($linkSql)->execute($linkParams);
            } catch (Throwable $e) {}
        }
    }

    // 1. Canonical account owner profile — anchored to users.patient_id or earliest non-dependent account row
    ensurePatientOtpColumns($pdo);
    $hasPatientAvatarCol = false;
    try {
        $hasPatientAvatarCol = (bool)$pdo->query("SHOW COLUMNS FROM patients LIKE 'profile_image'")->fetch();
    } catch (Throwable $ignoreCol) {}
    $avatarSelect = $hasPatientAvatarCol ? ', profile_image' : '';
    $otpSelect = ', COALESCE(is_otp_enabled, 0) AS is_otp_enabled';

    $profile = null;
    if ($userId) {
        try {
            $uRowStmt = $pdo->prepare("SELECT patient_id FROM users WHERE id = ? LIMIT 1");
            $uRowStmt->execute([$userId]);
            $ownerRowId = $uRowStmt->fetchColumn();
            if ($ownerRowId && is_numeric($ownerRowId)) {
                $ownerStmt = $pdo->prepare("
                    SELECT id, patient_id, hrn, full_name, gender, age, birthdate, email, contact_number, address{$avatarSelect}{$otpSelect}
                    FROM patients
                    WHERE id = ?
                    LIMIT 1
                ");
                $ownerStmt->execute([(int)$ownerRowId]);
                $profile = $ownerStmt->fetch(PDO::FETCH_ASSOC) ?: null;
            }
        } catch (Throwable $ignoreOwnerRow) {}
    }

    $profileClauses = [];
    $profileParams  = [];
    if ($userId) {
        $profileClauses[] = "user_id = ?";
        $profileParams[]  = $userId;
    }
    if ($patientId !== '') {
        $profileClauses[] = "patient_id = ?";
        $profileParams[]  = $patientId;
    }
    if ($patientEmail !== '') {
        $profileClauses[] = "email = ?";
        $profileParams[]  = $patientEmail;
    }

    $profileSql = !empty($profileClauses) ? implode(' OR ', $profileClauses) : "1=0";
    if (!$profile) {
        $stmtProfile = $pdo->prepare("
            SELECT id, patient_id, hrn, full_name, gender, age, birthdate, email, contact_number, address{$avatarSelect}{$otpSelect}
            FROM patients
            WHERE ($profileSql)
              AND COALESCE(appointment_recipient, 'Patient (Me)') NOT IN ('Other Patient', 'Someone Else / Dependent')
            ORDER BY (password IS NOT NULL AND password <> '') DESC, id ASC
            LIMIT 1
        ");
        $stmtProfile->execute($profileParams);
        $profile = $stmtProfile->fetch(PDO::FETCH_ASSOC);
    }
    if (!$profile) {
        $stmtFallback = $pdo->prepare("
            SELECT id, patient_id, hrn, full_name, gender, age, birthdate, email, contact_number, address{$avatarSelect}{$otpSelect}
            FROM patients
            WHERE ($profileSql)
            ORDER BY id ASC
            LIMIT 1
        ");
        $stmtFallback->execute($profileParams);
        $profile = $stmtFallback->fetch(PDO::FETCH_ASSOC);
    }

    if (!$profile) {
        $profile = [
            'patient_id'     => $patientId ?: 'PT-2026-001',
            'hrn'            => 'HRN-' . substr($patientId ?: 'PT-2026-001', 3),
            'full_name'      => $_SESSION['patient_name'] ?? 'Patient',
            'email'          => $patientEmail,
            'contact_number' => '',
            'gender'         => '',
            'birthdate'      => '',
            'address'        => '',
            'profile_image'  => null
        ];
    }

    // Resolve persisted profile image even if a newer booking row has NULL profile_image
    $resolvedAvatar = !empty($profile['profile_image']) ? $profile['profile_image'] : null;
    if ($resolvedAvatar === null && $hasPatientAvatarCol && !empty($profileClauses)) {
        try {
            $stmtAvatar = $pdo->prepare("
                SELECT profile_image
                FROM patients
                WHERE ($profileSql) AND profile_image IS NOT NULL AND profile_image <> ''
                ORDER BY id DESC
                LIMIT 1
            ");
            $stmtAvatar->execute($profileParams);
            $foundAvatar = $stmtAvatar->fetchColumn();
            if (is_string($foundAvatar) && trim($foundAvatar) !== '') {
                $resolvedAvatar = trim($foundAvatar);
            }
        } catch (Throwable $ignoreAvatarLookup) {}
    }
    if ($resolvedAvatar === null && $userId) {
        try {
            if ($pdo->query("SHOW COLUMNS FROM users LIKE 'profile_image'")->fetch()) {
                $uImgStmt = $pdo->prepare("SELECT profile_image FROM users WHERE id = ? LIMIT 1");
                $uImgStmt->execute([$userId]);
                $uImg = $uImgStmt->fetchColumn();
                if (is_string($uImg) && trim($uImg) !== '') {
                    $resolvedAvatar = trim($uImg);
                }
            }
        } catch (Throwable $ignoreUserAvatar) {}
    }
    if ($resolvedAvatar === null && !empty($_SESSION['patient_profile_image'])) {
        $resolvedAvatar = $_SESSION['patient_profile_image'];
    }
    $profile['profile_image'] = $resolvedAvatar;
    $_SESSION['patient_profile_image'] = $resolvedAvatar;

    // Resolve permanent account_patient_id from users table so header ID never changes across bookings
    $accountPatientId = '';
    if ($userId) {
        try {
            if ($pdo->query("SHOW COLUMNS FROM users LIKE 'account_patient_id'")->fetch()) {
                $apStmt = $pdo->prepare("SELECT account_patient_id FROM users WHERE id = ? LIMIT 1");
                $apStmt->execute([$userId]);
                $accountPatientId = trim((string)($apStmt->fetchColumn() ?: ''));
            }
        } catch (Throwable $ignoreAcct) {}
    }

    if ($accountPatientId !== '') {
        $profile['patient_id'] = $accountPatientId;
        $_SESSION['patient_id'] = $accountPatientId;
    } elseif (!empty($profile['patient_id'])) {
        $_SESSION['patient_id'] = $profile['patient_id'];
    }
    if (!empty($profile['full_name'])) {
        $_SESSION['patient_name'] = $profile['full_name'];
    }

    // 2. Appointments query - fetch all bookings linked to this patient
    $apptClauses = [];
    $apptParams  = [];

    if ($userId) {
        $apptClauses[] = "user_id = ?";
        $apptParams[]  = $userId;
    }
    if (!empty($profile['patient_id'])) {
        $apptClauses[] = "patient_id = ?";
        $apptParams[]  = $profile['patient_id'];
    }
    if ($patientId !== '' && $patientId !== ($profile['patient_id'] ?? '')) {
        $apptClauses[] = "patient_id = ?";
        $apptParams[]  = $patientId;
    }
    if (!empty($profile['email'])) {
        $apptClauses[] = "email = ?";
        $apptParams[]  = $profile['email'];
    } elseif ($patientEmail !== '') {
        $apptClauses[] = "email = ?";
        $apptParams[]  = $patientEmail;
    }
    if (!empty($profile['hrn'])) {
        $apptClauses[] = "hrn = ?";
        $apptParams[]  = $profile['hrn'];
    }
    if (!empty($profile['full_name'])) {
        $apptClauses[] = "LOWER(TRIM(full_name)) = LOWER(TRIM(?))";
        $apptParams[]  = $profile['full_name'];
    } elseif (!empty($_SESSION['patient_name'])) {
        $apptClauses[] = "LOWER(TRIM(full_name)) = LOWER(TRIM(?))";
        $apptParams[]  = $_SESSION['patient_name'];
    }
    if (!empty($profile['contact_number'])) {
        $cleanPhone = preg_replace('/[^0-9]/', '', $profile['contact_number']);
        if (strlen($cleanPhone) >= 7) {
            $apptClauses[] = "REPLACE(REPLACE(REPLACE(contact_number, '+', ''), ' ', ''), '-', '') LIKE ?";
            $apptParams[]  = '%' . substr($cleanPhone, -9);
        }
    }

    $apptSql = !empty($apptClauses) ? implode(' OR ', $apptClauses) : "1=0";
    $stmtAppts = $pdo->prepare("
        SELECT 
            id,
            patient_id,
            reference_no,
            hrn,
            user_id,
            full_name,
            patient_type,
            receiver,
            department,
            appointment_date,
            scheduled_time,
            preferred_time_slot,
            appointment_recipient,
            relationship_to_patient,
            status,
            assigned_doctor,
            consultation_mode,
            appointment_type,
            chief_complaints,
            consultation_nature,
            existing_medical_conditions,
            current_medications,
            referral_source,
            medical_history_diagnosis,
            medical_history_treatment,
            medical_history_prescription,
            active_medications,
            medical_history_date,
            medical_history_recorded_by,
            next_visit_date,
            rescheduled_by_staff,
            rescheduled_from_date,
            rescheduled_at,
            created_at,
            updated_at
        FROM patients
        WHERE ($apptSql)
          AND (
            appointment_date IS NOT NULL
            OR (department IS NOT NULL AND department <> '')
            OR (chief_complaints IS NOT NULL AND chief_complaints <> '')
          )
        ORDER BY id ASC
    ");
    $stmtAppts->execute($apptParams);
    $chronologicalAppts = $stmtAppts->fetchAll(PDO::FETCH_ASSOC);

    // Attach previous appointment info to each booking in chronological sequence
    $prevApptMeta = null;
    foreach ($chronologicalAppts as &$cRow) {
        $cRow['_prev_date'] = $prevApptMeta ? $prevApptMeta['date'] : null;
        $cRow['_prev_time'] = $prevApptMeta ? $prevApptMeta['time'] : null;
        $cRow['_prev_dept'] = $prevApptMeta ? $prevApptMeta['dept'] : null;
        $cRow['_prev_id']   = $prevApptMeta ? $prevApptMeta['patient_id'] : null;

        $rDate = !empty($cRow['appointment_date']) ? date('M j, Y', strtotime($cRow['appointment_date'])) : date('M j, Y', strtotime($cRow['created_at']));
        $rTime = !empty($cRow['scheduled_time']) ? date('g:i A', strtotime($cRow['scheduled_time'])) : '8:00 AM';
        $prevApptMeta = [
            'date'       => $rDate,
            'time'       => $rTime,
            'dept'       => formatDeptName($cRow['department'] ?? ''),
            'patient_id' => $cRow['patient_id']
        ];
    }
    unset($cRow);

    // Sort appointments: upcoming first by date ASC, then past by date DESC
    $appointments = $chronologicalAppts;
    usort($appointments, function($a, $b) {
        $today = date('Y-m-d');
        $da = $a['appointment_date'] ?: $today;
        $db = $b['appointment_date'] ?: $today;
        $aFuture = $da >= $today ? 0 : 1;
        $bFuture = $db >= $today ? 0 : 1;
        if ($aFuture !== $bFuture) return $aFuture <=> $bFuture;
        if ($aFuture === 0) return strcmp($da, $db) ?: ($b['id'] <=> $a['id']);
        return strcmp($db, $da) ?: ($b['id'] <=> $a['id']);
    });

    $now           = new DateTime('now', new DateTimeZone('Asia/Manila'));
    $today         = $now->format('Y-m-d');
    $upcoming      = [];
    $past          = [];
    $cancelled     = [];
    $rescheduled   = [];
    $medications   = [];
    $notifications = [];

    foreach ($appointments as $appt) {
        $statusLower = strtolower(trim($appt['status'] ?? 'pending'));
        $apptDate    = $appt['appointment_date'] ?: substr($appt['created_at'] ?? $today, 0, 10);
        $timeIso     = !empty($appt['scheduled_time']) ? $appt['scheduled_time'] : '08:00:00';
        $deptLabel   = formatDeptName($appt['department'] ?? '');
        $rawDoc      = trim($appt['assigned_doctor'] ?? '');
        $doctor      = ($rawDoc !== '' && strtolower($rawDoc) !== 'none')
            ? (str_starts_with($rawDoc, 'Dr.') ? $rawDoc : 'Dr. ' . $rawDoc)
            : 'Attending Physician';
        $timeLabel   = !empty($appt['preferred_time_slot']) ? $appt['preferred_time_slot'] : date('g:i A', strtotime($timeIso));

        $apptDateTime = new DateTime($apptDate . ' ' . $timeIso, new DateTimeZone('Asia/Manila'));
        $isPast = $apptDateTime < $now;

        $isExplicitCancelled = in_array($statusLower, ['cancelled', 'rejected', 'declined'], true);
        $isExplicitMissed    = in_array($statusLower, ['no-show', 'no_show', 'missed'], true);
        $isExplicitCompleted = ($statusLower === 'completed');
        $isRescheduledByStaff = !empty($appt['rescheduled_by_staff']) || ($statusLower === 'rescheduled');
        $reschedFromFormatted = !empty($appt['rescheduled_from_date']) ? date('M j, Y', strtotime($appt['rescheduled_from_date'])) : null;

        $natureLower   = strtolower(trim((string)($appt['consultation_nature'] ?? '')));
        $apptTypeLower = strtolower(trim((string)($appt['appointment_type'] ?? '')));
        $patTypeLower  = strtolower(trim((string)($appt['patient_type'] ?? '')));
        $isFollowUp = str_contains($natureLower, 'follow')
            || str_contains($apptTypeLower, 'follow')
            || in_array($patTypeLower, ['returning', 'old', 'follow-up', 'follow up'], true)
            || (!empty($appt['_prev_date']) && !$isOtherPatient);
        $visitType = $isFollowUp ? 'Follow-up' : 'Initial';

        // Resolve display status cleanly
        if ($isExplicitCancelled) {
            $displayStatus = 'Cancelled';
        } elseif ($isExplicitCompleted) {
            $displayStatus = 'Completed';
        } elseif ($isExplicitMissed) {
            $displayStatus = 'Missed';
        } elseif ($isPast) {
            // Past appointment that was never concluded
            $displayStatus = 'Missed';
        } elseif ($statusLower === 'ongoing' || $statusLower === 'in_consultation') {
            // Live window today
            $diffHours = ($now->getTimestamp() - $apptDateTime->getTimestamp()) / 3600;
            if ($apptDate === $today && $diffHours >= 0 && $diffHours <= 3) {
                $displayStatus = 'In Consultation';
            } else {
                $displayStatus = 'Confirmed';
            }
        } elseif ($statusLower === 'arrived') {
            $displayStatus = 'Checked In';
        } elseif (in_array($statusLower, ['approved', 'confirmed'], true)) {
            $displayStatus = 'Confirmed';
        } elseif (in_array($statusLower, ['pending', 'for_approval', 'for approval', 'for_processing'], true)) {
            $displayStatus = 'For approval';
        } else {
            $displayStatus = 'Confirmed';
        }

        $diagText = trim($appt['medical_history_diagnosis'] ?? '');
        $treatText = trim($appt['medical_history_treatment'] ?? '');
        $rxText = trim($appt['medical_history_prescription'] ?? '');

        $refNo = !empty($appt['reference_no']) ? $appt['reference_no'] : ($appt['patient_id'] ?: ('APT-' . $appt['id']));
        $recipientRaw = trim((string)($appt['appointment_recipient'] ?? 'Patient (Me)'));
        $apptFullName = trim((string)($appt['full_name'] ?? ''));
        $ownerFullName = trim((string)($profile['full_name'] ?? ''));
        $isOtherPatient = in_array(strtolower($recipientRaw), ['other patient', 'someone else / dependent'], true)
            || ($apptFullName !== '' && $ownerFullName !== '' && strcasecmp($apptFullName, $ownerFullName) !== 0);
        $resolvedPatientName = $apptFullName !== '' ? $apptFullName : $ownerFullName;
        $forWhoSuffix = ($isOtherPatient && $resolvedPatientName !== '') ? " for {$resolvedPatientName}" : '';

        $item = [
            'id'                          => (int)$appt['id'],
            'patient_id'                  => trim((string)($appt['patient_id'] ?? '')),
            'reference_no'                => $refNo,
            'patient_code'                => $refNo,
            'patient_name'                => $resolvedPatientName,
            'patient_type'                => $appt['patient_type'] ?? 'New',
            'encoded_by'                  => trim((string)($appt['medical_history_recorded_by'] ?? '')) ?: (trim((string)($appt['receiver'] ?? '')) ?: 'Online Portal'),
            'consult_type'                => $appt['consultation_nature'] ?: ((empty($appt['consultation_mode']) || strcasecmp((string)$appt['consultation_mode'], 'In-Person') === 0) ? 'Face-to-Face' : $appt['consultation_mode']),
            'remarks'                     => $appt['chief_complaints'] ?? '',
            'attended_date'               => !empty($appt['medical_history_date']) ? date('F j, Y g:i A', strtotime($appt['medical_history_date'])) : date('F j, Y', strtotime($apptDate)),
            'date'                        => date('M j, Y', strtotime($apptDate)),
            'date_raw'                    => $apptDate,
            'date_iso'                    => $apptDate,
            'month'                       => strtoupper(date('M', strtotime($apptDate))),
            'day'                         => date('j', strtotime($apptDate)),
            'time'                        => $timeLabel,
            'time_label'                  => $timeLabel,
            'time_iso'                    => $timeIso,
            'preferred_time_slot'         => $appt['preferred_time_slot'] ?? null,
            'appointment_recipient'       => $isOtherPatient ? 'Other Patient' : $recipientRaw,
            'is_other_patient'            => $isOtherPatient,
            'relationship_to_patient'     => !empty($appt['relationship_to_patient']) ? trim((string)$appt['relationship_to_patient']) : null,
            'existing_medical_conditions' => $appt['existing_medical_conditions'] ?? null,
            'current_medications'         => $appt['current_medications'] ?? null,
            'referral_source'             => $appt['referral_source'] ?? null,
            'datetime_iso'                => $apptDateTime->format(DateTime::ATOM),
            'title'                     => $deptLabel . ' Consultation',
            'detail'                    => $doctor . ' • ' . $timeLabel,
            'department'                => $deptLabel,
            'doctor'                    => $doctor,
            'mode'                      => (empty($appt['consultation_mode']) || strcasecmp((string)$appt['consultation_mode'], 'In-Person') === 0) ? 'Face-to-Face' : $appt['consultation_mode'],
            'consult_mode'              => (empty($appt['consultation_mode']) || strcasecmp((string)$appt['consultation_mode'], 'In-Person') === 0) ? 'Face-to-Face' : $appt['consultation_mode'],
            'chief_complaints'          => $appt['chief_complaints'] ?: 'General consultation',
            'diagnosis'                 => $diagText,
            'treatment'                 => $treatText,
            'prescription'              => $rxText,
            'next_visit_date'           => $appt['next_visit_date'] ?? null,
            'follow_up_notes'           => $appt['follow_up_notes'] ?? null,
            'previous_appointment_date' => $appt['_prev_date'] ?? null,
            'previous_appointment_time' => $appt['_prev_time'] ?? null,
            'previous_department'       => $appt['_prev_dept'] ?? null,
            'previous_patient_id'       => $appt['_prev_id'] ?? null,
            'status'                    => $displayStatus,
            'status_raw'                => $statusLower,
            'visit_type'                => $visitType,
            'is_rescheduled'            => $isRescheduledByStaff,
            'rescheduled_by_staff'      => $isRescheduledByStaff,
            'rescheduled_from_date'     => $reschedFromFormatted,
            'can_reschedule'            => (!$isPast && !$isExplicitCancelled && $displayStatus === 'For approval')
        ];

        if ($isRescheduledByStaff && !$isExplicitCancelled && !$isExplicitMissed) {
            $rescheduled[] = $item;
        }

        if ($isExplicitCancelled || $isExplicitMissed) {
            $cancelled[] = $item;
        } elseif ($isExplicitCompleted || $isPast) {
            $past[] = $item;
        } else {
            $upcoming[] = $item;
        }

        // Build notifications strictly from appointment & appointment-progress events
        if ($isRescheduledByStaff && !$isExplicitCancelled && !$isPast) {
            $fromNote = $reschedFromFormatted ? " from {$reschedFromFormatted}" : '';
            $notifications[] = [
                'id'    => 'notif-resched-' . $appt['id'],
                'title' => $deptLabel . ' Visit Rescheduled',
                'text'  => "Hospital staff moved the visit{$forWhoSuffix}{$fromNote} to " . date('M j, Y', strtotime($apptDate)) . " at {$timeLabel} ({$item['patient_code']}).",
                'time'  => 'Rescheduled',
                'view'  => 'appointments',
                'warn'  => true
            ];
        } elseif ($displayStatus === 'In Consultation') {
            $notifications[] = [
                'id'    => 'notif-ongoing-' . $appt['id'],
                'title' => $deptLabel . ' Consultation In Progress',
                'text'  => "Your visit{$forWhoSuffix} with {$doctor} ({$item['patient_code']}) is currently in consultation.",
                'time'  => 'In Progress',
                'view'  => 'appointments',
                'warn'  => false
            ];
        } elseif ($displayStatus === 'Checked In') {
            $notifications[] = [
                'id'    => 'notif-arrived-' . $appt['id'],
                'title' => $deptLabel . ' Visit Checked In',
                'text'  => "Checked in{$forWhoSuffix} for your " . date('M j, Y', strtotime($apptDate)) . " visit ({$item['patient_code']}). Please wait to be called.",
                'time'  => 'Checked In',
                'view'  => 'appointments',
                'warn'  => false
            ];
        } elseif ($displayStatus === 'Confirmed') {
            $notifications[] = [
                'id'    => 'notif-confirm-' . $appt['id'],
                'title' => $deptLabel . ' Visit Confirmed',
                'text'  => "{$doctor}{$forWhoSuffix} on " . date('M j, Y', strtotime($apptDate)) . " at {$timeLabel} ({$item['patient_code']}).",
                'time'  => 'Active Schedule',
                'view'  => 'appointments',
                'warn'  => false
            ];
        } elseif ($displayStatus === 'For approval') {
            $notifications[] = [
                'id'    => 'notif-wait-' . $appt['id'],
                'title' => 'Appointment Awaiting Approval',
                'text'  => "{$deptLabel} visit{$forWhoSuffix} on " . date('M j, Y', strtotime($apptDate)) . " at {$timeLabel} ({$item['patient_code']}) is pending staff verification.",
                'time'  => 'Pending Review',
                'view'  => 'appointments',
                'warn'  => true
            ];
        } elseif ($isExplicitCompleted) {
            $notifications[] = [
                'id'    => 'notif-done-' . $appt['id'],
                'title' => $deptLabel . ' Visit Completed',
                'text'  => "Your consultation{$forWhoSuffix} with {$doctor} on " . date('M j, Y', strtotime($apptDate)) . " ({$item['patient_code']}) has been completed.",
                'time'  => date('M j, Y', strtotime($apptDate)),
                'view'  => 'appointments',
                'warn'  => false
            ];
        } elseif ($isExplicitCancelled) {
            $notifications[] = [
                'id'    => 'notif-cancel-' . $appt['id'],
                'title' => $deptLabel . ' Visit Cancelled',
                'text'  => "Your {$deptLabel} appointment{$forWhoSuffix} on " . date('M j, Y', strtotime($apptDate)) . " ({$item['patient_code']}) was cancelled.",
                'time'  => 'Cancelled',
                'view'  => 'appointments',
                'warn'  => true
            ];
        }

        // Extract diagnosed medications from completed/diagnosed records
        $rawActiveMeds = trim($appt['active_medications'] ?? '');
        if ($rawActiveMeds !== '' || $rxText !== '') {
            $medName   = '';
            $medDosage = '';
            $medQty    = '';
            $medDiag   = $diagText ?: 'Clinical Consultation';
            $medTreat  = $treatText;

            if ($rawActiveMeds !== '' && str_starts_with($rawActiveMeds, '{')) {
                $decoded = json_decode($rawActiveMeds, true);
                if (is_array($decoded)) {
                    $medName   = trim($decoded['medicine_name'] ?? '');
                    $medDosage = trim($decoded['dosage'] ?? '');
                    $medQty    = trim($decoded['quantity'] ?? '');
                    if (!empty($decoded['diagnosis'])) $medDiag = trim($decoded['diagnosis']);
                    if (!empty($decoded['treatment'])) $medTreat = trim($decoded['treatment']);
                }
            }

            if ($medName === '' && $rxText !== '') {
                $lines = preg_split('/\r\n|\r|\n/', $rxText);
                $firstLine = trim($lines[0] ?? '');
                $firstLine = preg_replace('/^Rx:\s*/i', '', $firstLine);
                if (preg_match('/^(.*?)\s+#([0-9].*)$/', $firstLine, $m)) {
                    $medName = trim($m[1]);
                    $medQty  = trim($m[2]);
                } else {
                    $medName = $firstLine;
                }
                foreach ($lines as $ln) {
                    if (preg_match('/^Sig:\s*(.+)$/i', trim($ln), $sm)) {
                        $medDosage = trim($sm[1]);
                    }
                }
                if ($medDosage === '' && count($lines) > 1) {
                    $medDosage = trim($lines[1]);
                }
            }

            if ($medName === '' && $rawActiveMeds !== '' && !str_starts_with($rawActiveMeds, '{')) {
                $medName = $rawActiveMeds;
            }

            if ($medName !== '' && strtolower($medName) !== 'none' && strtolower($medName) !== 'n/a') {
                $dailyFreq = parseDailyScheduleLabel($medDosage);
                $totalDays = estimateDaysSupply($medQty, $medDosage);
                $prescribedDateRaw = $appt['medical_history_date'] ?: ($appt['updated_at'] ?: $apptDate);
                $elapsedDays = max(0, (int)floor((time() - strtotime($prescribedDateRaw)) / 86400));
                $daysLeft = max(1, $totalDays - $elapsedDays);
                $pct = min(100, max(10, (int)round(($daysLeft / max(1, $totalDays)) * 100)));

                $medications[] = [
                    'id'              => (int)$appt['id'],
                    'patient_code'    => $item['patient_code'],
                    'name'            => $medName,
                    'dosage'          => $medDosage ?: 'Take as directed by physician',
                    'frequency'       => $dailyFreq,
                    'quantity'        => $medQty ?: 'As prescribed',
                    'amount'          => $medQty ?: 'Prescribed course',
                    'diagnosis'       => $medDiag,
                    'treatment'       => $medTreat,
                    'prescription'    => $rxText,
                    'doctor'          => "{$doctor} ({$deptLabel})",
                    'department'      => $deptLabel,
                    'prescribed_date' => date('M j, Y', strtotime($prescribedDateRaw)),
                    'days_left'       => $daysLeft,
                    'total_days'      => $totalDays,
                    'pct'             => $pct,
                    'status'          => 'Active'
                ];
            }
        }
    }

    // Sort upcoming so nearest future visit is at index 0 (Next Appointment)
    usort($upcoming, function($a, $b) {
        $da = ($a['date_iso'] ?? '') . ' ' . ($a['time_iso'] ?? '08:00:00');
        $db = ($b['date_iso'] ?? '') . ' ' . ($b['time_iso'] ?? '08:00:00');
        return strcmp($da, $db) ?: ($a['id'] <=> $b['id']);
    });

    // Sort past so most recent visit is at index 0
    usort($past, function($a, $b) {
        $da = ($a['date_iso'] ?? '') . ' ' . ($a['time_iso'] ?? '08:00:00');
        $db = ($b['date_iso'] ?? '') . ' ' . ($b['time_iso'] ?? '08:00:00');
        return strcmp($db, $da) ?: ($b['id'] <=> $a['id']);
    });

    // Sort cancelled by date DESC
    usort($cancelled, function($a, $b) {
        $da = ($a['date_iso'] ?? '') . ' ' . ($a['time_iso'] ?? '08:00:00');
        $db = ($b['date_iso'] ?? '') . ' ' . ($b['time_iso'] ?? '08:00:00');
        return strcmp($db, $da) ?: ($b['id'] <=> $a['id']);
    });

    $departments = [
        [
            'id'     => 'general_medicine',
            'name'   => 'General Medical',
            'desc'   => 'Primary care, internal medicine & outpatient check-ups',
            'action' => 'Book General Medical'
        ],
        [
            'id'     => 'neurology',
            'name'   => 'Neurology',
            'desc'   => 'Brain, nervous system, seizure & headache specialists',
            'action' => 'Book Neurology'
        ],
        [
            'id'     => 'psychiatry',
            'name'   => 'Psychiatry',
            'desc'   => 'Mental wellness, counseling & behavioral health',
            'action' => 'Book Psychiatry'
        ]
    ];

    $notifPatientKeys = getNotificationPatientKeys(
        $userId ? (int)$userId : null,
        [(string)$patientId, (string)($profile['patient_id'] ?? '')],
        [(string)$patientEmail, (string)($profile['email'] ?? '')]
    );
    if (!empty($notifPatientKeys) && !empty($notifications)) {
        try {
            ensureNotificationStateTable($pdo);
            $placeholders = implode(',', array_fill(0, count($notifPatientKeys), '?'));
            $stmtNotif = $pdo->prepare("
                SELECT notification_id, MAX(is_read) AS is_read, MAX(is_dismissed) AS is_dismissed
                FROM patient_notification_states
                WHERE patient_key IN ($placeholders)
                GROUP BY notification_id
            ");
            $stmtNotif->execute($notifPatientKeys);
            $notifStateMap = [];
            foreach ($stmtNotif->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $notifStateMap[$row['notification_id']] = $row;
            }
            $filteredNotifs = [];
            foreach ($notifications as $n) {
                $nid = (string)($n['id'] ?? '');
                $state = $notifStateMap[$nid] ?? null;
                if ($state && !empty($state['is_dismissed'])) {
                    continue;
                }
                $n['is_read'] = ($state && !empty($state['is_read']));
                $filteredNotifs[] = $n;
            }
            $notifications = $filteredNotifs;
        } catch (Throwable $e) {
            // Fall back to unfiltered notifications if state lookup fails
        }
    }

    echo json_encode([
        'success'       => true,
        'profile'       => [
            'patient_id'     => $profile['patient_id'],
            'hrn'            => $profile['hrn'] ?? ('HRN-' . substr($profile['patient_id'] ?? 'PT-2026-001', 3)),
            'full_name'      => $profile['full_name'],
            'email'          => $profile['email'],
            'contact_number' => $profile['contact_number'] ?? '',
            'gender'         => $profile['gender'] ?? '',
            'birthdate'      => $profile['birthdate'] ?? '',
            'address'        => $profile['address'] ?? '',
            'profile_image'  => $profile['profile_image'] ?? ($_SESSION['patient_profile_image'] ?? null),
            'is_otp_enabled' => !empty($profile['is_otp_enabled']),
            'total_visits'   => count($chronologicalAppts)
        ],
        'stats'         => [
            'upcoming_count'    => count($upcoming),
            'past_count'        => count($past),
            'cancelled_count'   => count($cancelled),
            'rescheduled_count' => count($rescheduled),
            'active_meds'       => count($medications),
            'total_visits'      => count($chronologicalAppts)
        ],
        'departments'   => $departments,
        'upcoming'      => $upcoming,
        'past'          => $past,
        'cancelled'     => $cancelled,
        'rescheduled'   => $rescheduled,
        'medications'   => $medications,
        'notifications' => array_slice($notifications, 0, 8)
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    error_log("Patient dashboard API error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Unable to retrieve appointment schedules.'
    ]);
}
