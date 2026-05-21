--
-- Table structure for table `caipiao_withdraw_account`
--

DROP TABLE IF EXISTS `caipiao_withdraw_account`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `caipiao_withdraw_account` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'tài khoảnID',
  `uid` int(11) NOT NULL COMMENT 'ID người dùng',
  `type` varchar(20) NOT NULL DEFAULT 'bank' COMMENT 'tài khoản Loạibank=thẻ ngân hàng, usdt=USD T, alipay=, wechat=',
  `bank_name` varchar(50) DEFAULT '' COMMENT 'ngân hàngtên',
  `bank_account` varchar(50) DEFAULT '' COMMENT 'số thẻ ngân hàng/tài khoản',
  `account_name` varchar(50) DEFAULT '' COMMENT 'chủ thẻhọ tên/tài khoản',
  `bank_branch` varchar(100) DEFAULT '' COMMENT 'chi nhánh ngân hàng',
  `usdt_address` varchar(100) DEFAULT '' COMMENT 'USDTđịa chỉ',
  `usdt_network` varchar(20) DEFAULT '' COMMENT 'USD TTR C20/ER C20',
  `is_default` tinyint(1) DEFAULT '0' COMMENT 'mặc địnhtài khoản0=, 1=',
  `status` tinyint(1) DEFAULT '1' COMMENT 'Trạng thái0=Vô hiệu hóa, 1=Kích hoạt',
  `created_at` int(11) NOT NULL COMMENT 'Thời gian tạo',
  `updated_at` int(11) DEFAULT '0' COMMENT 'Thời gian cập nhật',
  `qr_code` varchar(500) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_uid` (`uid`),
  KEY `idx_type` (`type`),
  KEY `idx_is_default` (`is_default`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COMMENT='Rút tiềnBảng tài khoản';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `caipiao_withdraw_account`
--

LOCK TABLES `caipiao_withdraw_account` WRITE;
/*!40000 ALTER TABLE `caipiao_withdraw_account` DISABLE KEYS */;
INSERT INTO `caipiao_withdraw_account` VALUES (1,9875,'usdt','','','','','TGGdybAvW5hs4M9qiWNSAF16xY8Sw4X1Fo','TRC20',0,1,1764246912,1764246912,NULL),(2,9875,'alipay','','18067888888','张','','','',1,1,1764246941,1764247146,NULL),(7,9874,'usdt','USDT-TRC20','','USDT提现','','TKbM1Sv2Y1my6z1drv5uPwsLn8rJZPZa45','TRC20',1,1,2026,0,NULL),(8,9874,'wechat','微信','wechat_qr','洋洋','','','',0,1,2026,0,NULL),(10,9872,'usdt','USDT-TRC20','','USDT提现','','TH4QAUdpQaLq323JmX6AY8A6BQbHF2iBEp','TRC20',1,1,2026,0,NULL),(15,9872,'wechat','微信','wechat_qr','杀杀杀','','','',0,1,2026,2026,'/app/admin/upload/img/20261206/6933d00b7445.webp'),(16,9872,'alipay','支付宝','123456','杀杀杀','','','',0,1,2026,0,'http://127.0.0.1:8788/uploads/qrcode/20261206/6933d020a14bb_9872.webp'),(17,9874,'alipay','支付宝','9999','洋洋','','','',0,1,2026,2026,'/app/admin/upload/img/20261206/6933d0b96709.webp');
/*!40000 ALTER TABLE `caipiao_withdraw_account` ENABLE KEYS */;
UNLOCK TABLES;
