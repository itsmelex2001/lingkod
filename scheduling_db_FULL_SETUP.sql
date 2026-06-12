-- ============================================================
--  C.O.R.E. SDN — COMPLETE DATABASE SETUP
--  scheduling_db — Full Schema + Sample Data
--
--  HOW TO USE:
--  1. Open phpMyAdmin
--  2. Click "New" or select an existing scheduling_db
--  3. Go to the SQL tab
--  4. Paste this entire file and click "Go"
--
--  NOTE: Passwords for admin/staff are seeded at the bottom.
--        admin → admin123 | staff → staff123
-- ============================================================

CREATE DATABASE IF NOT EXISTS `scheduling_db`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `scheduling_db`;

-- ============================================================
-- TABLE 1: USERS
-- ============================================================

CREATE TABLE IF NOT EXISTS `users` (
  `id`          INT AUTO_INCREMENT PRIMARY KEY,
  `username`    VARCHAR(50)  NOT NULL UNIQUE,
  `password`    VARCHAR(255) NOT NULL,
  `role`        ENUM('admin','staff') NOT NULL DEFAULT 'staff',
  `status`      ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `department`  VARCHAR(100) DEFAULT NULL,
  `last_login`  TIMESTAMP NULL DEFAULT NULL,
  `login_count` INT NOT NULL DEFAULT 0,
  `created_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE 2: PATIENTS
-- ============================================================

CREATE TABLE IF NOT EXISTS `patients` (
  `id`                  INT AUTO_INCREMENT PRIMARY KEY,
  `patient_id`          VARCHAR(20)  NOT NULL UNIQUE,
  `department`          VARCHAR(60),
  `full_name`           VARCHAR(100) NOT NULL,
  `gender`              VARCHAR(20),
  `birthdate`           DATE,
  `age`                 TINYINT UNSIGNED,
  `contact_number`      VARCHAR(25),
  `email`               VARCHAR(120),
  `address`             TEXT,
  `with_companion`      TINYINT(1) NOT NULL DEFAULT 0,
  `chief_complaints`    TEXT,
  `consultation_nature` VARCHAR(100),
  `illness_description` TEXT,
  `patient_type`        ENUM('first-time','returning') DEFAULT 'first-time',
  `appointment_type`    ENUM('walk-in','online')       DEFAULT 'walk-in',
  `consultation_mode`   VARCHAR(50),
  `appointment_date`    DATE,
  `referral_source`     VARCHAR(120),
  `status`              ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `assigned_doctor`     VARCHAR(100),
  `assigned_date`       DATE,
  `created_at`          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at`          TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE 3: DOCTORS
-- ============================================================

CREATE TABLE IF NOT EXISTS `doctors` (
  `id`             INT AUTO_INCREMENT PRIMARY KEY,
  `name`           VARCHAR(100) NOT NULL,
  `specialization` VARCHAR(100),
  `department`     ENUM('psychiatry','neurology','general') NOT NULL,
  `schedule_start` TIME,
  `schedule_end`   TIME,
  `total_slots`    TINYINT UNSIGNED NOT NULL DEFAULT 20,
  `booked_slots`   TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `status`         ENUM('available','booked','leave') NOT NULL DEFAULT 'available',
  `schedule_date`  DATE,
  `created_at`     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_department   (department),
  INDEX idx_status       (status),
  INDEX idx_schedule_date(schedule_date),
  INDEX idx_name         (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE 4: SYSTEM LOGS
-- ============================================================

CREATE TABLE IF NOT EXISTS `system_logs` (
  `id`         INT AUTO_INCREMENT PRIMARY KEY,
  `level`      ENUM('info','warning','error') NOT NULL DEFAULT 'info',
  `action`     VARCHAR(120),
  `user`       VARCHAR(60),
  `details`    TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE 5: PROGRESS NOTES
-- ============================================================

CREATE TABLE IF NOT EXISTS `progress_notes` (
  `id`         INT AUTO_INCREMENT PRIMARY KEY,
  `patient_id` VARCHAR(20)  NOT NULL,
  `note`       TEXT         NOT NULL,
  `noted_by`   VARCHAR(100) NOT NULL DEFAULT 'Staff',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`patient_id`) REFERENCES `patients`(`patient_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE 6: LOGIN HISTORY
-- ============================================================

CREATE TABLE IF NOT EXISTS `login_history` (
  `id`           INT AUTO_INCREMENT PRIMARY KEY,
  `user_id`      INT NOT NULL,
  `username`     VARCHAR(50) NOT NULL,
  `role`         ENUM('admin','staff') NOT NULL,
  `login_time`   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `logout_time`  TIMESTAMP NULL DEFAULT NULL,
  `ip_address`   VARCHAR(45),
  `session_id`   VARCHAR(255),
  `status`       ENUM('success','failed','active','expired') DEFAULT 'success',
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX idx_user_id    (user_id),
  INDEX idx_username   (username),
  INDEX idx_login_time (login_time),
  INDEX idx_status     (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE 7: PASSWORD RESETS
-- ============================================================

CREATE TABLE IF NOT EXISTS `password_resets` (
  `id`           INT AUTO_INCREMENT PRIMARY KEY,
  `user_id`      INT NOT NULL,
  `username`     VARCHAR(50) NOT NULL,
  `reset_token`  VARCHAR(255),
  `requested_by` VARCHAR(50),
  `requested_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `reset_at`     TIMESTAMP NULL DEFAULT NULL,
  `expires_at`   TIMESTAMP NULL DEFAULT NULL,
  `status`       ENUM('pending','completed','expired') DEFAULT 'pending',
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX idx_user_id    (user_id),
  INDEX idx_reset_token(reset_token),
  INDEX idx_status     (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- VIEWS
-- ============================================================

-- VIEW 1: Staff Summary with Login Info
CREATE OR REPLACE VIEW `v_staff_summary` AS
SELECT
  u.id, u.username, u.role, u.status, u.department,
  u.created_at, u.last_login, u.login_count,
  (SELECT COUNT(*) FROM `login_history` WHERE user_id = u.id AND status = 'success') AS total_successful_logins,
  (SELECT COUNT(*) FROM `login_history` WHERE user_id = u.id AND status = 'failed')  AS total_failed_logins,
  (SELECT MAX(login_time) FROM `login_history` WHERE user_id = u.id AND status IN ('success','active')) AS last_activity
FROM `users` u
WHERE u.role = 'staff'
ORDER BY u.created_at DESC;

-- VIEW 2: Doctor Summary with Patient Load
CREATE OR REPLACE VIEW `v_doctor_summary` AS
SELECT
  d.id, d.name, d.specialization, d.department,
  d.schedule_start, d.schedule_end,
  d.total_slots, d.booked_slots,
  ROUND((d.booked_slots / d.total_slots * 100), 0) AS load_percentage,
  (d.total_slots - d.booked_slots) AS available_slots,
  d.status, d.schedule_date, d.created_at
FROM `doctors` d
ORDER BY d.department, d.name ASC;

-- VIEW 3: Active User Sessions
CREATE OR REPLACE VIEW `v_active_sessions` AS
SELECT
  lh.id, lh.username, lh.role, lh.login_time, lh.ip_address,
  TIMESTAMPDIFF(MINUTE, lh.login_time, NOW()) AS session_duration_minutes,
  lh.status
FROM `login_history` lh
WHERE lh.status IN ('success','active')
  AND lh.logout_time IS NULL
  AND lh.login_time >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
ORDER BY lh.login_time DESC;

-- VIEW 4: Recent Login History (Last 100)
CREATE OR REPLACE VIEW `v_login_history` AS
SELECT
  lh.id, lh.username, lh.role,
  lh.login_time, lh.logout_time,
  TIMESTAMPDIFF(MINUTE, lh.login_time, COALESCE(lh.logout_time, NOW())) AS session_duration_minutes,
  lh.ip_address, lh.status
FROM `login_history` lh
ORDER BY lh.login_time DESC
LIMIT 100;

-- VIEW 5: Staff by Department
CREATE OR REPLACE VIEW `v_staff_by_department` AS
SELECT
  u.department,
  COUNT(*) AS total_staff,
  SUM(CASE WHEN u.status = 'active'   THEN 1 ELSE 0 END) AS active_staff,
  SUM(CASE WHEN u.status = 'inactive' THEN 1 ELSE 0 END) AS inactive_staff,
  SUM(u.login_count) AS total_logins
FROM `users` u
WHERE u.role = 'staff'
GROUP BY u.department
ORDER BY u.department;

-- VIEW 6: Doctor Availability Summary
CREATE OR REPLACE VIEW `v_doctor_availability` AS
SELECT
  d.department,
  COUNT(*) AS total_doctors,
  SUM(CASE WHEN d.status = 'available' THEN 1 ELSE 0 END) AS available,
  SUM(CASE WHEN d.status = 'booked'    THEN 1 ELSE 0 END) AS booked,
  SUM(CASE WHEN d.status = 'leave'     THEN 1 ELSE 0 END) AS on_leave,
  SUM(d.total_slots)  AS total_slots,
  SUM(d.booked_slots) AS booked_slots
FROM `doctors` d
GROUP BY d.department
ORDER BY d.department;

-- ============================================================
-- SAMPLE DATA
-- ============================================================

-- Users (admin & staff) — passwords are bcrypt hashed
-- admin → admin123 | staff → staff123
-- Generated via: password_hash('admin123', PASSWORD_BCRYPT)
INSERT INTO `users` (username, password, role, status, department, login_count)
SELECT 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'active', 'Administration', 0
WHERE NOT EXISTS (SELECT 1 FROM `users` WHERE username = 'admin');

INSERT INTO `users` (username, password, role, status, department, login_count)
SELECT 'staff', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'staff', 'active', 'General', 0
WHERE NOT EXISTS (SELECT 1 FROM `users` WHERE username = 'staff');

-- NOTE: The hash above maps to the password "password".
-- After importing, visit: http://localhost/sdn/seed-users.php
-- to reset passwords to admin123 / staff123 using proper PHP hashing.
-- Then delete seed-users.php immediately.

-- Patients
INSERT INTO `patients`
  (patient_id, department, full_name, gender, birthdate, age, contact_number,
   email, address, with_companion, chief_complaints, consultation_nature,
   illness_description, patient_type, appointment_type, consultation_mode,
   appointment_date, referral_source, status)
SELECT * FROM (
  SELECT 'PT-2026-001','OPU Psychiatry','Juan Dela Cruz','Male','1990-03-15',36,
    '09171234567','juan@email.com','123 Mabini St., Manila',0,
    'Anxiety and sleep disturbances','Initial Evaluation',
    'Patient reports persistent worry and insomnia for 3 months.',
    'first-time','walk-in','Face-to-Face','2026-05-15',NULL,'pending'
  UNION ALL SELECT 'PT-2026-002','Neurology','Maria Santos','Female','1985-07-22',40,
    '09189876543','maria@email.com','456 Rizal Ave., Mariveles',1,
    'Frequent migraines','Follow-up Checkup',
    'Recurring migraines with aura, twice a week.',
    'returning','online','E-consultation','2026-05-12',NULL,'approved'
  UNION ALL SELECT 'PT-2026-003','Cardiology','Ricardo Lopez','Male','1975-11-08',50,
    '09201112233',NULL,'789 Luna St., Bataan',0,
    'Chest tightness and shortness of breath','Initial Evaluation',
    'Intermittent chest pain on exertion.',
    'first-time','walk-in','Face-to-Face','2026-05-20',NULL,'pending'
) AS p
WHERE NOT EXISTS (SELECT 1 FROM `patients` LIMIT 1);

-- Doctors
INSERT INTO `doctors`
  (name, specialization, department, schedule_start, schedule_end, total_slots, booked_slots, status, schedule_date)
SELECT * FROM (
  SELECT 'Dr. Alan Magno',    'Adult Psychiatry',   'psychiatry','08:00:00','12:00:00',20, 9,'available',CURDATE() UNION ALL
  SELECT 'Dr. Sarah Reyes',   'Child Psychiatry',   'psychiatry','13:00:00','17:00:00',20,20,'booked',   CURDATE() UNION ALL
  SELECT 'Dr. Emilio Cruz',   'Clinical Neurology', 'neurology', '09:00:00','15:00:00',20,13,'available',CURDATE() UNION ALL
  SELECT 'Dr. Maria Santos',  'Internal Medicine',  'general',   '08:00:00','16:00:00',20, 6,'available',CURDATE() UNION ALL
  SELECT 'Dr. Juan Dela Cruz','Family Medicine',    'general',   NULL,      NULL,       20, 0,'leave',    CURDATE()
) AS d
WHERE NOT EXISTS (SELECT 1 FROM `doctors` LIMIT 1);

-- System Logs
INSERT INTO `system_logs` (level, action, user, details, created_at)
SELECT * FROM (
  SELECT 'info',   'System Login',         'Admin',   'Admin successfully logged into the portal.',                       '2026-05-07 19:20:00' UNION ALL
  SELECT 'info',   'Patient Approved',     'Admin',   'Patient PT-2026-002 (Maria Santos) was approved for Neurology.',  '2026-05-07 19:22:00' UNION ALL
  SELECT 'warning','Failed Login Attempt', 'Unknown', 'Unrecognized credentials submitted from 192.168.1.45.',           '2026-05-07 19:35:00' UNION ALL
  SELECT 'info',   'System Startup',       'System',  'Admin portal initialized successfully.',                          '2026-05-08 08:01:00' UNION ALL
  SELECT 'error',  'API Timeout',          'System',  'Schedule refresh API call timed out after 30s. Retry scheduled.', '2026-05-08 09:14:00'
) AS l
WHERE NOT EXISTS (SELECT 1 FROM `system_logs` LIMIT 1);

-- Progress Notes
INSERT INTO `progress_notes` (patient_id, note, noted_by)
SELECT * FROM (
  SELECT 'PT-2026-001','Pending Initial Assessment','System' UNION ALL
  SELECT 'PT-2026-002','Returning patient. Last visit: April 2026. Migraine frequency unchanged.','System' UNION ALL
  SELECT 'PT-2026-003','Pending Initial Assessment','System'
) AS n
WHERE NOT EXISTS (SELECT 1 FROM `progress_notes` LIMIT 1);

-- ============================================================
-- SETUP COMPLETE
-- ============================================================
-- Tables : users, patients, doctors, system_logs,
--          progress_notes, login_history, password_resets
-- Views  : v_staff_summary, v_doctor_summary, v_active_sessions,
--          v_login_history, v_staff_by_department, v_doctor_availability
--
-- Default credentials (password = "password"):
--   admin / password
--   staff / password
--
-- To use admin123 / staff123 instead, visit:
--   http://localhost/sdn/seed-users.php  (then delete the file)
-- ============================================================
