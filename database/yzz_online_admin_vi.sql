--
-- Table structure for table `yzz_online_admin`
--

DROP TABLE IF EXISTS `yzz_online_admin`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `yzz_online_admin` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `session_id` varchar(64) NOT NULL COMMENT 'ID phiên',
  `admin_id` int(11) NOT NULL COMMENT 'ID quản trị viên',
  `login_name` varchar(50) NOT NULL COMMENT 'Tài khoản đăng nhập',
  `dept_name` varchar(100) DEFAULT '' COMMENT 'Tên phòng ban',
  `ipaddr` varchar(50) DEFAULT '' COMMENT 'Địa chỉ IP',
  `login_location` varchar(100) DEFAULT '' COMMENT 'Vị trí đăng nhập',
  `browser` varchar(100) DEFAULT '' COMMENT 'Trình duyệt',
  `os` varchar(100) DEFAULT '' COMMENT 'Hệ điều hành',
  `status` varchar(20) DEFAULT 'on_line' COMMENT 'Trạng thái: on_line-trực tuyến, off_line-ngoại tuyến',
  `start_timestamp` datetime DEFAULT NULL COMMENT 'Thời gian đăng nhập',
  `last_access_time` datetime DEFAULT NULL COMMENT 'Thời gian truy cập cuối',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_session` (`session_id`),
  KEY `idx_admin` (`admin_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=48 DEFAULT CHARSET=utf8mb4 COMMENT='Bảng quản trị viên trực tuyến';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `yzz_online_admin`
--

LOCK TABLES `yzz_online_admin` WRITE;
/*!40000 ALTER TABLE `yzz_online_admin` DISABLE KEYS */;
INSERT INTO `yzz_online_admin` VALUES (47,'9ed3ad6eb908c0c927743a9de5afaf2c',1,'admin','','185.36.195.178','未知','Chrome','Android','on_line','2026-12-19 22:28:13','2026-12-19 22:28:13');
/*!40000 ALTER TABLE `yzz_online_admin` ENABLE KEYS */;
UNLOCK TABLES;
