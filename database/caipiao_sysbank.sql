--
-- Table structure for table `caipiao_sysbank`
--

DROP TABLE IF EXISTS `caipiao_sysbank`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `caipiao_sysbank` (
  `id` smallint(6) NOT NULL AUTO_INCREMENT,
  `bankcode` char(15) NOT NULL COMMENT 'mã ngân hàng',
  `bankname` varchar(60) NOT NULL COMMENT 'tên ngân hàng',
  `banklogo` char(120) NOT NULL COMMENT 'LOGO ngân hàng',
  `state` tinyint(4) NOT NULL DEFAULT '1',
  `listorder` smallint(6) NOT NULL,
  `imgbg` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `caipiao_sysbank`
--

LOCK TABLES `caipiao_sysbank` WRITE;
/*!40000 ALTER TABLE `caipiao_sysbank` DISABLE KEYS */;
INSERT INTO `caipiao_sysbank` VALUES (1,'00015','建设银行','jianshe.gif',1,1,'CBC'),(2,'00016','兴业银行','xingye.gif',1,2,'CIB'),(3,'00017','农业银行','nongye.gif',1,3,'ABOC'),(4,'00018','工商银行','gongshang.gif',1,4,'ICBC'),(5,'00041','华夏银行','huaxia.gif',1,5,NULL),(6,'00050','北京银行','beijing.gif',1,6,NULL),(7,'00051','中国邮政','youzheng.gif',1,7,'CP'),(8,'00054','中信银行','zhongxin.gif',1,8,'CCB'),(9,'00055','南京银行','nanjing.gif',1,9,NULL),(10,'00083','中国银行','zhongguo.gif',1,10,'BC'),(11,'00084','上海银行','shanghaibank.gif',1,11,NULL),(12,'00085','宁波银行','ningbo.gif',1,12,NULL),(13,'00086','浙商银行','zheshang.gif',1,13,NULL),(14,'00087','平安银行','pingan.gif',1,14,'PING'),(15,'00095','渤海银行','bohai.gif',1,15,NULL),(16,'00032','上海浦东发展银行','shangpufa.gif',1,16,NULL),(18,'00052','广东发展银行','guangfa.gif',1,18,NULL),(19,'00000','招商银行','zhaoshang-logo.gif',1,19,'CMBC'),(20,'00005','交通银行','jiaotong.gif',1,20,'JIAO'),(21,'00013','民生银行','minsheng.gif',1,21,'CMSB'),(22,'90002','微信支付','weixin.gif',1,22,NULL),(23,'90002','支付宝','alipay.gif',1,23,NULL),(24,'00014','农村信用社','nongc.gif',1,24,NULL);
/*!40000 ALTER TABLE `caipiao_sysbank` ENABLE KEYS */;
UNLOCK TABLES;
