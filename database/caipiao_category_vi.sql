--
-- Table structure for table `caipiao_category`
--

DROP TABLE IF EXISTS `caipiao_category`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `caipiao_category` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID danh mục',
  `parentid` int(11) NOT NULL COMMENT 'ID danh mục cha',
  `catname` varchar(120) NOT NULL COMMENT 'Tên danh mục',
  `listorder` smallint(6) NOT NULL COMMENT 'Thứ tự sắp xếp',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=47 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='Bảng quản lý danh mục nội dung';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `caipiao_category`
--

LOCK TABLES `caipiao_category` WRITE;
/*!40000 ALTER TABLE `caipiao_category` DISABLE KEYS */;
INSERT INTO `caipiao_category` VALUES (29,0,'帮助中心',1),(30,0,'网站介绍',100),(33,29,'帮助指南',1),(34,29,'安全问题',2),(35,29,'Nạp tiền问题',3),(36,29,'购彩问题',4),(37,29,'Rút tiền问题',5),(38,29,'账户安全',6),(39,29,'玩法限制Quy tắc',7),(40,29,'快三技巧攻略',8),(41,0,'Khuyến mãiHoạt động',200),(46,29,'代理合作',42);
/*!40000 ALTER TABLE `caipiao_category` ENABLE KEYS */;
UNLOCK TABLES;
