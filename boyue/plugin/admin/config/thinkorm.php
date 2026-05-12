<?php
return [
    'default' => 'mysql',
    'connections' => [
        'mysql' => [
            
            'type' => 'mysql',
            
            'hostname' => '127.0.0.1',
            
            'database' => 'df',
            
            'username' => 'df',
            
            'password' => 'LfxhXsSLNfcAtTZ7',
            
            'hostport' => 3306,
            
            'params' => [
                
                \PDO::ATTR_TIMEOUT => 3,
            ],
            
            'charset' => 'utf8mb4',
            
            'prefix' => '',
            
            'break_reconnect' => true,
            
            'trigger_sql' => true,
            
            'bootstrap' =>  ''
        ],
    ],
];