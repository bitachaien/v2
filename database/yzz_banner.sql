--
-- Table structure for table `yzz_banner`
--

DROP TABLE IF EXISTS `yzz_banner`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `yzz_banner` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID khóa chính',
  `title` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'Tiêu đề banner',
  `image` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'URL địa chỉ hình ảnh',
  `link` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'Liên kết URL',
  `platform` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT 'Nền tảng: 0-tất cả, 1-PC, 2-Mobile',
  `sort` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'Thứ tự sắp xếp',
  `status` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT 'Trạng thái: 0-vô hiệu hóa, 1-kích hoạt',
  `start_time` int(10) unsigned DEFAULT NULL COMMENT 'Thời gian bắt đầu (Unix timestamp)',
  `end_time` int(10) unsigned DEFAULT NULL COMMENT 'Thời gian kết thúc (Unix timestamp)',
  `remark` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'Ghi chú mô tả',
  `created_at` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'Thời gian tạo (Unix timestamp)',
  `updated_at` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'Thời gian cập nhật (Unix timestamp)',
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`) COMMENT 'Chỉ mục trạng thái',
  KEY `idx_platform` (`platform`) COMMENT 'Chỉ mục nền tảng',
  KEY `idx_sort` (`sort`) COMMENT 'Chỉ mục sắp xếp',
  KEY `idx_status_platform` (`status`,`platform`) COMMENT 'Chỉ mục trạng thái và nền tảng',
  KEY `idx_time_range` (`start_time`,`end_time`) COMMENT 'Chỉ mục khoảng thời gian'
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Bảng quản lý banner quảng cáo';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `yzz_banner`
--

LOCK TABLES `yzz_banner` WRITE;
/*!40000 ALTER TABLE `yzz_banner` DISABLE KEYS */;
INSERT INTO `yzz_banner` VALUES (1,'首页轮播图1','/app/admin/upload/img/20261128/69294e20b048.webp','https://www.example.com',2,100,1,NULL,NULL,'',1764598405,1764598738),(2,'123','/app/admin/upload/img/20261202/692df2927101.webp','',2,0,1,NULL,NULL,'',1764618903,1764618903),(3,'324','/app/admin/upload/img/20261202/692df2aaaeb0.webp','',2,0,1,NULL,NULL,'',1764618924,1764618924),(4,'345','/app/admin/upload/img/20261202/692df2b4a7d2.webp','',2,0,1,NULL,NULL,'',1764618935,1764618935);
/*!40000 ALTER TABLE `yzz_banner` ENABLE KEYS */;
UNLOCK TABLES;

-- Made with Bob
