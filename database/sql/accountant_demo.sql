-- =====================================================================
-- Accountant by AppAmora — database structure + DEMO data
-- Generated from the local development database on 2026-11-20.
--
-- Contains: all tables (schema version = every migration up to
-- 2026_11_20_135741), one demo user, two demo workspaces ("Home" and
-- "Demo Shop") with accounts, categories, budgets and 161 transactions.
-- Temporary tables (sessions, cache, jobs, tokens) are created empty.
--
-- How to import
--   GoDaddy cPanel: phpMyAdmin -> select your database -> Import -> this file.
--   Linux:          mysql -u USER -p DATABASE < database/sql/accountant_demo.sql
--   (Do NOT add CREATE DATABASE / USE lines: cPanel database names are prefixed.)
--
-- SECURITY
--   * Demo login: demo@example.com / password  -> delete this user or change
--     its password before the site is public (Settings is not enough: run
--     DELETE FROM users WHERE email = 'demo@example.com'; to remove it).
--   * This file is in a PUBLIC GitHub repository. Never put real passwords,
--     real customer data or database credentials in it.
-- =====================================================================


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
DROP TABLE IF EXISTS `audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `audit_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `workspace_id` bigint(20) unsigned DEFAULT NULL,
  `actor_id` bigint(20) unsigned DEFAULT NULL,
  `action` varchar(20) NOT NULL,
  `auditable_type` varchar(255) NOT NULL,
  `auditable_id` bigint(20) unsigned NOT NULL,
  `before` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`before`)),
  `after` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`after`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `audit_logs_actor_id_foreign` (`actor_id`),
  KEY `audit_logs_auditable_type_auditable_id_index` (`auditable_type`,`auditable_id`),
  KEY `audit_logs_workspace_id_created_at_index` (`workspace_id`,`created_at`),
  CONSTRAINT `audit_logs_actor_id_foreign` FOREIGN KEY (`actor_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `audit_logs_workspace_id_foreign` FOREIGN KEY (`workspace_id`) REFERENCES `workspaces` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `audit_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `audit_logs` ENABLE KEYS */;
DROP TABLE IF EXISTS `budget_alerts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `budget_alerts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `budget_id` bigint(20) unsigned NOT NULL,
  `period` char(7) NOT NULL,
  `threshold` smallint(5) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `budget_alerts_budget_id_period_threshold_unique` (`budget_id`,`period`,`threshold`),
  CONSTRAINT `budget_alerts_budget_id_foreign` FOREIGN KEY (`budget_id`) REFERENCES `budgets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `budget_alerts` DISABLE KEYS */;
/*!40000 ALTER TABLE `budget_alerts` ENABLE KEYS */;
DROP TABLE IF EXISTS `budgets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `budgets` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `workspace_id` bigint(20) unsigned NOT NULL,
  `category_id` bigint(20) unsigned DEFAULT NULL,
  `amount` decimal(19,4) NOT NULL,
  `alert_thresholds` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`alert_thresholds`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `budgets_workspace_id_foreign` (`workspace_id`),
  KEY `budgets_category_id_foreign` (`category_id`),
  CONSTRAINT `budgets_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `budgets_workspace_id_foreign` FOREIGN KEY (`workspace_id`) REFERENCES `workspaces` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `budgets` DISABLE KEYS */;
INSERT INTO `budgets` VALUES
(1,1,NULL,1300.0000,'[70,90,100]','2026-11-20 13:07:10','2026-11-20 13:07:10'),
(2,1,3,350.0000,'[70,90,100]','2026-11-20 13:07:10','2026-11-20 13:07:10'),
(3,1,4,90.0000,'[70,90,100]','2026-11-20 13:07:10','2026-11-20 13:07:10'),
(4,1,7,150.0000,'[70,90,100]','2026-11-20 13:07:10','2026-11-20 13:07:10');
/*!40000 ALTER TABLE `budgets` ENABLE KEYS */;
DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` bigint(20) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` bigint(20) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `workspace_id` bigint(20) unsigned NOT NULL,
  `parent_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `type` varchar(20) NOT NULL,
  `color` varchar(7) DEFAULT NULL,
  `normal_amount` decimal(19,4) DEFAULT NULL,
  `normal_tolerance` smallint(5) unsigned NOT NULL DEFAULT 20,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `categories_parent_id_foreign` (`parent_id`),
  KEY `categories_workspace_id_type_index` (`workspace_id`,`type`),
  CONSTRAINT `categories_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `categories_workspace_id_foreign` FOREIGN KEY (`workspace_id`) REFERENCES `workspaces` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES
(1,1,NULL,'Salary','income','#16a34a',NULL,20,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(2,1,NULL,'Other income','income','#65a30d',NULL,20,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(3,1,NULL,'Groceries','expense','#ea580c',220.0000,20,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(4,1,NULL,'Dining out','expense','#f59e0b',60.0000,20,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(5,1,NULL,'Housing','expense','#7c3aed',NULL,20,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(6,1,NULL,'Utilities','expense','#0891b2',55.0000,20,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(7,1,NULL,'Transport','expense','#2563eb',NULL,20,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(8,1,NULL,'Health','expense','#db2777',NULL,20,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(9,1,NULL,'Shopping','expense','#c026d3',NULL,20,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(10,1,NULL,'Entertainment','expense','#e11d48',NULL,20,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(11,1,NULL,'Subscriptions','expense','#4f46e5',NULL,20,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(12,1,NULL,'Other expenses','expense','#64748b',NULL,20,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(13,2,NULL,'Sales','income','#16a34a',3500.0000,25,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(14,2,NULL,'Services','income','#059669',NULL,20,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(15,2,NULL,'Other income','income','#65a30d',NULL,20,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(16,2,NULL,'Suppliers','expense','#ea580c',1500.0000,25,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(17,2,NULL,'Rent','expense','#7c3aed',NULL,20,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(18,2,NULL,'Salaries','expense','#2563eb',NULL,20,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(19,2,NULL,'Utilities','expense','#0891b2',NULL,20,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(20,2,NULL,'Marketing','expense','#db2777',150.0000,25,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(21,2,NULL,'Software','expense','#4f46e5',NULL,20,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(22,2,NULL,'Transport','expense','#0284c7',NULL,20,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(23,2,NULL,'Taxes & fees','expense','#b91c1c',NULL,20,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(24,2,NULL,'Other expenses','expense','#64748b',NULL,20,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL);
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
DROP TABLE IF EXISTS `category_alerts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `category_alerts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `category_id` bigint(20) unsigned NOT NULL,
  `period` char(7) NOT NULL,
  `direction` varchar(10) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `category_alerts_category_id_period_direction_unique` (`category_id`,`period`,`direction`),
  CONSTRAINT `category_alerts_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `category_alerts` DISABLE KEYS */;
/*!40000 ALTER TABLE `category_alerts` ENABLE KEYS */;
DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` varchar(255) NOT NULL,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`),
  KEY `failed_jobs_connection_queue_failed_at_index` (`connection`,`queue`,`failed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `financial_accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `financial_accounts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `workspace_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `type` varchar(20) NOT NULL,
  `currency` char(3) NOT NULL,
  `opening_balance` decimal(19,4) NOT NULL DEFAULT 0.0000,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `financial_accounts_workspace_id_foreign` (`workspace_id`),
  CONSTRAINT `financial_accounts_workspace_id_foreign` FOREIGN KEY (`workspace_id`) REFERENCES `workspaces` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `financial_accounts` DISABLE KEYS */;
INSERT INTO `financial_accounts` VALUES
(1,1,'Main bank','bank','EUR',1200.0000,1,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(2,1,'Cash','cash','EUR',80.0000,1,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(3,2,'Business account','bank','EUR',5000.0000,1,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL);
/*!40000 ALTER TABLE `financial_accounts` ENABLE KEYS */;
DROP TABLE IF EXISTS `imports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `imports` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `workspace_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `filename` varchar(255) NOT NULL,
  `checksum` char(64) NOT NULL,
  `rows_imported` int(10) unsigned NOT NULL,
  `rows_skipped` int(10) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `imports_user_id_foreign` (`user_id`),
  KEY `imports_workspace_id_checksum_index` (`workspace_id`,`checksum`),
  CONSTRAINT `imports_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `imports_workspace_id_foreign` FOREIGN KEY (`workspace_id`) REFERENCES `workspaces` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `imports` DISABLE KEYS */;
/*!40000 ALTER TABLE `imports` ENABLE KEYS */;
DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` smallint(5) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES
(1,'0001_01_01_000000_create_users_table',1),
(2,'0001_01_01_000001_create_cache_table',1),
(3,'0001_01_01_000002_create_jobs_table',1),
(4,'2026_11_18_152828_create_personal_access_tokens_table',1),
(5,'2026_11_20_132117_create_workspaces_table',1),
(6,'2026_11_20_132118_create_financial_accounts_table',1),
(7,'2026_11_20_132119_create_categories_table',1),
(8,'2026_11_20_132120_create_transactions_table',1),
(9,'2026_11_20_132121_create_budgets_table',1),
(10,'2026_11_20_132122_create_audit_logs_table',1),
(11,'2026_11_20_132123_create_budget_alerts_table',1),
(12,'2026_11_20_132124_create_workspace_user_table',1),
(13,'2026_11_20_132125_create_notifications_table',1),
(14,'2026_11_20_135737_add_return_amount_to_transactions_table',1),
(15,'2026_11_20_135738_add_normal_amount_to_categories_table',1),
(16,'2026_11_20_135739_create_category_alerts_table',1),
(17,'2026_11_20_135740_add_ai_insights_enabled_to_workspaces_table',1),
(18,'2026_11_20_135741_create_imports_table',1);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifications` (
  `id` char(36) NOT NULL,
  `type` varchar(255) NOT NULL,
  `notifiable_type` varchar(255) NOT NULL,
  `notifiable_id` bigint(20) unsigned NOT NULL,
  `data` text NOT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `notifications_notifiable_type_notifiable_id_index` (`notifiable_type`,`notifiable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint(20) unsigned NOT NULL,
  `name` text NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`),
  KEY `personal_access_tokens_expires_at_index` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `transactions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `workspace_id` bigint(20) unsigned NOT NULL,
  `type` varchar(20) NOT NULL,
  `account_id` bigint(20) unsigned NOT NULL,
  `transfer_account_id` bigint(20) unsigned DEFAULT NULL,
  `category_id` bigint(20) unsigned DEFAULT NULL,
  `amount` decimal(19,4) NOT NULL,
  `return_amount` decimal(19,4) DEFAULT NULL,
  `currency` char(3) NOT NULL,
  `transaction_date` date NOT NULL,
  `merchant` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `transactions_account_id_foreign` (`account_id`),
  KEY `transactions_transfer_account_id_foreign` (`transfer_account_id`),
  KEY `transactions_category_id_foreign` (`category_id`),
  KEY `transactions_created_by_foreign` (`created_by`),
  KEY `transactions_updated_by_foreign` (`updated_by`),
  KEY `transactions_workspace_id_transaction_date_index` (`workspace_id`,`transaction_date`),
  CONSTRAINT `transactions_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `financial_accounts` (`id`),
  CONSTRAINT `transactions_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`),
  CONSTRAINT `transactions_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `transactions_transfer_account_id_foreign` FOREIGN KEY (`transfer_account_id`) REFERENCES `financial_accounts` (`id`),
  CONSTRAINT `transactions_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `transactions_workspace_id_foreign` FOREIGN KEY (`workspace_id`) REFERENCES `workspaces` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=162 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `transactions` DISABLE KEYS */;
INSERT INTO `transactions` VALUES
(1,1,'income',1,NULL,1,1450.0000,NULL,'EUR','2026-08-19','Employer',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(2,1,'expense',1,NULL,5,450.0000,NULL,'EUR','2026-08-03','Landlord',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(3,1,'expense',1,NULL,5,450.0000,NULL,'EUR','2026-08-07','Landlord',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(4,1,'expense',1,NULL,3,58.3200,NULL,'EUR','2026-08-27','Supermarket',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(5,1,'expense',1,NULL,3,45.1000,NULL,'EUR','2026-08-27','Supermarket',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(6,1,'expense',1,NULL,3,40.2500,NULL,'EUR','2026-08-14','Supermarket',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(7,1,'expense',1,NULL,3,40.9300,NULL,'EUR','2026-08-10','Supermarket',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(8,1,'expense',1,NULL,3,54.9200,NULL,'EUR','2026-08-09','Supermarket',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(9,1,'expense',1,NULL,3,58.6200,NULL,'EUR','2026-08-10','Supermarket',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(10,1,'expense',1,NULL,4,19.0100,NULL,'EUR','2026-08-29','Café',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(11,1,'expense',1,NULL,4,29.2100,NULL,'EUR','2026-08-01','Café',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(12,1,'expense',1,NULL,4,8.3600,NULL,'EUR','2026-08-06','Café',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(13,1,'expense',1,NULL,4,16.1600,NULL,'EUR','2026-08-25','Café',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(14,1,'expense',1,NULL,7,30.9100,NULL,'EUR','2026-08-28','Fuel station',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(15,1,'expense',1,NULL,7,39.3000,NULL,'EUR','2026-08-08','Fuel station',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(16,1,'expense',1,NULL,7,34.4900,NULL,'EUR','2026-08-04','Fuel station',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(17,1,'expense',1,NULL,6,56.6100,NULL,'EUR','2026-08-31','Electricity company',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(18,1,'expense',1,NULL,11,12.0000,NULL,'EUR','2026-08-02','Streaming service',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(19,1,'expense',1,NULL,10,16.1300,NULL,'EUR','2026-08-15','Cinema',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(20,1,'expense',1,NULL,10,20.0600,NULL,'EUR','2026-08-16','Cinema',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(21,1,'income',1,NULL,1,1450.0000,NULL,'EUR','2026-09-05','Employer',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(22,1,'expense',1,NULL,5,450.0000,NULL,'EUR','2026-09-07','Landlord',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(23,1,'expense',1,NULL,5,450.0000,NULL,'EUR','2026-09-02','Landlord',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(24,1,'expense',1,NULL,3,50.6600,NULL,'EUR','2026-09-26','Supermarket',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(25,1,'expense',1,NULL,3,66.7400,NULL,'EUR','2026-09-30','Supermarket',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(26,1,'expense',1,NULL,3,47.1500,NULL,'EUR','2026-09-13','Supermarket',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(27,1,'expense',1,NULL,3,55.0000,NULL,'EUR','2026-09-01','Supermarket',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(28,1,'expense',1,NULL,3,69.1200,NULL,'EUR','2026-09-10','Supermarket',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(29,1,'expense',1,NULL,3,55.9200,NULL,'EUR','2026-09-10','Supermarket',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(30,1,'expense',1,NULL,4,11.9800,NULL,'EUR','2026-09-28','Café',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(31,1,'expense',1,NULL,4,29.9000,NULL,'EUR','2026-09-25','Café',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(32,1,'expense',1,NULL,4,22.0200,NULL,'EUR','2026-09-03','Café',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(33,1,'expense',1,NULL,4,27.5100,NULL,'EUR','2026-09-06','Café',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(34,1,'expense',1,NULL,7,37.0400,NULL,'EUR','2026-09-09','Fuel station',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(35,1,'expense',1,NULL,7,44.3800,NULL,'EUR','2026-09-17','Fuel station',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(36,1,'expense',1,NULL,7,25.6900,NULL,'EUR','2026-09-30','Fuel station',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(37,1,'expense',1,NULL,6,63.2300,NULL,'EUR','2026-09-30','Electricity company',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(38,1,'expense',1,NULL,11,12.0000,NULL,'EUR','2026-09-11','Streaming service',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(39,1,'expense',1,NULL,10,19.7500,NULL,'EUR','2026-09-26','Cinema',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(40,1,'expense',1,NULL,10,16.1900,NULL,'EUR','2026-09-16','Cinema',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(41,1,'income',1,NULL,1,1450.0000,NULL,'EUR','2026-10-10','Employer',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(42,1,'expense',1,NULL,5,450.0000,NULL,'EUR','2026-10-14','Landlord',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(43,1,'expense',1,NULL,5,450.0000,NULL,'EUR','2026-10-19','Landlord',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(44,1,'expense',1,NULL,3,50.9600,NULL,'EUR','2026-10-28','Supermarket',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(45,1,'expense',1,NULL,3,54.9200,NULL,'EUR','2026-10-08','Supermarket',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(46,1,'expense',1,NULL,3,36.1200,NULL,'EUR','2026-10-01','Supermarket',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(47,1,'expense',1,NULL,3,54.0500,NULL,'EUR','2026-10-16','Supermarket',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(48,1,'expense',1,NULL,3,35.3800,NULL,'EUR','2026-10-27','Supermarket',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(49,1,'expense',1,NULL,3,36.2800,NULL,'EUR','2026-10-30','Supermarket',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(50,1,'expense',1,NULL,4,17.2900,NULL,'EUR','2026-10-20','Café',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(51,1,'expense',1,NULL,4,13.0800,NULL,'EUR','2026-10-11','Café',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(52,1,'expense',1,NULL,4,16.5700,NULL,'EUR','2026-10-06','Café',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(53,1,'expense',1,NULL,4,25.4000,NULL,'EUR','2026-10-18','Café',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(54,1,'expense',1,NULL,7,28.4900,NULL,'EUR','2026-10-06','Fuel station',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(55,1,'expense',1,NULL,7,46.9300,NULL,'EUR','2026-10-11','Fuel station',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(56,1,'expense',1,NULL,7,49.0400,NULL,'EUR','2026-10-19','Fuel station',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(57,1,'expense',1,NULL,6,66.9200,NULL,'EUR','2026-10-21','Electricity company',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(58,1,'expense',1,NULL,11,12.0000,NULL,'EUR','2026-10-25','Streaming service',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(59,1,'expense',1,NULL,10,19.0400,NULL,'EUR','2026-10-25','Cinema',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(60,1,'expense',1,NULL,10,24.9100,NULL,'EUR','2026-10-03','Cinema',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(61,1,'income',1,NULL,1,1450.0000,NULL,'EUR','2026-11-03','Employer',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(62,1,'expense',1,NULL,5,450.0000,NULL,'EUR','2026-11-12','Landlord',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(63,1,'expense',1,NULL,5,450.0000,NULL,'EUR','2026-11-15','Landlord',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(64,1,'expense',1,NULL,3,65.3200,NULL,'EUR','2026-11-08','Supermarket',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(65,1,'expense',1,NULL,3,49.5400,NULL,'EUR','2026-11-03','Supermarket',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(66,1,'expense',1,NULL,3,65.1900,NULL,'EUR','2026-11-12','Supermarket',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(67,1,'expense',1,NULL,3,66.4300,NULL,'EUR','2026-11-18','Supermarket',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(68,1,'expense',1,NULL,3,49.3800,NULL,'EUR','2026-11-04','Supermarket',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(69,1,'expense',1,NULL,3,62.5500,NULL,'EUR','2026-11-11','Supermarket',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(70,1,'expense',1,NULL,4,29.1600,NULL,'EUR','2026-11-19','Café',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(71,1,'expense',1,NULL,4,29.3200,NULL,'EUR','2026-11-01','Café',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(72,1,'expense',1,NULL,4,8.6500,NULL,'EUR','2026-11-19','Café',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(73,1,'expense',1,NULL,4,21.0600,NULL,'EUR','2026-11-12','Café',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(74,1,'expense',1,NULL,7,48.0300,NULL,'EUR','2026-11-15','Fuel station',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(75,1,'expense',1,NULL,7,41.2500,NULL,'EUR','2026-11-16','Fuel station',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(76,1,'expense',1,NULL,7,43.8700,NULL,'EUR','2026-11-01','Fuel station',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(77,1,'expense',1,NULL,6,57.1000,NULL,'EUR','2026-11-08','Electricity company',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(78,1,'expense',1,NULL,11,12.0000,NULL,'EUR','2026-11-02','Streaming service',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(79,1,'expense',1,NULL,10,16.8600,NULL,'EUR','2026-11-13','Cinema',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(80,1,'expense',1,NULL,10,21.0400,NULL,'EUR','2026-11-04','Cinema',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(81,1,'transfer',1,2,NULL,100.0000,NULL,'EUR','2026-11-03',NULL,'ATM withdrawal',NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(82,2,'income',3,NULL,13,191.7200,NULL,'EUR','2026-08-17','Customers',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(83,2,'income',3,NULL,13,270.1100,NULL,'EUR','2026-08-22','Customers',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(84,2,'income',3,NULL,13,208.6900,NULL,'EUR','2026-08-01','Customers',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(85,2,'income',3,NULL,13,213.1400,NULL,'EUR','2026-08-11','Customers',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(86,2,'income',3,NULL,13,352.3600,NULL,'EUR','2026-08-19','Customers',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(87,2,'income',3,NULL,13,367.2200,NULL,'EUR','2026-08-12','Customers',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(88,2,'income',3,NULL,13,284.6300,NULL,'EUR','2026-08-21','Customers',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(89,2,'income',3,NULL,13,251.3100,NULL,'EUR','2026-08-06','Customers',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(90,2,'income',3,NULL,13,377.5200,NULL,'EUR','2026-08-16','Customers',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(91,2,'income',3,NULL,13,344.6200,NULL,'EUR','2026-08-28','Customers',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(92,2,'income',3,NULL,13,242.0900,NULL,'EUR','2026-08-16','Customers',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(93,2,'income',3,NULL,13,267.8000,NULL,'EUR','2026-08-29','Customers',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(94,2,'expense',3,NULL,16,337.5200,469.1500,'EUR','2026-08-05','Wholesaler',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(95,2,'expense',3,NULL,16,539.6600,631.4000,'EUR','2026-08-11','Wholesaler',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(96,2,'expense',3,NULL,16,640.7900,954.7800,'EUR','2026-08-14','Wholesaler',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(97,2,'expense',3,NULL,17,800.0000,NULL,'EUR','2026-08-24','Property owner',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(98,2,'expense',3,NULL,18,1100.0000,NULL,'EUR','2026-08-30','Payroll',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(99,2,'expense',3,NULL,20,154.0000,NULL,'EUR','2026-08-14','Social media ads',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(100,2,'expense',3,NULL,20,189.3000,NULL,'EUR','2026-08-25','Social media ads',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(101,2,'expense',3,NULL,21,29.0000,NULL,'EUR','2026-08-03','POS subscription',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(102,2,'income',3,NULL,13,235.7800,NULL,'EUR','2026-09-01','Customers',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(103,2,'income',3,NULL,13,204.4300,NULL,'EUR','2026-09-19','Customers',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(104,2,'income',3,NULL,13,375.9400,NULL,'EUR','2026-09-22','Customers',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(105,2,'income',3,NULL,13,187.0700,NULL,'EUR','2026-09-27','Customers',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(106,2,'income',3,NULL,13,191.0100,NULL,'EUR','2026-09-26','Customers',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(107,2,'income',3,NULL,13,215.5600,NULL,'EUR','2026-09-29','Customers',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(108,2,'income',3,NULL,13,303.9100,NULL,'EUR','2026-09-11','Customers',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(109,2,'income',3,NULL,13,378.2000,NULL,'EUR','2026-09-20','Customers',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(110,2,'income',3,NULL,13,327.7100,NULL,'EUR','2026-09-25','Customers',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(111,2,'income',3,NULL,13,377.0300,NULL,'EUR','2026-09-06','Customers',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(112,2,'income',3,NULL,13,403.5100,NULL,'EUR','2026-09-19','Customers',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(113,2,'income',3,NULL,13,369.9500,NULL,'EUR','2026-09-19','Customers',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(114,2,'expense',3,NULL,16,522.8400,810.4000,'EUR','2026-09-09','Wholesaler',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(115,2,'expense',3,NULL,16,339.3600,515.8300,'EUR','2026-09-12','Wholesaler',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(116,2,'expense',3,NULL,16,536.4400,729.5600,'EUR','2026-09-15','Wholesaler',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(117,2,'expense',3,NULL,17,800.0000,NULL,'EUR','2026-09-11','Property owner',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(118,2,'expense',3,NULL,18,1100.0000,NULL,'EUR','2026-09-19','Payroll',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(119,2,'expense',3,NULL,20,127.8000,NULL,'EUR','2026-09-13','Social media ads',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(120,2,'expense',3,NULL,20,91.9300,NULL,'EUR','2026-09-29','Social media ads',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(121,2,'expense',3,NULL,21,29.0000,NULL,'EUR','2026-09-23','POS subscription',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(122,2,'income',3,NULL,13,242.7800,NULL,'EUR','2026-10-13','Customers',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(123,2,'income',3,NULL,13,355.3900,NULL,'EUR','2026-10-07','Customers',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(124,2,'income',3,NULL,13,313.5400,NULL,'EUR','2026-10-20','Customers',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(125,2,'income',3,NULL,13,410.6400,NULL,'EUR','2026-10-10','Customers',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(126,2,'income',3,NULL,13,351.2400,NULL,'EUR','2026-10-02','Customers',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(127,2,'income',3,NULL,13,394.6600,NULL,'EUR','2026-10-07','Customers',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(128,2,'income',3,NULL,13,363.2900,NULL,'EUR','2026-10-14','Customers',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(129,2,'income',3,NULL,13,299.3400,NULL,'EUR','2026-10-29','Customers',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(130,2,'income',3,NULL,13,253.2900,NULL,'EUR','2026-10-05','Customers',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(131,2,'income',3,NULL,13,299.5400,NULL,'EUR','2026-10-05','Customers',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(132,2,'income',3,NULL,13,230.2900,NULL,'EUR','2026-10-26','Customers',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(133,2,'income',3,NULL,13,389.2400,NULL,'EUR','2026-10-30','Customers',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(134,2,'expense',3,NULL,16,741.2500,1045.1600,'EUR','2026-10-10','Wholesaler',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(135,2,'expense',3,NULL,16,544.7300,729.9400,'EUR','2026-10-05','Wholesaler',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(136,2,'expense',3,NULL,16,649.8900,942.3400,'EUR','2026-10-27','Wholesaler',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(137,2,'expense',3,NULL,17,800.0000,NULL,'EUR','2026-10-01','Property owner',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(138,2,'expense',3,NULL,18,1100.0000,NULL,'EUR','2026-10-25','Payroll',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(139,2,'expense',3,NULL,20,169.3400,NULL,'EUR','2026-10-13','Social media ads',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(140,2,'expense',3,NULL,20,182.4400,NULL,'EUR','2026-10-05','Social media ads',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(141,2,'expense',3,NULL,21,29.0000,NULL,'EUR','2026-10-23','POS subscription',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(142,2,'income',3,NULL,13,241.4300,NULL,'EUR','2026-11-01','Customers',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(143,2,'income',3,NULL,13,204.1700,NULL,'EUR','2026-11-14','Customers',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(144,2,'income',3,NULL,13,221.1800,NULL,'EUR','2026-11-12','Customers',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(145,2,'income',3,NULL,13,287.4900,NULL,'EUR','2026-11-10','Customers',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(146,2,'income',3,NULL,13,283.8800,NULL,'EUR','2026-11-16','Customers',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(147,2,'income',3,NULL,13,199.3500,NULL,'EUR','2026-11-03','Customers',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(148,2,'income',3,NULL,13,325.4300,NULL,'EUR','2026-11-06','Customers',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(149,2,'income',3,NULL,13,262.7000,NULL,'EUR','2026-11-08','Customers',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(150,2,'income',3,NULL,13,376.0800,NULL,'EUR','2026-11-19','Customers',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(151,2,'income',3,NULL,13,206.5000,NULL,'EUR','2026-11-13','Customers',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(152,2,'income',3,NULL,13,182.3200,NULL,'EUR','2026-11-05','Customers',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(153,2,'income',3,NULL,13,363.9100,NULL,'EUR','2026-11-17','Customers',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(154,2,'expense',3,NULL,16,708.4600,857.2400,'EUR','2026-11-13','Wholesaler',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(155,2,'expense',3,NULL,16,870.6200,1166.6300,'EUR','2026-11-12','Wholesaler',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(156,2,'expense',3,NULL,16,602.9400,717.5000,'EUR','2026-11-14','Wholesaler',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(157,2,'expense',3,NULL,17,800.0000,NULL,'EUR','2026-11-20','Property owner',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(158,2,'expense',3,NULL,18,1100.0000,NULL,'EUR','2026-11-14','Payroll',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(159,2,'expense',3,NULL,20,125.7800,NULL,'EUR','2026-11-10','Social media ads',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(160,2,'expense',3,NULL,20,149.5000,NULL,'EUR','2026-11-10','Social media ads',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(161,2,'expense',3,NULL,21,29.0000,NULL,'EUR','2026-11-01','POS subscription',NULL,NULL,NULL,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL);
/*!40000 ALTER TABLE `transactions` ENABLE KEYS */;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES
(1,'Demo User','demo@example.com','2026-11-20 13:07:10','$2y$12$MxBMdvQgvkvNzNDUzCU8i.D7Ea4loMgtyW8QZyphOK5PYiVVJXxpy','014Wo8qsHRv578Els2qODmQtY1BTHBZMkSPYaDSEX9GWq1TqYaPjdFlSFHdi','2026-11-20 13:07:10','2026-11-20 13:07:10');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
DROP TABLE IF EXISTS `workspace_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `workspace_user` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `workspace_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `role` varchar(20) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `workspace_user_workspace_id_user_id_unique` (`workspace_id`,`user_id`),
  KEY `workspace_user_user_id_foreign` (`user_id`),
  CONSTRAINT `workspace_user_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `workspace_user_workspace_id_foreign` FOREIGN KEY (`workspace_id`) REFERENCES `workspaces` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `workspace_user` DISABLE KEYS */;
INSERT INTO `workspace_user` VALUES
(1,1,1,'owner','2026-11-20 13:07:10','2026-11-20 13:07:10'),
(2,2,1,'owner','2026-11-20 13:07:10','2026-11-20 13:07:10');
/*!40000 ALTER TABLE `workspace_user` ENABLE KEYS */;
DROP TABLE IF EXISTS `workspaces`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `workspaces` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `type` varchar(20) NOT NULL,
  `base_currency` char(3) NOT NULL,
  `timezone` varchar(64) NOT NULL,
  `country` char(2) DEFAULT NULL,
  `ai_insights_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `owner_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `workspaces_owner_id_foreign` (`owner_id`),
  CONSTRAINT `workspaces_owner_id_foreign` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `workspaces` DISABLE KEYS */;
INSERT INTO `workspaces` VALUES
(1,'Home','personal','EUR','Europe/Tirane','AL',0,1,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL),
(2,'Demo Shop','business','EUR','Europe/Tirane','AL',0,1,'2026-11-20 13:07:10','2026-11-20 13:07:10',NULL);
/*!40000 ALTER TABLE `workspaces` ENABLE KEYS */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

