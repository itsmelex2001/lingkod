-- Safe Transactional Reset: Clear Patient & Staff Data (Preserve Admin & Reference Tables)
USE `scheduling_db`;

START TRANSACTION;

DELETE FROM `progress_notes`;
DELETE FROM `patient_notification_states`;
DELETE FROM `patient_remember_tokens`;
DELETE FROM `password_resets`;
DELETE FROM `appointments`;
DELETE FROM `patients`;
DELETE FROM `login_history` WHERE `role` <> 'admin';
DELETE FROM `system_logs`;
DELETE FROM `users` WHERE `role` IN ('patient', 'staff') OR (`role` = '' AND `username` <> 'superadmin');

COMMIT;

ALTER TABLE `appointments` AUTO_INCREMENT = 1;
ALTER TABLE `patients` AUTO_INCREMENT = 1;
ALTER TABLE `progress_notes` AUTO_INCREMENT = 1;
ALTER TABLE `patient_notification_states` AUTO_INCREMENT = 1;
ALTER TABLE `patient_remember_tokens` AUTO_INCREMENT = 1;
ALTER TABLE `password_resets` AUTO_INCREMENT = 1;
ALTER TABLE `system_logs` AUTO_INCREMENT = 1;
