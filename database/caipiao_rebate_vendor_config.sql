--
-- Table structure for table `caipiao_rebate_vendor_config`
--

DROP TABLE IF EXISTS `caipiao_rebate_vendor_config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `caipiao_rebate_vendor_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `vendor_code` varchar(20) NOT NULL COMMENT 'nhà cung cấpmã',
  `category_code` varchar(20) NOT NULL COMMENT 'trò chơidanh mụcmã',
  `vendor_name` varchar(50) DEFAULT '' COMMENT 'nhà cung cấptên',
  `status` tinyint(4) DEFAULT '1' COMMENT 'Trạng thái1Kích hoạt 0Vô hiệu hóa',
  `base_rate` decimal(5,2) DEFAULT '0.50' COMMENT 'hoàn trả cượctỷ lệ(%)',
  `vip_bonus` decimal(5,2) DEFAULT '0.10' COMMENT 'VI Ptỷ lệ(%/)',
  `min_bet` decimal(15,2) DEFAULT '100.00' COMMENT 'thấp nhấtđặt cược',
  `created_at` int(11) DEFAULT '0',
  `updated_at` int(11) DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_vendor_category` (`vendor_code`,`category_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='游戏商反水Cấu hìnhBảng';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `caipiao_rebate_vendor_config`
--

LOCK TABLES `caipiao_rebate_vendor_config` WRITE;
/*!40000 ALTER TABLE `caipiao_rebate_vendor_config` DISABLE KEYS */;
/*!40000 ALTER TABLE `caipiao_rebate_vendor_config` ENABLE KEYS */;
UNLOCK TABLES;
