--
-- Table structure for table `im_group_members`
--

DROP TABLE IF EXISTS `im_group_members`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `im_group_members` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `group_id` int(10) unsigned NOT NULL COMMENT 'ID nhóm',
  `uid` int(10) unsigned NOT NULL COMMENT 'ID người dùng',
  `nickname` varchar(50) DEFAULT '' COMMENT 'Biệt danh trong nhóm',
  `role` tinyint(4) DEFAULT '0' COMMENT '0-thành viên, 1-quản trị viên, 2-chủ nhóm',
  `is_mute` tinyint(4) DEFAULT '0' COMMENT '0-bình thường, 1-bị cấm chat',
  `joined_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_group_uid` (`group_id`,`uid`),
  KEY `idx_uid` (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Bảng thành viên nhóm IM';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `im_group_members`
--

LOCK TABLES `im_group_members` WRITE;
/*!40000 ALTER TABLE `im_group_members` DISABLE KEYS */;
/*!40000 ALTER TABLE `im_group_members` ENABLE KEYS */;
UNLOCK TABLES;
