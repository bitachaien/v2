--
-- Table structure for table `caipiao_yuequxian`
--

DROP TABLE IF EXISTS `caipiao_yuequxian`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `caipiao_yuequxian` (
  `id` smallint(6) NOT NULL AUTO_INCREMENT,
  `fname` char(20) NOT NULL,
  `state` tinyint(4) NOT NULL DEFAULT '1' COMMENT '10',
  `f_id` int(10) NOT NULL,
  `money` int(10) NOT NULL,
  `addtime` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `caipiao_yuequxian`
--

LOCK TABLES `caipiao_yuequxian` WRITE;
/*!40000 ALTER TABLE `caipiao_yuequxian` DISABLE KEYS */;
/*!40000 ALTER TABLE `caipiao_yuequxian` ENABLE KEYS */;
UNLOCK TABLES;
