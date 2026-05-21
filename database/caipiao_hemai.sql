--
-- Table structure for table `caipiao_hemai`
--

DROP TABLE IF EXISTS `caipiao_hemai`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `caipiao_hemai` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT 'I D',
  `uid` bigint(20) NOT NULL COMMENT 'Người dùng I D',
  `username` varchar(60) NOT NULL COMMENT 'Người dùng',
  `cpname` varchar(60) NOT NULL COMMENT 'loại xổ sốmã(cqssc)',
  `cptitle` varchar(100) NOT NULL COMMENT 'loại tên xổ số',
  `typeid` varchar(20) NOT NULL COMMENT 'loại xổ số Loại(ssc,pk10)',
  `expect` varchar(50) NOT NULL COMMENT 'đặt cượcmã kỳ',
  `playid` varchar(50) NOT NULL COMMENT 'ID loại cược',
  `playtitle` varchar(100) NOT NULL COMMENT 'tên loại cược',
  `tzcode` text NOT NULL COMMENT 'số đã cược',
  `amount` decimal(18,2) NOT NULL DEFAULT '0.00' COMMENT 'Số tiền',
  `hemaipic` decimal(18,2) NOT NULL DEFAULT '0.00' COMMENT 'Số tiền',
  `buytotal` int(11) NOT NULL DEFAULT '0' COMMENT 'phần',
  `buyhave` int(11) NOT NULL DEFAULT '0' COMMENT 'phần',
  `buyed` int(11) NOT NULL DEFAULT '0' COMMENT 'tham giaphần',
  `baodi` int(11) NOT NULL DEFAULT '0' COMMENT 'đảm bảophần trăm(0-100)',
  `baodi_amount` decimal(18,2) NOT NULL DEFAULT '0.00' COMMENT 'đảm bảoSố tiền',
  `commission` decimal(5,2) NOT NULL DEFAULT '0.00' COMMENT 'tỷ lệ(0-10)',
  `public_type` tinyint(4) NOT NULL DEFAULT '1' COMMENT 'Loại: 1=,2=,3=',
  `views` int(11) NOT NULL DEFAULT '0' COMMENT '',
  `content` varchar(500) DEFAULT '' COMMENT 'cược nhóm',
  `status` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'Trạng thái: 0=đang diễn ra, 1=, 2=mở thưởng, 3=đã hủy',
  `isdraw` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'mở thưởng Trạng thái: 0=mở thưởng, 1=trúng thưởng, -1=trúng thưởng',
  `opencode` varchar(100) DEFAULT '' COMMENT 'số mở thưởng',
  `winamount` decimal(18,2) NOT NULL DEFAULT '0.00' COMMENT 'trúng thưởngSố tiền',
  `endtime` bigint(20) NOT NULL DEFAULT '0' COMMENT 'thời gian()',
  `createtime` bigint(20) NOT NULL DEFAULT '0' COMMENT 'Thời gian tạo()',
  `drawtime` bigint(20) DEFAULT '0' COMMENT 'thời gian mở thưởng()',
  `ip` varchar(45) DEFAULT NULL COMMENT 'I P',
  PRIMARY KEY (`id`),
  KEY `idx_uid` (`uid`),
  KEY `idx_cpname_expect` (`cpname`,`expect`),
  KEY `idx_status` (`status`),
  KEY `idx_createtime` (`createtime`),
  KEY `idx_endtime` (`endtime`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COMMENT='合买方案Bảng';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `caipiao_hemai`
--

LOCK TABLES `caipiao_hemai` WRITE;
/*!40000 ALTER TABLE `caipiao_hemai` DISABLE KEYS */;
INSERT INTO `caipiao_hemai` VALUES (1,1,'testuser1','jsk3','江苏快3','k3','20261124001','5star','五星直选','1,2,3,4,5|6,7,8,9,0|2,3,4,5,6|7,8,9,0,1|3,4,5,6,7',5000.00,10.00,500,92,408,30,1500.00,0.00,1,1,'稳定盈利方案，近期中奖率高，欢迎跟单！',0,0,'',0.00,1763994092000,1763983292,NULL,'127.0.0.1'),(2,2,'testuser2','jsk3','江苏快3','k3','20261124002','gyh','冠亚和','大,单,11,12,13',2000.00,20.00,100,9,91,50,1000.00,0.00,1,0,'即将截止，抓住最后机会！',0,0,'',0.00,1763987372000,1763979692,NULL,'127.0.0.1'),(3,1,'testuser1','jsk3','江苏快3','k3','20261124003','3star','三星直选','1,2,3|4,5,6|7,8,9',300.00,5.00,60,35,25,20,60.00,0.00,1,11,'小额稳定方案',0,0,'',0.00,1763997692000,1763985092,NULL,'127.0.0.1'),(4,3,'testuser3','jsk3','江苏快3','k3','20261124004','wx','五行','金,木,水',1000.00,10.00,100,0,100,0,0.00,0.00,1,0,'满员方案',1,0,'',0.00,1763986292000,1763972492,NULL,'127.0.0.1'),(5,1,'testuser1','jsk3','江苏快3','k3','20261123005','5star','五星直选','1,2,3,4,5',1000.00,10.00,100,0,100,30,300.00,0.00,1,0,'昨日中奖方案',2,1,'1,2,3,4,5',8800.00,1763907692000,1763900492,1763911292,'127.0.0.1'),(6,9872,'timibbs','f1k3','1分快3','k3','202611261309','k3hzzx','快三合买','k3hzzx:和值9:2|k3hzzx:和值17:2',4.00,0.40,10,9,1,10,0.40,0.00,1,2,'',0,0,'',0.00,1764164340000,1764164923,0,'98.98.91.137'),(7,9872,'timibbs','f1k3','1分快3','k3','202611270008','k3sthdx','快三合买','k3sthdx:222:10|k3sthdx:555:10',20.00,2.00,10,9,1,10,2.00,0.00,1,2,'',0,0,'',0.00,1764172680000,1764173262,0,'98.98.91.137');
/*!40000 ALTER TABLE `caipiao_hemai` ENABLE KEYS */;
UNLOCK TABLES;
