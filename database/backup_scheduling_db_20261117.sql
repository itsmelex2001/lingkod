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

DROP TABLE IF EXISTS `appointments`;
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
-- Dumping data for table `appointments`
--

LOCK TABLES `appointments` WRITE;
/*!40000 ALTER TABLE `appointments` DISABLE KEYS */;
INSERT INTO `appointments` VALUES (1,NULL,'PT-2026-086','General Medical','2026-11-14','08:00:00','08:30:00','scheduled','Booked from the patient portal. Waiting for department approval.',NULL,'2026-11-12 18:50:22','2026-11-12 18:50:22',NULL,NULL),(2,NULL,'PT-2026-087','Neurology','2026-11-14','13:30:00','14:00:00','scheduled','Booked from the patient portal. Waiting for department approval.',NULL,'2026-11-13 09:23:38','2026-11-13 09:23:38',NULL,NULL),(3,NULL,'PT-2026-088','General Medical','2026-11-16','08:00:00','08:30:00','scheduled','Booked from the patient portal. Waiting for department approval.','Sibling','2026-11-13 09:44:43','2026-11-13 09:44:43',NULL,NULL),(4,NULL,'PT-2026-089','General Medical','2026-11-18','10:00:00','10:30:00','scheduled','Booked from the patient portal. Waiting for department approval.',NULL,'2026-11-13 09:46:07','2026-11-13 09:46:07',NULL,NULL),(5,NULL,'PT-2026-091','General Medical','2026-11-18','13:00:00','13:30:00','scheduled','Booked from the patient portal. Waiting for department approval.',NULL,'2026-11-13 14:51:17','2026-11-13 14:51:17',NULL,NULL),(6,NULL,'PT-2026-092','General Medical','2026-11-18','08:00:00','08:30:00','scheduled','Booked from the patient portal. Waiting for department approval.',NULL,'2026-11-13 14:52:56','2026-11-13 14:52:56',NULL,NULL),(7,NULL,'PT-2026-093','General Medical','2026-11-17','08:00:00','08:30:00','scheduled','Booked from the patient portal. Waiting for department approval.',NULL,'2026-11-14 07:25:31','2026-11-14 07:25:31',NULL,NULL),(8,NULL,'PT-2026-094','General Medical','2026-11-17','08:00:00','08:30:00','scheduled','Booked from the patient portal. Waiting for department approval.','Spouse','2026-11-16 03:42:38','2026-11-16 03:42:38',NULL,NULL),(12,'APT-2026-0073','PT-2026-095','General Medical','2026-11-19','13:00:00','17:00:00','scheduled','Booked from appointment registration form. Waiting for department approval.',NULL,'2026-11-16 17:48:13','2026-11-16 17:48:13','In-Person','Afternoon (1:00 PM - 5:00 PM)'),(15,'APT-2026-0077','PT-2026-096','General Medical','2026-11-20','08:00:00','12:00:00','scheduled','Booked from appointment registration form. Waiting for department approval.',NULL,'2026-11-16 18:19:03','2026-11-16 18:19:03','In-Person','Morning (8:00 AM - 12:00 PM)');
/*!40000 ALTER TABLE `appointments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `clinical_guidelines`
--

DROP TABLE IF EXISTS `clinical_guidelines`;
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
-- Dumping data for table `clinical_guidelines`
--

LOCK TABLES `clinical_guidelines` WRITE;
/*!40000 ALTER TABLE `clinical_guidelines` DISABLE KEYS */;
INSERT INTO `clinical_guidelines` VALUES (1,'General Medical','Essential Hypertension (High Blood Pressure)','Regular monitoring of blood pressure, blood sugar, and cholesterol levels. Healthy diet, regular physical activity, sodium reduction (<2g/day), weight management, and smoking cessation.','Amlodipine 5mg tablet','1 tablet once daily','30 tablets','2026-11-10 12:17:00','2026-11-10 12:17:00'),(2,'General Medical','Type 2 Diabetes Mellitus','Self-monitoring of blood glucose, dietary modification (low glycemic index carbohydrates), regular moderate aerobic exercise, and annual diabetic foot/eye examinations.','Metformin 500mg tablet','1 tablet twice daily with meals','60 tablets','2026-11-10 12:17:00','2026-11-10 12:17:00'),(3,'General Medical','Acute Upper Respiratory Tract Infection (URTI)','Adequate hydration, warm saline gargles, voice rest, fever control, and symptomatic relief. Follow-up if symptoms persist past 7 days.','Paracetamol 500mg tablet','1 tablet every 4 to 6 hours as needed for fever or body aches','20 tablets','2026-11-10 12:17:00','2026-11-10 12:17:00'),(4,'General Medical','Bronchial Asthma (Acute Exacerbation)','Avoidance of known environmental triggers (dust, cold air, smoke). Instruction on proper MDI inhaler and spacer technique.','Salbutamol 100mcg Inhaler','2 puffs every 4 to 6 hours as needed for wheezing or dyspnea','1 canister','2026-11-10 12:17:00','2026-11-10 12:17:00'),(5,'Neurology','Migraine with or without Aura','Avoidance of identified dietary or environmental triggers. Establishment of a consistent sleep and meal schedule. Hydration and rest in a dark, quiet environment during attacks.','Sumatriptan 50mg tablet','1 tablet at the onset of a migraine attack','6 tablets','2026-11-10 12:17:00','2026-11-10 12:17:00'),(6,'Neurology','Tension-Type Headache (Frequent Episodic)','Stress reduction, posture correction, ergonomic workplace setup, neck stretching exercises, and trigger identification.','Ibuprofen 400mg tablet','1 tablet every 8 hours after meals as needed','15 tablets','2026-11-10 12:17:00','2026-11-10 12:17:00'),(7,'Neurology','Peripheral Neuropathy (Diabetic / Nutritional)','Strict glycemic control, daily foot inspection for micro-trauma, avoid walking barefoot, supportive footwear, and smoking cessation.','Pregabalin 75mg capsule','1 capsule once daily at bedtime','30 capsules','2026-11-10 12:17:00','2026-11-10 12:17:00'),(8,'Psychiatry','Generalized Anxiety Disorder (GAD)','Cognitive Behavioral Therapy (CBT). Breathing exercises, relaxation techniques, and mindfulness. Regular sleep schedule and caffeine restriction.','Escitalopram 10 mg tablet','Take once daily as prescribed','30 tablets','2026-11-10 12:17:00','2026-11-10 12:17:00'),(9,'Psychiatry','Major Depressive Disorder (Single / Recurrent Episode)','Psychotherapy (CBT/Interpersonal Therapy), behavioral activation, daily sunlight exposure, structured routine, and psychoeducation on medication adherence.','Sertraline 50mg tablet','1 tablet once daily in the morning','30 tablets','2026-11-10 12:17:00','2026-11-10 12:17:00'),(10,'Psychiatry','Insomnia Disorder (Non-Organic)','Cognitive Behavioral Therapy for Insomnia (CBT-I), sleep hygiene education (consistent wake time, dark/cool sleep environment, no screen exposure 1 hr before bed).','Zolpidem 10mg tablet','1 tablet at bedtime as needed for sleep (short-term use)','14 tablets','2026-11-10 12:17:00','2026-11-10 12:17:00');
/*!40000 ALTER TABLE `clinical_guidelines` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `doctors`
--

DROP TABLE IF EXISTS `doctors`;
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
-- Dumping data for table `doctors`
--

LOCK TABLES `doctors` WRITE;
/*!40000 ALTER TABLE `doctors` DISABLE KEYS */;
INSERT INTO `doctors` VALUES (1,'Dr. Alan Magno','Adult Psychiatry','psychiatry','08:00:00','14:30:00','in_clinic','2026-10-13','2026-09-22 02:47:25','2026-10-14 09:29:45',20,NULL),(2,'Dr. Sarah Reyes','Child & Adolescent Psychiatry','psychiatry','13:00:00','17:00:00','available','2026-10-13','2026-09-22 02:47:25','2026-10-13 08:50:48',20,NULL),(3,'Dr. Emilio Cruz','Clinical Neurology','neurology','09:00:00','16:00:00','in_clinic','2026-10-13','2026-09-22 02:47:25','2026-10-13 23:12:35',20,NULL),(4,'Dr. Maria Santos','Internal Medicine','general','08:00:00','16:00:00','available','2026-10-13','2026-09-22 02:47:25','2026-10-13 11:15:13',20,NULL),(5,'Dr. Juan Dela Cruz','Family Medicine & Routine Check-ups','general','08:00:00','16:00:00','available','2026-10-13','2026-09-22 02:47:25','2026-10-13 08:50:48',20,NULL),(6,'Dr. Antonio Ramos','Preventative Care & Routine Check-ups','general','08:30:00','16:00:00','on_break','2026-10-13','2026-10-13 08:50:48','2026-10-22 02:58:06',20,NULL),(7,'Dr. Elena Fernandez','Chronic Disease Management','general','09:00:00','16:30:00','available','2026-10-13','2026-10-13 08:50:48','2026-10-13 08:50:48',20,NULL),(8,'Dr. Benjamin Cruz','Pediatric Care','general','08:30:00','15:30:00','available','2026-10-13','2026-10-13 08:50:48','2026-10-13 08:50:48',20,NULL),(9,'Dr. Ricardo De Leon','General Surgery & Minor Procedures','general','10:00:00','16:00:00','available','2026-10-13','2026-10-13 08:50:48','2026-10-13 08:50:48',20,NULL),(10,'Dr. Robert Tan','Headache & Migraine Management','neurology','08:00:00','14:00:00','in_clinic','2026-10-13','2026-10-13 08:50:48','2026-10-22 02:08:03',20,NULL),(11,'Dr. Catherine Gomez','Stroke Rehabilitation','neurology','09:00:00','15:30:00','in','2026-10-13','2026-10-13 08:50:48','2026-11-08 09:57:35',20,NULL),(12,'Dr. Dominic Bautista','Epilepsy & Seizure Disorders','neurology','10:00:00','16:00:00','in_clinic','2026-10-13','2026-10-13 08:50:48','2026-10-13 23:12:35',20,NULL),(13,'Dr. Karen Villanueva','Movement Disorders','neurology','08:30:00','14:30:00','in_clinic','2026-10-13','2026-10-13 08:50:48','2026-10-14 02:37:08',20,NULL),(14,'Dr. Gabriel Mendoza','Substance Abuse & Addiction Treatment','psychiatry','08:00:00','15:00:00','in_clinic','2026-10-13','2026-10-13 08:50:48','2026-10-14 07:33:37',20,NULL),(15,'Dr. Patricia Lim','Anxiety & Depression Management','psychiatry','09:00:00','16:00:00','available','2026-10-13','2026-10-13 08:50:48','2026-10-13 08:50:48',20,NULL),(17,'Dr. Angelo Mercado, MD','Family Medicine & General Practice','general','08:00:00','12:00:00','in','2026-11-09','2026-11-09 14:43:42','2026-11-16 19:41:03',20,NULL),(18,'Dr. Beatrice Alcantara, MD','Routine Check-ups & Preventative Care','general','13:00:00','17:00:00','available','2026-11-09','2026-11-09 14:43:42','2026-11-09 14:43:42',20,NULL),(19,'Dr. Carlo Valenzuela, MD','Preventive Healthcare & Physical Examination','general','08:00:00','12:00:00','available','2026-11-09','2026-11-09 14:43:42','2026-11-09 14:43:42',20,NULL),(20,'Dr. Francesca David, MD','Stroke Evaluation','neurology','13:00:00','17:00:00','available','2026-11-09','2026-11-09 14:43:42','2026-11-09 14:43:42',20,NULL),(21,'Dr. Gerald Ocampo, MD','Neuromuscular Medicine','neurology','08:00:00','12:00:00','available','2026-11-09','2026-11-09 14:43:42','2026-11-09 14:43:42',20,NULL),(22,'Dr. Hannah Manalo, MD','Dementia Evaluation','neurology','13:00:00','17:00:00','available','2026-11-09','2026-11-09 14:43:42','2026-11-09 14:43:42',20,NULL),(23,'Dr. Ian Salazar, MD','Neurological Diagnostic Testing','neurology','08:00:00','12:00:00','available','2026-11-09','2026-11-09 14:43:42','2026-11-09 14:43:42',20,NULL),(24,'Dr. Lourdes Castillo, MD','Anxiety/Panic/Depression Management','psychiatry','13:00:00','17:00:00','available','2026-11-09','2026-11-09 14:43:42','2026-11-09 14:43:42',20,NULL),(25,'Dr. Manuel Santiago, MD','Mood Disorders','psychiatry','08:00:00','12:00:00','available','2026-11-09','2026-11-09 14:43:42','2026-11-09 14:43:42',20,NULL),(26,'Dr. Nicole Dizon, MD','Substance Abuse','psychiatry','13:00:00','17:00:00','available','2026-11-09','2026-11-09 14:43:42','2026-11-09 14:43:42',20,NULL),(27,'Dr. Oscar Villanueva, MD','Geriatric Psychiatry','psychiatry','08:00:00','12:00:00','available','2026-11-09','2026-11-09 14:43:42','2026-11-09 14:43:42',20,NULL),(28,'Dr. Priscilla Evangelista, MD','Clinical Neuropsychiatry','psychiatry','13:00:00','17:00:00','available','2026-11-09','2026-11-09 14:43:42','2026-11-09 14:43:42',20,NULL),(29,'Dr. Rafael Navarro, MD','Psychotherapy','psychiatry','08:00:00','12:00:00','available','2026-11-09','2026-11-09 14:43:42','2026-11-09 14:43:42',20,NULL);
/*!40000 ALTER TABLE `doctors` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `login_history`
--

DROP TABLE IF EXISTS `login_history`;
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
-- Dumping data for table `login_history`
--

LOCK TABLES `login_history` WRITE;
/*!40000 ALTER TABLE `login_history` DISABLE KEYS */;
INSERT INTO `login_history` VALUES (1,4,'alexgallardo13','staff','2026-10-14 00:53:46',NULL,'::1',NULL,'success'),(2,4,'alexgallardo13','staff','2026-10-14 01:09:13',NULL,'::1',NULL,'success'),(3,4,'alexgallardo13','staff','2026-10-14 01:27:11',NULL,'::1',NULL,'success'),(4,4,'alexgallardo13','staff','2026-10-14 01:42:25',NULL,'::1',NULL,'success'),(5,1,'admin','admin','2026-10-21 15:32:51',NULL,'::1',NULL,'success'),(6,1,'admin','admin','2026-10-21 23:09:46',NULL,'::1',NULL,'success'),(7,4,'alexgallardo13','staff','2026-10-21 23:50:21',NULL,'::1',NULL,'success');
/*!40000 ALTER TABLE `login_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `map_locations`
--

DROP TABLE IF EXISTS `map_locations`;
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
-- Dumping data for table `map_locations`
--

LOCK TABLES `map_locations` WRITE;
/*!40000 ALTER TABLE `map_locations` DISABLE KEYS */;
INSERT INTO `map_locations` VALUES (1,'Guard House Gate 2','01A',238,102.01),(2,'Radiology Complex','',379.999,251),(3,'EFMU Building','',385.997,331.012),(4,'HIMU','',378,372.01),(5,'Finance Management','',342.001,421.503),(6,'Custodial Care Unit (CCU)','',342,613.972),(7,'Male Ward Pavilion','',428.008,618.997),(8,'Nurse Station','',371.994,658.989),(9,'Observation Unit','',422.005,666),(10,'Female Ward Pavilion','',412.516,714.992),(11,'Old Custodial Care Unit','',317.266,770.02),(12,'Old Infirmary Unit','',412.245,774.758),(13,'Cadaver Holding Area','',311.994,809.244),(14,'TB-Dots','',262.013,869.994),(15,'Linen and Laundry','',185.014,951.999),(16,'General Hospital','',493.015,202.001),(17,'Power House','',150.07,829.932),(18,'Laboratory Complex','',530.995,277.135),(19,'Nutrition and Dietetics (NDU)','',494.005,330.997),(20,'Staff Dormitory','',558.006,355.973),(21,'Procurement and Materials Management (MMU)','',498.996,377.94),(22,'Warehouse','',481.994,415.995),(23,'Material Recovery Facility','',521.002,413.471),(24,'Hospital Operations and Patients Support (HOPS)','',402.5,432.507),(25,'COA Building','',480.501,445.746),(26,'Male Dorm','',426.984,524.985),(27,'Sewerage Treatment Plant','',496.009,582.991),(28,'OPU Building','',101.99,244.991),(30,'Lazareto Treatment Hub','',105.001,421.995),(31,'Chapel','',105.006,476.068),(32,'Covered Court','',215.002,571.003),(33,'Isolation Building-1','',208.009,702.007),(34,'Occupational Therapy Building','',211.998,658),(35,'Male Dorm A&B','',134.999,722.969),(36,'Isolation Building-2 (Bayanihan Bldg)','',114.17,590.973),(37,'Canteen (PACD)','',253.008,192.001),(38,'Integrated Medical Waste Autoclave Machine House','',224.999,909.989),(39,'Nurse Station (Male Ward)','',318.959,661.745),(40,'Medical Ward','',182.009,394.004),(41,'Malasakit Center Building','',84.008,448.063);
/*!40000 ALTER TABLE `map_locations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_resets`
--

DROP TABLE IF EXISTS `password_resets`;
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
-- Dumping data for table `password_resets`
--

LOCK TABLES `password_resets` WRITE;
/*!40000 ALTER TABLE `password_resets` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_resets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `patient_notification_states`
--

DROP TABLE IF EXISTS `patient_notification_states`;
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
-- Dumping data for table `patient_notification_states`
--

LOCK TABLES `patient_notification_states` WRITE;
/*!40000 ALTER TABLE `patient_notification_states` DISABLE KEYS */;
INSERT INTO `patient_notification_states` VALUES (1,'pid:pt-2026-086','notif-wait-57',1,1,'2026-11-13 11:41:16'),(2,'pid:pt-2026-086','notif-med-24',1,1,'2026-11-13 11:41:16'),(8,'uid:10','notif-med-24',1,1,'2026-11-13 13:17:35'),(9,'uid:10','notif-wait-61',1,1,'2026-11-13 13:17:35'),(10,'uid:10','notif-wait-57',1,1,'2026-11-13 13:17:35'),(12,'uid:1','notif-med-63',1,1,'2026-11-17 10:24:24'),(13,'uid:1','notif-confirm-62',1,1,'2026-11-17 10:24:24'),(14,'uid:1','notif-wait-73',1,1,'2026-11-17 10:24:24'),(15,'uid:1','notif-wait-78',1,1,'2026-11-17 10:24:24'),(16,'uid:1','notif-confirm-77',1,1,'2026-11-17 10:24:24'),(17,'uid:1','notif-med-24',1,1,'2026-11-17 10:24:24');
/*!40000 ALTER TABLE `patient_notification_states` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `patient_remember_tokens`
--

DROP TABLE IF EXISTS `patient_remember_tokens`;
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
-- Dumping data for table `patient_remember_tokens`
--

LOCK TABLES `patient_remember_tokens` WRITE;
/*!40000 ALTER TABLE `patient_remember_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `patient_remember_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `patients`
--

DROP TABLE IF EXISTS `patients`;
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
-- Dumping data for table `patients`
--

LOCK TABLES `patients` WRITE;
/*!40000 ALTER TABLE `patients` DISABLE KEYS */;
INSERT INTO `patients` VALUES (2,NULL,'PT-2026-002',NULL,'Neurology','Maria Santos',NULL,NULL,NULL,'Female','1985-07-22',40,'09189876543','maria@email.com',NULL,NULL,0,0,NULL,NULL,NULL,NULL,NULL,'456 Rizal Ave., Mariveles',1,NULL,'Patient (Me)',NULL,NULL,'Frequent migraines','Follow-up Checkup','Recurring migraines with aura, twice a week.',NULL,NULL,'returning','online','E-consultation','2026-05-12',NULL,'completed',NULL,NULL,'2026-09-22 02:47:25','2026-10-16 14:24:44',NULL,NULL,NULL,NULL,0,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Completed',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,NULL),(3,NULL,'PT-2026-003',NULL,'Cardiology','Ricardo Lopez',NULL,NULL,NULL,'Male','1975-11-08',50,'09201112233',NULL,NULL,NULL,0,0,NULL,NULL,NULL,NULL,NULL,'789 Luna St., Bataan',0,NULL,'Patient (Me)',NULL,NULL,'Chest tightness and shortness of breath','Initial Evaluation','Intermittent chest pain on exertion.',NULL,NULL,'first-time','walk-in','Face-to-Face','2026-05-20',NULL,'pending',NULL,NULL,'2026-09-22 02:47:25','2026-09-22 02:47:25',NULL,NULL,NULL,NULL,0,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Pending',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,NULL),(4,NULL,'PT-2026-004',NULL,'General','Tungtung Sahur',NULL,NULL,NULL,'Male','2001-12-13',24,'09774152027',NULL,NULL,NULL,0,0,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'Patient (Me)',NULL,NULL,'masakit buong right side ng mukha',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-22',NULL,'no-show',NULL,NULL,'2026-10-04 08:21:05','2026-10-04 08:55:08',NULL,NULL,NULL,'',0,0,'Direct','Non-Employee','Unschedule/Walk In','HRN-2026-5036714','Internal Medicine','17:30:00',NULL,'Pending',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,NULL),(5,NULL,'PT-2026-005',NULL,'General Medical','Juan Dela Cruz',NULL,NULL,NULL,'Male','1990-03-15',36,'09171234567','juan@email.com',NULL,NULL,0,0,NULL,NULL,NULL,NULL,NULL,'123 Mabini St., Manila',0,NULL,'Patient (Me)',NULL,NULL,'Auto Follow-Up',NULL,NULL,NULL,NULL,'returning','walk-in',NULL,'2026-10-30',NULL,'ongoing',NULL,NULL,'2026-10-04 09:34:27','2026-11-10 13:02:31',NULL,NULL,NULL,NULL,0,0,NULL,NULL,NULL,NULL,'General Medical Consultation',NULL,NULL,'Pending',NULL,'lahat ng gin logbi','2026-10-30','huwag kasi','inom gin mansi','2 packs of pancit canton','anqueandrea22','2026-10-04 17:34:27',0,0,NULL),(6,NULL,'PT-2026-006',NULL,'OPU Psychiatry','Lyka Justine Valintus',NULL,NULL,NULL,NULL,NULL,NULL,'09090909090',NULL,NULL,NULL,0,0,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'Patient (Me)',NULL,NULL,'sakit kiffarut',NULL,NULL,NULL,NULL,'first-time','walk-in','Teleconsultation','2026-10-05','Neurology','no-show',NULL,NULL,'2026-10-04 14:11:34','2026-10-10 14:58:06',NULL,'Alex Gallardo','','yes',0,1,NULL,NULL,NULL,'HRN-2026-4693500',NULL,'08:30:00',NULL,'Pending',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,NULL),(7,NULL,'PT-2026-007',NULL,'OPU Psychiatry','Dea Sahur',NULL,NULL,NULL,'Other','2021-01-31',5,'09875445564','',NULL,NULL,0,0,NULL,NULL,NULL,NULL,NULL,'asdsadsadasdasd',0,NULL,'Patient (Me)',NULL,NULL,'asdasdsa','Initial Evaluation','asdsad',NULL,NULL,'first-time','walk-in','Face-to-Face','2026-10-10','','transferred',NULL,NULL,'2026-10-09 01:14:10','2026-10-13 09:41:58',NULL,NULL,NULL,NULL,0,0,NULL,NULL,NULL,'HRN-2026-9628246',NULL,'23:23:00',NULL,'Pending',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,NULL),(9,12,'PT-2026-009',NULL,'Neurology','Jarelle Gallardo',NULL,NULL,NULL,'Male','1842-02-03',184,'09441636421',NULL,NULL,NULL,0,0,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'Patient (Me)',NULL,NULL,'unsa unsa n lang',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2099-10-26',NULL,'approved',NULL,NULL,'2026-10-13 06:26:36','2026-11-13 09:46:16',NULL,NULL,NULL,'',0,0,'Direct','Non-Employee','Unschedule/Walk In','HRN-2026-6120119','Neurology Consultation','09:30:00',NULL,'Pending',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,NULL),(12,NULL,'PT-2026-010',NULL,'Neurology','Dea Sahur',NULL,NULL,NULL,'Other','2021-01-31',5,'09875445564','',NULL,NULL,0,0,NULL,NULL,NULL,NULL,NULL,'asdsadsadasdasd',0,NULL,'Patient (Me)',NULL,NULL,'asdasdsa','Initial Evaluation','asdsad',NULL,NULL,'first-time','walk-in','Face-to-Face','2026-10-13','OPU Psychiatry','completed',NULL,NULL,'2026-10-13 09:41:58','2026-10-16 14:24:44',NULL,NULL,NULL,NULL,0,0,NULL,NULL,NULL,'HRN-2026-9628246',NULL,'23:23:00',NULL,'Completed',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,NULL),(16,NULL,'PT-2026-011',NULL,'Neurology','Josefina B. Gallardo','Josefina','Gallardo','B.','Female','1967-06-26',59,'09060911608','',NULL,NULL,0,0,NULL,NULL,NULL,NULL,NULL,'1696 Maap Road, Alas-asin',0,NULL,'Patient (Me)',NULL,NULL,'Clinical Neurology Consultation','Initial Evaluation','',NULL,NULL,'first-time','walk-in','Face-to-Face','2026-10-17','','completed','Dr. Emilio Cruz','2026-10-13','2026-10-13 11:24:29','2026-10-14 07:49:47',NULL,NULL,NULL,NULL,0,0,NULL,NULL,NULL,'HRN-2026-1839956',NULL,'10:30:00',NULL,'Sent','2026-10-14 03:42:25',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,NULL),(18,NULL,'PT-2026-012',NULL,'Neurology','Garry B. Gallardo','Garry','Gallardo','B.','Male','1995-06-27',31,'09423146213',NULL,NULL,NULL,0,0,NULL,NULL,NULL,NULL,NULL,'1696 Maap Road, Alas-asin',0,NULL,'Patient (Me)',NULL,NULL,'Stroke Rehabilitation','Initial Evaluation',NULL,NULL,NULL,'first-time','walk-in','Face-to-Face','2026-10-18',NULL,'transferred','Dr. Catherine Gomez','2026-10-13','2026-10-13 12:03:15','2026-10-14 06:26:59',NULL,NULL,NULL,NULL,0,0,NULL,NULL,NULL,'HRN-2026-7766074',NULL,'08:30:00',NULL,'Pending',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,NULL),(19,NULL,'PT-2026-013',NULL,'General Medical','Alexito Gallardo','Alexito','Gallardo',NULL,'Male','1967-08-26',59,'09423713316',NULL,NULL,NULL,0,0,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'Patient (Me)',NULL,NULL,'Routine Check-ups & Preventative Care',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2027-01-26',NULL,'ongoing','Dr. Antonio Ramos','2026-10-13','2026-10-13 12:29:25','2026-11-10 12:45:02',NULL,NULL,NULL,'Attending Physician: Dr. Antonio Ramos',0,0,'Direct','Employee','Unschedule/Walk In','HRN-2026-4979516','General Medical Consultation','08:00:00',NULL,'Pending',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,NULL),(20,NULL,'PT-2026-014',NULL,'General Medical','Joan Gallardo','Joan','Gallardo',NULL,'Female','1989-07-23',37,'09413416565',NULL,NULL,NULL,0,0,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'Patient (Me)',NULL,NULL,'Routine Check-ups & Preventative Care',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-26',NULL,'ongoing','Dr. Antonio Ramos','2026-10-13','2026-10-13 12:49:40','2026-11-10 12:44:54',NULL,NULL,NULL,'Attending Physician: Dr. Antonio Ramos',0,0,'Call','Employee','Unschedule/Walk In','HRN-2026-1857744','General Medical Consultation','09:50:00',NULL,'Pending',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,NULL),(21,NULL,'PT-2026-015',NULL,'General Management','Joan Gallardo','Joan','Gallardo',NULL,'Female','1988-08-27',38,'09713364896',NULL,NULL,NULL,0,0,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'Patient (Me)',NULL,NULL,'Routine Check-ups & Preventative Care',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-27',NULL,'completed','Dr. Antonio Ramos','2026-10-13','2026-10-13 12:56:58','2026-10-16 14:24:44',NULL,NULL,NULL,'Attending Physician: Dr. Antonio Ramos',0,0,'Direct','Employee','Unschedule/Walk In','HRN-2026-6528196','Routine Check-ups & Preventative Care','09:30:00',NULL,'Completed',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,NULL),(22,NULL,'PT-2026-016',NULL,'Neurology','Joseph D. Lee','Joseph','Lee','D.','Male','2004-06-08',22,'09872313055',NULL,NULL,NULL,0,0,NULL,NULL,NULL,NULL,NULL,'balon st. yakal',0,NULL,'Patient (Me)',NULL,NULL,'Headache & Migraine Management','Initial Evaluation',NULL,NULL,NULL,'first-time','walk-in','Face-to-Face','2026-10-20',NULL,'completed','Dr. Robert Tan','2026-10-14','2026-10-13 23:10:42','2026-10-14 00:57:39',NULL,NULL,NULL,NULL,0,0,NULL,NULL,NULL,'HRN-2026-3027921',NULL,'09:40:00',NULL,'Sent','2026-10-14 02:57:39',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,NULL),(23,NULL,'PT-2026-017',NULL,'Neurology','Alexander Reyes','Alexander','Reyes',NULL,'Male','2000-12-05',25,'09715325654',NULL,NULL,NULL,0,0,NULL,NULL,NULL,NULL,NULL,'1965 maap road',0,NULL,'Patient (Me)',NULL,NULL,'Movement Disorders','Initial Evaluation',NULL,NULL,NULL,'first-time','walk-in','Face-to-Face','2026-10-28',NULL,'no-show','Dr. Emilio Cruz','2026-11-08','2026-10-14 02:37:08','2026-11-08 09:53:56',NULL,NULL,NULL,NULL,0,0,NULL,NULL,NULL,'HRN-2026-2966139',NULL,'10:30:00',NULL,'Pending',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,NULL),(24,10,'PT-2026-018',NULL,'Neurology','Alexu B. Gallardo','Alexu','Gallardo','B','Male','2001-12-13',24,'+63 9774152027','alexgallardo121301@gmail.com','alexgallardo121301','$2y$12$g1VBP3c1Er1vJTCtzTTxDOiDurtT82s.jtsAmwCk4yqgzIZK/KDUy',0,1,'072544350a4512411bcfc0038a188a1ae7f57ba92aa40007e17b5105bd7aeca9','2026-11-01 09:56:56','2026-10-31 09:57:34',NULL,NULL,'1696 Maap Road, Alas-Asin, Mariveles, Bataan',0,NULL,'Patient (Me)',NULL,NULL,'Headache & Migraine Management','Follow-up Checkup','masakit ang julo',NULL,NULL,'first-time','walk-in','Face-to-Face','2026-10-19',NULL,'completed','Dr. Robert Tan','2026-10-14','2026-10-14 06:21:54','2026-11-14 03:20:25',NULL,NULL,NULL,NULL,0,0,NULL,NULL,NULL,'HRN-2026-6688774',NULL,'12:29:00',NULL,'Sent','2026-10-14 08:26:07','{\"medicine_name\":\"Sumatriptan 50mg\",\"dosage\":\"50mg (1 tablet at onset)\",\"quantity\":\"10 tablets\",\"frequency\":\"Once per day (Daily)\",\"diagnosis\":\"Migraine with Aura\",\"treatment\":\"Hydration and lifestyle protocol\",\"prescription\":\"Sumatriptan 50mg #10\",\"recorded_by\":\"Dr. Robert Tan\",\"prescribed_at\":\"2026-10-19 12:45:00\"}',NULL,'Migraine with Aura & Episodic Tension Headache','Hydration therapy, lifestyle modification, stress reduction protocol','Sumatriptan 50mg #10\nSig: Take 1 tablet at onset of headache',NULL,NULL,0,0,NULL),(25,NULL,'PT-2026-019',NULL,'General Management','Garry B. Gallardo',NULL,NULL,NULL,'Male','1995-06-27',31,'09423146213',NULL,NULL,NULL,0,0,NULL,NULL,NULL,NULL,NULL,'1696 Maap Road, Alas-asin',0,NULL,'Patient (Me)',NULL,NULL,'Stroke Rehabilitation','Initial Evaluation',NULL,NULL,NULL,'first-time','walk-in','Face-to-Face','2026-10-14','Neurology','transferred',NULL,NULL,'2026-10-14 06:26:59','2026-10-14 09:14:05',NULL,NULL,NULL,NULL,0,0,NULL,NULL,NULL,'HRN-2026-7766074',NULL,'08:30:00',NULL,'Sent','2026-10-14 08:28:47',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,NULL),(26,NULL,'PT-2026-020',NULL,'General Management','Jaemarl Mossendon',NULL,NULL,NULL,NULL,NULL,NULL,'Walk-in',NULL,NULL,NULL,0,0,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'Patient (Me)',NULL,NULL,'sakit batok',NULL,NULL,NULL,NULL,'first-time','walk-in',NULL,'2026-10-14',NULL,'no-show',NULL,NULL,'2026-10-14 06:35:13','2026-10-14 09:10:38',NULL,NULL,NULL,NULL,1,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Pending',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,NULL),(27,NULL,'PT-2026-021',NULL,'OPU Psychiatry','Alexito Gallardo',NULL,NULL,NULL,NULL,NULL,NULL,'Walk-in',NULL,NULL,NULL,0,0,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'Patient (Me)',NULL,NULL,'yes yes',NULL,NULL,NULL,NULL,'first-time','walk-in',NULL,'2026-10-14',NULL,'no-show',NULL,NULL,'2026-10-14 07:32:46','2026-10-14 07:33:18',NULL,NULL,NULL,NULL,1,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Pending',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,NULL),(28,NULL,'PT-2026-022',NULL,'Neurology','Jeong Hilarious',NULL,NULL,NULL,NULL,NULL,NULL,'Walk-in',NULL,NULL,NULL,0,0,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'Patient (Me)',NULL,NULL,'basta',NULL,NULL,NULL,NULL,'first-time','walk-in',NULL,'2026-10-14',NULL,'completed',NULL,NULL,'2026-10-14 07:51:08','2026-10-16 14:24:44',NULL,NULL,NULL,NULL,1,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Completed',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,NULL),(29,NULL,'PT-2026-023',NULL,'Neurology','juhoon imnida',NULL,NULL,NULL,NULL,NULL,NULL,'Walk-in',NULL,NULL,NULL,0,0,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'Patient (Me)',NULL,NULL,'hi hello',NULL,NULL,NULL,NULL,'first-time','walk-in',NULL,'2026-10-14',NULL,'completed','Dr. Dominic Bautista','2026-11-08','2026-10-14 08:02:51','2026-11-08 09:27:45',NULL,NULL,NULL,NULL,1,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Completed',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,NULL),(30,NULL,'PT-2026-024',NULL,'General Medical','hyghuu asdasfd','hyghuu','asdasfd',NULL,'Male','2005-12-04',20,'09232430135',NULL,NULL,NULL,0,0,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'Patient (Me)',NULL,NULL,'Routine Check-ups & Preventative Care',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-14',NULL,'completed','Dr. Antonio Ramos','2026-10-14','2026-10-14 09:07:21','2026-11-10 12:43:58',NULL,NULL,NULL,'Attending Physician: Dr. Antonio Ramos',0,0,'Direct','Employee','Scheduled','HRN-2026-6825712','General Medical Consultation','17:30:00',NULL,'Completed',NULL,'{\"medicine_name\":\"Amlodipine 5mg tablet\",\"dosage\":\"1 tablet once daily\",\"quantity\":\"30 tablets\",\"diagnosis\":\"Essential Hypertension (High Blood Pressure)\",\"treatment\":\"Regular monitoring of blood pressure, blood sugar, and cholesterol levels. Healthy diet, regular physical activity, sodium reduction (<2g\\/day), weight management, and smoking cessation.\",\"prescription\":\"Rx: Amlodipine 5mg tablet #30 tablets\\nSig: 1 tablet once daily\",\"recorded_by\":\"ricoblanco123\",\"prescribed_at\":\"2026-11-10 20:43:58\"}',NULL,'Essential Hypertension (High Blood Pressure)','Regular monitoring of blood pressure, blood sugar, and cholesterol levels. Healthy diet, regular physical activity, sodium reduction (<2g/day), weight management, and smoking cessation.','Rx: Amlodipine 5mg tablet #30 tablets\nSig: 1 tablet once daily','ricoblanco123','2026-11-10 20:43:58',0,0,NULL),(31,NULL,'PT-2026-025',NULL,'Neurology','Garry B. Gallardo',NULL,NULL,NULL,'Male','1995-06-27',31,'09423146213',NULL,NULL,NULL,0,0,NULL,NULL,NULL,NULL,NULL,'1696 Maap Road, Alas-asin',0,NULL,'Patient (Me)',NULL,NULL,'Stroke Rehabilitation','Initial Evaluation',NULL,NULL,NULL,'first-time','walk-in','Face-to-Face','2026-10-14','General Management','transferred','Dr. Dominic Bautista','2026-11-08','2026-10-14 09:14:05','2026-11-08 09:58:05',NULL,NULL,NULL,NULL,0,0,NULL,NULL,NULL,'HRN-2026-7766074',NULL,'08:30:00',NULL,'Pending',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,NULL),(32,NULL,'PT-2026-026',NULL,'Neurology','Daniel Padila',NULL,NULL,NULL,NULL,NULL,NULL,'Walk-in',NULL,NULL,NULL,0,0,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'Patient (Me)',NULL,NULL,'asdasd',NULL,NULL,NULL,NULL,'first-time','walk-in',NULL,'2026-10-14',NULL,'ongoing',NULL,NULL,'2026-10-14 09:19:30','2026-11-10 12:13:00',NULL,NULL,NULL,NULL,1,0,NULL,NULL,NULL,NULL,'Neurology Consultation',NULL,NULL,'Pending',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,NULL),(33,NULL,'PT-2026-027',NULL,'Neurology','Daniel Padila','Daniel','Padila',NULL,'Male','2005-10-20',20,'09741521122',NULL,NULL,NULL,0,0,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'Patient (Me)',NULL,NULL,'Routine Check-ups & Preventative Care',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-29',NULL,'ongoing','Dr. Antonio Ramos','2026-10-14','2026-10-14 09:25:07','2026-11-10 12:52:31',NULL,NULL,NULL,'Attending Physician: Dr. Antonio Ramos',0,0,'Direct','Employee','Scheduled','HRN-2026-5784607','Neurology Consultation','17:00:00',NULL,'Pending',NULL,'','2026-10-29','diabetes type 2','bawal matatamis','metformin','ricoblanco123','2026-10-22 10:51:57',0,0,NULL),(34,NULL,'PT-2026-028',NULL,'OPU Psychiatry','Garry B. Gallardo',NULL,NULL,NULL,'Male','1995-06-27',31,'09423146213',NULL,NULL,NULL,0,0,NULL,NULL,NULL,NULL,NULL,'1696 Maap Road, Alas-asin',0,NULL,'Patient (Me)',NULL,NULL,'Stroke Rehabilitation','Initial Evaluation',NULL,NULL,NULL,'first-time','walk-in','Face-to-Face','2026-10-14','Neurology','transferred',NULL,NULL,'2026-10-14 09:28:25','2026-10-14 09:29:12',NULL,NULL,NULL,NULL,0,0,NULL,NULL,NULL,'HRN-2026-7766074',NULL,'08:30:00',NULL,'Pending',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,NULL),(35,NULL,'PT-2026-029',NULL,'OPU Psychiatry','Garry B. Gallardo',NULL,NULL,NULL,'Male','1995-06-27',31,'09423146213',NULL,NULL,NULL,0,0,NULL,NULL,NULL,NULL,NULL,'1696 Maap Road, Alas-asin',0,NULL,'Patient (Me)',NULL,NULL,'Stroke Rehabilitation','Initial Evaluation',NULL,NULL,NULL,'first-time','walk-in','Face-to-Face','2026-10-14','OPU Psychiatry','pending',NULL,NULL,'2026-10-14 09:29:12','2026-10-14 09:29:12',NULL,NULL,NULL,NULL,0,0,NULL,NULL,NULL,'HRN-2026-7766074',NULL,'08:30:00',NULL,'Pending',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,NULL),(36,NULL,'PT-2026-030',NULL,'General Management','Maria Test A. Inquirer','Maria Test','Inquirer','A.','Female','1995-05-15',31,'09123456789',NULL,NULL,NULL,0,0,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'Patient (Me)',NULL,NULL,'General Inquiry — Questions about neurology clinic requirements and HMO coverage','General Inquiry',NULL,NULL,NULL,'first-time','online','Face-to-Face',NULL,NULL,'completed',NULL,NULL,'2026-10-21 23:08:59','2026-10-21 23:12:22',NULL,NULL,NULL,'Inquirer requesting callback regarding HMO accreditation for neurological EEG tests.',0,1,'Online Portal','N/A','Scheduled','HRN-2026-030','General Medicine',NULL,NULL,'Completed',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,NULL),(37,NULL,'PT-2026-031',NULL,'Neurology','Carlos D. Mendoza','Carlos','Mendoza','D.','Male','1988-11-20',37,'09171234567',NULL,NULL,NULL,0,0,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'Patient (Me)',NULL,NULL,'Frequent severe migraines and numbness in fingers','General Inquiry',NULL,NULL,NULL,'first-time','online','Face-to-Face',NULL,NULL,'rejected','Dr. Emilio Cruz','2026-11-08','2026-10-21 23:13:08','2026-11-08 09:56:20',NULL,NULL,NULL,'Inquiring if neurological examination requires fasting or prior MRI scans before consultation.',0,1,'Online Portal','N/A','Scheduled','HRN-2026-031','Neurology',NULL,NULL,'Pending',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,NULL),(38,NULL,'PT-2026-032',NULL,'Neurology','Lyka Miringhoy','Lyka','Miringhoy',NULL,'Female','2004-02-01',22,'09774152024',NULL,NULL,NULL,0,0,NULL,NULL,NULL,NULL,NULL,'balon anito',0,NULL,'Patient (Me)',NULL,NULL,'Migraine, Tension & Chronic Severe Headaches — Notes: dfsdf','Initial Evaluation','dfsdf',NULL,NULL,'first-time','online','Face-to-Face','2026-10-24',NULL,'ongoing','Dr. Robert Tan','2026-10-22','2026-10-22 02:08:03','2026-11-08 09:56:10',NULL,NULL,NULL,NULL,0,0,NULL,NULL,'Scheduled','HRN-2026-032',NULL,'09:30:00',NULL,'Pending',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,NULL),(39,NULL,'PT-2026-48BE7',NULL,'Neurology','Maria C. Santos','Maria','Santos','C','Female','1995-05-14',31,'09123456789','testpatient_1793380648@example.com','mariasantos_1793380648','$2y$11$7CRRwHztHC4V30Uie/v96.jT59UqTLdsS643Fa2X4l7k5mwgfig6y',0,1,NULL,'2026-11-01 01:17:28','2026-10-31 01:17:31',NULL,NULL,'123 Poblacion, Mariveles, Bataan',0,NULL,'Patient (Me)',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'first-time','walk-in',NULL,NULL,NULL,'approved',NULL,NULL,'2026-10-30 17:17:28','2026-11-10 13:13:35',NULL,NULL,NULL,NULL,0,0,NULL,NULL,NULL,NULL,'Neurology Consultation',NULL,NULL,'Pending',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,NULL),(40,NULL,'PT-2026-C92E3',NULL,'OPU Psychiatry','Maria C. Santos','Maria','Santos','C','Female','1995-05-14',31,'09123456789','testpatient_1793380785@example.com','mariasantos_1793380785','$2y$11$eVtbEvFlPrMHfpaBYZIKtuVJzdbevEVbz4UlmfaKtYZ2IF/fGYMVS',0,1,NULL,'2026-11-01 01:19:46','2026-10-31 01:19:48',NULL,NULL,'123 Poblacion, Mariveles, Bataan',0,NULL,'Patient (Me)',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'first-time','walk-in',NULL,NULL,NULL,'pending',NULL,NULL,'2026-10-30 17:19:46','2026-11-10 12:12:49',NULL,NULL,NULL,NULL,0,0,NULL,NULL,NULL,NULL,'OPU Psychiatry Consultation',NULL,NULL,'Pending',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,NULL),(42,NULL,'PT-2026-AE95F',NULL,'Neurology','Maria C. Santos','Maria','Santos','C','Female','1995-05-14',31,'09123456789','testpatient_1793382965@example.com','mariasantos_1793382965','$2y$11$MqoykvYvzkkqtlLp3mA5qevEusYZGjyp7b/b8hEQbLuMKLelbrJQe',0,0,'cad5100ab78a3ffa2ab6bf4a0d2d65f84e74e765ce1f96bf19025aedcc8d12db','2026-11-01 01:56:05',NULL,NULL,NULL,'123 Poblacion, Mariveles, Bataan',0,NULL,'Patient (Me)',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'first-time','walk-in',NULL,NULL,NULL,'approved',NULL,NULL,'2026-10-30 17:56:05','2026-11-10 13:13:30',NULL,NULL,NULL,NULL,0,0,NULL,NULL,NULL,NULL,'Neurology Consultation',NULL,NULL,'Pending',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,NULL),(43,NULL,'PT-2026-80D7F',NULL,'OPU Psychiatry','Maria C. Santos','Maria','Santos','C','Female','1995-05-14',31,'09123456789','testpatient_1793382987@example.com','mariasantos_1793382987','$2y$11$N7axljUx5FsAdTbe5AsEY.CIJnnZ.8vD.gWTm.rCGACX.CiIPju6e',0,1,NULL,'2026-11-01 01:56:28','2026-10-31 01:56:30',NULL,NULL,'123 Poblacion, Mariveles, Bataan',0,NULL,'Patient (Me)',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'first-time','walk-in',NULL,NULL,NULL,'pending',NULL,NULL,'2026-10-30 17:56:28','2026-11-08 16:27:38',NULL,NULL,NULL,NULL,0,0,NULL,NULL,NULL,NULL,'OPU Psychiatry Consultation',NULL,NULL,'Pending',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,NULL),(44,NULL,'PT-2026-74F60',NULL,'Neurology','Mamay B. Gallardo','Mamay','Gallardo','B','Female','1935-09-25',91,'+63 9774152027','akosipusa2001@gmail.com','mamaygallardo','$2y$12$CxRLz.dCvRX3lYzW655r8ey8TucTA25zI16quEhjCLdgZGjz0dxPS',0,1,'acaf31fdb667759bd0ea4dd14b11b44f0db2d0c3c792998e634587806f892eb3','2026-11-01 03:12:25','2026-10-31 03:13:44',NULL,NULL,'1696 Maap Road, Alas-Asin, Mariveles, Bataan',1,NULL,'Patient (Me)',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'first-time','walk-in',NULL,NULL,NULL,'approved',NULL,NULL,'2026-10-30 18:30:40','2026-11-10 13:03:41',NULL,NULL,NULL,NULL,0,0,NULL,NULL,NULL,NULL,'Neurology Consultation',NULL,NULL,'Pending',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,NULL),(45,NULL,'PT-2026-7EC47',NULL,'OPU Psychiatry','Ken Francis A. Sendon','Ken Francis','Sendon','A','Male','2003-04-24',23,'+63 9722366541','ddeya099@gmail.com','ddeya099','$2y$11$EFa9y9L4sGnX7jbFglS7ju91lQCDCFd9rna8XPQXHyJzOuEjJ9OxC',0,1,'0a39846adc6f31ef0e13a7aea92c6feb889b5e31d7c6f6ca1287b68aabd2a9cc','2026-11-01 03:29:50','2026-10-31 03:30:42',NULL,NULL,'Alas-Asin centro, mariveles, Bataan',1,NULL,'Patient (Me)',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'first-time','walk-in',NULL,NULL,NULL,'pending',NULL,NULL,'2026-10-30 19:29:50','2026-11-08 16:27:36',NULL,NULL,NULL,NULL,0,0,NULL,NULL,NULL,NULL,'OPU Psychiatry Consultation',NULL,NULL,'Pending',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,NULL),(47,10,'PT-2026-081',NULL,'Neurology','Alexu B. Gallardo','Alexu','Gallardo','B.','Male','2001-05-15',25,'+639123456789','alexgallardo131301@gmail.com','alexgallardo121301','$2y$11$5eBF9iNthLYEKzMEoxLXqO3Fu0.usupMjuAVeOgVtWlbeWHC/u5vm',0,1,NULL,NULL,NULL,NULL,NULL,'Mariveles, Bataan',1,NULL,'Patient (Me)',NULL,NULL,'Periodic headache and dizziness for 3 days','Routine Consultation','No prior chronic conditions',NULL,NULL,'returning','online','Face-to-Face','2026-11-02','Self-Referral','ongoing',NULL,NULL,'2026-10-31 13:13:57','2026-11-10 12:12:36',NULL,NULL,NULL,NULL,0,0,NULL,NULL,'Scheduled','HRN-2026-6688774','Neurology Consultation','08:00:00',NULL,'Pending',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,NULL),(48,10,'PT-2026-082',NULL,'OPU Psychiatry','Alexu B. Gallardo','Alexu','Gallardo','B.','Male','2001-12-13',24,'+639774152027','alexgallardo121301@gmail.com','alexgallardo121301','$2y$11$5eBF9iNthLYEKzMEoxLXqO3Fu0.usupMjuAVeOgVtWlbeWHC/u5vm',0,1,NULL,NULL,NULL,NULL,NULL,'1696 Maap Road, Alas-Asin, Mariveles, Bataan',0,NULL,'Patient (Me)',NULL,NULL,'masakit ulo pag walang pera','Routine Consultation',NULL,NULL,NULL,'returning','online','Face-to-Face','2026-11-02','Family / Friend','for_approval',NULL,NULL,'2026-10-31 13:29:39','2026-11-08 08:13:21',NULL,NULL,NULL,NULL,0,0,NULL,NULL,'Scheduled','HRN-2026-6688774','OPU Psychiatry Consultation','08:00:00',NULL,'Pending',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,NULL),(49,NULL,'PT-2026-0EAA5',NULL,'OPU Psychiatry','Bini Mikha','Bini','Mikha','','Female','2001-01-01',25,'+63 92121381821','maremrxxx@gmail.com','maremrxxx','$2y$11$Y6ENZyeBHRblrsVkqus5K.RvuHzLOtkuARbItts5kuV0gpFzqPP4K',0,0,'033f1043d8ba7c90e36d30b4525f65ad06391a55a17514503e8d0eca1b050d7d','2026-11-03 11:37:09',NULL,NULL,NULL,'pbb',1,NULL,'Patient (Me)',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'first-time','walk-in',NULL,NULL,NULL,'pending',NULL,NULL,'2026-11-02 03:37:09','2026-11-08 16:27:34',NULL,NULL,NULL,NULL,0,0,NULL,NULL,NULL,NULL,'OPU Psychiatry Consultation',NULL,NULL,'Pending',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,NULL),(50,10,'PT-2026-083',NULL,'OPU Psychiatry','Alexu B. Gallardo','Alexu','Gallardo','B.','Male','2001-12-13',24,'+639774152027','alexgallardo121301@gmail.com','alexgallardo121301','$2y$11$5eBF9iNthLYEKzMEoxLXqO3Fu0.usupMjuAVeOgVtWlbeWHC/u5vm',0,1,NULL,NULL,NULL,NULL,NULL,'1696 Maap Road, Alas-Asin, Mariveles, Bataan',0,NULL,'Patient (Me)',NULL,NULL,'jjj','Routine Consultation','jj///',NULL,NULL,'returning','online','Face-to-Face','2026-11-03','Self-Referral','ongoing',NULL,NULL,'2026-11-02 06:51:30','2026-11-08 08:13:21',NULL,NULL,NULL,NULL,0,0,NULL,NULL,'Scheduled','HRN-2026-6688774','OPU Psychiatry Consultation','08:00:00',NULL,'Pending',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,NULL),(51,NULL,'PT-2026-BDFD1',NULL,'Neurology','Loida Anque','Loida','Anque','','Other','1979-05-23',47,'+63 9751322323','lexaruugallardoo@gmail.com','loida','$2y$11$0hD02f.NEeIA/wHHakW6lOAZOxvx8gYybzJXmxKco88SLdqbKcyyO',0,0,'17bee74c186e41ab866ce128fb8f6fb64564ab52e742365c2db900d8fd0835f4','2026-11-03 20:51:58',NULL,NULL,NULL,'balon st. yakal',1,NULL,'Patient (Me)',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'first-time','walk-in',NULL,NULL,NULL,'ongoing',NULL,NULL,'2026-11-02 12:51:58','2026-11-10 13:13:55',NULL,NULL,NULL,NULL,0,0,NULL,NULL,NULL,NULL,'Neurology Consultation',NULL,NULL,'Pending',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,NULL),(54,12,'PT-2026-5BC6F',NULL,'General Medical','deya tadu','deya','tadu','','Female','2005-05-22',21,'+63 9656347647','aliksalt080824@gmail.com','aliksalt080824','$2y$12$SEMJ29w3Z9alOnZn..1JReyKc7KIjGlLpxcRy2CwmuFIRCc1HTc2q',0,1,'7bc9405724b7407d412ec55433d0782ad6da4ac0852165e09f8feb36e6a995d9','2026-11-03 21:47:39','2026-11-02 21:49:01',NULL,NULL,'balon yakal st.',1,NULL,'Patient (Me)',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'first-time','walk-in',NULL,NULL,NULL,'ongoing',NULL,NULL,'2026-11-02 13:47:39','2026-11-12 16:53:57',NULL,NULL,NULL,NULL,0,0,NULL,NULL,NULL,NULL,'General Medical Consultation',NULL,NULL,'Pending',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,NULL),(55,10,'PT-2026-084',NULL,'General Medical','Alexu B. Gallardo','Alexu','Gallardo','B.','Male','2001-12-13',24,'+639774152027','alexgallardo121301@gmail.com','alexgallardo121301','$2y$11$5eBF9iNthLYEKzMEoxLXqO3Fu0.usupMjuAVeOgVtWlbeWHC/u5vm',0,1,NULL,NULL,NULL,NULL,NULL,'1696 Maap Road, Alas-Asin, Mariveles, Bataan',0,NULL,'Patient (Me)',NULL,NULL,'dffgdfgdfg','Follow-up Checkup','fxfgxg',NULL,NULL,'returning','online','Face-to-Face','2026-11-09','Government Hospital / RHU','no-show',NULL,NULL,'2026-11-08 08:13:17','2026-11-08 11:04:14',NULL,NULL,NULL,NULL,0,0,NULL,NULL,'Scheduled','HRN-2026-6688774','General Medical Consultation','08:00:00',NULL,'Pending',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,NULL),(56,1,'PT-2026-085',NULL,'OPU Psychiatry','Alexu B. Gallardo','Alexu','Gallardo','B.','Male','2001-12-13',24,'+639774152027','alexgallardo121301@gmail.com','alexgallardo121301','$2y$11$5eBF9iNthLYEKzMEoxLXqO3Fu0.usupMjuAVeOgVtWlbeWHC/u5vm',0,1,NULL,NULL,NULL,NULL,NULL,'1696 Maap Road, Alas-Asin, Mariveles, Bataan',0,NULL,'Patient (Me)',NULL,NULL,'yes yes no','Diagnostic Assessment','basta sakit ng leeg ko palagi',NULL,NULL,'returning','online','Face-to-Face','2026-11-10','Self-Referral','ongoing',NULL,NULL,'2026-11-08 13:06:20','2026-11-10 13:01:36',NULL,NULL,NULL,NULL,0,0,NULL,NULL,'Scheduled','HRN-2026-6688774','OPU Psychiatry Consultation','10:00:00',NULL,'1-Day Sent','2026-11-08 23:25:47',NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,0,NULL),(57,10,'PT-2026-086',NULL,'General Medical','Alexu B. Gallardo','Alexu','Gallardo','B.','Male','2001-12-13',24,'+639774152027','alexgallardo121301@gmail.com','alexgallardo121301','$2y$11$5eBF9iNthLYEKzMEoxLXqO3Fu0.usupMjuAVeOgVtWlbeWHC/u5vm',0,1,NULL,NULL,NULL,NULL,NULL,'1696 Maap Road, Alas-Asin, Mariveles, Bataan',1,NULL,'Patient (Me)',NULL,NULL,'neck pain','Routine Consultation',NULL,NULL,NULL,'returning','online','Face-to-Face','2026-11-14','Self-Referral','for_approval',NULL,NULL,'2026-11-12 18:50:22','2026-11-12 18:50:22',NULL,NULL,NULL,NULL,0,0,NULL,NULL,'Scheduled','HRN-2026-6688774','General Medical Consultation','08:00:00',NULL,'Pending',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,NULL),(58,12,'PT-2026-087',NULL,'Neurology','deya tadu','deya','tadu',NULL,'Female','2005-05-22',21,'+639656347647','aliksalt080824@gmail.com','aliksalt080824','$2y$12$SEMJ29w3Z9alOnZn..1JReyKc7KIjGlLpxcRy2CwmuFIRCc1HTc2q',0,1,NULL,NULL,NULL,NULL,NULL,'balon yakal st.',1,NULL,'Patient (Me)',NULL,NULL,'asdasdsad','Routine Consultation','asdad',NULL,NULL,'first-time','online','Face-to-Face','2026-11-14','Self-Referral','approved',NULL,NULL,'2026-11-13 09:23:38','2026-11-14 08:24:12',NULL,NULL,NULL,NULL,0,0,NULL,NULL,'Scheduled','HRN-2026-087','Neurology Consultation','13:30:00',NULL,'Pending',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,NULL),(59,12,'PT-2026-088',NULL,'General Medical','Jarelle Gallardo','Jarelle','Gallardo',NULL,'Male','2013-03-12',13,'+639656347647','aliksalt080824@gmail.com','aliksalt080824','$2y$12$SEMJ29w3Z9alOnZn..1JReyKc7KIjGlLpxcRy2CwmuFIRCc1HTc2q',0,1,NULL,NULL,NULL,NULL,NULL,'1696 Maap Road, Alas-Asin, Mariveles, Bataan',1,'Sibling','Patient (Me)',NULL,NULL,'nose crack, got punched','Routine Consultation',NULL,NULL,NULL,'returning','online','Online Teleconsultation','2026-11-16','Self-Referral','approved',NULL,NULL,'2026-11-13 09:44:43','2026-11-13 14:42:41',NULL,NULL,NULL,NULL,0,0,NULL,NULL,'Scheduled','HRN-2026-087','General Medical Consultation','08:00:00',NULL,'Pending',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,NULL),(60,12,'PT-2026-089',NULL,'General Medical','Jarelle Gallardo','Jarelle','Gallardo',NULL,'Male','2013-03-12',13,'+639656347647','aliksalt080824@gmail.com','aliksalt080824','$2y$12$SEMJ29w3Z9alOnZn..1JReyKc7KIjGlLpxcRy2CwmuFIRCc1HTc2q',0,1,NULL,NULL,NULL,NULL,NULL,'1696 Maap Road, Alas-Asin, Mariveles, Bataan',1,NULL,'Patient (Me)',NULL,NULL,'fgdfgdfg','Routine Consultation',NULL,NULL,NULL,'returning','online','Face-to-Face','2026-11-18','Self-Referral','ongoing',NULL,NULL,'2026-11-13 09:46:07','2026-11-13 14:57:17',NULL,NULL,NULL,NULL,0,0,NULL,NULL,'Scheduled','HRN-2026-087','General Medical Consultation','10:00:00',NULL,'Pending',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,NULL),(61,10,'PT-2026-090',NULL,'General Medical','Alexu B. Gallardo','Alexu','Gallardo','B.','Male','2001-12-13',24,'+639774152027','alexgallardo121301@gmail.com','alexgallardo121301','$2y$11$5eBF9iNthLYEKzMEoxLXqO3Fu0.usupMjuAVeOgVtWlbeWHC/u5vm',0,1,NULL,NULL,NULL,NULL,NULL,'1696 Maap Road, Alas-Asin, Mariveles, Bataan',1,NULL,'Patient (Me)',NULL,NULL,'asdsadasd','Routine Consultation',NULL,NULL,NULL,'returning','online','Face-to-Face','2026-11-14','Self-Referral','completed',NULL,NULL,'2026-11-13 13:08:59','2026-11-16 20:02:49',NULL,NULL,NULL,NULL,0,1,'Online Portal',NULL,'Scheduled','HRN-2026-6688774','General Medical Consultation','10:00:00',NULL,'Completed',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,NULL),(62,10,'PT-2026-091',NULL,'Neurology','Alexu B. Gallardo','Alexu','Gallardo','B.','Male','2001-12-13',24,'+639774152027','alexgallardo121301@gmail.com','alexgallardo121301','$2y$11$5eBF9iNthLYEKzMEoxLXqO3Fu0.usupMjuAVeOgVtWlbeWHC/u5vm',0,1,NULL,NULL,NULL,NULL,NULL,'1696 Maap Road, Alas-Asin, Mariveles, Bataan',1,NULL,'Patient (Me)',NULL,NULL,'headache and cough','Routine Consultation',NULL,NULL,NULL,'returning','online','Face-to-Face','2026-11-18','Self-Referral','ongoing',NULL,NULL,'2026-11-13 14:51:17','2026-11-16 03:00:20',NULL,NULL,NULL,NULL,0,0,NULL,NULL,'Scheduled','HRN-2026-6688774','Neurology Consultation','13:00:00',NULL,'Pending',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,NULL),(63,10,'PT-2026-092',NULL,'General Medical','Alexu B. Gallardo','Alexu','Gallardo','B.','Male','2001-12-13',24,'+639774152027','alexgallardo121301@gmail.com','alexgallardo121301','$2y$11$5eBF9iNthLYEKzMEoxLXqO3Fu0.usupMjuAVeOgVtWlbeWHC/u5vm',0,1,NULL,NULL,NULL,NULL,NULL,'1696 Maap Road, Alas-Asin, Mariveles, Bataan',1,NULL,'Patient (Me)',NULL,NULL,'headache and cough','Routine Consultation',NULL,NULL,NULL,'returning','online','Face-to-Face','2026-11-18','Self-Referral','completed',NULL,NULL,'2026-11-13 14:52:56','2026-11-14 07:59:11',NULL,NULL,NULL,NULL,0,0,NULL,NULL,'Scheduled','HRN-2026-6688774','General Medical Consultation','08:00:00',NULL,'Completed',NULL,'{\"medicine_name\":\"Amlodipine 5mg tablet\",\"dosage\":\"1 tablet once daily\",\"quantity\":\"30 tablets\",\"diagnosis\":\"Essential Hypertension (High Blood Pressure)\",\"treatment\":\"Regular monitoring of blood pressure, blood sugar, and cholesterol levels. Healthy diet, regular physical activity, sodium reduction (<2g\\/day), weight management, and smoking cessation.\",\"prescription\":\"Rx: Amlodipine 5mg tablet #30 tablets\\nSig: 1 tablet once daily\",\"recorded_by\":\"ricoblanco123\",\"prescribed_at\":\"2026-11-14 15:59:11\"}',NULL,'Essential Hypertension (High Blood Pressure)','Regular monitoring of blood pressure, blood sugar, and cholesterol levels. Healthy diet, regular physical activity, sodium reduction (<2g/day), weight management, and smoking cessation.','Rx: Amlodipine 5mg tablet #30 tablets\nSig: 1 tablet once daily','ricoblanco123','2026-11-14 15:59:11',0,0,NULL),(64,10,'PT-2026-093',NULL,'Neurology','Alexu B. Gallardo','Alexu','Gallardo','B.','Male','2001-12-13',24,'+639774152027','alexgallardo121301@gmail.com','alexgallardo121301','$2y$11$5eBF9iNthLYEKzMEoxLXqO3Fu0.usupMjuAVeOgVtWlbeWHC/u5vm',0,1,NULL,NULL,NULL,NULL,NULL,'1696 Maap Road, Alas-Asin, Mariveles, Bataan',1,NULL,'Patient (Me)',NULL,NULL,'dfd','Routine Consultation','sfsdf',NULL,NULL,'returning','online','Face-to-Face','2026-11-17','Self-Referral','approved',NULL,NULL,'2026-11-14 07:25:31','2026-11-16 03:00:11',NULL,NULL,NULL,NULL,0,0,NULL,NULL,'Scheduled','HRN-2026-6688774','Neurology Consultation','08:00:00',NULL,'Pending',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,NULL),(65,13,'PT-2026-F330B',NULL,'Neurology','jeonghan yoon','jeonghan','yoon','','Male','1995-10-04',31,'+63 9099865116','idkbutdea@gmail.com','jeonghan1004','$2y$11$you/TYsS364Madhlu9EN8OvNMelvm1neczupPWYI9kHE/aZV7idki',0,1,'4f24bcda1e6fbf298d217f36164b25826a79d1a805362d9069ba5af1b647adbd','2026-11-17 11:26:27','2026-11-16 11:26:59',NULL,NULL,'BALONZKIE',1,NULL,'Patient (Me)',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'first-time','walk-in',NULL,'2026-11-17',NULL,'approved','Dr. Emilio Cruz','2026-11-17','2026-11-16 03:26:27','2026-11-16 20:16:57',NULL,NULL,NULL,NULL,0,0,NULL,NULL,NULL,NULL,'Neurology Consultation','13:00:00','Afternoon (1:00 PM - 5:00 PM)','Pending',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,NULL),(66,13,'PT-2026-094',NULL,'General Medical','andrea D. anque','andrea','anque','D.','Female','2005-06-22',21,'+639099865116','idkbutdea@gmail.com','jeonghan1004','$2y$11$you/TYsS364Madhlu9EN8OvNMelvm1neczupPWYI9kHE/aZV7idki',0,1,NULL,NULL,NULL,NULL,NULL,'balo',1,'Spouse','Patient (Me)',NULL,NULL,'sakit ulo naloloha','Routine Consultation','wala e',NULL,NULL,'first-time','online','Face-to-Face','2026-11-17','Self-Referral','ongoing','Dr. Angelo Mercado, MD','2026-11-16','2026-11-16 03:42:38','2026-11-17 11:11:26',NULL,NULL,NULL,NULL,0,0,NULL,NULL,'Scheduled','HRN-2026-094','General Medical Consultation','08:00:00',NULL,'Pending',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,NULL),(73,10,'PT-2026-095','APT-2026-0073','General Medical','Alexu B. Gallardo','Alexu','Gallardo','B.','Male','2001-12-13',24,'+639774152027','alexgallardo121301@gmail.com','alexgallardo121301','$2y$11$5eBF9iNthLYEKzMEoxLXqO3Fu0.usupMjuAVeOgVtWlbeWHC/u5vm',0,1,NULL,NULL,NULL,NULL,NULL,'1696 Maap Road, Alas-Asin, Mariveles, Bataan',1,NULL,'Patient (Me)',NULL,NULL,'asdsadasd\nasd\nasd\nasd\nsa\ndas\nd\nasd\nas\ndasd','Routine Consultation','Existing Conditions: asdsad\nas\nd\nsad\nas\nd\nasd | Current Medications: sadas\ndas\nd\nas\ndas\nd\nas','asdsad\nas\nd\nsad\nas\nd\nasd','sadas\ndas\nd\nas\ndas\nd\nas','returning','online','In-Person','2026-11-19',NULL,'approved','Dr. Beatrice Alcantara, MD','2026-11-17','2026-11-16 17:48:13','2026-11-17 11:13:32',NULL,NULL,NULL,NULL,0,0,NULL,NULL,'Scheduled','HRN-2026-6688774','General Medical Consultation','13:00:00','Afternoon (1:00 PM - 5:00 PM)','Pending',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,NULL),(77,10,'PT-2026-096','APT-2026-0077','General Medical','Alexu B. Gallardo','Alexu','Gallardo','B.','Male','2001-12-13',24,'+639774152027','alexgallardo121301@gmail.com','alexgallardo121301','$2y$11$5eBF9iNthLYEKzMEoxLXqO3Fu0.usupMjuAVeOgVtWlbeWHC/u5vm',0,1,NULL,NULL,NULL,NULL,NULL,'1696 Maap Road, Alas-Asin, Mariveles, Bataan',1,NULL,'Patient (Me)',NULL,NULL,'sdfsdf','Routine Consultation',NULL,NULL,NULL,'returning','online','In-Person','2026-11-20',NULL,'transferred','Dr. Angelo Mercado, MD','2026-11-17','2026-11-16 18:19:03','2026-11-16 20:10:42',NULL,NULL,NULL,NULL,0,0,NULL,NULL,'Scheduled','HRN-2026-6688774','General Medical Consultation','08:00:00','Morning (8:00 AM - 12:00 PM)','Pending',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,NULL),(78,10,'PT-2026-097',NULL,'OPU Psychiatry','Alexu B. Gallardo','Alexu','Gallardo','B.','Male','2001-12-13',24,'+639774152027','alexgallardo121301@gmail.com',NULL,NULL,0,0,NULL,NULL,NULL,NULL,NULL,'1696 Maap Road, Alas-Asin, Mariveles, Bataan',1,NULL,'Patient (Me)',NULL,NULL,'sdfsdf','Routine Consultation',NULL,NULL,NULL,'returning','online','In-Person','2026-11-20','General Medical','for_approval',NULL,NULL,'2026-11-16 20:10:42','2026-11-16 20:10:42',NULL,NULL,NULL,NULL,0,0,NULL,NULL,NULL,'HRN-2026-6688774','General Medical Consultation','08:00:00','Morning (8:00 AM - 12:00 PM)','Pending',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,NULL),(81,14,'PT-2026-4E9DD',NULL,'General Medical','Gusion B. Gallardo','Gusion','Gallardo','B','Male','2001-12-13',24,'+63 932644945','hehelexsu@gmail.com','hehelexsu2001','$2y$11$33ZZCWGSTcLctntHmwNZnun4JRRMrwju24k9Ym/jbJ0VCxCEmjTaC',0,1,'f3d5d9c55739ef1c4c6247d095b8975ef068f8985e5dc4e9d51cb33c22a92227','2026-11-18 19:35:24','2026-11-17 19:38:01',NULL,NULL,'asdadd',1,NULL,'Patient (Me)',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'first-time','walk-in',NULL,NULL,NULL,'approved','Dr. Angelo Mercado, MD','2026-11-17','2026-11-17 11:35:24','2026-11-17 11:41:46',NULL,NULL,NULL,NULL,0,0,NULL,NULL,NULL,NULL,'General Medical Consultation',NULL,NULL,'Pending',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,NULL),(82,NULL,'PT-2026-B47F3',NULL,NULL,'Ligaya B. Grace','Ligaya','Grace','B','Female','2009-07-08',17,'+63 9874451531','joygallardo853@gmail.com','joygallardo853.lingkod','$2y$11$0l1XKA0xTTlShADMBmbEW.AxT9avygJBQcXTnJAoC6ZaPaPilLymO',0,0,'129031cee20a0d7e516efb0aa91d025339bbc72dd7b02d96a105c3a59bf423e5','2026-11-18 19:56:26',NULL,NULL,NULL,'1696 Maap Road, Alas-Asin',1,NULL,'Patient (Me)','Josefina Gallardo','+63 9060911608',NULL,NULL,NULL,NULL,NULL,'first-time','walk-in',NULL,NULL,NULL,'pending',NULL,NULL,'2026-11-17 11:56:26','2026-11-17 11:56:26',NULL,NULL,NULL,NULL,0,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Pending',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,NULL),(83,NULL,'PT-2026-29D25',NULL,NULL,'Joyz B. Gracez','Joyz','Gracez','B','Female','2000-07-08',26,'+63 968461311','boholg761@gmail.com','boholg761.lingkod','$2y$11$T99KhE1SJVKNljekOR43I.IQv50GzPZuXeVCNxh31ctkYQ3UfiiZu',0,0,'b3e9789b7fe3e3ce44e91934e3b39d28a91be3f963923708f2995bf264ceb3cf','2026-11-18 20:00:44',NULL,NULL,NULL,'1696 Maap Road, Alas-Asin',1,NULL,'Patient (Me)',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'first-time','walk-in',NULL,NULL,NULL,'pending',NULL,NULL,'2026-11-17 12:00:44','2026-11-17 12:00:44',NULL,NULL,NULL,NULL,0,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Pending',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,NULL);
/*!40000 ALTER TABLE `patients` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `progress_notes`
--

DROP TABLE IF EXISTS `progress_notes`;
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
-- Dumping data for table `progress_notes`
--

LOCK TABLES `progress_notes` WRITE;
/*!40000 ALTER TABLE `progress_notes` DISABLE KEYS */;
INSERT INTO `progress_notes` VALUES (1,'PT-2026-005','Pending Initial Assessment','System','2026-09-22 02:47:25',NULL,NULL,NULL,NULL),(2,'PT-2026-002','Returning patient. Last visit: April 2026. Migraine frequency unchanged.','System','2026-09-22 02:47:25',NULL,NULL,NULL,NULL),(3,'PT-2026-003','Pending Initial Assessment','System','2026-09-22 02:47:25',NULL,NULL,NULL,NULL),(4,'PT-2026-004','Pending Initial Assessment','System','2026-10-04 08:21:05',NULL,NULL,NULL,NULL),(5,'PT-2026-005','Vitals recorded from Triage','anqueandrea22','2026-10-04 09:34:27','110/50',-1.00,85,69.00),(6,'PT-2026-005','Auto Follow-Up Scheduled by Staff','System','2026-10-04 09:34:27',NULL,NULL,NULL,NULL),(7,'PT-2026-006','Pending Initial Assessment','System','2026-10-04 14:11:34',NULL,NULL,NULL,NULL),(8,'PT-2026-007','Pending Initial Assessment','System','2026-10-09 01:14:10',NULL,NULL,NULL,NULL),(10,'PT-2026-009','Pending Initial Assessment','System','2026-10-13 06:26:36',NULL,NULL,NULL,NULL),(12,'PT-2026-011','Pending Initial Assessment','System','2026-10-13 11:24:29',NULL,NULL,NULL,NULL),(14,'PT-2026-012','Pending Initial Assessment','System','2026-10-13 12:03:15',NULL,NULL,NULL,NULL),(15,'PT-2026-013','Pending Initial Assessment','System','2026-10-13 12:29:25',NULL,NULL,NULL,NULL),(16,'PT-2026-014','Pending Initial Assessment','System','2026-10-13 12:49:40',NULL,NULL,NULL,NULL),(17,'PT-2026-015','Pending Initial Assessment','System','2026-10-13 12:56:58',NULL,NULL,NULL,NULL),(18,'PT-2026-016','Pending Initial Assessment','System','2026-10-13 23:10:42',NULL,NULL,NULL,NULL),(19,'PT-2026-017','Pending Initial Assessment','System','2026-10-14 02:37:08',NULL,NULL,NULL,NULL),(20,'PT-2026-018','Pending Initial Assessment','System','2026-10-14 06:21:54',NULL,NULL,NULL,NULL),(21,'PT-2026-020','Pending Initial Assessment','System','2026-10-14 06:35:13',NULL,NULL,NULL,NULL),(22,'PT-2026-021','Pending Initial Assessment','System','2026-10-14 07:32:46',NULL,NULL,NULL,NULL),(23,'PT-2026-022','Pending Initial Assessment','System','2026-10-14 07:51:08',NULL,NULL,NULL,NULL),(24,'PT-2026-023','Pending Initial Assessment','System','2026-10-14 08:02:51',NULL,NULL,NULL,NULL),(25,'PT-2026-024','Pending Initial Assessment','System','2026-10-14 09:07:21',NULL,NULL,NULL,NULL),(26,'PT-2026-026','Pending Initial Assessment','System','2026-10-14 09:19:30',NULL,NULL,NULL,NULL),(27,'PT-2026-027','Pending Initial Assessment','System','2026-10-14 09:25:07',NULL,NULL,NULL,NULL),(28,'PT-2026-030','General Inquiry Submitted. Awaiting review.','System','2026-10-21 23:08:59',NULL,NULL,NULL,NULL),(29,'PT-2026-030','Inquiry Resolved: Staff called patient and answered HMO questions.','admin','2026-10-21 23:12:22',NULL,NULL,NULL,NULL),(30,'PT-2026-031','General Inquiry Submitted. Awaiting review.','System','2026-10-21 23:13:08',NULL,NULL,NULL,NULL),(31,'PT-2026-032','Online Appointment Pre-Registration Submitted. Status: For Approval.','System','2026-10-22 02:08:03',NULL,NULL,NULL,NULL),(32,'PT-2026-027','Vitals recorded from Triage','ricoblanco123','2026-10-22 02:51:57','120/80',36.00,80,65.00),(33,'PT-2026-027','Follow-Up Scheduled by Staff','ricoblanco123','2026-10-22 02:51:57',NULL,NULL,NULL,NULL),(34,'PT-2026-081','Online Appointment Booked via Patient Portal (General Management on 2026-11-02 at 08:00 AM). Status: For Approval.','Patient Portal','2026-10-31 13:13:57',NULL,NULL,NULL,NULL),(35,'PT-2026-082','Online Appointment Booked via Patient Portal (OPU Psychiatry on 2026-11-02 at 08:00 AM). Status: For Approval.','Patient Portal','2026-10-31 13:29:39',NULL,NULL,NULL,NULL),(36,'PT-2026-083','Online Appointment Booked via Patient Portal (OPU Psychiatry on 2026-11-03 at 08:00 AM). Status: For Approval.','Patient Portal','2026-11-02 06:51:30',NULL,NULL,NULL,NULL),(37,'PT-2026-084','Online Appointment Booked via Patient Portal (General Medical on 2026-11-09 at 08:00 AM). Status: For Approval.','Patient Portal','2026-11-08 08:13:17',NULL,NULL,NULL,NULL),(38,'PT-2026-085','Online Appointment Booked via Patient Portal (OPU Psychiatry on 2026-11-09 at 10:00 AM). Status: For Approval.','Patient Portal','2026-11-08 13:06:20',NULL,NULL,NULL,NULL),(39,'PT-2026-024','Essential Hypertension (High Blood Pressure) — Regular monitoring of blood pressure, blood sugar, and cholesterol levels. Healthy diet, regular physical activity, sodium reduction (<2g/day), weight management, and smoking cessation. | Rx: Amlodipine 5mg tablet (1 tablet once daily)','ricoblanco123','2026-11-10 12:43:58',NULL,NULL,NULL,NULL),(40,'PT-2026-086','Online Appointment Booked via Patient Portal (General Medical on 2026-11-14 at 08:00 AM). Status: For Approval.','Patient Portal','2026-11-12 18:50:22',NULL,NULL,NULL,NULL),(41,'PT-2026-087','Online Appointment Booked via Patient Portal (Neurology on 2026-11-14 at 01:30 PM). Status: For Approval.','Patient Portal','2026-11-13 09:23:38',NULL,NULL,NULL,NULL),(42,'PT-2026-088','Online Appointment Booked via Patient Portal (General Medical on 2026-11-16 at 08:00 AM). Status: For Approval. [Booked for Dependent - Relationship: Sibling]','Patient Portal','2026-11-13 09:44:43',NULL,NULL,NULL,NULL),(43,'PT-2026-089','Online Appointment Booked via Patient Portal (General Medical on 2026-11-18 at 10:00 AM). Status: For Approval.','Patient Portal','2026-11-13 09:46:07',NULL,NULL,NULL,NULL),(44,'PT-2026-090','Online Department Inquiry submitted via Patient Portal. Status: For Approval.','Patient Portal','2026-11-13 13:08:59',NULL,NULL,NULL,NULL),(45,'PT-2026-091','Online Appointment Booked via Patient Portal (General Medical on 2026-11-18 at 01:00 PM, Ref: APT-2026-0062). Status: For Approval.','Patient Portal','2026-11-13 14:51:17',NULL,NULL,NULL,NULL),(46,'PT-2026-092','Online Appointment Booked via Patient Portal (General Medical on 2026-11-18 at 08:00 AM, Ref: APT-2026-0063). Status: For Approval.','Patient Portal','2026-11-13 14:52:56',NULL,NULL,NULL,NULL),(47,'PT-2026-093','Online Appointment Booked via Patient Portal (General Medical on 2026-11-17 at 08:00 AM, Ref: APT-2026-0064). Status: For Approval.','Patient Portal','2026-11-14 07:25:31',NULL,NULL,NULL,NULL),(48,'PT-2026-092','Essential Hypertension (High Blood Pressure) — Regular monitoring of blood pressure, blood sugar, and cholesterol levels. Healthy diet, regular physical activity, sodium reduction (<2g/day), weight management, and smoking cessation. | Rx: Amlodipine 5mg tablet (1 tablet once daily)','ricoblanco123','2026-11-14 07:59:11',NULL,NULL,NULL,NULL),(49,'PT-2026-094','Online Appointment Booked via Patient Portal (General Medical on 2026-11-17 at 08:00 AM, Ref: APT-2026-0066). Status: For Approval. [Booked for Dependent - Relationship: Spouse]','Patient Portal','2026-11-16 03:42:38',NULL,NULL,NULL,NULL),(56,'PT-2026-095','Online Appointment Booked (General Medical on 2026-11-19 • Afternoon (1:00 PM - 5:00 PM), Mode: In-Person, Recipient: Patient (Me), Ref: APT-2026-0073). Status: For Approval.','Patient Portal','2026-11-16 17:48:13',NULL,NULL,NULL,NULL),(61,'PT-2026-096','Online Appointment Booked (General Medical on 2026-11-20 • Morning (8:00 AM - 12:00 PM), Mode: In-Person, Recipient: Patient (Me), Ref: APT-2026-0077). Status: For Approval.','Patient Portal','2026-11-16 18:19:03',NULL,NULL,NULL,NULL),(62,'PT-2026-090','Inquiry Resolved: Inquiry addressed and resolved.','ricoblanco123','2026-11-16 20:02:49',NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `progress_notes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `specializations`
--

DROP TABLE IF EXISTS `specializations`;
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
-- Dumping data for table `specializations`
--

LOCK TABLES `specializations` WRITE;
/*!40000 ALTER TABLE `specializations` DISABLE KEYS */;
INSERT INTO `specializations` VALUES (1,'general','Internal Medicine','2026-11-09 14:43:42'),(2,'general','Family Medicine & General Practice','2026-11-09 14:43:42'),(3,'general','Routine Check-ups & Preventative Care','2026-11-09 14:43:42'),(4,'general','Chronic Disease Management','2026-11-09 14:43:42'),(5,'general','Pediatric Care','2026-11-09 14:43:42'),(6,'general','General Surgery & Minor Procedures','2026-11-09 14:43:42'),(7,'general','Preventive Healthcare & Physical Examination','2026-11-09 14:43:42'),(8,'neurology','Clinical Neurology','2026-11-09 14:43:42'),(9,'neurology','Headache & Migraine Management','2026-11-09 14:43:42'),(10,'neurology','Stroke Evaluation','2026-11-09 14:43:42'),(11,'neurology','Epilepsy & Seizure Disorders','2026-11-09 14:43:42'),(12,'neurology','Movement Disorders','2026-11-09 14:43:42'),(13,'neurology','Neuromuscular Medicine','2026-11-09 14:43:42'),(14,'neurology','Dementia Evaluation','2026-11-09 14:43:42'),(15,'neurology','Neurological Diagnostic Testing','2026-11-09 14:43:42'),(16,'psychiatry','Adult Psychiatry','2026-11-09 14:43:42'),(17,'psychiatry','Child & Adolescent Psychiatry','2026-11-09 14:43:42'),(18,'psychiatry','Anxiety/Panic/Depression Management','2026-11-09 14:43:42'),(19,'psychiatry','Mood Disorders','2026-11-09 14:43:42'),(20,'psychiatry','Substance Abuse','2026-11-09 14:43:42'),(21,'psychiatry','Geriatric Psychiatry','2026-11-09 14:43:42'),(22,'psychiatry','Clinical Neuropsychiatry','2026-11-09 14:43:42'),(23,'psychiatry','Psychotherapy','2026-11-09 14:43:42');
/*!40000 ALTER TABLE `specializations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `system_logs`
--

DROP TABLE IF EXISTS `system_logs`;
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
-- Dumping data for table `system_logs`
--

LOCK TABLES `system_logs` WRITE;
/*!40000 ALTER TABLE `system_logs` DISABLE KEYS */;
INSERT INTO `system_logs` VALUES (1,'info','System Login','Admin','Admin successfully logged into the portal.','2026-05-07 11:20:00'),(2,'info','Patient Approved','Admin','Patient PT-2026-002 (Maria Santos) was approved for Neurology.','2026-05-07 11:22:00'),(3,'warning','Failed Login Attempt','Unknown','Unrecognized credentials submitted from 192.168.1.45.','2026-05-07 11:35:00'),(4,'info','System Startup','System','Admin portal initialized successfully.','2026-05-08 00:01:00'),(5,'error','API Timeout','System','Schedule refresh API call timed out after 30s. Retry scheduled.','2026-05-08 01:14:00'),(6,'info','Staff Status Changed','admin','Staff \'staff\' status changed to inactive.','2026-10-04 06:10:35'),(7,'info','Staff Status Changed','admin','Staff \'staff\' status changed to active.','2026-10-04 06:10:50'),(8,'info','Staff Account Created','admin','New staff account \'anqueandrea22\' (Andrea Anque) created for OPU Psychiatry.','2026-10-04 06:37:59'),(9,'info','Patient Approved','anqueandrea22','Patient PT-2026-001 (Juan Dela Cruz) was approved for OPU Psychiatry.','2026-10-04 06:40:43'),(10,'info','Patient Completed','anqueandrea22','Patient PT-2026-001 (Juan Dela Cruz) marked as completed.','2026-10-04 06:40:59'),(11,'warning','Absence Override','admin','Admin forced leave status for doctor ID 5.','2026-10-04 07:59:10'),(12,'warning','Absence Override','admin','Admin forced leave status for doctor ID 5.','2026-10-04 07:59:14'),(13,'info','Patient Registered','Public Form','New registration: PT-2026-004 (Tungtung Sahur) for General Management.','2026-10-04 08:21:05'),(14,'info','Patient Approved','staff','Patient PT-2026-004 (Tungtung Sahur) was approved for General.','2026-10-04 08:54:58'),(15,'info','Patient No-Show','staff','Patient PT-2026-004 (Tungtung Sahur) marked as no-show.','2026-10-04 08:55:08'),(16,'warning','Absence Override','admin','Admin forced leave status for doctor ID 1.','2026-10-04 08:55:43'),(17,'info','Staff Status Changed','admin','Staff \'staff\' status changed to inactive.','2026-10-04 09:17:29'),(18,'warning','Absence Override','admin','Admin forced leave status for doctor ID 1.','2026-10-04 09:17:45'),(19,'info','Medical History Updated','anqueandrea22','Updated medical history and triage vitals for patient PT-2026-001.','2026-10-04 09:34:27'),(20,'info','Patient Referred','anqueandrea22','Patient PT-2026-005 (Juan Dela Cruz) referred to Neurology.','2026-10-04 09:39:21'),(21,'info','Staff Account Created','admin','New staff account \'alexgallardo13\' (Alex Gallardo) created for Neurology.','2026-10-04 09:40:31'),(22,'info','Patient Approved','alexgallardo13','Patient PT-2026-005 (Juan Dela Cruz) was approved for Neurology.','2026-10-04 09:43:23'),(23,'warning','Absence Override','admin','Admin forced leave status for doctor ID 2.','2026-10-04 13:37:41'),(24,'info','Absence Override Removed','admin','Admin restored availability for doctor ID 1.','2026-10-04 13:37:45'),(25,'warning','Absence Override','admin','Admin forced leave status for doctor ID 3.','2026-10-04 13:53:35'),(26,'info','Absence Override Removed','admin','Admin restored availability for doctor ID 2.','2026-10-04 13:53:38'),(27,'info','Patient Registered','Public Form','New registration: PT-2026-006 (Lyka Justine Valintus) for OPU Psychiatry.','2026-10-04 14:11:34'),(28,'info','Staff Status Changed','admin','Staff \'staff\' status changed to active.','2026-10-04 14:12:08'),(29,'info','Patient Approved','anqueandrea22','Patient PT-2026-006 (Lyka Justine Valintus) was approved for OPU Psychiatry.','2026-10-04 14:14:17'),(30,'info','Patient Referred','alexgallardo13','Patient PT-2026-005 (Juan Dela Cruz) referred to General Management.','2026-10-08 16:35:38'),(31,'warning','Absence Override','admin','Admin forced leave status for doctor ID 1.','2026-10-08 16:38:59'),(32,'info','Absence Override Removed','admin','Admin restored availability for doctor ID 1.','2026-10-08 16:39:13'),(33,'info','Patient Registered','Public Form','New registration: PT-2026-007 (Dea Sahur) for Neurology.','2026-10-09 01:14:10'),(34,'info','Patient Approved','alexgallardo13','Patient PT-2026-007 (Dea Sahur) was approved for Neurology.','2026-10-09 01:14:59'),(35,'info','Patient Referred','alexgallardo13','Patient PT-2026-007 (Dea Sahur) referred to OPU Psychiatry.','2026-10-10 00:42:56'),(36,'info','Schedule Updated','admin','Admin updated schedule dates/times for doctor ID 1.','2026-10-10 14:46:35'),(37,'info','Staff Account Updated','admin','Staff account \'staff\' updated.','2026-10-10 14:48:02'),(38,'info','Staff Status Changed','admin','Staff \'alexgallardo13\' status changed to inactive.','2026-10-10 14:52:17'),(39,'info','Staff Status Changed','admin','Staff \'alexgallardo13\' status changed to active.','2026-10-10 14:52:31'),(40,'info','Absence Override Removed','admin','Admin restored availability for doctor ID 3.','2026-10-10 14:55:17'),(41,'info','Staff Password Updated','admin','Staff account \'anqueandrea22\' password was updated.','2026-10-10 14:56:16'),(42,'info','Staff Password Updated','admin','Staff account \'anqueandrea22\' password was updated.','2026-10-10 14:56:32'),(43,'info','Patient No-Show','anqueandrea22','Patient PT-2026-006 (Lyka Justine Valintus) marked as no-show.','2026-10-10 14:58:06'),(44,'info','Staff Password Updated','admin','Staff account \'alexgallardo13\' password was updated.','2026-10-10 14:59:17'),(45,'info','Staff Account Created','admin','New staff account \'rexiemampang\' (Rexiebelle Mampang) created for OPU Psychiatry.','2026-10-10 15:02:27'),(46,'info','Staff Status Changed','admin','Staff \'alexgallardo13\' status changed to inactive.','2026-10-10 15:05:54'),(47,'info','Staff Status Changed','admin','Staff \'alexgallardo13\' status changed to active.','2026-10-10 15:05:57'),(48,'info','Staff Password Updated','admin','Staff account \'alexgallardo13\' password was updated.','2026-10-10 15:06:14'),(49,'warning','Absence Override','admin','Admin forced leave status for doctor ID 1.','2026-10-10 15:06:25'),(50,'info','Absence Override Removed','admin','Admin restored availability for doctor ID 1.','2026-10-10 15:06:28'),(51,'info','Staff Account Created','admin','New staff account \'joanamarielopez\' (Joana Marie Lopez) created for OPU Psychiatry.','2026-10-10 15:14:41'),(52,'info','Staff Status Changed','admin','Staff \'staff\' status changed to inactive.','2026-10-10 15:15:01'),(53,'info','Staff Account Created','admin','New staff account \'ryzzamaevalintus\' (Lyka Mae Valintus) created for General Management.','2026-10-10 15:22:11'),(54,'warning','Staff Account Deleted','admin','Staff account \'ryzzamaevalintus\' has been deleted.','2026-10-10 15:22:19'),(55,'info','Patient Completed','alexgallardo13','Patient PT-2026-002 (Maria Santos) marked as completed.','2026-10-11 10:58:15'),(56,'info','Patient Approved','anqueandrea22','Patient PT-2026-007 (Dea Sahur) was approved for OPU Psychiatry.','2026-10-12 14:39:07'),(57,'info','Doctor Updated','admin','Doctor ID 2 has been updated.','2026-10-12 14:43:37'),(58,'info','Doctor Updated','admin','Doctor ID 5 has been updated.','2026-10-12 14:43:53'),(59,'info','Doctor Updated','admin','Doctor ID 4 has been updated.','2026-10-12 14:44:00'),(60,'info','Doctor Updated','admin','Doctor ID 2 has been updated.','2026-10-12 14:45:51'),(61,'info','Doctor Updated','admin','Doctor ID 4 has been updated.','2026-10-12 14:45:59'),(62,'info','Doctor Updated','admin','Doctor ID 2 has been updated.','2026-10-12 14:46:09'),(63,'info','Patient Registered','Public Form','New registration: PT-2026-008 (Xela Bohol) for Neurology.','2026-10-12 15:30:59'),(64,'info','Patient No-Show','alexgallardo13','Patient PT-2026-008 (Xela Bohol) marked as no-show.','2026-10-13 05:56:22'),(65,'info','Patient Registered','Public Form','New registration: PT-2026-009 (Jarelle Gallardo) for General.','2026-10-13 06:26:36'),(66,'warning','Patient Deleted','alexgallardo13','Patient PT-2026-008 (Xela Bohol) with status no-show was permanently deleted by alexgallardo13.','2026-10-13 06:40:59'),(67,'info','Patient Registered','Public Form','New registration: PT-2026-010 (Alice Wonder B.) for OPU Psychiatry.','2026-10-13 06:55:02'),(68,'info','Patient Transferred','anqueandrea22','Patient Dea Sahur (PT-2026-007) transferred from OPU Psychiatry to Neurology (New ID: PT-2026-010). Origin record marked as Transferred.','2026-10-13 09:41:58'),(69,'info','Patient Registered','Public Form','New registration: PT-2026-011 (Josefina B. Gallardo) for Neurology.','2026-10-13 11:24:29'),(71,'info','Patient Registered','Public Form','New registration: PT-2026-012 (Garry B. Gallardo) for Neurology.','2026-10-13 12:03:15'),(72,'info','Patient Registered','Public Form','New registration: PT-2026-013 (Alexito Gallardo) for General Management.','2026-10-13 12:29:25'),(73,'info','Patient Registered','Public Form','New registration: PT-2026-014 (Joan Gallardo) for General Management.','2026-10-13 12:49:40'),(74,'info','Patient Registered','Public Form','New registration: PT-2026-015 (Joan Gallardo) for General Management.','2026-10-13 12:56:58'),(75,'warning','Staff Account Deleted','admin','Staff account \'staff\' has been deleted.','2026-10-13 13:37:59'),(76,'info','Patient Registered','Public Form','New registration: PT-2026-016 (Joseph D. Lee) for Neurology.','2026-10-13 23:10:42'),(77,'info','Patient Approved','alexgallardo13','Patient PT-2026-016 (Joseph D. Lee) was approved for Neurology.','2026-10-13 23:12:06'),(78,'info','Staff Status Changed','admin','Staff \'alexgallardo13\' status changed to inactive.','2026-10-13 23:14:21'),(79,'info','Staff Status Changed','admin','Staff \'alexgallardo13\' status changed to active.','2026-10-13 23:14:29'),(80,'info','Patient Completed','alexgallardo13','Patient PT-2026-016 (Joseph D. Lee) marked as completed.','2026-10-13 23:17:09'),(81,'info','SMS Reminder Sent','Daily CRON Job','SMS appointment reminder sent to Josefina B. Gallardo (09060911608) for appointment on 2026-10-17.','2026-10-14 00:44:28'),(82,'info','Daily SMS CRON Run','Daily CRON Job','Automated daily SMS CRON completed: 1 sent, 0 failed out of 1 eligible appointments.','2026-10-14 00:44:28'),(83,'info','SMS Reminder Sent','Staff Manual Test','SMS appointment reminder sent to Josefina B. Gallardo (09060911608) for appointment on 2026-10-17.','2026-10-14 00:48:33'),(84,'info','System Login','alexgallardo13','alexgallardo13 successfully logged into the portal from ::1.','2026-10-14 00:53:46'),(85,'info','SMS Reminder Sent','alexgallardo13','SMS appointment reminder sent to Josefina B. Gallardo (09060911608) for appointment on 2026-10-17.','2026-10-14 00:53:46'),(86,'info','SMS Reminder Sent','alexgallardo13','SMS appointment reminder sent to Joseph D. Lee (09872313055) for appointment on 2026-10-20.','2026-10-14 00:55:54'),(87,'info','SMS Reminder Sent','alexgallardo13','SMS appointment reminder sent to Joseph D. Lee (09872313055) for appointment on 2026-10-20.','2026-10-14 00:57:39'),(88,'info','System Login','alexgallardo13','alexgallardo13 successfully logged into the portal from ::1.','2026-10-14 01:09:13'),(89,'info','SMS Reminder Sent','alexgallardo13','SMS appointment reminder sent to Josefina B. Gallardo (09060911608) for appointment on 2026-10-17.','2026-10-14 01:09:13'),(90,'info','System Login','alexgallardo13','alexgallardo13 successfully logged into the portal from ::1.','2026-10-14 01:27:11'),(91,'info','SMS Reminder Sent','alexgallardo13','SMS appointment reminder sent to Josefina B. Gallardo (09060911608) for appointment on 2026-10-17.','2026-10-14 01:27:11'),(92,'info','System Login','alexgallardo13','alexgallardo13 successfully logged into the portal from ::1.','2026-10-14 01:42:25'),(93,'info','SMS Reminder Sent','alexgallardo13','SMS appointment reminder sent to Josefina B. Gallardo (09060911608) for appointment on 2026-10-17.','2026-10-14 01:42:25'),(94,'info','Patient Registered','Public Form','New registration: PT-2026-017 (Alexander Reyes) for Neurology.','2026-10-14 02:37:08'),(95,'info','Patient Approved','alexgallardo13','Patient PT-2026-017 (Alexander Reyes) was approved for Neurology.','2026-10-14 02:38:13'),(96,'info','Staff Account Created','admin','New staff account \'ricoblanco123\' (Rico Blanco) created for General Management.','2026-10-14 02:40:08'),(97,'info','Patient Registered','Public Form','New registration: PT-2026-018 (Dudung Lopez) for Neurology.','2026-10-14 06:21:54'),(98,'info','Patient Approved','alexgallardo13','Patient PT-2026-018 (Dudung Lopez) was approved for Neurology.','2026-10-14 06:25:40'),(99,'info','SMS Reminder Sent','alexgallardo13','SMS appointment reminder sent to Dudung Lopez (09076628622) for appointment on 2026-10-19.','2026-10-14 06:26:07'),(100,'info','Patient Completed','alexgallardo13','Patient PT-2026-018 (Dudung Lopez) marked as completed.','2026-10-14 06:26:27'),(101,'info','Patient No-Show','alexgallardo13','Patient PT-2026-017 (Alexander Reyes) marked as no-show.','2026-10-14 06:26:38'),(102,'info','Patient Transferred','alexgallardo13','Patient Garry B. Gallardo (PT-2026-012) transferred from Neurology to General Management (New ID: PT-2026-019). Origin record marked as Transferred.','2026-10-14 06:26:59'),(103,'info','Patient Approved','ricoblanco123','Patient PT-2026-019 (Garry B. Gallardo) was approved for General Management.','2026-10-14 06:28:33'),(104,'info','SMS Reminder Sent','ricoblanco123','SMS appointment reminder sent to Garry B. Gallardo (09423146213) for appointment on 2026-10-14.','2026-10-14 06:28:47'),(105,'info','Patient Registered','Public Form','New registration: PT-2026-020 (Jaemarl Mossendon) for General Management.','2026-10-14 06:35:13'),(106,'info','Patient Approved','ricoblanco123','Patient PT-2026-020 (Jaemarl Mossendon) was approved for General Management.','2026-10-14 06:35:13'),(107,'info','Patient Registered','Public Form','New registration: PT-2026-021 (Alexito Gallardo) for OPU Psychiatry.','2026-10-14 07:32:46'),(108,'info','Patient Approved','anqueandrea22','Patient PT-2026-021 (Alexito Gallardo) was approved for OPU Psychiatry.','2026-10-14 07:32:46'),(109,'info','Patient No-Show','anqueandrea22','Patient PT-2026-021 (Alexito Gallardo) marked as no-show.','2026-10-14 07:33:18'),(110,'info','Patient Completed','alexgallardo13','Patient PT-2026-011 (Josefina B. Gallardo) marked as completed.','2026-10-14 07:49:47'),(111,'info','Patient Approved','alexgallardo13','Patient PT-2026-010 (Dea Sahur) was approved for Neurology.','2026-10-14 07:49:50'),(112,'info','Patient Completed','alexgallardo13','Patient PT-2026-010 (Dea Sahur) marked as completed.','2026-10-14 07:49:54'),(113,'info','Patient Registered','Public Form','New registration: PT-2026-022 (Jeong Hilarious) for Neurology.','2026-10-14 07:51:08'),(114,'info','Patient Approved','alexgallardo13','Patient PT-2026-022 (Jeong Hilarious) was approved for Neurology.','2026-10-14 07:51:08'),(115,'info','Patient Completed','alexgallardo13','Patient PT-2026-022 (Jeong Hilarious) marked as completed.','2026-10-14 07:51:15'),(116,'info','Patient Registered','Public Form','New registration: PT-2026-023 (juhoon imnida) for Neurology.','2026-10-14 08:02:51'),(117,'info','Patient Approved','alexgallardo13','Patient PT-2026-023 (juhoon imnida) was approved for Neurology.','2026-10-14 08:02:51'),(118,'info','Patient Completed','alexgallardo13','Patient PT-2026-023 (juhoon imnida) marked as completed.','2026-10-14 08:03:10'),(119,'info','Patient Registered','Public Form','New registration: PT-2026-024 (hyghuu asdasfd) for General Management.','2026-10-14 09:07:21'),(120,'info','Patient No-Show','ricoblanco123','Patient PT-2026-020 (Jaemarl Mossendon) marked as no-show.','2026-10-14 09:10:38'),(121,'info','Patient Transferred','ricoblanco123','Patient Garry B. Gallardo (PT-2026-019) transferred from General Management to Neurology (New ID: PT-2026-025). Origin record marked as Transferred.','2026-10-14 09:14:05'),(122,'info','Patient Approved','ricoblanco123','Patient PT-2026-015 (Joan Gallardo) was approved for General Management.','2026-10-14 09:16:17'),(123,'info','Patient Completed','ricoblanco123','Patient PT-2026-015 (Joan Gallardo) marked as completed.','2026-10-14 09:17:52'),(124,'info','Patient Completed','ricoblanco123','Patient PT-2026-015 (Joan Gallardo) marked as completed.','2026-10-14 09:17:52'),(125,'info','Patient Completed','ricoblanco123','Patient PT-2026-015 (Joan Gallardo) marked as completed.','2026-10-14 09:17:53'),(126,'info','Patient Registered','Public Form','New registration: PT-2026-026 (Daniel Padila) for General Management.','2026-10-14 09:19:30'),(127,'info','Patient Approved','ricoblanco123','Patient PT-2026-026 (Daniel Padila) was approved for General Management.','2026-10-14 09:19:30'),(128,'info','Patient Registered','Public Form','New registration: PT-2026-027 (Daniel Padila) for General Management.','2026-10-14 09:25:07'),(129,'info','Patient Approved','ricoblanco123','Patient PT-2026-027 (Daniel Padila) was approved for General Management.','2026-10-14 09:25:36'),(130,'info','Patient Approved','alexgallardo13','Patient PT-2026-025 (Garry B. Gallardo) was approved for Neurology.','2026-10-14 09:27:59'),(131,'info','Patient Transferred','alexgallardo13','Patient Garry B. Gallardo (PT-2026-025) transferred from Neurology to OPU Psychiatry (New ID: PT-2026-028). Origin record marked as Transferred.','2026-10-14 09:28:25'),(132,'info','Patient Transferred','anqueandrea22','Patient Garry B. Gallardo (PT-2026-028) transferred from OPU Psychiatry to OPU Psychiatry (New ID: PT-2026-029). Origin record marked as Transferred.','2026-10-14 09:29:12'),(133,'info','Doctor Added','admin','New doctor \'Dr. Kwakwak\' (Veterinarian) added to neurology department.','2026-10-14 09:33:46'),(134,'info','Profile / Staff Account Updated','alexgallardo13','User \'alexgallardo13\' details were updated.','2026-10-18 13:55:05'),(135,'info','Doctor Updated','admin','Doctor ID 11 has been updated.','2026-10-21 13:57:40'),(136,'info','System Login','admin','admin successfully logged into the portal from ::1.','2026-10-21 15:32:51'),(137,'info','Staff Status Changed','admin','Staff \'alexgallardo13\' status changed to inactive.','2026-10-21 21:38:41'),(138,'info','Staff Status Changed','admin','Staff \'alexgallardo13\' status changed to active.','2026-10-21 21:38:45'),(139,'info','Inquiry Registered','Public Booking Portal','New for_approval inquiry: PT-2026-030 (Maria Test A. Inquirer) for General Management.','2026-10-21 23:08:59'),(140,'info','System Login','admin','admin successfully logged into the portal from ::1.','2026-10-21 23:09:46'),(141,'info','Inquiry Resolved','admin','Inquiry PT-2026-030 (Maria Test A. Inquirer) was resolved and marked completed.','2026-10-21 23:12:22'),(142,'info','Inquiry Registered','Public Booking Portal','New for_approval inquiry: PT-2026-031 (Carlos D. Mendoza) for Neurology.','2026-10-21 23:13:08'),(143,'warning','Failed Login Attempt','Unknown','Unrecognized credentials submitted for user \'alexgallardo13\' from ::1.','2026-10-21 23:49:31'),(144,'info','System Login','alexgallardo13','alexgallardo13 successfully logged into the portal from ::1.','2026-10-21 23:50:21'),(145,'info','Patient Registered','Public Booking Portal','New for_approval registration: PT-2026-032 (Lyka Miringhoy) for Neurology with Dr. Dr. Robert Tan.','2026-10-22 02:08:03'),(146,'info','Medical History Updated','ricoblanco123','Updated medical history and triage vitals for patient PT-2026-027.','2026-10-22 02:51:57'),(147,'info','Profile / Staff Account Updated','rexiemampang','User \'rexiemampang\' details were updated.','2026-11-02 07:26:09'),(148,'info','Profile / Staff Account Updated','rexiemampang','User \'rexiemampang\' details were updated.','2026-11-02 07:26:12'),(149,'info','Patient Approved','rexiemampang','Patient PT-2026-083 (Alexu B. Gallardo) was approved and scheduled for OPU Psychiatry.','2026-11-02 07:55:34'),(150,'info','Patient Arrived','rexiemampang','Patient PT-2026-083 (Alexu B. Gallardo) arrived at facility (Checked-In at Reception/Triage).','2026-11-02 07:55:58'),(151,'info','Consultation Ongoing','rexiemampang','Patient PT-2026-083 (Alexu B. Gallardo) consultation in progress with Attending Physician.','2026-11-02 07:56:26'),(152,'info','Doctor Assigned','alexgallardo13','Dr. Dr. Catherine Gomez assigned to patient PT-2026-031 (Carlos D. Mendoza).','2026-11-08 09:27:38'),(153,'info','Doctor Assigned','alexgallardo13','Dr. Dr. Emilio Cruz assigned to patient PT-2026-031 (Carlos D. Mendoza).','2026-11-08 09:27:41'),(154,'info','Doctor Assigned','alexgallardo13','Dr. Dr. Emilio Cruz assigned to patient PT-2026-025 (Garry B. Gallardo).','2026-11-08 09:27:43'),(155,'info','Doctor Assigned','alexgallardo13','Dr. Dr. Dominic Bautista assigned to patient PT-2026-023 (juhoon imnida).','2026-11-08 09:27:45'),(156,'info','Doctor Assigned','alexgallardo13','Dr. Dr. Emilio Cruz assigned to patient PT-2026-017 (Alexander Reyes).','2026-11-08 09:53:56'),(157,'info','Patient Approved','alexgallardo13','Patient PT-2026-032 (Lyka Miringhoy) was approved and scheduled for Neurology.','2026-11-08 09:56:03'),(158,'info','Patient Arrived','alexgallardo13','Patient PT-2026-032 (Lyka Miringhoy) arrived at facility (Checked-In at Reception/Triage).','2026-11-08 09:56:07'),(159,'info','Consultation Ongoing','alexgallardo13','Patient PT-2026-032 (Lyka Miringhoy) consultation in progress with Attending Physician.','2026-11-08 09:56:10'),(160,'info','Patient Rejected','alexgallardo13','Patient PT-2026-031 (Carlos D. Mendoza) registration was rejected.','2026-11-08 09:56:20'),(161,'info','Doctor Assigned','alexgallardo13','Dr. Dr. Dominic Bautista assigned to patient PT-2026-025 (Garry B. Gallardo).','2026-11-08 09:58:05'),(162,'info','Patient No-Show','admin','Patient PT-2026-084 (Alexu B. Gallardo) marked as no-show.','2026-11-08 11:04:14'),(163,'info','Patient Approved','ricoblanco123','Patient PT-2026-081 (Alexu B. Gallardo) was approved and scheduled for General Management.','2026-11-08 11:29:54'),(164,'info','Patient Arrived','ricoblanco123','Patient PT-2026-081 (Alexu B. Gallardo) arrived at facility (Checked-In at Reception/Triage).','2026-11-08 11:29:55'),(165,'info','Consultation Ongoing','ricoblanco123','Patient PT-2026-081 (Alexu B. Gallardo) consultation in progress with Attending Physician.','2026-11-08 11:29:56'),(166,'info','Patient Arrived','ricoblanco123','Patient PT-2026-026 (Daniel Padila) arrived at facility (Checked-In at Reception/Triage).','2026-11-08 11:30:10'),(167,'info','Consultation Ongoing','ricoblanco123','Patient PT-2026-026 (Daniel Padila) consultation in progress with Attending Physician.','2026-11-08 11:30:11'),(168,'info','Patient Approved','admin','Patient PT-2026-085 (Alexu B. Gallardo) was approved and scheduled for OPU Psychiatry.','2026-11-08 13:22:42'),(169,'info','Department Assigned/Corrected','ricoblanco123','Staff assigned/corrected department from \'Triage Pending\' to \'General Medical\' for patient PT-2026-5BC6F (deya tadu) based on chief complaint.','2026-11-08 16:27:26'),(170,'info','Department Assigned/Corrected','ricoblanco123','Staff assigned/corrected department from \'General Medical\' to \'Neurology\' for patient PT-2026-5BC6F (deya tadu) based on chief complaint.','2026-11-08 16:27:29'),(171,'info','Department Assigned/Corrected','ricoblanco123','Staff assigned/corrected department from \'Triage Pending\' to \'Neurology\' for patient PT-2026-BDFD1 (Loida Anque) based on chief complaint.','2026-11-08 16:27:32'),(172,'info','Department Assigned/Corrected','ricoblanco123','Staff assigned/corrected department from \'Triage Pending\' to \'OPU Psychiatry\' for patient PT-2026-0EAA5 (Bini Mikha) based on chief complaint.','2026-11-08 16:27:34'),(173,'info','Department Assigned/Corrected','ricoblanco123','Staff assigned/corrected department from \'Triage Pending\' to \'OPU Psychiatry\' for patient PT-2026-7EC47 (Ken Francis A. Sendon) based on chief complaint.','2026-11-08 16:27:36'),(174,'info','Department Assigned/Corrected','ricoblanco123','Staff assigned/corrected department from \'Triage Pending\' to \'OPU Psychiatry\' for patient PT-2026-80D7F (Maria C. Santos) based on chief complaint.','2026-11-08 16:27:38'),(175,'info','Department Assigned/Corrected','ricoblanco123','Staff assigned/corrected department from \'Triage Pending\' to \'General Medical\' for patient PT-2026-74F60 (Mamay B. Gallardo) based on chief complaint.','2026-11-08 16:27:40'),(176,'info','Department Assigned/Corrected','ricoblanco123','Staff assigned/corrected department from \'Triage Pending\' to \'Neurology\' for patient PT-2026-AE95F (Maria C. Santos) based on chief complaint.','2026-11-08 16:27:42'),(177,'info','Department Assigned/Corrected','ricoblanco123','Staff assigned/corrected department from \'Triage Pending\' to \'General Medical\' for patient PT-2026-C92E3 (Maria C. Santos) based on chief complaint.','2026-11-08 16:29:33'),(178,'info','Department Assigned/Corrected','ricoblanco123','Staff assigned/corrected department from \'Triage Pending\' to \'Neurology\' for patient PT-2026-48BE7 (Maria C. Santos) based on chief complaint.','2026-11-08 16:32:19'),(179,'info','Department Assigned/Corrected','ricoblanco123','Staff assigned/corrected department from \'General Management\' to \'General Medical\' for patient PT-2026-081 (Alexu B. Gallardo) based on chief complaint.','2026-11-08 16:45:02'),(180,'info','Staff Status Changed','admin','Staff \'alexgallardo13\' status changed to inactive.','2026-11-09 12:23:27'),(181,'info','Staff Status Changed','admin','Staff \'alexgallardo13\' status changed to active.','2026-11-09 12:23:36'),(182,'info','Department Assigned/Corrected','ricoblanco123','Staff assigned/corrected department from \'General\' to \'Neurology\' for patient PT-2026-009 (Jarelle Gallardo) based on chief complaint.','2026-11-10 11:22:41'),(183,'info','Department Assigned/Corrected','ricoblanco123','Staff assigned/corrected department from \'General Management\' to \'Neurology\' for patient PT-2026-027 (Daniel Padila) based on chief complaint.','2026-11-10 12:02:19'),(184,'info','Department Assigned/Corrected','ricoblanco123','Staff assigned/corrected department from \'General Medical\' to \'Neurology\' for patient PT-2026-081 (Alexu B. Gallardo) based on chief complaint.','2026-11-10 12:12:36'),(185,'info','Department Assigned/Corrected','ricoblanco123','Staff assigned/corrected department from \'General Medical\' to \'Neurology\' for patient PT-2026-74F60 (Mamay B. Gallardo) based on chief complaint.','2026-11-10 12:12:42'),(186,'info','Department Assigned/Corrected','ricoblanco123','Staff assigned/corrected department from \'General Medical\' to \'OPU Psychiatry\' for patient PT-2026-C92E3 (Maria C. Santos) based on chief complaint.','2026-11-10 12:12:49'),(187,'info','Department Assigned/Corrected','ricoblanco123','Staff assigned/corrected department from \'General Management\' to \'Neurology\' for patient PT-2026-026 (Daniel Padila) based on chief complaint.','2026-11-10 12:13:00'),(188,'info','Patient Approved','ricoblanco123','Patient PT-2026-024 (hyghuu asdasfd) was approved and scheduled for General Medical.','2026-11-10 12:29:10'),(189,'info','Patient Arrived','ricoblanco123','Patient PT-2026-024 (hyghuu asdasfd) arrived at facility (Checked-In at Reception/Triage).','2026-11-10 12:29:14'),(190,'info','Consultation Ongoing','ricoblanco123','Patient PT-2026-024 (hyghuu asdasfd) consultation in progress with Attending Physician.','2026-11-10 12:29:15'),(191,'info','Patient Approved','ricoblanco123','Patient PT-2026-014 (Joan Gallardo) was approved and scheduled for General Medical.','2026-11-10 12:32:52'),(192,'info','Patient Arrived','ricoblanco123','Patient PT-2026-014 (Joan Gallardo) arrived at facility (Checked-In at Reception/Triage).','2026-11-10 12:33:15'),(193,'info','Patient Approved','ricoblanco123','Patient PT-2026-013 (Alexito Gallardo) was approved and scheduled for General Medical.','2026-11-10 12:33:31'),(194,'info','Patient Approved','ricoblanco123','Patient PT-2026-005 (Juan Dela Cruz) was approved and scheduled for General Medical.','2026-11-10 12:33:54'),(195,'info','Patient Arrived','ricoblanco123','Patient PT-2026-013 (Alexito Gallardo) arrived at facility (Checked-In at Reception/Triage).','2026-11-10 12:34:06'),(196,'info','Progress Note Added','ricoblanco123','New progress note added for PT-2026-024 (hyghuu asdasfd).','2026-11-10 12:43:58'),(197,'info','Patient Completed','ricoblanco123','Patient PT-2026-024 (hyghuu asdasfd) medical consultation was fully executed, officially signed off, and completed.','2026-11-10 12:43:58'),(198,'info','Consultation Ongoing','ricoblanco123','Patient PT-2026-014 (Joan Gallardo) consultation in progress with Attending Physician.','2026-11-10 12:44:52'),(199,'info','Consultation Ongoing','ricoblanco123','Patient PT-2026-014 (Joan Gallardo) consultation in progress with Attending Physician.','2026-11-10 12:44:54'),(200,'info','Consultation Ongoing','ricoblanco123','Patient PT-2026-013 (Alexito Gallardo) consultation in progress with Attending Physician.','2026-11-10 12:45:02'),(201,'info','Consultation Ongoing','staff_user','Patient PT-2026-027 (Daniel Padila) consultation in progress with Attending Physician.','2026-11-10 12:52:31'),(202,'info','Consultation Ongoing','rexiemampang','Patient PT-2026-085 (Alexu B. Gallardo) consultation in progress with Attending Physician.','2026-11-10 13:01:36'),(203,'info','Consultation Ongoing','ricoblanco123','Patient PT-2026-005 (Juan Dela Cruz) consultation in progress with Attending Physician.','2026-11-10 13:02:31'),(204,'info','Consultation Ongoing','alexgallardo13','Patient PT-2026-5BC6F (deya tadu) consultation in progress with Attending Physician.','2026-11-10 13:03:11'),(205,'info','Patient Approved','alexgallardo13','Patient PT-2026-BDFD1 (Loida Anque) was approved and scheduled for Neurology.','2026-11-10 13:03:29'),(206,'info','Patient Approved','alexgallardo13','Patient PT-2026-74F60 (Mamay B. Gallardo) was approved and scheduled for Neurology.','2026-11-10 13:03:41'),(207,'info','Patient Approved','alexgallardo13','Patient PT-2026-AE95F (Maria C. Santos) was approved and scheduled for Neurology.','2026-11-10 13:13:30'),(208,'info','Patient Approved','alexgallardo13','Patient PT-2026-48BE7 (Maria C. Santos) was approved and scheduled for Neurology.','2026-11-10 13:13:35'),(209,'info','Patient Approved','alexgallardo13','Patient PT-2026-009 (Jarelle Gallardo) was approved and scheduled for Neurology.','2026-11-10 13:13:40'),(210,'info','Patient Arrived','alexgallardo13','Patient PT-2026-BDFD1 (Loida Anque) checked in at facility/triage desk.','2026-11-10 13:13:46'),(211,'info','Consultation Ongoing','alexgallardo13','Patient PT-2026-BDFD1 (Loida Anque) consultation in progress with Attending Physician.','2026-11-10 13:13:55'),(212,'info','Department Assigned/Corrected','alexgallardo13','Staff assigned/corrected department from \'Neurology\' to \'General Medical\' for patient PT-2026-5BC6F (deya tadu) based on chief complaint.','2026-11-10 14:20:43'),(213,'info','Patient Approved','ricoblanco123','Patient PT-2026-090 (Alexu B. Gallardo) was approved and scheduled for General Medical.','2026-11-13 14:42:32'),(214,'info','Patient Approved','ricoblanco123','Patient PT-2026-089 (Jarelle Gallardo) was approved and scheduled for General Medical.','2026-11-13 14:42:35'),(215,'info','Patient Approved','ricoblanco123','Patient PT-2026-088 (Jarelle Gallardo) was approved and scheduled for General Medical.','2026-11-13 14:42:41'),(216,'info','Patient Arrived','ricoblanco123','Patient PT-2026-090 (Alexu B. Gallardo) checked in at facility/triage desk.','2026-11-13 14:56:02'),(217,'info','Patient Approved','ricoblanco123','Patient PT-2026-092 (Alexu B. Gallardo) was approved and scheduled for General Medical.','2026-11-13 14:56:16'),(218,'info','Patient Arrived','ricoblanco123','Patient PT-2026-089 (Jarelle Gallardo) checked in at facility/triage desk.','2026-11-13 14:56:36'),(219,'info','Consultation Ongoing','ricoblanco123','Patient PT-2026-089 (Jarelle Gallardo) consultation in progress with Attending Physician.','2026-11-13 14:57:17'),(220,'info','Consultation Ongoing','ricoblanco123','Patient PT-2026-090 (Alexu B. Gallardo) consultation in progress with Attending Physician.','2026-11-13 14:57:40'),(221,'info','Patient Arrived','ricoblanco123','Patient PT-2026-092 (Alexu B. Gallardo) checked in at facility/triage desk.','2026-11-14 07:51:51'),(222,'info','Consultation Ongoing','ricoblanco123','Patient PT-2026-092 (Alexu B. Gallardo) consultation in progress with Attending Physician.','2026-11-14 07:52:18'),(223,'info','Progress Note Added','ricoblanco123','New progress note added for PT-2026-092 (Alexu B. Gallardo).','2026-11-14 07:59:11'),(224,'info','Patient Completed','ricoblanco123','Patient PT-2026-092 (Alexu B. Gallardo) medical consultation was fully executed, officially signed off, and completed.','2026-11-14 07:59:11'),(225,'info','Patient Approved','ricoblanco123','Patient PT-2026-091 (Alexu B. Gallardo) was approved and scheduled for General Medical.','2026-11-14 07:59:48'),(226,'info','Patient Approved','ricoblanco123','Patient PT-2026-093 (Alexu B. Gallardo) was approved and scheduled for General Medical.','2026-11-14 07:59:54'),(227,'info','Patient Arrived','ricoblanco123','Patient PT-2026-091 (Alexu B. Gallardo) checked in at facility/triage desk.','2026-11-14 08:00:04'),(228,'info','Consultation Ongoing','ricoblanco123','Patient PT-2026-091 (Alexu B. Gallardo) consultation in progress with Attending Physician.','2026-11-14 08:00:06'),(229,'info','Patient Approved','admin','Patient PT-2026-087 (deya tadu) was approved and scheduled for Neurology.','2026-11-14 08:24:12'),(230,'info','Department Assigned/Corrected','ricoblanco123','Staff assigned/corrected department from \'General Medical\' to \'Neurology\' for patient PT-2026-093 (Alexu B. Gallardo) based on chief complaint.','2026-11-16 03:00:11'),(231,'info','Department Assigned/Corrected','ricoblanco123','Staff assigned/corrected department from \'General Medical\' to \'Neurology\' for patient PT-2026-091 (Alexu B. Gallardo) based on chief complaint.','2026-11-16 03:00:20'),(232,'info','Doctor Assigned','ricoblanco123','Dr. Dr. Angelo Mercado, MD assigned to patient PT-2026-094 (andrea D. anque).','2026-11-16 13:37:05'),(233,'info','Patient Approved','ricoblanco123','Patient PT-2026-094 (andrea D. anque) was approved and scheduled for General Medical.','2026-11-16 13:37:08'),(240,'info','PATIENT_BOOK_APPOINTMENT','alexgallardo121301@gmail.com','Patient Alexu B. Gallardo (Ref: APT-2026-0073) booked General Medical appointment for 2026-11-19 (Afternoon (1:00 PM - 5:00 PM))','2026-11-16 17:48:13'),(245,'info','PATIENT_BOOK_APPOINTMENT','alexgallardo121301@gmail.com','Patient Alexu B. Gallardo (Ref: APT-2026-0077) booked General Medical appointment for 2026-11-20 (Morning (8:00 AM - 12:00 PM))','2026-11-16 18:19:03'),(246,'info','Patient Approved','ricoblanco123','Patient PT-2026-096 (Alexu B. Gallardo) was approved and scheduled for General Medical.','2026-11-16 19:42:05'),(247,'info','Doctor Assigned','ricoblanco123','Dr. Dr. Angelo Mercado, MD assigned to patient PT-2026-096 (Alexu B. Gallardo).','2026-11-16 19:42:11'),(248,'info','Patient Arrived','ricoblanco123','Patient PT-2026-096 (Alexu B. Gallardo) checked in at facility/triage desk.','2026-11-16 19:57:33'),(249,'info','Consultation Ongoing','ricoblanco123','Patient PT-2026-096 (Alexu B. Gallardo) consultation in progress with Dr. Angelo Mercado, MD.','2026-11-16 19:57:35'),(250,'info','Inquiry Resolved','ricoblanco123','Inquiry PT-2026-090 (Alexu B. Gallardo) was resolved and marked completed.','2026-11-16 20:02:49'),(251,'info','Patient Transferred','ricoblanco123','Patient Alexu B. Gallardo (PT-2026-096) transferred from General Medical to OPU Psychiatry (New ID: PT-2026-097). Origin record marked as Transferred.','2026-11-16 20:10:42'),(252,'info','Schedule & Physician Updated','alexgallardo13','Staff coordinated new schedule for PT-2026-F330B (jeonghan yoon): 2026-11-17 (Afternoon (1:00 PM - 5:00 PM)) with Dr. Emilio Cruz.','2026-11-16 20:16:50'),(253,'info','Patient Approved','alexgallardo13','Patient PT-2026-F330B (jeonghan yoon) was approved and scheduled for Neurology with Dr. Emilio Cruz.','2026-11-16 20:16:57'),(254,'info','Patient Arrived','ricoblanco123','Patient PT-2026-094 (andrea D. anque) checked in at facility/triage desk.','2026-11-17 11:11:24'),(255,'info','Consultation Ongoing','ricoblanco123','Patient PT-2026-094 (andrea D. anque) consultation in progress with Dr. Angelo Mercado, MD.','2026-11-17 11:11:26'),(256,'info','Doctor Assigned','ricoblanco123','Dr. Beatrice Alcantara, MD assigned to patient PT-2026-095 (Alexu B. Gallardo).','2026-11-17 11:12:58'),(257,'info','Patient Approved','ricoblanco123','Patient PT-2026-095 (Alexu B. Gallardo) was approved and scheduled for General Medical with Dr. Beatrice Alcantara, MD.','2026-11-17 11:13:32'),(258,'info','Doctor Assigned','ricoblanco123','Dr. Angelo Mercado, MD assigned to patient PT-2026-4E9DD (Gusion B. Gallardo).','2026-11-17 11:41:45'),(259,'info','Patient Approved','ricoblanco123','Patient PT-2026-4E9DD (Gusion B. Gallardo) was approved and scheduled for General Medical with Dr. Angelo Mercado, MD.','2026-11-17 11:41:46');
/*!40000 ALTER TABLE `system_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
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
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'admin',NULL,'System Administrator','$2y$10$UKLH4w8juR5DxVOvnOIT2OMD6yi184NkVAR5ZkI6rJk2lwAwBA4f6','admin','active','Administration',NULL,NULL,'2026-11-17 12:36:47',117,'2026-09-22 02:47:25'),(3,'anqueandrea22',NULL,'Andrea Anque','$2y$10$zKLftUpmbuEJqUo0KFK89uPaIE8eMEeciP3cDLyNZHPaX7oEQ5rG2','staff','active','OPU Psychiatry',NULL,NULL,'2026-10-21 23:51:15',31,'2026-10-04 06:37:59'),(4,'alexgallardo13',NULL,'Alex Gallardo','$2y$10$g/NZ2jZNQu9soBNpUZuoa.FShneZVx1BeddvnltvHMuWZpX/Q4s3G','staff','active','Neurology',NULL,'/sdn/public/uploads/avatars/avatar_4_1792331705.jpg','2026-11-17 04:24:30',62,'2026-10-04 09:40:31'),(5,'rexiemampang',NULL,'Rico Blanco','$2y$10$oRc4kgha4IQCGNiV.Cd.b.tS7TGylCH8WawIyjupjB6Ce72O56XeG','staff','active','OPU Psychiatry',NULL,NULL,'2026-11-02 07:01:18',9,'2026-10-10 15:02:27'),(6,'joanamarielopez',NULL,'Joana Marie Lopez','$2y$10$0y0yM1QcH34EchTU1oemOuUSU8MfV8M6JqJUGePZTwvQDpsbtBIjK','staff','active','OPU Psychiatry',NULL,NULL,NULL,0,'2026-10-10 15:14:41'),(8,'ricoblanco123',NULL,'Rico Blanco','$2y$10$ZfNFkaXRPzI4SCGkc2aAC.7gi3xAiI/mAXXW5OODkCEca7e2TjOTO','staff','active','General Management',NULL,NULL,'2026-11-17 12:08:48',44,'2026-10-14 02:40:08'),(9,'staff',NULL,NULL,'$2y$10$GIHYQHdQ45y7U1gA.TFOeOTHo75GSL5Z/FKYeemPMmTGj.RRwq1Ay','staff','active',NULL,NULL,NULL,'2026-11-02 05:42:14',1,'2026-10-14 07:41:22'),(10,'alexgallardo121301','alexgallardo121301@gmail.com',NULL,'$2y$11$5eBF9iNthLYEKzMEoxLXqO3Fu0.usupMjuAVeOgVtWlbeWHC/u5vm','','active',NULL,24,NULL,NULL,0,'2026-11-08 07:42:36'),(11,'superadmin',NULL,'Master Administrator','$2y$12$SYWz7hBea0fEGcYHO85cLuZvy68I/HhwKx2z5XpStCZJmzz93dTPG','','active','Hospital Executive Board',NULL,NULL,NULL,0,'2026-11-08 12:04:26'),(12,'aliksalt080824','aliksalt080824@gmail.com',NULL,'$2y$12$SEMJ29w3Z9alOnZn..1JReyKc7KIjGlLpxcRy2CwmuFIRCc1HTc2q','','active',NULL,54,NULL,NULL,0,'2026-11-12 16:53:57'),(13,'jeonghan1004','idkbutdea@gmail.com',NULL,'$2y$11$you/TYsS364Madhlu9EN8OvNMelvm1neczupPWYI9kHE/aZV7idki','','active',NULL,65,NULL,NULL,0,'2026-11-16 03:27:35'),(14,'hehelexsu2001','hehelexsu@gmail.com',NULL,'$2y$11$33ZZCWGSTcLctntHmwNZnun4JRRMrwju24k9Ym/jbJ0VCxCEmjTaC','','active',NULL,81,NULL,NULL,0,'2026-11-17 11:38:55');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Temporary table structure for view `v_active_sessions`
--

DROP TABLE IF EXISTS `v_active_sessions`;
/*!50001 DROP VIEW IF EXISTS `v_active_sessions`*/;
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

DROP TABLE IF EXISTS `v_doctor_availability`;

-- failed on view `v_doctor_availability`: CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_doctor_availability` AS select `d`.`department` AS `department`,count(0) AS `total_doctors`,sum(case when `d`.`status` = 'available' then 1 else 0 end) AS `available`,sum(case when `d`.`status` = 'booked' then 1 else 0 end) AS `booked`,sum(case when `d`.`status` = 'leave' then 1 else 0 end) AS `on_leave`,sum(`d`.`total_slots`) AS `total_slots`,sum(`d`.`booked_slots`) AS `booked_slots` from `doctors` `d` group by `d`.`department` order by `d`.`department`


--
-- Temporary table structure for view `v_doctor_summary`
--

DROP TABLE IF EXISTS `v_doctor_summary`;

-- failed on view `v_doctor_summary`: CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_doctor_summary` AS select `d`.`id` AS `id`,`d`.`name` AS `name`,`d`.`specialization` AS `specialization`,`d`.`department` AS `department`,`d`.`schedule_start` AS `schedule_start`,`d`.`schedule_end` AS `schedule_end`,`d`.`total_slots` AS `total_slots`,`d`.`booked_slots` AS `booked_slots`,round(`d`.`booked_slots` / `d`.`total_slots` * 100,0) AS `load_percentage`,`d`.`total_slots` - `d`.`booked_slots` AS `available_slots`,`d`.`status` AS `status`,`d`.`schedule_date` AS `schedule_date`,`d`.`created_at` AS `created_at` from `doctors` `d` order by `d`.`department`,`d`.`name`


--
-- Temporary table structure for view `v_login_history`
--

DROP TABLE IF EXISTS `v_login_history`;
/*!50001 DROP VIEW IF EXISTS `v_login_history`*/;
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

DROP TABLE IF EXISTS `v_staff_by_department`;
/*!50001 DROP VIEW IF EXISTS `v_staff_by_department`*/;
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

DROP TABLE IF EXISTS `v_staff_summary`;
/*!50001 DROP VIEW IF EXISTS `v_staff_summary`*/;
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

-- Dump completed on 2026-11-17 20:37:54
