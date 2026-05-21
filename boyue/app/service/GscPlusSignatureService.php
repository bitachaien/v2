<?php

namespace app\service;

use support\Log;

/**
 * GSC+ Signature Service
 * Handles MD5 signature generation and verification for GSC+ API
 * 
 * Signature Algorithm:
 * 1. Sort parameters by key (ascending, case-sensitive)
 * 2. Concatenate key=value pairs with & separator
 * 3. Append secret key
 * 4. Calculate MD5 hash (lowercase)
 */
class GscPlusSignatureService
{
    /**
     * Generate signature for outgoing requests to GSC+
     * 
     * @param array $data Request parameters (excluding 'sign')
     * @param string $secretKey Secret key from config
     * @return string MD5 signature (lowercase)
     */
    public static function generateSignature(array $data, string $secretKey): string
    {
        try {
            // Remove sign if exists
            unset($data['sign']);
            
            // Sort and concatenate
            $signString = self::sortAndConcatenate($data);
            
            // Append secret key
            $signString .= $secretKey;
            
            // Generate MD5 hash (lowercase)
            $signature = strtolower(md5($signString));
            
            Log::info('GSC+ signature generated', [
                'sign_string' => $signString,
                'signature' => $signature
            ]);
            
            return $signature;
            
        } catch (\Exception $e) {
            Log::error('Failed to generate GSC+ signature: ' . $e->getMessage(), [
                'data' => $data
            ]);
            throw $e;
        }
    }
    
    /**
     * Verify signature from incoming GSC+ callbacks
     * 
     * @param array $data Callback parameters (including 'sign')
     * @param string $signature Signature to verify
     * @param string $secretKey Secret key from config
     * @return bool True if signature is valid
     */
    public static function verifySignature(array $data, string $signature, string $secretKey): bool
    {
        try {
            // Generate expected signature
            $expectedSignature = self::generateSignature($data, $secretKey);
            
            // Compare signatures (case-insensitive)
            $isValid = strcasecmp($expectedSignature, $signature) === 0;
            
            if (!$isValid) {
                Log::warning('GSC+ signature verification failed', [
                    'expected' => $expectedSignature,
                    'received' => $signature,
                    'data' => $data
                ]);
            } else {
                Log::info('GSC+ signature verified successfully');
            }
            
            return $isValid;
            
        } catch (\Exception $e) {
            Log::error('Failed to verify GSC+ signature: ' . $e->getMessage(), [
                'signature' => $signature,
                'data' => $data
            ]);
            return false;
        }
    }
    
    /**
     * Sort parameters and concatenate into sign string
     * 
     * Rules:
     * - Sort by key (ascending, case-sensitive)
     * - Skip empty values
     * - Skip 'sign' parameter
     * - Format: key1=value1&key2=value2&...
     * 
     * @param array $data Parameters to sort and concatenate
     * @return string Concatenated sign string
     */
    public static function sortAndConcatenate(array $data): string
    {
        // Remove sign parameter
        unset($data['sign']);
        
        // Remove empty values
        $data = array_filter($data, function($value) {
            return $value !== '' && $value !== null;
        });
        
        // Sort by key (case-sensitive ascending)
        ksort($data, SORT_STRING);
        
        // Build sign string
        $parts = [];
        foreach ($data as $key => $value) {
            // Convert arrays/objects to JSON string
            if (is_array($value) || is_object($value)) {
                $value = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
            $parts[] = $key . '=' . $value;
        }
        
        return implode('&', $parts);
    }
    
    /**
     * Validate signature format
     * 
     * @param string $signature Signature to validate
     * @return bool True if format is valid (32 character MD5 hash)
     */
    public static function isValidSignatureFormat(string $signature): bool
    {
        return preg_match('/^[a-f0-9]{32}$/i', $signature) === 1;
    }
    
    /**
     * Generate signature with timestamp for request
     * Convenience method that adds timestamp if not present
     * 
     * @param array $data Request parameters
     * @param string $secretKey Secret key
     * @return array ['data' => array with timestamp, 'sign' => signature]
     */
    public static function signRequest(array $data, string $secretKey): array
    {
        // Add timestamp if not present
        if (!isset($data['timestamp'])) {
            $data['timestamp'] = (int)(microtime(true) * 1000); // milliseconds
        }
        
        // Generate signature
        $signature = self::generateSignature($data, $secretKey);
        
        // Add signature to data
        $data['sign'] = $signature;
        
        return [
            'data' => $data,
            'sign' => $signature
        ];
    }
    
    /**
     * Verify callback request with automatic signature extraction
     * 
     * @param array $data Callback data including 'sign' parameter
     * @param string $secretKey Secret key
     * @return array ['valid' => bool, 'error' => string|null]
     */
    public static function verifyCallback(array $data, string $secretKey): array
    {
        // Check if signature exists
        if (!isset($data['sign'])) {
            return [
                'valid' => false,
                'error' => 'Missing signature parameter'
            ];
        }
        
        $signature = $data['sign'];
        
        // Validate signature format
        if (!self::isValidSignatureFormat($signature)) {
            return [
                'valid' => false,
                'error' => 'Invalid signature format'
            ];
        }
        
        // Verify signature
        $isValid = self::verifySignature($data, $signature, $secretKey);
        
        return [
            'valid' => $isValid,
            'error' => $isValid ? null : 'Signature verification failed'
        ];
    }
}