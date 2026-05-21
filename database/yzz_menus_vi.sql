--
-- Table structure for table `yzz_menus`
--

DROP TABLE IF EXISTS `yzz_menus`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `yzz_menus` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID khóa chính',
  `pid` int(10) unsigned DEFAULT '0' COMMENT 'ID menu cấp cha',
  `name` varchar(100) NOT NULL COMMENT 'Tên định danh',
  `title` varchar(255) NOT NULL COMMENT 'Tiêu đề menu',
  `icon` varchar(255) DEFAULT NULL COMMENT 'Biểu tượng',
  `path` varchar(255) DEFAULT NULL COMMENT 'Đường dẫn',
  `component` varchar(255) DEFAULT NULL COMMENT 'Component',
  `type` tinyint(4) NOT NULL DEFAULT '1' COMMENT 'Loại: 0-thư mục, 1-menu, 2-nút',
  `sort` int(11) DEFAULT '0' COMMENT 'Thứ tự sắp xếp',
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT 'Trạng thái: 0-vô hiệu hóa, 1-kích hoạt',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP COMMENT 'Thời gian tạo',
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Thời gian cập nhật',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_name` (`name`),
  KEY `idx_pid` (`pid`)
) ENGINE=InnoDB AUTO_INCREMENT=1026 DEFAULT CHARSET=utf8mb4 COMMENT='Bảng menu hệ thống YZZ';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `yzz_menus`
--

LOCK TABLES `yzz_menus` WRITE;
/*!40000 ALTER TABLE `yzz_menus` DISABLE KEYS */;
INSERT INTO `yzz_menus` VALUES (100,0,'Statistics','数据统计111','ri:bar-chart-box-line','/statistics','Layout',1,1,1,'2026-11-29 02:36:15','2026-11-29 18:15:22'),(101,100,'StatisticsOverview','统计概况','ri:dashboard-line','overview','/statistics/overview/index',1,1,1,'2026-11-29 02:36:15','2026-11-29 19:34:26'),(102,100,'UserOperations','Người dùng运营','ri:user-heart-line','user-operations','/statistics/user-operations/index',1,2,1,'2026-11-29 02:36:15','2026-11-29 19:34:26'),(103,100,'FinanceAnalysis','财务xu析','ri:money-dollar-circle-line','finance-analysis','/statistics/finance-analysis/index',1,3,1,'2026-11-29 02:36:15','2026-11-29 19:34:26'),(104,100,'StatisticsLottery','彩种统计','ri:ticket-2-line','lottery','/statistics/lottery/index',1,4,1,'2026-11-29 02:36:15','2026-11-29 19:34:26'),(200,0,'Lottery','彩票管理','ri:ticket-line','/lottery','Layout',0,2,1,'2026-11-29 02:36:15','2026-11-29 02:36:15'),(201,200,'LotteryList','彩种列Bảng','ri:list-check','lottery-list','/lottery/lottery-list/index',1,1,1,'2026-11-29 02:36:15','2026-11-29 19:34:26'),(202,200,'PlayList','玩法管理','ri:gamepad-line','play-list','/lottery/play-list/index',1,2,1,'2026-11-29 02:36:15','2026-11-29 19:34:26'),(203,200,'LotteryResultList','开奖管理','ri:calendar-check-line','result-list','/lottery/result-list/index',1,3,1,'2026-11-29 02:36:15','2026-11-29 19:34:26'),(204,200,'GameRecord','游戏记录','ri:file-list-3-line','game-record','/lottery/game-record/index',1,4,1,'2026-11-29 02:36:15','2026-11-29 19:34:26'),(205,200,'AbnormalRecord','注单异常检测','ri:alarm-warning-line','abnormal-record','/lottery/abnormal-record/index',1,5,1,'2026-11-29 02:36:15','2026-11-29 19:34:26'),(208,200,'PreResult','系统彩预开奖','ri:gift-line','pre-result','/lottery/pre-result/index',1,8,1,'2026-11-29 02:36:15','2026-11-29 19:34:26'),(209,200,'PreResultHistory','预开奖历史','ri:history-line','pre-result-history','/lottery/pre-result-history/index',1,9,0,'2026-11-29 02:36:15','2026-11-29 19:34:26'),(300,0,'Member','Thành viên管理','ri:user-3-line','/member','Layout',0,20,1,'2026-11-29 02:36:15','2026-11-29 02:36:15'),(301,300,'MemberGroup','Thành viên组','ri:group-line','group','/member/group/index',1,1,1,'2026-11-29 02:36:15','2026-11-29 19:34:26'),(302,300,'TeamProfile','团队概况','ri:team-line','team-profile','/statistics/team/index',1,2,1,'2026-11-29 02:36:15','2026-11-29 19:34:26'),(303,300,'IpCheck','同IPThành viên检测','ri:shield-user-line','ip-check','/member/ip-check/index',1,3,1,'2026-11-29 02:36:15','2026-11-29 19:34:26'),(304,300,'WithdrawAccount','Rút tiền账户管理','ri:bank-card-line','withdraw-account','/member/withdraw-account/index',1,4,1,'2026-11-29 02:36:15','2026-11-29 19:34:26'),(305,300,'BalanceLog','账变记录','ri:file-list-3-line','balance-log','/member/balance-log/index',1,5,1,'2026-11-29 02:36:15','2026-11-29 19:34:26'),(306,300,'BankInfo','银行信息管理','ri:bank-line','bank-info','/member/bank-info/index',1,6,1,'2026-11-29 02:36:15','2026-11-29 19:34:26'),(307,300,'AgentLink','代理Đăng ký链接','ri:share-line','agent-link','/member/agent-link/index',1,7,1,'2026-11-29 02:36:15','2026-11-29 19:34:26'),(308,300,'LoginLog','Đăng nhậpngày志','ri:history-line','login-log','/member/login-log/index',1,8,1,'2026-11-29 02:36:15','2026-11-29 19:34:26'),(309,300,'Notice','站内信管理','ri:message-3-line','notice','/member/notice/index',1,9,1,'2026-11-29 02:36:15','2026-11-29 19:34:26'),(310,300,'MemberList','Thành viên列Bảng','ri:user-search-line','list','/member/list/index',1,10,1,'2026-11-29 02:36:15','2026-11-29 19:34:26'),(400,0,'Finance','财务管理','ri:wallet-3-line','/finance','Layout',0,30,1,'2026-11-29 02:36:15','2026-11-29 02:36:15'),(401,400,'FinanceRecharge','Nạp tiền管理','ri:hand-coin-line','recharge','/finance/recharge/index',1,1,1,'2026-11-29 02:36:15','2026-11-29 19:34:26'),(402,400,'FinanceWithdraw','Rút tiền管理','ri:bank-card-line','withdraw','/finance/withdraw/index',1,2,1,'2026-11-29 02:36:15','2026-11-29 19:34:26'),(403,400,'FinanceBank','Rút tiền银行配置','ri:bank-line','bank','/finance/bank/index',1,3,1,'2026-11-29 02:36:15','2026-11-29 19:34:26'),(404,400,'FinancePaySet','Nạp tiền方式配置','ri:settings-3-line','payset','/finance/payset/index',1,4,1,'2026-11-29 02:36:15','2026-11-29 19:34:26'),(500,0,'Activity','Hoạt động管理','ri:gift-2-line','/activity','Layout',0,35,1,'2026-11-29 02:36:15','2026-11-29 02:36:15'),(501,500,'ActivityList','Hoạt động列Bảng','ri:list-check','list','/activity/index',1,1,1,'2026-11-29 02:36:15','2026-11-29 19:34:26'),(502,500,'ActivityReward','Hoạt động奖励配置','ri:gift-line','reward','/activity/reward/index',1,2,1,'2026-11-29 02:36:15','2026-11-29 19:34:26'),(503,500,'ActivityCategory','Hoạt độngxu类','ri:list-settings-line','activity-category','/activity/activity-category/index',1,3,1,'2026-11-29 02:36:15','2026-12-18 15:11:44'),(600,0,'Live','真人视讯','ri:gamepad-line','/live','Layout',0,40,1,'2026-11-29 02:36:15','2026-11-29 02:36:15'),(601,600,'GamePlatform','商户信息','ri:store-3-line','platform','/live/platform/index',1,1,1,'2026-11-29 02:36:15','2026-11-29 19:34:26'),(602,600,'GameBet','Đặt cược记录','ri:file-list-line','bet','/live/bet/index',1,2,1,'2026-11-29 02:36:15','2026-11-29 19:34:26'),(603,600,'GameTransfer','额度转让','ri:exchange-cny-line','transfer','/live/transfer/index',1,3,1,'2026-11-29 02:36:15','2026-11-29 19:34:26'),(700,0,'Yebao','Số dư宝管理','ri:money-cny-box-line','/yebao','Layout',0,45,1,'2026-11-29 02:36:15','2026-11-29 02:36:15'),(701,700,'YebProduct','Số dư宝Loại','ri:list-settings-line','product-list','/Yebgroup/index',1,1,1,'2026-11-29 02:36:15','2026-11-29 19:34:26'),(702,700,'YebHolding','Số dư宝记录','ri:file-list-line','holding-list','/Yebrecord/index',1,2,1,'2026-11-29 02:36:15','2026-11-29 19:34:26'),(703,700,'YebRecord','Số dư宝收益','ri:hand-coin-line','revenue-list','/Yebrecord/shouyi',1,3,1,'2026-11-29 02:36:15','2026-11-29 19:34:26'),(800,0,'Robot','机器人与发单','ri:robot-line','/robot','Layout',0,50,1,'2026-11-29 02:36:15','2026-11-29 02:36:15'),(801,800,'RobotManage','机器人管理','ri:settings-3-line','manage','/robot/manage/index',1,1,1,'2026-11-29 02:36:15','2026-11-29 19:34:26'),(802,800,'RobotSendOrder','发单设置','ri:list-settings-line','send-order','/robot/send-order/index',1,2,1,'2026-11-29 02:36:15','2026-11-29 19:34:26'),(803,800,'RobotHeMai','合买列Bảng','ri:file-list-line','hemai','/robot/hemai/index',1,3,1,'2026-11-29 02:36:15','2026-11-29 19:34:26'),(850,0,'Chat28','聊ngày室28','ri:message-3-line','/chat28','Layout',0,55,1,'2026-12-11 14:01:33','2026-12-11 14:01:33'),(851,850,'Chat28Config','机器人配置','ri:settings-4-line','config','/chat28/config/index',1,1,1,'2026-12-11 14:01:33','2026-12-11 14:26:14'),(852,850,'Chat28Robot','机器人管理','ri:robot-line','robot','/chat28/robot/index',1,2,1,'2026-12-11 14:01:33','2026-12-11 14:26:14'),(853,850,'Chat28Message','Tin nhắn管理','ri:chat-3-line','message','/chat28/message/index',1,3,1,'2026-12-11 14:01:33','2026-12-11 14:26:14'),(854,850,'Chat28Live','实时监控','ri:live-line','live','/chat28/live/index',1,0,1,'2026-12-11 14:08:00','2026-12-11 14:26:14'),(900,0,'Maintenance','运维管理','ri:tools-line','/maintenance','Layout',0,90,1,'2026-11-29 02:36:15','2026-11-29 02:36:15'),(901,900,'DataClear','数据清理','ri:delete-bin-line','clear','/maintenance/clear/index',1,1,1,'2026-11-29 02:36:15','2026-11-29 19:34:26'),(902,900,'NoticeManage','Thông báo管理','ri:notification-line','notice','/maintenance/notice/index',1,2,1,'2026-11-29 02:36:15','2026-11-29 19:34:26'),(903,900,'TaskManage','计划任务','ri:time-line','task','/maintenance/task/index',1,3,1,'2026-11-29 02:36:15','2026-11-29 19:34:26'),(904,900,'MonitorServer','服务监控','ri:dashboard-3-line','server','/monitor/server/index',1,4,1,'2026-11-29 02:36:15','2026-11-29 19:34:26'),(1000,0,'System','系统管理','ri:user-3-line','/system','Layout',0,100,1,'2026-11-29 02:36:15','2026-11-29 02:36:15'),(1001,1000,'User','管理员管理','ri:user-line','user','/system/user/index',1,1,1,'2026-11-29 02:36:15','2026-11-29 19:34:26'),(1002,1000,'Role','Vai trò管理','ri:user-settings-line','role','/system/role/index',1,2,1,'2026-11-29 02:36:15','2026-11-29 19:34:26'),(1003,1000,'Menus','Menu管理','ri:menu-line','menu','/system/menu/index',1,3,1,'2026-11-29 02:36:15','2026-11-29 19:34:26'),(1004,1000,'SystemSettings','系统设置','ri:settings-3-line','settings','/system/settings/index',1,4,1,'2026-11-29 02:36:15','2026-11-29 19:34:26'),(1005,1000,'SystemBanner','轮播图管理','ri:image-line','banner','/system/banner/index',1,5,1,'2026-11-29 02:36:15','2026-11-29 19:34:26'),(1006,1000,'SystemLog','操作ngày志','ri:file-list-3-line','log','/system/log/index',1,6,1,'2026-11-29 02:36:15','2026-11-29 19:34:26'),(1007,1000,'UserCenter','个人中心','ri:user-heart-line','user-center','/system/user-center/index',1,99,0,'2026-11-29 02:36:15','2026-11-29 19:34:26'),(1010,1000,'home-lottery-config','首页开奖配置','ri:home-gear-line','home-lottery-config','/system/home-lottery-config/index',1,50,1,'2026-11-30 03:57:38','2026-11-30 03:57:38'),(1011,500,'LevelRewardConfig','Thăng cấp奖励配置','ri:vip-crown-line','level-reward-config','/activity/level-reward/config',1,40,1,'2026-12-02 11:54:51','2026-12-02 11:54:51'),(1012,500,'LevelRewardRecord','Thăng cấp记录','ri:file-list-2-line','level-reward-record','/activity/level-reward/record',1,41,1,'2026-12-02 11:54:51','2026-12-02 11:54:51'),(1013,200,'GameCategory','游戏xu类','ri:apps-line','game-category','/lottery/game-category/index',1,2,1,'2026-12-07 08:53:57','2026-12-07 08:53:57'),(1014,200,'GameList','游戏列Bảng','ri:gamepad-line','game-list','/lottery/game-list/index',1,3,1,'2026-12-07 09:12:59','2026-12-07 09:12:59'),(1015,200,'GameListDetail','游戏管理','ri:gamepad-line','game-list/:platform','/lottery/game-list/detail',1,3,1,'2026-12-07 10:02:38','2026-12-07 10:02:38'),(1016,300,'AgentCommission','代理Hoa hồng管理','ri:money-cny-circle-line','agent-commission','/member/agent-commission/index',1,8,1,'2026-12-09 06:00:46','2026-12-09 06:00:46'),(1017,400,'FinanceRebate','反水管理','ri:exchange-funds-line','rebate','/finance/rebate/index',1,5,1,'2026-12-13 03:21:21','2026-12-13 03:21:21'),(1018,500,'ActivityRewardConfig','阶梯奖励配置','ri:treasure-map-line','reward-config','/activity/reward-config',1,42,1,'2026-12-18 12:33:02','2026-12-19 02:07:59'),(1019,500,'ParticipationAudit','参与记录审核','ri:file-check-line','participation-audit','/activity/participation-audit',1,43,1,'2026-12-18 12:33:03','2026-12-18 12:33:03'),(1021,500,'ActivityTypeManage','Hoạt độngLoại','ri:list-settings-line','type','/activity/type/index',1,4,1,'2026-12-18 15:11:44','2026-12-18 15:11:44'),(1022,0,'Im','IM管理','ri:chat-4-line','/im','Layout',0,56,1,'2026-12-19 08:14:54','2026-12-19 08:14:54'),(1023,1022,'ImGroup','群聊管理','ri:group-line','group','/im/group/index',1,1,1,'2026-12-19 08:14:54','2026-12-19 08:14:54'),(1024,1022,'ImGroupMessage','群聊Tin nhắn','ri:message-2-line','group-message','/im/group-message/index',1,2,1,'2026-12-19 08:14:54','2026-12-19 08:14:54'),(1025,1022,'ImUserMessage','Người dùngTin nhắn','ri:chat-1-line','user-message','/im/user-message/index',1,3,1,'2026-12-19 08:14:54','2026-12-19 08:14:54');
/*!40000 ALTER TABLE `yzz_menus` ENABLE KEYS */;
UNLOCK TABLES;

SET @db_name = DATABASE();
SET @has_yzz_menu_link = (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'yzz_menus' AND COLUMN_NAME = 'link'
);
SET @sql_yzz_menu_link = IF(
  @has_yzz_menu_link = 0,
  "ALTER TABLE `yzz_menus` ADD COLUMN `link` varchar(255) DEFAULT NULL COMMENT 'Liên kết ngoài' AFTER `component`",
  'SELECT 1'
);
PREPARE stmt_yzz_menu_link FROM @sql_yzz_menu_link;
EXECUTE stmt_yzz_menu_link;
DEALLOCATE PREPARE stmt_yzz_menu_link;

SET @has_yzz_menu_iframe = (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'yzz_menus' AND COLUMN_NAME = 'isIframe'
);
SET @sql_yzz_menu_iframe = IF(
  @has_yzz_menu_iframe = 0,
  "ALTER TABLE `yzz_menus` ADD COLUMN `isIframe` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Hiển thị trong iframe' AFTER `link`",
  'SELECT 1'
);
PREPARE stmt_yzz_menu_iframe FROM @sql_yzz_menu_iframe;
EXECUTE stmt_yzz_menu_iframe;
DEALLOCATE PREPARE stmt_yzz_menu_iframe;

INSERT INTO `yzz_menus` (`id`,`pid`,`name`,`title`,`icon`,`path`,`component`,`link`,`isIframe`,`type`,`sort`,`status`,`created_at`,`updated_at`)
SELECT 1026,0,'FrontendCasinoXoso','Giao diện casino xoso','ri:computer-line','/frontend-casino-xoso','Layout',NULL,0,0,96,1,'2026-05-15 00:00:00','2026-05-15 00:00:00'
WHERE NOT EXISTS (SELECT 1 FROM `yzz_menus` WHERE `id` = 1026 OR `name` = 'FrontendCasinoXoso');

INSERT INTO `yzz_menus` (`id`,`pid`,`name`,`title`,`icon`,`path`,`component`,`link`,`isIframe`,`type`,`sort`,`status`,`created_at`,`updated_at`)
SELECT 1027,1026,'ClientCasinoControl','Client Casino','ri:apps-2-line','/outside/iframe/client-casino','','http://localhost:3001/',1,1,1,1,'2026-05-15 00:00:00','2026-05-15 00:00:00'
WHERE NOT EXISTS (SELECT 1 FROM `yzz_menus` WHERE `id` = 1027 OR `name` = 'ClientCasinoControl');
