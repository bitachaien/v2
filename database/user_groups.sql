--
-- Table structure for table `user_groups`
--

DROP TABLE IF EXISTS `user_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_groups` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `name` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Tên nhóm',
  `is_agent` tinyint(4) NOT NULL DEFAULT '0' COMMENT '0-người dùng thường, 1-đại lý',
  `lowest_bet` decimal(18,4) NOT NULL DEFAULT '0.0000' COMMENT 'Mức đặt cược thấp nhất',
  `highest_bet` decimal(18,4) NOT NULL DEFAULT '0.0000' COMMENT 'Mức đặt cược cao nhất',
  `rebate_schema` json DEFAULT NULL COMMENT 'Cấu hình hoàn trả cược',
  `title` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Tiêu đề',
  `rank_rules` json DEFAULT NULL COMMENT 'Quy tắc cấp độ',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Bảng nhóm người dùng';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_groups`
--

LOCK TABLES `user_groups` WRITE;
/*!40000 ALTER TABLE `user_groups` DISABLE KEYS */;
INSERT INTO `user_groups` VALUES (1,'普通Người dùng',0,1.0000,10000.0000,NULL,NULL,NULL),(2,'代理Người dùng',1,1.0000,50000.0000,NULL,NULL,NULL);
/*!40000 ALTER TABLE `user_groups` ENABLE KEYS */;
UNLOCK TABLES;
