-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: 127.0.0.1    Database: leave_system
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
-- Table structure for table `approval_steps`
--

DROP TABLE IF EXISTS `approval_steps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `approval_steps` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `leave_request_id` int(10) unsigned NOT NULL,
  `step_order` tinyint(3) unsigned NOT NULL,
  `role` enum('supervisor','hr','director') NOT NULL,
  `approver_user_id` int(10) unsigned DEFAULT NULL,
  `action` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `comments` text DEFAULT NULL,
  `acted_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_approval_step_request_role` (`leave_request_id`,`role`),
  KEY `fk_approval_steps_user` (`approver_user_id`),
  CONSTRAINT `fk_approval_steps_request` FOREIGN KEY (`leave_request_id`) REFERENCES `leave_requests` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_approval_steps_user` FOREIGN KEY (`approver_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `approval_steps`
--

LOCK TABLES `approval_steps` WRITE;
/*!40000 ALTER TABLE `approval_steps` DISABLE KEYS */;
INSERT INTO `approval_steps` VALUES (4,2,1,'supervisor',5,'approved','','2026-06-03 12:37:55','2026-06-03 09:35:22'),(5,2,2,'hr',7,'approved','','2026-06-03 12:39:24','2026-06-03 09:35:22'),(6,2,3,'director',6,'approved','','2026-06-03 12:40:16','2026-06-03 09:35:22'),(7,3,1,'supervisor',5,'approved','','2026-06-03 13:00:56','2026-06-03 09:57:23'),(8,3,2,'hr',7,'approved','','2026-06-04 10:53:34','2026-06-03 09:57:23'),(9,3,3,'director',6,'approved','','2026-06-04 11:01:22','2026-06-03 09:57:23'),(13,5,1,'supervisor',5,'approved','','2026-06-04 10:51:30','2026-06-04 07:50:33'),(14,5,2,'hr',7,'approved','','2026-06-04 10:53:36','2026-06-04 07:50:33'),(15,5,3,'director',6,'approved','','2026-06-04 11:01:14','2026-06-04 07:50:33');
/*!40000 ALTER TABLE `approval_steps` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `audit_logs`
--

DROP TABLE IF EXISTS `audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `audit_logs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned DEFAULT NULL,
  `action` varchar(120) NOT NULL,
  `entity_type` varchar(80) DEFAULT NULL,
  `entity_id` int(10) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_audit_logs_user` (`user_id`),
  KEY `idx_audit_logs_entity` (`entity_type`,`entity_id`),
  CONSTRAINT `fk_audit_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=148 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_logs`
--

LOCK TABLES `audit_logs` WRITE;
/*!40000 ALTER TABLE `audit_logs` DISABLE KEYS */;
INSERT INTO `audit_logs` VALUES (15,NULL,'login','users',1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-03 07:54:10'),(16,NULL,'logout','users',1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-03 07:58:58'),(17,NULL,'register','users',3,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-03 08:00:40'),(18,NULL,'login','users',1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-03 08:02:09'),(19,NULL,'logout','users',1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-03 08:02:14'),(20,NULL,'login','users',3,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-03 08:04:10'),(21,NULL,'logout','users',3,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-03 08:04:32'),(22,NULL,'login','users',1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-03 08:09:28'),(23,NULL,'logout','users',1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-03 08:31:03'),(24,NULL,'login','users',1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-03 08:33:07'),(25,NULL,'logout','users',1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-03 08:33:56'),(26,NULL,'login','users',1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-03 08:40:52'),(27,NULL,'logout','users',1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-03 08:41:16'),(28,NULL,'login','users',1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-03 08:52:08'),(29,NULL,'logout','users',1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-03 08:55:22'),(30,4,'register','users',4,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-03 09:04:44'),(31,4,'login','users',4,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-03 09:05:09'),(32,4,'logout','users',4,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-03 09:06:51'),(33,5,'register','users',5,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-03 09:09:26'),(34,6,'register','users',6,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-03 09:11:16'),(35,7,'register','users',7,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-03 09:14:19'),(36,4,'login','users',4,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-03 09:27:33'),(37,4,'update_user_access','users',7,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-03 09:28:19'),(38,4,'update_user_access','users',6,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-03 09:28:23'),(39,4,'update_user_access','users',5,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-03 09:28:26'),(40,4,'update_user_access','users',4,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-03 09:28:29'),(41,4,'logout','users',4,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-03 09:29:00'),(42,5,'login','users',5,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-03 09:29:50'),(43,5,'logout','users',5,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-03 09:30:17'),(44,8,'register','users',8,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-03 09:31:51'),(45,8,'login','users',8,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-03 09:32:21'),(46,8,'create_leave_request','leave_requests',2,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-03 09:35:22'),(47,8,'logout','users',8,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-03 09:35:32'),(48,7,'login','users',7,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-03 09:36:10'),(49,7,'logout','users',7,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-03 09:36:27'),(50,6,'login','users',6,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-03 09:36:56'),(51,6,'logout','users',6,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-03 09:37:06'),(52,5,'login','users',5,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-03 09:37:43'),(53,5,'approve_leave_request','leave_requests',2,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-03 09:37:55'),(54,5,'logout','users',5,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-03 09:38:07'),(55,8,'login','users',8,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-03 09:38:36'),(56,8,'logout','users',8,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-03 09:38:54'),(57,7,'login','users',7,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-03 09:39:18'),(58,7,'approve_leave_request','leave_requests',2,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-03 09:39:24'),(59,7,'logout','users',7,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-03 09:39:41'),(60,6,'login','users',6,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-03 09:40:09'),(61,6,'approve_leave_request','leave_requests',2,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-03 09:40:16'),(62,6,'logout','users',6,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-03 09:40:20'),(63,8,'login','users',8,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-03 09:40:42'),(64,8,'logout','users',8,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-03 09:41:56'),(65,4,'login','users',4,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-03 09:42:24'),(66,4,'logout','users',4,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-03 09:44:51'),(67,6,'login','users',6,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-03 09:45:23'),(68,6,'logout','users',6,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-03 09:47:39'),(69,6,'login','users',6,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-03 09:49:57'),(70,6,'logout','users',6,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-03 09:50:22'),(71,9,'register','users',9,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-03 09:51:48'),(72,9,'login','users',9,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-03 09:52:14'),(73,9,'logout','users',9,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-03 09:52:29'),(74,9,'login','users',9,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-03 09:52:48'),(75,9,'create_leave_request','leave_requests',3,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-03 09:57:23'),(76,9,'logout','users',9,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-03 09:58:51'),(77,5,'login','users',5,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-03 09:59:12'),(78,5,'approve_leave_request','leave_requests',3,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-03 10:00:56'),(79,5,'logout','users',5,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-03 10:01:03'),(80,6,'login','users',6,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-03 10:01:34'),(81,6,'logout','users',6,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-03 10:01:46'),(82,4,'login','users',4,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-03 10:02:13'),(83,4,'login','users',4,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-03 11:47:29'),(84,4,'logout','users',4,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-03 11:48:27'),(85,4,'login','users',4,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-03 12:11:29'),(86,4,'logout','users',4,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-03 12:11:41'),(87,4,'login','users',4,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-03 12:15:28'),(88,4,'logout','users',4,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-03 12:15:32'),(90,7,'login','users',7,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-03 12:57:04'),(91,7,'logout','users',7,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-03 12:57:32'),(92,7,'login','users',7,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-03 12:57:41'),(93,7,'logout','users',7,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-03 13:05:49'),(94,7,'login','users',7,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-03 13:11:35'),(95,7,'logout','users',7,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-03 13:17:28'),(96,7,'login','users',7,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-03 13:31:10'),(97,7,'logout','users',7,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-03 13:47:59'),(98,7,'login','users',7,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-03 14:11:14'),(99,7,'create_worker','users',11,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-03 14:12:53'),(100,7,'logout','users',7,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-03 14:13:11'),(101,NULL,'login','users',11,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-03 14:13:29'),(102,NULL,'create_leave_request','leave_requests',4,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-03 14:15:47'),(103,NULL,'logout','users',11,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-03 14:26:27'),(104,7,'login','users',7,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-03 14:27:56'),(105,7,'login','users',7,'127.0.0.1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.22621.6133','2026-06-04 07:22:53'),(106,7,'login','users',7,'127.0.0.1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.22621.6133','2026-06-04 07:23:30'),(107,7,'login','users',7,'127.0.0.1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.22621.6133','2026-06-04 07:23:31'),(108,7,'login','users',7,'127.0.0.1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.22621.6133','2026-06-04 07:23:31'),(109,4,'login','users',4,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-04 07:27:28'),(110,4,'logout','users',4,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-04 07:28:35'),(111,7,'login','users',7,'127.0.0.1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.22621.6133','2026-06-04 07:43:45'),(112,13,'request_account','users',13,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-04 07:48:38'),(113,4,'login','users',4,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-04 07:48:48'),(114,4,'approve_account_request','users',13,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-04 07:48:57'),(115,4,'logout','users',4,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-04 07:49:07'),(116,13,'login','users',13,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-04 07:49:32'),(117,13,'create_leave_request','leave_requests',5,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-04 07:50:33'),(118,13,'logout','users',13,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-04 07:50:55'),(119,5,'login','users',5,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-04 07:51:16'),(120,5,'approve_leave_request','leave_requests',5,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-04 07:51:30'),(121,5,'logout','users',5,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-04 07:52:48'),(122,7,'login','users',7,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-04 07:53:24'),(123,7,'approve_leave_request','leave_requests',3,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-04 07:53:34'),(124,7,'approve_leave_request','leave_requests',5,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-04 07:53:36'),(125,7,'logout','users',7,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-04 08:00:29'),(126,6,'login','users',6,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-04 08:01:03'),(127,6,'approve_leave_request','leave_requests',5,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-04 08:01:14'),(128,6,'approve_leave_request','leave_requests',3,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-04 08:01:22'),(129,6,'logout','users',6,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-04 08:01:49'),(130,4,'login','users',4,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-04 08:02:00'),(131,4,'logout','users',4,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-04 08:06:14'),(132,4,'login','users',4,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-04 08:06:18'),(133,4,'logout','users',4,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-04 09:16:53'),(134,14,'request_account','users',14,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-04 09:20:11'),(135,4,'login','users',4,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-04 09:20:24'),(136,4,'approve_account_request','users',14,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-04 09:21:11'),(137,4,'logout','users',4,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-04 09:21:32'),(138,14,'login','users',14,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-04 09:21:56'),(139,14,'logout','users',14,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-04 09:41:11'),(140,4,'login','users',4,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-04 09:41:15'),(141,7,'login','users',7,'127.0.0.1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.22621.6133','2026-06-04 09:50:09'),(142,4,'logout','users',4,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-04 10:07:03'),(143,4,'login','users',4,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-04 11:15:29'),(144,7,'login','users',7,'127.0.0.1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.22621.6133','2026-06-04 11:26:59'),(145,4,'logout','users',4,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-04 12:38:14'),(146,4,'login','users',4,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-04 12:38:37'),(147,4,'logout','users',4,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-04 13:42:52');
/*!40000 ALTER TABLE `audit_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `departments`
--

DROP TABLE IF EXISTS `departments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `departments` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(120) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `departments`
--

LOCK TABLES `departments` WRITE;
/*!40000 ALTER TABLE `departments` DISABLE KEYS */;
INSERT INTO `departments` VALUES (1,'ICT','2026-06-03 07:39:20'),(2,'Health','2026-06-04 07:48:38');
/*!40000 ALTER TABLE `departments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `employees`
--

DROP TABLE IF EXISTS `employees`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `employees` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `staff_id` varchar(50) NOT NULL,
  `department_id` int(10) unsigned DEFAULT NULL,
  `designation` varchar(120) DEFAULT NULL,
  `supervisor_id` int(10) unsigned DEFAULT NULL,
  `employment_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  UNIQUE KEY `staff_id` (`staff_id`),
  KEY `fk_employees_department` (`department_id`),
  KEY `fk_employees_supervisor` (`supervisor_id`),
  CONSTRAINT `fk_employees_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_employees_supervisor` FOREIGN KEY (`supervisor_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_employees_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employees`
--

LOCK TABLES `employees` WRITE;
/*!40000 ALTER TABLE `employees` DISABLE KEYS */;
INSERT INTO `employees` VALUES (3,4,'1234E',1,'Admin/Director',NULL,'2020-01-06','2026-06-03 09:04:44',NULL),(4,5,'1234R',1,'Supervisor',NULL,'2023-09-04','2026-06-03 09:09:26',NULL),(5,6,'1234W',1,'Director',NULL,'2023-09-04','2026-06-03 09:11:16',NULL),(6,7,'1234Q',1,'Hr',4,'2023-09-04','2026-06-03 09:14:19','2026-06-03 09:28:19'),(7,8,'1234T',1,'Cleaner',NULL,'2023-09-04','2026-06-03 09:31:51',NULL),(8,9,'1234Y',1,'Gate Man',NULL,'2025-06-11','2026-06-03 09:51:48',NULL),(10,13,'Y2000',2,'ICT officer',NULL,'2026-06-04','2026-06-04 07:48:38',NULL),(11,14,'1234A',2,'Supervisor',NULL,'2026-06-04','2026-06-04 09:20:11',NULL);
/*!40000 ALTER TABLE `employees` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `holidays`
--

DROP TABLE IF EXISTS `holidays`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `holidays` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(120) NOT NULL,
  `holiday_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `holiday_date` (`holiday_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `holidays`
--

LOCK TABLES `holidays` WRITE;
/*!40000 ALTER TABLE `holidays` DISABLE KEYS */;
/*!40000 ALTER TABLE `holidays` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `leave_balances`
--

DROP TABLE IF EXISTS `leave_balances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `leave_balances` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` int(10) unsigned NOT NULL,
  `leave_type_id` int(10) unsigned NOT NULL,
  `year` smallint(5) unsigned NOT NULL,
  `entitlement` decimal(6,2) NOT NULL DEFAULT 0.00,
  `carried_forward` decimal(6,2) NOT NULL DEFAULT 0.00,
  `used_days` decimal(6,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_leave_balance_employee_type_year` (`employee_id`,`leave_type_id`,`year`),
  KEY `fk_leave_balances_type` (`leave_type_id`),
  CONSTRAINT `fk_leave_balances_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_leave_balances_type` FOREIGN KEY (`leave_type_id`) REFERENCES `leave_types` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1058 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `leave_balances`
--

LOCK TABLES `leave_balances` WRITE;
/*!40000 ALTER TABLE `leave_balances` DISABLE KEYS */;
INSERT INTO `leave_balances` VALUES (78,3,1,2026,24.00,0.00,0.00,'2026-06-03 09:04:44',NULL),(79,3,5,2026,5.00,0.00,0.00,'2026-06-03 09:04:44',NULL),(80,3,3,2026,90.00,0.00,0.00,'2026-06-03 09:04:44',NULL),(81,3,4,2026,14.00,0.00,0.00,'2026-06-03 09:04:44',NULL),(82,3,2,2026,12.00,0.00,0.00,'2026-06-03 09:04:44',NULL),(83,3,6,2026,0.00,0.00,0.00,'2026-06-03 09:04:44',NULL),(84,3,7,2026,0.00,0.00,0.00,'2026-06-03 09:04:44',NULL),(99,4,1,2026,24.00,0.00,0.00,'2026-06-03 09:09:26',NULL),(100,4,5,2026,5.00,0.00,0.00,'2026-06-03 09:09:26',NULL),(101,4,3,2026,90.00,0.00,0.00,'2026-06-03 09:09:26',NULL),(102,4,4,2026,14.00,0.00,0.00,'2026-06-03 09:09:26',NULL),(103,4,2,2026,12.00,0.00,0.00,'2026-06-03 09:09:26',NULL),(104,4,6,2026,0.00,0.00,0.00,'2026-06-03 09:09:26',NULL),(105,4,7,2026,0.00,0.00,0.00,'2026-06-03 09:09:26',NULL),(106,5,1,2026,24.00,0.00,0.00,'2026-06-03 09:11:16',NULL),(107,5,5,2026,5.00,0.00,0.00,'2026-06-03 09:11:16',NULL),(108,5,3,2026,90.00,0.00,0.00,'2026-06-03 09:11:16',NULL),(109,5,4,2026,14.00,0.00,0.00,'2026-06-03 09:11:16',NULL),(110,5,2,2026,12.00,0.00,0.00,'2026-06-03 09:11:16',NULL),(111,5,6,2026,0.00,0.00,0.00,'2026-06-03 09:11:16',NULL),(112,5,7,2026,0.00,0.00,0.00,'2026-06-03 09:11:16',NULL),(113,6,1,2026,24.00,0.00,0.00,'2026-06-03 09:14:19',NULL),(114,6,5,2026,5.00,0.00,0.00,'2026-06-03 09:14:19',NULL),(115,6,3,2026,90.00,0.00,0.00,'2026-06-03 09:14:19',NULL),(116,6,4,2026,14.00,0.00,0.00,'2026-06-03 09:14:19',NULL),(117,6,2,2026,12.00,0.00,0.00,'2026-06-03 09:14:19',NULL),(118,6,6,2026,0.00,0.00,0.00,'2026-06-03 09:14:19',NULL),(119,6,7,2026,0.00,0.00,0.00,'2026-06-03 09:14:19',NULL),(141,7,1,2026,24.00,0.00,0.00,'2026-06-03 09:31:51',NULL),(142,7,5,2026,5.00,0.00,0.00,'2026-06-03 09:31:51',NULL),(143,7,3,2026,90.00,0.00,0.00,'2026-06-03 09:31:51',NULL),(144,7,4,2026,14.00,0.00,0.00,'2026-06-03 09:31:51',NULL),(145,7,2,2026,12.00,0.00,12.00,'2026-06-03 09:31:51','2026-06-03 09:40:16'),(146,7,6,2026,0.00,0.00,0.00,'2026-06-03 09:31:51',NULL),(147,7,7,2026,0.00,0.00,0.00,'2026-06-03 09:31:51',NULL),(407,8,1,2026,24.00,0.00,16.00,'2026-06-03 09:51:48','2026-06-04 08:01:22'),(408,8,5,2026,5.00,0.00,0.00,'2026-06-03 09:51:48',NULL),(409,8,3,2026,90.00,0.00,0.00,'2026-06-03 09:51:48',NULL),(410,8,4,2026,14.00,0.00,0.00,'2026-06-03 09:51:48',NULL),(411,8,2,2026,12.00,0.00,0.00,'2026-06-03 09:51:48',NULL),(412,8,6,2026,0.00,0.00,0.00,'2026-06-03 09:51:48',NULL),(413,8,7,2026,0.00,0.00,0.00,'2026-06-03 09:51:48',NULL),(736,10,1,2026,24.00,0.00,15.00,'2026-06-04 07:48:38','2026-06-04 08:01:14'),(737,10,5,2026,5.00,0.00,0.00,'2026-06-04 07:48:38',NULL),(738,10,3,2026,90.00,0.00,0.00,'2026-06-04 07:48:38',NULL),(739,10,4,2026,14.00,0.00,0.00,'2026-06-04 07:48:38',NULL),(740,10,2,2026,12.00,0.00,0.00,'2026-06-04 07:48:38',NULL),(741,10,6,2026,0.00,0.00,0.00,'2026-06-04 07:48:38',NULL),(742,10,7,2026,0.00,0.00,0.00,'2026-06-04 07:48:38',NULL),(904,11,1,2026,24.00,0.00,0.00,'2026-06-04 09:20:11',NULL),(905,11,5,2026,5.00,0.00,0.00,'2026-06-04 09:20:11',NULL),(906,11,3,2026,90.00,0.00,0.00,'2026-06-04 09:20:11',NULL),(907,11,4,2026,14.00,0.00,0.00,'2026-06-04 09:20:11',NULL),(908,11,2,2026,12.00,0.00,0.00,'2026-06-04 09:20:11',NULL),(909,11,6,2026,0.00,0.00,0.00,'2026-06-04 09:20:11',NULL),(910,11,7,2026,0.00,0.00,0.00,'2026-06-04 09:20:11',NULL);
/*!40000 ALTER TABLE `leave_balances` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `leave_requests`
--

DROP TABLE IF EXISTS `leave_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `leave_requests` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` int(10) unsigned NOT NULL,
  `leave_type_id` int(10) unsigned NOT NULL,
  `contact_number` varchar(30) DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `days_requested` decimal(6,2) NOT NULL,
  `reason` text DEFAULT NULL,
  `attachment_path` varchar(255) DEFAULT NULL,
  `status` enum('pending_supervisor','pending_hr','pending_director','approved','rejected','cancelled') NOT NULL DEFAULT 'pending_supervisor',
  `rejection_reason` text DEFAULT NULL,
  `submitted_at` datetime NOT NULL DEFAULT current_timestamp(),
  `finalized_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_leave_requests_status` (`status`),
  KEY `idx_leave_requests_dates` (`start_date`,`end_date`),
  KEY `fk_leave_requests_employee` (`employee_id`),
  KEY `fk_leave_requests_type` (`leave_type_id`),
  CONSTRAINT `fk_leave_requests_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_leave_requests_type` FOREIGN KEY (`leave_type_id`) REFERENCES `leave_types` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `leave_requests`
--

LOCK TABLES `leave_requests` WRITE;
/*!40000 ALTER TABLE `leave_requests` DISABLE KEYS */;
INSERT INTO `leave_requests` VALUES (2,7,2,'0726697863','2026-06-04','2026-06-19',12.00,'Sick','20260603123522-82106acc0f35d32a.docx','approved',NULL,'2026-06-03 12:35:22','2026-06-03 12:40:16','2026-06-03 09:35:22','2026-06-03 09:40:16'),(3,8,1,'0799876547','2026-06-04','2026-06-25',16.00,'Test','20260603125723-386cfda099550356.docx','approved',NULL,'2026-06-03 12:57:23','2026-06-04 11:01:22','2026-06-03 09:57:23','2026-06-04 08:01:22'),(5,10,1,'0700000000','2026-06-05','2026-06-25',15.00,'unhappy in the office',NULL,'approved',NULL,'2026-06-04 10:50:33','2026-06-04 11:01:14','2026-06-04 07:50:33','2026-06-04 08:01:14');
/*!40000 ALTER TABLE `leave_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `leave_types`
--

DROP TABLE IF EXISTS `leave_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `leave_types` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `default_entitlement` decimal(6,2) NOT NULL DEFAULT 0.00,
  `requires_balance` tinyint(1) NOT NULL DEFAULT 1,
  `requires_attachment` tinyint(1) NOT NULL DEFAULT 0,
  `attachment_after_days` decimal(6,2) DEFAULT NULL,
  `is_paid` tinyint(1) NOT NULL DEFAULT 1,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `leave_types`
--

LOCK TABLES `leave_types` WRITE;
/*!40000 ALTER TABLE `leave_types` DISABLE KEYS */;
INSERT INTO `leave_types` VALUES (1,'Annual Leave',24.00,1,0,NULL,1,1,'2026-06-03 07:33:26',NULL),(2,'Sick Leave',12.00,1,0,3.00,1,1,'2026-06-03 07:33:26',NULL),(3,'Maternity Leave',90.00,1,0,NULL,1,1,'2026-06-03 07:33:26',NULL),(4,'Paternity Leave',14.00,1,0,NULL,1,1,'2026-06-03 07:33:26',NULL),(5,'Compassionate Leave',5.00,1,1,NULL,1,1,'2026-06-03 07:33:26',NULL),(6,'Study Leave',0.00,0,0,NULL,1,1,'2026-06-03 07:33:26',NULL),(7,'Unpaid Leave',0.00,0,0,NULL,0,1,'2026-06-03 07:33:26',NULL);
/*!40000 ALTER TABLE `leave_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notifications` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `title` varchar(160) NOT NULL,
  `message` text NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_notifications_user_read` (`user_id`,`is_read`),
  CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
INSERT INTO `notifications` VALUES (6,4,'Leave request awaiting review','Mophat Oruma submitted a leave request for 12 working day(s).','index.php?route=approvals',1,'2026-06-03 09:35:22'),(7,5,'Leave request awaiting review','Mophat Oruma submitted a leave request for 12 working day(s).','index.php?route=approvals',1,'2026-06-03 09:35:22'),(8,4,'Leave request awaiting review','Mophat Oruma has a leave request awaiting Hr review.','index.php?route=approvals',1,'2026-06-03 09:37:55'),(9,7,'Leave request awaiting review','Mophat Oruma has a leave request awaiting Hr review.','index.php?route=approvals',1,'2026-06-03 09:37:55'),(10,8,'Leave request updated','Your leave request moved to Hr review.','index.php?route=leave%2Fview&id=2',1,'2026-06-03 09:37:55'),(11,4,'Leave request awaiting review','Mophat Oruma has a leave request awaiting Director review.','index.php?route=approvals',1,'2026-06-03 09:39:24'),(12,6,'Leave request awaiting review','Mophat Oruma has a leave request awaiting Director review.','index.php?route=approvals',1,'2026-06-03 09:39:24'),(13,8,'Leave request updated','Your leave request moved to Director review.','index.php?route=leave%2Fview&id=2',1,'2026-06-03 09:39:24'),(14,8,'Leave request approved','Your leave request has received final approval.','index.php?route=leave%2Fview&id=2',1,'2026-06-03 09:40:16'),(15,4,'Leave request awaiting review','Victor Ouma submitted a leave request for 16 working day(s).','index.php?route=approvals',1,'2026-06-03 09:57:23'),(16,5,'Leave request awaiting review','Victor Ouma submitted a leave request for 16 working day(s).','index.php?route=approvals',1,'2026-06-03 09:57:23'),(17,4,'Leave request awaiting review','Victor Ouma has a leave request awaiting Hr review.','index.php?route=approvals',1,'2026-06-03 10:00:56'),(18,7,'Leave request awaiting review','Victor Ouma has a leave request awaiting Hr review.','index.php?route=approvals',1,'2026-06-03 10:00:56'),(19,9,'Leave request updated','Your leave request moved to Hr review.','index.php?route=leave%2Fview&id=3',0,'2026-06-03 10:00:56'),(20,6,'Leave request awaiting review','Benard Omanyala submitted a leave request for 16 working day(s).','index.php?route=approvals',1,'2026-06-03 14:15:47'),(21,4,'Account request awaiting ICT approval','KEVIN submitted a new account request.','index.php?route=admin%2Faccount-requests',1,'2026-06-04 07:48:38'),(22,4,'Leave request awaiting review','KEVIN submitted a leave request for 15 working day(s).','index.php?route=approvals',1,'2026-06-04 07:50:33'),(23,5,'Leave request awaiting review','KEVIN submitted a leave request for 15 working day(s).','index.php?route=approvals',0,'2026-06-04 07:50:33'),(24,4,'Leave request awaiting review','KEVIN has a leave request awaiting Hr review.','index.php?route=approvals',1,'2026-06-04 07:51:30'),(25,7,'Leave request awaiting review','KEVIN has a leave request awaiting Hr review.','index.php?route=approvals',1,'2026-06-04 07:51:30'),(26,13,'Leave request updated','Your leave request moved to Hr review.','index.php?route=leave%2Fview&id=5',0,'2026-06-04 07:51:30'),(27,4,'Leave request awaiting review','Victor Ouma has a leave request awaiting Director review.','index.php?route=approvals',1,'2026-06-04 07:53:34'),(28,6,'Leave request awaiting review','Victor Ouma has a leave request awaiting Director review.','index.php?route=approvals',1,'2026-06-04 07:53:34'),(29,9,'Leave request updated','Your leave request moved to Director review.','index.php?route=leave%2Fview&id=3',0,'2026-06-04 07:53:34'),(30,4,'Leave request awaiting review','KEVIN has a leave request awaiting Director review.','index.php?route=approvals',1,'2026-06-04 07:53:36'),(31,6,'Leave request awaiting review','KEVIN has a leave request awaiting Director review.','index.php?route=approvals',1,'2026-06-04 07:53:36'),(32,13,'Leave request updated','Your leave request moved to Director review.','index.php?route=leave%2Fview&id=5',0,'2026-06-04 07:53:36'),(33,13,'Leave request approved','Your leave request has received final approval.','index.php?route=leave%2Fview&id=5',0,'2026-06-04 08:01:14'),(34,9,'Leave request approved','Your leave request has received final approval.','index.php?route=leave%2Fview&id=3',0,'2026-06-04 08:01:22'),(35,4,'Account request awaiting ICT approval','Elba Ouma submitted a new account request.','index.php?route=admin%2Faccount-requests',1,'2026-06-04 09:20:11');
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `full_name` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `national_id` varchar(50) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('admin','employee','supervisor','hr','director') NOT NULL DEFAULT 'employee',
  `phone` varchar(30) DEFAULT NULL,
  `profile_photo_path` varchar(255) DEFAULT NULL,
  `status` enum('pending','active','inactive','rejected') NOT NULL DEFAULT 'pending',
  `last_login_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `national_id` (`national_id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (4,'Evans Amos','evansamos702@gmail.com','39649455','pbkdf2_sha256$120000$d5c90a5af45fa2e95cfcae08264fa38c$bd729b13a04a3dcd4bbc134a403f98dfc1a81096996eaf1a6b2ca8a5df41d3e8','admin','0708471884',NULL,'active','2026-06-04 15:38:37','2026-06-03 09:04:44','2026-06-04 12:38:37'),(5,'Elba Ouma','elba123@gmail.com',NULL,'pbkdf2_sha256$120000$222f50f124b4e5cb38aa644781d663c4$eb459a0154197ff1dd4c8520342822024d571ff022e4b74c26d7d2429a943b35','supervisor','0102326587',NULL,'active','2026-06-04 10:51:15','2026-06-03 09:09:25','2026-06-04 07:51:15'),(6,'Felix Wesonga','felix123@gmail.com',NULL,'pbkdf2_sha256$120000$77c17f8823488bd60b8bd594afe59873$fa6148172208ec9f2a4203704b4433068df818787821877f6823d5f73d4c4931','director','0789877665',NULL,'active','2026-06-04 11:01:03','2026-06-03 09:11:15','2026-06-04 08:01:03'),(7,'Bright Bravo','bravo123@gmail.com',NULL,'pbkdf2_sha256$120000$0161e47d2114a8cbcb255e05d7512afe$ebc1ab8fbb65eade7c494737f290a91bfe56c794d25250069e4d4defa623d925','hr','0765567898',NULL,'active','2026-06-04 14:26:59','2026-06-03 09:14:19','2026-06-04 11:26:59'),(8,'Mophat Oruma','mophat123@gmail.com',NULL,'pbkdf2_sha256$120000$199060e8ad1296a7630f065b0f076fda$abbb478186120a800cec511f7c0335f3736cac45817c433ea5ec9f0861e490f8','employee','0726697863',NULL,'active','2026-06-03 12:40:42','2026-06-03 09:31:51','2026-06-03 09:40:42'),(9,'Victor Ouma','victor123@gmail.com',NULL,'pbkdf2_sha256$120000$6c29a8b0854c94c848f0485bbb6c2b79$c27e1fe62c137301b4839a8496a725748cdb9660f9a1ac86e904d87130723ab2','employee','0799876547',NULL,'active','2026-06-03 12:52:48','2026-06-03 09:51:48','2026-06-03 09:52:48'),(13,'KEVIN','lincolince@gmail.com','30210709','pbkdf2_sha256$120000$c49bc4e424843eb7cec54828bf3e99e3$16892b3f3254dbe9556a2b8c907a6e336e4e50cc6ee1dd8e062b526adaef7eef','employee','0700000000',NULL,'active','2026-06-04 10:49:32','2026-06-04 07:48:38','2026-06-04 07:49:32'),(14,'Elba Ouma','elba13@gmail.com','31210709','pbkdf2_sha256$120000$7dbc3902319702b55f2a676ec65c3f2e$925a7f3deb16382b8c1ad029401ade11c79248e9a6dc3a88f78d0685da6f7e10','employee','0102326587',NULL,'active','2026-06-04 12:21:56','2026-06-04 09:20:11','2026-06-04 09:21:56');
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

-- Dump completed on 2026-06-04 16:45:28
