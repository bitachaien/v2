--
-- Table structure for table `caipiao_verify_code`
--

DROP TABLE IF EXISTS `caipiao_verify_code`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `caipiao_verify_code` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `type` varchar(20) NOT NULL COMMENT 'phone/email',
  `target` varchar(100) NOT NULL,
  `code` varchar(10) NOT NULL,
  `expire_time` int(11) NOT NULL,
  `created_at` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user_type` (`user_id`,`type`,`target`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Xác thực码Bảng';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `caipiao_verify_code`
--

LOCK TABLES `caipiao_verify_code` WRITE;
/*!40000 ALTER TABLE `caipiao_verify_code` DISABLE KEYS */;
/*!40000 ALTER TABLE `caipiao_verify_code` ENABLE KEYS */;
UNLOCK TABLES;
