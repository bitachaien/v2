--
-- Table structure for table `caipiao_activity_user_stats`
--

DROP TABLE IF EXISTS `caipiao_activity_user_stats`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `caipiao_activity_user_stats` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL COMMENT 'ID người dùng',
  `activity_id` int(11) NOT NULL COMMENT 'ID hoạt động',
  `reward_type` varchar(30) NOT NULL COMMENT 'Loại phần thưởng',
  `period` varchar(20) NOT NULL COMMENT 'Chu kỳ: daily-20231201, weekly-2023W48, monthly-202312',
  `claim_count` int(11) NOT NULL DEFAULT '0' COMMENT 'Số lần đã nhận',
  `total_amount` decimal(20,2) NOT NULL DEFAULT '0.00' COMMENT 'Tổng số tiền',
  `last_claim_time` int(11) DEFAULT NULL COMMENT 'Thời gian nhận cuối',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_activity_period` (`uid`,`activity_id`,`reward_type`,`period`),
  KEY `idx_uid_period` (`uid`,`period`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Bảng thống kê nhận thưởng hoạt động của người dùng';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `caipiao_activity_user_stats`
--

LOCK TABLES `caipiao_activity_user_stats` WRITE;
/*!40000 ALTER TABLE `caipiao_activity_user_stats` DISABLE KEYS */;
/*!40000 ALTER TABLE `caipiao_activity_user_stats` ENABLE KEYS */;
UNLOCK TABLES;
