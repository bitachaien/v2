<?php

return [
    
    
    
    'system_lotteries' => [
        
        'f1k3'   => ['type' => 'k3', 'title' => '1分快3', 'interval' => 60],
        'f3k3'   => ['type' => 'k3', 'title' => '3分快3', 'interval' => 180],
        'f5k3'   => ['type' => 'k3', 'title' => '5分快3', 'interval' => 300],
        'sfks'   => ['type' => 'k3', 'title' => '10分快3', 'interval' => 600],
        'jisk3'  => ['type' => 'k3', 'title' => '极速快3', 'interval' => 60],
        
        
        'txssc'  => ['type' => 'ssc', 'title' => '腾讯分分彩', 'interval' => 60],
        'dfssc'  => ['type' => 'ssc', 'title' => '大发2分彩', 'interval' => 120],
        'ssc1fc' => ['type' => 'ssc', 'title' => '大发分分彩', 'interval' => 60],
        'ssc3fc' => ['type' => 'ssc', 'title' => '3分彩', 'interval' => 180],
        'ssc5fc' => ['type' => 'ssc', 'title' => '5分彩', 'interval' => 300],
        
        
        'pk101'  => ['type' => 'pk10', 'title' => '1分赛车', 'interval' => 60],
        'pk103'  => ['type' => 'pk10', 'title' => '3分赛车', 'interval' => 180],
        'pk105'  => ['type' => 'pk10', 'title' => '5分赛车', 'interval' => 300],
        'dfpk10' => ['type' => 'pk10', 'title' => '台湾PK10', 'interval' => 300],
        
        
        'yf11x5' => ['type' => 'x5', 'title' => '1分11选5', 'interval' => 60],
        
        
        'dflhc'  => ['type' => 'lhc', 'title' => '大发六合彩', 'interval' => 600],
        'lhc1f'  => ['type' => 'lhc', 'title' => '1分六合', 'interval' => 60],
        'lhc5f'  => ['type' => 'lhc', 'title' => '5分六合', 'interval' => 300],
        
        
        'yfxy28' => ['type' => 'xy28', 'title' => '1分幸运28', 'interval' => 60],
    ],
    
    
    'collector' => [
        'base_url' => 'http://vip.lkag3.com/K25cefa4949df10',
        'timeout' => 10,
        'retry_times' => 3,
        'interval' => 30,  
        
        
        'api_paths' => [
            
            'jsk3' => '/JSKS.josn',
            'gxk3' => '/GXKS.josn',
            'ahk3' => '/AHKS.josn',
            'hebk3' => '/HEBKS.josn',
            'hubk3' => '/HUBKS.josn',
            'shk3' => '/SHKS.josn',
            
            'cqssc' => '/CQSSC.josn',
            'xjssc' => '/XJSSC.josn',
            'tjssc' => '/TJSSC.josn',
            
            'bjpk10' => '/BJPK10.josn',
            'xyft' => '/XYFT.josn',
            
            'gd11x5' => '/GD11X5.josn',
            'sh11x5' => '/SH11X5.josn',
            
            'lhc' => '/XGLHC.josn',
        ],
        
        
        'code_mapping' => [
            'JSKS' => 'jsk3',
            'GXKS' => 'gxk3',
            'AHKS' => 'ahk3',
            'CQSSC' => 'cqssc',
            'BJPK10' => 'bjpk10',
            
        ],
    ],
    
    
    
    'k3_plays' => [
        
        'hz' => [
            'pattern' => '/^k3hz(\d+)$/',
            'check' => 'sum_equal',  
        ],
        'hzbig' => [
            'pattern' => '/^k3hzbig$/',
            'check' => 'sum_gte',
            'value' => 11,
        ],
        'hzsmall' => [
            'pattern' => '/^k3hzsmall$/',
            'check' => 'sum_lte', 
            'value' => 10,
        ],
        
        'sthtx' => [
            'pattern' => '/^k3sthtx$/',
            'check' => 'triple_any',
        ],
        
    ],
    
    
    'business' => [
        'bet_min' => 1,           
        'bet_max' => 100000,      
        'close_seconds' => 10,    
        'safety_buffer' => 3,     
    ],
];
