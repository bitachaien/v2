<?php

namespace process;

use Workerman\Worker;
use Workerman\Timer;
use Workerman\Connection\TcpConnection;
use support\Db;
use support\Log;

class AdminWebSocketServer
{
    
    protected $worker;
    
    
    protected static $clients = [];
    
    
    protected static $adminConnections = [];
    
    
    protected static $channels = [
        'statistics' => [],   
        'finance' => [],      
        'risk' => [],         
        'lottery' => [],      
        'robot' => [],        
        'monitor' => [],      
    ];
    
    
    public function onWorkerStart(Worker $worker)
    {
        $this->worker = $worker;
        
        Log::info('[AdminWS] 管理后台 WebSocket 服务启动，端口: ' . $worker->getSocketName());
        
        
        Timer::add(3, function () {
            $this->broadcastServerLoad();
        });
        
        
        Timer::add(5, function () {
            $this->broadcastOnlineStats();
        });
        
        
        $this->startRedisPollSubscribe($worker->id);
    }
    
    
    protected function startRedisPollSubscribe(int $workerId)
    {
        $redisConfig = config('redis.default', []);
        $queueKey = 'admin_websocket:push:worker:' . $workerId;
        
        Timer::add(0.1, function() use ($redisConfig, $queueKey, $workerId) {
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
                    
                    Log::info("[AdminWS] Worker {$workerId} Redis 队列轮询启动");
                }
                
                
                for ($i = 0; $i < 10; $i++) {
                    $message = $redis->lPop($queueKey);
                    if (!$message) break;
                    $this->handleRedisPush($message);
                }
                
            } catch (\Exception $e) {
                Log::error("[AdminWS] Redis 轮询Lỗi: " . $e->getMessage());
                $redis = null;
            }
        });
    }
    
    
    protected function handleRedisPush(string $message)
    {
        try {
            $payload = json_decode($message, true);
            if (!$payload) return;
            
            $target = $payload['target'] ?? '';
            $type = $payload['type'] ?? '';
            $data = $payload['data'] ?? [];
            
            switch ($target) {
                case 'channel':
                    $channel = $payload['channel'] ?? '';
                    if ($channel) {
                        $this->pushToChannel($channel, $type, $data);
                    }
                    break;
                    
                case 'admin':
                    $adminId = $payload['adminId'] ?? 0;
                    if ($adminId > 0) {
                        $this->pushToAdmin($adminId, $type, $data);
                    }
                    break;
                    
                case 'all':
                    $this->pushToAll($type, $data);
                    break;
            }
            
        } catch (\Exception $e) {
            Log::error("[AdminWS] 处理推送Thất bại: " . $e->getMessage());
        }
    }
    
    
    public function onConnect(TcpConnection $connection)
    {
        $connectionId = $connection->id;
        self::$clients[$connectionId] = [
            'conn' => $connection,
            'admin_id' => 0,
            'subscriptions' => [],
        ];
        
        Log::info("[AdminWS] 新连接: {$connectionId}");
    }
    
    
    public function onWebSocketConnect(TcpConnection $connection, $request)
    {
        $connectionId = $connection->id;
        
        
        $token = $request->get('token', '');
        
        if (!empty($token)) {
            
            $token = preg_replace('/^Bearer\s+/i', '', $token);
            $this->doAuth($connection, $token);
        } else {
            
            $connection->send(json_encode([
                'type' => 'connected',
                'payload' => ['message' => '请发送Xác thực信息'],
                'timestamp' => time() * 1000,
            ]));
        }
    }
    
    
    public function onMessage(TcpConnection $connection, $data)
    {
        $connectionId = $connection->id;
        
        try {
            $message = json_decode($data, true);
            if (!$message || !isset($message['type'])) {
                $this->sendError($connection, '无效的Tin nhắn格式');
                return;
            }
            
            $type = $message['type'];
            
            switch ($type) {
                case 'ping':
                    $connection->send(json_encode([
                        'type' => 'pong',
                        'timestamp' => time() * 1000,
                    ]));
                    break;
                    
                case 'auth':
                    $token = $message['payload']['token'] ?? '';
                    $this->doAuth($connection, $token);
                    break;
                    
                case 'subscribe':
                    $channels = $message['payload']['channels'] ?? [];
                    $this->handleSubscribe($connection, $channels);
                    break;
                    
                case 'unsubscribe':
                    $channels = $message['payload']['channels'] ?? [];
                    $this->handleUnsubscribe($connection, $channels);
                    break;
                    
                default:
                    $this->sendError($connection, '未知的Tin nhắn类型');
            }
            
        } catch (\Exception $e) {
            Log::error("[AdminWS] Tin nhắn处理Lỗi: " . $e->getMessage());
            $this->sendError($connection, 'Lỗi máy chủ');
        }
    }
    
    
    public function onClose(TcpConnection $connection)
    {
        $connectionId = $connection->id;
        
        if (isset(self::$clients[$connectionId])) {
            
            foreach (self::$clients[$connectionId]['subscriptions'] as $channel => $v) {
                unset(self::$channels[$channel][$connectionId]);
            }
            
            
            $adminId = self::$clients[$connectionId]['admin_id'] ?? 0;
            if ($adminId > 0 && isset(self::$adminConnections[$adminId])) {
                unset(self::$adminConnections[$adminId][$connectionId]);
                if (empty(self::$adminConnections[$adminId])) {
                    unset(self::$adminConnections[$adminId]);
                }
            }
            
            unset(self::$clients[$connectionId]);
        }
        
        Log::info("[AdminWS] 连接关闭: {$connectionId}，剩余: " . count(self::$clients));
    }
    
    
    protected function doAuth(TcpConnection $connection, string $token)
    {
        $connectionId = $connection->id;
        
        if (empty($token)) {
            $this->sendError($connection, 'Tokenkhông được để trống', 'auth_failed');
            return;
        }
        
        try {
            
            $config = config('plugin.admin.jwt', config('jwt'));
            $decoded = \Firebase\JWT\JWT::decode(
                $token,
                new \Firebase\JWT\Key($config['secret'], $config['algo'] ?? 'HS256')
            );
            
            $adminId = $decoded->admin_id ?? $decoded->id ?? $decoded->uid ?? 0;
            $username = $decoded->username ?? '';
            
            if ($adminId <= 0) {
                throw new \Exception('无效的管理员ID');
            }
            
            
            self::$clients[$connectionId]['admin_id'] = $adminId;
            
            if (!isset(self::$adminConnections[$adminId])) {
                self::$adminConnections[$adminId] = [];
            }
            self::$adminConnections[$adminId][$connectionId] = $connection;
            
            Log::info("[AdminWS] Xác thựcThành công: adminId={$adminId}, username={$username}");
            
            $connection->send(json_encode([
                'type' => 'auth_success',
                'payload' => [
                    'adminId' => $adminId,
                    'username' => $username,
                ],
                'timestamp' => time() * 1000,
            ]));
            
        } catch (\Firebase\JWT\ExpiredException $e) {
            $this->sendError($connection, 'Token已过期', 'auth_failed');
        } catch (\Exception $e) {
            Log::warning("[AdminWS] Xác thựcThất bại: " . $e->getMessage());
            $this->sendError($connection, 'Xác thựcThất bại: ' . $e->getMessage(), 'auth_failed');
        }
    }
    
    
    protected function handleSubscribe(TcpConnection $connection, array $channels)
    {
        $connectionId = $connection->id;
        $adminId = self::$clients[$connectionId]['admin_id'] ?? 0;
        
        if (!$adminId) {
            $this->sendError($connection, '请先Xác thực');
            return;
        }
        
        $subscribed = [];
        foreach ($channels as $channel) {
            if (isset(self::$channels[$channel])) {
                self::$channels[$channel][$connectionId] = $connection;
                self::$clients[$connectionId]['subscriptions'][$channel] = true;
                $subscribed[] = $channel;
                
                
                if ($channel === 'statistics') {
                    $this->sendStatisticsInit($connection);
                }
            }
        }
        
        Log::info("[AdminWS] 订阅: admin={$adminId}, channels=" . implode(',', $subscribed));
        
        $connection->send(json_encode([
            'type' => 'subscribed',
            'payload' => ['channels' => $subscribed],
            'timestamp' => time() * 1000,
        ]));
    }
    
    
    protected function sendStatisticsInit(TcpConnection $connection)
    {
        try {
            
            $overview = $this->getStatsOverview();
            $connection->send(json_encode([
                'type' => 'stats_overview',
                'payload' => $overview,
                'timestamp' => time() * 1000,
            ]));
            
            
            $realtimeList = $this->getRealtimeList(20);
            $connection->send(json_encode([
                'type' => 'stats_realtime_init',
                'payload' => $realtimeList,
                'timestamp' => time() * 1000,
            ]));
            
            Log::info("[AdminWS] 已推送 statistics 初始化dữ liệu");
            
        } catch (\Exception $e) {
            Log::error("[AdminWS] 推送初始化dữ liệuThất bại: " . $e->getMessage());
        }
    }
    
    
    protected function getStatsOverview(): array
    {
        $todayStart = strtotime(date('Y-m-d'));
        $todayEnd = $todayStart + 86400 - 1;
        $yesterdayStart = $todayStart - 86400;
        $yesterdayEnd = $todayStart - 1;
        
        
        $testUsers = Db::table('caipiao_member')
            ->where('isnb', 1)
            ->pluck('id')
            ->toArray();
        
        
        $todayRegister = Db::table('caipiao_member')
            ->where('isnb', 0)
            ->where('regtime', '>=', $todayStart)
            ->where('regtime', '<=', $todayEnd)
            ->count();
        $yesterdayRegister = Db::table('caipiao_member')
            ->where('isnb', 0)
            ->where('regtime', '>=', $yesterdayStart)
            ->where('regtime', '<=', $yesterdayEnd)
            ->count();
        
        
        $todayActive = Db::table('caipiao_member')
            ->where('isnb', 0)
            ->where('onlinetime', '>=', $todayStart)
            ->count();
        $yesterdayActive = Db::table('caipiao_member')
            ->where('isnb', 0)
            ->where('onlinetime', '>=', $yesterdayStart)
            ->where('onlinetime', '<=', $yesterdayEnd)
            ->count();
        
        
        $todayRecharge = Db::table('caipiao_recharge')
            ->where('state', 1)
            ->where('oddtime', '>=', $todayStart)
            ->where('oddtime', '<=', $todayEnd)
            ->when(!empty($testUsers), fn($q) => $q->whereNotIn('uid', $testUsers))
            ->sum('amount') ?? 0;
        $yesterdayRecharge = Db::table('caipiao_recharge')
            ->where('state', 1)
            ->where('oddtime', '>=', $yesterdayStart)
            ->where('oddtime', '<=', $yesterdayEnd)
            ->when(!empty($testUsers), fn($q) => $q->whereNotIn('uid', $testUsers))
            ->sum('amount') ?? 0;
        
        
        $todayWithdraw = Db::table('caipiao_withdraw')
            ->where('state', 1)
            ->where('oddtime', '>=', $todayStart)
            ->where('oddtime', '<=', $todayEnd)
            ->when(!empty($testUsers), fn($q) => $q->whereNotIn('uid', $testUsers))
            ->sum('amount') ?? 0;
        $yesterdayWithdraw = Db::table('caipiao_withdraw')
            ->where('state', 1)
            ->where('oddtime', '>=', $yesterdayStart)
            ->where('oddtime', '<=', $yesterdayEnd)
            ->when(!empty($testUsers), fn($q) => $q->whereNotIn('uid', $testUsers))
            ->sum('amount') ?? 0;
        
        
        $todayBet = Db::table('caipiao_touzhu')
            ->whereIn('isdraw', [1, -1])
            ->where('oddtime', '>=', $todayStart)
            ->where('oddtime', '<=', $todayEnd)
            ->when(!empty($testUsers), fn($q) => $q->whereNotIn('uid', $testUsers))
            ->sum('amount') ?? 0;
        $yesterdayBet = Db::table('caipiao_touzhu')
            ->whereIn('isdraw', [1, -1])
            ->where('oddtime', '>=', $yesterdayStart)
            ->where('oddtime', '<=', $yesterdayEnd)
            ->when(!empty($testUsers), fn($q) => $q->whereNotIn('uid', $testUsers))
            ->sum('amount') ?? 0;
        
        
        $todayPrize = Db::table('caipiao_touzhu')
            ->where('isdraw', 1)
            ->where('oddtime', '>=', $todayStart)
            ->where('oddtime', '<=', $todayEnd)
            ->when(!empty($testUsers), fn($q) => $q->whereNotIn('uid', $testUsers))
            ->sum('okamount') ?? 0;
        $yesterdayPrize = Db::table('caipiao_touzhu')
            ->where('isdraw', 1)
            ->where('oddtime', '>=', $yesterdayStart)
            ->where('oddtime', '<=', $yesterdayEnd)
            ->when(!empty($testUsers), fn($q) => $q->whereNotIn('uid', $testUsers))
            ->sum('okamount') ?? 0;
        
        
        $platformBalance = Db::table('caipiao_member')
            ->where('isnb', 0)
            ->sum('balance') ?? 0;
        
        
        $onlineCount = Db::table('caipiao_member')
            ->where('isnb', 0)
            ->where('onlinetime', '>=', time() - 1800)
            ->count();
        
        
        $todayProfit = $todayBet - $todayPrize;
        $yesterdayProfit = $yesterdayBet - $yesterdayPrize;
        
        
        $calcChange = function($today, $yesterday) {
            if ($yesterday == 0) return $today > 0 ? 100 : 0;
            return round(($today - $yesterday) / abs($yesterday) * 100, 1);
        };
        
        return [
            'todayRegister' => $todayRegister,
            'todayRegisterChange' => $calcChange($todayRegister, $yesterdayRegister),
            'todayActive' => $todayActive,
            'todayActiveChange' => $calcChange($todayActive, $yesterdayActive),
            'todayRecharge' => round($todayRecharge, 2),
            'todayRechargeChange' => $calcChange($todayRecharge, $yesterdayRecharge),
            'todayWithdraw' => round($todayWithdraw, 2),
            'todayWithdrawChange' => $calcChange($todayWithdraw, $yesterdayWithdraw),
            'todayBet' => round($todayBet, 2),
            'todayBetChange' => $calcChange($todayBet, $yesterdayBet),
            'todayPrize' => round($todayPrize, 2),
            'todayPrizeChange' => $calcChange($todayPrize, $yesterdayPrize),
            'todayProfit' => round($todayProfit, 2),
            'todayProfitChange' => $calcChange($todayProfit, $yesterdayProfit),
            'platformBalance' => round($platformBalance, 2),
            'onlineCount' => $onlineCount,
        ];
    }
    
    
    protected function getRealtimeList(int $limit = 20): array
    {
        $testUsers = Db::table('caipiao_member')
            ->where('isnb', 1)
            ->pluck('id')
            ->toArray();
        
        
        $recharges = Db::table('caipiao_recharge as r')
            ->leftJoin('caipiao_member as m', 'r.uid', '=', 'm.id')
            ->where('r.state', 1)
            ->when(!empty($testUsers), fn($q) => $q->whereNotIn('r.uid', $testUsers))
            ->orderBy('r.oddtime', 'desc')
            ->limit($limit)
            ->select(['m.username', 'r.amount', 'r.oddtime', Db::raw("'recharge' as type")])
            ->get();
        
        
        $withdraws = Db::table('caipiao_withdraw as w')
            ->leftJoin('caipiao_member as m', 'w.uid', '=', 'm.id')
            ->where('w.state', 1)
            ->when(!empty($testUsers), fn($q) => $q->whereNotIn('w.uid', $testUsers))
            ->orderBy('w.oddtime', 'desc')
            ->limit($limit)
            ->select(['m.username', 'w.amount', 'w.oddtime', Db::raw("'withdraw' as type")])
            ->get();
        
        
        $wins = Db::table('caipiao_touzhu as t')
            ->leftJoin('caipiao_member as m', 't.uid', '=', 'm.id')
            ->where('t.isdraw', 1)
            ->where('t.okamount', '>', 0)
            ->when(!empty($testUsers), fn($q) => $q->whereNotIn('t.uid', $testUsers))
            ->orderBy('t.oddtime', 'desc')
            ->limit($limit)
            ->select(['m.username', 't.okamount as amount', 't.oddtime', Db::raw("'win' as type")])
            ->get();
        
        
        return collect($recharges)->merge($withdraws)->merge($wins)
            ->sortByDesc('oddtime')
            ->take($limit)
            ->map(function ($item) {
                $username = $this->maskUsername($item->username ?? '');
                return [
                    'type' => $item->type,
                    'username' => $username,
                    'amount' => round($item->amount, 2),
                    'time' => date('H:i:s', $item->oddtime),
                ];
            })
            ->values()
            ->toArray();
    }
    
    
    protected function maskUsername(string $username): string
    {
        $len = mb_strlen($username);
        if ($len <= 2) {
            return $username[0] . '***';
        }
        return mb_substr($username, 0, 2) . '***' . mb_substr($username, -1);
    }
    
    
    protected function handleUnsubscribe(TcpConnection $connection, array $channels)
    {
        $connectionId = $connection->id;
        
        foreach ($channels as $channel) {
            unset(self::$channels[$channel][$connectionId]);
            unset(self::$clients[$connectionId]['subscriptions'][$channel]);
        }
        
        $connection->send(json_encode([
            'type' => 'unsubscribed',
            'payload' => ['channels' => $channels],
            'timestamp' => time() * 1000,
        ]));
    }
    
    
    protected function sendError(TcpConnection $connection, string $message, string $type = 'error')
    {
        $connection->send(json_encode([
            'type' => $type,
            'payload' => ['message' => $message],
            'timestamp' => time() * 1000,
        ]));
    }
    
    
    protected function pushToChannel(string $channel, string $type, array $data)
    {
        if (!isset(self::$channels[$channel])) return;
        
        $message = json_encode([
            'type' => $type,
            'payload' => $data,
            'timestamp' => time() * 1000,
        ]);
        
        foreach (self::$channels[$channel] as $connectionId => $connection) {
            try {
                $connection->send($message);
            } catch (\Exception $e) {
                
            }
        }
    }
    
    
    protected function pushToAdmin(int $adminId, string $type, array $data)
    {
        if (!isset(self::$adminConnections[$adminId])) return;
        
        $message = json_encode([
            'type' => $type,
            'payload' => $data,
            'timestamp' => time() * 1000,
        ]);
        
        foreach (self::$adminConnections[$adminId] as $connectionId => $connection) {
            try {
                $connection->send($message);
            } catch (\Exception $e) {
                
            }
        }
    }
    
    
    protected function pushToAll(string $type, array $data)
    {
        $message = json_encode([
            'type' => $type,
            'payload' => $data,
            'timestamp' => time() * 1000,
        ]);
        
        foreach (self::$clients as $client) {
            if ($client['admin_id'] > 0) {
                try {
                    $client['conn']->send($message);
                } catch (\Exception $e) {
                    
                }
            }
        }
    }
    
    
    protected function broadcastServerLoad()
    {
        if (empty(self::$channels['monitor'])) return;
        
        
        $load = sys_getloadavg();
        $cpuUsage = $load[0] * 100 / (function_exists('shell_exec') ? 
            (int)shell_exec('nproc') ?: 1 : 1);
        
        
        $memInfo = $this->getMemoryUsage();
        
        
        $diskTotal = disk_total_space('/');
        $diskFree = disk_free_space('/');
        $diskUsage = $diskTotal > 0 ? round((1 - $diskFree / $diskTotal) * 100, 1) : 0;
        
        $this->pushToChannel('monitor', 'server_load', [
            'cpu' => round(min($cpuUsage, 100), 1),
            'memory' => $memInfo['percent'],
            'disk' => $diskUsage,
            'load' => $load[0],
        ]);
    }
    
    
    protected function broadcastOnlineStats()
    {
        if (empty(self::$channels['statistics'])) return;
        
        try {
            
            $onlineCount = $this->getOnlineUserCount();
            
            $this->pushToChannel('statistics', 'stats_online', [
                'count' => $onlineCount,
                'adminOnline' => count(self::$adminConnections),
            ]);
        } catch (\Exception $e) {
            
        }
    }
    
    
    protected function getMemoryUsage(): array
    {
        $total = 1;
        $free = 0;
        
        if (is_readable('/proc/meminfo')) {
            $memInfo = file_get_contents('/proc/meminfo');
            if (preg_match('/MemTotal:\s+(\d+)/', $memInfo, $matches)) {
                $total = $matches[1];
            }
            if (preg_match('/MemAvailable:\s+(\d+)/', $memInfo, $matches)) {
                $free = $matches[1];
            }
        }
        
        $used = $total - $free;
        $percent = $total > 0 ? round($used / $total * 100, 1) : 0;
        
        return [
            'total' => $total,
            'used' => $used,
            'free' => $free,
            'percent' => $percent,
        ];
    }
    
    
    protected function getOnlineUserCount(): int
    {
        try {
            
            return Db::table('caipiao_member')
                ->where('isnb', 0)
                ->where('onlinetime', '>=', time() - 1800)
                ->count();
        } catch (\Exception $e) {
            return 0;
        }
    }
    
    
    
    
    public static function getOnlineAdminCount(): int
    {
        return count(self::$adminConnections);
    }
    
    
    public static function isAdminOnline(int $adminId): bool
    {
        return isset(self::$adminConnections[$adminId]) && !empty(self::$adminConnections[$adminId]);
    }
}
