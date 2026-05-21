--
-- Table structure for table `caipiao_question`
--

DROP TABLE IF EXISTS `caipiao_question`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `caipiao_question` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL COMMENT 'ID người dùng',
  `username` char(20) NOT NULL COMMENT 'Tên đăng nhập',
  `questionone` varchar(120) NOT NULL COMMENT 'Câu hỏi bảo mật 1',
  `answerone` varchar(120) NOT NULL COMMENT 'Câu trả lời 1',
  `questiontwo` varchar(120) NOT NULL COMMENT 'Câu hỏi bảo mật 2',
  `answertwo` varchar(120) NOT NULL COMMENT 'Câu trả lời 2',
  `questionthree` varchar(120) NOT NULL COMMENT 'Câu hỏi bảo mật 3',
  `answerthree` varchar(120) NOT NULL COMMENT 'Câu trả lời 3',
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='Câu hỏi bảo mật';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `caipiao_question`
--

LOCK TABLES `caipiao_question` WRITE;
/*!40000 ALTER TABLE `caipiao_question` DISABLE KEYS */;
INSERT INTO `caipiao_question` VALUES (1,8002,'hjjfukfu','您nhỏ学班主任的名字là?','fd3d02985ebb2bd8f937924fa431e510','您中学班主任的名字là?','65123dd3cbcc71e3f2134aefaa1956b9','您cao中班主任的名字là?','9a289f5c3953a84d4532ae615f263b16'),(13,8020,'zggcdyz','您nhỏ学班主任的名字là?','7055eced15538bfb7c07f8a5b28fc5d0','您母亲的姓名là?','dca1117a4a9933499a4a870b4190065a','您最喜欢的运动là?','a36abd601b784b2ece294786ee83e834'),(14,8021,'abc123','您的出生地là?','7055eced15538bfb7c07f8a5b28fc5d0','您中学班主任的名字là?','dca1117a4a9933499a4a870b4190065a','您cao中班主任的名字là?','a36abd601b784b2ece294786ee83e834'),(15,8022,'abc123t1','您的出生地là?','7055eced15538bfb7c07f8a5b28fc5d0','您cao中班主任的名字là?','dca1117a4a9933499a4a870b4190065a','您lớn学班主任的名字là?','a36abd601b784b2ece294786ee83e834'),(16,8017,'y123456','您的出生地là?','7055eced15538bfb7c07f8a5b28fc5d0','您中学班主任的名字là?','174a9535b7fd93ceecbe1fc0392fa0f2','您nhỏ学班主任的名字là?','6116afedcb0bc31083935c1c262ff4c9'),(17,8073,'ceshi','您的出生地là?','213b0e5bf59a1d51d20e50f15c148036','您nhỏ学班主任的名字là?','568965e8a32803ca331c8ce3ab645311','您cao中班主任的名字là?','63652f976d8b6a0c4d6cdac6909ba131'),(18,8105,'ceshi','您的出生地là?','7055eced15538bfb7c07f8a5b28fc5d0','您nhỏ学班主任的名字là?','dca1117a4a9933499a4a870b4190065a','您中学班主任的名字là?','a36abd601b784b2ece294786ee83e834');
/*!40000 ALTER TABLE `caipiao_question` ENABLE KEYS */;
UNLOCK TABLES;
