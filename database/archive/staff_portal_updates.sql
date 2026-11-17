-- ============================================================
--  STAFF PORTAL DATABASE UPDATES
--  Run this in phpMyAdmin against the `scheduling_db` database
-- ============================================================

USE `scheduling_db`;

-- 1. Appointments (Enhanced scheduling beyond the patient record)
CREATE TABLE IF NOT EXISTS `appointments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `patient_id` VARCHAR(20) NOT NULL,
  `doctor_id` INT,
  `department` VARCHAR(60),
  `appointment_date` DATE NOT NULL,
  `start_time` TIME NOT NULL,
  `end_time` TIME NOT NULL,
  `status` ENUM('scheduled','completed','cancelled','no-show') DEFAULT 'scheduled',
  `notes` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`patient_id`) REFERENCES `patients`(`patient_id`) ON DELETE CASCADE,
  FOREIGN KEY (`doctor_id`) REFERENCES `doctors`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Leave Requests (Availability & Leave Management)
CREATE TABLE IF NOT EXISTS `leave_requests` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `start_date` DATE NOT NULL,
  `end_date` DATE NOT NULL,
  `reason` TEXT,
  `status` ENUM('pending','approved','rejected') DEFAULT 'pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Notifications & Alerts
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT, -- NULL means broadcast to all
  `department` VARCHAR(60), -- If broadcast to specific dept
  `title` VARCHAR(100) NOT NULL,
  `message` TEXT NOT NULL,
  `type` ENUM('alert','info','urgent') DEFAULT 'info',
  `is_read` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. On-Call Roster
CREATE TABLE IF NOT EXISTS `on_call_roster` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `department` VARCHAR(60) NOT NULL,
  `shift_date` DATE NOT NULL,
  `shift_type` ENUM('day','night') NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert some dummy notifications
INSERT INTO `notifications` (`user_id`, `department`, `title`, `message`, `type`) VALUES
(NULL, 'All', 'System Update', 'The scheduling system will undergo maintenance at 2AM tonight.', 'info'),
(NULL, 'Psychiatry', 'Urgent Shift Swap', 'Dr. Magno requested a shift swap for tomorrow morning.', 'urgent'),
(NULL, 'Neurology', 'Patient No-Show', 'Patient PT-2026-002 did not show up for their 9AM slot.', 'alert');
