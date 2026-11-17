-- ============================================================
-- MIGRATION: Update Global Terminology & Department Enums/Data
-- Replaces 'General Management' with 'General Medical'
-- Aligns database schema and existing data with system terminology
-- ============================================================

USE `scheduling_db`;

-- 1. Ensure doctors table department ENUM supports 'general' and 'general_medical'
ALTER TABLE `doctors` 
  MODIFY COLUMN `department` ENUM('psychiatry', 'neurology', 'general', 'general_medical') NOT NULL DEFAULT 'general';

-- 2. Update existing patient registration department values
UPDATE `patients` 
SET `department` = 'General Medical' 
WHERE `department` IN ('General Management', 'general management', 'General care', 'general care');

-- 3. Update staff user department assignments
UPDATE `users` 
SET `department` = 'General Medical' 
WHERE `department` IN ('General Management', 'general management');

-- 4. Update hospital site map locations
UPDATE `map_locations` 
SET `name` = 'General Medical', 
    `description` = 'General Medical clinic, consulting rooms, and triage.' 
WHERE `name` IN ('General Management', 'general management');

-- 5. Update department metrics if populated
UPDATE `department_metrics` 
SET `department_name` = 'general' 
WHERE `department_name` = 'general';

