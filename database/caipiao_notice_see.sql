--
-- Table structure for table `caipiao_notice_see`
--

DROP TABLE IF EXISTS `caipiao_notice_see`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `caipiao_notice_see` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nid` int(11) NOT NULL,
  `uid` int(11) NOT NULL,
  `add_time` int(11) NOT NULL,
  `is_see` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `caipiao_notice_see`
--

LOCK TABLES `caipiao_notice_see` WRITE;
/*!40000 ALTER TABLE `caipiao_notice_see` DISABLE KEYS */;
/*!40000 ALTER TABLE `caipiao_notice_see` ENABLE KEYS */;
UNLOCK TABLES;
