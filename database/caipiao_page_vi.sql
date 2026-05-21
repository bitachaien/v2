--
-- Table structure for table `caipiao_page`
--

DROP TABLE IF EXISTS `caipiao_page`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `caipiao_page` (
  `catid` smallint(6) NOT NULL,
  `title` varchar(180) NOT NULL,
  `content` longtext NOT NULL,
  KEY `catid` (`catid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `caipiao_page`
--

LOCK TABLES `caipiao_page` WRITE;
/*!40000 ALTER TABLE `caipiao_page` DISABLE KEYS */;
INSERT INTO `caipiao_page` VALUES (19,'公司简介公司简介公司简介公司简介公司简介','公司简介公司简介公司简介公司简介公司简介公司简介公司简介公司简介公司简介公司简介公司简介公司简介公司简介公司简介公司简介公司简介公司简介');
/*!40000 ALTER TABLE `caipiao_page` ENABLE KEYS */;
UNLOCK TABLES;
