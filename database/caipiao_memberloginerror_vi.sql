--
-- Table structure for table `caipiao_memberloginerror`
--

DROP TABLE IF EXISTS `caipiao_memberloginerror`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `caipiao_memberloginerror` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` char(30) NOT NULL,
  `ip` char(20) NOT NULL,
  `time` int(11) NOT NULL,
  `errornum` smallint(6) NOT NULL,
  `locktime` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `caipiao_memberloginerror`
--

LOCK TABLES `caipiao_memberloginerror` WRITE;
/*!40000 ALTER TABLE `caipiao_memberloginerror` DISABLE KEYS */;
/*!40000 ALTER TABLE `caipiao_memberloginerror` ENABLE KEYS */;
UNLOCK TABLES;
