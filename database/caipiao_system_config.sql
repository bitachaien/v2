--
-- Table structure for table `caipiao_system_config`
--

DROP TABLE IF EXISTS `caipiao_system_config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `caipiao_system_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `config_key` varchar(50) NOT NULL,
  `config_value` varchar(255) DEFAULT '',
  `remark` varchar(100) DEFAULT '',
  `updated_at` int(11) DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `config_key` (`config_key`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `caipiao_system_config`
--

LOCK TABLES `caipiao_system_config` WRITE;
/*!40000 ALTER TABLE `caipiao_system_config` DISABLE KEYS */;
INSERT INTO `caipiao_system_config` VALUES (1,'current_api_provider','NG','当前聚合平台: NG 或 WG',1765223499),(2,'chat_robot_name','金海岸','聊天室机器人名称',1765588076),(3,'chat_robot_avatar','http://43.249.25.116/static/upload/image/jinhaian.png','聊天室机器人Ảnh đại diện',1765588076),(4,'chat_bill_enabled','1','是否Kích hoạt账单推送(1Kích hoạt/0Vô hiệu hóa)',1765588076),(5,'chat_result_enabled','1','是否Kích hoạt开奖推送(1Kích hoạt/0Vô hiệu hóa)',1765588076),(6,'chat_sealed_notice_enabled','1','是否Kích hoạt封盘提示(1Kích hoạt/0Vô hiệu hóa)',1765588076),(7,'chat_draw_notice_enabled','1','是否Kích hoạt开奖前提示(1Kích hoạt/0Vô hiệu hóa)',1765588076),(8,'chat_msg_pre_sealed','★---距離封盤時間還有{seconds}秒---★','封盘前倒计时提示({seconds}会被替换为秒数)',1765589849),(9,'chat_msg_sealed_line','封盤線〖{robot_name}〗莊顯示為準','封盘线提示({robot_name}会被替换为机器人名)',1765589849),(10,'chat_msg_no_talk','★★★★ 全体禁言 ★★★★','禁言提示',1765589849),(11,'chat_msg_draw_coming','-----------{lottery_name} 马上开奖啦!-----------','开奖前提示({lottery_name}会被替换为彩种名)',1765589849),(12,'rebate_enabled','1','反水总开关(1Kích hoạt/0Vô hiệu hóa)',1765597416),(13,'rebate_cycle','daily','结算周期(daily每日/realtime实时)',1765597416),(14,'rebate_min_amount','1','最低领取Số tiền',1765597416),(15,'rebate_expire_days','7','领取有效期(天)',1765597416),(16,'rebate_turnover_times','1','流水倍数要求',1765597416);
/*!40000 ALTER TABLE `caipiao_system_config` ENABLE KEYS */;
UNLOCK TABLES;
