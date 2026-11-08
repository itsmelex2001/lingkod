-- =====================================================================
-- MMWGH LINGKOD - Automated Reminder System Migration
-- Adds 1-day and day-of reminder tracking flags and indexes
-- =====================================================================

ALTER TABLE `patients`
  ADD COLUMN `1_day_reminder_sent` TINYINT(1) NOT NULL DEFAULT 0 AFTER `reminder_status`,
  ADD COLUMN `day_of_reminder_sent` TINYINT(1) NOT NULL DEFAULT 0 AFTER `1_day_reminder_sent`,
  ADD INDEX `idx_reminder_1day` (`status`, `appointment_date`, `1_day_reminder_sent`),
  ADD INDEX `idx_reminder_dayof` (`status`, `appointment_date`, `scheduled_time`, `day_of_reminder_sent`);

