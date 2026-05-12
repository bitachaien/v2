<?php

return [
    'default' => [
        'host'     => getenv('REDIS_HOST') ?: '127.0.0.1',
        'port'     => getenv('REDIS_PORT') ?: 6379,
        'password' => getenv('REDIS_PASSWORD') ?: null,
        'database' => getenv('REDIS_DB') ?: 0,
        'timeout'  => 2.0,
    ],
];

