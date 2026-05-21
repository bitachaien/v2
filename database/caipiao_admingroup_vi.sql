--
-- Table structure for table `caipiao_admingroup`
--

DROP TABLE IF EXISTS `caipiao_admingroup`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `caipiao_admingroup` (
  `groupid` smallint(6) NOT NULL AUTO_INCREMENT COMMENT 'ID nhóm',
  `groupname` varchar(30) NOT NULL COMMENT 'Tên nhóm quản trị',
  `level` smallint(6) NOT NULL COMMENT 'Cấp độ quyền hạn',
  PRIMARY KEY (`groupid`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='Bảng nhóm quản trị viên';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `caipiao_admingroup`
--

LOCK TABLES `caipiao_admingroup` WRITE;
/*!40000 ALTER TABLE `caipiao_admingroup` DISABLE KEYS */;
INSERT INTO `caipiao_admingroup` VALUES (1,'Siêu quản trị viên',1),(2,'Quản trị viên thường',2);
/*!40000 ALTER TABLE `caipiao_admingroup` ENABLE KEYS */;
UNLOCK TABLES;