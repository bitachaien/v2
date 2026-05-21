<?php

namespace app\controller\api;

use support\Request;
use support\Response;
use support\Db;
use support\Log;
use support\Redis;
use app\service\LotteryService;

class LotteryChatController
{
    
    private static $betTypeMap = [
        
        '大' => 'big',
        '小' => 'small',
        '单' => 'odd',
        '双' => 'even',
        
        '大单' => 'big_odd',
        '小单' => 'small_odd',
        '大双' => 'big_even',
        '小双' => 'small_even',
        
        '极大' => 'jida',
        '极小' => 'jixiao',
        
        '龙' => 'long',
        '虎' => 'hu',
        '豹' => 'bao',
        
        '对子' => 'duizi',
        '顺子' => 'shunzi',
        '豹子' => 'baozi'
    ];

    
    public function chatBet(Request $request): Response
    {
        try {
            $userId = $request->userId;
            if (!$userId) {
                return json(['code' => 401, 'message' => 'Vui lòng đăng nhập']);
            }

            $lotteryCode = $request->post('lotteryCode');
            $issue = $request->post('issue');
            $betText = $request->post('betText');
            $bets = $request->post('bets', []);

            if (empty($lotteryCode) || empty($issue)) {
                return json(['code' => 1, 'message' => '参数不完整']);
            }
            
            
            if (empty($bets) && !empty($betText)) {
                $parseResult = $this->parseBetText($betText);
                if (!$parseResult['valid']) {
                    return json(['code' => 1, 'message' => $parseResult['error']]);
                }
                $bets = $parseResult['bets'];
            }
            
            if (empty($bets)) {
                return json(['code' => 1, 'message' => '请输入Đặt cược内容']);
            }

            
            $caipiao = Db::table('caipiao_caipiao')
                ->where('name', $lotteryCode)
                ->first();
            
            if (!$caipiao) {
                return json(['code' => 1, 'message' => '彩种không tồn tại']);
            }
            
            if ($caipiao->isopen != 1) {
                return json(['code' => 1, 'message' => '彩种đã đóng']);
            }

            
            $sealCheckResult = $this->checkSealStatus($lotteryCode, $issue, $caipiao);
            if ($sealCheckResult !== true) {
                return json(['code' => 1002, 'message' => $sealCheckResult]);
            }

            
            $user = Db::table('caipiao_member')->where('id', $userId)->first();
            if (!$user) {
                return json(['code' => 1, 'message' => 'Người dùng không tồn tại']);
            }
            
            if ($user->islock == 1) {
                return json(['code' => 1, 'message' => 'tài khoản已被锁定']);
            }

            
            $playConfig = Db::table('caipiao_wanfa')
                ->where('typeid', $caipiao->typeid)
                ->where('isopen', 1)
                ->first();
            $minBet = floatval($playConfig->minxf ?? 1);       
            $maxBet = floatval($playConfig->maxxf ?? 10000);   
            $periodMaxBet = floatval($caipiao->maxbet ?? 100000); 

            
            try {
                $redis = new \Redis();
                $redis->connect('127.0.0.1', 6379);
                $rateKey = "bet_rate_limit:{$userId}";
                $rateCount = $redis->incr($rateKey);
                if ($rateCount === 1) {
                    $redis->expire($rateKey, 10); 
                }
                if ($rateCount > 10) { 
                    $redis->close();
                    return json(['code' => 1003, 'message' => '操作过于频繁，请稍后再试']);
                }
                $redis->close();
            } catch (\Exception $e) {
                Log::warning('Redis频率限制检查Thất bại: ' . $e->getMessage());
            }

            
            $totalAmount = 0;
            $betItems = [];
            foreach ($bets as $bet) {
                $amount = floatval($bet['amount'] ?? 0);
                if ($amount <= 0) {
                    return json(['code' => 1, 'message' => 'Đặt cượcSố tiền无效']);
                }
                
                if ($amount < $minBet) {
                    return json(['code' => 1, 'message' => "单注Số tiền不能低于 {$minBet} 元"]);
                }
                
                if ($amount > $maxBet) {
                    return json(['code' => 1, 'message' => "单注Số tiền不能超过 {$maxBet} 元"]);
                }
                $totalAmount += $amount;
                $betItems[] = [
                    'playid' => $bet['playid'] ?? $bet['type'] ?? '',
                    'label' => $bet['label'] ?? $bet['name'] ?? '',
                    'amount' => $amount,
                    'rate' => floatval($bet['rate'] ?? $bet['odds'] ?? 0)
                ];
            }

            
            $existingBetAmount = Db::table('caipiao_touzhu')
                ->where('uid', $userId)
                ->where('cpname', $lotteryCode)
                ->where('expect', $issue)
                ->where('iscancel', 0)
                ->sum('amount') ?? 0;
            
            if ($existingBetAmount + $totalAmount > $periodMaxBet) {
                $remaining = $periodMaxBet - $existingBetAmount;
                return json(['code' => 1, 'message' => "本期Đặt cược已达上限，剩余可投 {$remaining} 元"]);
            }

            
            if ($user->balance < $totalAmount) {
                return json(['code' => 1001, 'message' => 'Số dư không đủ']);
            }

            
            Db::beginTransaction();
            try {
                
                $affected = Db::table('caipiao_member')
                    ->where('id', $userId)
                    ->where('balance', '>=', $totalAmount) 
                    ->decrement('balance', $totalAmount);
                
                if ($affected === 0) {
                    Db::rollBack();
                    return json(['code' => 1001, 'message' => 'Số dư không đủhoặc操作过于频繁，请重试']);
                }
                
                
                $userAfter = Db::table('caipiao_member')->where('id', $userId)->first();
                $balanceBefore = $userAfter->balance + $totalAmount; 
                $balanceAfter = $userAfter->balance;

                
                $orderId = $this->generateOrderId();

                
                $playTitleParts = [];
                foreach ($betItems as $item) {
                    
                    $prefix = is_numeric($item['label']) ? '点' : '';
                    $playTitleParts[] = $item['label'] . $prefix . ':' . $item['amount'];
                }
                $playTitle = implode('|', $playTitleParts);
                
                
                $touzhuData = [
                    'trano' => $orderId,
                    'uid' => $userId,
                    'username' => $user->nickname ?? $user->username,
                    'typeid' => $caipiao->typeid ?? 'xy28',
                    'cpname' => $lotteryCode,
                    'cptitle' => $caipiao->title ?? $lotteryCode,
                    'expect' => $issue,
                    'playid' => 'xy28_combined',
                    'playtitle' => $playTitle,  
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
                    'source' => 'chat', 
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

                
                Db::table('caipiao_moneylog')->insert([
                    'uid' => $userId,
                    'username' => $user->nickname ?? $user->username,
                    'type' => 9,
                    'typename' => 'Đặt cược',
                    'trano' => $orderId,
                    'amount' => -$totalAmount,
                    'before' => $balanceBefore,
                    'after' => $balanceAfter,
                    'addtime' => time(),
                    'remark' => "期号:{$issue} Đặt cược",
                ]);

                
                $chatMessage = [
                    'lottery_code' => $lotteryCode,
                    'issue' => $issue,
                    'user_id' => $userId,
                    'user_name' => $user->nickname ?? $user->username ?? 'Người dùng' . substr($userId, -4),
                    'avatar' => $user->face ?? '',
                    'honor_level' => $this->calculateHonorLevel($userId),
                    'content' => $betText,
                    'message_type' => 'bet',
                    'is_win' => 0,
                    'created_at' => date('Y-m-d H:i:s')
                ];

                $chatMsgId = Db::table('caipiao_lottery_chat')->insertGetId($chatMessage);

                Db::commit();

                
                $broadcastMsg = [
                    'target' => 'lottery',
                    'lotteryCode' => $lotteryCode,
                    'type' => 'lottery_chat_message',
                    'data' => [
                        'id' => $chatMsgId,
                        'userId' => $userId,
                        'userName' => $chatMessage['user_name'],
                        'avatar' => $chatMessage['avatar'],
                        'honorLevel' => $chatMessage['honor_level'],
                        'content' => $betText,
                        'messageType' => 'bet',
                        'time' => date('H:i:s'),
                        'isSystem' => false,
                        'isWin' => false
                    ]
                ];
                
                
                try {
                    $redis = new \Redis();
                    $redis->connect('127.0.0.1', 6379);
                    $redis->rPush('websocket_push_queue', json_encode($broadcastMsg));
                    
                    
                    $statsMsg = [
                        'target' => 'lottery',
                        'lotteryCode' => $lotteryCode,
                        'type' => 'bet_stats_update',
                        'data' => [
                            'issue' => $issue,
                            'stats' => $this->calculateBetStats($lotteryCode, $issue)
                        ]
                    ];
                    $redis->rPush('websocket_push_queue', json_encode($statsMsg));
                    
                    $redis->close();
                } catch (\Exception $e) {
                    Log::warning('Redis push failed: ' . $e->getMessage());
                }

                return json([
                    'code' => 0,
                    'message' => 'Đặt cượcThành công',
                    'data' => [
                        'orderId' => $orderId,
                        'balance' => number_format($balanceAfter, 2, '.', ''),
                        'totalAmount' => $totalAmount
                    ]
                ]);

            } catch (\Exception $e) {
                Db::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            Log::error('聊天室Đặt cượcThất bại: ' . $e->getMessage());
            return json(['code' => 500, 'message' => 'Đặt cượcThất bại: ' . $e->getMessage()]);
        }
    }
    
    
    private function checkSealStatus(string $lotteryCode, string $issue, $caipiao)
    {
        $now = time();
        $ftime = $caipiao->ftime ?? 10; 
        
        
        $currentIssue = Db::table('caipiao_kaijiang')
            ->where('name', $lotteryCode)
            ->where('expect', $issue)
            ->first();
        
        if ($currentIssue) {
            $opentime = $currentIssue->opentime;
            $sealTime = $opentime - $ftime;
            
            
            if (!empty($currentIssue->opencode)) {
                return '该期已Mở thưởng，请Đặt cược下一期';
            }
            
            
            if ($now >= $sealTime) {
                return '当前期已封盘，请等待下一期';
            }
        } else {
            
            $latestIssue = Db::table('caipiao_kaijiang')
                ->where('name', $lotteryCode)
                ->orderBy('id', 'desc')
                ->first();
            
            if ($latestIssue) {
                
                if ($issue <= $latestIssue->expect && !empty($latestIssue->opencode)) {
                    return '该期已Mở thưởng，请Đặt cược下一期';
                }
            }
        }
        
        return true;
    }

    
    public function getChatMessages(Request $request, $code): Response
    {
        try {
            $lotteryCode = $code; 
            $page = (int)$request->get('page', 1);
            $pageSize = (int)$request->get('pageSize', 50);
            $before = $request->get('before'); 

            
            $query = Db::table('caipiao_lottery_chat')
                ->where('lottery_code', $lotteryCode)
                ->orderBy('id', 'desc');

            if ($before) {
                $query->where('id', '<', $before);
            }

            $total = $query->count();
            $list = $query->offset(($page - 1) * $pageSize)
                ->limit($pageSize)
                ->get();

            $messages = [];
            foreach ($list as $item) {
                $msg = [
                    'id' => $item->id,
                    'userId' => $item->user_id,
                    'userName' => $item->user_name,
                    'avatar' => $item->avatar,
                    'honorLevel' => $item->honor_level ?? 0,
                    'content' => $item->content,
                    'time' => $item->created_at,
                    'isSystem' => $item->message_type === 'system',
                    'isWin' => $item->is_win == 1,
                    'messageType' => $item->message_type ?? 'text'
                ];
                
                
                if (!empty($item->extra_data)) {
                    $extraData = json_decode($item->extra_data, true);
                    if (is_array($extraData)) {
                        
                        if ($item->message_type === 'result') {
                            $msg['issue'] = $extraData['issue'] ?? '';
                            $msg['code'] = $extraData['code'] ?? [];
                            $msg['sum'] = $extraData['sum'] ?? 0;
                            $msg['type'] = $extraData['type'] ?? '';
                        }
                        
                        elseif ($item->message_type === 'bill') {
                            $msg['issue'] = $extraData['issue'] ?? '';
                            $msg['details'] = $extraData['details'] ?? [];
                        }
                    }
                }
                
                $messages[] = $msg;
            }

            
            if ($page === 1) {
                try {
                    $redis = Redis::connection('default');
                    
                    
                    $robotKey = "lottery_chat_robot:{$lotteryCode}";
                    $robotMessages = $redis->lRange($robotKey, 0, 199);
                    foreach ($robotMessages as $robotMsg) {
                        $msg = json_decode($robotMsg, true);
                        if ($msg) {
                            $messages[] = $msg;
                        }
                    }
                    
                    
                    $lottery = Db::table('caipiao_caipiao')->where('name', $lotteryCode)->first();
                    $kjsj = $lottery->kjsj ?? 60; 
                    $filterTime = time() - max(120, $kjsj * 2); 
                    
                    $systemKey = "lottery_chat_system:{$lotteryCode}";
                    $systemMessages = $redis->lRange($systemKey, 0, 99);
                    foreach ($systemMessages as $sysMsg) {
                        $msg = json_decode($sysMsg, true);
                        if ($msg) {
                            
                            $msgTime = strtotime($msg['time'] ?? '1970-01-01');
                            if ($msgTime >= $filterTime) {
                                $messages[] = $msg;
                            }
                        }
                    }
                } catch (\Exception $e) {
                    Log::warning('LấyRedisTin nhắnThất bại: ' . $e->getMessage());
                }
            }

            
            usort($messages, function($a, $b) {
                $timeA = strtotime($a['time'] ?? '1970-01-01');
                $timeB = strtotime($b['time'] ?? '1970-01-01');
                return $timeA - $timeB;
            });

            
            $messages = array_slice($messages, -$pageSize);

            return json([
                'code' => 0,
                'message' => 'success',
                'data' => [
                    'list' => $messages,
                    'total' => $total,
                    'hasMore' => $total > $page * $pageSize
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Lấy聊天Tin nhắnThất bại: ' . $e->getMessage());
            return json(['code' => 500, 'message' => 'Lấy dữ liệu thất bại']);
        }
    }

    
    public function getChatOnlineCount(Request $request, $code): Response
    {
        try {
            $lotteryCode = $code;
            
            
            $count = rand(1000, 9999);

            return json([
                'code' => 0,
                'message' => 'success',
                'data' => [
                    'count' => $count
                ]
            ]);

        } catch (\Exception $e) {
            return json(['code' => 500, 'message' => 'Lấy dữ liệu thất bại']);
        }
    }

    
    public function getBetHistory(Request $request, $code): Response
    {
        try {
            $userId = $request->userId;
            if (!$userId) {
                return json(['code' => 401, 'message' => 'Vui lòng đăng nhập']);
            }

            $lotteryCode = $code;
            $timeRange = $request->get('timeRange', 'today');
            $page = intval($request->get('page', 1));
            $pageSize = intval($request->get('pageSize', 50));

            
            $now = time();
            switch ($timeRange) {
                case 'yesterday':
                    $startTime = strtotime('yesterday 00:00:00');
                    $endTime = strtotime('yesterday 23:59:59');
                    break;
                case 'week':
                    $startTime = strtotime('-7 days 00:00:00');
                    $endTime = $now;
                    break;
                case 'today':
                default:
                    $startTime = strtotime('today 00:00:00');
                    $endTime = $now;
                    break;
            }

            
            $query = Db::table('caipiao_touzhu')
                ->where('uid', $userId)
                ->where('cpname', $lotteryCode)
                ->where('oddtime', '>=', $startTime)
                ->where('oddtime', '<=', $endTime)
                ->where('iscancel', 0)
                ->orderBy('oddtime', 'desc');

            $total = $query->count();
            $list = $query->offset(($page - 1) * $pageSize)
                ->limit($pageSize)
                ->get()
                ->toArray();

            return json([
                'code' => 0,
                'message' => 'success',
                'data' => [
                    'list' => $list,
                    'total' => $total,
                    'page' => $page,
                    'pageSize' => $pageSize,
                    'hasMore' => $total > $page * $pageSize
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('LấyĐặt cược历史Thất bại: ' . $e->getMessage());
            return json(['code' => 500, 'message' => 'Lấy dữ liệu thất bại']);
        }
    }

    
    public function getIssueBetStats(Request $request, $code): Response
    {
        try {
            $lotteryCode = $code;
            $issue = $request->get('issue');

            if (empty($issue)) {
                
                $currentIssue = Db::table('caipiao_issue')
                    ->where('cpname', $lotteryCode)
                    ->where('status', '<', 2)
                    ->orderBy('expect', 'desc')
                    ->first();
                $issue = $currentIssue->expect ?? '';
            }

            if (empty($issue)) {
                return json(['code' => 0, 'data' => ['stats' => []]]);
            }

            
            
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
                'c_duizi' => 'duizi', 'c_shunzi' => 'shunzi', 'c_baozi' => 'baozi'
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

            return json([
                'code' => 0,
                'message' => 'success',
                'data' => [
                    'issue' => $issue,
                    'stats' => $result
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('LấyĐặt cược统计Thất bại: ' . $e->getMessage());
            return json(['code' => 500, 'message' => 'Lấy dữ liệu thất bại']);
        }
    }

    
    private function generateOrderId(): string
    {
        $microtime = microtime(true);
        $micro = sprintf('%06d', ($microtime - floor($microtime)) * 1000000);
        $pid = str_pad(getmypid() % 100, 2, '0', STR_PAD_LEFT);
        $rand = str_pad(mt_rand(0, 999), 3, '0', STR_PAD_LEFT);
        return 'BET' . date('YmdHis') . $micro . $pid . $rand;
    }

    
    private function parseBetText(string $text): array
    {
        $bets = [];
        
        $text = str_replace(' ', '|', trim($text));
        $parts = explode('|', $text);
        
        foreach ($parts as $part) {
            $part = trim($part);
            if (empty($part)) continue;
            
            
            if (preg_match('/^(.+?)(?:点)?:(\d+)$/u', $part, $matches)) {
                $playName = trim($matches[1]);
                $amount = intval($matches[2]);
                
                
                if (!isset(self::$betTypeMap[$playName]) && !is_numeric($playName)) {
                    return ['valid' => false, 'error' => "无法解析: {$part}"];
                }
                
                if ($amount <= 0) {
                    return ['valid' => false, 'error' => "Số tiền必须大于0: {$part}"];
                }
                
                
                $type = self::$betTypeMap[$playName] ?? null;
                if ($type === null) {
                    
                    $type = 'tm_' . $playName;
                }
                
                $bets[] = [
                    'type' => $type,
                    'value' => $playName,
                    'label' => $playName,  
                    'amount' => $amount
                ];
            } else {
                return ['valid' => false, 'error' => "无法解析: {$part}"];
            }
        }
        
        if (empty($bets)) {
            return ['valid' => false, 'error' => '请输入有效的Đặt cược内容'];
        }
        
        return ['valid' => true, 'bets' => $bets];
    }

    
    private function getPlayTypeOdds($lotteryCode): array
    {
        $odds = Db::table('caipiao_lottery_play_types')
            ->where('lottery_code', $lotteryCode)
            ->where('status', 1)
            ->get();

        $result = [];
        foreach ($odds as $item) {
            $result[$item->play_type] = $item->odds;
        }

        return $result;
    }

    
    private function calculateBetStats(string $lotteryCode, string $issue): array
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

    
    private function calculateHonorLevel($userId): int
    {
        
        $totalBet = Db::table('caipiao_touzhu')
            ->where('uid', $userId)
            ->where('iscancel', 0) 
            ->sum('amount') ?? 0;

        
        if ($totalBet >= 1000000) return 7;
        if ($totalBet >= 500000) return 6;
        if ($totalBet >= 100000) return 5;
        if ($totalBet >= 50000) return 4;
        if ($totalBet >= 10000) return 3;
        if ($totalBet >= 5000) return 2;
        if ($totalBet >= 1000) return 1;
        return 0;
    }

    
    public function getIssueBets(Request $request, $code)
    {
        $userId = $request->userId;
        if (!$userId) {
            return json(['code' => 401, 'message' => 'Vui lòng đăng nhập']);
        }

        $issue = $request->get('issue', '');
        if (empty($issue)) {
            return json(['code' => 1, 'message' => '期号không được để trống']);
        }

        
        $playTypes = Db::table('caipiao_wanfa')
            ->where('typeid', 'xy28')
            ->get();
        $oddsMap = [];
        foreach ($playTypes as $play) {
            $oddsMap[$play->playid] = $play->rate ?? 0;
        }

        $bets = Db::table('caipiao_touzhu')
            ->where('uid', $userId)
            ->where('cpname', $code)
            ->where('expect', $issue)
            ->where('iscancel', 0)
            ->orderBy('id', 'desc')
            ->get();

        $list = [];
        foreach ($bets as $bet) {
            
            $tzcode = $bet->tzcode ?? '';
            $items = json_decode($tzcode, true);
            
            $labels = [];
            $totalOdds = 0;
            $betCount = 0;
            
            if (is_array($items) && count($items) > 0) {
                $betCount = count($items);
                foreach ($items as $item) {
                    $playid = $item['playid'] ?? '';
                    $label = $item['label'] ?? $playid;
                    $amount = $item['amount'] ?? 0;
                    $labels[] = "{$label}:{$amount}";
                    
                    if ($totalOdds == 0) {
                        $totalOdds = $oddsMap[$playid] ?? 0;
                    }
                }
                $displayLabel = implode('|', $labels);
            } else {
                
                $displayLabel = $bet->playtitle ?? $bet->playid ?? '';
                $totalOdds = $oddsMap[$bet->playid] ?? 0;
                $betCount = 1;
            }
            
            $list[] = [
                'id' => $bet->id,
                'label' => $displayLabel,
                'totalAmount' => $bet->amount, 
                'betCount' => $betCount,       
                'odds' => $totalOdds,
                'time' => date('H:i:s', $bet->opentime ?: time())
            ];
        }

        return json([
            'code' => 0,
            'message' => 'Lấy dữ liệu thành công',
            'data' => $list
        ]);
    }

    
    public function cancelBet(Request $request, $id)
    {
        $userId = $request->userId;
        if (!$userId) {
            return json(['code' => 401, 'message' => 'Vui lòng đăng nhập']);
        }

        $bet = Db::table('caipiao_touzhu')
            ->where('id', $id)
            ->where('uid', $userId)
            ->first();

        if (!$bet) {
            return json(['code' => 1, 'message' => '订单không tồn tại']);
        }

        if ($bet->iscancel == 1) {
            return json(['code' => 1, 'message' => '订单已撤销']);
        }

        if ($bet->isdraw != 0) {
            return json(['code' => 1, 'message' => '订单已结算，无法撤单']);
        }

        
        $lottery = Db::table('caipiao_caipiao')->where('name', $bet->cpname)->first();
        if ($lottery) {
            $sealTime = $lottery->ftime ?? 30;
            $kaijiang = Db::table('caipiao_kaijiang')
                ->where('name', $bet->cpname)
                ->where('expect', $bet->expect)
                ->where('isdraw', 0) 
                ->first();
            
            if ($kaijiang && $kaijiang->opentime) {
                $remaining = $kaijiang->opentime - time();
                if ($remaining <= $sealTime) {
                    return json(['code' => 1, 'message' => '已封盘，无法撤单']);
                }
            }
        }

        
        Db::beginTransaction();
        try {
            
            Db::table('caipiao_touzhu')
                ->where('id', $id)
                ->update(['iscancel' => 1]);

            
            $user = Db::table('caipiao_member')->where('id', $userId)->first();
            $beforeBalance = $user->balance;
            
            
            Db::table('caipiao_member')
                ->where('id', $userId)
                ->increment('balance', $bet->amount);
            
            $afterBalance = $beforeBalance + $bet->amount;

            
            Db::table('caipiao_moneylog')->insert([
                'uid' => $userId,
                'username' => $user->username ?? '',
                'type' => 6, 
                'typename' => '撤单退款',
                'trano' => $bet->trano ?? '',
                'amount' => $bet->amount,
                'before' => $beforeBalance,
                'after' => $afterBalance,
                'remark' => "撤销Đặt cược[{$bet->expect}]{$bet->playtitle}",
                'addtime' => time(),
                'ip' => $request->getRealIp() ?? ''
            ]);

            Db::commit();
            
            
            $this->recallBetMessage($bet);
            
            return json(['code' => 0, 'message' => '撤单Thành công']);
        } catch (\Exception $e) {
            Db::rollBack();
            return json(['code' => 1, 'message' => '撤单Thất bại: ' . $e->getMessage()]);
        }
    }

    
    private function recallBetMessage($bet)
    {
        try {
            
            $tzcode = $bet->tzcode ?? '';
            $items = json_decode($tzcode, true);
            
            if (is_array($items) && count($items) > 0) {
                
                $contents = [];
                foreach ($items as $item) {
                    $label = $item['label'] ?? '';
                    $amount = $item['amount'] ?? 0;
                    if ($label && $amount) {
                        $contents[] = "{$label}:{$amount}";
                    }
                }
                $content = implode('|', $contents);
                
                
                $chatMsg = Db::table('caipiao_lottery_chat')
                    ->where('lottery_code', $bet->cpname)
                    ->where('issue', $bet->expect)
                    ->where('user_id', $bet->uid)
                    ->where('message_type', 'bet')
                    ->where('content', $content)
                    ->first();
                
                if ($chatMsg) {
                    
                    Db::table('caipiao_lottery_chat')->where('id', $chatMsg->id)->delete();
                    
                    
                    $redis = Redis::connection('default');
                    $recallMsg = [
                        'target' => 'lottery',
                        'lotteryCode' => $bet->cpname,
                        'type' => 'message_recall',
                        'data' => [
                            'messageId' => $chatMsg->id,
                            'issue' => $bet->expect
                        ]
                    ];
                    $redis->rPush('websocket_push_queue', json_encode($recallMsg));
                }
            }
        } catch (\Exception $e) {
            
            Log::warning('[cancelBet] 撤回Tin nhắnThất bại: ' . $e->getMessage());
        }
    }

    
    public function modifyBet(Request $request, $id)
    {
        $userId = $request->userId;
        if (!$userId) {
            return json(['code' => 401, 'message' => 'Vui lòng đăng nhập']);
        }

        $newAmount = floatval($request->post('amount', 0));
        $perAmount = floatval($request->post('perAmount', 0));
        if ($newAmount <= 0) {
            return json(['code' => 1, 'message' => 'Số tiền必须大于0']);
        }

        $bet = Db::table('caipiao_touzhu')
            ->where('id', $id)
            ->where('uid', $userId)
            ->first();

        if (!$bet) {
            return json(['code' => 1, 'message' => '订单không tồn tại']);
        }

        if ($bet->iscancel == 1) {
            return json(['code' => 1, 'message' => '订单已撤销']);
        }

        if ($bet->isdraw != 0) {
            return json(['code' => 1, 'message' => '订单已结算，无法Sửa']);
        }

        
        $lottery = Db::table('caipiao_caipiao')->where('name', $bet->cpname)->first();
        if ($lottery) {
            $sealTime = $lottery->ftime ?? 30;
            $kaijiang = Db::table('caipiao_kaijiang')
                ->where('name', $bet->cpname)
                ->where('expect', $bet->expect)
                ->where('isdraw', 0) 
                ->first();
            
            if ($kaijiang && $kaijiang->opentime) {
                $remaining = $kaijiang->opentime - time();
                if ($remaining <= $sealTime) {
                    return json(['code' => 1, 'message' => '已封盘，无法Sửa']);
                }
            }
        }

        $oldAmount = $bet->amount;
        $diff = $newAmount - $oldAmount;

        
        $user = Db::table('caipiao_member')->where('id', $userId)->first();
        if ($diff > 0 && $user->balance < $diff) {
            return json(['code' => 1, 'message' => 'Số dư không đủ']);
        }

        
        Db::beginTransaction();
        try {
            
            Db::table('caipiao_touzhu')
                ->where('id', $id)
                ->update(['amount' => $newAmount]);

            
            $user = Db::table('caipiao_member')->where('id', $userId)->first();
            $beforeBalance = $user->balance;
            
            
            if ($diff > 0) {
                
                Db::table('caipiao_member')
                    ->where('id', $userId)
                    ->decrement('balance', $diff);
                $afterBalance = $beforeBalance - $diff;
            } else {
                
                Db::table('caipiao_member')
                    ->where('id', $userId)
                    ->increment('balance', abs($diff));
                $afterBalance = $beforeBalance + abs($diff);
            }

            
            Db::table('caipiao_moneylog')->insert([
                'uid' => $userId,
                'username' => $user->username ?? '',
                'type' => 7, 
                'typename' => 'SửaĐặt cược',
                'trano' => $bet->trano ?? '',
                'amount' => -$diff,
                'before' => $beforeBalance,
                'after' => $afterBalance,
                'remark' => "SửaĐặt cược[{$bet->expect}]{$bet->playtitle}: {$oldAmount}→{$newAmount}",
                'addtime' => time(),
                'ip' => $request->getRealIp() ?? ''
            ]);

            Db::commit();
            
            
            $this->updateBetMessage($bet, $newAmount, $perAmount);
            
            return json(['code' => 0, 'message' => 'SửaThành công']);
        } catch (\Exception $e) {
            Db::rollBack();
            return json(['code' => 1, 'message' => 'SửaThất bại: ' . $e->getMessage()]);
        }
    }

    
    private function updateBetMessage($bet, $newAmount, $perAmount = 0)
    {
        try {
            
            $tzcode = $bet->tzcode ?? '';
            $items = json_decode($tzcode, true);
            
            if (!is_array($items) || count($items) === 0) {
                return;
            }
            
            
            $oldContents = [];
            foreach ($items as $item) {
                $label = $item['label'] ?? '';
                $amount = $item['amount'] ?? 0;
                if ($label && $amount) {
                    $oldContents[] = "{$label}:{$amount}";
                }
            }
            $oldContent = implode('|', $oldContents);
            
            
            $newContents = [];
            foreach ($items as &$item) {
                
                if ($perAmount > 0) {
                    $item['amount'] = $perAmount;
                } else {
                    $oldTotal = array_sum(array_column($items, 'amount'));
                    $ratio = $oldTotal > 0 ? $newAmount / $oldTotal : 1;
                    $item['amount'] = round($item['amount'] * $ratio, 2);
                }
                $label = $item['label'] ?? '';
                $newContents[] = "{$label}:{$item['amount']}";
            }
            $newContent = implode('|', $newContents);
            
            
            Db::table('caipiao_touzhu')
                ->where('id', $bet->id)
                ->update(['tzcode' => json_encode($items)]);
            
            
            $chatMsg = Db::table('caipiao_lottery_chat')
                ->where('lottery_code', $bet->cpname)
                ->where('issue', $bet->expect)
                ->where('user_id', $bet->uid)
                ->where('message_type', 'bet')
                ->where('content', $oldContent)
                ->first();
            
            if ($chatMsg) {
                
                Db::table('caipiao_lottery_chat')
                    ->where('id', $chatMsg->id)
                    ->update(['content' => $newContent]);
                
                
                $redis = Redis::connection('default');
                $updateMsg = [
                    'target' => 'lottery',
                    'lotteryCode' => $bet->cpname,
                    'type' => 'message_update',
                    'data' => [
                        'messageId' => $chatMsg->id,
                        'content' => $newContent
                    ]
                ];
                $redis->rPush('websocket_push_queue', json_encode($updateMsg));
            }
        } catch (\Exception $e) {
            Log::warning('[modifyBet] 更新Tin nhắnThất bại: ' . $e->getMessage());
        }
    }
}

