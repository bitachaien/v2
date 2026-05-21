--
-- Table structure for table `caipiao_api_provider_platform`
--

DROP TABLE IF EXISTS `caipiao_api_provider_platform`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `caipiao_api_provider_platform` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `provider_code` varchar(20) NOT NULL COMMENT 'Mã nền tảng tổng hợp: NG, WG',
  `platform_code` varchar(20) NOT NULL COMMENT 'Mã nền tảng con',
  `platform_name` varchar(50) NOT NULL COMMENT 'Tên nền tảng con',
  `plat_type` varchar(20) DEFAULT NULL COMMENT 'Mã trong nền tảng tổng hợp',
  `game_type` varchar(20) DEFAULT 'slot' COMMENT 'Loại trò chơi: slot,live,chess,fishing,sport',
  `status` tinyint(4) DEFAULT '1' COMMENT 'Trạng thái: 1-kích hoạt, 0-vô hiệu hóa',
  `sort` int(11) DEFAULT '0' COMMENT 'Sắp xếp',
  `created_at` int(11) DEFAULT '0',
  `updated_at` int(11) DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_provider_platform` (`provider_code`,`platform_code`)
) ENGINE=InnoDB AUTO_INCREMENT=69 DEFAULT CHARSET=utf8mb4 COMMENT='Bảng quan hệ nền tảng tổng hợp và nền tảng con';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `caipiao_api_provider_platform`
--

LOCK TABLES `caipiao_api_provider_platform` WRITE;
/*!40000 ALTER TABLE `caipiao_api_provider_platform` DISABLE KEYS */;
INSERT INTO `caipiao_api_provider_platform` VALUES (64,'WG','WG_2','WG电子','2','slot',1,0,0,1765221108),(65,'WG','WG_3','WG捕鱼','3','fishing',1,0,0,1765221108),(66,'WG','WG_9','WG休闲','9','slot',1,0,0,1765221108),(67,'WG','WG_1','WG棋牌','1','chess',1,0,0,1765221109),(68,'WG','WG_13','WG区đồng链','13','slot',1,0,0,1765221109);
/*!40000 ALTER TABLE `caipiao_api_provider_platform` ENABLE KEYS */;
UNLOCK TABLES;
