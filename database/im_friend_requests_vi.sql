--
-- Table structure for table `im_friend_requests`
--

DROP TABLE IF EXISTS `im_friend_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `im_friend_requests` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `from_uid` int(10) unsigned NOT NULL COMMENT 'ID người gửi yêu cầu',
  `to_uid` int(10) unsigned NOT NULL COMMENT 'ID người nhận yêu cầu',
  `message` varchar(100) DEFAULT '' COMMENT 'Tin nhắn xác thực',
  `status` tinyint(4) DEFAULT '0' COMMENT '0-chờ xử lý, 1-đã chấp nhận, 2-đã từ chối',
  `created_at` int(10) unsigned NOT NULL,
  `updated_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_to_uid` (`to_uid`,`status`),
  KEY `idx_from_uid` (`from_uid`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COMMENT='Bảng yêu cầu kết bạn';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `im_friend_requests`
--

LOCK TABLES `im_friend_requests` WRITE;
/*!40000 ALTER TABLE `im_friend_requests` DISABLE KEYS */;
INSERT INTO `im_friend_requests` VALUES (1,9875,9872,'我là',1,1764193887,1764195049),(2,9874,9872,'我là',1,1764194457,1765292800),(3,9872,9874,'我là',0,1764195195,1764195195),(4,9875,9874,'我là',0,1764198005,1764198005),(5,9872,9880,'我là',1,1765261902,1765262181);
/*!40000 ALTER TABLE `im_friend_requests` ENABLE KEYS */;
UNLOCK TABLES;
