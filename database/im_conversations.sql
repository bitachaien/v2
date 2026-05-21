--
-- Table structure for table `im_conversations`
--

DROP TABLE IF EXISTS `im_conversations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `im_conversations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uid` int(10) unsigned NOT NULL COMMENT 'ID người dùng',
  `target_type` tinyint(4) NOT NULL DEFAULT '1' COMMENT '1-chat riêng, 2-chat nhóm, 3-tin hệ thống',
  `target_id` int(10) unsigned NOT NULL COMMENT 'ID đối tượng (ID người dùng hoặc ID nhóm)',
  `last_msg_id` bigint(20) unsigned DEFAULT '0' COMMENT 'ID tin nhắn cuối',
  `last_content` varchar(255) DEFAULT '' COMMENT 'Nội dung tin nhắn cuối',
  `last_time` int(10) unsigned DEFAULT '0' COMMENT 'Thời gian tin nhắn cuối',
  `unread_count` int(10) unsigned DEFAULT '0' COMMENT 'Số tin chưa đọc',
  `is_top` tinyint(4) DEFAULT '0' COMMENT '0-bình thường, 1-ghim lên đầu',
  `is_mute` tinyint(4) DEFAULT '0' COMMENT '0-bình thường, 1-tắt thông báo',
  `updated_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_uid_target` (`uid`,`target_type`,`target_id`),
  KEY `idx_uid_time` (`uid`,`updated_at`),
  KEY `idx_user_conv` (`uid`,`target_type`,`target_id`),
  KEY `idx_updated` (`updated_at`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COMMENT='Bảng cuộc hội thoại IM';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `im_conversations`
--

LOCK TABLES `im_conversations` WRITE;
/*!40000 ALTER TABLE `im_conversations` DISABLE KEYS */;
INSERT INTO `im_conversations` VALUES (1,9875,1,9872,404,'1111',1765257340,2,0,0,1765257340),(2,9872,1,9875,404,'1111',1765257340,0,0,0,1765257340),(3,9872,1,9873,414,'😍',1766101627,0,0,0,1766101627),(4,9873,1,9872,414,'😍',1766101627,3,0,0,1766101627),(5,9880,1,9872,415,'😂',1766102068,2,0,0,1766102068),(6,9872,1,9880,415,'😂',1766102068,0,0,0,1766102068);
/*!40000 ALTER TABLE `im_conversations` ENABLE KEYS */;
UNLOCK TABLES;
