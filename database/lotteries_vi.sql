--
-- Table structure for table `lotteries`
--

DROP TABLE IF EXISTS `lotteries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lotteries` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `key` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Mã loại xổ số',
  `title` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Tên loại xổ số',
  `type` enum('ssc','k3','pk10','keno','x5','lhc','xy28','dpc') COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Loại xổ số',
  `is_open` tinyint(4) NOT NULL DEFAULT '1' COMMENT '0-đóng, 1-mở',
  `is_system` tinyint(4) NOT NULL DEFAULT '0' COMMENT '0-thủ công, 1-hệ thống',
  `period_count` int(11) NOT NULL COMMENT 'Số kỳ mỗi ngày',
  `period_interval_sec` int(11) NOT NULL COMMENT 'Khoảng cách giữa các kỳ (giây)',
  `close_time1` time NOT NULL DEFAULT '00:00:00' COMMENT 'Thời gian đóng cửa 1',
  `close_time2` time NOT NULL DEFAULT '00:00:00' COMMENT 'Thời gian đóng cửa 2',
  `list_order` int(11) NOT NULL DEFAULT '0' COMMENT 'Thứ tự sắp xếp',
  `settings` json DEFAULT NULL COMMENT 'Cài đặt bổ sung',
  `created_at` datetime NOT NULL COMMENT 'Thời gian tạo',
  `updated_at` datetime NOT NULL COMMENT 'Thời gian cập nhật',
  PRIMARY KEY (`id`),
  UNIQUE KEY `key` (`key`),
  KEY `idx_is_open` (`is_open`),
  KEY `idx_list_order` (`list_order`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Bảng loại xổ số';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lotteries`
--

LOCK TABLES `lotteries` WRITE;
/*!40000 ALTER TABLE `lotteries` DISABLE KEYS */;
INSERT INTO `lotteries` VALUES (1,'cqssc','重庆时时彩','ssc',1,1,120,600,'00:00:00','00:00:00',1,NULL,'2026-10-28 11:20:35','2026-10-28 11:20:35'),(2,'jsk3','Giang Tô快3','k3',1,1,82,600,'00:00:00','00:00:00',2,NULL,'2026-10-28 11:20:35','2026-10-28 11:20:35');
/*!40000 ALTER TABLE `lotteries` ENABLE KEYS */;
UNLOCK TABLES;
