<?php

namespace app\middleware;

use Webman\Http\Request;
use Webman\Http\Response;
use Webman\MiddlewareInterface;

class ExceptionMiddleware implements MiddlewareInterface
{
    public function process(Request $request, callable $handler): Response
    {
        try {
            return $handler($request);
        } catch (\Throwable $e) {
            
            error_log($e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
            
            $code = 500;
            $message = 'server_error';
            $data = null;
            
            
            if (getenv('APP_DEBUG') == '1') {
                $data = [
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                ];
            }
            
            return json(['code' => $code, 'message' => $message, 'data' => $data]);
        }
    }
}

