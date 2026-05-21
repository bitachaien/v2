--
-- Table structure for table `caipiao_message`
--

DROP TABLE IF EXISTS `caipiao_message`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `caipiao_message` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID tin nhắn',
  `sentid` int(11) NOT NULL COMMENT 'ID người gửi (0-hệ thống)',
  `sentname` char(20) NOT NULL COMMENT 'Tên người gửi (system-hệ thống)',
  `senttitle` varchar(255) NOT NULL COMMENT 'Tiêu đề tin nhắn',
  `sentcontext` text NOT NULL COMMENT 'Nội dung tin nhắn',
  `receid` int(11) NOT NULL COMMENT 'ID người nhận',
  `recename` char(20) NOT NULL COMMENT 'Tên người nhận',
  `senttime` int(11) NOT NULL COMMENT 'Thời gian gửi',
  `readtime` int(11) NOT NULL COMMENT 'Thời gian đọc',
  `sentdel` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'Người gửi đã xóa: 0-chưa, 1-đã xóa',
  `recdel` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'Người nhận đã xóa: 0-chưa, 1-đã xóa',
  PRIMARY KEY (`id`),
  KEY `sentid` (`sentid`),
  KEY `sentname` (`sentname`),
  KEY `receid` (`receid`),
  KEY `recename` (`recename`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='Bảng tin nhắn nội bộ';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `caipiao_message`
--

LOCK TABLES `caipiao_message` WRITE;
/*!40000 ALTER TABLE `caipiao_message` DISABLE KEYS */;
/*!40000 ALTER TABLE `caipiao_message` ENABLE KEYS */;
UNLOCK TABLES;
