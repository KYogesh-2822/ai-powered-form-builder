
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
DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` bigint(20) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` bigint(20) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `cache_locks` WRITE;
/*!40000 ALTER TABLE `cache_locks` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_locks` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `form_automations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `form_automations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` char(36) NOT NULL,
  `form_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `type` varchar(30) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'queued',
  `input` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`input`)),
  `result_schema` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`result_schema`)),
  `provider` varchar(40) DEFAULT NULL,
  `model` varchar(100) DEFAULT NULL,
  `input_tokens` int(10) unsigned DEFAULT NULL,
  `output_tokens` int(10) unsigned DEFAULT NULL,
  `latency_ms` int(10) unsigned DEFAULT NULL,
  `error` text DEFAULT NULL,
  `started_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `applied_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `form_automations_uuid_unique` (`uuid`),
  KEY `form_automations_form_id_type_created_at_index` (`form_id`,`type`,`created_at`),
  KEY `form_automations_status_created_at_index` (`status`,`created_at`),
  KEY `form_automations_user_id_created_at_index` (`user_id`,`created_at`),
  CONSTRAINT `form_automations_form_id_foreign` FOREIGN KEY (`form_id`) REFERENCES `forms` (`id`) ON DELETE CASCADE,
  CONSTRAINT `form_automations_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `form_automations` WRITE;
/*!40000 ALTER TABLE `form_automations` DISABLE KEYS */;
/*!40000 ALTER TABLE `form_automations` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `form_submissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `form_submissions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `form_id` bigint(20) unsigned NOT NULL,
  `reference` char(36) NOT NULL,
  `submission_token` char(36) DEFAULT NULL,
  `answers` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`answers`)),
  `searchable_text` longtext DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'complete',
  `ip_hash` char(64) DEFAULT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `started_at` timestamp NULL DEFAULT NULL,
  `submitted_at` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `form_submissions_reference_unique` (`reference`),
  UNIQUE KEY `form_submissions_form_id_submission_token_unique` (`form_id`,`submission_token`),
  KEY `form_submissions_form_id_submitted_at_index` (`form_id`,`submitted_at`),
  KEY `form_submissions_form_id_status_index` (`form_id`,`status`),
  KEY `form_submissions_submitted_at_index` (`submitted_at`),
  CONSTRAINT `form_submissions_form_id_foreign` FOREIGN KEY (`form_id`) REFERENCES `forms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `form_submissions` WRITE;
/*!40000 ALTER TABLE `form_submissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `form_submissions` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `form_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `form_versions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `form_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `version` int(10) unsigned NOT NULL,
  `source` varchar(30) NOT NULL,
  `summary` varchar(255) NOT NULL,
  `title` varchar(160) NOT NULL,
  `description` text DEFAULT NULL,
  `schema` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`schema`)),
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `form_versions_form_id_version_unique` (`form_id`,`version`),
  KEY `form_versions_user_id_foreign` (`user_id`),
  KEY `form_versions_form_id_created_at_index` (`form_id`,`created_at`),
  CONSTRAINT `form_versions_form_id_foreign` FOREIGN KEY (`form_id`) REFERENCES `forms` (`id`) ON DELETE CASCADE,
  CONSTRAINT `form_versions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `form_versions` WRITE;
/*!40000 ALTER TABLE `form_versions` DISABLE KEYS */;
INSERT INTO `form_versions` VALUES (1,1,1,1,'created','Initial form','Event registration','A seeded form that is ready to share.','{\"version\":1,\"settings\":{\"submitLabel\":\"Submit response\",\"successMessage\":\"Thank you. Your response has been recorded.\",\"showProgress\":true},\"sections\":[{\"id\":\"69b5c0af-4c00-44e0-9448-9e4b5007ae26\",\"title\":\"Event registration\",\"description\":\"Tell us how to prepare for your visit.\",\"fields\":[{\"id\":\"4ffb1490-bce5-412b-a935-5d638bbfbb4c\",\"type\":\"text\",\"label\":\"Full name\",\"key\":\"full_name_1vtl\",\"placeholder\":\"\",\"helpText\":\"\",\"defaultValue\":\"\",\"required\":true,\"options\":[],\"validation\":{\"min\":null,\"max\":null,\"minLength\":null,\"maxLength\":null,\"regex\":\"\",\"fileTypes\":[],\"maxFileSize\":5120},\"condition\":null},{\"id\":\"14a20685-2d0f-4ebe-a015-5a42277ea8c2\",\"type\":\"email\",\"label\":\"Email address\",\"key\":\"email_address_ydsj\",\"placeholder\":\"\",\"helpText\":\"\",\"defaultValue\":\"\",\"required\":true,\"options\":[],\"validation\":{\"min\":null,\"max\":null,\"minLength\":null,\"maxLength\":null,\"regex\":\"\",\"fileTypes\":[],\"maxFileSize\":5120},\"condition\":null}]}]}','2026-08-05 09:57:27');
/*!40000 ALTER TABLE `form_versions` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `forms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `forms` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `uuid` char(36) NOT NULL,
  `title` varchar(160) NOT NULL,
  `slug` varchar(180) NOT NULL,
  `description` text DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'draft',
  `schema` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`schema`)),
  `settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`settings`)),
  `version` int(10) unsigned NOT NULL DEFAULT 1,
  `published_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `forms_uuid_unique` (`uuid`),
  UNIQUE KEY `forms_slug_unique` (`slug`),
  KEY `forms_user_id_status_updated_at_index` (`user_id`,`status`,`updated_at`),
  KEY `forms_status_published_at_index` (`status`,`published_at`),
  CONSTRAINT `forms_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `forms` WRITE;
/*!40000 ALTER TABLE `forms` DISABLE KEYS */;
INSERT INTO `forms` VALUES (1,1,'eb4108d3-5a0e-4e38-9931-d06e94ffa4f6','Event registration','event-registration-demo','A seeded form that is ready to share.','published','{\"version\":1,\"settings\":{\"submitLabel\":\"Submit response\",\"successMessage\":\"Thank you. Your response has been recorded.\",\"showProgress\":true},\"sections\":[{\"id\":\"69b5c0af-4c00-44e0-9448-9e4b5007ae26\",\"title\":\"Event registration\",\"description\":\"Tell us how to prepare for your visit.\",\"fields\":[{\"id\":\"4ffb1490-bce5-412b-a935-5d638bbfbb4c\",\"type\":\"text\",\"label\":\"Full name\",\"key\":\"full_name_1vtl\",\"placeholder\":\"\",\"helpText\":\"\",\"defaultValue\":\"\",\"required\":true,\"options\":[],\"validation\":{\"min\":null,\"max\":null,\"minLength\":null,\"maxLength\":null,\"regex\":\"\",\"fileTypes\":[],\"maxFileSize\":5120},\"condition\":null},{\"id\":\"14a20685-2d0f-4ebe-a015-5a42277ea8c2\",\"type\":\"email\",\"label\":\"Email address\",\"key\":\"email_address_ydsj\",\"placeholder\":\"\",\"helpText\":\"\",\"defaultValue\":\"\",\"required\":true,\"options\":[],\"validation\":{\"min\":null,\"max\":null,\"minLength\":null,\"maxLength\":null,\"regex\":\"\",\"fileTypes\":[],\"maxFileSize\":5120},\"condition\":null}]}]}','[]',1,'2026-08-05 04:27:27','2026-08-05 04:27:27','2026-08-05 04:27:27',NULL);
/*!40000 ALTER TABLE `forms` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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

LOCK TABLES `job_batches` WRITE;
/*!40000 ALTER TABLE `job_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_batches` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0001_01_01_000000_create_users_table',1),(2,'0001_01_01_000001_create_cache_table',1),(3,'0001_01_01_000002_create_jobs_table',1),(4,'2024_01_01_000000_create_passkeys_table',1),(5,'2025_08_14_170933_add_two_factor_columns_to_users_table',1),(6,'2026_08_05_000100_create_form_builder_tables',1),(7,'2026_08_05_000200_create_form_automations_table',1),(8,'2026_08_05_000300_create_form_versions_and_submission_tokens',1);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `passkeys`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `passkeys` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `credential_id` varchar(255) NOT NULL,
  `credential` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`credential`)),
  `last_used_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `passkeys_credential_id_unique` (`credential_id`),
  KEY `passkeys_user_id_index` (`user_id`),
  CONSTRAINT `passkeys_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `passkeys` WRITE;
/*!40000 ALTER TABLE `passkeys` DISABLE KEYS */;
/*!40000 ALTER TABLE `passkeys` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `two_factor_secret` text DEFAULT NULL,
  `two_factor_recovery_codes` text DEFAULT NULL,
  `two_factor_confirmed_at` timestamp NULL DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Demo Builder','demo@formforge.test','2026-08-05 04:27:27','$2y$12$8EF9D4GvpM7Br3P//Sgry.ECR0OQkQf9YVzn6yh3j5DQNeta/gG.m',NULL,NULL,NULL,NULL,'2026-08-05 04:27:27','2026-08-05 04:27:27');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
