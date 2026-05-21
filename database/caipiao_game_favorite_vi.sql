--
-- Table structure for table `caipiao_game_favorite`
--

DROP TABLE IF EXISTS `caipiao_game_favorite`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `caipiao_game_favorite` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL COMMENT 'ID người dùng',
  `platform` varchar(50) NOT NULL COMMENT 'mã nhà cung cấp',
  `game_id` varchar(100) NOT NULL COMMENT 'ID trò chơi',
  `game_type` varchar(50) DEFAULT NULL COMMENT 'loại trò chơi',
  `game_name` varchar(200) NOT NULL COMMENT 'tên trò chơi',
  `game_icon` varchar(500) DEFAULT NULL COMMENT 'trò chơiBiểu tượng',
  `game_cover` varchar(500) DEFAULT NULL COMMENT 'trò chơiảnh bìa',
  `created_at` int(11) unsigned NOT NULL COMMENT 'Thời gian tạo',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_game` (`user_id`,`platform`,`game_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_game_type` (`game_type`)
) ENGINE=InnoDB AUTO_INCREMENT=60 DEFAULT CHARSET=utf8mb4 COMMENT='游戏收藏Bảng';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `caipiao_game_favorite`
--

LOCK TABLES `caipiao_game_favorite` WRITE;
/*!40000 ALTER TABLE `caipiao_game_favorite` DISABLE KEYS */;
INSERT INTO `caipiao_game_favorite` VALUES (32,9880,'PG','PG_65','slot','麻将胡了','https://image-uz.ng-demo.xyz/image//game_image/square/pg/zh-hans/65.webp','https://image-uz.ng-demo.xyz/image//game_image/square/pg/zh-hans/65.webp',1765209738),(33,9880,'PG','PG_74','slot','麻将胡了2','https://image-uz.ng-demo.xyz/image//game_image/square/pg/zh-hans/74.webp','https://image-uz.ng-demo.xyz/image//game_image/square/pg/zh-hans/74.webp',1765209739),(34,9880,'WL','WL_13','slot','财神到','https://image-uz.ng-demo.xyz/image//game_image/square/wl/zh-hans/13.webp','https://image-uz.ng-demo.xyz/image//game_image/square/wl/zh-hans/13.webp',1765209741),(35,9880,'WL','WL_22','slot','连环夺宝','https://image-uz.ng-demo.xyz/image//game_image/square/wl/zh-hans/22.webp','https://image-uz.ng-demo.xyz/image//game_image/square/wl/zh-hans/22.webp',1765209742),(36,9880,'WL','WL_85','live','视讯斗牛','https://image-uz.ng-demo.xyz/image//game_image/square/wl/zh-hans/85.webp','https://image-uz.ng-demo.xyz/image//game_image/square/wl/zh-hans/85.webp',1765209900),(39,9880,'WL','WL_1','fishing','捕鱼','https://image-uz.ng-demo.xyz/image//game_image/square/wl/zh-hans/1.webp','https://image-uz.ng-demo.xyz/image//game_image/square/wl/zh-hans/1.webp',1765210596),(40,9880,'WL','WL_2','chess','斗地主','https://image-uz.ng-demo.xyz/image//game_image/square/wl/zh-hans/2.webp','https://image-uz.ng-demo.xyz/image//game_image/square/wl/zh-hans/2.webp',1765211511),(41,9880,'WL','WL_3','chess','炸金花','https://image-uz.ng-demo.xyz/image//game_image/square/wl/zh-hans/3.webp','https://image-uz.ng-demo.xyz/image//game_image/square/wl/zh-hans/3.webp',1765211512),(42,9880,'WL','WL_4','chess','trăm人牛牛','https://image-uz.ng-demo.xyz/image//game_image/square/wl/zh-hans/4.webp','https://image-uz.ng-demo.xyz/image//game_image/square/wl/zh-hans/4.webp',1765211512),(43,9880,'WL','WL_7','chess','红黑lớn战','https://image-uz.ng-demo.xyz/image//game_image/square/wl/zh-hans/7.webp','https://image-uz.ng-demo.xyz/image//game_image/square/wl/zh-hans/7.webp',1765211513),(44,9880,'WL','WL_6','chess','二人麻将','https://image-uz.ng-demo.xyz/image//game_image/square/wl/zh-hans/6.webp','https://image-uz.ng-demo.xyz/image//game_image/square/wl/zh-hans/6.webp',1765211514),(45,9880,'WL','WL_5','chess','抢庄牛牛','https://image-uz.ng-demo.xyz/image//game_image/square/wl/zh-hans/5.webp','https://image-uz.ng-demo.xyz/image//game_image/square/wl/zh-hans/5.webp',1765211515),(51,9880,'BBIN','BBIN_5058','slot','疯狂水果盘','https://image-uz.ng-demo.xyz/image//game_image/square/bbin/zh-hans/5058.webp','https://image-uz.ng-demo.xyz/image//game_image/square/bbin/zh-hans/5058.webp',1765243144),(52,9880,'AG','AG_HM2D','fishing','捕鱼 2D','https://image-uz.ng-demo.xyz/image//game_image/square/ag/zh-hans/HM2D.webp','https://image-uz.ng-demo.xyz/image//game_image/square/ag/zh-hans/HM2D.webp',1765285467),(53,9872,'V8','V8_21031','slot','寻宝鼹鼠','https://image-uz.ng-demo.xyz/image//game_image/square/v8/zh-hans/21031.webp','https://image-uz.ng-demo.xyz/image//game_image/square/v8/zh-hans/21031.webp',1765607719),(54,9872,'PG','PG_65','slot','麻将胡了','https://image-uz.ng-demo.xyz/image//game_image/square/pg/zh-hans/65.webp','https://image-uz.ng-demo.xyz/image//game_image/square/pg/zh-hans/65.webp',1765607720),(56,9872,'FG','FG_dfdc','slot','nhiều福nhiều财','https://image-uz.ng-demo.xyz/image//game_image/square/fg/zh-hans/dfdc.webp','https://image-uz.ng-demo.xyz/image//game_image/square/fg/zh-hans/dfdc.webp',1765607724),(57,9872,'AG','AG_BAC','live','trăm家乐','https://image-uz.ng-demo.xyz/image//game_image/square/ag/zh-hans/BAC.webp','https://image-uz.ng-demo.xyz/image//game_image/square/ag/zh-hans/BAC.webp',1766099463),(58,9872,'AG','AG_DT','live','龙虎','https://image-uz.ng-demo.xyz/image//game_image/square/ag/zh-hans/DT.webp','https://image-uz.ng-demo.xyz/image//game_image/square/ag/zh-hans/DT.webp',1766099463),(59,9872,'BBIN','BBIN_5908','slot','糖果派对2','https://image-uz.ng-demo.xyz/image//game_image/square/bbin/zh-hans/5908.webp','https://image-uz.ng-demo.xyz/image//game_image/square/bbin/zh-hans/5908.webp',1766115794);
/*!40000 ALTER TABLE `caipiao_game_favorite` ENABLE KEYS */;
UNLOCK TABLES;
