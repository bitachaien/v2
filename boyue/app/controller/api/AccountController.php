<?php

namespace app\controller\api;

use support\Request;
use support\Db;

class AccountController
{
    
    public function balance(Request $request)
    {
        $userId = $request->userId ?? 0;
        
        if (!$userId) {
            return json([
                'code' => 401,
                'sign' => false,
                'message' => 'unauthorized',
                'data' => null
            ]);
        }
        
        try {
            $user = Db::table('caipiao_member')
                ->where('id', $userId)
                ->first();
            
            if (!$user) {
                return json([
                    'code' => 404,
                    'sign' => false,
                    'message' => 'user_not_found',
                    'data' => null
                ]);
            }
            
            return json([
                'code' => 0,
                'sign' => true,
                'message' => 'ok',
                'data' => [
                    'balance' => number_format($user->balance ?? 0, 2, '.', ''),
                    'xima' => number_format($user->xima ?? 0, 2, '.', ''),
                    'yebmoney' => number_format($user->yebmoney ?? 0, 2, '.', ''),
                ]
            ]);
            
        } catch (\Exception $e) {
            \support\Log::error('Lấy số dư thất bại: ' . $e->getMessage());
            return json([
                'code' => 500,
                'sign' => false,
                'message' => 'Lấy số dư thất bại: ' . $e->getMessage(),
                'data' => null
            ]);
        }
    }
    
    
    public function refreshBalance(Request $request)
    {
        $userId = $request->userId ?? 0;
        
        if (!$userId) {
            return json([
                'code' => 401,
                'sign' => false,
                'message' => 'unauthorized',
                'data' => null
            ]);
        }
        
        try {
            $user = Db::table('caipiao_member')
                ->where('id', $userId)
                ->first();
            
            if (!$user) {
                return json([
                    'code' => 404,
                    'sign' => false,
                    'message' => 'user_not_found',
                    'data' => null
                ]);
            }
            
            
            return json([
                'code' => 0,
                'sign' => true,
                'message' => 'ok',
                'data' => number_format($user->balance ?? 0, 2, '.', '')
            ]);
            
        } catch (\Exception $e) {
            \support\Log::error('Làm mớiSố dưThất bại: ' . $e->getMessage());
            return json([
                'code' => 500,
                'sign' => false,
                'message' => 'Làm mớiThất bại',
                'data' => '0.00'
            ]);
        }
    }
    
    
    public function transactions(Request $request)
    {
        return $this->transactionRecords($request);
    }
    
    
    public function betRecords(Request $request)
    {
        $userId = $request->userId ?? 0;
        
        if (!$userId) {
            return json([
                'code' => 401,
                'sign' => false,
                'message' => 'unauthorized',
                'data' => null
            ]);
        }
        
        try {
            $page = $request->get('page', 1);
            $pageSize = $request->get('pageSize', $request->get('limit', 20));
            $cpname = $request->get('cpname', ''); 
            $gameName = $request->get('gameName', ''); 
            $lotteryCode = $request->get('lotteryCode', ''); 
            $category = $request->get('category', ''); 
            $platform = $request->get('platform', ''); 
            $game = $request->get('game', ''); 
            $status = $request->get('status', ''); 
            $timeRange = $request->get('timeRange', ''); 
            $startDate = $request->get('startDate', '');
            $endDate = $request->get('endDate', '');
            
            $query = Db::table('caipiao_touzhu')
                ->where('uid', $userId);
            
            
            if ($category && $category !== 'lottery') {
                
                $query->whereRaw('1=0');
            }
            
            
            if ($platform) {
                
            }
            
            
            if ($game) {
                $query->where('cpname', $game);
            } elseif ($cpname) {
                $query->where('cpname', $cpname);
            } elseif ($gameName) {
                $query->where('cpname', $gameName);
            } elseif ($lotteryCode) {
                $query->where('cpname', $lotteryCode);
            }
            
            
            if ($status && $status !== 'all') {
                if ($status === 'pending') {
                    
                    $query->where('isdraw', 0);
                } elseif ($status === 'settled') {
                    
                    $query->whereIn('isdraw', [1, -1]);
                } elseif ($status === 'cancelled') {
                    
                    $query->where('isdraw', -2);
                } elseif ($status === 'waiting') {
                    
                    $query->where('isdraw', 0);
                } elseif ($status === 'win') {
                    $query->where('isdraw', 1);
                } elseif ($status === 'lose') {
                    $query->where('isdraw', -1);
                } else {
                    
                    $query->where('isdraw', $status);
                }
            }
            
            
            if ($timeRange) {
                list($startTime, $endTime) = $this->getTimeRange($timeRange);
                $query->where('oddtime', '>=', $startTime)
                      ->where('oddtime', '<=', $endTime);
            } elseif ($startDate || $endDate) {
                if ($startDate) {
                    $query->where('oddtime', '>=', strtotime($startDate . ' 00:00:00'));
                }
                if ($endDate) {
                    $query->where('oddtime', '<=', strtotime($endDate . ' 23:59:59'));
                }
            }
            
            $total = $query->count();
            
            $list = $query->orderBy('oddtime', 'desc')
                ->offset(($page - 1) * $pageSize)
                ->limit($pageSize)
                ->get();
            
            $data = [];
            foreach ($list as $item) {
                
                $statusText = '';
                $statusColor = '';
                $profit = ($item->okamount ?? 0) - ($item->amount ?? 0);
                if ($item->isdraw == 1 || $item->isdraw == -1) {
                    
                    $statusText = '已结算';
                    $statusColor = $profit > 0 ? 'green' : ($profit < 0 ? 'red' : 'gray');
                } elseif ($item->isdraw == 0) {
                    $statusText = '未结算';
                    $statusColor = 'blue';
                } elseif ($item->isdraw == -2) {
                    $statusText = '已撤单';
                    $statusColor = 'gray';
                }
                
                $data[] = [
                    'id' => (string)$item->id,
                    'trano' => $item->trano ?? '',
                    'cpname' => $item->cpname ?? '',
                    'cptitle' => $item->cptitle ?? '',
                    'gameName' => $item->cptitle ?? '',
                    'expect' => $item->expect ?? '',
                    'issue' => $item->expect ?? '',
                    'playid' => $item->playid ?? '',
                    'playtitle' => $item->playtitle ?? '',
                    'playType' => $item->playtitle ?? '',
                    'itemcount' => $item->itemcount ?? 0,
                    'amount' => number_format($item->amount ?? 0, 2, '.', ''),
                    'betAmount' => number_format($item->amount ?? 0, 2, '.', ''),
                    'okamount' => number_format($item->okamount ?? 0, 2, '.', ''),
                    'winAmount' => number_format($item->okamount ?? 0, 2, '.', ''),
                    'profit' => number_format($profit, 2, '.', ''),
                    'beishu' => $item->beishu ?? 1,
                    'multiple' => $item->beishu ?? 1,
                    'tzcode' => $item->tzcode ?? '',
                    'betContent' => $item->tzcode ?? '',
                    'opencode' => $item->opencode ?? '',
                    'drawNumber' => $item->opencode ?? '',
                    'resultNumbers' => $item->opencode ?? '',
                    'status' => $statusText,
                    'status_color' => $statusColor,
                    'isdraw' => (string)($item->isdraw ?? 0),
                    'oddtime' => date('Y-m-d H:i:s', $item->oddtime ?? time()),
                    'betTime' => date('Y-m-d H:i:s', $item->oddtime ?? time()),
                    'time' => date('Y-m-d H:i:s', $item->oddtime ?? time()),
                    'drawTime' => $item->opentime ? date('Y-m-d H:i:s', $item->opentime) : '',
                    'orderNo' => $item->trano ?? ''
                ];
            }
            
            return json([
                'code' => 0,
                'sign' => true,
                'message' => 'ok',
                'count' => $total,
                'total' => $total,
                'page' => (int)$page,
                'pageSize' => (int)$pageSize,
                'data' => $data
            ]);
            
        } catch (\Exception $e) {
            \support\Log::error('LấyĐặt cượclịch sử thất bại: ' . $e->getMessage());
            return json([
                'code' => 500,
                'sign' => false,
                'message' => 'Lấy dữ liệuThất bại：' . $e->getMessage(),
                'data' => null
            ]);
        }
    }
    
    
    public function betStats(Request $request)
    {
        $userId = $request->userId ?? 0;
        
        if (!$userId) {
            return json([
                'code' => 401,
                'sign' => false,
                'message' => 'unauthorized',
                'data' => null
            ]);
        }
        
        try {
            $startDate = $request->get('startDate', '');
            $endDate = $request->get('endDate', '');
            $status = $request->get('status', '');
            $category = $request->get('category', ''); 
            $platform = $request->get('platform', ''); 
            $game = $request->get('game', ''); 
            
            $query = Db::table('caipiao_touzhu')->where('uid', $userId);
            
            
            if ($category && $category !== 'lottery') {
                
                $query->whereRaw('1=0');
            }
            
            
            if ($platform) {
                
            }
            
            
            if ($game) {
                $query->where('cpname', $game);
            }
            
            
            if ($status && $status !== 'all') {
                if ($status === 'pending') {
                    $query->where('isdraw', 0);
                } elseif ($status === 'settled') {
                    $query->whereIn('isdraw', [1, -1]);
                } elseif ($status === 'cancelled') {
                    $query->where('isdraw', -2);
                } elseif ($status === 'waiting') {
                    $query->where('isdraw', 0);
                }
            }
            
            
            if ($startDate) {
                $query->where('oddtime', '>=', strtotime($startDate . ' 00:00:00'));
            }
            if ($endDate) {
                $query->where('oddtime', '<=', strtotime($endDate . ' 23:59:59'));
            }
            
            
            $count = $query->count();
            
            
            $amountQuery = clone $query;
            $totalAmount = $amountQuery->where('isdraw', '!=', -2)->sum('amount');
            
            
            $profitQuery = Db::table('caipiao_touzhu')->where('uid', $userId)->where('isdraw', '!=', -2);
            
            
            if ($category && $category !== 'lottery') {
                $profitQuery->whereRaw('1=0');
            }
            if ($game) {
                $profitQuery->where('cpname', $game);
            }
            if ($status && $status !== 'all') {
                if ($status === 'pending') {
                    $profitQuery->where('isdraw', 0);
                } elseif ($status === 'settled') {
                    $profitQuery->whereIn('isdraw', [1, -1]);
                } elseif ($status === 'cancelled') {
                    $profitQuery->where('isdraw', -2);
                } elseif ($status === 'waiting') {
                    $profitQuery->where('isdraw', 0);
                }
            }
            if ($startDate) {
                $profitQuery->where('oddtime', '>=', strtotime($startDate . ' 00:00:00'));
            }
            if ($endDate) {
                $profitQuery->where('oddtime', '<=', strtotime($endDate . ' 23:59:59'));
            }
            
            $totalWin = $profitQuery->sum('okamount');
            $totalBet = $profitQuery->sum('amount');
            $profit = $totalWin - $totalBet;
            
            return json([
                'code' => 0,
                'sign' => true,
                'message' => 'ok',
                'data' => [
                    'count' => $count,
                    'amount' => number_format($totalAmount ?? 0, 2, '.', ''),
                    'profit' => number_format($profit, 2, '.', '')
                ]
            ]);
            
        } catch (\Exception $e) {
            \support\Log::error('LấyĐặt cược统计Thất bại: ' . $e->getMessage());
            return json([
                'code' => 500,
                'sign' => false,
                'message' => 'Lấy dữ liệuThất bại：' . $e->getMessage(),
                'data' => null
            ]);
        }
    }
    
    
    public function profitLoss(Request $request)
    {
        $userId = $request->userId ?? 0;
        
        if (!$userId) {
            return json([
                'code' => 401,
                'sign' => false,
                'message' => 'unauthorized',
                'data' => null
            ]);
        }
        
        try {
            $startDate = $request->get('start_date', date('Y-m-d'));
            $endDate = $request->get('end_date', date('Y-m-d'));
            $category = $request->get('category', ''); 
            $platform = $request->get('platform', ''); 
            
            $startTime = strtotime($startDate . ' 00:00:00');
            $endTime = strtotime($endDate . ' 23:59:59');
            
            
            $baseQuery = Db::table('caipiao_touzhu')
                ->where('uid', $userId)
                ->where('isdraw', '!=', -2) 
                ->where('oddtime', '>=', $startTime)
                ->where('oddtime', '<=', $endTime);
            
            
            if ($category && $category !== 'lottery') {
                
                return json([
                    'code' => 0,
                    'sign' => true,
                    'message' => 'ok',
                    'data' => $this->getEmptyReportData($startDate, $endDate)
                ]);
            }
            
            
            $totalBet = (clone $baseQuery)->sum('amount') ?? 0;
            
            $totalWin = (clone $baseQuery)->where('isdraw', 1)->sum('okamount') ?? 0;
            
            
            $rebateTypes = ['fanshui', 'xima', 'activity_cz', 'activity_czzs', 'activity_bindcard'];
            $totalRebate = Db::table('caipiao_fuddetail')
                ->where('uid', $userId)
                ->whereIn('typename', $rebateTypes)
                ->where('oddtime', '>=', $startTime)
                ->where('oddtime', '<=', $endTime)
                ->sum('amount') ?? 0;
            
            
            $totalProfit = $totalWin - $totalBet + $totalRebate;
            
            $summary = [
                'total_bet' => number_format($totalBet, 2, '.', ''),
                'total_win' => number_format($totalWin, 2, '.', ''),
                'total_rebate' => number_format($totalRebate, 2, '.', ''),
                'total_profit' => number_format($totalProfit, 2, '.', '')
            ];
            
            
            $categoryList = [];
            
            
            $gameStats = Db::table('caipiao_touzhu')
                ->select(Db::raw('cpname, cptitle, SUM(amount) as bet_amount, SUM(CASE WHEN isdraw=1 THEN okamount ELSE 0 END) as win_amount'))
                ->where('uid', $userId)
                ->where('isdraw', '!=', -2)
                ->where('oddtime', '>=', $startTime)
                ->where('oddtime', '<=', $endTime)
                ->groupBy('cpname', 'cptitle')
                ->get();
            
            foreach ($gameStats as $game) {
                $bet = floatval($game->bet_amount);
                $win = floatval($game->win_amount);
                $profit = $win - $bet;
                
                $categoryList[] = [
                    'category' => 'lottery',
                    'category_name' => 'Xổ số',
                    'platform' => 'boyue',
                    'platform_name' => '博悦Xổ số',
                    'game' => $game->cpname,
                    'game_name' => $game->cptitle ?: $game->cpname,
                    'bet_amount' => number_format($bet, 2, '.', ''),
                    'win_amount' => number_format($win, 2, '.', ''),
                    'profit' => number_format($profit, 2, '.', '')
                ];
            }
            
            
            $dailyList = [];
            
            $dailyStats = Db::table('caipiao_touzhu')
                ->select(Db::raw('FROM_UNIXTIME(oddtime, "%Y-%m-%d") as date, SUM(amount) as bet_amount, SUM(CASE WHEN isdraw=1 THEN okamount ELSE 0 END) as win_amount'))
                ->where('uid', $userId)
                ->where('isdraw', '!=', -2)
                ->where('oddtime', '>=', $startTime)
                ->where('oddtime', '<=', $endTime)
                ->groupBy(Db::raw('FROM_UNIXTIME(oddtime, "%Y-%m-%d")'))
                ->orderBy('date', 'desc')
                ->get();
            
            foreach ($dailyStats as $day) {
                $bet = floatval($day->bet_amount);
                $win = floatval($day->win_amount);
                $profit = $win - $bet;
                
                $dailyList[] = [
                    'date' => $day->date,
                    'bet_amount' => number_format($bet, 2, '.', ''),
                    'win_amount' => number_format($win, 2, '.', ''),
                    'profit' => number_format($profit, 2, '.', '')
                ];
            }
            
            return json([
                'code' => 0,
                'sign' => true,
                'message' => 'ok',
                'data' => [
                    'summary' => $summary,
                    'category_list' => $categoryList,
                    'daily_list' => $dailyList,
                    'start_date' => $startDate,
                    'end_date' => $endDate
                ]
            ]);
            
        } catch (\Exception $e) {
            \support\Log::error('Lấy盈亏报表Thất bại: ' . $e->getMessage());
            return json([
                'code' => 500,
                'sign' => false,
                'message' => 'Lấy dữ liệuThất bại：' . $e->getMessage(),
                'data' => null
            ]);
        }
    }
    
    
    private function getEmptyReportData($startDate, $endDate)
    {
        return [
            'summary' => [
                'total_bet' => '0.00',
                'total_win' => '0.00',
                'total_rebate' => '0.00',
                'total_profit' => '0.00'
            ],
            'category_list' => [],
            'daily_list' => [],
            'start_date' => $startDate,
            'end_date' => $endDate
        ];
    }
    
    
    public function billRecords(Request $request)
    {
        $userId = $request->userId ?? 0;
        
        if (!$userId) {
            return json([
                'code' => 401,
                'message' => '未Đăng nhậphoặcĐăng nhập已过期',
                'data' => null
            ]);
        }
        
        try {
            $page = $request->get('page', 1);
            $pageSize = $request->get('pageSize', 20);
            $type = $request->get('type', 'all');
            $startDate = $request->get('startDate', '');
            $endDate = $request->get('endDate', '');
            $status = $request->get('status', '');
            
            $query = Db::table('caipiao_fuddetail')
                ->where('uid', $userId);
            
            
            if ($type && $type !== 'all') {
                $query->where('typename', $type);
            }
            
            
            if ($startDate) {
                $query->where('oddtime', '>=', strtotime($startDate . ' 00:00:00'));
            }
            if ($endDate) {
                $query->where('oddtime', '<=', strtotime($endDate . ' 23:59:59'));
            }
            
            
            $summary = [
                'totalIncome' => Db::table('caipiao_fuddetail')
                    ->where('uid', $userId)
                    ->where('amount', '>', 0)
                    ->sum('amount'),
                'totalExpense' => abs(Db::table('caipiao_fuddetail')
                    ->where('uid', $userId)
                    ->where('amount', '<', 0)
                    ->sum('amount'))
            ];
            $summary['netProfit'] = $summary['totalIncome'] - $summary['totalExpense'];
            
            $total = $query->count();
            
            $list = $query->orderBy('oddtime', 'desc')
                ->offset(($page - 1) * $pageSize)
                ->limit($pageSize)
                ->get();
            
            $data = [];
            foreach ($list as $item) {
                $data[] = [
                    'id' => $item->id,
                    'type' => $item->typename ?? '',
                    'amount' => number_format($item->amount ?? 0, 2, '.', ''),
                    'balance_before' => number_format($item->amountbefor ?? 0, 2, '.', ''),
                    'balance_after' => number_format($item->amountafter ?? 0, 2, '.', ''),
                    'remark' => $item->remark ?? '',
                    'created_at' => date('Y-m-d H:i:s', $item->oddtime ?? time()),
                    'status' => 'success'
                ];
            }
            
            return json([
                'code' => 0,
                'message' => 'Lấy dữ liệu thành công',
                'data' => [
                    'list' => $data,
                    'total' => $total,
                    'page' => (int)$page,
                    'pageSize' => (int)$pageSize,
                    'summary' => [
                        'totalIncome' => number_format($summary['totalIncome'], 2, '.', ''),
                        'totalExpense' => number_format($summary['totalExpense'], 2, '.', ''),
                        'netProfit' => number_format($summary['netProfit'], 2, '.', '')
                    ]
                ]
            ]);
            
        } catch (\Exception $e) {
            \support\Log::error('Lấy交易明细Thất bại: ' . $e->getMessage());
            return json([
                'code' => 500,
                'message' => 'Lấy dữ liệuThất bại：' . $e->getMessage(),
                'data' => null
            ]);
        }
    }
    
    
    public function rechargeRecords(Request $request)
    {
        $userId = $request->userId ?? 0;
        
        if (!$userId) {
            return json([
                'code' => 401,
                'message' => '未Đăng nhậphoặcĐăng nhập已过期',
                'data' => null
            ]);
        }
        
        try {
            $page = $request->get('page', 1);
            $pageSize = $request->get('pageSize', 20);
            $status = $request->get('status', 'all');
            $startDate = $request->get('startDate', '');
            $endDate = $request->get('endDate', '');
            
            $query = Db::table('caipiao_recharge')
                ->where('uid', $userId);
            
            
            if ($status && $status !== 'all') {
                $statusMap = [
                    'pending' => 0,
                    'success' => 1,
                    'failed' => -1
                ];
                if (isset($statusMap[$status])) {
                    $query->where('state', $statusMap[$status]);
                }
            }
            
            
            if ($startDate) {
                $query->where('oddtime', '>=', strtotime($startDate . ' 00:00:00'));
            }
            if ($endDate) {
                $query->where('oddtime', '<=', strtotime($endDate . ' 23:59:59'));
            }
            
            
            $todayStart = strtotime(date('Y-m-d 00:00:00'));
            $summary = [
                'totalRecharge' => Db::table('caipiao_recharge')
                    ->where('uid', $userId)
                    ->where('state', 1)
                    ->sum('amount'),
                'successCount' => Db::table('caipiao_recharge')
                    ->where('uid', $userId)
                    ->where('state', 1)
                    ->count(),
                'todayRecharge' => Db::table('caipiao_recharge')
                    ->where('uid', $userId)
                    ->where('state', 1)
                    ->where('oddtime', '>=', $todayStart)
                    ->sum('amount')
            ];
            
            $total = $query->count();
            
            $list = $query->orderBy('oddtime', 'desc')
                ->offset(($page - 1) * $pageSize)
                ->limit($pageSize)
                ->get();
            
            $data = [];
            foreach ($list as $item) {
                $statusText = '';
                switch ($item->state) {
                    case 0:
                        $statusText = 'Chờ duyệt';
                        break;
                    case 1:
                        $statusText = 'Nạp tiềnThành công';
                        break;
                    case -1:
                        $statusText = 'Nạp tiềnThất bại';
                        break;
                }
                
                
                $channel = $item->paytype ?? 'bank';
                $channelName = $item->paytypename ?? $this->getChannelName($channel);
                
                $data[] = [
                    'id' => $item->id,
                    'orderNo' => $item->trano ?? '',
                    'amount' => number_format($item->amount ?? 0, 2, '.', ''),
                    'actualAmount' => number_format($item->actualamount ?? $item->amount ?? 0, 2, '.', ''),
                    'channel' => $channel,
                    'channelName' => $channelName,
                    'status' => $item->state == 1 ? 'success' : ($item->state == 0 ? 'pending' : 'failed'),
                    'statusText' => $statusText,
                    'createdAt' => date('Y-m-d H:i:s', $item->oddtime ?? time()),
                    'paidAt' => '',
                    'completedAt' => $item->state == 1 ? date('Y-m-d H:i:s', $item->oddtime) : '',
                    'bonus' => number_format(0, 2, '.', ''),
                    'remark' => $item->remark ?? ''
                ];
            }
            
            return json([
                'code' => 0,
                'message' => 'Lấy dữ liệu thành công',
                'data' => [
                    'total' => $total,
                    'page' => (int)$page,
                    'pageSize' => (int)$pageSize,
                    'summary' => [
                        'totalRecharge' => number_format($summary['totalRecharge'], 2, '.', ''),
                        'successCount' => $summary['successCount'],
                        'todayRecharge' => number_format($summary['todayRecharge'], 2, '.', '')
                    ],
                    'list' => $data
                ]
            ]);
            
        } catch (\Exception $e) {
            \support\Log::error('Lấy nạp tiềnlịch sử thất bại: ' . $e->getMessage());
            return json([
                'code' => 500,
                'message' => 'Lấy dữ liệuThất bại：' . $e->getMessage(),
                'data' => null
            ]);
        }
    }
    
    
    public function withdrawRecords(Request $request)
    {
        $userId = $request->userId ?? 0;
        
        if (!$userId) {
            return json([
                'code' => 401,
                'message' => '未Đăng nhậphoặcĐăng nhập已过期',
                'data' => null
            ]);
        }
        
        try {
            $page = $request->get('page', 1);
            $pageSize = $request->get('pageSize', 20);
            $status = $request->get('status', 'all');
            $startDate = $request->get('startDate', '');
            $endDate = $request->get('endDate', '');
            
            $query = Db::table('caipiao_withdraw')
                ->where('uid', $userId);
            
            
            if ($status && $status !== 'all') {
                $statusMap = [
                    'pending' => 0,
                    'processing' => 1,
                    'success' => 1,  
                    'failed' => -1,
                    'rejected' => -1
                ];
                if (isset($statusMap[$status])) {
                    $query->where('state', $statusMap[$status]);
                }
            }
            
            
            if ($startDate) {
                $query->where('oddtime', '>=', strtotime($startDate . ' 00:00:00'));
            }
            if ($endDate) {
                $query->where('oddtime', '<=', strtotime($endDate . ' 23:59:59'));
            }
            
            
            $todayStart = strtotime(date('Y-m-d 00:00:00'));
            $summary = [
                'totalWithdraw' => Db::table('caipiao_withdraw')
                    ->where('uid', $userId)
                    ->where('state', 1)
                    ->sum('amount'),
                'processingCount' => Db::table('caipiao_withdraw')
                    ->where('uid', $userId)
                    ->where('state', 0)
                    ->count(),
                'successCount' => Db::table('caipiao_withdraw')
                    ->where('uid', $userId)
                    ->where('state', 1)
                    ->count(),
                'todayWithdraw' => Db::table('caipiao_withdraw')
                    ->where('uid', $userId)
                    ->where('state', 1)
                    ->where('oddtime', '>=', $todayStart)
                    ->sum('amount')
            ];
            
            $total = $query->count();
            
            $list = $query->orderBy('oddtime', 'desc')
                ->offset(($page - 1) * $pageSize)
                ->limit($pageSize)
                ->get();
            
            $data = [];
            foreach ($list as $item) {
                $statusText = '';
                switch ($item->state) {
                    case 0:
                        $statusText = 'Chờ duyệt';
                        break;
                    case 1:
                        $statusText = 'Rút tiềnThành công';
                        break;
                    case -1:
                        $statusText = 'Rút tiềnThất bại';
                        break;
                }
                
                $fee = $item->fee ?? 0;
                $actualAmount = $item->actualamount ?? (($item->amount ?? 0) - $fee);
                
                
                $paytype = $item->paytype ?? 'bank';
                $channelName = $item->paytypename ?? $this->getWithdrawChannelName($paytype);
                
                
                $accountInfo = '';
                if ($paytype === 'bank') {
                    $accountInfo = $this->maskBankAccount($item->banknumber ?? '');
                } else {
                    $accountInfo = $item->banknumber ?? '';
                }
                
                $data[] = [
                    'id' => $item->id,
                    'orderNo' => $item->trano ?? '',
                    'amount' => number_format($item->amount ?? 0, 2, '.', ''),
                    'actualAmount' => number_format($actualAmount, 2, '.', ''),
                    'fee' => number_format($fee, 2, '.', ''),
                    'channel' => $paytype,
                    'channelName' => $channelName,
                    'bankName' => $item->bankname ?? '',
                    'bankAccount' => $accountInfo,
                    'accountName' => $item->accountname ?? '',
                    'status' => $item->state == 1 ? 'success' : ($item->state == 0 ? 'pending' : 'failed'),
                    'statusText' => $statusText,
                    'reason' => '',
                    'createdAt' => date('Y-m-d H:i:s', $item->oddtime ?? time()),
                    'auditedAt' => null,
                    'completedAt' => $item->state == 1 ? date('Y-m-d H:i:s', $item->oddtime) : null,
                    'remark' => $item->remark ?? '',
                    'estimatedTime' => '2小时内到账'
                ];
            }
            
            return json([
                'code' => 0,
                'message' => 'Lấy dữ liệu thành công',
                'data' => [
                    'total' => $total,
                    'page' => (int)$page,
                    'pageSize' => (int)$pageSize,
                    'summary' => [
                        'totalWithdraw' => number_format($summary['totalWithdraw'], 2, '.', ''),
                        'processingCount' => $summary['processingCount'],
                        'successCount' => $summary['successCount'],
                        'todayWithdraw' => number_format($summary['todayWithdraw'], 2, '.', '')
                    ],
                    'list' => $data
                ]
            ]);
            
        } catch (\Exception $e) {
            \support\Log::error('Lấy rút tiềnlịch sử thất bại: ' . $e->getMessage());
            return json([
                'code' => 500,
                'message' => 'Lấy dữ liệuThất bại：' . $e->getMessage(),
                'data' => null
            ]);
        }
    }
    
    
    public function rebateRecords(Request $request)
    {
        $userId = $request->userId ?? 0;
        
        if (!$userId) {
            return json([
                'code' => 401,
                'message' => '未Đăng nhậphoặcĐăng nhập已过期',
                'data' => null
            ]);
        }
        
        try {
            $page = $request->get('page', 1);
            $pageSize = $request->get('pageSize', 20);
            $startDate = $request->get('startDate', '');
            $endDate = $request->get('endDate', '');
            $status = $request->get('status', '');
            
            
            try {
                $query = Db::table('caipiao_rebate')
                    ->where('userid', $userId);
                
                
                if ($status) {
                    $query->where('status', $status);
                }
                
                
                if ($startDate) {
                    $query->where('date', '>=', $startDate);
                }
                if ($endDate) {
                    $query->where('date', '<=', $endDate);
                }
                
                
                $todayDate = date('Y-m-d');
                $monthStart = date('Y-m-01');
                $summary = [
                    'totalRebate' => Db::table('caipiao_rebate')
                        ->where('userid', $userId)
                        ->where('status', 'settled')
                        ->sum('rebate_amount'),
                    'todayRebate' => Db::table('caipiao_rebate')
                        ->where('userid', $userId)
                        ->where('date', $todayDate)
                        ->where('status', 'settled')
                        ->sum('rebate_amount'),
                    'pendingRebate' => Db::table('caipiao_rebate')
                        ->where('userid', $userId)
                        ->where('status', 'pending')
                        ->sum('rebate_amount'),
                    'monthRebate' => Db::table('caipiao_rebate')
                        ->where('userid', $userId)
                        ->where('date', '>=', $monthStart)
                        ->where('status', 'settled')
                        ->sum('rebate_amount')
                ];
                
                $total = $query->count();
                
                $list = $query->orderBy('date', 'desc')
                    ->offset(($page - 1) * $pageSize)
                    ->limit($pageSize)
                    ->get();
                
                $data = [];
                foreach ($list as $item) {
                    $data[] = [
                        'id' => $item->id,
                        'date' => $item->date,
                        'betAmount' => number_format($item->bet_amount ?? 0, 2, '.', ''),
                        'validBet' => number_format($item->valid_bet ?? 0, 2, '.', ''),
                        'rebateRate' => number_format($item->rebate_rate ?? 0, 2, '.', ''),
                        'rebateAmount' => number_format($item->rebate_amount ?? 0, 2, '.', ''),
                        'status' => $item->status,
                        'settledAt' => $item->settled_at ? date('Y-m-d H:i:s', $item->settled_at) : '',
                        'remark' => $item->remark ?? '每日Hoàn trả'
                    ];
                }
                
                return json([
                    'code' => 0,
                    'message' => 'Lấy dữ liệu thành công',
                    'data' => [
                        'total' => $total,
                        'page' => (int)$page,
                        'pageSize' => (int)$pageSize,
                        'summary' => [
                            'totalRebate' => number_format($summary['totalRebate'], 2, '.', ''),
                            'todayRebate' => number_format($summary['todayRebate'], 2, '.', ''),
                            'pendingRebate' => number_format($summary['pendingRebate'], 2, '.', ''),
                            'monthRebate' => number_format($summary['monthRebate'], 2, '.', '')
                        ],
                        'list' => $data
                    ]
                ]);
                
            } catch (\Exception $e) {
                
                return json([
                    'code' => 0,
                    'message' => 'Lấy dữ liệu thành công',
                    'data' => [
                        'total' => 0,
                        'page' => (int)$page,
                        'pageSize' => (int)$pageSize,
                        'summary' => [
                            'totalRebate' => '0.00',
                            'todayRebate' => '0.00',
                            'pendingRebate' => '0.00',
                            'monthRebate' => '0.00'
                        ],
                        'list' => []
                    ]
                ]);
            }
            
        } catch (\Exception $e) {
            \support\Log::error('Lấy反水lịch sử thất bại: ' . $e->getMessage());
            return json([
                'code' => 500,
                'message' => 'Lấy dữ liệuThất bại：' . $e->getMessage(),
                'data' => null
            ]);
        }
    }
    
    
    private function getChannelName($channel)
    {
        $channelMap = [
            
            'alipay' => 'AlipayNạp tiền',
            'zhifubao' => 'AlipayNạp tiền',
            'zfb' => 'AlipayNạp tiền',
            
            
            'wechat' => 'WeChatNạp tiền',
            'weixin' => 'WeChatNạp tiền',
            'wx' => 'WeChatNạp tiền',
            
            
            'bank' => 'Thẻ ngân hàngNạp tiền',
            'bankcard' => 'Thẻ ngân hàngNạp tiền',
            'yinhangka' => 'Thẻ ngân hàngNạp tiền',
            
            
            'usdt' => 'USDTNạp tiền',
            'usdt-trc20' => 'USDTNạp tiền(TRC20)',
            'usdt-erc20' => 'USDTNạp tiền(ERC20)',
            
            
            'online' => '在线Thanh toán',
            'offline' => '线下Chuyển khoản',
            'admin' => '管理员操作',
            'admin_add' => '管理员加款',
            'admin_sub' => '管理员减款'
        ];
        
        return $channelMap[$channel] ?? $channel . 'Nạp tiền';
    }
    
    
    private function getWithdrawChannelName($channel)
    {
        $channelMap = [
            
            'alipay' => 'AlipayRút tiền',
            'zhifubao' => 'AlipayRút tiền',
            'zfb' => 'AlipayRút tiền',
            
            
            'wechat' => 'WeChatRút tiền',
            'weixin' => 'WeChatRút tiền',
            'wx' => 'WeChatRút tiền',
            
            
            'bank' => 'Thẻ ngân hàngRút tiền',
            'bankcard' => 'Thẻ ngân hàngRút tiền',
            'yinhangka' => 'Thẻ ngân hàngRút tiền',
            
            
            'usdt' => 'USDTRút tiền',
            'usdt-trc20' => 'USDTRút tiền(TRC20)',
            'usdt-erc20' => 'USDTRút tiền(ERC20)',
            
            
            'online' => '在线Rút tiền',
            'offline' => '线下Rút tiền'
        ];
        
        return $channelMap[$channel] ?? $channel . 'Rút tiền';
    }
    
    
    private function maskBankAccount($account)
    {
        if (empty($account)) {
            return '';
        }
        
        $len = strlen($account);
        if ($len <= 8) {
            return $account;
        }
        
        return substr($account, 0, 4) . ' **** **** ' . substr($account, -4);
    }
    
    
    public function transactionRecords(Request $request)
    {
        $userId = $request->userId ?? 0;
        
        if (!$userId) {
            return json([
                'code' => 401,
                'message' => '未Đăng nhậphoặcĐăng nhập已过期',
                'data' => null
            ]);
        }
        
        try {
            $page = $request->get('page', 1);
            $pageSize = $request->get('pageSize', 10);
            $type = $request->get('type', '');
            $status = $request->get('status', '');
            $atime = $request->get('atime', '');
            $startDate = $request->get('startDate', '');
            $endDate = $request->get('endDate', '');
            
            $query = Db::table('caipiao_fuddetail')
                ->where('uid', $userId);
            
            if ($type) {
                $query->where('type', $type);
            }
            
            if ($status !== '') {
                if ($type === 'withdraw') {
                    $query->where(function($q) use ($status) {
                        if ($status == -1) {
                            $q->where('remark', 'like', '%拒绝%')->orWhere('remark', 'like', '%Thất bại%');
                        } elseif ($status == 1) {
                            $q->where('remark', 'like', '%Thành công%');
                        } elseif ($status == 2) {
                            $q->where('remark', 'like', '%解冻%');
                        } elseif ($status == 0) {
                            $q->where('remark', 'like', '%出款中%')->orWhere('remark', 'like', '%Đang xử lý%');
                        }
                    });
                } elseif ($type === 'recharge') {
                    if ($status == 1) {
                        $query->where('amount', '>', 0);
                    } elseif ($status == -1) {
                        $query->where('amount', '<=', 0);
                    }
                } elseif ($type === 'order') {
                    $query->whereExists(function($q) use ($userId, $status) {
                        $q->select(Db::raw(1))
                          ->from('caipiao_touzhu')
                          ->whereColumn('caipiao_touzhu.trano', 'caipiao_fuddetail.trano')
                          ->where('caipiao_touzhu.uid', $userId)
                          ->where('caipiao_touzhu.isdraw', $status);
                    });
                }
            }
            
            
            if ($atime) {
                switch ($atime) {
                    case '1': 
                        $startTime = strtotime(date('Y-m-d 00:00:00'));
                        $endTime = time();
                        break;
                    case '2': 
                        $time = time() - (1 * 24 * 60 * 60);
                        $day = date("Y-m-d", $time);
                        $startTime = strtotime($day . ' 00:00:00');
                        $endTime = strtotime($day . ' 23:59:59');
                        break;
                    case '3': 
                        $time = time() - (7 * 24 * 60 * 60);
                        $day = date("Y-m-d", $time);
                        $startTime = strtotime($day . ' 00:00:00');
                        $endTime = time();
                        break;
                    default:
                        $startTime = null;
                        $endTime = null;
                }
                
                if ($startTime && $endTime) {
                    $query->where('oddtime', '>=', $startTime)
                          ->where('oddtime', '<=', $endTime);
                }
            } elseif ($startDate || $endDate) {
                
                if ($startDate) {
                    $query->where('oddtime', '>=', strtotime($startDate . ' 00:00:00'));
                }
                if ($endDate) {
                    $query->where('oddtime', '<=', strtotime($endDate . ' 23:59:59'));
                }
            }
            
            $total = $query->count();
            
            $list = $query->orderBy('oddtime', 'desc')
                ->offset(($page - 1) * $pageSize)
                ->limit($pageSize)
                ->get();
            
            $data = [];
            foreach ($list as $item) {
                
                $typeName = !empty($item->typename) ? $item->typename : $this->getTransactionTypeName($item->type ?? '');
                
                
                $amount = $item->amount ?? 0;
                $isExpense = $this->isExpenseType($item->type ?? '');
                
                $data[] = [
                    'id' => $item->id,
                    'type' => $item->type ?? '',
                    'typeName' => $typeName,
                    'amount' => $isExpense ? -abs($amount) : abs($amount),
                    'amountDisplay' => ($isExpense ? '-' : '+') . number_format(abs($amount), 2, '.', ''),
                    'balance' => number_format($item->amountafter ?? 0, 2, '.', ''),
                    'balanceBefore' => number_format($item->amountbefor ?? 0, 2, '.', ''),
                    'remark' => $item->remark ?? '',
                    'createdAt' => date('Y-m-d H:i:s', $item->oddtime ?? time()),
                    'orderNo' => $item->trano ?? ''
                ];
            }
            
            return json([
                'code' => 0,
                'message' => 'Lấy dữ liệu thành công',
                'data' => [
                    'total' => $total,
                    'page' => (int)$page,
                    'pageSize' => (int)$pageSize,
                    'list' => $data
                ]
            ]);
            
        } catch (\Exception $e) {
            \support\Log::error('Lấy资金lịch sử thất bại: ' . $e->getMessage());
            return json([
                'code' => 500,
                'message' => 'Lấy dữ liệuThất bại：' . $e->getMessage(),
                'data' => null
            ]);
        }
    }
    
    
    private function getTransactionTypeName($type)
    {
        $typeMap = [
            
            'order' => '代购',
            'cancel' => '撤单',
            'reward' => '返奖',
            'rollback' => '后台撤单',
            
            
            'yeb_dq' => 'Số dư宝定期',
            'yeb_lixi' => 'Số dư宝利息',
            'yeb_hq' => 'Số dư宝活期',
            
            
            'fanshui' => '每日加奖',
            'jinjishenhe' => 'Thăng cấp奖励',
            'yongjinshenhe' => 'Đại lý返点',
            'xima' => '洗码',
            'point' => '积分',
            'Hoàn trả' => 'Hoàn trả',
            
            
            'activity_bindcard' => 'Liên kết银行赠送Hoạt động',
            'activity_cz' => 'Nạp tiềnHoạt động',
            'activity_czzs' => 'Nạp tiền赠送Hoạt động',
            'activity_rxf' => '日消费赠送Hoạt động',
            'activity_rks' => '日亏损赠送Hoạt động',
            'activity_yxf' => '月消费赠送Hoạt động',
            'activity_yks' => '月亏损赠送Hoạt động',
            'Hoạt động奖励' => 'Hoạt động奖励',
            '签到奖励' => '签到奖励',
            
            
            'withdraw' => 'Rút tiền',
            'withdraw_return' => 'Rút tiền退回',
            'withdraw_reject' => 'Rút tiền退回',
            'withdraw_cancel' => 'Rút tiềnHủy',
            'adminadd' => '管理员加',
            'adminjian' => '管理员减',
            'recharge' => 'Nạp tiền',
            'transfer_in' => 'Chuyển khoản入款',
            'transfer_out' => 'Chuyển khoản出款',
            'yuebao_claim' => '利息宝收益领取',
            
            
            'Đại lýHoa hồng' => 'Đại lýHoa hồng',
            'Thăng cấp奖励' => 'Thăng cấp奖励'
        ];
        
        return $typeMap[$type] ?? $type;
    }
    
    
    private function isExpenseType($type)
    {
        $expenseTypes = [
            'order',        
            'xima',         
            'withdraw',     
            'yeb_dq',       
            'yeb_hq',       
            'adminjian',    
            'transfer_out'  
        ];
        
        return in_array($type, $expenseTypes);
    }
    
    
    public function transactionTypes(Request $request)
    {
        return json([
            'code' => 0,
            'message' => 'Lấy dữ liệu thành công',
            'data' => [
                ['value' => '', 'label' => '全部类型'],
                ['value' => 'order', 'label' => '代购'],
                ['value' => 'cancel', 'label' => '撤单'],
                ['value' => 'reward', 'label' => '返奖'],
                ['value' => 'fanshui', 'label' => '每日加奖'],
                ['value' => 'jinjishenhe', 'label' => 'Thăng cấp奖励'],
                ['value' => 'yongjinshenhe', 'label' => 'Đại lý返点'],
                ['value' => 'Đại lýHoa hồng', 'label' => 'Đại lýHoa hồng'],
                ['value' => 'xima', 'label' => '洗码'],
                ['value' => 'Hoàn trả', 'label' => 'Hoàn trả'],
                ['value' => 'yeb_lixi', 'label' => 'Số dư宝利息'],
                ['value' => 'yeb_dq', 'label' => 'Số dư宝定期'],
                ['value' => 'yeb_hq', 'label' => 'Số dư宝活期'],
                ['value' => 'yuebao_claim', 'label' => '利息宝收益'],
                ['value' => 'withdraw', 'label' => 'Rút tiền'],
                ['value' => 'withdraw_reject', 'label' => 'Rút tiền退回'],
                ['value' => 'withdraw_cancel', 'label' => 'Rút tiềnHủy'],
                ['value' => 'recharge', 'label' => 'Nạp tiền'],
                ['value' => 'adminadd', 'label' => '管理员加'],
                ['value' => 'adminjian', 'label' => '管理员减'],
                ['value' => 'rollback', 'label' => '后台撤单'],
                ['value' => 'point', 'label' => '积分'],
                ['value' => 'activity_cz', 'label' => 'Nạp tiềnHoạt động'],
                ['value' => 'activity_czzs', 'label' => 'Nạp tiền赠送'],
                ['value' => 'activity_bindcard', 'label' => '绑卡赠送'],
                ['value' => 'Hoạt động奖励', 'label' => 'Hoạt động奖励'],
                ['value' => '签到奖励', 'label' => '签到奖励']
            ]
        ]);
    }
    
    public function recordFilterOptions(Request $request)
    {
        try {
            $platforms = Db::table('caipiao_game_platform')
                ->where('status', 'online')
                ->orderBy('sort', 'asc')
                ->get();
            
            $platformsByType = [
                '' => [['text' => '全部平台', 'value' => '']],
                'lottery' => [['text' => '全部平台', 'value' => ''], ['text' => '博悦Xổ số', 'value' => 'boyue']]
            ];
            
            foreach ($platforms as $p) {
                $type = $p->type ?? 'slot';
                if (!isset($platformsByType[$type])) {
                    $platformsByType[$type] = [['text' => '全部平台', 'value' => '']];
                }
                $platformsByType[$type][] = ['text' => $p->name, 'value' => $p->code];
            }
            
            $categories = Db::table('caipiao_game_category')
                ->where('status', 1)
                ->orderBy('sort', 'asc')
                ->get();
            
            $categoryOptions = [['text' => '全部类型', 'value' => '']];
            $defaultCategories = [
                ['code' => 'lottery', 'name' => 'Xổ số'],
                ['code' => 'live', 'name' => 'Live Casino视讯'],
                ['code' => 'slot', 'name' => 'Điện tửTrò chơi'],
                ['code' => 'sport', 'name' => 'Thể thao'],
                ['code' => 'chess', 'name' => 'Bài']
            ];
            
            if ($categories && count($categories) > 0) {
                foreach ($categories as $cat) {
                    if ($cat->code !== 'hot') {
                        $categoryOptions[] = ['text' => $cat->name, 'value' => $cat->code];
                    }
                }
            } else {
                foreach ($defaultCategories as $cat) {
                    $categoryOptions[] = ['text' => $cat['name'], 'value' => $cat['code']];
                }
            }
            
            return json([
                'code' => 0,
                'message' => 'Lấy dữ liệu thành công',
                'data' => [
                    'betStatusOptions' => [
                        ['text' => '全部状态', 'value' => ''],
                        ['text' => '未结算', 'value' => 'pending'],
                        ['text' => '已结算', 'value' => 'settled'],
                        ['text' => '已撤单', 'value' => 'cancelled'],
                        ['text' => '待结算', 'value' => 'waiting']
                    ],
                    'betCategoryOptions' => $categoryOptions,
                    'platformConfigMap' => $platformsByType,
                    'accountDetailOptionsMap' => [
                        '' => [['text' => '小类明细', 'value' => '']],
                        'withdraw' => [
                            ['text' => '小类明细', 'value' => ''],
                            ['text' => 'Rút tiền拒绝', 'value' => -1],
                            ['text' => 'Rút tiềnThành công', 'value' => 1],
                            ['text' => 'Rút tiền解冻', 'value' => 2],
                            ['text' => '出款中', 'value' => 0]
                        ],
                        'recharge' => [
                            ['text' => '小类明细', 'value' => ''],
                            ['text' => 'Chờ duyệt', 'value' => 0],
                            ['text' => 'Nạp tiềnThành công', 'value' => 1],
                            ['text' => 'Nạp tiềnThất bại', 'value' => -1]
                        ],
                        'order' => [
                            ['text' => '全部状态', 'value' => ''],
                            ['text' => '待Mở thưởng', 'value' => 0],
                            ['text' => '已Trúng thưởng', 'value' => 1],
                            ['text' => '未Trúng thưởng', 'value' => 2]
                        ]
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return json(['code' => 500, 'message' => 'Lấy dữ liệu thất bại']);
        }
    }
    
    
    public function receiveStats(Request $request)
    {
        $userId = $request->userId ?? 0;
        
        if (!$userId) {
            return json([
                'code' => 401,
                'message' => '未Đăng nhậphoặcĐăng nhập已过期',
                'data' => null
            ]);
        }
        
        try {
            
            
            $receiveTypes = [
                'fanshui',          
                'jinjishenhe',      
                'yongjinshenhe',    
                'yuebao_claim',     
                'activity_cz',      
                'activity_czzs',    
                'activity_bindcard',
                'activity_rxf',     
                'activity_rks',     
                'activity_yxf',     
                'activity_yks'      
            ];
            
            
            $fudTotal = Db::table('caipiao_fuddetail')
                ->where('uid', $userId)
                ->whereIn('type', $receiveTypes)
                ->sum('amount');
            
            
            $yuebaoInterestTotal = 0;
            try {
                $stats = Db::table('yzz_yuebao_stats')
                    ->where('uid', $userId)
                    ->first();
                if ($stats) {
                    $yuebaoInterestTotal = (float)($stats->total_interest ?? 0);
                }
            } catch (\Exception $e) {
                
            }
            
            
            $fudYuebaoTotal = Db::table('caipiao_fuddetail')
                ->where('uid', $userId)
                ->where('type', 'yuebao_claim')
                ->sum('amount');
            
            
            $otherTotal = $fudTotal - $fudYuebaoTotal;
            $totalReceive = abs($otherTotal) + abs($yuebaoInterestTotal);
            
            return json([
                'code' => 0,
                'message' => 'Lấy dữ liệu thành công',
                'data' => [
                    'totalReceive' => number_format($totalReceive, 2, '.', '')
                ]
            ]);
            
        } catch (\Exception $e) {
            \support\Log::error('Lấy领取统计Thất bại: ' . $e->getMessage());
            return json([
                'code' => 500,
                'message' => 'Lấy dữ liệuThất bại',
                'data' => null
            ]);
        }
    }
    
    
    public function profitLossStats(Request $request)
    {
        $userId = $request->userId ?? 0;
        
        if (!$userId) {
            return json(['code' => 401, 'msg' => 'unauthorized', 'data' => null]);
        }
        
        try {
            $timeRange = $request->get('timeRange', 'today');
            $walletType = $request->get('walletType', 'main');
            
            
            list($startTime, $endTime) = $this->getTimeRange($timeRange);
            
            
            $user = Db::table('caipiao_member')->where('id', $userId)->first();
            $balance = $user->balance ?? 0;
            
            
            $recharge = Db::table('caipiao_recharge')
                ->where('uid', $userId)
                ->where('state', 1)
                ->where('oddtime', '>=', $startTime)
                ->where('oddtime', '<=', $endTime)
                ->sum('amount') ?? 0;
            
            
            $withdraw = Db::table('caipiao_withdraw')
                ->where('uid', $userId)
                ->where('state', 1)
                ->where('oddtime', '>=', $startTime)
                ->where('oddtime', '<=', $endTime)
                ->sum('amount') ?? 0;
            
            
            $bet = Db::table('caipiao_touzhu')
                ->where('uid', $userId)
                ->where('oddtime', '>=', $startTime)
                ->where('oddtime', '<=', $endTime)
                ->sum('amount') ?? 0;
            
            
            $payout = Db::table('caipiao_touzhu')
                ->where('uid', $userId)
                ->where('isdraw', 1)
                ->where('oddtime', '>=', $startTime)
                ->where('oddtime', '<=', $endTime)
                ->sum('okamount') ?? 0;
            
            
            $rebate = Db::table('caipiao_fuddetail')
                ->where('uid', $userId)
                ->whereIn('type', ['fanshui', 'yongjinshenhe'])
                ->where('oddtime', '>=', $startTime)
                ->where('oddtime', '<=', $endTime)
                ->sum('amount') ?? 0;
            
            
            $bonus = Db::table('caipiao_fuddetail')
                ->where('uid', $userId)
                ->where('type', 'like', 'activity_%')
                ->where('oddtime', '>=', $startTime)
                ->where('oddtime', '<=', $endTime)
                ->sum('amount') ?? 0;
            
            
            $transferIn = Db::table('caipiao_fuddetail')
                ->where('uid', $userId)
                ->where('type', 'transfer_in')
                ->where('oddtime', '>=', $startTime)
                ->where('oddtime', '<=', $endTime)
                ->sum('amount') ?? 0;
            
            $transferOut = Db::table('caipiao_fuddetail')
                ->where('uid', $userId)
                ->where('type', 'transfer_out')
                ->where('oddtime', '>=', $startTime)
                ->where('oddtime', '<=', $endTime)
                ->sum('amount') ?? 0;
            
            
            $profit = $payout + $rebate + $bonus - $bet;
            
            return json([
                'code' => 0,
                'msg' => 'success',
                'data' => [
                    'balance' => number_format($balance, 2, '.', ''),
                    'profit' => number_format($profit, 2, '.', ''),
                    'recharge' => number_format($recharge, 2, '.', ''),
                    'withdraw' => number_format($withdraw, 2, '.', ''),
                    'bet' => number_format($bet, 2, '.', ''),
                    'payout' => number_format($payout, 2, '.', ''),
                    'rebate' => number_format($rebate, 2, '.', ''),
                    'bonus' => number_format($bonus, 2, '.', ''),
                    'transferIn' => number_format($transferIn, 2, '.', ''),
                    'transferOut' => number_format($transferOut, 2, '.', '')
                ]
            ]);
            
        } catch (\Exception $e) {
            return json([
                'code' => 500,
                'msg' => 'Tra cứuThất bại：' . $e->getMessage(),
                'data' => null
            ]);
        }
    }
    
    
    public function transactionList(Request $request)
    {
        $userId = $request->userId ?? 0;
        
        if (!$userId) {
            return json(['code' => 401, 'msg' => 'unauthorized', 'data' => null]);
        }
        
        try {
            $timeRange = $request->get('timeRange', 'today');
            $type = $request->get('type', '');
            $page = $request->get('page', 1);
            $pageSize = $request->get('pageSize', 20);
            
            
            list($startTime, $endTime) = $this->getTimeRange($timeRange);
            
            $query = Db::table('caipiao_fuddetail')
                ->where('uid', $userId)
                ->where('oddtime', '>=', $startTime)
                ->where('oddtime', '<=', $endTime);
            
            
            if ($type) {
                switch ($type) {
                    case 'bet':
                        $query->where('type', 'order');
                        break;
                    case 'payout':
                        $query->where('type', 'reward');
                        break;
                    case 'recharge':
                        $query->where('type', 'recharge');
                        break;
                    case 'withdraw':
                        $query->where('type', 'withdraw');
                        break;
                    case 'rebate':
                        $query->whereIn('type', ['fanshui', 'yongjinshenhe', 'xima']);
                        break;
                    case 'bonus':
                        $query->where('type', 'like', 'activity_%');
                        break;
                }
            }
            
            $total = $query->count();
            $records = $query->orderBy('oddtime', 'desc')
                ->offset(($page - 1) * $pageSize)
                ->limit($pageSize)
                ->get();
            
            $list = [];
            foreach ($records as $record) {
                $amount = $record->amount ?? 0;
                $isExpense = $this->isExpenseType($record->type ?? '');
                
                
                $title = $this->getTransactionTypeName($record->type ?? '');
                $subTitle = $record->remark ?: ($record->trano ? "单号: {$record->trano}" : '');
                
                $list[] = [
                    'id' => $record->trano ?? $record->id,
                    'title' => $title,
                    'subTitle' => $subTitle,
                    'time' => date('H:i:s', $record->oddtime ?? time()),
                    'amount' => $isExpense ? -abs($amount) : abs($amount),
                    'currency' => 'CNY'
                ];
            }
            
            return json([
                'code' => 0,
                'msg' => 'success',
                'data' => [
                    'total' => $total,
                    'list' => $list
                ]
            ]);
            
        } catch (\Exception $e) {
            return json([
                'code' => 500,
                'msg' => 'Tra cứuThất bại：' . $e->getMessage(),
                'data' => null
            ]);
        }
    }
    
    
    private function getTimeRange($timeRange)
    {
        $now = time();
        $startTime = 0;
        $endTime = $now;
        
        switch ($timeRange) {
            case 'today':
                $startTime = strtotime(date('Y-m-d 00:00:00'));
                break;
            case 'yesterday':
                $startTime = strtotime(date('Y-m-d 00:00:00', strtotime('-1 day')));
                $endTime = strtotime(date('Y-m-d 23:59:59', strtotime('-1 day')));
                break;
            case 'week':
                $startTime = strtotime(date('Y-m-d 00:00:00', strtotime('-7 days')));
                break;
            case 'month':
                $startTime = strtotime(date('Y-m-01 00:00:00'));
                break;
            default:
                $startTime = strtotime(date('Y-m-d 00:00:00'));
        }
        
        return [$startTime, $endTime];
    }
}
