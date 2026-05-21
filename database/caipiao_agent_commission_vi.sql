--
-- Table structure for table `caipiao_agent_commission`
--

DROP TABLE IF EXISTS `caipiao_agent_commission`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `caipiao_agent_commission` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `agent_id` int(11) NOT NULL COMMENT 'ID đại lý',
  `sub_id` int(11) DEFAULT NULL COMMENT 'ID cấp dưới',
  `type` tinyint(1) DEFAULT '1' COMMENT 'Loại: 1-hoa hồng trực tiếp, 2-hoa hồng khác',
  `performance` decimal(12,2) DEFAULT '0.00' COMMENT 'Số tiền thành tích',
  `rate` decimal(5,2) DEFAULT '0.00' COMMENT 'Tỷ lệ hoa hồng (%)',
  `amount` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT 'Số tiền hoa hồng',
  `status` tinyint(1) DEFAULT '1' COMMENT 'Trạng thái: 1-chờ nhận, 2-đã nhận',
  `settle_date` date DEFAULT NULL COMMENT 'Ngày thanh toán',
  `claimed_at` int(11) DEFAULT NULL COMMENT 'Thời gian nhận',
  `created_at` int(11) NOT NULL COMMENT 'Thời gian tạo',
  `updated_at` int(11) DEFAULT NULL COMMENT 'Thời gian cập nhật',
  PRIMARY KEY (`id`),
  KEY `idx_agent_id` (`agent_id`),
  KEY `idx_status` (`status`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_agent_date` (`agent_id`,`settle_date`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COMMENT='Bảng ghi nhận hoa hồng đại lý';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `caipiao_agent_commission`
--

LOCK TABLES `caipiao_agent_commission` WRITE;
/*!40000 ALTER TABLE `caipiao_agent_commission` DISABLE KEYS */;
INSERT INTO `caipiao_agent_commission` VALUES (3,9872,NULL,1,12026.00,15.00,1803.75,2,'2026-12-09',1765234787,1765233481,NULL),(4,9872,NULL,1,11450.00,15.00,1717.50,2,'2026-12-10',1765434251,1765303200,NULL),(5,9872,NULL,1,0.00,15.00,0.00,2,'2026-12-11',1765434251,1765389600,NULL),(6,9872,NULL,1,0.00,15.00,0.00,1,'2026-12-14',NULL,1765648800,NULL),(7,9872,NULL,1,0.00,15.00,0.00,1,'2026-12-18',NULL,1766001375,NULL),(8,9872,NULL,1,0.00,15.00,0.00,1,'2026-12-19',NULL,1766080800,NULL),(9,9872,NULL,1,0.00,15.00,0.00,1,'2026-12-20',NULL,1766167200,NULL);
/*!40000 ALTER TABLE `caipiao_agent_commission` ENABLE KEYS */;
UNLOCK TABLES;
