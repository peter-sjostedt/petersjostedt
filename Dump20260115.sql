CREATE DATABASE  IF NOT EXISTS `petersjo_db` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `petersjo_db`;
-- MySQL dump 10.13  Distrib 8.0.42, for Win64 (x86_64)
--
-- Host: localhost    Database: petersjo_db
-- ------------------------------------------------------
-- Server version	9.3.0

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `article_mappings`
--

DROP TABLE IF EXISTS `article_mappings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `article_mappings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `owner_org_id` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Mottagaren som ansvarar f├Âr mappningen',
  `sender_org_id` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Avs├ñndaren',
  `sender_article_id` int NOT NULL COMMENT 'Avs├ñndarens artikel',
  `my_article_id` int NOT NULL COMMENT 'Mottagarens artikel',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_mapping` (`owner_org_id`,`sender_article_id`),
  KEY `sender_article_id` (`sender_article_id`),
  KEY `my_article_id` (`my_article_id`),
  KEY `idx_owner` (`owner_org_id`),
  KEY `idx_sender` (`sender_org_id`),
  CONSTRAINT `article_mappings_ibfk_1` FOREIGN KEY (`owner_org_id`) REFERENCES `organizations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `article_mappings_ibfk_2` FOREIGN KEY (`sender_org_id`) REFERENCES `organizations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `article_mappings_ibfk_3` FOREIGN KEY (`sender_article_id`) REFERENCES `articles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `article_mappings_ibfk_4` FOREIGN KEY (`my_article_id`) REFERENCES `articles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `article_mappings`
--

LOCK TABLES `article_mappings` WRITE;
/*!40000 ALTER TABLE `article_mappings` DISABLE KEYS */;
/*!40000 ALTER TABLE `article_mappings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `articles`
--

DROP TABLE IF EXISTS `articles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `articles` (
  `id` int NOT NULL AUTO_INCREMENT,
  `organization_id` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sku` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Artikelnummer, unikt per organisation',
  `is_used` tinyint(1) DEFAULT '0',
  `name` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `data` json DEFAULT NULL COMMENT 'Artikeldata enligt organisationens article_schema',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_org_sku` (`organization_id`,`sku`),
  KEY `idx_sku` (`sku`),
  KEY `idx_is_active` (`is_active`),
  KEY `idx_is_used` (`is_used`),
  CONSTRAINT `articles_ibfk_1` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `articles`
--

LOCK TABLES `articles` WRITE;
/*!40000 ALTER TABLE `articles` DISABLE KEYS */;
INSERT INTO `articles` VALUES (4,'SE556112-3344','BLU-L-BOM-VIT',0,'Blus',NULL,'{\"farg\": \"Vit\", \"storlek\": \"L\", \"material\": \"Bomull\", \"artikelnamn\": \"Blus\"}',1,'2026-01-14 12:11:45','2026-01-14 13:19:17'),(5,'SE556112-3344','BYX-3XL-BOM-VIT',0,'Byxa',NULL,'{\"farg\": \"Vit\", \"storlek\": \"3XL\", \"material\": \"Bomull\", \"artikelnamn\": \"Byxa\"}',1,'2026-01-14 12:13:12','2026-01-14 13:19:17'),(6,'SE556112-3344','BLU-BOM-VIT-XS',0,'Blus',NULL,'{\"farg\": \"Vit\", \"storlek\": \"XS\", \"material\": \"Bomull\", \"artikelnamn\": \"Blus\"}',1,'2026-01-14 13:19:17','2026-01-14 13:19:17'),(7,'SE556112-3344','BLU-BOM-VIT-S',0,'Blus',NULL,'{\"farg\": \"Vit\", \"storlek\": \"S\", \"material\": \"Bomull\", \"artikelnamn\": \"Blus\"}',1,'2026-01-14 13:19:17','2026-01-14 13:19:17'),(8,'SE556112-3344','BLU-BOM-VIT-M',0,'Blus',NULL,'{\"farg\": \"Vit\", \"storlek\": \"M\", \"material\": \"Bomull\", \"artikelnamn\": \"Blus\"}',1,'2026-01-14 13:19:17','2026-01-14 13:19:17'),(9,'SE556112-3344','BLU-BOM-VIT-L',0,'Blus',NULL,'{\"farg\": \"Vit\", \"storlek\": \"L\", \"material\": \"Bomull\", \"artikelnamn\": \"Blus\"}',1,'2026-01-14 13:19:17','2026-01-14 13:19:17'),(10,'SE556112-3344','BLU-BOM-VIT-XL',0,'Blus',NULL,'{\"farg\": \"Vit\", \"storlek\": \"XL\", \"material\": \"Bomull\", \"artikelnamn\": \"Blus\"}',1,'2026-01-14 13:19:17','2026-01-14 13:19:17'),(11,'SE556112-3344','BLU-BOM-VIT-2XL',0,'Blus',NULL,'{\"farg\": \"Vit\", \"storlek\": \"2XL\", \"material\": \"Bomull\", \"artikelnamn\": \"Blus\"}',1,'2026-01-14 13:19:17','2026-01-14 13:19:17');
/*!40000 ALTER TABLE `articles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `event`
--

DROP TABLE IF EXISTS `event`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `event` (
  `id` int NOT NULL AUTO_INCREMENT,
  `organization_id` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `event_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `metadata` json DEFAULT NULL,
  `event_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_event_type` (`event_type`),
  KEY `idx_created` (`created_at`),
  KEY `idx_organization` (`organization_id`),
  CONSTRAINT `fk_event_organization` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `event`
--

LOCK TABLES `event` WRITE;
/*!40000 ALTER TABLE `event` DISABLE KEYS */;
INSERT INTO `event` VALUES (1,'SE556112-3344','shipment','{\"customer\": \"SE556889-0011\", \"parentId\": null, \"producer\": \"SE556112-3344\", \"createdBy\": {\"unitId\": null, \"userId\": 10}, \"shipmentId\": \"SH-2026-001\", \"salesOrderId\": \"\", \"purchaseOrderId\": \"\"}','2026-01-14 21:22:07','2026-01-14 21:22:07');
/*!40000 ALTER TABLE `event` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `event_types`
--

DROP TABLE IF EXISTS `event_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `event_types` (
  `id` int NOT NULL AUTO_INCREMENT,
  `code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'scanned, ownership_changed, location_changed, etc.',
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `is_system` tinyint(1) DEFAULT '0' COMMENT 'Systemh├ñndelser kan inte raderas',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `event_types`
--

LOCK TABLES `event_types` WRITE;
/*!40000 ALTER TABLE `event_types` DISABLE KEYS */;
INSERT INTO `event_types` VALUES (1,'scanned','Skannad','RFID-tagg skannad',1,'2026-01-13 14:43:07'),(2,'ownership_changed','Ägarbyte','Taggen bytte ägare',1,'2026-01-13 14:43:07'),(3,'location_changed','Platsändring','Taggen flyttades till annan enhet',1,'2026-01-13 14:43:07'),(4,'status_changed','Statusändring','Taggens status ändrades',1,'2026-01-13 14:43:07'),(5,'created','Skapad','Ny RFID-tagg registrerad',1,'2026-01-13 14:43:07');
/*!40000 ALTER TABLE `event_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `events`
--

DROP TABLE IF EXISTS `events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `events` (
  `id` int NOT NULL AUTO_INCREMENT,
  `event_type_id` int NOT NULL,
  `rfid_id` int NOT NULL,
  `unit_id` int NOT NULL COMMENT 'Enheten som utf├Ârde h├ñndelsen',
  `data` json DEFAULT NULL COMMENT 'Extra data: previous_owner, new_owner, etc.',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_event_type` (`event_type_id`),
  KEY `idx_rfid` (`rfid_id`),
  KEY `idx_unit` (`unit_id`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `events_ibfk_1` FOREIGN KEY (`event_type_id`) REFERENCES `event_types` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `events_ibfk_2` FOREIGN KEY (`rfid_id`) REFERENCES `rfids` (`id`) ON DELETE CASCADE,
  CONSTRAINT `events_ibfk_3` FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `events`
--

LOCK TABLES `events` WRITE;
/*!40000 ALTER TABLE `events` DISABLE KEYS */;
/*!40000 ALTER TABLE `events` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `files`
--

DROP TABLE IF EXISTS `files`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `files` (
  `id` int NOT NULL AUTO_INCREMENT,
  `original_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `stored_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mime_type` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_size` int NOT NULL,
  `file_path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `folder` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `uploaded_by` int DEFAULT NULL,
  `parent_id` int DEFAULT NULL,
  `size_variant` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_uploaded_by` (`uploaded_by`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_parent_id` (`parent_id`),
  KEY `idx_folder` (`folder`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `files`
--

LOCK TABLES `files` WRITE;
/*!40000 ALTER TABLE `files` DISABLE KEYS */;
INSERT INTO `files` VALUES (2,'Framtidsfullmakt-Peter.pdf','037b777a2e9f1ff0aed93aa9b350acc6.pdf','application/pdf',267051,'documents/037b777a2e9f1ff0aed93aa9b350acc6.pdf',NULL,NULL,NULL,NULL,'2026-01-12 06:06:12');
/*!40000 ALTER TABLE `files` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `logs`
--

DROP TABLE IF EXISTS `logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `logs` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned DEFAULT NULL,
  `action` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_created` (`created_at`),
  CONSTRAINT `logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=140 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `logs`
--

LOCK TABLES `logs` WRITE;
/*!40000 ALTER TABLE `logs` DISABLE KEYS */;
INSERT INTO `logs` VALUES (1,NULL,'SYSTEM_TEST: Automatiskt test','::1','2026-01-08 15:04:54'),(2,NULL,'LOGIN_FAILED: Email: admin@petersjostedt.se','::1','2026-01-08 20:47:11'),(3,1,'LOGIN: Admin login','::1','2026-01-08 20:48:00'),(4,1,'LOGIN: Admin login','::1','2026-01-09 12:42:32'),(5,1,'LOGIN: Admin login','::1','2026-01-10 12:48:29'),(6,1,'TEST_DEBUG: Detta är en debug-logg','::1','2026-01-11 07:06:33'),(7,1,'TEST_INFO: Detta är en info-logg','::1','2026-01-11 07:06:33'),(8,1,'TEST_WARNING: Detta är en varningslogg','::1','2026-01-11 07:06:33'),(9,1,'TEST_ERROR: Detta är en fellogg','::1','2026-01-11 07:06:33'),(10,1,'TEST_SECURITY: Detta är en säkerhetslogg','::1','2026-01-11 07:06:33'),(11,1,'BACKUP_FAILED: mysqldump misslyckades: ','::1','2026-01-11 07:16:32'),(12,1,'BACKUP_CREATED: Typ: daily, Fil: backup_daily_2026-01-11_082238.sql.gz, Storlek: 1.82 KB, Tid: 0.13s','::1','2026-01-11 07:22:38'),(13,NULL,'MIGRATION_EXECUTED: Migration kördes: 2026_01_11_090800_initial_schema.sql','0.0.0.0','2026-01-11 08:11:16'),(14,NULL,'MIGRATION_CREATED: Migration skapad: 2026_01_11_091127_add_example_column_to_users.sql','0.0.0.0','2026-01-11 08:11:27'),(15,NULL,'MIGRATION_CREATED: Migration skapad: 2026_01_12_064757_create_files_table.sql','0.0.0.0','2026-01-12 05:47:57'),(16,NULL,'MIGRATION_ERROR: Migration misslyckades: 2026_01_12_064757_create_files_table.sql - SQLSTATE[HY000]: General error: 3780 Referencing column \'uploaded_by\' and referenced column \'id\' in foreign key constraint \'files_ibfk_1\' are incompatible.','0.0.0.0','2026-01-12 05:53:46'),(17,NULL,'MIGRATION_EXECUTED: Migration kördes: 2026_01_12_064757_create_files_table.sql','0.0.0.0','2026-01-12 05:54:14'),(18,NULL,'MIGRATION_EXECUTED: Migration kördes: TEMPLATE.sql','0.0.0.0','2026-01-12 05:54:14'),(19,NULL,'IMAGE_UPLOADED: Bild uppladdad: Scan_20250521 (2).png → b55d30d11de65175031fd0cc0ffce5f3.png (2480x3507 → 150x150, 99.8% komprimering)','::1','2026-01-12 06:03:56'),(20,NULL,'FILE_SERVED: Fil serverad: Scan_20250521 (2).png (ID: 1)','::1','2026-01-12 06:03:56'),(21,NULL,'FILE_DELETED: Fil raderad: Scan_20250521 (2).png (ID: 1)','::1','2026-01-12 06:04:53'),(22,NULL,'FILE_UPLOADED: Fil uppladdad: Framtidsfullmakt-Peter.pdf (application/pdf, 260.79 KB)','::1','2026-01-12 06:06:12'),(23,NULL,'IMAGE_UPLOADED: Bild uppladdad: Adapter (1).png → a605c759723489885c2e1f1adad60a06.png (560x407 → 150x150, 86.1% komprimering)','::1','2026-01-12 06:11:34'),(24,NULL,'FILE_SERVED: Fil serverad: Adapter (1).png (ID: 3)','::1','2026-01-12 06:11:34'),(25,NULL,'MIGRATION_CREATED: Migration skapad: 2026_01_12_071739_add_image_variant_fields.sql','0.0.0.0','2026-01-12 06:17:39'),(26,NULL,'FILE_DELETED: Fil raderad: Adapter (1).png (ID: 3)','::1','2026-01-12 06:17:44'),(27,NULL,'MIGRATION_EXECUTED: Migration kördes: 2026_01_12_071739_add_image_variant_fields.sql','0.0.0.0','2026-01-12 06:17:54'),(28,NULL,'MIGRATION_EXECUTED: Migration kördes: 2026_01_12_131905_add_folder_to_files.sql','0.0.0.0','2026-01-12 12:19:17'),(29,1,'LOGIN: Admin login','::1','2026-01-12 13:35:37'),(30,NULL,'MIGRATION_ERROR: Migration misslyckades: 2026_01_13_111942_create_organizations_units.sql - Ingen up SQL hittades i 2026_01_13_111942_create_organizations_units.sql','::1','2026-01-13 10:20:47'),(31,1,'MIGRATIONS_FAILED: [{\"migration\":\"2026_01_13_111942_create_organizations_units.sql\",\"error\":\"Ingen up SQL hittades i 2026_01_13_111942_create_organizations_units.sql\"}]','::1','2026-01-13 10:20:47'),(32,NULL,'MIGRATION_EXECUTED: Migration kördes: 2026_01_13_111942_create_organizations_units.sql','::1','2026-01-13 10:22:25'),(33,1,'MIGRATIONS_RUN: 1 migrations kördes','::1','2026-01-13 10:22:25'),(34,1,'ORG_CREATE: Skapade organisation: SE10500101-1234 - Test','::1','2026-01-13 10:36:34'),(35,1,'ORG_UPDATE: Uppdaterade organisation: SE10500101-1234','::1','2026-01-13 10:39:58'),(36,1,'ORG_UPDATE: Uppdaterade organisation: SE10500101-1234','::1','2026-01-13 10:44:28'),(37,1,'ORG_UPDATE: Uppdaterade organisation: SE10500101-1234','::1','2026-01-13 12:51:31'),(38,1,'ORG_UPDATE: Uppdaterade organisation: SE10500101-1234','::1','2026-01-13 12:59:27'),(39,1,'ORG_UPDATE: Uppdaterade organisation: SE10500101-1234','::1','2026-01-13 13:01:09'),(40,1,'ORG_UPDATE: Uppdaterade organisation: SE10500101-1234','::1','2026-01-13 13:01:19'),(41,1,'ORG_UPDATE: Uppdaterade organisation: SE10500101-1234','::1','2026-01-13 13:06:55'),(42,1,'ORG_UPDATE: Uppdaterade organisation: SE10500101-1234','::1','2026-01-13 13:08:54'),(43,1,'ORG_UPDATE: Uppdaterade organisation: SYSTEM','::1','2026-01-13 13:45:05'),(44,1,'ORG_UPDATE: Uppdaterade organisation: SE10500101-1234','::1','2026-01-13 13:48:01'),(45,1,'ORG_UPDATE: Uppdaterade organisation: SE10500101-1234','::1','2026-01-13 13:48:21'),(46,1,'ORG_UPDATE: Uppdaterade organisation: SE10500101-1234','::1','2026-01-13 13:56:30'),(47,NULL,'MIGRATION_ERROR: Migration misslyckades: 2026_01_13_152000_create_articles_rfids.sql - Ingen up SQL hittades i 2026_01_13_152000_create_articles_rfids.sql','::1','2026-01-13 14:43:59'),(48,1,'MIGRATIONS_FAILED: [{\"migration\":\"2026_01_13_152000_create_articles_rfids.sql\",\"error\":\"Ingen up SQL hittades i 2026_01_13_152000_create_articles_rfids.sql\"}]','::1','2026-01-13 14:43:59'),(49,1,'LOGIN: Admin login','::1','2026-01-14 05:13:48'),(50,1,'ORG_UPDATE: Uppdaterade organisation: SE10500101-1234','::1','2026-01-14 05:23:59'),(51,1,'ORG_UPDATE: Uppdaterade organisation: SE10500101-1234','::1','2026-01-14 05:30:10'),(52,1,'ORG_UPDATE: Uppdaterade organisation: SE10500101-1234','::1','2026-01-14 05:30:25'),(53,1,'ORG_UPDATE: Uppdaterade organisation: SE10500101-1234','::1','2026-01-14 05:31:13'),(54,1,'SESSION_TERMINATED: Avslutade session ID: 4','::1','2026-01-14 05:35:42'),(55,1,'BACKUP_CREATED: Typ: daily, Fil: backup_daily_2026-01-14_063620.sql.gz, Storlek: 5.08 KB, Tid: 0.22s','::1','2026-01-14 05:36:20'),(56,1,'LOGOUT: Admin logout','::1','2026-01-14 10:57:36'),(57,NULL,'LOGIN_FAILED: Email: admin@berendsen.se','::1','2026-01-14 10:57:47'),(58,NULL,'LOGIN_FAILED: Email: admin@berendsen.se','::1','2026-01-14 10:58:21'),(59,NULL,'LOGIN_FAILED: Email: admin@textilia.se','::1','2026-01-14 10:58:50'),(60,10,'LOGIN: Partner login','::1','2026-01-14 10:59:22'),(61,1,'LOGIN: Admin login','::1','2026-01-14 11:09:27'),(62,1,'ORG_UPDATE: Uppdaterade organisation: SE556112-3344','::1','2026-01-14 11:31:43'),(63,10,'ARTICLE_CREATE: Skapade artikel: 0000 - Jacka','::1','2026-01-14 11:34:09'),(64,NULL,'MIGRATION_EXECUTED: Migration kördes: 2026_01_14_100000_add_org_types.sql','::1','2026-01-14 11:45:59'),(65,NULL,'MIGRATION_ERROR: Migration misslyckades: 2026_01_14_100000_simplify_articles.sql - Ingen up SQL hittades i 2026_01_14_100000_simplify_articles.sql','::1','2026-01-14 11:45:59'),(66,1,'MIGRATIONS_FAILED: [{\"migration\":\"2026_01_14_100000_simplify_articles.sql\",\"error\":\"Ingen up SQL hittades i 2026_01_14_100000_simplify_articles.sql\"}]','::1','2026-01-14 11:45:59'),(67,NULL,'MIGRATION_ERROR: Migration misslyckades: 2026_01_14_100000_simplify_articles.sql - Ingen up SQL hittades i 2026_01_14_100000_simplify_articles.sql','::1','2026-01-14 11:46:40'),(68,1,'MIGRATIONS_FAILED: [{\"migration\":\"2026_01_14_100000_simplify_articles.sql\",\"error\":\"Ingen up SQL hittades i 2026_01_14_100000_simplify_articles.sql\"}]','::1','2026-01-14 11:46:40'),(69,NULL,'MIGRATION_ERROR: Migration misslyckades: 2026_01_14_100000_simplify_articles.sql - Ingen up SQL hittades i 2026_01_14_100000_simplify_articles.sql','::1','2026-01-14 11:46:42'),(70,1,'MIGRATIONS_FAILED: [{\"migration\":\"2026_01_14_100000_simplify_articles.sql\",\"error\":\"Ingen up SQL hittades i 2026_01_14_100000_simplify_articles.sql\"}]','::1','2026-01-14 11:46:42'),(71,NULL,'MIGRATION_ERROR: Migration misslyckades: 2026_01_14_100000_simplify_articles.sql - Ingen up SQL hittades i 2026_01_14_100000_simplify_articles.sql','::1','2026-01-14 11:46:45'),(72,1,'MIGRATIONS_FAILED: [{\"migration\":\"2026_01_14_100000_simplify_articles.sql\",\"error\":\"Ingen up SQL hittades i 2026_01_14_100000_simplify_articles.sql\"}]','::1','2026-01-14 11:46:45'),(73,NULL,'MIGRATION_ERROR: Migration misslyckades: 2026_01_14_100000_simplify_articles.sql - Ingen up SQL hittades i 2026_01_14_100000_simplify_articles.sql','::1','2026-01-14 11:46:48'),(74,1,'MIGRATIONS_FAILED: [{\"migration\":\"2026_01_14_100000_simplify_articles.sql\",\"error\":\"Ingen up SQL hittades i 2026_01_14_100000_simplify_articles.sql\"}]','::1','2026-01-14 11:46:48'),(75,NULL,'MIGRATION_ERROR: Migration misslyckades: 2026_01_14_100000_simplify_articles.sql - Ingen up SQL hittades i 2026_01_14_100000_simplify_articles.sql','::1','2026-01-14 11:46:52'),(76,1,'MIGRATIONS_FAILED: [{\"migration\":\"2026_01_14_100000_simplify_articles.sql\",\"error\":\"Ingen up SQL hittades i 2026_01_14_100000_simplify_articles.sql\"}]','::1','2026-01-14 11:46:52'),(77,NULL,'MIGRATION_ERROR: Migration misslyckades: 2026_01_14_100000_simplify_articles.sql - Ingen up SQL hittades i 2026_01_14_100000_simplify_articles.sql','::1','2026-01-14 11:46:58'),(78,1,'MIGRATIONS_FAILED: [{\"migration\":\"2026_01_14_100000_simplify_articles.sql\",\"error\":\"Ingen up SQL hittades i 2026_01_14_100000_simplify_articles.sql\"}]','::1','2026-01-14 11:46:58'),(79,NULL,'MIGRATION_ERROR: Migration misslyckades: 2026_01_14_100000_simplify_articles.sql - Ingen up SQL hittades i 2026_01_14_100000_simplify_articles.sql','::1','2026-01-14 11:47:00'),(80,1,'MIGRATIONS_FAILED: [{\"migration\":\"2026_01_14_100000_simplify_articles.sql\",\"error\":\"Ingen up SQL hittades i 2026_01_14_100000_simplify_articles.sql\"}]','::1','2026-01-14 11:47:00'),(81,NULL,'MIGRATION_ROLLBACK_ERROR: Återställning misslyckades: 2026_01_14_100000_add_org_types.sql - SQLSTATE[01000]: Warning: 1265 Data truncated for column \'org_type\' at row 1','::1','2026-01-14 11:47:02'),(82,1,'MIGRATIONS_ROLLBACK_FAILED: [{\"migration\":\"2026_01_14_100000_add_org_types.sql\",\"error\":\"SQLSTATE[01000]: Warning: 1265 Data truncated for column \'org_type\' at row 1\"}]','::1','2026-01-14 11:47:02'),(83,NULL,'MIGRATION_ERROR: Migration misslyckades: 2026_01_14_100000_simplify_articles.sql - Ingen up SQL hittades i 2026_01_14_100000_simplify_articles.sql','::1','2026-01-14 11:47:04'),(84,1,'MIGRATIONS_FAILED: [{\"migration\":\"2026_01_14_100000_simplify_articles.sql\",\"error\":\"Ingen up SQL hittades i 2026_01_14_100000_simplify_articles.sql\"}]','::1','2026-01-14 11:47:04'),(85,1,'LOGIN: Admin login','::1','2026-01-14 11:48:24'),(86,NULL,'MIGRATION_ERROR: Migration misslyckades: 2026_01_14_100000_simplify_articles.sql - Ingen up SQL hittades i 2026_01_14_100000_simplify_articles.sql','::1','2026-01-14 11:48:29'),(87,1,'MIGRATIONS_FAILED: [{\"migration\":\"2026_01_14_100000_simplify_articles.sql\",\"error\":\"Ingen up SQL hittades i 2026_01_14_100000_simplify_articles.sql\"}]','::1','2026-01-14 11:48:29'),(88,NULL,'MIGRATION_ERROR: Migration misslyckades: 2026_01_14_100000_simplify_articles.sql - Ingen up SQL hittades i 2026_01_14_100000_simplify_articles.sql','::1','2026-01-14 11:49:06'),(89,1,'MIGRATIONS_FAILED: [{\"migration\":\"2026_01_14_100000_simplify_articles.sql\",\"error\":\"Ingen up SQL hittades i 2026_01_14_100000_simplify_articles.sql\"}]','::1','2026-01-14 11:49:06'),(90,NULL,'MIGRATION_ERROR: Migration misslyckades: 2026_01_14_100000_simplify_articles.sql - Ingen up SQL hittades i 2026_01_14_100000_simplify_articles.sql','::1','2026-01-14 11:49:09'),(91,1,'MIGRATIONS_FAILED: [{\"migration\":\"2026_01_14_100000_simplify_articles.sql\",\"error\":\"Ingen up SQL hittades i 2026_01_14_100000_simplify_articles.sql\"}]','::1','2026-01-14 11:49:09'),(92,NULL,'MIGRATION_ERROR: Migration misslyckades: 2026_01_14_100000_simplify_articles.sql - Ingen up SQL hittades i 2026_01_14_100000_simplify_articles.sql','::1','2026-01-14 11:49:12'),(93,1,'MIGRATIONS_FAILED: [{\"migration\":\"2026_01_14_100000_simplify_articles.sql\",\"error\":\"Ingen up SQL hittades i 2026_01_14_100000_simplify_articles.sql\"}]','::1','2026-01-14 11:49:12'),(94,NULL,'MIGRATION_ERROR: Migration misslyckades: 2026_01_14_100000_simplify_articles.sql - Ingen up SQL hittades i 2026_01_14_100000_simplify_articles.sql','::1','2026-01-14 11:49:13'),(95,1,'MIGRATIONS_FAILED: [{\"migration\":\"2026_01_14_100000_simplify_articles.sql\",\"error\":\"Ingen up SQL hittades i 2026_01_14_100000_simplify_articles.sql\"}]','::1','2026-01-14 11:49:13'),(96,NULL,'MIGRATION_ROLLBACK_ERROR: Återställning misslyckades: 2026_01_14_100000_add_org_types.sql - SQLSTATE[01000]: Warning: 1265 Data truncated for column \'org_type\' at row 1','::1','2026-01-14 11:49:23'),(97,1,'MIGRATIONS_ROLLBACK_FAILED: [{\"migration\":\"2026_01_14_100000_add_org_types.sql\",\"error\":\"SQLSTATE[01000]: Warning: 1265 Data truncated for column \'org_type\' at row 1\"}]','::1','2026-01-14 11:49:23'),(98,NULL,'MIGRATION_ERROR: Migration misslyckades: 2026_01_14_100000_simplify_articles.sql - Ingen up SQL hittades i 2026_01_14_100000_simplify_articles.sql','::1','2026-01-14 11:49:30'),(99,1,'MIGRATIONS_FAILED: [{\"migration\":\"2026_01_14_100000_simplify_articles.sql\",\"error\":\"Ingen up SQL hittades i 2026_01_14_100000_simplify_articles.sql\"}]','::1','2026-01-14 11:49:30'),(100,NULL,'MIGRATION_EXECUTED: Migration kördes: 2026_01_14_100000_simplify_articles.sql','::1','2026-01-14 11:50:40'),(101,1,'MIGRATIONS_RUN: 1 migrations kördes','::1','2026-01-14 11:50:40'),(102,10,'ARTICLE_UPDATE: Uppdaterade artikel: 0000','::1','2026-01-14 12:02:00'),(103,10,'ARTICLE_UPDATE: Uppdaterade artikel: 000088','::1','2026-01-14 12:02:09'),(104,10,'ARTICLE_CREATE: Skapade artikel: ART-0001','::1','2026-01-14 12:09:27'),(105,10,'ARTICLE_CREATE: Skapade artikel: ART-0002','::1','2026-01-14 12:10:43'),(106,10,'ARTICLE_DELETE: Raderade artikel: ART-0001','::1','2026-01-14 12:11:24'),(107,10,'ARTICLE_DELETE: Raderade artikel: ART-0002','::1','2026-01-14 12:11:26'),(108,10,'ARTICLE_CREATE: Skapade artikel: BLU-L-BOM-VIT','::1','2026-01-14 12:11:45'),(109,10,'ARTICLE_DELETE: Raderade artikel: 000088','::1','2026-01-14 12:12:25'),(110,10,'ARTICLE_UPDATE: Uppdaterade artikel: BLU-L-BOM-VIT','::1','2026-01-14 12:12:40'),(111,10,'ARTICLE_CREATE: Skapade artikel: BYX-3XL-BOM-VIT','::1','2026-01-14 12:13:12'),(112,10,'ARTICLE_UPDATE: Uppdaterade artikel: BLU-L-BOM-VIT','::1','2026-01-14 12:13:28'),(113,1,'ORG_UPDATE: Uppdaterade organisation: SE556112-3344','::1','2026-01-14 12:13:49'),(114,1,'ORG_UPDATE: Uppdaterade organisation: SE556112-3344','::1','2026-01-14 12:27:55'),(115,1,'ORG_UPDATE: Uppdaterade organisation: SE556112-3344','::1','2026-01-14 12:27:55'),(116,10,'ARTICLE_UPDATE: Uppdaterade artikel: BLU-L-BOM-VIT','::1','2026-01-14 12:28:52'),(117,10,'ARTICLE_UPDATE: Uppdaterade artikel: BYX-3XL-BOM-VIT','::1','2026-01-14 12:29:22'),(118,1,'ORG_UPDATE: Uppdaterade organisation: SE556112-3344','::1','2026-01-14 12:29:41'),(119,1,'ORG_UPDATE: Uppdaterade organisation: SE556011-2233','::1','2026-01-14 12:34:53'),(120,10,'ARTICLE_DELETE: Raderade artikel: BLU-BOM-VIT-XS-2','::1','2026-01-14 13:20:02'),(121,10,'ARTICLE_DELETE: Raderade artikel: BLU-BOM-VIT-S-2','::1','2026-01-14 13:20:04'),(122,10,'ARTICLE_DELETE: Raderade artikel: BLU-BOM-VIT-XL-2','::1','2026-01-14 13:20:07'),(123,10,'ARTICLE_DELETE: Raderade artikel: BLU-BOM-VIT-L-2','::1','2026-01-14 13:20:10'),(124,10,'ARTICLE_DELETE: Raderade artikel: BLU-BOM-VIT-2XL-2','::1','2026-01-14 13:20:13'),(125,10,'ARTICLE_DELETE: Raderade artikel: BLU-BOM-VIT-M-2','::1','2026-01-14 13:20:19'),(126,1,'ORG_UPDATE: Uppdaterade organisation: SE556112-3344','::1','2026-01-14 13:21:36'),(127,NULL,'MIGRATION_ERROR: Migration misslyckades: 2026_01_14_120000_create_event_rfid_event.sql - Ingen up SQL hittades i 2026_01_14_120000_create_event_rfid_event.sql','::1','2026-01-14 21:09:54'),(128,1,'MIGRATIONS_FAILED: [{\"migration\":\"2026_01_14_120000_create_event_rfid_event.sql\",\"error\":\"Ingen up SQL hittades i 2026_01_14_120000_create_event_rfid_event.sql\"}]','::1','2026-01-14 21:09:54'),(129,NULL,'MIGRATION_EXECUTED: Migration kördes: 2026_01_14_120000_create_event_rfid_event.sql','::1','2026-01-14 21:11:02'),(130,1,'MIGRATIONS_RUN: 1 migrations kördes','::1','2026-01-14 21:11:02'),(131,NULL,'MIGRATION_EXECUTED: Migration kördes: 2026_01_14_130000_create_organization_relations.sql','::1','2026-01-14 21:20:16'),(132,1,'MIGRATIONS_RUN: 1 migrations kördes','::1','2026-01-14 21:20:16'),(133,10,'RELATION_ADD: Lade till relation: SE556889-0011 (customer)','::1','2026-01-14 21:21:17'),(134,10,'RELATION_ADD: Lade till relation: SE556223-1199 (customer)','::1','2026-01-14 21:21:34'),(135,10,'SHIPMENT_CREATE: Skapade shipment: SH-2026-001','::1','2026-01-14 21:22:07'),(136,NULL,'MIGRATION_ERROR: Migration misslyckades: 2026_01_15_140000_alter_event_add_organization_and_event_at.sql - SQLSTATE[23000]: Integrity constraint violation: 1452 Cannot add or update a child row: a foreign key constraint fails (`petersjo_db`.`#sql-179c_...','::1','2026-01-15 06:45:16'),(137,1,'MIGRATIONS_FAILED: [{\"migration\":\"2026_01_15_140000_alter_event_add_organization_and_event_at.sql\",\"error\":\"SQLSTATE[23000]: Integrity constraint violation: 1452 Cannot add or update a child row: a foreign key constraint fails (`petersjo_db`.`#sql-179c...','::1','2026-01-15 06:45:16'),(138,NULL,'MIGRATION_EXECUTED: Migration kördes: 2026_01_15_140000_alter_event_add_organization_and_event_at.sql','::1','2026-01-15 06:48:05'),(139,1,'MIGRATIONS_RUN: 1 migrations kördes','::1','2026-01-15 06:48:05');
/*!40000 ALTER TABLE `logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  `executed_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `migration` (`migration`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'2026_01_11_090800_initial_schema.sql',1,'2026-01-11 08:11:16'),(2,'2026_01_12_064757_create_files_table.sql',2,'2026-01-12 05:54:14'),(3,'TEMPLATE.sql',2,'2026-01-12 05:54:14'),(4,'2026_01_12_071739_add_image_variant_fields.sql',3,'2026-01-12 06:17:54'),(5,'2026_01_12_131905_add_folder_to_files.sql',4,'2026-01-12 12:19:17'),(6,'2026_01_13_111942_create_organizations_units.sql',5,'2026-01-13 10:22:25'),(7,'2026_01_13_152000_create_articles_rfids.sql',6,'2026-01-13 14:45:10'),(8,'2026_01_13_152100_create_events.sql',6,'2026-01-13 14:45:10'),(9,'2026_01_13_160000_add_organization_to_users.sql',7,'2026-01-13 14:58:34'),(10,'2026_01_14_100000_add_org_types.sql',8,'2026-01-14 11:45:59'),(11,'2026_01_14_100000_simplify_articles.sql',9,'2026-01-14 11:50:40'),(12,'2026_01_14_120000_create_event_rfid_event.sql',10,'2026-01-14 21:11:02'),(13,'2026_01_14_130000_create_organization_relations.sql',11,'2026-01-14 21:20:16'),(14,'2026_01_15_140000_alter_event_add_organization_and_event_at.sql',12,'2026-01-15 06:48:05');
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `organization_relations`
--

DROP TABLE IF EXISTS `organization_relations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `organization_relations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `organization_id` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Organisationen som äger relationen',
  `partner_org_id` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Partnerorganisationen',
  `relation_type` enum('customer','supplier') COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Typ av relation',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_relation` (`organization_id`,`partner_org_id`,`relation_type`),
  KEY `idx_organization` (`organization_id`),
  KEY `idx_partner` (`partner_org_id`),
  KEY `idx_type` (`relation_type`),
  CONSTRAINT `organization_relations_ibfk_1` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `organization_relations_ibfk_2` FOREIGN KEY (`partner_org_id`) REFERENCES `organizations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `organization_relations`
--

LOCK TABLES `organization_relations` WRITE;
/*!40000 ALTER TABLE `organization_relations` DISABLE KEYS */;
INSERT INTO `organization_relations` VALUES (1,'SE556112-3344','SE556889-0011','customer',1,'2026-01-14 21:21:17','2026-01-14 21:21:17'),(2,'SE556112-3344','SE556223-1199','customer',1,'2026-01-14 21:21:34','2026-01-14 21:21:34');
/*!40000 ALTER TABLE `organization_relations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `organizations`
--

DROP TABLE IF EXISTS `organizations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `organizations` (
  `id` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Organisationsnummer med landskod, t.ex. SE556123-4567',
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `org_type` enum('system','customer','supplier','laundry') COLLATE utf8mb4_unicode_ci DEFAULT 'customer' COMMENT 'system=Hospitex, customer=sjukhus/vård, supplier=textilproducent, laundry=tvätteri',
  `article_schema` json DEFAULT NULL COMMENT 'Organisationens artikelattribut-struktur',
  `address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `postal_code` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country` varchar(2) COLLATE utf8mb4_unicode_ci DEFAULT 'SE',
  `phone` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_org_type` (`org_type`),
  KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `organizations`
--

LOCK TABLES `organizations` WRITE;
/*!40000 ALTER TABLE `organizations` DISABLE KEYS */;
INSERT INTO `organizations` VALUES ('DK12345678','Nordic Healthcare Textiles','supplier',NULL,'Vesterbrogade 100','1620','København','DK','+45 33 12 34 56','sales@nht.dk',1,'2026-01-14 07:02:37','2026-01-14 07:02:37'),('SE556011-2233','Sahlgrenska Universitetssjukhuset','customer','[{\"label\": \"Artikelnamn\", \"sort_order\": \"0\"}, {\"label\": \"Avdelning\", \"sort_order\": \"1\"}]','Per Dubbsgatan 15','413 45','Göteborg','SE','031-342 10 00','info@sahlgrenska.se',1,'2026-01-14 06:52:59','2026-01-14 12:34:53'),('SE556112-3344','Textilia AB','supplier','[{\"label\": \"Artikelnamn\", \"sort_order\": \"0\"}, {\"label\": \"Storlek\", \"sort_order\": \"1\"}, {\"label\": \"Material\", \"sort_order\": \"2\"}, {\"label\": \"Färg\", \"sort_order\": \"3\"}]','Industrigatan 45','602 38','Norrköping','SE','011-123 45 67','info@textilia.se',1,'2026-01-14 07:02:37','2026-01-14 13:21:36'),('SE556223-1199','CleanCare Medical','laundry',NULL,'Sterilgatan 15','212 35','Malmö','SE','040-680 50 00','service@cleancare.se',1,'2026-01-14 07:02:37','2026-01-14 07:02:37'),('SE556223-4455','Norrlands Universitetssjukhus','customer',NULL,'Norrlandsvägen 10','901 85','Umeå','SE','090-785 00 00','info@nus.se',0,'2026-01-14 06:52:59','2026-01-14 06:52:59'),('SE556334-5566','Sjukvårdstextil Sverige','supplier',NULL,'Fabriksvägen 8','721 31','Västerås','SE','021-456 78 90','kontakt@sjukvardstextil.se',1,'2026-01-14 07:02:37','2026-01-14 07:02:37'),('SE556445-1122','Berendsen Textil Service','laundry',NULL,'Tvättvägen 5','169 70','Solna','SE','08-555 123 00','info@berendsen.se',1,'2026-01-14 07:02:37','2026-01-14 07:02:37'),('SE556445-6677','Skånes Universitetssjukhus','customer',NULL,'Jan Waldenströms gata 35','205 02','Malmö','SE','040-33 10 00','info@sus.se',1,'2026-01-14 06:52:59','2026-01-14 06:52:59'),('SE556677-8899','Karolinska Universitetssjukhuset','customer','[{\"type\": \"text\", \"label\": \"Artikelnamn\", \"required\": true}, {\"type\": \"select\", \"label\": \"Storlek\", \"options\": [\"XS\", \"S\", \"M\", \"L\", \"XL\"]}, {\"type\": \"text\", \"label\": \"Material\"}]','Eugeniavägen 3','171 76','Solna','SE','08-517 700 00','info@karolinska.se',1,'2026-01-14 06:52:59','2026-01-14 06:52:59'),('SE556778-9900','Tvättman AB','laundry',NULL,'Renhållningsvägen 22','421 30','Västra Frölunda','SE','031-712 00 00','order@tvattman.se',1,'2026-01-14 07:02:37','2026-01-14 07:02:37'),('SE556889-0011','Akademiska Sjukhuset','customer',NULL,'Sjukhusvägen 1','752 37','Uppsala','SE','018-611 00 00','info@akademiska.se',1,'2026-01-14 06:52:59','2026-01-14 06:52:59'),('SE556998-7766','MediTex Scandinavia','supplier',NULL,'Väversgatan 12','411 05','Göteborg','SE','031-987 65 43','order@meditex.se',1,'2026-01-14 07:02:37','2026-01-14 07:02:37'),('SYSTEM','Hospitex System','system',NULL,NULL,NULL,'1234567890123456789012345','SE',NULL,NULL,1,'2026-01-13 10:22:25','2026-01-13 13:45:05');
/*!40000 ALTER TABLE `organizations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rfid_event`
--

DROP TABLE IF EXISTS `rfid_event`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `rfid_event` (
  `id` int NOT NULL AUTO_INCREMENT,
  `event_id` int NOT NULL,
  `rfid` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_rfid` (`rfid`),
  KEY `idx_event` (`event_id`),
  CONSTRAINT `rfid_event_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `event` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rfid_event`
--

LOCK TABLES `rfid_event` WRITE;
/*!40000 ALTER TABLE `rfid_event` DISABLE KEYS */;
/*!40000 ALTER TABLE `rfid_event` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rfids`
--

DROP TABLE IF EXISTS `rfids`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `rfids` (
  `id` int NOT NULL AUTO_INCREMENT,
  `epc` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'RFID-taggens unika ID',
  `article_id` int NOT NULL COMMENT '├ägarens artikel',
  `owner_org_id` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Nuvarande ├ñgare',
  `location_unit_id` int DEFAULT NULL COMMENT 'Var taggen befinner sig just nu',
  `status` enum('active','inactive','lost','scrapped') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `epc` (`epc`),
  KEY `location_unit_id` (`location_unit_id`),
  KEY `idx_epc` (`epc`),
  KEY `idx_article` (`article_id`),
  KEY `idx_owner` (`owner_org_id`),
  KEY `idx_status` (`status`),
  CONSTRAINT `rfids_ibfk_1` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `rfids_ibfk_2` FOREIGN KEY (`owner_org_id`) REFERENCES `organizations` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `rfids_ibfk_3` FOREIGN KEY (`location_unit_id`) REFERENCES `units` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rfids`
--

LOCK TABLES `rfids` WRITE;
/*!40000 ALTER TABLE `rfids` DISABLE KEYS */;
/*!40000 ALTER TABLE `rfids` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expires_at` timestamp NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`),
  KEY `user_id` (`user_id`),
  KEY `idx_token` (`token`),
  KEY `idx_expires` (`expires_at`),
  CONSTRAINT `sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sessions`
--

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
INSERT INTO `sessions` VALUES (1,1,'81889d6110e44c65cff65fdec2427135975e58793c112863eca402e57b03bfde','::1','2026-01-09 20:52:52'),(6,10,'9d0194a6612be51fd0cb3825dfdaa67454871f23b2f3530acb463c8ae2a0b494','::1','2026-01-16 06:48:14'),(8,1,'70e78e9205b76a84ee5edea304de967d0962d9f9c99aeb6e9f1ba1a802531fdd','::1','2026-01-16 06:48:05');
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `settings` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `setting_value` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`),
  KEY `idx_key` (`setting_key`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `settings`
--

LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `units`
--

DROP TABLE IF EXISTS `units`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `units` (
  `id` int NOT NULL AUTO_INCREMENT,
  `organization_id` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `api_key` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'För systemintegration och skannrar',
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Hashat med password_hash()',
  `is_active` tinyint(1) DEFAULT '1',
  `last_login_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `api_key` (`api_key`),
  KEY `idx_organization` (`organization_id`),
  KEY `idx_is_active` (`is_active`),
  CONSTRAINT `units_ibfk_1` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `units`
--

LOCK TABLES `units` WRITE;
/*!40000 ALTER TABLE `units` DISABLE KEYS */;
INSERT INTO `units` VALUES (3,'SE556677-8899','Sterilcentralen','api_karolinska_steril_001','$argon2id$v=19$m=65536,t=4,p=3$Tk43WGtvLmJ2VGV0QXNhSg$qBCU7Hk/NRg164pF1uxRcOKDKVe+cipABZTvahyK+0A',1,NULL,'2026-01-14 06:52:59','2026-01-14 06:52:59'),(4,'SE556677-8899','Kirurgi A','api_karolinska_kirurgia_002','$argon2id$v=19$m=65536,t=4,p=3$Tk43WGtvLmJ2VGV0QXNhSg$qBCU7Hk/NRg164pF1uxRcOKDKVe+cipABZTvahyK+0A',1,NULL,'2026-01-14 06:52:59','2026-01-14 06:52:59'),(5,'SE556677-8899','Kirurgi B','api_karolinska_kirurgib_003','$argon2id$v=19$m=65536,t=4,p=3$Tk43WGtvLmJ2VGV0QXNhSg$qBCU7Hk/NRg164pF1uxRcOKDKVe+cipABZTvahyK+0A',1,NULL,'2026-01-14 06:52:59','2026-01-14 06:52:59'),(6,'SE556677-8899','Akutmottagningen','api_karolinska_akut_004','$argon2id$v=19$m=65536,t=4,p=3$Tk43WGtvLmJ2VGV0QXNhSg$qBCU7Hk/NRg164pF1uxRcOKDKVe+cipABZTvahyK+0A',1,NULL,'2026-01-14 06:52:59','2026-01-14 06:52:59'),(7,'SE556677-8899','IVA','api_karolinska_iva_005','$argon2id$v=19$m=65536,t=4,p=3$Tk43WGtvLmJ2VGV0QXNhSg$qBCU7Hk/NRg164pF1uxRcOKDKVe+cipABZTvahyK+0A',1,NULL,'2026-01-14 06:52:59','2026-01-14 06:52:59'),(8,'SE556011-2233','Sterilcentralen SU','api_sahlgrenska_steril_001','$argon2id$v=19$m=65536,t=4,p=3$Tk43WGtvLmJ2VGV0QXNhSg$qBCU7Hk/NRg164pF1uxRcOKDKVe+cipABZTvahyK+0A',1,NULL,'2026-01-14 06:52:59','2026-01-14 06:52:59'),(9,'SE556011-2233','Ortopedi','api_sahlgrenska_ortopedi_002','$argon2id$v=19$m=65536,t=4,p=3$Tk43WGtvLmJ2VGV0QXNhSg$qBCU7Hk/NRg164pF1uxRcOKDKVe+cipABZTvahyK+0A',1,NULL,'2026-01-14 06:52:59','2026-01-14 06:52:59'),(10,'SE556011-2233','Kardiologi','api_sahlgrenska_kardio_003','$argon2id$v=19$m=65536,t=4,p=3$Tk43WGtvLmJ2VGV0QXNhSg$qBCU7Hk/NRg164pF1uxRcOKDKVe+cipABZTvahyK+0A',1,NULL,'2026-01-14 06:52:59','2026-01-14 06:52:59'),(11,'SE556445-6677','Sterilcentral Malmö','api_sus_steril_001','$argon2id$v=19$m=65536,t=4,p=3$Tk43WGtvLmJ2VGV0QXNhSg$qBCU7Hk/NRg164pF1uxRcOKDKVe+cipABZTvahyK+0A',1,NULL,'2026-01-14 06:52:59','2026-01-14 06:52:59'),(12,'SE556445-6677','Operation Lund','api_sus_op_lund_002','$argon2id$v=19$m=65536,t=4,p=3$Tk43WGtvLmJ2VGV0QXNhSg$qBCU7Hk/NRg164pF1uxRcOKDKVe+cipABZTvahyK+0A',1,NULL,'2026-01-14 06:52:59','2026-01-14 06:52:59'),(13,'SE556889-0011','Sterilcentralen Uppsala','api_akademiska_steril_001','$argon2id$v=19$m=65536,t=4,p=3$Tk43WGtvLmJ2VGV0QXNhSg$qBCU7Hk/NRg164pF1uxRcOKDKVe+cipABZTvahyK+0A',1,NULL,'2026-01-14 06:52:59','2026-01-14 06:52:59'),(14,'SE556112-3344','Produktion Norrköping','api_textilia_prod_001','$argon2id$v=19$m=65536,t=4,p=3$cS9CNzRNLndwWi9oZTdSMQ$iyhHBVajpQGhottD91U6OvtK+it0kkDD81SN31A8sco',1,NULL,'2026-01-14 07:02:37','2026-01-14 07:02:37'),(15,'SE556112-3344','Lager & Distribution','api_textilia_lager_002','$argon2id$v=19$m=65536,t=4,p=3$cS9CNzRNLndwWi9oZTdSMQ$iyhHBVajpQGhottD91U6OvtK+it0kkDD81SN31A8sco',1,NULL,'2026-01-14 07:02:37','2026-01-14 07:02:37'),(16,'SE556998-7766','Tillverkning','api_meditex_tillv_001','$argon2id$v=19$m=65536,t=4,p=3$cS9CNzRNLndwWi9oZTdSMQ$iyhHBVajpQGhottD91U6OvtK+it0kkDD81SN31A8sco',1,NULL,'2026-01-14 07:02:37','2026-01-14 07:02:37'),(17,'SE556445-1122','Tvätteri Solna','api_berendsen_solna_001','$argon2id$v=19$m=65536,t=4,p=3$cS9CNzRNLndwWi9oZTdSMQ$iyhHBVajpQGhottD91U6OvtK+it0kkDD81SN31A8sco',1,NULL,'2026-01-14 07:02:37','2026-01-14 07:02:37'),(18,'SE556445-1122','Tvätteri Göteborg','api_berendsen_gbg_002','$argon2id$v=19$m=65536,t=4,p=3$cS9CNzRNLndwWi9oZTdSMQ$iyhHBVajpQGhottD91U6OvtK+it0kkDD81SN31A8sco',1,NULL,'2026-01-14 07:02:37','2026-01-14 07:02:37'),(19,'SE556778-9900','Huvudtvätteri','api_tvattman_main_001','$argon2id$v=19$m=65536,t=4,p=3$cS9CNzRNLndwWi9oZTdSMQ$iyhHBVajpQGhottD91U6OvtK+it0kkDD81SN31A8sco',1,NULL,'2026-01-14 07:02:37','2026-01-14 07:02:37'),(20,'SE556223-1199','Steriltvätteri Malmö','api_cleancare_malmo_001','$argon2id$v=19$m=65536,t=4,p=3$cS9CNzRNLndwWi9oZTdSMQ$iyhHBVajpQGhottD91U6OvtK+it0kkDD81SN31A8sco',1,NULL,'2026-01-14 07:02:37','2026-01-14 07:02:37');
/*!40000 ALTER TABLE `units` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('admin','org_admin','user') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'user' COMMENT 'admin=system admin, org_admin=organization admin, user=basic user',
  `organization_id` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_email` (`email`),
  KEY `idx_organization_id` (`organization_id`),
  CONSTRAINT `fk_users_organization` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'admin@petersjostedt.se','$argon2id$v=19$m=65536,t=4,p=1$ZHp4c253bEpZTzhTRzAvbA$qVt4qkeCa1NB/dJsHNNeLuYMtQFmkdCkKYqez4SpzuM','Admin','admin',NULL,'2026-01-08 20:42:54','2026-01-14 06:49:22'),(2,'admin@karolinska.se','$argon2id$v=19$m=65536,t=4,p=3$Tk43WGtvLmJ2VGV0QXNhSg$qBCU7Hk/NRg164pF1uxRcOKDKVe+cipABZTvahyK+0A','Anna Karlsson','org_admin','SE556677-8899','2026-01-14 06:52:59','2026-01-14 06:52:59'),(3,'chef@sahlgrenska.se','$argon2id$v=19$m=65536,t=4,p=3$Tk43WGtvLmJ2VGV0QXNhSg$qBCU7Hk/NRg164pF1uxRcOKDKVe+cipABZTvahyK+0A','Erik Lindberg','org_admin','SE556011-2233','2026-01-14 06:52:59','2026-01-14 06:52:59'),(4,'admin@sus.se','$argon2id$v=19$m=65536,t=4,p=3$Tk43WGtvLmJ2VGV0QXNhSg$qBCU7Hk/NRg164pF1uxRcOKDKVe+cipABZTvahyK+0A','Maria Svensson','org_admin','SE556445-6677','2026-01-14 06:52:59','2026-01-14 06:52:59'),(5,'lars.berg@karolinska.se','$argon2id$v=19$m=65536,t=4,p=3$Tk43WGtvLmJ2VGV0QXNhSg$qBCU7Hk/NRg164pF1uxRcOKDKVe+cipABZTvahyK+0A','Lars Berg','user','SE556677-8899','2026-01-14 06:52:59','2026-01-14 06:52:59'),(6,'eva.holm@karolinska.se','$argon2id$v=19$m=65536,t=4,p=3$Tk43WGtvLmJ2VGV0QXNhSg$qBCU7Hk/NRg164pF1uxRcOKDKVe+cipABZTvahyK+0A','Eva Holm','user','SE556677-8899','2026-01-14 06:52:59','2026-01-14 06:52:59'),(7,'johan.nyman@sahlgrenska.se','$argon2id$v=19$m=65536,t=4,p=3$Tk43WGtvLmJ2VGV0QXNhSg$qBCU7Hk/NRg164pF1uxRcOKDKVe+cipABZTvahyK+0A','Johan Nyman','user','SE556011-2233','2026-01-14 06:52:59','2026-01-14 06:52:59'),(8,'karin.lund@sus.se','$argon2id$v=19$m=65536,t=4,p=3$Tk43WGtvLmJ2VGV0QXNhSg$qBCU7Hk/NRg164pF1uxRcOKDKVe+cipABZTvahyK+0A','Karin Lund','user','SE556445-6677','2026-01-14 06:52:59','2026-01-14 06:52:59'),(9,'per.strom@akademiska.se','$argon2id$v=19$m=65536,t=4,p=3$Tk43WGtvLmJ2VGV0QXNhSg$qBCU7Hk/NRg164pF1uxRcOKDKVe+cipABZTvahyK+0A','Per Ström','user','SE556889-0011','2026-01-14 06:52:59','2026-01-14 06:52:59'),(10,'admin@textilia.se','$argon2id$v=19$m=65536,t=4,p=3$ZGV1bi9xSWFhbG1DNFRwZg$VqivxmaDSdUglSSXjqTsZ9GXVfBFHVCPaMrWt0MYdOs','Anders Textil','org_admin','SE556112-3344','2026-01-14 07:02:37','2026-01-14 10:59:22'),(11,'chef@meditex.se','$argon2id$v=19$m=65536,t=4,p=3$cS9CNzRNLndwWi9oZTdSMQ$iyhHBVajpQGhottD91U6OvtK+it0kkDD81SN31A8sco','Maria Lindqvist','org_admin','SE556998-7766','2026-01-14 07:02:37','2026-01-14 07:02:37'),(12,'admin@berendsen.se','$argon2id$v=19$m=65536,t=4,p=3$UXpsay9LTjFZVjUyYkJkSw$5fVgqEQnruHreRXKK/99VSAZmSRVQxCi+fZSsm9Lc+M','Björn Cleansson','org_admin','SE556445-1122','2026-01-14 07:02:37','2026-01-14 10:58:21'),(13,'chef@tvattman.se','$argon2id$v=19$m=65536,t=4,p=3$cS9CNzRNLndwWi9oZTdSMQ$iyhHBVajpQGhottD91U6OvtK+it0kkDD81SN31A8sco','Kerstin Tvätt','org_admin','SE556778-9900','2026-01-14 07:02:37','2026-01-14 07:02:37'),(14,'lager@textilia.se','$argon2id$v=19$m=65536,t=4,p=3$cS9CNzRNLndwWi9oZTdSMQ$iyhHBVajpQGhottD91U6OvtK+it0kkDD81SN31A8sco','Erik Lagerström','user','SE556112-3344','2026-01-14 07:02:37','2026-01-14 07:02:37'),(15,'produktion@berendsen.se','$argon2id$v=19$m=65536,t=4,p=3$cS9CNzRNLndwWi9oZTdSMQ$iyhHBVajpQGhottD91U6OvtK+it0kkDD81SN31A8sco','Lisa Produktsson','user','SE556445-1122','2026-01-14 07:02:37','2026-01-14 07:02:37');
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

-- Dump completed on 2026-01-15  8:09:47
