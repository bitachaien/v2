--
-- Table structure for table `caipiao_dailifandian`
--

DROP TABLE IF EXISTS `caipiao_dailifandian`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `caipiao_dailifandian` (
  `id` mediumint(8) NOT NULL AUTO_INCREMENT,
  `uid` mediumint(8) DEFAULT NULL,
  `username` char(20) DEFAULT NULL,
  `trano` char(20) DEFAULT NULL,
  `amount` decimal(10,3) DEFAULT NULL,
  `touzhujine` decimal(10,3) DEFAULT NULL,
  `fandian` decimal(6,1) DEFAULT NULL,
  `xiajiid` mediumint(8) DEFAULT NULL,
  `xiajiuser` char(20) DEFAULT NULL COMMENT 'Cấp dưới',
  `xiajifandian` decimal(6,1) DEFAULT NULL,
  `shenhe` tinyint(2) DEFAULT '0' COMMENT '1-thông qua, 0-chờ đợi, -1-thất bại',
  `oddtime` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `caipiao_dailifandian`
--

LOCK TABLES `caipiao_dailifandian` WRITE;
/*!40000 ALTER TABLE `caipiao_dailifandian` DISABLE KEYS */;
/*!40000 ALTER TABLE `caipiao_dailifandian` ENABLE KEYS */;
UNLOCK TABLES;
