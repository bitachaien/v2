--
-- Table structure for table `wa_admin_roles`
--

DROP TABLE IF EXISTS `wa_admin_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wa_admin_roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Khóa chính',
  `role_id` int(11) NOT NULL COMMENT 'ID vai trò',
  `admin_id` int(11) NOT NULL COMMENT 'ID quản trị viên',
  PRIMARY KEY (`id`),
  UNIQUE KEY `role_admin_id` (`role_id`,`admin_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COMMENT='Bảng vai trò quản trị viên';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wa_admin_roles`
--

LOCK TABLES `wa_admin_roles` WRITE;
/*!40000 ALTER TABLE `wa_admin_roles` DISABLE KEYS */;
INSERT INTO `wa_admin_roles` VALUES (1,1,1);
/*!40000 ALTER TABLE `wa_admin_roles` ENABLE KEYS */;
UNLOCK TABLES;
