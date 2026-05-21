--
-- Table structure for table `caipiao_rebate_category_config`
--

DROP TABLE IF EXISTS `caipiao_rebate_category_config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `caipiao_rebate_category_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_code` varchar(20) NOT NULL COMMENT 'danh mụcmã',
  `category_name` varchar(50) NOT NULL COMMENT 'danh mụctên',
  `status` tinyint(4) DEFAULT '1' COMMENT 'Trạng thái1Kích hoạt 0Vô hiệu hóa',
  `sort` int(11) DEFAULT '0' COMMENT 'Sắp xếp',
  `icon` varchar(255) DEFAULT '' COMMENT 'Biểu tượng',
  `created_at` int(11) DEFAULT '0',
  `updated_at` int(11) DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `category_code` (`category_code`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COMMENT='反水Danh mụcCấu hìnhBảng';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `caipiao_rebate_category_config`
--

LOCK TABLES `caipiao_rebate_category_config` WRITE;
/*!40000 ALTER TABLE `caipiao_rebate_category_config` DISABLE KEYS */;
INSERT INTO `caipiao_rebate_category_config` VALUES (1,'slot','电子',1,1,'',1765599857,0),(2,'live','真人',1,2,'',1765599857,0),(3,'fishing','捕鱼',1,3,'',1765599857,0),(4,'chess','棋牌',1,4,'',1765599857,0),(5,'lottery','彩票',1,5,'',1765599857,0),(6,'sport','体育',1,6,'',1765599857,1765600011);
/*!40000 ALTER TABLE `caipiao_rebate_category_config` ENABLE KEYS */;
UNLOCK TABLES;
