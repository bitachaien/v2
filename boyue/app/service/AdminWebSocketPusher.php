<?php

namespace app\service;

use support\Log;

class AdminWebSocketPusher
{
    
    const QUEUE_PREFIX = 'admin_websocket:push:worker:';
    
    
    const WORKER_COUNT = 2;
    
    
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
            
            return true;
        } catch (\Exception $e) {
            Log::error("[AdminWSPusher] 推送失败: " . $e->getMessage());
            self::$redis = null;
            return false;
        }
    }
    
    
    public static function pushToChannel(string $channel, string $type, array $data): bool
    {
        return self::publish([
            'target' => 'channel',
            'channel' => $channel,
            'type' => $type,
            'data' => $data,
        ]);
    }
    
    
    public static function pushToAdmin(int $adminId, string $type, array $data): bool
    {
        return self::publish([
            'target' => 'admin',
            'adminId' => $adminId,
            'type' => $type,
            'data' => $data,
        ]);
    }
    
    
    public static function pushToAll(string $type, array $data): bool
    {
        return self::publish([
            'target' => 'all',
            'type' => $type,
            'data' => $data,
        ]);
    }
    
    
    
    
    public static function pushRealtimeActivity(array $data): bool
    {
        return self::pushToChannel('statistics', 'stats_realtime', [
            'type' => $data['type'] ?? 'other',
            'username' => $data['username'] ?? '',
            'amount' => floatval($data['amount'] ?? 0),
            'time' => $data['time'] ?? date('H:i'),
            'extra' => $data['extra'] ?? null,
        ]);
    }
    
    
    public static function pushStatsOverview(array $data): bool
    {
        return self::pushToChannel('statistics', 'stats_overview', $data);
    }
    
    
    public static function pushOnlineCount(int $count, int $change = 0): bool
    {
        return self::pushToChannel('statistics', 'stats_online', [
            'count' => $count,
            'change' => $change,
        ]);
    }
    
    
    
    
    public static function pushNewDeposit(array $data): bool
    {
        return self::pushToChannel('finance', 'finance_new_deposit', [
            'id' => $data['id'] ?? 0,
            'username' => $data['username'] ?? '',
            'amount' => floatval($data['amount'] ?? 0),
            'channel' => $data['channel'] ?? '',
            'time' => $data['time'] ?? date('Y-m-d H:i:s'),
        ]);
    }
    
    
    public static function pushNewWithdraw(array $data): bool
    {
        return self::pushToChannel('finance', 'finance_new_withdraw', [
            'id' => $data['id'] ?? 0,
            'username' => $data['username'] ?? '',
            'amount' => floatval($data['amount'] ?? 0),
            'riskLevel' => $data['riskLevel'] ?? 'low',
            'time' => $data['time'] ?? date('Y-m-d H:i:s'),
        ]);
    }
    
    
    public static function pushLargeAmountAlert(array $data): bool
    {
        return self::pushToChannel('finance', 'finance_large_alert', [
            'type' => $data['type'] ?? 'withdraw',
            'amount' => floatval($data['amount'] ?? 0),
            'username' => $data['username'] ?? '',
            'warning' => $data['warning'] ?? '大额资金操作',
            'time' => $data['time'] ?? date('Y-m-d H:i:s'),
        ]);
    }
    
    
    
    
    public static function pushRiskAlert(array $data): bool
    {
        return self::pushToChannel('risk', 'risk_alert', [
            'userId' => $data['userId'] ?? 0,
            'username' => $data['username'] ?? '',
            'rule' => $data['rule'] ?? '',
            'level' => $data['level'] ?? 'medium',
            'detail' => $data['detail'] ?? '',
            'time' => $data['time'] ?? date('Y-m-d H:i:s'),
        ]);
    }
    
    
    public static function pushUserBanned(array $data): bool
    {
        return self::pushToChannel('risk', 'risk_ban_notify', [
            'username' => $data['username'] ?? '',
            'reason' => $data['reason'] ?? '',
            'operator' => $data['operator'] ?? 'system',
            'time' => $data['time'] ?? date('Y-m-d H:i:s'),
        ]);
    }
    
    
    
    
    public static function pushLotteryOpen(array $data): bool
    {
        return self::pushToChannel('lottery', 'lottery_open', [
            'lotteryId' => $data['lotteryId'] ?? 0,
            'lotteryCode' => $data['lotteryCode'] ?? '',
            'lotteryName' => $data['lotteryName'] ?? '',
            'issue' => $data['issue'] ?? '',
            'numbers' => $data['numbers'] ?? '',
            'time' => $data['time'] ?? date('Y-m-d H:i:s'),
        ]);
    }
    
    
    public static function pushLotteryClose(array $data): bool
    {
        return self::pushToChannel('lottery', 'lottery_close', [
            'lotteryId' => $data['lotteryId'] ?? 0,
            'lotteryCode' => $data['lotteryCode'] ?? '',
            'issue' => $data['issue'] ?? '',
            'status' => $data['status'] ?? 'closed',
        ]);
    }
    
    
    
    
    public static function pushRobotLog(array $data): bool
    {
        return self::pushToChannel('robot', 'robot_log', [
            'botId' => $data['botId'] ?? 0,
            'botName' => $data['botName'] ?? '',
            'level' => $data['level'] ?? 'info',
            'message' => $data['message'] ?? '',
            'time' => $data['time'] ?? date('Y-m-d H:i:s'),
        ]);
    }
    
    
    public static function pushRobotStatus(array $data): bool
    {
        return self::pushToChannel('robot', 'robot_status', [
            'botId' => $data['botId'] ?? 0,
            'botName' => $data['botName'] ?? '',
            'status' => $data['status'] ?? 'offline',
            'message' => $data['message'] ?? '',
            'time' => $data['time'] ?? date('Y-m-d H:i:s'),
        ]);
    }
    
    
    
    
    public static function pushSystemNotice(array $data): bool
    {
        return self::pushToChannel('monitor', 'system_notice', [
            'title' => $data['title'] ?? '',
            'content' => $data['content'] ?? '',
            'level' => $data['level'] ?? 'info',
            'time' => $data['time'] ?? date('Y-m-d H:i:s'),
        ]);
    }
    
    
    public static function pushServerLoad(array $data): bool
    {
        return self::pushToChannel('monitor', 'server_load', [
            'cpu' => $data['cpu'] ?? 0,
            'memory' => $data['memory'] ?? 0,
            'disk' => $data['disk'] ?? 0,
            'load' => $data['load'] ?? 0,
        ]);
    }
}
