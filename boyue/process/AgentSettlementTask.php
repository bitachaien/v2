<?php

namespace process;

use Workerman\Timer;
use support\Db;
use support\Log;

class AgentSettlementTask
{
    
    public function onWorkerStart()
    {
        
        $now = time();
        $todaySettleTime = strtotime(date('Y-m-d') . ' 02:00:00');
        
        if ($now >= $todaySettleTime) {
            
            $nextSettleTime = $todaySettleTime + 86400;
        } else {
            $nextSettleTime = $todaySettleTime;
        }
        
        $initialDelay = $nextSettleTime - $now;
        
        Log::info("[代理结算] 定时任务启动，下次结算时间: " . date('Y-m-d H:i:s', $nextSettleTime));
        
        
        Timer::add($initialDelay, function() {
            $this->runSettlement();
            
            
            Timer::add(86400, function() {
                $this->runSettlement();
            });
        }, [], false);
        
        
        Timer::add(3600, function() {
            $this->checkAndRunSettlement();
        });
    }
    
    
    private function checkAndRunSettlement()
    {
        $today = date('Y-m-d');
        $hour = (int)date('H');
        
        
        if ($hour < 2 || $hour > 5) {
            return;
        }
        
        
        $hasSettled = Db::table('caipiao_agent_commission')
            ->where('settle_date', $today)
            ->exists();
        
        if (!$hasSettled) {
            Log::info("[代理结算] 检测到今日未结算，执行补充结算");
            $this->runSettlement();
        }
    }
    
    
    private function runSettlement()
    {
        $startTime = microtime(true);
        Log::info("[代理结算] ========== 开始执行代理佣金结算 ==========");
        
        try {
            $today = date('Y-m-d');
            $yesterday = date('Y-m-d', strtotime('-1 day'));
            $yesterdayStart = strtotime($yesterday);
            $yesterdayEnd = $yesterdayStart + 86399;
            
            
            $settlementCycle = Db::table('caipiao_setting')
                ->where('name', 'agent_settlement_cycle')
                ->value('value') ?? 'daily';
            
            
            $agentMode = Db::table('caipiao_setting')
                ->where('name', 'agent_mode')
                ->value('value') ?? '一级净盈利';
            
            Log::info("[代理结算] 结算周期: {$settlementCycle}, 代理模式: {$agentMode}");
            
            
            switch ($settlementCycle) {
                case 'daily':
                    $startDate = $yesterdayStart;
                    $endDate = $yesterdayEnd;
                    break;
                case 'weekly':
                    
                    if (date('N') != 1) {
                        Log::info("[代理结算] 周结算模式，今天非周一，跳过");
                        return;
                    }
                    $startDate = strtotime('monday last week');
                    $endDate = strtotime('sunday last week') + 86399;
                    break;
                case 'monthly':
                    
                    if (date('j') != 1) {
                        Log::info("[代理结算] 月结算模式，今天非1号，跳过");
                        return;
                    }
                    $startDate = strtotime('first day of last month');
                    $endDate = strtotime('last day of last month') + 86399;
                    break;
                default:
                    $startDate = $yesterdayStart;
                    $endDate = $yesterdayEnd;
            }
            
            Log::info("[代理结算] 结算时间范围: " . date('Y-m-d H:i:s', $startDate) . " ~ " . date('Y-m-d H:i:s', $endDate));
            
            
            $agents = Db::table('caipiao_member')
                ->where('proxy', 1)
                ->get();
            
            $successCount = 0;
            $skipCount = 0;
            $errorCount = 0;
            $totalCommission = 0;
            
            foreach ($agents as $agent) {
                try {
                    $result = $this->settleAgentCommission($agent, $startDate, $endDate, $agentMode, $today);
                    
                    if ($result['status'] === 'success') {
                        $successCount++;
                        $totalCommission += $result['commission'];
                    } elseif ($result['status'] === 'skip') {
                        $skipCount++;
                    } else {
                        $errorCount++;
                    }
                    
                } catch (\Exception $e) {
                    $errorCount++;
                    Log::error("[代理结算] 代理{$agent->id}结算异常: " . $e->getMessage());
                }
            }
            
            $duration = round(microtime(true) - $startTime, 2);
            
            Log::info("[代理结算] ========== 结算完成 ==========");
            Log::info("[代理结算] 成功: {$successCount}, 跳过: {$skipCount}, 失败: {$errorCount}");
            Log::info("[代理结算] 总佣金: {$totalCommission}, 耗时: {$duration}秒");
            
            
            $this->recordSettlementLog($today, $successCount, $skipCount, $errorCount, $totalCommission, $duration);
            
        } catch (\Exception $e) {
            Log::error("[代理结算] 结算过程异常: " . $e->getMessage());
        }
    }
    
    
    private function settleAgentCommission($agent, $startDate, $endDate, $agentMode, $settleDate)
    {
        
        $exists = Db::table('caipiao_agent_commission')
            ->where('agent_id', $agent->id)
            ->where('settle_date', $settleDate)
            ->exists();
        
        if ($exists) {
            return ['status' => 'skip', 'reason' => '已结算', 'commission' => 0];
        }
        
        
        $subIds = Db::table('caipiao_member')
            ->where('parentid', $agent->id)
            ->pluck('id')
            ->toArray();
        
        if (empty($subIds)) {
            return ['status' => 'skip', 'reason' => '无下级', 'commission' => 0];
        }
        
        
        $performance = 0;
        
        if ($agentMode === '有效投注' || $agentMode === '流水模式') {
            
            $performance = Db::table('caipiao_touzhu')
                ->whereIn('uid', $subIds)
                ->where('oddtime', '>=', $startDate)
                ->where('oddtime', '<=', $endDate)
                ->where('isdraw', '!=', 0)
                ->sum('amount') ?? 0;
        } else {
            
            $betAmount = Db::table('caipiao_touzhu')
                ->whereIn('uid', $subIds)
                ->where('oddtime', '>=', $startDate)
                ->where('oddtime', '<=', $endDate)
                ->where('isdraw', '!=', 0)
                ->sum('amount') ?? 0;
            
            $winAmount = Db::table('caipiao_touzhu')
                ->whereIn('uid', $subIds)
                ->where('oddtime', '>=', $startDate)
                ->where('oddtime', '<=', $endDate)
                ->where('isdraw', 1)
                ->sum('okamount') ?? 0;
            
            $performance = $betAmount - $winAmount;
        }
        
        
        $effectiveCount = $this->getEffectiveSubordinateCount($subIds);
        
        if ($effectiveCount <= 0) {
            return ['status' => 'skip', 'reason' => '无有效下级', 'commission' => 0];
        }
        
        
        $rate = $this->getCommissionRate($effectiveCount);
        
        if ($rate <= 0) {
            return ['status' => 'skip', 'reason' => '返佣比例为0', 'commission' => 0];
        }
        
        
        $commission = round($performance * $rate / 100, 2);
        $type = $commission >= 0 ? 1 : 2; 
        
        
        Db::beginTransaction();
        
        try {
            
            $doubleCheck = Db::table('caipiao_agent_commission')
                ->where('agent_id', $agent->id)
                ->where('settle_date', $settleDate)
                ->lockForUpdate()
                ->exists();
            
            if ($doubleCheck) {
                Db::rollBack();
                return ['status' => 'skip', 'reason' => '并发检查已存在', 'commission' => 0];
            }
            
            Db::table('caipiao_agent_commission')->insert([
                'agent_id' => $agent->id,
                'sub_id' => null, 
                'type' => $type,
                'performance' => abs($performance),
                'rate' => $rate,
                'amount' => $commission,
                'status' => 1, 
                'settle_date' => $settleDate,
                'created_at' => time()
            ]);
            
            Db::commit();
            
            Log::info("[代理结算] 代理{$agent->id}({$agent->username}) 业绩:{$performance} 比例:{$rate}% 佣金:{$commission}");
            
            return ['status' => 'success', 'commission' => $commission];
            
        } catch (\Exception $e) {
            Db::rollBack();
            throw $e;
        }
    }
    
    
    private function getEffectiveSubordinateCount(array $subIds)
    {
        if (empty($subIds)) {
            return 0;
        }
        
        $minRecharge = Db::table('caipiao_setting')
            ->where('name', 'agent_valid_recharge')
            ->value('value') ?? 100;
        
        $minBet = Db::table('caipiao_setting')
            ->where('name', 'agent_valid_bet')
            ->value('value') ?? 500;
        
        $count = 0;
        
        foreach ($subIds as $subId) {
            
            $totalRecharge = Db::table('caipiao_recharge')
                ->where('uid', $subId)
                ->where('state', 1)
                ->sum('amount') ?? 0;
            
            if ($totalRecharge < $minRecharge) {
                continue;
            }
            
            
            $totalBet = Db::table('caipiao_touzhu')
                ->where('uid', $subId)
                ->sum('amount') ?? 0;
            
            if ($totalBet >= $minBet) {
                $count++;
            }
        }
        
        return $count;
    }
    
    
    private function getCommissionRate(int $effectiveCount)
    {
        $rate = Db::table('caipiao_agent_rate')
            ->where('effective_count', '<=', $effectiveCount)
            ->where('status', 1)
            ->orderBy('effective_count', 'desc')
            ->value('rate');
        
        return $rate ?? 0;
    }
    
    
    private function recordSettlementLog($date, $success, $skip, $error, $commission, $duration)
    {
        try {
            Db::table('caipiao_agent_settlement_log')->insert([
                'settle_date' => $date,
                'success_count' => $success,
                'skip_count' => $skip,
                'error_count' => $error,
                'total_commission' => $commission,
                'duration' => $duration,
                'created_at' => time()
            ]);
        } catch (\Exception $e) {
            
            Log::warning("[代理结算] 记录结算日志失败: " . $e->getMessage());
        }
    }
}

