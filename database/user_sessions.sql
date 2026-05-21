--
-- Table structure for table `user_sessions`
--

DROP TABLE IF EXISTS `user_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_sessions` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) NOT NULL COMMENT 'ID người dùng',
  `session_id` char(32) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ID phiên',
  `ip` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Địa chỉ IP',
  `ua` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'User Agent',
  `expired_at` datetime NOT NULL COMMENT 'Thời gian hết hạn',
  `created_at` datetime NOT NULL COMMENT 'Thời gian tạo',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_session` (`session_id`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Bảng phiên đăng nhập người dùng';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_sessions`
--

LOCK TABLES `user_sessions` WRITE;
/*!40000 ALTER TABLE `user_sessions` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_sessions` ENABLE KEYS */;
UNLOCK TABLES;
