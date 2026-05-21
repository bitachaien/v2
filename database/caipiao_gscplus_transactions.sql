--
-- Table structure for table `caipiao_gscplus_transactions`
-- GSC+ Seamless Wallet Transaction Records
--

DROP TABLE IF EXISTS `caipiao_gscplus_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `caipiao_gscplus_transactions` (
  `id` BIGINT(20) NOT NULL AUTO_INCREMENT COMMENT 'ID chính',
  `transaction_id` VARCHAR(100) NOT NULL COMMENT 'ID giao dịch từ GSC+',
  `uid` INT(11) NOT NULL COMMENT 'ID người dùng',
  `product_code` VARCHAR(50) NOT NULL COMMENT 'Mã sản phẩm game',
  `action` ENUM('withdraw', 'deposit') NOT NULL COMMENT 'Loại giao dịch: withdraw=rút tiền, deposit=nạp tiền',
  `wager_code` VARCHAR(100) DEFAULT NULL COMMENT 'Mã cược',
  `wager_status` VARCHAR(50) DEFAULT NULL COMMENT 'Trạng thái cược',
  `round_id` VARCHAR(100) DEFAULT NULL COMMENT 'ID vòng chơi',
  `amount` DECIMAL(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Số tiền giao dịch',
  `bet_amount` DECIMAL(15,2) DEFAULT '0.00' COMMENT 'Số tiền cược',
  `valid_bet_amount` DECIMAL(15,2) DEFAULT '0.00' COMMENT 'Số tiền cược hợp lệ',
  `prize_amount` DECIMAL(15,2) DEFAULT '0.00' COMMENT 'Số tiền thắng',
  `tip_amount` DECIMAL(15,2) DEFAULT '0.00' COMMENT 'Tiền tip',
  `before_balance` DECIMAL(15,2) DEFAULT '0.00' COMMENT 'Số dư trước giao dịch',
  `after_balance` DECIMAL(15,2) DEFAULT '0.00' COMMENT 'Số dư sau giao dịch',
  `settled_at` BIGINT(20) DEFAULT NULL COMMENT 'Timestamp thanh toán (milliseconds)',
  `payload` JSON DEFAULT NULL COMMENT 'Dữ liệu gốc từ GSC+ (JSON format)',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Thời gian tạo',
  `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT 'Thời gian cập nhật',
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_transaction_id` (`transaction_id`),
  KEY `idx_uid` (`uid`),
  KEY `idx_wager_code` (`wager_code`),
  KEY `idx_product_code` (`product_code`),
  KEY `idx_action` (`action`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_settled_at` (`settled_at`),
  KEY `idx_uid_created` (`uid`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='GSC+ Giao dịch ví liền mạch';
/*!40101 SET character_set_client = @saved_cs_client */;