<?php

namespace app\service;

use support\Db;
use support\Log;
use support\Redis;

/**
 * GSC+ Configuration Service
 * Manages GSC+ API configuration from database with caching
 */
class GscPlusConfigService
{
    private const CACHE_KEY = 'gscplus:config';
    private const CACHE_TTL = 3600; // 1 hour
    
    /**
     * Get active GSC+ configuration
     * 
     * @return object|null Configuration object or null if not found
     */
    public static function getConfig(): ?object
    {
        try {
            // Try to get from cache first
            $cached = Redis::get(self::CACHE_KEY);
            if ($cached) {
                return json_decode($cached);
            }
            
            // Get from database
            $config = Db::table('caipiao_gscplus_config')
                ->where('status', 1)
                ->first();
            
            if (!$config) {
                Log::warning('GSC+ config not found or inactive');
                return null;
            }
            
            // Cache the config
            Redis::setex(self::CACHE_KEY, self::CACHE_TTL, json_encode($config));
            
            return $config;
            
        } catch (\Exception $e) {
            Log::error('Failed to get GSC+ config: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get operator code
     * 
     * @return string|null
     */
    public static function getOperatorCode(): ?string
    {
        $config = self::getConfig();
        return $config->operator_code ?? null;
    }
    
    /**
     * Get secret key for signature generation
     * 
     * @return string|null
     */
    public static function getSecretKey(): ?string
    {
        $config = self::getConfig();
        return $config->secret_key ?? null;
    }
    
    /**
     * Get GSC+ API base URL
     * 
     * @return string|null
     */
    public static function getApiUrl(): ?string
    {
        $config = self::getConfig();
        return $config->api_url ?? null;
    }
    
    /**
     * Get callback URL for GSC+ to call
     * 
     * @return string|null
     */
    public static function getCallbackUrl(): ?string
    {
        $config = self::getConfig();
        return $config->callback_url ?? null;
    }
    
    /**
     * Get currency code
     * 
     * @return string Default to 'CNY' if not configured
     */
    public static function getCurrency(): string
    {
        $config = self::getConfig();
        return $config->currency ?? 'CNY';
    }
    
    /**
     * Check if GSC+ is enabled
     * 
     * @return bool
     */
    public static function isEnabled(): bool
    {
        $config = self::getConfig();
        return $config && $config->status == 1;
    }
    
    /**
     * Clear configuration cache
     * Use this after updating config in database
     * 
     * @return bool
     */
    public static function clearCache(): bool
    {
        try {
            Redis::del(self::CACHE_KEY);
            Log::info('GSC+ config cache cleared');
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to clear GSC+ config cache: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Validate configuration completeness
     * 
     * @return array ['valid' => bool, 'errors' => array]
     */
    public static function validateConfig(): array
    {
        $config = self::getConfig();
        $errors = [];
        
        if (!$config) {
            $errors[] = 'GSC+ configuration not found';
            return ['valid' => false, 'errors' => $errors];
        }
        
        if (empty($config->operator_code)) {
            $errors[] = 'Operator code is missing';
        }
        
        if (empty($config->secret_key)) {
            $errors[] = 'Secret key is missing';
        }
        
        if (empty($config->api_url)) {
            $errors[] = 'API URL is missing';
        }
        
        if (empty($config->callback_url)) {
            $errors[] = 'Callback URL is missing';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
}