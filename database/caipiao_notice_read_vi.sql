--
-- Table structure for table `caipiao_notice_read`
--

DROP TABLE IF EXISTS `caipiao_notice_read`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `caipiao_notice_read` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID bản ghi',
  `uid` int(11) NOT NULL COMMENT 'ID người dùng',
  `notice_id` int(11) NOT NULL COMMENT 'ID thông báo',
  `read_time` int(11) DEFAULT NULL COMMENT 'Thời gian đọc',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uid_notice` (`uid`,`notice_id`) COMMENT 'Người dùng-thông báo',
  KEY `idx_uid` (`uid`),
  KEY `idx_notice_id` (`notice_id`),
  KEY `idx_read_time` (`read_time`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COMMENT='Bảng lịch sử đọc thông báo';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `caipiao_notice_read`
--

LOCK TABLES `caipiao_notice_read` WRITE;
/*!40000 ALTER TABLE `caipiao_notice_read` DISABLE KEYS */;
INSERT INTO `caipiao_notice_read` VALUES (1,9872,3,1764450151),(2,9872,2,1764450154),(3,9872,1,1764450156),(4,9872,4,1764621247),(5,9872,5,1764621279),(6,9872,6,1766112397);
/*!40000 ALTER TABLE `caipiao_notice_read` ENABLE KEYS */;
UNLOCK TABLES;
