<?php

namespace plugin\admin\app\controller;

use support\Request;
use support\Db;

class AgentCommissionController
{
    /**
     * 获取佣金记录列表
     */
    public function list(Request $request)
    {
        $agentId = $request->get('agentId', '');
        $status = $request->get('status', '');
        $startDate = $request->get('startDate', '');
        $endDate = $request->get('endDate', '');
        $page = max(1, (int)$request->get('page', 1));
        $pageSize = min(100, max(1, (int)$request->get('pageSize', 20)));
        
        try {
            $query = Db::table('caipiao_agent_commission as c')
                ->leftJoin('caipiao_member as m', 'c.agent_id', '=', 'm.id')
                ->select([
                    'c.*',
                    'm.username as agentUsername'
                ]);
            
            if (!empty($agentId)) {
                $query->where(function($q) use ($agentId) {
                    $q->where('c.agent_id', $agentId)
                      ->orWhere('m.username', 'like', "%{$agentId}%");
                });
            }
            
            if ($status !== '') {
                $query->where('c.status', $status);
            }
            
            if (!empty($startDate)) {
                $query->where('c.created_at', '>=', strtotime($startDate));
            }
            
            if (!empty($endDate)) {
                $query->where('c.created_at', '<=', strtotime($endDate . ' 23:59:59'));
            }
            
            $total = $query->count();
            
            $list = $query->orderBy('c.id', 'desc')
                ->offset(($page - 1) * $pageSize)
                ->limit($pageSize)
                ->get();
            
            $result = [];
            foreach ($list as $item) {
                // 统计下级人数
                $subCount = Db::table('caipiao_member')
                    ->where('parentid', $item->agent_id)
                    ->count();
                
                $result[] = [
                    'id' => $item->id,
                    'agentId' => $item->agent_id,
                    'agentUsername' => $item->agentUsername ?? 'N/A',
                    'subCount' => $subCount,
                    'performance' => $item->performance ?? 0,
                    'rate' => $item->rate ?? 0,
                    'amount' => $item->amount ?? 0,
                    'status' => $item->status,
                    'settleDate' => $item->settle_date ?? '-',
                    'claimedAt' => $item->claimed_at ? date('Y-m-d H:i:s', $item->claimed_at) : '-',
                    'createdAt' => date('Y-m-d H:i:s', $item->created_at)
                ];
            }
            
            return json([
                'code' => 0,
                'msg' => 'success',
                'data' => [
                    'list' => $result,
                    'total' => $total
                ]
            ]);
            
        } catch (\Exception $e) {
            return json(['code' => 500, 'msg' => '获取失败: ' . $e->getMessage()]);
        }
    }

    /**
     * 获取统计数据
     */
    public function stats(Request $request)
    {
        try {
            // 累计佣金
            $totalCommission = Db::table('caipiao_agent_commission')
                ->sum('amount') ?? 0;
            
            // 已领取
            $claimed = Db::table('caipiao_agent_commission')
                ->where('status', 2)
                ->sum('amount') ?? 0;
            
            // 待领取
            $pending = Db::table('caipiao_agent_commission')
                ->where('status', 1)
                ->sum('amount') ?? 0;
            
            // 代理人数
            $agentCount = Db::table('caipiao_member')
                ->where('proxy', 1)
                ->count();
            
            return json([
                'code' => 0,
                'msg' => 'success',
                'data' => [
                    'totalCommission' => $totalCommission,
                    'claimed' => $claimed,
                    'pending' => $pending,
                    'agentCount' => $agentCount
                ]
            ]);
            
        } catch (\Exception $e) {
            return json(['code' => 500, 'msg' => '获取失败: ' . $e->getMessage()]);
        }
    }

    /**
     * 手动发放佣金
     */
    public function manualClaim(Request $request)
    {
        $id = (int)$request->post('id', 0);
        
        if (!$id) {
            return json(['code' => 400, 'msg' => '参数错误']);
        }
        
        try {
            $record = Db::table('caipiao_agent_commission')
                ->where('id', $id)
                ->first();
            
            if (!$record) {
                return json(['code' => 404, 'msg' => '记录不存在']);
            }
            
            if ($record->status == 2) {
                return json(['code' => 400, 'msg' => '已发放，请勿重复操作']);
            }
            
            Db::beginTransaction();
            
            // 更新状态
            Db::table('caipiao_agent_commission')
                ->where('id', $id)
                ->update([
                    'status' => 2,
                    'claimed_at' => time()
                ]);
            
            // 增加用户余额
            Db::table('caipiao_member')
                ->where('id', $record->agent_id)
                ->increment('balance', $record->amount);
            
            // 记录资金变动
            Db::table('caipiao_fuddetail')->insert([
                'uid' => $record->agent_id,
                'type' => '代理佣金',
                'money' => $record->amount,
                'balance' => Db::table('caipiao_member')->where('id', $record->agent_id)->value('balance'),
                'oddtime' => time(),
                'memo' => '管理员手动发放代理佣金'
            ]);
            
            Db::commit();
            
            return json(['code' => 0, 'msg' => '发放成功']);
            
        } catch (\Exception $e) {
            Db::rollBack();
            return json(['code' => 500, 'msg' => '发放失败: ' . $e->getMessage()]);
        }
    }

    /**
     * 获取返佣比例配置
     */
    public function rates(Request $request)
    {
        if ($request->method() === 'POST') {
            return $this->saveRates($request);
        }
        
        try {
            $rates = Db::table('caipiao_agent_rate')
                ->where('status', 1)
                ->orderBy('effective_count', 'asc')
                ->get();
            
            $list = [];
            foreach ($rates as $rate) {
                $list[] = [
                    'id' => $rate->id,
                    'effectiveCount' => $rate->effective_count,
                    'performance' => $rate->performance,
                    'rate' => $rate->rate,
                    'commission' => $rate->commission
                ];
            }
            
            // 如果没有配置，返回默认值
            if (empty($list)) {
                $list = [
                    ['effectiveCount' => 1, 'performance' => 100, 'rate' => 1, 'commission' => 1],
                    ['effectiveCount' => 2, 'performance' => 50000, 'rate' => 5, 'commission' => 2500],
                    ['effectiveCount' => 3, 'performance' => 100000, 'rate' => 10, 'commission' => 10000],
                    ['effectiveCount' => 4, 'performance' => 150000, 'rate' => 15, 'commission' => 22500],
                    ['effectiveCount' => 5, 'performance' => 200000, 'rate' => 20, 'commission' => 40000]
                ];
            }
            
            return json([
                'code' => 0,
                'msg' => 'success',
                'data' => ['list' => $list]
            ]);
            
        } catch (\Exception $e) {
            return json(['code' => 500, 'msg' => '获取失败: ' . $e->getMessage()]);
        }
    }

    /**
     * 获取代理列表
     */
    public function agentList(Request $request)
    {
        $search = $request->get('search', '');
        
        try {
            $query = Db::table('caipiao_member')
                ->where('proxy', 1);
            
            if (!empty($search)) {
                $query->where(function($q) use ($search) {
                    $q->where('username', 'like', "%{$search}%")
                      ->orWhere('id', $search);
                });
            }
            
            $agents = $query->orderBy('id', 'desc')->limit(100)->get();
            
            $list = [];
            foreach ($agents as $agent) {
                // 下级人数
                $subCount = Db::table('caipiao_member')
                    ->where('parentid', $agent->id)
                    ->count();
                
                // 总业绩和佣金
                $commissionData = Db::table('caipiao_agent_commission')
                    ->where('agent_id', $agent->id)
                    ->selectRaw('SUM(performance) as total_performance, SUM(amount) as total_commission, SUM(CASE WHEN status = 1 THEN amount ELSE 0 END) as pending')
                    ->first();
                
                $list[] = [
                    'id' => $agent->id,
                    'username' => $agent->username,
                    'subCount' => $subCount,
                    'totalPerformance' => $commissionData->total_performance ?? 0,
                    'totalCommission' => $commissionData->total_commission ?? 0,
                    'pendingCommission' => $commissionData->pending ?? 0,
                    'regtime' => date('Y-m-d H:i:s', $agent->regtime ?? 0)
                ];
            }
            
            return json([
                'code' => 0,
                'msg' => 'success',
                'data' => ['list' => $list]
            ]);
            
        } catch (\Exception $e) {
            return json(['code' => 500, 'msg' => '获取失败: ' . $e->getMessage()]);
        }
    }

    /**
     * 取消代理资格
     */
    public function removeAgent(Request $request)
    {
        $id = (int)$request->post('id', 0);
        
        if (!$id) {
            return json(['code' => 400, 'msg' => '参数错误']);
        }
        
        try {
            Db::table('caipiao_member')
                ->where('id', $id)
                ->update(['proxy' => 0]);
            
            return json(['code' => 0, 'msg' => '已取消代理资格']);
            
        } catch (\Exception $e) {
            return json(['code' => 500, 'msg' => '操作失败: ' . $e->getMessage()]);
        }
    }

    /**
     * 代理系统配置（获取/保存）
     */
    public function settings(Request $request)
    {
        if ($request->method() === 'GET') {
            // 获取所有配置
            try {
                $configKeys = [
                    'agent_mode' => '一级净盈利',
                    'agent_settlement_cycle' => 'monthly',
                    'agent_settlement_day' => 1,
                    'agent_valid_recharge' => 100,
                    'agent_valid_bet' => 500,
                    'agent_audit_multiple' => 1,
                    'register_ip_limit' => 5,
                    'register_device_limit' => 3
                ];
                
                $settings = [];
                foreach ($configKeys as $key => $default) {
                    $value = Db::table('caipiao_setting')->where('name', $key)->value('value');
                    $settings[$key] = $value !== null ? $value : $default;
                }
                
                return json([
                    'code' => 0,
                    'msg' => 'success',
                    'data' => [
                        'agentMode' => $settings['agent_mode'],
                        'settlementCycle' => $settings['agent_settlement_cycle'],
                        'settlementDay' => (int)$settings['agent_settlement_day'],
                        'validRecharge' => (int)$settings['agent_valid_recharge'],
                        'validBet' => (int)$settings['agent_valid_bet'],
                        'auditMultiple' => (float)$settings['agent_audit_multiple'],
                        'ipLimit' => (int)$settings['register_ip_limit'],
                        'deviceLimit' => (int)$settings['register_device_limit']
                    ]
                ]);
            } catch (\Exception $e) {
                return json(['code' => 500, 'msg' => '获取失败: ' . $e->getMessage()]);
            }
        }
        
        // 保存设置
        try {
            $settings = [
                'agent_mode' => $request->post('agentMode', '一级净盈利'),
                'agent_settlement_cycle' => $request->post('settlementCycle', 'monthly'),
                'agent_settlement_day' => (int)$request->post('settlementDay', 1),
                'agent_valid_recharge' => (int)$request->post('validRecharge', 100),
                'agent_valid_bet' => (int)$request->post('validBet', 500),
                'agent_audit_multiple' => (float)$request->post('auditMultiple', 1),
                'register_ip_limit' => (int)$request->post('ipLimit', 5),
                'register_device_limit' => (int)$request->post('deviceLimit', 3)
            ];
            
            foreach ($settings as $name => $value) {
                Db::table('caipiao_setting')->updateOrInsert(
                    ['name' => $name],
                    ['value' => (string)$value]
                );
            }
            
            return json(['code' => 0, 'msg' => '保存成功']);
            
        } catch (\Exception $e) {
            return json(['code' => 500, 'msg' => '保存失败: ' . $e->getMessage()]);
        }
    }

    /**
     * 执行佣金结算
     * 优化：使用事务、提前查询代理模式、正确统计有效人数
     */
    public function settlement(Request $request)
    {
        try {
            $today = date('Y-m-d');
            $monthStart = strtotime(date('Y-m-01'));
            
            // 提前查询代理模式（只查一次）
            $agentMode = Db::table('caipiao_setting')
                ->where('name', 'agent_mode')
                ->value('value') ?? '一级净盈利';
            
            // 获取所有代理
            $agents = Db::table('caipiao_member')
                ->where('proxy', 1)
                ->get();
            
            $count = 0;
            $errors = [];
            
            foreach ($agents as $agent) {
                try {
                    // 检查是否已有今日结算记录（提前检查，避免不必要的计算）
                    $exists = Db::table('caipiao_agent_commission')
                        ->where('agent_id', $agent->id)
                        ->where('settle_date', $today)
                        ->first();
                    
                    if ($exists) continue;
                    
                    // 获取直属下级
                    $subIds = Db::table('caipiao_member')
                        ->where('parentid', $agent->id)
                        ->pluck('id')
                        ->toArray();
                    
                    if (empty($subIds)) continue;
                    
                    // 计算业绩
                    if ($agentMode === '有效投注') {
                        // 有效投注模式：按已开奖的投注额计算
                        $performance = Db::table('caipiao_touzhu')
                            ->whereIn('uid', $subIds)
                            ->where('oddtime', '>=', $monthStart)
                            ->where('isdraw', '!=', 0)
                            ->sum('amount') ?? 0;
                    } else {
                        // 一级净盈利模式：按下级输赢计算（平台赢的钱）
                        $betAmount = Db::table('caipiao_touzhu')
                            ->whereIn('uid', $subIds)
                            ->where('oddtime', '>=', $monthStart)
                            ->where('isdraw', '!=', 0)
                            ->sum('amount') ?? 0;
                        
                        $winAmount = Db::table('caipiao_touzhu')
                            ->whereIn('uid', $subIds)
                            ->where('oddtime', '>=', $monthStart)
                            ->where('isdraw', 1)
                            ->sum('okamount') ?? 0;
                        
                        $performance = $betAmount - $winAmount;
                    }
                    
                    // 净盈利模式下，如果平台亏损则不计算佣金
                    if ($performance <= 0) continue;
                    
                    // 正确计算有效人数（使用子查询避免groupBy+count问题）
                    $effectiveUsers = Db::table('caipiao_touzhu')
                        ->selectRaw('uid')
                        ->whereIn('uid', $subIds)
                        ->where('oddtime', '>=', $monthStart)
                        ->groupBy('uid')
                        ->havingRaw('SUM(amount) >= 100')
                        ->get();
                    $effectiveCount = count($effectiveUsers);
                    
                    if ($effectiveCount <= 0) continue;
                    
                    // 获取返佣比例
                    $rate = $this->getCommissionRate($effectiveCount);
                    
                    if ($rate <= 0) continue;
                    
                    // 计算佣金（保留2位小数）
                    $commission = round($performance * $rate / 100, 2);
                    
                    // 使用事务插入佣金记录
                    Db::beginTransaction();
                    
                    // 再次检查防止并发重复插入
                    $doubleCheck = Db::table('caipiao_agent_commission')
                        ->where('agent_id', $agent->id)
                        ->where('settle_date', $today)
                        ->lockForUpdate()
                        ->first();
                    
                    if ($doubleCheck) {
                        Db::rollBack();
                        continue;
                    }
                    
                    Db::table('caipiao_agent_commission')->insert([
                        'agent_id' => $agent->id,
                        'type' => 1,
                        'performance' => $performance,
                        'rate' => $rate,
                        'amount' => $commission,
                        'status' => 1,
                        'settle_date' => $today,
                        'created_at' => time()
                    ]);
                    
                    Db::commit();
                    $count++;
                    
                } catch (\Exception $e) {
                    Db::rollBack();
                    $errors[] = "代理{$agent->id}: " . $e->getMessage();
                    continue;
                }
            }
            
            $msg = "结算完成，共生成 {$count} 条记录";
            if (!empty($errors)) {
                $msg .= "，" . count($errors) . " 条失败";
                \support\Log::warning('部分结算失败: ' . implode('; ', $errors));
            }
            
            return json([
                'code' => 0,
                'msg' => $msg,
                'data' => ['count' => $count, 'errors' => count($errors)]
            ]);
            
        } catch (\Exception $e) {
            \support\Log::error('佣金结算失败: ' . $e->getMessage());
            return json(['code' => 500, 'msg' => '结算失败: ' . $e->getMessage()]);
        }
    }

    /**
     * 根据有效人数获取返佣比例
     */
    private function getCommissionRate($effectiveCount)
    {
        $rate = Db::table('caipiao_agent_rate')
            ->where('effective_count', '<=', $effectiveCount)
            ->where('status', 1)
            ->orderBy('effective_count', 'desc')
            ->value('rate');
        
        return $rate ?? 0;
    }

    /**
     * 保存返佣比例配置
     */
    private function saveRates(Request $request)
    {
        $rates = $request->post('rates', []);
        
        if (empty($rates) || !is_array($rates)) {
            return json(['code' => 400, 'msg' => '参数错误']);
        }
        
        try {
            Db::beginTransaction();
            
            // 删除现有配置
            Db::table('caipiao_agent_rate')->delete();
            
            // 插入新配置
            foreach ($rates as $rate) {
                Db::table('caipiao_agent_rate')->insert([
                    'effective_count' => $rate['effectiveCount'] ?? 0,
                    'performance' => $rate['performance'] ?? 0,
                    'rate' => $rate['rate'] ?? 0,
                    'commission' => ($rate['performance'] ?? 0) * ($rate['rate'] ?? 0) / 100,
                    'status' => 1,
                    'created_at' => time()
                ]);
            }
            
            Db::commit();
            
            return json(['code' => 0, 'msg' => '保存成功']);
            
        } catch (\Exception $e) {
            Db::rollBack();
            return json(['code' => 500, 'msg' => '保存失败: ' . $e->getMessage()]);
        }
    }
}
