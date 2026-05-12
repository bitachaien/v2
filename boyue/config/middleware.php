<?php

return [
    '' => [
        
        app\middleware\ExceptionMiddleware::class,
        app\middleware\CorsMiddleware::class,
        app\middleware\RateLimitMiddleware::class,  
        app\middleware\EncryptionMiddleware::class, 
    ]
];