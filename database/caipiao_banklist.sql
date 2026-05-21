--
-- Table structure for table `caipiao_banklist`
--

DROP TABLE IF EXISTS `caipiao_banklist`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `caipiao_banklist` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID khóa chính',
  `uid` int(11) NOT NULL COMMENT 'ID hội viên',
  `username` char(30) NOT NULL COMMENT 'Tên đăng nhập',
  `bankaddress` varchar(80) NOT NULL COMMENT 'Địa chỉ ngân hàng mở tài khoản',
  `bankname` varchar(60) NOT NULL COMMENT 'Tên ngân hàng',
  `bankcode` char(20) NOT NULL COMMENT 'Mã ngân hàng',
  `bankbranch` varchar(80) NOT NULL COMMENT 'Chi nhánh',
  `accountname` varchar(30) NOT NULL COMMENT 'Tên chủ tài khoản',
  `banknumber` varchar(22) NOT NULL COMMENT 'Số tài khoản ngân hàng',
  `isdefault` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Mặc định: 0-không, 1-có',
  `state` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Trạng thái: 0-chờ duyệt, 1-đã duyệt, 2-từ chối',
  `date` datetime NOT NULL COMMENT 'Thời gian tạo',
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`),
  KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='Bảng liên kết ngân hàng thành viên';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `caipiao_banklist`
--

LOCK TABLES `caipiao_banklist` WRITE;
/*!40000 ALTER TABLE `caipiao_banklist` DISABLE KEYS */;
/*!40000 ALTER TABLE `caipiao_banklist` ENABLE KEYS */;
UNLOCK TABLES;
