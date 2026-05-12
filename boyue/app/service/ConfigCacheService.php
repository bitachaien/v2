<?php

namespace app\service;

use support\Db;
use support\Redis;

class ConfigCacheService
{
    const CACHE_PREFIX = 'sys_config:';
    const CACHE_TTL = 300; 
    const CACHE_ALL_KEY = 'sys_config:all';
    
    
    public static function get(string $name, $default = null)
    {
        $cacheKey = self::CACHE_PREFIX . $name;
        
        try {
            $redis = Redis::connection('default')->client();
            $cached = $redis->get($cacheKey);
            
            if ($cached !== false && $cached !== null) {
                return $cached === '__NULL__' ? null : $cached;
            }
        } catch (\Exception $e) {
            
        }
        
        
        $value = Db::table('caipiao_setting')
            ->where('name', $name)
            ->value('value');
        
        
        try {
            $redis = Redis::connection('default')->client();
            $redis->setex($cacheKey, self::CACHE_TTL, $value ?? '__NULL__');
        } catch (\Exception $e) {
            
        }
        
        return $value ?? $default;
    }
    
    
    public static function getMultiple(array $names): array
    {
        $result = [];
        
        try {
            $redis = Redis::connection('default')->client();
            $keys = array_map(fn($n) => self::CACHE_PREFIX . $n, $names);
            $cached = $redis->mget($keys);
            
            $missing = [];
            foreach ($names as $i => $name) {
                if ($cached[$i] !== false && $cached[$i] !== null) {
                    $result[$name] = $cached[$i] === '__NULL__' ? null : $cached[$i];
                } else {
                    $missing[] = $name;
                }
            }
            
            
            if (!empty($missing)) {
                $dbConfigs = Db::table('caipiao_setting')
                    ->whereIn('name', $missing)
                    ->pluck('value', 'name')
                    ->toArray();
                
                $pipe = $redis->pipeline();
                foreach ($missing as $name) {
                    $value = $dbConfigs[$name] ?? null;
                    $result[$name] = $value;
                    $pipe->setex(self::CACHE_PREFIX . $name, self::CACHE_TTL, $value ?? '__NULL__');
                }
                $pipe->execute();
            }
            
        } catch (\Exception $e) {
            
            $dbConfigs = Db::table('caipiao_setting')
                ->whereIn('name', $names)
                ->pluck('value', 'name')
                ->toArray();
            
            foreach ($names as $name) {
                $result[$name] = $dbConfigs[$name] ?? null;
            }
        }
        
        return $result;
    }
    
    
    public static function getAll(): array
    {
        try {
            $redis = Redis::connection('default')->client();
            $cached = $redis->get(self::CACHE_ALL_KEY);
            
            if ($cached) {
                return json_decode($cached, true) ?: [];
            }
        } catch (\Exception $e) {
            
        }
        
        
        $configs = Db::table('caipiao_setting')
            ->pluck('value', 'name')
            ->toArray();
        
        
        try {
            $redis = Redis::connection('default')->client();
            $redis->setex(self::CACHE_ALL_KEY, self::CACHE_TTL, json_encode($configs));
        } catch (\Exception $e) {
            
        }
        
        return $configs;
    }
    
    
    public static function clear(?string $name = null): void
    {
        try {
            $redis = Redis::connection('default')->client();
            
            if ($name) {
                $redis->del(self::CACHE_PREFIX . $name);
            } else {
                
                $keys = $redis->keys(self::CACHE_PREFIX . '*');
                if (!empty($keys)) {
                    $redis->del(...$keys);
                }
                $redis->del(self::CACHE_ALL_KEY);
            }
        } catch (\Exception $e) {
            
        }
    }
    
    
    public static function getWithdrawConfig(): array
    {
        $names = ['tikuanMin', 'tikuanMax', 'tikuanfeerate', 'tikuanfeefixed', 'tikuancishu'];
        $configs = self::getMultiple($names);
        
        return [
            'minAmount' => floatval($configs['tikuanMin'] ?? 100),
            'maxAmount' => floatval($configs['tikuanMax'] ?? 500000),
            'feeRate' => floatval($configs['tikuanfeerate'] ?? 0),
            'feeFixed' => floatval($configs['tikuanfeefixed'] ?? 0),
            'dailyTimes' => intval($configs['tikuancishu'] ?? 10),
        ];
    }
}

