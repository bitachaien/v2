--
-- Table structure for table `caipiao_linebanklist`
--

DROP TABLE IF EXISTS `caipiao_linebanklist`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `caipiao_linebanklist` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `bankname` varchar(60) NOT NULL COMMENT 'ngân hàngtên',
  `accountname` varchar(30) NOT NULL COMMENT 'họ tên',
  `banknumber` char(22) NOT NULL COMMENT '',
  `banklogo` char(120) NOT NULL COMMENT 'ngân hànglogo',
  `listorder` smallint(6) NOT NULL,
  `state` tinyint(4) NOT NULL DEFAULT '1' COMMENT '1bật 0tắt',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `caipiao_linebanklist`
--

LOCK TABLES `caipiao_linebanklist` WRITE;
/*!40000 ALTER TABLE `caipiao_linebanklist` DISABLE KEYS */;
/*!40000 ALTER TABLE `caipiao_linebanklist` ENABLE KEYS */;
UNLOCK TABLES;
