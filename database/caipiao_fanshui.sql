--
-- Table structure for table `caipiao_fanshui`
--

DROP TABLE IF EXISTS `caipiao_fanshui`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `caipiao_fanshui` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `trano` char(60) NOT NULL,
  `listorder` smallint(6) NOT NULL,
  `status` tinyint(4) NOT NULL DEFAULT '1',
  `addtime` int(11) NOT NULL,
  `uid` int(11) NOT NULL COMMENT 'ID thành viên',
  `username` char(30) NOT NULL COMMENT 'Tên thành viên',
  `groupname` char(30) NOT NULL COMMENT 'Cấp độ thành viên khi nhận hoàn trả',
  `bili` char(10) NOT NULL COMMENT 'Tỷ lệ hoàn trả',
  `touzhuedu` decimal(20,2) NOT NULL COMMENT 'Hạn mức doanh thu',
  `amount` decimal(20,2) NOT NULL COMMENT 'Số tiền hoàn trả',
  `oddtime` int(11) NOT NULL COMMENT 'Thời gian nhận',
  `shenhe` tinyint(4) NOT NULL COMMENT 'Trạng thái duyệt',
  `yongjinfw` char(80) NOT NULL COMMENT 'Phạm vi hoàn trả',
  `vendor_code` varchar(20) DEFAULT '' COMMENT 'Mã nhà cung cấp game (trống là hoàn trả hàng ngày)',
  `category_code` varchar(20) DEFAULT '' COMMENT 'Mã phân loại game',
  `gametype` tinyint(2) DEFAULT '0' COMMENT 'Loại game: 1-live casino, 2-bắn cá, 3-slot, 4-xổ số, 5-thể thao, 6-cờ bài, 7-esports',
  `betid` varchar(64) DEFAULT '' COMMENT 'Số đơn cược',
  `platform_type` varchar(20) DEFAULT '' COMMENT 'Loại nền tảng',
  `fanshui_type` tinyint(1) DEFAULT '1' COMMENT '1-hoàn trả người chơi 2-hoàn trả đại lý',
  `parent_uid` int(11) DEFAULT '0' COMMENT 'ID người dùng cấp trên (dùng cho hoàn trả đại lý)',
  `settle_time` int(11) DEFAULT '0' COMMENT 'Thời gian thanh toán (T1)',
  `can_claim` tinyint(1) DEFAULT '0' COMMENT 'Có thể nhận không',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `caipiao_fanshui`
--

LOCK TABLES `caipiao_fanshui` WRITE;
/*!40000 ALTER TABLE `caipiao_fanshui` DISABLE KEYS */;
INSERT INTO `caipiao_fanshui` VALUES (1,'DR202612021106076914',0,1,1764644767,9872,'timibbs','VIP9','0.5',47026.00,235.13,1764644767,1,'','','',0,'','',1,0,0,0);
/*!40000 ALTER TABLE `caipiao_fanshui` ENABLE KEYS */;
UNLOCK TABLES;
