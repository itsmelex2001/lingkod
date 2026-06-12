-- ============================================================
--  SDN — ADMIN PORTAL DB EXTENSIONS
--  HOW TO USE:
--  1. Open phpMyAdmin
--  2. Select your scheduling_db database
--  3. Go to the SQL tab
--  4. Paste this entire file and click "Go"
-- ============================================================

USE `scheduling_db`;

-- 1. Departments Configuration
CREATE TABLE IF NOT EXISTS `departments_config` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `department_name` ENUM('psychiatry','neurology','general') NOT NULL UNIQUE,
  `operating_hours_start` TIME NOT NULL DEFAULT '08:00:00',
  `operating_hours_end` TIME NOT NULL DEFAULT '17:00:00',
  `capacity_limit` INT NOT NULL DEFAULT 50,
  `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `departments_config` (department_name) VALUES 
('psychiatry'), ('neurology'), ('general');

-- 2. Master Schedules
CREATE TABLE IF NOT EXISTS `master_schedules` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `department` ENUM('psychiatry','neurology','general') NOT NULL,
  `shift_date` DATE NOT NULL,
  `shift_start` TIME NOT NULL,
  `shift_end` TIME NOT NULL,
  `recurrence_pattern` ENUM('none','daily','weekly','monthly') DEFAULT 'none',
  `status` ENUM('scheduled','completed','cancelled') DEFAULT 'scheduled',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Shift Swaps
CREATE TABLE IF NOT EXISTS `shift_swaps` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `requester_id` INT NOT NULL,
  `target_user_id` INT NOT NULL,
  `shift_id` INT NOT NULL,
  `department` ENUM('psychiatry','neurology','general') NOT NULL,
  `status` ENUM('pending','approved','rejected') DEFAULT 'pending',
  `request_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `resolved_at` TIMESTAMP NULL DEFAULT NULL,
  `resolved_by` INT NULL, -- Admin who resolved it
  FOREIGN KEY (`requester_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`target_user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`shift_id`) REFERENCES `master_schedules`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`resolved_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. System Settings
CREATE TABLE IF NOT EXISTS `system_settings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `setting_key` VARCHAR(50) NOT NULL UNIQUE,
  `setting_value` VARCHAR(255) NOT NULL,
  `description` TEXT,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `system_settings` (setting_key, setting_value, description) VALUES 
('max_weekly_hours', '40', 'Maximum allowed working hours per staff per week.'),
('allow_shift_swaps', 'true', 'Enable or disable staff shift swap requests.'),
('system_maintenance_mode', 'false', 'Put the entire portal into maintenance mode.'),
('default_appointment_duration', '30', 'Default length of patient appointments in minutes.');
