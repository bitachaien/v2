<?php

namespace app\middleware;

use app\Utils\Crypto;
use Webman\MiddlewareInterface;
use Webman\Http\Response;
use Webman\Http\Request;

class EncryptionMiddleware implements MiddlewareInterface
{
    private static $excludePaths = [
        '/im/upload',
        '/upload',
    ];
    
    private static function isEnabled(): bool
    {
        return config('app.encryption_enabled', false);
    }
    
    public function process(Request $request, callable $handler): Response
    {
        $path = $request->path();
        foreach (self::$excludePaths as $exclude) {
            if (strpos($path, $exclude) !== false) {
                return $handler($request);
            }
        }
        
        if (self::isEnabled() && $request->header('X-Encrypted') === '1') {
            $rawBody = $request->rawBody();
            
            if (!empty($rawBody)) {
                $decrypted = Crypto::decrypt($rawBody);
                
                if ($decrypted !== null && is_array($decrypted)) {
                    
                    $reflection = new \ReflectionClass($request);
                    $current = $reflection;
                    
                    
                    while ($current) {
                        if ($current->hasProperty('data')) {
                            $prop = $current->getProperty('data');
                            $prop->setAccessible(true);
                            $data = $prop->getValue($request);
                            $data['post'] = $decrypted;
                            $prop->setValue($request, $data);
                            break;
                        }
                        $current = $current->getParentClass();
                    }
                }
            }
        }
        
        $response = $handler($request);
        
        if (self::isEnabled()) {
            $content = $response->rawBody();
            $data = json_decode($content, true);
            
            if ($data !== null) {
                $encrypted = Crypto::encrypt($data);
                return new Response(200, [
                    'Content-Type' => 'text/plain',
                    'X-Encrypted' => '1'
                ], $encrypted);
            }
        }
        
        return $response;
    }
}
