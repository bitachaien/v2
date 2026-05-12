<?php

use support\Log;
use support\Request;
use app\process\Http;

global $argv;

return [
    'webman' => [
        'handler' => Http::class,
        'listen' => 'http://0.0.0.0:8788',
        'count' => cpu_count() * 4,
        'user' => '',
        'group' => '',
        'reusePort' => false,
        'eventLoop' => '',
        'context' => [],
        'constructor' => [
            'requestClass' => Request::class,
            'logger' => Log::channel('default'),
            'appPath' => app_path(),
            'publicPath' => public_path()
        ]
    ],
    
    'monitor' => [
        'handler' => app\process\Monitor::class,
        'reloadable' => false,
        'constructor' => [
            
            'monitorDir' => array_merge([
                app_path(),
                config_path(),
                base_path() . '/process',
                base_path() . '/support',
                base_path() . '/resource',
                base_path() . '/.env',
            ], glob(base_path() . '/plugin/*/app'), glob(base_path() . '/plugin/*/config'), glob(base_path() . '/plugin/*/api')),
            
            'monitorExtensions' => [
                'php', 'html', 'htm', 'env'
            ],
            'options' => [
                'enable_file_monitor' => !in_array('-d', $argv) && DIRECTORY_SEPARATOR === '/',
                'enable_memory_monitor' => DIRECTORY_SEPARATOR === '/',
            ]
        ]
    ],
    
    
    'universal-lottery-collector' => [
        'handler' => process\UniversalLotteryCollector::class,
        'listen' => '', 
        'count' => 1,   
        'reloadable' => true,
        'constructor' => []
    ],
    
    
    
    
    
    
    
    
    
    
    
    'animal-lottery-collector' => [
        'handler' => process\AnimalLotteryCollector::class,
        'listen' => '',
        'count' => 1,
        'reloadable' => true,
        'constructor' => []
    ],
    
    
    'system-lottery-generator' => [
        'handler' => process\SystemLotteryGenerator::class,
        'listen' => '',
        'count' => 1,
        'reloadable' => true,
        'constructor' => []
    ],
    
    
    'lottery-settlement' => [
        'handler' => process\LotterySettlement::class,
        'listen' => '',
        'count' => 1,
        'reloadable' => true,
        'constructor' => []
    ],
    
    
    'scheduled-tasks' => [
        'handler' => process\ScheduledTasks::class,
        'listen' => '',
        'count' => 1,
        'reloadable' => true,
        'constructor' => []
    ],
    
    
    'websocket' => [
        'handler' => process\WebSocketServer::class,
        'listen' => 'websocket://0.0.0.0:8789',
        'count' => 4,  
        'reloadable' => true,
        'constructor' => []
    ],
    
    
    'admin-websocket' => [
        'handler' => process\AdminWebSocketServer::class,
        'listen' => 'websocket://0.0.0.0:8790',
        'count' => 2,  
        'reloadable' => true,
        'constructor' => []
    ],
    
    
    'yuebao-interest' => [
        'handler' => process\YuebaoInterest::class,
        'listen' => '',
        'count' => 1,
        'reloadable' => true,
        'constructor' => []
    ],
    
    
    'agent-settlement' => [
        'handler' => process\AgentSettlementTask::class,
        'listen' => '',
        'count' => 1,
        'reloadable' => false, 
        'constructor' => []
    ],
    
    
    'robot-auto-bet' => [
        'handler' => process\RobotAutoBet::class,
        'listen' => '',
        'count' => 1,
        'reloadable' => true,
        'constructor' => []
    ],
    
    
    'rebate-settlement' => [
        'handler' => process\RebateSettlement::class,
        'listen' => '',
        'count' => 1,
        'reloadable' => true,
        'constructor' => []
    ]
];
