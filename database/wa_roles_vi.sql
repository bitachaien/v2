--
-- Table structure for table `wa_roles`
--

DROP TABLE IF EXISTS `wa_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wa_roles` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Khóa chính',
  `name` varchar(80) NOT NULL COMMENT 'Vai trò',
  `rules` text COMMENT 'Quyền hạn',
  `created_at` datetime NOT NULL COMMENT 'Thời gian tạo',
  `updated_at` datetime NOT NULL COMMENT 'Thời gian cập nhật',
  `pid` int(10) unsigned DEFAULT NULL COMMENT 'ID vai trò cấp cha',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COMMENT='Bảng vai trò quản trị viên';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wa_roles`
--

LOCK TABLES `wa_roles` WRITE;
/*!40000 ALTER TABLE `wa_roles` DISABLE KEYS */;
INSERT INTO `wa_roles` VALUES (1,'超级管理员','*','2022-08-13 16:15:01','2022-12-23 12:05:07',NULL);
/*!40000 ALTER TABLE `wa_roles` ENABLE KEYS */;
UNLOCK TABLES;
