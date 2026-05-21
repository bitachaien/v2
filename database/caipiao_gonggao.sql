--
-- Table structure for table `caipiao_gonggao`
--

DROP TABLE IF EXISTS `caipiao_gonggao`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `caipiao_gonggao` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID thông báo',
  `title` varchar(255) NOT NULL COMMENT 'Tiêu đề thông báo',
  `content` text NOT NULL COMMENT 'Nội dung thông báo',
  `type` varchar(20) DEFAULT 'system' COMMENT 'Loại thông báo: system-hệ thống, activity-hoạt động, update-cập nhật',
  `oddtime` int(11) NOT NULL COMMENT 'Thời gian tạo',
  PRIMARY KEY (`id`),
  KEY `idx_type` (`type`)
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='Bảng thông báo công khai';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `caipiao_gonggao`
--

LOCK TABLES `caipiao_gonggao` WRITE;
/*!40000 ALTER TABLE `caipiao_gonggao` DISABLE KEYS */;
/*!40000 ALTER TABLE `caipiao_gonggao` ENABLE KEYS */;
UNLOCK TABLES;
