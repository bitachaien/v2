--
-- Table structure for table `caipiao_canglottery`
--

DROP TABLE IF EXISTS `caipiao_canglottery`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `caipiao_canglottery` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL COMMENT 'ID thành viên',
  `name` varchar(80) NOT NULL COMMENT 'Tên loại xổ số',
  `type` char(30) NOT NULL DEFAULT '' COMMENT 'Mã loại xổ số (duy nhất)',
  `status` tinyint(1) NOT NULL COMMENT 'Có yêu thích không 1-yêu thích 0-hủy yêu thích',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=47 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `caipiao_canglottery`
--

LOCK TABLES `caipiao_canglottery` WRITE;
/*!40000 ALTER TABLE `caipiao_canglottery` DISABLE KEYS */;
INSERT INTO `caipiao_canglottery` VALUES (40,9846,'Thượng Hải快3','shk3',0),(41,9846,'香港六合彩','lhc',1),(42,9846,'重庆时时彩','cqssc',1),(43,9846,'Giang Tô快3','jsk3',1),(44,9846,'Thượng Hải11选5','sh11x5',1),(45,9846,'mới疆时时彩','xjssc',1),(46,9845,'5xu彩','ssc5fc',0);
/*!40000 ALTER TABLE `caipiao_canglottery` ENABLE KEYS */;
UNLOCK TABLES;
