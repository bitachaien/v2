--
-- Table structure for table `caipiao_agentlink`
--

DROP TABLE IF EXISTS `caipiao_agentlink`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `caipiao_agentlink` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL,
  `username` char(60) NOT NULL,
  `proxy` tinyint(4) NOT NULL DEFAULT '0' COMMENT '1-đại lý 0-người chơi',
  `tpltype` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Loại mẫu 0-mặc định',
  `usenum` int(11) NOT NULL COMMENT 'Số lần sử dụng',
  `okusenum` int(11) NOT NULL COMMENT 'Số lần đã sử dụng',
  `fandian` text NOT NULL,
  `oddtime` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `caipiao_agentlink`
--

LOCK TABLES `caipiao_agentlink` WRITE;
/*!40000 ALTER TABLE `caipiao_agentlink` DISABLE KEYS */;
/*!40000 ALTER TABLE `caipiao_agentlink` ENABLE KEYS */;
UNLOCK TABLES;
