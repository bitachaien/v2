<?php

namespace app\controller\api;

use support\Request;
use support\Db;
use support\Log;

class CollectorController
{
    
    private $apiBaseUrl = 'http://vip.lkag3.com/K25cefa4949df10';
    
    private $lotteryApis = [
        
        'cqssc' => '/CQSSC.josn',
        'xjssc' => '/XJSSC.josn',
        'tjssc' => '/TJSSC.josn',
        
        
        'jsk3' => '/JSKS.josn',
        'ahk3' => '/AHKS.josn',
        'bjk3' => '/BJKS.josn',
        'gxk3' => '/GXKS.josn',
        'hebk3' => '/HEBKS.josn',
        'hubk3' => '/HUBKS.josn',
        'jlk3' => '/JLKS.josn',
        'jxk3' => '/JXKS.josn',
        'shk3' => '/SHKS.josn',
        'gsk3' => '/GSKS.josn',
        
        
        'gd11x5' => '/GD11X5.josn',
        'sh11x5' => '/SH11X5.josn',
        'jx11x5' => '/JX11X5.josn',
        
        
        'bjpk10' => '/BJPK10.josn',
    ];
    
    
    public function trigger(Request $request)
    {
        try {
            $name = $request->post('name'); 
            
            if ($name) {
                
                if (!isset($this->lotteryApis[$name])) {
                    return json([
                        'code' => 400,
                        'message' => '彩种không tồn tại',
                        'data' => null
                    ]);
                }
                
                $result = $this->collectOne($name);
                
                return json([
                    'code' => 0,
                    'message' => 'ok',
                    'data' => $result
                ]);
            } else {
                
                $results = $this->collectAll();
                
                return json([
                    'code' => 0,
                    'message' => 'ok',
                    'data' => $results
                ]);
            }
            
        } catch (\Exception $e) {
            Log::error('手动触发采集Thất bại: ' . $e->getMessage());
            return json([
                'code' => 500,
                'message' => '采集Thất bại: ' . $e->getMessage(),
                'data' => null
            ]);
        }
    }
    
    
    public function status(Request $request)
    {
        try {
            $lotteries = Db::table('caipiao_caipiao')
                ->where('isopen', 1)
                ->orderBy('allsort', 'asc')
                ->get();
            
            $statusList = [];
            
            foreach ($lotteries as $lottery) {
                
                $latest = Db::table('caipiao_kaijiang')
                    ->where('name', $lottery->name)
                    ->orderBy('id', 'desc')
                    ->first();
                
                $statusList[] = [
                    'name' => $lottery->name,
                    'title' => $lottery->title,
                    'latest_expect' => $latest->expect ?? '',
                    'latest_opencode' => $latest->opencode ?? '',
                    'latest_time' => $latest->opentime ?? 0,
                    'latest_time_format' => $latest ? date('Y-m-d H:i:s', $latest->opentime) : '',
                    'total_count' => Db::table('caipiao_kaijiang')->where('name', $lottery->name)->count(),
                ];
            }
            
            return json([
                'code' => 0,
                'message' => 'ok',
                'data' => [
                    'lotteries' => $statusList,
                    'server_time' => time(),
                    'server_time_format' => date('Y-m-d H:i:s'),
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Lấy采集状态Thất bại: ' . $e->getMessage());
            return json([
                'code' => 500,
                'message' => 'Lấy状态Thất bại',
                'data' => null
            ]);
        }
    }
    
    
    private function collectAll()
    {
        $results = [];
        
        foreach ($this->lotteryApis as $name => $apiPath) {
            try {
                $results[$name] = $this->collectOne($name);
            } catch (\Exception $e) {
                $results[$name] = [
                    'success' => false,
                    'message' => $e->getMessage(),
                    'new_count' => 0
                ];
            }
        }
        
        return $results;
    }
    
    
    private function collectOne($name)
    {
        $apiPath = $this->lotteryApis[$name] ?? null;
        
        if (!$apiPath) {
            return ['success' => false, 'message' => '彩种không tồn tại', 'new_count' => 0];
        }
        
        
        $lottery = Db::table('caipiao_caipiao')
            ->where('name', $name)
            ->first();
        
        if (!$lottery) {
            return ['success' => false, 'message' => '彩种không tồn tại', 'new_count' => 0];
        }
        
        $url = $this->apiBaseUrl . $apiPath;
        
        
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
            return [
                'success' => true,
                'message' => 'đã tồn tại',
                'new_count' => 0,
                'expect' => $expect,
                'opencode' => $opencode
            ];
        }
        
        
        try {
            Db::table('caipiao_kaijiang')->insert([
                'name' => $name,
                'title' => $lottery->title,
                'expect' => $expect,
                'opencode' => $opencode,
                'opentime' => $opentime,
                'source' => '第三方API',
                'sourcecode' => '',
                'remarks' => '',
                'addtime' => time(),
                'isdraw' => 0,
            ]);
            
            return [
                'success' => true,
                'message' => '采集Thành công',
                'new_count' => 1,
                'expect' => $expect,
                'opencode' => $opencode
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'dữ liệu库插入Thất bại: ' . $e->getMessage(),
                'new_count' => 0
            ];
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
}

