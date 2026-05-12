<?php

declare(strict_types=1);

namespace app\cache;

use support\Log;
use support\Redis;


class BannerCache
{
    
    private const PREFIX = 'banner:';

    
    private const KEY_LIST = 'list';

    
    private const KEY_DETAIL = 'detail:';

    
    private const DEFAULT_TTL = 300; 

    
    private const LIST_TTL = 180; 

    
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
            Log::warning('Redis连接失败，缓存已禁用: ' . $e->getMessage());
            return null;
        }
    }

    
    public static function getList(int $platform, int $limit): ?array
    {
        try {
            $redis = self::getRedis();
            if ($redis === null) {
                return null;
            }

            $key = self::buildListKey($platform, $limit);
            $data = $redis->get($key);

            if ($data !== null) {
                Log::debug("Banner缓存命中: {$key}");
                return json_decode($data, true);
            }

            return null;
        } catch (\Throwable $e) {
            Log::warning("Banner缓存读取失败: " . $e->getMessage());
            return null;
        }
    }

    
    public static function setList(int $platform, int $limit, array $data, int $ttl = self::LIST_TTL): bool
    {
        try {
            $redis = self::getRedis();
            if ($redis === null) {
                return false;
            }

            $key = self::buildListKey($platform, $limit);
            $redis->setex($key, $ttl, json_encode($data, JSON_UNESCAPED_UNICODE));
            return true;
        } catch (\Throwable $e) {
            Log::warning("Banner缓存写入失败: " . $e->getMessage());
            return false;
        }
    }

    
    public static function getDetail(int $id): ?array
    {
        try {
            $redis = self::getRedis();
            if ($redis === null) {
                return null;
            }

            $key = self::buildDetailKey($id);
            $data = $redis->get($key);

            if ($data !== null) {
                Log::debug("Banner详情缓存命中: {$key}");
                return json_decode($data, true);
            }

            return null;
        } catch (\Throwable $e) {
            Log::warning("Banner详情缓存读取失败: " . $e->getMessage());
            return null;
        }
    }

    
    public static function setDetail(int $id, array $data, int $ttl = self::DEFAULT_TTL): bool
    {
        try {
            $redis = self::getRedis();
            if ($redis === null) {
                return false;
            }

            $key = self::buildDetailKey($id);
            $redis->setex($key, $ttl, json_encode($data, JSON_UNESCAPED_UNICODE));
            return true;
        } catch (\Throwable $e) {
            Log::warning("Banner详情缓存写入失败: " . $e->getMessage());
            return false;
        }
    }

    
    public static function deleteDetail(int $id): bool
    {
        try {
            $redis = self::getRedis();
            if ($redis === null) {
                return false;
            }

            $key = self::buildDetailKey($id);
            $redis->del($key);
            return true;
        } catch (\Throwable $e) {
            Log::warning("Banner详情缓存删除失败: " . $e->getMessage());
            return false;
        }
    }

    
    public static function clearAll(): bool
    {
        try {
            
            self::clearListCache();

            Log::info("Banner缓存已清除");
            return true;
        } catch (\Throwable $e) {
            Log::error("Banner缓存清除失败: " . $e->getMessage());
            return false;
        }
    }

    
    public static function clearListCache(): void
    {
        try {
            $redis = self::getRedis();
            if ($redis === null) {
                return;
            }

            
            $platforms = [0, 1, 2];
            $limits = [5, 10, 15, 20];

            foreach ($platforms as $platform) {
                foreach ($limits as $limit) {
                    $key = self::buildListKey($platform, $limit);
                    $redis->del($key);
                }
            }
        } catch (\Throwable $e) {
            Log::warning("Banner列表缓存清除失败: " . $e->getMessage());
        }
    }

    
    public static function refresh(?int $id = null): void
    {
        
        self::clearListCache();

        
        if ($id !== null) {
            self::deleteDetail($id);
        }
    }

    
    private static function buildListKey(int $platform, int $limit): string
    {
        return self::PREFIX . self::KEY_LIST . ":{$platform}:{$limit}";
    }

    
    private static function buildDetailKey(int $id): string
    {
        return self::PREFIX . self::KEY_DETAIL . $id;
    }

    
    public static function getStats(): array
    {
        return [
            'prefix'      => self::PREFIX,
            'default_ttl' => self::DEFAULT_TTL,
            'list_ttl'    => self::LIST_TTL,
        ];
    }
}
