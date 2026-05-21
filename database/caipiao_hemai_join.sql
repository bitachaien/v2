--
-- Table structure for table `caipiao_hemai_join`
--

DROP TABLE IF EXISTS `caipiao_hemai_join`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `caipiao_hemai_join` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT 'bản ghiID',
  `hemai_id` bigint(20) NOT NULL COMMENT 'cược nhómI D',
  `uid` bigint(20) NOT NULL COMMENT 'Người dùng I D',
  `username` varchar(60) NOT NULL COMMENT 'Người dùng',
  `num` int(11) NOT NULL DEFAULT '0' COMMENT 'tham giaphần',
  `amount` decimal(18,2) NOT NULL DEFAULT '0.00' COMMENT 'tham giaSố tiền',
  `winamount` decimal(18,2) NOT NULL DEFAULT '0.00' COMMENT 'trúng thưởngSố tiền',
  `status` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'Trạng thái: 0=tham gia, 1=đã hoàn thành, 2=',
  `jointime` bigint(20) NOT NULL DEFAULT '0' COMMENT 'tham giathời gian()',
  `ip` varchar(45) DEFAULT NULL COMMENT 'tham giaIP',
  PRIMARY KEY (`id`),
  KEY `idx_hemai_id` (`hemai_id`),
  KEY `idx_uid` (`uid`),
  KEY `idx_jointime` (`jointime`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COMMENT='合买参与Bản ghiBảng';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `caipiao_hemai_join`
--

LOCK TABLES `caipiao_hemai_join` WRITE;
/*!40000 ALTER TABLE `caipiao_hemai_join` DISABLE KEYS */;
INSERT INTO `caipiao_hemai_join` VALUES (1,1,4,'user001',50,500.00,0.00,0,1763983892000,'192.168.1.100'),(2,1,5,'user002',100,1000.00,0.00,0,1763984192000,'192.168.1.101'),(3,1,6,'user003',80,800.00,0.00,0,1763984492000,'192.168.1.102'),(4,1,7,'user004',70,700.00,0.00,0,1763984792000,'192.168.1.103'),(5,1,8,'user005',100,1000.00,0.00,0,1763985092000,'192.168.1.104'),(6,2,4,'user001',30,600.00,0.00,0,1763980892000,'192.168.1.100'),(7,2,5,'user002',30,600.00,0.00,0,1763981492000,'192.168.1.101'),(8,2,6,'user003',30,600.00,0.00,0,1763982092000,'192.168.1.102'),(9,5,4,'user001',20,200.00,1760.00,1,1763900492000,'192.168.1.100'),(10,5,5,'user002',30,300.00,2640.00,1,1763900492000,'192.168.1.101'),(11,5,6,'user003',20,200.00,1760.00,1,1763900492000,'192.168.1.102'),(14,3,9872,'timibbs',1,5.00,0.00,0,1763987014000,'122.10.198.206'),(15,2,9872,'timibbs',1,20.00,0.00,0,1763987070000,'122.10.198.206'),(16,1,9872,'timibbs',4,40.00,0.00,0,1763987306000,'122.10.198.206'),(17,3,9872,'timibbs',4,20.00,0.00,0,1763987577000,'122.10.198.206'),(18,1,9872,'timibbs',2,20.00,0.00,0,1763988680000,'122.10.198.206'),(19,1,9872,'timibbs',1,10.00,0.00,0,1763990922000,'122.10.198.206'),(20,1,9872,'timibbs',1,10.00,0.00,0,1763991411000,'122.10.198.206'),(21,6,9872,'timibbs',1,0.40,0.00,0,1764164923000,'98.98.91.137'),(22,7,9872,'timibbs',1,2.00,0.00,0,1764173262000,'98.98.91.137');
/*!40000 ALTER TABLE `caipiao_hemai_join` ENABLE KEYS */;
UNLOCK TABLES;
