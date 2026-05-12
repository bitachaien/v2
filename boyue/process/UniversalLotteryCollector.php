<?php

namespace process;

use Workerman\Timer;
use support\Db;
use support\Log;

class UniversalLotteryCollector
{
    
    private static $apiBaseUrl = 'http://vip.lkag3.com/K25cefa4949df10';
    private static $ajaxApiUrl = 'https://www.lkag3.com/Issue/ajax_draw';  
    
    
    private static $apiPaths = [
        
        'cqssc' => '/CQSSC.josn',
        'xjssc' => '/XJSSC.josn',
        'tjssc' => '/TJSSC.josn',
        
        'jsk3' => '/JSKS.josn',
        'gxk3' => '/GXKS.josn',
        'fck3' => '/FC3D.josn',
        'ahk3' => '/AHKS.josn',
        'hebk3' => '/HEBKS.josn',
        'hubk3' => '/HUBKS.josn',
        'jlk3' => '/JLKS.josn',
        'bjk3' => '/BJKS.josn',
        'shk3' => '/SHKS.josn',
        'gsk3' => '/GSKS.josn',
        'guizhouk3' => '/GZKS.josn',
        
        'gd11x5' => '/GD11X5.josn',
        'sh11x5' => '/SH11X5.josn',
        'jx11x5' => '/JX11X5.josn',
        'js11x5' => '/JS11X5.josn',
        'sd11x5' => '/SD11X5.josn',
        
        'bjpk10' => '/BJPK10.josn',
        'xyft' => '/XYFT.josn',
        
        'lhc' => '/XGLHC.josn',
        
        'pl3' => '/PL3.josn',
        'bjkeno' => '/BJKL8.josn',
        
        'xy28' => '/XY28.josn',
        'jndpcdd' => '/JNDPCDD.josn',
        'cqpcdd' => '/CQPCDD.josn',
    ];
    
    
    private static $lotteryMapping = [
        
        'CQSSC' => 'cqssc',      
        'XJSSC' => 'xjssc',      
        'TJSSC' => 'tjssc',      
        
        
        'JSKS' => 'jsk3',        
        'GXKS' => 'gxk3',        
        'FC3D' => 'fck3',        
        'AHKS' => 'ahk3',        
        'HEBKS' => 'hebk3',      
        'HUBKS' => 'hubk3',      
        'JLKS' => 'jlk3',        
        'BJKS' => 'bjk3',        
        'SHKS' => 'shk3',        
        'GSKS' => 'gsk3',        
        'GZKS' => 'guizhouk3',   
        
        
        'GD11X5' => 'gd11x5',    
        'SH11X5' => 'sh11x5',    
        'JX11X5' => 'jx11x5',    
        'JS11X5' => 'js11x5',    
        'SD11X5' => 'sd11x5',    
        
        
        'BJPK10' => 'bjpk10',    
        'XYFT' => 'xyft',        
        
        
        'XGLHC' => 'lhc',        
        
        
        'PL3' => 'pl3',          
        'BJKL8' => 'bjkeno',     
        'TWBG' => 'twbg',        
        
        
        'XY28' => 'xy28',        
        'JNDPCDD' => 'jndpcdd',  
        'CQPCDD' => 'cqpcdd',    
        
        
        
        
        
        
        
        
        
        
        
        
    ];
    
    
    public function onWorkerStart($worker)
    {
        Log::info('通用彩票采集进程启动');
        
        
        self::collectAllLotteries();
        
        
        Timer::add(30, function() {
            self::collectAllLotteries();
        });
        
        
        Timer::add(3600, function() {
            $hour = date('H');
            if ($hour == '03') {
                self::cleanOldData();
            }
        });
    }
    
    
    private static function collectAllLotteries()
    {
        try {
            Log::info('开始采集所有彩种开奖数据...');
            
            $successCount = 0;
            $skipCount = 0;
            $errorCount = 0;
            $collectedNames = []; 
            
            
            $ajaxSuccess = self::collectByAjaxApi($collectedNames, $successCount, $skipCount, $errorCount);
            
            if ($ajaxSuccess) {
                Log::info("新API采集成功");
            } else {
                Log::warning("新API失败，使用老API逐个采集");
            }
            
            
            foreach (self::$apiPaths as $name => $apiPath) {
                
                if (in_array($name, $collectedNames)) {
                    continue;
                }
                
                $lottery = Db::table('caipiao_caipiao')
                    ->where('name', $name)
                    ->where('isopen', 1)
                    ->first();
                
                if (!$lottery) {
                    continue; 
                }
                
                
                $url = self::$apiBaseUrl . $apiPath;
                $response = self::httpGetWithRetry($url, 3, 10);
                
                if (!$response) {
                    $errorCount++;
                    continue;
                }
                
                
                $data = json_decode($response, true);
                
                
                if (isset($data['status']) && $data['status'] === false) {
                    
                    continue;
                }
                
                if (!$data || !is_array($data) || empty($data)) {
                    $errorCount++;
                    continue;
                }
                
                
                if (!isset($data[0]) || !is_array($data[0])) {
                    continue;
                }
                $item = $data[0];
                $expect = $item['issue'] ?? '';
                $opencode = $item['code'] ?? '';
                $opendate = $item['opendate'] ?? '';
                
                if (!$expect || !$opencode) {
                    $errorCount++;
                    continue;
                }
                
                
                $exists = Db::table('caipiao_kaijiang')
                    ->where('name', $name)
                    ->where('expect', $expect)
                    ->exists();
                
                if ($exists) {
                    $skipCount++;
                    continue;
                }
                
                
                $opentime = strtotime($opendate);
                if ($opentime === false) {
                    $opentime = time();
                }
                
                
                try {
                    Db::table('caipiao_kaijiang')->insert([
                        'name' => $name,
                        'title' => $lottery->title,
                        'expect' => $expect,
                        'opencode' => $opencode,
                        'opentime' => $opentime,
                        'source' => '第三方API',
                        'sourcecode' => $item['lotterycode'] ?? '',
                        'remarks' => '',
                        'addtime' => time(),
                        'isdraw' => 0,
                    ]);
                    
                    $successCount++;
                    Log::info("{$lottery->title} 采集成功: 期号 {$expect}, 号码 {$opencode}");
                    
                } catch (\Exception $e) {
                    if (strpos($e->getMessage(), 'Duplicate entry') === false) {
                        Log::error("{$lottery->title} 插入失败: " . $e->getMessage());
                        $errorCount++;
                    } else {
                        $skipCount++;
                    }
                }
            }
            
            Log::info("采集完成 - 成功: {$successCount}, 跳过: {$skipCount}, 失败: {$errorCount}");
            
        } catch (\Exception $e) {
            Log::error('采集异常: ' . $e->getMessage());
        }
    }
    
    
    private static function collectByAjaxApi(&$collectedNames, &$successCount, &$skipCount, &$errorCount)
    {
        try {
            
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => self::$ajaxApiUrl,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => http_build_query([
                    'lotterycode' => '',
                    'lotteryname' => ''
                ]),
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/x-www-form-urlencoded; charset=UTF-8',
                    'Accept: application/json',
                    'X-Requested-With: XMLHttpRequest',
                    'User-Agent: Mozilla/5.0'
                ],
                CURLOPT_TIMEOUT => 10,
                CURLOPT_SSL_VERIFYPEER => false,
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            
            if ($httpCode != 200 || !$response) {
                return false;
            }
            
            
            $data = json_decode($response, true);
            if (!$data || !isset($data['data']) || !is_array($data['data'])) {
                return false;
            }
            
            
            foreach ($data['data'] as $item) {
                $lotteryCode = $item['lotterycode'] ?? '';
                
                
                if (!isset(self::$lotteryMapping[$lotteryCode])) {
                    continue;
                }
                
                $name = self::$lotteryMapping[$lotteryCode];
                $expect = $item['issue'] ?? '';
                $opencode = $item['code'] ?? '';
                $opendate = $item['opendate'] ?? '';
                
                if (!$expect || !$opencode) {
                    continue;
                }
                
                
                $collectedNames[] = $name;
                
                
                $lottery = Db::table('caipiao_caipiao')
                    ->where('name', $name)
                    ->where('isopen', 1)
                    ->first();
                
                if (!$lottery) {
                    continue;
                }
                
                
                $exists = Db::table('caipiao_kaijiang')
                    ->where('name', $name)
                    ->where('expect', $expect)
                    ->exists();
                
                if ($exists) {
                    $skipCount++;
                    continue;
                }
                
                
                try {
                    Db::table('caipiao_kaijiang')->insert([
                        'name' => $name,
                        'title' => $lottery->title,
                        'expect' => $expect,
                        'opencode' => $opencode,
                        'opentime' => strtotime($opendate) ?: time(),
                        'source' => '第三方API',
                        'sourcecode' => $lotteryCode,
                        'remarks' => $item['lotteryname'] ?? '',
                        'addtime' => time(),
                        'isdraw' => 0,
                    ]);
                    
                    $successCount++;
                    Log::info("{$lottery->title} 采集成功(新API): 期号 {$expect}");
                    
                } catch (\Exception $e) {
                    if (strpos($e->getMessage(), 'Duplicate entry') === false) {
                        $errorCount++;
                    } else {
                        $skipCount++;
                    }
                }
            }
            
            return true; 
            
        } catch (\Exception $e) {
            Log::error('新API异常: ' . $e->getMessage());
            return false;
        }
    }
    
    
    private static function httpGetWithRetry(string $url, int $maxRetries = 3, int $timeout = 10)
    {
        $lastError = '';
        
        for ($retry = 0; $retry < $maxRetries; $retry++) {
            try {
                $ch = curl_init();
                curl_setopt_array($ch, [
                    CURLOPT_URL => $url,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT => $timeout,
                    CURLOPT_CONNECTTIMEOUT => 5,
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_USERAGENT => 'LotteryCollector/1.0',
                    CURLOPT_HTTPHEADER => ['Accept: application/json'],
                ]);
                
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $error = curl_error($ch);
                curl_close($ch);
                
                if ($response !== false && $httpCode === 200) {
                    return $response;
                }
                
                $lastError = $error ?: "HTTP {$httpCode}";
                
                
                if ($retry < $maxRetries - 1) {
                    usleep(100000 * pow(2, $retry)); 
                }
                
            } catch (\Exception $e) {
                $lastError = $e->getMessage();
            }
        }
        
        Log::warning("HTTP请求失败 URL={$url}, 重试{$maxRetries}次后放弃, 错误: {$lastError}");
        return false;
    }
    
    
    private static function cleanOldData()
    {
        try {
            $sixtyDaysAgo = time() - (60 * 24 * 3600);
            $deleted = Db::table('caipiao_kaijiang')
                ->where('addtime', '<', $sixtyDaysAgo)
                ->delete();
            
            if ($deleted > 0) {
                Log::info("清理了 {$deleted} 条60天前的开奖数据");
            }
        } catch (\Exception $e) {
            Log::error('清理旧数据失败: ' . $e->getMessage());
        }
    }
}

