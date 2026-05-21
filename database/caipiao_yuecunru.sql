--
-- Table structure for table `caipiao_yuecunru`
--

DROP TABLE IF EXISTS `caipiao_yuecunru`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `caipiao_yuecunru` (
  `id` smallint(6) NOT NULL AUTO_INCREMENT,
  `fname` char(20) NOT NULL,
  `state` tinyint(4) NOT NULL DEFAULT '1' COMMENT '1 0',
  `f_id` int(10) NOT NULL,
  `money` int(10) NOT NULL,
  `qmoney` int(10) NOT NULL,
  `addtime` int(11) NOT NULL,
  `type` int(10) NOT NULL DEFAULT '0' COMMENT '0,1',
  `trano` varchar(100) NOT NULL,
  `uid` int(10) NOT NULL,
  `lixitime` int(11) NOT NULL COMMENT 'thời gian',
  `txtype` int(10) NOT NULL DEFAULT '0' COMMENT '0rút tiền1rút tiền',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=56 DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `caipiao_yuecunru`
--

LOCK TABLES `caipiao_yuecunru` WRITE;
/*!40000 ALTER TABLE `caipiao_yuecunru` DISABLE KEYS */;
INSERT INTO `caipiao_yuecunru` VALUES (55,'随存随取',1,1,1000,0,1719679415,0,'ic24063000433501',9872,1761756215,0);
/*!40000 ALTER TABLE `caipiao_yuecunru` ENABLE KEYS */;
UNLOCK TABLES;
