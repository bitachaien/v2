--
-- Table structure for table `wa_users`
--

DROP TABLE IF EXISTS `wa_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wa_users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Khóa chính',
  `username` varchar(32) NOT NULL COMMENT 'Tên đăng nhập',
  `nickname` varchar(40) NOT NULL COMMENT 'Biệt danh',
  `password` varchar(255) NOT NULL COMMENT 'Mật khẩu đã mã hóa',
  `sex` enum('0','1') NOT NULL DEFAULT '1' COMMENT '0-nữ, 1-nam',
  `avatar` varchar(255) DEFAULT NULL COMMENT 'Ảnh đại diện',
  `email` varchar(128) DEFAULT NULL COMMENT 'Email',
  `mobile` varchar(16) DEFAULT NULL COMMENT 'Số điện thoại',
  `level` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'Cấp độ người dùng',
  `birthday` date DEFAULT NULL COMMENT 'Ngày sinh',
  `money` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT 'Số dư (đồng)',
  `score` int(11) NOT NULL DEFAULT '0' COMMENT 'Điểm tích lũy',
  `last_time` datetime DEFAULT NULL COMMENT 'Thời gian đăng nhập cuối',
  `last_ip` varchar(50) DEFAULT NULL COMMENT 'IP đăng nhập cuối',
  `join_time` datetime DEFAULT NULL COMMENT 'Thời gian đăng ký',
  `join_ip` varchar(50) DEFAULT NULL COMMENT 'IP đăng ký',
  `token` varchar(50) DEFAULT NULL COMMENT 'Token xác thực',
  `created_at` datetime DEFAULT NULL COMMENT 'Thời gian tạo',
  `updated_at` datetime DEFAULT NULL COMMENT 'Thời gian cập nhật',
  `role` int(11) NOT NULL DEFAULT '1' COMMENT 'ID vai trò',
  `status` tinyint(4) NOT NULL DEFAULT '0' COMMENT '0-kích hoạt, 1-vô hiệu hóa',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  KEY `join_time` (`join_time`),
  KEY `mobile` (`mobile`),
  KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Bảng người dùng';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wa_users`
--

LOCK TABLES `wa_users` WRITE;
/*!40000 ALTER TABLE `wa_users` DISABLE KEYS */;
/*!40000 ALTER TABLE `wa_users` ENABLE KEYS */;
UNLOCK TABLES;
