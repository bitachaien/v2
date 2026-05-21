--
-- Table structure for table `caipiao_bbbetrecord`
--

DROP TABLE IF EXISTS `caipiao_bbbetrecord`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `caipiao_bbbetrecord` (
  `bbId` int(11) NOT NULL AUTO_INCREMENT,
  `UserName` varchar(25) DEFAULT NULL,
  `WagersID` varchar(35) DEFAULT NULL,
  `WagersDate` datetime DEFAULT NULL,
  `SerialID` varchar(35) DEFAULT NULL,
  `RoundNo` varchar(25) DEFAULT NULL,
  `GameType` varchar(25) DEFAULT NULL,
  `WagerDetail` varchar(45) DEFAULT NULL,
  `GameCode` varchar(25) DEFAULT NULL,
  `Result` varchar(25) DEFAULT NULL,
  `Card` varchar(25) DEFAULT NULL,
  `BetAmount` decimal(10,0) DEFAULT NULL,
  `Origin` varchar(15) DEFAULT NULL,
  `Commissionable` decimal(10,0) DEFAULT NULL,
  `Payoff` decimal(10,0) DEFAULT NULL,
  `ExchangeRate` decimal(10,0) DEFAULT NULL,
  PRIMARY KEY (`bbId`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=9 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `caipiao_bbbetrecord`
--

LOCK TABLES `caipiao_bbbetrecord` WRITE;
/*!40000 ALTER TABLE `caipiao_bbbetrecord` DISABLE KEYS */;
/*!40000 ALTER TABLE `caipiao_bbbetrecord` ENABLE KEYS */;
UNLOCK TABLES;
