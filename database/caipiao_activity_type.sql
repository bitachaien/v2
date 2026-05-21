--
-- Table structure for table `caipiao_activity_type`
--

DROP TABLE IF EXISTS `caipiao_activity_type`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `caipiao_activity_type` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID loại',
  `name` varchar(50) NOT NULL COMMENT 'Tên loại: Đơn may mắn, Lương tuần, Vua đánh mã PG, v.v.',
  `code` varchar(50) NOT NULL COMMENT 'Mã loại: lucky_order, weekly_salary, pg_betting_king, v.v.',
  `icon` varchar(255) DEFAULT '' COMMENT 'Biểu tượng',
  `sort` int(11) DEFAULT '0' COMMENT 'Sắp xếp',
  `status` tinyint(1) DEFAULT '1' COMMENT 'Trạng thái: 1-kích hoạt, 0-vô hiệu hóa',
  `remark` varchar(255) DEFAULT '' COMMENT 'Ghi chú',
  `created_at` int(11) DEFAULT NULL COMMENT 'Thời gian tạo',
  `updated_at` int(11) DEFAULT NULL COMMENT 'Thời gian cập nhật',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COMMENT='Bảng loại hoạt động (Định danh mẫu)';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `caipiao_activity_type`
--

LOCK TABLES `caipiao_activity_type` WRITE;
/*!40000 ALTER TABLE `caipiao_activity_type` DISABLE KEYS */;
INSERT INTO `caipiao_activity_type` VALUES 
(1,'Hoạt động nạp tiền','deposit','',10,0,'',1764313004,1766066827),
(2,'Hoạt động hoàn trả','rebate','',9,0,'',1764313004,1766066828),
(3,'Hoạt động tiền thưởng','bonus','',8,0,'',1764313004,1766066829),
(4,'Hoạt động VIP','vip','',7,0,'',1764313004,1766066829),
(5,'Hoạt động khác','other','',3,0,'',1764313004,1766066830),
(13,'Đơn cược may mắn','lucky_order','',100,1,'',NULL,NULL),
(14,'Cứu trợ thua lỗ','loss_rescue','',90,1,'',NULL,NULL),
(15,'Lương tuần','weekly_salary','',80,1,'',NULL,NULL),
(16,'Lương tháng','monthly_salary','',70,1,'',NULL,NULL),
(17,'Vua đánh mã PG','pg_betting_king','',60,1,'',NULL,NULL),
(21,'Hoạt động chung','general_activity','',5,1,'',1766072720,1766072720),
(22,'Hoạt động điểm danh','checkin','',95,1,'',1766085097,1766085097);
/*!40000 ALTER TABLE `caipiao_activity_type` ENABLE KEYS */;
UNLOCK TABLES;

-- Made with Bob