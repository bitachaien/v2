<?php

namespace process;

use Workerman\Timer;
use support\Db;
use support\Log;
use support\Redis;

class RobotAutoBet
{
    
    protected $robots = [];
    
    
    protected $config = null;
    
    
    protected $currentIssues = [];
    
    
    protected $sealedStatus = [];
    
    
    protected $bettedRobots = [];
    
    
    protected $playTypes = [
        
        ['type' => 'big', 'label' => '大', 'playid' => 'big', 'odds' => 1.98, 'weight' => 20],
        ['type' => 'small', 'label' => '小', 'playid' => 'small', 'odds' => 1.98, 'weight' => 20],
        ['type' => 'odd', 'label' => '单', 'playid' => 'odd', 'odds' => 1.98, 'weight' => 20],
        ['type' => 'even', 'label' => '双', 'playid' => 'even', 'odds' => 1.98, 'weight' => 20],
        
        ['type' => 'big_odd', 'label' => '大单', 'playid' => 'big_odd', 'odds' => 3.7, 'weight' => 15],
        ['type' => 'big_even', 'label' => '大双', 'playid' => 'big_even', 'odds' => 3.7, 'weight' => 15],
        ['type' => 'small_odd', 'label' => '小单', 'playid' => 'small_odd', 'odds' => 3.7, 'weight' => 15],
        ['type' => 'small_even', 'label' => '小双', 'playid' => 'small_even', 'odds' => 3.7, 'weight' => 15],
        
        ['type' => 'jida', 'label' => '极大', 'playid' => 'jida', 'odds' => 15, 'weight' => 5],
        ['type' => 'jixiao', 'label' => '极小', 'playid' => 'jixiao', 'odds' => 15, 'weight' => 5],
        
        ['type' => 'long', 'label' => '龙', 'playid' => 'long', 'odds' => 2.85, 'weight' => 10],
        ['type' => 'hu', 'label' => '虎', 'playid' => 'hu', 'odds' => 2.85, 'weight' => 10],
        ['type' => 'bao', 'label' => '豹', 'playid' => 'bao', 'odds' => 2.85, 'weight' => 3],
        
        ['type' => 'duizi', 'label' => '对子', 'playid' => 'duizi', 'odds' => 3.2, 'weight' => 8],
        ['type' => 'shunzi', 'label' => '顺子', 'playid' => 'shunzi', 'odds' => 14, 'weight' => 3],
        ['type' => 'baozi', 'label' => '豹子', 'playid' => 'baozi', 'odds' => 66, 'weight' => 1],
        
        ['type' => 'tm_0', 'label' => '0点', 'playid' => 'tm_0', 'odds' => 488, 'weight' => 1],
        ['type' => 'tm_1', 'label' => '1点', 'playid' => 'tm_1', 'odds' => 128, 'weight' => 1],
        ['type' => 'tm_2', 'label' => '2点', 'playid' => 'tm_2', 'odds' => 88, 'weight' => 1],
        ['type' => 'tm_3', 'label' => '3点', 'playid' => 'tm_3', 'odds' => 58, 'weight' => 1],
        ['type' => 'tm_4', 'label' => '4点', 'playid' => 'tm_4', 'odds' => 48, 'weight' => 1],
        ['type' => 'tm_5', 'label' => '5点', 'playid' => 'tm_5', 'odds' => 38, 'weight' => 2],
        ['type' => 'tm_6', 'label' => '6点', 'playid' => 'tm_6', 'odds' => 28, 'weight' => 2],
        ['type' => 'tm_7', 'label' => '7点', 'playid' => 'tm_7', 'odds' => 28, 'weight' => 2],
        ['type' => 'tm_8', 'label' => '8点', 'playid' => 'tm_8', 'odds' => 28, 'weight' => 2],
        ['type' => 'tm_9', 'label' => '9点', 'playid' => 'tm_9', 'odds' => 28, 'weight' => 2],
        ['type' => 'tm_10', 'label' => '10点', 'playid' => 'tm_10', 'odds' => 28, 'weight' => 2],
        ['type' => 'tm_11', 'label' => '11点', 'playid' => 'tm_11', 'odds' => 28, 'weight' => 2],
        ['type' => 'tm_12', 'label' => '12点', 'playid' => 'tm_12', 'odds' => 28, 'weight' => 2],
        ['type' => 'tm_13', 'label' => '13点', 'playid' => 'tm_13', 'odds' => 28, 'weight' => 3],
        ['type' => 'tm_14', 'label' => '14点', 'playid' => 'tm_14', 'odds' => 28, 'weight' => 3],
        ['type' => 'tm_15', 'label' => '15点', 'playid' => 'tm_15', 'odds' => 28, 'weight' => 2],
        ['type' => 'tm_16', 'label' => '16点', 'playid' => 'tm_16', 'odds' => 28, 'weight' => 2],
        ['type' => 'tm_17', 'label' => '17点', 'playid' => 'tm_17', 'odds' => 28, 'weight' => 2],
        ['type' => 'tm_18', 'label' => '18点', 'playid' => 'tm_18', 'odds' => 28, 'weight' => 2],
        ['type' => 'tm_19', 'label' => '19点', 'playid' => 'tm_19', 'odds' => 28, 'weight' => 2],
        ['type' => 'tm_20', 'label' => '20点', 'playid' => 'tm_20', 'odds' => 28, 'weight' => 2],
        ['type' => 'tm_21', 'label' => '21点', 'playid' => 'tm_21', 'odds' => 28, 'weight' => 2],
        ['type' => 'tm_22', 'label' => '22点', 'playid' => 'tm_22', 'odds' => 38, 'weight' => 2],
        ['type' => 'tm_23', 'label' => '23点', 'playid' => 'tm_23', 'odds' => 48, 'weight' => 1],
        ['type' => 'tm_24', 'label' => '24点', 'playid' => 'tm_24', 'odds' => 58, 'weight' => 1],
        ['type' => 'tm_25', 'label' => '25点', 'playid' => 'tm_25', 'odds' => 88, 'weight' => 1],
        ['type' => 'tm_26', 'label' => '26点', 'playid' => 'tm_26', 'odds' => 128, 'weight' => 1],
        ['type' => 'tm_27', 'label' => '27点', 'playid' => 'tm_27', 'odds' => 488, 'weight' => 1],
    ];

    
    public function onWorkerStart()
    {
        Log::info('[RobotAutoBet] 机器人自动下注进程启动');
        
        
        $this->loadRobots();
        $this->loadConfig();
        
        
        Timer::add(1, [$this, 'checkAndBet']);
        
        
        Timer::add(60, [$this, 'loadConfig']);
        
        
        Timer::add(300, [$this, 'restoreRobotBalance']);
    }

    
    public function loadRobots()
    {
        try {
            $this->robots = Db::table('caipiao_member')
                ->where('is_robot', 1)
                ->where('islock', 0)
                ->select(['id', 'username', 'nickname', 'balance', 'face'])
                ->get()
                ->toArray();
            
            Log::info('[RobotAutoBet] 加载 ' . count($this->robots) . ' 个机器人用户');
        } catch (\Exception $e) {
            Log::error('[RobotAutoBet] 加载机器人用户失败: ' . $e->getMessage());
            $this->robots = [];
        }
    }

    
    public function loadConfig()
    {
        try {
            $this->config = Db::table('caipiao_robot_config')
                ->where('is_enabled', 1)
                ->first();
            
            if ($this->config) {
                Log::info('[RobotAutoBet] 配置加载成功: ' . $this->config->name);
            } else {
                Log::warning('[RobotAutoBet] 没有启用的机器人配置');
            }
        } catch (\Exception $e) {
            Log::error('[RobotAutoBet] 加载配置失败: ' . $e->getMessage());
            $this->config = null;
        }
    }

    
    public function checkAndBet()
    {
        if (!$this->config || empty($this->robots)) {
            return;
        }
        
        
        $lotteryCodes = array_filter(explode(',', $this->config->lottery_codes ?? ''));
        if (empty($lotteryCodes)) {
            return;
        }
        
        foreach ($lotteryCodes as $lotteryCode) {
            $lotteryCode = trim($lotteryCode);
            $this->processBetForLottery($lotteryCode);
        }
    }

    
    protected function processBetForLottery(string $lotteryCode)
    {
        try {
            
            $issueInfo = $this->getCurrentIssueInfo($lotteryCode);
            if (!$issueInfo) {
                return;
            }
            
            $currentIssue = $issueInfo['currentIssue'];
            $countdown = $issueInfo['countdown'];
            $status = $issueInfo['status'];
            $lastIssue = $issueInfo['lastIssue'] ?? null;
            
            
            if ($status === 2) {
                return;
            }
            
            
            if ($lastIssue) {
                $lastDrawn = Db::table('caipiao_kaijiang')
                    ->where('name', $lotteryCode)
                    ->where('expect', $lastIssue)
                    ->where('opencode', '!=', '')
                    ->exists();
                
                if (!$lastDrawn) {
                    
                    return;
                }
            }
            
            
            $issueKey = $lotteryCode . '_' . $currentIssue;
            if (!isset($this->bettedRobots[$issueKey])) {
                $this->bettedRobots[$issueKey] = [];
            }
            
            
            foreach ($this->bettedRobots as $key => $val) {
                if (strpos($key, $lotteryCode . '_') === 0 && $key !== $issueKey) {
                    unset($this->bettedRobots[$key]);
                }
            }
            
            
            $ftime = $issueInfo['ftime'] ?? 10;
            $betWindow = $countdown > $ftime && $countdown < ($issueInfo['totalTime'] ?? 90) * 0.8;
            
            if (!$betWindow) {
                return;
            }
            
            
            $minCount = $this->config->bet_count_min ?? 1;
            $maxCount = $this->config->bet_count_max ?? 5;
            $targetCount = rand($minCount, $maxCount);
            
            
            if (count($this->bettedRobots[$issueKey]) >= $targetCount) {
                return;
            }
            
            
            $betIntervalMin = $this->config->bet_interval_min ?? 10;
            $betIntervalMax = $this->config->bet_interval_max ?? 60;
            
            
            $remainingCount = $targetCount - count($this->bettedRobots[$issueKey]);
            $remainingTime = $countdown - $ftime;
            $betProbability = min(1, ($remainingCount / max(1, $remainingTime)) * 2);
            
            if (mt_rand(1, 100) > $betProbability * 100) {
                return;
            }
            
            
            $availableRobots = array_filter($this->robots, function($robot) use ($issueKey) {
                return !in_array($robot->id, $this->bettedRobots[$issueKey]);
            });
            
            if (empty($availableRobots)) {
                return;
            }
            
            $robot = $availableRobots[array_rand($availableRobots)];
            
            
            $this->placeBet($robot, $lotteryCode, $currentIssue);
            
            
            $this->bettedRobots[$issueKey][] = $robot->id;
            
        } catch (\Exception $e) {
            Log::error("[RobotAutoBet] 处理彩种 {$lotteryCode} 下注失败: " . $e->getMessage());
        }
    }

    
    protected function placeBet($robot, string $lotteryCode, string $issue)
    {
        try {
            
            if (empty($this->playTypes)) {
                Log::error("[RobotAutoBet] playTypes为空！");
                return;
            }
            
            
            $betCount = rand(1, 3);
            $selectedPlays = $this->selectPlaysByWeight($betCount);
            
            $totalAmount = 0;
            $betItems = [];
            $betTextParts = [];
            
            foreach ($selectedPlays as $play) {
                if (!$play) {
                    continue;
                }
                
                
                $minAmount = $this->config->min_bet_amount ?? 10;
                $maxAmount = $this->config->max_bet_amount ?? 500;
                $amount = rand($minAmount, $maxAmount);
                
                
                $amount = round($amount / 10) * 10;
                if ($amount < $minAmount) $amount = $minAmount;
                
                $totalAmount += $amount;
                $betItems[] = [
                    'playid' => $play['playid'],
                    'label' => $play['label'],
                    'amount' => $amount
                ];
                $betTextParts[] = $play['label'] . ':' . $amount;
            }
            
            
            if ($robot->balance < $totalAmount) {
                Log::warning("[RobotAutoBet] 机器人 {$robot->nickname} 余额不足");
                return;
            }
            
            $betText = implode('|', $betTextParts);
            
            
            $caipiao = Db::table('caipiao_caipiao')
                ->where('name', $lotteryCode)
                ->first();
            
            
            Db::beginTransaction();
            try {
                
                $balanceBefore = $robot->balance;
                Db::table('caipiao_member')
                    ->where('id', $robot->id)
                    ->decrement('balance', $totalAmount);
                
                $balanceAfter = $balanceBefore - $totalAmount;
                
                
                $orderId = 'RB' . date('YmdHis') . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
                
                
                $touzhuData = [
                    'trano' => $orderId,
                    'uid' => $robot->id,
                    'username' => $robot->nickname ?? $robot->username,
                    'typeid' => $caipiao->typeid ?? 'xy28',
                    'cpname' => $lotteryCode,
                    'cptitle' => $caipiao->title ?? $lotteryCode,
                    'expect' => $issue,
                    'playid' => 'xy28_combined',
                    'playtitle' => '组合投注',
                    'tzcode' => json_encode($betItems),
                    'amount' => $totalAmount,
                    'amountbefor' => $balanceBefore,
                    'amountafter' => $balanceAfter,
                    'itemcount' => count($betItems),
                    'beishu' => 1,
                    'isdraw' => 0, 
                    'oddtime' => time(),
                    'mode' => '元',
                    'yjf' => '元',
                    'source' => 'robot',
                    'repoint' => 0,
                    'repointamout' => 0,
                    'okamount' => 0,
                    'okcount' => 0,
                    'Chase' => 0,
                    'stopChase' => 0,
                    'opencode' => '',
                    'play_type' => 0,
                    'fenshu' => 0,
                    'ishemai' => 0,
                    'rengou' => 0,
                    'isbaodi' => 0,
                    'baodi' => 0,
                    'jindu' => 0,
                    'hemaipic' => 0,
                    'isfull' => 0,
                    'payamount' => 0,
                    'bdjindu' => 0,
                    'showtype' => 0,
                    'opentime' => 0,
                    'realbaodi' => 0,
                    'winorno' => -1,
                    'iscancel' => 0
                ];
                
                Db::table('caipiao_touzhu')->insert($touzhuData);
                
                Db::commit();
                
                
                foreach ($this->robots as &$r) {
                    if ($r->id == $robot->id) {
                        $r->balance -= $totalAmount;
                        break;
                    }
                }
                
                
                $chatMsgId = 'robot_' . time() . '_' . mt_rand(100000, 999999);
                $honorLevel = rand(1, 6);
                $chatMessage = [
                    'id' => $chatMsgId,
                    'lottery_code' => $lotteryCode,
                    'issue' => $issue,
                    'userId' => $robot->id,
                    'userName' => $robot->nickname ?? $robot->username,
                    'avatar' => $robot->face ?? '',
                    'honorLevel' => $honorLevel,
                    'content' => $betText,
                    'messageType' => 'bet',
                    'time' => date('Y-m-d H:i:s'),
                    'isSystem' => false,
                    'isWin' => false,
                    'source' => 'robot'
                ];
                
                
                $redis = Redis::connection('default');
                $cacheKey = "lottery_chat_robot:{$lotteryCode}";
                $redis->lPush($cacheKey, json_encode($chatMessage));
                $redis->lTrim($cacheKey, 0, 199); 
                $redis->expire($cacheKey, 3600);  
                
                
                $this->pushChatMessage($lotteryCode, $chatMessage);
                
                
                $this->pushBetStatsUpdate($lotteryCode, $issue);
                
                Log::info("[RobotAutoBet] 机器人 {$robot->nickname} 下注成功: {$lotteryCode} {$issue} {$betText} 总额:{$totalAmount}");
                
            } catch (\Exception $e) {
                Db::rollBack();
                throw $e;
            }
            
        } catch (\Exception $e) {
            Log::error("[RobotAutoBet] 机器人 {$robot->nickname} 下注失败: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine());
        }
    }

    
    protected function pushChatMessage(string $lotteryCode, array $message)
    {
        try {
            $redis = new \Redis();
            $redis->connect('127.0.0.1', 6379);
            
            $broadcastMsg = [
                'target' => 'lottery',
                'lotteryCode' => $lotteryCode,
                'type' => 'lottery_chat_message',
                'data' => array_merge($message, ['lotteryCode' => $lotteryCode])
            ];
            
            $redis->rPush('websocket_push_queue', json_encode($broadcastMsg));
            $redis->close();
        } catch (\Exception $e) {
            Log::warning('[RobotAutoBet] 推送聊天消息失败: ' . $e->getMessage());
        }
    }

    
    protected function pushBetStatsUpdate(string $lotteryCode, string $issue)
    {
        try {
            
            $stats = $this->calculateBetStats($lotteryCode, $issue);
            
            $redis = new \Redis();
            $redis->connect('127.0.0.1', 6379);
            
            $statsMsg = [
                'target' => 'lottery',
                'lotteryCode' => $lotteryCode,
                'type' => 'bet_stats_update',
                'data' => [
                    'issue' => $issue,
                    'stats' => $stats,
                    'lotteryCode' => $lotteryCode
                ]
            ];
            
            $redis->rPush('websocket_push_queue', json_encode($statsMsg));
            $redis->close();
        } catch (\Exception $e) {
            Log::warning('[RobotAutoBet] 推送统计更新失败: ' . $e->getMessage());
        }
    }

    
    protected function calculateBetStats(string $lotteryCode, string $issue): array
    {
        $bets = Db::table('caipiao_touzhu')
            ->where('cpname', $lotteryCode)
            ->where('expect', $issue)
            ->where('iscancel', 0)
            ->get();

        $stats = [];
        
        $playIdMap = [
            
            'c_big' => 'big', 'c_small' => 'small', 'c_odd' => 'odd', 'c_even' => 'even',
            'c_big_odd' => 'big_odd', 'c_big_even' => 'big_even', 
            'c_small_odd' => 'small_odd', 'c_small_even' => 'small_even',
            'c_jida' => 'jida', 'c_jixiao' => 'jixiao',
            'c_long' => 'long', 'c_hu' => 'hu', 'c_bao' => 'bao',
            'c_duizi' => 'duizi', 'c_shunzi' => 'shunzi', 'c_baozi' => 'baozi',
            
            'extreme_big' => 'jida', 'extreme_small' => 'jixiao',
            
            'big' => 'big', 'small' => 'small', 'odd' => 'odd', 'even' => 'even',
            'big_odd' => 'big_odd', 'big_even' => 'big_even',
            'small_odd' => 'small_odd', 'small_even' => 'small_even',
            'jida' => 'jida', 'jixiao' => 'jixiao',
            'long' => 'long', 'hu' => 'hu', 'bao' => 'bao',
            'duizi' => 'duizi', 'shunzi' => 'shunzi', 'baozi' => 'baozi'
        ];

        foreach ($bets as $bet) {
            $tzcode = $bet->tzcode ?? '';
            
            if (strpos($tzcode, '[') === 0) {
                $items = json_decode($tzcode, true);
                if (is_array($items)) {
                    foreach ($items as $item) {
                        $playid = $item['playid'] ?? '';
                        $amount = floatval($item['amount'] ?? 0);
                        
                        
                        if ($playid === 'number') {
                            $label = $item['label'] ?? '';
                            $key = is_numeric($label) ? 'tm_' . $label : $label;
                        } else {
                            $key = $playIdMap[$playid] ?? $playid;
                            if (strpos($playid, 'hz_') === 0) {
                                $key = 'tm_' . str_replace('hz_', '', $playid);
                            }
                        }
                        
                        if (!isset($stats[$key])) {
                            $stats[$key] = ['users' => [], 'amount' => 0];
                        }
                        $stats[$key]['users'][$bet->uid] = true;
                        $stats[$key]['amount'] += $amount;
                    }
                }
            } else {
                $playid = $bet->playid ?? '';
                $key = $playIdMap[$playid] ?? $playid;
                $amount = floatval($bet->amount ?? 0);
                
                if (!isset($stats[$key])) {
                    $stats[$key] = ['users' => [], 'amount' => 0];
                }
                $stats[$key]['users'][$bet->uid] = true;
                $stats[$key]['amount'] += $amount;
            }
        }

        $result = [];
        foreach ($stats as $key => $data) {
            $result[$key] = [
                'users' => count($data['users']),
                'amount' => round($data['amount'], 2)
            ];
        }

        return $result;
    }

    
    protected function selectPlaysByWeight(int $count): array
    {
        $selected = [];
        $usedKeys = [];
        
        
        $totalWeight = 0;
        foreach ($this->playTypes as $play) {
            $totalWeight += ($play['weight'] ?? 1);
        }
        
        for ($i = 0; $i < $count; $i++) {
            $rand = mt_rand(1, $totalWeight);
            $cumulative = 0;
            
            foreach ($this->playTypes as $key => $play) {
                if (in_array($key, $usedKeys)) {
                    continue; 
                }
                
                $cumulative += ($play['weight'] ?? 1);
                if ($rand <= $cumulative) {
                    $selected[] = $play;
                    $usedKeys[] = $key;
                    break;
                }
            }
        }
        
        return $selected;
    }

    
    protected function getCurrentIssueInfo(string $lotteryCode): ?array
    {
        try {
            $caipiao = Db::table('caipiao_caipiao')
                ->where('name', $lotteryCode)
                ->first();
            
            if (!$caipiao) {
                return null;
            }
            
            $now = time();
            $kjsj = $caipiao->kjsj ?? 90; 
            $ftime = $caipiao->ftime ?? 10; 
            
            
            $pendingIssue = Db::table('caipiao_kaijiang')
                ->where('name', $lotteryCode)
                ->where('opentime', '>', $now) 
                ->orderBy('opentime', 'asc')
                ->first();
            
            if (!$pendingIssue) {
                
                $lastIssue = Db::table('caipiao_kaijiang')
                    ->where('name', $lotteryCode)
                    ->orderBy('id', 'desc')
                    ->first();
                
                if ($lastIssue) {
                    
                    $nextIssueNum = intval(substr($lastIssue->expect, -4)) + 1;
                    $currentIssue = date('Ymd') . str_pad($nextIssueNum, 4, '0', STR_PAD_LEFT);
                    $endTime = $lastIssue->opentime + $kjsj;
                } else {
                    return null;
                }
            } else {
                $currentIssue = $pendingIssue->expect;
                $endTime = $pendingIssue->opentime;
            }
            
            $countdown = $endTime - $now;
            
            
            $status = 1; 
            if ($countdown <= $ftime) {
                $status = 2; 
            }
            
            
            $lastIssueRecord = Db::table('caipiao_kaijiang')
                ->where('name', $lotteryCode)
                ->where('expect', '<', $currentIssue)
                ->orderBy('expect', 'desc')
                ->first();
            
            return [
                'currentIssue' => $currentIssue,
                'lastIssue' => $lastIssueRecord ? $lastIssueRecord->expect : null,
                'countdown' => $countdown,
                'status' => $status,
                'ftime' => $ftime,
                'totalTime' => $kjsj,
                'endTime' => $endTime
            ];
            
        } catch (\Exception $e) {
            Log::error("[RobotAutoBet] 获取期号信息失败 {$lotteryCode}: " . $e->getMessage());
            return null;
        }
    }

    
    public function restoreRobotBalance()
    {
        try {
            $minBalance = ($this->config->max_bet_amount ?? 500) * 10;
            
            
            $affected = Db::table('caipiao_member')
                ->where('is_robot', 1)
                ->where('balance', '<', $minBalance)
                ->update(['balance' => rand(50000, 100000) / 10]);
            
            if ($affected > 0) {
                Log::info("[RobotAutoBet] 恢复 {$affected} 个机器人余额");
                
                $this->loadRobots();
            }
        } catch (\Exception $e) {
            Log::error('[RobotAutoBet] 恢复机器人余额失败: ' . $e->getMessage());
        }
    }
}

