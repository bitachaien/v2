<?php

namespace process;

use Workerman\Crontab\Crontab;
use support\Db;
use support\Log;
use app\constant\YuebaoConstant;

class YuebaoInterest
{
    public function onWorkerStart()
    {
        
        new Crontab('0 3 * * *', function(){
            $this->calculateDailyInterest();
            $this->settleExpiredFixed();
        });
        
        Log::info('Số dư宝利息计算定时任务已启动');
    }
    
    
    private function calculateDailyInterest()
    {
        Log::info('开始计算Số dư宝活期利息');
        
        try {
            
            $currentProduct = Db::table('yzz_yuebao_product')
                ->where('type', 'current')
                ->where('status', 1)
                ->first();
            
            if (!$currentProduct) {
                Log::warning('活期产品配置không tồn tại');
                return;
            }
            
            $rate = $currentProduct->rate; 
            
            
            $users = Db::table('yzz_yuebao_stats')
                ->where('current_amount', '>', 0)
                ->where('is_open', 1)
                ->get();
            
            $processCount = 0;
            $totalInterest = 0;
            
            foreach ($users as $stats) {
                
                $interest = (float)bcdiv(
                    bcmul((string)$stats->current_amount, (string)$rate, 10),
                    '365',
                    8
                );
                
                if ($interest <= 0) {
                    continue;
                }
                
                Db::beginTransaction();
                
                try {
                    
                    $user = Db::table('caipiao_member')
                        ->where('id', $stats->uid)
                        ->first();
                    
                    if (!$user) {
                        continue;
                    }
                    
                    
                    Db::table('yzz_yuebao_stats')
                        ->where('uid', $stats->uid)
                        ->update([
                            'current_amount' => Db::raw("current_amount + {$interest}"),
                            'total_interest' => Db::raw("total_interest + {$interest}"),
                            'yesterday_interest' => $interest,
                            'update_time' => time()
                        ]);
                    
                    
                    Db::table('caipiao_member')
                        ->where('id', $stats->uid)
                        ->increment('yebmoney', $interest);
                    
                    
                    $this->updateSevenDayRate($stats->uid);
                    
                    
                    $orderId = YuebaoConstant::generateOrderId(YuebaoConstant::ORDER_PREFIX_INCOME);
                    
                    
                    Db::table('yzz_yuebao_record')->insert([
                        'order_id' => $orderId,
                        'uid' => $stats->uid,
                        'username' => $user->username,
                        'type' => 'income',
                        'amount' => $interest,
                        'before_yebao' => $stats->current_amount,
                        'after_yebao' => $stats->current_amount + $interest,
                        'status' => 'success',
                        'remark' => '活期Số dư宝-Hôm qua收益',
                        'create_time' => time(),
                        'ip' => '127.0.0.1',
                    ]);
                    
                    Db::commit();
                    
                    $processCount++;
                    $totalInterest += $interest;
                    
                } catch (\Exception $e) {
                    Db::rollBack();
                    Log::error("计算Người dùng{$stats->uid}利息Thất bại: " . $e->getMessage());
                }
            }
            
            Log::info("活期利息计算完成，处理 {$processCount} 个Người dùng，总计发放 {$totalInterest} 元");
            
        } catch (\Exception $e) {
            Log::error('计算活期利息失败: ' . $e->getMessage());
        }
    }
    
    
    private function settleExpiredFixed()
    {
        Log::info('开始结算到期定期产品');
        
        try {
            $now = time();
            
            
            $holdings = Db::table('yzz_yuebao_holding')
                ->where('status', 'running')
                ->where('end_time', '<=', $now)
                ->get();
            
            $settleCount = 0;
            
            foreach ($holdings as $holding) {
                Db::beginTransaction();
                
                try {
                    
                    $user = Db::table('caipiao_member')
                        ->where('id', $holding->uid)
                        ->first();
                    
                    if (!$user) {
                        continue;
                    }
                    
                    
                    $actualInterest = $holding->expected_interest;
                    $totalAmount = $holding->amount + $actualInterest;
                    
                    
                    Db::table('yzz_yuebao_stats')
                        ->where('uid', $holding->uid)
                        ->update([
                            'fixed_amount' => Db::raw("fixed_amount - {$holding->amount}"),
                            'current_amount' => Db::raw("current_amount + {$totalAmount}"),
                            'total_interest' => Db::raw("total_interest + {$actualInterest}")
                        ]);
                    
                    
                    Db::table('caipiao_member')
                        ->where('id', $holding->uid)
                        ->update([
                            'dyebmoney' => Db::raw("dyebmoney - {$holding->amount}"),
                            'yebmoney' => Db::raw("yebmoney + {$totalAmount}")
                        ]);
                    
                    
                    Db::table('yzz_yuebao_holding')
                        ->where('id', $holding->id)
                        ->update([
                            'actual_interest' => $actualInterest,
                            'status' => 'done',
                            'settle_time' => $now
                        ]);
                    
                    
                    $orderId = YuebaoConstant::generateOrderId('YBFIX');
                    
                    
                    Db::table('yzz_yuebao_record')->insert([
                        'order_id' => $orderId,
                        'uid' => $holding->uid,
                        'username' => $user->username,
                        'type' => 'income',
                        'product_id' => $holding->product_id,
                        'product_name' => $holding->product_name,
                        'amount' => $actualInterest,
                        'before_yebao' => 0,
                        'after_yebao' => 0,
                        'status' => 'success',
                        'remark' => "定期{$holding->product_name}到期收益",
                        'create_time' => $now,
                        'ip' => '127.0.0.1',
                    ]);
                    
                    Db::commit();
                    
                    $settleCount++;
                    
                    Log::info("定期持仓 {$holding->order_id} 结算完成，Người dùng {$holding->uid}，收益 {$actualInterest}");
                    
                } catch (\Exception $e) {
                    Db::rollBack();
                    Log::error("结算定期持仓{$holding->id}Thất bại: " . $e->getMessage());
                }
            }
            
            Log::info("定期产品结算完成，处理 {$settleCount} 笔");
            
        } catch (\Exception $e) {
            Log::error('结算定期产品失败: ' . $e->getMessage());
        }
    }
    
    
    private function updateSevenDayRate($uid)
    {
        try {
            
            $sevenDaysAgo = time() - 7 * 86400;
            
            $records = Db::table('yzz_yuebao_record')
                ->where('uid', $uid)
                ->where('type', 'income')
                ->where('create_time', '>=', $sevenDaysAgo)
                ->get();
            
            if (count($records) === 0) {
                return;
            }
            
            
            $totalInterest = 0;
            foreach ($records as $record) {
                $totalInterest += $record->amount;
            }
            
            
            $stats = Db::table('yzz_yuebao_stats')
                ->where('uid', $uid)
                ->first();
            
            if ($stats && $stats->current_amount > 0) {
                
                $sevenDayRate = (float)bcdiv(
                    bcmul(
                        bcdiv((string)$totalInterest, (string)$stats->current_amount, 10),
                        '365',
                        10
                    ),
                    '7',
                    8
                );
                
                Db::table('yzz_yuebao_stats')
                    ->where('uid', $uid)
                    ->update(['seven_day_rate' => $sevenDayRate]);
            }
            
        } catch (\Exception $e) {
            Log::error("更新Người dùng{$uid}七日年化Thất bại: " . $e->getMessage());
        }
    }
}
