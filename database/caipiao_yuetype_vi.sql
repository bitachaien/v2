--
-- Table structure for table `caipiao_yuetype`
--

DROP TABLE IF EXISTS `caipiao_yuetype`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `caipiao_yuetype` (
  `id` smallint(6) NOT NULL AUTO_INCREMENT,
  `name` char(20) NOT NULL,
  `state` tinyint(4) NOT NULL DEFAULT '1' COMMENT '1bật -1tắt',
  `day` int(10) NOT NULL,
  `liyun` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `caipiao_yuetype`
--

LOCK TABLES `caipiao_yuetype` WRITE;
/*!40000 ALTER TABLE `caipiao_yuetype` DISABLE KEYS */;
INSERT INTO `caipiao_yuetype` VALUES (1,'随存随取',1,0,1.20),(2,'定期7ngày',1,6,1.40),(4,'定期15ngày',1,14,1.60),(5,'定期30ngày',1,30,2.00);
/*!40000 ALTER TABLE `caipiao_yuetype` ENABLE KEYS */;
UNLOCK TABLES;
