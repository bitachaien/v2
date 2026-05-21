<?php

namespace process;

use Workerman\Timer;
use support\Db;
use support\Log;
use GuzzleHttp\Client;


class AnimalLotteryCollector
{
    
    private static $apiBaseUrl = 'https://sdjwchartjbda.t4nmvxp8jsgq.net/gameChart';
    
    
    private static $client = null;
    
    
    private static $lotteries = [
        'yfdwc' => [
            'gameId' => 'OG1ANL',
            'title' => '一分动物彩'
        ],
        'sfdwc' => [
            'gameId' => 'OG3ANL',
            'title' => '三分动物彩'
        ],
        'wfdwc' => [
            'gameId' => 'OG5ANL',
            'title' => '五分动物彩'
        ],
    ];
    
    
    public function onWorkerStart($worker)
    {
        Log::info('动物彩采集进程启动');
        
        
        $this->collectAll();
        
        
        
        Timer::add(15, function() {
            $this->collectAll();
        });
    }
    
    
    private function collectAll()
    {
        foreach (self::$lotteries as $name => $config) {
            try {
                
                $lottery = Db::table('caipiao_caipiao')
                    ->where('name', $name)
                    ->where('isopen', 1)
                    ->first();
                
                if (!$lottery) {
                    continue; 
                }
                
                
                $this->collectLatest($name, $config['gameId'], $config['title']);
                
            } catch (\Exception $e) {
                Log::error("采集动物彩 {$name} 异常: " . $e->getMessage());
            }
        }
    }
    
    
    private function collectLatest($name, $gameId, $title)
    {
        try {
            
            $url = self::$apiBaseUrl . '/getLotteryRecord';
            $response = $this->httpPost($url, [
                'gameId' => $gameId,
                'issueDate' => date('Y-m-d'),
                'pageNo' => 1,
                'pageSize' => 5
            ]);
            
            if (!$response) {
                return;
            }
            
            $result = json_decode($response, true);
            
            
            $isSuccess = (isset($result['status']) && $result['status'] === 'success') ||
                         (isset($result['code']) && $result['code'] === 'success');
            
            if (!$isSuccess) {
                Log::warning("动物彩API返回Thất bại: " . json_encode($result));
                return;
            }
            
            if (!isset($result['data']['records']) || empty($result['data']['records'])) {
                return;
            }
            
            $newCount = 0;
            
            foreach ($result['data']['records'] as $record) {
                $issueNo = $record['issue'] ?? '';
                $opencode = $record['result'] ?? '';
                $endTime = $record['frontEndTime'] ?? '';
                
                if (empty($issueNo) || empty($opencode)) {
                    continue;
                }
                
                
                $exists = Db::table('caipiao_kaijiang')
                    ->where('name', $name)
                    ->where('expect', $issueNo)
                    ->exists();
                
                if ($exists) {
                    continue;
                }
                
                
                $opentime = 0;
                if (!empty($endTime)) {
                    $opentime = strtotime($endTime);
                    if ($opentime === false) {
                        $opentime = time();
                    }
                } else {
                    $opentime = time();
                }
                
                
                try {
                    Db::table('caipiao_kaijiang')->insert([
                        'name' => $name,
                        'title' => $title,
                        'expect' => $issueNo,
                        'opencode' => $opencode,
                        'opentime' => $opentime,
                        'source' => '第三方API',
                        'sourcecode' => $gameId,
                        'remarks' => '',
                        'addtime' => time(),
                        'isdraw' => 0,
                    ]);
                    
                    $newCount++;
                    Log::info("{$title} 采集Thành công: 期号 {$issueNo}, Mở thưởng号码 {$opencode}");
                    
                } catch (\Exception $e) {
                    
                    if (strpos($e->getMessage(), 'Duplicate entry') === false) {
                        Log::error("{$title} 插入Thất bại: " . $e->getMessage());
                    }
                }
            }
            
            if ($newCount > 0) {
                Log::info("{$title} 本次采集新增 {$newCount} 期");
            }
            
        } catch (\Exception $e) {
            Log::error("采集 {$name} 最新Mở thưởngThất bại: " . $e->getMessage());
        }
    }
    
    
    private function getClient()
    {
        if (self::$client === null) {
            self::$client = new Client([
                'timeout' => 15,
                'verify' => false,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ]
            ]);
        }
        return self::$client;
    }
    
    
    private function httpPost($url, $data)
    {
        try {
            $client = $this->getClient();
            $response = $client->post($url, [
                'json' => $data
            ]);
            
            return $response->getBody()->getContents();
            
        } catch (\Exception $e) {
            Log::warning("动物彩APIYêu cầu thất bại: {$url}, Lỗi: " . $e->getMessage());
            return false;
        }
    }
}
