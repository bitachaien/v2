--
-- Table structure for table `caipiao_bet_stats`
--

DROP TABLE IF EXISTS `caipiao_bet_stats`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `caipiao_bet_stats` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL COMMENT 'ID người dùng',
  `category_code` varchar(20) NOT NULL COMMENT 'Phân loại trò chơi',
  `vendor_code` varchar(20) NOT NULL COMMENT 'Mã nền tảng',
  `total_bet` decimal(20,2) NOT NULL DEFAULT '0.00' COMMENT 'Tổng cược hợp lệ tích lũy',
  `created_at` int(11) NOT NULL DEFAULT '0',
  `updated_at` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_uid_cat_vendor` (`uid`,`category_code`,`vendor_code`),
  KEY `idx_uid` (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Thống kê cược tích lũy người dùng';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `caipiao_bet_stats`
--

LOCK TABLES `caipiao_bet_stats` WRITE;
/*!40000 ALTER TABLE `caipiao_bet_stats` DISABLE KEYS */;
/*!40000 ALTER TABLE `caipiao_bet_stats` ENABLE KEYS */;
UNLOCK TABLES;
