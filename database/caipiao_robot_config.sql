--
-- Table structure for table `caipiao_robot_config`
--

DROP TABLE IF EXISTS `caipiao_robot_config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `caipiao_robot_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL COMMENT 'thiết lậptên',
  `lottery_codes` varchar(500) DEFAULT '' COMMENT 'loại xổ số',
  `is_enabled` tinyint(1) DEFAULT '1' COMMENT 'Kích hoạt',
  `min_bet_amount` decimal(10,2) DEFAULT '10.00' COMMENT 'tối thiểusố tiền đặt cược',
  `max_bet_amount` decimal(10,2) DEFAULT '500.00' COMMENT 'tối đasố tiền đặt cược',
  `bet_interval_min` int(11) DEFAULT '30' COMMENT 'tối thiểuđặt cược',
  `bet_interval_max` int(11) DEFAULT '120' COMMENT 'tối đađặt cược',
  `bet_count_min` int(11) DEFAULT '1' COMMENT 'đặt cược',
  `bet_count_max` int(11) DEFAULT '5' COMMENT 'đặt cược',
  `play_types` text COMMENT 'loại cược JSO N',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COMMENT='RobotCấu hìnhBảng';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `caipiao_robot_config`
--

LOCK TABLES `caipiao_robot_config` WRITE;
/*!40000 ALTER TABLE `caipiao_robot_config` DISABLE KEYS */;
INSERT INTO `caipiao_robot_config` VALUES (1,'默认配置','yfxy28',1,10.00,500.00,2,15,30,100,'[\"大\",\"小\",\"单\",\"双\",\"大单\",\"大双\",\"小单\",\"小双\"]','2026-12-10 16:07:06','2026-12-13 02:52:13');
/*!40000 ALTER TABLE `caipiao_robot_config` ENABLE KEYS */;
UNLOCK TABLES;
