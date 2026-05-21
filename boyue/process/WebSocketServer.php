<?php

namespace process;

use Workerman\Worker;
use Workerman\Timer;
use Workerman\Connection\TcpConnection;
use support\Db;
use support\Log;
use support\Redis;
use app\service\IMService;

class WebSocketServer
{
    
    protected $worker;
    
    
    protected static $clients = [];
    
    
    const HEARTBEAT_TIMEOUT = 90;
    
    
    protected static $userConnections = [];
    
    
    protected static $subscriptions = [];
    
    
    protected static $lastIssues = [];
    
    
    protected static $sentBillIssues = [];
    
    
    protected static $sentSealedNotices = [];
    
    
    protected static $sentDrawNotices = [];
    
    
    protected static $chatBotConfig = null;
    
    
    protected static $chatBotConfigTime = 0;
    
    
    protected function getChatBotConfig(): array
    {
        
        if (self::$chatBotConfig !== null && time() - self::$chatBotConfigTime < 300) {
            return self::$chatBotConfig;
        }
        
        try {
            $configKeys = [
                'chat_robot_name',
                'chat_robot_avatar',
                'chat_bill_enabled',
                'chat_result_enabled',
                'chat_sealed_notice_enabled',
                'chat_draw_notice_enabled',
                'chat_msg_pre_sealed',
                'chat_msg_sealed_line',
                'chat_msg_no_talk',
                'chat_msg_draw_coming'
            ];
            
            $configs = Db::table('caipiao_system_config')
                ->whereIn('config_key', $configKeys)
                ->pluck('config_value', 'config_key');
            
            self::$chatBotConfig = [
                'robot_name' => $configs['chat_robot_name'] ?? '',
                'robot_avatar' => $configs['chat_robot_avatar'] ?? '',
                'bill_enabled' => (int)($configs['chat_bill_enabled'] ?? 1),
                'result_enabled' => (int)($configs['chat_result_enabled'] ?? 1),
                'sealed_notice_enabled' => (int)($configs['chat_sealed_notice_enabled'] ?? 1),
                'draw_notice_enabled' => (int)($configs['chat_draw_notice_enabled'] ?? 1),
                'msg_pre_sealed' => $configs['chat_msg_pre_sealed'] ?? '',
                'msg_sealed_line' => $configs['chat_msg_sealed_line'] ?? '',
                'msg_no_talk' => $configs['chat_msg_no_talk'] ?? '',
                'msg_draw_coming' => $configs['chat_msg_draw_coming'] ?? '',
            ];
            self::$chatBotConfigTime = time();
            
        } catch (\Exception $e) {
            Log::warning("[WebSocket] Lấy聊天室机器人cấu hình thất bại: " . $e->getMessage());
            self::$chatBotConfig = [
                'robot_name' => '',
                'robot_avatar' => '',
                'bill_enabled' => 1,
                'result_enabled' => 1,
                'sealed_notice_enabled' => 1,
                'draw_notice_enabled' => 1,
                'msg_pre_sealed' => '',
                'msg_sealed_line' => '',
                'msg_no_talk' => '',
                'msg_draw_coming' => '',
            ];
        }
        
        return self::$chatBotConfig;
    }
    
    
    public function onWorkerStart(Worker $worker)
    {
        $this->worker = $worker;
        
        Log::info('[WebSocket] 服务启动，监听端口: ' . $worker->getSocketName());
        
        
        Timer::add(1, function () {
            $this->broadcastCountdown();
        });
        
        
        Timer::add(1, function () {
            $this->checkAndBroadcastResults();
        });
        
        
        Timer::add(30, function () {
            $this->checkHeartbeatTimeout();
        });
        
        
        $this->startRedisPollSubscribe($worker->id);
    }
    
    
    protected function startRedisPollSubscribe(int $workerId)
    {
        $redisConfig = config('redis.default', []);
        $queueKey = 'websocket:push:worker:' . $workerId; 
        $globalQueueKey = 'websocket_push_queue'; 
        
        
        Timer::add(0.1, function() use ($redisConfig, $queueKey, $globalQueueKey, $workerId) {
            try {
                static $redis = null;
                
                if ($redis === null) {
                    $redis = new \Redis();
                    $redis->connect(
                        $redisConfig['host'] ?? '127.0.0.1',
                        $redisConfig['port'] ?? 6379,
                        $redisConfig['timeout'] ?? 1.0
                    );
                    
                    if (!empty($redisConfig['password'])) {
                        $redis->auth($redisConfig['password']);
                    }
                    
                    if (!empty($redisConfig['database'])) {
                        $redis->select($redisConfig['database']);
                    }
                    
                    
                }
                
                
                for ($i = 0; $i < 10; $i++) {
                    $message = $redis->lPop($queueKey);
                    if (!$message) {
                        break;
                    }
                    $this->handleRedisPush($message, $workerId);
                }
                
                
                if ($workerId === 0) {
                    for ($i = 0; $i < 20; $i++) {
                        $message = $redis->lPop($globalQueueKey);
                        if (!$message) {
                            break;
                        }
                        
                        
                        $workerCount = $this->worker->count ?? 4;
                        for ($w = 0; $w < $workerCount; $w++) {
                            $workerQueue = 'websocket:push:worker:' . $w;
                            $redis->rPush($workerQueue, $message);
                        }
                    }
                }
                
            } catch (\Exception $e) {
                Log::error("[WebSocket] Worker {$workerId} Redis 轮询Lỗi: " . $e->getMessage());
                $redis = null; 
            }
        });
    }
    
    
    protected function handleRedisPush(string $message, int $workerId = 0)
    {
        try {
            $payload = json_decode($message, true);
            if (!$payload) {
                return;
            }
            
            $target = $payload['target'] ?? '';
            $type = $payload['type'] ?? '';
            $data = $payload['data'] ?? [];
            
            switch ($target) {
                case 'user':
                    $userId = $payload['userId'] ?? 0;
                    if ($userId > 0) {
                        
                        $hasConnection = isset(self::$userConnections[$userId]) && !empty(self::$userConnections[$userId]);
                        
                        if ($hasConnection) {
                            self::pushToUser($userId, $type, $data);
                        }
                        
                    }
                    break;
                    
                case 'users':
                    $userIds = $payload['userIds'] ?? [];
                    self::pushToUsers($userIds, $type, $data);
                    break;
                    
                case 'lottery':
                    $lotteryCode = $payload['lotteryCode'] ?? '';
                    if ($lotteryCode) {
                        
                        $data['lotteryCode'] = $lotteryCode;
                        self::broadcast($lotteryCode, $type, $data);
                    }
                    break;
                    
                case 'all':
                    self::broadcastToAll($type, $data);
                    break;
            }
            
        } catch (\Exception $e) {
            Log::error("[WebSocket] Worker {$workerId} 处理推送Thất bại: " . $e->getMessage());
        }
    }
    
    
    protected function checkHeartbeatTimeout()
    {
        $now = time();
        $timeout = self::HEARTBEAT_TIMEOUT;
        $closedCount = 0;
        
        foreach (self::$clients as $connectionId => $client) {
            $lastActive = $client['last_active'] ?? $now;
            if ($now - $lastActive > $timeout) {
                try {
                    $client['conn']->close();
                    $closedCount++;
                } catch (\Exception $e) {
                    
                }
            }
        }
        
        if ($closedCount > 0) {
            Log::info("[WebSocket] 心跳超时清理: 关闭了 {$closedCount} 个死连接，剩余: " . count(self::$clients));
        }
    }
    
    
    public function onConnect(TcpConnection $connection)
    {
        $connectionId = $connection->id;
        self::$clients[$connectionId] = [
            'conn' => $connection,
            'subscriptions' => [],
            'user_id' => 0,
            'last_active' => time(),
        ];
        
        
        $connection->send(json_encode([
            'type' => 'connected',
            'data' => [
                'message' => '连接Thành công',
                'serverTime' => time(),
            ]
        ]));
    }
    
    
    public function onMessage(TcpConnection $connection, $data)
    {
        $connectionId = $connection->id;
        
        try {
            $message = json_decode($data, true);
            
            if (!$message || !isset($message['type'])) {
                $connection->send(json_encode([
                    'type' => 'error',
                    'data' => ['message' => '无效的Tin nhắn格式']
                ]));
                return;
            }
            
            switch ($message['type']) {
                case 'subscribe':
                    $this->handleSubscribe($connection, $message['data'] ?? []);
                    break;
                    
                case 'unsubscribe':
                    $this->handleUnsubscribe($connection, $message['data'] ?? []);
                    break;
                    
                case 'ping':
                    
                    if (isset(self::$clients[$connectionId])) {
                        self::$clients[$connectionId]['last_active'] = time();
                    }
                    $connection->send(json_encode([
                        'type' => 'pong',
                        'data' => ['serverTime' => time()]
                    ]));
                    break;
                    
                case 'auth':
                    $this->handleAuth($connection, $message['data'] ?? []);
                    break;
                
                
                case 'im_send':
                    $this->handleIMSend($connection, $message['data'] ?? []);
                    break;
                    
                case 'im_read':
                    $this->handleIMRead($connection, $message['data'] ?? []);
                    break;
                
                case 'im_typing':
                    $this->handleIMTyping($connection, $message['data'] ?? []);
                    break;
                    
                case 'im_recall':
                    $this->handleIMRecall($connection, $message['data'] ?? []);
                    break;
                
                
                case 'refresh_platform_balances':
                    $this->handleRefreshPlatformBalances($connection);
                    break;
                
                
                case 'lottery_chat_send':
                    $this->handleLotteryChatSend($connection, $message['data'] ?? []);
                    break;
                    
                default:
                    $connection->send(json_encode([
                        'type' => 'error',
                        'data' => ['message' => '未知的Tin nhắn类型']
                    ]));
            }
            
        } catch (\Exception $e) {
            Log::error("[WebSocket] Tin nhắn处理Lỗi: " . $e->getMessage());
            $connection->send(json_encode([
                'type' => 'error',
                'data' => ['message' => 'Lỗi máy chủ']
            ]));
        }
    }
    
    
    public function onClose(TcpConnection $connection)
    {
        $connectionId = $connection->id;
        
        
        if (isset(self::$clients[$connectionId])) {
            
            foreach (self::$clients[$connectionId]['subscriptions'] as $lotteryCode => $v) {
                unset(self::$subscriptions[$lotteryCode][$connectionId]);
            }
            
            
            $userId = self::$clients[$connectionId]['user_id'] ?? 0;
            if ($userId > 0 && isset(self::$userConnections[$userId])) {
                unset(self::$userConnections[$userId][$connectionId]);
                if (empty(self::$userConnections[$userId])) {
                    unset(self::$userConnections[$userId]);
                    
                    
                    $this->notifyFriendsOffline($userId);
                }
            }
            
            unset(self::$clients[$connectionId]);
        }
    }
    
    
    protected function notifyFriendsOnline(int $userId)
    {
        try {
            $friends = Db::table('im_friends')
                ->where('uid', $userId)
                ->where('is_blocked', 0)
                ->pluck('friend_uid');
            
            foreach ($friends as $friendUid) {
                self::pushToUser($friendUid, 'im_presence', [
                    'userId' => $userId,
                    'isOnline' => true,
                ]);
            }
        } catch (\Exception $e) {
            
        }
    }
    
    
    protected function notifyFriendsOffline(int $userId)
    {
        try {
            $friends = Db::table('im_friends')
                ->where('uid', $userId)
                ->where('is_blocked', 0)
                ->pluck('friend_uid');
            
            foreach ($friends as $friendUid) {
                self::pushToUser($friendUid, 'im_presence', [
                    'userId' => $userId,
                    'isOnline' => false,
                ]);
            }
        } catch (\Exception $e) {
            
        }
    }
    
    
    protected function handleSubscribe(TcpConnection $connection, array $data)
    {
        $connectionId = $connection->id;
        $lotteryCode = $data['lotteryCode'] ?? '';
        
        if (empty($lotteryCode)) {
            $connection->send(json_encode([
                'type' => 'error',
                'data' => ['message' => '请指定彩种代码']
            ]));
            return;
        }
        
        
        $lotteryCode = $this->normalizeK3Code($lotteryCode);
        
        
        self::$clients[$connectionId]['subscriptions'][$lotteryCode] = true;
        
        if (!isset(self::$subscriptions[$lotteryCode])) {
            self::$subscriptions[$lotteryCode] = [];
        }
        self::$subscriptions[$lotteryCode][$connectionId] = $connection;
        
        
        $currentInfo = $this->getCurrentIssueInfo($lotteryCode);
        
        if ($currentInfo) {
            $connection->send(json_encode([
                'type' => 'subscribed',
                'data' => [
                    'lotteryCode' => $lotteryCode,
                    'current' => $currentInfo
                ]
            ]));
        } else {
            
            Log::warning("[WebSocket] Lấy期号信息Thất bại，但仍返回 subscribed: {$lotteryCode}");
            $connection->send(json_encode([
                'type' => 'subscribed',
                'data' => [
                    'lotteryCode' => $lotteryCode,
                    'current' => null,
                    'error' => 'Lấy期号信息Thất bại'
                ]
            ]));
        }
    }
    
    
    protected function handleUnsubscribe(TcpConnection $connection, array $data)
    {
        $connectionId = $connection->id;
        $lotteryCode = $data['lotteryCode'] ?? '';
        
        if (empty($lotteryCode)) {
            return;
        }
        
        $lotteryCode = $this->normalizeK3Code($lotteryCode);
        
        unset(self::$clients[$connectionId]['subscriptions'][$lotteryCode]);
        unset(self::$subscriptions[$lotteryCode][$connectionId]);
        
        $connection->send(json_encode([
            'type' => 'unsubscribed',
            'data' => ['lotteryCode' => $lotteryCode]
        ]));
    }
    
    
    protected function handleAuth(TcpConnection $connection, array $data)
    {
        $connectionId = $connection->id;
        $token = $data['token'] ?? '';
        
        if (empty($token)) {
            $connection->send(json_encode([
                'type' => 'auth_failed',
                'data' => ['message' => 'Tokenkhông được để trống']
            ]));
            return;
        }
        
        try {
            
            $config = config('jwt');
            $decoded = \Firebase\JWT\JWT::decode(
                $token, 
                new \Firebase\JWT\Key($config['secret'], $config['algo'])
            );
            
            $userId = $decoded->uid ?? 0;
            $username = $decoded->username ?? '';
            
            if ($userId <= 0) {
                throw new \Exception('无效的Người dùngID');
            }
            
            
            if (($decoded->type ?? '') === 'refresh') {
                throw new \Exception('请使用Access Token');
            }
            
            
            self::$clients[$connectionId]['user_id'] = $userId;
            
            
            $isNewOnline = !isset(self::$userConnections[$userId]) || empty(self::$userConnections[$userId]);
            
            
            if (!isset(self::$userConnections[$userId])) {
                self::$userConnections[$userId] = [];
            }
            self::$userConnections[$userId][$connectionId] = $connection;
            
            
            if ($isNewOnline) {
                $this->notifyFriendsOnline($userId);
            }
            
            Log::info("[WebSocket] Người dùngXác thựcThành công [JWT]: userId={$userId}, username={$username}, connectionId={$connectionId}");
            
            $connection->send(json_encode([
                'type' => 'auth_success',
                'data' => [
                    'message' => 'Xác thựcThành công',
                    'userId' => $userId,
                    'username' => $username
                ]
            ]));
            
        } catch (\Firebase\JWT\ExpiredException $e) {
            Log::warning("[WebSocket] Token已过期");
            $connection->send(json_encode([
                'type' => 'auth_failed',
                'data' => ['message' => 'Token已过期，请重新Đăng nhập']
            ]));
        } catch (\Exception $e) {
            Log::warning("[WebSocket] Xác thựcThất bại: " . $e->getMessage());
            $connection->send(json_encode([
                'type' => 'auth_failed',
                'data' => ['message' => 'Xác thựcThất bại: ' . $e->getMessage()]
            ]));
        }
    }
    
    
    protected function handleIMSend(TcpConnection $connection, array $data)
    {
        $connectionId = $connection->id;
        $userId = self::$clients[$connectionId]['user_id'] ?? 0;
        
        
        $tempId = $data['tempId'] ?? '';
        
        if (!$userId) {
            $connection->send(json_encode([
                'type' => 'im_error',
                'data' => ['message' => 'Vui lòng đăng nhập', 'tempId' => $tempId]
            ]));
            return;
        }
        
        $targetType = $data['targetType'] ?? 1;  
        $targetId = $data['targetId'] ?? 0;
        $content = $data['content'] ?? '';
        $msgType = $data['msgType'] ?? 1;
        
        if (empty($content) || !$targetId) {
            $connection->send(json_encode([
                'type' => 'im_error',
                'data' => ['message' => 'Tham số không hợp lệ', 'tempId' => $tempId]
            ]));
            return;
        }
        
        try {
            if ($targetType == 1) {
                $result = IMService::sendPrivateMessage($userId, $targetId, $content, $msgType);
            } else {
                $result = IMService::sendGroupMessage($userId, $targetId, $content, $msgType);
            }
            
            if ($result['success']) {
                
                $responseData = $result['data'];
                $responseData['tempId'] = $tempId;
                $responseData['messageId'] = $responseData['msgId'] ?? null;  
                
                $connection->send(json_encode([
                    'type' => 'im_sent',
                    'data' => $responseData
                ]));
            } else {
                $connection->send(json_encode([
                    'type' => 'im_error',
                    'data' => ['message' => $result['error'], 'tempId' => $tempId]
                ]));
            }
        } catch (\Exception $e) {
            Log::error('[IM] 发送Thất bại: ' . $e->getMessage());
            $connection->send(json_encode([
                'type' => 'im_error',
                'data' => ['message' => '发送Thất bại', 'tempId' => $tempId]
            ]));
        }
    }
    
    
    protected function handleIMRead(TcpConnection $connection, array $data)
    {
        $connectionId = $connection->id;
        $userId = self::$clients[$connectionId]['user_id'] ?? 0;
        
        if (!$userId) {
            return;
        }
        
        $targetType = $data['targetType'] ?? 1;
        $targetId = $data['targetId'] ?? 0;
        
        if ($targetId) {
            IMService::markAsRead($userId, $targetType, $targetId);
        }
    }
    
    
    protected function handleIMTyping(TcpConnection $connection, array $data)
    {
        $connectionId = $connection->id;
        $userId = self::$clients[$connectionId]['user_id'] ?? 0;
        
        if (!$userId) {
            return;
        }
        
        $targetType = $data['targetType'] ?? 1;
        $targetId = $data['targetId'] ?? 0;
        
        if (!$targetId) {
            return;
        }
        
        
        $sender = Db::table('caipiao_member')->where('id', $userId)->first();
        
        if ($targetType == 1) {
            
            self::pushToUser($targetId, 'im_typing', [
                'userId' => $userId,
                'nickname' => $sender->nickname ?? $sender->username ?? '',
            ]);
        } else {
            
            $members = Db::table('im_group_members')
                ->where('group_id', $targetId)
                ->where('uid', '!=', $userId)
                ->pluck('uid');
            
            foreach ($members as $uid) {
                self::pushToUser($uid, 'im_typing', [
                    'userId' => $userId,
                    'nickname' => $sender->nickname ?? $sender->username ?? '',
                    'groupId' => $targetId,
                ]);
            }
        }
    }
    
    
    protected function handleIMRecall(TcpConnection $connection, array $data)
    {
        $connectionId = $connection->id;
        $userId = self::$clients[$connectionId]['user_id'] ?? 0;
        
        if (!$userId) {
            $connection->send(json_encode([
                'type' => 'im_error',
                'data' => ['message' => 'Vui lòng đăng nhập']
            ]));
            return;
        }
        
        $msgId = $data['msgId'] ?? 0;
        if (!$msgId) {
            $connection->send(json_encode([
                'type' => 'im_error',
                'data' => ['message' => 'Tham số không hợp lệ']
            ]));
            return;
        }
        
        
        $msg = Db::table('im_messages')->where('id', $msgId)->first();
        if (!$msg) {
            $connection->send(json_encode([
                'type' => 'im_error',
                'data' => ['message' => 'Tin nhắnkhông tồn tại']
            ]));
            return;
        }
        
        
        if ($msg->from_uid != $userId) {
            $connection->send(json_encode([
                'type' => 'im_error',
                'data' => ['message' => '只能撤回自己的Tin nhắn']
            ]));
            return;
        }
        
        
        if (time() - $msg->created_at > 120) {
            $connection->send(json_encode([
                'type' => 'im_error',
                'data' => ['message' => '超过2分钟的Tin nhắn不能撤回']
            ]));
            return;
        }
        
        
        Db::table('im_messages')->where('id', $msgId)->update(['is_recall' => 1]);
        
        
        $connection->send(json_encode([
            'type' => 'im_recalled',
            'data' => ['msgId' => $msgId]
        ]));
        
        
        if ($msg->to_uid) {
            
            self::pushToUser($msg->to_uid, 'im_recall', [
                'msgId' => $msgId,
                'fromUid' => $userId,
            ]);
        } elseif ($msg->to_group) {
            
            $members = Db::table('im_group_members')
                ->where('group_id', $msg->to_group)
                ->where('uid', '!=', $userId)
                ->pluck('uid');
            
            foreach ($members as $uid) {
                self::pushToUser($uid, 'im_recall', [
                    'msgId' => $msgId,
                    'fromUid' => $userId,
                    'groupId' => $msg->to_group,
                ]);
            }
        }
    }
    
    
    protected function broadcastCountdown()
    {
        if (empty(self::$subscriptions)) {
            return;
        }
        
        $now = time();
        
        foreach (self::$subscriptions as $lotteryCode => $connections) {
            if (empty($connections)) {
                continue;
            }
            
            $info = $this->getCurrentIssueInfo($lotteryCode);
            if (!$info) {
                continue;
            }
            
            
            $message = json_encode([
                'type' => 'countdown',
                'data' => [
                    'lotteryCode' => $lotteryCode,
                    'lotteryName' => $info['lotteryName'],
                    'currentIssue' => $info['currentIssue'],
                    'countdown' => $info['countdown'],
                    'status' => $info['status'],
                    'endTime' => $info['endTime'],
                    'lastIssue' => $info['lastIssue'],
                    'lastOpenCode' => $info['lastOpenCode'],
                    'serverTime' => $now,
                ]
            ]);
            
            foreach ($connections as $connectionId => $connection) {
                try {
                    $connection->send($message);
                } catch (\Exception $e) {
                    
                }
            }
            
            $currentIssue = $info['currentIssue'];
            $sentKey = $lotteryCode . '_' . $currentIssue;
            $countdown = $info['countdown'];
            
            
            $caipiao = Db::table('caipiao_caipiao')->where('name', $lotteryCode)->first();
            $ftime = $caipiao->ftime ?? 10;
            
            
            $timeToSealed = $countdown - $ftime; 
            if ($info['status'] === 1 && $timeToSealed == 11) {
                
                $redis = Redis::connection('default');
                $preLockKey = "lottery_pre_lock:{$lotteryCode}:{$currentIssue}";
                if ($redis->setnx($preLockKey, 1)) {
                    $redis->expire($preLockKey, 120); 
                    self::$sentSealedNotices[$sentKey . '_pre'] = true;
                    $this->pushPreSealedNotice($lotteryCode, $currentIssue, $timeToSealed);
                    Log::info("[WebSocket] 封盘前Lưu ý: {$lotteryCode} 期号 {$currentIssue} 距封盘 {$timeToSealed} 秒");
                }
            }
            
            
            if ($info['status'] === 2) {
                
                $redis = Redis::connection('default');
                
                
                $sealedLockKey = "lottery_sealed_lock:{$lotteryCode}:{$currentIssue}";
                if ($redis->setnx($sealedLockKey, 1)) {
                    $redis->expire($sealedLockKey, 120); 
                    self::$sentSealedNotices[$sentKey] = true;
                    $this->pushSealedLineNotice($lotteryCode, $currentIssue);
                    Log::info("[WebSocket] 封盘线Lưu ý: {$lotteryCode} 期号 {$currentIssue}");
                }
                
                
                $billLockKey = "lottery_bill_lock:{$lotteryCode}:{$currentIssue}";
                if ($redis->setnx($billLockKey, 1)) {
                    $redis->expire($billLockKey, 120); 
                    self::$sentBillIssues[$sentKey] = true;
                    
                    
                    $botConfig = $this->getChatBotConfig();
                    if ($botConfig['bill_enabled']) {
                    $this->pushBotBillMessage($lotteryCode, $currentIssue);
                    Log::info("[WebSocket] 封盘推送账单: {$lotteryCode} 期号 {$currentIssue}");
                    }
                    
                    
                    if ($botConfig['draw_notice_enabled']) {
                    $this->pushDrawComingNotice($lotteryCode, $currentIssue);
                    }
                }
            }
        }
    }
    
    
    protected function pushPreSealedNotice(string $lotteryCode, string $issue, int $secondsToSealed): void
    {
        
        $botConfig = $this->getChatBotConfig();
        $msgTemplate = $botConfig['msg_pre_sealed'];
        
        
        for ($i = 0; $i < 2; $i++) {
            $content = str_replace('{seconds}', $secondsToSealed, $msgTemplate);
            $msgId = 'sys_' . time() . '_' . mt_rand(100000, 999999);
            
            $chatMsg = [
                'id' => $msgId,
                'lottery_code' => $lotteryCode,
                'issue' => $issue,
                'userId' => 0,
                'userName' => '系统Công bố',
                'avatar' => '',
                'honorLevel' => 0,
                'content' => $content,
                'messageType' => 'system',
                'time' => date('Y-m-d H:i:s'),
                'isSystem' => true,
                'isWin' => false,
                'source' => 'system'
            ];
            
            
            $this->saveSystemMessageToRedis($lotteryCode, $chatMsg);
            
            self::broadcastLotteryChat($lotteryCode, $chatMsg);
        }
    }
    
    
    protected function saveSystemMessageToRedis(string $lotteryCode, array $chatMsg): void
    {
        try {
            $redis = Redis::connection('default');
            $cacheKey = "lottery_chat_system:{$lotteryCode}";
            $redis->lPush($cacheKey, json_encode($chatMsg));
            $redis->lTrim($cacheKey, 0, 19); 
            
            
            $lottery = Db::table('caipiao_caipiao')->where('name', $lotteryCode)->first();
            $kjsj = $lottery->kjsj ?? 60; 
            $expireTime = max(120, $kjsj * 2); 
            $redis->expire($cacheKey, $expireTime);
        } catch (\Exception $e) {
            Log::warning("[WebSocket] Lưu系统Tin nhắn到RedisThất bại: " . $e->getMessage());
        }
    }
    
    
    protected function pushSealedLineNotice(string $lotteryCode, string $issue): void
    {
        
        $botConfig = $this->getChatBotConfig();
        $robotName = $botConfig['robot_name'];
        
        
        $sealedLineMsg = str_replace('{robot_name}', $robotName, $botConfig['msg_sealed_line']);
        $noTalkMsg = $botConfig['msg_no_talk'];
        
        $messages = [
            $sealedLineMsg,
            $noTalkMsg
        ];
        
        foreach ($messages as $content) {
            
            $msgId = 'sys_' . time() . '_' . mt_rand(100000, 999999);
            
            $chatMsg = [
                'id' => $msgId,
                'lottery_code' => $lotteryCode,
                'issue' => $issue,
                'userId' => 0,
                'userName' => '系统Công bố',
                'avatar' => '',
                'honorLevel' => 0,
                'content' => $content,
                'messageType' => 'system',
                'time' => date('Y-m-d H:i:s'),
                'isSystem' => true,
                'isWin' => false,
                'source' => 'system'
            ];
            
            
            $this->saveSystemMessageToRedis($lotteryCode, $chatMsg);
            
            
            self::broadcastLotteryChat($lotteryCode, $chatMsg);
        }
    }
    
    
    protected function checkAndBroadcastResults()
    {
        if (empty(self::$subscriptions)) {
            return;
        }
        
        foreach (self::$subscriptions as $lotteryCode => $connections) {
            if (empty($connections)) {
                continue;
            }
            
            
            $lastDraw = Db::table('caipiao_kaijiang')
                ->where('name', $lotteryCode)
                ->where('opentime', '<=', time())
                ->where('opencode', '!=', '')
                ->orderBy('id', 'desc')
                ->first();
            
            if (!$lastDraw) {
                continue;
            }
            
            
            if (!$this->tryMarkIssuePushed($lotteryCode, $lastDraw->expect)) {
                continue;
            }
            
            
            self::$lastIssues[$lotteryCode] = $lastDraw->expect;
            
            
            $openCode = [];
            if (!empty($lastDraw->opencode)) {
                $codes = explode(',', $lastDraw->opencode);
                $openCode = array_map('intval', array_slice($codes, 0, 3));
            }
            
            
            $sum = array_sum($openCode);
            
            
            $isBig = $sum >= 14;
            $isOdd = $sum % 2 === 1;
            $resultType = ($isBig ? '大' : '小') . ($isOdd ? '单' : '双');
            
            $message = json_encode([
                'type' => 'draw_result',
                'data' => [
                    'lotteryCode' => $lotteryCode,
                    'issue' => $lastDraw->expect,
                    'openCode' => $openCode,
                    'openTime' => date('Y-m-d H:i:s', $lastDraw->opentime),
                    'sum' => $sum,
                    'size' => $isBig ? 'big' : 'small',
                    'oddEven' => $isOdd ? 'odd' : 'even',
                    'type' => $resultType,
                ]
            ]);
            
            Log::info("[WebSocket] 推送Mở thưởng: {$lotteryCode} 期号 {$lastDraw->expect}");
            
            foreach ($connections as $connectionId => $connection) {
                try {
                    $connection->send($message);
                } catch (\Exception $e) {
                    
                }
            }
            
            
            
            
            $botConfig = $this->getChatBotConfig();
            if ($botConfig['result_enabled']) {
            $this->pushBotDrawResult($lotteryCode, $lastDraw->expect, $openCode, $sum, $resultType);
            }
            
            
            $this->pushWinNotifications($lotteryCode, $lastDraw->expect);
        }
    }
    
    
    protected function getCurrentIssueInfo(string $lotteryCode): ?array
    {
        try {
            $caipiao = Db::table('caipiao_caipiao')
                ->where('name', $lotteryCode)
                ->first();
            
            if (!$caipiao) {
                Log::warning("[WebSocket] 彩种không tồn tại: {$lotteryCode}");
                return null;
            }
            
            
            if ($caipiao->typeid === 'dwc') {
                return $this->getAnimalLotteryInfo($caipiao);
            }
            
            $now = time();
            $issueInfo = $this->calculateCurrentIssue($caipiao, $now);
            
            
            $closeSeconds = $caipiao->ftime ?? 10;
            $status = 1; 
            if ($issueInfo['remainTime'] <= $closeSeconds) {
                $status = 2; 
            }
            
            
            $lastResult = Db::table('caipiao_kaijiang')
                ->where('name', $lotteryCode)
                ->where('expect', '<', $issueInfo['currFullExpect'])
                ->where('opentime', '<=', time())  
                ->where('opencode', '!=', '')
                ->orderBy('expect', 'desc')
                ->first();
            
            $lastOpenCode = null;
            $lastIssue = null;
            if ($lastResult) {
                $lastOpenCode = $lastResult->opencode;
                $lastIssue = $lastResult->expect;
            }
            
            
            if ($issueInfo['isClosed'] ?? false) {
                $status = 0; 
            }
            
            return [
                'lotteryName' => $caipiao->title,
                'currentIssue' => $issueInfo['currFullExpect'],
                'countdown' => max(0, $issueInfo['remainTime']),
                'status' => $status,  
                'endTime' => $issueInfo['issueTime'] + $caipiao->expecttime * 60,
                'lastIssue' => $lastIssue,
                'lastOpenCode' => $lastOpenCode,
            ];
            
        } catch (\Exception $e) {
            Log::error("[WebSocket] Lấy期号信息Thất bại: " . $e->getMessage());
            return null;
        }
    }
    
    
    protected function calculateCurrentIssue($caipiao, $now): array
    {
        
        $currentIssue = Db::table('caipiao_kaijiang')
            ->where('name', $caipiao->name)
            ->where('opentime', '>', $now)  
            ->orderBy('opentime', 'asc')    
            ->first();
        
        if ($currentIssue) {
            $remainTime = $currentIssue->opentime - $now;
            $currExpect = substr($currentIssue->expect, -4);
            
            return [
                'currExpect' => $currExpect,
                'currFullExpect' => $currentIssue->expect,
                'remainTime' => max(0, $remainTime),
                'issueTime' => $currentIssue->opentime,
                'isClosed' => false
            ];
        }
        
        
        $today = date('Y-m-d', $now);
        $firsttime = strtotime($today . ' ' . ($caipiao->firsttime ?: '00:00:00'));
        
        $expecttime = $caipiao->expecttime;
        $isClosed = false;
        
        $is24Hour = empty($caipiao->endtime);
        if ($is24Hour) {
            $endtime = $firsttime + 86400 - 1;
        } else {
            $endtime = strtotime($today . ' ' . $caipiao->endtime);
            if ($endtime <= $firsttime) {
                $endtime += 86400;
            }
        }
        
        if (!$is24Hour && $now < $firsttime) {
            $currIssueNum = 1;
            $issueTime = $firsttime;
            $remainTime = $firsttime - $now;
            $isClosed = true;
        } else if (!$is24Hour && $now > $endtime) {
            $currIssueNum = 1;
            $issueTime = $firsttime + 86400;
            $remainTime = $issueTime - $now;
            $isClosed = true;
        } else {
            $passedMinutes = ($now - $firsttime) / 60;
            if ($passedMinutes < 0) {
                $passedMinutes += 1440;
            }
            $currIssueNum = floor($passedMinutes / $expecttime) + 1;
            $issueTime = $firsttime + ($currIssueNum - 1) * $expecttime * 60;
            $remainTime = $issueTime + $expecttime * 60 - $now;
        }
        
        $datePrefix = date('Ymd', $issueTime);
        $currExpect = str_pad($currIssueNum, 4, '0', STR_PAD_LEFT);
        $currFullExpect = $datePrefix . $currExpect;
        
        return [
            'currExpect' => $currExpect,
            'currFullExpect' => $currFullExpect,
            'remainTime' => max(0, $remainTime),
            'issueTime' => $issueTime,
            'isClosed' => $isClosed
        ];
    }
    
    
    protected function getAnimalLotteryInfo($caipiao): ?array
    {
        static $animalApiCache = [];
        static $animalApiCacheTime = [];
        
        $name = $caipiao->name;
        $cacheKey = $name;
        $cacheTtl = 2; 
        
        
        if (isset($animalApiCache[$cacheKey]) && 
            isset($animalApiCacheTime[$cacheKey]) && 
            time() - $animalApiCacheTime[$cacheKey] < $cacheTtl) {
            return $animalApiCache[$cacheKey];
        }
        
        try {
            
            $gameIdMap = [
                'yfdwc' => 'OG1ANL',
                'sfdwc' => 'OG3ANL',
                'wfdwc' => 'OG5ANL',
            ];
            $gameId = $gameIdMap[$name] ?? 'OG1ANL';
            $ftime = (int)$caipiao->ftime ?: 10;
            
            
            $client = new \GuzzleHttp\Client(['timeout' => 5, 'verify' => false]);
            $response = $client->post('https://sdjwchartjbda.t4nmvxp8jsgq.net/gameChart/getLotteryPlanForCocos/json', [
                'json' => ['gameId' => $gameId]
            ]);
            $result = json_decode($response->getBody()->getContents(), true);
            
            if ($result['code'] !== 'success' || !isset($result['data'])) {
                return null;
            }
            
            $data = $result['data'];
            $apiTime = isset($data['time']) ? strtotime($data['time']) : time();
            $endTime = strtotime($data['endTime']);
            $issueNo = $data['issueNo'] ?? '';
            
            $remainTime = max(0, $endTime - $apiTime);
            $guessEndTime = $endTime - $ftime;
            $guessRemainTime = max(0, $guessEndTime - $apiTime);
            
            
            $status = $guessRemainTime > 0 ? 1 : 2;
            
            
            $lastResult = Db::table('caipiao_kaijiang')
                ->where('name', $name)
                ->where('expect', '<', $issueNo)
                ->where('opencode', '!=', '')
                ->orderBy('id', 'desc')
                ->first();
            
            $info = [
                'lotteryName' => $caipiao->title,
                'currentIssue' => $issueNo,
                'countdown' => $remainTime,
                'status' => $status,
                'endTime' => $endTime,
                'guessRemainTime' => $guessRemainTime,
                'lastIssue' => $lastResult ? $lastResult->expect : null,
                'lastOpenCode' => $lastResult ? $lastResult->opencode : null,
            ];
            
            
            $animalApiCache[$cacheKey] = $info;
            $animalApiCacheTime[$cacheKey] = time();
            
            return $info;
            
        } catch (\Exception $e) {
            Log::error("[WebSocket] Lấy动物彩信息Thất bại: " . $e->getMessage());
            return null;
        }
    }
    
    
    protected function normalizeK3Code(string $code): string
    {
        if (preg_match('/^(\d+)(f)(k3)$/i', $code, $matches)) {
            return 'f' . $matches[1] . 'k3';
        }
        return strtolower($code);
    }
    
    
    protected function tryMarkIssuePushed(string $lotteryCode, string $issue): bool
    {
        static $redis = null;
        
        try {
            if ($redis === null) {
                $redisConfig = config('redis.default', []);
                $redis = new \Redis();
                $redis->connect(
                    $redisConfig['host'] ?? '127.0.0.1',
                    $redisConfig['port'] ?? 6379,
                    $redisConfig['timeout'] ?? 1.0
                );
                if (!empty($redisConfig['password'])) {
                    $redis->auth($redisConfig['password']);
                }
                if (!empty($redisConfig['database'])) {
                    $redis->select($redisConfig['database']);
                }
            }
            
            $key = "websocket:pushed:{$lotteryCode}";
            
            
            $currentValue = $redis->get($key);
            if ($currentValue === $issue) {
                return false; 
            }
            
            
            
            $redis->watch($key);
            $redis->multi();
            $redis->set($key, $issue, ['EX' => 300]); 
            $result = $redis->exec();
            
            
            return $result !== false;
            
        } catch (\Exception $e) {
            Log::error("[WebSocket] Redis 标记期号Thất bại: " . $e->getMessage());
            
            $lastIssue = self::$lastIssues[$lotteryCode] ?? '';
            return $lastIssue !== $issue;
        }
    }
    
    
    public static function broadcast(string $lotteryCode, string $type, array $data)
    {
        $lotteryCode = strtolower($lotteryCode);
        
        if (!isset(self::$subscriptions[$lotteryCode])) {
            return;
        }
        
        $message = json_encode([
            'type' => $type,
            'data' => $data
        ]);
        
        foreach (self::$subscriptions[$lotteryCode] as $connection) {
            try {
                $connection->send($message);
            } catch (\Exception $e) {
                
            }
        }
    }
    
    
    public static function getStats(): array
    {
        $subscriptionStats = [];
        foreach (self::$subscriptions as $code => $connections) {
            $subscriptionStats[$code] = count($connections);
        }
        
        return [
            'totalConnections' => count(self::$clients),
            'authenticatedUsers' => count(self::$userConnections),
            'subscriptions' => $subscriptionStats,
        ];
    }
    
    
    
    
    public static function pushToUser(int $userId, string $type, array $data): int
    {
        if (!isset(self::$userConnections[$userId])) {
            return 0;
        }
        
        $message = json_encode([
            'type' => $type,
            'data' => $data
        ]);
        
        $successCount = 0;
        foreach (self::$userConnections[$userId] as $connection) {
            try {
                $connection->send($message);
                $successCount++;
            } catch (\Exception $e) {
                
            }
        }
        
        return $successCount;
    }
    
    
    public static function pushToUsers(array $userIds, string $type, array $data): void
    {
        foreach ($userIds as $userId) {
            self::pushToUser($userId, $type, $data);
        }
    }
    
    
    public static function broadcastToAll(string $type, array $data): void
    {
        $message = json_encode([
            'type' => $type,
            'data' => $data
        ]);
        
        foreach (self::$clients as $client) {
            try {
                $client['conn']->send($message);
            } catch (\Exception $e) {
                
            }
        }
    }
    
    
    
    
    protected function handleLotteryChatSend(TcpConnection $connection, array $data)
    {
        $connectionId = $connection->id;
        $userId = self::$clients[$connectionId]['user_id'] ?? 0;
        
        if (!$userId) {
            $connection->send(json_encode([
                'type' => 'lottery_chat_error',
                'data' => ['message' => 'Vui lòng đăng nhập']
            ]));
            return;
        }
        
        $lotteryCode = $data['lotteryCode'] ?? '';
        $content = $data['content'] ?? '';
        $messageType = $data['messageType'] ?? 'text'; 
        
        if (empty($lotteryCode) || empty($content)) {
            $connection->send(json_encode([
                'type' => 'lottery_chat_error',
                'data' => ['message' => 'Tham số không hợp lệ']
            ]));
            return;
        }
        
        try {
            
            $user = Db::table('caipiao_member')->where('id', $userId)->first();
            if (!$user) {
                $connection->send(json_encode([
                    'type' => 'lottery_chat_error',
                    'data' => ['message' => 'Người dùng không tồn tại']
                ]));
                return;
            }
            
            
            $msgId = Db::table('caipiao_lottery_chat')->insertGetId([
                'lottery_code' => $lotteryCode,
                'issue' => $data['issue'] ?? '',
                'user_id' => $userId,
                'user_name' => $user->nickname ?? $user->username ?? 'Người dùng' . substr($userId, -4),
                'avatar' => $user->face ?? '',
                'honor_level' => $this->calculateHonorLevel($userId),
                'content' => $content,
                'message_type' => $messageType,
                'is_win' => 0,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            
            $chatMsg = [
                'id' => $msgId,
                'userId' => $userId,
                'userName' => $user->nickname ?? $user->username ?? '',
                'avatar' => $user->face ?? '',
                'honorLevel' => $this->calculateHonorLevel($userId),
                'content' => $content,
                'messageType' => $messageType,
                'time' => date('H:i:s'),
                'isSystem' => false,
                'isWin' => false
            ];
            
            
            $connection->send(json_encode([
                'type' => 'lottery_chat_sent',
                'data' => $chatMsg
            ]));
            
            
            self::broadcastLotteryChat($lotteryCode, $chatMsg);
            
        } catch (\Exception $e) {
            Log::error('[WebSocket] Xổ số聊天发送Thất bại: ' . $e->getMessage());
            $connection->send(json_encode([
                'type' => 'lottery_chat_error',
                'data' => ['message' => '发送Thất bại']
            ]));
        }
    }
    
    
    protected function calculateHonorLevel($userId): int
    {
        $totalBet = Db::table('caipiao_bet_orders')
            ->where('user_id', $userId)
            ->sum('total_amount') ?? 0;
        
        if ($totalBet >= 1000000) return 7;
        if ($totalBet >= 500000) return 6;
        if ($totalBet >= 100000) return 5;
        if ($totalBet >= 50000) return 4;
        if ($totalBet >= 10000) return 3;
        if ($totalBet >= 5000) return 2;
        if ($totalBet >= 1000) return 1;
        return 0;
    }
    
    
    public static function broadcastLotteryChat(string $lotteryCode, array $chatMsg): void
    {
        try {
            
            $redis = Redis::connection('default');
            $broadcastMsg = [
                'target' => 'lottery',
                'lotteryCode' => $lotteryCode,
                'type' => 'lottery_chat_message',
                'data' => [
                    'lotteryCode' => $lotteryCode,
                    'message' => $chatMsg
                ]
            ];
            $redis->rPush('websocket_push_queue', json_encode($broadcastMsg));
        } catch (\Exception $e) {
            Log::warning("[WebSocket] 广播聊天Tin nhắnThất bại: " . $e->getMessage());
            
            self::broadcastLotteryChatLocal($lotteryCode, $chatMsg);
        }
    }
    
    
    protected static function broadcastLotteryChatLocal(string $lotteryCode, array $chatMsg): void
    {
        if (!isset(self::$subscriptions[$lotteryCode])) {
            return;
        }
        
        $message = json_encode([
            'type' => 'lottery_chat_message',
            'data' => [
                'lotteryCode' => $lotteryCode,
                'message' => $chatMsg
            ]
        ]);
        
        foreach (self::$subscriptions[$lotteryCode] as $connectionId => $connection) {
            try {
                $connection->send($message);
            } catch (\Exception $e) {
                
            }
        }
    }
    
    
    public static function broadcastLotteryResult(string $lotteryCode, array $resultData): void
    {
        $chatMsg = [
            'id' => 0,
            'userId' => 0,
            'userName' => '系统',
            'avatar' => '',
            'honorLevel' => 0,
            'content' => sprintf('第 %s 期Mở thưởng结果：%s = %d (%s)',
                $resultData['issue'] ?? '',
                implode('+', $resultData['openCode'] ?? []),
                $resultData['sum'] ?? 0,
                $resultData['type'] ?? ''
            ),
            'messageType' => 'result',
            'time' => date('H:i:s'),
            'isSystem' => true,
            'isWin' => false,
            'resultData' => $resultData
        ];
        
        self::broadcastLotteryChat($lotteryCode, $chatMsg);
    }
    
    
    protected function pushDrawComingNotice(string $lotteryCode, string $issue): void
    {
        $noticeKey = $lotteryCode . '_' . $issue;
        
        
        if (isset(self::$sentDrawNotices[$noticeKey])) {
            return;
        }
        self::$sentDrawNotices[$noticeKey] = true;
        
        
        $botConfig = $this->getChatBotConfig();
        
        
        $lottery = Db::table('caipiao_caipiao')->where('name', $lotteryCode)->first();
        $lotteryName = $lottery->title ?? 'Xổ số';
        
        
        $content = str_replace('{lottery_name}', $lotteryName, $botConfig['msg_draw_coming']);
        $msgId = 'sys_' . time() . '_' . mt_rand(100000, 999999);
        
        $chatMsg = [
            'id' => $msgId,
            'lottery_code' => $lotteryCode,
            'issue' => $issue,
            'userId' => 0,
            'userName' => '系统Công bố',
            'avatar' => '',
            'honorLevel' => 0,
            'content' => $content,
            'messageType' => 'system',
            'time' => date('Y-m-d H:i:s'),
            'isSystem' => true,
            'isWin' => false,
            'source' => 'system'
        ];
        
        
        $this->saveSystemMessageToRedis($lotteryCode, $chatMsg);
        
        self::broadcastLotteryChat($lotteryCode, $chatMsg);
        Log::info("[WebSocket] Mở thưởngLưu ý: {$lotteryCode} 期号 {$issue}");
    }
    
    
    protected function pushBotDrawResult(string $lotteryCode, string $issue, array $openCode, int $sum, string $resultType): void
    {
        
        $botConfig = $this->getChatBotConfig();
        $robotName = $botConfig['robot_name'];
        $robotAvatar = $botConfig['robot_avatar'];
        
        
        $contentText = sprintf('第 %s 期Mở thưởng结果：%s = %d (%s)', $issue, implode('+', $openCode), $sum, $resultType);
        
        
        $extraData = json_encode([
            'issue' => $issue,
            'code' => $openCode,
            'sum' => $sum,
            'type' => $resultType
        ]);
        
        try {
            $insertId = Db::table('caipiao_lottery_chat')->insertGetId([
                'lottery_code' => $lotteryCode,
                'issue' => $issue,
                'user_id' => 0,
                'user_name' => $robotName,
                'avatar' => $robotAvatar,
                'honor_level' => 0,
                'content' => $contentText,
                'message_type' => 'result',
                'is_win' => 0,
                'extra_data' => $extraData,
                'created_at' => date('Y-m-d H:i:s')
            ]);
        } catch (\Exception $e) {
            $insertId = time() . rand(1000, 9999);
            Log::error("[WebSocket] 存储Mở thưởngTin nhắnThất bại: " . $e->getMessage());
        }
        
        $chatMsg = [
            'id' => $insertId,
            'userId' => 0,
            'userName' => $robotName,
            'avatar' => $robotAvatar,
            'honorLevel' => 0,
            'content' => $contentText,
            'messageType' => 'result',
            'time' => date('H:i:s'),
            'isSystem' => false,
            'isWin' => false,
            'issue' => $issue,
            'code' => $openCode,
            'sum' => $sum,
            'type' => $resultType
        ];
        
        self::broadcastLotteryChat($lotteryCode, $chatMsg);
        Log::info("[WebSocket] 机器人推送Mở thưởng: {$lotteryCode} 期号 {$issue}");
    }
    
    
    protected function pushWinNotifications(string $lotteryCode, string $issue): void
    {
        try {
            
            $winBets = Db::table('caipiao_touzhu')
                ->where('cpname', $lotteryCode)
                ->where('expect', $issue)
                ->where('isdraw', 1) 
                ->where('okamount', '>', 0) 
                ->where(function($query) {
                    $query->whereNull('source')
                          ->orWhere('source', '!=', 'robot');
                })
                ->get();
            
            if ($winBets->isEmpty()) {
                return;
            }
            
            
            $userWins = [];
            foreach ($winBets as $bet) {
                $uid = $bet->uid;
                if (!isset($userWins[$uid])) {
                    $userWins[$uid] = [
                        'totalWin' => 0,
                        'totalBet' => 0,
                        'items' => []
                    ];
                }
                $userWins[$uid]['totalWin'] += $bet->okamount;
                $userWins[$uid]['totalBet'] += $bet->amount;
                $userWins[$uid]['items'][] = [
                    'playTitle' => $bet->playtitle ?? '',
                    'betAmount' => $bet->amount,
                    'winAmount' => $bet->okamount
                ];
            }
            
            
            foreach ($userWins as $userId => $winData) {
                $profit = $winData['totalWin'] - $userWins[$userId]['totalBet'];
                
                self::pushToUser($userId, 'lottery_win', [
                    'lotteryCode' => $lotteryCode,
                    'issue' => $issue,
                    'totalBet' => $winData['totalBet'],
                    'totalWin' => $winData['totalWin'],
                    'profit' => $profit,
                    'items' => $winData['items']
                ]);
                
                Log::info("[WebSocket] 推送Trúng thưởngThông báo: userId={$userId}, issue={$issue}, win={$winData['totalWin']}");
            }
            
        } catch (\Exception $e) {
            Log::error("[WebSocket] 推送Trúng thưởngThông báoThất bại: " . $e->getMessage());
        }
    }
    
    
    protected function pushBotBillMessage(string $lotteryCode, string $issue): void
    {
        
        $botConfig = $this->getChatBotConfig();
        $robotName = $botConfig['robot_name'];
        $robotAvatar = $botConfig['robot_avatar'];
        
        try {
            
            
            $bets = Db::table('caipiao_touzhu')
                ->select([
                    'uid',
                    'username',
                    Db::raw('SUM(amount) as total_amount'),
                    Db::raw('GROUP_CONCAT(CONCAT(playtitle, amount) SEPARATOR "/") as bet_details')
                ])
                ->where('cpname', $lotteryCode)
                ->where('expect', $issue)
                ->groupBy('uid', 'username')
                ->orderBy('total_amount', 'desc')
                ->limit(50) 
                ->get();
            
            if (count($bets) == 0) {
                
                $this->pushEmptyBillMessage($lotteryCode, $issue);
                return;
            }
            
            
            $billDetails = [];
            foreach ($bets as $bet) {
                
                $details = $bet->bet_details ?? '';
                $betItems = [];
                
                
                $userBets = Db::table('caipiao_touzhu')
                    ->where('uid', $bet->uid)
                    ->where('cpname', $lotteryCode)
                    ->where('expect', $issue)
                    ->get();
                
                foreach ($userBets as $ub) {
                    
                    $tzcode = $ub->tzcode ?? '';
                    if (strpos($tzcode, '[') === 0) {
                        
                        $items = json_decode($tzcode, true);
                        if (is_array($items)) {
                            foreach ($items as $item) {
                                $betItems[] = [
                                    'label' => $item['label'] ?? $item['title'] ?? $item['playid'] ?? '',
                                    'amount' => $item['amount'] ?? 0
                                ];
                            }
                        }
                    } else {
                        
                        $betItems[] = [
                            'label' => $ub->playtitle ?? $tzcode,
                            'amount' => $ub->amount ?? 0
                        ];
                    }
                }
                
                $billDetails[] = [
                    'name' => $bet->username ?? 'Người dùng' . substr($bet->uid, -4),
                    'total' => floatval($bet->total_amount),
                    'bets' => $betItems
                ];
            }
            
            
            $extraData = json_encode([
                'issue' => $issue,
                'details' => $billDetails
            ]);
            
            
            $totalUsers = count($billDetails);
            $totalAmount = array_sum(array_column($billDetails, 'total'));
            $contentSummary = sprintf('第 %s 期账单：共 %d 人Đặt cược，总Số tiền %.2f 元', $issue, $totalUsers, $totalAmount);
            
            try {
                $insertId = Db::table('caipiao_lottery_chat')->insertGetId([
                    'lottery_code' => $lotteryCode,
                    'issue' => $issue,
                    'user_id' => 0,
                    'user_name' => $robotName,
                    'avatar' => $robotAvatar,
                    'honor_level' => 0,
                    'content' => $contentSummary,
                    'message_type' => 'bill',
                    'is_win' => 0,
                    'extra_data' => $extraData,
                    'created_at' => date('Y-m-d H:i:s')
                ]);
            } catch (\Exception $e) {
                $insertId = time() . rand(1000, 9999);
                Log::error("[WebSocket] 存储账单Tin nhắnThất bại: " . $e->getMessage());
            }
            
            
            $chatMsg = [
                'id' => $insertId,
                'userId' => 0,
                'userName' => $robotName,
                'avatar' => $robotAvatar,
                'honorLevel' => 0,
                'content' => $contentSummary,
                'messageType' => 'bill',
                'time' => date('H:i:s'),
                'isSystem' => false,
                'isWin' => false,
                'issue' => $issue,
                'details' => $billDetails
            ];
            
            self::broadcastLotteryChat($lotteryCode, $chatMsg);
            
            Log::info("[WebSocket] 机器人推送账单: {$lotteryCode} 期号 {$issue}, 共 " . count($billDetails) . " 条");
            
        } catch (\Exception $e) {
            Log::error("[WebSocket] 推送账单Thất bại: " . $e->getMessage());
        }
    }
    
    
    protected function pushEmptyBillMessage(string $lotteryCode, string $issue): void
    {
        
        $botConfig = $this->getChatBotConfig();
        $robotName = $botConfig['robot_name'];
        $robotAvatar = $botConfig['robot_avatar'];
        
        try {
            $contentSummary = sprintf('第 %s 期账单：本期无Đặt cược', $issue);
            
            $extraData = json_encode([
                'issue' => $issue,
                'details' => []
            ]);
            
            try {
                $insertId = Db::table('caipiao_lottery_chat')->insertGetId([
                    'lottery_code' => $lotteryCode,
                    'issue' => $issue,
                    'user_id' => 0,
                    'user_name' => $robotName,
                    'avatar' => $robotAvatar,
                    'honor_level' => 0,
                    'content' => $contentSummary,
                    'message_type' => 'bill',
                    'is_win' => 0,
                    'extra_data' => $extraData,
                    'created_at' => date('Y-m-d H:i:s')
                ]);
            } catch (\Exception $e) {
                $insertId = time() . rand(1000, 9999);
                Log::error("[WebSocket] 存储空账单Tin nhắnThất bại: " . $e->getMessage());
            }
            
            
            $chatMsg = [
                'id' => $insertId,
                'userId' => 0,
                'userName' => $robotName,
                'avatar' => $robotAvatar,
                'honorLevel' => 0,
                'content' => $contentSummary,
                'messageType' => 'bill',
                'time' => date('H:i:s'),
                'isSystem' => false,
                'isWin' => false,
                'issue' => $issue,
                'details' => []
            ];
            
            self::broadcastLotteryChat($lotteryCode, $chatMsg);
            
        } catch (\Exception $e) {
            Log::error("[WebSocket] 推送空账单Thất bại: " . $e->getMessage());
        }
    }
    
    
    public static function broadcastOnlineCount(string $lotteryCode): void
    {
        $count = isset(self::$subscriptions[$lotteryCode]) 
            ? count(self::$subscriptions[$lotteryCode]) 
            : 0;
        
        $message = json_encode([
            'type' => 'lottery_chat_online',
            'data' => [
                'lotteryCode' => $lotteryCode,
                'count' => $count
            ]
        ]);
        
        if (isset(self::$subscriptions[$lotteryCode])) {
            foreach (self::$subscriptions[$lotteryCode] as $connection) {
                try {
                    $connection->send($message);
                } catch (\Exception $e) {
                    
                }
            }
        }
    }
    
    
    
    
    public static function pushBetSettled(int $userId, array $data): void
    {
        $payload = [
            'orderId' => $data['orderId'] ?? '',
            'lotteryCode' => $data['lotteryCode'] ?? '',
            'issue' => $data['issue'] ?? '',
            'status' => $data['status'] ?? 'lose', 
            'betAmount' => floatval($data['betAmount'] ?? 0),
            'winAmount' => floatval($data['winAmount'] ?? 0),
            'profit' => floatval($data['winAmount'] ?? 0) - floatval($data['betAmount'] ?? 0),
            'settleTime' => $data['settleTime'] ?? time(),
        ];
        
        $count = self::pushToUser($userId, 'bet_settled', $payload);
        
        if ($count > 0) {
            Log::info("[WebSocket] 推送Đặt cược结算: userId={$userId}, orderId={$payload['orderId']}, status={$payload['status']}");
        }
    }
    
    
    
    
    public static function pushBalanceUpdate(int $userId, array $data): void
    {
        $payload = [
            'balance' => floatval($data['balance'] ?? 0),
            'change' => floatval($data['change'] ?? 0),
            'reason' => $data['reason'] ?? 'other',
            'orderId' => $data['orderId'] ?? '',
            'time' => $data['time'] ?? time(),
        ];
        
        $count = self::pushToUser($userId, 'balance_update', $payload);
        
        if ($count > 0) {
            Log::info("[WebSocket] 推送Số dư变动: userId={$userId}, change={$payload['change']}, reason={$payload['reason']}");
        }
    }
    
    
    
    
    public static function pushNotificationToAll(array $data): void
    {
        $payload = [
            'id' => $data['id'] ?? 0,
            'title' => $data['title'] ?? '',
            'content' => $data['content'] ?? '',
            'msgType' => $data['msgType'] ?? 'system', 
            'level' => $data['level'] ?? 'info', 
            'time' => $data['time'] ?? time(),
            'url' => $data['url'] ?? '',
        ];
        
        self::broadcastToAll('notification', $payload);
        Log::info("[WebSocket] 广播系统Thông báo: title={$payload['title']}");
    }
    
    
    public static function pushNotificationToUser(int $userId, array $data): void
    {
        $payload = [
            'id' => $data['id'] ?? 0,
            'title' => $data['title'] ?? '',
            'content' => $data['content'] ?? '',
            'msgType' => 'personal',
            'level' => $data['level'] ?? 'info',
            'time' => $data['time'] ?? time(),
            'url' => $data['url'] ?? '',
        ];
        
        self::pushToUser($userId, 'notification', $payload);
    }
    
    
    
    
    public static function pushLotteryStatus(string $lotteryCode, array $data): void
    {
        $lotteryCode = strtolower($lotteryCode);
        
        $payload = [
            'lotteryCode' => $lotteryCode,
            'status' => $data['status'] ?? 'normal', 
            'message' => $data['message'] ?? '',
            'resumeTime' => $data['resumeTime'] ?? 0,
        ];
        
        
        self::broadcast($lotteryCode, 'lottery_status', $payload);
        Log::info("[WebSocket] 推送彩种状态: {$lotteryCode} -> {$payload['status']}");
    }
    
    
    public static function isUserOnline(int $userId): bool
    {
        return isset(self::$userConnections[$userId]) && !empty(self::$userConnections[$userId]);
    }
    
    
    public static function getUserConnectionCount(int $userId): int
    {
        return isset(self::$userConnections[$userId]) ? count(self::$userConnections[$userId]) : 0;
    }
    
    
    
    protected function handleRefreshPlatformBalances(TcpConnection $connection)
    {
        $connectionId = $connection->id;
        $userId = self::$clients[$connectionId]['user_id'] ?? 0;
        
        if (!$userId) {
            $connection->send(json_encode([
                'type' => 'error',
                'data' => ['message' => 'Vui lòng đăng nhập']
            ]));
            return;
        }
        
        \Workerman\Timer::add(0.001, function() use ($connection, $userId) {
            $this->doRefreshPlatformBalances($connection, $userId);
        }, [], false);
    }
    
    protected function doRefreshPlatformBalances(TcpConnection $connection, int $userId)
    {
        try {
            $user = Db::table('caipiao_member')->where('id', $userId)->first();
            if (!$user) {
                return;
            }
            
            $enteredPlatforms = Db::table('caipiao_game_recent')
                ->where('user_id', $userId)
                ->distinct()
                ->pluck('platform')
                ->toArray();
            
            $balancePlatforms = Db::table('caipiao_game_balance')
                ->where('uid', $userId)
                ->distinct()
                ->pluck('platform')
                ->toArray();
            
            $enteredPlatforms = array_unique(array_merge($enteredPlatforms, $balancePlatforms));
            
            if (empty($enteredPlatforms)) {
                $connection->send(json_encode([
                    'type' => 'platform_balance_complete',
                    'data' => [
                        'totalPlatforms' => 0,
                        'totalBalance' => 0,
                        'mainBalance' => $user->balance,
                        'message' => '暂无进入过的平台'
                    ]
                ]));
                return;
            }
            
            $platforms = Db::table('caipiao_game_platform')
                ->where('status', 'online')
                ->whereIn('code', $enteredPlatforms)
                ->orderBy('sort', 'asc')
                ->get();
            
            $ngConfig = Db::table('caipiao_game_platform')
                ->where('code', 'NG')
                ->first();
            
            $apiUrl = $ngConfig->api_url ?? env('NG_API_URL', 'https://api.ng777.net');
            $sn = $ngConfig->api_key ?? env('NG_SN', '');
            $secretKey = $ngConfig->api_secret ?? env('NG_SECRET_KEY', '');
            
            $totalBalance = 0;
            $count = 0;
            
            foreach ($platforms as $platform) {
                $balance = 0;
                
                try {
                    $balance = $this->fetchNGBalance($user, $platform->code, $apiUrl, $sn, $secretKey);
                    
                    $exists = Db::table('caipiao_game_balance')
                        ->where('uid', $userId)
                        ->where('platform', $platform->code)
                        ->exists();
                    
                    if ($exists) {
                        Db::table('caipiao_game_balance')
                            ->where('uid', $userId)
                            ->where('platform', $platform->code)
                            ->update(['balance' => $balance, 'updated_at' => time()]);
                    } elseif ($balance > 0) {
                        Db::table('caipiao_game_balance')->insert([
                            'uid' => $userId,
                            'platform' => $platform->code,
                            'balance' => $balance,
                            'created_at' => time(),
                            'updated_at' => time()
                        ]);
                    }
                } catch (\Exception $e) {
                }
                
                $totalBalance += $balance;
                $count++;
                
                $connection->send(json_encode([
                    'type' => 'platform_balance',
                    'data' => [
                        'code' => $platform->code,
                        'name' => $platform->name,
                        'balance' => $balance,
                        'icon' => $platform->icon ?? '',
                        'type' => $platform->type ?? 'slot',
                        'progress' => $count . '/' . count($platforms)
                    ]
                ]));
            }
            
            $connection->send(json_encode([
                'type' => 'platform_balance_complete',
                'data' => [
                    'totalPlatforms' => count($platforms),
                    'totalBalance' => $totalBalance,
                    'mainBalance' => $user->balance
                ]
            ]));
            
        } catch (\Exception $e) {
            $connection->send(json_encode([
                'type' => 'error',
                'data' => ['message' => 'Làm mớiThất bại']
            ]));
        }
    }
    
    protected function fetchNGBalance($user, $platType, $apiUrl, $sn, $secretKey): float
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyz';
        $random = '';
        for ($i = 0; $i < 32; $i++) {
            $random .= $characters[random_int(0, 35)];
        }
        $sign = strtolower(md5($random . $sn . $secretKey));
        
        $params = json_encode([
            'playerId' => $user->username,
            'platType' => strtolower($platType),
            'currency' => 'CNY'
        ]);
        
        $ch = curl_init($apiUrl . '/api/server/balance');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($params),
            'sign: ' . $sign,
            'random: ' . $random,
            'sn: ' . $sn
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        if ($response) {
            $result = json_decode($response, true);
            if (isset($result['code']) && $result['code'] == '10000' && isset($result['data']['balance'])) {
                return floatval($result['data']['balance']);
            }
        }
        
        return 0;
    }
}
