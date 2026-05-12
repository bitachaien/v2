<?php
return  [
    'default' => 'mysql',
    'connections' => [
        'mysql' => [
            'driver'      => 'mysql',
            'host'        => getenv('DB_HOST') ?: '127.0.0.1',
            'port'        => getenv('DB_PORT') ?: '3306',
            'database'    => getenv('DB_DATABASE') ?: 'boyue',
            'username'    => getenv('DB_USERNAME') ?: 'boyue',
            'password'    => getenv('DB_PASSWORD') ?: 'nhDHjmHJTpmKsWye',
            'charset'     => getenv('DB_CHARSET') ?: 'utf8mb4',
            'collation'   => 'utf8mb4_general_ci',
            'prefix'      => getenv('DB_PREFIX') ?: '',
            'strict'      => true,
            'engine'      => null,
        ],
    ],
];