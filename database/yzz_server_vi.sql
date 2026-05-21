--
-- Table structure for table `yzz_server`
--

DROP TABLE IF EXISTS `yzz_server`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `yzz_server` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL COMMENT 'Tên máy chủ',
  `ip` varchar(50) NOT NULL COMMENT 'Địa chỉ IP',
  `port` int(11) DEFAULT '22' COMMENT 'Cổng SSH',
  `type` varchar(20) DEFAULT 'local' COMMENT 'Loại: local-cục bộ, remote-từ xa',
  `status` varchar(20) DEFAULT 'running' COMMENT 'Trạng thái: running-đang chạy, stopped-đã dừng, error-lỗi',
  `sort` int(11) DEFAULT '0' COMMENT 'Thứ tự sắp xếp',
  `created_at` int(11) DEFAULT '0' COMMENT 'Thời gian tạo',
  `updated_at` int(11) DEFAULT '0' COMMENT 'Thời gian cập nhật',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COMMENT='Bảng danh sách máy chủ';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `yzz_server`
--

LOCK TABLES `yzz_server` WRITE;
/*!40000 ALTER TABLE `yzz_server` DISABLE KEYS */;
INSERT INTO `yzz_server` VALUES (1,'本地应用服务器','127.0.0.1',22,'local','running',1,1764343855,1764343855);
/*!40000 ALTER TABLE `yzz_server` ENABLE KEYS */;
UNLOCK TABLES;
