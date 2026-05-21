--
-- Table structure for table `caipiao_game_category`
--

DROP TABLE IF EXISTS `caipiao_game_category`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `caipiao_game_category` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL COMMENT 'Tên danh mục',
  `code` varchar(50) NOT NULL COMMENT 'Mã danh mục',
  `icon` varchar(255) DEFAULT NULL,
  `icon_img` varchar(255) DEFAULT NULL,
  `sort` int(11) DEFAULT '0',
  `status` tinyint(1) DEFAULT '1',
  `path` varchar(100) DEFAULT NULL,
  `created_at` int(11) DEFAULT NULL,
  `updated_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COMMENT='Danh mục game trang chủ';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `caipiao_game_category`
--

LOCK TABLES `caipiao_game_category` WRITE;
/*!40000 ALTER TABLE `caipiao_game_category` DISABLE KEYS */;
INSERT INTO `caipiao_game_category` VALUES (1,'热门','hot',NULL,'/assets/img/icon_dtfl_rm_1.avif',0,1,'',1766070038,1766070038),(2,'电子','slot',NULL,'/assets/img/icon_dtfl_dz_1.avif',1,1,'/game/slot',1766070038,1766070038),(3,'真人','live',NULL,'/assets/img/icon_dtfl_zr_1.avif',2,1,'/game/live',1766070038,1766070038),(4,'捕鱼','fish',NULL,'/assets/img/icon_dtfl_by_1.avif',3,1,'/game/fish',1766070038,1766070038),(5,'棋牌','chess',NULL,'/assets/img/icon_dtfl_qp_1.avif',4,1,'/game/chess',1766070038,1766070038),(6,'彩票','lottery',NULL,'/assets/img/icon_dtfl_cp_1.avif',5,1,'/game/lottery',1766070038,1766070038),(7,'体育','sport',NULL,'/assets/img/icon_dtfl_ty_1.avif',6,1,'/game/sport',1766070038,1766070038),(8,'区块链','blockchain',NULL,'/assets/img/icon_dtfl_qkl_1.avif',7,0,'/game/blockchain',1766070038,1766070150),(9,'电竞','esport',NULL,'/assets/img/icon_dtfl_dj_1.avif',8,1,'/game/esports',1766070038,1766070129);
/*!40000 ALTER TABLE `caipiao_game_category` ENABLE KEYS */;
UNLOCK TABLES;
