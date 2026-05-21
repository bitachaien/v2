--
-- Table structure for table `yzz_user_level`
--

DROP TABLE IF EXISTS `yzz_user_level`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `yzz_user_level` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID khóa chính',
  `user_id` bigint(20) unsigned NOT NULL COMMENT 'ID người dùng',
  `current_level_id` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT 'ID cấp độ hiện tại',
  `current_points` bigint(20) unsigned NOT NULL DEFAULT '0' COMMENT 'Điểm tích lũy hiện tại',
  `total_points` bigint(20) unsigned NOT NULL DEFAULT '0' COMMENT 'Tổng điểm tích lũy',
  `pending_reward` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT 'Số tiền thưởng chờ nhận',
  `last_upgrade_time` datetime DEFAULT NULL COMMENT 'Thời gian nâng cấp gần nhất',
  `gmt_create` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Thời gian tạo',
  `gmt_modified` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Thời gian cập nhật',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_id` (`user_id`),
  KEY `idx_current_level_id` (`current_level_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Bảng cấp độ người dùng';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `yzz_user_level`
--

LOCK TABLES `yzz_user_level` WRITE;
/*!40000 ALTER TABLE `yzz_user_level` DISABLE KEYS */;
INSERT INTO `yzz_user_level` VALUES (5,9874,6,0,0,0.00,'2026-12-02 12:10:09','2026-12-02 11:12:52','2026-12-02 12:10:09');
/*!40000 ALTER TABLE `yzz_user_level` ENABLE KEYS */;
UNLOCK TABLES;
