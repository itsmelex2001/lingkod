-- LINGKOD System Administrator Settings Schema (MySQL)
-- Matches Laravel migrations 2026_11_26_100001..100004

CREATE TABLE IF NOT EXISTS `system_settings` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `group_key` VARCHAR(32) NOT NULL,
  `setting_key` VARCHAR(64) NOT NULL,
  `setting_value` TEXT NULL,
  `value_type` VARCHAR(20) NOT NULL DEFAULT 'string',
  `description` VARCHAR(255) NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `system_settings_setting_key_unique` (`setting_key`),
  KEY `system_settings_group_key_index` (`group_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `department_scheduling_rules` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `department_code` VARCHAR(32) NOT NULL,
  `display_name` VARCHAR(80) NOT NULL,
  `daily_capacity_limit` SMALLINT UNSIGNED NOT NULL DEFAULT 50,
  `am_slot_limit` SMALLINT UNSIGNED NULL,
  `pm_slot_limit` SMALLINT UNSIGNED NULL,
  `booking_cutoff_time` VARCHAR(8) NOT NULL DEFAULT '16:00',
  `qr_expiry_hours_override` SMALLINT UNSIGNED NULL,
  `allow_same_day_booking` TINYINT(1) NOT NULL DEFAULT 1,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `department_scheduling_rules_department_code_unique` (`department_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `chatbot_faqs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `slug` VARCHAR(80) NOT NULL,
  `locale` VARCHAR(5) NOT NULL DEFAULT 'en',
  `persona` VARCHAR(20) NOT NULL DEFAULT 'both',
  `question` VARCHAR(255) NOT NULL,
  `answer_html` TEXT NOT NULL,
  `keywords_json` JSON NULL,
  `follow_ups_json` JSON NULL,
  `sort_order` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `chatbot_faqs_slug_locale_unique` (`slug`, `locale`),
  KEY `chatbot_faqs_locale_persona_is_active_index` (`locale`, `persona`, `is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `system_backup_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `filename` VARCHAR(160) NOT NULL,
  `disk_path` VARCHAR(255) NOT NULL,
  `size_bytes` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `tables_count` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `trigger_type` VARCHAR(20) NOT NULL DEFAULT 'manual',
  `status` VARCHAR(20) NOT NULL DEFAULT 'completed',
  `error_message` TEXT NULL,
  `triggered_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `system_backup_logs_status_created_at_index` (`status`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
