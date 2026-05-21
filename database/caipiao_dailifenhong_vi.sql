--
-- Table structure for table `caipiao_dailifenhong`
--

DROP TABLE IF EXISTS `caipiao_dailifenhong`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `caipiao_dailifenhong` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `trano` char(60) NOT NULL,
  `uid` int(11) NOT NULL,
  `username` char(20) NOT NULL,
  `tzsumamount` decimal(14,4) NOT NULL,
  `fjsumamount` decimal(14,4) NOT NULL,
  `yingkui` decimal(14,4) NOT NULL,
  `fanwei` char(60) NOT NULL,
  `bili` char(10) NOT NULL,
  `amount` decimal(14,4) NOT NULL,
  `oddtime` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`),
  KEY `trano` (`trano`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `caipiao_dailifenhong`
--

LOCK TABLES `caipiao_dailifenhong` WRITE;
/*!40000 ALTER TABLE `caipiao_dailifenhong` DISABLE KEYS */;
/*!40000 ALTER TABLE `caipiao_dailifenhong` ENABLE KEYS */;
UNLOCK TABLES;
