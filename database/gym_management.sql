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
  `full_address` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `city` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `district` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_default` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `member_id` (`member_id`),
  CONSTRAINT `addresses_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `addresses`
--

LOCK TABLES `addresses` WRITE;
/*!40000 ALTER TABLE `addresses` DISABLE KEYS */;
INSERT INTO `addresses` VALUES (1,13,'18/20 Phan Văn Trị, P5, TP.HCM','TP. HCM','Quận 5',1),(11,1,'45 Nguyễn Trãi, Phường 2','Hồ Chí Minh','Quận 5',1),(12,2,'78 Trần Hưng Đạo','Hà Nội','Hoàn Kiếm',1),(13,2,'12 Nguyễn Chí Thanh','Hà Nội','Đống Đa',0),(14,3,'56 Lý Thường Kiệt','Đà Nẵng','Hải Châu',1),(15,3,'90 Nguyễn Văn Linh','Đà Nẵng','Thanh Khê',0),(26,2,'18/16A Võ Văn Kiệt, Quận 2, TPHCM','TP.HCM','Quận 2',0),(27,2,'56656','TP. HCM','Quận 5',0);
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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Máy đo BMI';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bmi_devices`
--

LOCK TABLES `bmi_devices` WRITE;
/*!40000 ALTER TABLE `bmi_devices` DISABLE KEYS */;
INSERT INTO `bmi_devices` VALUES (1,'BMI - 06','Tầng 1 - Khu A','active','2026-02-15 11:55:01'),(2,'BMI - 07','Tầng 1 - Khu C','active','2026-02-15 11:56:16'),(3,'BMI - 01','Tầng 2','active','2026-02-15 11:56:30');
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
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Lịch sử đo BMI của hội viên';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bmi_measurements`
--

LOCK TABLES `bmi_measurements` WRITE;
/*!40000 ALTER TABLE `bmi_measurements` DISABLE KEYS */;
INSERT INTO `bmi_measurements` VALUES (3,13,3,180.00,55.00,16.98,'gay','2026-02-15 11:57:31'),(4,3,2,165.00,70.00,25.71,'thua can','2026-02-15 11:57:49'),(8,2,3,180.00,70.00,21.60,'binh thuong','2026-03-07 23:10:55');
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
  `item_type` enum('product','package','service') COLLATE utf8mb4_unicode_ci NOT NULL,
  `item_id` int NOT NULL,
  `quantity` int DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_cart_item` (`cart_id`,`item_type`,`item_id`),
  KEY `cart_id` (`cart_id`),
  CONSTRAINT `cart_items_ibfk_1` FOREIGN KEY (`cart_id`) REFERENCES `carts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=57 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cart_items`
--

LOCK TABLES `cart_items` WRITE;
/*!40000 ALTER TABLE `cart_items` DISABLE KEYS */;
INSERT INTO `cart_items` VALUES (29,1,'service',3,1,'2026-02-20 00:08:34'),(31,2,'package',1,1,'2026-02-20 00:08:34'),(32,3,'service',4,2,'2026-02-20 00:08:34'),(34,4,'package',2,1,'2026-02-20 00:08:34'),(35,4,'product',1,1,'2026-02-20 00:08:34'),(36,4,'service',3,1,'2026-02-20 00:08:34'),(39,2,'product',19,1,'2026-03-07 21:59:45'),(40,5,'product',19,1,'2026-03-07 22:02:19'),(41,6,'product',19,1,'2026-03-07 22:04:57'),(42,7,'product',19,1,'2026-03-07 22:07:58'),(43,8,'product',19,10,'2026-03-07 22:08:29'),(44,9,'product',1,10,'2026-03-07 23:12:29'),(45,10,'product',2,1,'2026-03-07 23:15:03'),(46,11,'product',2,1,'2026-03-07 23:15:24'),(47,12,'product',9,1,'2026-03-07 23:16:25'),(48,1,'product',19,5,'2026-03-11 09:58:06'),(51,13,'product',19,3,'2026-03-11 10:13:37'),(52,14,'product',19,2,'2026-03-11 10:27:27'),(54,3,'product',19,2,'2026-03-11 10:32:57'),(56,15,'product',21,1,'2026-03-16 15:20:30');
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
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `carts`
--

LOCK TABLES `carts` WRITE;
/*!40000 ALTER TABLE `carts` DISABLE KEYS */;
INSERT INTO `carts` VALUES (1,1,'2026-02-20 07:08:08','checked_out'),(2,2,'2026-02-20 07:08:08','checked_out'),(3,3,'2026-02-20 07:08:08','checked_out'),(4,13,'2026-02-20 07:08:08','active'),(5,2,'2026-03-08 05:02:19','checked_out'),(6,2,'2026-03-08 05:04:57','checked_out'),(7,2,'2026-03-08 05:07:58','checked_out'),(8,2,'2026-03-08 05:08:29','checked_out'),(9,2,'2026-03-08 06:12:29','checked_out'),(10,2,'2026-03-08 06:15:03','checked_out'),(11,2,'2026-03-08 06:15:24','checked_out'),(12,2,'2026-03-08 06:16:08','checked_out'),(13,1,'2026-03-11 17:02:11','checked_out'),(14,1,'2026-03-11 17:27:27','checked_out'),(15,1,'2026-03-11 17:31:20','active');
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
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES (1,'test','test','active'),(2,'Whey Protein','Các loại whey protein tăng cơ, phục hồi cơ bắp.','active'),(3,'Thực phẩm bổ sung','Vitamin, BCAA, Creatine và các sản phẩm hỗ trợ tập luyện.','active'),(4,'Nước uống thể thao','Nước điện giải và đồ uống bổ sung năng lượng.','active'),(5,'Phụ kiện tập gym','Găng tay, đai lưng, dây kéo, bình nước tập gym.','active'),(6,'Thiết bị tập cá nhân','Dụng cụ tập tại nhà như dây kháng lực, tạ tay.','active'),(7,'Quần áo thể thao','Trang phục tập gym cho nam và nữ.','active'),(8,'Combo khuyến mãi','Các gói sản phẩm bán theo combo ưu đãi.','inactive'),(9,'Hàng nhập khẩu','Sản phẩm nhập khẩu chính hãng từ Mỹ và châu Âu.','active'),(10,'Sản phẩm giảm cân','Các sản phẩm hỗ trợ giảm mỡ, kiểm soát cân nặng.','active'),(11,'Sản phẩm tăng cân','Mass gainer và thực phẩm hỗ trợ tăng cân.','active');
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
  `status` enum('active','cancelled') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_reg` (`member_id`,`class_id`),
  KEY `idx_reg_member` (`member_id`),
  KEY `idx_reg_class` (`class_id`),
  CONSTRAINT `fk_reg_class` FOREIGN KEY (`class_id`) REFERENCES `class_schedules` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_reg_member` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Đăng ký lớp tập nhóm';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `class_registrations`
--

LOCK TABLES `class_registrations` WRITE;
/*!40000 ALTER TABLE `class_registrations` DISABLE KEYS */;
INSERT INTO `class_registrations` VALUES (1,1,1,'2026-03-10 01:56:01','cancelled'),(2,2,2,'2026-03-02 14:15:00','cancelled'),(3,3,3,'2026-03-14 13:32:28','cancelled'),(4,13,4,'2026-03-04 16:20:00','active'),(5,1,5,'2026-03-11 16:35:49','active'),(6,1,2,'2026-03-11 16:35:45','active'),(7,1,3,'2026-03-10 01:55:59','cancelled'),(8,3,2,'2026-03-14 13:31:57','cancelled'),(9,3,5,'2026-03-14 13:33:03','active');
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
  `class_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Tên lớp tập',
  `class_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'cardio, yoga, strength, hiit, boxing...',
  `trainer_id` int DEFAULT NULL,
  `schedule_time` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'VD: 06:00 - 07:30',
  `schedule_days` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'VD: Thứ 2, Thứ 4, Thứ 6',
  `capacity` int DEFAULT '20' COMMENT 'Sức chứa tối đa',
  `enrolled_count` int DEFAULT '0' COMMENT 'Số người đã đăng ký',
  `room` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Phòng tập',
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_class_trainer` (`trainer_id`),
  KEY `idx_class_status` (`status`),
  CONSTRAINT `fk_class_trainer` FOREIGN KEY (`trainer_id`) REFERENCES `trainers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Lớp tập nhóm';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `class_schedules`
--

LOCK TABLES `class_schedules` WRITE;
/*!40000 ALTER TABLE `class_schedules` DISABLE KEYS */;
INSERT INTO `class_schedules` VALUES (1,'Yoga Sáng Năng Động','yoga',1,'06:00 - 07:30','Thứ 2, Thứ 4, Thứ 6',25,0,'Phòng A1','active','2026-03-07 22:44:36'),(2,'Cardio Giảm Cân','cardio',2,'18:00 - 19:00','Thứ 3, Thứ 5, Thứ 7',30,1,'Phòng B2','active','2026-03-07 22:44:36'),(3,'Boxing Cơ Bản','boxing',1,'19:30 - 21:00','Thứ 2, Thứ 5',15,0,'Phòng C1','active','2026-03-07 22:44:36'),(4,'HIIT Đốt Mỡ','hiit',2,'17:00 - 18:00','Thứ 3, Thứ 6',20,0,'Phòng B1','active','2026-03-07 22:44:36'),(5,'Strength Training','strength',1,'07:00 - 08:30','Thứ 4, Thứ 7, Chủ Nhật',18,2,'Phòng Gym','active','2026-03-07 22:44:36');
/*!40000 ALTER TABLE `class_schedules` ENABLE KEYS */;
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
  PRIMARY KEY (`id`),
  KEY `idx_equipment_maintenance_equipment_id` (`equipment_id`),
  CONSTRAINT `fk_equipment_maintenance_equipment` FOREIGN KEY (`equipment_id`) REFERENCES `equipment` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Lịch sử bảo trì thiết bị';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `equipment_maintenance`
--

LOCK TABLES `equipment_maintenance` WRITE;
/*!40000 ALTER TABLE `equipment_maintenance` DISABLE KEYS */;
INSERT INTO `equipment_maintenance` VALUES (2,2,'2026-03-17','ggg');
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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `import_details`
--

LOCK TABLES `import_details` WRITE;
/*!40000 ALTER TABLE `import_details` DISABLE KEYS */;
INSERT INTO `import_details` VALUES (1,1,1,NULL,2,26000000.00),(2,2,NULL,1,10,1500000.00),(3,4,NULL,19,10,800001.00);
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
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `import_slips`
--

LOCK TABLES `import_slips` WRITE;
/*!40000 ALTER TABLE `import_slips` DISABLE KEYS */;
INSERT INTO `import_slips` VALUES (1,1,1,52000000.00,'2024-02-01 08:30:00','Nhập máy chạy bộ mới','Đã nhập'),(2,1,2,15000000.00,'2024-02-02 09:00:00','Nhập bổ sung Whey','Đã nhập'),(3,5,1,80000000.00,'2026-03-02 18:51:00','h','Đã nhập'),(4,2,2,8000010.00,'2026-03-11 17:37:00','lllllll','Đã nhập');
/*!40000 ALTER TABLE `import_slips` ENABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Hội viên đăng ký dinh dưỡng';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `member_nutrition_plans`
--

LOCK TABLES `member_nutrition_plans` WRITE;
/*!40000 ALTER TABLE `member_nutrition_plans` DISABLE KEYS */;
INSERT INTO `member_nutrition_plans` VALUES (1,2,1,'2026-02-19','2026-02-21','đã áp dụng');
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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `member_packages`
--

LOCK TABLES `member_packages` WRITE;
/*!40000 ALTER TABLE `member_packages` DISABLE KEYS */;
INSERT INTO `member_packages` VALUES (1,13,1,'2025-02-06','2025-03-06','active'),(2,3,2,'2026-02-10','2026-05-10','active');
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
  `status` enum('còn hiệu lực','đã dùng') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'còn hiệu lực',
  PRIMARY KEY (`id`),
  KEY `idx_member_services_member` (`member_id`),
  KEY `idx_member_services_service` (`service_id`),
  CONSTRAINT `member_services_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  CONSTRAINT `member_services_ibfk_2` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Hội viên sử dụng dịch vụ';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `member_services`
--

LOCK TABLES `member_services` WRITE;
/*!40000 ALTER TABLE `member_services` DISABLE KEYS */;
INSERT INTO `member_services` VALUES (1,2,4,'2026-02-19','2026-02-20','đã dùng');
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
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `member_tiers`
--

LOCK TABLES `member_tiers` WRITE;
/*!40000 ALTER TABLE `member_tiers` DISABLE KEYS */;
INSERT INTO `member_tiers` VALUES (1,'Đồng',1,0.00,0.00,'active','2026-01-26 18:51:00'),(2,'Bạc',2,3000000.00,5.00,'active','2026-01-26 18:51:00'),(3,'Vàng',3,10000000.00,10.00,'active','2026-01-26 18:51:00'),(4,'Bạch Kim',4,30000000.00,15.00,'active','2026-01-26 18:51:00'),(5,'Kim Cương',5,50000000.00,20.00,'active','2026-01-26 18:51:00');
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
  `activity_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Loại hoạt động: cardio, strength, yoga, hiit, boxing...',
  `intensity` enum('thấp','trung bình','cao','rất cao') COLLATE utf8mb4_unicode_ci DEFAULT 'trung bình' COMMENT 'Cường độ tập',
  `calories_burned` int DEFAULT '0' COMMENT 'Lượng calo đốt cháy (ước tính)',
  `note` text COLLATE utf8mb4_unicode_ci COMMENT 'Ghi chú của PT hoặc hội viên',
  `status` enum('dự kiến','đang tập','hoàn thành','huỷ') COLLATE utf8mb4_unicode_ci DEFAULT 'dự kiến' COMMENT 'Trạng thái buổi tập',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_member` (`member_id`),
  KEY `idx_trainer` (`trainer_id`),
  KEY `idx_training_date` (`training_date`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_mts_member` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_mts_trainer` FOREIGN KEY (`trainer_id`) REFERENCES `trainers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Lịch tập cá nhân của hội viên';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `member_training_schedules`
--

LOCK TABLES `member_training_schedules` WRITE;
/*!40000 ALTER TABLE `member_training_schedules` DISABLE KEYS */;
INSERT INTO `member_training_schedules` VALUES (1,1,1,'2026-03-10 06:00:00',60,'cardio','trung bình',450,'Tập chạy bộ và đạp xe','dự kiến','2026-03-07 22:44:36','2026-03-07 22:44:36'),(2,2,2,'2026-03-11 18:00:00',90,'strength','cao',600,'Tập tạ tay và chân','dự kiến','2026-03-07 22:44:36','2026-03-07 22:44:36'),(3,3,1,'2026-03-09 07:00:00',45,'yoga','thấp',200,'Yoga thư giãn','hoàn thành','2026-03-07 22:44:36','2026-03-07 22:44:36'),(4,13,NULL,'2026-03-12 19:00:00',60,'boxing','cao',550,'Tập tự do - luyện đấm bao cát','dự kiến','2026-03-07 22:44:36','2026-03-07 22:44:36'),(5,2,2,'2026-03-08 17:30:00',75,'hiit','rất cao',700,'HIIT cường độ cao, đốt calo','hoàn thành','2026-03-07 22:44:36','2026-03-07 22:44:36'),(6,3,2,'2026-03-17 18:00:00',60,'cardio','trung bình',0,'Lịch tạo tự động từ lớp Cardio Giảm Cân [CLASS_ID:2]','huỷ','2026-03-14 06:31:57','2026-03-14 06:32:23'),(7,3,1,'2026-03-16 19:30:00',60,'boxing','trung bình',0,'Lịch tạo tự động từ lớp Boxing Cơ Bản [CLASS_ID:3]','huỷ','2026-03-14 06:32:28','2026-03-14 06:33:00'),(8,3,1,'2026-03-15 07:00:00',60,'strength','trung bình',0,'Lịch tạo tự động từ lớp Strength Training [CLASS_ID:5]','dự kiến','2026-03-14 06:33:03','2026-03-14 06:33:03');
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
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `members`
--

LOCK TABLES `members` WRITE;
/*!40000 ALTER TABLE `members` DISABLE KEYS */;
INSERT INTO `members` VALUES (1,1,'Trương Trung Kiên','0912345678','aaaaaaaaaaaaaaaaaaaaaaaaa','2024-01-15','active',170.00,65.00,3,14989750.00),(2,2,'Nguyễn Tường Huy','0987654321','Quận 3, TP.HCM','2024-02-20','active',180.00,70.00,3,23390000.00),(3,3,'Nguyễn Nguyên Bảo','0903456789','Thủ Đức, TP.HCM','2023-11-05','active',165.00,70.00,2,3882000.00),(13,9,'test','0786026878','666 Võ Văn Kiệt, Gò Vấp, TP.HCM','2026-02-15','active',180.00,55.00,1,0.00);
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
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `membership_packages`
--

LOCK TABLES `membership_packages` WRITE;
/*!40000 ALTER TABLE `membership_packages` DISABLE KEYS */;
INSERT INTO `membership_packages` VALUES (1,'Gói 1 Tháng',1,500000.00,'Tập gym không giới hạn 1 tháng','active'),(2,'Gói 3 Tháng',3,1350000.00,'Tiết kiệm hơn so với gói lẻ','active'),(3,'Gói 6 Tháng',6,2500000.00,'Ưu đãi mạnh cho hội viên lâu dài','active'),(4,'Gói 12 Tháng',12,4800000.00,'Gói năm – rẻ nhất','active'),(6,'test',12,24000.00,'test','active');
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
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Thông báo cho người dùng';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
INSERT INTO `notifications` VALUES (1,2,'aaa','aaa',0,'2026-03-05 09:45:25'),(2,1,'66','6677',0,'2026-03-11 09:56:56'),(3,1,'3636','363636',0,'2026-03-15 10:04:15'),(4,2,'3636','363636',0,'2026-03-15 10:04:15'),(5,3,'3636','363636',0,'2026-03-15 10:04:15'),(6,9,'3636','363636',0,'2026-03-15 10:04:15'),(7,10,'3636','363636',0,'2026-03-15 10:04:15'),(8,11,'3636','363636',0,'2026-03-15 10:04:15'),(9,12,'3636','363636',0,'2026-03-15 10:04:15'),(10,13,'3636','363636',0,'2026-03-15 10:04:15'),(11,14,'3636','363636',0,'2026-03-15 10:04:15'),(12,15,'3636','363636',0,'2026-03-15 10:04:15'),(13,16,'3636','363636',0,'2026-03-15 10:04:15'),(14,17,'3636','363636',0,'2026-03-15 10:04:15'),(15,18,'3636','363636',0,'2026-03-15 10:04:15'),(16,19,'3636','363636',0,'2026-03-15 10:04:15'),(17,20,'3636','363636',0,'2026-03-15 10:04:15'),(18,21,'3636','363636',0,'2026-03-15 10:04:15'),(19,22,'3636','363636',0,'2026-03-15 10:04:15'),(20,17,'Feedback mới từ hội viên','Hội viên Trương Trung Kiên vừa gửi feedback mới. Vui lòng kiểm tra mục Phản hồi.',0,'2026-03-15 11:37:55');
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
INSERT INTO `nutrition_plan_items` VALUES (1,1,2,1.00,'sáng, chiều, tối','aaaaaaa'),(2,1,1,2.00,'Bữa trưa','Tăng lượng protein chính trong ngày'),(3,1,2,1.50,'Bữa trưa','Bổ sung tinh bột vừa phải'),(4,2,5,2.00,'Bữa sáng','Tinh bột chậm giúp no lâu'),(5,2,3,1.00,'Bữa sáng','Bổ sung protein và chất béo tốt'),(6,3,4,1.50,'Bữa tối','Omega-3 hỗ trợ phục hồi cơ');
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
INSERT INTO `nutrition_plans` VALUES (1,'Tăng cân cơ bản 2500 Calo','tăng cân',2500,'BMI < 18.5','Chế độ ăn dành cho người gầy, tập trung vào thực phẩm giàu năng lượng, tăng khẩu phần tinh bột và protein.','hoạt động'),(2,'Giảm cân khoa học 1500 Calo','giảm cân',1500,'BMI 23 - 27','Thực đơn giảm calo an toàn, hạn chế đường và chất béo xấu, tăng rau xanh và protein nạc.','hoạt động'),(3,'Tăng cơ chuyên sâu 2800 Calo','tăng cơ',2800,'BMI 18.5 - 24.9','Chế độ ăn giàu protein kết hợp carb phức, phù hợp người tập gym 4-6 buổi/tuần.','hoạt động'),(4,'Giảm mỡ Low-carb 1700 Calo','giảm mỡ',1700,'BMI > 23','Giảm tinh bột nhanh, ưu tiên protein và chất béo tốt, hỗ trợ đốt mỡ hiệu quả.','không hoạt động'),(5,'Tư vấn dinh dưỡng cá nhân hóa','tư vấn',NULL,'Mọi chỉ số BMI','Gói tư vấn 1:1 cùng chuyên gia, xây dựng thực đơn phù hợp thể trạng và mục tiêu.','hoạt động'),(6,'test','giảm cân',3000,'23','test','hoạt động');
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
  `item_type` enum('product','package','service') COLLATE utf8mb4_unicode_ci NOT NULL,
  `item_id` int NOT NULL,
  `item_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `price` decimal(15,2) NOT NULL,
  `quantity` int DEFAULT '1',
  `discount` decimal(15,2) DEFAULT '0.00',
  `subtotal` decimal(15,2) GENERATED ALWAYS AS (((`price` * `quantity`) - `discount`)) STORED,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `idx_item_lookup` (`item_type`,`item_id`),
  CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_items`
--

LOCK TABLES `order_items` WRITE;
/*!40000 ALTER TABLE `order_items` DISABLE KEYS */;
INSERT INTO `order_items` (`id`, `order_id`, `item_type`, `item_id`, `item_name`, `price`, `quantity`, `discount`, `created_at`) VALUES (1,1,'product',1,'Whey Gold Standard 5lbs',1850000.00,1,100000.00,'2026-02-20 00:09:14'),(2,1,'product',2,'Nước khoáng Lavie 500ml',10000.00,10,0.00,'2026-02-20 00:09:14'),(3,8,'service',3,'test',50000.00,2,0.00,'2026-02-20 00:09:14'),(4,9,'product',3,'Găng tay tập Gym Adidas',450000.00,1,50000.00,'2026-02-20 00:09:14'),(5,10,'package',1,'Gói 1 Tháng',500000.00,1,0.00,'2026-02-20 00:09:14'),(6,10,'product',4,'test_product',50000.00,2,1000.00,'2026-02-20 00:09:14'),(7,11,'package',3,'Gói 6 Tháng',2500000.00,1,200000.00,'2026-02-20 00:09:14'),(8,11,'service',4,'test2',70000.00,3,10000.00,'2026-02-20 00:09:14'),(9,11,'product',1,'Whey Gold Standard 5lbs',1850000.00,1,200000.00,'2026-02-20 00:09:14'),(10,12,'product',19,'Mass Gainer Serious Mass 6lbs',1350000.00,1,0.00,'2026-03-07 22:01:30'),(11,13,'product',19,'Mass Gainer Serious Mass 6lbs',1350000.00,1,0.00,'2026-03-07 22:02:31'),(12,14,'product',19,'Mass Gainer Serious Mass 6lbs',1350000.00,1,0.00,'2026-03-07 22:05:14'),(13,15,'product',19,'Mass Gainer Serious Mass 6lbs',1350000.00,1,0.00,'2026-03-07 22:08:05'),(14,16,'product',19,'Mass Gainer Serious Mass 6lbs',1350000.00,10,0.00,'2026-03-07 23:08:05'),(15,17,'product',1,'Whey Gold Standard 5lbs',1850000.00,10,0.00,'2026-03-07 23:12:37'),(16,18,'product',2,'Nước khoáng Lavie 500ml',10000.00,1,0.00,'2026-03-07 23:15:07'),(17,19,'product',2,'Nước khoáng Lavie 500ml',10000.00,1,0.00,'2026-03-07 23:15:28'),(18,20,'product',9,'BCAA 2:1:1 400g',550000.00,1,0.00,'2026-03-07 23:16:29'),(19,21,'product',19,'Mass Gainer Serious Mass 6lbs',1350000.00,5,0.00,'2026-03-11 09:58:19'),(20,22,'product',19,'Mass Gainer Serious Mass 6lbs',1282500.00,3,0.00,'2026-03-11 10:25:47'),(21,23,'product',19,'Mass Gainer Serious Mass 6lbs',1215000.00,2,0.00,'2026-03-11 10:29:29'),(22,24,'product',19,'Mass Gainer Serious Mass 6lbs',1282500.00,2,0.00,'2026-03-11 10:35:28');
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
  `transfer_code` varchar(20) DEFAULT NULL,
  `proof_img` varchar(255) DEFAULT NULL,
  `status` enum('pending','confirmed','delivered','cancelled') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  PRIMARY KEY (`id`),
  KEY `member_id` (`member_id`),
  KEY `address_id` (`address_id`),
  CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`),
  CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`address_id`) REFERENCES `addresses` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `orders`
--

LOCK TABLES `orders` WRITE;
/*!40000 ALTER TABLE `orders` DISABLE KEYS */;
INSERT INTO `orders` VALUES (1,1,1,'2026-02-18 09:15:00',250000.00,'cash','delivered'),(8,1,11,'2026-02-18 18:50:10',100000.00,'cash','delivered'),(9,2,12,'2026-02-18 11:45:00',125000.00,'cash','delivered'),(10,2,13,'2026-02-18 13:00:00',99000.99,'online','pending'),(11,3,14,'2026-02-18 14:20:00',760000.00,'cash','cancelled'),(12,2,NULL,'2026-03-08 05:01:30',1380000.00,'online','delivered'),(13,2,NULL,'2026-03-08 05:02:31',1380000.00,'cash','cancelled'),(14,2,27,'2026-03-08 05:05:14',1380000.00,'cash','pending'),(15,2,NULL,'2026-03-08 05:08:05',1380000.00,'online','pending'),(16,2,NULL,'2026-03-08 06:08:05',13530000.00,'cash','delivered'),(17,2,NULL,'2026-03-08 06:12:37',18530000.00,'cash','cancelled'),(18,2,NULL,'2026-03-08 06:15:07',40000.00,'cash','pending'),(19,2,NULL,'2026-03-08 06:15:28',40000.00,'cash','pending'),(20,2,NULL,'2026-03-08 06:16:29',580000.00,'cash','pending'),(21,1,NULL,'2026-03-11 16:58:19',6780000.00,'cash','delivered'),(22,1,NULL,'2026-03-11 17:25:47',3492750.00,'cash','delivered'),(23,1,NULL,'2026-03-11 17:29:29',2217000.00,'cash','delivered'),(24,3,NULL,'2026-03-11 17:35:28',2082000.00,'cash','pending');
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
  `code` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
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
  `img` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Đường dẫn hình ảnh sản phẩm',
  `unit` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'hộp' COMMENT 'Đơn vị tính: hộp, chai, cái...',
  `stock_quantity` int DEFAULT '0' COMMENT 'Số lượng tồn kho',
  `selling_price` decimal(15,2) DEFAULT '0.00' COMMENT 'Giá bán lẻ cho hội viên',
  `status` enum('active','inactive') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  PRIMARY KEY (`id`),
  KEY `fk_product_category` (`category_id`),
  CONSTRAINT `fk_product_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` VALUES (1,2,'Whey Gold Standard 5lbs','product_1772922977_1291.jpg','Hộp',30,1850000.00,'active'),(2,1,'Nước khoáng Lavie 500ml','product_1771589404_1838.jpg','Chai',98,10000.00,'active'),(3,1,'Găng tay tập Gym Adidas','product_1771589404_1838.jpg','Đôi',15,450000.00,'active'),(4,1,'test_product','product_1771589404_1838.jpg','chai',30,50000.00,'inactive'),(8,2,'Creatine Monohydrate 300g','product_1771589404_1838.jpg','hộp',60,450000.00,'active'),(9,2,'BCAA 2:1:1 400g','product_1771589404_1838.jpg','hộp',34,550000.00,'active'),(10,3,'Nước điện giải Pocari 500ml','product_1771589404_1838.jpg','chai',200,15000.00,'active'),(12,4,'Găng tay tập gym cao cấp','product_1771589404_1838.jpg','cái',70,120000.00,'active'),(13,4,'Đai lưng tập gym','product_1771589404_1838.jpg','cái',30,250000.00,'active'),(14,5,'Dây kháng lực 5 mức','product_1771589404_1838.jpg','bộ',45,180000.00,'active'),(16,7,'Combo Whey + Creatine','product_1771589404_1838.jpg','combo',20,2100000.00,'inactive'),(17,8,'Whey Dymatize Elite 5lbs','product_1771589404_1838.jpg','hộp',25,1950000.00,'active'),(18,9,'Fat Burner L-Carnitine','product_1771589404_1838.jpg','hộp',30,650000.00,'active'),(19,10,'Mass Gainer Serious Mass 6lbs','product_1772920712_1461.jpg','hộp',33,1350000.00,'active'),(20,2,'kien','product_1773475707_7704.jpg','hộp',50,500000.00,'active'),(21,5,'kiên2','product_1773475759_5841.jpg','cái',2,100000.00,'active');
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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `promotion_usage`
--

LOCK TABLES `promotion_usage` WRITE;
/*!40000 ALTER TABLE `promotion_usage` DISABLE KEYS */;
INSERT INTO `promotion_usage` VALUES (1,1,1,22,384750.00,'2026-03-11 10:25:47'),(2,1,7,23,243000.00,'2026-03-11 10:29:29'),(3,3,6,24,513000.00,'2026-03-11 10:35:28');
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
INSERT INTO `role_permissions` VALUES (4,1),(7,1),(5,2),(5,4),(6,6),(5,7),(6,7),(5,10),(6,10);
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
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (4,'admin','Quản trị toàn hệ thống','active'),(5,'staff','Nhân viên hỗ trợ khách hàng','active'),(6,'member','Hội viên sử dụng dịch vụ','active'),(7,'aaaaaaaaaa','aaaaaaaaaaa','active');
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
  `img` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Đường dẫn hình ảnh dịch vụ',
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
INSERT INTO `services` VALUES (3,'test',NULL,'thư giãn',50000.00,'test','hoạt động'),(4,'test2',NULL,'thư giãn',70000.00,'test2','hoạt động'),(5,'aaa',NULL,'thư giãn',5000.00,'aaaaa','hoạt động');
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
  `position` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('active','inactive') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  PRIMARY KEY (`id`),
  KEY `users_id` (`users_id`),
  CONSTRAINT `staff_ibfk_1` FOREIGN KEY (`users_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `staff`
--

LOCK TABLES `staff` WRITE;
/*!40000 ALTER TABLE `staff` DISABLE KEYS */;
INSERT INTO `staff` VALUES (1,13,'Phạm Văn Đức','Nhân viên lễ tân','active'),(2,14,'Hoàng Thị Lan','Nhân viên tư vấn','active'),(3,20,'Nguyễn Minh Tuấn','Nhân viên kỹ thuật','active'),(4,21,'Trần Thanh Hương','Nhân viên chăm sóc khách hàng','active'),(5,22,'Lê Văn Hải','Quản lý ca','active');
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
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `suppliers`
--

LOCK TABLES `suppliers` WRITE;
/*!40000 ALTER TABLE `suppliers` DISABLE KEYS */;
INSERT INTO `suppliers` VALUES (1,'Công ty Thể Thao Đại Việt','0901234567','123 Lý Thường Kiệt, Q10, TP.HCM','2024-01-27 10:00:00'),(2,'Whey Store VN','0909888777','456 CMT8, Q3, TP.HCM','2024-01-27 10:00:00'),(17,'a','a','a','2026-02-17 10:31:25');
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
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tier_promotions`
--

LOCK TABLES `tier_promotions` WRITE;
/*!40000 ALTER TABLE `tier_promotions` DISABLE KEYS */;
INSERT INTO `tier_promotions` VALUES (1,'Giảm PT cho hội viên Bạc',2,'percentage',10.00,'[\"personal_training\"]','2024-01-01','2029-07-31',10000,'active'),(2,'Tặng 2 buổi tập cho hội viên Vàng',3,'package',2.00,'[\"gym_session\"]','2024-01-01','2030-11-21',50,'active'),(3,'Giảm 50K phí đăng ký Kim Cương',5,'fixed',50000.00,'[\"registration_fee\"]','2024-01-01','2030-12-20',NULL,'active'),(4,'Giảm 15% supplement cho Bạch Kim',4,'percentage',15.00,'[\"protein\", \"vitamin\"]','2024-01-01','2031-07-24',20000,'active'),(5,'aaa',4,'fixed',200000.00,NULL,'2025-06-13','2030-06-20',NULL,'active'),(6,'Giảm PT cho hội viên Bạc 200slot',2,'percentage',20.00,NULL,'2026-03-01','2030-06-26',200,'active'),(7,'Giảm đặc biệt cho HV Vàng 100 slot',3,'percentage',10.00,'[\"personal_training\"]','2026-03-01','2029-06-28',100,'active');
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
  `phone` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `trainers`
--

LOCK TABLES `trainers` WRITE;
/*!40000 ALTER TABLE `trainers` DISABLE KEYS */;
INSERT INTO `trainers` VALUES (1,'test',NULL,'Nội bộ','0786026878','hoạt động'),(2,'Trương Trung Kiên',NULL,'Nội bộ','0786026878','hoạt động'),(3,'Nguyễn Nguyên Bảo',NULL,'Nội bộ','07860263336','hoạt động');
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
  `note` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`id`),
  KEY `member_id` (`member_id`),
  KEY `trainer_id` (`trainer_id`),
  CONSTRAINT `training_schedules_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  CONSTRAINT `training_schedules_ibfk_2` FOREIGN KEY (`trainer_id`) REFERENCES `trainers` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `training_schedules`
--

LOCK TABLES `training_schedules` WRITE;
/*!40000 ALTER TABLE `training_schedules` DISABLE KEYS */;
INSERT INTO `training_schedules` VALUES (1,2,1,'2026-02-20 11:00:00','test'),(2,3,1,'2026-03-21 13:33:00','nnnnnnnnnnnnn'),(3,3,2,'2026-03-21 17:33:00','');
/*!40000 ALTER TABLE `training_schedules` ENABLE KEYS */;
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
  `status` enum('active','locked') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  KEY `role_id` (`role_id`),
  CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,3,'truongtrungkien','$2y$10$JsEamv3qig6Xc3h5YqlPnuOO13Z0GyZankG4dMRPVjJhLRhudLJXy','kien@gmail.com','active','2026-01-26 19:05:40'),(2,3,'nguyentuonghuy','123456','huy@gmail.com','active','2026-01-26 19:05:40'),(3,3,'nguyennguyenbao','123456','bao@gmail.com','active','2026-01-26 19:05:40'),(9,6,'test1','123456','test1@gmail.com','active','2026-02-15 11:22:23'),(10,6,'nguyenvana','123456','nguyenvana@gmail.com','active','2026-03-11 03:00:00'),(11,6,'tranthib','123456','tranthib@gmail.com','active','2026-03-11 03:05:00'),(12,6,'lethic','123456','lethic@gmail.com','active','2026-03-11 03:10:00'),(13,5,'phamvand','123456','phamvand@gmail.com','active','2026-03-11 03:15:00'),(14,5,'hoangthie','123456','hoangthie@gmail.com','active','2026-03-11 03:20:00'),(15,6,'vuhongf','123456','vuhongf@gmail.com','active','2026-03-11 03:25:00'),(16,6,'dovanh','123456','dovanh@gmail.com','active','2026-03-11 03:30:00'),(17,4,'admin2','123456','admin2@gmail.com','active','2026-03-11 03:35:00'),(18,6,'nguyenvanminh','123456','nguyenvanminh@gmail.com','active','2026-03-11 03:40:00'),(19,6,'tranthilan','123456','tranthilan@gmail.com','active','2026-03-11 03:45:00'),(20,5,'nguyenminhtuan','123456','nguyenminhtuan@gmail.com','active','2026-03-11 03:50:00'),(21,5,'tranthanhhuong','123456','tranthanhhuong@gmail.com','active','2026-03-11 03:55:00'),(22,5,'levanhai','123456','levanhai@gmail.com','active','2026-03-11 04:00:00'),(23,7,'h','1','h@gmail.com','active','2026-03-15 11:28:26');
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

-- Dump completed on 2026-03-16 23:08:20
