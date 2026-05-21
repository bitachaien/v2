--
-- Table structure for table `caipiao_activity_category`
--

DROP TABLE IF EXISTS `caipiao_activity_category`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `caipiao_activity_category` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID danh mục',
  `name` varchar(50) NOT NULL COMMENT 'Tên danh mục',
  `code` varchar(50) NOT NULL COMMENT 'Mã danh mục',
  `icon` varchar(255) DEFAULT NULL COMMENT 'URL biểu tượng',
  `sort` int(11) DEFAULT '0' COMMENT 'Sắp xếp',
  `status` tinyint(1) DEFAULT '1' COMMENT 'Trạng thái: 1-kích hoạt 0-vô hiệu hóa',
  `remark` varchar(255) DEFAULT NULL COMMENT 'Ghi chú',
  `created_at` int(11) DEFAULT NULL COMMENT 'Thời gian tạo',
  `updated_at` int(11) DEFAULT NULL COMMENT 'Thời gian cập nhật',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_code` (`code`),
  KEY `idx_status` (`status`),
  KEY `idx_sort` (`sort`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COMMENT='Bảng phân loại hoạt động（Độc lập với phân loại game trang chủ）';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `caipiao_activity_category`
--

LOCK TABLES `caipiao_activity_category` WRITE;
/*!40000 ALTER TABLE `caipiao_activity_category` DISABLE KEYS */;
INSERT INTO `caipiao_activity_category` VALUES (1,'综合','all','/assets/img/icon_dtfl_zh_0.svg',100,1,'',1766069249,1766070824),(2,'电子','slot','/assets/img/icon_dtfl_dz_0.svg',90,1,'',1766069249,1766070828),(3,'真人','live','/assets/img/icon_dtfl_zr_0.svg',80,1,'',1766069249,1766070831),(4,'捕鱼','fish','/assets/img/icon_dtfl_by_0.svg',70,1,'',1766069249,1766070834),(5,'棋牌','chess','/assets/img/icon_dtfl_qp_0.svg',60,1,'',1766069249,1766070837),(6,'彩票','lottery','/assets/img/icon_dtfl_cp_0.svg',50,1,'',1766069249,1766070841),(7,'体育','sport','/assets/img/icon_dtfl_ty_0.svg',40,0,'',1766069249,1766087678),(8,'区đồng链','qkl','',30,0,'',1766070857,1766071263);
/*!40000 ALTER TABLE `caipiao_activity_category` ENABLE KEYS */;
UNLOCK TABLES;
