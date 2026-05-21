--
-- Table structure for table `caipiao_agent_rate`
--

DROP TABLE IF EXISTS `caipiao_agent_rate`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `caipiao_agent_rate` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `effective_count` int(11) NOT NULL COMMENT 'Số người hợp lệ',
  `performance` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT 'Yêu cầu thành tích',
  `commission` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT 'Ví dụ số tiền hoa hồng',
  `rate` decimal(5,2) NOT NULL DEFAULT '0.00' COMMENT 'Tỷ lệ hoa hồng (%)',
  `status` tinyint(1) DEFAULT '1' COMMENT 'Trạng thái: 1-kích hoạt, 0-vô hiệu hóa',
  `created_at` int(11) DEFAULT NULL,
  `updated_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_effective_count` (`effective_count`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COMMENT='Bảng cấu hình tỷ lệ hoa hồng đại lý';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `caipiao_agent_rate`
--

LOCK TABLES `caipiao_agent_rate` WRITE;
/*!40000 ALTER TABLE `caipiao_agent_rate` DISABLE KEYS */;
INSERT INTO `caipiao_agent_rate` VALUES (1,1,100.00,1.00,1.00,1,1765231105,NULL),(2,2,50000.00,2500.00,5.00,1,1765231105,NULL),(3,3,100000.00,10000.00,10.00,1,1765231105,NULL),(4,4,150000.00,22500.00,15.00,1,1765231105,NULL),(5,5,200000.00,40000.00,20.00,1,1765231105,NULL);
/*!40000 ALTER TABLE `caipiao_agent_rate` ENABLE KEYS */;
UNLOCK TABLES;
