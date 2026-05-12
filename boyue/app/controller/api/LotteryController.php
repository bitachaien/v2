<?php

namespace app\controller\api;

use support\Request;
use support\Db;
use app\service\AdminEventTrigger;
use app\service\UserLevelService;

class LotteryController
{
    
    public function list(Request $request)
    {
        try {
            
            $lotteries = Db::table('caipiao_caipiao')
                ->where('isopen', 1)
                ->orderBy('listorder', 'asc')
                ->orderBy('id', 'asc')
                ->get();
            
            
            
            $categoryMap = [
                'ssc' => ['title' => '时时彩', 'code' => 'ssc', 'color' => '#ffa801'],
                'pk10' => ['title' => 'PK10', 'code' => 'pk10', 'color' => '#3c40c6'],
                'x5' => ['title' => '11选5', 'code' => 'x5', 'color' => '#0be881'],
                'k3' => ['title' => '快三', 'code' => 'k3', 'color' => '#ff5e57'],
                'xy28' => ['title' => 'PC蛋蛋', 'code' => 'xy28', 'color' => '#575fcf'],
                'lhc' => ['title' => '六合彩', 'code' => 'lhc', 'color' => '#05c46b'],
                'keno' => ['title' => '快乐8', 'code' => 'keno', 'color' => '#e17055'],
                '3d' => ['title' => '福彩3D', 'code' => '3d', 'color' => '#fd79a8'],
                'dpc' => ['title' => '低频彩', 'code' => 'dpc', 'color' => '#00b894'],
                'dwc' => ['title' => '动物彩', 'code' => 'dwc', 'color' => '#e84393'],
            ];
            
            
            $categories = [];
            $hotGames = [];
            $gamesByType = [];
            
            foreach ($lotteries as $lottery) {
                $typeId = $lottery->typeid;
                $game = [
                    'name' => $lottery->name,
                    'title' => $lottery->title,
                    'typeid' => $typeId,
                    'is_hot' => $lottery->is_hot == 1,
                    'hemai_status' => $lottery->hemai_status == 1
                ];
                
                
                if ($lottery->is_hot == 1) {
                    $hotGames[] = $game;
                }
                
                
                if (!isset($gamesByType[$typeId])) {
                    $gamesByType[$typeId] = [];
                }
                $gamesByType[$typeId][] = $game;
            }
            
            
            $categories = [
                ['title' => '热门', 'code' => 'remen', 'color' => '#f53b57']
            ];
            
            foreach ($categoryMap as $typeId => $cat) {
                if (isset($gamesByType[$typeId]) && count($gamesByType[$typeId]) > 0) {
                    $categories[] = $cat;
                }
            }
            
            return json([
                'code' => 0,
                'sign' => true,
                'message' => 'ok',
                'data' => [
                    'categories' => $categories,
                    'hotGames' => $hotGames,
                    'games' => $gamesByType
                ]
            ]);
            
        } catch (\Exception $e) {
            \support\Log::error('彩种列表获取失败: ' . $e->getMessage());
            return json([
                'code' => 500,
                'sign' => false,
                'message' => '获取数据失败',
                'data' => null
            ]);
        }
    }
    
    
    public function info(Request $request, $name)
    {
        try {
            
            $caipiao = Db::table('caipiao_caipiao')
                ->where('name', $name)
                ->first();
            
            if (!$caipiao) {
                return json([
                    'code' => 404,
                    'sign' => false,
                    'message' => '彩种不存在',
                    'data' => null
                ]);
            }
            
            
            if ($caipiao->isopen != 1) {
                return json([
                    'code' => 400,
                    'sign' => false,
                    'message' => '该彩种已关闭',
                    'data' => null
                ]);
            }
            
            
            $lastKj = Db::table('caipiao_kaijiang')
                ->where('name', $name)
                ->orderBy('id', 'desc')
                ->first();
            
            $now = time();
            
            
            $closeSeconds = $caipiao->ftime ?? 10; 
            
            
            $isThirdParty = false;
            $thirdPartyInterval = 0;
            
            
            $recentKj = Db::table('caipiao_kaijiang')
                ->where('name', $name)
                ->orderBy('id', 'desc')
                ->limit(2)
                ->get();
            
            if (count($recentKj) >= 2) {
                $interval = abs($recentKj[0]->opentime - $recentKj[1]->opentime);
                
                if ($interval > 120) {
                    $isThirdParty = true;
                    $thirdPartyInterval = $interval;
                }
            }
            
            $currFullExpect = '';
            $lastFullExpect = $lastKj ? $lastKj->expect : '';
            $remainTime = 0;
            
            if ($isThirdParty && $lastKj) {
                
                $lastExpectNum = $lastKj->expect;
                $lastOpenTime = $lastKj->opentime;
                
                
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
                
                $issueInfo = $this->calculateCurrentIssue($caipiao, $now);
                $currFullExpect = $issueInfo['currFullExpect'];
                $remainTime = $issueInfo['remainTime'];
            }
            
            $isSealed = $remainTime <= $closeSeconds;
            
            return json([
                'code' => 0,
                'sign' => true,
                'message' => 'ok',
                'data' => [
                    'name' => $caipiao->name,
                    'title' => $caipiao->title,
                    'shortname' => $caipiao->title,
                    'currExpect' => substr($currFullExpect, -4),
                    'currFullExpect' => $currFullExpect,
                    'lastExpect' => $lastFullExpect,
                    'lastFullExpect' => $lastFullExpect,
                    'remainTime' => max(0, $remainTime),
                    'openRemainTime' => max(0, $remainTime - $closeSeconds),
                    'isSealed' => $isSealed,
                    'closeSeconds' => $closeSeconds,
                    'expecttime' => $isThirdParty ? ceil($thirdPartyInterval / 60) : $caipiao->expecttime,
                    'lastOpencode' => $lastKj ? $lastKj->opencode : ''
                ]
            ]);
            
        } catch (\Exception $e) {
            \support\Log::error('彩种信息获取失败: ' . $e->getMessage());
            return json([
                'code' => 500,
                'sign' => false,
                'message' => '获取数据失败：' . $e->getMessage(),
                'data' => null
            ]);
        }
    }
    
    
    private function calculateCurrentIssue($caipiao, $now)
    {
        
        $currentIssue = Db::table('caipiao_kaijiang')
            ->where('name', $caipiao->name)
            ->where('opentime', '>', $now)  
            ->orderBy('opentime', 'asc')    
            ->first();
        
        if ($currentIssue) {
            
            $remainTime = $currentIssue->opentime - $now;
            $openRemainTime = max(0, $remainTime - 10);
            
            
            $currExpect = substr($currentIssue->expect, -4);
            
            return [
                'currExpect' => $currExpect,
                'currFullExpect' => $currentIssue->expect,
                'remainTime' => max(0, $remainTime),
                'openRemainTime' => $openRemainTime,
                'issueTime' => $currentIssue->opentime
            ];
        }
        
        
        $today = date('Y-m-d');
        $firsttime = strtotime($today . ' ' . $caipiao->firsttime);
        $endtime = !empty($caipiao->endtime) 
            ? strtotime($today . ' ' . $caipiao->endtime)
            : strtotime($today . ' 23:59:59');  
        
        
        if ($endtime <= $firsttime) {
            $endtime += 86400;
        }
        
        $expecttime = $caipiao->expecttime;
        
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
        $openRemainTime = max(0, $remainTime - 10);
        
        return [
            'currExpect' => $currExpect,
            'currFullExpect' => $currFullExpect,
            'remainTime' => max(0, $remainTime),
            'openRemainTime' => $openRemainTime,
            'issueTime' => $issueTime
        ];
    }
    
    
    public function history(Request $request, $name)
    {
        try {
            $limit = $request->get('limit', 20);
            $limit = min($limit, 100); 
            
            $list = Db::table('caipiao_kaijiang')
                ->where('name', $name)
                ->orderBy('id', 'desc')
                ->limit($limit)
                ->get();
            
            $data = [];
            foreach ($list as $item) {
                $data[] = [
                    'expect' => $item->expect,
                    'opencode' => $item->opencode,
                    'opentime' => $item->opentime,
                    'opentime_format' => date('Y-m-d H:i:s', $item->opentime)
                ];
            }
            
            return json([
                'code' => 0,
                'sign' => true,
                'message' => 'ok',
                'data' => $data
            ]);
            
        } catch (\Exception $e) {
            \support\Log::error('开奖历史获取失败: ' . $e->getMessage());
            return json([
                'code' => 500,
                'sign' => false,
                'message' => '获取数据失败',
                'data' => []
            ]);
        }
    }
    
    
    public function latest(Request $request, $name)
    {
        try {
            $expect = $request->get('expect');
            
            
            $query = Db::table('caipiao_kaijiang')
                ->where('name', $name);
            
            
            if (!empty($expect)) {
                $query->where('expect', $expect);
            } else {
                
                $query->where('opentime', '<=', time())
                    ->orderBy('id', 'desc')
                    ->limit(1);
            }
            
            $kj = $query->first();
            
            if (!$kj) {
                return json([
                    'code' => 404,
                    'sign' => false,
                    'message' => '暂无开奖数据',
                    'data' => null
                ]);
            }
            
            return json([
                'code' => 0,
                'sign' => true,
                'message' => !empty($expect) ? '该期已开奖' : '最新开奖',
                'data' => [
                    'expect' => $kj->expect,
                    'opencode' => $kj->opencode,
                    'opentime' => $kj->opentime,
                    'opentime_format' => date('Y-m-d H:i:s', $kj->opentime)
                ]
            ]);
            
        } catch (\Exception $e) {
            \support\Log::error('最新开奖获取失败: ' . $e->getMessage());
            return json([
                'code' => 500,
                'sign' => false,
                'message' => '获取数据失败',
                'data' => null
            ]);
        }
    }
    
    
    public function plays(Request $request, $name)
    {
        try {
            
            $caipiao = Db::table('caipiao_caipiao')
                ->where('name', $name)
                ->first();
            
            if (!$caipiao) {
                return json([
                    'code' => 404,
                    'sign' => false,
                    'message' => '彩种不存在',
                    'data' => null
                ]);
            }
            
            
            $plays = Db::table('caipiao_wanfa')
                ->where('typeid', $caipiao->typeid)
                ->where('isopen', 1)
                ->orderBy('id', 'asc')
                ->get();
            
            
            $data = [];
            foreach ($plays as $play) {
                $data[] = [
                    'id' => $play->id,
                    'playid' => $play->playid,
                    'title' => $play->title,
                    'rate' => $play->rate ?? '',
                    'max' => $play->maxxf ?? 0,
                    'min' => $play->minxf ?? 0,
                    'info' => $play->remark ?? ''
                ];
            }
            
            return json([
                'code' => 0,
                'sign' => true,
                'message' => 'ok',
                'data' => $data
            ]);
            
        } catch (\Exception $e) {
            \support\Log::error('玩法赔率获取失败: ' . $e->getMessage());
            return json([
                'code' => 500,
                'sign' => false,
                'message' => '获取数据失败',
                'data' => null
            ]);
        }
    }
    
    
    public function doublePlays(Request $request, $name)
    {
        try {
            
            $caipiao = Db::table('caipiao_caipiao')
                ->where('name', $name)
                ->first();
            
            if (!$caipiao) {
                return json([
                    'code' => 404,
                    'sign' => false,
                    'message' => '彩种不存在',
                    'data' => null
                ]);
            }
            
            
            $now = time();
            $issueInfo = $this->calculateCurrentIssue($caipiao, $now);
            
            
            $playGroups = [];
            
            
            switch ($caipiao->typeid) {
                case 'ssc': 
                    $playGroups = [
                        [
                            'group_id' => 'zonghe',
                            'group_name' => '总和',
                            'options' => [
                                ['code' => 'sum_big', 'name' => '总和大', 'rate' => 1.98, 'desc' => '总和>=23'],
                                ['code' => 'sum_small', 'name' => '总和小', 'rate' => 1.98, 'desc' => '总和<=22'],
                                ['code' => 'sum_odd', 'name' => '总和单', 'rate' => 1.98, 'desc' => '总和为单数'],
                                ['code' => 'sum_even', 'name' => '总和双', 'rate' => 1.98, 'desc' => '总和为双数'],
                            ]
                        ],
                        '龙虎' => [
                            ['id' => 'dragon', 'title' => '龙', 'rate' => '1.98', 'info' => '万位>个位'],
                            ['id' => 'tiger', 'title' => '虎', 'rate' => '1.98', 'info' => '万位<个位'],
                            ['id' => 'tie', 'title' => '和', 'rate' => '8.00', 'info' => '万位=个位'],
                        ],
                        '万位' => [
                            ['id' => 'wan_big', 'title' => '大', 'rate' => '1.98', 'info' => '>=5'],
                            ['id' => 'wan_small', 'title' => '小', 'rate' => '1.98', 'info' => '<=4'],
                            ['id' => 'wan_odd', 'title' => '单', 'rate' => '1.98', 'info' => '单数'],
                            ['id' => 'wan_even', 'title' => '双', 'rate' => '1.98', 'info' => '双数'],
                        ],
                        '千位' => [
                            ['id' => 'qian_big', 'title' => '大', 'rate' => '1.98', 'info' => '>=5'],
                            ['id' => 'qian_small', 'title' => '小', 'rate' => '1.98', 'info' => '<=4'],
                            ['id' => 'qian_odd', 'title' => '单', 'rate' => '1.98', 'info' => '单数'],
                            ['id' => 'qian_even', 'title' => '双', 'rate' => '1.98', 'info' => '双数'],
                        ],
                        '百位' => [
                            ['id' => 'bai_big', 'title' => '大', 'rate' => '1.98', 'info' => '>=5'],
                            ['id' => 'bai_small', 'title' => '小', 'rate' => '1.98', 'info' => '<=4'],
                            ['id' => 'bai_odd', 'title' => '单', 'rate' => '1.98', 'info' => '单数'],
                            ['id' => 'bai_even', 'title' => '双', 'rate' => '1.98', 'info' => '双数'],
                        ],
                        '十位' => [
                            ['id' => 'shi_big', 'title' => '大', 'rate' => '1.98', 'info' => '>=5'],
                            ['id' => 'shi_small', 'title' => '小', 'rate' => '1.98', 'info' => '<=4'],
                            ['id' => 'shi_odd', 'title' => '单', 'rate' => '1.98', 'info' => '单数'],
                            ['id' => 'shi_even', 'title' => '双', 'rate' => '1.98', 'info' => '双数'],
                        ],
                        '个位' => [
                            ['id' => 'ge_big', 'title' => '大', 'rate' => '1.98', 'info' => '>=5'],
                            ['id' => 'ge_small', 'title' => '小', 'rate' => '1.98', 'info' => '<=4'],
                            ['id' => 'ge_odd', 'title' => '单', 'rate' => '1.98', 'info' => '单数'],
                            ['id' => 'ge_even', 'title' => '双', 'rate' => '1.98', 'info' => '双数'],
                        ],
                    ];
                    break;
                    
                case 'k3': 
                    $doubleSidePlays = [
                        '和值' => [
                            ['id' => 'sum_big', 'title' => '和值大', 'rate' => '1.98', 'info' => '和值>=11'],
                            ['id' => 'sum_small', 'title' => '和值小', 'rate' => '1.98', 'info' => '和值<=10'],
                            ['id' => 'sum_odd', 'title' => '和值单', 'rate' => '1.98', 'info' => '和值为单数'],
                            ['id' => 'sum_even', 'title' => '和值双', 'rate' => '1.98', 'info' => '和值为双数'],
                        ],
                        '大小单双' => [
                            ['id' => 'big_odd', 'title' => '大单', 'rate' => '3.96', 'info' => '和值大且单'],
                            ['id' => 'big_even', 'title' => '大双', 'rate' => '3.96', 'info' => '和值大且双'],
                            ['id' => 'small_odd', 'title' => '小单', 'rate' => '3.96', 'info' => '和值小且单'],
                            ['id' => 'small_even', 'title' => '小双', 'rate' => '3.96', 'info' => '和值小且双'],
                        ],
                    ];
                    break;
                    
                case 'pk10': 
                    $doubleSidePlays = [
                        '冠亚和' => [
                            ['id' => 'gy_big', 'title' => '冠亚和大', 'rate' => '1.98', 'info' => '冠亚和>=12'],
                            ['id' => 'gy_small', 'title' => '冠亚和小', 'rate' => '1.98', 'info' => '冠亚和<=11'],
                            ['id' => 'gy_odd', 'title' => '冠亚和单', 'rate' => '1.98', 'info' => '冠亚和为单数'],
                            ['id' => 'gy_even', 'title' => '冠亚和双', 'rate' => '1.98', 'info' => '冠亚和为双数'],
                        ],
                        '冠军' => [
                            ['id' => 'champion_big', 'title' => '大', 'rate' => '1.98', 'info' => '>=6'],
                            ['id' => 'champion_small', 'title' => '小', 'rate' => '1.98', 'info' => '<=5'],
                            ['id' => 'champion_odd', 'title' => '单', 'rate' => '1.98', 'info' => '单数'],
                            ['id' => 'champion_even', 'title' => '双', 'rate' => '1.98', 'info' => '双数'],
                        ],
                        '亚军' => [
                            ['id' => 'second_big', 'title' => '大', 'rate' => '1.98', 'info' => '>=6'],
                            ['id' => 'second_small', 'title' => '小', 'rate' => '1.98', 'info' => '<=5'],
                            ['id' => 'second_odd', 'title' => '单', 'rate' => '1.98', 'info' => '单数'],
                            ['id' => 'second_even', 'title' => '双', 'rate' => '1.98', 'info' => '双数'],
                        ],
                        '龙虎' => [
                            ['id' => 'dragon_1_10', 'title' => '1vs10龙', 'rate' => '1.98', 'info' => '第1名>第10名'],
                            ['id' => 'tiger_1_10', 'title' => '1vs10虎', 'rate' => '1.98', 'info' => '第1名<第10名'],
                            ['id' => 'dragon_2_9', 'title' => '2vs9龙', 'rate' => '1.98', 'info' => '第2名>第9名'],
                            ['id' => 'tiger_2_9', 'title' => '2vs9虎', 'rate' => '1.98', 'info' => '第2名<第9名'],
                        ],
                    ];
                    break;
                    
                case 'x5': 
                    $doubleSidePlays = [
                        '总和' => [
                            ['id' => 'sum_big', 'title' => '总和大', 'rate' => '1.98', 'info' => '总和>=30'],
                            ['id' => 'sum_small', 'title' => '总和小', 'rate' => '1.98', 'info' => '总和<30'],
                            ['id' => 'sum_odd', 'title' => '总和单', 'rate' => '1.98', 'info' => '总和为单数'],
                            ['id' => 'sum_even', 'title' => '总和双', 'rate' => '1.98', 'info' => '总和为双数'],
                        ],
                        '龙虎' => [
                            ['id' => 'dragon', 'title' => '龙', 'rate' => '1.98', 'info' => '第1球>第5球'],
                            ['id' => 'tiger', 'title' => '虎', 'rate' => '1.98', 'info' => '第1球<第5球'],
                        ],
                    ];
                    break;
                    
                default:
                    
                    break;
            }
            
            if (empty($playGroups)) {
                return json([
                    'code' => 404,
                    'sign' => false,
                    'message' => '该彩种暂不支持双面玩法',
                    'data' => null
                ]);
            }
            
            return json([
                'code' => 0,
                'sign' => true,
                'message' => 'ok',
                'data' => [
                    'game' => [
                        'name' => $name,
                        'title' => $caipiao->title,
                        'type' => $caipiao->typeid
                    ],
                    'current_issue' => $issueInfo['currFullExpect'],
                    'countdown' => $issueInfo['remainTime'],
                    'play_groups' => $playGroups
                ]
            ]);
            
        } catch (\Exception $e) {
            \support\Log::error('双面玩法获取失败: ' . $e->getMessage());
            return json([
                'code' => 500,
                'sign' => false,
                'message' => '获取数据失败',
                'data' => null
            ]);
        }
    }
    
    
    public function bet(Request $request)
    {
        try {
            
            if (!isset($request->userId)) {
                return json([
                    'code' => 401,
                    'sign' => false,
                    'message' => '请先登录',
                    'data' => null
                ]);
            }
            
            $userId = $request->userId;
            
            
            $lotteryname = $request->post('lotteryname');
            $expect = $request->post('expect');
            $playid = $request->post('playid');
            $tzcode = $request->post('tzcode');
            $amount = $request->post('amount');
            $beishu = $request->post('beishu', 1);
            $mode = $request->post('mode', 1);
            $itemcount = $request->post('itemcount', 1);
            $yjf = $request->post('yjf', '元');
            $ishemai = $request->post('ishemai', 0);
            
            
            if (empty($lotteryname) || empty($expect) || empty($playid) || $tzcode === '' || $tzcode === null) {
                return json([
                    'code' => 400,
                    'sign' => false,
                    'message' => '参数不完整',
                    'data' => null
                ]);
            }
            
            
            $caipiao = Db::table('caipiao_caipiao')
                ->where('name', $lotteryname)
                ->first();
            
            if (!$caipiao || $caipiao->isopen != 1) {
                return json([
                    'code' => 400,
                    'sign' => false,
                    'message' => '该彩种不可用',
                    'data' => null
                ]);
            }
            
            
            if ($playid === 'xy28_combined') {
                
                $betItems = [];
                if (strpos($tzcode, '[') === 0) {
                    $betItems = json_decode($tzcode, true);
                } else {
                    
                    $parts = explode(',', $tzcode);
                    foreach ($parts as $part) {
                        if (strpos($part, ':') !== false) {
                            list($pid, $amt) = explode(':', $part);
                            $betItems[] = ['playid' => trim($pid), 'amount' => floatval($amt)];
                        } else {
                            $betItems[] = ['playid' => trim($part), 'amount' => floatval($amount) / count($parts)];
                        }
                    }
                }
                
                if (empty($betItems)) {
                    return json(['code' => 400, 'message' => '投注内容不能为空']);
                }
                
                
                $totalBetAmount = 0;
                $betLabels = [];
                $categories = [];  
                
                foreach ($betItems as &$item) {
                    $play = Db::table('caipiao_wanfa')
                        ->where('typeid', $caipiao->typeid)
                        ->where('playid', $item['playid'])
                        ->first();
                    
                    if (!$play) {
                        
                        $play = Db::table('caipiao_wanfa')
                            ->where('playid', $item['playid'])
                            ->first();
                    }
                    
                    if ($play) {
                        $item['title'] = $play->title;
                        $item['rate'] = $play->rate;
                        $totalBetAmount += $item['amount'];
                        $betLabels[] = $play->title . $item['amount'];
                        
                        
                        $playid = $item['playid'];
                        if (in_array($playid, ['c_big', 'c_small', 'c_odd', 'c_even', 'c_big_odd', 'c_big_even', 'c_small_odd', 'c_small_even', 'c_jida', 'c_jixiao'])) {
                            $categories['双面'] = true;
                        } elseif (in_array($playid, ['c_long', 'c_hu', 'c_bao'])) {
                            $categories['龙虎'] = true;
                        } elseif (in_array($playid, ['c_duizi', 'c_shunzi', 'c_baozi'])) {
                            $categories['趣味'] = true;
                        } elseif (strpos($playid, 'hz_') === 0) {
                            $categories['和值'] = true;
                        }
                    }
                }
                
                
                $totalAmount = floatval($amount) ?: $totalBetAmount;
                
                
                $user = Db::table('caipiao_member')->where('id', $userId)->first();
                if (!$user) {
                    return json(['code' => 404, 'message' => '用户不存在']);
                }
                
                
                if ($user->balance < $totalAmount) {
                    return json(['code' => 400, 'message' => '余额不足']);
                }
                
                
                $now = time();
                $issueInfo = $this->calculateCurrentIssue($caipiao, $now);
                $currFullExpect = $issueInfo['currFullExpect'];
                
                
                Db::beginTransaction();
                try {
                    
                    Db::table('caipiao_member')->where('id', $userId)->decrement('balance', $totalAmount);
                    
                    
                    $trano = $this->generateTrano();
                    
                    
                    $betId = Db::table('caipiao_touzhu')->insertGetId([
                        'uid' => $userId,
                        'username' => $user->username,
                        'trano' => $trano,
                        'cpname' => $lotteryname,
                        'cptitle' => $caipiao->title,
                        'typeid' => $caipiao->typeid,
                        'expect' => $expect,
                        'playid' => 'xy28_combined',
                        'playtitle' => implode('/', $betLabels),
                        'tzcode' => json_encode($betItems),  
                        'amount' => $totalAmount,
                        'amountafter' => $user->balance - $totalAmount,
                        'beishu' => 1,
                        'mode' => 1,
                        'itemcount' => count($betItems),
                        'yjf' => '元',
                        'ishemai' => 0,
                        'isdraw' => 0,
                        'okamount' => 0,
                        'okcount' => 0,
                        'oddtime' => $now
                    ]);
                    
                    
                    Db::table('caipiao_moneylog')->insert([
                        'uid' => $userId,
                        'username' => $user->username,
                        'type' => 9,
                        'typename' => '投注',
                        'trano' => $trano,
                        'amount' => -$totalAmount,
                        'before' => $user->balance,
                        'after' => $user->balance - $totalAmount,
                        'remark' => "投注 {$caipiao->title} 第{$expect}期",
                        'addtime' => $now,
                        'ip' => $request->getRemoteIp()
                    ]);
                    
                    Db::commit();
                    
                    return json([
                        'code' => 0,
                        'sign' => true,
                        'message' => '投注成功',
                        'data' => [
                            'trano' => $trano,
                            'balance' => number_format($user->balance - $totalAmount, 2, '.', ''),
                            'items' => count($betItems)
                        ]
                    ]);
                } catch (\Exception $e) {
                    Db::rollBack();
                    throw $e;
                }
            }
            
            
            
            $originalPlayid = $playid;
            
            
            if (preg_match('/^[a-z0-9]+_tm$/', $playid) && $caipiao->typeid) {
                
                $number = str_pad($tzcode, 2, '0', STR_PAD_LEFT);
                $playid = $caipiao->typeid . '_tm_' . $number;
                \support\Log::info("智能匹配特码: {$originalPlayid} + tzcode:{$tzcode} → {$playid}");
            }
            
            else if (preg_match('/^[a-z0-9]+_(.+)$/', $playid, $matches)) {
                $basePlay = $matches[1];  
                $newPlayid = $caipiao->typeid . '_' . $basePlay;
                
                
                $playExists = Db::table('caipiao_wanfa')
                    ->where('typeid', $caipiao->typeid)
                    ->where('playid', $newPlayid)
                    ->exists();
                
                if ($playExists) {
                    $playid = $newPlayid;
                    \support\Log::info("智能匹配玩法: {$originalPlayid} → {$playid} (typeid={$caipiao->typeid})");
                }
            }
            
            
            
            $now = time();
            
            
            $recentKj = Db::table('caipiao_kaijiang')
                ->where('name', $lotteryname)
                ->orderBy('id', 'desc')
                ->limit(2)
                ->get();
            
            $isThirdParty = false;
            $thirdPartyInterval = 0;
            $currFullExpect = '';
            $remainTime = 0;
            
            if (count($recentKj) >= 2) {
                $interval = abs($recentKj[0]->opentime - $recentKj[1]->opentime);
                if ($interval > 120) {
                    $isThirdParty = true;
                    $thirdPartyInterval = $interval;
                }
            }
            
            if ($isThirdParty && count($recentKj) > 0) {
                
                $lastKj = $recentKj[0];
                $lastExpectNum = $lastKj->expect;
                $lastOpenTime = $lastKj->opentime;
                
                $nextOpenTime = $lastOpenTime + $thirdPartyInterval;
                $remainTime = $nextOpenTime - $now;
                
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
                
                $issueInfo = $this->calculateCurrentIssue($caipiao, $now);
                $currFullExpect = $issueInfo['currFullExpect'];
                $remainTime = $issueInfo['remainTime'];
            }
            
            if ($expect != $currFullExpect) {
                \support\Log::info("期号验证失败: 提交={$expect}, 当前={$currFullExpect}, 第三方=" . ($isThirdParty ? 'Y' : 'N'));
                return json([
                    'code' => 400,
                    'sign' => false,
                    'message' => '期号错误或已过期',
                    'data' => null
                ]);
            }
            
            
            $closeSeconds = $caipiao->ftime ?? 10;
            if ($remainTime <= $closeSeconds) {
                return json([
                    'code' => 400,
                    'sign' => false,
                    'message' => '该期已封盘',
                    'data' => null
                ]);
            }
            
            
            $play = Db::table('caipiao_wanfa')
                ->where('playid', $playid)
                ->first();
            
            if (!$play) {
                return json([
                    'code' => 400,
                    'sign' => false,
                    'message' => '玩法不存在',
                    'data' => null
                ]);
            }
            
            
            $unitPrice = $amount;
            if ($yjf == '角') {
                $unitPrice = $amount * 0.1;
            } else if ($yjf == '分') {
                $unitPrice = $amount * 0.01;
            }
            
            $totalAmount = $unitPrice * $beishu * $itemcount;
            
            
            if ($totalAmount < $play->minxf || $totalAmount > $play->maxxf) {
                return json([
                    'code' => 400,
                    'sign' => false,
                    'message' => "投注金额须在 {$play->minxf} - {$play->maxxf} 之间",
                    'data' => null
                ]);
            }
            
            
            $user = Db::table('caipiao_member')
                ->where('id', $userId)
                ->first();
            
            if (!$user) {
                return json([
                    'code' => 404,
                    'sign' => false,
                    'message' => '用户不存在',
                    'data' => null
                ]);
            }
            
            
            if ($user->balance < $totalAmount) {
                return json([
                    'code' => 400,
                    'sign' => false,
                    'message' => '余额不足',
                    'data' => null
                ]);
            }
            
            
            Db::beginTransaction();
            
            try {
                
                Db::table('caipiao_member')
                    ->where('id', $userId)
                    ->decrement('balance', $totalAmount);
                
                
                $trano = $this->generateTrano();
                
                
                $betId = Db::table('caipiao_touzhu')->insertGetId([
                    'uid' => $userId,
                    'username' => $user->username,
                    'trano' => $trano,
                    'cpname' => $lotteryname,
                    'cptitle' => $caipiao->title,
                    'typeid' => $caipiao->typeid,
                    'expect' => $expect,
                    'playid' => $playid,
                    'playtitle' => $play->title,
                    'tzcode' => $tzcode,
                    'amount' => $totalAmount,
                    'amountafter' => $user->balance - $totalAmount,
                    'beishu' => $beishu,
                    'mode' => $mode,
                    'itemcount' => $itemcount,
                    'yjf' => $yjf,
                    'ishemai' => $ishemai,
                    'isdraw' => 0,
                    'okamount' => 0,
                    'okcount' => 0,
                    'oddtime' => $now
                    
                ]);
                
                
                Db::table('caipiao_moneylog')->insert([
                    'uid' => $userId,
                    'username' => $user->username,
                    'type' => 9, 
                    'typename' => '投注',
                    'trano' => $trano,
                    'amount' => -$totalAmount,
                    'before' => $user->balance,
                    'after' => $user->balance - $totalAmount,
                    'remark' => "投注 {$caipiao->title} 第{$expect}期",
                    'addtime' => $now,
                    'ip' => $request->getRealIp()
                ]);
                
                Db::commit();
                
                
                try {
                    UserLevelService::checkAndUpgrade($userId);
                } catch (\Exception $e) {
                    
                }
                
                
                try {
                    AdminEventTrigger::onNewBet([
                        'username' => $user->username,
                        'money' => $totalAmount,
                        'lottery_name' => $caipiao->title,
                        'expect' => $expect,
                    ]);
                } catch (\Exception $e) {
                    
                }
                
                return json([
                    'code' => 0,
                    'sign' => true,
                    'message' => '投注成功',
                    'data' => [
                        'trano' => $trano,
                        'balance' => number_format($user->balance - $totalAmount, 2, '.', '')
                    ]
                ]);
                
            } catch (\Exception $e) {
                Db::rollBack();
                throw $e;
            }
            
        } catch (\Exception $e) {
            \support\Log::error('投注失败: ' . $e->getMessage());
            return json([
                'code' => 500,
                'sign' => false,
                'message' => '投注失败：' . $e->getMessage(),
                'data' => null
            ]);
        }
    }
    
    
    private function generateTrano()
    {
        
        $letters = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
        $prefix = $letters[rand(0, strlen($letters) - 1)];
        return $prefix . date('ymdHis') . str_pad(rand(0, 99999), 5, '0', STR_PAD_LEFT);
    }
    
    
    public function myBets(Request $request, $name)
    {
        try {
            
            if (!isset($request->userId)) {
                return json([
                    'code' => 401,
                    'sign' => false,
                    'message' => '请先登录',
                    'data' => []
                ]);
            }
            
            $userId = $request->userId;
            $page = $request->get('page', 1);
            $limit = $request->get('limit', 20);
            
            
            $today = strtotime(date('Y-m-d'));
            $tomorrow = $today + 86400;
            
            $query = Db::table('caipiao_touzhu')
                ->where('uid', $userId)
                ->where('cpname', $name)
                ->where('oddtime', '>=', $today)
                ->where('oddtime', '<', $tomorrow);
            
            $total = $query->count();
            
            $list = $query->orderBy('id', 'desc')
                ->offset(($page - 1) * $limit)
                ->limit($limit)
                ->get();
            
            $data = [];
            foreach ($list as $item) {
                
                $statusText = '';
                $statusColor = 'blue';
                if ($item->isdraw == 1) {
                    $statusText = '已中奖';
                    $statusColor = 'green';
                } elseif ($item->isdraw == 0) {
                    $statusText = '未开奖';
                    $statusColor = 'blue';
                } elseif ($item->isdraw == -1) {
                    $statusText = '未中奖';
                    $statusColor = 'gray';
                } elseif ($item->isdraw == -2) {
                    $statusText = '已撤单';
                    $statusColor = 'red';
                }
                
                $data[] = [
                    'id' => $item->id,
                    'trano' => $item->trano,
                    'expect' => $item->expect,
                    'playtitle' => $item->playtitle,
                    'tzcode' => $item->tzcode,
                    'amount' => number_format($item->amount, 2, '.', ''),
                    'beishu' => $item->beishu,
                    'itemcount' => $item->itemcount,
                    'okamount' => number_format($item->okamount ?? 0, 2, '.', ''),
                    'status' => $statusText,
                    'status_color' => $statusColor,
                    'isdraw' => $item->isdraw,
                    'time' => date('Y-m-d H:i:s', $item->oddtime),
                    'canCancel' => ($item->isdraw == 0) 
                ];
            }
            
            return json([
                'code' => 0,
                'sign' => true,
                'message' => 'ok',
                'count' => $total,
                'data' => $data
            ]);
            
        } catch (\Exception $e) {
            \support\Log::error('今日投注记录获取失败: ' . $e->getMessage());
            return json([
                'code' => 500,
                'sign' => false,
                'message' => '获取数据失败',
                'count' => 0,
                'data' => []
            ]);
        }
    }
    
    
    public function chase(Request $request)
    {
        try {
            
            if (!isset($request->userId)) {
                return json([
                    'code' => 401,
                    'sign' => false,
                    'message' => '请先登录',
                    'data' => null
                ]);
            }
            
            $userId = $request->userId;
            
            
            $lotteryname = $request->post('lotteryname');
            $playid = $request->post('playid');
            $tzcode = $request->post('tzcode');
            $plans = $request->post('plans'); 
            $mode = $request->post('mode', 1);
            $itemcount = $request->post('itemcount', 1);
            $amount = $request->post('amount', 2);
            $yjf = $request->post('yjf', '元');
            $stopOnWin = $request->post('stopOnWin', 0); 
            
            
            if (empty($lotteryname) || empty($playid) || $tzcode === '' || $tzcode === null || !$plans || !is_array($plans)) {
                return json([
                    'code' => 400,
                    'sign' => false,
                    'message' => '参数不完整',
                    'data' => null
                ]);
            }
            
            
            $caipiao = Db::table('caipiao_caipiao')
                ->where('name', $lotteryname)
                ->first();
            
            if (!$caipiao || $caipiao->isopen != 1) {
                return json([
                    'code' => 400,
                    'sign' => false,
                    'message' => '该彩种不可用',
                    'data' => null
                ]);
            }
            
            
            $play = Db::table('caipiao_wanfa')
                ->where('playid', $playid)
                ->first();
            
            if (!$play) {
                return json([
                    'code' => 400,
                    'sign' => false,
                    'message' => '玩法不存在',
                    'data' => null
                ]);
            }
            
            
            $user = Db::table('caipiao_member')
                ->where('id', $userId)
                ->first();
            
            if (!$user) {
                return json([
                    'code' => 404,
                    'sign' => false,
                    'message' => '用户不存在',
                    'data' => null
                ]);
            }
            
            
            $unitPrice = $amount;
            if ($yjf == '角') {
                $unitPrice = $amount * 0.1;
            } else if ($yjf == '分') {
                $unitPrice = $amount * 0.01;
            }
            
            $totalAmount = 0;
            foreach ($plans as $plan) {
                $beishu = $plan['beishu'] ?? 1;
                $totalAmount += $unitPrice * $beishu * $itemcount;
            }
            
            
            if ($user->balance < $totalAmount) {
                return json([
                    'code' => 400,
                    'sign' => false,
                    'message' => '余额不足',
                    'data' => null
                ]);
            }
            
            
            Db::beginTransaction();
            
            try {
                
                $chaseNo = 'C' . date('ymdHis') . str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
                $now = time();
                $betIds = [];
                
                
                foreach ($plans as $plan) {
                    $expect = $plan['expect'];
                    $beishu = $plan['beishu'] ?? 1;
                    
                    $betAmount = $unitPrice * $beishu * $itemcount;
                    $trano = $this->generateTrano();
                    
                    $betId = Db::table('caipiao_touzhu')->insertGetId([
                        'uid' => $userId,
                        'username' => $user->username,
                        'trano' => $trano,
                        'cpname' => $lotteryname,
                        'cptitle' => $caipiao->title,
                        'typeid' => $caipiao->typeid,
                        'expect' => $expect,
                        'playid' => $playid,
                        'playtitle' => $play->title,
                        'tzcode' => $tzcode,
                        'amount' => $betAmount,
                        'amountafter' => $user->balance - $betAmount,
                        'beishu' => $beishu,
                        'mode' => $mode,
                        'itemcount' => $itemcount,
                        'yjf' => $yjf,
                        'ishemai' => 0,
                        'isdraw' => 0,
                        'okamount' => 0,
                        'okcount' => 0,
                        'oddtime' => $now,
                        
                        'chaseno' => $chaseNo,
                        'stoponwin' => $stopOnWin
                    ]);
                    
                    $betIds[] = $betId;
                }
                
                
                Db::table('caipiao_member')
                    ->where('id', $userId)
                    ->decrement('balance', $totalAmount);
                
                
                Db::table('caipiao_moneylog')->insert([
                    'uid' => $userId,
                    'username' => $user->username,
                    'type' => 9,
                    'typename' => '追号',
                    'trano' => $chaseNo,
                    'amount' => -$totalAmount,
                    'before' => $user->balance,
                    'after' => $user->balance - $totalAmount,
                    'remark' => "追号 {$caipiao->title} 共" . count($plans) . "期",
                    'addtime' => $now,
                    'ip' => $request->getRealIp()
                ]);
                
                Db::commit();
                
                
                try {
                    AdminEventTrigger::onNewBet([
                        'username' => $user->username,
                        'money' => $totalAmount,
                        'lottery_name' => $caipiao->title,
                        'expect' => $plans[0]['expect'] ?? '',
                    ]);
                } catch (\Exception $e) {
                    
                }
                
                return json([
                    'code' => 0,
                    'sign' => true,
                    'message' => '追号成功',
                    'data' => [
                        'chaseNo' => $chaseNo,
                        'betIds' => $betIds,
                        'totalAmount' => number_format($totalAmount, 2, '.', ''),
                        'balance' => number_format($user->balance - $totalAmount, 2, '.', '')
                    ]
                ]);
                
            } catch (\Exception $e) {
                Db::rollBack();
                throw $e;
            }
            
        } catch (\Exception $e) {
            \support\Log::error('追号失败: ' . $e->getMessage());
            return json([
                'code' => 500,
                'sign' => false,
                'message' => '追号失败：' . $e->getMessage(),
                'data' => null
            ]);
        }
    }
    
    
    public function cancel(Request $request)
    {
        try {
            
            if (!isset($request->userId)) {
                return json([
                    'code' => 401,
                    'sign' => false,
                    'message' => '请先登录',
                    'data' => null
                ]);
            }
            
            $userId = $request->userId;
            $trano = $request->post('trano');
            
            if (!$trano) {
                return json([
                    'code' => 400,
                    'sign' => false,
                    'message' => '订单号不能为空',
                    'data' => null
                ]);
            }
            
            
            $bet = Db::table('caipiao_touzhu')
                ->where('trano', $trano)
                ->where('uid', $userId)
                ->first();
            
            if (!$bet) {
                return json([
                    'code' => 404,
                    'sign' => false,
                    'message' => '订单不存在',
                    'data' => null
                ]);
            }
            
            
            if ($bet->isdraw != 0) {
                return json([
                    'code' => 400,
                    'sign' => false,
                    'message' => '该订单不能撤单',
                    'data' => null
                ]);
            }
            
            
            $caipiao = Db::table('caipiao_caipiao')
                ->where('name', $bet->cpname)
                ->first();
            
            if ($caipiao) {
                $issueInfo = $this->calculateCurrentIssue($caipiao, time());
                $closeSeconds = $caipiao->ftime ?? 10;
                
                if ($bet->expect == $issueInfo['currFullExpect'] && $issueInfo['remainTime'] <= $closeSeconds) {
                    return json([
                        'code' => 400,
                        'sign' => false,
                        'message' => '该期已封盘，不能撤单',
                        'data' => null
                    ]);
                }
            }
            
            
            $user = Db::table('caipiao_member')
                ->where('id', $userId)
                ->first();
            
            
            Db::beginTransaction();
            
            try {
                
                Db::table('caipiao_member')
                    ->where('id', $userId)
                    ->increment('balance', $bet->amount);
                
                
                Db::table('caipiao_touzhu')
                    ->where('id', $bet->id)
                    ->update([
                        'isdraw' => -2,
                        'canceltime' => time()
                    ]);
                
                
                Db::table('caipiao_moneylog')->insert([
                    'uid' => $userId,
                    'username' => $user->username,
                    'type' => 10,
                    'typename' => '撤单',
                    'trano' => $trano,
                    'amount' => $bet->amount,
                    'before' => $user->balance,
                    'after' => $user->balance + $bet->amount,
                    'remark' => "撤单 {$bet->cptitle} 第{$bet->expect}期",
                    'addtime' => time(),
                    'ip' => $request->getRealIp()
                ]);
                
                Db::commit();
                
                return json([
                    'code' => 0,
                    'sign' => true,
                    'message' => '撤单成功',
                    'data' => [
                        'refund' => number_format($bet->amount, 2, '.', ''),
                        'balance' => number_format($user->balance + $bet->amount, 2, '.', '')
                    ]
                ]);
                
            } catch (\Exception $e) {
                Db::rollBack();
                throw $e;
            }
            
        } catch (\Exception $e) {
            \support\Log::error('撤单失败: ' . $e->getMessage());
            return json([
                'code' => 500,
                'sign' => false,
                'message' => '撤单失败：' . $e->getMessage(),
                'data' => null
            ]);
        }
    }
    
    
    public function statistics(Request $request, $name)
    {
        try {
            $count = $request->get('count', 100);
            $count = min($count, 200); 
            
            
            $list = Db::table('caipiao_kaijiang')
                ->where('name', $name)
                ->orderBy('id', 'desc')
                ->limit($count)
                ->get();
            
            if (count($list) == 0) {
                return json([
                    'code' => 0,
                    'sign' => true,
                    'message' => 'ok',
                    'data' => [
                        'count' => 0,
                        'numbers' => []
                    ]
                ]);
            }
            
            
            $stats = [];
            
            foreach ($list as $item) {
                if (!$item->opencode) continue;
                
                $codes = explode(',', $item->opencode);
                foreach ($codes as $code) {
                    $code = trim($code);
                    if ($code === '') continue;
                    
                    if (!isset($stats[$code])) {
                        $stats[$code] = [
                            'number' => $code,
                            'count' => 0,
                            'rate' => 0,
                            'lastIssue' => '',
                            'missCount' => 0
                        ];
                    }
                    
                    $stats[$code]['count']++;
                    if (!$stats[$code]['lastIssue']) {
                        $stats[$code]['lastIssue'] = $item->expect;
                    }
                }
            }
            
            
            $totalIssues = count($list);
            foreach ($stats as $code => &$stat) {
                $stat['rate'] = round(($stat['count'] / $totalIssues) * 100, 2);
                
                
                $missCount = 0;
                foreach ($list as $item) {
                    if (!$item->opencode) continue;
                    
                    $codes = explode(',', $item->opencode);
                    if (in_array($code, $codes)) {
                        break;
                    }
                    $missCount++;
                }
                $stat['missCount'] = $missCount;
            }
            
            
            usort($stats, function($a, $b) {
                return $b['count'] - $a['count'];
            });
            
            return json([
                'code' => 0,
                'sign' => true,
                'message' => 'ok',
                'data' => [
                    'count' => $totalIssues,
                    'numbers' => array_values($stats)
                ]
            ]);
            
        } catch (\Exception $e) {
            \support\Log::error('号码统计获取失败: ' . $e->getMessage());
            return json([
                'code' => 500,
                'sign' => false,
                'message' => '获取数据失败',
                'data' => null
            ]);
        }
    }
    
    
    public function doubleBet(Request $request)
    {
        try {
            
            if (!isset($request->userId)) {
                return json([
                    'code' => 401,
                    'sign' => false,
                    'message' => '请先登录',
                    'data' => null
                ]);
            }
            
            $userId = $request->userId;
            $gameName = $request->post('game_name');
            $issue = $request->post('issue');
            $bets = $request->post('bets');
            $totalAmount = $request->post('total_amount');
            
            
            if (!$gameName || !$issue || !$bets || !$totalAmount) {
                return json([
                    'code' => 400,
                    'sign' => false,
                    'message' => '参数不完整',
                    'data' => null
                ]);
            }
            
            if (!is_array($bets) || empty($bets)) {
                return json([
                    'code' => 400,
                    'sign' => false,
                    'message' => '投注数据格式错误',
                    'data' => null
                ]);
            }
            
            
            $caipiao = Db::table('caipiao_caipiao')
                ->where('name', $gameName)
                ->first();
            
            if (!$caipiao || $caipiao->isopen != 1) {
                return json([
                    'code' => 400,
                    'sign' => false,
                    'message' => '该彩种不可用',
                    'data' => null
                ]);
            }
            
            
            $now = time();
            $issueInfo = $this->calculateCurrentIssue($caipiao, $now);
            
            if ($issue != $issueInfo['currFullExpect']) {
                return json([
                    'code' => 400,
                    'sign' => false,
                    'message' => '期号错误或已过期',
                    'data' => null
                ]);
            }
            
            
            $closeSeconds = $caipiao->ftime ?? 10;
            if ($issueInfo['remainTime'] <= $closeSeconds) {
                return json([
                    'code' => 400,
                    'sign' => false,
                    'message' => '该期已封盘',
                    'data' => null
                ]);
            }
            
            
            $calculatedAmount = 0;
            foreach ($bets as $bet) {
                $calculatedAmount += floatval($bet['amount'] ?? 0);
            }
            
            if (abs($calculatedAmount - $totalAmount) > 0.01) {
                return json([
                    'code' => 400,
                    'sign' => false,
                    'message' => '投注金额不一致',
                    'data' => null
                ]);
            }
            
            
            $user = Db::table('caipiao_member')->where('id', $userId)->first();
            if (!$user || $user->balance < $totalAmount) {
                return json([
                    'code' => 400,
                    'sign' => false,
                    'message' => '余额不足',
                    'data' => null
                ]);
            }
            
            
            
            
            
            
            
            $orderId = 'TZ' . date('YmdHis') . rand(1000, 9999);
            
            
            
            
            return json([
                'code' => 0,
                'sign' => true,
                'message' => '投注成功',
                'data' => [
                    'order_id' => $orderId,
                    'total_bets' => count($bets),
                    'total_amount' => $totalAmount,
                    'balance' => $user->balance - $totalAmount
                ]
            ]);
            
        } catch (\Exception $e) {
            \support\Log::error('双面投注失败: ' . $e->getMessage());
            return json([
                'code' => 500,
                'sign' => false,
                'message' => '投注失败：' . $e->getMessage(),
                'data' => null
            ]);
        }
    }
}
