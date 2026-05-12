<?php

namespace process;

use Workerman\Timer;
use support\Db;
use support\Log;
use app\service\WebSocketPusher;
use app\service\GamePlatformService;

class ScheduledTasks
{
    
    public function onWorkerStart($worker)
    {
        Log::info('计划任务进程启动');
        
        
        Timer::add(60, function() {
            $this->checkAllTasks();
        });
    }
    
    
    private function checkAllTasks()
    {
        $now = time();
        $currentTime = date('H:i', $now);
        $currentDate = date('Y-m-d', $now);
        $currentDay = date('d', $now);
        
        try {
            
            $settings = $this->getSettings();
            
            
            if ($this->shouldRun('daily_consume', $settings['daily_consume_time'] ?? '23:00', $currentTime)) {
                $this->dailyConsumeReward($settings);
            }
            
            
            if ($this->shouldRun('daily_loss', $settings['daily_loss_time'] ?? '23:30', $currentTime)) {
                $this->dailyLossReward($settings);
            }
            
            
            if ($currentDay == '01' && $this->shouldRun('monthly_consume', $settings['monthly_consume_time'] ?? '00:30', $currentTime)) {
                $this->monthlyConsumeReward($settings);
            }
            
            
            if ($currentDay == '01' && $this->shouldRun('monthly_loss', $settings['monthly_loss_time'] ?? '01:00', $currentTime)) {
                $this->monthlyLossReward($settings);
            }
            
            
            if ($currentTime == '00:00') {
                $this->cleanOldData();
            }
            
            
            $this->yuebaoInterestTask();
            
            
            $this->syncGameBetRecords();
            
        } catch (\Exception $e) {
            Log::error('计划任务执行异常: ' . $e->getMessage());
        }
    }
    
    
    private function shouldRun($taskName, $scheduledTime, $currentTime)
    {
        
        if ($scheduledTime != $currentTime) {
            return false;
        }
        
        
        $cacheKey = "task_executed_{$taskName}_" . date('Y-m-d');
        
        
        $executed = Db::table('system_task_log')
            ->where('task_name', $taskName)
            ->where('execute_date', date('Y-m-d'))
            ->exists();
        
        return !$executed;
    }
    
    
    private function logTask($taskName, $result)
    {
        try {
            Db::table('system_task_log')->insert([
                'task_name' => $taskName,
                'execute_date' => date('Y-m-d'),
                'execute_time' => date('Y-m-d H:i:s'),
                'result' => $result,
                'created_at' => time()
            ]);
        } catch (\Exception $e) {
            Log::error("记录任务日志失败: " . $e->getMessage());
        }
    }
    
    
    private function dailyConsumeReward($settings)
    {
        Log::info('开始执行每日消费赠送活动');
        
        try {
            
            $today = strtotime(date('Y-m-d'));
            $tomorrow = $today + 86400;
            
            
            $users = Db::table('caipiao_touzhu as t')
                ->leftJoin('caipiao_member as m', 't.uid', '=', 'm.id')
                ->select('t.uid', 'm.username', Db::raw('SUM(t.amount) as total_consume'))
                ->whereBetween('t.oddtime', [$today, $tomorrow])
                ->groupBy('t.uid', 'm.username')
                ->having('total_consume', '>=', $settings['daily_consume_min'] ?? 100)
                ->get();
            
            $rewardCount = 0;
            foreach ($users as $user) {
                
                $rewardAmount = $user->total_consume * ($settings['daily_consume_rate'] ?? 0.01);
                
                
                Db::table('caipiao_member')
                    ->where('id', $user->uid)
                    ->increment('balance', $rewardAmount);
                
                
                Db::table('caipiao_fuddetail')->insert([
                    'uid' => $user->uid,
                    'username' => $user->username,
                    'type' => 'activity_daily_consume',
                    'amount' => $rewardAmount,
                    'afterbalance' => Db::table('caipiao_member')->where('id', $user->uid)->value('balance'),
                    'remark' => '每日消费赠送活动',
                    'oddtime' => time(),
                ]);
                
                $rewardCount++;
            }
            
            $result = "发放成功，受益用户: {$rewardCount}";
            Log::info($result);
            $this->logTask('daily_consume', $result);
            
        } catch (\Exception $e) {
            $result = "执行失败: " . $e->getMessage();
            Log::error($result);
            $this->logTask('daily_consume', $result);
        }
    }
    
    
    private function dailyLossReward($settings)
    {
        Log::info('开始执行每日亏损赠送活动');
        
        try {
            $today = strtotime(date('Y-m-d'));
            $tomorrow = $today + 86400;
            
            
            $users = Db::table('caipiao_touzhu as t')
                ->leftJoin('caipiao_member as m', 't.uid', '=', 'm.id')
                ->select('t.uid', 'm.username', 
                    Db::raw('SUM(t.amount) as total_bet'),
                    Db::raw('SUM(t.okamount) as total_win'))
                ->whereBetween('t.oddtime', [$today, $tomorrow])
                ->groupBy('t.uid', 'm.username')
                ->get();
            
            $rewardCount = 0;
            foreach ($users as $user) {
                $loss = $user->total_bet - $user->total_win;
                
                
                if ($loss >= ($settings['daily_loss_min'] ?? 100)) {
                    $rewardAmount = $loss * ($settings['daily_loss_rate'] ?? 0.05);
                    
                    
                    Db::table('caipiao_member')
                        ->where('id', $user->uid)
                        ->increment('balance', $rewardAmount);
                    
                    
                    Db::table('caipiao_fuddetail')->insert([
                        'uid' => $user->uid,
                        'username' => $user->username,
                        'type' => 'activity_daily_loss',
                        'amount' => $rewardAmount,
                        'afterbalance' => Db::table('caipiao_member')->where('id', $user->uid)->value('balance'),
                        'remark' => '每日亏损赠送活动',
                        'oddtime' => time(),
                    ]);
                    
                    $rewardCount++;
                }
            }
            
            $result = "发放成功，受益用户: {$rewardCount}";
            Log::info($result);
            $this->logTask('daily_loss', $result);
            
        } catch (\Exception $e) {
            $result = "执行失败: " . $e->getMessage();
            Log::error($result);
            $this->logTask('daily_loss', $result);
        }
    }
    
    
    private function monthlyConsumeReward($settings)
    {
        Log::info('开始执行每月消费赠送活动');
        
        try {
            
            $lastMonthStart = strtotime(date('Y-m-01', strtotime('-1 month')));
            $lastMonthEnd = strtotime(date('Y-m-01'));
            
            
            $users = Db::table('caipiao_touzhu as t')
                ->leftJoin('caipiao_member as m', 't.uid', '=', 'm.id')
                ->select('t.uid', 'm.username', Db::raw('SUM(t.amount) as total_consume'))
                ->whereBetween('t.oddtime', [$lastMonthStart, $lastMonthEnd])
                ->groupBy('t.uid', 'm.username')
                ->having('total_consume', '>=', $settings['monthly_consume_min'] ?? 1000)
                ->get();
            
            $rewardCount = 0;
            foreach ($users as $user) {
                $rewardAmount = $user->total_consume * ($settings['monthly_consume_rate'] ?? 0.02);
                
                Db::table('caipiao_member')
                    ->where('id', $user->uid)
                    ->increment('balance', $rewardAmount);
                
                Db::table('caipiao_fuddetail')->insert([
                    'uid' => $user->uid,
                    'username' => $user->username,
                    'type' => 'activity_monthly_consume',
                    'amount' => $rewardAmount,
                    'afterbalance' => Db::table('caipiao_member')->where('id', $user->uid)->value('balance'),
                    'remark' => '每月消费赠送活动',
                    'oddtime' => time(),
                ]);
                
                $rewardCount++;
            }
            
            $result = "发放成功，受益用户: {$rewardCount}";
            Log::info($result);
            $this->logTask('monthly_consume', $result);
            
        } catch (\Exception $e) {
            $result = "执行失败: " . $e->getMessage();
            Log::error($result);
            $this->logTask('monthly_consume', $result);
        }
    }
    
    
    private function monthlyLossReward($settings)
    {
        Log::info('开始执行每月亏损赠送活动');
        
        
    }
    
    
    private function cleanOldData()
    {
        Log::info('开始执行数据清理任务');
        
        try {
            
            $deleteTime = time() - (30 * 86400);
            
            $count = Db::table('caipiao_kaijiang')
                ->where('addtime', '<', $deleteTime)
                ->delete();
            
            Log::info("清理了 {$count} 条开奖数据");
            
        } catch (\Exception $e) {
            Log::error('数据清理失败: ' . $e->getMessage());
        }
    }
    
    
    private function yuebaoInterestTask()
    {
        try {
            $now = time();
            
            
            $holdings = Db::table('yzz_yuebao_holding as h')
                ->leftJoin('yzz_yuebao_product as p', 'h.product_id', '=', 'p.id')
                ->where('h.status', 'running')
                ->where('h.duration_days', 0)  
                ->select('h.*', 'p.settle_cycle_hours', 'p.max_interest', 'p.audit_multiple', 'p.auto_claim')
                ->get();
            
            if ($holdings->isEmpty()) {
                return;
            }
            
            $processedCount = 0;
            $totalInterest = 0;
            
            foreach ($holdings as $holding) {
                
                $settleCycleHours = $holding->settle_cycle_hours ?: 1;
                $settleCycleSeconds = $settleCycleHours * 3600;
                
                
                $lastInterestTime = $holding->last_interest_time ?: $holding->create_time;
                
                
                if (($now - $lastInterestTime) < $settleCycleSeconds) {
                    continue; 
                }
                
                
                
                $dailyRate = (float)$holding->rate;
                $interest = bcmul(
                    bcmul($holding->amount, $dailyRate, 8),
                    bcdiv($settleCycleHours, 24, 8),
                    2
                );
                
                
                $maxInterest = (float)($holding->max_interest ?? 0);
                if ($maxInterest > 0) {
                    $newTotalInterest = bcadd($holding->actual_interest, $interest, 2);
                    if ($newTotalInterest > $maxInterest) {
                        $interest = bcsub($maxInterest, $holding->actual_interest, 2);
                        if ($interest <= 0) {
                            continue; 
                        }
                    }
                }
                
                $orderId = 'YBINT' . date('YmdHis') . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
                
                
                $autoClaim = !empty($holding->auto_claim);
                
                if ($autoClaim) {
                    
                    $member = Db::table('caipiao_member')->where('id', $holding->uid)->first();
                    if (!$member) continue;
                    
                    $beforeBalance = (float)$member->balance;
                    $afterBalance = bcadd($beforeBalance, $interest, 2);
                    
                    
                    Db::table('caipiao_member')
                        ->where('id', $holding->uid)
                        ->update(['balance' => $afterBalance]);
                    
                    
                    Db::table('yzz_yuebao_holding')
                        ->where('id', $holding->id)
                        ->update([
                            'last_interest_time' => $now,
                            'update_time' => $now,
                        ]);
                    
                    
                    Db::table('yzz_yuebao_record')->insert([
                        'order_id' => $orderId,
                        'uid' => $holding->uid,
                        'username' => $holding->username,
                        'type' => 'interest',
                        'product_id' => $holding->product_id,
                        'product_name' => $holding->product_name,
                        'amount' => $interest,
                        'before_balance' => $beforeBalance,
                        'after_balance' => $afterBalance,
                        'before_yebao' => $holding->amount,
                        'after_yebao' => $holding->amount,
                        'status' => 'success',
                        'remark' => "利息自动发放(结算周期{$settleCycleHours}小时)",
                        'create_time' => $now,
                    ]);
                    
                    
                    Db::table('caipiao_fuddetail')->insert([
                        'uid' => $holding->uid,
                        'username' => $holding->username,
                        'type' => 'yuebao_interest',
                        'amount' => $interest,
                        'beforebalance' => $beforeBalance,
                        'afterbalance' => $afterBalance,
                        'remark' => '利息宝自动发放',
                        'oddtime' => $now,
                    ]);
                    
                    
                    $nextSettleTime = date('m/d H:i:s', $now + $settleCycleSeconds);
                    WebSocketPusher::pushYuebaoInterest($holding->uid, [
                        'pendingInterest' => '0.00',
                        'totalInterest' => bcadd($holding->actual_interest, $interest, 2),
                        'currentAmount' => $holding->amount,
                        'addedInterest' => $interest,
                        'nextSettleTime' => $nextSettleTime,
                    ]);
                } else {
                    
                    Db::table('yzz_yuebao_holding')
                        ->where('id', $holding->id)
                        ->update([
                            'actual_interest' => Db::raw("actual_interest + {$interest}"),
                            'last_interest_time' => $now,
                            'update_time' => $now,
                        ]);
                    
                    
                    Db::table('yzz_yuebao_record')->insert([
                        'order_id' => $orderId,
                        'uid' => $holding->uid,
                        'username' => $holding->username,
                        'type' => 'interest',
                        'product_id' => $holding->product_id,
                        'product_name' => $holding->product_name,
                        'amount' => $interest,
                        'before_balance' => 0,
                        'after_balance' => 0,
                        'before_yebao' => $holding->amount,
                        'after_yebao' => $holding->amount,
                        'status' => 'success',
                        'remark' => "利息累计(结算周期{$settleCycleHours}小时,待领取)",
                        'create_time' => $now,
                    ]);
                    
                    
                    $newPendingInterest = bcadd($holding->actual_interest, $interest, 2);
                    $nextSettleTime = date('m/d H:i:s', $now + $settleCycleSeconds);
                    WebSocketPusher::pushYuebaoInterest($holding->uid, [
                        'pendingInterest' => $newPendingInterest,
                        'totalInterest' => $holding->actual_interest,
                        'currentAmount' => $holding->amount,
                        'addedInterest' => $interest,
                        'nextSettleTime' => $nextSettleTime,
                    ]);
                }
                
                
                Db::table('yzz_yuebao_stats')
                    ->where('uid', $holding->uid)
                    ->update([
                        'total_interest' => Db::raw("total_interest + {$interest}"),
                        'update_time' => $now,
                    ]);
                
                $processedCount++;
                $totalInterest = bcadd($totalInterest, $interest, 2);
            }
            
            if ($processedCount > 0) {
                Log::info("余额宝计息完成", [
                    'processed_count' => $processedCount,
                    'total_interest' => $totalInterest,
                    'auto_claim' => $autoClaim,
                ]);
            }
            
        } catch (\Exception $e) {
            Log::error('余额宝计息任务异常: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
    
    
    private function getYuebaoAutoClaim(): bool
    {
        try {
            $config = Db::table('caipiao_option')
                ->where('name', 'yuebao_interest_config')
                ->value('value');
            
            if ($config) {
                $data = json_decode($config, true);
                return !empty($data['auto_claim']);
            }
        } catch (\Exception $e) {
            Log::warning('获取余额宝配置失败: ' . $e->getMessage());
        }
        
        return false; 
    }
    
    
    private function getSettings()
    {
        try {
            
            $settings = Db::table('caipiao_setting')
                ->whereIn('name', [
                    'daily_consume_time', 'daily_consume_min', 'daily_consume_rate',
                    'daily_loss_time', 'daily_loss_min', 'daily_loss_rate',
                    'monthly_consume_time', 'monthly_consume_min', 'monthly_consume_rate',
                    'monthly_loss_time', 'monthly_loss_min', 'monthly_loss_rate'
                ])
                ->pluck('value', 'name');
            
            if ($settings->isEmpty()) {
                
                return $this->getDefaultSettings();
            }
            
            
            $defaults = $this->getDefaultSettings();
            foreach ($settings as $key => $value) {
                $defaults[$key] = $value;
            }
            
            return $defaults;
            
        } catch (\Exception $e) {
            
            Log::warning("读取系统设置失败，使用默认值: " . $e->getMessage());
            return $this->getDefaultSettings();
        }
    }
    
    
    private function getDefaultSettings()
    {
        return [
            
            'daily_consume_time' => '23:00',      
            'daily_consume_min' => 100,           
            'daily_consume_rate' => 0.01,         
            
            
            'daily_loss_time' => '23:30',         
            'daily_loss_min' => 100,              
            'daily_loss_rate' => 0.05,            
            
            
            'monthly_consume_time' => '00:30',    
            'monthly_consume_min' => 1000,        
            'monthly_consume_rate' => 0.02,       
            
            
            'monthly_loss_time' => '01:00',       
            'monthly_loss_min' => 1000,           
            'monthly_loss_rate' => 0.05,          
        ];
    }
    
    
    private function syncGameBetRecords()
    {
        try {
            $service = new GamePlatformService();
            $result = $service->syncBetRecords();
            
            if ($result['success']) {
                if ($result['synced'] > 0 || $result['updated'] > 0) {
                    Log::info("同步第三方投注记录完成", [
                        'synced' => $result['synced'],
                        'updated' => $result['updated'],
                        'commission_triggered' => $result['commission_triggered'] ?? 0,
                        'total' => $result['total']
                    ]);
                }
            } else {
                Log::warning("同步第三方投注记录失败: " . ($result['msg'] ?? 'unknown'));
            }
        } catch (\Exception $e) {
            Log::error("同步第三方投注记录异常: " . $e->getMessage());
        }
    }
}

