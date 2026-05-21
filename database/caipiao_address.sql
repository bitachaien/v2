--
-- Table structure for table `caipiao_address`
--

DROP TABLE IF EXISTS `caipiao_address`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `caipiao_address` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID khóa chính',
  `address` varchar(255) DEFAULT NULL COMMENT 'Địa chỉ URL banner',
  `is_mobile` tinyint(1) NOT NULL COMMENT 'Nền tảng: 0-PC, 1-Mobile',
  `beizhu` varchar(80) NOT NULL COMMENT 'Ghi chú',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8 COMMENT='Bảng quản lý banner quảng cáo';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `caipiao_address`
--

LOCK TABLES `caipiao_address` WRITE;
/*!40000 ALTER TABLE `caipiao_address` DISABLE KEYS */;
INSERT INTO `caipiao_address` VALUES (1,'/ascn/images/banner/1.jpg',0,'2021 Phát phát phát'),(2,'/ascn/images/banner/2.jpg',0,'2021 Phát phát phát'),(3,'/ascn/images/banner/3.jpg',0,'2021 Phát phát phát'),(4,'/ascn/images/banner/4.jpg',0,'2021 Phát phát phát'),(5,'/ascn/images/banner/5.jpg',0,'2021 Phát phát phát'),(6,'/ascn/images/banner/1.jpg',1,'2021 Phát phát phát'),(7,'/ascn/images/banner/2.jpg',1,'2021 Phát phát phát'),(8,'/ascn/images/banner/3.jpg',1,'2021 Phát phát phát'),(9,'/ascn/images/banner/4.jpg',1,'2021 Phát phát phát'),(10,'/ascn/images/banner/5.jpg',1,'2021 Phát phát phát');
/*!40000 ALTER TABLE `caipiao_address` ENABLE KEYS */;
UNLOCK TABLES;