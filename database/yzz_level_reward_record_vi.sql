--
-- Table structure for table `yzz_level_reward_record`
--

DROP TABLE IF EXISTS `yzz_level_reward_record`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `yzz_level_reward_record` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID khóa chính',
  `user_id` bigint(20) unsigned NOT NULL COMMENT 'ID người dùng',
  `username` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'Tên đăng nhập',
  `order_no` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'Mã đơn hàng',
  `from_level_id` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT 'ID cấp độ cũ',
  `from_level_name` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'Tên cấp độ cũ',
  `to_level_id` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT 'ID cấp độ mới',
  `to_level_name` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'Tên cấp độ mới',
  `reward_amount` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT 'Số tiền thưởng',
  `balance_before` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT 'Số dư trước khi nhận',
  `balance_after` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT 'Số dư sau khi nhận',
  `status` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'Trạng thái: 0-chờ nhận, 1-đã nhận, 2-đã hết hạn',
  `claim_time` datetime DEFAULT NULL COMMENT 'Thời gian nhận thưởng',
  `expire_time` datetime DEFAULT NULL COMMENT 'Thời gian hết hạn',
  `remark` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'Ghi chú',
  `gmt_create` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Thời gian tạo',
  `gmt_modified` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Thời gian cập nhật',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_order_no` (`order_no`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_status` (`status`),
  KEY `idx_gmt_create` (`gmt_create`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Bảng lịch sử thưởng thăng cấp';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `yzz_level_reward_record`
--

LOCK TABLES `yzz_level_reward_record` WRITE;
/*!40000 ALTER TABLE `yzz_level_reward_record` DISABLE KEYS */;
INSERT INTO `yzz_level_reward_record` VALUES (5,9874,'123456','LR202612021112522531',9,'VIP9',10,'代理',1.00,1000000.00,1000001.00,1,'2026-12-02 11:12:52',NULL,'Thăng cấp至代理奖励','2026-12-02 11:12:52','2026-12-02 11:12:52'),(6,9874,'123456','LR202612021123037247',10,'VIP10',2,'VIP2',88.88,1000001.00,1000089.88,1,'2026-12-02 11:23:03',NULL,'Thăng cấp至VIP2奖励','2026-12-02 11:23:03','2026-12-02 11:23:03'),(7,9874,'123456','LR202612021210093088',2,'VIP2',6,'VIP6',127.00,1050189.88,1050316.88,1,'2026-12-02 12:10:09',NULL,'Thăng cấp至VIP6奖励','2026-12-02 12:10:09','2026-12-02 12:10:09');
/*!40000 ALTER TABLE `yzz_level_reward_record` ENABLE KEYS */;
UNLOCK TABLES;
