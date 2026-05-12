<?php

namespace app\controller\api;

use support\Request;
use support\Db;
use GuzzleHttp\Client;


class AnimalController
{
    
    private $apiBase = 'https://sdjwchartjbda.t4nmvxp8jsgq.net/gameChart';
    
    
    private $gameIdMap = [
        'yfdwc' => 'OG1ANL',   
        'sfdwc' => 'OG3ANL',   
        'wfdwc' => 'OG5ANL',   
    ];
    
    
    private $animals = [
        1 => ['name' => '饿小宝', 'en' => 'Exiaobao'],
        2 => ['name' => '盒马', 'en' => 'Freshippo'],
        3 => ['name' => '票票', 'en' => 'Piaopao'],
        4 => ['name' => '虾仔', 'en' => 'Xiazai'],
        5 => ['name' => '支小宝', 'en' => 'Zhixiaobao'],
        6 => ['name' => '欢猩', 'en' => 'Huanxing'],
    ];
    
    
    private function getClient()
    {
        return new Client([
            'timeout' => 10,
            'verify' => false,
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ]
        ]);
    }
    
    
    private function getGameId($name)
    {
        return $this->gameIdMap[$name] ?? 'OG1ANL';
    }
    
    
    public function games(Request $request)
    {
        try {
            $client = $this->getClient();
            $response = $client->post($this->apiBase . '/getOpenGame', [
                'json' => [
                    'gamePrefix' => 'OG',
                    'playId' => 'ANL'
                ]
            ]);
            
            $result = json_decode($response->getBody()->getContents(), true);
            
            if ($result['code'] === 'success' && isset($result['data']['data'])) {
                $games = [];
                foreach ($result['data']['data'] as $game) {
                    $games[] = [
                        'gameId' => $game['gameId'],
                        'name' => $game['gameName'],
                        'code' => $this->getCodeFromGameId($game['gameId'])
                    ];
                }
                
                return json([
                    'code' => 0,
                    'message' => 'ok',
                    'data' => $games
                ]);
            }
            
            return json([
                'code' => 500,
                'message' => '获取游戏列表失败',
                'data' => []
            ]);
            
        } catch (\Exception $e) {
            \support\Log::error('获取动物彩游戏列表失败: ' . $e->getMessage());
            return json([
                'code' => 500,
                'message' => '服务暂不可用',
                'data' => []
            ]);
        }
    }
    
    
    private function getCodeFromGameId($gameId)
    {
        $map = array_flip($this->gameIdMap);
        return $map[$gameId] ?? 'yfdwc';
    }
    
    
    public function info(Request $request, $name)
    {
        try {
            $gameId = $this->getGameId($name);
            $client = $this->getClient();
            
            
            $caipiao = Db::table('caipiao_caipiao')
                ->where('name', $name)
                ->first();
            $ftime = $caipiao ? (int)$caipiao->ftime : 10; 
            
            $response = $client->post($this->apiBase . '/getLotteryPlanForCocos/json', [
                'json' => [
                    'gameId' => $gameId
                ]
            ]);
            
            $result = json_decode($response->getBody()->getContents(), true);
            
            if ($result['code'] === 'success' && isset($result['data'])) {
                $data = $result['data'];
                
                
                $apiTime = isset($data['time']) ? strtotime($data['time']) : time();
                $endTime = strtotime($data['endTime']);
                $apiGuessEndTime = strtotime($data['guessEndTime']);
                $issueNo = $data['issueNo'] ?? '';
                
                
                $remainTime = max(0, $endTime - $apiTime);
                
                
                $finalGuessEndTime = $endTime - $ftime;
                $guessRemainTime = max(0, $finalGuessEndTime - $apiTime);
                
                
                $championStats = [];
                if (isset($data['ChampionCount'])) {
                    foreach ($data['ChampionCount'] as $animalId => $count) {
                        $championStats[] = [
                            'id' => (int)$animalId,
                            'name' => $this->animals[$animalId]['name'] ?? '',
                            'en' => $this->animals[$animalId]['en'] ?? '',
                            'count' => $count
                        ];
                    }
                    
                    usort($championStats, function($a, $b) {
                        return $b['count'] - $a['count'];
                    });
                }
                
                return json([
                    'code' => 0,
                    'message' => 'ok',
                    'data' => [
                        'name' => $name,
                        'gameId' => $gameId,
                        'issueNo' => $issueNo,
                        'endTime' => $data['endTime'] ?? '',
                        'guessEndTime' => date('Y-m-d H:i:s', $finalGuessEndTime),
                        'remainTime' => $remainTime,
                        'guessRemainTime' => $guessRemainTime,
                        'isSealed' => $guessRemainTime <= 0,
                        'ftime' => $ftime,
                        'serverTime' => $data['time'] ?? '',
                        'championStats' => $championStats,
                        'animals' => array_values($this->animals)
                    ]
                ]);
            }
            
            return json([
                'code' => 500,
                'message' => '获取期号信息失败',
                'data' => null
            ]);
            
        } catch (\Exception $e) {
            \support\Log::error('获取动物彩期号失败: ' . $e->getMessage());
            return json([
                'code' => 500,
                'message' => '服务暂不可用: ' . $e->getMessage(),
                'data' => null
            ]);
        }
    }
    
    
    public function result(Request $request, $name)
    {
        try {
            $gameId = $this->getGameId($name);
            $issueNo = $request->get('issueNo');
            
            if (!$issueNo) {
                return json([
                    'code' => 400,
                    'message' => '期号不能为空',
                    'data' => null
                ]);
            }
            
            $client = $this->getClient();
            $response = $client->post($this->apiBase . '/lotteryOpenCacheForCocos.json', [
                'json' => [
                    'gameId' => $gameId,
                    'issueNo' => $issueNo
                ]
            ]);
            
            $result = json_decode($response->getBody()->getContents(), true);
            
            if ($result['code'] === 'success' && isset($result['data']['backData'])) {
                $backData = $result['data']['backData'];
                
                
                $lotteryOpen = $backData['lotteryOpen'] ?? '';
                $ranks = $lotteryOpen ? explode(',', $lotteryOpen) : [];
                
                
                $rankInfo = [];
                foreach ($ranks as $index => $animalId) {
                    $animalId = (int)$animalId;
                    $rankInfo[] = [
                        'rank' => $index + 1,
                        'animalId' => $animalId,
                        'name' => $this->animals[$animalId]['name'] ?? '',
                        'en' => $this->animals[$animalId]['en'] ?? ''
                    ];
                }
                
                return json([
                    'code' => 0,
                    'message' => 'ok',
                    'data' => [
                        'gameId' => $backData['gameId'] ?? $gameId,
                        'issueNo' => $backData['issueNo'] ?? $issueNo,
                        'lotteryOpen' => $lotteryOpen,
                        'openTime' => $backData['openTime'] ?? '',
                        'ranks' => $rankInfo
                    ]
                ]);
            }
            
            return json([
                'code' => 404,
                'message' => '暂无开奖数据',
                'data' => null
            ]);
            
        } catch (\Exception $e) {
            \support\Log::error('获取动物彩开奖结果失败: ' . $e->getMessage());
            return json([
                'code' => 500,
                'message' => '服务暂不可用',
                'data' => null
            ]);
        }
    }
    
    
    public function history(Request $request, $name)
    {
        try {
            $pageNo = (int)$request->get('page', 1);
            $pageSize = (int)$request->get('limit', 20);
            $offset = ($pageNo - 1) * $pageSize;
            
            
            $total = Db::table('caipiao_kaijiang')
                ->where('name', $name)
                ->count();
            
            $list = Db::table('caipiao_kaijiang')
                ->where('name', $name)
                ->orderBy('id', 'desc')
                ->offset($offset)
                ->limit($pageSize)
                ->get();
            
            $records = [];
            foreach ($list as $row) {
                $resultStr = $row->opencode ?? '';
                $ranks = $resultStr ? explode(',', $resultStr) : [];
                
                
                $champion = null;
                if (count($ranks) > 0) {
                    $championId = (int)$ranks[0];
                    $champion = [
                        'id' => $championId,
                        'name' => $this->animals[$championId]['name'] ?? '',
                    ];
                }
                
                $records[] = [
                    'issue' => $row->expect,
                    'result' => $resultStr,
                    'endTime' => date('H:i:s', $row->opentime),
                    'champion' => $champion,
                    'ranks' => $ranks
                ];
            }
            
            return json([
                'code' => 0,
                'message' => 'ok',
                'data' => [
                    'count' => $total,
                    'pages' => ceil($total / $pageSize),
                    'records' => $records
                ]
            ]);
            
        } catch (\Exception $e) {
            \support\Log::error('获取动物彩历史记录失败: ' . $e->getMessage());
            return json([
                'code' => 0,
                'message' => 'ok',
                'data' => ['count' => 0, 'pages' => 0, 'records' => []]
            ]);
        }
    }
    
    
    public function plays(Request $request, $name)
    {
        try {
            
            $plays = [
                [
                    'id' => 'dwd',
                    'title' => '定位胆',
                    'desc' => '猜指定名次的动物',
                    'ranks' => ['冠军', '亚军', '季军', '第四', '第五', '第六'],
                    'options' => $this->getAnimalOptions(9.9)
                ],
                [
                    'id' => 'qian2',
                    'title' => '猜冠亚军',
                    'desc' => '猜冠军和亚军',
                    'ranks' => ['冠军', '亚军'],
                    'options' => $this->getAnimalOptions(9.9)
                ],
                [
                    'id' => 'qian3',
                    'title' => '猜前三名',
                    'desc' => '猜冠军、亚军、季军',
                    'ranks' => ['冠军', '亚军', '季军'],
                    'options' => $this->getAnimalOptions(9.9)
                ],
            ];
            
            return json([
                'code' => 0,
                'message' => 'ok',
                'data' => [
                    'plays' => $plays,
                    'animals' => $this->animals
                ]
            ]);
            
        } catch (\Exception $e) {
            return json([
                'code' => 500,
                'message' => '获取玩法失败',
                'data' => null
            ]);
        }
    }
    
    
    private function getAnimalOptions($rate)
    {
        $options = [];
        foreach ($this->animals as $id => $animal) {
            $options[] = [
                'id' => $id,
                'name' => $animal['name'],
                'en' => $animal['en'],
                'rate' => $rate
            ];
        }
        return $options;
    }
    
    
    public function bet(Request $request)
    {
        try {
            
            if (!isset($request->userId)) {
                return json([
                    'code' => 401,
                    'message' => '请先登录',
                    'data' => null
                ]);
            }
            
            $userId = $request->userId;
            
            
            $name = $request->post('name', 'yfdwc');        
            $issueNo = $request->post('issueNo');           
            $playType = $request->post('playType');         
            $bets = $request->post('bets');                 
            
            if (!$issueNo || !$playType || !$bets || !is_array($bets)) {
                return json([
                    'code' => 400,
                    'message' => '参数不完整',
                    'data' => null
                ]);
            }
            
            
            $caipiao = Db::table('caipiao_caipiao')
                ->where('name', $name)
                ->first();
            
            if (!$caipiao || $caipiao->isopen != 1) {
                return json([
                    'code' => 400,
                    'message' => '该彩种不可用',
                    'data' => null
                ]);
            }
            
            
            $gameId = $this->getGameId($name);
            $ftime = (int)$caipiao->ftime ?: 10; 
            
            try {
                $client = $this->getClient();
                $response = $client->post($this->apiBase . '/getLotteryPlanForCocos/json', [
                    'json' => ['gameId' => $gameId]
                ]);
                $result = json_decode($response->getBody()->getContents(), true);
                
                if ($result['code'] === 'success' && isset($result['data'])) {
                    $apiData = $result['data'];
                    $currentIssue = $apiData['issueNo'];
                    
                    
                    $apiTime = isset($apiData['time']) ? strtotime($apiData['time']) : time();
                    $endTime = strtotime($apiData['endTime']);
                    $apiGuessEndTime = strtotime($apiData['guessEndTime']);
                    
                    
                    $finalGuessEndTime = $endTime - $ftime;
                    
                    if ($issueNo != $currentIssue) {
                        return json([
                            'code' => 400,
                            'message' => '期号已过期',
                            'data' => null
                        ]);
                    }
                    
                    if ($apiTime > $finalGuessEndTime) {
                        return json([
                            'code' => 400,
                            'message' => '该期已封盘',
                            'data' => null
                        ]);
                    }
                }
            } catch (\Exception $e) {
                \support\Log::warning('验证期号失败: ' . $e->getMessage());
            }
            
            
            $user = Db::table('caipiao_member')
                ->where('id', $userId)
                ->first();
            
            if (!$user) {
                return json([
                    'code' => 404,
                    'message' => '用户不存在',
                    'data' => null
                ]);
            }
            
            
            $totalAmount = 0;
            foreach ($bets as $bet) {
                $totalAmount += floatval($bet['amount'] ?? 0);
            }
            
            if ($totalAmount <= 0) {
                return json([
                    'code' => 400,
                    'message' => '投注金额无效',
                    'data' => null
                ]);
            }
            
            
            if ($user->balance < $totalAmount) {
                return json([
                    'code' => 400,
                    'message' => '余额不足',
                    'data' => null
                ]);
            }
            
            
            $tzcodes = [];
            foreach ($bets as $bet) {
                $rankId = $bet['rankId'] ?? '';
                $animalId = $bet['animalId'] ?? 0;
                $amount = $bet['amount'] ?? 0;
                $tzcodes[] = "{$rankId}:{$animalId}:{$amount}";
            }
            $tzcode = implode('|', $tzcodes);
            
            
            $playid = 'dwc_' . $playType;
            
            
            Db::beginTransaction();
            
            try {
                $now = time();
                
                
                Db::table('caipiao_member')
                    ->where('id', $userId)
                    ->decrement('balance', $totalAmount);
                
                
                $trano = 'A' . date('ymdHis') . str_pad(rand(0, 99999), 5, '0', STR_PAD_LEFT);
                
                
                $betId = Db::table('caipiao_touzhu')->insertGetId([
                    'uid' => $userId,
                    'username' => $user->username,
                    'trano' => $trano,
                    'cpname' => $name,
                    'cptitle' => $caipiao->title,
                    'typeid' => $caipiao->typeid,
                    'expect' => $issueNo,
                    'playid' => $playid,
                    'playtitle' => $this->getPlayTitle($playType),
                    'tzcode' => $tzcode,
                    'amount' => $totalAmount,
                    'amountafter' => $user->balance - $totalAmount,
                    'beishu' => 1,
                    'mode' => 1,
                    'itemcount' => count($bets),
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
                    'remark' => "投注 {$caipiao->title} 第{$issueNo}期",
                    'addtime' => $now,
                    'ip' => $request->getRealIp()
                ]);
                
                Db::commit();
                
                return json([
                    'code' => 0,
                    'message' => '投注成功',
                    'data' => [
                        'trano' => $trano,
                        'totalAmount' => $totalAmount,
                        'balance' => number_format($user->balance - $totalAmount, 2, '.', '')
                    ]
                ]);
                
            } catch (\Exception $e) {
                Db::rollBack();
                throw $e;
            }
            
        } catch (\Exception $e) {
            \support\Log::error('动物彩投注失败: ' . $e->getMessage());
            return json([
                'code' => 500,
                'message' => '投注失败：' . $e->getMessage(),
                'data' => null
            ]);
        }
    }
    
    
    private function getPlayTitle($playType)
    {
        $titles = [
            'dwd' => '定位胆',
            'qian2' => '猜冠亚军',
            'qian3' => '猜前三名',
        ];
        return $titles[$playType] ?? '动物彩';
    }
    
    
    public function myBets(Request $request, $name)
    {
        try {
            if (!isset($request->userId)) {
                return json([
                    'code' => 401,
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
                    'okamount' => number_format($item->okamount ?? 0, 2, '.', ''),
                    'status' => $statusText,
                    'status_color' => $statusColor,
                    'isdraw' => $item->isdraw,
                    'time' => date('Y-m-d H:i:s', $item->oddtime)
                ];
            }
            
            return json([
                'code' => 0,
                'message' => 'ok',
                'count' => $total,
                'data' => $data
            ]);
            
        } catch (\Exception $e) {
            \support\Log::error('获取动物彩投注记录失败: ' . $e->getMessage());
            return json([
                'code' => 500,
                'message' => '获取数据失败',
                'count' => 0,
                'data' => []
            ]);
        }
    }
}
