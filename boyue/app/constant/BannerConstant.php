<?php

declare(strict_types=1);

namespace app\constant;


class BannerConstant
{
    
    public const PLATFORM_ALL = 0;

    
    public const PLATFORM_PC = 1;

    
    public const PLATFORM_MOBILE = 2;

    
    public const STATUS_DISABLED = 0;

    
    public const STATUS_ENABLED = 1;

    
    public const DEFAULT_PAGE_SIZE = 20;

    
    public const DEFAULT_API_LIMIT = 10;

    
    public const PLATFORM_MAP = [
        self::PLATFORM_ALL    => '全部',
        self::PLATFORM_PC     => 'PC端',
        self::PLATFORM_MOBILE => '移动端',
    ];

    
    public const STATUS_MAP = [
        self::STATUS_DISABLED => '禁用',
        self::STATUS_ENABLED  => '启用',
    ];

    
    public static function getPlatformText(int $platform): string
    {
        return self::PLATFORM_MAP[$platform] ?? '未知';
    }

    
    public static function getStatusText(int $status): string
    {
        return self::STATUS_MAP[$status] ?? '未知';
    }

    
    public static function isValidPlatform(int $platform): bool
    {
        return array_key_exists($platform, self::PLATFORM_MAP);
    }

    
    public static function isValidStatus(int $status): bool
    {
        return array_key_exists($status, self::STATUS_MAP);
    }
}
