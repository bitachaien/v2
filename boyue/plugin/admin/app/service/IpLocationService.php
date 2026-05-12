<?php

namespace plugin\admin\app\service;

/**
 * IP 归属地服务
 * 使用 ipinfo.io API 获取 IP 地址归属地信息
 */
class IpLocationService
{
    /**
     * 缓存时间（秒）- 24小时
     */
    const CACHE_TTL = 86400;

    /**
     * API 超时时间（秒）
     */
    const TIMEOUT = 3;

    /**
     * 获取 IP 归属地
     * @param string $ip IP地址
     * @return string 归属地字符串，如 "美国 加利福尼亚 Mountain View"
     */
    public static function getLocation(string $ip): string
    {
        if (empty($ip) || !filter_var($ip, FILTER_VALIDATE_IP)) {
            return '';
        }

        // 内网IP直接返回
        if (self::isPrivateIp($ip)) {
            return '内网IP';
        }

        // 尝试从缓存获取
        $cacheKey = 'ip_location:' . $ip;
        $cached = self::getCache($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        // 调用 API 获取
        $location = self::fetchFromApi($ip);
        
        // 缓存结果
        if ($location !== '') {
            self::setCache($cacheKey, $location, self::CACHE_TTL);
        }

        return $location;
    }

    /**
     * 从 ipinfo.io API 获取归属地
     * @param string $ip
     * @return string
     */
    private static function fetchFromApi(string $ip): string
    {
        try {
            $url = "https://ipinfo.io/{$ip}/json";
            
            $context = stream_context_create([
                'http' => [
                    'timeout' => self::TIMEOUT,
                    'header' => "Accept: application/json\r\n",
                ],
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                ],
            ]);

            $response = @file_get_contents($url, false, $context);
            
            if ($response === false) {
                return '';
            }

            $data = json_decode($response, true);
            if (!$data || isset($data['error'])) {
                return '';
            }

            // 组装归属地字符串
            return self::formatLocation($data);

        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * 格式化归属地信息
     * @param array $data API 返回数据
     * @return string
     */
    private static function formatLocation(array $data): string
    {
        $parts = [];

        // 国家
        if (!empty($data['country'])) {
            $country = self::getCountryName($data['country']);
            $parts[] = $country;
        }

        // 省/州
        if (!empty($data['region'])) {
            $parts[] = $data['region'];
        }

        // 城市
        if (!empty($data['city'])) {
            $parts[] = $data['city'];
        }

        return implode(' ', $parts);
    }

    /**
     * 国家代码转中文名称
     * @param string $code
     * @return string
     */
    private static function getCountryName(string $code): string
    {
        $countries = [
            'CN' => '中国',
            'US' => '美国',
            'JP' => '日本',
            'KR' => '韩国',
            'HK' => '香港',
            'TW' => '台湾',
            'MO' => '澳门',
            'SG' => '新加坡',
            'MY' => '马来西亚',
            'TH' => '泰国',
            'VN' => '越南',
            'PH' => '菲律宾',
            'ID' => '印尼',
            'IN' => '印度',
            'AU' => '澳大利亚',
            'CA' => '加拿大',
            'GB' => '英国',
            'DE' => '德国',
            'FR' => '法国',
            'RU' => '俄罗斯',
            'BR' => '巴西',
        ];

        return $countries[$code] ?? $code;
    }

    /**
     * 判断是否为内网IP
     * @param string $ip
     * @return bool
     */
    private static function isPrivateIp(string $ip): bool
    {
        return !filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        );
    }

    /**
     * 获取缓存
     * @param string $key
     * @return string|null
     */
    private static function getCache(string $key): ?string
    {
        try {
            $redis = self::getRedis();
            if ($redis) {
                $value = $redis->get($key);
                return $value !== false ? $value : null;
            }
        } catch (\Exception $e) {
            // 忽略缓存错误
        }
        return null;
    }

    /**
     * 设置缓存
     * @param string $key
     * @param string $value
     * @param int $ttl
     */
    private static function setCache(string $key, string $value, int $ttl): void
    {
        try {
            $redis = self::getRedis();
            if ($redis) {
                $redis->setex($key, $ttl, $value);
            }
        } catch (\Exception $e) {
            // 忽略缓存错误
        }
    }

    /**
     * 获取 Redis 连接
     * @return \Redis|null
     */
    private static function getRedis(): ?\Redis
    {
        static $redis = null;
        
        if ($redis === null) {
            try {
                $redis = new \Redis();
                $host = getenv('REDIS_HOST') ?: '127.0.0.1';
                $port = getenv('REDIS_PORT') ?: 6379;
                $password = getenv('REDIS_PASSWORD') ?: '';
                
                $redis->connect($host, (int)$port, 2);
                
                if ($password) {
                    $redis->auth($password);
                }
            } catch (\Exception $e) {
                $redis = false;
            }
        }
        
        return $redis ?: null;
    }

    /**
     * 批量更新会员归属地（用于修复历史数据）
     * @param int $limit 每次处理数量
     * @return array 处理结果
     */
    public static function batchUpdateMemberLocation(int $limit = 100): array
    {
        $updated = 0;
        $failed = 0;

        $members = \support\Db::table('caipiao_member')
            ->where(function($query) {
                $query->where('iparea', '')
                    ->orWhereNull('iparea');
            })
            ->where('loginip', '!=', '')
            ->whereNotNull('loginip')
            ->limit($limit)
            ->get(['id', 'loginip']);

        foreach ($members as $member) {
            $location = self::getLocation($member->loginip);
            
            if ($location !== '') {
                \support\Db::table('caipiao_member')
                    ->where('id', $member->id)
                    ->update(['iparea' => $location]);
                $updated++;
            } else {
                $failed++;
            }

            // 避免频繁请求
            usleep(100000); // 100ms
        }

        return [
            'updated' => $updated,
            'failed' => $failed,
            'total' => count($members),
        ];
    }
}
