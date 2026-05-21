--
-- Table structure for table `caipiao_payset`
--

DROP TABLE IF EXISTS `caipiao_payset`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `caipiao_payset` (
  `id` smallint(6) NOT NULL AUTO_INCREMENT,
  `paytype` char(20) NOT NULL,
  `paytypetitle` varchar(30) NOT NULL,
  `ftitle` varchar(160) NOT NULL COMMENT 'Tiêu đề',
  `minmoney` decimal(10,2) NOT NULL DEFAULT '50.00' COMMENT 'Số tiền tối thiểu',
  `maxmoney` decimal(10,2) NOT NULL DEFAULT '50000.00' COMMENT 'Số tiền tối đa',
  `remark` text NOT NULL COMMENT 'Mô tả',
  `configs` text NOT NULL COMMENT 'Cấu hình',
  `isonline` tinyint(4) NOT NULL DEFAULT '-1' COMMENT 'Trực tuyến: 1-có, -1-không',
  `listorder` smallint(6) NOT NULL COMMENT 'Thứ tự sắp xếp',
  `state` tinyint(4) NOT NULL DEFAULT '1' COMMENT 'Trạng thái: 1-bật, -1-tắt',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=42 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `caipiao_payset`
--

LOCK TABLES `caipiao_payset` WRITE;
/*!40000 ALTER TABLE `caipiao_payset` DISABLE KEYS */;
INSERT INTO `caipiao_payset` VALUES (1,'alipay','Alipay扫码Nạp tiền','扫码自动到账',1.00,999999.00,'<span style=\"color:#F46E00;font-family:\'Microsoft YaHei\', SimSun, Arial;line-height:24px;background-color:#FFFDEB;\"> \r\n<div>\r\n	Số điện thoạiNgười dùngNạp tiền流程：<br>\r\n1：选择输入tốt自己要Nạp tiền的Số tiền,输入付款AlipayTài khoản&nbsp;点击下一步<br>\r\n2：跳转出二维码，然后长按二维码保存hoặc截屏起来<br>\r\n3：进入自己Alipay的扫一扫，右上hào点击下相册，选择扫描刚刚保存的二维码<br><p>\r\n4：Số điện thoại点击下立即支付，支付Thành công后刷mới下网页立即到账哦。</p></div>\r\n</span><span style=\"color:#F46E00;font-family:\'Microsoft YaHei\', SimSun, Arial;line-height:24px;background-color:#FFFDEB;\"></span>','a:12:{s:10:\"merchantid\";s:0:\"\";s:12:\"merchantkey1\";s:0:\"\";s:12:\"merchantkey2\";s:0:\"\";s:11:\"redirecturl\";s:0:\"\";s:11:\"hrefbackurl\";s:0:\"\";s:13:\"returnbackurl\";s:0:\"\";s:8:\"bankname\";s:0:\"\";s:8:\"bankcode\";s:0:\"\";s:5:\"isewm\";s:1:\"1\";s:6:\"ewmurl\";s:48:\"/app/admin/upload/img/20261202/692df2b4a7d2.webp\";s:7:\"account\";s:3:\"111\";s:11:\"accountname\";s:3:\"111\";}',-1,0,1),(6,'weixin','WeChat扫码Nạp tiền','扫码自动到账',50.00,999999.00,'<span style=\"color:#F46E00;font-family:\" background-color:#fffdeb;\"=\"\">Số điện thoạiNgười dùngNạp tiền流程：</span><br />\r\n<span style=\"color:#F46E00;font-family:\" background-color:#fffdeb;\"=\"\">1：选择输入tốt自己要Nạp tiền的Số tiền,输入要Nạp tiền的Tài khoản&nbsp;点击下一步</span><br />\r\n<span style=\"color:#F46E00;font-family:\" background-color:#fffdeb;\"=\"\">2：跳转出二维码，然后长按二维码保存hoặc截屏起来</span><br />\r\n<span style=\"color:#F46E00;font-family:\" background-color:#fffdeb;\"=\"\">3：进入自己WeChathoặcAlipay扫一扫，右上hào点击下相册，选择扫描刚刚保存的二维码</span><br />\r\n<span style=\"color:#F46E00;font-family:\" background-color:#fffdeb;\"=\"\">4：Số điện thoại点击下立即支付，支付Thành công后刷mới下网页立即到账哦。</span>','a:4:{s:8:\"bankname\";s:0:\"\";s:8:\"bankcode\";s:0:\"\";s:5:\"isewm\";s:1:\"1\";s:6:\"ewmurl\";s:48:\"/app/admin/upload/img/20261202/692df2b4a7d2.webp\";}',-1,0,1),(26,'linepay','银行Chuyển khoản','测试',300.00,999999.00,'银行卡','a:4:{s:8:\"bankname\";s:12:\"Ngân hàng Merchants\";s:10:\"bankbranch\";s:12:\"深圳xu行\";s:8:\"bankcode\";s:17:\"65245488125555568\";s:11:\"accountname\";s:6:\"鱼崽\";}',-1,0,1),(32,'bjalipay','Alipay','Alipay自动到账',100.00,5000.00,'<span style=\"color:#F46E00;font-family:\" line-height:24px;background-color:#fffdeb;\"=\"\">1、扫一扫弹出的二维码进行Nạp tiền。</span><br />\r\n<span style=\"color:#F46E00;font-family:\" line-height:24px;background-color:#fffdeb;\"=\"\">2、có thể使用其他Số điện thoại扫二维码进行Nạp tiền，也có thể将二维码保存到相册再使用WeChat识别相册中的二维码进行Nạp tiền，该二维码仅当次có效，每次Nạp tiền前务必重mới保存最mới的二维码。</span>','a:6:{s:10:\"merchantid\";s:0:\"\";s:12:\"merchantkey1\";s:0:\"\";s:12:\"merchantkey2\";s:0:\"\";s:11:\"redirecturl\";s:22:\"http://pay.dfyl668.com\";s:11:\"hrefbackurl\";s:22:\"http://pay.dfyl668.com\";s:13:\"returnbackurl\";s:22:\"http://pay.dfyl668.com\";}',1,32,0),(39,'bjwy','网银','trăm聚网银',100.00,50000.00,'','a:6:{s:10:\"merchantid\";s:0:\"\";s:12:\"merchantkey1\";s:0:\"\";s:12:\"merchantkey2\";s:0:\"\";s:11:\"redirecturl\";s:22:\"http://pay.dfyl668.com\";s:11:\"hrefbackurl\";s:22:\"http://pay.dfyl668.com\";s:13:\"returnbackurl\";s:22:\"http://pay.dfyl668.com\";}',1,39,0),(41,'USDT','TRC20','6666',50.00,999999.00,'6666','a:2:{s:5:\"trc20\";s:34:\"TJq8fLDcUedgB7kLrKhYNSdTJ9fyBRBpJ8\";s:5:\"erc20\";s:42:\"0x4838B106FCe9647Bdf1E7877BF73cE8B0BAD5f97\";}',-1,0,1);
/*!40000 ALTER TABLE `caipiao_payset` ENABLE KEYS */;
UNLOCK TABLES;
