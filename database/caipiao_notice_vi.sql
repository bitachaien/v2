--
-- Table structure for table `caipiao_notice`
--

DROP TABLE IF EXISTS `caipiao_notice`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `caipiao_notice` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `admin_user` varchar(20) NOT NULL,
  `title` varchar(50) NOT NULL,
  `content` varchar(500) NOT NULL,
  `users` varchar(500) NOT NULL,
  `add_time` int(11) NOT NULL,
  `is_send` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `caipiao_notice`
--

LOCK TABLES `caipiao_notice` WRITE;
/*!40000 ALTER TABLE `caipiao_notice` DISABLE KEYS */;
INSERT INTO `caipiao_notice` VALUES (1,'','测试','<p>123456<img src=\"/app/admin/upload/img/20261029/69013b1945fd.png\" contenteditable=\"false\" style=\"font-size: 14px; max-width: 100%;\"/></p>','9872',1761686237,0),(2,'','111','111','9872',1764251646,0),(3,'','123','12345','9872',1764449432,0),(4,'','测试666','lớn使馆发傻瓜','9872',1764621241,0),(5,'','lớnlớn','<p>123456<img src=\"/app/admin/upload/img/20261029/69013b1945fd.png\" contenteditable=\"false\" style=\"font-size: 14px; max-width: 100%;\"/></p>','9872',1764621275,0),(6,'','ceshi','<p>121111</p>','9872',1766099385,0);
/*!40000 ALTER TABLE `caipiao_notice` ENABLE KEYS */;
UNLOCK TABLES;
