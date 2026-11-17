-- Clinical Decision Support System (CDSS)
-- clinical_guidelines table definition

CREATE TABLE IF NOT EXISTS `clinical_guidelines` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `department` VARCHAR(100) NOT NULL,
    `diagnosis` VARCHAR(255) NOT NULL,
    `treatment_plan` TEXT NOT NULL,
    `medicine_name` VARCHAR(255) NOT NULL,
    `dosage_instructions` VARCHAR(255) NOT NULL,
    `default_quantity` VARCHAR(100) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_guidelines_diagnosis` (`diagnosis`),
    INDEX `idx_guidelines_department` (`department`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

