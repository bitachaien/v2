<?php

use Webman\Route;

Route::group('/api/v1', function () {
    
    
    Route::options('[{path:.+}]', function() {
        return response('');
    });
    
    
    Route::get('/index', [app\controller\api\IndexController::class, 'index']);
    Route::get('/lottery-hall', [app\controller\api\IndexController::class, 'lotteryHall']);
    Route::get('/banners', [app\controller\api\IndexController::class, 'banners']);
    Route::get('/config', [app\controller\api\IndexController::class, 'config']);
    Route::get('/home/realtime-draws', [app\controller\api\IndexController::class, 'realtimeDraws']);
    Route::get('/lottery/current', [app\controller\api\IndexController::class, 'lotteryCurrentInfo']);
    
    
    Route::get('/notice/list', [app\controller\api\NoticeController::class, 'list']);
    Route::get('/notice/unread-count', [app\controller\api\NoticeController::class, 'unreadCount']);
    Route::get('/notice/detail/{id}', [app\controller\api\NoticeController::class, 'detail']);
    Route::get('/notices', [app\controller\api\IndexController::class, 'notices']);  
    Route::get('/notice/{id}', [app\controller\api\IndexController::class, 'noticeDetail']);  
    
    
    Route::get('/activity/categories', [app\controller\api\ActivityController::class, 'categories']);
    Route::get('/activity/list', [app\controller\api\ActivityController::class, 'list']);
    Route::get('/activity/detail/{id}', [app\controller\api\ActivityController::class, 'detail']);
    
    
    Route::post('/auth/login', [app\controller\api\AuthController::class, 'login']);
    Route::post('/auth/logout', [app\controller\api\AuthController::class, 'logout']);
    Route::post('/auth/refresh', [app\controller\api\AuthController::class, 'refresh']);  
    
    
    Route::post('/register', [app\controller\api\RegisterController::class, 'register']);
    Route::post('/check-username', [app\controller\api\RegisterController::class, 'checkUsername']);
    Route::post('/validate-reccode', [app\controller\api\RegisterController::class, 'validateReccode']);
    
    
    Route::get('/recharge/methods', [app\controller\api\RechargeController::class, 'methods']);
    Route::get('/recharge/config/{type}', [app\controller\api\RechargeController::class, 'config']);
    
    
    Route::get('/level-rewards/configs', [app\controller\api\LevelRewardController::class, 'getLevelConfigs']);
    
    
    Route::get('/yuebao/config', [app\controller\api\YuebaoController::class, 'config']);
    
    
    
    Route::get('/lottery/announcements', [app\controller\api\NoticeController::class, 'list']);
    
    
    Route::get('/lottery/categories', [app\controller\api\LotteryController::class, 'list']);
    
    
    // Game Launch Routes (GSC+ Integration) - Public access
    Route::post('/game/launch', [app\controller\api\GameLaunchController::class, 'launch']);
    Route::get('/game/gscplus/list', [app\controller\api\GameLaunchController::class, 'list']);
    Route::get('/game/gscplus/platforms', [app\controller\api\GameLaunchController::class, 'platforms']);
    Route::get('/game/gscplus/categories', [app\controller\api\GameLaunchController::class, 'categories']);
    
    // Legacy Game Routes (Keep for backward compatibility)
    Route::get('/game/hot', [app\controller\api\GameController::class, 'hot']);
    Route::get('/game/search', [app\controller\api\GameController::class, 'search']);
    
    
    Route::get('/animal/games', [app\controller\api\AnimalController::class, 'games']);
    Route::get('/animal/{name}/info', [app\controller\api\AnimalController::class, 'info']);
    Route::get('/animal/{name}/result', [app\controller\api\AnimalController::class, 'result']);
    Route::get('/animal/{name}/history', [app\controller\api\AnimalController::class, 'history']);
    Route::get('/animal/{name}/plays', [app\controller\api\AnimalController::class, 'plays']);
    
    
    Route::get('/lottery/{code}/expect', [app\controller\api\XY28Controller::class, 'expect']);
    Route::get('/lottery/{code}/last-result', [app\controller\api\XY28Controller::class, 'lastResult']);
    Route::get('/lottery/{code}/history', [app\controller\api\XY28Controller::class, 'history']);
    Route::get('/lottery/{code}/play-types', [app\controller\api\XY28Controller::class, 'playTypes']);
    Route::get('/lottery/{code}/bose-config', [app\controller\api\XY28Controller::class, 'boseConfig']); 
    Route::get('/lottery/{code}/hot-cold', [app\controller\api\XY28Controller::class, 'hotCold']); 
    
    Route::post('/lbpalNotify',[app\controller\api\RechargeController::class,'lbpalNotify']);
    
    // GSC+ Seamless Wallet Callback Routes
    Route::get('/gscplus/health', [app\controller\api\GscPlusCallbackController::class, 'health']);
    Route::post('/gscplus/test', [app\controller\api\GscPlusCallbackController::class, 'test']);
    Route::get('/gscplus/config', [app\controller\api\GscPlusCallbackController::class, 'config']);
    Route::post('/gscplus/seamless/balance', [app\controller\api\GscPlusCallbackController::class, 'balance']);
    Route::post('/gscplus/seamless/withdraw', [app\controller\api\GscPlusCallbackController::class, 'withdraw']);
    Route::post('/gscplus/seamless/deposit', [app\controller\api\GscPlusCallbackController::class, 'deposit']);
    Route::post('/gscplus/seamless/pushbetdata', [app\controller\api\GscPlusCallbackController::class, 'pushBetData']);
});

Route::group('/api/v1', function () {
    
    
    Route::post('/auth/heartbeat', [app\controller\api\AuthController::class, 'heartbeat']);  
    Route::get('/auth/profile', [app\controller\api\AuthController::class, 'profile']);
    Route::put('/auth/profile', [app\controller\api\AuthController::class, 'updateProfile']);
    Route::post('/auth/profile', [app\controller\api\AuthController::class, 'updateProfile']);  
    
    
    Route::post('/orders', [app\controller\api\OrderController::class, 'create']);
    Route::get('/orders', [app\controller\api\OrderController::class, 'index']);
    Route::get('/orders/{order_no}', [app\controller\api\OrderController::class, 'show']);
    Route::post('/orders/{order_no}/cancel', [app\controller\api\OrderController::class, 'cancel']);
    
    
    Route::get('/account/balance', [app\controller\api\AccountController::class, 'balance']);
    Route::post('/account/refresh-balance', [app\controller\api\AccountController::class, 'refreshBalance']);
    Route::get('/account/transactions', [app\controller\api\AccountController::class, 'transactions']);
    Route::get('/account/transaction-records', [app\controller\api\AccountController::class, 'transactionRecords']); 
    Route::get('/account/transaction-types', [app\controller\api\AccountController::class, 'transactionTypes']);
    Route::get('/account/record-filter-options', [app\controller\api\AccountController::class, 'recordFilterOptions']);
    Route::get('/account/stats/profit-loss', [app\controller\api\AccountController::class, 'profitLossStats']); 
    Route::get('/account/transaction/list', [app\controller\api\AccountController::class, 'transactionList']); 
    Route::get('/account/bill-records', [app\controller\api\AccountController::class, 'billRecords']);
    Route::get('/account/bet-records', [app\controller\api\AccountController::class, 'betRecords']);
    Route::get('/account/bet-stats', [app\controller\api\AccountController::class, 'betStats']);
    Route::get('/account/profit-loss', [app\controller\api\AccountController::class, 'profitLoss']);
    Route::get('/account/recharge-records', [app\controller\api\AccountController::class, 'rechargeRecords']);
    Route::get('/account/withdraw-records', [app\controller\api\AccountController::class, 'withdrawRecords']);
    Route::get('/account/receive-stats', [app\controller\api\AccountController::class, 'receiveStats']);
    Route::get('/account/rebate-records', [app\controller\api\AccountController::class, 'rebateRecords']);
    
    
    Route::get('/user/today-stats', [app\controller\api\UserController::class, 'todayStats']);
    Route::get('/user/today-stats/detail', [app\controller\api\UserController::class, 'todayStatsDetail']);
    Route::get('/user/devices', [app\controller\api\UserController::class, 'devices']);
    
    
    Route::get('/user/settings', [app\controller\api\XY28Controller::class, 'getUserSettings']); 
    Route::post('/user/settings', [app\controller\api\XY28Controller::class, 'saveUserSettings']); 
    
    
    Route::get('/agent/info', [app\controller\api\AgentController::class, 'info']);
    Route::get('/agent/invite-info', [app\controller\api\AgentController::class, 'inviteInfo']);
    Route::get('/agent/overview', [app\controller\api\AgentController::class, 'overview']);
    Route::get('/agent/my-stats', [app\controller\api\AgentController::class, 'myStats']);
    Route::get('/agent/performance', [app\controller\api\AgentController::class, 'performance']);
    Route::get('/agent/commission', [app\controller\api\AgentController::class, 'commission']);
    Route::post('/agent/claim-commission', [app\controller\api\AgentController::class, 'claimCommission']);
    Route::get('/agent/subordinate/list', [app\controller\api\AgentController::class, 'subordinateList']);
    Route::get('/agent/subordinate/bets', [app\controller\api\AgentController::class, 'subordinateBets']);
    Route::get('/agent/subordinate/finance', [app\controller\api\AgentController::class, 'subordinateFinance']);
    Route::get('/agent/subordinate/claims', [app\controller\api\AgentController::class, 'subordinateClaims']);
    Route::post('/agent/create-account', [app\controller\api\AgentController::class, 'createAccount']);
    Route::get('/agent/commission-rates', [app\controller\api\AgentController::class, 'commissionRates']);
    Route::get('/agent/calculate-commission', [app\controller\api\AgentController::class, 'calculateCommission']);
    
    
    Route::get('/bet/records', [app\controller\api\BetController::class, 'records']);
    Route::get('/bet/records/{trano}', [app\controller\api\BetController::class, 'detail']);
    
    
    Route::post('/notice/mark-read', [app\controller\api\NoticeController::class, 'markRead']);
    
    
    Route::get('/message/list', [app\controller\api\MessageController::class, 'list']);
    Route::get('/message/detail/{id}', [app\controller\api\MessageController::class, 'detail']);
    Route::post('/message/mark-read', [app\controller\api\MessageController::class, 'markRead']);
    Route::post('/message/delete', [app\controller\api\MessageController::class, 'delete']);
    Route::get('/message/unread-count', [app\controller\api\MessageController::class, 'unreadCount']);
    
    
    Route::post('/recharge/submit', [app\controller\api\RechargeController::class, 'submit']);
    Route::post('/recharge/confirm', [app\controller\api\RechargeController::class, 'confirm']);
    Route::get('/recharge/status/{trano}', [app\controller\api\RechargeController::class, 'status']);
    Route::get('/recharge/records', [app\controller\api\RechargeController::class, 'records']);
    
    
    Route::get('/activity/daily-reward', [app\controller\api\ActivityController::class, 'dailyReward']);
    Route::post('/activity/claim-daily-reward', [app\controller\api\ActivityController::class, 'claimDailyReward']);
    
    
    Route::get('/activity/rebate-by-vendor', [app\controller\api\ActivityController::class, 'rebateByVendor']);
    Route::post('/activity/claim-vendor-rebate', [app\controller\api\ActivityController::class, 'claimVendorRebate']);
    
    
    Route::get('/activity/rewards/{activityId}', [app\controller\api\ActivityRewardController::class, 'getRewards']);
    Route::get('/activity/check-reward/{activityId}', [app\controller\api\ActivityRewardController::class, 'checkReward']);
    Route::post('/activity/claim-reward', [app\controller\api\ActivityRewardController::class, 'claimReward']);
    Route::get('/activity/participation-history', [app\controller\api\ActivityRewardController::class, 'participationHistory']);
    
    
    Route::get('/rebate/list', [app\controller\api\RebateController::class, 'list']);
    Route::get('/rebate/summary', [app\controller\api\RebateController::class, 'summary']);
    Route::post('/rebate/claim', [app\controller\api\RebateController::class, 'claim']);
    Route::get('/rebate/rates', [app\controller\api\RebateController::class, 'rates']);
    Route::get('/rebate/tier-rates', [app\controller\api\RebateController::class, 'tierRates']);

    
    
    Route::get('/level-rewards', [app\controller\api\LevelRewardController::class, 'getRewardInfo']);           
    Route::post('/level-rewards', [app\controller\api\LevelRewardController::class, 'claimReward']);            
    Route::get('/level-rewards/records', [app\controller\api\LevelRewardController::class, 'getRecords']);      
    
    
    Route::get('/activity/level-reward', [app\controller\api\LevelRewardController::class, 'getRewardInfo']);
    Route::post('/activity/claim-level-reward', [app\controller\api\LevelRewardController::class, 'claimReward']);
    
    
    Route::get('/withdraw/config', [app\controller\api\WithdrawController::class, 'config']);
    Route::get('/withdraw/accounts', [app\controller\api\WithdrawController::class, 'accounts']);
    Route::post('/withdraw/submit', [app\controller\api\WithdrawController::class, 'submit']);
    Route::get('/withdraw/records', [app\controller\api\WithdrawController::class, 'records']);
    Route::post('/withdraw/cancel', [app\controller\api\WithdrawController::class, 'cancel']);
    
    
    Route::post('/withdraw/account/add', [app\controller\api\WithdrawController::class, 'addAccount']);
    Route::post('/withdraw/account/delete', [app\controller\api\WithdrawController::class, 'deleteAccount']);
    Route::post('/withdraw/account/default', [app\controller\api\WithdrawController::class, 'setDefaultAccount']);
    Route::post('/withdraw/upload-qrcode', [app\controller\api\WithdrawController::class, 'uploadQrCode']);
    
    
    Route::post('/auth/change-password', [app\controller\api\AuthController::class, 'changePassword']);
    Route::post('/auth/set-fund-password', [app\controller\api\AuthController::class, 'setFundPassword']);
    Route::post('/auth/change-fund-password', [app\controller\api\AuthController::class, 'changeFundPassword']);
    
    
    Route::get('/security/info', [app\controller\api\SecurityController::class, 'info']);
    Route::post('/security/phone/send-code', [app\controller\api\SecurityController::class, 'sendPhoneCode']);
    Route::post('/security/phone/bind', [app\controller\api\SecurityController::class, 'bindPhone']);
    Route::post('/security/email/send-code', [app\controller\api\SecurityController::class, 'sendEmailCode']);
    Route::post('/security/email/bind', [app\controller\api\SecurityController::class, 'bindEmail']);
    Route::get('/security/google/secret', [app\controller\api\SecurityController::class, 'getGoogleSecret']);
    Route::post('/security/google/bind', [app\controller\api\SecurityController::class, 'bindGoogle']);
    Route::get('/security/question/list', [app\controller\api\SecurityController::class, 'getQuestionList']);
    Route::post('/security/question/set', [app\controller\api\SecurityController::class, 'setQuestion']);
    Route::post('/security/verify-fund-pwd', [app\controller\api\SecurityController::class, 'verifyFundPwd']);
    
    
    // Legacy Game Routes - Moved to authenticated section
    // Route::get('/game/list', [app\controller\api\GameController::class, 'list']); // Commented - conflicts with GSC+ routes
    Route::post('/game/enter', [app\controller\api\GameController::class, 'enter']);
    Route::get('/game/balance/{platform}', [app\controller\api\GameController::class, 'balance']);
    Route::post('/game/transfer/in', [app\controller\api\GameController::class, 'transferIn']);
    Route::post('/game/transfer/out', [app\controller\api\GameController::class, 'transferOut']);
    Route::post('/game/transfer/recall-all', [app\controller\api\GameController::class, 'recallAll']);
    Route::get('/game/records', [app\controller\api\GameController::class, 'records']);
    Route::get('/game/platform-balances', [app\controller\api\GameController::class, 'platformBalances']);
    Route::post('/game/refresh-platform-balances', [app\controller\api\GameController::class, 'refreshPlatformBalances']);
    Route::post('/game/recover-all', [app\controller\api\GameController::class, 'recoverAll']);
    Route::post('/game/recover', [app\controller\api\GameController::class, 'recoverPlatform']);
    
    
    
    Route::post('/game/favorite/add', [app\controller\api\GameController::class, 'addFavorite']);
    Route::post('/game/favorite/remove', [app\controller\api\GameController::class, 'removeFavorite']);
    Route::get('/game/favorites', [app\controller\api\GameController::class, 'getFavorites']);
    Route::post('/game/recent/add', [app\controller\api\GameController::class, 'addRecent']);
    Route::get('/game/recent', [app\controller\api\GameController::class, 'getRecent']);
    
    
    Route::get('/game/{name}/info', [app\controller\api\LotteryController::class, 'info']);
    Route::get('/game/{name}/history', [app\controller\api\LotteryController::class, 'history']);
    Route::get('/game/{name}/latest', [app\controller\api\LotteryController::class, 'latest']);
    Route::get('/game/{name}/plays', [app\controller\api\LotteryController::class, 'plays']);
    Route::get('/game/{name}/double-plays', [app\controller\api\LotteryController::class, 'doublePlays']);
    Route::get('/game/{name}/my-bets', [app\controller\api\LotteryController::class, 'myBets']);
    Route::get('/game/{name}/statistics', [app\controller\api\LotteryController::class, 'statistics']);
    Route::post('/game/bet', [app\controller\api\LotteryController::class, 'bet']);
    Route::post('/game/double-bet', [app\controller\api\LotteryController::class, 'doubleBet']);
    Route::post('/game/chase', [app\controller\api\LotteryController::class, 'chase']);
    Route::post('/game/cancel', [app\controller\api\LotteryController::class, 'cancel']);
    
    
    Route::get('/entertainment/platforms', [app\controller\api\EntertainmentController::class, 'platforms']);
    Route::post('/entertainment/enter', [app\controller\api\EntertainmentController::class, 'enter']);
    Route::get('/entertainment/balance', [app\controller\api\EntertainmentController::class, 'balance']);
    Route::post('/entertainment/transfer-in', [app\controller\api\EntertainmentController::class, 'transferIn']);
    Route::post('/entertainment/transfer-out', [app\controller\api\EntertainmentController::class, 'transferOut']);
    Route::post('/entertainment/transfer-all-out', [app\controller\api\EntertainmentController::class, 'transferAllOut']);
    
    
    Route::post('/collector/trigger', [app\controller\api\CollectorController::class, 'trigger']);
    Route::get('/collector/status', [app\controller\api\CollectorController::class, 'status']);
    
    
    Route::get('/hemai/list', [app\controller\api\HemaiController::class, 'list']);
    Route::get('/hemai/detail/{id}', [app\controller\api\HemaiController::class, 'detail']);
    Route::get('/hemai/users/{id}', [app\controller\api\HemaiController::class, 'users']);
    Route::post('/hemai/buy', [app\controller\api\HemaiController::class, 'buy']);
    Route::get('/lottery/list', [app\controller\api\HemaiController::class, 'lotteryList']);
    Route::post('/hemai/create', [app\controller\api\HemaiController::class, 'create']);
    Route::get('/hemai/my-records', [app\controller\api\HemaiController::class, 'myRecords']);
    Route::post('/hemai/cancel/{id}', [app\controller\api\HemaiController::class, 'cancel']);
    Route::post('/hemai/cancel-join', [app\controller\api\HemaiController::class, 'cancelJoin']);
    Route::get('/hemai/share/{id}', [app\controller\api\HemaiController::class, 'share']);
    
    
    Route::get('/yuebao/info', [app\controller\api\YuebaoController::class, 'info']);
    Route::get('/yuebao/products', [app\controller\api\YuebaoController::class, 'products']);
    Route::post('/yuebao/transfer-in', [app\controller\api\YuebaoController::class, 'transferIn']);
    Route::post('/yuebao/transfer-out', [app\controller\api\YuebaoController::class, 'transferOut']);
    Route::post('/yuebao/claim', [app\controller\api\YuebaoController::class, 'claim']);
    Route::get('/yuebao/records', [app\controller\api\YuebaoController::class, 'records']);
    Route::get('/yuebao/analysis', [app\controller\api\YuebaoController::class, 'analysis']);
    Route::post('/yuebao/export', [app\controller\api\YuebaoController::class, 'export']);
    Route::get('/yuebao/holdings', [app\controller\api\YuebaoController::class, 'holdings']);
    
    
    
    Route::post('/bet/submit', [app\controller\api\LotteryController::class, 'bet']);
    Route::post('/bet/chase', [app\controller\api\LotteryController::class, 'chase']);
    Route::post('/bet/quick', [app\controller\api\LotteryController::class, 'bet']); 
    
    
    
    
    
    
    Route::get('/chase/records', [app\controller\api\ChaseController::class, 'records']); 
    Route::get('/chase/detail/{chaseNo}', [app\controller\api\ChaseController::class, 'detail']); 
    Route::post('/chase/cancel/{chaseNo}', [app\controller\api\ChaseController::class, 'cancel']); 
    
    
    Route::get('/bet/chase-records', [app\controller\api\ChaseController::class, 'records']); 
    Route::get('/bet/chase-records/{chaseNo}', [app\controller\api\ChaseController::class, 'detail']); 
    Route::post('/bet/chase/{chaseNo}/cancel', [app\controller\api\ChaseController::class, 'cancel']); 
    
    
    Route::get('/lottery/{code}/trend', [app\controller\api\LotteryController::class, 'statistics']); 
    Route::get('/lottery/{code}/hot-numbers', [app\controller\api\LotteryController::class, 'statistics']); 
    Route::get('/lottery/{code}/missing', [app\controller\api\LotteryController::class, 'statistics']); 
    
    
    
    
    
    Route::get('/hemai/my-created', [app\controller\api\HemaiController::class, 'myRecords']); 
    Route::get('/hemai/my-joined', [app\controller\api\HemaiController::class, 'myRecords']); 
    
    Route::post('/hemai/{hemaiId}/join', [app\controller\api\HemaiController::class, 'buy']); 
    Route::get('/hemai/{hemaiId}', [app\controller\api\HemaiController::class, 'detail']); 
    
    
    Route::get('/user/balance', [app\controller\api\AccountController::class, 'balance']); 
    Route::get('/user/bet-statistics', [app\controller\api\UserController::class, 'todayStats']); 
    
    
    Route::post('/lottery/chat-bet', [app\controller\api\LotteryChatController::class, 'chatBet']); 
    Route::get('/lottery/{code}/chat-messages', [app\controller\api\LotteryChatController::class, 'getChatMessages']); 
    Route::get('/lottery/{code}/chat-online', [app\controller\api\LotteryChatController::class, 'getChatOnlineCount']); 
    Route::get('/lottery/{code}/bet-history', [app\controller\api\LotteryChatController::class, 'getBetHistory']); 
    Route::get('/lottery/{code}/bet-stats', [app\controller\api\LotteryChatController::class, 'getIssueBetStats']); 
    Route::get('/lottery/{code}/issue-bets', [app\controller\api\LotteryChatController::class, 'getIssueBets']); 
    Route::post('/lottery/bet/{id}/cancel', [app\controller\api\LotteryChatController::class, 'cancelBet']); 
    Route::post('/lottery/bet/{id}/modify', [app\controller\api\LotteryChatController::class, 'modifyBet']); 
    
    
    Route::post('/animal/bet', [app\controller\api\AnimalController::class, 'bet']);
    Route::get('/animal/{name}/my-bets', [app\controller\api\AnimalController::class, 'myBets']);
    
})->middleware([
    app\middleware\AuthMiddleware::class,
]);

Route::get('/app/admin/home-lottery-config/data', [plugin\admin\app\controller\HomeLotteryConfigController::class, 'getData']);
Route::post('/app/admin/home-lottery-config/save', [plugin\admin\app\controller\HomeLotteryConfigController::class, 'save']);
Route::post('/app/admin/home-lottery-config/update-sort', [plugin\admin\app\controller\HomeLotteryConfigController::class, 'updateSort']);

Route::any('/app/admin/system/setting', [plugin\admin\app\controller\SystemController::class, 'setting']);
Route::post('/app/admin/system/save', [plugin\admin\app\controller\SystemController::class, 'save']);

Route::any('/app/admin/robot/setting', [plugin\admin\app\controller\RobotController::class, 'setting']);
Route::any('/app/admin/robot/add-robot', [plugin\admin\app\controller\RobotController::class, 'addRobot']);
Route::post('/app/admin/robot/delete-robot', [plugin\admin\app\controller\RobotController::class, 'deleteRobot']);
Route::any('/app/admin/robot/fadan', [plugin\admin\app\controller\RobotController::class, 'fadan']);
Route::post('/app/admin/robot/change-hemai-status', [plugin\admin\app\controller\RobotController::class, 'changeHemaiStatus']);
Route::post('/app/admin/robot/change-hemai-value', [plugin\admin\app\controller\RobotController::class, 'changeHemaiValue']);
Route::any('/app/admin/robot/hemai', [plugin\admin\app\controller\RobotController::class, 'hemai']);
Route::any('/app/admin/robot/hemai-detail', [plugin\admin\app\controller\RobotController::class, 'hemaiDetail']);

Route::group('/app/admin/api/im', function () {
    Route::get('/group/list', [app\controller\admin\ImController::class, 'groupList']);
    Route::get('/group/detail', [app\controller\admin\ImController::class, 'groupDetail']);
    Route::post('/group/create', [app\controller\admin\ImController::class, 'groupCreate']);
    Route::post('/group/update', [app\controller\admin\ImController::class, 'groupUpdate']);
    Route::post('/group/delete', [app\controller\admin\ImController::class, 'groupDelete']);
    Route::post('/group/toggle-status', [app\controller\admin\ImController::class, 'groupToggleStatus']);
    Route::get('/group/members', [app\controller\admin\ImController::class, 'groupMembers']);
    Route::post('/group/remove-member', [app\controller\admin\ImController::class, 'groupRemoveMember']);
    Route::get('/group/options', [app\controller\admin\ImController::class, 'groupOptions']);
    Route::get('/group-message/list', [app\controller\admin\ImController::class, 'groupMessageList']);
    Route::post('/group-message/delete', [app\controller\admin\ImController::class, 'groupMessageDelete']);
    Route::post('/group-message/batch-delete', [app\controller\admin\ImController::class, 'groupMessageBatchDelete']);
    Route::get('/user-message/list', [app\controller\admin\ImController::class, 'userMessageList']);
    Route::post('/user-message/delete', [app\controller\admin\ImController::class, 'userMessageDelete']);
    Route::post('/user-message/batch-delete', [app\controller\admin\ImController::class, 'userMessageBatchDelete']);
});

Route::group('/app/admin/api/activity-reward', function () {
    
    Route::get('/list', [app\controller\admin\ActivityRewardController::class, 'list']);
    Route::get('/detail', [app\controller\admin\ActivityRewardController::class, 'detail']);
    Route::post('/add', [app\controller\admin\ActivityRewardController::class, 'add']);
    Route::post('/edit', [app\controller\admin\ActivityRewardController::class, 'edit']);
    Route::post('/delete', [app\controller\admin\ActivityRewardController::class, 'delete']);
    
    
    Route::get('/participation-list', [app\controller\admin\ActivityRewardController::class, 'participationList']);
    Route::post('/audit', [app\controller\admin\ActivityRewardController::class, 'audit']);
    
    
    Route::get('/statistics', [app\controller\admin\ActivityRewardController::class, 'statistics']);
});

Route::group('/api/lottery', function () {
    
    Route::get('/list', [app\controller\api\K3Controller::class, 'list']);
    
    
    Route::group('/k3', function () {
        
        Route::get('/current', [app\controller\api\K3Controller::class, 'current']);
        
        
        Route::get('/history', [app\controller\api\K3Controller::class, 'history']);
        
        
        Route::get('/odds', [app\controller\api\K3Controller::class, 'odds']);
    });
});

Route::group('/api', function () {
    
    Route::group('/lottery/k3', function () {
        
        Route::post('/bet', [app\controller\api\K3Controller::class, 'bet']);
        
        
        Route::post('/hemai', [app\controller\api\K3Controller::class, 'hemai']);
    });
    
    
    Route::group('/user', function () {
        
        Route::get('/balance', [app\controller\api\AccountController::class, 'balance']);
        
        
        Route::get('/bets', [app\controller\api\OrderController::class, 'bets']);
    });
    
    
    Route::group('/im', function () {
        
        Route::get('/conversations', [app\controller\api\IMController::class, 'conversations']);
        Route::get('/messages', [app\controller\api\IMController::class, 'messages']);
        Route::post('/send', [app\controller\api\IMController::class, 'send']);
        Route::get('/unread', [app\controller\api\IMController::class, 'unread']);
        Route::post('/read', [app\controller\api\IMController::class, 'read']);
        Route::get('/user/search', [app\controller\api\IMController::class, 'searchUser']); 
        Route::get('/user', [app\controller\api\IMController::class, 'user']);
        Route::get('/customer-service', [app\controller\api\IMController::class, 'customerService']);
        
        
        Route::get('/contacts', [app\controller\api\IMController::class, 'contacts']);
        Route::get('/friend-requests', [app\controller\api\IMController::class, 'friendRequests']);
        Route::post('/friend-request', [app\controller\api\IMController::class, 'sendFriendRequest']);
        Route::post('/friend-request/handle', [app\controller\api\IMController::class, 'handleFriendRequest']);
        Route::post('/friend/remark', [app\controller\api\IMController::class, 'setFriendRemark']);
        Route::post('/friend/block', [app\controller\api\IMController::class, 'blockFriend']);
        Route::post('/friend/delete', [app\controller\api\IMController::class, 'deleteFriend']);
        
        
        Route::get('/groups', [app\controller\api\IMController::class, 'groups']);
        Route::post('/group/create', [app\controller\api\IMController::class, 'createGroup']);
        Route::get('/group/{id}/members', [app\controller\api\IMController::class, 'groupMembers']);
        Route::post('/group/{id}/invite', [app\controller\api\IMController::class, 'inviteMembers']);
        Route::post('/group/{id}/kick', [app\controller\api\IMController::class, 'kickMember']);
        Route::post('/group/{id}/admin', [app\controller\api\IMController::class, 'setAdmin']);
        Route::post('/group/{id}/quit', [app\controller\api\IMController::class, 'quitGroup']);
        
        
        Route::post('/conversation/top', [app\controller\api\IMController::class, 'toggleTop']);
        Route::post('/conversation/delete', [app\controller\api\IMController::class, 'deleteConversation']);
        Route::post('/conversation/mute', [app\controller\api\IMController::class, 'toggleMute']);
        
        
        Route::post('/upload', [app\controller\api\IMController::class, 'upload']);
    });
})->middleware([
    app\middleware\AuthMiddleware::class,
]);

$spaRoutes = [
    '/home-new',
    '/mine',
    '/activity',
    '/lottery',
    '/game',
    '/login',
    '/register',
    '/recharge',
    '/withdraw',
    '/bet',
    '/hemai',
    '/message',
    '/notice',
    '/security',
    '/yuebao',
    '/entertainment',
    '/member',
    '/im',
];

foreach ($spaRoutes as $route) {
    Route::get($route . '[{path:.*}]', function () {
        $indexFile = public_path() . '/index.html';
        if (file_exists($indexFile)) {
            return response(file_get_contents($indexFile), 200, ['Content-Type' => 'text/html; charset=utf-8']);
        }
        return response('Not Found', 404);
    });
}

Route::get('/', function () {
    $indexFile = public_path() . '/index.html';
    if (file_exists($indexFile)) {
        return response(file_get_contents($indexFile), 200, ['Content-Type' => 'text/html; charset=utf-8']);
    }
    return response('Not Found', 404);
});
