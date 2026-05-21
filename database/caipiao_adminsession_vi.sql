--
-- Table structure for table `caipiao_adminsession`
--

DROP TABLE IF EXISTS `caipiao_adminsession`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `caipiao_adminsession` (
  `sid` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID phiên',
  `userid` int(11) NOT NULL COMMENT 'ID quản trị viên',
  `username` char(60) NOT NULL COMMENT 'Tên đăng nhập',
  `sessionid` char(32) NOT NULL COMMENT 'ID phiên đăng nhập',
  `ip` char(20) NOT NULL COMMENT 'IP đăng nhập',
  `time` int(11) NOT NULL COMMENT 'Thời gian đăng nhập',
  PRIMARY KEY (`sid`),
  KEY `userid` (`userid`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='Bảng phiên đăng nhập quản trị';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `caipiao_adminsession`
--

LOCK TABLES `caipiao_adminsession` WRITE;
/*!40000 ALTER TABLE `caipiao_adminsession` DISABLE KEYS */;
INSERT INTO `caipiao_adminsession` VALUES (20,11,'admin','4ec37b3e00098c99d1d6f76330596b1b','156.59.13.143',1764411199);
/*!40000 ALTER TABLE `caipiao_adminsession` ENABLE KEYS */;
UNLOCK TABLES;
