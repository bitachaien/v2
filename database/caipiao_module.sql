--
-- Table structure for table `caipiao_module`
--

DROP TABLE IF EXISTS `caipiao_module`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `caipiao_module` (
  `moduleid` smallint(6) NOT NULL AUTO_INCREMENT,
  `title` varchar(120) NOT NULL,
  `name` char(60) NOT NULL,
  `remark` varchar(120) NOT NULL,
  `listorder` smallint(6) NOT NULL,
  PRIMARY KEY (`moduleid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `caipiao_module`
--

LOCK TABLES `caipiao_module` WRITE;
/*!40000 ALTER TABLE `caipiao_module` DISABLE KEYS */;
/*!40000 ALTER TABLE `caipiao_module` ENABLE KEYS */;
UNLOCK TABLES;
