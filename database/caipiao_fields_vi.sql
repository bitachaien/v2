--
-- Table structure for table `caipiao_fields`
--

DROP TABLE IF EXISTS `caipiao_fields`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `caipiao_fields` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID trường',
  `title` varchar(120) NOT NULL COMMENT 'Tiêu đề trường',
  `tbname` char(30) NOT NULL COMMENT 'Tên bảng',
  `name` char(30) NOT NULL COMMENT 'Tên trường',
  `fieldtype` char(20) NOT NULL COMMENT 'Loại trường',
  `length` smallint(6) NOT NULL COMMENT 'Độ dài trường',
  `istitle` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'Là tiêu đề: 0-không, 1-có',
  `islist` tinyint(4) NOT NULL DEFAULT '1' COMMENT 'Hiển thị danh sách: 0-không, 1-có',
  `remark` varchar(60) NOT NULL COMMENT 'Ghi chú',
  `setting` text NOT NULL COMMENT 'Cài đặt trường',
  `listorder` smallint(6) NOT NULL COMMENT 'Thứ tự sắp xếp',
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT 'Trạng thái: 0-vô hiệu hóa, 1-kích hoạt',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='Bảng quản lý trường tùy chỉnh';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `caipiao_fields`
--

LOCK TABLES `caipiao_fields` WRITE;
/*!40000 ALTER TABLE `caipiao_fields` DISABLE KEYS */;
/*!40000 ALTER TABLE `caipiao_fields` ENABLE KEYS */;
UNLOCK TABLES;
