--
-- Table structure for table `caipiao_jinjijiangli`
--

DROP TABLE IF EXISTS `caipiao_jinjijiangli`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `caipiao_jinjijiangli` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `trano` char(60) NOT NULL COMMENT '//số giao dịch',
  `listorder` smallint(6) NOT NULL,
  `status` tinyint(4) NOT NULL DEFAULT '1',
  `addtime` int(11) NOT NULL,
  `uid` mediumtext NOT NULL COMMENT 'ID hội viên',
  `username` char(20) NOT NULL COMMENT 'tên hội viên',
  `groupid` mediumtext NOT NULL COMMENT 'ID cấp hội viên',
  `beforegroupname` varchar(20) DEFAULT NULL COMMENT '//thăng cấpCấp độ',
  `groupname` varchar(20) NOT NULL COMMENT 'tên cấp hội viên',
  `jlje` decimal(10,0) NOT NULL COMMENT 'thăng cấpthưởngSố tiền',
  `point` float NOT NULL COMMENT 'thăng cấpĐiểm tích lũy',
  `oddtime` int(11) NOT NULL COMMENT 'thưởngthời gian',
  `shenhe` tinyint(4) NOT NULL COMMENT 'duyệt',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `caipiao_jinjijiangli`
--

LOCK TABLES `caipiao_jinjijiangli` WRITE;
/*!40000 ALTER TABLE `caipiao_jinjijiangli` DISABLE KEYS */;
/*!40000 ALTER TABLE `caipiao_jinjijiangli` ENABLE KEYS */;
UNLOCK TABLES;
