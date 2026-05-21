<?php

namespace app\controller\api;

use support\Request;
use support\Db;

class AgentController
{
    
    public function info(Request $request)
    {
        $userId = $request->userId ?? 0;
        
        if (!$userId) {
            return json(['code' => 401, 'message' => '未Đăng nhập', 'data' => null]);
        }
        
        try {
            $user = Db::table('caipiao_member')
                ->where('id', $userId)
                ->first();
            
            if (!$user) {
                return json(['code' => 404, 'message' => 'Người dùng không tồn tại', 'data' => null]);
            }
            
            
            $agentMode = Db::table('caipiao_setting')
                ->where('name', 'agent_mode')
                ->value('value') ?? '一级净盈利';
            
            
            $settlementDay = Db::table('caipiao_setting')
                ->where('name', 'agent_settlement_day')
                ->value('value') ?? date('Y/m/t');
            
            return json([
                'code' => 0,
                'message' => 'success',
                'data' => [
                    'agentId' => (string)$user->id,
                    'agentMode' => $agentMode,
                    'auditMultiple' => number_format($user->audit_multiple ?? 0, 2),
                    'settlementDate' => $settlementDay
                ]
            ]);
            
        } catch (\Exception $e) {
            \support\Log::error('LấyĐại lý信息Thất bại: ' . $e->getMessage());
            return json(['code' => 500, 'message' => 'Lấy dữ liệu thất bại', 'data' => null]);
        }
    }

    
    public function inviteInfo(Request $request)
    {
        $userId = $request->userId ?? 0;
        
        if (!$userId) {
            return json(['code' => 401, 'message' => '未Đăng nhập', 'data' => null]);
        }
        
        try {
            $user = Db::table('caipiao_member')
                ->where('id', $userId)
                ->first();
            
            if (!$user) {
                return json(['code' => 404, 'message' => 'Người dùng không tồn tại', 'data' => null]);
            }
            
            
            $domain = Db::table('caipiao_setting')
                ->where('name', 'site_domain')
                ->value('value') ?? $request->host();
            
            
            $secureCode = \app\service\InviteCodeService::encode($userId);
            $inviteCode = (string)$user->id; 
            
            
            $inviteLink = "https://{$domain}/?invite={$secureCode}";
            
            
            $qrcodeUrl = "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" . urlencode($inviteLink);
            
            return json([
                'code' => 0,
                'message' => 'success',
                'data' => [
                    'inviteCode' => $secureCode, 
                    'numericCode' => $inviteCode, 
                    'inviteLink' => $inviteLink,
                    'qrcodeUrl' => $qrcodeUrl
                ]
            ]);
            
        } catch (\Exception $e) {
            \support\Log::error('LấyMời信息Thất bại: ' . $e->getMessage());
            return json(['code' => 500, 'message' => 'Lấy dữ liệu thất bại', 'data' => null]);
        }
    }

    
    public function overview(Request $request)
    {
        $userId = $request->userId ?? 0;
        
        if (!$userId) {
            return json(['code' => 401, 'message' => '未Đăng nhập', 'data' => null]);
        }
        
        try {
            
            $totalInvited = Db::table('caipiao_member')
                ->where('parentid', $userId)
                ->count();
            
            
            $totalEarned = Db::table('caipiao_agent_commission')
                ->where('agent_id', $userId)
                ->sum('amount') ?? 0;
            
            
            $claimed = Db::table('caipiao_agent_commission')
                ->where('agent_id', $userId)
                ->where('status', 2) 
                ->sum('amount') ?? 0;
            
            
            $pending = Db::table('caipiao_agent_commission')
                ->where('agent_id', $userId)
                ->where('status', 1) 
                ->sum('amount') ?? 0;
            
            
            $yesterday = strtotime(date('Y-m-d', strtotime('-1 day')));
            $yesterdayEnd = $yesterday + 86399;
            
            $yesterdayPerformance = Db::table('caipiao_touzhu as t')
                ->join('caipiao_member as m', 't.uid', '=', 'm.id')
                ->where('m.parentid', $userId)
                ->where('t.oddtime', '>=', $yesterday)
                ->where('t.oddtime', '<=', $yesterdayEnd)
                ->sum('t.amount') ?? 0;
            
            
            $totalCommission = $totalEarned;
            
            
            $nextMonth = strtotime('first day of next month');
            $diff = $nextMonth - time();
            $days = floor($diff / 86400);
            $hours = floor(($diff % 86400) / 3600);
            $minutes = floor(($diff % 3600) / 60);
            $seconds = $diff % 60;
            $nextSettlement = "{$days}天 " . sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
            
            return json([
                'code' => 0,
                'message' => 'success',
                'data' => [
                    'totalEarned' => number_format($totalEarned, 2, '.', ''),
                    'totalInvited' => $totalInvited,
                    'nextSettlement' => $nextSettlement,
                    'yesterdayPerformance' => number_format($yesterdayPerformance, 2, '.', ''),
                    'totalCommission' => number_format($totalCommission, 2, '.', ''),
                    'claimed' => number_format($claimed, 2, '.', ''),
                    'pending' => number_format($pending, 2, '.', '')
                ]
            ]);
            
        } catch (\Exception $e) {
            \support\Log::error('Lấy概览dữ liệuThất bại: ' . $e->getMessage());
            return json(['code' => 500, 'message' => 'Lấy dữ liệu thất bại', 'data' => null]);
        }
    }

    
    public function myStats(Request $request)
    {
        $userId = $request->userId ?? 0;
        $dateType = $request->get('dateType', 'today');
        
        if (!$userId) {
            return json(['code' => 401, 'message' => '未Đăng nhập', 'data' => null]);
        }
        
        try {
            
            list($startTime, $endTime) = $this->getDateRange($dateType);
            
            
            $directIds = Db::table('caipiao_member')
                ->where('parentid', $userId)
                ->pluck('id')
                ->toArray();
            
            
            $direct = $this->getSubordinateStats($directIds, $startTime, $endTime);
            
            
            $directPerformance = 0;
            if (!empty($directIds)) {
                $directPerformance = Db::table('caipiao_touzhu')
                    ->whereIn('uid', $directIds)
                    ->where('oddtime', '>=', $startTime)
                    ->where('oddtime', '<=', $endTime)
                    ->sum('amount') ?? 0;
            }
            $direct['directPerformance'] = $directPerformance;
            
            
            $allSubIds = $this->getAllSubordinateIds($userId);
            $otherIds = array_diff($allSubIds, $directIds); 
            
            
            
            $commissionData = Db::table('caipiao_agent_commission')
                ->where('agent_id', $userId)
                ->where('created_at', '>=', $startTime)
                ->where('created_at', '<=', $endTime)
                ->selectRaw('
                    SUM(CASE WHEN type = 1 THEN amount ELSE 0 END) as direct_commission,
                    SUM(CASE WHEN type = 2 THEN amount ELSE 0 END) as other_commission,
                    SUM(amount) as total_commission,
                    SUM(CASE WHEN status = 2 THEN amount ELSE 0 END) as earned_commission
                ')
                ->first();
            
            $revenue = [
                'directCommission' => number_format($commissionData->direct_commission ?? 0, 2, '.', ''),
                'otherCommission' => number_format($commissionData->other_commission ?? 0, 2, '.', ''),
                'totalCommission' => number_format($commissionData->total_commission ?? 0, 2, '.', ''),
                'earnedCommission' => number_format($commissionData->earned_commission ?? 0, 2, '.', ''),
                'promoReward' => '0.00',
                'agentReward' => '0.00'
            ];
            
            
            $directCount = count($directIds);
            $otherCount = count($otherIds);
            $totalCount = $directCount + $otherCount;
            
            
            $directPerformanceTotal = 0;
            if (!empty($directIds)) {
                $directPerformanceTotal = Db::table('caipiao_touzhu')
                    ->whereIn('uid', $directIds)
                    ->sum('amount') ?? 0;
            }
            
            
            $otherPerformanceTotal = 0;
            if (!empty($otherIds)) {
                $otherPerformanceTotal = Db::table('caipiao_touzhu')
                    ->whereIn('uid', $otherIds)
                    ->sum('amount') ?? 0;
            }
            
            
            $directRechargeTotal = 0;
            if (!empty($directIds)) {
                $directRechargeTotal = Db::table('caipiao_recharge')
                    ->whereIn('uid', $directIds)
                    ->where('state', 1)
                    ->sum('amount') ?? 0;
            }
            
            
            $directWithdrawTotal = 0;
            if (!empty($directIds)) {
                $directWithdrawTotal = Db::table('caipiao_withdraw')
                    ->whereIn('uid', $directIds)
                    ->where('state', 1)
                    ->sum('amount') ?? 0;
            }
            
            
            $directClaimTotal = 0;
            if (!empty($directIds)) {
                $directClaimTotal = Db::table('caipiao_fuddetail')
                    ->whereIn('uid', $directIds)
                    ->whereIn('type', ['Hoạt động奖励', 'Thăng cấp奖励', '签到奖励', 'Hoàn trả'])
                    ->sum('amount') ?? 0;
            }
            
            
            $directValidBetTotal = 0;
            if (!empty($directIds)) {
                $directValidBetTotal = Db::table('caipiao_touzhu')
                    ->whereIn('uid', $directIds)
                    ->where('isdraw', '!=', 0)
                    ->sum('amount') ?? 0;
            }
            
            
            $directWinLossTotal = 0;
            if (!empty($directIds)) {
                $betAmount = Db::table('caipiao_touzhu')
                    ->whereIn('uid', $directIds)
                    ->where('isdraw', '!=', 0)
                    ->sum('amount') ?? 0;
                $winAmount = Db::table('caipiao_touzhu')
                    ->whereIn('uid', $directIds)
                    ->where('isdraw', 1)
                    ->sum('okamount') ?? 0;
                $directWinLossTotal = $betAmount - $winAmount; 
            }
            
            $all = [
                'totalCount' => $totalCount,
                'directCount' => $directCount,
                'otherCount' => $otherCount,
                'directPerformance' => number_format($directPerformanceTotal, 2, '.', ''),
                'otherPerformance' => number_format($otherPerformanceTotal, 2, '.', ''),
                'totalPerformance' => number_format($directPerformanceTotal + $otherPerformanceTotal, 2, '.', ''),
                'directRechargeTotal' => number_format($directRechargeTotal, 2, '.', ''),
                'directWithdrawTotal' => number_format($directWithdrawTotal, 2, '.', ''),
                'directClaimTotal' => number_format($directClaimTotal, 2, '.', ''),
                'directValidBetTotal' => number_format($directValidBetTotal, 2, '.', ''),
                'directWinLossTotal' => number_format($directWinLossTotal, 2, '.', '')
            ];
            
            
            $allCommission = Db::table('caipiao_agent_commission')
                ->where('agent_id', $userId)
                ->selectRaw('
                    SUM(CASE WHEN type = 1 THEN amount ELSE 0 END) as direct_commission,
                    SUM(CASE WHEN type = 2 THEN amount ELSE 0 END) as other_commission,
                    SUM(amount) as total_commission,
                    SUM(CASE WHEN status = 1 THEN amount ELSE 0 END) as pending,
                    SUM(CASE WHEN status = 2 THEN amount ELSE 0 END) as claimed
                ')
                ->first();
            
            $totalRevenue = [
                'directCommission' => number_format($allCommission->direct_commission ?? 0, 2, '.', ''),
                'otherCommission' => number_format($allCommission->other_commission ?? 0, 2, '.', ''),
                'totalCommission' => number_format($allCommission->total_commission ?? 0, 2, '.', ''),
                'pending' => number_format($allCommission->pending ?? 0, 2, '.', ''),
                'claimed' => number_format($allCommission->claimed ?? 0, 2, '.', ''),
                'cumulativeCommission' => number_format($allCommission->total_commission ?? 0, 2, '.', ''),
                'promoRewardTotal' => '0.00', 
                'agentRewardTotal' => '0.00'  
            ];
            
            return json([
                'code' => 0,
                'message' => 'success',
                'data' => [
                    'direct' => $direct,
                    'revenue' => $revenue,
                    'all' => $all,
                    'totalRevenue' => $totalRevenue
                ]
            ]);
            
        } catch (\Exception $e) {
            \support\Log::error('Lấy我的dữ liệuThất bại: ' . $e->getMessage());
            return json(['code' => 500, 'message' => 'Lấy dữ liệu thất bại', 'data' => null]);
        }
    }

    
    public function performance(Request $request)
    {
        $userId = $request->userId ?? 0;
        $dateType = $request->get('dateType', 'today');
        $page = max(1, (int)$request->get('page', 1));
        $pageSize = min(50, max(1, (int)$request->get('pageSize', 20)));
        
        if (!$userId) {
            return json(['code' => 401, 'message' => '未Đăng nhập', 'data' => null]);
        }
        
        try {
            list($startTime, $endTime) = $this->getDateRange($dateType);
            
            
            $directIds = Db::table('caipiao_member')
                ->where('parentid', $userId)
                ->pluck('id')
                ->toArray();
            
            if (empty($directIds)) {
                return json([
                    'code' => 0,
                    'message' => 'success',
                    'data' => ['list' => [], 'total' => 0]
                ]);
            }
            
            
            $list = Db::table('caipiao_touzhu')
                ->whereIn('uid', $directIds)
                ->where('oddtime', '>=', $startTime)
                ->where('oddtime', '<=', $endTime)
                ->selectRaw('
                    FROM_UNIXTIME(oddtime, "%Y-%m-%d") as date,
                    SUM(amount) as amount,
                    SUM(CASE WHEN isdraw != 0 THEN amount ELSE 0 END) as validBet,
                    SUM(CASE WHEN isdraw = 1 THEN okamount - amount ELSE -amount END) as winLoss
                ')
                ->groupByRaw('FROM_UNIXTIME(oddtime, "%Y-%m-%d")')
                ->orderByRaw('FROM_UNIXTIME(oddtime, "%Y-%m-%d") DESC')
                ->offset(($page - 1) * $pageSize)
                ->limit($pageSize)
                ->get();
            
            $result = [];
            foreach ($list as $item) {
                $result[] = [
                    'id' => md5($item->date),
                    'date' => $item->date,
                    'amount' => number_format($item->amount, 2, '.', ''),
                    'validBet' => number_format($item->validBet, 2, '.', ''),
                    'winLoss' => number_format($item->winLoss, 2, '.', '')
                ];
            }
            
            return json([
                'code' => 0,
                'message' => 'success',
                'data' => ['list' => $result]
            ]);
            
        } catch (\Exception $e) {
            \support\Log::error('Lấy业绩Thất bại: ' . $e->getMessage());
            return json(['code' => 500, 'message' => 'Lấy dữ liệu thất bại', 'data' => null]);
        }
    }

    
    public function commission(Request $request)
    {
        $userId = $request->userId ?? 0;
        $dateType = $request->get('dateType', 'today');
        $page = max(1, (int)$request->get('page', 1));
        $pageSize = min(50, max(1, (int)$request->get('pageSize', 20)));
        
        if (!$userId) {
            return json(['code' => 401, 'message' => '未Đăng nhập', 'data' => null]);
        }
        
        try {
            list($startTime, $endTime) = $this->getDateRange($dateType);
            
            
            $nextMonth = strtotime('first day of next month');
            $diff = $nextMonth - time();
            $days = floor($diff / 86400);
            $hours = floor(($diff % 86400) / 3600);
            $minutes = floor(($diff % 3600) / 60);
            $seconds = $diff % 60;
            $countdown = "{$days}天 " . sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
            
            $list = Db::table('caipiao_agent_commission')
                ->where('agent_id', $userId)
                ->where('created_at', '>=', $startTime)
                ->where('created_at', '<=', $endTime)
                ->orderBy('created_at', 'desc')
                ->offset(($page - 1) * $pageSize)
                ->limit($pageSize)
                ->get();
            
            $result = [];
            foreach ($list as $item) {
                $result[] = [
                    'id' => $item->id,
                    'date' => date('Y-m-d', $item->created_at),
                    'performance' => number_format($item->performance ?? 0, 2, '.', ''),
                    'rate' => $item->rate ?? 0,
                    'commission' => number_format($item->amount, 2, '.', ''),
                    'status' => $item->status == 2 ? 'claimed' : 'pending',
                    'statusText' => $item->status == 2 ? '已领取' : '待领取'
                ];
            }
            
            return json([
                'code' => 0,
                'message' => 'success',
                'data' => [
                    'countdown' => $countdown,
                    'list' => $result
                ]
            ]);
            
        } catch (\Exception $e) {
            \support\Log::error('LấyHoa hồng列表Thất bại: ' . $e->getMessage());
            return json(['code' => 500, 'message' => 'Lấy dữ liệu thất bại', 'data' => null]);
        }
    }

    
    public function claimCommission(Request $request)
    {
        $userId = $request->userId ?? 0;
        
        if (!$userId) {
            return json(['code' => 401, 'message' => '未Đăng nhập', 'data' => null]);
        }
        
        try {
            
            $antiCheatResult = \app\service\AntiCheatService::checkClaimAllowed($userId);
            if (!$antiCheatResult['allowed']) {
                return json([
                    'code' => 403, 
                    'message' => $antiCheatResult['reason'] ?? '领取受限', 
                    'data' => null
                ]);
            }
            
            Db::beginTransaction();
            
            
            $pendingRecords = Db::table('caipiao_agent_commission')
                ->where('agent_id', $userId)
                ->where('status', 1)
                ->lockForUpdate()
                ->get();
            
            if ($pendingRecords->isEmpty()) {
                Db::rollBack();
                return json(['code' => 400, 'message' => '暂无可领取Hoa hồng', 'data' => null]);
            }
            
            
            $positiveAmount = $pendingRecords->where('amount', '>', 0)->sum('amount');
            $negativeAmount = abs($pendingRecords->where('amount', '<', 0)->sum('amount'));
            $pending = max(0, $positiveAmount - $negativeAmount);
            $recordIds = $pendingRecords->pluck('id')->toArray();
            
            if ($pending <= 0) {
                Db::rollBack();
                $msg = $negativeAmount > 0 
                    ? "负盈利{$negativeAmount}元待抵扣，暂无可领取Hoa hồng" 
                    : '暂无可领取Hoa hồng';
                return json(['code' => 400, 'message' => $msg, 'data' => null]);
            }
            
            
            if (!\app\service\AgentCommissionService::checkAuditMultiple($userId, $pending)) {
                $auditMultiple = Db::table('caipiao_setting')
                    ->where('name', 'agent_audit_multiple')
                    ->value('value') ?? 1;
                $requiredBet = $pending * $auditMultiple;
                Db::rollBack();
                return json([
                    'code' => 400, 
                    'message' => "需完成{$requiredBet}元Đặt cượcVòng cược后才能领取", 
                    'data' => null
                ]);
            }
            
            
            Db::table('caipiao_agent_commission')
                ->whereIn('id', $recordIds)
                ->where('status', 1)  
                ->update([
                    'status' => 2,
                    'claimed_at' => time()
                ]);
            
            
            $user = Db::table('caipiao_member')
                ->where('id', $userId)
                ->lockForUpdate()
                ->first();
            
            if (!$user) {
                Db::rollBack();
                return json(['code' => 400, 'message' => 'Người dùng không tồn tại', 'data' => null]);
            }
            
            $oldBalance = $user->balance ?? 0;
            $newBalance = $oldBalance + $pending;
            
            
            Db::table('caipiao_member')
                ->where('id', $userId)
                ->update(['balance' => $newBalance]);
            
            
            $trano = 'AC' . date('YmdHis') . $userId . rand(100, 999);
            Db::table('caipiao_fuddetail')->insert([
                'trano' => $trano,
                'uid' => $userId,
                'username' => $user->username ?? '',
                'type' => 'Đại lýHoa hồng',
                'typename' => 'Đại lýHoa hồng领取',
                'amount' => $pending,
                'amountbefor' => $oldBalance,
                'amountafter' => $newBalance,
                'oddtime' => time(),
                'remark' => '领取Đại lýHoa hồng，共' . count($recordIds) . '笔'
            ]);
            
            Db::commit();
            
            \support\Log::info("代理佣金领取成功: userId={$userId}, amount={$pending}, records=" . implode(',', $recordIds));
            
            return json([
                'code' => 0,
                'message' => '领取Thành công',
                'data' => ['amount' => number_format($pending, 2, '.', '')]
            ]);
            
        } catch (\Exception $e) {
            Db::rollBack();
            \support\Log::error('领取Hoa hồngThất bại: ' . $e->getMessage() . ' | userId=' . $userId);
            return json(['code' => 500, 'message' => '领取Thất bại', 'data' => null]);
        }
    }

    
    public function subordinateList(Request $request)
    {
        $userId = $request->userId ?? 0;
        $dateType = $request->get('dateType', 'today');
        $sortBy = $request->get('sortBy', 'loginDate');
        $memberId = $request->get('memberId', '');
        $page = max(1, (int)$request->get('page', 1));
        $pageSize = min(50, max(1, (int)$request->get('pageSize', 20)));
        
        if (!$userId) {
            return json(['code' => 401, 'message' => '未Đăng nhập', 'data' => null]);
        }
        
        try {
            list($startTime, $endTime) = $this->getDateRange($dateType);
            
            
            $query = Db::table('caipiao_member as m')
                ->where('m.parentid', $userId)
                ->selectRaw('
                    m.id,
                    m.username,
                    m.regtime,
                    m.logintime,
                    COALESCE((SELECT SUM(amount) FROM caipiao_recharge WHERE uid = m.id AND state = 1), 0) as recharge_amount,
                    COALESCE((SELECT SUM(amount) FROM caipiao_touzhu WHERE uid = m.id AND isdraw != 0), 0) as valid_bet
                ');
            
            if (!empty($memberId)) {
                $query->where('m.id', $memberId);
            }
            
            
            switch ($sortBy) {
                case 'registerDate':
                    $query->orderBy('m.regtime', 'desc');
                    break;
                case 'rechargeAmount':
                    $query->orderBy('recharge_amount', 'desc');
                    break;
                default:
                    $query->orderBy('m.logintime', 'desc');
            }
            
            $list = $query->offset(($page - 1) * $pageSize)
                ->limit($pageSize)
                ->get();
            
            $result = [];
            foreach ($list as $item) {
                $result[] = [
                    'id' => $item->id,
                    'memberId' => $item->username,
                    'registerTime' => date('Y-m-d', $item->regtime),
                    'rechargeAmount' => number_format($item->recharge_amount, 2, '.', ''),
                    'validBet' => number_format($item->valid_bet, 2, '.', ''),
                    'lastLogin' => $item->logintime ? date('Y-m-d H:i', $item->logintime) : '从未Đăng nhập'
                ];
            }
            
            return json([
                'code' => 0,
                'message' => 'success',
                'data' => ['list' => $result]
            ]);
            
        } catch (\Exception $e) {
            \support\Log::error('LấyCấp dưới列表Thất bại: ' . $e->getMessage());
            return json(['code' => 500, 'message' => 'Lấy dữ liệu thất bại', 'data' => null]);
        }
    }

    
    public function subordinateBets(Request $request)
    {
        $userId = $request->userId ?? 0;
        $dateType = $request->get('dateType', 'today');
        $sortBy = $request->get('sortBy', 'validBet');
        $memberId = $request->get('memberId', '');
        $page = max(1, (int)$request->get('page', 1));
        $pageSize = min(50, max(1, (int)$request->get('pageSize', 20)));
        
        if (!$userId) {
            return json(['code' => 401, 'message' => '未Đăng nhập', 'data' => null]);
        }
        
        try {
            list($startTime, $endTime) = $this->getDateRange($dateType);
            
            
            $subQuery = Db::table('caipiao_member')
                ->where('parentid', $userId);
            
            if (!empty($memberId)) {
                $subQuery->where('id', $memberId);
            }
            
            $subIds = $subQuery->pluck('id')->toArray();
            
            if (empty($subIds)) {
                return json(['code' => 0, 'message' => 'success', 'data' => ['list' => []]]);
            }
            
            
            $stats = Db::table('caipiao_touzhu')
                ->join('caipiao_member', 'caipiao_touzhu.uid', '=', 'caipiao_member.id')
                ->whereIn('caipiao_touzhu.uid', $subIds)
                ->where('caipiao_touzhu.oddtime', '>=', $startTime)
                ->where('caipiao_touzhu.oddtime', '<=', $endTime)
                ->selectRaw('
                    caipiao_member.id,
                    caipiao_member.username,
                    SUM(caipiao_touzhu.amount) as validBet,
                    COUNT(*) as betCount,
                    SUM(CASE WHEN caipiao_touzhu.isdraw = 1 THEN caipiao_touzhu.okamount - caipiao_touzhu.amount ELSE -caipiao_touzhu.amount END) as winLoss
                ')
                ->groupBy('caipiao_member.id', 'caipiao_member.username');
            
            
            switch ($sortBy) {
                case 'betCount':
                    $stats->orderBy('betCount', 'desc');
                    break;
                case 'winLoss':
                    $stats->orderBy('winLoss', 'desc');
                    break;
                default:
                    $stats->orderBy('validBet', 'desc');
            }
            
            $list = $stats->offset(($page - 1) * $pageSize)
                ->limit($pageSize)
                ->get();
            
            $result = [];
            foreach ($list as $item) {
                $result[] = [
                    'id' => $item->id,
                    'memberId' => $item->username,
                    'validBet' => number_format($item->validBet, 2, '.', ''),
                    'betCount' => $item->betCount,
                    'winLoss' => number_format($item->winLoss, 2, '.', '')
                ];
            }
            
            return json([
                'code' => 0,
                'message' => 'success',
                'data' => ['list' => $result]
            ]);
            
        } catch (\Exception $e) {
            \support\Log::error('LấyCấp dướiĐặt cượcThất bại: ' . $e->getMessage());
            return json(['code' => 500, 'message' => 'Lấy dữ liệu thất bại', 'data' => null]);
        }
    }

    
    public function subordinateFinance(Request $request)
    {
        $userId = $request->userId ?? 0;
        $dateType = $request->get('dateType', 'today');
        $sortBy = $request->get('sortBy', 'rechargeAmount');
        $memberId = $request->get('memberId', '');
        $page = max(1, (int)$request->get('page', 1));
        $pageSize = min(50, max(1, (int)$request->get('pageSize', 20)));
        
        if (!$userId) {
            return json(['code' => 401, 'message' => '未Đăng nhập', 'data' => null]);
        }
        
        try {
            list($startTime, $endTime) = $this->getDateRange($dateType);
            
            
            $query = Db::table('caipiao_member as m')
                ->where('m.parentid', $userId)
                ->selectRaw("
                    m.id,
                    m.username,
                    COALESCE((SELECT SUM(amount) FROM caipiao_recharge WHERE uid = m.id AND state = 1 AND oddtime >= ? AND oddtime <= ?), 0) as recharge_amount,
                    COALESCE((SELECT COUNT(*) FROM caipiao_recharge WHERE uid = m.id AND state = 1 AND oddtime >= ? AND oddtime <= ?), 0) as recharge_count,
                    COALESCE((SELECT SUM(amount) FROM caipiao_withdraw WHERE uid = m.id AND state = 1 AND oddtime >= ? AND oddtime <= ?), 0) as withdraw_amount,
                    COALESCE((SELECT COUNT(*) FROM caipiao_withdraw WHERE uid = m.id AND state = 1 AND oddtime >= ? AND oddtime <= ?), 0) as withdraw_count
                ", [$startTime, $endTime, $startTime, $endTime, $startTime, $endTime, $startTime, $endTime]);
            
            if (!empty($memberId)) {
                $query->where('m.id', $memberId);
            }
            
            
                switch ($sortBy) {
                    case 'withdrawAmount':
                    $query->orderBy('withdraw_amount', 'desc');
                    break;
                    case 'rechargeCount':
                    $query->orderBy('recharge_count', 'desc');
                    break;
                    default:
                    $query->orderBy('recharge_amount', 'desc');
            }
            
            $list = $query->offset(($page - 1) * $pageSize)
                ->limit($pageSize)
                ->get();
            
            $result = [];
            foreach ($list as $item) {
                $result[] = [
                    'id' => $item->id,
                    'memberId' => $item->username,
                    'rechargeAmount' => number_format($item->recharge_amount, 2, '.', ''),
                    'rechargeCount' => (int)$item->recharge_count,
                    'withdrawAmount' => number_format($item->withdraw_amount, 2, '.', ''),
                    'withdrawCount' => (int)$item->withdraw_count
                ];
            }
            
            return json([
                'code' => 0,
                'message' => 'success',
                'data' => ['list' => $result]
            ]);
            
        } catch (\Exception $e) {
            \support\Log::error('LấyCấp dưới财务Thất bại: ' . $e->getMessage());
            return json(['code' => 500, 'message' => 'Lấy dữ liệu thất bại', 'data' => null]);
        }
    }

    
    public function subordinateClaims(Request $request)
    {
        $userId = $request->userId ?? 0;
        $dateType = $request->get('dateType', 'today');
        $sortBy = $request->get('sortBy', 'totalClaim');
        $memberId = $request->get('memberId', '');
        $page = max(1, (int)$request->get('page', 1));
        $pageSize = min(50, max(1, (int)$request->get('pageSize', 20)));
        
        if (!$userId) {
            return json(['code' => 401, 'message' => '未Đăng nhập', 'data' => null]);
        }
        
        try {
            list($startTime, $endTime) = $this->getDateRange($dateType);
            
            $subQuery = Db::table('caipiao_member')
                ->where('parentid', $userId);
            
            if (!empty($memberId)) {
                $subQuery->where('id', $memberId);
            }
            
            $subIds = $subQuery->pluck('id')->toArray();
            
            if (empty($subIds)) {
                return json(['code' => 0, 'message' => 'success', 'data' => ['list' => []]]);
            }
            
            
            $list = Db::table('caipiao_fuddetail')
                ->join('caipiao_member', 'caipiao_fuddetail.uid', '=', 'caipiao_member.id')
                ->whereIn('caipiao_fuddetail.uid', $subIds)
                ->where('caipiao_fuddetail.oddtime', '>=', $startTime)
                ->where('caipiao_fuddetail.oddtime', '<=', $endTime)
                ->whereIn('caipiao_fuddetail.type', ['Hoạt động奖励', 'Thăng cấp奖励', '签到奖励', 'Hoàn trả'])
                ->selectRaw('
                    caipiao_member.id,
                    caipiao_member.username,
                    caipiao_fuddetail.type as claimType,
                    caipiao_fuddetail.amount as claimAmount,
                    caipiao_fuddetail.oddtime as claimTime
                ')
                ->orderBy('caipiao_fuddetail.oddtime', 'desc')
                ->offset(($page - 1) * $pageSize)
                ->limit($pageSize)
                ->get();
            
            $result = [];
            foreach ($list as $item) {
                $result[] = [
                    'id' => $item->id . '_' . $item->claimTime,
                    'memberId' => $item->username,
                    'claimType' => $item->claimType,
                    'claimAmount' => number_format($item->claimAmount, 2, '.', ''),
                    'claimTime' => date('Y-m-d H:i', $item->claimTime)
                ];
            }
            
            return json([
                'code' => 0,
                'message' => 'success',
                'data' => ['list' => $result]
            ]);
            
        } catch (\Exception $e) {
            \support\Log::error('LấyCấp dưới领取lịch sử thất bại: ' . $e->getMessage());
            return json(['code' => 500, 'message' => 'Lấy dữ liệu thất bại', 'data' => null]);
        }
    }

    
    public function createAccount(Request $request)
    {
        $userId = $request->userId ?? 0;
        $username = trim($request->post('username', ''));
        $password = $request->post('password', '');
        
        if (!$userId) {
            return json(['code' => 401, 'message' => '未Đăng nhập', 'data' => null]);
        }
        
        try {
            
            if (empty($username) || strlen($username) < 4 || strlen($username) > 16) {
                return json(['code' => 400, 'message' => 'Tên người dùngcần từ 4-16 ký tự', 'data' => null]);
            }
            
            if (!preg_match('/^[a-zA-Z0-9]+$/', $username)) {
                return json(['code' => 400, 'message' => 'Tên người dùngchỉ được chứa chữ cái và số', 'data' => null]);
            }
            
            
            if (empty($password) || strlen($password) < 8 || strlen($password) > 16) {
                return json(['code' => 400, 'message' => 'Mật khẩu需8-16位', 'data' => null]);
            }
            
            if (!preg_match('/[a-zA-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
                return json(['code' => 400, 'message' => 'Mật khẩu需包含字母和数字', 'data' => null]);
            }
            
            
            $exists = Db::table('caipiao_member')
                ->where('username', $username)
                ->first();
            
            if ($exists) {
                return json(['code' => 400, 'message' => 'Tên người dùngđã tồn tại', 'data' => null]);
            }
            
            
            $agent = Db::table('caipiao_member')
                ->where('id', $userId)
                ->first();
            
            if (!$agent || $agent->proxy != 1) {
                return json(['code' => 403, 'message' => '无开户权限', 'data' => null]);
            }
            
            
            $ip = $request->getRealIp();
            $now = time();
            
            $newUserId = Db::table('caipiao_member')->insertGetId([
                'username' => $username,
                'password' => password_hash($password, PASSWORD_BCRYPT),
                'parentid' => $userId,
                'groupid' => 1,
                'nickname' => $username,
                'proxy' => 0,
                'balance' => 0,
                'regtime' => $now,
                'regip' => $ip,
                'source' => 'Đại lý开户',
                'islock' => 0
            ]);
            
            return json([
                'code' => 0,
                'message' => '开户Thành công',
                'data' => [
                    'userId' => $newUserId,
                    'username' => $username
                ]
            ]);
            
        } catch (\Exception $e) {
            \support\Log::error('直属开户Thất bại: ' . $e->getMessage());
            return json(['code' => 500, 'message' => '开户Thất bại', 'data' => null]);
        }
    }

    
    public function commissionRates(Request $request)
    {
        try {
            
            $rates = Db::table('caipiao_agent_rate')
                ->orderBy('effective_count', 'asc')
                ->get();
            
            if ($rates->isEmpty()) {
                
                $rates = [
                    ['effective_count' => 1, 'performance' => 100, 'commission' => 1, 'rate' => 1],
                    ['effective_count' => 2, 'performance' => 50000, 'commission' => 2500, 'rate' => 5],
                    ['effective_count' => 3, 'performance' => 100000, 'commission' => 10000, 'rate' => 10],
                    ['effective_count' => 4, 'performance' => 150000, 'commission' => 22500, 'rate' => 15],
                    ['effective_count' => 5, 'performance' => 200000, 'commission' => 40000, 'rate' => 20]
                ];
            } else {
                $rates = $rates->map(function($item) {
                    return [
                        'effectiveCount' => $item->effective_count,
                        'performance' => $item->performance,
                        'commission' => $item->commission,
                        'rate' => $item->rate
                    ];
                })->toArray();
            }
            
            return json([
                'code' => 0,
                'message' => 'success',
                'data' => ['list' => $rates]
            ]);
            
        } catch (\Exception $e) {
            \support\Log::error('Lấy返佣比例Thất bại: ' . $e->getMessage());
            
            return json([
                'code' => 0,
                'message' => 'success',
                'data' => [
                    'list' => [
                        ['effectiveCount' => 1, 'performance' => 100, 'commission' => 1, 'rate' => 1],
                        ['effectiveCount' => 2, 'performance' => 50000, 'commission' => 2500, 'rate' => 5],
                        ['effectiveCount' => 3, 'performance' => 100000, 'commission' => 10000, 'rate' => 10],
                        ['effectiveCount' => 4, 'performance' => 150000, 'commission' => 22500, 'rate' => 15],
                        ['effectiveCount' => 5, 'performance' => 200000, 'commission' => 40000, 'rate' => 20]
                    ]
                ]
            ]);
        }
    }

    
    public function calculateCommission(Request $request)
    {
        $effectiveCount = (int)$request->get('effectiveCount', 0);
        $directWinLoss = (float)$request->get('directWinLoss', 0);
        $claimBonus = (float)$request->get('claimBonus', 0);
        
        try {
            
            $rate = 0;
            if ($effectiveCount >= 5) $rate = 20;
            elseif ($effectiveCount >= 4) $rate = 15;
            elseif ($effectiveCount >= 3) $rate = 10;
            elseif ($effectiveCount >= 2) $rate = 5;
            elseif ($effectiveCount >= 1) $rate = 1;
            
            
            $commission = ($directWinLoss - $claimBonus) * $rate / 100;
            $commission = max(0, $commission);
            
            return json([
                'code' => 0,
                'message' => 'success',
                'data' => [
                    'rate' => $rate,
                    'commission' => number_format($commission, 2, '.', '')
                ]
            ]);
            
        } catch (\Exception $e) {
            return json(['code' => 500, 'message' => '计算Thất bại', 'data' => null]);
        }
    }

    

    
    private function getDateRange($dateType)
    {
        $today = strtotime(date('Y-m-d'));
        
        switch ($dateType) {
            case 'yesterday':
                return [$today - 86400, $today - 1];
            case 'week':
                $weekStart = strtotime('monday this week');
                return [$weekStart, time()];
            case 'lastWeek':
                $lastWeekStart = strtotime('monday last week');
                $lastWeekEnd = strtotime('sunday last week') + 86399;
                return [$lastWeekStart, $lastWeekEnd];
            case 'month':
                $monthStart = strtotime(date('Y-m-01'));
                return [$monthStart, time()];
            case 'lastMonth':
                $lastMonthStart = strtotime('first day of last month');
                $lastMonthEnd = strtotime('last day of last month') + 86399;
                return [$lastMonthStart, $lastMonthEnd];
            default: 
                return [$today, $today + 86399];
        }
    }

    
    private function getSubordinateStats($subIds, $startTime, $endTime)
    {
        if (empty($subIds)) {
            return [
                'newDirect' => 0,
                'rechargeCount' => 0,
                'firstRechargeCount' => 0,
                'todayRegFirstRecharge' => 0,
                'rechargeAmount' => 0,
                'firstRechargeAmount' => 0,
                'todayRegFirstRechargeAmount' => 0,
                'withdrawAmount' => 0,
                'withdrawCount' => 0,
                'claimReward' => 0,
                'validBet' => 0,
                'directWinLoss' => 0
            ];
        }
        
        
        $newDirect = Db::table('caipiao_member')
            ->whereIn('id', $subIds)
            ->where('regtime', '>=', $startTime)
            ->where('regtime', '<=', $endTime)
            ->count();
        
        
        $rechargeStats = Db::table('caipiao_recharge')
            ->whereIn('uid', $subIds)
            ->where('state', 1)
            ->where('oddtime', '>=', $startTime)
            ->where('oddtime', '<=', $endTime)
            ->selectRaw('COUNT(*) as count, SUM(amount) as amount')
            ->first();
        
        
        $withdrawStats = Db::table('caipiao_withdraw')
            ->whereIn('uid', $subIds)
            ->where('state', 1)
            ->where('oddtime', '>=', $startTime)
            ->where('oddtime', '<=', $endTime)
            ->selectRaw('COUNT(*) as count, SUM(amount) as amount')
            ->first();
        
        
        $betStats = Db::table('caipiao_touzhu')
            ->whereIn('uid', $subIds)
            ->where('oddtime', '>=', $startTime)
            ->where('oddtime', '<=', $endTime)
            ->selectRaw('
                SUM(CASE WHEN isdraw != 0 THEN amount ELSE 0 END) as validBet,
                SUM(CASE WHEN isdraw = 1 THEN okamount - amount ELSE -amount END) as winLoss
            ')
            ->first();
        
        return [
            'newDirect' => $newDirect,
            'rechargeCount' => $rechargeStats->count ?? 0,
            'firstRechargeCount' => 0, 
            'todayRegFirstRecharge' => 0,
            'rechargeAmount' => number_format($rechargeStats->amount ?? 0, 2, '.', ''),
            'firstRechargeAmount' => '0.00',
            'todayRegFirstRechargeAmount' => '0.00',
            'withdrawAmount' => number_format($withdrawStats->amount ?? 0, 2, '.', ''),
            'withdrawCount' => $withdrawStats->count ?? 0,
            'claimReward' => '0.00',
            'validBet' => number_format($betStats->validBet ?? 0, 2, '.', ''),
            'directWinLoss' => number_format($betStats->winLoss ?? 0, 2, '.', '')
        ];
    }

    
    private function getAllSubordinateIds($userId, $maxLevel = 10)
    {
        $allIds = [];
        $currentIds = [$userId];
        $level = 0;
        
        while (!empty($currentIds) && $level < $maxLevel) {
            $subIds = Db::table('caipiao_member')
                ->whereIn('parentid', $currentIds)
                ->pluck('id')
                ->toArray();
            
            if (empty($subIds)) break;
            
            $allIds = array_merge($allIds, $subIds);
            $currentIds = $subIds;
            $level++;
        }
        
        return array_unique($allIds);
    }
}
