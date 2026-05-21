<?php

namespace plugin\admin\app\service;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;

/**
 * 后台管理员 JWT 服务
 */
class AdminJwtService
{
    /**
     * 生成管理员 Access Token
     */
    public static function generateToken(int $adminId, string $username, array $roles = [], array $extra = []): string
    {
        $config = config('jwt');
        $now = time();
        
        $payload = [
            'iss' => $config['issuer'],
            'iat' => $now,
            'exp' => $now + $config['ttl'],
            'admin_id' => $adminId,
            'username' => $username,
            'roles' => $roles,
            'type' => 'admin',  // 区分前台Người dùng
        ];
        
        if (!empty($extra)) {
            $payload = array_merge($payload, $extra);
        }
        
        return JWT::encode($payload, $config['secret'], $config['algo']);
    }
    
    /**
     * 生成管理员 Refresh Token
     */
    public static function generateRefreshToken(int $adminId, string $username): string
    {
        $config = config('jwt');
        $now = time();
        
        $payload = [
            'iss' => $config['issuer'],
            'iat' => $now,
            'exp' => $now + $config['refresh_ttl'],
            'admin_id' => $adminId,
            'username' => $username,
            'type' => 'admin_refresh',
        ];
        
        return JWT::encode($payload, $config['secret'], $config['algo']);
    }
    
    /**
     * 验证并解码 Token
     */
    public static function verifyToken(string $token): ?object
    {
        if (empty($token)) {
            return null;
        }
        
        try {
            $config = config('jwt');
            $decoded = JWT::decode($token, new Key($config['secret'], $config['algo']));
            
            // 确保是管理员 token
            if (($decoded->type ?? '') !== 'admin') {
                return null;
            }
            
            return $decoded;
        } catch (ExpiredException $e) {
            return null;
        } catch (\Exception $e) {
            return null;
        }
    }
    
    /**
     * 验证 Token 并返回详细结果
     */
    public static function validateToken(string $token): array
    {
        if (empty($token)) {
            return ['valid' => false, 'payload' => null, 'error' => 'Token为空'];
        }
        
        try {
            $config = config('jwt');
            $decoded = JWT::decode($token, new Key($config['secret'], $config['algo']));
            
            // 确保是管理员 token
            if (($decoded->type ?? '') !== 'admin') {
                return ['valid' => false, 'payload' => null, 'error' => '非管理员Token'];
            }
            
            return ['valid' => true, 'payload' => $decoded, 'error' => null];
        } catch (ExpiredException $e) {
            return ['valid' => false, 'payload' => null, 'error' => 'Token已过期'];
        } catch (\UnexpectedValueException $e) {
            return ['valid' => false, 'payload' => null, 'error' => 'Token格式无效'];
        } catch (\Exception $e) {
            return ['valid' => false, 'payload' => null, 'error' => 'Token验证Thất bại'];
        }
    }
    
    /**
     * 刷新 Token
     */
    public static function refreshToken(string $refreshToken): ?array
    {
        if (empty($refreshToken)) {
            return null;
        }
        
        try {
            $config = config('jwt');
            $decoded = JWT::decode($refreshToken, new Key($config['secret'], $config['algo']));
            
            if (($decoded->type ?? '') !== 'admin_refresh') {
                return null;
            }
            
            // 生成新的 access token
            $accessToken = self::generateToken(
                $decoded->admin_id,
                $decoded->username,
                [] // roles 需要重新从dữ liệu库Lấy
            );
            
            return [
                'access_token' => $accessToken,
                'expires_in' => $config['ttl'],
            ];
        } catch (\Exception $e) {
            return null;
        }
    }
}
