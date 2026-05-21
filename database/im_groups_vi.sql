--
-- Table structure for table `im_groups`
--

DROP TABLE IF EXISTS `im_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `im_groups` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL COMMENT 'Tên nhóm',
  `avatar` varchar(255) DEFAULT '' COMMENT 'Ảnh đại diện nhóm',
  `owner_uid` int(10) unsigned NOT NULL COMMENT 'ID chủ nhóm',
  `member_count` int(10) unsigned DEFAULT '1' COMMENT 'Số lượng thành viên',
  `max_members` int(10) unsigned DEFAULT '500' COMMENT 'Số thành viên tối đa',
  `notice` text COMMENT 'Thông báo nhóm',
  `is_mute` tinyint(4) DEFAULT '0' COMMENT '0-bình thường, 1-tắt tiếng',
  `created_at` int(10) unsigned NOT NULL,
  `updated_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_owner` (`owner_uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Bảng nhóm chat IM';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `im_groups`
--

LOCK TABLES `im_groups` WRITE;
/*!40000 ALTER TABLE `im_groups` DISABLE KEYS */;
/*!40000 ALTER TABLE `im_groups` ENABLE KEYS */;
UNLOCK TABLES;
