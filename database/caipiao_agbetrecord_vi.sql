--
-- Table structure for table `caipiao_agbetrecord`
--

DROP TABLE IF EXISTS `caipiao_agbetrecord`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `caipiao_agbetrecord` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID duy nhất toàn cầu nền tảng',
  `betId` varchar(45) DEFAULT NULL COMMENT 'ID đơn cược',
  `username` varchar(45) DEFAULT NULL COMMENT 'Tên người chơi',
  `platType` varchar(45) DEFAULT NULL COMMENT 'Loại nền tảng plat_type',
  `gameType` varchar(25) DEFAULT NULL COMMENT 'Loại trò chơi game_type',
  `betAmount` varchar(35) DEFAULT NULL COMMENT 'Số tiền đặt cược',
  `validAmount` decimal(15,4) DEFAULT NULL COMMENT 'Số tiền cược hợp lệ',
  `winLoss` decimal(15,4) DEFAULT NULL COMMENT 'Số tiền thắng thua',
  `gameName` varchar(15) DEFAULT NULL COMMENT 'Tên trò chơi',
  `betContent` varchar(255) DEFAULT NULL COMMENT 'Nội dung đặt cược',
  `awardResult` varchar(15) DEFAULT NULL COMMENT 'Kết quả mở thưởng',
  `betTime` datetime DEFAULT NULL COMMENT 'Thời gian đặt cược (Bắc Kinh)',
  `lastUpdateTime` datetime DEFAULT NULL COMMENT 'Thời gian cập nhật cuối (Bắc Kinh)',
  `status` varchar(255) DEFAULT NULL COMMENT 'Trạng thái: 1-đã thanh toán 2-chưa thanh toán 0-đơn không hợp lệ',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=17227968 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `caipiao_agbetrecord`
--

LOCK TABLES `caipiao_agbetrecord` WRITE;
/*!40000 ALTER TABLE `caipiao_agbetrecord` DISABLE KEYS */;
/*!40000 ALTER TABLE `caipiao_agbetrecord` ENABLE KEYS */;
UNLOCK TABLES;
