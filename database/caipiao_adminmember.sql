--
-- Table structure for table `caipiao_adminmember`
--

DROP TABLE IF EXISTS `caipiao_adminmember`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `caipiao_adminmember` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID quản trị viên',
  `groupid` smallint(6) NOT NULL COMMENT 'ID nhóm',
  `username` char(60) NOT NULL COMMENT 'Tên đăng nhập',
  `email` char(60) NOT NULL COMMENT 'Email',
  `password` char(64) NOT NULL COMMENT 'Mật khẩu (mã hóa)',
  `safecode` mediumint(9) NOT NULL DEFAULT '1234' COMMENT 'Mã bảo mật',
  `loginip` char(20) NOT NULL COMMENT 'IP đăng nhập cuối',
  `iparea` char(20) NOT NULL COMMENT 'Khu vực IP',
  `logintime` int(11) NOT NULL COMMENT 'Thời gian đăng nhập cuối',
  `islock` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Trạng thái khóa: 0-bình thường, 1-đã khóa',
  PRIMARY KEY (`id`),
  KEY `groupid` (`groupid`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='Bảng tài khoản quản trị viên';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `caipiao_adminmember`
--

LOCK TABLES `caipiao_adminmember` WRITE;
/*!40000 ALTER TABLE `caipiao_adminmember` DISABLE KEYS */;
INSERT INTO `caipiao_adminmember` VALUES (11,1,'admin','','MDAwMDAwMDAwMLGIet6Gp7lr',888888,'156.59.13.143','新西兰',1764411199,0);
/*!40000 ALTER TABLE `caipiao_adminmember` ENABLE KEYS */;
UNLOCK TABLES;
