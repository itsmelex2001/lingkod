-- Migration: Separate permanent Account Patient ID (PT-YYYY-NNNN) from per-booking Appointment Reference Number (APT-YYYY-NNNN)
-- Run in order on database `scheduling_db`.

-- 1. Add permanent account_patient_id to users
ALTER TABLE users
  ADD COLUMN account_patient_id VARCHAR(20) NULL AFTER patient_id,
  ADD UNIQUE KEY uniq_users_account_patient_id (account_patient_id);

-- 2. Backfill existing patient accounts in users with stable PT-YYYY-NNNN IDs
UPDATE users u
LEFT JOIN (
    SELECT user_id, MIN(YEAR(created_at)) AS first_year
    FROM patients
    WHERE user_id IS NOT NULL
    GROUP BY user_id
) p ON p.user_id = u.id
SET u.account_patient_id = CONCAT(
    'PT-',
    COALESCE(p.first_year, YEAR(u.created_at), YEAR(CURDATE())),
    '-',
    LPAD(u.id, 4, '0')
)
WHERE (u.role = '' OR u.role = 'patient' OR u.patient_id IS NOT NULL)
  AND (u.account_patient_id IS NULL OR u.account_patient_id = '');

-- 3. Add reference_no to patients (booking/visit rows) with UNIQUE index
ALTER TABLE patients
  ADD COLUMN reference_no VARCHAR(20) NULL AFTER patient_id,
  ADD UNIQUE KEY uniq_patients_reference_no (reference_no);

-- 4. Backfill patients.reference_no (e.g. PT-2026-086 -> APT-2026-0086, PT-2026-090 -> APT-2026-0090)
UPDATE patients
SET reference_no = CASE
    WHEN patient_id REGEXP '^PT-[0-9]{4}-[0-9]+$' THEN
        CONCAT(
            'APT-',
            SUBSTRING_INDEX(SUBSTRING_INDEX(patient_id, '-', 2), '-', -1),
            '-',
            LPAD(CAST(SUBSTRING_INDEX(patient_id, '-', -1) AS UNSIGNED), 4, '0')
        )
    ELSE
        CONCAT('APT-', YEAR(created_at), '-', LPAD(id, 4, '0'))
END
WHERE reference_no IS NULL OR reference_no = '';

-- 5. Add reference_no to appointments with UNIQUE index
ALTER TABLE appointments
  ADD COLUMN reference_no VARCHAR(20) NULL AFTER id,
  ADD UNIQUE KEY uniq_appointments_reference_no (reference_no);

-- 6. Backfill appointments.reference_no matching patients.reference_no
UPDATE appointments a
LEFT JOIN patients p ON p.patient_id = a.patient_id
SET a.reference_no = COALESCE(
    NULLIF(p.reference_no, ''),
    CASE
        WHEN a.patient_id REGEXP '^PT-[0-9]{4}-[0-9]+$' THEN
            CONCAT(
                'APT-',
                SUBSTRING_INDEX(SUBSTRING_INDEX(a.patient_id, '-', 2), '-', -1),
                '-',
                LPAD(CAST(SUBSTRING_INDEX(a.patient_id, '-', -1) AS UNSIGNED), 4, '0')
            )
        ELSE
            CONCAT('APT-', YEAR(a.created_at), '-', LPAD(a.id, 4, '0'))
    END
)
WHERE a.reference_no IS NULL OR a.reference_no = '';

