<?php

namespace app\Utils;

class Crypto
{
    
    private static $key = 'QD2024SecretKey1234567890ABCDEF0';
    
    
    private static $iv = 'QD2024IV12345678';
    
    
    public static function encrypt($data): string
    {
        $json = json_encode($data, JSON_UNESCAPED_UNICODE);
        $encrypted = openssl_encrypt(
            $json,
            'AES-256-CBC',
            self::$key,
            OPENSSL_RAW_DATA,
            self::$iv
        );
        return base64_encode($encrypted);
    }
    
    
    public static function decrypt(string $encryptedData)
    {
        $data = base64_decode($encryptedData);
        $decrypted = openssl_decrypt(
            $data,
            'AES-256-CBC',
            self::$key,
            OPENSSL_RAW_DATA,
            self::$iv
        );
        
        if ($decrypted === false) {
            return null;
        }
        
        return json_decode($decrypted, true);
    }
    
    
    public static function isEncryptedRequest(): bool
    {
        return isset($_SERVER['HTTP_X_ENCRYPTED']) && $_SERVER['HTTP_X_ENCRYPTED'] === '1';
    }
    
    
    public static function getDecryptedInput(): ?array
    {
        $input = file_get_contents('php://input');
        
        if (empty($input)) {
            return null;
        }
        
        
        if (self::isEncryptedRequest()) {
            return self::decrypt($input);
        }
        
        return json_decode($input, true);
    }
}
