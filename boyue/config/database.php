<?php

return [
    
    'default' => 'mysql',

    
    'connections' => [
        'mysql' => [
            'driver'      => getenv('DB_CONNECTION') ?: 'mysql',
            'host'        => getenv('DB_HOST') ?: '127.0.0.1',
            'port'        => getenv('DB_PORT') ?: 3306,
            'database'    => getenv('DB_DATABASE') ?: 'df',
            'username'    => getenv('DB_USERNAME') ?: 'root',
            'password'    => getenv('DB_PASSWORD') ?: '',
            'unix_socket' => '',
            'charset'     => getenv('DB_CHARSET') ?: 'utf8mb4',
            'collation'   => 'utf8mb4_unicode_ci',
            'prefix'      => getenv('DB_PREFIX') ?: '',
            'strict'      => false,
            'engine'      => null,
            'options'     => extension_loaded('pdo_mysql') ? array_filter([
                \PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ],
    ],
];
