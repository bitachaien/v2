--
-- Table structure for table `caipiao_rebate_tier_config`
--

DROP TABLE IF EXISTS `caipiao_rebate_tier_config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `caipiao_rebate_tier_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_code` varchar(20) NOT NULL COMMENT 'trò chơidanh mục: slot/live/chess/fishing/lottery/sport',
  `vendor_code` varchar(20) NOT NULL COMMENT 'mã nhà cung cấp: P G/A G/BBI N',
  `min_bet` decimal(20,2) NOT NULL DEFAULT '0.00' COMMENT 'thấp nhấttích lũyrửa mã',
  `rate` decimal(5,2) NOT NULL DEFAULT '0.00' COMMENT 'hoàn trả cượctỷ lệ%',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Trạng thái: 1Kích hoạt 0Vô hiệu hóa',
  `created_at` int(11) NOT NULL DEFAULT '0',
  `updated_at` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_cat_vendor_minbet` (`category_code`,`vendor_code`,`min_bet`),
  KEY `idx_category` (`category_code`),
  KEY `idx_vendor` (`vendor_code`)
) ENGINE=InnoDB AUTO_INCREMENT=50 DEFAULT CHARSET=utf8mb4 COMMENT='反水阶梯Cấu hình';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `caipiao_rebate_tier_config`
--

LOCK TABLES `caipiao_rebate_tier_config` WRITE;
/*!40000 ALTER TABLE `caipiao_rebate_tier_config` DISABLE KEYS */;
INSERT INTO `caipiao_rebate_tier_config` VALUES (27,'slot','*',1.00,1.00,1,1766058522,1766058522),(28,'slot','*',70000.00,1.10,1,1766058522,1766058522),(29,'slot','*',700000.00,1.20,1,1766058522,1766058522),(30,'slot','*',3000000.00,1.30,1,1766058522,1766058522),(31,'slot','*',15000000.00,1.50,1,1766058522,1766058522),(32,'live','*',1.00,0.80,1,1766058579,1766058579),(33,'live','*',100000.00,0.90,1,1766058579,1766058579),(34,'live','*',1000000.00,1.00,1,1766058579,1766058579),(35,'fishing','*',1.00,1.00,1,1766058579,1766058579),(36,'fishing','*',70000.00,1.10,1,1766058579,1766058579),(37,'fishing','*',700000.00,1.20,1,1766058579,1766058579),(38,'chess','*',1.00,0.50,1,1766058579,1766058579),(39,'chess','*',100000.00,0.60,1,1766058579,1766058579),(40,'chess','*',1000000.00,0.70,1,1766058579,1766058579),(41,'lottery','*',1.00,0.50,1,1766058579,1766058579),(42,'lottery','*',100000.00,0.60,1,1766058579,1766058579),(43,'lottery','*',1000000.00,0.70,1,1766058579,1766058579),(44,'sport','*',1.00,0.80,1,1766058579,1766058579),(45,'sport','*',100000.00,0.90,1,1766058579,1766058579),(46,'sport','*',1000000.00,1.00,1,1766058579,1766058579),(47,'esport','*',1.00,0.80,1,1766058579,1766058579),(48,'esport','*',100000.00,0.90,1,1766058579,1766058579),(49,'esport','*',1000000.00,1.00,1,1766058579,1766058579);
/*!40000 ALTER TABLE `caipiao_rebate_tier_config` ENABLE KEYS */;
UNLOCK TABLES;
