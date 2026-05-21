--
-- Table structure for table `system_double_side_config`
--

DROP TABLE IF EXISTS `system_double_side_config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `system_double_side_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `typeid` varchar(20) NOT NULL COMMENT 'Loại xổ số (ssc/k3/pk10/x5/lhc)',
  `playid` varchar(50) NOT NULL COMMENT 'ID cách chơi',
  `play_name` varchar(50) NOT NULL COMMENT 'Tên cách chơi',
  `rate` decimal(10,2) DEFAULT '1.98' COMMENT 'Tỷ lệ trả thưởng',
  `status` tinyint(1) DEFAULT '1' COMMENT '1-kích hoạt, 0-vô hiệu hóa',
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_play` (`typeid`,`playid`)
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COMMENT='Cấu hình cách chơi hai mặt';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_double_side_config`
--

LOCK TABLES `system_double_side_config` WRITE;
/*!40000 ALTER TABLE `system_double_side_config` DISABLE KEYS */;
INSERT INTO `system_double_side_config` VALUES (1,'ssc','lmp_d1q_da','万位大',1.98,1),(2,'ssc','lmp_d1q_xiao','万位小',1.98,1),(3,'ssc','lmp_d1q_dan','万位单',1.98,1),(4,'ssc','lmp_d1q_shuang','万位双',1.98,1),(5,'ssc','lmp_zongh_da','总和大',1.98,1),(6,'ssc','lmp_zongh_xiao','总和小',1.98,1),(7,'ssc','lmp_lh_long','龙',1.98,1),(8,'ssc','lmp_lh_hu','虎',1.98,1),(9,'ssc','lmp_lh_he','和',8.00,1),(10,'k3','sum_big','和值大',1.98,1),(11,'k3','sum_small','和值小',1.98,1),(12,'k3','sum_odd','和值单',1.98,1),(13,'k3','sum_even','和值双',1.98,1),(14,'k3','big_odd','大单',3.96,1),(15,'k3','big_even','大双',3.96,1),(16,'k3','small_odd','小单',3.96,1),(17,'k3','small_even','小双',3.96,1),(18,'pk10','lmp_d1m_da','冠军大',1.98,1),(19,'pk10','lmp_d1m_xiao','冠军小',1.98,1),(20,'pk10','gyh_da','冠亚和大',1.98,1),(21,'pk10','gyh_xiao','冠亚和小',1.98,1),(22,'x5','sum_big','总和大',1.98,1),(23,'x5','sum_small','总和小',1.98,1),(24,'x5','dragon','龙',1.98,1),(25,'x5','tiger','虎',1.98,1);
/*!40000 ALTER TABLE `system_double_side_config` ENABLE KEYS */;
UNLOCK TABLES;
