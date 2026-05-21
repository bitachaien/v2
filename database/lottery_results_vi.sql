--
-- Table structure for table `lottery_results`
--

DROP TABLE IF EXISTS `lottery_results`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lottery_results` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `lottery_id` bigint(20) NOT NULL COMMENT 'ID loại xổ số',
  `lottery_key` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Mã loại xổ số',
  `title` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Tên loại xổ số',
  `expect` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Mã kỳ quay',
  `open_code` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Số mở thưởng',
  `source_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Dữ liệu gốc mở thưởng',
  `source` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Nguồn dữ liệu',
  `is_drawn` tinyint(4) NOT NULL DEFAULT '0' COMMENT '0-chưa quay, 1-đã quay',
  `open_time` datetime NOT NULL COMMENT 'Thời gian mở thưởng',
  `created_at` datetime NOT NULL COMMENT 'Thời gian tạo',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_result` (`lottery_key`,`expect`),
  KEY `idx_lottery_time` (`lottery_id`,`open_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Bảng kết quả mở thưởng';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lottery_results`
--

LOCK TABLES `lottery_results` WRITE;
/*!40000 ALTER TABLE `lottery_results` DISABLE KEYS */;
/*!40000 ALTER TABLE `lottery_results` ENABLE KEYS */;
UNLOCK TABLES;
