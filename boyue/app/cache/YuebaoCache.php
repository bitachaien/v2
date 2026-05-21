<?php

declare(strict_types=1);

namespace app\cache;

use support\Log;
use support\Redis;


class YuebaoCache
{
    
    private const PREFIX = 'yuebao:';

    
    private const KEY_PRODUCTS = 'products';

    
    private const KEY_USER_STATS = 'stats:';

    
    private const DEFAULT_TTL = 300; 

    
    private const PRODUCTS_TTL = 600; 

    
    private const STATS_TTL = 60; 

    
    private const KEY_RATE_LIMIT = 'rate_limit:';

    
    private const RATE_LIMIT_WINDOW = 60;

    
    private const MAX_TRANSFER_IN_PER_MINUTE = 5;

    
    private const MAX_TRANSFER_OUT_PER_MINUTE = 3;

    
    private static bool $enabled = true;

    
    private static function getRedis()
    {
        try {
            if (!self::$enabled) {
                return null;
            }
            return Redis::connection('default');
        } catch (\Throwable $e) {
            
            self::$enabled = false;
            Log::warning('Redis连接Thất bại，Số dư宝缓存已禁用: ' . $e->getMessage());
            return null;
        }
    }

    

    
    public static function getProducts(): ?array
    {
        try {
            $redis = self::getRedis();
            if ($redis === null) {
                return null;
            }

            $key = self::PREFIX . self::KEY_PRODUCTS;
            $data = $redis->get($key);

            if ($data !== null) {
                Log::debug("Số dư宝产品缓存命中: {$key}");
                return json_decode($data, true);
            }

            return null;
        } catch (\Throwable $e) {
            Log::warning("Số dư宝产品缓存读取Thất bại: " . $e->getMessage());
            return null;
        }
    }

    
    public static function setProducts(array $data, int $ttl = self::PRODUCTS_TTL): bool
    {
        try {
            $redis = self::getRedis();
            if ($redis === null) {
                return false;
            }

            $key = self::PREFIX . self::KEY_PRODUCTS;
            $redis->setex($key, $ttl, json_encode($data, JSON_UNESCAPED_UNICODE));
            return true;
        } catch (\Throwable $e) {
            Log::warning("Số dư宝产品缓存写入Thất bại: " . $e->getMessage());
            return false;
        }
    }

    
    public static function clearProducts(): bool
    {
        try {
            $redis = self::getRedis();
            if ($redis === null) {
                return false;
            }

            $key = self::PREFIX . self::KEY_PRODUCTS;
            $redis->del($key);
            return true;
        } catch (\Throwable $e) {
            Log::warning("Số dư宝产品缓存清除Thất bại: " . $e->getMessage());
            return false;
        }
    }

    

    
    public static function getUserStats(int $uid): ?array
    {
        try {
            $redis = self::getRedis();
            if ($redis === null) {
                return null;
            }

            $key = self::buildUserStatsKey($uid);
            $data = $redis->get($key);

            if ($data !== null) {
                Log::debug("Số dư宝Người dùng统计缓存命中: {$key}");
                return json_decode($data, true);
            }

            return null;
        } catch (\Throwable $e) {
            Log::warning("Số dư宝Người dùng统计缓存读取Thất bại: " . $e->getMessage());
            return null;
        }
    }

    
    public static function setUserStats(int $uid, array $data, int $ttl = self::STATS_TTL): bool
    {
        try {
            $redis = self::getRedis();
            if ($redis === null) {
                return false;
            }

            $key = self::buildUserStatsKey($uid);
            $redis->setex($key, $ttl, json_encode($data, JSON_UNESCAPED_UNICODE));
            return true;
        } catch (\Throwable $e) {
            Log::warning("Số dư宝Người dùng统计缓存写入Thất bại: " . $e->getMessage());
            return false;
        }
    }

    
    public static function clearUserStats(int $uid): bool
    {
        try {
            $redis = self::getRedis();
            if ($redis === null) {
                return false;
            }

            $key = self::buildUserStatsKey($uid);
            $redis->del($key);
            return true;
        } catch (\Throwable $e) {
            Log::warning("Số dư宝Người dùng统计缓存清除Thất bại: " . $e->getMessage());
            return false;
        }
    }

    

    
    public static function clearAll(): bool
    {
        try {
            self::clearProducts();
            Log::info("Số dư宝缓存已清除");
            return true;
        } catch (\Throwable $e) {
            Log::error("Số dư宝缓存清除Thất bại: " . $e->getMessage());
            return false;
        }
    }

    
    public static function refresh(?int $uid = null): void
    {
        
        self::clearProducts();

        
        if ($uid !== null && $uid > 0) {
            self::clearUserStats($uid);
        }
    }

    
    private static function buildUserStatsKey(int $uid): string
    {
        return self::PREFIX . self::KEY_USER_STATS . $uid;
    }

    
    public static function getStats(): array
    {
        return [
            'prefix'       => self::PREFIX,
            'default_ttl'  => self::DEFAULT_TTL,
            'products_ttl' => self::PRODUCTS_TTL,
            'stats_ttl'    => self::STATS_TTL,
            'enabled'      => self::$enabled,
        ];
    }

    

    
    public static function checkTransferInLimit(int $uid): bool
    {
        return self::checkRateLimit($uid, 'transfer_in', self::MAX_TRANSFER_IN_PER_MINUTE);
    }

    
    public static function checkTransferOutLimit(int $uid): bool
    {
        return self::checkRateLimit($uid, 'transfer_out', self::MAX_TRANSFER_OUT_PER_MINUTE);
    }

    
    private static function checkRateLimit(int $uid, string $action, int $maxCount): bool
    {
        try {
            $redis = self::getRedis();
            if ($redis === null) {
                
                return true;
            }

            $key = self::PREFIX . self::KEY_RATE_LIMIT . $action . ':' . $uid;
            $count = (int)$redis->get($key);

            if ($count >= $maxCount) {
                Log::warning("Người dùng {$uid} {$action} 操作频率超限: {$count}/{$maxCount}");
                return false;
            }

            
            $redis->incr($key);
            
            if ($count === 0) {
                $redis->expire($key, self::RATE_LIMIT_WINDOW);
            }

            return true;
        } catch (\Throwable $e) {
            Log::warning("频率限制检查Thất bại: " . $e->getMessage());
            
            return true;
        }
    }

    
    public static function getRateLimitInfo(int $uid, string $action): array
    {
        $maxCount = $action === 'transfer_in' 
            ? self::MAX_TRANSFER_IN_PER_MINUTE 
            : self::MAX_TRANSFER_OUT_PER_MINUTE;

        try {
            $redis = self::getRedis();
            if ($redis === null) {
                return ['remaining' => $maxCount, 'limit' => $maxCount, 'reset_in' => 0];
            }

            $key = self::PREFIX . self::KEY_RATE_LIMIT . $action . ':' . $uid;
            $count = (int)$redis->get($key);
            $ttl = (int)$redis->ttl($key);

            return [
                'remaining' => max(0, $maxCount - $count),
                'limit'     => $maxCount,
                'reset_in'  => $ttl > 0 ? $ttl : 0
            ];
        } catch (\Throwable $e) {
            return ['remaining' => $maxCount, 'limit' => $maxCount, 'reset_in' => 0];
        }
    }
}
