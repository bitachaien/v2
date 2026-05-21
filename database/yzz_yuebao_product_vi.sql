--
-- Table structure for table `yzz_yuebao_product`
--

DROP TABLE IF EXISTS `yzz_yuebao_product`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `yzz_yuebao_product` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID khóa chính',
  `name` varchar(64) NOT NULL DEFAULT '' COMMENT 'Tên sản phẩm',
  `type` varchar(20) NOT NULL DEFAULT 'current' COMMENT 'Loại: current-không kỳ hạn, fixed-có kỳ hạn',
  `rate` decimal(8,6) NOT NULL DEFAULT '0.000000' COMMENT 'Lãi suất',
  `rate_desc` varchar(64) NOT NULL DEFAULT '' COMMENT 'Mô tả lãi suất',
  `duration_days` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT 'Số ngày kỳ hạn (0-không kỳ hạn)',
  `settle_cycle_hours` smallint(5) unsigned NOT NULL DEFAULT '1' COMMENT 'Chu kỳ thanh toán (giờ)',
  `audit_multiple` decimal(5,2) NOT NULL DEFAULT '1.00' COMMENT 'Bội số kiểm tra rút tiền',
  `auto_claim` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT 'Tự động nhận: 0-thủ công, 1-tự động',
  `max_interest` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT 'Lãi tối đa (0-không giới hạn)',
  `min_amount` decimal(12,2) NOT NULL DEFAULT '1.00' COMMENT 'Số tiền gửi tối thiểu',
  `max_amount` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT 'Số tiền gửi tối đa (0-không giới hạn)',
  `total_quota` decimal(14,2) NOT NULL DEFAULT '0.00' COMMENT 'Hạn mức tổng (0-không giới hạn)',
  `sold_amount` decimal(14,2) NOT NULL DEFAULT '0.00' COMMENT 'Số tiền đã bán',
  `sort` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT 'Thứ tự sắp xếp (số nhỏ ưu tiên)',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT 'Trạng thái: 0-vô hiệu hóa, 1-kích hoạt',
  `is_deleted` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT 'Đã xóa: 0-chưa xóa, 1-đã xóa',
  `remark` varchar(255) NOT NULL DEFAULT '' COMMENT 'Ghi chú mô tả',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'Thời gian tạo',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'Thời gian cập nhật',
  PRIMARY KEY (`id`),
  KEY `idx_type` (`type`),
  KEY `idx_status` (`status`),
  KEY `idx_sort` (`sort`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COMMENT='Bảng cấu hình sản phẩm tiết kiệm';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `yzz_yuebao_product`
--

LOCK TABLES `yzz_yuebao_product` WRITE;
/*!40000 ALTER TABLE `yzz_yuebao_product` DISABLE KEYS */;
INSERT INTO `yzz_yuebao_product` VALUES (1,'活期存入','current',0.025000,'一giờ结算',0,1,1.00,0,0.00,20.00,0.00,0.00,0.00,0,1,0,'',1763994821,1763994821),(2,'定期7ngày','fixed',0.125500,'+12.55%',7,1,1.00,0,0.00,100.00,0.00,0.00,0.00,1,0,0,'',1763994821,1763994821),(3,'定期30ngày','fixed',0.200000,'+20.00%',30,1,1.00,0,0.00,500.00,0.00,0.00,0.00,2,0,0,'',1763994821,1763994821),(4,'定期90ngày','fixed',0.350000,'+35.00%',90,1,1.00,0,0.00,1000.00,0.00,0.00,0.00,3,0,0,'',1763994821,1763994821);
/*!40000 ALTER TABLE `yzz_yuebao_product` ENABLE KEYS */;
UNLOCK TABLES;
