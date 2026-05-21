--
-- Table structure for table `account_transactions`
--

DROP TABLE IF EXISTS `account_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `account_transactions` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) NOT NULL COMMENT 'ID người dùng',
  `type` enum('recharge','withdraw','bet','payout','rebate','adjust') COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Loại giao dịch',
  `amount` decimal(18,4) NOT NULL COMMENT 'Số tiền',
  `balance_before` decimal(18,4) NOT NULL COMMENT 'Số dư trước thay đổi',
  `balance_after` decimal(18,4) NOT NULL COMMENT 'Số dư sau thay đổi',
  `ref_type` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Loại liên kết',
  `ref_id` bigint(20) NOT NULL COMMENT 'ID liên kết',
  `remark` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Ghi chú',
  `created_at` datetime NOT NULL COMMENT 'Thời gian tạo',
  PRIMARY KEY (`id`),
  KEY `idx_user_created` (`user_id`,`created_at`),
  KEY `idx_ref` (`ref_type`,`ref_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Bảng lịch sử giao dịch';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `account_transactions`
--

LOCK TABLES `account_transactions` WRITE;
/*!40000 ALTER TABLE `account_transactions` DISABLE KEYS */;
/*!40000 ALTER TABLE `account_transactions` ENABLE KEYS */;
UNLOCK TABLES;
