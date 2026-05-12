<?php

namespace app\service;

use support\Log;

class WebSocketPusher
{
    
    const QUEUE_PREFIX = 'websocket:push:worker:';
    
    
    const WORKER_COUNT = 4;
    
    
    private static $redis = null;
    
    
    private static function getRedis(): \Redis
    {
        if (self::$redis === null || !self::$redis->ping()) {
            $redisConfig = config('redis.default', []);
            self::$redis = new \Redis();
            self::$redis->connect(
                $redisConfig['host'] ?? '127.0.0.1',
                $redisConfig['port'] ?? 6379,
                $redisConfig['timeout'] ?? 2.0
            );
            
            if (!empty($redisConfig['password'])) {
                self::$redis->auth($redisConfig['password']);
            }
            
            if (!empty($redisConfig['database'])) {
                self::$redis->select($redisConfig['database']);
            }
        }
        
        return self::$redis;
    }
    
    
    private static function publish(array $payload): bool
    {
        try {
            $message = json_encode($payload);
            $redis = self::getRedis();
            
            
            for ($i = 0; $i < self::WORKER_COUNT; $i++) {
                $queueKey = self::QUEUE_PREFIX . $i;
                $redis->rPush($queueKey, $message);
            }
            
            Log::info("[WebSocketPusher] 广播到所有 Worker", [
                'workerCount' => self::WORKER_COUNT,
                'target' => $payload['target'] ?? 'unknown',
                'type' => $payload['type'] ?? 'unknown',
                'userId' => $payload['userId'] ?? null,
            ]);
            
            return true;
        } catch (\Exception $e) {
            Log::error("[WebSocketPusher] Redis 广播失败: " . $e->getMessage(), [
                'payload' => $payload
            ]);
            self::$redis = null; 
            return false;
        }
    }
    
    
    public static function pushToUser(int $userId, string $type, array $data): bool
    {
        return self::publish([
            'target' => 'user',
            'userId' => $userId,
            'type' => $type,
            'data' => $data,
        ]);
    }
    
    
    public static function pushToUsers(array $userIds, string $type, array $data): bool
    {
        return self::publish([
            'target' => 'users',
            'userIds' => $userIds,
            'type' => $type,
            'data' => $data,
        ]);
    }
    
    
    public static function broadcast(string $lotteryCode, string $type, array $data): bool
    {
        return self::publish([
            'target' => 'lottery',
            'lotteryCode' => $lotteryCode,
            'type' => $type,
            'data' => $data,
        ]);
    }
    
    
    public static function broadcastToAll(string $type, array $data): bool
    {
        return self::publish([
            'target' => 'all',
            'type' => $type,
            'data' => $data,
        ]);
    }
    
    
    
    
    public static function pushBetCreated(int $userId, array $data): bool
    {
        $payload = [
            'orderNo' => $data['orderNo'] ?? '',
            'gameName' => $data['gameName'] ?? '',
            'issue' => $data['issue'] ?? '',
            'playCategory' => $data['playCategory'] ?? '',
            'playType' => $data['playType'] ?? '',
            'betContent' => $data['betContent'] ?? '',
            'betAmount' => floatval($data['betAmount'] ?? 0),
            'status' => 'pending',
            'betTime' => $data['betTime'] ?? date('Y-m-d H:i:s'),
            'balanceAfter' => isset($data['balanceAfter']) ? floatval($data['balanceAfter']) : null,
        ];
        
        
        if ($payload['balanceAfter'] === null) {
            unset($payload['balanceAfter']);
        }
        
        return self::pushToUser($userId, 'bet_created', $payload);
    }
    
    
    
    
    public static function pushBetSettled(int $userId, array $data): bool
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
        
        return self::pushToUser($userId, 'bet_settled', $payload);
    }
    
    
    
    
    public static function pushBalanceUpdate(int $userId, array $data): bool
    {
        $payload = [
            'balance' => floatval($data['balance'] ?? 0),
            'change' => floatval($data['change'] ?? 0),
            'reason' => $data['reason'] ?? 'other',
            'orderId' => $data['orderId'] ?? '',
            'time' => $data['time'] ?? time(),
        ];
        
        return self::pushToUser($userId, 'balance_update', $payload);
    }
    
    
    
    
    public static function pushNotificationToAll(array $data): bool
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
        
        return self::broadcastToAll('notification', $payload);
    }
    
    
    public static function pushNotificationToUser(int $userId, array $data): bool
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
        
        return self::pushToUser($userId, 'notification', $payload);
    }
    
    
    
    
    public static function pushLotteryStatus(string $lotteryCode, array $data): bool
    {
        $payload = [
            'lotteryCode' => strtolower($lotteryCode),
            'status' => $data['status'] ?? 'normal',
            'message' => $data['message'] ?? '',
            'resumeTime' => $data['resumeTime'] ?? 0,
        ];
        
        return self::broadcast($lotteryCode, 'lottery_status', $payload);
    }
    
    
    
    
    public static function pushYuebaoInterest(int $userId, array $data): bool
    {
        $payload = [
            'pendingInterest' => $data['pendingInterest'] ?? '0.00',
            'totalInterest' => $data['totalInterest'] ?? '0.00',
            'currentAmount' => $data['currentAmount'] ?? '0.00',
            'addedInterest' => $data['addedInterest'] ?? '0.00',
            'nextSettleTime' => $data['nextSettleTime'] ?? '',
            'time' => $data['time'] ?? time(),
        ];
        
        return self::pushToUser($userId, 'yuebao_interest', $payload);
    }
    
    
    public static function pushYuebaoBalance(int $userId, array $data): bool
    {
        $payload = [
            'type' => $data['type'] ?? 'deposit', 
            'amount' => $data['amount'] ?? '0.00',
            'currentAmount' => $data['currentAmount'] ?? '0.00',
            'pendingInterest' => $data['pendingInterest'] ?? '0.00',
            'totalInterest' => $data['totalInterest'] ?? '0.00',
            'time' => $data['time'] ?? time(),
        ];
        
        return self::pushToUser($userId, 'yuebao_balance', $payload);
    }
    
    
    
    
    public static function pushPlatformBalance(int $userId, array $data): bool
    {
        $payload = [
            'code' => $data['code'] ?? '',
            'name' => $data['name'] ?? '',
            'balance' => floatval($data['balance'] ?? 0),
            'icon' => $data['icon'] ?? '',
            'type' => $data['type'] ?? 'slot',
        ];
        
        return self::pushToUser($userId, 'platform_balance', $payload);
    }
    
    
    public static function pushPlatformBalanceComplete(int $userId, array $data): bool
    {
        $payload = [
            'totalPlatforms' => $data['totalPlatforms'] ?? 0,
            'totalBalance' => floatval($data['totalBalance'] ?? 0),
            'mainBalance' => floatval($data['mainBalance'] ?? 0),
        ];
        
        return self::pushToUser($userId, 'platform_balance_complete', $payload);
    }
}
