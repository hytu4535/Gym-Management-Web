-- MySQL dump 10.13  Distrib 8.0.41, for Win64 (x86_64)
--
-- Host: 127.0.0.1    Database: gym_management
-- ------------------------------------------------------
-- Server version	8.0.41

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
-- Table structure for table `addresses`
--

DROP TABLE IF EXISTS `addresses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `addresses` (
  `id` int NOT NULL AUTO_INCREMENT,
  `member_id` int NOT NULL,
  `type` enum('home','work','other') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'home',
  `full_address` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `city` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `district` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ward` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_default` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `member_id` (`member_id`),
  CONSTRAINT `addresses_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=52 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `addresses`
--

LOCK TABLES `addresses` WRITE;
/*!40000 ALTER TABLE `addresses` DISABLE KEYS */;
INSERT INTO `addresses` VALUES (1,13,'home','18/20 Phan Văn Trị, P5, TP.HCM','TP. HCM','Quận 5',NULL,1),(11,1,'home','45 Nguyễn Trãi, Phường 2','Hồ Chí Minh','Quận 5',NULL,1),(12,2,'home','78 Trần Hưng Đạo','Hà Nội','Hoàn Kiếm',NULL,0),(13,2,'home','12 Nguyễn Chí Thanh','Hà Nội','Đống Đa',NULL,0),(14,3,'home','56 Lý Thường Kiệt','Đà Nẵng','Hải Châu',NULL,1),(15,3,'home','90 Nguyễn Văn Linh','Đà Nẵng','Thanh Khê',NULL,0),(26,2,'home','18/16A Võ Văn Kiệt, Quận 2, TPHCM','TP.HCM','Quận 2',NULL,1),(27,2,'home','56656','TP. HCM','Quận 5',NULL,0),(28,18,'home','1','1','1',NULL,0),(30,19,'home','z','z','z',NULL,0),(31,19,'home','a','a','a',NULL,0),(33,3,'home','zzzzzzz','aaa','aaaaa',NULL,0),(36,1,'home','zxvxzvxzzvxzzzvxz','xzvzvxxzvzxv','zxvzxvzxvxzvxzvz',NULL,0),(37,18,'home','zzzzzzzzzzzzzzzzz','zzzzzzzzzzz','zzzzzzzzzzzzzzz',NULL,1),(38,19,'home','aa','aa','aa',NULL,1),(39,19,'home','a','a','a',NULL,0),(40,19,'home','aaaaa','a','aa',NULL,0),(41,25,'home',';llllllllllll','llllllllllllllll','llllllllllllll',NULL,1),(42,25,'home','fffffffff','fffffffff','fffffffffff',NULL,0),(43,25,'home','vvvvvvvvvvv','vvv','vv',NULL,0),(44,26,'home','aaaaa','aaaaaaaa','aaaaaaa',NULL,1),(45,27,'home','s','s','s',NULL,1),(46,32,'home','zddd334','zddd','zdddd',NULL,0),(48,32,'home','gvrfv','vfgvfg','vfgvfgv',NULL,0),(49,32,'work','aa','Thành phố Hồ Chí Minh','Quận 5','Phường 2',1),(50,35,'home','zz','Tỉnh Phú Thọ','Huyện Cẩm Khê','Xã Phú Khê',1),(51,36,'home','z','z','z',NULL,1);
/*!40000 ALTER TABLE `addresses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bmi_devices`
--

DROP TABLE IF EXISTS `bmi_devices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `bmi_devices` (
  `id` int NOT NULL AUTO_INCREMENT,
  `device_code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Mã máy đo',
  `location` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Vị trí đặt máy',
  `status` enum('active','inactive','maintenance') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Máy đo BMI';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bmi_devices`
--

LOCK TABLES `bmi_devices` WRITE;
/*!40000 ALTER TABLE `bmi_devices` DISABLE KEYS */;
INSERT INTO `bmi_devices` VALUES (2,'BMI - 07','Tầng 1 - Khu C','active','2026-02-15 11:56:16'),(3,'BMI - 01','Tầng 2','inactive','2026-02-15 11:56:30'),(8,'BMI - 08','Tầng 1 - Khu B','active','2026-03-29 16:23:52');
/*!40000 ALTER TABLE `bmi_devices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bmi_measurements`
--

DROP TABLE IF EXISTS `bmi_measurements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `bmi_measurements` (
  `id` int NOT NULL AUTO_INCREMENT,
  `member_id` int NOT NULL,
  `device_id` int DEFAULT NULL,
  `height` decimal(5,2) NOT NULL COMMENT 'Chiều cao (cm)',
  `weight` decimal(5,2) NOT NULL COMMENT 'Cân nặng (kg)',
  `bmi` decimal(5,2) NOT NULL COMMENT 'Chỉ số BMI',
  `body_type` enum('gay','binh thuong','thua can','beo phi') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Phân loại thể trạng',
  `measured_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_bmi_device` (`device_id`),
  KEY `idx_bmi_member` (`member_id`),
  KEY `idx_bmi_date` (`measured_at`),
  CONSTRAINT `fk_bmi_device` FOREIGN KEY (`device_id`) REFERENCES `bmi_devices` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_bmi_member` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Lịch sử đo BMI của hội viên';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bmi_measurements`
--

LOCK TABLES `bmi_measurements` WRITE;
/*!40000 ALTER TABLE `bmi_measurements` DISABLE KEYS */;
INSERT INTO `bmi_measurements` VALUES (4,3,2,165.00,70.00,25.71,'thua can','2026-02-15 11:57:49'),(8,2,3,180.00,70.00,21.60,'binh thuong','2026-03-07 23:10:55'),(14,3,2,175.00,75.00,24.49,'binh thuong','2026-03-29 16:24:18'),(15,1,2,175.00,100.00,32.65,'beo phi','2026-03-29 16:24:39'),(17,36,NULL,70.00,40.00,81.63,'beo phi','2026-03-30 17:24:51');
/*!40000 ALTER TABLE `bmi_measurements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cart_items`
--

DROP TABLE IF EXISTS `cart_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cart_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `cart_id` int NOT NULL,
  `item_type` enum('product','package','service','class') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `item_id` int NOT NULL,
  `quantity` int DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_cart_item` (`cart_id`,`item_type`,`item_id`),
  KEY `cart_id` (`cart_id`),
  CONSTRAINT `cart_items_ibfk_1` FOREIGN KEY (`cart_id`) REFERENCES `carts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=156 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cart_items`
--

LOCK TABLES `cart_items` WRITE;
/*!40000 ALTER TABLE `cart_items` DISABLE KEYS */;
INSERT INTO `cart_items` VALUES (29,1,'service',3,1,'2026-02-20 00:08:34'),(31,2,'package',1,1,'2026-02-20 00:08:34'),(32,3,'service',4,2,'2026-02-20 00:08:34'),(34,4,'package',2,1,'2026-02-20 00:08:34'),(35,4,'product',1,1,'2026-02-20 00:08:34'),(36,4,'service',3,1,'2026-02-20 00:08:34'),(39,2,'product',19,1,'2026-03-07 21:59:45'),(40,5,'product',19,1,'2026-03-07 22:02:19'),(41,6,'product',19,1,'2026-03-07 22:04:57'),(42,7,'product',19,1,'2026-03-07 22:07:58'),(43,8,'product',19,10,'2026-03-07 22:08:29'),(44,9,'product',1,10,'2026-03-07 23:12:29'),(45,10,'product',2,1,'2026-03-07 23:15:03'),(46,11,'product',2,1,'2026-03-07 23:15:24'),(47,12,'product',9,1,'2026-03-07 23:16:25'),(48,1,'product',19,5,'2026-03-11 09:58:06'),(51,13,'product',19,3,'2026-03-11 10:13:37'),(52,14,'product',19,2,'2026-03-11 10:27:27'),(54,3,'product',19,2,'2026-03-11 10:32:57'),(59,15,'package',3,1,'2026-03-18 11:42:59'),(60,15,'service',4,1,'2026-03-18 11:43:35'),(61,15,'product',19,1,'2026-03-18 11:43:56'),(63,16,'product',13,1,'2026-03-22 19:59:16'),(64,17,'package',2,1,'2026-03-22 20:18:51'),(65,18,'product',18,2,'2026-03-22 20:45:33'),(66,19,'product',18,1,'2026-03-22 21:13:19'),(67,20,'product',17,1,'2026-03-22 21:15:18'),(68,21,'product',17,1,'2026-03-22 21:17:11'),(69,22,'product',1,5,'2026-03-22 21:22:33'),(70,23,'product',1,1,'2026-03-22 21:26:01'),(72,23,'package',4,1,'2026-03-24 13:10:32'),(75,24,'product',19,1,'2026-03-25 15:09:24'),(76,25,'product',19,2,'2026-03-25 15:10:42'),(78,27,'product',19,6,'2026-03-25 15:28:50'),(79,28,'product',19,1,'2026-03-25 15:37:44'),(80,29,'product',19,1,'2026-03-25 15:39:33'),(81,30,'product',19,2,'2026-03-25 21:24:22'),(82,30,'package',7,1,'2026-03-25 21:50:26'),(83,31,'product',19,2,'2026-03-26 07:45:19'),(84,31,'product',1,1,'2026-03-26 08:39:37'),(85,32,'product',19,1,'2026-03-27 04:33:00'),(86,33,'product',19,2,'2026-03-27 04:40:52'),(87,34,'product',19,3,'2026-03-27 04:53:20'),(88,35,'product',19,1,'2026-03-27 06:23:29'),(89,36,'product',18,1,'2026-03-27 06:28:51'),(90,37,'product',22,1,'2026-03-27 22:18:33'),(94,40,'service',5,1,'2026-03-28 16:38:10'),(95,40,'product',18,1,'2026-03-28 16:38:36'),(96,41,'product',22,1,'2026-03-28 16:47:30'),(97,42,'product',19,1,'2026-03-28 16:48:34'),(98,43,'product',19,1,'2026-03-28 16:51:00'),(99,44,'product',19,1,'2026-03-28 16:51:20'),(101,45,'product',19,1,'2026-03-28 17:07:32'),(102,45,'product',18,1,'2026-03-28 17:07:35'),(103,46,'product',22,1,'2026-03-29 05:40:57'),(104,46,'package',3,1,'2026-03-29 05:42:00'),(105,47,'class',1,1,'2026-03-29 06:02:52'),(106,48,'class',2,1,'2026-03-29 06:03:59'),(108,49,'class',2,1,'2026-03-29 06:18:53'),(109,50,'product',22,1,'2026-03-29 06:28:00'),(110,51,'product',19,1,'2026-03-29 06:28:22'),(111,52,'product',19,1,'2026-03-29 06:29:23'),(112,53,'product',19,1,'2026-03-29 06:29:50'),(113,54,'product',19,1,'2026-03-29 06:31:08'),(114,55,'product',19,1,'2026-03-29 06:31:51'),(115,56,'product',19,1,'2026-03-29 06:34:31'),(116,57,'product',19,1,'2026-03-29 06:34:54'),(117,58,'product',19,1,'2026-03-29 06:36:12'),(118,59,'product',19,2,'2026-03-29 07:48:25'),(120,60,'product',19,2,'2026-03-29 08:24:35'),(121,61,'product',19,2,'2026-03-29 08:27:51'),(122,62,'product',19,2,'2026-03-29 08:32:16'),(123,63,'product',19,2,'2026-03-29 08:32:34'),(124,64,'product',19,2,'2026-03-29 08:34:56'),(125,65,'product',19,2,'2026-03-29 08:37:17'),(126,66,'product',19,2,'2026-03-29 08:42:04'),(127,67,'product',19,2,'2026-03-29 08:46:33'),(128,68,'product',19,2,'2026-03-29 08:48:51'),(129,69,'product',19,2,'2026-03-29 08:50:44'),(130,70,'product',19,2,'2026-03-29 08:53:14'),(131,71,'product',19,2,'2026-03-29 08:56:39'),(132,72,'product',19,2,'2026-03-29 08:58:14'),(133,73,'product',19,2,'2026-03-29 08:59:30'),(134,74,'product',19,2,'2026-03-29 09:00:55'),(135,75,'product',19,2,'2026-03-29 09:16:58'),(136,76,'product',19,1,'2026-03-29 09:18:31'),(137,77,'product',19,10,'2026-03-29 09:21:17'),(138,78,'product',19,30,'2026-03-29 09:21:44'),(139,79,'product',19,30,'2026-03-29 09:27:38'),(141,80,'product',19,3,'2026-03-29 09:31:51'),(142,81,'product',19,2,'2026-03-29 14:03:22'),(143,82,'product',19,3,'2026-03-29 14:18:51'),(144,83,'product',19,3,'2026-03-29 14:21:11'),(145,83,'product',18,1,'2026-03-29 16:34:18'),(146,84,'package',2,1,'2026-03-29 17:00:49'),(147,85,'product',19,5,'2026-03-30 09:00:28'),(148,86,'product',19,1,'2026-03-30 09:10:41'),(149,87,'product',19,3,'2026-03-30 09:57:03'),(150,88,'product',19,5,'2026-03-30 10:14:58'),(151,89,'product',4,11,'2026-03-30 10:31:57'),(152,90,'package',11,1,'2026-03-30 15:36:25'),(153,91,'package',12,1,'2026-03-30 15:37:38'),(154,92,'class',7,1,'2026-03-30 15:41:47'),(155,93,'class',9,1,'2026-03-31 09:26:08');
/*!40000 ALTER TABLE `cart_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `carts`
--

DROP TABLE IF EXISTS `carts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `carts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `member_id` int NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `status` enum('active','checked_out') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  PRIMARY KEY (`id`),
  KEY `member_id` (`member_id`),
  CONSTRAINT `carts_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=94 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `carts`
--

LOCK TABLES `carts` WRITE;
/*!40000 ALTER TABLE `carts` DISABLE KEYS */;
INSERT INTO `carts` VALUES (1,1,'2026-02-20 07:08:08','checked_out'),(2,2,'2026-02-20 07:08:08','checked_out'),(3,3,'2026-02-20 07:08:08','checked_out'),(4,13,'2026-02-20 07:08:08','active'),(5,2,'2026-03-08 05:02:19','checked_out'),(6,2,'2026-03-08 05:04:57','checked_out'),(7,2,'2026-03-08 05:07:58','checked_out'),(8,2,'2026-03-08 05:08:29','checked_out'),(9,2,'2026-03-08 06:12:29','checked_out'),(10,2,'2026-03-08 06:15:03','checked_out'),(11,2,'2026-03-08 06:15:24','checked_out'),(12,2,'2026-03-08 06:16:08','checked_out'),(13,1,'2026-03-11 17:02:11','checked_out'),(14,1,'2026-03-11 17:27:27','checked_out'),(15,1,'2026-03-11 17:31:20','checked_out'),(16,1,'2026-03-22 14:54:17','checked_out'),(17,1,'2026-03-23 03:18:51','checked_out'),(18,1,'2026-03-23 03:45:33','checked_out'),(19,1,'2026-03-23 04:13:19','checked_out'),(20,1,'2026-03-23 04:15:18','checked_out'),(21,1,'2026-03-23 04:17:11','checked_out'),(22,1,'2026-03-23 04:22:33','checked_out'),(23,1,'2026-03-23 04:26:01','checked_out'),(24,1,'2026-03-25 20:41:25','checked_out'),(25,1,'2026-03-25 22:10:42','checked_out'),(26,1,'2026-03-25 22:12:24','active'),(27,18,'2026-03-25 22:28:50','checked_out'),(28,18,'2026-03-25 22:37:44','checked_out'),(29,18,'2026-03-25 22:39:33','checked_out'),(30,19,'2026-03-26 04:24:22','checked_out'),(31,19,'2026-03-26 14:45:19','checked_out'),(32,25,'2026-03-27 11:33:00','checked_out'),(33,25,'2026-03-27 11:40:52','checked_out'),(34,25,'2026-03-27 11:53:20','checked_out'),(35,25,'2026-03-27 13:23:29','checked_out'),(36,25,'2026-03-27 13:28:51','checked_out'),(37,25,'2026-03-28 05:18:33','active'),(38,22,'2026-03-28 06:03:00','active'),(39,18,'2026-03-28 23:29:52','active'),(40,26,'2026-03-28 23:38:07','checked_out'),(41,26,'2026-03-28 23:47:30','checked_out'),(42,26,'2026-03-28 23:48:34','checked_out'),(43,26,'2026-03-28 23:51:00','checked_out'),(44,26,'2026-03-28 23:51:20','checked_out'),(45,26,'2026-03-28 23:57:30','active'),(46,27,'2026-03-29 12:40:57','checked_out'),(47,27,'2026-03-29 13:02:52','checked_out'),(48,27,'2026-03-29 13:03:59','checked_out'),(49,27,'2026-03-29 13:18:17','checked_out'),(50,27,'2026-03-29 13:28:00','checked_out'),(51,27,'2026-03-29 13:28:22','checked_out'),(52,27,'2026-03-29 13:29:23','checked_out'),(53,27,'2026-03-29 13:29:50','checked_out'),(54,27,'2026-03-29 13:31:08','checked_out'),(55,27,'2026-03-29 13:31:51','checked_out'),(56,27,'2026-03-29 13:34:31','checked_out'),(57,27,'2026-03-29 13:34:54','checked_out'),(58,27,'2026-03-29 13:36:12','checked_out'),(59,27,'2026-03-29 14:48:25','checked_out'),(60,27,'2026-03-29 15:11:14','checked_out'),(61,27,'2026-03-29 15:27:51','checked_out'),(62,27,'2026-03-29 15:32:16','checked_out'),(63,27,'2026-03-29 15:32:34','checked_out'),(64,27,'2026-03-29 15:34:56','checked_out'),(65,27,'2026-03-29 15:37:17','checked_out'),(66,27,'2026-03-29 15:42:04','checked_out'),(67,27,'2026-03-29 15:46:33','checked_out'),(68,27,'2026-03-29 15:48:51','checked_out'),(69,27,'2026-03-29 15:50:44','checked_out'),(70,27,'2026-03-29 15:53:14','checked_out'),(71,27,'2026-03-29 15:56:39','checked_out'),(72,27,'2026-03-29 15:58:14','checked_out'),(73,29,'2026-03-29 15:59:30','active'),(74,27,'2026-03-29 16:00:55','checked_out'),(75,27,'2026-03-29 16:16:58','checked_out'),(76,27,'2026-03-29 16:18:31','active'),(77,32,'2026-03-29 16:21:17','checked_out'),(78,32,'2026-03-29 16:21:44','checked_out'),(79,32,'2026-03-29 16:27:38','checked_out'),(80,32,'2026-03-29 16:30:00','checked_out'),(81,32,'2026-03-29 21:03:21','checked_out'),(82,32,'2026-03-29 21:18:51','checked_out'),(83,32,'2026-03-29 21:21:11','checked_out'),(84,32,'2026-03-30 00:00:49','checked_out'),(85,35,'2026-03-30 16:00:28','checked_out'),(86,35,'2026-03-30 16:10:41','checked_out'),(87,36,'2026-03-30 16:57:03','checked_out'),(88,36,'2026-03-30 17:14:58','checked_out'),(89,36,'2026-03-30 17:31:57','checked_out'),(90,36,'2026-03-30 22:36:25','checked_out'),(91,36,'2026-03-30 22:37:38','checked_out'),(92,36,'2026-03-30 22:41:47','checked_out'),(93,36,'2026-03-31 16:26:08','checked_out');
/*!40000 ALTER TABLE `carts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `categories` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `status` enum('active','inactive') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES (1,'test','test','active'),(2,'Whey Protein','Các loại whey protein tăng cơ, phục hồi cơ bắp.','active'),(4,'Nước uống thể thao','Nước điện giải và đồ uống bổ sung năng lượng.','active'),(5,'Phụ kiện tập gym','Găng tay, đai lưng, dây kéo, bình nước tập gym.','active'),(6,'Thiết bị tập cá nhân','Dụng cụ tập tại nhà như dây kháng lực, tạ tay.','active'),(7,'Quần áo thể thao','Trang phục tập gym cho nam và nữ.','active'),(8,'Combo khuyến mãi','Các gói sản phẩm bán theo combo ưu đãi.','inactive'),(9,'Hàng nhập khẩu','Sản phẩm nhập khẩu chính hãng từ Mỹ và châu Âu.','active'),(10,'Sản phẩm giảm cân','Các sản phẩm hỗ trợ giảm mỡ, kiểm soát cân nặng.','active'),(11,'Sản phẩm tăng cân','Mass gainer và thực phẩm hỗ trợ tăng cân.','active'),(12,'Thực phẩm bổ sung','Vitamin, BCAA, Creatine và các sản phẩm hỗ trợ tập luyện.','active'),(16,'zzzzzzzzz','zzzzzzzzzzzzzzzzzzz','active');
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `class_registrations`
--

DROP TABLE IF EXISTS `class_registrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `class_registrations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `member_id` int NOT NULL,
  `class_id` int NOT NULL,
  `registered_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `status` enum('active','cancelled') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_reg` (`member_id`,`class_id`),
  KEY `idx_reg_member` (`member_id`),
  KEY `idx_reg_class` (`class_id`),
  CONSTRAINT `fk_reg_class` FOREIGN KEY (`class_id`) REFERENCES `class_schedules` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_reg_member` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Đăng ký lớp tập nhóm';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `class_registrations`
--

LOCK TABLES `class_registrations` WRITE;
/*!40000 ALTER TABLE `class_registrations` DISABLE KEYS */;
INSERT INTO `class_registrations` VALUES (1,1,1,'2026-03-23 04:35:01','cancelled'),(2,2,2,'2026-03-02 14:15:00','cancelled'),(3,3,3,'2026-03-14 13:32:28','cancelled'),(4,13,4,'2026-03-04 16:20:00','active'),(5,1,5,'2026-03-11 16:35:49','cancelled'),(6,1,2,'2026-03-27 00:10:17','cancelled'),(7,1,3,'2026-03-27 00:10:53','active'),(8,3,2,'2026-03-14 13:31:57','cancelled'),(9,3,5,'2026-03-14 13:33:03','active'),(10,19,2,'2026-03-26 16:59:04','active'),(11,25,3,'2026-03-27 00:27:32','active'),(12,22,2,'2026-03-28 06:00:44','active'),(13,27,1,'2026-03-29 13:02:52','active'),(14,27,2,'2026-03-29 13:18:53','active'),(15,36,7,'2026-03-30 22:41:47','active'),(16,36,9,'2026-03-31 16:26:08','active');
/*!40000 ALTER TABLE `class_registrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `class_schedules`
--

DROP TABLE IF EXISTS `class_schedules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `class_schedules` (
  `id` int NOT NULL AUTO_INCREMENT,
  `class_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Tên lớp tập',
  `class_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'cardio, yoga, strength, hiit, boxing...',
  `trainer_id` int DEFAULT NULL,
  `schedule_start_time` time DEFAULT NULL COMMENT 'Giờ bắt đầu',
  `schedule_end_time` time DEFAULT NULL COMMENT 'Giờ kết thúc',
  `schedule_days` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'VD: Thứ 2, Thứ 4, Thứ 6',
  `price_per_session` decimal(15,2) DEFAULT '0.00' COMMENT 'Gia moi buoi tap',
  `capacity` int DEFAULT '20' COMMENT 'Sức chứa tối đa',
  `enrolled_count` int DEFAULT '0' COMMENT 'Số người đã đăng ký',
  `room` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Phòng tập',
  `status` enum('active','inactive') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_class_trainer` (`trainer_id`),
  KEY `idx_class_status` (`status`),
  CONSTRAINT `fk_class_trainer` FOREIGN KEY (`trainer_id`) REFERENCES `trainers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Lớp tập nhóm';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `class_schedules`
--

LOCK TABLES `class_schedules` WRITE;
/*!40000 ALTER TABLE `class_schedules` DISABLE KEYS */;
INSERT INTO `class_schedules` VALUES (1,'Yoga Sáng Năng Động','yoga',1,'08:00:00','11:00:00','Thứ 2, Thứ 4, Thứ 6',50000.00,25,1,'Phòng A1','active','2026-03-07 22:44:36'),(2,'Cardio Giảm Cân','cardio',2,'14:00:00','16:00:00','Thứ 3, Thứ 5, Thứ 7',70000.00,30,3,'Phòng B2','active','2026-03-07 22:44:36'),(3,'Boxing Cơ Bản','boxing',1,'08:00:00','10:00:00','Thứ 2, Thứ 5',60000.00,15,2,'Phòng C1','active','2026-03-07 22:44:36'),(4,'HIIT Đốt Mỡ','hiit',2,'15:00:00','17:00:00','Thứ 3, Thứ 6',50000.00,20,0,'Phòng B1','active','2026-03-07 22:44:36'),(5,'Strength Training','strength',1,'10:00:00','12:00:00','Thứ 4, Thứ 7, Chủ Nhật',40000.00,18,1,'Phòng Gym','active','2026-03-07 22:44:36'),(7,'zzzzzz','zzzzzzzzzz',1,'01:41:00','14:41:00','Thứ 2, Thứ 4, Thứ 6',80000.00,20,1,'a2','inactive','2026-03-30 15:41:28'),(9,'ttk6','boxing',10,'19:24:00','21:24:00','Thứ 2, Thứ 4, Thứ 6',30000.00,20,1,'A2','inactive','2026-03-31 09:24:28');
/*!40000 ALTER TABLE `class_schedules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contact_messages`
--

DROP TABLE IF EXISTS `contact_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `contact_messages` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `member_id` int DEFAULT NULL,
  `full_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('new','read','closed') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'new',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_contact_user` (`user_id`),
  KEY `idx_contact_created` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contact_messages`
--

LOCK TABLES `contact_messages` WRITE;
/*!40000 ALTER TABLE `contact_messages` DISABLE KEYS */;
INSERT INTO `contact_messages` VALUES (1,1,1,'Trương Trung Kiên','kien@gmail.com','0912345678','ghggggggggggggggggggggggg','read','2026-03-25 14:23:34'),(2,27,19,'k','k@gmail.com','0786026878','jjj','read','2026-03-25 23:08:30');
/*!40000 ALTER TABLE `contact_messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `departments`
--

DROP TABLE IF EXISTS `departments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `departments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `departments`
--

LOCK TABLES `departments` WRITE;
/*!40000 ALTER TABLE `departments` DISABLE KEYS */;
INSERT INTO `departments` VALUES (1,'Lễ tân'),(2,'Huấn luyện viên'),(3,'Quản lý'),(5,'Kinh doanh & Chăm sóc khách hàng'),(7,'Tài chính'),(8,'Kỹ thuật'),(9,'Vận hành');
/*!40000 ALTER TABLE `departments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `equipment`
--

DROP TABLE IF EXISTS `equipment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `equipment` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` int DEFAULT '1',
  `status` enum('dang su dung','bao tri','ngung hoat dong') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'dang su dung',
  PRIMARY KEY (`id`),
  KEY `idx_equipment_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Thiết bị phòng Gym';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `equipment`
--

LOCK TABLES `equipment` WRITE;
/*!40000 ALTER TABLE `equipment` DISABLE KEYS */;
INSERT INTO `equipment` VALUES (1,'a',12,'dang su dung'),(2,'dddd',5,'bao tri');
/*!40000 ALTER TABLE `equipment` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `equipment_maintenance`
--

DROP TABLE IF EXISTS `equipment_maintenance`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `equipment_maintenance` (
  `id` int NOT NULL AUTO_INCREMENT,
  `equipment_id` int NOT NULL,
  `maintenance_date` date NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `status` enum('cho_bao_tri','dang_bao_tri','hoan_thanh','huy') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'dang_bao_tri',
  PRIMARY KEY (`id`),
  KEY `idx_equipment_maintenance_equipment_id` (`equipment_id`),
  KEY `idx_equipment_maintenance_status` (`status`),
  CONSTRAINT `fk_equipment_maintenance_equipment` FOREIGN KEY (`equipment_id`) REFERENCES `equipment` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Lịch sử bảo trì thiết bị';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `equipment_maintenance`
--

LOCK TABLES `equipment_maintenance` WRITE;
/*!40000 ALTER TABLE `equipment_maintenance` DISABLE KEYS */;
INSERT INTO `equipment_maintenance` VALUES (2,2,'2026-03-17','ggg','dang_bao_tri');
/*!40000 ALTER TABLE `equipment_maintenance` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `feedback`
--

DROP TABLE IF EXISTS `feedback`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `feedback` (
  `id` int NOT NULL AUTO_INCREMENT,
  `member_id` int NOT NULL,
  `responded_by` int DEFAULT NULL,
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `rating` int DEFAULT NULL,
  `status` enum('new','processing','processed','closed') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'new',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_feedback_member_id` (`member_id`),
  KEY `idx_feedback_status` (`status`),
  KEY `idx_feedback_created_at` (`created_at`),
  KEY `idx_feedback_responded_by` (`responded_by`),
  CONSTRAINT `fk_feedback_member` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_feedback_responded_by` FOREIGN KEY (`responded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `chk_feedback_rating` CHECK (((`rating` >= 1) and (`rating` <= 5)))
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Phản hồi từ hội viên';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `feedback`
--

LOCK TABLES `feedback` WRITE;
/*!40000 ALTER TABLE `feedback` DISABLE KEYS */;
INSERT INTO `feedback` VALUES (1,1,NULL,'HLV Kiên ko biết dạy, tập tay mà bắt nâng tạ bằng chân?',1,'new','2026-03-15 11:37:55');
/*!40000 ALTER TABLE `feedback` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `import_details`
--

DROP TABLE IF EXISTS `import_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `import_details` (
  `id` int NOT NULL AUTO_INCREMENT,
  `import_id` int NOT NULL,
  `equipment_id` int DEFAULT NULL COMMENT 'Link tới máy móc (nếu có)',
  `product_id` int DEFAULT NULL COMMENT 'Link tới sản phẩm Whey/Nước (nếu có)',
  `quantity` int NOT NULL DEFAULT '1',
  `import_price` decimal(15,2) NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`id`),
  KEY `fk_detail_import` (`import_id`),
  KEY `fk_detail_equipment` (`equipment_id`),
  KEY `fk_detail_product` (`product_id`),
  CONSTRAINT `fk_detail_equipment` FOREIGN KEY (`equipment_id`) REFERENCES `equipment` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_detail_import` FOREIGN KEY (`import_id`) REFERENCES `import_slips` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_detail_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `import_details`
--

LOCK TABLES `import_details` WRITE;
/*!40000 ALTER TABLE `import_details` DISABLE KEYS */;
INSERT INTO `import_details` VALUES (1,1,1,NULL,2,26000000.00),(2,2,NULL,1,10,1500000.00),(3,4,NULL,19,10,800001.00),(4,6,NULL,18,15,500001.00),(5,6,NULL,13,5,185001.00),(6,7,NULL,19,12,800000.00),(7,8,NULL,19,100,1000000.00),(8,9,NULL,19,30,1000000.00),(9,10,NULL,4,40,500000.00),(12,13,NULL,16,11,111111.00);
/*!40000 ALTER TABLE `import_details` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `import_slips`
--

DROP TABLE IF EXISTS `import_slips`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `import_slips` (
  `id` int NOT NULL AUTO_INCREMENT,
  `staff_id` int NOT NULL,
  `supplier_id` int NOT NULL,
  `total_amount` decimal(15,2) DEFAULT '0.00',
  `import_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `note` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `status` enum('Đã nhập','Đang chờ duyệt','Đã hủy') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'Đang chờ duyệt',
  PRIMARY KEY (`id`),
  KEY `fk_import_staff` (`staff_id`),
  KEY `fk_import_supplier` (`supplier_id`),
  CONSTRAINT `fk_import_staff` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_import_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `import_slips`
--

LOCK TABLES `import_slips` WRITE;
/*!40000 ALTER TABLE `import_slips` DISABLE KEYS */;
INSERT INTO `import_slips` VALUES (1,1,1,52000000.00,'2024-02-01 08:30:00','Nhập máy chạy bộ mới','Đã nhập'),(2,1,2,15000000.00,'2024-02-02 09:00:00','Nhập bổ sung Whey','Đã nhập'),(3,5,1,80000000.00,'2026-03-02 18:51:00','h','Đã nhập'),(4,2,2,8000010.00,'2026-03-11 17:37:00','lllllll','Đã nhập'),(5,3,1,100000.00,'2026-03-23 02:53:00','ssssssssss','Đang chờ duyệt'),(6,5,1,8425020.00,'2026-03-23 02:57:00','fffffffffffffff','Đã nhập'),(7,1,23,9600000.00,'2026-03-25 20:51:00','aaaaaaaaaaaaaaa','Đã nhập'),(8,7,24,100000000.00,'2026-03-29 15:35:00','aaaaaaaaaaaaaaaaaaaaaaaaaa','Đã nhập'),(9,2,24,30000000.00,'2026-03-29 21:22:00','','Đã nhập'),(10,3,23,20000000.00,'2026-03-30 17:34:00','aaaaaaaaaaaaaaaaaa','Đã nhập'),(13,13,24,1222221.00,'2026-03-31 00:31:00','aaaaaa','Đang chờ duyệt');
/*!40000 ALTER TABLE `import_slips` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `login_attempts`
--

DROP TABLE IF EXISTS `login_attempts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `login_attempts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `bucket_type` enum('account','ip') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `bucket_key` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `identifier` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `attempt_count` int NOT NULL DEFAULT '0',
  `last_attempt_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `locked_until` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_bucket_key` (`bucket_key`),
  KEY `idx_bucket_type` (`bucket_type`),
  KEY `idx_locked_until` (`locked_until`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `login_attempts`
--

LOCK TABLES `login_attempts` WRITE;
/*!40000 ALTER TABLE `login_attempts` DISABLE KEYS */;
INSERT INTO `login_attempts` VALUES (1,'account','1a3747dca3105db81fffbc0c9fde8e9a03f9104ace5d8d3067ac0c919816b541','h3','::1',5,'2026-03-27 22:44:44','2026-03-27 22:59:44','2026-03-27 22:44:40','2026-03-27 22:44:44'),(3,'account','2eab3d39006d28cce39642d3b2685560373a19c9c9c7999c80442d9042a43f24','h','::1',1,'2026-03-27 22:54:09',NULL,'2026-03-27 22:54:09','2026-03-27 22:54:09'),(5,'account','a753ee47a3c446d11fff842e67f1b571710e7cef3a8fb54ceee31b5b7c6f80de','y','::1',1,'2026-03-27 22:54:27',NULL,'2026-03-27 22:54:27','2026-03-27 22:54:27'),(6,'account','be39372ff715743c9050be81e36e864873c621b65c9f1241d8ed29cf4329c573','admin2','::1',4,'2026-03-27 22:54:46',NULL,'2026-03-27 22:54:31','2026-03-27 22:54:46'),(7,'ip','477d9f64244a7f8802a7420c5f1a61284f55ad262f7211bf8d99f71ec448df14','admin2','::1',2,'2026-03-27 22:54:46',NULL,'2026-03-27 22:54:42','2026-03-27 22:54:46');
/*!40000 ALTER TABLE `login_attempts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `member_nutrition_plans`
--

DROP TABLE IF EXISTS `member_nutrition_plans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `member_nutrition_plans` (
  `id` int NOT NULL AUTO_INCREMENT,
  `member_id` int NOT NULL,
  `nutrition_plan_id` int NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('đã áp dụng','kết thúc') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'đã áp dụng',
  PRIMARY KEY (`id`),
  KEY `idx_member_nutrition_member` (`member_id`),
  KEY `idx_member_nutrition_plan` (`nutrition_plan_id`),
  CONSTRAINT `member_nutrition_plans_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  CONSTRAINT `member_nutrition_plans_ibfk_2` FOREIGN KEY (`nutrition_plan_id`) REFERENCES `nutrition_plans` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Hội viên đăng ký dinh dưỡng';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `member_nutrition_plans`
--

LOCK TABLES `member_nutrition_plans` WRITE;
/*!40000 ALTER TABLE `member_nutrition_plans` DISABLE KEYS */;
INSERT INTO `member_nutrition_plans` VALUES (1,2,1,'2026-02-19','2026-02-21','kết thúc'),(2,1,2,'2026-03-18','2026-03-28','kết thúc'),(3,19,1,'2026-03-26','2026-04-04','đã áp dụng');
/*!40000 ALTER TABLE `member_nutrition_plans` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `member_packages`
--

DROP TABLE IF EXISTS `member_packages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `member_packages` (
  `id` int NOT NULL AUTO_INCREMENT,
  `member_id` int NOT NULL,
  `package_id` int NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` enum('active','expired','cancelled') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  PRIMARY KEY (`id`),
  KEY `member_id` (`member_id`),
  KEY `package_id` (`package_id`),
  CONSTRAINT `member_packages_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  CONSTRAINT `member_packages_ibfk_2` FOREIGN KEY (`package_id`) REFERENCES `membership_packages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `member_packages`
--

LOCK TABLES `member_packages` WRITE;
/*!40000 ALTER TABLE `member_packages` DISABLE KEYS */;
INSERT INTO `member_packages` VALUES (1,13,1,'2025-02-06','2025-03-06','active'),(2,3,2,'2026-02-10','2026-05-10','active'),(3,1,3,'2026-03-18','2026-09-18','active'),(4,1,2,'2026-03-22','2026-06-22','active'),(5,1,4,'2026-03-25','2027-03-25','active'),(6,19,7,'2026-03-25','2026-04-25','active'),(7,27,3,'2026-03-29','2026-09-29','active'),(8,27,7,'2026-03-12','2028-03-12','active'),(9,27,7,'2026-03-12','2028-03-12','active'),(10,27,7,'2026-03-12','2028-03-12','active'),(11,32,2,'2026-03-29','2026-06-29','active'),(13,36,12,'2026-03-30','2026-04-30','active');
/*!40000 ALTER TABLE `member_packages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `member_services`
--

DROP TABLE IF EXISTS `member_services`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `member_services` (
  `id` int NOT NULL AUTO_INCREMENT,
  `member_id` int NOT NULL,
  `service_id` int NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('còn hiệu lực','đã dùng','hết hạn','bị hủy') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'còn hiệu lực',
  PRIMARY KEY (`id`),
  KEY `idx_member_services_member` (`member_id`),
  KEY `idx_member_services_service` (`service_id`),
  CONSTRAINT `member_services_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `member_services_ibfk_2` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Hội viên sử dụng dịch vụ';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `member_services`
--

LOCK TABLES `member_services` WRITE;
/*!40000 ALTER TABLE `member_services` DISABLE KEYS */;
INSERT INTO `member_services` VALUES (1,2,4,'2026-02-19','2026-02-20','đã dùng'),(2,1,4,'2026-03-18','2026-04-18','còn hiệu lực'),(3,26,5,'2026-03-28','2026-04-28','còn hiệu lực');
/*!40000 ALTER TABLE `member_services` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `member_tiers`
--

DROP TABLE IF EXISTS `member_tiers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `member_tiers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL COMMENT 'Đồng, Bạc, Vàng, Bạch Kim, Kim Cương',
  `level` int NOT NULL COMMENT 'Cấp độ 1-5',
  `min_spent` decimal(12,2) DEFAULT '0.00' COMMENT 'Số tiền tối thiểu để đạt hạng',
  `base_discount` decimal(5,2) DEFAULT '0.00' COMMENT 'Giảm giá cơ bản (%)',
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_tier_name` (`name`),
  UNIQUE KEY `uk_tier_level` (`level`)
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `member_tiers`
--

LOCK TABLES `member_tiers` WRITE;
/*!40000 ALTER TABLE `member_tiers` DISABLE KEYS */;
INSERT INTO `member_tiers` VALUES (1,'Đồng',1,0.00,0.00,'active','2026-01-26 18:51:00'),(2,'Bạc',2,3000000.00,5.00,'active','2026-01-26 18:51:00'),(3,'Vàng',3,10000000.00,10.00,'active','2026-01-26 18:51:00'),(4,'Bạch Kim',4,30000000.00,15.00,'active','2026-01-26 18:51:00'),(5,'Kim Cương',5,50000000.00,20.00,'active','2026-01-26 18:51:00'),(10,'Trung Kiên',6,100000000.00,30.00,'active','2026-03-25 22:02:59');
/*!40000 ALTER TABLE `member_tiers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `member_training_schedules`
--

DROP TABLE IF EXISTS `member_training_schedules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `member_training_schedules` (
  `id` int NOT NULL AUTO_INCREMENT,
  `member_id` int NOT NULL COMMENT 'ID hội viên',
  `trainer_id` int DEFAULT NULL COMMENT 'ID huấn luyện viên (NULL = tập tự do)',
  `training_date` datetime NOT NULL COMMENT 'Ngày giờ tập',
  `duration` int DEFAULT '60' COMMENT 'Thời lượng tập (phút)',
  `activity_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Loại hoạt động: cardio, strength, yoga, hiit, boxing...',
  `intensity` enum('thấp','trung bình','cao','rất cao') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'trung bình' COMMENT 'Cường độ tập',
  `calories_burned` int DEFAULT '0' COMMENT 'Lượng calo đốt cháy (ước tính)',
  `note` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Ghi chú của PT hoặc hội viên',
  `status` enum('dự kiến','đang tập','hoàn thành','huỷ') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'dự kiến' COMMENT 'Trạng thái buổi tập',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_member` (`member_id`),
  KEY `idx_trainer` (`trainer_id`),
  KEY `idx_training_date` (`training_date`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_mts_member` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_mts_trainer` FOREIGN KEY (`trainer_id`) REFERENCES `trainers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Lịch tập cá nhân của hội viên';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `member_training_schedules`
--

LOCK TABLES `member_training_schedules` WRITE;
/*!40000 ALTER TABLE `member_training_schedules` DISABLE KEYS */;
INSERT INTO `member_training_schedules` VALUES (1,1,1,'2026-03-10 06:00:00',60,'cardio','trung bình',450,'Tập chạy bộ và đạp xe','dự kiến','2026-03-07 22:44:36','2026-03-07 22:44:36'),(2,2,2,'2026-03-11 18:00:00',90,'strength','cao',600,'Tập tạ tay và chân','dự kiến','2026-03-07 22:44:36','2026-03-07 22:44:36'),(3,3,1,'2026-03-09 07:00:00',45,'yoga','thấp',200,'Yoga thư giãn','hoàn thành','2026-03-07 22:44:36','2026-03-07 22:44:36'),(4,13,NULL,'2026-03-12 19:00:00',60,'boxing','cao',550,'Tập tự do - luyện đấm bao cát','dự kiến','2026-03-07 22:44:36','2026-03-07 22:44:36'),(5,2,2,'2026-03-08 17:30:00',75,'hiit','rất cao',700,'HIIT cường độ cao, đốt calo','hoàn thành','2026-03-07 22:44:36','2026-03-07 22:44:36'),(6,3,2,'2026-03-17 18:00:00',60,'cardio','trung bình',0,'Lịch tạo tự động từ lớp Cardio Giảm Cân [CLASS_ID:2]','huỷ','2026-03-14 06:31:57','2026-03-14 06:32:23'),(7,3,1,'2026-03-16 19:30:00',60,'boxing','trung bình',0,'Lịch tạo tự động từ lớp Boxing Cơ Bản [CLASS_ID:3]','huỷ','2026-03-14 06:32:28','2026-03-14 06:33:00'),(8,3,1,'2026-03-15 07:00:00',60,'strength','trung bình',0,'Lịch tạo tự động từ lớp Strength Training [CLASS_ID:5]','dự kiến','2026-03-14 06:33:03','2026-03-14 06:33:03'),(9,1,1,'2026-03-30 06:00:00',60,'boxing','trung bình',0,'Lịch tạo tự động từ lớp Boxing Cơ Bản [CLASS_ID:3]','dự kiến','2026-03-19 08:39:11','2026-03-26 17:10:53'),(10,1,1,'2026-03-23 06:00:00',60,'yoga','trung bình',0,'Lịch tạo tự động từ lớp Yoga Sáng Năng Động [CLASS_ID:1]','huỷ','2026-03-22 21:35:01','2026-03-26 17:10:23'),(11,19,2,'2026-03-31 18:00:00',60,'cardio','trung bình',0,'Lịch tạo tự động từ lớp Cardio Giảm Cân [CLASS_ID:2]','dự kiến','2026-03-26 09:59:04','2026-03-26 09:59:04'),(12,1,2,'2026-03-31 06:00:00',60,'cardio','trung bình',0,'Lịch tạo tự động từ lớp Cardio Giảm Cân [CLASS_ID:2]','huỷ','2026-03-26 17:10:17','2026-03-26 17:10:26'),(13,25,1,'2026-03-30 06:00:00',60,'boxing','trung bình',0,'Lịch tạo tự động từ lớp Boxing Cơ Bản [CLASS_ID:3]','dự kiến','2026-03-26 17:27:32','2026-03-26 17:27:32'),(14,22,2,'2026-03-31 06:00:00',60,'cardio','trung bình',0,'Lịch tạo tự động từ lớp Cardio Giảm Cân [CLASS_ID:2]','dự kiến','2026-03-27 23:00:44','2026-03-27 23:00:44'),(15,27,1,'2026-03-30 08:00:00',60,'yoga','trung bình',0,'Lịch tạo tự động từ lớp Yoga Sáng Năng Động [CLASS_ID:1]','dự kiến','2026-03-29 06:02:52','2026-03-29 06:02:52'),(16,27,2,'2026-03-31 14:00:00',60,'cardio','trung bình',0,'Lịch tạo tự động từ lớp Cardio Giảm Cân [CLASS_ID:2]','dự kiến','2026-03-29 06:03:59','2026-03-29 06:18:53'),(17,36,2,'2026-04-06 01:41:00',60,'zzzzzzzzzz','trung bình',0,'Lịch tạo tự động từ lớp zzzzzz [CLASS_ID:7]','dự kiến','2026-03-30 15:41:47','2026-03-30 15:41:47'),(18,36,10,'2026-04-06 19:24:00',60,'boxing','trung bình',0,'Lịch tạo tự động từ lớp ttk6 [CLASS_ID:9]','dự kiến','2026-03-31 09:26:08','2026-03-31 09:26:08');
/*!40000 ALTER TABLE `member_training_schedules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `members`
--

DROP TABLE IF EXISTS `members`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `members` (
  `id` int NOT NULL AUTO_INCREMENT,
  `users_id` int NOT NULL,
  `full_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `join_date` date DEFAULT NULL,
  `status` enum('active','inactive') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `height` decimal(5,2) DEFAULT NULL COMMENT 'Chiều cao (cm)',
  `weight` decimal(5,2) DEFAULT NULL COMMENT 'Cân nặng (kg)',
  `tier_id` int DEFAULT '1' COMMENT 'Hạng hội viên',
  `total_spent` decimal(12,2) DEFAULT '0.00' COMMENT 'Tổng tiền đã chi',
  PRIMARY KEY (`id`),
  KEY `users_id` (`users_id`),
  KEY `fk_members_tier` (`tier_id`),
  CONSTRAINT `fk_members_tier` FOREIGN KEY (`tier_id`) REFERENCES `member_tiers` (`id`),
  CONSTRAINT `members_ibfk_1` FOREIGN KEY (`users_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `members`
--

LOCK TABLES `members` WRITE;
/*!40000 ALTER TABLE `members` DISABLE KEYS */;
INSERT INTO `members` VALUES (1,1,'Trương Trung Kiên','0912345678','18/16 Phan Văn Trị, P.Chợ Quán, Q.5, TPHCM','2024-01-15','active',175.00,100.00,4,41579375.00),(2,2,'Nguyễn Tường Huy','0987654321','Quận 3, TP.HCM','2024-02-20','active',180.00,70.00,3,23390000.00),(3,3,'Nguyễn Nguyên Bảo','0903456789','Thủ Đức, TP.HCM','2023-11-05','active',175.00,75.00,2,3882000.00),(13,9,'test','0786026878','666 Võ Văn Kiệt, Gò Vấp, TP.HCM','2026-02-15','active',180.00,55.00,1,0.00),(18,25,'ý ý','0786026878',NULL,'2026-03-25','active',175.00,55.00,3,10242000.00),(19,27,'k','0786026878','','2026-03-25','active',156.00,56.00,2,7304000.00),(22,23,'kkkk','0786026878',NULL,'2026-03-26','active',156.00,76.00,1,0.00),(24,32,'Bẻo','09999999999',NULL,'2026-03-26','active',166.00,66.00,1,0.00),(25,34,'h3','0786026870',NULL,'2026-03-26','active',190.00,80.00,2,8738000.00),(26,37,'nty4','0786026878','','2026-03-28','active',0.00,0.00,2,8886000.00),(27,38,'nty5','0912345674',NULL,'2026-03-29','active',180.00,56.00,3,20517900.00),(28,39,'h6','0917267033','','2026-03-29','active',0.00,0.00,1,0.00),(29,40,'H7','0786026878','','2026-03-29','active',0.00,0.00,1,0.00),(31,42,'nty7','0786026866','','2026-03-29','active',0.00,0.00,1,0.00),(32,43,'nty8','0812345679','','2026-03-29','active',175.00,75.00,5,68480962.00),(35,45,'ttk3','0912345678','','2026-03-30','active',185.00,85.00,1,0.00),(36,46,'ttk4','0123456789','','2026-03-30','active',70.00,40.00,2,9092920.00);
/*!40000 ALTER TABLE `members` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `membership_packages`
--

DROP TABLE IF EXISTS `membership_packages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `membership_packages` (
  `id` int NOT NULL AUTO_INCREMENT,
  `package_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `duration_months` int NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `status` enum('active','inactive') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'active',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `membership_packages`
--

LOCK TABLES `membership_packages` WRITE;
/*!40000 ALTER TABLE `membership_packages` DISABLE KEYS */;
INSERT INTO `membership_packages` VALUES (1,'Gói 1 Tháng',1,500000.00,'Tập gym không giới hạn 1 tháng','active'),(2,'Gói 3 Tháng',3,1350000.00,'Tiết kiệm hơn so với gói lẻ','active'),(3,'Gói 6 Tháng',6,2500000.00,'Ưu đãi mạnh cho hội viên lâu dài','active'),(4,'Gói 12 Tháng',12,4800000.00,'Gói năm – rẻ nhất','active'),(7,'Gói 24 Tháng',24,8500000.00,'ok ok ok ok ok ok ok ok ok ok ok ok ok ok ok ok ok ok ok ok ok ok ok ok ok ok ok ok ok ok ok ok ok ok ','active'),(12,'testsetsetaaa',5,300000.00,'','active');
/*!40000 ALTER TABLE `membership_packages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_read` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_notifications_user_id` (`user_id`),
  CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=65 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Thông báo cho người dùng';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
INSERT INTO `notifications` VALUES (2,1,'66','6677',1,'2026-03-11 09:56:56'),(3,1,'3636','363636',1,'2026-03-15 10:04:15'),(4,2,'3636','363636',1,'2026-03-15 10:04:15'),(5,3,'3636','363636',1,'2026-03-15 10:04:15'),(6,9,'3636','363636',1,'2026-03-15 10:04:15'),(7,10,'3636','363636',0,'2026-03-15 10:04:15'),(8,11,'3636','363636',0,'2026-03-15 10:04:15'),(10,13,'3636','363636',0,'2026-03-15 10:04:15'),(11,14,'3636','363636',0,'2026-03-15 10:04:15'),(12,15,'3636','363636',0,'2026-03-15 10:04:15'),(13,16,'3636','363636',0,'2026-03-15 10:04:15'),(14,17,'3636','363636',0,'2026-03-15 10:04:15'),(15,18,'3636','363636',0,'2026-03-15 10:04:15'),(16,19,'3636','363636',0,'2026-03-15 10:04:15'),(17,20,'3636','363636',0,'2026-03-15 10:04:15'),(18,21,'3636','363636',0,'2026-03-15 10:04:15'),(19,22,'3636','363636',0,'2026-03-15 10:04:15'),(20,17,'Feedback mới từ hội viên','Hội viên Trương Trung Kiên vừa gửi feedback mới. Vui lòng kiểm tra mục Phản hồi.',1,'2026-03-15 11:37:55'),(21,9,'aaaaa','aaaaaaaa',0,'2026-03-19 08:29:44'),(22,10,'aaaaa','aaaaaaaa',0,'2026-03-19 08:29:44'),(23,11,'aaaaa','aaaaaaaa',0,'2026-03-19 08:29:44'),(25,15,'aaaaa','aaaaaaaa',0,'2026-03-19 08:29:44'),(26,16,'aaaaa','aaaaaaaa',0,'2026-03-19 08:29:44'),(27,18,'aaaaa','aaaaaaaa',0,'2026-03-19 08:29:44'),(28,19,'aaaaa','aaaaaaaa',0,'2026-03-19 08:29:44'),(29,1,'aa','aaa',1,'2026-03-25 14:22:07'),(30,9,'yyyyyyyyyy','yyyyyyyyyyy',0,'2026-03-25 15:13:06'),(31,10,'yyyyyyyyyy','yyyyyyyyyyy',0,'2026-03-25 15:13:06'),(32,11,'yyyyyyyyyy','yyyyyyyyyyy',0,'2026-03-25 15:13:06'),(33,15,'yyyyyyyyyy','yyyyyyyyyyy',0,'2026-03-25 15:13:06'),(34,16,'yyyyyyyyyy','yyyyyyyyyyy',0,'2026-03-25 15:13:06'),(35,18,'yyyyyyyyyy','yyyyyyyyyyy',0,'2026-03-25 15:13:06'),(36,19,'yyyyyyyyyy','yyyyyyyyyyy',0,'2026-03-25 15:13:06'),(37,9,'yyyyyyyy','yyyyyyyy',0,'2026-03-25 15:13:33'),(38,10,'yyyyyyyy','yyyyyyyy',0,'2026-03-25 15:13:33'),(39,11,'yyyyyyyy','yyyyyyyy',0,'2026-03-25 15:13:33'),(40,15,'yyyyyyyy','yyyyyyyy',0,'2026-03-25 15:13:33'),(41,16,'yyyyyyyy','yyyyyyyy',0,'2026-03-25 15:13:33'),(42,18,'yyyyyyyy','yyyyyyyy',0,'2026-03-25 15:13:33'),(43,19,'yyyyyyyy','yyyyyyyy',0,'2026-03-25 15:13:33'),(44,1,'123123','123123',0,'2026-03-25 23:04:06'),(45,2,'123123','123123',0,'2026-03-25 23:04:06'),(46,3,'123123','123123',0,'2026-03-25 23:04:06'),(47,9,'123123','123123',0,'2026-03-25 23:04:06'),(48,10,'123123','123123',0,'2026-03-25 23:04:06'),(49,11,'123123','123123',0,'2026-03-25 23:04:06'),(50,13,'123123','123123',0,'2026-03-25 23:04:06'),(51,14,'123123','123123',0,'2026-03-25 23:04:06'),(52,15,'123123','123123',0,'2026-03-25 23:04:06'),(53,16,'123123','123123',0,'2026-03-25 23:04:06'),(54,17,'123123','123123',0,'2026-03-25 23:04:06'),(55,18,'123123','123123',0,'2026-03-25 23:04:06'),(56,19,'123123','123123',0,'2026-03-25 23:04:06'),(57,20,'123123','123123',0,'2026-03-25 23:04:06'),(58,21,'123123','123123',0,'2026-03-25 23:04:06'),(59,22,'123123','123123',0,'2026-03-25 23:04:06'),(60,23,'123123','123123',0,'2026-03-25 23:04:06'),(61,25,'123123','123123',0,'2026-03-25 23:04:06'),(62,27,'123123','123123',1,'2026-03-25 23:04:06'),(63,29,'123123','123123',0,'2026-03-25 23:04:06'),(64,27,'cccc','cccc',1,'2026-03-26 07:55:22');
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `nutrition_items`
--

DROP TABLE IF EXISTS `nutrition_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `nutrition_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `serving_desc` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `calories` int DEFAULT NULL,
  `protein` decimal(6,2) DEFAULT NULL,
  `carbs` decimal(6,2) DEFAULT NULL,
  `fat` decimal(6,2) DEFAULT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `status` enum('hoạt động','không hoạt động') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'hoạt động',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `nutrition_items`
--

LOCK TABLES `nutrition_items` WRITE;
/*!40000 ALTER TABLE `nutrition_items` DISABLE KEYS */;
INSERT INTO `nutrition_items` VALUES (1,'Ức gà luộc','100g',165,31.00,0.00,3.60,'Nguồn protein nạc cao, phù hợp cho tăng cơ và giảm mỡ.','hoạt động'),(2,'Cơm trắng','1 chén (150g)',200,4.00,45.00,0.40,'Nguồn tinh bột phổ biến, cung cấp năng lượng nhanh.','hoạt động'),(3,'Trứng gà','1 quả (~50g)',70,6.00,0.60,5.00,'Giàu protein và chất béo tốt, thích hợp cho bữa sáng.','hoạt động'),(4,'Cá hồi áp chảo','100g',208,20.00,0.00,13.00,'Giàu omega-3, hỗ trợ tim mạch và phục hồi cơ bắp.','hoạt động'),(5,'Khoai lang luộc','100g',86,1.60,20.00,0.10,'Tinh bột chậm, giúp no lâu và ổn định đường huyết.','hoạt động'),(6,'test','50',90,6.00,0.60,13.00,'test','hoạt động');
/*!40000 ALTER TABLE `nutrition_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `nutrition_plan_items`
--

DROP TABLE IF EXISTS `nutrition_plan_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `nutrition_plan_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nutrition_plan_id` int NOT NULL,
  `item_id` int NOT NULL,
  `servings_per_day` decimal(5,2) DEFAULT '1.00',
  `meal_time` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `note` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_plan` (`nutrition_plan_id`),
  KEY `idx_item` (`item_id`),
  CONSTRAINT `fk_plan_item_item` FOREIGN KEY (`item_id`) REFERENCES `nutrition_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_plan_item_plan` FOREIGN KEY (`nutrition_plan_id`) REFERENCES `nutrition_plans` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `nutrition_plan_items`
--

LOCK TABLES `nutrition_plan_items` WRITE;
/*!40000 ALTER TABLE `nutrition_plan_items` DISABLE KEYS */;
INSERT INTO `nutrition_plan_items` VALUES (1,1,2,1.00,'sáng','aaaaaaa'),(2,1,1,2.00,'trưa','Tăng lượng protein chính trong ngày'),(3,1,2,1.50,'trưa','Bổ sung tinh bột vừa phải'),(4,2,5,2.00,'sáng','Tinh bột chậm giúp no lâu'),(5,2,3,1.00,'sáng','Bổ sung protein và chất béo tốt'),(6,3,4,1.50,'tối','Omega-3 hỗ trợ phục hồi cơ');
/*!40000 ALTER TABLE `nutrition_plan_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `nutrition_plans`
--

DROP TABLE IF EXISTS `nutrition_plans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `nutrition_plans` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('tăng cân','giảm cân','tư vấn','duy trì','tăng cơ','giảm mỡ','khác') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `calories` int DEFAULT NULL COMMENT 'Tổng calo/ngày',
  `bmi_range` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `status` enum('hoạt động','không hoạt động') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'hoạt động',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Chế độ dinh dưỡng & tư vấn';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `nutrition_plans`
--

LOCK TABLES `nutrition_plans` WRITE;
/*!40000 ALTER TABLE `nutrition_plans` DISABLE KEYS */;
INSERT INTO `nutrition_plans` VALUES (1,'Tăng cân cơ bản 2500 Calo','tăng cân',2500,'16 - 18.4','Chế độ ăn dành cho người gầy, tập trung vào thực phẩm giàu năng lượng, tăng khẩu phần tinh bột và protein.','hoạt động'),(2,'Giảm cân khoa học 1500 Calo','giảm cân',1500,'25 - 34.9','Thực đơn giảm calo an toàn, hạn chế đường và chất béo xấu, tăng rau xanh và protein nạc.','hoạt động'),(3,'Tăng cơ chuyên sâu 2800 Calo','tăng cơ',2800,'16 - 18.4','Chế độ ăn giàu protein kết hợp carb phức, phù hợp người tập gym 4-6 buổi/tuần.','hoạt động'),(4,'Giảm mỡ Low-carb 1700 Calo','giảm mỡ',1700,'23 - 24.9','Giảm tinh bột nhanh, ưu tiên protein và chất béo tốt, hỗ trợ đốt mỡ hiệu quả.','không hoạt động');
/*!40000 ALTER TABLE `nutrition_plans` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_items`
--

DROP TABLE IF EXISTS `order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_id` int NOT NULL,
  `item_type` enum('product','package','service','class') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `item_id` int NOT NULL,
  `item_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `price` decimal(15,2) NOT NULL,
  `quantity` int DEFAULT '1',
  `discount` decimal(15,2) DEFAULT '0.00',
  `subtotal` decimal(15,2) GENERATED ALWAYS AS (((`price` * `quantity`) - `discount`)) STORED,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `idx_item_lookup` (`item_type`,`item_id`),
  CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=103 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_items`
--

LOCK TABLES `order_items` WRITE;
/*!40000 ALTER TABLE `order_items` DISABLE KEYS */;
INSERT INTO `order_items` (`id`, `order_id`, `item_type`, `item_id`, `item_name`, `price`, `quantity`, `discount`, `created_at`) VALUES (1,1,'product',1,'Whey Gold Standard 5lbs',1850000.00,1,100000.00,'2026-02-20 00:09:14'),(2,1,'product',2,'Nước khoáng Lavie 500ml',10000.00,10,0.00,'2026-02-20 00:09:14'),(3,8,'service',3,'test',50000.00,2,0.00,'2026-02-20 00:09:14'),(4,9,'product',3,'Găng tay tập Gym Adidas',450000.00,1,50000.00,'2026-02-20 00:09:14'),(5,10,'package',1,'Gói 1 Tháng',500000.00,1,0.00,'2026-02-20 00:09:14'),(6,10,'product',4,'test_product',50000.00,2,1000.00,'2026-02-20 00:09:14'),(7,11,'package',3,'Gói 6 Tháng',2500000.00,1,200000.00,'2026-02-20 00:09:14'),(8,11,'service',4,'test2',70000.00,3,10000.00,'2026-02-20 00:09:14'),(9,11,'product',1,'Whey Gold Standard 5lbs',1850000.00,1,200000.00,'2026-02-20 00:09:14'),(10,12,'product',19,'Mass Gainer Serious Mass 6lbs',1350000.00,1,0.00,'2026-03-07 22:01:30'),(11,13,'product',19,'Mass Gainer Serious Mass 6lbs',1350000.00,1,0.00,'2026-03-07 22:02:31'),(12,14,'product',19,'Mass Gainer Serious Mass 6lbs',1350000.00,1,0.00,'2026-03-07 22:05:14'),(13,15,'product',19,'Mass Gainer Serious Mass 6lbs',1350000.00,1,0.00,'2026-03-07 22:08:05'),(14,16,'product',19,'Mass Gainer Serious Mass 6lbs',1350000.00,10,0.00,'2026-03-07 23:08:05'),(15,17,'product',1,'Whey Gold Standard 5lbs',1850000.00,10,0.00,'2026-03-07 23:12:37'),(16,18,'product',2,'Nước khoáng Lavie 500ml',10000.00,1,0.00,'2026-03-07 23:15:07'),(17,19,'product',2,'Nước khoáng Lavie 500ml',10000.00,1,0.00,'2026-03-07 23:15:28'),(18,20,'product',9,'BCAA 2:1:1 400g',550000.00,1,0.00,'2026-03-07 23:16:29'),(19,21,'product',19,'Mass Gainer Serious Mass 6lbs',1350000.00,5,0.00,'2026-03-11 09:58:19'),(20,22,'product',19,'Mass Gainer Serious Mass 6lbs',1282500.00,3,0.00,'2026-03-11 10:25:47'),(21,23,'product',19,'Mass Gainer Serious Mass 6lbs',1215000.00,2,0.00,'2026-03-11 10:29:29'),(22,24,'product',19,'Mass Gainer Serious Mass 6lbs',1282500.00,2,0.00,'2026-03-11 10:35:28'),(23,25,'product',19,'Mass Gainer Serious Mass 6lbs',1215000.00,1,0.00,'2026-03-18 11:51:49'),(24,25,'package',3,'Gói 6 Tháng',2500000.00,1,0.00,'2026-03-18 11:51:49'),(25,25,'service',4,'test2',70000.00,1,0.00,'2026-03-18 11:51:49'),(26,26,'product',13,'Đai lưng tập gym',225000.00,1,0.00,'2026-03-22 20:00:03'),(27,27,'package',2,'Gói 3 Tháng',1350000.00,1,0.00,'2026-03-22 20:18:59'),(28,28,'product',18,'Fat Burner L-Carnitine',585000.00,2,0.00,'2026-03-22 21:10:32'),(29,29,'product',18,'Fat Burner L-Carnitine',585000.00,1,0.00,'2026-03-22 21:13:55'),(30,30,'product',17,'Whey Dymatize Elite 5lbs',1755000.00,1,0.00,'2026-03-22 21:15:44'),(31,31,'product',17,'Whey Dymatize Elite 5lbs',1755000.00,1,0.00,'2026-03-22 21:17:37'),(32,32,'product',1,'Whey Gold Standard 5lbs',1665000.00,5,0.00,'2026-03-22 21:22:54'),(33,33,'product',1,'Whey Gold Standard 5lbs',1572500.00,1,0.00,'2026-03-25 12:59:20'),(34,33,'package',4,'Gói 12 Tháng',4800000.00,1,0.00,'2026-03-25 12:59:20'),(35,34,'product',19,'Mass Gainer Serious Mass 6lbs',1147500.00,1,0.00,'2026-03-25 15:10:27'),(36,35,'product',19,'Mass Gainer Serious Mass 6lbs',1147500.00,2,0.00,'2026-03-25 15:11:49'),(37,36,'product',19,'Mass Gainer Serious Mass 6lbs',1350000.00,6,0.00,'2026-03-25 15:29:16'),(38,37,'product',19,'Mass Gainer Serious Mass 6lbs',1282500.00,1,0.00,'2026-03-25 15:38:12'),(39,38,'product',19,'Mass Gainer Serious Mass 6lbs',1282500.00,1,0.00,'2026-03-25 15:39:48'),(40,39,'product',19,'Mass Gainer Serious Mass 6lbs',1350000.00,2,0.00,'2026-03-25 21:50:44'),(41,39,'package',7,'test',24000.00,1,0.00,'2026-03-25 21:50:44'),(42,40,'product',1,'Whey Gold Standard 5lbs',1850000.00,1,0.00,'2026-03-26 09:38:23'),(43,40,'product',19,'Mass Gainer Serious Mass 6lbs',1350000.00,2,0.00,'2026-03-26 09:38:23'),(44,41,'product',19,'Mass Gainer Serious Mass 6lbs',1350000.00,1,0.00,'2026-03-27 04:33:32'),(45,42,'product',19,'Mass Gainer Serious Mass 6lbs',1350000.00,2,0.00,'2026-03-27 04:42:45'),(46,43,'product',19,'Mass Gainer Serious Mass 6lbs',1282500.00,3,0.00,'2026-03-27 06:01:01'),(47,44,'product',19,'Mass Gainer Serious Mass 6lbs',1282500.00,1,0.00,'2026-03-27 06:23:39'),(48,45,'product',18,'Fat Burner L-Carnitine',617500.00,1,32500.00,'2026-03-27 06:29:17'),(49,46,'product',18,'Fat Burner L-Carnitine',650000.00,1,0.00,'2026-03-28 16:45:21'),(50,46,'service',5,'xông hơi Sauna + anh Kiên massage',5000000.00,1,0.00,'2026-03-28 16:45:21'),(51,47,'product',22,'zzzzz',47500.00,1,2500.00,'2026-03-28 16:47:42'),(52,48,'product',19,'Mass Gainer Serious Mass 6lbs',1282500.00,1,67500.00,'2026-03-28 16:49:04'),(53,49,'product',19,'Mass Gainer Serious Mass 6lbs',1282500.00,1,67500.00,'2026-03-28 16:51:13'),(54,50,'product',19,'Mass Gainer Serious Mass 6lbs',1282500.00,1,67500.00,'2026-03-28 16:51:39'),(55,51,'product',22,'zzzzz',50000.00,1,0.00,'2026-03-29 05:42:14'),(56,51,'package',3,'Gói 6 Tháng',2500000.00,1,0.00,'2026-03-29 05:42:14'),(57,52,'class',1,'Yoga Sáng Năng Động',50000.00,1,0.00,'2026-03-29 06:03:40'),(58,53,'class',2,'Cardio Giảm Cân',70000.00,1,0.00,'2026-03-29 06:16:58'),(59,54,'class',2,'Cardio Giảm Cân',70000.00,1,0.00,'2026-03-29 06:27:51'),(60,55,'product',22,'zzzzz',50000.00,1,0.00,'2026-03-29 06:28:05'),(61,56,'product',19,'Mass Gainer Serious Mass 6lbs',1350000.00,1,0.00,'2026-03-29 06:28:29'),(62,57,'product',19,'Mass Gainer Serious Mass 6lbs',1282500.00,1,67500.00,'2026-03-29 06:29:30'),(63,58,'product',19,'Mass Gainer Serious Mass 6lbs',1282500.00,1,67500.00,'2026-03-29 06:29:58'),(64,59,'product',19,'Mass Gainer Serious Mass 6lbs',1282500.00,1,67500.00,'2026-03-29 06:31:20'),(65,60,'product',19,'Mass Gainer Serious Mass 6lbs',1282500.00,1,67500.00,'2026-03-29 06:32:07'),(66,61,'product',19,'Mass Gainer Serious Mass 6lbs',1282500.00,1,67500.00,'2026-03-29 06:34:47'),(67,62,'product',19,'Mass Gainer Serious Mass 6lbs',1282500.00,1,67500.00,'2026-03-29 06:35:04'),(68,63,'product',19,'Mass Gainer Serious Mass 6lbs',1215000.00,1,135000.00,'2026-03-29 06:36:26'),(69,64,'product',19,'Mass Gainer Serious Mass 6lbs',1215000.00,2,270000.00,'2026-03-29 08:07:34'),(70,65,'product',19,'Mass Gainer Serious Mass 6lbs',1215000.00,2,270000.00,'2026-03-29 08:24:53'),(71,66,'product',19,'Mass Gainer Serious Mass 6lbs',1215000.00,2,270000.00,'2026-03-29 08:28:13'),(72,67,'product',19,'Mass Gainer Serious Mass 6lbs',1215000.00,2,270000.00,'2026-03-29 08:32:26'),(73,68,'product',19,'Mass Gainer Serious Mass 6lbs',1215000.00,2,270000.00,'2026-03-29 08:34:06'),(74,69,'product',19,'Mass Gainer Serious Mass 6lbs',1215000.00,2,270000.00,'2026-03-29 08:36:15'),(75,70,'product',19,'Mass Gainer Serious Mass 6lbs',1215000.00,2,270000.00,'2026-03-29 08:37:24'),(76,71,'product',19,'Mass Gainer Serious Mass 6lbs',1215000.00,2,270000.00,'2026-03-29 08:42:15'),(77,72,'product',19,'Mass Gainer Serious Mass 6lbs',1215000.00,2,270000.00,'2026-03-29 08:46:41'),(78,73,'product',19,'Mass Gainer Serious Mass 6lbs',1215000.00,2,270000.00,'2026-03-29 08:49:00'),(79,74,'product',19,'Mass Gainer Serious Mass 6lbs',1215000.00,2,270000.00,'2026-03-29 08:50:52'),(80,75,'product',19,'Mass Gainer Serious Mass 6lbs',1215000.00,2,270000.00,'2026-03-29 08:53:26'),(81,76,'product',19,'Mass Gainer Serious Mass 6lbs',1215000.00,2,270000.00,'2026-03-29 08:56:47'),(82,77,'product',19,'Mass Gainer Serious Mass 6lbs',1215000.00,2,270000.00,'2026-03-29 08:58:24'),(83,78,'product',19,'Mass Gainer Serious Mass 6lbs',1215000.00,2,270000.00,'2026-03-29 09:01:04'),(84,79,'product',19,'Mass Gainer Serious Mass 6lbs',1215000.00,2,270000.00,'2026-03-29 09:17:10'),(85,80,'product',19,'Mass Gainer Serious Mass 6lbs',1350000.00,10,0.00,'2026-03-29 09:21:34'),(86,81,'product',19,'Mass Gainer Serious Mass 6lbs',1215000.00,30,4050000.00,'2026-03-29 09:27:31'),(87,82,'product',19,'Mass Gainer Serious Mass 6lbs',1147500.00,30,6075000.00,'2026-03-29 09:28:39'),(88,83,'product',19,'Mass Gainer Serious Mass 6lbs',1080000.00,3,810000.00,'2026-03-29 09:36:34'),(89,84,'product',19,'Mass Gainer Serious Mass 6lbs',1080000.00,2,540000.00,'2026-03-29 14:03:33'),(90,85,'product',19,'Mass Gainer Serious Mass 6lbs',1080000.00,3,810000.00,'2026-03-29 14:20:22'),(91,86,'product',18,'Fat Burner L-Carnitine',520000.00,1,130000.00,'2026-03-29 16:34:26'),(92,86,'product',19,'Mass Gainer Serious Mass 6lbs',1080000.00,3,810000.00,'2026-03-29 16:34:26'),(93,87,'package',2,'Gói 3 Tháng',1350000.00,1,0.00,'2026-03-29 17:00:57'),(94,88,'product',19,'Mass Gainer Serious Mass 6lbs',1350000.00,5,0.00,'2026-03-30 09:02:05'),(95,89,'product',19,'Mass Gainer Serious Mass 6lbs',1350000.00,1,0.00,'2026-03-30 09:10:46'),(96,90,'product',19,'Mass Gainer Serious Mass 6lbs',1350000.00,3,0.00,'2026-03-30 09:57:18'),(97,91,'product',19,'Mass Gainer Serious Mass 6lbs',1282500.00,5,337500.00,'2026-03-30 10:15:10'),(98,92,'product',4,'Kiên Đẹp Trai',9500.00,11,5500.00,'2026-03-30 10:35:13'),(99,93,'package',11,'tests',500000.00,1,0.00,'2026-03-30 15:36:33'),(100,94,'package',12,'testsetset',500000.00,1,0.00,'2026-03-30 15:37:50'),(101,95,'class',7,'zzzzzz',50000.00,1,0.00,'2026-03-30 15:42:07'),(102,96,'class',9,'ttk6',30000.00,1,0.00,'2026-03-31 09:26:15');
/*!40000 ALTER TABLE `order_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `orders` (
  `id` int NOT NULL AUTO_INCREMENT,
  `member_id` int NOT NULL,
  `address_id` int DEFAULT NULL,
  `order_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `total_amount` decimal(12,2) DEFAULT '0.00',
  `payment_method` enum('cash','online','bank_transfer') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'cash',
  `transfer_code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `proof_img` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `note` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `status` enum('pending','confirmed','delivered','cancelled') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `handled_by` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `member_id` (`member_id`),
  KEY `address_id` (`address_id`),
  CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`),
  CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`address_id`) REFERENCES `addresses` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=97 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `orders`
--

LOCK TABLES `orders` WRITE;
/*!40000 ALTER TABLE `orders` DISABLE KEYS */;
INSERT INTO `orders` VALUES (1,1,1,'2026-02-18 09:15:00',250000.00,'cash',NULL,NULL,NULL,'delivered',NULL),(8,1,11,'2026-02-18 18:50:10',100000.00,'cash',NULL,NULL,NULL,'delivered',NULL),(9,2,12,'2026-02-18 11:45:00',125000.00,'cash',NULL,NULL,NULL,'delivered',NULL),(10,2,13,'2026-02-18 13:00:00',99000.99,'online',NULL,NULL,NULL,'confirmed',NULL),(11,3,14,'2026-02-18 14:20:00',760000.00,'cash',NULL,NULL,NULL,'cancelled',NULL),(12,2,NULL,'2026-03-08 05:01:30',1380000.00,'online',NULL,NULL,NULL,'delivered',NULL),(13,2,NULL,'2026-03-08 05:02:31',1380000.00,'cash',NULL,NULL,NULL,'cancelled',NULL),(14,2,27,'2026-03-08 05:05:14',1380000.00,'cash',NULL,NULL,NULL,'pending',NULL),(15,2,NULL,'2026-03-08 05:08:05',1380000.00,'online',NULL,NULL,NULL,'pending',NULL),(16,2,NULL,'2026-03-08 06:08:05',13530000.00,'cash',NULL,NULL,NULL,'delivered',NULL),(17,2,NULL,'2026-03-08 06:12:37',18530000.00,'cash',NULL,NULL,NULL,'cancelled',NULL),(18,2,NULL,'2026-03-08 06:15:07',40000.00,'cash',NULL,NULL,NULL,'pending',NULL),(19,2,NULL,'2026-03-08 06:15:28',40000.00,'cash',NULL,NULL,NULL,'pending',NULL),(20,2,NULL,'2026-03-08 06:16:29',580000.00,'cash',NULL,NULL,NULL,'pending',NULL),(21,1,NULL,'2026-03-11 16:58:19',6780000.00,'cash',NULL,NULL,NULL,'delivered',NULL),(22,1,NULL,'2026-03-11 17:25:47',3492750.00,'cash',NULL,NULL,NULL,'delivered',NULL),(23,1,NULL,'2026-03-11 17:29:29',2217000.00,'cash',NULL,NULL,NULL,'delivered',NULL),(24,3,NULL,'2026-03-11 17:35:28',2082000.00,'cash',NULL,NULL,NULL,'pending',NULL),(25,1,NULL,'2026-03-18 18:51:49',3436500.00,'online',NULL,NULL,NULL,'confirmed',NULL),(26,1,NULL,'2026-03-23 03:00:03',255000.00,'cash',NULL,NULL,NULL,'pending',NULL),(27,1,NULL,'2026-03-23 03:18:59',1350000.00,'cash',NULL,NULL,NULL,'pending',NULL),(28,1,NULL,'2026-03-23 04:10:32',1200000.00,'bank_transfer','TT028IA','order_1774213832_69c05ac8ab9e1.jpg',NULL,'pending',NULL),(29,1,NULL,'2026-03-23 04:13:55',556500.00,'bank_transfer','TT028GN','order_1774214035_69c05b93451ad.jpg',NULL,'delivered',NULL),(30,1,NULL,'2026-03-23 04:15:44',1785000.00,'bank_transfer','TT028LF','order_1774214144_69c05c00a5157.jpg',NULL,'pending',NULL),(31,1,NULL,'2026-03-23 04:17:37',1785000.00,'bank_transfer','TT028VC','order_1774214257_69c05c713271d.jpg',NULL,'pending',NULL),(32,1,NULL,'2026-03-23 04:22:54',7522500.00,'bank_transfer','TT028OM','order_1774214574_69c05daebda06.jpg',NULL,'confirmed',NULL),(33,1,NULL,'2026-03-25 19:59:20',5446625.00,'bank_transfer','TT033HS','order_1774443560_69c3dc2843fd5.jpg',NULL,'pending',NULL),(34,1,NULL,'2026-03-25 22:10:27',1127500.00,'bank_transfer','TT034AY','order_1774451427_69c3fae3b0271.jpg',NULL,'pending',NULL),(35,1,NULL,'2026-03-25 22:11:49',2125000.00,'bank_transfer','TT034IH','order_1774451509_69c3fb3544040.jpg',NULL,'pending',NULL),(36,18,28,'2026-03-25 22:29:16',8130000.00,'bank_transfer','TT034OC','order_1774452556_69c3ff4c9f383.jpg',NULL,'pending',NULL),(37,18,NULL,'2026-03-25 22:38:12',1056000.00,'cash',NULL,NULL,NULL,'confirmed',NULL),(38,18,NULL,'2026-03-25 22:39:48',1056000.00,'cash',NULL,NULL,NULL,'pending',NULL),(39,19,31,'2026-03-26 04:50:44',2724000.00,'cash',NULL,NULL,NULL,'pending',NULL),(40,19,40,'2026-03-26 16:38:23',4580000.00,'cash',NULL,NULL,NULL,'delivered',NULL),(41,25,41,'2026-03-27 11:33:32',1350000.00,'bank_transfer','TT041EE','order_1774586012_69c6089c948e6.jpg',NULL,'pending',NULL),(42,25,41,'2026-03-27 11:42:45',2700000.00,'cash',NULL,NULL,'yyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyy','pending',NULL),(43,25,41,'2026-03-27 13:01:01',3108000.00,'cash',NULL,NULL,NULL,'pending',NULL),(44,25,41,'2026-03-27 13:23:39',1056000.00,'bank_transfer','TT041NF','order_1774592619_69c6226b53165.jpg',NULL,'delivered',NULL),(45,25,41,'2026-03-27 13:29:17',524000.00,'cash',NULL,NULL,NULL,'confirmed',NULL),(46,26,44,'2026-03-28 23:45:21',5650000.00,'cash',NULL,NULL,NULL,'confirmed',NULL),(47,26,44,'2026-03-28 23:47:42',68000.00,'bank_transfer','TT046GS','order_1774716462_69c8062e43a89.jpg',NULL,'pending',NULL),(48,26,44,'2026-03-28 23:49:04',1056000.00,'bank_transfer','TT046JP','order_1774716544_69c8068036dda.jpg',NULL,'pending',NULL),(49,26,44,'2026-03-28 23:51:13',1056000.00,'bank_transfer','TT046KD','order_1774716673_69c8070158039.jpg',NULL,'pending',NULL),(50,26,44,'2026-03-28 23:51:39',1056000.00,'cash',NULL,NULL,NULL,'pending',NULL),(51,27,45,'2026-03-29 12:42:14',2580000.00,'cash',NULL,NULL,NULL,'pending',NULL),(52,27,NULL,'2026-03-29 13:03:40',50000.00,'bank_transfer','TT046DI','order_1774764220_69c8c0bcd219c.jpg',NULL,'pending',NULL),(53,27,NULL,'2026-03-29 13:16:58',70000.00,'bank_transfer','TT046BV','order_1774765018_69c8c3da16db9.jpg',NULL,'pending',NULL),(54,27,NULL,'2026-03-29 13:27:51',70000.00,'cash',NULL,NULL,NULL,'pending',NULL),(55,27,45,'2026-03-29 13:28:05',80000.00,'cash',NULL,NULL,NULL,'pending',NULL),(56,27,45,'2026-03-29 13:28:29',1380000.00,'bank_transfer','TT046ZO','order_1774765709_69c8c68d9cb63.jpg',NULL,'pending',NULL),(57,27,45,'2026-03-29 13:29:30',1056000.00,'cash',NULL,NULL,NULL,'pending',NULL),(58,27,45,'2026-03-29 13:29:58',1312500.00,'bank_transfer','TT046LD','order_1774765798_69c8c6e64dbfe.jpg',NULL,'pending',NULL),(59,27,45,'2026-03-29 13:31:20',1056000.00,'bank_transfer','TT046GU','order_1774765880_69c8c7384e3b0.jpg',NULL,'pending',NULL),(60,27,45,'2026-03-29 13:32:07',1056000.00,'bank_transfer','TT046EG','order_1774765927_69c8c767967cf.jpg',NULL,'pending',NULL),(61,27,45,'2026-03-29 13:34:47',1056000.00,'cash',NULL,NULL,NULL,'pending',NULL),(62,27,45,'2026-03-29 13:35:04',1056000.00,'bank_transfer','TT046XB','order_1774766104_69c8c818d3da4.jpg',NULL,'pending',NULL),(63,27,45,'2026-03-29 13:36:26',1123500.00,'bank_transfer','TT046CD','order_1774766186_69c8c86a35e55.jpg',NULL,'pending',NULL),(64,27,45,'2026-03-29 15:07:34',2217000.00,'cash',NULL,NULL,NULL,'pending',NULL),(65,27,45,'2026-03-29 15:24:53',30000.00,'bank_transfer','TT046DH','order_1774772693_69c8e1d51b524.jpg',NULL,'pending',NULL),(66,27,45,'2026-03-29 15:28:13',30000.00,'bank_transfer','TT046UW','order_1774772893_69c8e29dc38b2.jpg',NULL,'pending',NULL),(67,27,45,'2026-03-29 15:32:26',30000.00,'bank_transfer','TT046LD','order_1774773146_69c8e39ac65ef.jpg',NULL,'pending',NULL),(68,27,45,'2026-03-29 15:34:06',30000.00,'cash',NULL,NULL,NULL,'pending',NULL),(69,27,45,'2026-03-29 15:36:15',30000.00,'bank_transfer','TT046CS','order_1774773375_69c8e47f214f5.jpg',NULL,'confirmed',NULL),(70,27,45,'2026-03-29 15:37:24',30000.00,'cash',NULL,NULL,NULL,'confirmed',NULL),(71,27,45,'2026-03-29 15:42:15',30000.00,'bank_transfer','TT046RS','order_1774773735_69c8e5e7e78d1.jpg',NULL,'pending',NULL),(72,27,45,'2026-03-29 15:46:41',30000.00,'cash',NULL,NULL,NULL,'pending',NULL),(73,27,45,'2026-03-29 15:49:00',30000.00,'cash',NULL,NULL,NULL,'pending',NULL),(74,27,45,'2026-03-29 15:50:52',30000.00,'cash',NULL,NULL,NULL,'confirmed',NULL),(75,27,45,'2026-03-29 15:53:26',30000.00,'cash',NULL,NULL,NULL,'pending',NULL),(76,27,45,'2026-03-29 15:56:47',30000.00,'cash',NULL,NULL,NULL,'pending',NULL),(77,27,45,'2026-03-29 15:58:24',1998300.00,'cash',NULL,NULL,NULL,'pending',NULL),(78,27,45,'2026-03-29 16:01:04',1998300.00,'cash',NULL,NULL,NULL,'delivered',NULL),(79,27,45,'2026-03-29 16:17:10',1998300.00,'bank_transfer','TT046DB','order_1774775830_69c8ee16242d0.jpg',NULL,'pending',NULL),(80,32,46,'2026-03-29 16:21:34',12180000.00,'cash',NULL,NULL,NULL,'pending',NULL),(81,32,46,'2026-03-29 16:27:31',29554500.00,'cash',NULL,NULL,NULL,'pending',NULL),(82,32,46,'2026-03-29 16:28:39',24902062.00,'cash',NULL,NULL,NULL,'pending',NULL),(83,32,46,'2026-03-29 16:36:34',1844400.00,'cash',NULL,NULL,NULL,'pending',NULL),(84,32,46,'2026-03-29 21:03:33',1239600.00,'cash',NULL,NULL,NULL,'pending',NULL),(85,32,46,'2026-03-29 21:20:22',1585200.00,'cash',NULL,NULL,NULL,'pending',NULL),(86,32,46,'2026-03-29 23:34:26',3038000.00,'cash',NULL,NULL,NULL,'pending',NULL),(87,32,NULL,'2026-03-30 00:00:57',1080000.00,'cash',NULL,NULL,NULL,'pending',NULL),(88,35,50,'2026-03-30 16:02:05',6780000.00,'cash',NULL,NULL,NULL,'confirmed',NULL),(89,35,50,'2026-03-30 16:10:46',1380000.00,'cash',NULL,NULL,NULL,'pending',NULL),(90,36,51,'2026-03-30 16:57:18',4080000.00,'cash',NULL,NULL,NULL,'confirmed',NULL),(91,36,51,'2026-03-30 17:15:10',4903500.00,'cash',NULL,NULL,NULL,'confirmed',NULL),(92,36,51,'2026-03-30 17:35:13',109420.00,'bank_transfer','TT088FD','order_1774866913_69ca51e1a43d3.png','zzzzzzzzzzzzzzzzzzzzz','confirmed',NULL),(93,36,NULL,'2026-03-30 22:36:33',380000.00,'cash',NULL,NULL,NULL,'pending',NULL),(94,36,NULL,'2026-03-30 22:37:50',380000.00,'cash',NULL,NULL,NULL,'pending',NULL),(95,36,NULL,'2026-03-30 22:42:07',38000.00,'cash',NULL,NULL,NULL,'pending',NULL),(96,36,NULL,'2026-03-31 16:26:15',22800.00,'cash',NULL,NULL,NULL,'pending',NULL);
/*!40000 ALTER TABLE `orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `permission`
--

DROP TABLE IF EXISTS `permission`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `permission` (
  `id` int NOT NULL AUTO_INCREMENT,
  `code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `permission`
--

LOCK TABLES `permission` WRITE;
/*!40000 ALTER TABLE `permission` DISABLE KEYS */;
INSERT INTO `permission` VALUES (1,'MANAGE_ALL'),(9,'MANAGE_EQUIPMENT'),(10,'MANAGE_FEEDBACK'),(8,'MANAGE_INVENTORY'),(3,'MANAGE_MEMBERS'),(4,'MANAGE_PACKAGES'),(11,'MANAGE_PROMOTIONS'),(7,'MANAGE_SALES'),(6,'MANAGE_SERVICES_NUTRITION'),(2,'MANAGE_STAFF'),(5,'MANAGE_TRAINERS'),(12,'VIEW_REPORTS');
/*!40000 ALTER TABLE `permission` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `products` (
  `id` int NOT NULL AUTO_INCREMENT,
  `category_id` int DEFAULT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `short_description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Mô tả ngắn sản phẩm',
  `rating` decimal(2,1) DEFAULT '0.0' COMMENT 'Đánh giá trung bình (0-5)',
  `review` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Đánh giá sản phẩm',
  `img` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Đường dẫn hình ảnh sản phẩm',
  `unit` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'hộp' COMMENT 'Đơn vị tính: hộp, chai, cái...',
  `stock_quantity` int DEFAULT '0' COMMENT 'Số lượng tồn kho',
  `selling_price` decimal(15,2) DEFAULT '0.00' COMMENT 'Giá bán lẻ cho hội viên',
  `status` enum('active','inactive') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  PRIMARY KEY (`id`),
  KEY `fk_product_category` (`category_id`),
  CONSTRAINT `fk_product_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` VALUES (1,2,'Whey Gold Standard 5lbs','aaaaaaaaaaaaaaaaa',0.0,NULL,'product_1773913550_2254.jpg','Hộp',23,1850000.00,'active'),(2,1,'Nước khoáng Lavie 500ml','aaaaaaaaaaaaaa',0.0,NULL,'product_1773913141_2352.jpg','Chai',98,10000.00,'active'),(3,1,'Găng tay tập Gym Adidas','a Kiên đẹp trai',0.0,NULL,'product_1773913173_7139.jpg','Đôi',15,450000.00,'active'),(4,9,'Kiên Đẹp Trai','a Kiên đẹp trai',0.0,NULL,'product_1773967630_7324.jpg','người',30,10000.00,'active'),(8,2,'Creatine Monohydrate 300g',NULL,0.0,NULL,'product_1773913198_6790.jpg','hộp',60,450000.00,'active'),(9,2,'BCAA 2:1:1 400g',NULL,0.0,NULL,'product_1773913216_4721.jpg','hộp',34,550000.00,'active'),(10,12,'Nước điện giải Pocari 500ml','',0.0,NULL,'product_1773913234_8720.jpg','chai',200,15000.00,'active'),(12,4,'Găng tay tập gym cao cấp',NULL,0.0,NULL,'product_1773913250_9366.jpg','cái',70,120000.00,'active'),(13,4,'Đai lưng tập gym',NULL,0.0,NULL,'product_1773913292_4758.jpg','cái',29,250000.00,'active'),(14,5,'Dây kháng lực 5 mức',NULL,0.0,NULL,'product_1773913307_3822.jpg','bộ',45,180000.00,'active'),(16,7,'Combo Whey + Creatine',NULL,0.0,NULL,'product_1773913458_6257.jpg','combo',20,2100000.00,'active'),(17,8,'Whey Dymatize Elite 5lbs',NULL,0.0,NULL,'product_1773913336_7668.jpg','hộp',23,1950000.00,'active'),(18,9,'Fat Burner L-Carnitine',NULL,0.0,NULL,'product_1773913361_6387.jpg','hộp',24,650000.00,'active'),(19,10,'Mass Gainer Serious Mass 6lbs',NULL,0.0,NULL,'product_1772920712_1461.jpg','hộp',14,1350000.00,'active'),(22,16,'zzzzz','z',0.0,NULL,'product_1774649729_4835.jpg','z',47,50000.00,'active');
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `promotion_usage`
--

DROP TABLE IF EXISTS `promotion_usage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `promotion_usage` (
  `id` int NOT NULL AUTO_INCREMENT,
  `member_id` int NOT NULL,
  `promotion_id` int NOT NULL,
  `order_id` int DEFAULT NULL COMMENT 'Đơn hàng áp dụng',
  `applied_amount` decimal(10,2) DEFAULT NULL COMMENT 'Số tiền được giảm',
  `applied_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_usage_member` (`member_id`),
  KEY `idx_usage_promotion` (`promotion_id`),
  KEY `idx_usage_date` (`applied_at`),
  CONSTRAINT `promotion_usage_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  CONSTRAINT `promotion_usage_ibfk_2` FOREIGN KEY (`promotion_id`) REFERENCES `tier_promotions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=46 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `promotion_usage`
--

LOCK TABLES `promotion_usage` WRITE;
/*!40000 ALTER TABLE `promotion_usage` DISABLE KEYS */;
INSERT INTO `promotion_usage` VALUES (1,1,1,22,384750.00,'2026-03-11 10:25:47'),(2,1,7,23,243000.00,'2026-03-11 10:29:29'),(3,3,6,24,513000.00,'2026-03-11 10:35:28'),(4,1,7,25,378500.00,'2026-03-18 11:51:49'),(5,1,7,29,58500.00,'2026-03-22 21:13:55'),(6,1,7,32,832500.00,'2026-03-22 21:22:54'),(7,1,4,33,955875.00,'2026-03-25 12:59:20'),(8,1,9,34,50000.00,'2026-03-25 15:10:27'),(9,1,5,35,200000.00,'2026-03-25 15:11:49'),(10,18,6,37,256500.00,'2026-03-25 15:38:12'),(11,18,6,38,256500.00,'2026-03-25 15:39:48'),(12,19,10,39,30000.00,'2026-03-25 21:50:44'),(13,25,10,41,30000.00,'2026-03-27 04:33:32'),(14,25,10,42,30000.00,'2026-03-27 04:42:45'),(15,25,6,43,769500.00,'2026-03-27 06:01:01'),(16,25,6,44,256500.00,'2026-03-27 06:23:39'),(17,25,6,45,123500.00,'2026-03-27 06:29:17'),(18,26,10,46,30000.00,'2026-03-28 16:45:21'),(19,26,6,47,9500.00,'2026-03-28 16:47:42'),(20,26,6,48,256500.00,'2026-03-28 16:49:04'),(21,26,6,49,256500.00,'2026-03-28 16:51:13'),(22,26,6,50,256500.00,'2026-03-28 16:51:39'),(23,27,6,57,256500.00,'2026-03-29 06:29:30'),(24,27,6,59,256500.00,'2026-03-29 06:31:20'),(25,27,6,60,256500.00,'2026-03-29 06:32:07'),(26,27,6,61,256500.00,'2026-03-29 06:34:47'),(27,27,6,62,256500.00,'2026-03-29 06:35:04'),(28,27,7,63,121500.00,'2026-03-29 06:36:26'),(29,27,7,64,243000.00,'2026-03-29 08:07:34'),(30,27,7,75,0.00,'2026-03-29 08:53:26'),(31,27,7,76,0.00,'2026-03-29 08:56:47'),(32,27,7,77,218700.00,'2026-03-29 08:58:24'),(33,27,7,78,218700.00,'2026-03-29 09:01:04'),(34,27,7,79,218700.00,'2026-03-29 09:17:10'),(35,32,7,81,3280500.00,'2026-03-29 09:27:31'),(36,32,4,82,4389188.00,'2026-03-29 09:28:39'),(39,32,11,85,1036800.00,'2026-03-29 14:20:22'),(40,36,6,91,1218375.00,'2026-03-30 10:15:10'),(41,36,6,92,19855.00,'2026-03-30 10:35:13'),(42,36,6,93,95000.00,'2026-03-30 15:36:33'),(43,36,6,94,95000.00,'2026-03-30 15:37:50'),(44,36,6,95,9500.00,'2026-03-30 15:42:07'),(45,36,6,96,5700.00,'2026-03-31 09:26:15');
/*!40000 ALTER TABLE `promotion_usage` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reports`
--

DROP TABLE IF EXISTS `reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reports` (
  `id` int NOT NULL AUTO_INCREMENT,
  `type` enum('doanh thu','hoi vien','thiet bi','dich vu','khac') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `period_start` date DEFAULT NULL,
  `period_end` date DEFAULT NULL,
  `created_by` int NOT NULL,
  `data` json DEFAULT NULL,
  `file_path` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` enum('draft','completed','archived') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'draft',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_reports_created_by` (`created_by`),
  KEY `idx_reports_type` (`type`),
  CONSTRAINT `reports_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Báo cáo thống kê';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reports`
--

LOCK TABLES `reports` WRITE;
/*!40000 ALTER TABLE `reports` DISABLE KEYS */;
/*!40000 ALTER TABLE `reports` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `role_action_permissions`
--

DROP TABLE IF EXISTS `role_action_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `role_action_permissions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `role_id` int NOT NULL,
  `permission_code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `can_view` tinyint(1) NOT NULL DEFAULT '0',
  `can_add` tinyint(1) NOT NULL DEFAULT '0',
  `can_edit` tinyint(1) NOT NULL DEFAULT '0',
  `can_delete` tinyint(1) NOT NULL DEFAULT '0',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_role_permission_code` (`role_id`,`permission_code`),
  KEY `idx_role_permissions_role_id` (`role_id`),
  CONSTRAINT `fk_role_action_permissions_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=79 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `role_action_permissions`
--

LOCK TABLES `role_action_permissions` WRITE;
/*!40000 ALTER TABLE `role_action_permissions` DISABLE KEYS */;
INSERT INTO `role_action_permissions` VALUES (2,4,'MANAGE_STAFF',1,1,1,1,'2026-03-30 08:40:10'),(3,4,'MANAGE_MEMBERS',1,1,1,1,'2026-03-30 08:40:10'),(4,4,'MANAGE_PACKAGES',1,1,1,1,'2026-03-30 08:40:10'),(5,4,'MANAGE_TRAINERS',1,1,1,1,'2026-03-30 08:40:10'),(6,4,'MANAGE_SERVICES_NUTRITION',1,1,1,1,'2026-03-30 08:40:10'),(7,4,'MANAGE_SALES',1,1,1,1,'2026-03-30 08:40:10'),(8,4,'MANAGE_INVENTORY',1,1,1,1,'2026-03-30 08:40:10'),(9,4,'MANAGE_EQUIPMENT',1,1,1,1,'2026-03-30 08:40:10'),(10,4,'MANAGE_FEEDBACK',1,1,1,1,'2026-03-30 08:40:10'),(11,4,'MANAGE_PROMOTIONS',1,1,1,1,'2026-03-30 08:40:10'),(12,4,'VIEW_REPORTS',1,1,1,1,'2026-03-30 08:40:10'),(13,4,'MANAGE_ALL',1,1,1,1,'2026-03-30 08:40:10'),(68,11,'MANAGE_STAFF',1,1,1,0,'2026-03-30 08:56:19'),(69,11,'MANAGE_MEMBERS',1,1,1,1,'2026-03-30 08:56:19'),(70,11,'MANAGE_PACKAGES',1,0,0,0,'2026-03-30 08:56:19'),(71,11,'MANAGE_TRAINERS',1,0,0,0,'2026-03-30 08:56:19'),(72,11,'MANAGE_SERVICES_NUTRITION',1,0,0,0,'2026-03-30 08:56:19'),(73,11,'MANAGE_SALES',1,0,0,0,'2026-03-30 08:56:19'),(74,11,'MANAGE_INVENTORY',1,0,0,0,'2026-03-30 08:56:19'),(75,11,'MANAGE_EQUIPMENT',1,0,0,0,'2026-03-30 08:56:19'),(76,11,'MANAGE_FEEDBACK',1,0,0,0,'2026-03-30 08:56:19'),(77,11,'MANAGE_PROMOTIONS',1,0,0,0,'2026-03-30 08:56:19'),(78,11,'VIEW_REPORTS',1,0,0,0,'2026-03-30 08:56:19');
/*!40000 ALTER TABLE `role_action_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `role_permissions`
--

DROP TABLE IF EXISTS `role_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `role_permissions` (
  `role_id` int NOT NULL,
  `permission_id` int NOT NULL,
  PRIMARY KEY (`role_id`,`permission_id`),
  KEY `fk_role_permissions_permission` (`permission_id`),
  CONSTRAINT `fk_role_permissions_permission` FOREIGN KEY (`permission_id`) REFERENCES `permission` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_role_permissions_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `role_permissions`
--

LOCK TABLES `role_permissions` WRITE;
/*!40000 ALTER TABLE `role_permissions` DISABLE KEYS */;
INSERT INTO `role_permissions` VALUES (4,1),(4,2),(11,2),(4,3),(11,3),(4,4),(11,4),(4,5),(11,5),(4,6),(11,6),(4,7),(11,7),(4,8),(11,8),(4,9),(11,9),(4,10),(11,10),(4,11),(11,11),(4,12),(11,12);
/*!40000 ALTER TABLE `role_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `status` enum('active','inactive') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (4,'Quản trị viên','Quản trị toàn hệ thống','active'),(5,'Nhân viên dịch vụ','Nhân viên dịch vụ','active'),(6,'Hội viên','Hội viên sử dụng dịch vụ','active'),(7,'Lễ tân','Tiếp nhận và hỗ trợ khách hàng','active'),(8,'Kế toán','Quản lý thu chi và tài chính','active'),(9,'Kỹ thuật viên','Bảo trì và sửa chữa thiết bị','active'),(10,'Nhân viên vệ sinh','Dọn dẹp và giữ vệ sinh phòng tập','active'),(11,'Bảo vệ','Đảm bảo an ninh','active'),(12,'Nhân viên tư vấn','Tư vấn và bán gói tập','active'),(13,'Nhân viên hỗ trợ','Chăm sóc và hỗ trợ khách hàng','active'),(14,'Huấn luyện viên (PT)','Tư vấn và hướng dẫn cho hội viên','active'),(15,'ntyntynty','ntyntyntyn','active');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `services`
--

DROP TABLE IF EXISTS `services`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `services` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `img` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Đường dẫn hình ảnh dịch vụ',
  `type` enum('thư giãn','xoa bóp','hỗ trợ') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `status` enum('hoạt động','không hoạt động') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'hoạt động',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Dịch vụ phòng gym';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `services`
--

LOCK TABLES `services` WRITE;
/*!40000 ALTER TABLE `services` DISABLE KEYS */;
INSERT INTO `services` VALUES (3,'Massage phục hồi sau tập','assets/uploads/services/service_1774445517_3e958b4e.jpg','xoa bóp',100000.00,'Tập hết mình, relax hết cỡ! Dịch vụ mát-xa tại gym giúp bạn giảm đau cơ, thư giãn tức thì và phục hồi nhanh hơn. Không gian chill, kỹ thuật viên chuyên nghiệp.','hoạt động'),(4,'Xông hơi sauna – thư giãn chuẩn gym','assets/uploads/services/service_1774445765_fb6ca8a3.jpg','thư giãn',120000.00,'Giải nhiệt sau buổi tập với phòng xông hơi hiện đại, giúp thải độc, giảm căng cơ và thư giãn cực đã. Không gian sạch đẹp, ấm áp – “reset” cơ thể nhanh chóng.','hoạt động'),(5,'xông hơi Sauna + anh Kiên massage','assets/uploads/services/service_1774445956_0a536abf.jpg','thư giãn',5000000.00,'Xông hơi sauna giúp thải độc, giảm căng cơ, thư giãn cực đã sau buổi tập. Sau đó được anh Kiên trực tiếp massage, đánh tan đau mỏi, làm xong nhẹ người liền.','hoạt động');
/*!40000 ALTER TABLE `services` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `staff`
--

DROP TABLE IF EXISTS `staff`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `staff` (
  `id` int NOT NULL AUTO_INCREMENT,
  `users_id` int NOT NULL,
  `full_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `position` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `department_id` int DEFAULT NULL,
  `status` enum('active','inactive','on_leave') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `users_id` (`users_id`),
  KEY `department_id` (`department_id`),
  CONSTRAINT `staff_ibfk_1` FOREIGN KEY (`users_id`) REFERENCES `users` (`id`),
  CONSTRAINT `staff_ibfk_2` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `staff`
--

LOCK TABLES `staff` WRITE;
/*!40000 ALTER TABLE `staff` DISABLE KEYS */;
INSERT INTO `staff` VALUES (1,13,'Phạm Văn D',NULL,NULL,'Nhân viên tư vấn',5,'active','2026-03-28 03:26:42','2026-03-28 04:18:18'),(2,14,'Hoàng Thị E',NULL,NULL,'Kỹ thuật viên',8,'active','2026-03-28 03:26:42','2026-03-28 04:18:31'),(3,20,'Nguyễn Minh Tuấn',NULL,NULL,'Lễ tân',1,'active','2026-03-28 03:26:42','2026-03-28 04:18:51'),(4,21,'Trần Thanh Hương',NULL,NULL,'Kế toán',7,'active','2026-03-28 03:26:42','2026-03-28 04:19:10'),(5,22,'Lê Văn Hải',NULL,NULL,'Kế toán',3,'active','2026-03-28 03:26:42','2026-03-28 04:12:09'),(7,32,'Bẻo',NULL,NULL,'Huấn luyện viên (PT)',2,'active','2026-03-28 03:44:09','2026-03-28 04:21:37'),(8,38,'nty5',NULL,NULL,'ntyntynty',8,'active','2026-03-29 16:06:32','2026-03-29 16:06:32'),(9,41,'nty6',NULL,NULL,'ntyntynty',7,'active','2026-03-29 16:08:36','2026-03-29 16:09:14'),(10,43,'nty8',NULL,NULL,'Kế toán',3,'active','2026-03-30 15:55:00','2026-03-30 15:55:45'),(13,47,'ttk5',NULL,NULL,'Kỹ thuật viên',7,'active','2026-03-31 00:30:11','2026-03-31 00:30:11');
/*!40000 ALTER TABLE `staff` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `suppliers`
--

DROP TABLE IF EXISTS `suppliers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `suppliers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `status` enum('active','inactive') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `suppliers`
--

LOCK TABLES `suppliers` WRITE;
/*!40000 ALTER TABLE `suppliers` DISABLE KEYS */;
INSERT INTO `suppliers` VALUES (1,'Công ty Thể Thao Đại Việt','0901234567','123 Lý Thường Kiệt, Q10, TP.HCM','active','2024-01-27 10:00:00'),(2,'Whey Store VN','0909888777','456 CMT8, Q3, TP.HCM','active','2024-01-27 10:00:00'),(17,'a','a','a','active','2026-02-17 10:31:25'),(23,'Công ty TNHH Thiết Bị Minh Phát','0908123456','125 Nguyễn Văn Cừ, Quận 5, TP. Hồ Chí Minh','active','2026-03-24 12:50:09'),(24,'Công ty TNHH Thiết Bị Minh Phát','0908123456','125 Nguyễn Văn Cừ, Quận 5, TP. Hồ Chí Minh','active','2026-03-24 12:50:10'),(25,'Công ty TNHH Thiết Bị Minh Phát','0908123456','125 Nguyễn Văn Cừ, Quận 5, TP. Hồ Chí Minh','active','2026-03-24 12:50:10');
/*!40000 ALTER TABLE `suppliers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tier_promotions`
--

DROP TABLE IF EXISTS `tier_promotions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tier_promotions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `tier_id` int NOT NULL COMMENT 'Hạng áp dụng',
  `discount_type` enum('percentage','fixed','package') DEFAULT 'percentage',
  `discount_value` decimal(10,2) NOT NULL COMMENT 'Giá trị giảm',
  `applicable_items` json DEFAULT NULL COMMENT 'Danh sách dịch vụ áp dụng',
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `usage_limit` int DEFAULT NULL COMMENT 'Số lần sử dụng tối đa',
  `status` enum('active','inactive','expired') DEFAULT 'active',
  PRIMARY KEY (`id`),
  KEY `tier_id` (`tier_id`),
  KEY `idx_promotion_dates` (`start_date`,`end_date`),
  KEY `idx_promotion_status` (`status`),
  CONSTRAINT `tier_promotions_ibfk_1` FOREIGN KEY (`tier_id`) REFERENCES `member_tiers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tier_promotions`
--

LOCK TABLES `tier_promotions` WRITE;
/*!40000 ALTER TABLE `tier_promotions` DISABLE KEYS */;
INSERT INTO `tier_promotions` VALUES (1,'Giảm PT cho hội viên Bạc',2,'percentage',10.00,'[\"personal_training\"]','2024-01-01','2029-07-31',10000,'active'),(2,'Tặng 2 buổi tập cho hội viên Vàng',3,'package',2.00,'[\"gym_session\"]','2024-01-01','2030-11-21',50,'active'),(3,'Giảm 50K phí đăng ký Kim Cương',5,'fixed',50000.00,'[\"registration_fee\"]','2024-01-01','2030-12-20',NULL,'active'),(4,'Giảm 15% supplement cho Bạch Kim',4,'percentage',15.00,'[\"protein\", \"vitamin\"]','2024-01-01','2031-07-24',20000,'active'),(5,'aaa',4,'fixed',200000.00,NULL,'2025-06-13','2030-06-20',NULL,'active'),(6,'Giảm PT cho hội viên Bạc 200slot',2,'percentage',20.00,NULL,'2026-03-01','2030-06-26',200,'active'),(7,'Giảm đặc biệt cho HV Vàng 100 slot',3,'percentage',10.00,'[\"personal_training\"]','2026-03-01','2029-06-28',100,'active'),(8,'Bạch kim 3',4,'percentage',10.00,'[\"Sản phẩm giảm cân\"]','2026-03-25','2026-03-27',33,'active'),(9,'Bạch kim 4',4,'fixed',50000.00,NULL,'2026-03-25','2026-03-28',32,'active'),(10,'Đồng 4',1,'fixed',30000.00,NULL,'2026-03-25','2026-03-28',30,'active'),(11,'test',5,'percentage',40.00,NULL,'2026-03-19','2026-04-11',10,'active');
/*!40000 ALTER TABLE `tier_promotions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `trainers`
--

DROP TABLE IF EXISTS `trainers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `trainers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `full_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `img` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Đường dẫn hình ảnh huấn luyện viên',
  `type` enum('Nội bộ','Tự do') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'Nội bộ',
  `specialization` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `experience` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `trainers`
--

LOCK TABLES `trainers` WRITE;
/*!40000 ALTER TABLE `trainers` DISABLE KEYS */;
INSERT INTO `trainers` VALUES (1,'Trương Trung Kiên',NULL,'Nội bộ',NULL,NULL,'0786026822','hoạt động'),(2,'Ngô Gia Phúc',NULL,'Nội bộ',NULL,NULL,'0786026878','hoạt động'),(3,'Nguyễn Nguyên Bảo',NULL,'Nội bộ',NULL,NULL,'07860263336','hoạt động'),(7,'Hoàng Thị Lan',NULL,'Nội bộ',NULL,NULL,'0786026888','hoạt động'),(8,'Hoàng Thị Lan',NULL,'Nội bộ',NULL,NULL,'03333333333','hoạt động'),(10,'ttk6',NULL,'Nội bộ','ttk6','10 năm','0786026878','hoạt động');
/*!40000 ALTER TABLE `trainers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `training_schedules`
--

DROP TABLE IF EXISTS `training_schedules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `training_schedules` (
  `id` int NOT NULL AUTO_INCREMENT,
  `member_id` int NOT NULL,
  `trainer_id` int DEFAULT NULL,
  `training_date` datetime NOT NULL,
  `end_time` datetime DEFAULT NULL,
  `status` enum('pending','confirmed','completed','canceled') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'pending',
  `note` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`id`),
  KEY `member_id` (`member_id`),
  KEY `trainer_id` (`trainer_id`),
  CONSTRAINT `training_schedules_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  CONSTRAINT `training_schedules_ibfk_2` FOREIGN KEY (`trainer_id`) REFERENCES `trainers` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `training_schedules`
--

LOCK TABLES `training_schedules` WRITE;
/*!40000 ALTER TABLE `training_schedules` DISABLE KEYS */;
INSERT INTO `training_schedules` VALUES (1,2,1,'2026-02-20 11:00:00','2026-02-20 13:56:00','pending','test'),(2,3,1,'2026-03-21 13:33:00','2026-03-21 14:33:00','pending','nnnnnnnnnnnnn'),(3,3,2,'2026-03-21 17:33:00','2026-03-21 18:33:00','pending',''),(4,1,3,'2026-03-19 15:41:00','2026-03-19 16:41:00','pending','tui dang fa hihi'),(5,1,1,'2026-03-20 11:10:00','2026-03-20 12:10:00','pending','aaaaaaaaaa'),(6,1,2,'2026-03-23 04:34:00','2026-03-23 05:34:00','pending','kkkkk'),(8,36,2,'2026-03-30 22:43:00','2026-03-30 23:43:00','pending','aaaaa'),(9,35,2,'2026-03-30 22:43:00','2026-03-30 23:43:00','pending','ffff'),(11,36,10,'2026-03-31 16:30:00','2026-03-31 17:30:00','completed',''),(12,36,10,'2026-03-31 08:00:00','2026-03-31 09:00:00','completed','aaaaaa');
/*!40000 ALTER TABLE `training_schedules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_permissions`
--

DROP TABLE IF EXISTS `user_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_permissions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `permission_code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `can_view` tinyint(1) NOT NULL DEFAULT '0',
  `can_add` tinyint(1) NOT NULL DEFAULT '0',
  `can_edit` tinyint(1) NOT NULL DEFAULT '0',
  `can_delete` tinyint(1) NOT NULL DEFAULT '0',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_user_permission_code` (`user_id`,`permission_code`),
  KEY `idx_user_permissions_user_id` (`user_id`),
  CONSTRAINT `fk_user_permissions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_permissions`
--

LOCK TABLES `user_permissions` WRITE;
/*!40000 ALTER TABLE `user_permissions` DISABLE KEYS */;
INSERT INTO `user_permissions` VALUES (1,41,'MANAGE_MEMBERS',1,0,0,0,'2026-03-29 09:09:50'),(2,41,'MANAGE_TRAINERS',1,0,0,0,'2026-03-29 09:09:50'),(3,41,'MANAGE_SALES',1,0,0,0,'2026-03-29 09:09:50');
/*!40000 ALTER TABLE `user_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `role_id` int NOT NULL,
  `username` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `password` varchar(267) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `full_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `phone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `avatar` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` enum('active','locked') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  KEY `role_id` (`role_id`),
  CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=49 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,3,'truongtrungkien','123456','kien@gmail.com','','',NULL,'active','2026-01-26 19:05:40'),(2,3,'nguyentuonghuy','123456','huy@gmail.com','',NULL,NULL,'active','2026-01-26 19:05:40'),(3,3,'nguyennguyenbao','123456','bao@gmail.com','',NULL,NULL,'active','2026-01-26 19:05:40'),(9,6,'test1','$2y$12$Ue66tFFe0NXDl1PNUE4rr.8R4RbtmFaCTBZWVMMyFx.iQQztoR3bm','test1@gmail.com','test1','07860267777',NULL,'active','2026-02-15 11:22:23'),(10,6,'nguyenvana','$2y$12$TF7jjk6EvnU6Eelm4oyJSunjSZBx0nP3iI/FevoZFNsNQxbm5/Psi','nguyenvana@gmail.com','Nguyễn Văn A','0912345678',NULL,'active','2026-03-11 03:00:00'),(11,6,'tranthib','$2y$12$7V3vwc6C4.OOZPr57ZhVQuP1PjZ1yQcagoqbuZg3fHB1sYSWWfypO','tranthib@gmail.com','Trần Thị B','0901234567',NULL,'active','2026-03-11 03:05:00'),(13,5,'phamvand','$2y$12$Ssh2bIyhjTezT7aJvTnZnur8qkT/1oyWIw.q6EtWYhh.3jCs8pXSy','phamvand@gmail.com','Phạm Văn D','0971122334',NULL,'active','2026-03-11 03:15:00'),(14,5,'hoangthie','$2y$12$x1Pr763MumskxLipARXdO.joCluOQTRaB/CTCPih1XD08vYY5LM8y','hoangthie@gmail.com','Hoàng Thị E','0965566778',NULL,'active','2026-03-11 03:20:00'),(15,6,'vuhongf','$2y$12$aSdtO96ae8Ou9Bh015daUuKL78Rt.XehTslrX1VgkZ/0Eo3CQd2au','vuhongf@gmail.com','Vũ Hồng F','0939988776',NULL,'active','2026-03-11 03:25:00'),(16,6,'dovanh','$2y$12$CbjtmM9Cp/oe/FmrX1.UOOdPmIdmJWH1wemTjUNYnaIL6uo44.9H.','dovanh@gmail.com','Đỗ Văn H','0923456789',NULL,'active','2026-03-11 03:30:00'),(17,4,'admin2','$2y$12$HcW3UbNGOWV2yrgAAvGTnerTcYiQcChY6JAMKOQb3lNm8B4Uj7NY6','admin2@gmail.com','Trương Trung Kiên','0891234567',NULL,'active','2026-03-11 03:35:00'),(18,6,'nguyenvanminh','$2y$12$RL5wsCQ23K2q.w26Ta8b2Oa.89ZVNt613CvwDoSFH3BtHxmUPdZc6','nguyenvanminh@gmail.com','Nguyễn Văn Minh','0887654321',NULL,'active','2026-03-11 03:40:00'),(19,6,'tranthilan','$2y$12$VDow0YnZgWXpPH2Mqpt9tuJkAYo4349oe6YZOWgur6hVoTlpoqWCS','tranthilan@gmail.com','Trần Thị Lan','0862345678',NULL,'active','2026-03-11 03:45:00'),(20,5,'nguyenminhtuan','$2y$12$lAyTIJMYy59s6pobwOWYeuUsLGs0qZKHRNbXtLqRNUAAWK/OrMx6e','nguyenminhtuan@gmail.com','Nguyễn Minh Tuấn','0859876543',NULL,'active','2026-03-11 03:50:00'),(21,5,'tranthanhhuong','$2y$12$/sbwQHwNomp7tHFeGU1ksuLjkTxb9PPGv04dW3EP/n9wy/1BOzwIC','tranthanhhuong@gmail.com','Trần Thanh Hương','0841122334',NULL,'active','2026-03-11 03:55:00'),(22,5,'levanhai','$2y$12$AnmRdWvHtWihoHe3Z9FuhOMEkWWUZEoZirSWwADWmMGEwvzsj1Py.','levanhai@gmail.com','Lê Văn Hải','0835566778',NULL,'active','2026-03-11 04:00:00'),(23,7,'h','$2y$12$UI.89hxNh4uR3FiqNyL1uueHlJ/jY5NslMTKyVMw.FgDxq3EMj8wu','h@gmail.com','h','0786026878',NULL,'active','2026-03-15 11:28:26'),(25,6,'y','$2y$12$3uUB373YfHDVyV4E6C48fOc0BgsNAZNtsXoPMZ.BTeFuVK8vddeqW','y@gmail.com','Nguyễn Thị Ý','0812345679',NULL,'active','2026-03-25 15:22:27'),(27,6,'k','$2y$12$LFGmJgD0pmbhXQpi8kfigO1rjWLLRpusNur1GhSgC0DGNCdGFIOBm','k@gmail.com','k','0912345666',NULL,'active','2026-03-25 21:08:56'),(29,6,'z','$2y$12$ze/Sm7tKbEjISLrfosbwu..p1/gFkF22.1sJhCLvsMYMwnhtd8YPu','z@kk.com','z','0786026833',NULL,'active','2026-03-25 22:12:21'),(32,9,'bbb','$2y$12$7XIC7cC7n1H.9UZLohy/zeMUERcCoC479gyt3I53Nz5fL/7e9mABG','beo@gmail.com','Bẻo ','09999999999',NULL,'active','2026-03-26 16:02:32'),(33,9,'hhh','$2y$12$rSxG0b5w8Xit.4FXlBBqw.xrY0QKH2YTrFMXdwNlXT7NRwoqxJUjq','h22@gmail.com','Nguyễn Tường Huy','0786026878',NULL,'active','2026-03-26 16:22:13'),(34,6,'h3h3h3','$2y$12$mvLz0V50DQvYBAMWSZ9WWOaqksDblzSrykN9AjpFpbA3/BukVeTti','h3@gmail.com','h3','0786026870',NULL,'active','2026-03-26 17:26:37'),(35,14,'nty','$2y$12$8QHWnNjEarhwZdH.Sjq/uOJhKa9tOAiDnTPgtaky1ELNoSHkFHL/y','nty@gmail.com','nty','0786026866',NULL,'active','2026-03-28 00:59:46'),(37,6,'nty4','$2y$12$b3kfvcMfPxXAr.5exMm92ebf1VUybE3kELiY9NZkBMZrX3qQe1p8K','nty4@gmail.com','nty4','0786026867',NULL,'active','2026-03-28 16:34:04'),(38,15,'nty5','$2y$12$hu3JHYRViFfW.GinBqdKI.CG3sek.C.vcv2UlYN0OPWLFlGJvFFBy','nty5@gmail.com','nty5','0912345674','assets/uploads/avatars/avatar_38_1774762842.jpg','active','2026-03-29 05:16:45'),(39,6,'h6','$2y$12$FpvucJd09JzO.7neNRUwkufJS5pLOOAClUpkTkka9LkAZ0y1LthWC','aaaaaaa@gmail.com','h6','0812345679',NULL,'active','2026-03-29 07:58:09'),(40,6,'h77','$2y$12$C/tCLYJedkhFGZRKDwYYbuLG8oPHp1BwPPvCYLFVu3zs6uxk3QPGO','h7@gmail.com','H7','0786026878',NULL,'active','2026-03-29 08:02:34'),(41,15,'nty6','$2y$12$tgkVugXoc.kuzXR/bJutv.HG/8DoXo0xkWx1.H0HRh7IjQzkx96fe','nty6@gmail.com','nty6','0786026878',NULL,'active','2026-03-29 09:07:15'),(42,6,'0862920522','$2y$12$8ovFWVnCdKddKIlnQvwMMe6uKKmUCPeeeglqwtAeLbUM60q0c..Y.','nty7@gmail.com','nty7','0786026866',NULL,'active','2026-03-29 09:19:45'),(43,8,'nty8','$2y$12$p85tU1qq2C.XajC/3DzEpevSO5Sbi3W2ZQDaRt1cw3PETmHou3gCy','nty8@gmail.com','nty8','0812345679','assets/uploads/avatars/avatar_43_1774803710.jpg','active','2026-03-29 09:20:31'),(44,11,'nty9','$2y$12$Q.AYmBXz52iCRvd8NaI3zOBe8lA8dgsU3dcn.CIxS/knZGWW/BV5e','nguyenanhtu4537@gmail.com','nty9','0786026878',NULL,'active','2026-03-30 08:41:40'),(45,6,'ttk3','$2y$12$CLEJ.HcMWDg81ksptKQg6OQ0CxlDaBa6a1swuZme4Bk5loYnUJYoW','ttk3@gmail.com','ttk3','0912345678','assets/uploads/avatars/avatar_45_1774861301.jpg','active','2026-03-30 08:59:22'),(46,6,'ttk4','$2y$12$dqi2ectC11QWxImRhK5mtOcWau8ood7HZG3.ebcXH98UULZeH0oTK','ttk4@gmail.com','ttk4','0123456789',NULL,'active','2026-03-30 09:55:49'),(47,9,'ttk5','$2y$12$Gg3NEAf7fg.AMfTfj8RGjeZ7vXiFYYjZMZuRk61E8SeB.e2iEdUWS','ttk5@gmail.com','ttk5','0912345678',NULL,'active','2026-03-30 15:14:22'),(48,14,'ttk6','$2y$12$5RJ2lmULWg7cDtY8orZKOuaVIYaTDhy5VK8rNZqu9x0pNXLcmUmSa','ttk6@gmail.com','ttk6','0786026878',NULL,'active','2026-03-31 09:22:31');
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

-- Dump completed on 2026-03-31 16:55:31
