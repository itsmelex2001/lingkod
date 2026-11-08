-- =============================================================================
-- Migration: Patient Portal Authentication and Security Architecture
-- Database: scheduling_db
-- =============================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- 1. Extend users table role enum and add email/patient_id columns
ALTER TABLE `users`
  MODIFY COLUMN `role` ENUM('admin', 'staff', 'doctor', 'nurse', 'patient') NOT NULL DEFAULT 'staff';

-- Safely add email to users if not existing
SET @exist_col := (SELECT COUNT(*) FROM information_schema.COLUMNS 
                   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'email');
SET @query := IF(@exist_col = 0, 'ALTER TABLE `users` ADD COLUMN `email` VARCHAR(120) NULL UNIQUE AFTER `username`', 'SELECT 1');
PREPARE stmt FROM @query; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Safely add patient_id to users if not existing
SET @exist_col := (SELECT COUNT(*) FROM information_schema.COLUMNS 
                   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'patient_id');
SET @query := IF(@exist_col = 0, 'ALTER TABLE `users` ADD COLUMN `patient_id` INT NULL AFTER `department`, ADD INDEX `idx_users_patient_id` (`patient_id`)', 'SELECT 1');
PREPARE stmt FROM @query; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 2. Extend patients table with authentication columns
SET @exist_col := (SELECT COUNT(*) FROM information_schema.COLUMNS 
                   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'patients' AND COLUMN_NAME = 'user_id');
SET @query := IF(@exist_col = 0, 'ALTER TABLE `patients` ADD COLUMN `user_id` INT NULL AFTER `id`, ADD INDEX `idx_patients_user_id` (`user_id`)', 'SELECT 1');
PREPARE stmt FROM @query; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exist_col := (SELECT COUNT(*) FROM information_schema.COLUMNS 
                   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'patients' AND COLUMN_NAME = 'password');
SET @query := IF(@exist_col = 0, 'ALTER TABLE `patients` ADD COLUMN `password` VARCHAR(255) NULL AFTER `email`', 'SELECT 1');
PREPARE stmt FROM @query; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exist_col := (SELECT COUNT(*) FROM information_schema.COLUMNS 
                   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'patients' AND COLUMN_NAME = 'email_verified');
SET @query := IF(@exist_col = 0, 'ALTER TABLE `patients` ADD COLUMN `email_verified` TINYINT(1) NOT NULL DEFAULT 0 AFTER `password`', 'SELECT 1');
PREPARE stmt FROM @query; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exist_col := (SELECT COUNT(*) FROM information_schema.COLUMNS 
                   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'patients' AND COLUMN_NAME = 'verification_token');
SET @query := IF(@exist_col = 0, 'ALTER TABLE `patients` ADD COLUMN `verification_token` VARCHAR(64) NULL AFTER `email_verified`', 'SELECT 1');
PREPARE stmt FROM @query; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exist_col := (SELECT COUNT(*) FROM information_schema.COLUMNS 
                   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'patients' AND COLUMN_NAME = 'reset_token');
SET @query := IF(@exist_col = 0, 'ALTER TABLE `patients` ADD COLUMN `reset_token` VARCHAR(64) NULL AFTER `verification_token`', 'SELECT 1');
PREPARE stmt FROM @query; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exist_col := (SELECT COUNT(*) FROM information_schema.COLUMNS 
                   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'patients' AND COLUMN_NAME = 'reset_token_expires_at');
SET @query := IF(@exist_col = 0, 'ALTER TABLE `patients` ADD COLUMN `reset_token_expires_at` DATETIME NULL AFTER `reset_token`', 'SELECT 1');
PREPARE stmt FROM @query; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 3. Backfill users table from existing patient records
INSERT INTO `users` (`username`, `email`, `password`, `role`, `status`, `patient_id`, `created_at`)
SELECT 
    COALESCE(p.patient_id, CONCAT('patient_', p.id)) AS username,
    p.email,
    COALESCE(p.password, '$2y$10$wO0H8u4sM8wP2B2Xw8f4OOn7v1P/9N9zY7QJ6C0C9K0O6Q6u9M9G.') AS password,
    'patient' AS role,
    'active' AS status,
    p.id AS patient_id,
    p.created_at
FROM `patients` p
LEFT JOIN `users` u ON (u.patient_id = p.id OR (p.email IS NOT NULL AND p.email != '' AND u.email = p.email))
WHERE u.id IS NULL AND p.email IS NOT NULL AND p.email != ''
ON DUPLICATE KEY UPDATE `patient_id` = VALUES(`patient_id`);

-- 4. Cross-link patients.user_id = users.id
UPDATE `patients` p
JOIN `users` u ON (u.patient_id = p.id OR (p.email IS NOT NULL AND p.email != '' AND u.email = p.email))
SET p.user_id = u.id
WHERE p.user_id IS NULL;

SET FOREIGN_KEY_CHECKS = 1;

