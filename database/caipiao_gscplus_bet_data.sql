--
-- Table structure for table `caipiao_gscplus_bet_data`
-- GSC+ Detailed Betting Records
--

DROP TABLE IF EXISTS `caipiao_gscplus_bet_data`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `caipiao_gscplus_bet_data` (
  `id` BIGINT(20) NOT NULL AUTO_INCREMENT COMMENT 'ID chính',
  `uid` INT(11) NOT NULL COMMENT 'ID người dùng',
  `member_account` VARCHAR(100) NOT NULL COMMENT 'Tài khoản người chơi',
  `wager_code` VARCHAR(100) NOT NULL COMMENT 'Mã cược duy nhất',
  `round_id` VARCHAR(100) DEFAULT NULL COMMENT 'ID vòng chơi',
  `product_code` VARCHAR(50) NOT NULL COMMENT 'Mã sản phẩm',
  `game_code` VARCHAR(50) DEFAULT NULL COMMENT 'Mã game',
  `bet_amount` DECIMAL(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Số tiền cược',
  `valid_bet_amount` DECIMAL(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Số tiền cược hợp lệ',
  `prize_amount` DECIMAL(15,2) DEFAULT '0.00' COMMENT 'Số tiền thắng',
  `wager_status` VARCHAR(50) NOT NULL COMMENT 'Trạng thái cược (settled, cancelled, etc.)',
  `wager_type` VARCHAR(50) DEFAULT NULL COMMENT 'Loại cược',
  `currency` VARCHAR(10) NOT NULL DEFAULT 'CNY' COMMENT 'Loại tiền tệ',
  `settled_at` BIGINT(20) DEFAULT NULL COMMENT 'Timestamp thanh toán (milliseconds)',
  `payload` JSON DEFAULT NULL COMMENT 'Dữ liệu gốc từ GSC+ (JSON format)',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Thời gian tạo',
  `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT 'Thời gian cập nhật',
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_wager_code` (`wager_code`),
  KEY `idx_uid` (`uid`),
  KEY `idx_member_account` (`member_account`),
  KEY `idx_product_code` (`product_code`),
  KEY `idx_game_code` (`game_code`),
  KEY `idx_wager_status` (`wager_status`),
  KEY `idx_settled_at` (`settled_at`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_uid_product` (`uid`, `product_code`),
  KEY `idx_uid_settled` (`uid`, `settled_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='GSC+ Dữ liệu cược chi tiết';
/*!40101 SET character_set_client = @saved_cs_client */;