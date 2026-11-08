-- =============================================================================
-- Migration: Remove Slots Constraints & Ensure Account-Linked Appointments
-- Database: scheduling_db
-- =============================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- 1. Remove total_slots and booked_slots from doctors table if present
SET @exist_col := (SELECT COUNT(*) FROM information_schema.COLUMNS 
                   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'doctors' AND COLUMN_NAME = 'total_slots');
SET @query := IF(@exist_col > 0, 'ALTER TABLE `doctors` DROP COLUMN `total_slots`', 'SELECT 1');
PREPARE stmt FROM @query; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exist_col := (SELECT COUNT(*) FROM information_schema.COLUMNS 
                   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'doctors' AND COLUMN_NAME = 'booked_slots');
SET @query := IF(@exist_col > 0, 'ALTER TABLE `doctors` DROP COLUMN `booked_slots`', 'SELECT 1');
PREPARE stmt FROM @query; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 2. Modify doctors status to VARCHAR so it does not restrict to 'booked' or 'available'
ALTER TABLE `doctors` MODIFY COLUMN `status` VARCHAR(30) NOT NULL DEFAULT 'active';

-- Recreate views without slot columns
CREATE OR REPLACE VIEW `v_doctor_summary` AS
SELECT
  d.id, d.name, d.specialization, d.department,
  d.schedule_start, d.schedule_end,
  d.status, d.schedule_date, d.created_at
FROM `doctors` d
ORDER BY d.department, d.name ASC;

CREATE OR REPLACE VIEW `v_doctor_availability` AS
SELECT
  d.department,
  COUNT(*) AS total_doctors,
  SUM(CASE WHEN d.status = 'available' OR d.status = 'active' THEN 1 ELSE 0 END) AS active_doctors,
  SUM(CASE WHEN d.status = 'leave' THEN 1 ELSE 0 END) AS on_leave
FROM `doctors` d
GROUP BY d.department
ORDER BY d.department;

-- 3. Ensure user_id column and index on patients table
SET @exist_col := (SELECT COUNT(*) FROM information_schema.COLUMNS 
                   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'patients' AND COLUMN_NAME = 'user_id');
SET @query := IF(@exist_col = 0, 'ALTER TABLE `patients` ADD COLUMN `user_id` INT NULL AFTER `id`, ADD INDEX `idx_patients_user_id` (`user_id`)', 'SELECT 1');
PREPARE stmt FROM @query; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 4. Cross-link any existing appointments where user_id is null to the registered user account
UPDATE `patients` p
JOIN `users` u ON (u.patient_id = p.id OR u.username = p.patient_id OR (p.email IS NOT NULL AND p.email != '' AND u.email = p.email))
SET p.user_id = u.id
WHERE p.user_id IS NULL;

SET FOREIGN_KEY_CHECKS = 1;
