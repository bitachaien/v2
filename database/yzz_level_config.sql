--
-- Table structure for table `yzz_level_config`
--

DROP TABLE IF EXISTS `yzz_level_config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `yzz_level_config` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID khóa chính',
  `level_id` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT 'ID cấp độ (1-9)',
  `level_name` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'Tên cấp độ',
  `level_icon` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'URL biểu tượng cấp độ',
  `required_points` bigint(20) unsigned NOT NULL DEFAULT '0' COMMENT 'Điểm tích lũy yêu cầu nâng cấp',
  `reward_amount` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT 'Số tiền thưởng thăng cấp',
  `daily_withdraw_limit` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT 'Hạn mức rút tiền hàng ngày',
  `rebate_rate` decimal(5,4) NOT NULL DEFAULT '0.0000' COMMENT 'Tỷ lệ hoàn trả cược',
  `sort_order` int(11) NOT NULL DEFAULT '0' COMMENT 'Thứ tự sắp xếp',
  `is_enabled` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Trạng thái: 0-vô hiệu hóa, 1-kích hoạt',
  `remark` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'Ghi chú',
  `gmt_create` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Thời gian tạo',
  `gmt_modified` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Thời gian cập nhật',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_level_id` (`level_id`),
  KEY `idx_is_enabled` (`is_enabled`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Bảng cấu hình cấp độ VIP';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `yzz_level_config`
--

LOCK TABLES `yzz_level_config` WRITE;
/*!40000 ALTER TABLE `yzz_level_config` DISABLE KEYS */;
INSERT INTO `yzz_level_config` VALUES (1,1,'VIP1','',0,8.00,50000.00,0.0050,1,1,'','2026-12-02 10:29:24','2026-12-02 10:29:24'),(2,2,'VIP2','',10000,18.00,100000.00,0.0060,2,1,'','2026-12-02 10:29:24','2026-12-02 10:29:24'),(3,3,'VIP3','',50000,58.00,200000.00,0.0070,3,1,'','2026-12-02 10:29:24','2026-12-02 10:29:24'),(4,4,'VIP4','',200000,128.00,300000.00,0.0080,4,1,'','2026-12-02 10:29:24','2026-12-02 10:29:24'),(5,5,'VIP5','',500000,288.00,500000.00,0.0090,5,1,'','2026-12-02 10:29:24','2026-12-02 10:29:24'),(6,6,'VIP6','',1000000,588.00,800000.00,0.0100,6,1,'','2026-12-02 10:29:24','2026-12-02 10:29:24'),(7,7,'VIP7','',3000000,1288.00,1000000.00,0.0110,7,1,'','2026-12-02 10:29:24','2026-12-02 10:29:24'),(8,8,'VIP8','',8000000,2888.00,2000000.00,0.0120,8,1,'','2026-12-02 10:29:24','2026-12-02 10:29:24'),(9,9,'VIP9','',20000000,5888.00,5000000.00,0.0150,9,1,'','2026-12-02 10:29:24','2026-12-02 10:29:24');
/*!40000 ALTER TABLE `yzz_level_config` ENABLE KEYS */;
UNLOCK TABLES;
