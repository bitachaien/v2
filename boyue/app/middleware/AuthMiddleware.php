<?php

namespace app\middleware;

use Webman\Http\Request;
use Webman\Http\Response;
use Webman\MiddlewareInterface;
use app\constants\ErrorCode;
use app\service\JwtService;

class AuthMiddleware implements MiddlewareInterface
{
    
    protected $except = [
        '/api/v1/index',
        '/api/v1/lottery-hall',
        '/api/v1/banners',
        '/api/v1/notices',
        '/api/v1/notice/',
        '/api/v1/config',
        '/api/v1/auth/login',
        '/api/v1/auth/refresh',
        '/api/v1/register',
        '/api/v1/check-username',
        '/api/v1/validate-reccode',
        '/api/v1/game/platforms',
        '/api/v1/game/list',
        '/api/v1/game/hot',
        '/api/v1/game/categories',
        '/api/v1/entertainment/platforms',
        '/api/v1/rebate/tier-rates',
    ];

    
    public function process(Request $request, callable $handler): Response
    {
        $path = $request->path();
        
        
        foreach ($this->except as $pattern) {
            if ($path === $pattern || str_starts_with($path, $pattern)) {
                return $handler($request);
            }
        }
        
        
        $token = $request->header('authorization', '');
        
        
        if (str_starts_with($token, 'Bearer ')) {
            $token = substr($token, 7);
        }
        
        
        if (empty($token)) {
            $token = $request->header('token', '');
        }
        if (empty($token)) {
            $token = $request->get('token', '');
        }
        if (empty($token)) {
            $token = $request->post('token', '');
        }
        
        if (empty($token)) {
            return json([
                'code' => ErrorCode::TOKEN_MISSING,
                'message' => ErrorCode::getMessage(ErrorCode::TOKEN_MISSING),
                'data' => null
            ], 401);
        }
        
        
        $result = JwtService::validateToken($token);
        
        if (!$result['valid']) {
            $errorCode = ErrorCode::TOKEN_INVALID;
            if (str_contains($result['error'] ?? '', '过期')) {
                $errorCode = ErrorCode::TOKEN_EXPIRED;
            }
            
            return json([
                'code' => $errorCode,
                'message' => $result['error'] ?? ErrorCode::getMessage($errorCode),
                'data' => null
            ], 401);
        }
        
        $payload = $result['payload'];
        
        
        if (JwtService::isRefreshToken($payload)) {
            return json([
                'code' => ErrorCode::TOKEN_INVALID,
                'message' => '请使用Access Token',
                'data' => null
            ], 401);
        }
        
        
        $request->userId = $payload->uid ?? 0;
        $request->username = $payload->username ?? '';
        $request->token = $token;
        $request->jwtPayload = $payload;
        
        return $handler($request);
    }
}

