-- =====================================================================
-- MMWGH LINKOD - SMS Appointment Reminder Schema Updates
-- =====================================================================

-- Add reminder status and timestamp columns to patients table
ALTER TABLE `patients` 
  ADD COLUMN IF NOT EXISTS `reminder_status` VARCHAR(20) DEFAULT 'Pending' AFTER `scheduled_time`,
  ADD COLUMN IF NOT EXISTS `reminder_sent_at` DATETIME NULL DEFAULT NULL AFTER `reminder_status`;

-- Index appointment_date and status for rapid daily CRON query execution
CREATE INDEX IF NOT EXISTS `idx_patient_reminder_cron` 
  ON `patients` (`status`, `appointment_date`, `reminder_status`);
