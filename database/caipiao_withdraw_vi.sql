--
-- Table structure for table `caipiao_withdraw`
--

DROP TABLE IF EXISTS `caipiao_withdraw`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `caipiao_withdraw` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL,
  `username` char(30) NOT NULL,
  `trano` char(60) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `actualamount` decimal(12,2) NOT NULL COMMENT 'Số tiền',
  `oldaccountmoney` decimal(12,2) NOT NULL COMMENT 'rút tiềnSố tiền',
  `newaccountmoney` decimal(12,2) NOT NULL COMMENT 'rút tiềnSố tiền',
  `fee` decimal(12,2) NOT NULL COMMENT 'phí giao dịch',
  `accountname` varchar(30) NOT NULL COMMENT 'họ tên thật ngân hàng',
  `bankname` varchar(30) NOT NULL COMMENT 'ngân hàngtên',
  `bankbranch` varchar(40) NOT NULL COMMENT '',
  `paytype` varchar(30) DEFAULT 'bank' COMMENT 'rút tiềnbank=thẻ ngân hàng, alipay=, wechat=, usdt=USD T',
  `paytypename` varchar(50) DEFAULT '' COMMENT 'rút tiềntên',
  `banknumber` char(30) NOT NULL COMMENT 'ngân hàngtài khoản',
  `remark` varchar(155) NOT NULL,
  `oddtime` int(11) NOT NULL,
  `state` tinyint(4) NOT NULL COMMENT '0chờ duyệt 1đã duyệt -1hoàn trảhủy',
  `stateadmin` char(30) NOT NULL,
  `updatetime` int(11) DEFAULT '0' COMMENT 'Thời gian cập nhật',
  `qr_code` varchar(500) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`),
  KEY `username` (`username`),
  KEY `trano` (`trano`),
  KEY `state` (`state`),
  KEY `oddtime` (`oddtime`)
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `caipiao_withdraw`
--

LOCK TABLES `caipiao_withdraw` WRITE;
/*!40000 ALTER TABLE `caipiao_withdraw` DISABLE KEYS */;
INSERT INTO `caipiao_withdraw` VALUES (1,9872,'timibbs','WTH20261124043941001',500.00,495.00,0.00,0.00,5.00,'Zhang San','Ngân hàng Công Thương','Bắc Kinhxu行','bank','银行卡Rút tiền','6222021234567890','Rút tiền申vui lòng',1763929781,2,'',1764253282,NULL),(2,9872,'timibbs','WTH20261124043941002',1000.00,990.00,0.00,0.00,10.00,'Zhang San','Ngân hàng Xây dựng','Thượng Hảixu行','bank','银行卡Rút tiền','6217001234567890','Rút tiềnThành công',1763923181,1,'admin',0,NULL),(3,9872,'timibbs','WTH20261124043941003',2000.00,1980.00,0.00,0.00,20.00,'Zhang San','Ngân hàng Nông nghiệp','广州xu行','bank','银行卡Rút tiền','6228481234567890','银行卡信息错误',1763919581,-1,'admin',0,NULL),(4,9872,'timibbs','WTH20261124043941004',800.00,792.00,0.00,0.00,8.00,'Zhang San','Alipay','','alipay','AlipayRút tiền','138****8888','Rút tiền到Alipay',1763915981,1,'admin',0,NULL),(5,9872,'timibbs','WTH20261124043941005',1500.00,1485.00,0.00,0.00,15.00,'Zhang San','USDT-TRC20','','usdt','USDTRút tiền','TXnB...8Kp2','USDTRút tiền',1763928581,1,'',1764253212,NULL),(6,9872,'timibbs','WTH20261124043941006',600.00,594.00,0.00,0.00,6.00,'Zhang San','WeChat','','wechat','WeChatRút tiền','wx_123456','Rút tiền到WeChat',1763926781,1,'admin',0,NULL),(7,9872,'timibbs','202611300629320098724872',1000.00,1000.00,300012.22,299012.22,0.00,'杀杀杀','中国Ngân hàng Công Thương','1111','bank','银行卡','1111111111','',1764455372,1,'',1764455391,NULL),(8,9872,'timibbs','202612060556380098726697',10000.00,10000.00,251855.40,241855.40,0.00,'123','USDT-TRC20','','usdt','USDT','TGHKYiZuFqBKqcpDgD5U7ZfSDBkaXM','',1764971798,1,'',1764971821,NULL),(9,9872,'timibbs','202612061158460098725911',1000.00,1000.00,241855.40,240855.40,0.00,'USDTRút tiền','USDT-TRC20','','usdt','USDT','THfrD6LTsikaGShFCc5sv4YqoR84Aj','Người dùng自己取消',1764993526,3,'',1764993918,NULL),(10,9872,'timibbs','202612061222540098726730',1000.00,1000.00,241855.40,240855.40,0.00,'USDTRút tiền','USDT-TRC20','','usdt','USDT','THfrD6LTsikaGShFCc5sv4YqoR84Aj','',1764994974,2,'',1764995475,NULL),(11,9872,'timibbs','202612061338230098723048',1000.00,1000.00,240855.40,239855.40,0.00,'杀杀杀','WeChat','','wechat','WeChat','wechat_qr','',1764999503,1,'',1764999512,NULL),(12,9872,'timibbs','202612061340320098725253',100.00,100.00,239855.40,239755.40,0.00,'杀杀杀','WeChat','','wechat','WeChat','wechat_qr','',1764999632,0,'',1764999632,NULL),(13,9872,'timibbs','202612061342130098722316',100.00,100.00,239755.40,239655.40,0.00,'杀杀杀','WeChat','','wechat','WeChat','wechat_qr','',1764999733,0,'',1764999733,NULL),(14,9872,'timibbs','202612061344240098721427',100.00,100.00,239655.40,239555.40,0.00,'杀杀杀','WeChat','','wechat','WeChat','wechat_qr','',1764999864,0,'',1764999864,NULL),(15,9872,'timibbs','202612061357480098727182',100.00,100.00,239555.40,239455.40,0.00,'杀杀杀','WeChat','','wechat','WeChat','wechat_qr','',1765000668,0,'',1765000668,''),(16,9872,'timibbs','202612061440530098729481',100.00,100.00,239455.40,239355.40,0.00,'杀杀杀','WeChat','','wechat','WeChat','wechat_qr','',1765003253,2,'',1765003266,'http://127.0.0.1:8788/uploads/qrcode/20261206/6933cfdfa31a2_9872.webp'),(17,9872,'timibbs','202612061441230098728694',100.00,100.00,239455.40,239355.40,0.00,'杀杀杀','WeChat','','wechat','WeChat','wechat_qr','',1765003283,2,'',1765003288,'/app/admin/upload/img/20261206/6933d00b7445.webp'),(18,9874,'123456','202612061443440098742967',100.00,100.00,1551485.48,1551385.48,0.00,'洋洋','Alipay','','alipay','Alipay','9999','',1765003424,1,'',1765003443,'http://127.0.0.1:8788/uploads/qrcode/20261206/6933d08b7d450_9874.webp'),(19,9874,'123456','202612061444200098746602',100.00,100.00,1551385.48,1551285.48,0.00,'洋洋','Alipay','','alipay','Alipay','9999','',1765003461,1,'',1765003467,'/app/admin/upload/img/20261206/6933d0b96709.webp'),(20,19001,'','W1765232583001',3000.00,0.00,0.00,0.00,0.00,'','','','bank','','','',1765189383,1,'',0,NULL),(21,19002,'','W1765232583002',5000.00,0.00,0.00,0.00,0.00,'','','','bank','','','',1765146183,1,'',0,NULL),(22,19003,'','W1765232583003',1000.00,0.00,0.00,0.00,0.00,'','','','bank','','','',1765059783,1,'',0,NULL),(23,9872,'timibbs','202612191008050098725471',100.00,100.00,249523.20,249423.20,0.00,'USDTRút tiền','USDT-TRC20','','usdt','USDT','TH4QAUdpQaLq323JmX6AY8A6BQbHF2','',1766110085,0,'',1766110085,'');
/*!40000 ALTER TABLE `caipiao_withdraw` ENABLE KEYS */;
UNLOCK TABLES;
