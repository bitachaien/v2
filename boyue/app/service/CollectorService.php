<?php

namespace app\service;

use app\model\Lottery;
use app\model\LotteryResult;
use support\Redis;

class CollectorService
{
    
    public static function collectAll(): void
    {
        echo "[" . date('Y-m-d H:i:s') . "] 开始采集Mở thưởngdữ liệu...\n";
        
        
        $lotteries = Lottery::where('is_open', 1)->get();
        
        foreach ($lotteries as $lottery) {
            try {
                self::collectLottery($lottery);
            } catch (\Exception $e) {
                error_log("采集Thất bại: {$lottery->key} - " . $e->getMessage());
            }
        }
        
        echo "[" . date('Y-m-d H:i:s') . "] 采集完成\n";
    }
    
    
    protected static function collectLottery($lottery): void
    {
        $lockKey = "collector:lock:{$lottery->key}";
        $redis = Redis::connection();
        
        
        $locked = $redis->set($lockKey, 1, 'EX', 30, 'NX');
        
        if (!$locked) {
            echo "  - {$lottery->key}: 已有采集任务运行中\n";
            return;
        }
        
        try {
            
            $result = self::fetchFromSource($lottery);
            
            if ($result) {
                self::saveLotteryResult($lottery, $result);
                echo "  - {$lottery->key}: 采集Thành công 期号:{$result['expect']}\n";
            }
            
        } finally {
            
            $redis->del($lockKey);
        }
    }
    
    
    protected static function fetchFromSource($lottery): ?array
    {
        
        
        
        
        
        return null;
    }
    
    
    protected static function saveLotteryResult($lottery, array $data): void
    {
        
        $exists = LotteryResult::where('lottery_key', $data['lottery_key'])
            ->where('expect', $data['expect'])
            ->exists();
        
        if ($exists) {
            return; 
        }
        
        
        LotteryResult::create([
            'lottery_id' => $lottery->id,
            'lottery_key' => $data['lottery_key'],
            'title' => $lottery->title,
            'expect' => $data['expect'],
            'open_code' => $data['open_code'],
            'source_code' => $data['open_code'],
            'source' => $data['source'] ?? 'unknown',
            'is_drawn' => 1,
            'open_time' => $data['open_time'],
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }
}

