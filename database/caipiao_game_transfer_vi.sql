--
-- Table structure for table `caipiao_game_transfer`
--

DROP TABLE IF EXISTS `caipiao_game_transfer`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `caipiao_game_transfer` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_no` varchar(50) NOT NULL COMMENT 'mã đơn',
  `uid` int(11) NOT NULL COMMENT 'ID người dùng',
  `platform` varchar(20) NOT NULL COMMENT 'mã nhà cung cấp',
  `type` varchar(10) NOT NULL COMMENT 'Loạiin=, out=',
  `amount` decimal(15,2) NOT NULL COMMENT 'Số tiền',
  `before_balance` decimal(15,2) DEFAULT '0.00' COMMENT 'chuyển khoảnSố dư',
  `after_balance` decimal(15,2) DEFAULT '0.00' COMMENT 'chuyển khoảnSố dư',
  `game_balance` decimal(15,2) DEFAULT '0.00' COMMENT 'trò chơiSố dư',
  `status` tinyint(1) DEFAULT '0' COMMENT 'Trạng thái0=đang xử lý, 1=thành công, 2=thất bại',
  `created_at` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_order_no` (`order_no`),
  KEY `idx_uid` (`uid`),
  KEY `idx_platform` (`platform`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='游戏Chuyển khoảnBản ghiBảng';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `caipiao_game_transfer`
--

LOCK TABLES `caipiao_game_transfer` WRITE;
/*!40000 ALTER TABLE `caipiao_game_transfer` DISABLE KEYS */;
/*!40000 ALTER TABLE `caipiao_game_transfer` ENABLE KEYS */;
UNLOCK TABLES;
