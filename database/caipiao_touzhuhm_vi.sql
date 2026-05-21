--
-- Table structure for table `caipiao_touzhuhm`
--

DROP TABLE IF EXISTS `caipiao_touzhuhm`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `caipiao_touzhuhm` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `touzhuid` int(11) NOT NULL COMMENT 'đặt cược BảngI D',
  `isdraw` tinyint(4) NOT NULL DEFAULT '0' COMMENT '0 1 -1 -2',
  `trano` char(60) NOT NULL COMMENT 'mã phiếu',
  `yjf` char(10) NOT NULL DEFAULT '1' COMMENT '1 0.1 0.01 0.001',
  `typeid` char(20) NOT NULL COMMENT 'loại xổ số',
  `playid` char(30) NOT NULL COMMENT 'mã loại cược',
  `playtitle` varchar(60) NOT NULL COMMENT 'tên loại cược',
  `cptitle` varchar(30) NOT NULL COMMENT 'tiêu đề xổ số',
  `cpname` varchar(60) NOT NULL COMMENT 'tên xổ số',
  `expect` char(25) NOT NULL COMMENT 'mã kỳ',
  `uid` int(11) NOT NULL COMMENT 'ID hội viên',
  `username` char(60) NOT NULL COMMENT 'biệt danh hội viên',
  `itemcount` int(11) NOT NULL COMMENT 'số cược đặt',
  `beishu` smallint(6) NOT NULL DEFAULT '1' COMMENT 'tỷ lệ cược',
  `tzcode` longtext NOT NULL COMMENT 'số đã cược',
  `repoint` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT 'tỷ lệ hoàn cược',
  `repointamout` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT 'số tiền hoàn cược',
  `mode` varchar(40) NOT NULL COMMENT 'tiền thưởng',
  `amount` decimal(20,2) NOT NULL COMMENT 'số tiền đặt cược',
  `amountbefor` decimal(14,2) NOT NULL COMMENT 'số tiền đặt cược',
  `amountafter` decimal(14,2) NOT NULL COMMENT 'số tiền đặt cược',
  `okamount` decimal(20,2) NOT NULL COMMENT 'Số tiền',
  `okcount` smallint(6) NOT NULL COMMENT 'số cược trúng thưởng',
  `Chase` tinyint(4) NOT NULL DEFAULT '0' COMMENT '1cược dây 0',
  `stopChase` tinyint(4) NOT NULL COMMENT 'trúng thưởngcược dây 1',
  `oddtime` int(11) NOT NULL COMMENT 'thời gian đặt cược',
  `opencode` char(60) NOT NULL COMMENT 'số mở thưởng',
  `source` char(20) NOT NULL COMMENT 'pc,mobile',
  `play_type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0loại cược 1loại cược',
  `fenshu` int(11) NOT NULL DEFAULT '0' COMMENT 'cược nhómphần',
  `ishemai` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'cược nhóm',
  `rengou` int(11) NOT NULL DEFAULT '0' COMMENT 'cược nhómtham giaphần',
  `isbaodi` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'đảm bảo',
  `baodi` int(11) NOT NULL DEFAULT '0' COMMENT 'đảm bảophần',
  `jindu` decimal(14,2) NOT NULL DEFAULT '0.00' COMMENT 'cược nhóm',
  `hemaipic` decimal(14,2) NOT NULL DEFAULT '0.00' COMMENT 'cược nhóm',
  `isfull` int(11) NOT NULL DEFAULT '0' COMMENT 'phần',
  `payamount` decimal(14,2) NOT NULL DEFAULT '0.00' COMMENT 'cược nhómtham giaSố tiền',
  `bdjindu` decimal(14,2) NOT NULL DEFAULT '0.00' COMMENT 'đảm bảo',
  `showtype` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'cược nhóm',
  `opentime` int(11) NOT NULL DEFAULT '0' COMMENT 'mở thưởngthời gian',
  `realbaodi` int(11) NOT NULL DEFAULT '0' COMMENT 'đảm bảo',
  `winorno` int(6) DEFAULT '-1',
  `is_rebet` tinyint(1) DEFAULT '0',
  `time` int(10) unsigned DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`),
  KEY `username` (`username`),
  KEY `trano` (`trano`),
  KEY `isdraw` (`isdraw`),
  KEY `typeid` (`typeid`),
  KEY `playid` (`playid`),
  KEY `cpname` (`cpname`)
) ENGINE=InnoDB AUTO_INCREMENT=224018 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='Cược管理';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `caipiao_touzhuhm`
--

LOCK TABLES `caipiao_touzhuhm` WRITE;
/*!40000 ALTER TABLE `caipiao_touzhuhm` DISABLE KEYS */;
INSERT INTO `caipiao_touzhuhm` VALUES (224015,58637,0,'Y2406272230328','1','k3','k3hzbig','và值lớn','1xu快3','f1k3','202406271351',9872,'timibbs',1,1,'lớn',0.00,0.00,'1.70',10.00,10070.00,10060.00,0.00,0,0,0,1719498632,'','mobile',0,100,1,10,0,0,0.00,1.00,0,10.00,0.00,0,0,0,-1,0,1719498632),(224016,58649,0,'E2406300042244','1','k3','k3hzsmallodd','nhỏ单','1xu快3','f1k3','202406300043',9872,'timibbs',1,1,'nhỏ单',0.00,0.00,'1.96',10.00,8930.00,8920.00,0.00,0,0,0,1719679344,'','mobile',0,100,1,10,0,0,0.00,1.00,0,10.00,0.00,0,0,0,-1,0,1719679344),(224017,58653,0,'X2407202241055','1','x5','x5qsfs','前三直选复式','1xu11选5','yf11x5','202407201362',9872,'timibbs',4,1,'01,02|03,07|02,03',0.00,0.00,'975',1.00,8072.00,8064.00,0.00,0,0,0,1721486465,'','mobile',0,8,1,1,1,7,0.00,1.00,0,1.00,0.88,3,0,7,-1,0,1721486465);
/*!40000 ALTER TABLE `caipiao_touzhuhm` ENABLE KEYS */;
UNLOCK TABLES;
