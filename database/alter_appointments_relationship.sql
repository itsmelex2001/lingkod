-- =========================================================================
-- SQL Migration: Add relationship_to_patient to appointments & patients table
-- =========================================================================

CREATE TABLE IF NOT EXISTS `appointments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `patient_id` VARCHAR(50) NOT NULL,
    `department` VARCHAR(100) DEFAULT NULL,
    `appointment_date` DATE DEFAULT NULL,
    `start_time` TIME DEFAULT NULL,
    `end_time` TIME DEFAULT NULL,
    `status` VARCHAR(50) DEFAULT 'pending',
    `notes` TEXT DEFAULT NULL,
    `relationship_to_patient` VARCHAR(50) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_appts_patient_id` (`patient_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `appointments`
ADD COLUMN IF NOT EXISTS `relationship_to_patient` VARCHAR(50) DEFAULT NULL;

ALTER TABLE `patients`
ADD COLUMN IF NOT EXISTS `relationship_to_patient` VARCHAR(50) DEFAULT NULL AFTER `with_companion`;

