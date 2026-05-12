<?php

use Workerman\Crontab\Crontab;
use app\service\CollectorService;

return [
    
    'collector' => [
        'handler' => function () {
            
            new Crontab('*/5 * * * *', function() {
                try {
                    CollectorService::collectAll();
                } catch (\Exception $e) {
                    error_log('采集任务异常: ' . $e->getMessage());
                }
            });
        },
    ],
];

