<?php

namespace app\constants;

/**
 * Game Category to GSC+ Product Code Mapping
 * Maps frontend categories to GSC+ game types and product codes
 */
class GameCategoryMapping
{
    /**
     * Category to GSC+ Game Type mapping
     */
    const CATEGORY_TO_GAME_TYPE = [
        'NO_HU' => 'SLOT',
        'CASINO_TRUC_TUYEN' => 'LIVE_CASINO',
        'BAN_CA' => 'FISHING',
        'THE_THAO' => 'SPORTS',
        'XOC_DIA' => 'TABLE_GAME',
        'GAME_BAI' => 'CARD_GAME',
        'E_SPORTS' => 'E_SPORTS',
    ];

    /**
     * Platform to Product Code mapping
     * Based on GSC+ official documentation
     */
    const PLATFORM_PRODUCT_CODES = [
        'PP' => '1001',      // Pragmatic Play
        'PG' => '1002',      // PG Soft
        'AG' => '1003',      // Asia Gaming
        'SEXY' => '1004',    // Sexy Baccarat
        'WM' => '1005',      // WM Casino
        'DG' => '1006',      // Dream Gaming
        'BG' => '1007',      // BG Gaming
        'CQ9' => '1008',     // CQ9
        'JILI' => '1009',    // JILI
        'FC' => '1010',      // FC Gaming
        'JDB' => '1013',     // JDB
        'KA' => '1015',      // KA Gaming
        'SABA' => '1016',    // SABA Sports
        'CMD' => '1017',     // CMD Sports
        'BBIN' => '1018',    // BBIN
        'IM' => '1019',      // IM Sports
        'BTI' => '1020',     // BTI Sports
        'TF' => '1021',      // TF Gaming
        'MG' => '1022',      // Micro Gaming
        'PNG' => '1023',     // Play'n GO
        'CG' => '1024',      // CG Gaming
        'EVOPLAY' => '1025', // Evoplay
        'NETENT' => '1026',  // NetEnt
        'REDTIGER' => '1027',// Red Tiger
        'RELAX' => '1028',   // Relax Gaming
        'YGGDRASIL' => '1029', // Yggdrasil
        'BOOONGO' => '1030', // Booongo
        'BGAMING' => '1031', // BGaming
        'ELK' => '1032',     // ELK Studios
        'NOLIMIT' => '1033', // Nolimit City
        'HACKSAW' => '1034', // Hacksaw Gaming
        'PLAYSON' => '1035', // Playson
        'QUICKSPIN' => '1036', // Quickspin
        'BB' => '1037',      // BB Gaming
        'SA' => '1038',      // SA Gaming
        'PRETTY' => '1039',  // Pretty Gaming
        'SPADE' => '1040',   // Spade Gaming
        'HABANERO' => '1041',// Habanero
        'NAGA' => '1042',    // Naga Games
        'SV388' => '1043',   // SV388
        'WS168' => '1044',   // WS168
        'GW' => '1045',      // GW Gaming
        'TCG' => '1046',     // TCG
        'SPRIBE' => '1047',  // Spribe
    ];

    /**
     * Get GSC+ game type from category code
     * 
     * @param string $categoryCode Category code (e.g., 'NO_HU')
     * @return string|null GSC+ game type or null if not found
     */
    public static function getGameType(string $categoryCode): ?string
    {
        return self::CATEGORY_TO_GAME_TYPE[$categoryCode] ?? null;
    }

    /**
     * Get product code from platform code
     * 
     * @param string $platformCode Platform code (e.g., 'JDB')
     * @return string|null Product code or null if not found
     */
    public static function getProductCode(string $platformCode): ?string
    {
        return self::PLATFORM_PRODUCT_CODES[strtoupper($platformCode)] ?? null;
    }

    /**
     * Get all supported platforms
     * 
     * @return array Array of platform codes
     */
    public static function getSupportedPlatforms(): array
    {
        return array_keys(self::PLATFORM_PRODUCT_CODES);
    }

    /**
     * Get all supported categories
     * 
     * @return array Array of category codes
     */
    public static function getSupportedCategories(): array
    {
        return array_keys(self::CATEGORY_TO_GAME_TYPE);
    }

    /**
     * Check if platform is supported
     * 
     * @param string $platformCode Platform code
     * @return bool True if supported
     */
    public static function isPlatformSupported(string $platformCode): bool
    {
        return isset(self::PLATFORM_PRODUCT_CODES[strtoupper($platformCode)]);
    }

    /**
     * Check if category is supported
     *
     * @param string $categoryCode Category code
     * @return bool True if supported
     */
    public static function isCategorySupported(string $categoryCode): bool
    {
        return isset(self::CATEGORY_TO_GAME_TYPE[$categoryCode]);
    }

    /**
     * Get all categories
     *
     * @return array Array of category codes
     */
    public static function getAllCategories(): array
    {
        return array_keys(self::CATEGORY_TO_GAME_TYPE);
    }

    /**
     * Get platform name from code
     *
     * @param string $platformCode Platform code
     * @return string Platform name
     */
    public static function getPlatformName(string $platformCode): string
    {
        // Platform names mapping
        $names = [
            'PP' => 'Pragmatic Play',
            'PG' => 'PG Soft',
            'AG' => 'Asia Gaming',
            'SEXY' => 'Sexy Baccarat',
            'WM' => 'WM Casino',
            'DG' => 'Dream Gaming',
            'BG' => 'BG Gaming',
            'CQ9' => 'CQ9',
            'JILI' => 'JILI',
            'FC' => 'FC Gaming',
            'JDB' => 'JDB',
            'KA' => 'KA Gaming',
            'SABA' => 'SABA Sports',
            'CMD' => 'CMD Sports',
            'BBIN' => 'BBIN',
            'IM' => 'IM Sports',
            'BTI' => 'BTI Sports',
            'TF' => 'TF Gaming',
            'MG' => 'Micro Gaming',
            'PNG' => 'Play\'n GO',
            'CG' => 'CG Gaming',
            'EVOPLAY' => 'Evoplay',
            'NETENT' => 'NetEnt',
            'REDTIGER' => 'Red Tiger',
            'RELAX' => 'Relax Gaming',
            'YGGDRASIL' => 'Yggdrasil',
            'BOOONGO' => 'Booongo',
            'BGAMING' => 'BGaming',
            'ELK' => 'ELK Studios',
            'NOLIMIT' => 'Nolimit City',
            'HACKSAW' => 'Hacksaw Gaming',
            'PLAYSON' => 'Playson',
            'QUICKSPIN' => 'Quickspin',
            'BB' => 'BB Gaming',
            'SA' => 'SA Gaming',
            'PRETTY' => 'Pretty Gaming',
            'SPADE' => 'Spade Gaming',
            'HABANERO' => 'Habanero',
            'NAGA' => 'Naga Games',
            'SV388' => 'SV388',
            'WS168' => 'WS168',
            'GW' => 'GW Gaming',
            'TCG' => 'TCG',
            'SPRIBE' => 'Spribe',
        ];
        
        return $names[strtoupper($platformCode)] ?? $platformCode;
    }
}