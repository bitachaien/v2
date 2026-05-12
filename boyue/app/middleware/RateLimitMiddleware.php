<?php

namespace app\middleware;

use Webman\Http\Request;
use Webman\Http\Response;
use Webman\MiddlewareInterface;
use support\Redis;

class RateLimitMiddleware implements MiddlewareInterface
{
    
    protected $limits = [
        '/api/v1/auth/login' => [5, 60, 300],      
        '/api/v1/register' => [3, 60, 600],        
        '/api/v1/withdraw/submit' => [10, 60, 60], 
        '/api/v1/recharge/submit' => [20, 60, 60], 
    ];
    
    public function process(Request $request, callable $handler): Response
    {
        $path = $request->path();
        $ip = $request->getRealIp();
        
        
        if (!isset($this->limits[$path])) {
            return $handler($request);
        }
        
        [$maxAttempts, $window, $lockTime] = $this->limits[$path];
        
        $key = 'rate_limit:' . md5($path . ':' . $ip);
        $lockKey = 'rate_lock:' . md5($path . ':' . $ip);
        
        try {
            $redis = Redis::connection('default')->client();
            
            
            if ($redis->exists($lockKey)) {
                $ttl = $redis->ttl($lockKey);
                return $this->tooManyRequests("请求过于频繁，请 {$ttl} 秒后再试");
            }
            
            
            $current = (int)$redis->get($key);
            
            if ($current >= $maxAttempts) {
                
                $redis->setex($lockKey, $lockTime, 1);
                $redis->del($key);
                return $this->tooManyRequests("请求过于频繁，请 {$lockTime} 秒后再试");
                }
            
            
                $redis->incr($key);
            if ($current == 0) {
                $redis->expire($key, $window);
            }
            
        } catch (\Exception $e) {
            
            \support\Log::warning('RateLimitMiddleware Redis error: ' . $e->getMessage());
        }
        
        return $handler($request);
    }
    
    
    protected function tooManyRequests(string $message): Response
    {
        return json([
            'code' => 429,
            'message' => $message,
            'data' => null
        ], 429);
    }
}
