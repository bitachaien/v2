--
-- Table structure for table `order_items`
--

DROP TABLE IF EXISTS `order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `order_items` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `order_id` bigint(20) NOT NULL COMMENT 'ID đơn hàng',
  `line_no` int(11) NOT NULL COMMENT 'Số dòng',
  `code` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Nội dung đặt cược',
  `bets` int(11) NOT NULL COMMENT 'Số cược',
  `multiple` int(11) NOT NULL DEFAULT '1' COMMENT 'Bội số cược',
  `amount` decimal(18,4) NOT NULL COMMENT 'Số tiền',
  `bonus` decimal(18,4) NOT NULL DEFAULT '0.0000' COMMENT 'Số tiền trúng thưởng',
  `status` enum('pending','won','lost','canceled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending' COMMENT 'pending-chờ xử lý, won-thắng, lost-thua, canceled-đã hủy',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_order_line` (`order_id`,`line_no`),
  KEY `idx_order_id` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Bảng chi tiết đơn cược';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_items`
--

LOCK TABLES `order_items` WRITE;
/*!40000 ALTER TABLE `order_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `order_items` ENABLE KEYS */;
UNLOCK TABLES;
