--
-- Table structure for table `caipiao_caipiaotimes`
--

DROP TABLE IF EXISTS `caipiao_caipiaotimes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `caipiao_caipiaotimes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` char(20) NOT NULL,
  `expect` int(11) NOT NULL,
  `starttime` time NOT NULL,
  `stoptime` time NOT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `expect` (`expect`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `caipiao_caipiaotimes`
--

LOCK TABLES `caipiao_caipiaotimes` WRITE;
/*!40000 ALTER TABLE `caipiao_caipiaotimes` DISABLE KEYS */;
/*!40000 ALTER TABLE `caipiao_caipiaotimes` ENABLE KEYS */;
UNLOCK TABLES;
