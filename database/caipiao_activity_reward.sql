--
-- Table structure for table `caipiao_activity_reward`
--

DROP TABLE IF EXISTS `caipiao_activity_reward`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `caipiao_activity_reward` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `activity_id` int(11) NOT NULL COMMENT 'ID hoạt động liên kết',
  `reward_type` varchar(30) NOT NULL COMMENT 'Loại phần thưởng: lucky_order-đơn may mắn, loss_rescue-cứu trợ thua lỗ, weekly_salary-lương tuần',
  `level_name` varchar(50) NOT NULL COMMENT 'Tên bậc',
  `condition_min` decimal(20,2) NOT NULL DEFAULT '0.00' COMMENT 'Giá trị điều kiện tối thiểu',
  `condition_max` decimal(20,2) NOT NULL DEFAULT '0.00' COMMENT 'Giá trị điều kiện tối đa',
  `reward_amount` decimal(20,2) NOT NULL DEFAULT '0.00' COMMENT 'Số tiền thưởng',
  `reward_rate` decimal(5,2) NOT NULL DEFAULT '0.00' COMMENT 'Tỷ lệ thưởng (%)',
  `condition_type` varchar(30) NOT NULL DEFAULT 'amount' COMMENT 'Loại điều kiện: amount-số tiền, order_tail-số cuối đơn, bet_count-số lần đặt',
  `condition_value` varchar(100) DEFAULT NULL COMMENT 'Giá trị điều kiện (ví dụ số cuối: 888,8888)',
  `need_apply` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Có cần đăng ký không',
  `limit_times` int(11) NOT NULL DEFAULT '1' COMMENT 'Giới hạn số lần: 0-không giới hạn',
  `limit_period` varchar(20) NOT NULL DEFAULT 'once' COMMENT 'Chu kỳ giới hạn: once-một lần, daily-hàng ngày, weekly-hàng tuần, monthly-hàng tháng',
  `sort` int(11) NOT NULL DEFAULT '0' COMMENT 'Sắp xếp',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Trạng thái: 1-kích hoạt, 0-vô hiệu hóa',
  `created_at` int(11) DEFAULT NULL,
  `updated_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_activity_id` (`activity_id`),
  KEY `idx_reward_type` (`reward_type`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=97 DEFAULT CHARSET=utf8mb4 COMMENT='Bảng cấu hình phần thưởng hoạt động';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `caipiao_activity_reward`
--

LOCK TABLES `caipiao_activity_reward` WRITE;
/*!40000 ALTER TABLE `caipiao_activity_reward` DISABLE KEYS */;
INSERT INTO `caipiao_activity_reward` VALUES 
(1,2,'lucky_order','Số cuối đơn cược 888',0.00,0.00,888.00,0.00,'order_tail','888',1,3,'daily',1,1,1766060382,1766060382),
(2,2,'lucky_order','Số cuối đơn cược 8888',0.00,0.00,1888.00,0.00,'order_tail','8888',1,2,'daily',2,1,1766060382,1766060382),
(3,2,'lucky_order','Số cuối đơn cược 88888',0.00,0.00,2888.00,0.00,'order_tail','88888',1,3,'daily',3,1,1766060382,1766060382),
(4,3,'loss_rescue','Thua lỗ ≥1000',1000.00,4999.99,18.00,0.00,'amount',NULL,1,1,'daily',1,1,1766060382,1766060382),
(5,3,'loss_rescue','Thua lỗ ≥5000',5000.00,9999.99,88.00,0.00,'amount',NULL,1,1,'daily',2,1,1766060382,1766060382),
(6,3,'loss_rescue','Thua lỗ ≥10000',10000.00,49999.99,188.00,0.00,'amount',NULL,1,1,'daily',3,1,1766060382,1766060382),
(7,3,'loss_rescue','Thua lỗ ≥50000',50000.00,99999.99,888.00,0.00,'amount',NULL,1,1,'daily',4,1,1766060382,1766060382),
(8,3,'loss_rescue','Thua lỗ ≥100000',100000.00,999999.99,1888.00,0.00,'amount',NULL,1,1,'daily',5,1,1766060382,1766060382),
(9,4,'weekly_salary','Tổng cược ≥5000',5000.00,49999.99,8.00,0.00,'amount',NULL,1,1,'weekly',1,1,1766060382,1766060382),
(10,4,'weekly_salary','Tổng cược ≥50000',50000.00,99999.99,18.00,0.00,'amount',NULL,1,1,'weekly',2,1,1766060382,1766060382),
(11,4,'weekly_salary','Tổng cược ≥100000',100000.00,499999.99,38.00,0.00,'amount',NULL,1,1,'weekly',3,1,1766060382,1766060382),
(12,4,'weekly_salary','Tổng cược ≥500000',500000.00,999999.99,88.00,0.00,'amount',NULL,1,1,'weekly',4,1,1766060382,1766060382),
(13,4,'weekly_salary','Tổng cược ≥1000000',1000000.00,2999999.99,288.00,0.00,'amount',NULL,1,1,'weekly',5,1,1766060382,1766060382),
(14,4,'weekly_salary','Tổng cược ≥3000000',3000000.00,4999999.99,688.00,0.00,'amount',NULL,1,1,'weekly',6,1,1766060382,1766060382),
(15,4,'weekly_salary','Tổng cược ≥5000000',5000000.00,9999999.99,1188.00,0.00,'amount',NULL,1,1,'weekly',7,1,1766060382,1766060382),
(16,4,'weekly_salary','Tổng cược ≥10000000',10000000.00,999999999.99,2888.00,0.00,'amount',NULL,1,1,'weekly',8,1,1766060382,1766060382),
(17,13,'weekly_salary','Bậc 1',5000.00,50000.00,8.00,0.00,'amount',NULL,0,1,'weekly',1,1,1766078055,1766078055),
(18,13,'weekly_salary','Bậc 2',50000.00,100000.00,18.00,0.00,'amount',NULL,0,1,'weekly',2,1,1766078055,1766078055),
(19,13,'weekly_salary','Bậc 3',100000.00,500000.00,38.00,0.00,'amount',NULL,0,1,'weekly',3,1,1766078055,1766078055),
(20,13,'weekly_salary','Bậc 4',500000.00,1000000.00,88.00,0.00,'amount',NULL,0,1,'weekly',4,1,1766078055,1766078055),
(21,13,'weekly_salary','Bậc 5',1000000.00,3000000.00,288.00,0.00,'amount',NULL,0,1,'weekly',5,1,1766078055,1766078055),
(22,13,'weekly_salary','Bậc 6',3000000.00,5000000.00,688.00,0.00,'amount',NULL,0,1,'weekly',6,1,1766078055,1766078055),
(23,13,'weekly_salary','Bậc 7',5000000.00,10000000.00,1188.00,0.00,'amount',NULL,0,1,'weekly',7,1,1766078055,1766078055),
(24,13,'weekly_salary','Bậc 8',10000000.00,999999999.00,2888.00,0.00,'amount',NULL,0,1,'weekly',8,1,1766078055,1766078055),
(42,14,'lucky_order','Gấp 4 lần cược hợp lệ',0.00,0.00,2888.00,3.00,'order_tail','88888',0,3,'daily',3,1,1766082080,1766082080),
(43,14,'lucky_order','Gấp 2 lần cược hợp lệ',0.00,0.00,1888.00,2.00,'order_tail','8888',0,3,'daily',2,1,1766082080,1766082080),
(44,14,'lucky_order','Gấp 1 lần cược hợp lệ',0.00,0.00,888.00,1.00,'order_tail','888',0,3,'daily',1,1,1766082080,1766082080),
(66,17,'pg_betting_king','Bậc 3',100000.00,0.00,58.00,0.00,'amount','',0,1,'daily',3,1,1766083409,1766083409),
(67,17,'pg_betting_king','Bậc 1',10000.00,0.00,8.00,0.00,'amount','',0,1,'daily',1,1,1766083409,1766083409),
(68,17,'pg_betting_king','Bậc 4',500000.00,0.00,388.00,0.00,'amount','',0,1,'daily',4,1,1766083409,1766083409),
(69,17,'pg_betting_king','Bậc 5',1000000.00,0.00,888.00,0.00,'amount','',0,1,'daily',5,1,1766083409,1766083409),
(70,17,'pg_betting_king','Bậc 2',50000.00,0.00,28.00,0.00,'amount','',0,1,'daily',2,1,1766083409,1766083409),
(85,19,'checkin','Ngày thứ 5',5.00,0.00,12.00,0.00,'checkin','',0,1,'daily',5,1,1766087030,1766087030),
(86,19,'checkin','Ngày thứ 1',1.00,0.00,1.00,0.00,'checkin','',0,1,'daily',1,1,1766087030,1766087030),
(87,19,'checkin','Ngày thứ 3',3.00,0.00,5.00,0.00,'checkin','',0,1,'daily',3,1,1766087030,1766087030),
(88,19,'checkin','Ngày thứ 2',2.00,0.00,3.00,0.00,'checkin','',0,1,'daily',2,1,1766087030,1766087030),
(89,19,'checkin','Ngày thứ 6',6.00,0.00,18.00,0.00,'checkin','',0,1,'daily',6,1,1766087030,1766087030),
(90,19,'checkin','Ngày thứ 4',4.00,0.00,8.00,0.00,'checkin','',0,1,'daily',4,1,1766087030,1766087030),
(91,19,'checkin','Ngày thứ 7',7.00,0.00,28.00,0.00,'checkin','',0,1,'daily',7,1,1766087030,1766087030),
(92,15,'loss_rescue','Bậc 3',100000.00,0.00,58.00,0.00,'amount','',0,1,'daily',3,1,1766087836,1766087836),
(93,15,'loss_rescue','Bậc 5',1000000.00,0.00,888.00,0.00,'amount','',0,1,'daily',5,1,1766087837,1766087837),
(94,15,'loss_rescue','Bậc 1',10000.00,0.00,8.00,0.00,'amount','',0,1,'daily',1,1,1766087837,1766087837),
(95,15,'loss_rescue','Bậc 4',500000.00,0.00,388.00,0.00,'amount','',0,1,'daily',4,1,1766087837,1766087837),
(96,15,'loss_rescue','Bậc 2',50000.00,0.00,28.00,0.00,'amount','',0,1,'daily',2,1,1766087837,1766087837);
/*!40000 ALTER TABLE `caipiao_activity_reward` ENABLE KEYS */;
UNLOCK TABLES;

-- Made with Bob