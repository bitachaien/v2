--
-- Table structure for table `caipiao_agent_settlement_log`
--

DROP TABLE IF EXISTS `caipiao_agent_settlement_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `caipiao_agent_settlement_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `settle_date` date NOT NULL COMMENT 'Ngày thanh toán',
  `success_count` int(11) NOT NULL DEFAULT '0' COMMENT 'Số lượng thành công',
  `skip_count` int(11) NOT NULL DEFAULT '0' COMMENT 'Số lượng bỏ qua',
  `error_count` int(11) NOT NULL DEFAULT '0' COMMENT 'Số lượng thất bại',
  `total_commission` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT 'Tổng số tiền hoa hồng',
  `duration` decimal(6,2) DEFAULT '0.00' COMMENT 'Thời gian thực thi (giây)',
  `created_at` int(11) NOT NULL COMMENT 'Thời gian tạo',
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_settle_date` (`settle_date`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COMMENT='Bảng nhật ký thanh toán đại lý';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `caipiao_agent_settlement_log`
--

LOCK TABLES `caipiao_agent_settlement_log` WRITE;
/*!40000 ALTER TABLE `caipiao_agent_settlement_log` DISABLE KEYS */;
INSERT INTO `caipiao_agent_settlement_log` VALUES (1,'2026-12-10',1,0,0,1717.50,0.08,1765303200),(2,'2026-12-11',1,0,0,0.00,0.11,1765389600),(3,'2026-12-14',1,0,0,0.00,0.03,1765648800),(4,'2026-12-18',1,0,0,0.00,0.03,1766001375),(5,'2026-12-19',1,0,0,0.00,0.05,1766080800),(6,'2026-12-20',1,0,0,0.00,0.06,1766167200);
/*!40000 ALTER TABLE `caipiao_agent_settlement_log` ENABLE KEYS */;
UNLOCK TABLES;
