<?php

namespace app\service;

use support\Db;

class AgentCommissionService
{
    
    public static function onBetSettled(int $userId, float $betAmount, float $winAmount, string $gameType = '')
    {
        try {
            
            $user = Db::table('caipiao_member')->where('id', $userId)->first();
            if (!$user || !$user->parentid) {
                return; 
            }
            
            
            $agent = Db::table('caipiao_member')
                ->where('id', $user->parentid)
                ->where('proxy', 1)
                ->first();
            
            if (!$agent) {
                return; 
            }
            
            
            if (!self::isValidMember($userId)) {
                return; 
            }
            
            
            $agentMode = Db::table('caipiao_setting')
                ->where('name', 'agent_mode')
                ->value('value') ?? '一级净盈利';
            
            
            $netProfit = $betAmount - $winAmount; 
            
            
            $effectiveCount = self::getEffectiveSubordinateCount($agent->id);
            
            
            $rate = self::getCommissionRate($effectiveCount);
            
            if ($rate <= 0) {
                return;
            }
            
            $commission = 0;
            $type = 1; 
            
            if ($agentMode === '有效投注' || $agentMode === '流水模式') {
                
                $commission = round($betAmount * $rate / 100, 2);
            } else {
                
                $commission = round($netProfit * $rate / 100, 2);
                
                
                if ($commission < 0) {
                    $type = 2; 
                }
            }
            
            
            self::recordRealTimeCommission($agent->id, $userId, $commission, $type, $betAmount, $winAmount);
            
        } catch (\Exception $e) {
            \support\Log::error('佣金计算失败: ' . $e->getMessage(), [
                'userId' => $userId,
                'betAmount' => $betAmount,
                'winAmount' => $winAmount
            ]);
        }
    }
    
    
    public static function isValidMember(int $userId): bool
    {
        
        $minRecharge = Db::table('caipiao_setting')
            ->where('name', 'agent_valid_recharge')
            ->value('value') ?? 100;
        
        $minBet = Db::table('caipiao_setting')
            ->where('name', 'agent_valid_bet')
            ->value('value') ?? 500;
        
        
        $totalRecharge = Db::table('caipiao_recharge')
            ->where('uid', $userId)
            ->where('state', 1)
            ->sum('amount') ?? 0;
        
        if ($totalRecharge < $minRecharge) {
            return false;
        }
        
        
        $totalBet = Db::table('caipiao_touzhu')
            ->where('uid', $userId)
            ->sum('amount') ?? 0;
        
        return $totalBet >= $minBet;
    }
    
    
    public static function getEffectiveSubordinateCount(int $agentId): int
    {
        $subIds = Db::table('caipiao_member')
            ->where('parentid', $agentId)
            ->pluck('id')
            ->toArray();
        
        if (empty($subIds)) {
            return 0;
        }
        
        $count = 0;
        foreach ($subIds as $subId) {
            if (self::isValidMember($subId)) {
                $count++;
            }
        }
        
        return $count;
    }
    
    
    public static function getCommissionRate(int $effectiveCount): float
    {
        if ($effectiveCount <= 0) {
            return 0;
        }
        
        $rate = Db::table('caipiao_agent_rate')
            ->where('effective_count', '<=', $effectiveCount)
            ->where('status', 1)
            ->orderBy('effective_count', 'desc')
            ->value('rate');
        
        return $rate ?? 0;
    }
    
    
    private static function recordRealTimeCommission(
        int $agentId, 
        int $subId, 
        float $commission, 
        int $type,
        float $betAmount,
        float $winAmount
    ) {
        $today = date('Y-m-d');
        
        
        $existing = Db::table('caipiao_agent_commission')
            ->where('agent_id', $agentId)
            ->where('sub_id', $subId)
            ->where('settle_date', $today)
            ->first();
        
        if ($existing) {
            
            Db::table('caipiao_agent_commission')
                ->where('id', $existing->id)
                ->update([
                    'performance' => Db::raw("performance + {$betAmount}"),
                    'amount' => Db::raw("amount + {$commission}"),
                    'updated_at' => time()
                ]);
        } else {
            
            $effectiveCount = self::getEffectiveSubordinateCount($agentId);
            $rate = self::getCommissionRate($effectiveCount);
            
            
            Db::table('caipiao_agent_commission')->insert([
                'agent_id' => $agentId,
                'sub_id' => $subId,
                'type' => $type,
                'performance' => $betAmount,
                'rate' => $rate,
                'amount' => $commission,
                'status' => 1, 
                'settle_date' => $today,
                'created_at' => time()
            ]);
        }
        
        \support\Log::info('实时佣金记录', [
            'agent_id' => $agentId,
            'sub_id' => $subId,
            'commission' => $commission,
            'type' => $type
        ]);
    }
    
    
    public static function handleNegativeCommission(int $agentId): float
    {
        
        $positiveAmount = Db::table('caipiao_agent_commission')
            ->where('agent_id', $agentId)
            ->where('status', 1)
            ->where('amount', '>', 0)
            ->sum('amount') ?? 0;
        
        
        $negativeAmount = abs(Db::table('caipiao_agent_commission')
            ->where('agent_id', $agentId)
            ->where('status', 1)
            ->where('amount', '<', 0)
            ->sum('amount') ?? 0);
        
        
        $actualAmount = max(0, $positiveAmount - $negativeAmount);
        
        return $actualAmount;
    }
    
    
    public static function checkAuditMultiple(int $agentId, float $commissionAmount): bool
    {
        
        $auditMultiple = Db::table('caipiao_setting')
            ->where('name', 'agent_audit_multiple')
            ->value('value') ?? 1;
        
        if ($auditMultiple <= 0) {
            return true; 
        }
        
        
        $requiredBet = $commissionAmount * $auditMultiple;
        
        
        $lastClaimTime = Db::table('caipiao_agent_commission')
            ->where('agent_id', $agentId)
            ->where('status', 2)
            ->max('claimed_at') ?? 0;
        
        $actualBet = Db::table('caipiao_touzhu')
            ->where('uid', $agentId)
            ->where('oddtime', '>', $lastClaimTime)
            ->sum('amount') ?? 0;
        
        return $actualBet >= $requiredBet;
    }
    
    
    public static function getAgentStats(int $agentId): array
    {
        
        $totalCommission = Db::table('caipiao_agent_commission')
            ->where('agent_id', $agentId)
            ->sum('amount') ?? 0;
        
        
        $claimedCommission = Db::table('caipiao_agent_commission')
            ->where('agent_id', $agentId)
            ->where('status', 2)
            ->sum('amount') ?? 0;
        
        
        $pendingCommission = self::handleNegativeCommission($agentId);
        
        
        $negativeCommission = abs(Db::table('caipiao_agent_commission')
            ->where('agent_id', $agentId)
            ->where('status', 1)
            ->where('amount', '<', 0)
            ->sum('amount') ?? 0);
        
        
        $effectiveCount = self::getEffectiveSubordinateCount($agentId);
        
        
        $currentRate = self::getCommissionRate($effectiveCount);
        
        return [
            'totalCommission' => round($totalCommission, 2),
            'claimedCommission' => round($claimedCommission, 2),
            'pendingCommission' => round($pendingCommission, 2),
            'negativeCommission' => round($negativeCommission, 2),
            'effectiveCount' => $effectiveCount,
            'currentRate' => $currentRate
        ];
    }
}
