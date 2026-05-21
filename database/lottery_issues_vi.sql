--
-- Table structure for table `lottery_issues`
--

DROP TABLE IF EXISTS `lottery_issues`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lottery_issues` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `lottery_id` bigint(20) NOT NULL COMMENT 'ID loại xổ số',
  `expect` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Mã kỳ quay',
  `start_time` datetime NOT NULL COMMENT 'Thời gian bắt đầu',
  `end_time` datetime NOT NULL COMMENT 'Thời gian kết thúc',
  `close_ahead_sec` int(11) NOT NULL DEFAULT '120' COMMENT 'Đóng trước khi quay (giây)',
  `status` enum('init','opened','closed','drawn') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'init' COMMENT 'init-khởi tạo, opened-đang mở, closed-đã đóng, drawn-đã quay',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_issue` (`lottery_id`,`expect`),
  KEY `idx_lottery_start` (`lottery_id`,`start_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Bảng kỳ quay xổ số';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lottery_issues`
--

LOCK TABLES `lottery_issues` WRITE;
/*!40000 ALTER TABLE `lottery_issues` DISABLE KEYS */;
/*!40000 ALTER TABLE `lottery_issues` ENABLE KEYS */;
UNLOCK TABLES;
