--
-- Table structure for table `caipiao_gscplus_config`
-- GSC+ API Configuration
--

DROP TABLE IF EXISTS `caipiao_gscplus_config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `caipiao_gscplus_config` (
  `id` INT(11) NOT NULL AUTO_INCREMENT COMMENT 'ID chính',
  `operator_code` VARCHAR(50) NOT NULL COMMENT 'Mã operator từ GSC+',
  `secret_key` VARCHAR(255) NOT NULL COMMENT 'Secret key để tạo chữ ký MD5 (mã hóa)',
  `api_url` VARCHAR(255) NOT NULL COMMENT 'URL API GSC+ (base URL)',
  `callback_url` VARCHAR(255) NOT NULL COMMENT 'URL callback của hệ thống',
  `currency` VARCHAR(10) NOT NULL DEFAULT 'CNY' COMMENT 'Loại tiền tệ (CNY, USD, VND, etc.)',
  `status` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Trạng thái: 1=active, 0=inactive',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Thời gian tạo',
  `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT 'Thời gian cập nhật',
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_operator_code` (`operator_code`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='GSC+ Cấu hình API';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Default configuration data for GSC+
-- Note: Update operator_code, secret_key, and URLs with actual values
--

LOCK TABLES `caipiao_gscplus_config` WRITE;
/*!40000 ALTER TABLE `caipiao_gscplus_config` DISABLE KEYS */;
INSERT INTO `caipiao_gscplus_config` (`operator_code`, `secret_key`, `api_url`, `callback_url`, `currency`, `status`) 
VALUES 
('YOUR_OPERATOR_CODE', 'YOUR_SECRET_KEY_ENCRYPTED', 'https://api.gscplus.com', 'https://yourdomain.com/api/gscplus/callback', 'CNY', 1);
/*!40000 ALTER TABLE `caipiao_gscplus_config` ENABLE KEYS */;
UNLOCK TABLES;