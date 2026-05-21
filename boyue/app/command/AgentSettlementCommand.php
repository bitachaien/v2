<?php

namespace app\command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use support\Db;

class AgentSettlementCommand extends Command
{
    protected static $defaultName = 'agent:settlement';
    protected static $defaultDescription = 'Đại lýHoa hồng自动结算';

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('[' . date('Y-m-d H:i:s') . '] 开始执行Đại lýHoa hồng结算...');
        
        try {
            $today = date('Y-m-d');
            $lastMonth = strtotime('first day of last month');
            $lastMonthEnd = strtotime('last day of last month') + 86399;
            
            
            $agentMode = Db::table('caipiao_setting')
                ->where('name', 'agent_mode')
                ->value('value') ?? '一级净盈利';
            
            $output->writeln("Đại lý模式: {$agentMode}");
            
            
            $agents = Db::table('caipiao_member')
                ->where('proxy', 1)
                ->get();
            
            $output->writeln("Đại lý总数: " . count($agents));
            
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
                    
                    
                    if ($agentMode === '有效Đặt cược') {
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
                    
                    $output->writeln("  Đại lý{$agent->id}({$agent->username}): 业绩={$performance}, 比例={$rate}%, Hoa hồng={$commission}");
                    
                } catch (\Exception $e) {
                    Db::rollBack();
                    $errorCount++;
                    $output->writeln("  Đại lý{$agent->id}Lỗi: " . $e->getMessage());
                }
            }
            
            $output->writeln("\n结算完成:");
            $output->writeln("  Thành công: {$successCount}");
            $output->writeln("  跳过: {$skipCount}");
            $output->writeln("  Thất bại: {$errorCount}");
            
            \support\Log::info("Đại lýHoa hồng自动结算完成", [
                'success' => $successCount,
                'skip' => $skipCount,
                'error' => $errorCount,
                'date' => $today
            ]);
            
            return self::SUCCESS;
            
        } catch (\Exception $e) {
            $output->writeln('结算Thất bại: ' . $e->getMessage());
            \support\Log::error('Đại lýHoa hồng自动结算Thất bại: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
