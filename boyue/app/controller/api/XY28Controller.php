<?php

namespace app\controller\api;

use support\Request;
use support\Db;
use app\constants\ErrorCode;

class XY28Controller
{
    
    private function validateLottery($code)
    {
        $lottery = Db::table('caipiao_caipiao')
            ->where('name', $code)
            ->where('isopen', 1)
            ->first();
        
        if (!$lottery) {
            return null;
        }
        
        return $lottery;
    }
    
    
    
    
    public function expect(Request $request, $code)
    {
        try {
            $lottery = $this->validateLottery($code);
            if (!$lottery) {
                return json(['code' => ErrorCode::INVALID_PARAMS, 'message' => '彩种不存在或已关闭', 'data' => null]);
            }
            
            $now = time();
            
            
            $recentKj = Db::table('caipiao_kaijiang')
                ->where('name', $code)
                ->orderBy('id', 'desc')
                ->limit(2)
                ->get();
            
            $isThirdParty = false;
            $thirdPartyInterval = 0;
            
            if (count($recentKj) >= 2) {
                $interval = abs($recentKj[0]->opentime - $recentKj[1]->opentime);
                
                if ($interval > 120) {
                    $isThirdParty = true;
                    $thirdPartyInterval = $interval;
                }
            }
            
            $lastResult = count($recentKj) > 0 ? $recentKj[0] : null;
            $lastFullExpect = $lastResult ? $lastResult->expect : '';
            
            $currFullExpect = '';
            $remainTime = 0;
            $nextOpenTime = $now;
            
            if ($isThirdParty && $lastResult) {
                
                $lastExpectNum = $lastResult->expect;
                $lastOpenTime = $lastResult->opentime;
                
                
                $nextOpenTime = $lastOpenTime + $thirdPartyInterval;
                $remainTime = $nextOpenTime - $now;
                
                
                if ($remainTime < -60) {
                    $remainTime = 0;
                } else if ($remainTime < 0) {
                    $remainTime = 0;
                }
                
                
                if (preg_match('/^(\d{6,8})(\d{2,4})$/', $lastExpectNum, $matches)) {
                    $datePrefix = $matches[1];
                    $issueNum = intval($matches[2]);
                    $numLen = strlen($matches[2]);
                    
                    $today6 = date('ymd');
                    $today8 = date('Ymd');
                    
                    if ($datePrefix == $today6 || $datePrefix == $today8) {
                        $nextIssueNum = $issueNum + 1;
                        $currFullExpect = $datePrefix . str_pad($nextIssueNum, $numLen, '0', STR_PAD_LEFT);
                    } else {
                        $newDatePrefix = (strlen($datePrefix) == 6) ? $today6 : $today8;
                        $currFullExpect = $newDatePrefix . str_pad(1, $numLen, '0', STR_PAD_LEFT);
                    }
                } else {
                    $currFullExpect = strval(intval($lastExpectNum) + 1);
                }
            } else {
                
                $issueInfo = $this->calculateCurrentIssue($lottery, $now);
                $currFullExpect = $issueInfo['currFullExpect'];
                $remainTime = $issueInfo['remainTime'];
                $nextOpenTime = $issueInfo['nextOpenTime'];
            }
            
            
            $openCodes = [];
            if ($lastResult && $lastResult->opencode) {
                $openCodes = explode(',', $lastResult->opencode);
            }
            
            return json([
                'code' => ErrorCode::SUCCESS,
                'message' => '获取成功',
                'data' => [
                    'lotteryCode' => $code,
                    'lotteryTitle' => $lottery->title ?? $code,  
                    'currExpect' => substr($currFullExpect, -4),
                    'currFullExpect' => $currFullExpect,
                    'lastFullExpect' => $lastFullExpect,
                    'openCodes' => $openCodes,
                    'opencode' => $lastResult ? $lastResult->opencode : '',
                    'remainMs' => max(0, $remainTime) * 1000,
                    'remainTime' => max(0, $remainTime),
                    'openTime' => date('Y-m-d H:i:s', $nextOpenTime),
                    'openTimestamp' => $nextOpenTime
                ]
            ]);
        } catch (\Exception $e) {
            \support\Log::error('获取期号失败: ' . $e->getMessage());
            return json(['code' => ErrorCode::SYSTEM_ERROR, 'message' => '获取失败', 'data' => null]);
        }
    }
    
    
    public function lastResult(Request $request, $code)
    {
        try {
            $result = Db::table('caipiao_kaijiang')
                ->where('name', $code)
                ->where('opentime', '<=', time())
                ->orderBy('id', 'desc')
                ->first();
            
            if (!$result) {
                return json(['code' => ErrorCode::DATA_NOT_FOUND, 'message' => '暂无开奖数据', 'data' => null]);
            }
            
            $openCode = explode(',', $result->opencode);
            $sum = array_sum(array_slice($openCode, 0, 3));  
            
            return json([
                'code' => ErrorCode::SUCCESS,
                'message' => '获取成功',
                'data' => [
                    'expect' => $result->expect,  
                    'openCode' => $openCode,
                    'sum' => $sum,
                    'openTime' => date('Y-m-d H:i:s', $result->opentime)
                ]
            ]);
        } catch (\Exception $e) {
            \support\Log::error('获取开奖结果失败: ' . $e->getMessage());
            return json(['code' => ErrorCode::SYSTEM_ERROR, 'message' => '获取失败', 'data' => null]);
        }
    }
    
    
    public function history(Request $request, $code)
    {
        try {
            $page = max(1, $request->get('page', 1));
            $pageSize = min(100, max(1, $request->get('pageSize', 20)));
            $offset = ($page - 1) * $pageSize;
            
            
            $total = Db::table('caipiao_kaijiang')
                ->where('name', $code)
                ->where('opencode', '!=', '')
                ->count();
            
            $list = Db::table('caipiao_kaijiang')
                ->where('name', $code)
                ->where('opencode', '!=', '')  
                ->orderBy('opentime', 'desc')  
                ->offset($offset)
                ->limit($pageSize)
                ->get();
            
            $data = [];
            foreach ($list as $item) {
                $openCode = array_map('intval', explode(',', $item->opencode));
                
                $sum = $openCode[0] + $openCode[1] + $openCode[2];
                $data[] = [
                    'expect' => $item->expect,
                    'openCode' => array_slice($openCode, 0, 3),  
                    'sum' => $sum,
                    'openTime' => date('Y-m-d H:i:s', $item->opentime)
                ];
            }
            
            return json([
                'code' => ErrorCode::SUCCESS,
                'message' => '获取成功',
                'data' => [
                    'list' => $data,
                    'pagination' => [
                        'page' => $page,
                        'pageSize' => $pageSize,
                        'total' => $total,
                        'totalPages' => ceil($total / $pageSize)
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            \support\Log::error('获取开奖历史失败: ' . $e->getMessage());
            return json(['code' => ErrorCode::SYSTEM_ERROR, 'message' => '获取失败', 'data' => null]);
        }
    }
    
    
    public function playTypes(Request $request, $code)
    {
        try {
            $lottery = Db::table('caipiao_caipiao')->where('name', $code)->first();
            if (!$lottery) {
                return json(['code' => ErrorCode::DATA_NOT_FOUND, 'message' => '彩种不存在', 'data' => null]);
            }
            
            $plays = Db::table('caipiao_wanfa')
                ->where('typeid', $lottery->typeid)
                ->where('isopen', 1)
                ->orderBy('id', 'asc')
                ->get();
            
            $playTypes = [];
            foreach ($plays as $play) {
                $playTypes[] = [
                    'playType' => $play->playid,
                    'playName' => $play->title,
                    'odds' => (float)$play->rate,
                    'minAmount' => (float)($play->minxf ?? 1),
                    'maxAmount' => (float)($play->maxxf ?? 10000),
                    'description' => $play->remark ?? ''
                ];
            }
            
            return json([
                'code' => ErrorCode::SUCCESS,
                'message' => '获取成功',
                'data' => [
                    'lotteryCode' => $code,
                    'lotteryName' => $lottery->title,
                    'playTypes' => $playTypes
                ]
            ]);
        } catch (\Exception $e) {
            \support\Log::error('获取玩法失败: ' . $e->getMessage());
            return json(['code' => ErrorCode::SYSTEM_ERROR, 'message' => '获取失败', 'data' => null]);
        }
    }
    
    public function getUserSettings(Request $request)
    {
        $userId = $request->userId;
        if (!$userId) {
            return json(['code' => 401, 'message' => '请先登录']);
        }
        
        try {
            $user = Db::table('caipiao_member')->where('id', $userId)->first();
            if (!$user) {
                return json(['code' => 1, 'message' => '用户不存在']);
            }
            
            $settings = $user->settings ? json_decode($user->settings, true) : [];
            $defaults = ['hot_cold_period' => 50, 'game_music' => true, 'autoLogoutDays' => 365];
            
            return json(['code' => 0, 'message' => '获取成功', 'data' => array_merge($defaults, $settings)]);
        } catch (\Exception $e) {
            return json(['code' => 1, 'message' => '获取失败']);
        }
    }
    
    public function saveUserSettings(Request $request)
    {
        $userId = $request->userId;
        if (!$userId) {
            return json(['code' => 401, 'message' => '请先登录']);
        }
        
        $key = $request->post('key');
        $value = $request->post('value');
        
        if (empty($key)) {
            return json(['code' => 1, 'message' => '参数错误']);
        }
        
        try {
            $user = Db::table('caipiao_member')->where('id', $userId)->first();
            if (!$user) {
                return json(['code' => 1, 'message' => '用户不存在']);
            }
            
            $settings = $user->settings ? json_decode($user->settings, true) : [];
            $settings[$key] = $value;
            
            Db::table('caipiao_member')->where('id', $userId)->update(['settings' => json_encode($settings)]);
            
            return json(['code' => 0, 'message' => '保存成功', 'data' => $settings]);
        } catch (\Exception $e) {
            return json(['code' => 1, 'message' => '保存失败']);
        }
    }
    
    
    public function hotCold(Request $request, $code)
    {
        try {
            $limit = intval($request->get('limit', 100)); 
            $limit = min(max($limit, 50), 500); 
            
            
            $history = Db::table('caipiao_kaijiang')
                ->where('name', $code)
                ->whereNotNull('opencode')
                ->where('opencode', '!=', '')
                ->orderBy('opentime', 'desc')
                ->limit($limit)
                ->get();
            
            if ($history->isEmpty()) {
                return json(['code' => 0, 'message' => '暂无数据', 'data' => ['stats' => [], 'numbers' => []]]);
            }
            
            
            $stats = [
                'big' => ['count' => 0, 'miss' => $limit],
                'small' => ['count' => 0, 'miss' => $limit],
                'odd' => ['count' => 0, 'miss' => $limit],
                'even' => ['count' => 0, 'miss' => $limit],
                'big_odd' => ['count' => 0, 'miss' => $limit],
                'big_even' => ['count' => 0, 'miss' => $limit],
                'small_odd' => ['count' => 0, 'miss' => $limit],
                'small_even' => ['count' => 0, 'miss' => $limit],
                'jida' => ['count' => 0, 'miss' => $limit],
                'jixiao' => ['count' => 0, 'miss' => $limit],
                'long' => ['count' => 0, 'miss' => $limit],
                'hu' => ['count' => 0, 'miss' => $limit],
                'bao' => ['count' => 0, 'miss' => $limit],
                'duizi' => ['count' => 0, 'miss' => $limit],
                'shunzi' => ['count' => 0, 'miss' => $limit],
                'baozi' => ['count' => 0, 'miss' => $limit],
            ];
            
            $numbers = [];
            for ($i = 0; $i <= 27; $i++) {
                $numbers[$i] = ['count' => 0, 'miss' => $limit];
            }
            
            
            foreach ($history as $idx => $item) {
                $balls = explode(',', $item->opencode);
                if (count($balls) < 3) continue;
                
                $b1 = intval($balls[0]);
                $b2 = intval($balls[1]);
                $b3 = intval($balls[2]);
                $sum = $b1 + $b2 + $b3;
                
                
                if (isset($numbers[$sum])) {
                    $numbers[$sum]['count']++;
                    if ($numbers[$sum]['miss'] === $limit) {
                        $numbers[$sum]['miss'] = $idx;
                    }
                }
                
                
                $isBig = $sum >= 14;
                if ($isBig) {
                    $stats['big']['count']++;
                    if ($stats['big']['miss'] === $limit) $stats['big']['miss'] = $idx;
                } else {
                    $stats['small']['count']++;
                    if ($stats['small']['miss'] === $limit) $stats['small']['miss'] = $idx;
                }
                
                
                $isOdd = $sum % 2 !== 0;
                if ($isOdd) {
                    $stats['odd']['count']++;
                    if ($stats['odd']['miss'] === $limit) $stats['odd']['miss'] = $idx;
                } else {
                    $stats['even']['count']++;
                    if ($stats['even']['miss'] === $limit) $stats['even']['miss'] = $idx;
                }
                
                
                if ($isBig && $isOdd) {
                    $stats['big_odd']['count']++;
                    if ($stats['big_odd']['miss'] === $limit) $stats['big_odd']['miss'] = $idx;
                }
                if ($isBig && !$isOdd) {
                    $stats['big_even']['count']++;
                    if ($stats['big_even']['miss'] === $limit) $stats['big_even']['miss'] = $idx;
                }
                if (!$isBig && $isOdd) {
                    $stats['small_odd']['count']++;
                    if ($stats['small_odd']['miss'] === $limit) $stats['small_odd']['miss'] = $idx;
                }
                if (!$isBig && !$isOdd) {
                    $stats['small_even']['count']++;
                    if ($stats['small_even']['miss'] === $limit) $stats['small_even']['miss'] = $idx;
                }
                
                
                if ($sum >= 22) {
                    $stats['jida']['count']++;
                    if ($stats['jida']['miss'] === $limit) $stats['jida']['miss'] = $idx;
                }
                if ($sum <= 5) {
                    $stats['jixiao']['count']++;
                    if ($stats['jixiao']['miss'] === $limit) $stats['jixiao']['miss'] = $idx;
                }
                
                
                if ($b1 > $b3) {
                    $stats['long']['count']++;
                    if ($stats['long']['miss'] === $limit) $stats['long']['miss'] = $idx;
                } elseif ($b1 < $b3) {
                    $stats['hu']['count']++;
                    if ($stats['hu']['miss'] === $limit) $stats['hu']['miss'] = $idx;
                } else {
                    $stats['bao']['count']++;
                    if ($stats['bao']['miss'] === $limit) $stats['bao']['miss'] = $idx;
                }
                
                
                $sorted = [$b1, $b2, $b3];
                sort($sorted);
                
                
                if ($b1 === $b2 && $b2 === $b3) {
                    $stats['baozi']['count']++;
                    if ($stats['baozi']['miss'] === $limit) $stats['baozi']['miss'] = $idx;
                }
                
                elseif ($b1 === $b2 || $b2 === $b3 || $b1 === $b3) {
                    $stats['duizi']['count']++;
                    if ($stats['duizi']['miss'] === $limit) $stats['duizi']['miss'] = $idx;
                }
                
                elseif ($sorted[2] - $sorted[1] === 1 && $sorted[1] - $sorted[0] === 1) {
                    $stats['shunzi']['count']++;
                    if ($stats['shunzi']['miss'] === $limit) $stats['shunzi']['miss'] = $idx;
                }
            }
            
            return json([
                'code' => 0,
                'message' => '获取成功',
                'data' => [
                    'total' => $limit,
                    'stats' => $stats,
                    'numbers' => $numbers
                ]
            ]);
        } catch (\Exception $e) {
            \support\Log::error('获取冷热遗漏失败: ' . $e->getMessage());
            return json(['code' => 1, 'message' => '获取失败', 'data' => null]);
        }
    }
    
    
    
    
    private function calculateCurrentIssue($lottery, $now)
    {
        
        $currentIssue = Db::table('caipiao_kaijiang')
            ->where('name', $lottery->name)
            ->where('opentime', '>', $now)  
            ->orderBy('opentime', 'asc')    
            ->first();
        
        if ($currentIssue) {
            $remainTime = $currentIssue->opentime - $now;
            $currExpect = substr($currentIssue->expect, -4);
            
            return [
                'currExpect' => $currExpect,
                'currFullExpect' => $currentIssue->expect,
                'remainTime' => max(0, $remainTime),
                'nextOpenTime' => $currentIssue->opentime,
                'issueTime' => $currentIssue->opentime
            ];
        }
        
        
        $today = date('Y-m-d');
        $firsttime = strtotime($today . ' ' . $lottery->firsttime);
        $endtime = !empty($lottery->endtime) 
            ? strtotime($today . ' ' . $lottery->endtime)
            : strtotime($today . ' 23:59:59');
        
        if ($endtime <= $firsttime) {
            $endtime += 86400;
        }
        
        $expecttime = $lottery->expecttime;
        
        if ($now < $firsttime) {
            $currIssueNum = 1;
            $issueTime = $firsttime;
        } else if ($now > $endtime) {
            $currIssueNum = 1;
            $issueTime = $firsttime + 86400;
        } else {
            $passedMinutes = ($now - $firsttime) / 60;
            $currIssueNum = floor($passedMinutes / $expecttime) + 1;
            $issueTime = $firsttime + ($currIssueNum - 1) * $expecttime * 60;
            
            if ($now >= $issueTime + $expecttime * 60) {
                $currIssueNum++;
                $issueTime += $expecttime * 60;
            }
        }
        
        $datePrefix = date('Ymd', $issueTime);
        $currExpect = str_pad($currIssueNum, 4, '0', STR_PAD_LEFT);
        $currFullExpect = $datePrefix . $currExpect;
        
        $nextIssueTime = $issueTime + $expecttime * 60;
        $remainTime = $nextIssueTime - $now;
        
        return [
            'currExpect' => $currExpect,
            'currFullExpect' => $currFullExpect,
            'remainTime' => max(0, $remainTime),
            'nextOpenTime' => $nextIssueTime,
            'issueTime' => $issueTime
        ];
    }
    
    
    public function boseConfig(Request $request, $code)
    {
        try {
            $lottery = $this->validateLottery($code);
            if (!$lottery) {
                return json(['code' => ErrorCode::INVALID_PARAMS, 'message' => '彩种不存在或已关闭', 'data' => null]);
            }
            
            $config = require config_path() . '/xy28_bose.php';
            
            return json([
                'code' => ErrorCode::SUCCESS,
                'message' => '获取成功',
                'data' => [
                    'lotteryCode' => $code,
                    'boseConfig' => [
                        'hong' => [
                            'name' => '红波',
                            'color' => 'red',
                            'numbers' => $config['hong']
                        ],
                        'lv' => [
                            'name' => '绿波',
                            'color' => 'green',
                            'numbers' => $config['lv']
                        ],
                        'lan' => [
                            'name' => '蓝波',
                            'color' => 'blue',
                            'numbers' => $config['lan']
                        ]
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            \support\Log::error('获取波色配置失败: ' . $e->getMessage());
            return json(['code' => ErrorCode::SYSTEM_ERROR, 'message' => '获取失败', 'data' => null]);
        }
    }
    
    
    private function generateOrderId()
    {
        return 'BET' . date('YmdHis') . rand(1000, 9999);
    }
}
