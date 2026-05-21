<?php

namespace app\service;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;

class JwtService
{
    
    public static function generateToken(int $userId, string $username, array $extra = []): string
    {
        $config = config('jwt');
        $now = time();
        
        $payload = [
            'iss' => $config['issuer'],           
            'iat' => $now,                         
            'exp' => $now + $config['ttl'],        
            'uid' => $userId,                      
            'username' => $username,               
            'type' => 'access',                    
        ];
        
        
        if (!empty($extra)) {
            $payload = array_merge($payload, $extra);
        }
        
        return JWT::encode($payload, $config['secret'], $config['algo']);
    }
    
    
    public static function generateRefreshToken(int $userId, string $username): string
    {
        $config = config('jwt');
        $now = time();
        
        $payload = [
            'iss' => $config['issuer'],
            'iat' => $now,
            'exp' => $now + $config['refresh_ttl'],
            'uid' => $userId,
            'username' => $username,
            'type' => 'refresh',
        ];
        
        return JWT::encode($payload, $config['secret'], $config['algo']);
    }
    
    
    public static function verifyToken(string $token): ?object
    {
        if (empty($token)) {
            return null;
        }
        
        try {
            $config = config('jwt');
            $decoded = JWT::decode($token, new Key($config['secret'], $config['algo']));
            return $decoded;
        } catch (ExpiredException $e) {
            
            return null;
        } catch (\Exception $e) {
            
            return null;
        }
    }
    
    
    public static function validateToken(string $token): array
    {
        if (empty($token)) {
            return ['valid' => false, 'payload' => null, 'error' => 'Token为空'];
        }
        
        try {
            $config = config('jwt');
            $decoded = JWT::decode($token, new Key($config['secret'], $config['algo']));
            return ['valid' => true, 'payload' => $decoded, 'error' => null];
        } catch (ExpiredException $e) {
            return ['valid' => false, 'payload' => null, 'error' => 'Token已过期'];
        } catch (\UnexpectedValueException $e) {
            return ['valid' => false, 'payload' => null, 'error' => 'Token格式无效'];
        } catch (\Exception $e) {
            return ['valid' => false, 'payload' => null, 'error' => 'Token验证Thất bại: ' . $e->getMessage()];
        }
    }
    
    
    public static function getUserIdFromToken(string $token): int
    {
        try {
            
            $parts = explode('.', $token);
            if (count($parts) !== 3) {
                return 0;
            }
            
            $payload = json_decode(base64_decode($parts[1]));
            return $payload->uid ?? 0;
        } catch (\Exception $e) {
            return 0;
        }
    }
    
    
    public static function isRefreshToken(object $payload): bool
    {
        return ($payload->type ?? '') === 'refresh';
    }
    
    
    public static function getTokenTTL(object $payload): int
    {
        $exp = $payload->exp ?? 0;
        $remaining = $exp - time();
        return max(0, $remaining);
    }
}
