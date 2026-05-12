<?php

use plugin\admin\app\middleware\AccessControl;
use plugin\admin\app\middleware\AdminLogMiddleware;
use app\middleware\EncryptionMiddleware;

return [
    '' => [
        EncryptionMiddleware::class,
        AccessControl::class,
        AdminLogMiddleware::class,
    ]
];
