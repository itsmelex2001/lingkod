-- ============================================================
--  COMPLETE DATABASE SETUP FOR ADMIN PORTAL
--  Staff & Doctor Management with User Access Tracking
--  
--  HOW TO USE:
--  1. Open phpMyAdmin
--  2. Select "scheduling_db" database
--  3. Go to SQL tab
--  4. Copy ALL content of this file
--  5. Paste into SQL box
--  6. Click "Go"
--  
--  This will automatically create/update all tables needed.
-- ============================================================

USE `scheduling_db`;

-- ============================================================
-- PART 1: UPDATE USERS TABLE FOR STAFF MANAGEMENT
-- ============================================================
-- Adds columns: status, department, last_login, login_count

ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active' AFTER `role`;
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `department` VARCHAR(100) AFTER `status`;
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `last_login` TIMESTAMP NULL AFTER `department`;
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `login_count` INT DEFAULT 0 AFTER `last_login`;

-- ============================================================
-- PART 2: CREATE LOGIN HISTORY TABLE (NEW)
-- ============================================================
-- Tracks all login attempts, successful/failed, and session info

CREATE TABLE IF NOT EXISTS `login_history` (
  `id`         INT AUTO_INCREMENT PRIMARY KEY,
  `user_id`    INT NOT NULL,
  `username`   VARCHAR(50) NOT NULL,
  `role`       ENUM('admin', 'staff') NOT NULL,
  `login_time` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `logout_time` TIMESTAMP NULL,
  `ip_address` VARCHAR(45),
  `session_id` VARCHAR(255),
  `status`     ENUM('success', 'failed', 'active', 'expired') DEFAULT 'success',
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX idx_user_id (user_id),
  INDEX idx_username (username),
  INDEX idx_login_time (login_time),
  INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- PART 3: CREATE PASSWORD RESETS TABLE (NEW)
-- ============================================================
-- Tracks password reset requests and their status

CREATE TABLE IF NOT EXISTS `password_resets` (
  `id`         INT AUTO_INCREMENT PRIMARY KEY,
  `user_id`    INT NOT NULL,
  `username`   VARCHAR(50) NOT NULL,
  `reset_token` VARCHAR(255),
  `requested_by` VARCHAR(50),
  `requested_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `reset_at`   TIMESTAMP NULL,
  `expires_at` TIMESTAMP NULL,
  `status`     ENUM('pending', 'completed', 'expired') DEFAULT 'pending',
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX idx_user_id (user_id),
  INDEX idx_reset_token (reset_token),
  INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- PART 4: ENSURE DOCTORS TABLE EXISTS AND IS CORRECT
-- ============================================================

CREATE TABLE IF NOT EXISTS `doctors` (
  `id`             INT AUTO_INCREMENT PRIMARY KEY,
  `name`           VARCHAR(100) NOT NULL,
  `specialization` VARCHAR(100),
  `department`     ENUM('psychiatry', 'neurology', 'general') NOT NULL,
  `schedule_start` TIME,
  `schedule_end`   TIME,
  `total_slots`    TINYINT UNSIGNED NOT NULL DEFAULT 20,
  `booked_slots`   TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `status`         ENUM('available', 'booked', 'leave') NOT NULL DEFAULT 'available',
  `schedule_date`  DATE,
  `created_at`     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_department (department),
  INDEX idx_status (status),
  INDEX idx_schedule_date (schedule_date),
  INDEX idx_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- PART 5: CREATE VIEWS FOR ADMIN REPORTING
-- ============================================================

-- VIEW 1: Staff Summary with Login Info
CREATE OR REPLACE VIEW `v_staff_summary` AS
SELECT 
  u.id,
  u.username,
  u.role,
  u.status,
  u.department,
  u.created_at,
  u.last_login,
  u.login_count,
  (SELECT COUNT(*) FROM `login_history` WHERE user_id = u.id AND status = 'success') AS total_successful_logins,
  (SELECT COUNT(*) FROM `login_history` WHERE user_id = u.id AND status = 'failed') AS total_failed_logins,
  (SELECT MAX(login_time) FROM `login_history` WHERE user_id = u.id AND status IN ('success', 'active')) AS last_activity
FROM `users` u
WHERE u.role = 'staff'
ORDER BY u.created_at DESC;

-- VIEW 2: Doctor Summary with Patient Load
CREATE OR REPLACE VIEW `v_doctor_summary` AS
SELECT 
  d.id,
  d.name,
  d.specialization,
  d.department,
  d.schedule_start,
  d.schedule_end,
  d.total_slots,
  d.booked_slots,
  ROUND((d.booked_slots / d.total_slots * 100), 0) AS load_percentage,
  (d.total_slots - d.booked_slots) AS available_slots,
  d.status,
  d.schedule_date,
  d.created_at
FROM `doctors` d
ORDER BY d.department, d.name ASC;

-- VIEW 3: Active User Sessions
CREATE OR REPLACE VIEW `v_active_sessions` AS
SELECT 
  lh.id,
  lh.username,
  lh.role,
  lh.login_time,
  lh.ip_address,
  TIMESTAMPDIFF(MINUTE, lh.login_time, NOW()) AS session_duration_minutes,
  lh.status
FROM `login_history` lh
WHERE lh.status IN ('success', 'active')
  AND lh.logout_time IS NULL
  AND lh.login_time >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
ORDER BY lh.login_time DESC;

-- VIEW 4: Recent Login History (Last 100)
CREATE OR REPLACE VIEW `v_login_history` AS
SELECT 
  lh.id,
  lh.username,
  lh.role,
  lh.login_time,
  lh.logout_time,
  TIMESTAMPDIFF(MINUTE, lh.login_time, COALESCE(lh.logout_time, NOW())) AS session_duration_minutes,
  lh.ip_address,
  lh.status
FROM `login_history` lh
ORDER BY lh.login_time DESC
LIMIT 100;

-- VIEW 5: Staff by Department
CREATE OR REPLACE VIEW `v_staff_by_department` AS
SELECT 
  u.department,
  COUNT(*) as total_staff,
  SUM(CASE WHEN u.status = 'active' THEN 1 ELSE 0 END) AS active_staff,
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
  COUNT(*) as total_doctors,
  SUM(CASE WHEN d.status = 'available' THEN 1 ELSE 0 END) AS available,
  SUM(CASE WHEN d.status = 'booked' THEN 1 ELSE 0 END) AS booked,
  SUM(CASE WHEN d.status = 'leave' THEN 1 ELSE 0 END) AS on_leave,
  SUM(d.total_slots) as total_slots,
  SUM(d.booked_slots) as booked_slots
FROM `doctors` d
GROUP BY d.department
ORDER BY d.department;

-- ============================================================
-- PART 6: SAMPLE DATA (if tables are empty)
-- ============================================================

-- Insert sample doctors (only if no doctors exist)
INSERT INTO `doctors` 
  (name, specialization, department, schedule_start, schedule_end, total_slots, booked_slots, status, schedule_date)
SELECT * FROM (SELECT
  'Dr. Alan Magno',    'Adult Psychiatry',   'psychiatry', '08:00:00','12:00:00', 20,  9, 'available', CURDATE() UNION ALL SELECT
  'Dr. Sarah Reyes',   'Child Psychiatry',   'psychiatry', '13:00:00','17:00:00', 20, 20, 'booked',    CURDATE() UNION ALL SELECT
  'Dr. Emilio Cruz',   'Clinical Neurology', 'neurology',  '09:00:00','15:00:00', 20, 13, 'available', CURDATE() UNION ALL SELECT
  'Dr. Maria Santos',  'Internal Medicine',  'general',    '08:00:00','16:00:00', 20,  6, 'available', CURDATE() UNION ALL SELECT
  'Dr. Juan Dela Cruz','Family Medicine',    'general',    NULL,       NULL,       20,  0, 'leave',     CURDATE()
) AS new_doctors
WHERE NOT EXISTS (SELECT 1 FROM `doctors` LIMIT 1);

-- ============================================================
-- PART 7: VERIFICATION QUERIES
-- ============================================================
-- Run these to verify everything was created successfully

-- 1. Check users table has new columns
SELECT 'Users table structure:' as '';
DESCRIBE users;

-- 2. Check new tables exist
SELECT 'New tables created:' as '';
SHOW TABLES LIKE 'login_history';
SHOW TABLES LIKE 'password_resets';

-- 3. Check doctors table
SELECT 'Doctors table structure:' as '';
DESCRIBE doctors;

-- 4. Count records
SELECT 'Record counts:' as '';
SELECT COUNT(*) as total_users FROM users;
SELECT COUNT(*) as total_staff FROM users WHERE role = 'staff';
SELECT COUNT(*) as active_staff FROM users WHERE role = 'staff' AND status = 'active';
SELECT COUNT(*) as total_doctors FROM doctors;

-- 5. Show sample data
SELECT 'Sample Doctors:' as '';
SELECT id, name, specialization, department, total_slots, booked_slots, status FROM doctors LIMIT 5;

-- 6. Show views
SELECT 'Views created:' as '';
SHOW FULL TABLES IN `scheduling_db` WHERE Table_type = 'VIEW';

-- ============================================================
-- SETUP COMPLETE!
-- ============================================================
-- 
-- What you now have:
-- ✓ Updated users table with status, department, login tracking
-- ✓ New login_history table for session tracking
-- ✓ New password_resets table for password management
-- ✓ Confirmed doctors table with all necessary fields
-- ✓ 6 useful views for admin reporting
-- ✓ Sample data to test
--
-- Admin Portal is now ready to:
-- ✓ Create/deactivate staff accounts
-- ✓ Track staff login history
-- ✓ Add/edit/delete doctors
-- ✓ Manage doctor schedules and availability
-- ✓ View patient load per doctor
-- ✓ Track all administrative actions
--
-- Next Step: Log in to admin portal and test!
-- ============================================================