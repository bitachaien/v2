<?php

use plugin\admin\app\controller\AccountController;
use plugin\admin\app\controller\DictController;
use plugin\admin\app\controller\StatisticsController;
use plugin\admin\app\controller\StatisticsApiController;
use plugin\admin\app\controller\LotteryController;
use plugin\admin\app\controller\PlayController;
use plugin\admin\app\controller\BankController;
use plugin\admin\app\controller\LoginLogController;
use plugin\admin\app\controller\NoticeController;
use plugin\admin\app\controller\GameController;
use plugin\admin\app\controller\MemberGroupController;
use plugin\admin\app\controller\MemberController;
use plugin\admin\app\controller\TeamReportController;
use plugin\admin\app\controller\IpCheckController;
use plugin\admin\app\controller\FuddetailController;
use plugin\admin\app\controller\UploadController;
use plugin\admin\app\controller\WithdrawController;
use plugin\admin\app\controller\RechargeController;
use plugin\admin\app\controller\SysBankController;
use plugin\admin\app\controller\PaysetController;
use plugin\admin\app\controller\LiveController;
use plugin\admin\app\controller\MarketingController;
use plugin\admin\app\controller\LevelRewardController;
use plugin\admin\app\controller\AdminApiController;
use plugin\admin\app\controller\RobotApiController;
use plugin\admin\app\controller\MaintenanceApiController;
use plugin\admin\app\controller\FinanceApiController;
use plugin\admin\app\controller\Chat28ApiController;
use plugin\admin\app\controller\RebateController;
use Webman\Route;
use support\Request;

Route::any('/app/admin/account/captcha/{type}', [AccountController::class, 'captcha']);

Route::post('/app/admin/account/login', [AccountController::class, 'login']);
Route::get('/app/admin/account/info', [AccountController::class, 'info']);
Route::post('/app/admin/account/update', [AccountController::class, 'update']);
Route::post('/app/admin/account/password', [AccountController::class, 'password']);
Route::post('/app/admin/account/refreshToken', [AccountController::class, 'refreshToken']);
Route::post('/app/admin/account/logout', [AccountController::class, 'logout']);

Route::any('/app/admin/dict/get/{name}', [DictController::class, 'get']);

Route::any('/app/admin/statistics/overview', [StatisticsController::class, 'overview']);
Route::any('/app/admin/statistics/profit', [StatisticsController::class, 'profit']);
Route::any('/app/admin/statistics/user', [StatisticsController::class, 'user']);
Route::any('/app/admin/statistics/team', [StatisticsController::class, 'team']);
Route::any('/app/admin/statistics/lottery-bet', [StatisticsController::class, 'lottery_bet']);
Route::any('/app/admin/statistics/recharge', [StatisticsController::class, 'recharge']);

Route::get('/app/admin/api/statistics/overview', [StatisticsApiController::class, 'overview']);
Route::get('/app/admin/api/statistics/realtime', [StatisticsApiController::class, 'realtime']);
Route::get('/app/admin/api/statistics/recharge-trend', [StatisticsApiController::class, 'rechargeTrend']);
Route::get('/app/admin/api/statistics/bet-trend', [StatisticsApiController::class, 'betTrend']);
Route::get('/app/admin/api/statistics/user-growth', [StatisticsApiController::class, 'userGrowth']);
Route::get('/app/admin/api/statistics/finance', [StatisticsApiController::class, 'finance']);
Route::get('/app/admin/api/statistics/profit', [StatisticsApiController::class, 'profit']);
Route::get('/app/admin/api/statistics/user', [StatisticsApiController::class, 'user']);
Route::get('/app/admin/api/statistics/team', [StatisticsApiController::class, 'team']);
Route::get('/app/admin/api/statistics/lottery', [StatisticsApiController::class, 'lottery']);
Route::get('/app/admin/api/statistics/retention', [StatisticsApiController::class, 'retention']);
Route::get('/app/admin/api/statistics/value-analysis', [StatisticsApiController::class, 'valueAnalysis']);
Route::get('/app/admin/api/statistics/device-distribution', [StatisticsApiController::class, 'deviceDistribution']);

Route::get('/app/admin/api/statistics/team/overview', [StatisticsApiController::class, 'teamOverview']);
Route::get('/app/admin/api/statistics/team/levels', [StatisticsApiController::class, 'teamLevels']);
Route::get('/app/admin/api/statistics/team/commission-trend', [StatisticsApiController::class, 'teamCommissionTrend']);
Route::get('/app/admin/api/statistics/team/performance-trend', [StatisticsApiController::class, 'teamPerformanceTrend']);
Route::get('/app/admin/api/statistics/team/rank', [StatisticsApiController::class, 'teamRank']);

Route::get('/app/admin/system/info', [\plugin\admin\app\controller\SystemController::class, 'info']);

Route::get('/app/admin/api/system/menus', [\plugin\admin\app\controller\SystemController::class, 'menus']);

Route::get('/app/admin/api/admin/role-list', [AdminApiController::class, 'roleList']);
Route::get('/app/admin/api/admin/role-detail', [AdminApiController::class, 'roleDetail']);
Route::post('/app/admin/api/admin/role-add', [AdminApiController::class, 'roleAdd']);
Route::post('/app/admin/api/admin/role-edit', [AdminApiController::class, 'roleEdit']);
Route::post('/app/admin/api/admin/role-delete', [AdminApiController::class, 'roleDelete']);
Route::get('/app/admin/api/admin/role-options', [AdminApiController::class, 'roleOptions']);

Route::get('/app/admin/api/admin/list', [AdminApiController::class, 'list']);
Route::get('/app/admin/api/admin/detail', [AdminApiController::class, 'detail']);
Route::post('/app/admin/api/admin/add', [AdminApiController::class, 'add']);
Route::post('/app/admin/api/admin/edit', [AdminApiController::class, 'edit']);
Route::post('/app/admin/api/admin/delete', [AdminApiController::class, 'delete']);
Route::post('/app/admin/api/admin/status', [AdminApiController::class, 'status']);

Route::get('/app/admin/api/admin/log-list', [AdminApiController::class, 'logList']);
Route::post('/app/admin/api/admin/log-delete', [AdminApiController::class, 'logDelete']);
Route::post('/app/admin/api/admin/log-clear', [AdminApiController::class, 'logClear']);

Route::get('/app/admin/api/admin/rule-tree', [AdminApiController::class, 'ruleTree']);
Route::get('/app/admin/api/admin/rule-list', [AdminApiController::class, 'ruleList']);
Route::post('/app/admin/api/admin/role-rules', [AdminApiController::class, 'roleRules']);
Route::get('/app/admin/api/admin/role-rule-ids', [AdminApiController::class, 'roleRuleIds']);

Route::get('/app/admin/api/admin/ip-location', [AdminApiController::class, 'ipLocation']);
Route::post('/app/admin/api/admin/batch-update-location', [AdminApiController::class, 'batchUpdateLocation']);

Route::get('/app/admin/api/yzz-menu/tree', [\plugin\admin\app\controller\YzzMenuController::class, 'tree']);
Route::get('/app/admin/api/yzz-menu/full-tree', [\plugin\admin\app\controller\YzzMenuController::class, 'fullTree']);
Route::get('/app/admin/api/yzz-menu/list', [\plugin\admin\app\controller\YzzMenuController::class, 'list']);
Route::get('/app/admin/api/yzz-menu/detail', [\plugin\admin\app\controller\YzzMenuController::class, 'detail']);
Route::post('/app/admin/api/yzz-menu/add', [\plugin\admin\app\controller\YzzMenuController::class, 'add']);
Route::post('/app/admin/api/yzz-menu/edit', [\plugin\admin\app\controller\YzzMenuController::class, 'edit']);
Route::post('/app/admin/api/yzz-menu/delete', [\plugin\admin\app\controller\YzzMenuController::class, 'delete']);
Route::post('/app/admin/api/yzz-menu/status', [\plugin\admin\app\controller\YzzMenuController::class, 'status']);
Route::post('/app/admin/api/yzz-menu/sort', [\plugin\admin\app\controller\YzzMenuController::class, 'sort']);
Route::get('/app/admin/api/yzz-menu/parent-options', [\plugin\admin\app\controller\YzzMenuController::class, 'parentOptions']);
Route::post('/app/admin/api/yzz-menu/import', [\plugin\admin\app\controller\YzzMenuController::class, 'import']);

Route::get('/app/admin/system/banner-list', [MarketingController::class, 'bannerList']);
Route::post('/app/admin/system/banner-add', [MarketingController::class, 'bannerAdd']);
Route::post('/app/admin/system/banner-edit', [MarketingController::class, 'bannerEdit']);
Route::post('/app/admin/system/banner-delete', [MarketingController::class, 'bannerDelete']);
Route::post('/app/admin/system/banner-status', [MarketingController::class, 'bannerStatus']);

Route::get('/app/admin/api/activity/list', [MarketingController::class, 'activityList']);
Route::get('/app/admin/api/activity/detail', [MarketingController::class, 'activityDetail']);
Route::post('/app/admin/api/activity/add', [MarketingController::class, 'activityAdd']);
Route::post('/app/admin/api/activity/edit', [MarketingController::class, 'activityEdit']);
Route::post('/app/admin/api/activity/delete', [MarketingController::class, 'activityDelete']);
Route::post('/app/admin/api/activity/status', [MarketingController::class, 'activityStatus']);
Route::get('/app/admin/api/activity/type-options', [MarketingController::class, 'activityTypeOptions']);

Route::get('/app/admin/api/activity/type-list', [MarketingController::class, 'activityTypeList']);
Route::post('/app/admin/api/activity/type-add', [MarketingController::class, 'activityTypeAdd']);
Route::post('/app/admin/api/activity/type-edit', [MarketingController::class, 'activityTypeEdit']);
Route::post('/app/admin/api/activity/type-delete', [MarketingController::class, 'activityTypeDelete']);
Route::post('/app/admin/api/activity/type-status', [MarketingController::class, 'activityTypeStatus']);

Route::get('/app/admin/api/activity-category/list', [\app\controller\admin\ActivityCategoryController::class, 'list']);
Route::get('/app/admin/api/activity-category/options', [\app\controller\admin\ActivityCategoryController::class, 'options']);
Route::post('/app/admin/api/activity-category/add', [\app\controller\admin\ActivityCategoryController::class, 'add']);
Route::post('/app/admin/api/activity-category/edit', [\app\controller\admin\ActivityCategoryController::class, 'edit']);
Route::post('/app/admin/api/activity-category/delete', [\app\controller\admin\ActivityCategoryController::class, 'delete']);
Route::post('/app/admin/api/activity-category/status', [\app\controller\admin\ActivityCategoryController::class, 'status']);

Route::get('/app/admin/api/level-reward/config-list', [LevelRewardController::class, 'configList']);
Route::post('/app/admin/api/level-reward/config-save', [LevelRewardController::class, 'configSave']);
Route::post('/app/admin/api/level-reward/config-delete', [LevelRewardController::class, 'configDelete']);

Route::get('/app/admin/api/level-reward/record-list', [LevelRewardController::class, 'recordList']);

Route::get('/app/admin/api/level-reward/user-level-list', [LevelRewardController::class, 'userLevelList']);

Route::post('/app/admin/api/level-reward/grant', [LevelRewardController::class, 'grantReward']);
Route::post('/app/admin/api/level-reward/batch-grant', [LevelRewardController::class, 'batchGrantReward']);

Route::get('/app/admin/api/level-reward/stats', [LevelRewardController::class, 'stats']);

Route::get('/app/admin/api/level-reward/status-options', [LevelRewardController::class, 'statusOptions']);

Route::get('/app/admin/api/gift/config', [MarketingController::class, 'giftConfig']);
Route::post('/app/admin/api/gift/save', [MarketingController::class, 'giftSave']);
Route::post('/app/admin/api/gift/send', [MarketingController::class, 'giftSend']);
Route::post('/app/admin/api/gift/batch-send', [MarketingController::class, 'giftBatchSend']);
Route::get('/app/admin/api/gift/records', [MarketingController::class, 'giftRecords']);
Route::get('/app/admin/api/gift/type-options', [MarketingController::class, 'giftTypeOptions']);

Route::get('/app/admin/api/game/platform-list', [GameController::class, 'platformList']);
Route::get('/app/admin/api/game/platform-detail', [GameController::class, 'platformDetail']);
Route::post('/app/admin/api/game/platform-add', [GameController::class, 'platformAdd']);
Route::post('/app/admin/api/game/platform-edit', [GameController::class, 'platformEdit']);
Route::post('/app/admin/api/game/platform-delete', [GameController::class, 'platformDelete']);
Route::post('/app/admin/api/game/platform-status', [GameController::class, 'platformStatus']);
Route::get('/app/admin/api/game/platform-balance', [GameController::class, 'platformBalance']);
Route::get('/app/admin/api/game/platform-balance-all', [GameController::class, 'platformBalanceAll']);
Route::get('/app/admin/api/game/platform-type-options', [GameController::class, 'platformTypeOptions']);
Route::get('/app/admin/api/game/platform-status-options', [GameController::class, 'platformStatusOptions']);
Route::get('/app/admin/api/game/platform-options', [GameController::class, 'platformOptions']);

Route::get('/app/admin/api/game/bet-list', [GameController::class, 'betList']);
Route::get('/app/admin/api/game/bet-detail', [GameController::class, 'betDetail']);
Route::get('/app/admin/api/game/bet-status-options', [GameController::class, 'betStatusOptions']);

Route::get('/app/admin/api/game/transfer-list', [GameController::class, 'transferList']);
Route::get('/app/admin/api/game/transfer-detail', [GameController::class, 'transferDetail']);
Route::post('/app/admin/api/game/transfer-manual', [GameController::class, 'transferManual']);
Route::get('/app/admin/api/game/transfer-type-options', [GameController::class, 'transferTypeOptions']);
Route::get('/app/admin/api/game/transfer-status-options', [GameController::class, 'transferStatusOptions']);
Route::post('/app/admin/api/game/transfer-recall-all', [GameController::class, 'transferRecallAll']);

Route::get('/app/admin/api/game/game-list', [GameController::class, 'gameList']);
Route::post('/app/admin/api/game/game-save', [GameController::class, 'gameSave']);
Route::post('/app/admin/api/game/game-status', [GameController::class, 'gameStatus']);
Route::post('/app/admin/api/game/game-hot', [GameController::class, 'gameHot']);
Route::post('/app/admin/api/game/game-delete', [GameController::class, 'gameDelete']);

Route::post('/app/admin/api/game/sync-ng-platforms', [GameController::class, 'syncNGPlatforms']);
Route::post('/app/admin/api/game/sync-ng-games', [GameController::class, 'syncNGGames']);
Route::post('/app/admin/api/game/update-platform', [GameController::class, 'updatePlatform']);
Route::post('/app/admin/api/game/delete-platform', [GameController::class, 'deletePlatform']);

Route::get('/app/admin/api/yebao/product-list', [\plugin\admin\app\controller\YebaoController::class, 'productList']);
Route::get('/app/admin/api/yebao/product-detail', [\plugin\admin\app\controller\YebaoController::class, 'productDetail']);
Route::post('/app/admin/api/yebao/product-add', [\plugin\admin\app\controller\YebaoController::class, 'productAdd']);
Route::post('/app/admin/api/yebao/product-edit', [\plugin\admin\app\controller\YebaoController::class, 'productEdit']);
Route::post('/app/admin/api/yebao/product-delete', [\plugin\admin\app\controller\YebaoController::class, 'productDelete']);
Route::post('/app/admin/api/yebao/product-status', [\plugin\admin\app\controller\YebaoController::class, 'productStatus']);
Route::get('/app/admin/api/yebao/product-options', [\plugin\admin\app\controller\YebaoController::class, 'productOptions']);
Route::get('/app/admin/api/yebao/product-type-options', [\plugin\admin\app\controller\YebaoController::class, 'productTypeOptions']);

Route::get('/app/admin/api/yebao/holding-list', [\plugin\admin\app\controller\YebaoController::class, 'holdingList']);
Route::get('/app/admin/api/yebao/holding-detail', [\plugin\admin\app\controller\YebaoController::class, 'holdingDetail']);
Route::post('/app/admin/api/yebao/holding-settle', [\plugin\admin\app\controller\YebaoController::class, 'holdingSettle']);
Route::post('/app/admin/api/yebao/holding-delete', [\plugin\admin\app\controller\YebaoController::class, 'holdingDelete']);
Route::get('/app/admin/api/yebao/holding-stats', [\plugin\admin\app\controller\YebaoController::class, 'holdingStats']);
Route::get('/app/admin/api/yebao/holding-status-options', [\plugin\admin\app\controller\YebaoController::class, 'holdingStatusOptions']);

Route::get('/app/admin/api/yebao/record-list', [\plugin\admin\app\controller\YebaoController::class, 'recordList']);
Route::get('/app/admin/api/yebao/record-detail', [\plugin\admin\app\controller\YebaoController::class, 'recordDetail']);
Route::post('/app/admin/api/yebao/record-delete', [\plugin\admin\app\controller\YebaoController::class, 'recordDelete']);
Route::get('/app/admin/api/yebao/record-type-options', [\plugin\admin\app\controller\YebaoController::class, 'recordTypeOptions']);

Route::post('/app/admin/api/yebao/admin-deposit', [\plugin\admin\app\controller\YebaoController::class, 'adminDeposit']);
Route::post('/app/admin/api/yebao/admin-withdraw', [\plugin\admin\app\controller\YebaoController::class, 'adminWithdraw']);
Route::get('/app/admin/api/yebao/user-info', [\plugin\admin\app\controller\YebaoController::class, 'userInfo']);

Route::get('/app/admin/api/yebao/user-stats', [\plugin\admin\app\controller\YebaoController::class, 'userStats']);
Route::get('/app/admin/api/yebao/overview', [\plugin\admin\app\controller\YebaoController::class, 'overview']);

Route::get('/app/admin/api/yebao/config', [\plugin\admin\app\controller\YebaoController::class, 'config']);
Route::post('/app/admin/api/yebao/config-save', [\plugin\admin\app\controller\YebaoController::class, 'configSave']);

Route::get('/app/admin/api/robot/send-order/list', [RobotApiController::class, 'sendOrderList']);
Route::post('/app/admin/api/robot/send-order/update', [RobotApiController::class, 'sendOrderUpdate']);
Route::get('/app/admin/api/robot/send-order/wanfa-options', [RobotApiController::class, 'wanfaOptions']);
Route::get('/app/admin/api/robot/send-order/baomi-options', [RobotApiController::class, 'baomiOptions']);

Route::get('/app/admin/api/robot/hemai/list', [RobotApiController::class, 'hemaiList']);
Route::get('/app/admin/api/robot/hemai/detail', [RobotApiController::class, 'hemaiDetail']);
Route::get('/app/admin/api/robot/hemai/lottery-options', [RobotApiController::class, 'lotteryOptions']);
Route::get('/app/admin/api/robot/hemai/status-options', [RobotApiController::class, 'hemaiStatusOptions']);

Route::get('/app/admin/api/chat28/config/list', [Chat28ApiController::class, 'configList']);
Route::get('/app/admin/api/chat28/config/detail', [Chat28ApiController::class, 'configDetail']);
Route::post('/app/admin/api/chat28/config/create', [Chat28ApiController::class, 'configCreate']);
Route::post('/app/admin/api/chat28/config/update', [Chat28ApiController::class, 'configUpdate']);
Route::post('/app/admin/api/chat28/config/toggle', [Chat28ApiController::class, 'configToggle']);
Route::post('/app/admin/api/chat28/config/delete', [Chat28ApiController::class, 'configDelete']);

Route::get('/app/admin/api/chat28/robot/list', [Chat28ApiController::class, 'robotList']);
Route::post('/app/admin/api/chat28/robot/create', [Chat28ApiController::class, 'robotCreate']);
Route::post('/app/admin/api/chat28/robot/batch-create', [Chat28ApiController::class, 'robotBatchCreate']);
Route::post('/app/admin/api/chat28/robot/update', [Chat28ApiController::class, 'robotUpdate']);
Route::post('/app/admin/api/chat28/robot/delete', [Chat28ApiController::class, 'robotDelete']);
Route::post('/app/admin/api/chat28/robot/batch-recharge', [Chat28ApiController::class, 'robotBatchRecharge']);

Route::get('/app/admin/api/chat28/message/list', [Chat28ApiController::class, 'messageList']);
Route::post('/app/admin/api/chat28/message/delete', [Chat28ApiController::class, 'messageDelete']);
Route::post('/app/admin/api/chat28/message/send-system', [Chat28ApiController::class, 'sendSystemMessage']);

Route::get('/app/admin/api/chat28/stats', [Chat28ApiController::class, 'stats']);
Route::get('/app/admin/api/chat28/lottery-options', [Chat28ApiController::class, 'lotteryOptions']);

Route::get('/app/admin/api/chat28/bot-config', [Chat28ApiController::class, 'botConfig']);
Route::post('/app/admin/api/chat28/bot-config-save', [Chat28ApiController::class, 'botConfigSave']);

Route::post('/app/admin/api/maintenance/clear-data', [MaintenanceApiController::class, 'clearData']);
Route::get('/app/admin/api/maintenance/clear-type-options', [MaintenanceApiController::class, 'clearTypeOptions']);
Route::get('/app/admin/api/maintenance/preview-count', [MaintenanceApiController::class, 'previewCount']);
Route::get('/app/admin/api/maintenance/state-options', [MaintenanceApiController::class, 'stateOptions']);

Route::get('/app/admin/api/maintenance/notice/list', [MaintenanceApiController::class, 'noticeList']);
Route::post('/app/admin/api/maintenance/notice/create', [MaintenanceApiController::class, 'noticeCreate']);
Route::post('/app/admin/api/maintenance/notice/update', [MaintenanceApiController::class, 'noticeUpdate']);
Route::post('/app/admin/api/maintenance/notice/delete', [MaintenanceApiController::class, 'noticeDelete']);
Route::get('/app/admin/api/maintenance/notice/type-options', [MaintenanceApiController::class, 'noticeTypeOptions']);

Route::get('/app/admin/api/maintenance/task/list', [MaintenanceApiController::class, 'taskList']);
Route::post('/app/admin/api/maintenance/task/update', [MaintenanceApiController::class, 'taskUpdate']);

Route::get('/app/admin/api/monitor/server', [MaintenanceApiController::class, 'monitorServer']);

Route::get('/app/admin/api/monitor/online/list', [MaintenanceApiController::class, 'onlineList']);
Route::post('/app/admin/api/monitor/online/batchForceLogout', [MaintenanceApiController::class, 'batchForceLogout']);

Route::post('/app/admin/api/common/upload', [FinanceApiController::class, 'upload']);

Route::get('/app/admin/api/lottery/index', [LotteryController::class, 'apiIndex']);
Route::get('/app/admin/api/lottery/detail', [LotteryController::class, 'detail']);
Route::post('/app/admin/api/lottery/save', [LotteryController::class, 'save']);
Route::post('/app/admin/api/lottery/set-status', [LotteryController::class, 'setStatus']);
Route::post('/app/admin/api/lottery/delete', [LotteryController::class, 'delete']);
Route::get('/app/admin/api/lottery/get-lottery-list', [LotteryController::class, 'getLotteryList']);

Route::get('/app/admin/api/lottery/play-list', [LotteryController::class, 'playList']);
Route::get('/app/admin/api/lottery/play-detail', [LotteryController::class, 'playDetail']);
Route::post('/app/admin/api/lottery/play-save', [LotteryController::class, 'playSave']);
Route::post('/app/admin/api/lottery/play-status', [LotteryController::class, 'playStatus']);

Route::get('/app/admin/api/lottery/pre-result-list', [LotteryController::class, 'preResultList']);
Route::post('/app/admin/api/lottery/pre-result-save', [LotteryController::class, 'preResultSave']);
Route::get('/app/admin/api/lottery/pre-result-history', [LotteryController::class, 'preResultHistoryList']);
Route::get('/app/admin/api/lottery/pre-result-history-list', [LotteryController::class, 'preResultHistoryList']);

Route::get('/app/admin/api/lottery/result-list', [LotteryController::class, 'resultList']);
Route::post('/app/admin/api/lottery/result-add', [LotteryController::class, 'apiResultAdd']);
Route::post('/app/admin/api/lottery/result-edit', [LotteryController::class, 'apiResultEdit']);
Route::post('/app/admin/api/lottery/result-reset', [LotteryController::class, 'resultReset']);
Route::post('/app/admin/api/lottery/result-delete', [LotteryController::class, 'resultDelete']);

Route::get('/app/admin/api/game-category/index', [\app\controller\admin\GameCategoryController::class, 'index']);
Route::get('/app/admin/api/game-category/detail', [\app\controller\admin\GameCategoryController::class, 'detail']);
Route::post('/app/admin/api/game-category/save', [\app\controller\admin\GameCategoryController::class, 'save']);
Route::post('/app/admin/api/game-category/set-status', [\app\controller\admin\GameCategoryController::class, 'setStatus']);
Route::post('/app/admin/api/game-category/update-sort', [\app\controller\admin\GameCategoryController::class, 'updateSort']);
Route::post('/app/admin/api/game-category/delete', [\app\controller\admin\GameCategoryController::class, 'delete']);

Route::get('/app/admin/api/member/list', [MemberController::class, 'list']);
Route::get('/app/admin/api/member/info', [MemberController::class, 'info']);
Route::get('/app/admin/api/member/child-list', [MemberController::class, 'childList']);
Route::get('/app/admin/api/member/balance-log-list', [MemberController::class, 'balanceLogList']);
Route::post('/app/admin/api/member/add', [MemberController::class, 'add']);
Route::post('/app/admin/api/member/edit', [MemberController::class, 'editSave']);
Route::post('/app/admin/api/member/delete', [MemberController::class, 'delete']);
Route::post('/app/admin/api/member/lock', [MemberController::class, 'lock']);
Route::post('/app/admin/api/member/kick', [MemberController::class, 'kick']);
Route::post('/app/admin/api/member/edit-balance', [MemberController::class, 'editBalanceSave']);
Route::post('/app/admin/api/member/edit-point', [MemberController::class, 'editPointSave']);
Route::post('/app/admin/api/member/edit-xima', [MemberController::class, 'editXimaSave']);
Route::post('/app/admin/api/member/reset-fund-password', [MemberController::class, 'resetFundPassword']);

Route::get('/app/admin/api/member/security-info', [MemberController::class, 'securityInfo']);
Route::post('/app/admin/api/member/reset-google', [MemberController::class, 'resetGoogle']);
Route::post('/app/admin/api/member/unbind-phone', [MemberController::class, 'unbindPhone']);
Route::post('/app/admin/api/member/unbind-email', [MemberController::class, 'unbindEmail']);
Route::post('/app/admin/api/member/reset-question', [MemberController::class, 'resetQuestion']);

Route::get('/app/admin/api/membergroup/list', [MemberGroupController::class, 'list']);

Route::get('/app/admin/api/member/bank-list', [BankController::class, 'list']);
Route::post('/app/admin/api/member/bank-edit', [BankController::class, 'edit']);
Route::post('/app/admin/api/member/bank-delete', [BankController::class, 'delete']);

Route::get('/app/admin/member/agent-link-list', [MemberController::class, 'agentLinkList']);
Route::post('/app/admin/member/agent-link-save', [MemberController::class, 'agentLinkSave']);
Route::post('/app/admin/member/agent-link-delete', [MemberController::class, 'agentLinkDelete']);

Route::get('/app/admin/agent-commission/list', [\plugin\admin\app\controller\AgentCommissionController::class, 'list']);
Route::get('/app/admin/agent-commission/stats', [\plugin\admin\app\controller\AgentCommissionController::class, 'stats']);
Route::post('/app/admin/agent-commission/manual-claim', [\plugin\admin\app\controller\AgentCommissionController::class, 'manualClaim']);
Route::any('/app/admin/agent-commission/rates', [\plugin\admin\app\controller\AgentCommissionController::class, 'rates']);
Route::any('/app/admin/agent-commission/settings', [\plugin\admin\app\controller\AgentCommissionController::class, 'settings']);
Route::post('/app/admin/agent-commission/settlement', [\plugin\admin\app\controller\AgentCommissionController::class, 'settlement']);
Route::get('/app/admin/agent-commission/agent-list', [\plugin\admin\app\controller\AgentCommissionController::class, 'agentList']);
Route::post('/app/admin/agent-commission/remove-agent', [\plugin\admin\app\controller\AgentCommissionController::class, 'removeAgent']);

Route::get('/app/admin/lottery/edit', [LotteryController::class, 'edit']);
Route::any('/app/admin/lottery/index', [LotteryController::class, 'index']);
Route::any('/app/admin/lottery/detail', [LotteryController::class, 'detail']);
Route::any('/app/admin/lottery/issue', [LotteryController::class, 'issue']);
Route::any('/app/admin/lottery/result', [LotteryController::class, 'result']);
Route::get('/app/admin/lottery/get-lottery-list', [LotteryController::class, 'getLotteryList']);
Route::get('/app/admin/lottery/result-list', [LotteryController::class, 'resultList']);
Route::any('/app/admin/lottery/result-add', [LotteryController::class, 'resultAdd']);
Route::any('/app/admin/lottery/result-edit', [LotteryController::class, 'resultEdit']);
Route::post('/app/admin/lottery/result-save', [LotteryController::class, 'resultSave']);
Route::post('/app/admin/lottery/result-reset', [LotteryController::class, 'resultReset']);
Route::post('/app/admin/lottery/result-delete', [LotteryController::class, 'resultDelete']);
Route::any('/app/admin/lottery/pre-result', [LotteryController::class, 'preResult']);
Route::get('/app/admin/lottery/pre-result-list', [LotteryController::class, 'preResultList']);
Route::post('/app/admin/lottery/pre-result-save', [LotteryController::class, 'preResultSave']);
Route::any('/app/admin/lottery/pre-result-history', [LotteryController::class, 'preResultHistory']);
Route::get('/app/admin/lottery/pre-result-history-list', [LotteryController::class, 'preResultHistoryList']);
Route::post('/app/admin/lottery/save', [LotteryController::class, 'save']);
Route::post('/app/admin/lottery/set-status', [LotteryController::class, 'setStatus']);
Route::post('/app/admin/lottery/delete', [LotteryController::class, 'delete']);

Route::any('/app/admin/lottery/play', [LotteryController::class, 'play']);
Route::get('/app/admin/lottery/play-list', [LotteryController::class, 'playList']);
Route::post('/app/admin/lottery/play-status', [LotteryController::class, 'playStatus']);
Route::post('/app/admin/lottery/play-save', [LotteryController::class, 'playSave']);

Route::any('/app/admin/lottery/game-log', [GameController::class, 'record']);
Route::get('/app/admin/game/record-list', [GameController::class, 'recordList']);
Route::post('/app/admin/game/cancel', [GameController::class, 'cancel']);

Route::any('/app/admin/lottery/bet-check', [GameController::class, 'checkAnomalyOrder']);
Route::get('/app/admin/game/check-anomaly-list', [GameController::class, 'checkAnomalyOrderList']);

Route::any('/app/admin/member/group', [MemberGroupController::class, 'index']);
Route::get('/app/admin/membergroup/list', [MemberGroupController::class, 'list']);
Route::any('/app/admin/membergroup/add', [MemberGroupController::class, 'add']);
Route::any('/app/admin/membergroup/edit', [MemberGroupController::class, 'edit']);
Route::post('/app/admin/membergroup/delete', [MemberGroupController::class, 'delete']);
Route::any('/app/admin/membergroup/set-limit', [MemberGroupController::class, 'setLimit']);
Route::post('/app/admin/membergroup/save-limit-config', [MemberGroupController::class, 'saveLimitConfig']);
Route::get('/app/admin/membergroup/get-limit', [MemberGroupController::class, 'getLimit']);

Route::any('/app/admin/member/index', [MemberController::class, 'index']);
Route::get('/app/admin/member/list', [MemberController::class, 'list']);
Route::any('/app/admin/member/info', [MemberController::class, 'info']);
Route::any('/app/admin/member/balance-log', [MemberController::class, 'balanceLog']);
Route::get('/app/admin/member/balance-log-list', [MemberController::class, 'balanceLogList']);
Route::any('/app/admin/member/children', [MemberController::class, 'children']);
Route::any('/app/admin/member/edit', [MemberController::class, 'edit']);
Route::post('/app/admin/member/delete', [MemberController::class, 'delete']);
Route::post('/app/admin/member/kick', [MemberController::class, 'kick']);
Route::any('/app/admin/member/edit-balance', [MemberController::class, 'editBalance']);
Route::any('/app/admin/member/edit-point', [MemberController::class, 'editPoint']);
Route::any('/app/admin/member/edit-xima', [MemberController::class, 'editXima']);
Route::get('/app/admin/member/devices', [MemberController::class, 'devices']);

Route::any('/app/admin/member/team', [TeamReportController::class, 'index']);
Route::get('/app/admin/team-report/data', [TeamReportController::class, 'getData']);

Route::any('/app/admin/member/ip-check', [IpCheckController::class, 'index']);
Route::get('/app/admin/member/ip-check-data', [IpCheckController::class, 'getData']);
Route::get('/app/admin/member/ip-check-members', [IpCheckController::class, 'getMembersByIp']);
Route::get('/app/admin/member/ip-check-stats', [IpCheckController::class, 'getStats']);

Route::any('/app/admin/member/bank', [BankController::class, 'index']);
Route::get('/app/admin/member/bank-list', [FinanceApiController::class, 'memberBankList']);
Route::post('/app/admin/member/bank-edit', [FinanceApiController::class, 'memberBankEdit']);
Route::post('/app/admin/member/bank-delete', [FinanceApiController::class, 'memberBankDelete']);

Route::any('/app/admin/member/login-log', [LoginLogController::class, 'index']);
Route::get('/app/admin/member/login-log-list', [LoginLogController::class, 'list']);

Route::any('/app/admin/member/notice', [NoticeController::class, 'index']);
Route::get('/app/admin/member/notice-list', [NoticeController::class, 'list']);
Route::any('/app/admin/member/notice-add', [NoticeController::class, 'add']);
Route::any('/app/admin/member/notice-edit', [NoticeController::class, 'edit']);
Route::post('/app/admin/member/notice-delete', [NoticeController::class, 'delete']);

Route::post('/app/admin/upload/image', [UploadController::class, 'image']);
Route::post('/app/admin/upload/file', [UploadController::class, 'file']);
Route::post('/app/admin/upload/avatar', [UploadController::class, 'avatar']);

Route::any('/app/admin/finance/withdraw', [WithdrawController::class, 'index']);
Route::get('/app/admin/finance/withdraw-list', [WithdrawController::class, 'list']);
Route::post('/app/admin/finance/withdraw-approve', [WithdrawController::class, 'approve']);
Route::post('/app/admin/finance/withdraw-reject', [WithdrawController::class, 'reject']);

Route::any('/app/admin/finance/recharge', [RechargeController::class, 'index']);
Route::get('/app/admin/finance/recharge-list', [RechargeController::class, 'list']);
Route::post('/app/admin/finance/recharge-approve', [RechargeController::class, 'approve']);
Route::post('/app/admin/finance/recharge-reject', [RechargeController::class, 'reject']);
Route::post('/app/admin/finance/recharge-delete', [RechargeController::class, 'delete']);

Route::any('/app/admin/finance/sysbank', [SysBankController::class, 'index']);
Route::any('/app/admin/finance/sysbank-list', [SysBankController::class, 'list']);
Route::any('/app/admin/finance/sysbank-add', [SysBankController::class, 'add']);
Route::post('/app/admin/finance/sysbank-save', [SysBankController::class, 'save']);
Route::any('/app/admin/finance/sysbank-edit', [SysBankController::class, 'edit']);
Route::post('/app/admin/finance/sysbank-update', [SysBankController::class, 'update']);
Route::post('/app/admin/finance/sysbank-delete', [SysBankController::class, 'delete']);
Route::post('/app/admin/finance/sysbank-deleteall', [SysBankController::class, 'deleteAll']);
Route::post('/app/admin/finance/sysbank-setstate', [SysBankController::class, 'setState']);
Route::post('/app/admin/finance/sysbank-listorder', [SysBankController::class, 'listOrder']);

Route::any('/app/admin/finance/payset', [PaysetController::class, 'index']);
Route::get('/app/admin/finance/payset-list', [PaysetController::class, 'list']);
Route::any('/app/admin/finance/payset-add', [PaysetController::class, 'add']);
Route::post('/app/admin/finance/payset-save', [PaysetController::class, 'save']);
Route::any('/app/admin/finance/payset-edit', [PaysetController::class, 'edit']);
Route::post('/app/admin/finance/payset-update', [PaysetController::class, 'update']);
Route::post('/app/admin/finance/payset-delete', [PaysetController::class, 'delete']);
Route::post('/app/admin/finance/payset-setstate', [PaysetController::class, 'setState']);

Route::any('/app/admin/live/merchant', [LiveController::class, 'merchant']);
Route::any('/app/admin/live/transfer', [LiveController::class, 'transfer']);
Route::get('/app/admin/live/transfer-list', [LiveController::class, 'transferList']);
Route::any('/app/admin/live/betrecord', [LiveController::class, 'betrecord']);
Route::get('/app/admin/live/betrecord-list', [LiveController::class, 'betrecordList']);
Route::any('/app/admin/live/collect', [LiveController::class, 'collect']);
Route::post('/app/admin/live/get-ag-record', [LiveController::class, 'getAgRecord']);
Route::post('/app/admin/live/get-bbin-record', [LiveController::class, 'getBbinRecord']);

Route::get('/app/admin/withdraw-account/select', [FinanceApiController::class, 'withdrawAccountList']);
Route::post('/app/admin/withdraw-account/insert', [FinanceApiController::class, 'withdrawAccountInsert']);
Route::post('/app/admin/withdraw-account/update', [FinanceApiController::class, 'withdrawAccountUpdate']);
Route::post('/app/admin/withdraw-account/delete', [FinanceApiController::class, 'withdrawAccountDelete']);
Route::post('/app/admin/withdraw-account/setDefault', [FinanceApiController::class, 'withdrawAccountSetDefault']);

Route::get('/app/admin/api/rebate/list', [RebateController::class, 'list']);
Route::post('/app/admin/api/rebate/audit', [RebateController::class, 'audit']);
Route::post('/app/admin/api/rebate/batch-audit', [RebateController::class, 'batchAudit']);
Route::get('/app/admin/api/rebate/stats', [RebateController::class, 'stats']);
Route::get('/app/admin/api/rebate/report', [RebateController::class, 'report']);

Route::get('/app/admin/api/rebate/tier-config', [RebateController::class, 'tierConfig']);
Route::post('/app/admin/api/rebate/tier-config-save', [RebateController::class, 'tierConfigSave']);
Route::post('/app/admin/api/rebate/tier-config-delete', [RebateController::class, 'tierConfigDelete']);
Route::get('/app/admin/api/rebate/user-bet-stats', [RebateController::class, 'userBetStats']);
Route::post('/app/admin/api/rebate/cleanup-tier-config', [RebateController::class, 'cleanupTierConfig']);

Route::fallback(function (Request $request) {
    return response($request->uri() . ' not found' , 404);
}, 'admin');
