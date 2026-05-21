--
-- Table structure for table `caipiao_dailiyongjin`
--

DROP TABLE IF EXISTS `caipiao_dailiyongjin`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `caipiao_dailiyongjin` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `listorder` smallint(6) NOT NULL,
  `status` tinyint(4) NOT NULL DEFAULT '1',
  `addtime` int(11) NOT NULL,
  `uid` int(11) NOT NULL COMMENT 'ID thành viên',
  `username` char(30) NOT NULL COMMENT 'Tên thành viên',
  `touzhuedu` decimal(10,2) NOT NULL COMMENT 'Doanh thu cấp dưới',
  `yongjinfw` char(30) NOT NULL COMMENT 'Phạm vi hoa hồng',
  `amount` decimal(10,2) NOT NULL COMMENT 'Số tiền hoa hồng',
  `yjtype` char(10) NOT NULL COMMENT 'Loại hoa hồng',
  `oddtime` int(11) NOT NULL COMMENT 'Thời gian nhận',
  `shenhe` tinyint(4) NOT NULL COMMENT 'Trạng thái duyệt',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `caipiao_dailiyongjin`
--

LOCK TABLES `caipiao_dailiyongjin` WRITE;
/*!40000 ALTER TABLE `caipiao_dailiyongjin` DISABLE KEYS */;
/*!40000 ALTER TABLE `caipiao_dailiyongjin` ENABLE KEYS */;
UNLOCK TABLES;
