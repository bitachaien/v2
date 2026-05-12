<?php

namespace app\middleware;

use Webman\Http\Request;
use Webman\Http\Response;
use Webman\MiddlewareInterface;

class CorsMiddleware implements MiddlewareInterface
{
    public function process(Request $request, callable $handler): Response
    {
        $origin = $request->header('origin', '*');
        $allowedOrigins = config('cors.origins', ['*']);
        
        
        $allowOrigin = '*';
        if (!in_array('*', $allowedOrigins)) {
            $allowOrigin = in_array($origin, $allowedOrigins) ? $origin : '';
        }
        
        
        if ($request->method() == 'OPTIONS') {
            return response('')
                ->withHeaders([
                    'Access-Control-Allow-Origin' => $allowOrigin ?: $origin,
                    'Access-Control-Allow-Credentials' => 'true',
                    'Access-Control-Allow-Headers' => 'Authorization, Content-Type, X-Requested-With, Accept, X-Encrypted',
                    'Access-Control-Allow-Methods' => 'GET, POST, PUT, DELETE, OPTIONS, PATCH',
                    'Access-Control-Expose-Headers' => 'X-Encrypted',
                    'Access-Control-Max-Age' => '86400',
                ]);
        }
        
        $response = $handler($request);
        
        
        if ($allowOrigin || in_array('*', $allowedOrigins)) {
            $response->withHeaders([
                'Access-Control-Allow-Origin' => $allowOrigin ?: $origin,
                'Access-Control-Allow-Credentials' => 'true',
                'Access-Control-Allow-Headers' => 'Authorization, Content-Type, X-Requested-With, Accept, X-Encrypted',
                'Access-Control-Allow-Methods' => 'GET, POST, PUT, DELETE, OPTIONS, PATCH',
                'Access-Control-Expose-Headers' => 'X-Encrypted',
                'Access-Control-Max-Age' => '86400',
            ]);
        }
        
        return $response;
    }
}

