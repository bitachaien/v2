--
-- Table structure for table `caipiao_czddh`
--

DROP TABLE IF EXISTS `caipiao_czddh`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `caipiao_czddh` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `paytype` char(20) NOT NULL COMMENT 'Alipay: alipay, Ví QQ: tenpay, WeChat: weixin',
  `uid` int(11) NOT NULL,
  `username` char(60) NOT NULL,
  `trano` char(60) NOT NULL,
  `threetrano` char(255) NOT NULL,
  `amount` decimal(14,2) NOT NULL,
  `oddtime` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`),
  KEY `trano` (`trano`),
  KEY `threetrano` (`threetrano`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='Bảng quan hệ đơn nạp tiền nền tảng và đơn bên thứ ba';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `caipiao_czddh`
--

LOCK TABLES `caipiao_czddh` WRITE;
/*!40000 ALTER TABLE `caipiao_czddh` DISABLE KEYS */;
/*!40000 ALTER TABLE `caipiao_czddh` ENABLE KEYS */;
UNLOCK TABLES;
