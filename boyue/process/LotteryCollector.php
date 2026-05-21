<?php

namespace process;

use Workerman\Timer;
use support\Db;
use support\Log;

class LotteryCollector
{
    
    private static $apiBaseUrl = 'http://vip.lkag3.com/K25cefa4949df10';
    
    
    private static $lotteryApis = [
        
        'cqssc' => '/CQSSC.josn',     
        'xjssc' => '/XJSSC.josn',     
        'tjssc' => '/TJSSC.josn',     
        
        
        'jsk3' => '/JSKS.josn',       
        'gxk3' => '/GXKS.josn',       
        'fck3' => '/FC3D.josn',       
        
        
        
        
        
        
        
        
        
        'gd11x5' => '/GD11X5.josn',   
        'sh11x5' => '/SH11X5.josn',   
        'jx11x5' => '/JX11X5.josn',   
        'js11x5' => '/JS11X5.josn',   
        'sd11x5' => '/SD11X5.josn',   
        
        
        'bjpk10' => '/BJPK10.josn',   
        'xyft' => '/XYFT.josn',       
        
        
        'lhc' => '/XGLHC.josn',       
    ];
    
    
    public function onWorkerStart($worker)
    {
        Log::info('Xổ số采集进程启动');
        
        
        $this->collectAll();
        
        
        Timer::add(30, function() {
            $this->collectAll();
        });
        
        
        Timer::add(3600, function() {
            $hour = date('H');
            if ($hour == '03') {
                $this->cleanOldData(7);
            }
        });
    }
    
    
    private function collectAll()
    {
        Log::info('开始采集Mở thưởngdữ liệu...');
        $successCount = 0;
        $failCount = 0;
        
        foreach (self::$lotteryApis as $name => $apiPath) {
            try {
                
                $lottery = Db::table('caipiao_caipiao')
                    ->where('name', $name)
                    ->where('isopen', 1)
                    ->first();
                
                if (!$lottery) {
                    continue; 
                }
                
                
                $result = $this->collectOne($name, $apiPath, $lottery->title);
                
                if ($result['success']) {
                    $successCount++;
                    if ($result['new_count'] > 0) {
                        Log::info("{$lottery->title} 采集Thành công，新增 {$result['new_count']} 期");
                    }
                } else {
                    $failCount++;
                    Log::warning("{$lottery->title} 采集Thất bại: {$result['message']}");
                }
                
            } catch (\Exception $e) {
                $failCount++;
                Log::error("采集 {$name} 异常: " . $e->getMessage());
            }
        }
        
        Log::info("采集完成，Thành công: {$successCount}, Thất bại: {$failCount}");
    }
    
    
    private function collectOne($name, $apiPath, $title)
    {
        $url = self::$apiBaseUrl . $apiPath;
        
        
        $response = $this->httpGet($url);
        
        if (!$response) {
            return ['success' => false, 'message' => 'HTTPYêu cầu thất bại', 'new_count' => 0];
        }
        
        
        $data = json_decode($response, true);
        
        if (!$data || !isset($data[0])) {
            return ['success' => false, 'message' => 'dữ liệu格式Lỗi', 'new_count' => 0];
        }
        
        
        $expect = $data[0]['issue'] ?? '';
        $opencode = $data[0]['code'] ?? '';
        $opendate = $data[0]['opendate'] ?? '';
        
        if (!$expect || !$opencode) {
            return ['success' => false, 'message' => '期号hoặcMở thưởng号码为空', 'new_count' => 0];
        }
        
        
        $opentime = $this->parseTime($opendate);
        
        
        $exists = Db::table('caipiao_kaijiang')
            ->where('name', $name)
            ->where('expect', $expect)
            ->exists();
        
        if ($exists) {
            return ['success' => true, 'message' => 'đã tồn tại', 'new_count' => 0];
        }
        
        
        try {
            Db::table('caipiao_kaijiang')->insert([
                'name' => $name,
                'title' => $title,
                'expect' => $expect,
                'opencode' => $opencode,
                'opentime' => $opentime,
                'source' => '第三方API',
                'sourcecode' => '',
                'remarks' => '',
                'addtime' => time(),
                'isdraw' => 0,
            ]);
            
            return ['success' => true, 'message' => '采集Thành công', 'new_count' => 1];
            
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'dữ liệu库插入Thất bại: ' . $e->getMessage(), 'new_count' => 0];
        }
    }
    
    
    private function httpGet($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        
        $result = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            Log::error("HTTPYêu cầu thất bại: {$url}, Lỗi: {$error}");
            return false;
        }
        
        return $result;
    }
    
    
    private function parseTime($dateStr)
    {
        if (empty($dateStr)) {
            return time();
        }
        
        
        $dateStr = str_replace('/', '-', $dateStr);
        
        $timestamp = strtotime($dateStr);
        
        return $timestamp ?: time();
    }
    
    
    private function cleanOldData($days = 7)
    {
        try {
            $deleteTime = time() - ($days * 86400);
            
            $count = Db::table('caipiao_kaijiang')
                ->where('addtime', '<', $deleteTime)
                ->delete();
            
            if ($count > 0) {
                Log::info("清理了 {$count} 条 {$days} 天前的Mở thưởngdữ liệu");
            }
            
        } catch (\Exception $e) {
            Log::error("清理旧dữ liệuThất bại: " . $e->getMessage());
        }
    }
}

