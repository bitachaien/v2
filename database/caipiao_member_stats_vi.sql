--
-- Table structure for table `caipiao_member_stats`
--

DROP TABLE IF EXISTS `caipiao_member_stats`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `caipiao_member_stats` (
  `uid` bigint(20) NOT NULL COMMENT 'ID người dùng',
  `level` int(11) NOT NULL DEFAULT '1' COMMENT 'Người dùngCấp độ',
  `follower_count` int(11) NOT NULL DEFAULT '0' COMMENT '',
  `hemai_count` int(11) NOT NULL DEFAULT '0' COMMENT 'cược nhóm',
  `hemai_win_count` int(11) NOT NULL DEFAULT '0' COMMENT 'trúng thưởng',
  `last_5_win` int(11) NOT NULL DEFAULT '0' COMMENT '5trúng thưởng',
  `total_amount` decimal(18,2) NOT NULL DEFAULT '0.00' COMMENT 'tích lũySố tiền',
  `total_win` decimal(18,2) NOT NULL DEFAULT '0.00' COMMENT 'tích lũytrúng thưởng',
  `updatetime` bigint(20) NOT NULL DEFAULT '0' COMMENT 'Thời gian cập nhật',
  PRIMARY KEY (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Người dùng统计Bảng';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `caipiao_member_stats`
--

LOCK TABLES `caipiao_member_stats` WRITE;
/*!40000 ALTER TABLE `caipiao_member_stats` DISABLE KEYS */;
INSERT INTO `caipiao_member_stats` VALUES (1,1,6,3,1,1,6300.00,8800.00,1763991411),(2,1,4,1,0,0,2000.00,0.00,1763987070),(3,3,45,32,18,3,58000.00,72000.00,1763986892),(4,2,12,8,4,2,15000.00,18000.00,1763986892),(5,2,8,5,2,1,8000.00,6500.00,1763986892);
/*!40000 ALTER TABLE `caipiao_member_stats` ENABLE KEYS */;
UNLOCK TABLES;
