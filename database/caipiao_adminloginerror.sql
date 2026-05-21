--
-- Table structure for table `caipiao_adminloginerror`
--

DROP TABLE IF EXISTS `caipiao_adminloginerror`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `caipiao_adminloginerror` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID khóa chính',
  `username` char(30) NOT NULL COMMENT 'Tên đăng nhập',
  `ip` char(20) NOT NULL COMMENT 'Địa chỉ IP',
  `time` int(11) NOT NULL COMMENT 'Thời gian lỗi',
  `errornum` smallint(6) NOT NULL COMMENT 'Số lần lỗi',
  `locktime` int(11) NOT NULL COMMENT 'Thời gian khóa',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='Bảng lỗi đăng nhập quản trị';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `caipiao_adminloginerror`
--

LOCK TABLES `caipiao_adminloginerror` WRITE;
/*!40000 ALTER TABLE `caipiao_adminloginerror` DISABLE KEYS */;
/*!40000 ALTER TABLE `caipiao_adminloginerror` ENABLE KEYS */;
UNLOCK TABLES;
