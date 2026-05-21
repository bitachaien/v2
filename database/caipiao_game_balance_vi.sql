--
-- Table structure for table `caipiao_game_balance`
--

DROP TABLE IF EXISTS `caipiao_game_balance`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `caipiao_game_balance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL COMMENT 'ID hội viên',
  `platform` varchar(20) NOT NULL COMMENT 'mã nhà cung cấp',
  `balance` decimal(15,2) DEFAULT '0.00' COMMENT 'số dư',
  `updated_at` int(11) DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_uid_platform` (`uid`,`platform`),
  KEY `idx_uid` (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Bảng số dư game';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `caipiao_game_balance`
--

LOCK TABLES `caipiao_game_balance` WRITE;
/*!40000 ALTER TABLE `caipiao_game_balance` DISABLE KEYS */;
/*!40000 ALTER TABLE `caipiao_game_balance` ENABLE KEYS */;
UNLOCK TABLES;
