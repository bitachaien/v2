--
-- Table structure for table `caipiao_transrecord`
--

DROP TABLE IF EXISTS `caipiao_transrecord`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `caipiao_transrecord` (
  `transID` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(10) unsigned NOT NULL,
  `transBillno` varchar(25) DEFAULT NULL COMMENT 'số',
  `transType` varchar(10) DEFAULT NULL COMMENT 'Loại',
  `transDes` varchar(25) DEFAULT NULL,
  `tansAmount` decimal(10,0) DEFAULT NULL,
  `state` int(4) DEFAULT NULL COMMENT 'Trạng thái 1 thành công 0thất bại',
  `transTime` datetime DEFAULT NULL,
  PRIMARY KEY (`transID`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=56 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `caipiao_transrecord`
--

LOCK TABLES `caipiao_transrecord` WRITE;
/*!40000 ALTER TABLE `caipiao_transrecord` DISABLE KEYS */;
/*!40000 ALTER TABLE `caipiao_transrecord` ENABLE KEYS */;
UNLOCK TABLES;
