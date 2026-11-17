CREATE DATABASE IF NOT EXISTS `scheduling_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `scheduling_db`;

-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: scheduling_db
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `appointments`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `appointments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `reference_no` varchar(20) DEFAULT NULL,
  `patient_id` varchar(50) NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `appointment_date` date DEFAULT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `status` varchar(50) DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `relationship_to_patient` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `consultation_mode` varchar(50) DEFAULT NULL,
  `preferred_time_slot` varchar(60) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_appts_patient_id` (`patient_id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `clinical_guidelines`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `clinical_guidelines` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `department` varchar(100) NOT NULL,
  `diagnosis` varchar(255) NOT NULL,
  `treatment_plan` text NOT NULL,
  `medicine_name` varchar(255) NOT NULL,
  `dosage_instructions` varchar(255) NOT NULL,
  `default_quantity` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_guidelines_diagnosis` (`diagnosis`),
  KEY `idx_guidelines_department` (`department`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `doctors`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `doctors` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `specialization` varchar(100) DEFAULT NULL,
  `department` enum('psychiatry','neurology','general') NOT NULL,
  `schedule_start` time DEFAULT NULL,
  `schedule_end` time DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'in',
  `schedule_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `total_slots` int(11) NOT NULL DEFAULT 20,
  `profile_image` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_department` (`department`),
  KEY `idx_status` (`status`),
  KEY `idx_schedule_date` (`schedule_date`),
  KEY `idx_name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `login_history`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `login_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `role` enum('admin','staff') NOT NULL,
  `login_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `logout_time` timestamp NULL DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `session_id` varchar(255) DEFAULT NULL,
  `status` enum('success','failed','active','expired') DEFAULT 'success',
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_username` (`username`),
  KEY `idx_login_time` (`login_time`),
  KEY `idx_status` (`status`),
  CONSTRAINT `login_history_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `map_locations`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `map_locations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `x_coordinate` float NOT NULL,
  `y_coordinate` float NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=42 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `password_resets`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `reset_token` varchar(255) DEFAULT NULL,
  `requested_by` varchar(50) DEFAULT NULL,
  `requested_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `reset_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `status` enum('pending','completed','expired') DEFAULT 'pending',
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_reset_token` (`reset_token`),
  KEY `idx_status` (`status`),
  CONSTRAINT `password_resets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `patient_notification_states`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `patient_notification_states` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `patient_key` varchar(191) NOT NULL,
  `notification_id` varchar(120) NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `is_dismissed` tinyint(1) NOT NULL DEFAULT 0,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_patient_notif` (`patient_key`,`notification_id`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `patient_remember_tokens`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `patient_remember_tokens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `patient_db_id` int(11) NOT NULL,
  `selector` varchar(32) NOT NULL,
  `token_hash` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_selector` (`selector`),
  KEY `idx_patient_db_id` (`patient_db_id`),
  KEY `idx_expires_at` (`expires_at`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `patients`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `patients` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `patient_id` varchar(20) NOT NULL,
  `reference_no` varchar(20) DEFAULT NULL,
  `department` varchar(60) DEFAULT NULL,
  `full_name` varchar(100) NOT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `middle_initial` varchar(10) DEFAULT NULL,
  `gender` varchar(20) DEFAULT NULL,
  `birthdate` date DEFAULT NULL,
  `age` tinyint(3) unsigned DEFAULT NULL,
  `contact_number` varchar(25) DEFAULT NULL,
  `email` varchar(120) DEFAULT NULL,
  `username` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `email_verified` tinyint(1) NOT NULL DEFAULT 0,
  `is_verified` tinyint(1) NOT NULL DEFAULT 0,
  `verification_token` varchar(255) DEFAULT NULL,
  `token_expires_at` datetime DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_token_expires_at` datetime DEFAULT NULL,
  `address` text DEFAULT NULL,
  `with_companion` tinyint(1) NOT NULL DEFAULT 0,
  `relationship_to_patient` varchar(50) DEFAULT NULL,
  `appointment_recipient` varchar(50) DEFAULT 'Patient (Me)',
  `guardian_name` varchar(100) DEFAULT NULL,
  `guardian_contact_number` varchar(25) DEFAULT NULL,
  `chief_complaints` text DEFAULT NULL,
  `consultation_nature` varchar(100) DEFAULT NULL,
  `illness_description` text DEFAULT NULL,
  `existing_medical_conditions` text DEFAULT NULL,
  `current_medications` text DEFAULT NULL,
  `patient_type` enum('first-time','returning') DEFAULT 'first-time',
  `appointment_type` enum('walk-in','online') DEFAULT 'walk-in',
  `consultation_mode` varchar(50) DEFAULT NULL,
  `appointment_date` date DEFAULT NULL,
  `referral_source` varchar(120) DEFAULT NULL,
  `status` varchar(40) NOT NULL DEFAULT 'for_approval',
  `assigned_doctor` varchar(100) DEFAULT NULL,
  `assigned_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `assigned_staff_id` int(11) DEFAULT NULL,
  `receiver` varchar(100) DEFAULT NULL,
  `informant` varchar(100) DEFAULT NULL,
  `recommendation_remarks` text DEFAULT NULL,
  `unscheduled_walkin` tinyint(1) DEFAULT 0,
  `inquiry_only` tinyint(1) DEFAULT 0,
  `mode_of_inquiry` varchar(50) DEFAULT NULL,
  `employee_type` varchar(50) DEFAULT NULL,
  `select_type` varchar(50) DEFAULT NULL,
  `hrn` varchar(50) DEFAULT NULL,
  `specialty` varchar(100) DEFAULT NULL,
  `scheduled_time` time DEFAULT NULL,
  `preferred_time_slot` varchar(60) DEFAULT NULL,
  `reminder_status` varchar(20) DEFAULT 'Pending',
  `reminder_sent_at` datetime DEFAULT NULL,
  `active_medications` text DEFAULT NULL,
  `next_visit_date` date DEFAULT NULL,
  `medical_history_diagnosis` text DEFAULT NULL,
  `medical_history_treatment` text DEFAULT NULL,
  `medical_history_prescription` text DEFAULT NULL,
  `medical_history_recorded_by` varchar(100) DEFAULT NULL,
  `medical_history_date` datetime DEFAULT NULL,
  `1_day_reminder_sent` tinyint(1) NOT NULL DEFAULT 0,
  `day_of_reminder_sent` tinyint(1) NOT NULL DEFAULT 0,
  `profile_image` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `patient_id` (`patient_id`),
  KEY `idx_patient_reminder_cron` (`status`,`appointment_date`,`reminder_status`),
  KEY `idx_patients_reset_token` (`reset_token`)
) ENGINE=InnoDB AUTO_INCREMENT=84 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `progress_notes`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `progress_notes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `patient_id` varchar(20) NOT NULL,
  `note` text NOT NULL,
  `noted_by` varchar(100) NOT NULL DEFAULT 'Staff',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `blood_pressure` varchar(20) DEFAULT NULL,
  `temperature` decimal(5,2) DEFAULT NULL,
  `heart_rate` int(11) DEFAULT NULL,
  `weight` decimal(5,2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `patient_id` (`patient_id`),
  CONSTRAINT `progress_notes_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=63 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `specializations`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `specializations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `department` enum('general','neurology','psychiatry') NOT NULL,
  `name` varchar(150) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_dept_spec` (`department`,`name`)
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `system_logs`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `system_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `level` enum('info','warning','error') NOT NULL DEFAULT 'info',
  `action` varchar(120) DEFAULT NULL,
  `user` varchar(60) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=260 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `users`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(120) DEFAULT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','staff') NOT NULL DEFAULT 'staff',
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `department` varchar(100) DEFAULT NULL,
  `patient_id` int(11) DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `last_login` timestamp NULL DEFAULT NULL,
  `login_count` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Temporary table structure for view `v_active_sessions`
--

SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `v_active_sessions` AS SELECT
 1 AS `id`,
  1 AS `username`,
  1 AS `role`,
  1 AS `login_time`,
  1 AS `ip_address`,
  1 AS `session_duration_minutes`,
  1 AS `status` */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `v_doctor_availability`
--


-- failed on view `v_doctor_availability`: CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_doctor_availability` AS select `d`.`department` AS `department`,count(0) AS `total_doctors`,sum(case when `d`.`status` = 'available' then 1 else 0 end) AS `available`,sum(case when `d`.`status` = 'booked' then 1 else 0 end) AS `booked`,sum(case when `d`.`status` = 'leave' then 1 else 0 end) AS `on_leave`,sum(`d`.`total_slots`) AS `total_slots`,sum(`d`.`booked_slots`) AS `booked_slots` from `doctors` `d` group by `d`.`department` order by `d`.`department`


--
-- Temporary table structure for view `v_doctor_summary`
--


-- failed on view `v_doctor_summary`: CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_doctor_summary` AS select `d`.`id` AS `id`,`d`.`name` AS `name`,`d`.`specialization` AS `specialization`,`d`.`department` AS `department`,`d`.`schedule_start` AS `schedule_start`,`d`.`schedule_end` AS `schedule_end`,`d`.`total_slots` AS `total_slots`,`d`.`booked_slots` AS `booked_slots`,round(`d`.`booked_slots` / `d`.`total_slots` * 100,0) AS `load_percentage`,`d`.`total_slots` - `d`.`booked_slots` AS `available_slots`,`d`.`status` AS `status`,`d`.`schedule_date` AS `schedule_date`,`d`.`created_at` AS `created_at` from `doctors` `d` order by `d`.`department`,`d`.`name`


--
-- Temporary table structure for view `v_login_history`
--

SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `v_login_history` AS SELECT
 1 AS `id`,
  1 AS `username`,
  1 AS `role`,
  1 AS `login_time`,
  1 AS `logout_time`,
  1 AS `session_duration_minutes`,
  1 AS `ip_address`,
  1 AS `status` */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `v_staff_by_department`
--

SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `v_staff_by_department` AS SELECT
 1 AS `department`,
  1 AS `total_staff`,
  1 AS `active_staff`,
  1 AS `inactive_staff`,
  1 AS `total_logins` */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `v_staff_summary`
--

SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `v_staff_summary` AS SELECT
 1 AS `id`,
  1 AS `username`,
  1 AS `role`,
  1 AS `status`,
  1 AS `department`,
  1 AS `created_at`,
  1 AS `last_login`,
  1 AS `login_count`,
  1 AS `total_successful_logins`,
  1 AS `total_failed_logins`,
  1 AS `last_activity` */;
SET character_set_client = @saved_cs_client;

--
-- Dumping routines for database 'scheduling_db'
--

--
-- Final view structure for view `v_active_sessions`
--

/*!50001 DROP VIEW IF EXISTS `v_active_sessions`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_active_sessions` AS select `lh`.`id` AS `id`,`lh`.`username` AS `username`,`lh`.`role` AS `role`,`lh`.`login_time` AS `login_time`,`lh`.`ip_address` AS `ip_address`,timestampdiff(MINUTE,`lh`.`login_time`,current_timestamp()) AS `session_duration_minutes`,`lh`.`status` AS `status` from `login_history` `lh` where `lh`.`status` in ('success','active') and `lh`.`logout_time` is null and `lh`.`login_time` >= current_timestamp() - interval 24 hour order by `lh`.`login_time` desc */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_doctor_availability`
--

/*!50001 DROP VIEW IF EXISTS `v_doctor_availability`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_doctor_availability` AS select `d`.`department` AS `department`,count(0) AS `total_doctors`,sum(case when `d`.`status` = 'available' then 1 else 0 end) AS `available`,sum(case when `d`.`status` = 'booked' then 1 else 0 end) AS `booked`,sum(case when `d`.`status` = 'leave' then 1 else 0 end) AS `on_leave`,sum(`d`.`total_slots`) AS `total_slots`,sum(`d`.`booked_slots`) AS `booked_slots` from `doctors` `d` group by `d`.`department` order by `d`.`department` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_doctor_summary`
--

/*!50001 DROP VIEW IF EXISTS `v_doctor_summary`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_doctor_summary` AS select `d`.`id` AS `id`,`d`.`name` AS `name`,`d`.`specialization` AS `specialization`,`d`.`department` AS `department`,`d`.`schedule_start` AS `schedule_start`,`d`.`schedule_end` AS `schedule_end`,`d`.`total_slots` AS `total_slots`,`d`.`booked_slots` AS `booked_slots`,round(`d`.`booked_slots` / `d`.`total_slots` * 100,0) AS `load_percentage`,`d`.`total_slots` - `d`.`booked_slots` AS `available_slots`,`d`.`status` AS `status`,`d`.`schedule_date` AS `schedule_date`,`d`.`created_at` AS `created_at` from `doctors` `d` order by `d`.`department`,`d`.`name` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_login_history`
--

/*!50001 DROP VIEW IF EXISTS `v_login_history`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_login_history` AS select `lh`.`id` AS `id`,`lh`.`username` AS `username`,`lh`.`role` AS `role`,`lh`.`login_time` AS `login_time`,`lh`.`logout_time` AS `logout_time`,timestampdiff(MINUTE,`lh`.`login_time`,coalesce(`lh`.`logout_time`,current_timestamp())) AS `session_duration_minutes`,`lh`.`ip_address` AS `ip_address`,`lh`.`status` AS `status` from `login_history` `lh` order by `lh`.`login_time` desc limit 100 */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_staff_by_department`
--

/*!50001 DROP VIEW IF EXISTS `v_staff_by_department`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_staff_by_department` AS select `u`.`department` AS `department`,count(0) AS `total_staff`,sum(case when `u`.`status` = 'active' then 1 else 0 end) AS `active_staff`,sum(case when `u`.`status` = 'inactive' then 1 else 0 end) AS `inactive_staff`,sum(`u`.`login_count`) AS `total_logins` from `users` `u` where `u`.`role` = 'staff' group by `u`.`department` order by `u`.`department` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_staff_summary`
--

/*!50001 DROP VIEW IF EXISTS `v_staff_summary`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_staff_summary` AS select `u`.`id` AS `id`,`u`.`username` AS `username`,`u`.`role` AS `role`,`u`.`status` AS `status`,`u`.`department` AS `department`,`u`.`created_at` AS `created_at`,`u`.`last_login` AS `last_login`,`u`.`login_count` AS `login_count`,(select count(0) from `login_history` where `login_history`.`user_id` = `u`.`id` and `login_history`.`status` = 'success') AS `total_successful_logins`,(select count(0) from `login_history` where `login_history`.`user_id` = `u`.`id` and `login_history`.`status` = 'failed') AS `total_failed_logins`,(select max(`login_history`.`login_time`) from `login_history` where `login_history`.`user_id` = `u`.`id` and `login_history`.`status` in ('success','active')) AS `last_activity` from `users` `u` where `u`.`role` = 'staff' order by `u`.`created_at` desc */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-11-17 20:38:40
-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: scheduling_db
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Dumping data for table `doctors`
--

LOCK TABLES `doctors` WRITE;
/*!40000 ALTER TABLE `doctors` DISABLE KEYS */;
INSERT  IGNORE INTO `doctors` VALUES (1,'Dr. Alan Magno','Adult Psychiatry','psychiatry','08:00:00','14:30:00','in_clinic','2026-10-13','2026-09-22 02:47:25','2026-10-14 09:29:45',20,NULL),(2,'Dr. Sarah Reyes','Child & Adolescent Psychiatry','psychiatry','13:00:00','17:00:00','available','2026-10-13','2026-09-22 02:47:25','2026-10-13 08:50:48',20,NULL),(3,'Dr. Emilio Cruz','Clinical Neurology','neurology','09:00:00','16:00:00','in_clinic','2026-10-13','2026-09-22 02:47:25','2026-10-13 23:12:35',20,NULL),(4,'Dr. Maria Santos','Internal Medicine','general','08:00:00','16:00:00','available','2026-10-13','2026-09-22 02:47:25','2026-10-13 11:15:13',20,NULL),(5,'Dr. Juan Dela Cruz','Family Medicine & Routine Check-ups','general','08:00:00','16:00:00','available','2026-10-13','2026-09-22 02:47:25','2026-10-13 08:50:48',20,NULL),(6,'Dr. Antonio Ramos','Preventative Care & Routine Check-ups','general','08:30:00','16:00:00','on_break','2026-10-13','2026-10-13 08:50:48','2026-10-22 02:58:06',20,NULL),(7,'Dr. Elena Fernandez','Chronic Disease Management','general','09:00:00','16:30:00','available','2026-10-13','2026-10-13 08:50:48','2026-10-13 08:50:48',20,NULL),(8,'Dr. Benjamin Cruz','Pediatric Care','general','08:30:00','15:30:00','available','2026-10-13','2026-10-13 08:50:48','2026-10-13 08:50:48',20,NULL),(9,'Dr. Ricardo De Leon','General Surgery & Minor Procedures','general','10:00:00','16:00:00','available','2026-10-13','2026-10-13 08:50:48','2026-10-13 08:50:48',20,NULL),(10,'Dr. Robert Tan','Headache & Migraine Management','neurology','08:00:00','14:00:00','in_clinic','2026-10-13','2026-10-13 08:50:48','2026-10-22 02:08:03',20,NULL),(11,'Dr. Catherine Gomez','Stroke Rehabilitation','neurology','09:00:00','15:30:00','in','2026-10-13','2026-10-13 08:50:48','2026-11-08 09:57:35',20,NULL),(12,'Dr. Dominic Bautista','Epilepsy & Seizure Disorders','neurology','10:00:00','16:00:00','in_clinic','2026-10-13','2026-10-13 08:50:48','2026-10-13 23:12:35',20,NULL),(13,'Dr. Karen Villanueva','Movement Disorders','neurology','08:30:00','14:30:00','in_clinic','2026-10-13','2026-10-13 08:50:48','2026-10-14 02:37:08',20,NULL),(14,'Dr. Gabriel Mendoza','Substance Abuse & Addiction Treatment','psychiatry','08:00:00','15:00:00','in_clinic','2026-10-13','2026-10-13 08:50:48','2026-10-14 07:33:37',20,NULL),(15,'Dr. Patricia Lim','Anxiety & Depression Management','psychiatry','09:00:00','16:00:00','available','2026-10-13','2026-10-13 08:50:48','2026-10-13 08:50:48',20,NULL),(17,'Dr. Angelo Mercado, MD','Family Medicine & General Practice','general','08:00:00','12:00:00','in','2026-11-09','2026-11-09 14:43:42','2026-11-16 19:41:03',20,NULL),(18,'Dr. Beatrice Alcantara, MD','Routine Check-ups & Preventative Care','general','13:00:00','17:00:00','available','2026-11-09','2026-11-09 14:43:42','2026-11-09 14:43:42',20,NULL),(19,'Dr. Carlo Valenzuela, MD','Preventive Healthcare & Physical Examination','general','08:00:00','12:00:00','available','2026-11-09','2026-11-09 14:43:42','2026-11-09 14:43:42',20,NULL),(20,'Dr. Francesca David, MD','Stroke Evaluation','neurology','13:00:00','17:00:00','available','2026-11-09','2026-11-09 14:43:42','2026-11-09 14:43:42',20,NULL),(21,'Dr. Gerald Ocampo, MD','Neuromuscular Medicine','neurology','08:00:00','12:00:00','available','2026-11-09','2026-11-09 14:43:42','2026-11-09 14:43:42',20,NULL),(22,'Dr. Hannah Manalo, MD','Dementia Evaluation','neurology','13:00:00','17:00:00','available','2026-11-09','2026-11-09 14:43:42','2026-11-09 14:43:42',20,NULL),(23,'Dr. Ian Salazar, MD','Neurological Diagnostic Testing','neurology','08:00:00','12:00:00','available','2026-11-09','2026-11-09 14:43:42','2026-11-09 14:43:42',20,NULL),(24,'Dr. Lourdes Castillo, MD','Anxiety/Panic/Depression Management','psychiatry','13:00:00','17:00:00','available','2026-11-09','2026-11-09 14:43:42','2026-11-09 14:43:42',20,NULL),(25,'Dr. Manuel Santiago, MD','Mood Disorders','psychiatry','08:00:00','12:00:00','available','2026-11-09','2026-11-09 14:43:42','2026-11-09 14:43:42',20,NULL),(26,'Dr. Nicole Dizon, MD','Substance Abuse','psychiatry','13:00:00','17:00:00','available','2026-11-09','2026-11-09 14:43:42','2026-11-09 14:43:42',20,NULL),(27,'Dr. Oscar Villanueva, MD','Geriatric Psychiatry','psychiatry','08:00:00','12:00:00','available','2026-11-09','2026-11-09 14:43:42','2026-11-09 14:43:42',20,NULL),(28,'Dr. Priscilla Evangelista, MD','Clinical Neuropsychiatry','psychiatry','13:00:00','17:00:00','available','2026-11-09','2026-11-09 14:43:42','2026-11-09 14:43:42',20,NULL),(29,'Dr. Rafael Navarro, MD','Psychotherapy','psychiatry','08:00:00','12:00:00','available','2026-11-09','2026-11-09 14:43:42','2026-11-09 14:43:42',20,NULL);
/*!40000 ALTER TABLE `doctors` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `map_locations`
--

LOCK TABLES `map_locations` WRITE;
/*!40000 ALTER TABLE `map_locations` DISABLE KEYS */;
INSERT  IGNORE INTO `map_locations` VALUES (1,'Guard House Gate 2','01A',238,102.01),(2,'Radiology Complex','',379.999,251),(3,'EFMU Building','',385.997,331.012),(4,'HIMU','',378,372.01),(5,'Finance Management','',342.001,421.503),(6,'Custodial Care Unit (CCU)','',342,613.972),(7,'Male Ward Pavilion','',428.008,618.997),(8,'Nurse Station','',371.994,658.989),(9,'Observation Unit','',422.005,666),(10,'Female Ward Pavilion','',412.516,714.992),(11,'Old Custodial Care Unit','',317.266,770.02),(12,'Old Infirmary Unit','',412.245,774.758),(13,'Cadaver Holding Area','',311.994,809.244),(14,'TB-Dots','',262.013,869.994),(15,'Linen and Laundry','',185.014,951.999),(16,'General Hospital','',493.015,202.001),(17,'Power House','',150.07,829.932),(18,'Laboratory Complex','',530.995,277.135),(19,'Nutrition and Dietetics (NDU)','',494.005,330.997),(20,'Staff Dormitory','',558.006,355.973),(21,'Procurement and Materials Management (MMU)','',498.996,377.94),(22,'Warehouse','',481.994,415.995),(23,'Material Recovery Facility','',521.002,413.471),(24,'Hospital Operations and Patients Support (HOPS)','',402.5,432.507),(25,'COA Building','',480.501,445.746),(26,'Male Dorm','',426.984,524.985),(27,'Sewerage Treatment Plant','',496.009,582.991),(28,'OPU Building','',101.99,244.991),(30,'Lazareto Treatment Hub','',105.001,421.995),(31,'Chapel','',105.006,476.068),(32,'Covered Court','',215.002,571.003),(33,'Isolation Building-1','',208.009,702.007),(34,'Occupational Therapy Building','',211.998,658),(35,'Male Dorm A&B','',134.999,722.969),(36,'Isolation Building-2 (Bayanihan Bldg)','',114.17,590.973),(37,'Canteen (PACD)','',253.008,192.001),(38,'Integrated Medical Waste Autoclave Machine House','',224.999,909.989),(39,'Nurse Station (Male Ward)','',318.959,661.745),(40,'Medical Ward','',182.009,394.004),(41,'Malasakit Center Building','',84.008,448.063);
/*!40000 ALTER TABLE `map_locations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `clinical_guidelines`
--

LOCK TABLES `clinical_guidelines` WRITE;
/*!40000 ALTER TABLE `clinical_guidelines` DISABLE KEYS */;
INSERT  IGNORE INTO `clinical_guidelines` VALUES (1,'General Medical','Essential Hypertension (High Blood Pressure)','Regular monitoring of blood pressure, blood sugar, and cholesterol levels. Healthy diet, regular physical activity, sodium reduction (<2g/day), weight management, and smoking cessation.','Amlodipine 5mg tablet','1 tablet once daily','30 tablets','2026-11-10 12:17:00','2026-11-10 12:17:00'),(2,'General Medical','Type 2 Diabetes Mellitus','Self-monitoring of blood glucose, dietary modification (low glycemic index carbohydrates), regular moderate aerobic exercise, and annual diabetic foot/eye examinations.','Metformin 500mg tablet','1 tablet twice daily with meals','60 tablets','2026-11-10 12:17:00','2026-11-10 12:17:00'),(3,'General Medical','Acute Upper Respiratory Tract Infection (URTI)','Adequate hydration, warm saline gargles, voice rest, fever control, and symptomatic relief. Follow-up if symptoms persist past 7 days.','Paracetamol 500mg tablet','1 tablet every 4 to 6 hours as needed for fever or body aches','20 tablets','2026-11-10 12:17:00','2026-11-10 12:17:00'),(4,'General Medical','Bronchial Asthma (Acute Exacerbation)','Avoidance of known environmental triggers (dust, cold air, smoke). Instruction on proper MDI inhaler and spacer technique.','Salbutamol 100mcg Inhaler','2 puffs every 4 to 6 hours as needed for wheezing or dyspnea','1 canister','2026-11-10 12:17:00','2026-11-10 12:17:00'),(5,'Neurology','Migraine with or without Aura','Avoidance of identified dietary or environmental triggers. Establishment of a consistent sleep and meal schedule. Hydration and rest in a dark, quiet environment during attacks.','Sumatriptan 50mg tablet','1 tablet at the onset of a migraine attack','6 tablets','2026-11-10 12:17:00','2026-11-10 12:17:00'),(6,'Neurology','Tension-Type Headache (Frequent Episodic)','Stress reduction, posture correction, ergonomic workplace setup, neck stretching exercises, and trigger identification.','Ibuprofen 400mg tablet','1 tablet every 8 hours after meals as needed','15 tablets','2026-11-10 12:17:00','2026-11-10 12:17:00'),(7,'Neurology','Peripheral Neuropathy (Diabetic / Nutritional)','Strict glycemic control, daily foot inspection for micro-trauma, avoid walking barefoot, supportive footwear, and smoking cessation.','Pregabalin 75mg capsule','1 capsule once daily at bedtime','30 capsules','2026-11-10 12:17:00','2026-11-10 12:17:00'),(8,'Psychiatry','Generalized Anxiety Disorder (GAD)','Cognitive Behavioral Therapy (CBT). Breathing exercises, relaxation techniques, and mindfulness. Regular sleep schedule and caffeine restriction.','Escitalopram 10 mg tablet','Take once daily as prescribed','30 tablets','2026-11-10 12:17:00','2026-11-10 12:17:00'),(9,'Psychiatry','Major Depressive Disorder (Single / Recurrent Episode)','Psychotherapy (CBT/Interpersonal Therapy), behavioral activation, daily sunlight exposure, structured routine, and psychoeducation on medication adherence.','Sertraline 50mg tablet','1 tablet once daily in the morning','30 tablets','2026-11-10 12:17:00','2026-11-10 12:17:00'),(10,'Psychiatry','Insomnia Disorder (Non-Organic)','Cognitive Behavioral Therapy for Insomnia (CBT-I), sleep hygiene education (consistent wake time, dark/cool sleep environment, no screen exposure 1 hr before bed).','Zolpidem 10mg tablet','1 tablet at bedtime as needed for sleep (short-term use)','14 tablets','2026-11-10 12:17:00','2026-11-10 12:17:00');
/*!40000 ALTER TABLE `clinical_guidelines` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `specializations`
--

LOCK TABLES `specializations` WRITE;
/*!40000 ALTER TABLE `specializations` DISABLE KEYS */;
INSERT  IGNORE INTO `specializations` VALUES (1,'general','Internal Medicine','2026-11-09 14:43:42'),(2,'general','Family Medicine & General Practice','2026-11-09 14:43:42'),(3,'general','Routine Check-ups & Preventative Care','2026-11-09 14:43:42'),(4,'general','Chronic Disease Management','2026-11-09 14:43:42'),(5,'general','Pediatric Care','2026-11-09 14:43:42'),(6,'general','General Surgery & Minor Procedures','2026-11-09 14:43:42'),(7,'general','Preventive Healthcare & Physical Examination','2026-11-09 14:43:42'),(8,'neurology','Clinical Neurology','2026-11-09 14:43:42'),(9,'neurology','Headache & Migraine Management','2026-11-09 14:43:42'),(10,'neurology','Stroke Evaluation','2026-11-09 14:43:42'),(11,'neurology','Epilepsy & Seizure Disorders','2026-11-09 14:43:42'),(12,'neurology','Movement Disorders','2026-11-09 14:43:42'),(13,'neurology','Neuromuscular Medicine','2026-11-09 14:43:42'),(14,'neurology','Dementia Evaluation','2026-11-09 14:43:42'),(15,'neurology','Neurological Diagnostic Testing','2026-11-09 14:43:42'),(16,'psychiatry','Adult Psychiatry','2026-11-09 14:43:42'),(17,'psychiatry','Child & Adolescent Psychiatry','2026-11-09 14:43:42'),(18,'psychiatry','Anxiety/Panic/Depression Management','2026-11-09 14:43:42'),(19,'psychiatry','Mood Disorders','2026-11-09 14:43:42'),(20,'psychiatry','Substance Abuse','2026-11-09 14:43:42'),(21,'psychiatry','Geriatric Psychiatry','2026-11-09 14:43:42'),(22,'psychiatry','Clinical Neuropsychiatry','2026-11-09 14:43:42'),(23,'psychiatry','Psychotherapy','2026-11-09 14:43:42');
/*!40000 ALTER TABLE `specializations` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-11-17 20:38:40
-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: scheduling_db
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Dumping data for table `users`
--
-- WHERE:  role='admin'

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT  IGNORE INTO `users` VALUES (1,'admin',NULL,'System Administrator','$2y$10$UKLH4w8juR5DxVOvnOIT2OMD6yi184NkVAR5ZkI6rJk2lwAwBA4f6','admin','active','Administration',NULL,NULL,'2026-11-17 12:36:47',117,'2026-09-22 02:47:25');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-11-17 20:38:40

-- =============================================================================
-- Future Schema Updates (Append Idempotent IF NOT EXISTS Migrations Below)
-- =============================================================================

