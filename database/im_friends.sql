--
-- Table structure for table `im_friends`
--

DROP TABLE IF EXISTS `im_friends`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `im_friends` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uid` int(10) unsigned NOT NULL COMMENT 'ID người dùng',
  `friend_uid` int(10) unsigned NOT NULL COMMENT 'ID bạn bè',
  `remark` varchar(50) DEFAULT '' COMMENT 'Ghi chú',
  `is_blocked` tinyint(4) DEFAULT '0' COMMENT '0-bình thường, 1-đã chặn',
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_uid_friend` (`uid`,`friend_uid`),
  KEY `idx_friend` (`friend_uid`),
  KEY `idx_user_friend` (`uid`,`friend_uid`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COMMENT='Bảng quan hệ bạn bè';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `im_friends`
--

LOCK TABLES `im_friends` WRITE;
/*!40000 ALTER TABLE `im_friends` DISABLE KEYS */;
INSERT INTO `im_friends` VALUES (1,9872,9875,'',0,1764195049),(2,9875,9872,'',0,1764195049),(3,9880,9872,'',0,1765262181),(4,9872,9880,'',0,1765262181),(5,9872,9874,'',0,1765292800),(6,9874,9872,'',0,1765292800);
/*!40000 ALTER TABLE `im_friends` ENABLE KEYS */;
UNLOCK TABLES;
