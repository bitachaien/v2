--
-- Table structure for table `caipiao_yukaijiang`
--

DROP TABLE IF EXISTS `caipiao_yukaijiang`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `caipiao_yukaijiang` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` char(30) NOT NULL COMMENT 'xổ sốmã',
  `opencode` char(180) NOT NULL COMMENT 'số mở thưởng',
  `expect` char(60) NOT NULL COMMENT 'mã kỳ',
  `stateadmin` char(20) DEFAULT NULL,
  `opentime` int(11) NOT NULL COMMENT 'thời gian mở thưởng',
  `hid` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `expect` (`expect`)
) ENGINE=InnoDB AUTO_INCREMENT=38 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='预Mở thưởng管理';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `caipiao_yukaijiang`
--

LOCK TABLES `caipiao_yukaijiang` WRITE;
/*!40000 ALTER TABLE `caipiao_yukaijiang` DISABLE KEYS */;
INSERT INTO `caipiao_yukaijiang` VALUES (1,'yfxy28','5,9,7,21','202610290141','admin',1761675660,0),(2,'yfxy28','0,4,9,13','202610290146','admin',1761675960,0),(3,'dfssc','2,7,7,1,2','20261029046','admin',2026,0),(4,'f1k3','1,1,1','202611260335','admin',2026,0),(5,'f1k3','1,1,1','202611260336','admin',2026,0),(6,'f1k3','1,1,1','202611260339','admin',2026,0),(7,'f1k3','1,1,1','202611260340','admin',2026,0),(8,'f1k3','1,1,1','202611260353','admin',2026,0),(9,'f1k3','2,2,2','202611260356','admin',2026,0),(10,'f1k3','3,3,3','202611260357','admin',2026,0),(11,'f1k3','3,3,3','202611260358','admin',2026,0),(12,'f1k3','4,4,4','202611260362','admin',2026,0),(13,'f1k3','6,6,6','202611260364','admin',2026,0),(14,'f1k3','4,4,4','202611260373','admin',2026,0),(15,'f1k3','1,1,1','202611260376','admin',2026,0),(16,'f1k3','1,1,1','202611260379','admin',2026,0),(17,'f1k3','1,1,1','202611261252','admin',1764161520,0),(18,'f1k3','3,3,3','202611261254','admin',1764161640,0),(19,'f1k3','2,2,2','202611261259','admin',1764161940,0),(20,'f1k3','2,2,2','202611261266','admin',1764162360,0),(21,'f1k3','2,2,2','202611261274','admin',1764162840,0),(22,'dfssc','5,6,0,5,4','20261127263','admin',1764207960,0),(23,'jlk3','2,2,2','20261127073','admin',1764162300,0),(24,'jlk3','2,2,2','20261127074','admin',1764162600,0),(25,'jlk3','1,3,5','20261130001','admin',1764399900,0),(26,'jlk3','1,3,4','20261130002','admin',1764400200,0),(27,'jlk3','3,1,6','20261130003','admin',1764400500,0),(28,'jlk3','1,3,6','20261130004','admin',1764400800,0),(29,'jlk3','1,2,4','20261202001','admin',1764572700,0),(30,'yfxy28','5,5,5,15','202612100926','admin',1765351560,0),(31,'yfxy28','8,8,8,24','202612100928','admin',1765351680,0),(32,'yfxy28','7,7,7,21','202612100929','admin',1765351740,0),(33,'yfxy28','9,8,7,24','202612100931','admin',1765351860,0),(34,'yfxy28','0,1,2,3','202612100933','admin',1765351980,0),(35,'yfxy28','2,2,2,6','202612100948','admin',1765352880,0),(36,'yfxy28','8,8,8','202612100949','admin',1765352940,0),(37,'yfxy28','9,9,6','202612100951','admin',1765353060,0);
/*!40000 ALTER TABLE `caipiao_yukaijiang` ENABLE KEYS */;
UNLOCK TABLES;
