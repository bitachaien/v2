--
-- Table structure for table `caipiao_kenolimitred`
--

DROP TABLE IF EXISTS `caipiao_kenolimitred`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `caipiao_kenolimitred` (
  `id` tinyint(4) NOT NULL AUTO_INCREMENT,
  `name` varchar(30) NOT NULL,
  `mixmoney` int(11) NOT NULL,
  `maxmoney` bigint(20) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `caipiao_kenolimitred`
--

LOCK TABLES `caipiao_kenolimitred` WRITE;
/*!40000 ALTER TABLE `caipiao_kenolimitred` DISABLE KEYS */;
/*!40000 ALTER TABLE `caipiao_kenolimitred` ENABLE KEYS */;
UNLOCK TABLES;
