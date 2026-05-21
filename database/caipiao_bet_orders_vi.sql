--
-- Table structure for table `caipiao_bet_orders`
--

DROP TABLE IF EXISTS `caipiao_bet_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `caipiao_bet_orders` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID đơn hàng',
  `order_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Số đơn hàng',
  `user_id` int(10) unsigned NOT NULL COMMENT 'ID người dùng',
  `lottery_code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Mã loại xổ số',
  `issue` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Số kỳ',
  `bet_text` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT 'Văn bản cược (ví dụ: Lớn lẻ:340Lớn chẵn:330)',
  `bet_data` json DEFAULT NULL COMMENT 'Chi tiết cược JSON',
  `total_amount` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT 'Tổng số tiền cược',
  `win_amount` decimal(12,2) DEFAULT '0.00' COMMENT 'Số tiền trúng thưởng',
  `status` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'Trạng thái: 0-chờ mở thưởng, 1-đã trúng, 2-không trúng, -1-đã hủy',
  `settled_at` datetime DEFAULT NULL COMMENT 'Thời gian thanh toán',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Thời gian tạo',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_order_id` (`order_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_lottery_issue` (`lottery_code`,`issue`),
  KEY `idx_status` (`status`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Bảng đơn hàng cược';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `caipiao_bet_orders`
--

LOCK TABLES `caipiao_bet_orders` WRITE;
/*!40000 ALTER TABLE `caipiao_bet_orders` DISABLE KEYS */;
INSERT INTO `caipiao_bet_orders` VALUES (1,'BET202612101234325579',9872,'yfxy28','202612100755','lớn:10','[{\"type\": \"size\", \"value\": \"lớn\", \"amount\": 10}]',10.00,0.00,0,NULL,'2026-12-10 12:34:32'),(2,'BET202612101243018612',9872,'yfxy28','202612100764','lớn:10|lớn单:10','[{\"type\": \"size\", \"value\": \"lớn\", \"amount\": 10}, {\"type\": \"combo\", \"value\": \"lớn单\", \"amount\": 10}]',20.00,0.00,0,NULL,'2026-12-10 12:43:01'),(3,'BET202612101301424932',9872,'yfxy28','202612100782','lớn:10|nhỏ:10|单:10|双:10','[{\"type\": \"size\", \"value\": \"lớn\", \"amount\": 10}, {\"type\": \"size\", \"value\": \"nhỏ\", \"amount\": 10}, {\"type\": \"parity\", \"value\": \"单\", \"amount\": 10}, {\"type\": \"parity\", \"value\": \"双\", \"amount\": 10}]',40.00,0.00,0,NULL,'2026-12-10 13:01:42'),(4,'BET202612101304323635',9872,'yfxy28','202612100785','lớn:10|nhỏ:10|单:10|双:10','[{\"type\": \"size\", \"value\": \"lớn\", \"amount\": 10}, {\"type\": \"size\", \"value\": \"nhỏ\", \"amount\": 10}, {\"type\": \"parity\", \"value\": \"单\", \"amount\": 10}, {\"type\": \"parity\", \"value\": \"双\", \"amount\": 10}]',40.00,0.00,0,NULL,'2026-12-10 13:04:32'),(5,'BET202612101335367821',9872,'yfxy28','202612100816','lớn:10|lớn单:10|lớn双:10|nhỏ:10|单:10|nhỏ单:10|nhỏ双:10|双:10','[{\"type\": \"size\", \"value\": \"lớn\", \"amount\": 10}, {\"type\": \"combo\", \"value\": \"lớn单\", \"amount\": 10}, {\"type\": \"combo\", \"value\": \"lớn双\", \"amount\": 10}, {\"type\": \"size\", \"value\": \"nhỏ\", \"amount\": 10}, {\"type\": \"parity\", \"value\": \"单\", \"amount\": 10}, {\"type\": \"combo\", \"value\": \"nhỏ单\", \"amount\": 10}, {\"type\": \"combo\", \"value\": \"nhỏ双\", \"amount\": 10}, {\"type\": \"parity\", \"value\": \"双\", \"amount\": 10}]',80.00,0.00,0,NULL,'2026-12-10 13:35:36'),(6,'BET202612101431162144',9872,'yfxy28','202612100872','lớn:10|lớn单:10','[{\"type\": \"size\", \"value\": \"lớn\", \"amount\": 10}, {\"type\": \"combo\", \"value\": \"lớn单\", \"amount\": 10}]',20.00,0.00,0,NULL,'2026-12-10 14:31:16'),(7,'BET202612101433329814',9872,'yfxy28','202612100874','lớn:10|lớn单:10','[{\"type\": \"size\", \"value\": \"lớn\", \"amount\": 10}, {\"type\": \"combo\", \"value\": \"lớn单\", \"amount\": 10}]',20.00,0.00,0,NULL,'2026-12-10 14:33:32'),(8,'BET202612101434387391',9872,'yfxy28','202612100875','nhỏ:10|lớn:10|单:10|双:10|lớn单:10|lớn双:10|nhỏ单:10|nhỏ双:10','[{\"type\": \"size\", \"value\": \"nhỏ\", \"amount\": 10}, {\"type\": \"size\", \"value\": \"lớn\", \"amount\": 10}, {\"type\": \"parity\", \"value\": \"单\", \"amount\": 10}, {\"type\": \"parity\", \"value\": \"双\", \"amount\": 10}, {\"type\": \"combo\", \"value\": \"lớn单\", \"amount\": 10}, {\"type\": \"combo\", \"value\": \"lớn双\", \"amount\": 10}, {\"type\": \"combo\", \"value\": \"nhỏ单\", \"amount\": 10}, {\"type\": \"combo\", \"value\": \"nhỏ双\", \"amount\": 10}]',80.00,0.00,0,NULL,'2026-12-10 14:34:38'),(9,'BET202612101556220286',9872,'yfxy28','202612100957','lớn双:10|lớn单:10|lớn:10|nhỏ:10|单:10|nhỏ单:10|nhỏ双:10|双:10','[{\"type\": \"combo\", \"value\": \"lớn双\", \"amount\": 10}, {\"type\": \"combo\", \"value\": \"lớn单\", \"amount\": 10}, {\"type\": \"size\", \"value\": \"lớn\", \"amount\": 10}, {\"type\": \"size\", \"value\": \"nhỏ\", \"amount\": 10}, {\"type\": \"parity\", \"value\": \"单\", \"amount\": 10}, {\"type\": \"combo\", \"value\": \"nhỏ单\", \"amount\": 10}, {\"type\": \"combo\", \"value\": \"nhỏ双\", \"amount\": 10}, {\"type\": \"parity\", \"value\": \"双\", \"amount\": 10}]',80.00,0.00,0,NULL,'2026-12-10 15:56:22'),(10,'BET202612101557468515',9872,'yfxy28','202612100958','nhỏ:10|nhỏ单:10','[{\"type\": \"size\", \"value\": \"nhỏ\", \"amount\": 10}, {\"type\": \"combo\", \"value\": \"nhỏ单\", \"amount\": 10}]',20.00,0.00,0,NULL,'2026-12-10 15:57:46'),(11,'BET202612101604246103',9872,'yfxy28','202612100965','111','[{\"type\": \"number\", \"value\": \"11\", \"amount\": 1}]',1.00,0.00,0,NULL,'2026-12-10 16:04:24'),(12,'BET202612101645395222',9872,'yfxy28','202612101006','lớn:10|lớn单:10|lớn双:10|nhỏ:10|单:10|nhỏ单:10','[{\"type\": \"size\", \"value\": \"lớn\", \"amount\": 10}, {\"type\": \"combo\", \"value\": \"lớn单\", \"amount\": 10}, {\"type\": \"combo\", \"value\": \"lớn双\", \"amount\": 10}, {\"type\": \"size\", \"value\": \"nhỏ\", \"amount\": 10}, {\"type\": \"parity\", \"value\": \"单\", \"amount\": 10}, {\"type\": \"combo\", \"value\": \"nhỏ单\", \"amount\": 10}]',60.00,0.00,0,NULL,'2026-12-10 16:45:39'),(13,'BET202612101646434525',9872,'yfxy28','202612101007','lớn单:10|lớn:10|nhỏ:10|lớn双:10','[{\"type\": \"combo\", \"value\": \"lớn单\", \"amount\": 10}, {\"type\": \"size\", \"value\": \"lớn\", \"amount\": 10}, {\"type\": \"size\", \"value\": \"nhỏ\", \"amount\": 10}, {\"type\": \"combo\", \"value\": \"lớn双\", \"amount\": 10}]',40.00,0.00,0,NULL,'2026-12-10 16:46:43');
/*!40000 ALTER TABLE `caipiao_bet_orders` ENABLE KEYS */;
UNLOCK TABLES;
