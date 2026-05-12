#!/usr/bin/env php
<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../support/bootstrap.php';

use support\Db;

echo "[" . date('Y-m-d H:i:s') . "] 开始执行代理佣金结算...\n";

try {
    $today = date('Y-m-d');
    $lastMonth = strtotime('first day of last month');
    $lastMonthEnd = strtotime('last day of last month') + 86399;
    
    
    $agentMode = Db::table('caipiao_setting')
        ->where('name', 'agent_mode')
        ->value('value') ?? '一级净盈利';
    
    echo "代理模式: {$agentMode}\n";
    
    
    $agents = Db::table('caipiao_member')
        ->where('proxy', 1)
        ->get();
    
    echo "代理总数: " . count($agents) . "\n";
    
    $successCount = 0;
    $skipCount = 0;
    $errorCount = 0;
    
    foreach ($agents as $agent) {
        try {
            
            $exists = Db::table('caipiao_agent_commission')
                ->where('agent_id', $agent->id)
                ->where('settle_date', $today)
                ->first();
            
            if ($exists) {
                $skipCount++;
                continue;
            }
            
            
            $subIds = Db::table('caipiao_member')
                ->where('parentid', $agent->id)
                ->pluck('id')
                ->toArray();
            
            if (empty($subIds)) {
                $skipCount++;
                continue;
            }
            
            
            if ($agentMode === '有效投注' || $agentMode === '流水模式') {
                $performance = Db::table('caipiao_touzhu')
                    ->whereIn('uid', $subIds)
                    ->where('oddtime', '>=', $lastMonth)
                    ->where('oddtime', '<=', $lastMonthEnd)
                    ->where('isdraw', '!=', 0)
                    ->sum('amount') ?? 0;
            } else {
                $betAmount = Db::table('caipiao_touzhu')
                    ->whereIn('uid', $subIds)
                    ->where('oddtime', '>=', $lastMonth)
                    ->where('oddtime', '<=', $lastMonthEnd)
                    ->where('isdraw', '!=', 0)
                    ->sum('amount') ?? 0;
                
                $winAmount = Db::table('caipiao_touzhu')
                    ->whereIn('uid', $subIds)
                    ->where('oddtime', '>=', $lastMonth)
                    ->where('oddtime', '<=', $lastMonthEnd)
                    ->where('isdraw', 1)
                    ->sum('okamount') ?? 0;
                
                $performance = $betAmount - $winAmount;
            }
            
            if ($performance <= 0) {
                $skipCount++;
                continue;
            }
            
            
            $effectiveUsers = Db::table('caipiao_touzhu')
                ->selectRaw('uid')
                ->whereIn('uid', $subIds)
                ->where('oddtime', '>=', $lastMonth)
                ->where('oddtime', '<=', $lastMonthEnd)
                ->groupBy('uid')
                ->havingRaw('SUM(amount) >= 100')
                ->get();
            $effectiveCount = count($effectiveUsers);
            
            if ($effectiveCount <= 0) {
                $skipCount++;
                continue;
            }
            
            
            $rate = Db::table('caipiao_agent_rate')
                ->where('effective_count', '<=', $effectiveCount)
                ->where('status', 1)
                ->orderBy('effective_count', 'desc')
                ->value('rate') ?? 0;
            
            if ($rate <= 0) {
                $skipCount++;
                continue;
            }
            
            $commission = round($performance * $rate / 100, 2);
            
            
            Db::beginTransaction();
            
            $doubleCheck = Db::table('caipiao_agent_commission')
                ->where('agent_id', $agent->id)
                ->where('settle_date', $today)
                ->lockForUpdate()
                ->first();
            
            if ($doubleCheck) {
                Db::rollBack();
                $skipCount++;
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
            $successCount++;
            
            echo "  代理{$agent->id}({$agent->username}): 业绩={$performance}, 比例={$rate}%, 佣金={$commission}\n";
            
        } catch (\Exception $e) {
            Db::rollBack();
            $errorCount++;
            echo "  代理{$agent->id}错误: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n结算完成:\n";
    echo "  成功: {$successCount}\n";
    echo "  跳过: {$skipCount}\n";
    echo "  失败: {$errorCount}\n";
    
    \support\Log::info("代理佣金自动结算完成", [
        'success' => $successCount,
        'skip' => $skipCount,
        'error' => $errorCount,
        'date' => $today
    ]);
    
} catch (\Exception $e) {
    echo '结算失败: ' . $e->getMessage() . "\n";
    \support\Log::error('代理佣金自动结算失败: ' . $e->getMessage());
    exit(1);
}

echo "[" . date('Y-m-d H:i:s') . "] 结算完成\n";
exit(0);
