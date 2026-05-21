--
-- Table structure for table `yzz_yuebao_stats`
--

DROP TABLE IF EXISTS `yzz_yuebao_stats`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `yzz_yuebao_stats` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID bản ghi',
  `uid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'ID người dùng',
  `current_amount` decimal(14,2) NOT NULL DEFAULT '0.00' COMMENT 'Số dư không kỳ hạn',
  `fixed_amount` decimal(14,2) NOT NULL DEFAULT '0.00' COMMENT 'Số dư có kỳ hạn',
  `total_interest` decimal(14,2) NOT NULL DEFAULT '0.00' COMMENT 'Tổng lãi tích lũy',
  `yesterday_interest` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT 'Lãi ngày hôm qua',
  `seven_day_rate` decimal(8,6) NOT NULL DEFAULT '0.000000' COMMENT 'Lãi suất 7 ngày',
  `is_open` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT 'Đã mở: 0-chưa mở, 1-đã mở',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'Thời gian tạo',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'Thời gian cập nhật',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_uid` (`uid`),
  KEY `idx_is_open` (`is_open`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COMMENT='Bảng thống kê tiết kiệm người dùng';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `yzz_yuebao_stats`
--

LOCK TABLES `yzz_yuebao_stats` WRITE;
/*!40000 ALTER TABLE `yzz_yuebao_stats` DISABLE KEYS */;
INSERT INTO `yzz_yuebao_stats` VALUES (2,9872,23211.40,-11200.00,921.55,1.52,0.007137,1,1764621949,1766168502),(3,9875,0.00,0.00,0.00,0.00,0.000000,0,1764862600,1764862600),(4,9874,200102.75,-100000.00,2394.27,13.70,0.007140,1,1764876469,1766084400),(5,9880,0.00,0.00,0.00,0.00,0.000000,0,1765119401,1765119401),(6,19131,0.00,0.00,0.00,0.00,0.000000,0,1766155170,1766155170);
/*!40000 ALTER TABLE `yzz_yuebao_stats` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping events for database 'by'
--

--
-- Dumping routines for database 'by'
--

--
-- Final view structure for view `view_activity_full`
--

/*!50001 DROP VIEW IF EXISTS `view_activity_full`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8 */;
/*!50001 SET character_set_results     = utf8 */;
/*!50001 SET collation_connection      = utf8_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`boyue`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `view_activity_full` AS select `a`.`id` AS `id`,`a`.`title` AS `title`,`a`.`desc` AS `desc`,`a`.`content` AS `content`,`a`.`banner` AS `banner`,`a`.`type` AS `type`,`a`.`type_code` AS `type_code`,`a`.`category` AS `category`,`a`.`start_date` AS `start_date`,`a`.`end_date` AS `end_date`,`a`.`status` AS `status`,`a`.`sort` AS `sort`,`a`.`created_at` AS `created_at`,`c`.`name` AS `category_name`,`c`.`icon` AS `category_icon`,`t`.`name` AS `type_name` from ((`caipiao_huodong` `a` left join `caipiao_activity_category` `c` on((`a`.`category` = `c`.`code`))) left join `caipiao_activity_type` `t` on((`a`.`type_code` = `t`.`code`))) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-12-20  2:34:48
