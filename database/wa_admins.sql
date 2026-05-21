--
-- Table structure for table `wa_admins`
--

DROP TABLE IF EXISTS `wa_admins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wa_admins` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `username` varchar(32) NOT NULL COMMENT 'Tên đăng nhập',
  `nickname` varchar(40) NOT NULL COMMENT 'Biệt danh',
  `password` varchar(255) NOT NULL COMMENT 'Mật khẩu đã mã hóa',
  `avatar` varchar(255) DEFAULT '/app/admin/avatar.png' COMMENT 'Ảnh đại diện',
  `email` varchar(100) DEFAULT NULL COMMENT 'Email',
  `mobile` varchar(16) DEFAULT NULL COMMENT 'Số điện thoại',
  `real_name` varchar(50) DEFAULT '' COMMENT 'Họ tên thật',
  `sex` tinyint(1) DEFAULT '0' COMMENT '0-không xác định, 1-nam, 2-nữ',
  `address` varchar(255) DEFAULT '' COMMENT 'Địa chỉ',
  `des` text COMMENT 'Mô tả',
  `created_at` datetime DEFAULT NULL COMMENT 'Thời gian tạo',
  `updated_at` datetime DEFAULT NULL COMMENT 'Thời gian cập nhật',
  `login_at` datetime DEFAULT NULL COMMENT 'Thời gian đăng nhập cuối',
  `status` tinyint(4) DEFAULT NULL COMMENT '0-kích hoạt, 1-vô hiệu hóa',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COMMENT='Bảng quản trị viên';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wa_admins`
--

LOCK TABLES `wa_admins` WRITE;
/*!40000 ALTER TABLE `wa_admins` DISABLE KEYS */;
INSERT INTO `wa_admins` VALUES (1,'admin','超级管理员','$2y$10$XnVjwrtlA7f4k4HCo1Icc.1sI1mLeufr//8RmF1y4b9WZrzAFIcYe','/app/admin/avatar.png','1@gmail.com','18888888888','鱼崽',2,'泰国','鱼崽天下第一','2025-10-28 19:36:27','2026-12-19 22:28:13','2026-12-19 22:28:13',0);
/*!40000 ALTER TABLE `wa_admins` ENABLE KEYS */;
UNLOCK TABLES;
