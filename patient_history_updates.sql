-- ============================================================
-- PATIENT MEDICAL HISTORY UPDATE
-- ============================================================
-- Run this script to add the new Patient Medical History feature.
-- ============================================================

CREATE TABLE IF NOT EXISTS `patient_medical_history` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `patient_id` VARCHAR(20) NOT NULL,
  `record_date` DATE NOT NULL,
  `diagnosis` VARCHAR(255) NOT NULL,
  `treatment` TEXT,
  `prescription` TEXT,
  `remarks` TEXT,
  `recorded_by` VARCHAR(100) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`patient_id`) REFERENCES `patients`(`patient_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert some dummy data if the table is empty
INSERT INTO `patient_medical_history` (`patient_id`, `record_date`, `diagnosis`, `treatment`, `prescription`, `remarks`, `recorded_by`)
SELECT 'PT-2026-001', '2026-05-10', 'Hypertension', 'Dietary changes and monitoring', 'Amlodipine 5mg', 'Patient advised to reduce sodium intake.', 'Dr. Santos'
WHERE NOT EXISTS (SELECT 1 FROM `patient_medical_history` LIMIT 1);

INSERT INTO `patient_medical_history` (`patient_id`, `record_date`, `diagnosis`, `treatment`, `prescription`, `remarks`, `recorded_by`)
SELECT 'PT-2026-002', '2026-04-15', 'Migraine', 'Rest in dark room, avoid triggers', 'Sumatriptan 50mg', 'Follow up in 2 months.', 'Dr. Reyes'
WHERE NOT EXISTS (SELECT 1 FROM `patient_medical_history` LIMIT 1);
