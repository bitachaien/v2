<?php

namespace process;

use Workerman\Timer;
use support\Log;
use app\service\RebateService;

class RebateSettlement
{
    
    protected static $lastRunDate = '';
    
    public function __construct()
    {
        
        $this->checkAndSettle();
    }

    public function onWorkerStart()
    {
        
        Timer::add(300, function() {
            $this->checkAndSettle();
        });
        
        Log::info('反水结算定时任务进程启动');
    }

    
    protected function checkAndSettle()
    {
        $today = date('Y-m-d');
        $currentHour = (int)date('H');
        
        
        if ($currentHour >= 1 && $currentHour < 2 && self::$lastRunDate !== $today) {
            $this->doSettle();
            self::$lastRunDate = $today;
        }
        
        
        $this->doSettleOverdue();
    }

    
    protected function doSettle()
    {
        try {
            Log::info('开始执行反水T+1结算...');
            
            $rebateService = new RebateService();
            $result = $rebateService->settleRebates();
            
            Log::info('反水结算完成', $result);
            
        } catch (\Exception $e) {
            Log::error('反水结算Thất bại: ' . $e->getMessage());
        }
    }

    
    protected function doSettleOverdue()
    {
        try {
            $now = time();
            
            $affected = \support\Db::table('caipiao_fanshui')
                ->where('settle_time', '<=', $now)
                ->where('settle_time', '>', 0)
                ->where('can_claim', 0)
                ->where('shenhe', 0)
                ->update(['can_claim' => 1]);
            
            if ($affected > 0) {
                Log::info("反水兜底结算：处理了 {$affected} 条过期lịch sử");
            }
            
        } catch (\Exception $e) {
            Log::error('反水兜底结算失败: ' . $e->getMessage());
        }
    }
}
