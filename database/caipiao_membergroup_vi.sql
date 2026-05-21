--
-- Table structure for table `caipiao_membergroup`
--

DROP TABLE IF EXISTS `caipiao_membergroup`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `caipiao_membergroup` (
  `groupid` int(11) NOT NULL AUTO_INCREMENT,
  `listorder` smallint(6) NOT NULL,
  `groupstatus` tinyint(4) NOT NULL DEFAULT '1',
  `addtime` int(11) NOT NULL,
  `groupname` char(80) NOT NULL,
  `isagent` tinyint(4) NOT NULL COMMENT 'Là đại lý',
  `isdefautreg` tinyint(4) NOT NULL COMMENT 'Mặc định đăng ký',
  `lowest` smallint(6) NOT NULL DEFAULT '2' COMMENT 'Cược tối thiểu',
  `highest` int(11) NOT NULL COMMENT 'Cược tối đa',
  `fanshui` char(255) NOT NULL COMMENT 'Hoàn trả cược (0.1 = 0.1%)',
  `fs_lottery` decimal(5,2) DEFAULT '0.00' COMMENT 'Tỷ lệ hoàn trả xổ số %',
  `fs_realperson` decimal(5,2) DEFAULT '0.00' COMMENT 'Tỷ lệ hoàn trả casino %',
  `fs_sport` decimal(5,2) DEFAULT '0.00' COMMENT 'Tỷ lệ hoàn trả thể thao %',
  `fs_esport` decimal(5,2) DEFAULT '0.00' COMMENT 'Tỷ lệ hoàn trả esports %',
  `fs_chess` decimal(5,2) DEFAULT '0.00' COMMENT 'Tỷ lệ hoàn trả game bài %',
  `fs_electron` decimal(5,2) DEFAULT '0.00' COMMENT 'Tỷ lệ hoàn trả slot %',
  `fs_fish` decimal(5,2) DEFAULT '0.00' COMMENT 'Tỷ lệ hoàn trả bắn cá %',
  `touhan` char(20) NOT NULL COMMENT 'Hạn mức đặt cược',
  `shengjiedu` char(30) NOT NULL COMMENT 'Điều kiện thăng cấp',
  `rifanyonglv` char(255) NOT NULL COMMENT 'Tỷ lệ hoa hồng ngày',
  `yuefanyonglv` char(255) NOT NULL COMMENT 'Tỷ lệ hoa hồng tháng',
  `jjje` float NOT NULL COMMENT 'Số tiền thăng cấp',
  `free_withdraw_times` int(11) NOT NULL DEFAULT '0' COMMENT 'Số lần rút miễn phí',
  `weekly_betting` decimal(14,2) NOT NULL DEFAULT '0.00' COMMENT 'Doanh thu cược tuần',
  `weekly_bonus` decimal(14,2) NOT NULL DEFAULT '0.00' COMMENT 'Thưởng tuần',
  `monthly_betting` decimal(14,2) NOT NULL DEFAULT '0.00' COMMENT 'Doanh thu cược tháng',
  `monthly_bonus` decimal(14,2) NOT NULL DEFAULT '0.00' COMMENT 'Thưởng tháng',
  `tiaoji` float NOT NULL COMMENT 'Tiền thưởng',
  `configs` longtext NOT NULL,
  `level` smallint(6) NOT NULL,
  PRIMARY KEY (`groupid`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `caipiao_membergroup`
--

LOCK TABLES `caipiao_membergroup` WRITE;
/*!40000 ALTER TABLE `caipiao_membergroup` DISABLE KEYS */;
INSERT INTO `caipiao_membergroup` VALUES (1,0,1,1456912144,'VIP1',0,1,2,10000,'100-2000|0.1;2000-10000|0.2;10000-200000|0.3',0.10,0.10,0.10,0.10,0.10,0.10,0.10,'农民啊啊啊','0','0','',0,0,0.00,0.00,0.00,0.00,0,'a:204:{s:7:\"min_156\";i:100;s:7:\"max_156\";i:1000;s:7:\"min_157\";i:0;s:7:\"max_157\";i:0;s:7:\"min_158\";i:0;s:7:\"max_158\";i:0;s:7:\"min_159\";i:0;s:7:\"max_159\";i:0;s:7:\"min_160\";i:0;s:7:\"max_160\";i:0;s:7:\"min_161\";i:0;s:7:\"max_161\";i:0;s:7:\"min_162\";i:0;s:7:\"max_162\";i:0;s:7:\"min_163\";i:0;s:7:\"max_163\";i:0;s:7:\"min_164\";i:0;s:7:\"max_164\";i:0;s:7:\"min_165\";i:0;s:7:\"max_165\";i:0;s:7:\"min_166\";i:0;s:7:\"max_166\";i:0;s:7:\"min_167\";i:0;s:7:\"max_167\";i:0;s:7:\"min_168\";i:0;s:7:\"max_168\";i:0;s:7:\"min_169\";i:0;s:7:\"max_169\";i:0;s:7:\"min_170\";i:0;s:7:\"max_170\";i:0;s:7:\"min_171\";i:0;s:7:\"max_171\";i:0;s:7:\"min_172\";i:0;s:7:\"max_172\";i:0;s:7:\"min_173\";i:0;s:7:\"max_173\";i:0;s:7:\"min_174\";i:0;s:7:\"max_174\";i:0;s:7:\"min_175\";i:0;s:7:\"max_175\";i:0;s:7:\"min_176\";i:0;s:7:\"max_176\";i:0;s:7:\"min_177\";i:0;s:7:\"max_177\";i:0;s:7:\"min_178\";i:0;s:7:\"max_178\";i:0;s:7:\"min_179\";i:0;s:7:\"max_179\";i:0;s:7:\"min_180\";i:0;s:7:\"max_180\";i:0;s:7:\"min_181\";i:0;s:7:\"max_181\";i:0;s:7:\"min_333\";i:0;s:7:\"max_333\";i:0;s:7:\"min_334\";i:0;s:7:\"max_334\";i:0;s:7:\"min_335\";i:0;s:7:\"max_335\";i:0;s:7:\"min_336\";i:0;s:7:\"max_336\";i:0;s:7:\"min_624\";i:0;s:7:\"max_624\";i:0;s:7:\"min_625\";i:0;s:7:\"max_625\";i:0;s:7:\"min_626\";i:0;s:7:\"max_626\";i:0;s:7:\"min_627\";i:0;s:7:\"max_627\";i:0;s:7:\"min_628\";i:0;s:7:\"max_628\";i:0;s:7:\"min_629\";i:0;s:7:\"max_629\";i:0;s:7:\"min_630\";i:0;s:7:\"max_630\";i:0;s:7:\"min_631\";i:0;s:7:\"max_631\";i:0;s:7:\"min_632\";i:0;s:7:\"max_632\";i:0;s:7:\"min_633\";i:0;s:7:\"max_633\";i:0;s:7:\"min_634\";i:0;s:7:\"max_634\";i:0;s:7:\"min_635\";i:0;s:7:\"max_635\";i:0;s:7:\"min_636\";i:0;s:7:\"max_636\";i:0;s:7:\"min_637\";i:0;s:7:\"max_637\";i:0;s:7:\"min_638\";i:0;s:7:\"max_638\";i:0;s:7:\"min_639\";i:0;s:7:\"max_639\";i:0;s:7:\"min_640\";i:0;s:7:\"max_640\";i:0;s:7:\"min_641\";i:0;s:7:\"max_641\";i:0;s:7:\"min_642\";i:0;s:7:\"max_642\";i:0;s:7:\"min_643\";i:0;s:7:\"max_643\";i:0;s:7:\"min_644\";i:0;s:7:\"max_644\";i:0;s:7:\"min_645\";i:0;s:7:\"max_645\";i:0;s:7:\"min_646\";i:0;s:7:\"max_646\";i:0;s:7:\"min_647\";i:0;s:7:\"max_647\";i:0;s:7:\"min_648\";i:0;s:7:\"max_648\";i:0;s:7:\"min_649\";i:0;s:7:\"max_649\";i:0;s:7:\"min_650\";i:0;s:7:\"max_650\";i:0;s:7:\"min_651\";i:0;s:7:\"max_651\";i:0;s:7:\"min_652\";i:0;s:7:\"max_652\";i:0;s:7:\"min_653\";i:0;s:7:\"max_653\";i:0;s:7:\"min_654\";i:0;s:7:\"max_654\";i:0;s:7:\"min_655\";i:0;s:7:\"max_655\";i:0;s:7:\"min_656\";i:0;s:7:\"max_656\";i:0;s:7:\"min_657\";i:0;s:7:\"max_657\";i:0;s:7:\"min_658\";i:0;s:7:\"max_658\";i:0;s:7:\"min_659\";i:0;s:7:\"max_659\";i:0;s:7:\"min_660\";i:0;s:7:\"max_660\";i:0;s:7:\"min_661\";i:0;s:7:\"max_661\";i:0;s:7:\"min_662\";i:0;s:7:\"max_662\";i:0;s:7:\"min_663\";i:0;s:7:\"max_663\";i:0;s:7:\"min_664\";i:0;s:7:\"max_664\";i:0;s:7:\"min_665\";i:0;s:7:\"max_665\";i:0;s:7:\"min_666\";i:0;s:7:\"max_666\";i:0;s:7:\"min_667\";i:0;s:7:\"max_667\";i:0;s:7:\"min_668\";i:0;s:7:\"max_668\";i:0;s:7:\"min_669\";i:0;s:7:\"max_669\";i:0;s:7:\"min_670\";i:0;s:7:\"max_670\";i:0;s:7:\"min_671\";i:0;s:7:\"max_671\";i:0;s:7:\"min_672\";i:0;s:7:\"max_672\";i:0;s:7:\"min_673\";i:0;s:7:\"max_673\";i:0;s:7:\"min_674\";i:0;s:7:\"max_674\";i:0;s:7:\"min_675\";i:0;s:7:\"max_675\";i:0;s:7:\"min_676\";i:0;s:7:\"max_676\";i:0;s:7:\"min_677\";i:0;s:7:\"max_677\";i:0;s:7:\"min_735\";i:0;s:7:\"max_735\";i:0;s:7:\"min_736\";i:0;s:7:\"max_736\";i:0;s:7:\"min_737\";i:0;s:7:\"max_737\";i:0;s:7:\"min_738\";i:0;s:7:\"max_738\";i:0;s:7:\"min_739\";i:0;s:7:\"max_739\";i:0;s:7:\"min_740\";i:0;s:7:\"max_740\";i:0;s:7:\"min_741\";i:0;s:7:\"max_741\";i:0;s:7:\"min_742\";i:0;s:7:\"max_742\";i:0;s:7:\"min_743\";i:0;s:7:\"max_743\";i:0;s:7:\"min_744\";i:0;s:7:\"max_744\";i:0;s:7:\"min_745\";i:0;s:7:\"max_745\";i:0;s:7:\"min_746\";i:0;s:7:\"max_746\";i:0;s:7:\"min_747\";i:0;s:7:\"max_747\";i:0;s:7:\"min_748\";i:0;s:7:\"max_748\";i:0;s:7:\"min_785\";i:0;s:7:\"max_785\";i:0;s:7:\"min_786\";i:0;s:7:\"max_786\";i:0;s:7:\"min_787\";i:0;s:7:\"max_787\";i:0;s:7:\"min_788\";i:0;s:7:\"max_788\";i:0;}',0),(2,1,1,1492393617,'VIP2',0,0,2,0,'100-2000|0.2;2000-10000|0.3;10000-200000|0.4',0.20,0.20,0.20,0.20,0.20,0.20,0.20,'地主','2000','0','',8,0,500.00,3.00,1000.00,18.00,1,'',0),(3,2,1,1492393701,'VIP3',0,0,2,0,'100-2000|0.3;2000-10000|0.4;10000-200000|0.5',0.30,0.30,0.30,0.30,0.30,0.30,0.30,'知县','10000','0','',18,0,2000.00,5.00,5000.00,28.00,6,'',0),(4,3,1,1456974912,'VIP4',0,0,2,0,'100-2000|0.4;2000-10000|0.5;10000-200000|0.6',0.40,0.40,0.40,0.40,0.40,0.40,0.40,'通判','50000','0','',38,0,5000.00,10.00,100000.00,58.00,16,'',0),(5,4,1,1492395106,'VIP5',0,0,2,0,'100-2000|0.5;2000-10000|0.5;10000-200000|0.5',0.50,0.50,0.50,0.50,0.50,0.50,0.50,'知府','500000','0','',68,0,11000.00,28.00,500000.00,88.00,74,'',0),(6,5,1,1492395119,'VIP6',0,0,2,0,'100-2000|0.6;2000-10000|0.7;10000-200000|0.8',0.60,0.60,0.60,0.60,0.60,0.60,0.60,'总督','2000000','0','',108,5,100000.00,68.00,1000000.00,188.00,392,'',0),(7,6,1,1469113035,'VIP7',0,0,2,0,'100-2000|0.6;2000-10000|0.7;10000-200000|0.8',0.70,0.70,0.70,0.70,0.70,0.70,0.70,'巡抚','5000000','0','',888,5,500000.00,88.00,5000000.00,588.00,2080,'',0),(8,7,1,1492394070,'VIP8',0,0,2,0,'100-2000|0.7;2000-10000|0.8;10000-200000|0.9',0.80,0.80,0.80,0.80,0.80,0.80,0.80,'丞相','20000000','0','',1888,5,1000000.00,188.00,10000000.00,888.00,8968,'',0),(9,8,1,1469113237,'VIP9',0,0,2,0,'100-2000|1;2000-10000|1.1;10000-200000|1.2',1.00,1.00,1.00,1.00,1.00,1.00,1.00,'帝王','50000000','0','',3888,5,5000000.00,588.00,30000000.00,1888.00,47856,'',0),(19,0,1,1764877498,'VIP10',0,0,2,0,'100-2000|0.1;2000-10000|0.5;10000-200000|1;200000-1000000|1.5',1.50,1.50,1.50,1.50,1.50,1.50,1.50,'阎罗王','100000000','','',5888,10,10000000.00,888.00,50000000.00,5888.00,0,'',0),(20,0,1,1764877524,'VIP11',0,0,2,0,'100-2000|0.1;2000-10000|0.5;10000-200000|1;200000-1000000|1.5',0.00,0.00,0.00,0.00,0.00,0.00,0.00,'阎罗王','2000000000','','',8888,10,50000000.00,1288.00,80000000.00,8888.00,0,'',0),(21,9,1,1764878109,'VIP12',0,0,2,0,'',0.00,0.00,0.00,0.00,0.00,0.00,0.00,'','3000000000','','',12888,10,80000000.00,5888.00,100000000.00,12888.00,0,'',0),(22,10,1,1764878115,'VIP13',0,0,2,0,'',0.00,0.00,0.00,0.00,0.00,0.00,0.00,'','5000000000','','',18888,10,100000000.00,8888.00,200000000.00,16888.00,0,'',0);
/*!40000 ALTER TABLE `caipiao_membergroup` ENABLE KEYS */;
UNLOCK TABLES;
