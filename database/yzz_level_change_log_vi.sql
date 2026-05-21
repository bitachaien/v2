--
-- Table structure for table `yzz_level_change_log`
--

DROP TABLE IF EXISTS `yzz_level_change_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `yzz_level_change_log` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID khóa chính',
  `user_id` bigint(20) unsigned NOT NULL COMMENT 'ID người dùng',
  `from_level_id` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT 'ID cấp độ cũ',
  `to_level_id` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT 'ID cấp độ mới',
  `change_type` tinyint(4) NOT NULL DEFAULT '1' COMMENT 'Loại thay đổi: 1-nâng cấp, 2-hạ cấp, 3-điều chỉnh hệ thống',
  `change_reason` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'Lý do thay đổi',
  `operator_id` bigint(20) unsigned NOT NULL DEFAULT '0' COMMENT 'ID người thao tác (0-hệ thống)',
  `gmt_create` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Thời gian tạo',
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_gmt_create` (`gmt_create`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Bảng nhật ký thay đổi cấp độ';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `yzz_level_change_log`
--

LOCK TABLES `yzz_level_change_log` WRITE;
/*!40000 ALTER TABLE `yzz_level_change_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `yzz_level_change_log` ENABLE KEYS */;
UNLOCK TABLES;
