--
-- Table structure for table `caipiao_agent_link`
--

DROP TABLE IF EXISTS `caipiao_agent_link`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `caipiao_agent_link` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `uid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT 'ID người dùng',
  `username` varchar(50) NOT NULL DEFAULT '' COMMENT 'Tên người dùng',
  `type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1-đại lý, 0-thành viên',
  `total_count` int(11) NOT NULL DEFAULT '-1' COMMENT 'Giới hạn số lần, -1 không giới hạn',
  `used_count` int(11) NOT NULL DEFAULT '0' COMMENT 'Số lần đã sử dụng',
  `template` varchar(50) NOT NULL DEFAULT 'default' COMMENT 'Mẫu đăng ký',
  `code` varchar(32) NOT NULL DEFAULT '' COMMENT 'Mã quảng bá',
  `created_at` int(11) NOT NULL DEFAULT '0',
  `updated_at` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_uid` (`uid`),
  KEY `idx_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Bảng liên kết quảng bá đại lý';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `caipiao_agent_link`
--

LOCK TABLES `caipiao_agent_link` WRITE;
/*!40000 ALTER TABLE `caipiao_agent_link` DISABLE KEYS */;
/*!40000 ALTER TABLE `caipiao_agent_link` ENABLE KEYS */;
UNLOCK TABLES;
