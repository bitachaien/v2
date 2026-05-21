--
-- Table structure for table `caipiao_activity_participation`
--

DROP TABLE IF EXISTS `caipiao_activity_participation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `caipiao_activity_participation` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `activity_id` int(11) NOT NULL COMMENT 'ID hoạt động',
  `reward_id` int(11) NOT NULL COMMENT 'ID cấu hình phần thưởng',
  `uid` int(11) NOT NULL COMMENT 'ID người dùng',
  `username` varchar(60) NOT NULL COMMENT 'Tên người dùng',
  `reward_type` varchar(30) NOT NULL COMMENT 'Loại phần thưởng',
  `condition_value` varchar(100) DEFAULT NULL COMMENT 'Giá trị điều kiện kích hoạt',
  `reward_amount` decimal(20,2) NOT NULL COMMENT 'Số tiền thưởng',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Trạng thái: 0-chờ duyệt 1-đã phát 2-đã từ chối',
  `trano` varchar(60) DEFAULT NULL COMMENT 'Mã giao dịch',
  `apply_time` int(11) NOT NULL COMMENT 'Thời gian đăng ký',
  `audit_time` int(11) DEFAULT NULL COMMENT 'Thời gian duyệt',
  `audit_admin` varchar(60) DEFAULT NULL COMMENT 'Quản trị viên duyệt',
  `audit_remark` varchar(255) DEFAULT NULL COMMENT 'Ghi chú duyệt',
  `ip` varchar(45) DEFAULT NULL COMMENT 'Địa chỉ IP',
  PRIMARY KEY (`id`),
  KEY `idx_uid` (`uid`),
  KEY `idx_activity_id` (`activity_id`),
  KEY `idx_reward_id` (`reward_id`),
  KEY `idx_status` (`status`),
  KEY `idx_apply_time` (`apply_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Bảng ghi nhận tham gia hoạt động';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `caipiao_activity_participation`
--

LOCK TABLES `caipiao_activity_participation` WRITE;
/*!40000 ALTER TABLE `caipiao_activity_participation` DISABLE KEYS */;
/*!40000 ALTER TABLE `caipiao_activity_participation` ENABLE KEYS */;
UNLOCK TABLES;
