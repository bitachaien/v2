--
-- Table structure for table `yzz_yuebao_holding`
--

DROP TABLE IF EXISTS `yzz_yuebao_holding`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `yzz_yuebao_holding` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID khóa chính',
  `order_id` varchar(32) NOT NULL DEFAULT '' COMMENT 'Mã đơn hàng',
  `uid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'ID người dùng',
  `username` varchar(32) NOT NULL DEFAULT '' COMMENT 'Tên đăng nhập',
  `product_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'ID sản phẩm',
  `product_name` varchar(64) NOT NULL DEFAULT '' COMMENT 'Tên sản phẩm',
  `amount` decimal(14,2) NOT NULL DEFAULT '0.00' COMMENT 'Số tiền gửi',
  `rate` decimal(8,6) NOT NULL DEFAULT '0.000000' COMMENT 'Lãi suất',
  `duration_days` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT 'Số ngày kỳ hạn (0-không kỳ hạn)',
  `expected_interest` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT 'Lãi dự kiến',
  `actual_interest` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT 'Lãi thực tế',
  `status` varchar(20) NOT NULL DEFAULT 'running' COMMENT 'Trạng thái: running-đang chạy, settled-đã thanh toán, done-đã hoàn thành, canceled-đã hủy',
  `end_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'Thời gian đáo hạn',
  `settle_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'Thời gian thanh toán',
  `last_interest_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'Thời gian tính lãi cuối',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'Thời gian tạo',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'Thời gian cập nhật',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_order_id` (`order_id`),
  KEY `idx_uid` (`uid`),
  KEY `idx_status` (`status`),
  KEY `idx_end_time` (`end_time`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COMMENT='Bảng nắm giữ sản phẩm tiết kiệm';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `yzz_yuebao_holding`
--

LOCK TABLES `yzz_yuebao_holding` WRITE;
/*!40000 ALTER TABLE `yzz_yuebao_holding` DISABLE KEYS */;
INSERT INTO `yzz_yuebao_holding` VALUES (1,'YBMIG17643245889872',9872,'timibbs',1,'活期存入',1000.00,0.025000,0,0.00,0.00,'settled',0,1764325411,0,1719679415,1719679415),(2,'YBIN202612020445495274',9872,'timibbs',2,'定期7天',1000.00,0.125500,7,125.50,0.00,'settled',1765226749,1764874746,1764621949,1764621949,1764874474),(3,'YBIN202612020645196704906645',9872,'timibbs',1,'活期存入',900.00,0.025000,0,0.00,0.00,'settled',0,1764874748,1764871392,1764629119,1764874474),(4,'YBIN202612050107073349809466',9872,'timibbs',1,'活期存入',100.00,0.025000,0,0.00,0.00,'done',0,1764961200,1764960855,1764868027,1764960870),(5,'YBIN202612050236247619195897',9872,'timibbs',1,'活期存入',1000.00,0.025000,0,0.00,0.00,'done',0,1764961200,1764960855,1764873384,1764960870),(6,'YBIN202612050308407457643488',9872,'timibbs',1,'活期存入',10000.00,0.025000,0,0.00,0.00,'done',0,1764961200,1764960855,1764875320,1764960870),(7,'YBIN202612050327589319059342',9874,'123456',1,'活期存入',100000.00,0.025000,0,0.00,0.00,'done',0,1764961200,1764960855,1764876478,1764960855),(8,'YBIN202612190716551685919638',9872,'timibbs',1,'活期存入',1000.00,0.025000,0,0.00,15.60,'running',0,0,1766168502,1766099815,1766168502);
/*!40000 ALTER TABLE `yzz_yuebao_holding` ENABLE KEYS */;
UNLOCK TABLES;
