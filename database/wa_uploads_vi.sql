--
-- Table structure for table `wa_uploads`
--

DROP TABLE IF EXISTS `wa_uploads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wa_uploads` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Khóa chính',
  `name` varchar(128) NOT NULL COMMENT 'Tên file',
  `url` varchar(255) NOT NULL COMMENT 'URL file',
  `admin_id` int(11) DEFAULT NULL COMMENT 'ID quản trị viên',
  `file_size` int(11) NOT NULL COMMENT 'Kích thước file',
  `mime_type` varchar(255) NOT NULL COMMENT 'Loại MIME',
  `image_width` int(11) DEFAULT NULL COMMENT 'Chiều rộng hình ảnh',
  `image_height` int(11) DEFAULT NULL COMMENT 'Chiều cao hình ảnh',
  `ext` varchar(128) NOT NULL COMMENT 'Phần mở rộng',
  `storage` varchar(255) NOT NULL DEFAULT 'local' COMMENT 'Vị trí lưu trữ',
  `created_at` date DEFAULT NULL COMMENT 'Thời gian tải lên',
  `category` varchar(128) DEFAULT NULL COMMENT 'Danh mục',
  `updated_at` date DEFAULT NULL COMMENT 'Thời gian cập nhật',
  PRIMARY KEY (`id`),
  KEY `category` (`category`),
  KEY `admin_id` (`admin_id`),
  KEY `name` (`name`),
  KEY `ext` (`ext`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Bảng quản lý file đính kèm';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wa_uploads`
--

LOCK TABLES `wa_uploads` WRITE;
/*!40000 ALTER TABLE `wa_uploads` DISABLE KEYS */;
/*!40000 ALTER TABLE `wa_uploads` ENABLE KEYS */;
UNLOCK TABLES;
