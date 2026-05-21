--
-- Table structure for table `caipiao_membersession`
--

DROP TABLE IF EXISTS `caipiao_membersession`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `caipiao_membersession` (
  `sid` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL,
  `username` char(60) NOT NULL,
  `sessionid` char(32) NOT NULL,
  `ip` char(20) NOT NULL COMMENT 'IP đăng nhập',
  `time` int(11) NOT NULL COMMENT 'Thời gian đăng nhập',
  PRIMARY KEY (`sid`),
  KEY `userid` (`userid`)
) ENGINE=InnoDB AUTO_INCREMENT=392 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `caipiao_membersession`
--

LOCK TABLES `caipiao_membersession` WRITE;
/*!40000 ALTER TABLE `caipiao_membersession` DISABLE KEYS */;
INSERT INTO `caipiao_membersession` VALUES (391,9872,'timibbs','29bcbe08daf538c21a1b6e857a7eead3','98.98.91.137',1764176312);
/*!40000 ALTER TABLE `caipiao_membersession` ENABLE KEYS */;
UNLOCK TABLES;
