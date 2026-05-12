<?php

namespace app\service;

use support\Db;

class InviteCodeService
{
    
    private const CHARSET = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
    
    
    private const CODE_LENGTH = 6;
    
    
    private const SALT = 'AgentCode2024';
    
    
    public static function encode(int $userId): string
    {
        
        $base = strlen(self::CHARSET);
        $code = '';
        
        
        $mixed = ($userId * 17 + 12345) ^ 98765;
        
        
        while ($mixed > 0) {
            $code = self::CHARSET[$mixed % $base] . $code;
            $mixed = intval($mixed / $base);
        }
        
        
        while (strlen($code) < self::CODE_LENGTH - 1) {
            $code = self::CHARSET[0] . $code;
        }
        
        
        $checksum = self::calculateChecksum($code);
        $code = $code . self::CHARSET[$checksum % $base];
        
        return $code;
    }
    
    
    public static function decode(string $code): ?int
    {
        $code = strtoupper(trim($code));
        
        
        if (strlen($code) < 2) {
            return null;
        }
        
        
        $mainCode = substr($code, 0, -1);
        $checksumChar = substr($code, -1);
        $expectedChecksum = self::calculateChecksum($mainCode);
        $base = strlen(self::CHARSET);
        
        if (self::CHARSET[$expectedChecksum % $base] !== $checksumChar) {
            return null;
        }
        
        
        $mixed = 0;
        for ($i = 0; $i < strlen($mainCode); $i++) {
            $pos = strpos(self::CHARSET, $mainCode[$i]);
            if ($pos === false) {
                return null;
            }
            $mixed = $mixed * $base + $pos;
        }
        
        
        $userId = (($mixed ^ 98765) - 12345) / 17;
        
        
        if ($userId != intval($userId) || $userId <= 0) {
            return null;
        }
        
        return intval($userId);
    }
    
    
    private static function calculateChecksum(string $code): int
    {
        $sum = 0;
        for ($i = 0; $i < strlen($code); $i++) {
            $sum += ord($code[$i]) * ($i + 1);
        }
        return $sum;
    }
    
    
    public static function validate(string $code): bool
    {
        $userId = self::decode($code);
        
        if (!$userId) {
            return false;
        }
        
        
        $user = Db::table('caipiao_member')
            ->where('id', $userId)
            ->where('proxy', 1)
            ->first();
        
        return $user !== null;
    }
    
    
    public static function getAgentByCode(string $code): ?object
    {
        $userId = self::decode($code);
        
        if (!$userId) {
            return null;
        }
        
        return Db::table('caipiao_member')
            ->where('id', $userId)
            ->where('proxy', 1)
            ->first();
    }
    
    
    public static function generateInviteLink(int $userId, string $baseUrl = ''): string
    {
        $code = self::encode($userId);
        
        if (empty($baseUrl)) {
            $baseUrl = Db::table('caipiao_setting')
                ->where('name', 'site_url')
                ->value('value') ?? 'https://example.com';
        }
        
        return rtrim($baseUrl, '/') . '/?invite=' . $code;
    }
    
    
    public static function generateQrCodeUrl(string $content): string
    {
        
        return 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . urlencode($content);
    }
}
