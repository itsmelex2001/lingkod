-- ============================================================
--  SDN — REGISTRATION SCHEMA UPDATES
--  Adds new columns to the `patients` table to accommodate
--  the distinct fields from the OPU Psychiatry and General forms.
-- ============================================================

USE `scheduling_db`;

ALTER TABLE `patients`
  -- OPU Psychiatry specific fields
  ADD COLUMN `receiver` VARCHAR(100) DEFAULT NULL AFTER `department`,
  ADD COLUMN `informant` VARCHAR(100) DEFAULT NULL AFTER `contact_number`,
  ADD COLUMN `recommendation_remarks` TEXT DEFAULT NULL AFTER `illness_description`,
  ADD COLUMN `unscheduled_walkin` TINYINT(1) DEFAULT 0 AFTER `recommendation_remarks`,
  ADD COLUMN `inquiry_only` TINYINT(1) DEFAULT 0 AFTER `unscheduled_walkin`,

  -- General Medical specific fields
  ADD COLUMN `mode_of_inquiry` VARCHAR(50) DEFAULT NULL AFTER `inquiry_only`,
  ADD COLUMN `employee_type` VARCHAR(50) DEFAULT NULL AFTER `mode_of_inquiry`,
  ADD COLUMN `select_type` VARCHAR(50) DEFAULT NULL AFTER `employee_type`,
  ADD COLUMN `hrn` VARCHAR(50) DEFAULT NULL AFTER `patient_id`,
  ADD COLUMN `specialty` VARCHAR(100) DEFAULT NULL AFTER `assigned_doctor`,
  ADD COLUMN `scheduled_time` TIME DEFAULT NULL AFTER `appointment_date`;
