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
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `approval_steps`
--

LOCK TABLES `approval_steps` WRITE;
/*!40000 ALTER TABLE `approval_steps` DISABLE KEYS */;
INSERT INTO `approval_steps` VALUES (25,10,1,'supervisor',22,'approved','','2026-06-08 13:01:58','2026-06-08 09:53:11'),(26,10,2,'director',23,'approved','','2026-06-08 13:06:44','2026-06-08 09:53:11'),(27,10,3,'hr',24,'approved','','2026-06-08 13:07:30','2026-06-08 09:53:11');
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
) ENGINE=InnoDB AUTO_INCREMENT=231 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_logs`
--

LOCK TABLES `audit_logs` WRITE;
/*!40000 ALTER TABLE `audit_logs` DISABLE KEYS */;
INSERT INTO `audit_logs` VALUES (103,1,'logout','users',1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-08 06:23:40'),(104,14,'request_account','users',14,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-08 06:24:56'),(105,1,'login','users',1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-08 06:25:14'),(106,1,'approve_account_request','users',14,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-08 06:25:32'),(107,1,'logout','users',1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-08 06:26:32'),(108,14,'login','users',14,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-08 06:26:53'),(109,14,'logout','users',14,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-08 06:28:38'),(110,1,'login','users',1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-08 06:30:54'),(111,1,'logout','users',1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-08 06:41:22'),(112,1,'login','users',1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-08 06:41:46'),(113,1,'logout','users',1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-08 06:58:57'),(114,1,'login','users',1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-08 07:05:27'),(115,1,'create_worker','users',22,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-08 07:32:05'),(116,1,'logout','users',1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-08 07:32:31'),(117,22,'login','users',22,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-08 07:32:51'),(118,22,'update_password','users',22,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-08 07:33:35'),(119,22,'logout','users',22,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-08 07:34:03'),(120,1,'login','users',1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-08 07:34:07'),(121,1,'create_worker','users',23,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-08 07:34:46'),(122,1,'logout','users',1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-08 07:35:03'),(123,23,'login','users',23,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-08 07:35:23'),(124,23,'update_password','users',23,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-08 07:35:58'),(125,23,'logout','users',23,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-08 07:36:09'),(126,1,'login','users',1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-08 07:36:15'),(127,1,'create_worker','users',24,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-08 07:37:08'),(128,1,'logout','users',1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-08 07:37:26'),(129,24,'login','users',24,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-08 07:37:48'),(130,24,'update_password','users',24,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-08 07:38:11'),(131,24,'logout','users',24,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-08 07:38:39'),(132,23,'login','users',23,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-08 07:38:56'),(133,23,'logout','users',23,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-08 07:39:31'),(134,1,'login','users',1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-08 09:00:33'),(135,1,'logout','users',1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-08 09:01:43'),(136,1,'login','users',1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-08 09:02:07'),(137,1,'create_worker','users',25,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-08 09:14:57'),(138,1,'save_leave_type','leave_types',5,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-08 09:21:24'),(139,1,'logout','users',1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-08 09:23:35'),(140,26,'request_account','users',26,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-08 09:27:55'),(141,1,'login','users',1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-08 09:28:11'),(142,1,'approve_account_request','users',26,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-08 09:32:15'),(143,1,'logout','users',1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-08 09:41:26'),(144,26,'login','users',26,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-08 09:42:05'),(145,26,'logout','users',26,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-08 09:43:54'),(146,25,'login','users',25,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-08 09:48:44'),(147,25,'update_password','users',25,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-08 09:49:17'),(148,25,'logout','users',25,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-08 09:50:33'),(149,1,'login','users',1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-08 09:50:38'),(150,1,'update_user_access','users',25,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-08 09:50:50'),(151,1,'logout','users',1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-08 09:50:57'),(152,25,'login','users',25,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-08 09:51:02'),(153,25,'logout','users',25,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-08 09:51:25'),(154,26,'login','users',26,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-08 09:51:29'),(155,26,'create_leave_request','leave_requests',10,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-08 09:53:11'),(156,26,'logout','users',26,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-08 09:57:04'),(157,22,'login','users',22,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-08 09:57:59'),(158,22,'approve_leave_request','leave_requests',10,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-08 10:01:58'),(159,22,'logout','users',22,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-08 10:06:03'),(160,23,'login','users',23,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-08 10:06:32'),(161,23,'approve_leave_request','leave_requests',10,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-08 10:06:44'),(162,23,'logout','users',23,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-08 10:07:03'),(163,24,'login','users',24,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-08 10:07:23'),(164,24,'approve_leave_request','leave_requests',10,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-08 10:07:30'),(165,24,'logout','users',24,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-08 10:09:41'),(166,1,'login','users',1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-08 10:09:49'),(167,1,'logout','users',1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-08 10:13:55'),(168,1,'login','users',1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-06-08 10:40:46'),(169,1,'login','users',1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0','2026-06-08 13:21:34'),(170,1,'logout','users',1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0','2026-06-08 13:22:25'),(171,1,'login','users',1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0','2026-06-08 13:22:45'),(172,1,'logout','users',1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0','2026-06-08 13:23:39'),(173,22,'login','users',22,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0','2026-06-08 13:24:10'),(174,22,'logout','users',22,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0','2026-06-09 05:15:10'),(175,1,'login','users',1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0','2026-06-09 05:15:21'),(176,1,'logout','users',1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0','2026-06-09 12:47:25'),(177,27,'request_account','users',27,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0','2026-06-09 12:50:14'),(178,1,'login','users',1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0','2026-06-09 12:51:45'),(179,1,'approve_account_request','users',27,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0','2026-06-09 12:51:52'),(180,1,'logout','users',1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0','2026-06-09 12:52:35'),(181,27,'login','users',27,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0','2026-06-09 12:53:03'),(182,27,'logout','users',27,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0','2026-06-09 12:53:16'),(183,1,'login','users',1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0','2026-06-09 12:53:30'),(184,1,'update_user_access','users',27,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0','2026-06-09 12:54:08'),(185,1,'logout','users',1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0','2026-06-09 12:54:15'),(186,27,'login','users',27,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0','2026-06-09 12:54:20'),(187,27,'logout','users',27,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0','2026-06-09 12:58:07'),(188,1,'login','users',1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0','2026-06-09 12:58:14'),(189,1,'logout','users',1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0','2026-06-09 13:17:26'),(190,29,'request_account','users',29,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0','2026-06-09 13:33:13'),(191,1,'login','users',1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0','2026-06-09 13:33:30'),(192,1,'logout','users',1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0','2026-06-09 14:24:50'),(193,30,'request_account','users',30,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0','2026-06-09 14:28:40'),(194,1,'login','users',1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0','2026-06-09 14:29:30'),(195,1,'logout','users',1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0','2026-06-10 04:42:18'),(196,1,'login','users',1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0','2026-06-10 05:15:13'),(197,1,'logout','users',1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0','2026-06-10 05:15:33'),(198,1,'login','users',1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0','2026-06-10 06:02:21'),(199,1,'logout','users',1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0','2026-06-10 06:02:45'),(200,1,'login','users',1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0','2026-06-10 08:37:55'),(201,1,'logout','users',1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0','2026-06-10 08:40:53'),(202,1,'login','users',1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0','2026-06-10 08:41:46'),(203,1,'logout','users',1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0','2026-06-10 08:42:30'),(204,27,'login','users',27,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0','2026-06-10 08:44:08'),(205,27,'logout','users',27,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0','2026-06-10 08:44:31'),(206,27,'login','users',27,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0','2026-06-10 08:44:43'),(207,27,'logout','users',27,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0','2026-06-10 08:44:55'),(208,27,'login','users',27,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0','2026-06-10 08:47:06'),(209,27,'logout','users',27,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0','2026-06-10 08:47:21'),(210,27,'login','users',27,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0','2026-06-10 08:48:38'),(211,27,'logout','users',27,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0','2026-06-10 09:06:42'),(212,27,'login','users',27,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0','2026-06-10 09:16:19'),(213,27,'logout','users',27,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0','2026-06-10 09:16:32'),(214,27,'login','users',27,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0','2026-06-10 09:59:48'),(215,27,'logout','users',27,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0','2026-06-10 10:00:27'),(216,1,'login','users',1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0','2026-06-10 10:00:49'),(217,1,'save_leave_type','leave_types',2,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0','2026-06-10 10:04:30'),(218,1,'save_leave_type','leave_types',2,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0','2026-06-10 10:04:36'),(219,1,'save_leave_type','leave_types',3,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0','2026-06-10 10:04:43'),(220,1,'logout','users',1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0','2026-06-10 10:04:53'),(221,27,'login','users',27,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0','2026-06-10 10:04:59'),(222,27,'logout','users',27,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0','2026-06-10 12:20:52'),(223,27,'login','users',27,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0','2026-06-10 12:25:16'),(224,27,'logout','users',27,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0','2026-06-10 12:30:10'),(225,1,'login','users',1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0','2026-06-10 12:33:06'),(226,1,'logout','users',1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0','2026-06-10 12:35:44'),(227,1,'login','users',1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0','2026-06-10 12:47:06'),(228,1,'logout','users',1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0','2026-06-10 13:05:08'),(229,1,'login','users',1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0','2026-06-10 13:08:09'),(230,1,'logout','users',1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0','2026-06-10 13:49:05');
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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `departments`
--

LOCK TABLES `departments` WRITE;
/*!40000 ALTER TABLE `departments` DISABLE KEYS */;
INSERT INTO `departments` VALUES (1,'ICT','2026-06-03 07:39:20');
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
) ENGINE=InnoDB AUTO_INCREMENT=34 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employees`
--

LOCK TABLES `employees` WRITE;
/*!40000 ALTER TABLE `employees` DISABLE KEYS */;
INSERT INTO `employees` VALUES (3,1,'202004567645',1,'Admin/Director',NULL,'2020-01-06','2026-06-03 09:04:44','2026-06-05 22:01:57'),(18,14,'220006787550',1,'Employee',NULL,'2026-06-02','2026-06-08 06:24:56',NULL),(26,22,'202006577875',1,'Employee',NULL,'2026-05-12','2026-06-08 07:32:05',NULL),(27,23,'202006577876',1,'Director',NULL,'2026-03-10','2026-06-08 07:34:46',NULL),(28,24,'202006577871',1,'HR',NULL,'2026-03-11','2026-06-08 07:37:08',NULL),(29,25,'546766753',1,'ICT Officer',27,'2026-03-02','2026-06-08 09:14:57',NULL),(30,26,'220006787570',1,'Employee',NULL,'2026-06-08','2026-06-08 09:27:55',NULL),(31,27,'220006787574',1,'Employee',NULL,'2026-06-09','2026-06-09 12:50:14',NULL),(32,29,'202006577890',1,'HR',NULL,'2026-06-09','2026-06-09 13:33:13',NULL),(33,30,'202006577808',1,'HR',NULL,'2026-06-09','2026-06-09 14:28:40',NULL);
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
) ENGINE=InnoDB AUTO_INCREMENT=1915 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `leave_balances`
--

LOCK TABLES `leave_balances` WRITE;
/*!40000 ALTER TABLE `leave_balances` DISABLE KEYS */;
INSERT INTO `leave_balances` VALUES (1165,3,1,2026,24.00,0.00,0.00,'2026-06-08 05:54:08',NULL),(1166,3,5,2026,5.00,0.00,0.00,'2026-06-08 05:54:08',NULL),(1167,3,3,2026,90.00,0.00,0.00,'2026-06-08 05:54:08',NULL),(1168,3,4,2026,14.00,0.00,0.00,'2026-06-08 05:54:08',NULL),(1169,3,2,2026,12.00,0.00,0.00,'2026-06-08 05:54:08',NULL),(1170,3,6,2026,0.00,0.00,0.00,'2026-06-08 05:54:08',NULL),(1183,18,1,2026,24.00,0.00,0.00,'2026-06-08 06:24:56',NULL),(1184,18,5,2026,5.00,0.00,0.00,'2026-06-08 06:24:56',NULL),(1185,18,3,2026,90.00,0.00,0.00,'2026-06-08 06:24:56',NULL),(1186,18,4,2026,14.00,0.00,0.00,'2026-06-08 06:24:56',NULL),(1187,18,2,2026,12.00,0.00,0.00,'2026-06-08 06:24:56',NULL),(1188,18,6,2026,0.00,0.00,0.00,'2026-06-08 06:24:56',NULL),(1303,26,1,2026,24.00,0.00,0.00,'2026-06-08 07:32:05',NULL),(1304,26,5,2026,5.00,0.00,0.00,'2026-06-08 07:32:05',NULL),(1305,26,3,2026,90.00,0.00,0.00,'2026-06-08 07:32:05',NULL),(1306,26,4,2026,14.00,0.00,0.00,'2026-06-08 07:32:05',NULL),(1307,26,2,2026,12.00,0.00,0.00,'2026-06-08 07:32:05',NULL),(1308,26,6,2026,0.00,0.00,0.00,'2026-06-08 07:32:05',NULL),(1327,27,1,2026,24.00,0.00,0.00,'2026-06-08 07:34:46',NULL),(1328,27,5,2026,5.00,0.00,0.00,'2026-06-08 07:34:46',NULL),(1329,27,3,2026,90.00,0.00,0.00,'2026-06-08 07:34:46',NULL),(1330,27,4,2026,14.00,0.00,0.00,'2026-06-08 07:34:46',NULL),(1331,27,2,2026,12.00,0.00,0.00,'2026-06-08 07:34:46',NULL),(1332,27,6,2026,0.00,0.00,0.00,'2026-06-08 07:34:46',NULL),(1351,28,1,2026,24.00,0.00,0.00,'2026-06-08 07:37:08',NULL),(1352,28,5,2026,5.00,0.00,0.00,'2026-06-08 07:37:08',NULL),(1353,28,3,2026,90.00,0.00,0.00,'2026-06-08 07:37:08',NULL),(1354,28,4,2026,14.00,0.00,0.00,'2026-06-08 07:37:08',NULL),(1355,28,2,2026,12.00,0.00,0.00,'2026-06-08 07:37:08',NULL),(1356,28,6,2026,0.00,0.00,0.00,'2026-06-08 07:37:08',NULL),(1423,29,1,2026,24.00,0.00,0.00,'2026-06-08 09:14:57',NULL),(1424,29,5,2026,5.00,0.00,0.00,'2026-06-08 09:14:57',NULL),(1425,29,3,2026,90.00,0.00,0.00,'2026-06-08 09:14:57',NULL),(1426,29,4,2026,14.00,0.00,0.00,'2026-06-08 09:14:57',NULL),(1427,29,2,2026,12.00,0.00,0.00,'2026-06-08 09:14:57',NULL),(1428,29,6,2026,0.00,0.00,0.00,'2026-06-08 09:14:57',NULL),(1429,30,1,2026,24.00,0.00,14.00,'2026-06-08 09:27:55','2026-06-08 10:07:30'),(1430,30,5,2026,5.00,0.00,0.00,'2026-06-08 09:27:55',NULL),(1431,30,3,2026,90.00,0.00,0.00,'2026-06-08 09:27:55',NULL),(1432,30,4,2026,14.00,0.00,0.00,'2026-06-08 09:27:55',NULL),(1433,30,2,2026,12.00,0.00,0.00,'2026-06-08 09:27:55',NULL),(1434,30,6,2026,0.00,0.00,0.00,'2026-06-08 09:27:55',NULL),(1621,31,1,2026,24.00,0.00,0.00,'2026-06-09 12:50:14',NULL),(1622,31,5,2026,5.00,0.00,0.00,'2026-06-09 12:50:14',NULL),(1623,31,3,2026,90.00,0.00,0.00,'2026-06-09 12:50:14',NULL),(1624,31,4,2026,14.00,0.00,0.00,'2026-06-09 12:50:14',NULL),(1625,31,2,2026,12.00,0.00,0.00,'2026-06-09 12:50:14',NULL),(1626,31,6,2026,0.00,0.00,0.00,'2026-06-09 12:50:14',NULL),(1669,32,1,2026,24.00,0.00,0.00,'2026-06-09 13:33:13',NULL),(1670,32,5,2026,5.00,0.00,0.00,'2026-06-09 13:33:13',NULL),(1671,32,3,2026,90.00,0.00,0.00,'2026-06-09 13:33:13',NULL),(1672,32,4,2026,14.00,0.00,0.00,'2026-06-09 13:33:13',NULL),(1673,32,2,2026,12.00,0.00,0.00,'2026-06-09 13:33:13',NULL),(1674,32,6,2026,0.00,0.00,0.00,'2026-06-09 13:33:13',NULL),(1693,33,1,2026,24.00,0.00,0.00,'2026-06-09 14:28:40',NULL),(1694,33,5,2026,5.00,0.00,0.00,'2026-06-09 14:28:40',NULL),(1695,33,3,2026,90.00,0.00,0.00,'2026-06-09 14:28:40',NULL),(1696,33,4,2026,14.00,0.00,0.00,'2026-06-09 14:28:40',NULL),(1697,33,2,2026,12.00,0.00,0.00,'2026-06-09 14:28:40',NULL),(1698,33,6,2026,0.00,0.00,0.00,'2026-06-09 14:28:40',NULL);
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
  `handover_notes` text DEFAULT NULL,
  `attachment_path` varchar(255) DEFAULT NULL,
  `status` enum('pending_supervisor','pending_hr','pending_director','approved','rejected','cancelled') NOT NULL DEFAULT 'pending_supervisor',
  `rejection_reason` text DEFAULT NULL,
  `submitted_at` datetime NOT NULL DEFAULT current_timestamp(),
  `finalized_at` datetime DEFAULT NULL,
  `resumed_at` datetime DEFAULT NULL,
  `resumed_by_user_id` int(10) unsigned DEFAULT NULL,
  `resumption_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_leave_requests_status` (`status`),
  KEY `idx_leave_requests_dates` (`start_date`,`end_date`),
  KEY `fk_leave_requests_employee` (`employee_id`),
  KEY `fk_leave_requests_type` (`leave_type_id`),
  KEY `fk_leave_requests_resumed_by` (`resumed_by_user_id`),
  CONSTRAINT `fk_leave_requests_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_leave_requests_resumed_by` FOREIGN KEY (`resumed_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_leave_requests_type` FOREIGN KEY (`leave_type_id`) REFERENCES `leave_types` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `leave_requests`
--

LOCK TABLES `leave_requests` WRITE;
/*!40000 ALTER TABLE `leave_requests` DISABLE KEYS */;
INSERT INTO `leave_requests` VALUES (10,30,1,'0740471317','2026-06-09','2026-06-26',14.00,'',NULL,NULL,'approved',NULL,'2026-06-08 12:53:11','2026-06-08 13:07:30',NULL,NULL,NULL,'2026-06-08 09:53:11','2026-06-08 10:07:30');
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
  `gender_eligibility` enum('any','male','female') NOT NULL DEFAULT 'any',
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
INSERT INTO `leave_types` VALUES (1,'Annual Leave','any',24.00,1,0,NULL,1,1,'2026-06-03 07:33:26',NULL),(2,'Sick Leave','any',0.00,1,1,3.00,1,1,'2026-06-03 07:33:26','2026-06-10 10:04:30'),(3,'Maternity Leave','female',0.00,1,0,NULL,1,1,'2026-06-03 07:33:26','2026-06-10 10:12:36'),(4,'Paternity Leave','male',14.00,1,0,NULL,1,1,'2026-06-03 07:33:26','2026-06-10 10:12:36'),(5,'Compassionate Leave','any',5.00,1,1,NULL,1,1,'2026-06-03 07:33:26',NULL),(6,'Study Leave','any',0.00,0,0,NULL,1,1,'2026-06-03 07:33:26',NULL);
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
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
INSERT INTO `notifications` VALUES (12,1,'Account request awaiting ICT approval','seinlus nyongesa submitted a new account request.','index.php?route=admin%2Faccount-requests',1,'2026-06-08 06:24:56'),(13,1,'Account request awaiting ICT approval','Blessing Barbra submitted a new account request.','index.php?route=admin%2Faccount-requests',1,'2026-06-08 09:27:55'),(14,22,'Leave request awaiting review','Blessing Barbra submitted a leave request for 14 working day(s).','index.php?route=approvals',1,'2026-06-08 09:53:15'),(15,23,'Leave request awaiting review','Blessing Barbra has a leave request awaiting Director review.','index.php?route=approvals',0,'2026-06-08 10:01:58'),(16,26,'Leave request updated','Your leave request moved to Director review.','index.php?route=leave%2Fview&id=10',0,'2026-06-08 10:01:58'),(17,24,'Leave request awaiting review','Blessing Barbra has a leave request awaiting HR review.','index.php?route=approvals',0,'2026-06-08 10:06:44'),(18,26,'Leave request updated','Your leave request moved to HR review.','index.php?route=leave%2Fview&id=10',0,'2026-06-08 10:06:44'),(19,26,'Leave request approved','Your leave request has received final approval.','index.php?route=leave%2Fview&id=10',0,'2026-06-08 10:07:30'),(21,1,'Account request awaiting ICT approval','Benard Omanyala submitted a new account request.','index.php?route=admin%2Faccount-requests',1,'2026-06-09 12:50:14'),(22,1,'Account request awaiting ICT approval','Victor Wafula submitted a new account request.','index.php?route=admin%2Faccount-requests',1,'2026-06-09 13:33:13'),(23,1,'Account request awaiting ICT approval','Esau Wanzala submitted a new account request.','index.php?route=admin%2Faccount-requests',1,'2026-06-09 14:28:40');
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
  `gender` enum('male','female') DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('admin','employee','supervisor','hr','director') NOT NULL DEFAULT 'employee',
  `phone` varchar(30) DEFAULT NULL,
  `profile_photo_path` varchar(255) DEFAULT NULL,
  `employment_document_path` varchar(255) DEFAULT NULL,
  `status` enum('pending','active','inactive','rejected') NOT NULL DEFAULT 'pending',
  `last_login_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `national_id` (`national_id`)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Evans Amos','evansamos702@gmail.com','39649455',NULL,'pbkdf2_sha256$120000$88b6d0efcff4336e6cd830626b2f16ff$9f67c27a5a2737b539600cc1684524a2566a9b29ea698a6914e2425fa096abe1','admin','0708471884','profile-4-20260605075047-04b1c931e7e7.jpg',NULL,'active','2026-06-10 16:08:09','2026-06-03 09:04:44','2026-06-10 13:08:09'),(14,'seinlus nyongesa','seinlusnyongesa@gmail.com','36955967',NULL,'pbkdf2_sha256$120000$c0fd46fb95dde88340406352f942a4f7$a216c74acc29af8b987aac64ad55968d2a9e2a80895cb9e447340c69352cf5a4','employee','0700547482',NULL,NULL,'active','2026-06-08 09:26:53','2026-06-08 06:24:56','2026-06-08 06:26:53'),(22,'Mophat Oruma','mophat123@gmail.com','34567689',NULL,'pbkdf2_sha256$120000$8f97893ac43686aed1b17e5e42300010$72d99ab67c15e2d4f26007459fc05c71d3b29201b8ce1209184289c80b0e38a2','supervisor','0726697863',NULL,NULL,'active','2026-06-08 16:24:10','2026-06-08 07:32:05','2026-06-08 13:24:10'),(23,'Newton Omang\'a','newton123@gmail.com','43568798',NULL,'pbkdf2_sha256$120000$8ea1cab60b397f2c7a285b9f024dc92d$7323842adfe3db3becad01d77f738104d54af3972b80917533fa2f6bafe25f26','director','0726697864',NULL,NULL,'active','2026-06-08 13:06:32','2026-06-08 07:34:46','2026-06-08 10:06:32'),(24,'Brendah Auma','brenda123@gmail.com','56437898',NULL,'pbkdf2_sha256$120000$77c96f37138b3785aa4397e846e22467$5d48027ec8eafe7e98e97458ffea6a55551c1e5348f13aad6fbb7507e81ee869','hr','0726697861',NULL,NULL,'active','2026-06-08 13:07:23','2026-06-08 07:37:08','2026-06-08 10:07:23'),(25,'test040','test123@gmail.com','546766753',NULL,'pbkdf2_sha256$120000$4156ae839649a50aca81622da64cf48e$caebe3e98c1e2911623405599b1ef2f8278e8bb5158174f36c3ae76db5475824','employee','0726697865',NULL,NULL,'active','2026-06-08 12:51:02','2026-06-08 09:14:57','2026-06-08 09:51:02'),(26,'Blessing Barbra','barbrablessing9@gmail.com','34563546',NULL,'pbkdf2_sha256$120000$caa4d4276d0d5f71cc3d8e510d774388$9fa985e081a8297dd95572282710ced7c00c9889dd16d78b2f4c1f4126f85a45','employee','0740471317',NULL,NULL,'active','2026-06-08 12:51:29','2026-06-08 09:27:55','2026-06-08 09:51:29'),(27,'Benard Omanyala','benardkds@gmail.com','43625465',NULL,'pbkdf2_sha256$120000$d86e901293cdaead998fcd432fd4a294$3ef8fc350dcb73b522fdffd7444583e5dac2987246a16ef35360b812f4ead1de','supervisor','0740471315',NULL,NULL,'active','2026-06-10 15:25:16','2026-06-09 12:50:13','2026-06-10 12:25:16'),(29,'Victor Wafula','victorr123@gmail.com','56437890',NULL,'pbkdf2_sha256$120000$d679fd45316d20c16b9ceb20c381004d$66d05588438657be21ab376a95917d2d2d602bd693bedf3f7c788546cb486fe5','employee','0726697865',NULL,'employment-20260609163312-0d86984353fa35c7.pdf','pending',NULL,'2026-06-09 13:33:13',NULL),(30,'Esau Wanzala','wanzallaesau@gmail.com','56437850',NULL,'pbkdf2_sha256$120000$242424a6b821c5803943e636435a77d1$e2fa3512b8fc13431e7394f993c2eb1c4f8301e7b057f5bd6f5cec685e43ecfc','employee','0726697809',NULL,'employment-20260609172840-4dc62e915061fb98.pdf','pending',NULL,'2026-06-09 14:28:40',NULL);
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

-- Dump completed on 2026-06-10 17:02:17
