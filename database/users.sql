--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `parent_id` bigint(20) DEFAULT '0' COMMENT 'ID đại lý cấp trên',
  `group_id` bigint(20) NOT NULL COMMENT 'ID nhóm người dùng',
  `username` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Tên đăng nhập',
  `nickname` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'Biệt danh',
  `password_hash` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Mật khẩu đã mã hóa',
  `trade_password_hash` varchar(128) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Mật khẩu giao dịch đã mã hóa',
  `email` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Email',
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Số điện thoại',
  `is_agent` tinyint(4) NOT NULL DEFAULT '0' COMMENT '0-người dùng thường, 1-đại lý',
  `is_internal` tinyint(4) NOT NULL DEFAULT '0' COMMENT '0-người dùng thường, 1-tài khoản nội bộ',
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '1-bình thường, 0-vô hiệu hóa',
  `last_login_ip` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'IP đăng nhập cuối',
  `last_login_at` datetime DEFAULT NULL COMMENT 'Thời gian đăng nhập cuối',
  `created_at` datetime NOT NULL COMMENT 'Thời gian tạo',
  `updated_at` datetime NOT NULL COMMENT 'Thời gian cập nhật',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  KEY `idx_group_id` (`group_id`),
  KEY `idx_parent_id` (`parent_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Bảng người dùng';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,0,1,'test','测试Người dùng','$2y$10$h2Hq0Cp5MN5VUi8tjQBAR.Y5Aqjwht0n83BzjRlfdzVR1UmiNA7o2',NULL,NULL,NULL,0,0,1,'127.0.0.1','2026-10-28 19:22:11','2026-10-28 11:20:35','2026-10-28 19:22:11');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Temporary table structure for view `view_activity_full`
--

DROP TABLE IF EXISTS `view_activity_full`;
/*!50001 DROP VIEW IF EXISTS `view_activity_full`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `view_activity_full` AS SELECT 
 1 AS `id`,
 1 AS `title`,
 1 AS `desc`,
 1 AS `content`,
 1 AS `banner`,
 1 AS `type`,
 1 AS `type_code`,
 1 AS `category`,
 1 AS `start_date`,
 1 AS `end_date`,
 1 AS `status`,
 1 AS `sort`,
 1 AS `created_at`,
 1 AS `category_name`,
 1 AS `category_icon`,
 1 AS `type_name`*/;
SET character_set_client = @saved_cs_client;
