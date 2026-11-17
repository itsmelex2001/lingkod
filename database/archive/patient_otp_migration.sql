-- Migration: Add Email OTP verification columns to patients table
SET @col1 := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'patients' AND COLUMN_NAME = 'otp_hash');
SET @q1 := IF(@col1 = 0, 'ALTER TABLE `patients` ADD COLUMN `otp_hash` VARCHAR(255) NULL AFTER `token_expires_at`', 'SELECT 1');
PREPARE s1 FROM @q1; EXECUTE s1; DEALLOCATE PREPARE s1;

SET @col2 := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'patients' AND COLUMN_NAME = 'otp_expires_at');
SET @q2 := IF(@col2 = 0, 'ALTER TABLE `patients` ADD COLUMN `otp_expires_at` DATETIME NULL AFTER `otp_hash`', 'SELECT 1');
PREPARE s2 FROM @q2; EXECUTE s2; DEALLOCATE PREPARE s2;

SET @col3 := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'patients' AND COLUMN_NAME = 'otp_attempts');
SET @q3 := IF(@col3 = 0, 'ALTER TABLE `patients` ADD COLUMN `otp_attempts` TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER `otp_expires_at`', 'SELECT 1');
PREPARE s3 FROM @q3; EXECUTE s3; DEALLOCATE PREPARE s3;
