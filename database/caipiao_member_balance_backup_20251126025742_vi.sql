--
-- Table structure for table `caipiao_member_balance_backup_20261126025742`
--

DROP TABLE IF EXISTS `caipiao_member_balance_backup_20261126025742`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `caipiao_member_balance_backup_20261126025742` (
  `id` int(11) NOT NULL DEFAULT '0',
  `username` char(60) CHARACTER SET utf8 NOT NULL,
  `balance` decimal(14,3) NOT NULL COMMENT 'Số tiền',
  `money` decimal(14,2) NOT NULL DEFAULT '0.00' COMMENT 'Số dư',
  `backup_time` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `caipiao_member_balance_backup_20261126025742`
--

LOCK TABLES `caipiao_member_balance_backup_20261126025742` WRITE;
/*!40000 ALTER TABLE `caipiao_member_balance_backup_20261126025742` DISABLE KEYS */;
INSERT INTO `caipiao_member_balance_backup_20261126025742` VALUES (9872,'timibbs',105855.500,0.00,'2026-11-26 02:57:42'),(9873,'zxdssda',0.000,0.00,'2026-11-26 02:57:42'),(9874,'123456',1000000.000,0.00,'2026-11-26 02:57:42'),(9875,'1234567',10000.000,0.00,'2026-11-26 02:57:42');
/*!40000 ALTER TABLE `caipiao_member_balance_backup_20261126025742` ENABLE KEYS */;
UNLOCK TABLES;
