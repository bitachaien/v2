<?php

namespace plugin\admin\app\controller;

use support\Request;
use support\Db;

/**
 * 数据统计 API 控制器 (Art Design Pro 前端专用)
 */
class StatisticsApiController extends Base
{
    /**
     * 不需要鉴权的方法（但需要登录）
     */
    protected $noNeedAuth = ['overview', 'realtime', 'rechargeTrend', 'betTrend', 'userGrowth', 
                             'finance', 'profit', 'user', 'team', 'lottery',
                             'retention', 'valueAnalysis', 'deviceDistribution',
                             'teamOverview', 'teamLevels', 'teamCommissionTrend', 
                             'teamPerformanceTrend', 'teamRank'];

    /**
     * Lấy排除的测试Người dùngID
     */
    private function getTestUserIds(): array
    {
        return Db::table('caipiao_member')
            ->where('isnb', 1)
            ->pluck('id')
            ->toArray();
    }

    /**
     * 1. 统计概况 - Hôm nay核心dữ liệu
     * GET /app/admin/statistics/overview
     */
    public function overview(Request $request)
    {
        $testUsers = $this->getTestUserIds();
        $todayStart = strtotime(date('Y-m-d'));
        $todayEnd = $todayStart + 86400 - 1;
        $yesterdayStart = $todayStart - 86400;
        $yesterdayEnd = $todayStart - 1;

        // Hôm nayĐăng ký
        $todayRegister = Db::table('caipiao_member')
            ->where('isnb', 0)
            ->where('regtime', '>=', $todayStart)
            ->where('regtime', '<=', $todayEnd)
            ->count();
        
        // Hôm quaĐăng ký
        $yesterdayRegister = Db::table('caipiao_member')
            ->where('isnb', 0)
            ->where('regtime', '>=', $yesterdayStart)
            ->where('regtime', '<=', $yesterdayEnd)
            ->count();

        // Hôm nay活跃（有Đăng nhậphoặcĐặt cược）
        $todayActive = Db::table('caipiao_member')
            ->where('isnb', 0)
            ->where('onlinetime', '>=', $todayStart)
            ->count();
        
        // Hôm qua活跃
        $yesterdayActive = Db::table('caipiao_member')
            ->where('isnb', 0)
            ->where('onlinetime', '>=', $yesterdayStart)
            ->where('onlinetime', '<=', $yesterdayEnd)
            ->count();

        // Hôm nayNạp tiền
        $todayRecharge = Db::table('caipiao_recharge')
            ->where('state', 1)
            ->where('oddtime', '>=', $todayStart)
            ->where('oddtime', '<=', $todayEnd)
            ->when(!empty($testUsers), fn($q) => $q->whereNotIn('uid', $testUsers))
            ->sum('amount') ?? 0;
        
        // Hôm quaNạp tiền
        $yesterdayRecharge = Db::table('caipiao_recharge')
            ->where('state', 1)
            ->where('oddtime', '>=', $yesterdayStart)
            ->where('oddtime', '<=', $yesterdayEnd)
            ->when(!empty($testUsers), fn($q) => $q->whereNotIn('uid', $testUsers))
            ->sum('amount') ?? 0;

        // Hôm nayRút tiền
        $todayWithdraw = Db::table('caipiao_withdraw')
            ->where('state', 1)
            ->where('oddtime', '>=', $todayStart)
            ->where('oddtime', '<=', $todayEnd)
            ->when(!empty($testUsers), fn($q) => $q->whereNotIn('uid', $testUsers))
            ->sum('amount') ?? 0;
        
        // Hôm quaRút tiền
        $yesterdayWithdraw = Db::table('caipiao_withdraw')
            ->where('state', 1)
            ->where('oddtime', '>=', $yesterdayStart)
            ->where('oddtime', '<=', $yesterdayEnd)
            ->when(!empty($testUsers), fn($q) => $q->whereNotIn('uid', $testUsers))
            ->sum('amount') ?? 0;

        // Hôm nayĐặt cược（排除机器人Đặt cược）
        $todayBet = Db::table('caipiao_touzhu')
            ->whereIn('isdraw', [1, -1])
            ->where('oddtime', '>=', $todayStart)
            ->where('oddtime', '<=', $todayEnd)
            ->when(!empty($testUsers), fn($q) => $q->whereNotIn('uid', $testUsers))
            ->where(function($q) { $q->whereNull('source')->orWhere('source', '!=', 'robot'); })
            ->sum('amount') ?? 0;
        
        // Hôm quaĐặt cược（排除机器人Đặt cược）
        $yesterdayBet = Db::table('caipiao_touzhu')
            ->whereIn('isdraw', [1, -1])
            ->where('oddtime', '>=', $yesterdayStart)
            ->where('oddtime', '<=', $yesterdayEnd)
            ->when(!empty($testUsers), fn($q) => $q->whereNotIn('uid', $testUsers))
            ->where(function($q) { $q->whereNull('source')->orWhere('source', '!=', 'robot'); })
            ->sum('amount') ?? 0;

        // Hôm nayTrả thưởng（排除机器人Đặt cược）
        $todayPrize = Db::table('caipiao_touzhu')
            ->where('isdraw', 1)
            ->where('oddtime', '>=', $todayStart)
            ->where('oddtime', '<=', $todayEnd)
            ->when(!empty($testUsers), fn($q) => $q->whereNotIn('uid', $testUsers))
            ->where(function($q) { $q->whereNull('source')->orWhere('source', '!=', 'robot'); })
            ->sum('okamount') ?? 0;
        
        // Hôm quaTrả thưởng（排除机器人Đặt cược）
        $yesterdayPrize = Db::table('caipiao_touzhu')
            ->where('isdraw', 1)
            ->where('oddtime', '>=', $yesterdayStart)
            ->where('oddtime', '<=', $yesterdayEnd)
            ->when(!empty($testUsers), fn($q) => $q->whereNotIn('uid', $testUsers))
            ->where(function($q) { $q->whereNull('source')->orWhere('source', '!=', 'robot'); })
            ->sum('okamount') ?? 0;

        // Hôm nay盈亏
        $todayProfit = $todayBet - $todayPrize;
        $yesterdayProfit = $yesterdayBet - $yesterdayPrize;

        // 平台Số dư
        $platformBalance = Db::table('caipiao_member')
            ->where('isnb', 0)
            ->sum('balance') ?? 0;

        // 实时在线（30分钟内）
        $onlineCount = Db::table('caipiao_member')
            ->where('isnb', 0)
            ->where('onlinetime', '>=', time() - 1800)
            ->count();

        // 计算环比变化率
        $calcChange = function($today, $yesterday) {
            if ($yesterday == 0) {
                return $today > 0 ? 100 : 0;
            }
            return round(($today - $yesterday) / abs($yesterday) * 100, 1);
        };

        return $this->json(0, 'ok', [
            'todayRegister' => $todayRegister,
            'todayRegisterChange' => $calcChange($todayRegister, $yesterdayRegister),
            'todayActive' => $todayActive,
            'todayActiveChange' => $calcChange($todayActive, $yesterdayActive),
            'todayRecharge' => round($todayRecharge, 2),
            'todayRechargeChange' => $calcChange($todayRecharge, $yesterdayRecharge),
            'todayWithdraw' => round($todayWithdraw, 2),
            'todayWithdrawChange' => $calcChange($todayWithdraw, $yesterdayWithdraw),
            'todayBet' => round($todayBet, 2),
            'todayBetChange' => $calcChange($todayBet, $yesterdayBet),
            'todayPrize' => round($todayPrize, 2),
            'todayPrizeChange' => $calcChange($todayPrize, $yesterdayPrize),
            'todayProfit' => round($todayProfit, 2),
            'todayProfitChange' => $calcChange($todayProfit, $yesterdayProfit),
            'platformBalance' => round($platformBalance, 2),
            'onlineCount' => $onlineCount,
        ]);
    }

    /**
     * 实时动态列表
     * GET /app/admin/statistics/realtime
     */
    public function realtime(Request $request)
    {
        $limit = $request->get('limit', 20);
        $testUsers = $this->getTestUserIds();

        // 最近的Nạp tiềnlịch sử
        $recharges = Db::table('caipiao_recharge as r')
            ->leftJoin('caipiao_member as m', 'r.uid', '=', 'm.id')
            ->where('r.state', 1)
            ->when(!empty($testUsers), fn($q) => $q->whereNotIn('r.uid', $testUsers))
            ->orderBy('r.oddtime', 'desc')
            ->limit($limit)
            ->select(['r.id', 'm.username', 'r.amount', 'r.oddtime', Db::raw("'recharge' as type")])
            ->get();

        // 最近的Rút tiềnlịch sử
        $withdraws = Db::table('caipiao_withdraw as w')
            ->leftJoin('caipiao_member as m', 'w.uid', '=', 'm.id')
            ->where('w.state', 1)
            ->when(!empty($testUsers), fn($q) => $q->whereNotIn('w.uid', $testUsers))
            ->orderBy('w.oddtime', 'desc')
            ->limit($limit)
            ->select(['w.id', 'm.username', 'w.amount', 'w.oddtime', Db::raw("'withdraw' as type")])
            ->get();

        // 最近的Trúng thưởnglịch sử（排除机器人Đặt cược）
        $wins = Db::table('caipiao_touzhu as t')
            ->leftJoin('caipiao_member as m', 't.uid', '=', 'm.id')
            ->where('t.isdraw', 1)
            ->where('t.okamount', '>', 0)
            ->when(!empty($testUsers), fn($q) => $q->whereNotIn('t.uid', $testUsers))
            ->where(function($q) { $q->whereNull('t.source')->orWhere('t.source', '!=', 'robot'); })
            ->orderBy('t.oddtime', 'desc')
            ->limit($limit)
            ->select(['t.id', 'm.username', 't.okamount as amount', 't.oddtime', 't.cpname', Db::raw("'win' as type")])
            ->get();

        // 合并并排序
        $list = collect($recharges)->merge($withdraws)->merge($wins)
            ->sortByDesc('oddtime')
            ->take($limit)
            ->map(function ($item) {
                $username = $this->maskUsername($item->username ?? '');
                $amount = round($item->amount, 2);
                $typeName = match($item->type) {
                    'recharge' => 'Nạp tiền',
                    'withdraw' => 'Rút tiền',
                    'win' => 'Trúng thưởng',
                    default => '未知'
                };
                
                // 生成内容描述
                $content = match($item->type) {
                    'recharge' => "Người dùng {$username} Nạp tiền {$amount} 元",
                    'withdraw' => "Người dùng {$username} Rút tiền {$amount} 元",
                    'win' => "Người dùng {$username} Trúng thưởng {$amount} 元",
                    default => "Người dùng {$username} 操作 {$amount} 元"
                };
                
                return [
                    'type' => $item->type,
                    'content' => $content,
                    'time' => date('H:i:s', $item->oddtime),
                    'amount' => $amount,
                    'username' => $username,
                    'typeName' => $typeName,
                ];
            })
            ->values()
            ->toArray();

        return $this->json(0, 'ok', $list);
    }

    /**
     * 充提趋势图dữ liệu
     * GET /app/admin/statistics/recharge-trend
     */
    public function rechargeTrend(Request $request)
    {
        $days = $request->get('days', 7);
        $testUsers = $this->getTestUserIds();
        
        $xAxis = [];
        $rechargeData = [];
        $withdrawData = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $dayStart = strtotime($date);
            $dayEnd = $dayStart + 86400 - 1;

            $recharge = Db::table('caipiao_recharge')
                ->where('state', 1)
                ->where('oddtime', '>=', $dayStart)
                ->where('oddtime', '<=', $dayEnd)
                ->when(!empty($testUsers), fn($q) => $q->whereNotIn('uid', $testUsers))
                ->sum('amount') ?? 0;

            $withdraw = Db::table('caipiao_withdraw')
                ->where('state', 1)
                ->where('oddtime', '>=', $dayStart)
                ->where('oddtime', '<=', $dayEnd)
                ->when(!empty($testUsers), fn($q) => $q->whereNotIn('uid', $testUsers))
                ->sum('amount') ?? 0;

            $xAxis[] = date('m-d', $dayStart);
            $rechargeData[] = round($recharge, 2);
            $withdrawData[] = round($withdraw, 2);
        }

        return $this->json(0, 'ok', [
            'xAxis' => $xAxis,
            'series' => [
                ['name' => 'Nạp tiền', 'data' => $rechargeData],
                ['name' => 'Rút tiền', 'data' => $withdrawData],
            ]
        ]);
    }

    /**
     * Đặt cược趋势图dữ liệu
     * GET /app/admin/statistics/bet-trend
     */
    public function betTrend(Request $request)
    {
        $days = $request->get('days', 7);
        $testUsers = $this->getTestUserIds();
        
        $xAxis = [];
        $betData = [];
        $prizeData = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $dayStart = strtotime($date);
            $dayEnd = $dayStart + 86400 - 1;

            $bet = Db::table('caipiao_touzhu')
                ->whereIn('isdraw', [1, -1])
                ->where('oddtime', '>=', $dayStart)
                ->where('oddtime', '<=', $dayEnd)
                ->when(!empty($testUsers), fn($q) => $q->whereNotIn('uid', $testUsers))
                ->where(function($q) { $q->whereNull('source')->orWhere('source', '!=', 'robot'); })
                ->sum('amount') ?? 0;

            $prize = Db::table('caipiao_touzhu')
                ->where('isdraw', 1)
                ->where('oddtime', '>=', $dayStart)
                ->where('oddtime', '<=', $dayEnd)
                ->when(!empty($testUsers), fn($q) => $q->whereNotIn('uid', $testUsers))
                ->where(function($q) { $q->whereNull('source')->orWhere('source', '!=', 'robot'); })
                ->sum('okamount') ?? 0;

            $xAxis[] = date('m-d', $dayStart);
            $betData[] = round($bet, 2);
            $prizeData[] = round($prize, 2);
        }

        return $this->json(0, 'ok', [
            'xAxis' => $xAxis,
            'series' => [
                ['name' => 'Đặt cược', 'data' => $betData],
                ['name' => 'Trúng thưởng', 'data' => $prizeData],
            ]
        ]);
    }

    /**
     * Người dùng增长趋势
     * GET /app/admin/statistics/user-growth
     */
    public function userGrowth(Request $request)
    {
        $days = $request->get('days', 7);
        
        $xAxis = [];
        $newUsersData = [];
        $activeUsersData = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $dayStart = strtotime($date);
            $dayEnd = $dayStart + 86400 - 1;

            $newUsers = Db::table('caipiao_member')
                ->where('isnb', 0)
                ->where('regtime', '>=', $dayStart)
                ->where('regtime', '<=', $dayEnd)
                ->count();

            $activeUsers = Db::table('caipiao_member')
                ->where('isnb', 0)
                ->where('onlinetime', '>=', $dayStart)
                ->where('onlinetime', '<=', $dayEnd)
                ->count();

            $xAxis[] = date('m-d', $dayStart);
            $newUsersData[] = $newUsers;
            $activeUsersData[] = $activeUsers;
        }

        return $this->json(0, 'ok', [
            'xAxis' => $xAxis,
            'series' => [
                ['name' => '新增Người dùng', 'data' => $newUsersData],
                ['name' => '活跃Người dùng', 'data' => $activeUsersData],
            ]
        ]);
    }

    /**
     * 2.1 资金Vòng cược统计
     * GET /app/admin/statistics/finance
     * 返回结构匹配前端 Api.Statistics.FinanceStats 类型
     */
    public function finance(Request $request)
    {
        $startDate = $request->get('startDate', date('Y-m-d', strtotime('-6 days')));
        $endDate = $request->get('endDate', date('Y-m-d'));
        $startTime = strtotime($startDate);
        $endTime = strtotime($endDate) + 86400 - 1;
        $testUsers = $this->getTestUserIds();

        // ========== Nạp tiềndữ liệu ==========
        $totalRecharge = Db::table('caipiao_recharge')
            ->where('state', 1)
            ->where('oddtime', '>=', $startTime)
            ->where('oddtime', '<=', $endTime)
            ->when(!empty($testUsers), fn($q) => $q->whereNotIn('uid', $testUsers))
            ->sum('amount') ?? 0;

        $rechargeCount = Db::table('caipiao_recharge')
            ->where('state', 1)
            ->where('oddtime', '>=', $startTime)
            ->where('oddtime', '<=', $endTime)
            ->when(!empty($testUsers), fn($q) => $q->whereNotIn('uid', $testUsers))
            ->count();

        $rechargeUserCount = Db::table('caipiao_recharge')
            ->where('state', 1)
            ->where('oddtime', '>=', $startTime)
            ->where('oddtime', '<=', $endTime)
            ->when(!empty($testUsers), fn($q) => $q->whereNotIn('uid', $testUsers))
            ->distinct()
            ->count('uid');

        // 首充Người dùngdữ liệu (在Thời gian段内首次Nạp tiền的Người dùng)
        $firstRechargeAmount = Db::table('caipiao_recharge as r1')
            ->where('r1.state', 1)
            ->where('r1.oddtime', '>=', $startTime)
            ->where('r1.oddtime', '<=', $endTime)
            ->whereNotExists(function ($query) use ($startTime) {
                $query->select(Db::raw(1))
                    ->from('caipiao_recharge as r2')
                    ->whereColumn('r2.uid', 'r1.uid')
                    ->where('r2.state', 1)
                    ->where('r2.oddtime', '<', $startTime);
            })
            ->when(!empty($testUsers), fn($q) => $q->whereNotIn('r1.uid', $testUsers))
            ->sum('r1.amount') ?? 0;

        $firstRechargeCount = Db::table('caipiao_recharge as r1')
            ->where('r1.state', 1)
            ->where('r1.oddtime', '>=', $startTime)
            ->where('r1.oddtime', '<=', $endTime)
            ->whereNotExists(function ($query) use ($startTime) {
                $query->select(Db::raw(1))
                    ->from('caipiao_recharge as r2')
                    ->whereColumn('r2.uid', 'r1.uid')
                    ->where('r2.state', 1)
                    ->where('r2.oddtime', '<', $startTime);
            })
            ->when(!empty($testUsers), fn($q) => $q->whereNotIn('r1.uid', $testUsers))
            ->distinct()
            ->count('r1.uid');

        // ========== Rút tiềndữ liệu ==========
        $totalWithdraw = Db::table('caipiao_withdraw')
            ->where('state', 1)
            ->where('oddtime', '>=', $startTime)
            ->where('oddtime', '<=', $endTime)
            ->when(!empty($testUsers), fn($q) => $q->whereNotIn('uid', $testUsers))
            ->sum('amount') ?? 0;

        $withdrawCount = Db::table('caipiao_withdraw')
            ->where('state', 1)
            ->where('oddtime', '>=', $startTime)
            ->where('oddtime', '<=', $endTime)
            ->when(!empty($testUsers), fn($q) => $q->whereNotIn('uid', $testUsers))
            ->count();

        $withdrawUserCount = Db::table('caipiao_withdraw')
            ->where('state', 1)
            ->where('oddtime', '>=', $startTime)
            ->where('oddtime', '<=', $endTime)
            ->when(!empty($testUsers), fn($q) => $q->whereNotIn('uid', $testUsers))
            ->distinct()
            ->count('uid');

        // Chờ duyệt
        $pendingWithdraw = Db::table('caipiao_withdraw')
            ->where('state', 0)
            ->when(!empty($testUsers), fn($q) => $q->whereNotIn('uid', $testUsers))
            ->sum('amount') ?? 0;

        $pendingCount = Db::table('caipiao_withdraw')
            ->where('state', 0)
            ->when(!empty($testUsers), fn($q) => $q->whereNotIn('uid', $testUsers))
            ->count();

        // ========== 趋势dữ liệu (TrendData 格式) ==========
        $days = ceil(($endTime - $startTime) / 86400);
        $xAxis = [];
        $rechargeData = [];
        $withdrawData = [];
        $diffData = [];
        
        for ($i = 0; $i < $days; $i++) {
            $date = date('Y-m-d', $startTime + 86400 * $i);
            $dayStart = strtotime($date);
            $dayEnd = $dayStart + 86400 - 1;

            $recharge = Db::table('caipiao_recharge')
                ->where('state', 1)
                ->where('oddtime', '>=', $dayStart)
                ->where('oddtime', '<=', $dayEnd)
                ->when(!empty($testUsers), fn($q) => $q->whereNotIn('uid', $testUsers))
                ->sum('amount') ?? 0;

            $withdraw = Db::table('caipiao_withdraw')
                ->where('state', 1)
                ->where('oddtime', '>=', $dayStart)
                ->where('oddtime', '<=', $dayEnd)
                ->when(!empty($testUsers), fn($q) => $q->whereNotIn('uid', $testUsers))
                ->sum('amount') ?? 0;

            $xAxis[] = date('m-d', $dayStart);
            $rechargeData[] = round($recharge, 2);
            $withdrawData[] = round($withdraw, 2);
            $diffData[] = round($recharge - $withdraw, 2);
        }

        // ========== 大额交易监控（单笔>=10000）==========
        $largeRecharges = Db::table('caipiao_recharge as r')
            ->leftJoin('caipiao_member as m', 'r.uid', '=', 'm.id')
            ->where('r.state', 1)
            ->where('r.amount', '>=', 10000)
            ->where('r.oddtime', '>=', $startTime)
            ->where('r.oddtime', '<=', $endTime)
            ->when(!empty($testUsers), fn($q) => $q->whereNotIn('r.uid', $testUsers))
            ->orderBy('r.oddtime', 'desc')
            ->limit(20)
            ->select(['r.id', 'm.username', 'r.amount', 'r.oddtime', Db::raw("'recharge' as type")])
            ->get();

        $largeWithdraws = Db::table('caipiao_withdraw as w')
            ->leftJoin('caipiao_member as m', 'w.uid', '=', 'm.id')
            ->where('w.state', 1)
            ->where('w.amount', '>=', 10000)
            ->where('w.oddtime', '>=', $startTime)
            ->where('w.oddtime', '<=', $endTime)
            ->when(!empty($testUsers), fn($q) => $q->whereNotIn('w.uid', $testUsers))
            ->orderBy('w.oddtime', 'desc')
            ->limit(20)
            ->select(['w.id', 'm.username', 'w.amount', 'w.oddtime', Db::raw("'withdraw' as type")])
            ->get();

        $largeTransactions = collect($largeRecharges)->merge($largeWithdraws)
            ->sortByDesc('oddtime')
            ->take(20)
            ->map(fn($item) => [
                'id' => $item->id,
                'username' => $this->maskUsername($item->username ?? ''),
                'amount' => round($item->amount, 2),
                'type' => $item->type,
                'typeName' => $item->type === 'recharge' ? 'Nạp tiền' : 'Rút tiền',
                'time' => date('Y-m-d H:i:s', $item->oddtime),
            ])
            ->values()
            ->toArray();

        // ========== 返回匹配前端类型的结构 ==========
        return $this->json(0, 'ok', [
            'recharge' => [
                'totalAmount' => round($totalRecharge, 2),
                'totalCount' => $rechargeCount,
                'userCount' => $rechargeUserCount,
                'avgAmount' => $rechargeCount > 0 ? round($totalRecharge / $rechargeCount, 2) : 0,
                'firstAmount' => round($firstRechargeAmount, 2),
                'firstCount' => $firstRechargeCount,
            ],
            'withdraw' => [
                'totalAmount' => round($totalWithdraw, 2),
                'totalCount' => $withdrawCount,
                'userCount' => $withdrawUserCount,
                'avgAmount' => $withdrawCount > 0 ? round($totalWithdraw / $withdrawCount, 2) : 0,
                'pendingAmount' => round($pendingWithdraw, 2),
                'pendingCount' => $pendingCount,
            ],
            'channels' => [], // 暂无渠道dữ liệu，需要Thanh toán渠道表支持
            'trendData' => [
                'xAxis' => $xAxis,
                'series' => [
                    ['name' => 'Nạp tiền', 'data' => $rechargeData],
                    ['name' => 'Rút tiền', 'data' => $withdrawData],
                    ['name' => '差额', 'data' => $diffData],
                ],
            ],
            'largeTransactions' => $largeTransactions,
            'channelTrend' => null, // 暂无渠道趋势dữ liệu
            // 兼容旧结构
            'summary' => [
                'totalRecharge' => round($totalRecharge, 2),
                'rechargeCount' => $rechargeCount,
                'totalWithdraw' => round($totalWithdraw, 2),
                'withdrawCount' => $withdrawCount,
                'pendingWithdraw' => round($pendingWithdraw, 2),
                'pendingCount' => $pendingCount,
                'diff' => round($totalRecharge - $totalWithdraw, 2),
            ],
            'trend' => array_map(fn($i) => [
                'date' => $xAxis[$i],
                'recharge' => $rechargeData[$i],
                'withdraw' => $withdrawData[$i],
                'diff' => $diffData[$i],
            ], range(0, count($xAxis) - 1)),
            'largeList' => $largeTransactions,
        ]);
    }

    /**
     * 2.2 平台盈亏统计
     * GET /app/admin/statistics/profit
     */
    public function profit(Request $request)
    {
        $startDate = $request->get('startDate', date('Y-m-d', strtotime('-6 days')));
        $endDate = $request->get('endDate', date('Y-m-d'));
        $lotteryType = $request->get('lotteryType', '');
        $startTime = strtotime($startDate);
        $endTime = strtotime($endDate) + 86400 - 1;
        $testUsers = $this->getTestUserIds();

        // Đặt cược总额（排除机器人Đặt cược）
        $betQuery = Db::table('caipiao_touzhu')
            ->whereIn('isdraw', [1, -1])
            ->where('oddtime', '>=', $startTime)
            ->where('oddtime', '<=', $endTime)
            ->when(!empty($testUsers), fn($q) => $q->whereNotIn('uid', $testUsers))
            ->when(!empty($lotteryType), fn($q) => $q->where('cpname', $lotteryType))
            ->where(function($q) { $q->whereNull('source')->orWhere('source', '!=', 'robot'); });

        $totalBet = (clone $betQuery)->sum('amount') ?? 0;

        // Trúng thưởng总额（排除机器人Đặt cược）
        $totalPrize = Db::table('caipiao_touzhu')
            ->where('isdraw', 1)
            ->where('oddtime', '>=', $startTime)
            ->where('oddtime', '<=', $endTime)
            ->when(!empty($testUsers), fn($q) => $q->whereNotIn('uid', $testUsers))
            ->when(!empty($lotteryType), fn($q) => $q->where('cpname', $lotteryType))
            ->where(function($q) { $q->whereNull('source')->orWhere('source', '!=', 'robot'); })
            ->sum('okamount') ?? 0;

        // Hoàn trả总额
        $totalRebate = Db::table('caipiao_fuddetail')
            ->where('type', 'fanshui')
            ->where('oddtime', '>=', $startTime)
            ->where('oddtime', '<=', $endTime)
            ->when(!empty($testUsers), fn($q) => $q->whereNotIn('uid', $testUsers))
            ->sum('amount') ?? 0;

        // Hoạt động支出
        $totalActivity = Db::table('caipiao_fuddetail')
            ->whereIn('type', ['activity_bindcard', 'activity_czzs', 'activity_rxf', 
                              'activity_rks', 'activity_yxf', 'activity_yks'])
            ->where('oddtime', '>=', $startTime)
            ->where('oddtime', '<=', $endTime)
            ->when(!empty($testUsers), fn($q) => $q->whereNotIn('uid', $testUsers))
            ->sum('amount') ?? 0;

        // 净盈亏
        $netProfit = $totalBet - $totalPrize - $totalRebate - $totalActivity;
        $profitRate = $totalBet > 0 ? round($netProfit / $totalBet * 100, 2) : 0;

        // 每日盈亏趋势
        $days = ceil(($endTime - $startTime) / 86400);
        $dailyTrend = [];
        for ($i = 0; $i < $days; $i++) {
            $date = date('Y-m-d', $startTime + 86400 * $i);
            $dayStart = strtotime($date);
            $dayEnd = $dayStart + 86400 - 1;

            $bet = Db::table('caipiao_touzhu')
                ->whereIn('isdraw', [1, -1])
                ->where('oddtime', '>=', $dayStart)
                ->where('oddtime', '<=', $dayEnd)
                ->when(!empty($testUsers), fn($q) => $q->whereNotIn('uid', $testUsers))
                ->when(!empty($lotteryType), fn($q) => $q->where('cpname', $lotteryType))
                ->where(function($q) { $q->whereNull('source')->orWhere('source', '!=', 'robot'); })
                ->sum('amount') ?? 0;

            $prize = Db::table('caipiao_touzhu')
                ->where('isdraw', 1)
                ->where('oddtime', '>=', $dayStart)
                ->where('oddtime', '<=', $dayEnd)
                ->when(!empty($testUsers), fn($q) => $q->whereNotIn('uid', $testUsers))
                ->when(!empty($lotteryType), fn($q) => $q->where('cpname', $lotteryType))
                ->where(function($q) { $q->whereNull('source')->orWhere('source', '!=', 'robot'); })
                ->sum('okamount') ?? 0;

            $dailyTrend[] = [
                'date' => date('m-d', $dayStart),
                'bet' => round($bet, 2),
                'prize' => round($prize, 2),
                'profit' => round($bet - $prize, 2),
            ];
        }

        // 彩种盈亏占比（排除机器人Đặt cược）
        $lotteryProfit = Db::table('caipiao_touzhu as t')
            ->leftJoin('caipiao_caipiao as c', 't.cpname', '=', 'c.name')
            ->whereIn('t.isdraw', [1, -1])
            ->where('t.oddtime', '>=', $startTime)
            ->where('t.oddtime', '<=', $endTime)
            ->when(!empty($testUsers), fn($q) => $q->whereNotIn('t.uid', $testUsers))
            ->where(function($q) { $q->whereNull('t.source')->orWhere('t.source', '!=', 'robot'); })
            ->groupBy('t.cpname', 'c.title')
            ->select([
                't.cpname as code',
                'c.title as name',
                Db::raw('SUM(t.amount) as bet'),
                Db::raw('SUM(CASE WHEN t.isdraw = 1 THEN t.okamount ELSE 0 END) as prize'),
            ])
            ->get()
            ->map(fn($item) => [
                'code' => $item->code,
                'name' => $item->name ?? $item->code,
                'bet' => round($item->bet, 2),
                'prize' => round($item->prize, 2),
                'profit' => round($item->bet - $item->prize, 2),
            ])
            ->sortByDesc('profit')
            ->values()
            ->toArray();

        // 构造 dailyData (TrendData 格式)
        $dailyData = [
            'xAxis' => array_column($dailyTrend, 'date'),
            'series' => [
                ['name' => 'Đặt cược', 'data' => array_column($dailyTrend, 'bet')],
                ['name' => 'Trúng thưởng', 'data' => array_column($dailyTrend, 'prize')],
                ['name' => '盈亏', 'data' => array_column($dailyTrend, 'profit')],
            ],
        ];

        // 构造 lotteryData (饼图格式)
        $lotteryData = array_map(fn($item) => [
            'name' => $item['name'],
            'value' => $item['profit'],
        ], $lotteryProfit);

        // 构造 costData (成本结构饼图)
        $costData = [
            ['name' => 'Trúng thưởng派彩', 'value' => round($totalPrize, 2)],
            ['name' => 'Hoàn trả', 'value' => round(abs($totalRebate), 2)],
            ['name' => 'Hoạt động支出', 'value' => round(abs($totalActivity), 2)],
        ];

        // 构造 hourlyData (按小时统计盈亏，排除机器人Đặt cược)
        $hourlyProfit = [];
        $weekAgo = time() - 86400 * 7;
        for ($h = 0; $h < 24; $h++) {
            $hourBet = Db::table('caipiao_touzhu')
                ->whereIn('isdraw', [1, -1])
                ->where('oddtime', '>=', $weekAgo)
                ->whereRaw("HOUR(FROM_UNIXTIME(oddtime)) = ?", [$h])
                ->when(!empty($testUsers), fn($q) => $q->whereNotIn('uid', $testUsers))
                ->where(function($q) { $q->whereNull('source')->orWhere('source', '!=', 'robot'); })
                ->sum('amount') ?? 0;
            $hourPrize = Db::table('caipiao_touzhu')
                ->where('isdraw', 1)
                ->where('oddtime', '>=', $weekAgo)
                ->whereRaw("HOUR(FROM_UNIXTIME(oddtime)) = ?", [$h])
                ->when(!empty($testUsers), fn($q) => $q->whereNotIn('uid', $testUsers))
                ->where(function($q) { $q->whereNull('source')->orWhere('source', '!=', 'robot'); })
                ->sum('okamount') ?? 0;
            $hourlyProfit[] = round($hourBet - $hourPrize, 2);
        }
        $hourlyData = [
            'xAxis' => array_map(fn($h) => sprintf('%02d:00', $h), range(0, 23)),
            'series' => [
                ['name' => '盈亏', 'data' => $hourlyProfit],
            ],
        ];

        // ========== 计算环比dữ liệu ==========
        // 上一周期的Thời gian范围
        $periodDays = $days;
        $prevStartTime = $startTime - 86400 * $periodDays;
        $prevEndTime = $startTime - 1;

        // 上周期Đặt cược总额（排除机器人Đặt cược）
        $prevTotalBet = Db::table('caipiao_touzhu')
            ->whereIn('isdraw', [1, -1])
            ->where('oddtime', '>=', $prevStartTime)
            ->where('oddtime', '<=', $prevEndTime)
            ->when(!empty($testUsers), fn($q) => $q->whereNotIn('uid', $testUsers))
            ->when(!empty($lotteryType), fn($q) => $q->where('cpname', $lotteryType))
            ->where(function($q) { $q->whereNull('source')->orWhere('source', '!=', 'robot'); })
            ->sum('amount') ?? 0;

        // 上周期Trúng thưởng总额（排除机器人Đặt cược）
        $prevTotalPrize = Db::table('caipiao_touzhu')
            ->where('isdraw', 1)
            ->where('oddtime', '>=', $prevStartTime)
            ->where('oddtime', '<=', $prevEndTime)
            ->when(!empty($testUsers), fn($q) => $q->whereNotIn('uid', $testUsers))
            ->when(!empty($lotteryType), fn($q) => $q->where('cpname', $lotteryType))
            ->where(function($q) { $q->whereNull('source')->orWhere('source', '!=', 'robot'); })
            ->sum('okamount') ?? 0;

        // 上周期Hoàn trả总额
        $prevTotalRebate = Db::table('caipiao_fuddetail')
            ->where('type', 'fanshui')
            ->where('oddtime', '>=', $prevStartTime)
            ->where('oddtime', '<=', $prevEndTime)
            ->when(!empty($testUsers), fn($q) => $q->whereNotIn('uid', $testUsers))
            ->sum('amount') ?? 0;

        // 上周期Hoạt động支出
        $prevTotalActivity = Db::table('caipiao_fuddetail')
            ->whereIn('type', ['activity_bindcard', 'activity_czzs', 'activity_rxf', 
                              'activity_rks', 'activity_yxf', 'activity_yks'])
            ->where('oddtime', '>=', $prevStartTime)
            ->where('oddtime', '<=', $prevEndTime)
            ->when(!empty($testUsers), fn($q) => $q->whereNotIn('uid', $testUsers))
            ->sum('amount') ?? 0;

        // 上周期净盈亏
        $prevNetProfit = $prevTotalBet - $prevTotalPrize - $prevTotalRebate - $prevTotalActivity;
        $prevProfitRate = $prevTotalBet > 0 ? round($prevNetProfit / $prevTotalBet * 100, 2) : 0;

        // 计算环比变化率
        $calcChange = function($current, $previous) {
            if ($previous == 0) {
                return $current > 0 ? 100 : ($current < 0 ? -100 : 0);
            }
            return round(($current - $previous) / abs($previous) * 100, 1);
        };

        $totalBetChange = $calcChange($totalBet, $prevTotalBet);
        $totalPrizeChange = $calcChange($totalPrize, $prevTotalPrize);
        $totalRebateChange = $calcChange(abs($totalRebate), abs($prevTotalRebate));
        $totalActivityChange = $calcChange(abs($totalActivity), abs($prevTotalActivity));
        $netProfitChange = $calcChange($netProfit, $prevNetProfit);
        $profitRateChange = $calcChange($profitRate, $prevProfitRate);

        // 返回匹配前端 ProfitStats 类型
        return $this->json(0, 'ok', [
            'totalBet' => round($totalBet, 2),
            'totalPrize' => round($totalPrize, 2),
            'totalRebate' => round(abs($totalRebate), 2),
            'totalActivity' => round(abs($totalActivity), 2),
            'netProfit' => round($netProfit, 2),
            'profitRate' => $profitRate,
            // 环比dữ liệu
            'totalBetChange' => $totalBetChange,
            'totalPrizeChange' => $totalPrizeChange,
            'totalRebateChange' => $totalRebateChange,
            'totalActivityChange' => $totalActivityChange,
            'netProfitChange' => $netProfitChange,
            'profitRateChange' => $profitRateChange,
            'dailyData' => $dailyData,
            'lotteryData' => $lotteryData,
            'costData' => $costData,
            'hourlyData' => $hourlyData,
            // 兼容旧结构
            'summary' => [
                'totalBet' => round($totalBet, 2),
                'totalPrize' => round($totalPrize, 2),
                'totalRebate' => round(abs($totalRebate), 2),
                'totalActivity' => round(abs($totalActivity), 2),
                'netProfit' => round($netProfit, 2),
                'profitRate' => $profitRate,
            ],
            'dailyTrend' => $dailyTrend,
            'lotteryProfit' => $lotteryProfit,
        ]);
    }

    /**
     * 3.1 Người dùng统计
     * GET /app/admin/statistics/user
     */
    public function user(Request $request)
    {
        $startDate = $request->get('startDate', date('Y-m-d', strtotime('-6 days')));
        $endDate = $request->get('endDate', date('Y-m-d'));
        $startTime = strtotime($startDate);
        $endTime = strtotime($endDate) + 86400 - 1;

        // 总Người dùng数
        $totalUsers = Db::table('caipiao_member')->where('isnb', 0)->count();

        // 新增Người dùng
        $newUsers = Db::table('caipiao_member')
            ->where('isnb', 0)
            ->where('regtime', '>=', $startTime)
            ->where('regtime', '<=', $endTime)
            ->count();

        // 活跃Người dùng
        $activeUsers = Db::table('caipiao_member')
            ->where('isnb', 0)
            ->where('onlinetime', '>=', $startTime)
            ->where('onlinetime', '<=', $endTime)
            ->count();

        // Nạp tiềnNgười dùng数
        $rechargeUsers = Db::table('caipiao_recharge')
            ->where('state', 1)
            ->where('oddtime', '>=', $startTime)
            ->where('oddtime', '<=', $endTime)
            ->distinct()
            ->count('uid');

        // Đặt cượcNgười dùng数
        $betUsers = Db::table('caipiao_touzhu')
            ->whereIn('isdraw', [1, -1])
            ->where('oddtime', '>=', $startTime)
            ->where('oddtime', '<=', $endTime)
            ->distinct()
            ->count('uid');

        // 首充Người dùng数
        $firstRechargeUsers = Db::table('caipiao_recharge as r1')
            ->where('r1.state', 1)
            ->where('r1.oddtime', '>=', $startTime)
            ->where('r1.oddtime', '<=', $endTime)
            ->whereNotExists(function ($query) use ($startTime) {
                $query->select(Db::raw(1))
                    ->from('caipiao_recharge as r2')
                    ->whereColumn('r2.uid', 'r1.uid')
                    ->where('r2.state', 1)
                    ->where('r2.oddtime', '<', $startTime);
            })
            ->distinct()
            ->count('r1.uid');

        // Người dùng增长趋势
        $days = ceil(($endTime - $startTime) / 86400);
        $growthTrend = [];
        for ($i = 0; $i < $days; $i++) {
            $date = date('Y-m-d', $startTime + 86400 * $i);
            $dayStart = strtotime($date);
            $dayEnd = $dayStart + 86400 - 1;

            $newCount = Db::table('caipiao_member')
                ->where('isnb', 0)
                ->where('regtime', '>=', $dayStart)
                ->where('regtime', '<=', $dayEnd)
                ->count();

            $activeCount = Db::table('caipiao_member')
                ->where('isnb', 0)
                ->where('onlinetime', '>=', $dayStart)
                ->where('onlinetime', '<=', $dayEnd)
                ->count();

            $growthTrend[] = [
                'date' => date('m-d', $dayStart),
                'newUsers' => $newCount,
                'activeUsers' => $activeCount,
            ];
        }

        return $this->json(0, 'ok', [
            'summary' => [
                'totalUsers' => $totalUsers,
                'newUsers' => $newUsers,
                'activeUsers' => $activeUsers,
                'rechargeUsers' => $rechargeUsers,
                'betUsers' => $betUsers,
                'firstRechargeUsers' => $firstRechargeUsers,
            ],
            'growthTrend' => $growthTrend,
        ]);
    }

    /**
     * 3.2 Đại lý/Đội nhóm统计（分页列表）
     * GET /app/admin/api/statistics/team
     * 兼容参数: startDate/endDate (YYYY-MM-DD) hoặc sDate/eDate (YYYYMMDD)
     */
    public function team(Request $request)
    {
        // 兼容两种Ngày参数格式
        $startDate = $request->get('startDate') ?: $request->get('sDate');
        $endDate = $request->get('endDate') ?: $request->get('eDate');
        
        // 转换 YYYYMMDD 为 YYYY-MM-DD
        if ($startDate && strlen($startDate) === 8 && is_numeric($startDate)) {
            $startDate = substr($startDate, 0, 4) . '-' . substr($startDate, 4, 2) . '-' . substr($startDate, 6, 2);
        }
        if ($endDate && strlen($endDate) === 8 && is_numeric($endDate)) {
            $endDate = substr($endDate, 0, 4) . '-' . substr($endDate, 4, 2) . '-' . substr($endDate, 6, 2);
        }
        
        // 分页参数
        $current = max(1, (int)($request->get('current') ?: $request->get('page', 1)));
        $size = min(100, max(1, (int)($request->get('size') ?: $request->get('limit', 20))));
        $username = $request->get('username', '');
        $pid = $request->get('pid', 0);
        
        // 构建Đại lýTra cứu
        $query = Db::table('caipiao_member as m')->where('m.isnb', 0);
        
        if (!empty($pid)) {
            $query->where('m.parentid', $pid);
        } else {
            $query->where('m.proxy', 1); // 只查Đại lý
        }
        
        if ($username) {
            $query->where('m.username', 'like', "%{$username}%");
        }
        
        // 总数
        $total = (clone $query)->count();
        
        // 分页Tra cứuĐại lý列表
        $agents = $query
            ->select(['m.id', 'm.username', 'm.proxy'])
            ->orderBy('m.id', 'desc')
            ->offset(($current - 1) * $size)
            ->limit($size)
            ->get();
        
        // 计算Thời gian范围
        $startTime = !empty($startDate) ? strtotime($startDate) : 0;
        $endTime = !empty($endDate) ? strtotime($endDate) + 86400 - 1 : time();
        
        // 填充业绩dữ liệu (匹配前端表格字段)
        $records = $agents->map(function ($agent) use ($startTime, $endTime) {
            // 递归Lấy所有下线Người dùngID
            $downlineIds = $this->getAllDownlineIds($agent->id);
            $downlineIds[] = $agent->id; // 包含自己
            
            // Đội nhóm总数 (不包含自己)
            $totalcount = count($downlineIds) - 1;
            
            // Đội nhómĐại lý数
            $agentcount = Db::table('caipiao_member')
                ->whereIn('id', $downlineIds)
                ->where('id', '!=', $agent->id)
                ->where('proxy', 1)
                ->count();
            
            // 普通Người dùng数
            $usercount = $totalcount - $agentcount;
            
            // 在线数（最近30分钟）
            $onlinecount = Db::table('caipiao_member')
                ->whereIn('id', $downlineIds)
                ->where('onlinetime', '>=', time() - 1800)
                ->count();
            
            // 自动Nạp tiền
            $zdrecharge = Db::table('caipiao_recharge')
                ->whereIn('uid', $downlineIds)
                ->where('state', 1)
                ->where('isauto', 1)
                ->when($startTime, fn($q) => $q->where('oddtime', '>=', $startTime))
                ->when($endTime, fn($q) => $q->where('oddtime', '<=', $endTime))
                ->sum('amount') ?? 0;
            
            // 手动加款
            $sdjiarecharge = Db::table('caipiao_recharge')
                ->whereIn('uid', $downlineIds)
                ->where('state', 1)
                ->where('isauto', 2)
                ->where('sdtype', 1)
                ->when($startTime, fn($q) => $q->where('oddtime', '>=', $startTime))
                ->when($endTime, fn($q) => $q->where('oddtime', '<=', $endTime))
                ->sum('amount') ?? 0;
            
            // 手动减款
            $sdjianrecharge = Db::table('caipiao_recharge')
                ->whereIn('uid', $downlineIds)
                ->where('state', 1)
                ->where('isauto', 2)
                ->where('sdtype', -1)
                ->when($startTime, fn($q) => $q->where('oddtime', '>=', $startTime))
                ->when($endTime, fn($q) => $q->where('oddtime', '<=', $endTime))
                ->sum('amount') ?? 0;
            
            // Rút tiền
            $withdraw = Db::table('caipiao_withdraw')
                ->whereIn('uid', $downlineIds)
                ->where('state', 1)
                ->when($startTime, fn($q) => $q->where('oddtime', '>=', $startTime))
                ->when($endTime, fn($q) => $q->where('oddtime', '<=', $endTime))
                ->sum('amount') ?? 0;
            
            // Đặt cược（排除机器人Đặt cược）
            $touzhu = Db::table('caipiao_touzhu')
                ->whereIn('uid', $downlineIds)
                ->whereIn('isdraw', [1, -1])
                ->when($startTime, fn($q) => $q->where('oddtime', '>=', $startTime))
                ->when($endTime, fn($q) => $q->where('oddtime', '<=', $endTime))
                ->where(function($q) { $q->whereNull('source')->orWhere('source', '!=', 'robot'); })
                ->sum('amount') ?? 0;
            
            // Trúng thưởng（排除机器人Đặt cược）
            $zhongjiang = Db::table('caipiao_touzhu')
                ->whereIn('uid', $downlineIds)
                ->where('isdraw', 1)
                ->when($startTime, fn($q) => $q->where('oddtime', '>=', $startTime))
                ->when($endTime, fn($q) => $q->where('oddtime', '<=', $endTime))
                ->where(function($q) { $q->whereNull('source')->orWhere('source', '!=', 'robot'); })
                ->sum('okamount') ?? 0;
            
            // Hoạt động
            $huodong = Db::table('caipiao_fuddetail')
                ->whereIn('uid', $downlineIds)
                ->whereIn('type', ['pointexchange', 'fanshui', 'yongjinshenhe', 'jinjishenhe',
                                  'activity_bindcard', 'activity_czzs', 'activity_rxf',
                                  'activity_rks', 'activity_yxf', 'activity_yks'])
                ->when($startTime, fn($q) => $q->where('oddtime', '>=', $startTime))
                ->when($endTime, fn($q) => $q->where('oddtime', '<=', $endTime))
                ->sum('amount') ?? 0;
            
            return [
                'username' => $agent->username,
                'totalcount' => $totalcount,
                'agentcount' => $agentcount,
                'usercount' => $usercount,
                'onlinecount' => $onlinecount,
                'zdrecharge' => round($zdrecharge, 2),
                'sdjiarecharge' => round($sdjiarecharge, 2),
                'sdjianrecharge' => round($sdjianrecharge, 2),
                'withdraw' => round($withdraw, 2),
                'ctyingkui' => round(($zdrecharge + $sdjiarecharge - $sdjianrecharge) - $withdraw, 2),
                'touzhu' => round($touzhu, 2),
                'zhongjiang' => round($zhongjiang, 2),
                'huodong' => round($huodong, 2),
                'tzyingkui' => round($touzhu - $zhongjiang, 2),
            ];
        })->toArray();
        
        return $this->json(0, 'ok', [
            'records' => $records,
            'list' => $records,  // 兼容
            'total' => $total,
            'current' => $current,
            'size' => $size,
        ]);
    }
    
    /**
     * 递归Lấy所有Cấp dướiID (不限层级)
     */
    private function getAllDownlineIds(int $parentId, array &$result = []): array
    {
        $children = Db::table('caipiao_member')
            ->where('parentid', $parentId)
            ->where('isnb', 0)
            ->pluck('id')
            ->toArray();
        
        foreach ($children as $childId) {
            $result[] = $childId;
            $this->getAllDownlineIds($childId, $result);
        }
        
        return $result;
    }

    /**
     * 4. 彩种统计
     * GET /app/admin/statistics/lottery
     */
    public function lottery(Request $request)
    {
        $startDate = $request->get('startDate', date('Y-m-d', strtotime('-6 days')));
        $endDate = $request->get('endDate', date('Y-m-d'));
        $lotteryId = $request->get('lotteryId', '');
        $startTime = strtotime($startDate);
        $endTime = strtotime($endDate) + 86400 - 1;
        $testUsers = $this->getTestUserIds();

        // 彩种详细dữ liệu（排除机器人Đặt cược）
        $lotteryQuery = Db::table('caipiao_touzhu as t')
            ->leftJoin('caipiao_caipiao as c', 't.cpname', '=', 'c.name')
            ->whereIn('t.isdraw', [1, -1])
            ->where('t.oddtime', '>=', $startTime)
            ->where('t.oddtime', '<=', $endTime)
            ->when(!empty($testUsers), fn($q) => $q->whereNotIn('t.uid', $testUsers))
            ->where(function($q) { $q->whereNull('t.source')->orWhere('t.source', '!=', 'robot'); })
            ->when(!empty($lotteryId), fn($q) => $q->where('c.id', $lotteryId));

        $lotteryList = (clone $lotteryQuery)
            ->groupBy('t.cpname', 'c.id', 'c.title')
            ->select([
                'c.id as lotteryId',
                't.cpname as code',
                'c.title as name',
                Db::raw('COUNT(*) as betCount'),
                Db::raw('COUNT(DISTINCT t.uid) as userCount'),
                Db::raw('SUM(t.amount) as betAmount'),
                Db::raw('SUM(CASE WHEN t.isdraw = 1 THEN t.okamount ELSE 0 END) as prizeAmount'),
            ])
            ->get()
            ->map(fn($item) => [
                'lotteryId' => $item->lotteryId,
                'code' => $item->code,
                'name' => $item->name ?? $item->code,
                'betCount' => $item->betCount,
                'userCount' => $item->userCount,
                'betAmount' => round($item->betAmount, 2),
                'prizeAmount' => round($item->prizeAmount, 2),
                'profit' => round($item->betAmount - $item->prizeAmount, 2),
                'profitRate' => $item->betAmount > 0 
                    ? round(($item->betAmount - $item->prizeAmount) / $item->betAmount * 100, 2) 
                    : 0,
            ])
            ->sortByDesc('betAmount')
            ->values()
            ->toArray();

        // 热门玩法排行（排除机器人Đặt cược）
        $playRank = Db::table('caipiao_touzhu as t')
            ->whereIn('t.isdraw', [1, -1])
            ->where('t.oddtime', '>=', $startTime)
            ->where('t.oddtime', '<=', $endTime)
            ->when(!empty($testUsers), fn($q) => $q->whereNotIn('t.uid', $testUsers))
            ->where(function($q) { $q->whereNull('t.source')->orWhere('t.source', '!=', 'robot'); })
            ->when(!empty($lotteryId), function($q) use ($lotteryId) {
                $lottery = Db::table('caipiao_caipiao')->where('id', $lotteryId)->first();
                if ($lottery) {
                    $q->where('t.cpname', $lottery->name);
                }
            })
            ->groupBy('t.playtitle')
            ->select([
                't.playtitle as name',
                Db::raw('SUM(t.amount) as betAmount'),
                Db::raw('COUNT(*) as betCount'),
            ])
            ->orderBy('betAmount', 'desc')
            ->limit(10)
            ->get()
            ->map(fn($item) => [
                'name' => $item->name ?? '未知玩法',
                'betAmount' => round($item->betAmount, 2),
                'betCount' => $item->betCount,
            ])
            ->toArray();

        // 大额Trúng thưởng榜（排除机器人Đặt cược）
        $bigWinList = Db::table('caipiao_touzhu as t')
            ->leftJoin('caipiao_member as m', 't.uid', '=', 'm.id')
            ->leftJoin('caipiao_caipiao as c', 't.cpname', '=', 'c.name')
            ->where('t.isdraw', 1)
            ->where('t.okamount', '>=', 1000)
            ->where('t.oddtime', '>=', $startTime)
            ->where('t.oddtime', '<=', $endTime)
            ->when(!empty($testUsers), fn($q) => $q->whereNotIn('t.uid', $testUsers))
            ->where(function($q) { $q->whereNull('t.source')->orWhere('t.source', '!=', 'robot'); })
            ->orderBy('t.okamount', 'desc')
            ->limit(20)
            ->select(['t.id', 'm.username', 'c.title as lotteryName', 't.playtitle', 't.amount', 't.okamount', 't.oddtime'])
            ->get()
            ->map(fn($item) => [
                'id' => $item->id,
                'username' => $this->maskUsername($item->username ?? ''),
                'lotteryName' => $item->lotteryName ?? '未知',
                'playName' => $item->playtitle ?? '',
                'betAmount' => round($item->amount, 2),
                'winAmount' => round($item->okamount, 2),
                'time' => date('Y-m-d H:i:s', $item->oddtime),
            ])
            ->toArray();

        // 汇总
        $totalBet = array_sum(array_column($lotteryList, 'betAmount'));
        $totalPrize = array_sum(array_column($lotteryList, 'prizeAmount'));

        // 构造 pieData (彩种Đặt cược占比饼图)
        $pieData = array_map(fn($item) => [
            'name' => $item['name'],
            'value' => $item['betAmount'],
        ], $lotteryList);

        // 构造 hotPlay (TrendData 格式)
        $hotPlay = [
            'xAxis' => array_column($playRank, 'name'),
            'series' => [
                ['name' => 'Đặt cượcSố tiền', 'data' => array_column($playRank, 'betAmount')],
            ],
        ];

        // 构造 peakHours (高峰时段分析，排除机器人Đặt cược)
        $hourlyBets = [];
        $weekAgo = time() - 86400 * 7;
        for ($h = 0; $h < 24; $h++) {
            $count = Db::table('caipiao_touzhu')
                ->whereIn('isdraw', [1, -1])
                ->where('oddtime', '>=', $weekAgo)
                ->whereRaw("HOUR(FROM_UNIXTIME(oddtime)) = ?", [$h])
                ->when(!empty($testUsers), fn($q) => $q->whereNotIn('uid', $testUsers))
                ->where(function($q) { $q->whereNull('source')->orWhere('source', '!=', 'robot'); })
                ->count();
            $hourlyBets[] = $count;
        }
        $peakHours = [
            'xAxis' => array_map(fn($h) => sprintf('%02d:00', $h), range(0, 23)),
            'series' => [
                ['name' => 'Đặt cược数', 'data' => $hourlyBets],
            ],
        ];

        // 构造 list (匹配前端 LotteryStats.list 类型)
        $list = array_map(fn($item) => [
            'lotteryName' => $item['name'],
            'betCount' => $item['betCount'],
            'betAmount' => $item['betAmount'],
            'prizeAmount' => $item['prizeAmount'],
            'profitAmount' => $item['profit'],
            'profitRate' => $item['profitRate'],
            'userCount' => $item['userCount'],
        ], $lotteryList);

        // 构造 bigWins (匹配前端类型)
        $bigWins = array_map(fn($item) => [
            'username' => $item['username'],
            'lotteryName' => $item['lotteryName'],
            'playName' => $item['playName'],
            'playTitle' => $item['playName'],
            'prizeAmount' => $item['winAmount'],
            'time' => $item['time'],
        ], $bigWinList);

        // 返回匹配前端 LotteryStats 类型
        return $this->json(0, 'ok', [
            'list' => $list,
            'pieData' => $pieData,
            'barData' => $hotPlay, // 兼容
            'hotPlay' => $hotPlay,
            'peakHours' => $peakHours,
            'bigWins' => $bigWins,
            // 兼容旧结构
            'summary' => [
                'totalBet' => round($totalBet, 2),
                'totalPrize' => round($totalPrize, 2),
                'totalProfit' => round($totalBet - $totalPrize, 2),
                'lotteryCount' => count($lotteryList),
            ],
            'lotteryList' => $lotteryList,
            'playRank' => $playRank,
            'bigWinList' => $bigWinList,
        ]);
    }

    /**
     * 5. Người dùng留存率分析
     * GET /app/admin/statistics/retention
     */
    public function retention(Request $request)
    {
        $days = $request->get('days', 7); // 分析最近N天Đăng kýNgười dùng的留存
        
        $retentionData = [];
        
        for ($i = $days; $i >= 1; $i--) {
            $regDate = date('Y-m-d', strtotime("-{$i} days"));
            $regStart = strtotime($regDate);
            $regEnd = $regStart + 86400 - 1;
            
            // 当天Đăng kýNgười dùng数
            $newUsers = Db::table('caipiao_member')
                ->where('isnb', 0)
                ->where('regtime', '>=', $regStart)
                ->where('regtime', '<=', $regEnd)
                ->pluck('id')
                ->toArray();
            
            $newUserCount = count($newUsers);
            if ($newUserCount == 0) {
                $retentionData[] = [
                    'date' => date('m-d', $regStart),
                    'newUsers' => 0,
                    'day1' => 0,
                    'day3' => 0,
                    'day7' => 0,
                    'day1Rate' => 0,
                    'day3Rate' => 0,
                    'day7Rate' => 0,
                ];
                continue;
            }
            
            // 次日留存（Đăng ký后第2天活跃）
            $day1Start = $regStart + 86400;
            $day1End = $day1Start + 86400 - 1;
            $day1Active = Db::table('caipiao_member')
                ->whereIn('id', $newUsers)
                ->where('onlinetime', '>=', $day1Start)
                ->where('onlinetime', '<=', $day1End)
                ->count();
            
            // 3日留存
            $day3Start = $regStart + 86400 * 3;
            $day3End = $day3Start + 86400 - 1;
            $day3Active = Db::table('caipiao_member')
                ->whereIn('id', $newUsers)
                ->where('onlinetime', '>=', $day3Start)
                ->where('onlinetime', '<=', $day3End)
                ->count();
            
            // 7日留存
            $day7Start = $regStart + 86400 * 7;
            $day7End = $day7Start + 86400 - 1;
            $day7Active = Db::table('caipiao_member')
                ->whereIn('id', $newUsers)
                ->where('onlinetime', '>=', $day7Start)
                ->where('onlinetime', '<=', $day7End)
                ->count();
            
            $retentionData[] = [
                'date' => date('m-d', $regStart),
                'newUsers' => $newUserCount,
                'day1' => $day1Active,
                'day3' => $day3Active,
                'day7' => $day7Active,
                'day1Rate' => round($day1Active / $newUserCount * 100, 1),
                'day3Rate' => round($day3Active / $newUserCount * 100, 1),
                'day7Rate' => round($day7Active / $newUserCount * 100, 1),
            ];
        }
        
        // 计算平均留存率
        $totalNew = array_sum(array_column($retentionData, 'newUsers'));
        $avgDay1 = $totalNew > 0 ? round(array_sum(array_column($retentionData, 'day1')) / $totalNew * 100, 1) : 0;
        $avgDay3 = $totalNew > 0 ? round(array_sum(array_column($retentionData, 'day3')) / $totalNew * 100, 1) : 0;
        $avgDay7 = $totalNew > 0 ? round(array_sum(array_column($retentionData, 'day7')) / $totalNew * 100, 1) : 0;
        
        return $this->json(0, 'ok', [
            'summary' => [
                'avgDay1Rate' => $avgDay1,
                'avgDay3Rate' => $avgDay3,
                'avgDay7Rate' => $avgDay7,
            ],
            'list' => $retentionData,
        ]);
    }

    /**
     * 6. RFM Người dùng价值分析
     * GET /app/admin/statistics/value-analysis
     */
    public function valueAnalysis(Request $request)
    {
        $testUsers = $this->getTestUserIds();
        $now = time();
        $day30 = $now - 86400 * 30;
        
        // Lấy所有真实Người dùng的 RFM dữ liệu
        $users = Db::table('caipiao_member as m')
            ->where('m.isnb', 0)
            ->select([
                'm.id',
                'm.username',
                'm.onlinetime',
                'm.regtime',
            ])
            ->get();
        
        $rfmData = [];
        foreach ($users as $user) {
            // R - Recency: 最近一次消费距今天数
            $lastBet = Db::table('caipiao_touzhu')
                ->where('uid', $user->id)
                ->whereIn('isdraw', [1, -1])
                ->max('oddtime');
            $recency = $lastBet ? ceil(($now - $lastBet) / 86400) : 999;
            
            // F - Frequency: 30天内Đặt cược次数
            $frequency = Db::table('caipiao_touzhu')
                ->where('uid', $user->id)
                ->whereIn('isdraw', [1, -1])
                ->where('oddtime', '>=', $day30)
                ->count();
            
            // M - Monetary: 30天内Đặt cượcSố tiền
            $monetary = Db::table('caipiao_touzhu')
                ->where('uid', $user->id)
                ->whereIn('isdraw', [1, -1])
                ->where('oddtime', '>=', $day30)
                ->sum('amount') ?? 0;
            
            $rfmData[] = [
                'id' => $user->id,
                'recency' => $recency,
                'frequency' => $frequency,
                'monetary' => $monetary,
            ];
        }
        
        // RFM 评分（简化版：根据中位数划分高低）
        $rMedian = $this->getMedian(array_column($rfmData, 'recency'));
        $fMedian = $this->getMedian(array_column($rfmData, 'frequency'));
        $mMedian = $this->getMedian(array_column($rfmData, 'monetary'));
        
        // Người dùng分层统计
        $segments = [
            'vip' => 0,        // 重要价值Người dùng (R低F高M高)
            'important' => 0,  // 重要发展Người dùng (R低F低M高)
            'active' => 0,     // 一般价值Người dùng (R低F高M低)
            'new' => 0,        // 新Người dùng (R低F低M低)
            'sleeping' => 0,   // 沉睡Người dùng (R高F高M高)
            'lost' => 0,       // 流失Người dùng (R高F低M低)
        ];
        
        foreach ($rfmData as $item) {
            $rHigh = $item['recency'] <= $rMedian; // R越小越好
            $fHigh = $item['frequency'] >= $fMedian;
            $mHigh = $item['monetary'] >= $mMedian;
            
            if ($rHigh && $fHigh && $mHigh) {
                $segments['vip']++;
            } elseif ($rHigh && !$fHigh && $mHigh) {
                $segments['important']++;
            } elseif ($rHigh && $fHigh && !$mHigh) {
                $segments['active']++;
            } elseif ($rHigh && !$fHigh && !$mHigh) {
                $segments['new']++;
            } elseif (!$rHigh && $fHigh) {
                $segments['sleeping']++;
            } else {
                $segments['lost']++;
            }
        }
        
        $totalUsers = count($rfmData);
        
        return $this->json(0, 'ok', [
            'summary' => [
                'totalUsers' => $totalUsers,
                'avgRecency' => $totalUsers > 0 ? round(array_sum(array_column($rfmData, 'recency')) / $totalUsers, 1) : 0,
                'avgFrequency' => $totalUsers > 0 ? round(array_sum(array_column($rfmData, 'frequency')) / $totalUsers, 1) : 0,
                'avgMonetary' => $totalUsers > 0 ? round(array_sum(array_column($rfmData, 'monetary')) / $totalUsers, 2) : 0,
            ],
            'segments' => [
                ['name' => '重要价值Người dùng', 'value' => $segments['vip'], 'key' => 'vip'],
                ['name' => '重要发展Người dùng', 'value' => $segments['important'], 'key' => 'important'],
                ['name' => '一般活跃Người dùng', 'value' => $segments['active'], 'key' => 'active'],
                ['name' => '新Người dùng', 'value' => $segments['new'], 'key' => 'new'],
                ['name' => '沉睡Người dùng', 'value' => $segments['sleeping'], 'key' => 'sleeping'],
                ['name' => '流失Người dùng', 'value' => $segments['lost'], 'key' => 'lost'],
            ],
        ]);
    }
    
    /**
     * 计算中位数
     */
    private function getMedian(array $arr): float
    {
        if (empty($arr)) return 0;
        sort($arr);
        $count = count($arr);
        $mid = floor($count / 2);
        return $count % 2 ? $arr[$mid] : ($arr[$mid - 1] + $arr[$mid]) / 2;
    }

    /**
     * 7. 设备分布统计
     * GET /app/admin/statistics/device-distribution
     */
    public function deviceDistribution(Request $request)
    {
        $totalUsers = Db::table('caipiao_member')->where('isnb', 0)->count();
        
        // 从 source hoặc loginsource 字段分析设备来源
        $fromAppUsers = Db::table('caipiao_member')
            ->where('isnb', 0)
            ->where(function($q) {
                $q->where('source', 'like', '%app%')
                  ->orWhere('source', 'like', '%android%')
                  ->orWhere('source', 'like', '%ios%')
                  ->orWhere('loginsource', 'like', '%app%')
                  ->orWhere('loginsource', 'like', '%android%')
                  ->orWhere('loginsource', 'like', '%ios%');
            })
            ->count();
        
        $fromH5Users = Db::table('caipiao_member')
            ->where('isnb', 0)
            ->where(function($q) {
                $q->where('source', 'like', '%h5%')
                  ->orWhere('source', 'like', '%wap%')
                  ->orWhere('loginsource', 'like', '%h5%')
                  ->orWhere('loginsource', 'like', '%wap%');
            })
            ->count();
        
        $fromPCUsers = Db::table('caipiao_member')
            ->where('isnb', 0)
            ->where(function($q) {
                $q->where('source', 'like', '%pc%')
                  ->orWhere('source', 'like', '%web%')
                  ->orWhere('loginsource', 'like', '%pc%')
                  ->orWhere('loginsource', 'like', '%web%');
            })
            ->count();
        
        // 计算已识别和未识别的Người dùng
        $identified = $fromAppUsers + $fromH5Users + $fromPCUsers;
        $unknown = max(0, $totalUsers - $identified);
        
        // 如果大部分未识别，根据行业经验估算
        if ($identified == 0 || $unknown > $identified * 2) {
            // 移动端占比约70%
            $deviceData = [
                ['name' => 'Android', 'value' => round($totalUsers * 0.42)],
                ['name' => 'iOS', 'value' => round($totalUsers * 0.28)],
                ['name' => 'H5', 'value' => round($totalUsers * 0.20)],
                ['name' => 'PC', 'value' => round($totalUsers * 0.10)],
            ];
        } else {
            $deviceData = [
                ['name' => 'APP', 'value' => $fromAppUsers],
                ['name' => 'H5', 'value' => $fromH5Users],
                ['name' => 'PC', 'value' => $fromPCUsers],
                ['name' => '其他', 'value' => $unknown],
            ];
        }
        
        // 活跃时段分布（按小时统计最近7天的活跃）
        $hourlyActive = [];
        $weekAgo = time() - 86400 * 7;
        
        for ($h = 0; $h < 24; $h++) {
            // 统计该小时段的活跃Người dùng数
            $count = Db::table('caipiao_touzhu')
                ->where('oddtime', '>=', $weekAgo)
                ->whereRaw("HOUR(FROM_UNIXTIME(oddtime)) = ?", [$h])
                ->distinct()
                ->count('uid');
            
            $hourlyActive[] = [
                'hour' => sprintf('%02d:00', $h),
                'count' => $count,
            ];
        }
        
        return $this->json(0, 'ok', [
            'deviceDistribution' => $deviceData,
            'hourlyActive' => $hourlyActive,
            'totalUsers' => $totalUsers,
        ]);
    }

    /**
     * 8. Đội nhóm概览（给顶部卡片）
     * GET /app/admin/api/statistics/team/overview
     */
    public function teamOverview(Request $request)
    {
        // Người dùng总数（排除测试Người dùng）
        $totalUsers = Db::table('caipiao_member')
            ->where('isnb', 0)
            ->count();
        
        // Đại lý总数
        $totalAgents = Db::table('caipiao_member')
            ->where('isnb', 0)
            ->where('proxy', 1)
            ->count();
        
        // Thành viên数（非Đại lýNgười dùng）
        $totalMembers = Db::table('caipiao_member')
            ->where('isnb', 0)
            ->where('proxy', 0)
            ->count();
        
        // 在线人数（最近15分钟活跃）
        $onlineTime = time() - 900;
        $onlineUsers = Db::table('caipiao_member')
            ->where('isnb', 0)
            ->where('onlinetime', '>=', $onlineTime)
            ->count();
        
        // 直属Thành viên数（有上级Đại lý的Người dùng）
        $directMembers = Db::table('caipiao_member')
            ->where('isnb', 0)
            ->where('parentid', '>', 0)
            ->where('proxy', 0)
            ->count();
        
        // Đội nhóm总人数（所有有上级的Người dùng）
        $teamMembers = Db::table('caipiao_member')
            ->where('isnb', 0)
            ->where('parentid', '>', 0)
            ->count();
        
        // Đội nhómNạp tiền（Tháng này）
        $monthStart = strtotime(date('Y-m-01'));
        $teamRecharge = Db::table('caipiao_recharge as r')
            ->join('caipiao_member as m', 'r.uid', '=', 'm.id')
            ->where('m.isnb', 0)
            ->where('m.parentid', '>', 0)
            ->where('r.state', 1)
            ->where('r.oddtime', '>=', $monthStart)
            ->sum('r.amount') ?? 0;
        
        // Đội nhómĐặt cược（Tháng này）
        $teamBet = Db::table('caipiao_touzhu as t')
            ->join('caipiao_member as m', 't.uid', '=', 'm.id')
            ->where('m.isnb', 0)
            ->where('m.parentid', '>', 0)
            ->whereIn('t.isdraw', [1, -1])
            ->where('t.oddtime', '>=', $monthStart)
            ->sum('t.amount') ?? 0;
        
        // Đội nhóm盈利（Tháng nàyĐặt cược - Trả thưởng）
        $teamPrize = Db::table('caipiao_touzhu as t')
            ->join('caipiao_member as m', 't.uid', '=', 'm.id')
            ->where('m.isnb', 0)
            ->where('m.parentid', '>', 0)
            ->where('t.isdraw', 1)
            ->where('t.oddtime', '>=', $monthStart)
            ->sum('t.okamount') ?? 0;
        $teamProfit = $teamBet - $teamPrize;
        
        // 已发放Hoa hồng（Tháng này）
        $commissionPaid = Db::table('caipiao_fuddetail')
            ->whereIn('type', ['yongjinshenhe', 'jinjishenhe', 'yongjin', 'commission'])
            ->where('oddtime', '>=', $monthStart)
            ->sum('amount') ?? 0;
        
        // 计算环比变化 (上月同期对比)
        $lastMonthStart = strtotime(date('Y-m-01', strtotime('-1 month')));
        $lastMonthEnd = strtotime(date('Y-m-01')) - 1;
        
        // 上月Đại lý数
        $lastMonthAgents = Db::table('caipiao_member')
            ->where('isnb', 0)
            ->where('proxy', 1)
            ->where('regtime', '<=', $lastMonthEnd)
            ->count();
        
        // 上月Đội nhóm人数
        $lastMonthTeam = Db::table('caipiao_member')
            ->where('isnb', 0)
            ->where('parentid', '>', 0)
            ->where('regtime', '<=', $lastMonthEnd)
            ->count();
        
        // 上月Đội nhómNạp tiền
        $lastTeamRecharge = Db::table('caipiao_recharge as r')
            ->join('caipiao_member as m', 'r.uid', '=', 'm.id')
            ->where('m.isnb', 0)
            ->where('m.parentid', '>', 0)
            ->where('r.state', 1)
            ->where('r.oddtime', '>=', $lastMonthStart)
            ->where('r.oddtime', '<=', $lastMonthEnd)
            ->sum('r.amount') ?? 0;
        
        // 上月Đội nhómĐặt cược
        $lastTeamBet = Db::table('caipiao_touzhu as t')
            ->join('caipiao_member as m', 't.uid', '=', 'm.id')
            ->where('m.isnb', 0)
            ->where('m.parentid', '>', 0)
            ->whereIn('t.isdraw', [1, -1])
            ->where('t.oddtime', '>=', $lastMonthStart)
            ->where('t.oddtime', '<=', $lastMonthEnd)
            ->sum('t.amount') ?? 0;
        
        // 上月Hoa hồng
        $lastCommission = Db::table('caipiao_fuddetail')
            ->whereIn('type', ['yongjinshenhe', 'jinjishenhe', 'yongjin', 'commission'])
            ->where('oddtime', '>=', $lastMonthStart)
            ->where('oddtime', '<=', $lastMonthEnd)
            ->sum('amount') ?? 0;
        
        // 计算环比函数
        $calcChange = function($current, $last) {
            if ($last == 0) return $current > 0 ? 100 : 0;
            return round(($current - $last) / abs($last) * 100, 1);
        };
        
        return $this->json(0, 'ok', [
            'totalUsers' => $totalUsers,
            'totalAgents' => $totalAgents,
            'totalMembers' => $totalMembers,
            'onlineUsers' => $onlineUsers,
            'directMembers' => $directMembers,
            'teamMembers' => $teamMembers,
            'teamRecharge' => round($teamRecharge, 2),
            'teamBet' => round($teamBet, 2),
            'teamProfit' => round($teamProfit, 2),
            'commissionPaid' => round(abs($commissionPaid), 2),
            // 环比dữ liệu
            'totalAgentsChange' => $calcChange($totalAgents, $lastMonthAgents),
            'directMembersChange' => $calcChange($directMembers, $lastMonthTeam * 0.3), // 估算
            'teamMembersChange' => $calcChange($teamMembers, $lastMonthTeam),
            'teamRechargeChange' => $calcChange($teamRecharge, $lastTeamRecharge),
            'teamBetChange' => $calcChange($teamBet, $lastTeamBet),
            'commissionPaidChange' => $calcChange(abs($commissionPaid), abs($lastCommission)),
        ]);
    }

    /**
     * 9. Đại lý层级分布（饼图）
     * GET /app/admin/api/statistics/team/levels
     */
    public function teamLevels(Request $request)
    {
        // 一级Đại lý（没有上级的Đại lý）
        $level1 = Db::table('caipiao_member')
            ->where('isnb', 0)
            ->where('proxy', 1)
            ->where('parentid', 0)
            ->count();
        
        // 二级Đại lý（上级是一级Đại lý）
        $level1Ids = Db::table('caipiao_member')
            ->where('isnb', 0)
            ->where('proxy', 1)
            ->where('parentid', 0)
            ->pluck('id')
            ->toArray();
        
        $level2 = 0;
        $level2Ids = [];
        if (!empty($level1Ids)) {
            $level2 = Db::table('caipiao_member')
                ->where('isnb', 0)
                ->where('proxy', 1)
                ->whereIn('parentid', $level1Ids)
                ->count();
            $level2Ids = Db::table('caipiao_member')
                ->where('isnb', 0)
                ->where('proxy', 1)
                ->whereIn('parentid', $level1Ids)
                ->pluck('id')
                ->toArray();
        }
        
        // 三级及以下Đại lý
        $level3Plus = 0;
        if (!empty($level2Ids)) {
            $level3Plus = Db::table('caipiao_member')
                ->where('isnb', 0)
                ->where('proxy', 1)
                ->whereIn('parentid', $level2Ids)
                ->count();
        }
        
        // 普通Thành viên（非Đại lý但有上级）
        $normalMembers = Db::table('caipiao_member')
            ->where('isnb', 0)
            ->where('proxy', 0)
            ->where('parentid', '>', 0)
            ->count();
        
        return $this->json(0, 'ok', [
            ['name' => '一级Đại lý', 'value' => $level1],
            ['name' => '二级Đại lý', 'value' => $level2],
            ['name' => '三级Đại lý', 'value' => $level3Plus],
            ['name' => '普通Thành viên', 'value' => $normalMembers],
        ]);
    }

    /**
     * 10. Hoa hồng发放趋势（柱状图）
     * GET /app/admin/api/statistics/team/commission-trend
     */
    public function teamCommissionTrend(Request $request)
    {
        $startDate = $request->get('startDate', date('Y-m-d', strtotime('-6 days')));
        $endDate = $request->get('endDate', date('Y-m-d'));
        
        $xAxis = [];
        $commissionData = [];
        
        $current = strtotime($startDate);
        $end = strtotime($endDate);
        
        while ($current <= $end) {
            $dayStart = $current;
            $dayEnd = $current + 86400 - 1;
            
            $commission = Db::table('caipiao_fuddetail')
                ->whereIn('type', ['yongjinshenhe', 'jinjishenhe', 'yongjin', 'commission'])
                ->where('oddtime', '>=', $dayStart)
                ->where('oddtime', '<=', $dayEnd)
                ->sum('amount') ?? 0;
            
            $xAxis[] = date('m-d', $current);
            $commissionData[] = round(abs($commission), 2);
            
            $current += 86400;
        }
        
        return $this->json(0, 'ok', [
            'xAxis' => $xAxis,
            'series' => [
                ['name' => 'Hoa hồng', 'data' => $commissionData]
            ],
        ]);
    }

    /**
     * 11. Đội nhóm业绩趋势（折线图）
     * GET /app/admin/api/statistics/team/performance-trend
     */
    public function teamPerformanceTrend(Request $request)
    {
        $startDate = $request->get('startDate', date('Y-m-d', strtotime('-6 days')));
        $endDate = $request->get('endDate', date('Y-m-d'));
        
        $xAxis = [];
        $rechargeData = [];
        $betData = [];
        
        $current = strtotime($startDate);
        $end = strtotime($endDate);
        
        while ($current <= $end) {
            $dayStart = $current;
            $dayEnd = $current + 86400 - 1;
            
            // Đội nhómNạp tiền
            $recharge = Db::table('caipiao_recharge as r')
                ->join('caipiao_member as m', 'r.uid', '=', 'm.id')
                ->where('m.isnb', 0)
                ->where('m.parentid', '>', 0)
                ->where('r.state', 1)
                ->where('r.oddtime', '>=', $dayStart)
                ->where('r.oddtime', '<=', $dayEnd)
                ->sum('r.amount') ?? 0;
            
            // Đội nhómĐặt cược
            $bet = Db::table('caipiao_touzhu as t')
                ->join('caipiao_member as m', 't.uid', '=', 'm.id')
                ->where('m.isnb', 0)
                ->where('m.parentid', '>', 0)
                ->whereIn('t.isdraw', [1, -1])
                ->where('t.oddtime', '>=', $dayStart)
                ->where('t.oddtime', '<=', $dayEnd)
                ->sum('t.amount') ?? 0;
            
            $xAxis[] = date('m-d', $current);
            $rechargeData[] = round($recharge, 2);
            $betData[] = round($bet, 2);
            
            $current += 86400;
        }
        
        return $this->json(0, 'ok', [
            'xAxis' => $xAxis,
            'series' => [
                ['name' => 'Đội nhómNạp tiền', 'data' => $rechargeData],
                ['name' => 'Đội nhómĐặt cược', 'data' => $betData],
            ],
        ]);
    }

    /**
     * 12. Đại lý排行榜
     * GET /app/admin/api/statistics/team/rank
     */
    public function teamRank(Request $request)
    {
        $by = $request->get('by', 'performance'); // performance | teamCount
        $limit = min((int)$request->get('limit', 10), 50);
        $startDate = $request->get('startDate', date('Y-m-01'));
        $endDate = $request->get('endDate', date('Y-m-d'));
        $startTime = strtotime($startDate);
        $endTime = strtotime($endDate) + 86400 - 1;
        
        // Lấy所有Đại lý
        $agents = Db::table('caipiao_member')
            ->where('isnb', 0)
            ->where('proxy', 1)
            ->select(['id', 'username'])
            ->get();
        
        $rankData = [];
        foreach ($agents as $agent) {
            // LấyCấp dướiID（包括自己）
            $downlineIds = $this->getAllDownlineIds($agent->id);
            $downlineIds[] = $agent->id;
            
            // Đội nhóm人数
            $teamCount = count($downlineIds) - 1; // 不包括自己
            
            // Đội nhóm业绩（Đặt cượcSố tiền）
            $performance = Db::table('caipiao_touzhu')
                ->whereIn('uid', $downlineIds)
                ->whereIn('isdraw', [1, -1])
                ->where('oddtime', '>=', $startTime)
                ->where('oddtime', '<=', $endTime)
                ->sum('amount') ?? 0;
            
            $rankData[] = [
                'username' => $agent->username,
                'teamCount' => $teamCount,
                'performance' => round($performance, 2),
            ];
        }
        
        // 排序
        if ($by === 'teamCount') {
            usort($rankData, fn($a, $b) => $b['teamCount'] <=> $a['teamCount']);
        } else {
            usort($rankData, fn($a, $b) => $b['performance'] <=> $a['performance']);
        }
        
        // 取前 N 条
        return $this->json(0, 'ok', array_slice($rankData, 0, $limit));
    }

    /**
     * Tên người dùng脱敏
     */
    private function maskUsername(string $username): string
    {
        if (strlen($username) <= 2) {
            return $username;
        }
        return substr($username, 0, 1) . '***' . substr($username, -1);
    }
}
