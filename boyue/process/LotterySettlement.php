<?php

namespace process;

use Workerman\Timer;
use support\Db;
use support\Log;
use app\service\WebSocketPusher;
use app\service\AdminEventTrigger;

class LotterySettlement
{
    
    public function onWorkerStart($worker)
    {
        Log::info('Xổ số结算进程启动');
        
        
        $this->settleAll();
        
        
        Timer::add(10, function() {
            $this->settleAll();
        });
    }
    
    
    private function settleAll()
    {
        try {
            
            
            
            $bets = Db::table('caipiao_touzhu as t')
                ->leftJoin('caipiao_kaijiang as k', function($join) {
                    $join->on('t.cpname', '=', 'k.name')
                         ->on('t.expect', '=', 'k.expect');
                })
                ->select('t.*', 'k.opencode')
                ->where('t.isdraw', 0)
                ->where(function($query) {
                    $query->whereNull('t.source')
                          ->orWhere('t.source', '!=', 'robot');
                })
                ->whereNotNull('k.opencode')
                ->where('k.opencode', '!=', '')
                ->where('k.opentime', '<=', time())  
                ->orderBy('t.id', 'desc')
                ->limit(50)  
                ->get();
            
            
            $this->settleRobotBets();
            
            if ($bets->isEmpty()) {
                return;
            }
            
            Log::info("开始结算 " . count($bets) . " 条Đặt cượclịch sử");
            
            $settledCount = 0;
            $errorCount = 0;
            
            foreach ($bets as $bet) {
                try {
                    
                    $result = $this->settleBet($bet);
                    
                    if ($result['success']) {
                        $settledCount++;
                        if ($result['is_win']) {
                            Log::info("Người dùng {$bet->userid} Trúng thưởng {$result['win_amount']} 元，订单：{$bet->tzcode}");
                        }
                    } else {
                        $errorCount++;
                    }
                    
                } catch (\Exception $e) {
                    $errorCount++;
                    Log::error("结算Đặt cượcThất bại ID:{$bet->id}, Lỗi: " . $e->getMessage());
                }
            }
            
            if ($settledCount > 0) {
                Log::info("结算完成，Thành công: {$settledCount}, Thất bại: {$errorCount}");
            }
            
        } catch (\Exception $e) {
            Log::error('结算任务异常: ' . $e->getMessage());
        }
    }
    
    
    private function settleBet($bet)
    {
        Db::beginTransaction();
        
        try {
            
            if ($bet->playid === 'xy28_combined') {
                return $this->settleCombinedBet($bet);
            }
            
            
            
            $wanfa = Db::table('caipiao_wanfa')
                ->where('typeid', $bet->typeid)
                ->where('playid', $bet->playid)
                ->first();
            
            if (!$wanfa) {
                Log::warning("玩法không tồn tại: typeid={$bet->typeid}, playid={$bet->playid}");
                Db::rollBack();
                return ['success' => false, 'message' => '玩法không tồn tại'];
            }
            
            
            $winResult = $this->checkWinWithCount($bet);
            $winCount = $winResult['count'];  
            $isWin = $winCount > 0;
            $winAmount = 0;
            $userBalance = 0;
            
            if ($isWin) {
                
                
                $singleBetAmount = $bet->itemcount > 0 ? ($bet->amount / $bet->itemcount) : $bet->amount;
                
                $winAmount = $singleBetAmount * $winCount * $wanfa->rate;
                $winAmount = round($winAmount, 2);  
                
                
                Db::table('caipiao_member')
                    ->where('id', $bet->uid)
                    ->increment('balance', $winAmount);
                
                
                $userBalance = Db::table('caipiao_member')->where('id', $bet->uid)->value('balance');
                Db::table('caipiao_moneylog')->insert([
                    'uid' => $bet->uid,
                    'username' => $bet->username,
                    'type' => 10,
                    'typename' => 'Trúng thưởng',
                    'trano' => $bet->trano,
                    'amount' => $winAmount,
                    'before' => $userBalance - $winAmount,
                    'after' => $userBalance,
                    'addtime' => time(),
                    'remark' => "期号:{$bet->expect} Trúng thưởng{$winCount}注",
                ]);
            }
            
            
            
            Db::table('caipiao_touzhu')
                ->where('id', $bet->id)
                ->update([
                    'isdraw' => $isWin ? 1 : -1,  
                    'okamount' => $winAmount,  
                    'okcount' => $winCount,  
                    'opencode' => $bet->opencode,  
                ]);
            
            Db::commit();
            
            
            try {
                \app\service\AgentCommissionService::onBetSettled(
                    $bet->uid,
                    $bet->amount,
                    $winAmount,
                    $bet->cpname
                );
            } catch (\Exception $e) {
                
                Log::warning("[Đại lýHoa hồng] 计算Thất bại: " . $e->getMessage());
            }
            
            
            try {
                $rebateService = new \app\service\RebateService();
                $rebateService->processRebate(
                    $bet->uid,
                    $bet->amount,
                    $bet->cpname,  
                    $bet->trano,   
                    4              
                );
            } catch (\Exception $e) {
                
                Log::warning("[反水] 计算Thất bại: " . $e->getMessage());
            }
            
            
            try {
                
                WebSocketPusher::pushBetSettled($bet->uid, [
                    'orderId' => $bet->trano,
                    'lotteryCode' => $bet->cpname,
                    'issue' => $bet->expect,
                    'status' => $isWin ? 'win' : 'lose',
                    'betAmount' => $bet->amount,
                    'winAmount' => $winAmount,
                    'settleTime' => time(),
                ]);
                
                
                if ($isWin && $winAmount > 0) {
                    WebSocketPusher::pushBalanceUpdate($bet->uid, [
                        'balance' => $userBalance,
                        'change' => $winAmount,
                        'reason' => 'bet_win',
                        'orderId' => $bet->trano,
                        'time' => time(),
                    ]);
                    
                    
                    AdminEventTrigger::onWin([
                        'username' => $bet->username,
                        'winmoney' => $winAmount,
                        'lottery_name' => $bet->cpname,
                        'expect' => $bet->expect,
                    ]);
                }
            } catch (\Exception $e) {
                
                Log::warning("[WebSocket] 推送Thất bại: " . $e->getMessage());
            }
            
            return [
                'success' => true,
                'is_win' => $isWin,
                'win_amount' => $winAmount
            ];
            
        } catch (\Exception $e) {
            Db::rollBack();
            Log::error("结算Thất bại ID:{$bet->id}, Lỗi: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    
    private function settleCombinedBet($bet)
    {
        try {
            
            $betItems = json_decode($bet->tzcode, true);
            if (empty($betItems)) {
                Db::rollBack();
                return ['success' => false, 'message' => 'Đặt cược明细解析Thất bại'];
            }
            
            
            $nums = explode(',', $bet->opencode);
            $sum = array_sum(array_slice(array_map('intval', $nums), 0, 3));
            
            $totalWinAmount = 0;
            $winCount = 0;
            $resultDetails = [];
            
            
            foreach ($betItems as $item) {
                $playid = $item['playid'] ?? '';
                $betAmount = floatval($item['amount'] ?? 0);
                $rate = floatval($item['rate'] ?? 0);
                $title = $item['title'] ?? $item['label'] ?? $playid;
                
                
                if (empty($playid) || $betAmount <= 0) {
                    Log::warning("跳过无效Đặt cược项: playid={$playid}, amount={$betAmount}");
                    continue;
                }
                
                
                if ($rate <= 0) {
                    $play = Db::table('caipiao_wanfa')->where('playid', $playid)->first();
                    if ($play && floatval($play->rate) > 0) {
                        $rate = floatval($play->rate);
                    } else {
                        
                        $mappedPlayid = $this->mapPlayId($playid);
                        if ($mappedPlayid !== $playid) {
                            $play = Db::table('caipiao_wanfa')->where('playid', $mappedPlayid)->first();
                            if ($play && floatval($play->rate) > 0) {
                                $rate = floatval($play->rate);
                            }
                        }
                    }
                }
                
                
                if ($rate <= 0) {
                    Log::warning("玩法赔率异常，使用默认赔率: playid={$playid}");
                    $rate = $this->getDefaultOdds($playid); 
                }
                
                
                $isWin = $this->checkXy28Win($bet->opencode, $playid, '');
                
                if ($isWin && $rate > 0) {
                    $itemWinAmount = $betAmount * $rate;
                    $totalWinAmount += $itemWinAmount;
                    $winCount++;
                    $resultDetails[] = "{$title}✓+" . number_format($itemWinAmount, 2);
                } else {
                    $resultDetails[] = "{$title}✗-" . number_format($betAmount, 2);
                }
            }
            
            $isWin = $totalWinAmount > 0;
            $userBalance = 0;
            
            if ($isWin) {
                
                Db::table('caipiao_member')
                    ->where('id', $bet->uid)
                    ->increment('balance', $totalWinAmount);
                
                
                $userBalance = Db::table('caipiao_member')->where('id', $bet->uid)->value('balance');
                Db::table('caipiao_moneylog')->insert([
                    'uid' => $bet->uid,
                    'username' => $bet->username,
                    'type' => 10,
                    'typename' => 'Trúng thưởng',
                    'trano' => $bet->trano,
                    'amount' => $totalWinAmount,
                    'before' => $userBalance - $totalWinAmount,
                    'after' => $userBalance,
                    'addtime' => time(),
                    'remark' => "期号:{$bet->expect} 合并Đặt cượcTrúng thưởng{$winCount}项",
                ]);
            }
            
            
            Db::table('caipiao_touzhu')
                ->where('id', $bet->id)
                ->update([
                    'isdraw' => $isWin ? 1 : -1,
                    'okamount' => $totalWinAmount,
                    'okcount' => $winCount,
                    'opencode' => $bet->opencode,
                ]);
            
            Db::commit();
            
            
            try {
                $rebateService = new \app\service\RebateService();
                $rebateService->processRebate(
                    $bet->uid,
                    $bet->amount,
                    $bet->cpname,  
                    $bet->trano,   
                    4              
                );
            } catch (\Exception $e) {
                Log::warning("[反水] 合并Đặt cược反水计算Thất bại: " . $e->getMessage());
            }
            
            Log::info("合并Đặt cược结算完成 ID:{$bet->id}, 结果: " . implode('/', $resultDetails));
            
            return [
                'success' => true,
                'is_win' => $isWin,
                'win_amount' => $totalWinAmount
            ];
            
        } catch (\Exception $e) {
            Db::rollBack();
            Log::error("合并Đặt cược结算Thất bại ID:{$bet->id}, Lỗi: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    
    private function checkWinWithCount($bet)
    {
        require_once base_path() . '/app/library/WinChecker.php';
        
        $opencode = $bet->opencode;
        $playid = $bet->playid;
        $tzcode = $bet->tzcode;
        $typeid = $bet->typeid;
        
        try {
            
            
            
            $isWin = false;
            
            
            if (strpos($typeid, 'xy28') !== false || strpos($typeid, 'pcdd') !== false) {
                $isWin = $this->checkXy28Win($opencode, $playid, $tzcode);
                return ['count' => $isWin ? 1 : 0];
            }
            
            switch ($typeid) {
                case 'ssc':
                    $isWin = $this->checkSscWin($opencode, $playid, $tzcode);
                    return ['count' => $isWin ? 1 : 0];
                    
                case 'k3':
                    $isWin = $this->checkK3Win($opencode, $playid, $tzcode);
                    return ['count' => $isWin ? 1 : 0];
                    
                case 'pk10':
                    $isWin = $this->checkPk10Win($opencode, $playid, $tzcode);
                    return ['count' => $isWin ? 1 : 0];
                    
                case 'x5':
                    $isWin = $this->checkX5Win($opencode, $playid, $tzcode);
                    return ['count' => $isWin ? 1 : 0];
                    
                case 'lhc':
                    $isWin = $this->checkLhcWin($opencode, $playid, $tzcode);
                    return ['count' => $isWin ? 1 : 0];
                    
                case 'dwc':
                    return $this->checkDwcWinWithCount($opencode, $playid, $tzcode);
                    
                default:
                    Log::warning("未知彩种类型: {$typeid}");
                    return ['count' => 0];
            }
        } catch (\Exception $e) {
            Log::error("Trúng thưởng判断异常: " . $e->getMessage());
            return ['count' => 0];
        }
    }
    
    
    private function checkWin($bet)
    {
        $result = $this->checkWinWithCount($bet);
        return $result['count'] > 0;
    }
    
    
    private function checkXy28Win($opencode, $playid, $tzcode)
    {
        
        $nums = explode(',', $opencode);
        $sum = array_sum(array_slice($nums, 0, 3)); 
        
        
        if ($playid === 'big') return $sum >= 14;
        if ($playid === 'small') return $sum <= 13;
        if ($playid === 'odd') return $sum % 2 == 1;
        if ($playid === 'even') return $sum % 2 == 0;
        if ($playid === 'big_odd') return $sum >= 14 && $sum % 2 == 1;
        if ($playid === 'big_even') return $sum >= 14 && $sum % 2 == 0;
        if ($playid === 'small_odd') return $sum <= 13 && $sum % 2 == 1;
        if ($playid === 'small_even') return $sum <= 13 && $sum % 2 == 0;
        if ($playid === 'extreme_big') return $sum >= 22 && $sum <= 27;
        if ($playid === 'extreme_small') return $sum >= 0 && $sum <= 5;
        
        
        
        if (preg_match('/^hz_(\d+)$/', $playid, $m)) {
            return $sum == intval($m[1]);
        }
        
        
        if ($playid === 'c_big') return $sum >= 14;
        if ($playid === 'c_small') return $sum <= 13;
        if ($playid === 'c_odd') return $sum % 2 == 1;
        if ($playid === 'c_even') return $sum % 2 == 0;
        if ($playid === 'c_big_odd') return $sum >= 14 && $sum % 2 == 1;
        if ($playid === 'c_big_even') return $sum >= 14 && $sum % 2 == 0;
        if ($playid === 'c_small_odd') return $sum <= 13 && $sum % 2 == 1;
        if ($playid === 'c_small_even') return $sum <= 13 && $sum % 2 == 0;
        if ($playid === 'c_jida') return $sum >= 22 && $sum <= 27;
        if ($playid === 'c_jixiao') return $sum >= 0 && $sum <= 5;
        
        
        if ($playid === 'c_long') return intval($nums[0]) > intval($nums[2]);
        if ($playid === 'c_hu') return intval($nums[0]) < intval($nums[2]);
        if ($playid === 'c_bao') return intval($nums[0]) == intval($nums[2]);
        
        
        $sortedNums = [intval($nums[0]), intval($nums[1]), intval($nums[2])];
        sort($sortedNums);
        $isBaozi = ($sortedNums[0] == $sortedNums[1] && $sortedNums[1] == $sortedNums[2]);
        $isDuizi = !$isBaozi && ($sortedNums[0] == $sortedNums[1] || $sortedNums[1] == $sortedNums[2] || $sortedNums[0] == $sortedNums[2]);
        $isShunzi = !$isBaozi && !$isDuizi && ($sortedNums[2] - $sortedNums[1] == 1 && $sortedNums[1] - $sortedNums[0] == 1);
        
        if ($playid === 'c_baozi') return $isBaozi;
        if ($playid === 'c_duizi') return $isDuizi;
        if ($playid === 'c_shunzi') return $isShunzi;
        
        
        
        if (strpos($playid, '_tm_') !== false) {
            $tzNums = explode(',', $tzcode);
            foreach ($tzNums as $num) {
                if ($sum == intval($num)) {
                    return true;
                }
            }
            return false;
        }
        
        
        if (strpos($playid, '_tmbs') !== false) {
            $tzNums = explode(',', $tzcode);
            return in_array($sum, array_map('intval', $tzNums));
        }
        
        
        if (strpos($playid, '_bs_') !== false) {
            $boseConfig = require config_path() . '/xy28_bose.php';
            if (strpos($playid, 'hong') !== false) {
                return in_array($sum, $boseConfig['hong']);
            } else if (strpos($playid, 'lv') !== false) {
                return in_array($sum, $boseConfig['lv']);
            } else if (strpos($playid, 'lan') !== false) {
                return in_array($sum, $boseConfig['lan']);
            }
        }
        
        
        if (strpos($playid, '_hunhe_') !== false) {
            
            if (strpos($playid, 'baozi') !== false) {
                return ($nums[0] == $nums[1] && $nums[1] == $nums[2]);
            }
            
            if (strpos($playid, 'big') !== false) {
                return $sum >= 14;
            }
            if (strpos($playid, 'small') !== false) {
                return $sum <= 13;
            }
            
            if (strpos($playid, 'odd') !== false) {
                return $sum % 2 == 1;
            }
            if (strpos($playid, 'even') !== false) {
                return $sum % 2 == 0;
            }
            
            if (strpos($playid, 'ji_big') !== false) {
                return $sum >= 23 && $sum <= 27;
            }
            if (strpos($playid, 'ji_small') !== false) {
                return $sum >= 0 && $sum <= 4;
            }
        }
        
        return false;
    }
    
    
    private function checkSscWin($opencode, $playid, $tzcode)
    {
        $nums = explode(',', $opencode);
        $tzNums = explode(',', $tzcode);
        
        
        if (strpos($playid, 'lmp_') === 0) {
            return \app\library\WinChecker::checkSscDouble($opencode, $playid, $tzcode);
        }
        
        
        if ($playid == 'zhixuan5' || $playid == 'wx_zhixuan') {
            return $opencode === $tzcode;
        }
        
        
        if ($playid == 'tongxuan5' || $playid == 'wx_tongxuan') {
            sort($nums);
            sort($tzNums);
            return implode(',', $nums) === implode(',', $tzNums);
        }
        
        
        if ($playid == 'zhixuan4' || $playid == 'sx_zhixuan') {
            return substr($opencode, 0, strrpos($opencode, ',')) === $tzcode;
        }
        
        
        if ($playid == 'zhixuan3' || $playid == 'sax_zhixuan') {
            $open3 = implode(',', array_slice($nums, 0, 3));
            return $open3 === $tzcode;
        }
        
        
        if ($playid == 'zusan' || $playid == 'sax_zusan') {
            $open3 = array_slice($nums, 0, 3);
            sort($open3);
            sort($tzNums);
            return implode(',', $open3) === implode(',', $tzNums);
        }
        
        
        if ($playid == 'zuliu' || $playid == 'sax_zuliu') {
            $open3 = array_slice($nums, 0, 3);
            sort($open3);
            sort($tzNums);
            return implode(',', $open3) === implode(',', $tzNums);
        }
        
        
        if ($playid == 'zhixuan2' || $playid == 'ex_zhixuan') {
            $open2 = implode(',', array_slice($nums, 0, 2));
            return $open2 === $tzcode;
        }
        
        
        if ($playid == 'zuxuan2' || $playid == 'ex_zuxuan') {
            $open2 = array_slice($nums, 0, 2);
            sort($open2);
            sort($tzNums);
            return implode(',', $open2) === implode(',', $tzNums);
        }
        
        
        if (strpos($playid, 'dwd_') === 0 || strpos($playid, 'yix_') === 0) {
            
            if (strpos($playid, 'd1') !== false || strpos($playid, 'wan') !== false) {
                return $nums[0] == $tzcode;
            }
            if (strpos($playid, 'd2') !== false || strpos($playid, 'qian') !== false) {
                return $nums[1] == $tzcode;
            }
            if (strpos($playid, 'd3') !== false || strpos($playid, 'bai') !== false) {
                return $nums[2] == $tzcode;
            }
            if (strpos($playid, 'd4') !== false || strpos($playid, 'shi') !== false) {
                return $nums[3] == $tzcode;
            }
            if (strpos($playid, 'd5') !== false || strpos($playid, 'ge') !== false) {
                return $nums[4] == $tzcode;
            }
        }
        
        
        
        $checkSanXingTai = function($a, $b, $c) {
            $arr = [intval($a), intval($b), intval($c)];
            sort($arr);
            
            
            $isBaozi = ($arr[0] == $arr[1] && $arr[1] == $arr[2]);
            
            
            $isShunzi = false;
            if (!$isBaozi) {
                if ($arr[2] - $arr[1] == 1 && $arr[1] - $arr[0] == 1) {
                    $isShunzi = true;
                }
                
                if (($arr[0] == 0 && $arr[1] == 1 && $arr[2] == 2) ||
                    ($arr[0] == 0 && $arr[1] == 8 && $arr[2] == 9) ||
                    ($arr[0] == 0 && $arr[1] == 1 && $arr[2] == 9)) {
                    $isShunzi = true;
                }
            }
            
            
            $isDuizi = !$isBaozi && ($arr[0] == $arr[1] || $arr[1] == $arr[2] || $arr[0] == $arr[2]);
            
            
            $isBanshun = false;
            if (!$isBaozi && !$isShunzi && !$isDuizi) {
                
                $hasAdjacent = false;
                for ($i = 0; $i < 3; $i++) {
                    for ($j = $i + 1; $j < 3; $j++) {
                        $diff = abs($arr[$i] - $arr[$j]);
                        if ($diff == 1 || $diff == 9) { 
                            $hasAdjacent = true;
                            break 2;
                        }
                    }
                }
                $isBanshun = $hasAdjacent;
            }
            
            
            $isZaliu = !$isBaozi && !$isShunzi && !$isDuizi && !$isBanshun;
            
            return [
                'baozi' => $isBaozi,
                'shunzi' => $isShunzi,
                'duizi' => $isDuizi,
                'banshun' => $isBanshun,
                'zaliu' => $isZaliu
            ];
        };
        
        
        if (strpos($playid, 'sscq3') === 0) {
            $xingtai = $checkSanXingTai($nums[0], $nums[1], $nums[2]);
            if ($playid == 'sscq3bz') return $xingtai['baozi'];
            if ($playid == 'sscq3sz') return $xingtai['shunzi'];
            if ($playid == 'sscq3dz') return $xingtai['duizi'];
            if ($playid == 'sscq3bs') return $xingtai['banshun'];
            if ($playid == 'sscq3zl') return $xingtai['zaliu'];
        }
        
        
        if (strpos($playid, 'sscz3') === 0) {
            $xingtai = $checkSanXingTai($nums[1], $nums[2], $nums[3]);
            if ($playid == 'sscz3bz') return $xingtai['baozi'];
            if ($playid == 'sscz3sz') return $xingtai['shunzi'];
            if ($playid == 'sscz3dz') return $xingtai['duizi'];
            if ($playid == 'sscz3bs') return $xingtai['banshun'];
            if ($playid == 'sscz3zl') return $xingtai['zaliu'];
        }
        
        
        if (strpos($playid, 'ssch3') === 0) {
            $xingtai = $checkSanXingTai($nums[2], $nums[3], $nums[4]);
            if ($playid == 'ssch3bz') return $xingtai['baozi'];
            if ($playid == 'ssch3sz') return $xingtai['shunzi'];
            if ($playid == 'ssch3dz') return $xingtai['duizi'];
            if ($playid == 'ssch3bs') return $xingtai['banshun'];
            if ($playid == 'ssch3zl') return $xingtai['zaliu'];
        }
        
        
        
        if (strpos($playid, 'sscww') === 0) {
            $num = intval($nums[0]);
            if ($playid == 'sscwwd') return $num >= 5;
            if ($playid == 'sscwwx') return $num < 5;
            if ($playid == 'sscwwdd') return $num % 2 == 1;
            if ($playid == 'sscwwss') return $num % 2 == 0;
        }
        
        
        if (strpos($playid, 'sscqw') === 0) {
            $num = intval($nums[1]);
            if ($playid == 'sscqwd') return $num >= 5;
            if ($playid == 'sscqwx') return $num < 5;
            if ($playid == 'sscqwdd') return $num % 2 == 1;
            if ($playid == 'sscqwss') return $num % 2 == 0;
        }
        
        
        if (strpos($playid, 'sscbw') === 0) {
            $num = intval($nums[2]);
            if ($playid == 'sscbwd') return $num >= 5;
            if ($playid == 'sscbwx') return $num < 5;
            if ($playid == 'sscbwdd') return $num % 2 == 1;
            if ($playid == 'sscbwss') return $num % 2 == 0;
        }
        
        
        if (strpos($playid, 'sscsw') === 0) {
            $num = intval($nums[3]);
            if ($playid == 'sscswd') return $num >= 5;
            if ($playid == 'sscswx') return $num < 5;
            if ($playid == 'sscswdd') return $num % 2 == 1;
            if ($playid == 'sscswss') return $num % 2 == 0;
        }
        
        
        if (strpos($playid, 'sscgw') === 0) {
            $num = intval($nums[4]);
            if ($playid == 'sscgwd') return $num >= 5;
            if ($playid == 'sscgwx') return $num < 5;
            if ($playid == 'sscgwdd') return $num % 2 == 1;
            if ($playid == 'sscgwss') return $num % 2 == 0;
        }
        
        
        $sum = array_sum(array_map('intval', $nums));
        
        if ($playid == 'zhlhzhd') return $sum >= 23;
        if ($playid == 'zhlhzhx') return $sum < 23;
        if ($playid == 'zhlhzhdd') return $sum % 2 == 1;
        if ($playid == 'zhlhzhss') return $sum % 2 == 0;
        
        
        $first = intval($nums[0]);
        $last = intval($nums[4]);
        if ($playid == 'zhlhl') return $first > $last;
        if ($playid == 'zhlhh') return $first < $last;
        if ($playid == 'zhlhhe') return $first == $last;
        
        return false;
    }
    
    
    private function checkK3Win($opencode, $playid, $tzcode)
    {
        $originalNums = explode(',', $opencode);  
        $nums = $originalNums;
        sort($nums);  
        $sum = array_sum($nums);
        
        
        
        if (preg_match('/^k3hz(\d+)$/', $playid, $m)) {
            return $sum == intval($m[1]);
        }
        
        if ($playid == 'k3hzbig') return $sum >= 11;
        if ($playid == 'k3hzsmall') return $sum <= 10;
        if ($playid == 'k3hzodd') return $sum % 2 == 1;
        if ($playid == 'k3hzeven') return $sum % 2 == 0;
        
        if ($playid == 'k3hzbigodd') return $sum >= 11 && $sum % 2 == 1;
        if ($playid == 'k3hzbigeven') return $sum >= 11 && $sum % 2 == 0;
        if ($playid == 'k3hzsmallodd') return $sum <= 10 && $sum % 2 == 1;
        if ($playid == 'k3hzsmalleven') return $sum <= 10 && $sum % 2 == 0;
        
        
        $isTriple = ($nums[0] == $nums[1] && $nums[1] == $nums[2]);
        
        if ($playid == 'k3sthtx') return $isTriple;
        
        if ($playid == 'k3sthdx') {
            return $isTriple && $nums[0] == intval($tzcode);
        }
        
        if (preg_match('/^k3sth(\d)(\d)(\d)$/', $playid, $m)) {
            return $isTriple && $nums[0] == intval($m[1]);
        }
        
        
        $isThreeDiff = ($nums[0] != $nums[1] && $nums[1] != $nums[2]);
        
        if ($playid == 'k3sbthbz') {
            $tzNums = array_map('intval', explode(',', $tzcode));
            sort($tzNums);
            return $isThreeDiff && $nums == $tzNums;
        }
        
        if (preg_match('/^k3sbth([1-6])$/', $playid, $m)) {
            return $isThreeDiff && in_array(intval($m[1]), $nums);
        }
        
        
        $isSequence = ($nums[2] - $nums[1] == 1 && $nums[1] - $nums[0] == 1);
        
        if ($playid == 'k3slhtx') return $isSequence;
        
        if ($playid == 'k3slhdx') {
            $tzNums = array_map('intval', explode(',', $tzcode));
            sort($tzNums);
            return $isSequence && $nums == $tzNums;
        }
        
        
        $hasPair = ($nums[0] == $nums[1] || $nums[1] == $nums[2] || $nums[0] == $nums[2]);
        $pairNum = null;
        $diffNum = null;
        if ($nums[0] == $nums[1]) { $pairNum = $nums[0]; $diffNum = $nums[2]; }
        elseif ($nums[1] == $nums[2]) { $pairNum = $nums[1]; $diffNum = $nums[0]; }
        elseif ($nums[0] == $nums[2]) { $pairNum = $nums[0]; $diffNum = $nums[1]; }
        
        
        if ($playid == 'k3ethfx') return $hasPair && !$isTriple;
        
        if (preg_match('/^k3eth(\d)\1$/', $playid, $m)) {
            return $hasPair && !$isTriple && $pairNum == intval($m[1]);
        }
        
        if ($playid == 'k3ethdx') {
            $tzNums = array_map('intval', explode(',', $tzcode));
            if (count($tzNums) >= 3) {
                
                $tzPair = $tzNums[0];
                $tzDiff = $tzNums[2];
                return $hasPair && !$isTriple && $pairNum == $tzPair && $diffNum == $tzDiff;
            }
        }
        
        if (preg_match('/^k3ethdx(\d)\1$/', $playid, $m)) {
            return $hasPair && !$isTriple && $pairNum == intval($m[1]);
        }
        
        
        
        if ($playid == 'k3ebthbz') {
            $tzNums = array_map('intval', explode(',', $tzcode));
            return in_array($tzNums[0], $nums) && in_array($tzNums[1], $nums);
        }
        
        if (preg_match('/^k3ebth([1-6])$/', $playid, $m)) {
            return in_array(intval($m[1]), $nums);
        }
        
        
        
        if (preg_match('/^sjdx([1-6])$/', $playid, $m)) {
            return in_array(intval($m[1]), $nums);
        }
        if ($playid == 'sjdxd') return $sum >= 11;  
        if ($playid == 'sjdxs') return $sum <= 10;  
        
        
        
        $hongNums = [3, 4, 5];
        $heiNums = [1, 2, 6];
        $hongCount = count(array_intersect($originalNums, $hongNums));
        $heiCount = count(array_intersect($originalNums, $heiNums));
        
        if ($playid == 'hhmhong') return $hongCount > $heiCount;  
        if ($playid == 'hhmhei') return $heiCount > $hongCount;   
        
        if (preg_match('/^hhm([1-6])$/', $playid, $m)) {
            return in_array(intval($m[1]), $nums);
        }
        
        if ($playid == 'hhmhongd') return $hongCount > $heiCount && $sum >= 11;
        if ($playid == 'hhmhongx') return $hongCount > $heiCount && $sum <= 10;
        if ($playid == 'hhmhongdd') return $hongCount > $heiCount && $sum % 2 == 1;
        if ($playid == 'hhmhongss') return $hongCount > $heiCount && $sum % 2 == 0;
        
        if ($playid == 'hhmheid') return $heiCount > $hongCount && $sum >= 11;
        if ($playid == 'hhmheix') return $heiCount > $hongCount && $sum <= 10;
        if ($playid == 'hhmheidd') return $heiCount > $hongCount && $sum % 2 == 1;
        if ($playid == 'hhmheixx') return $heiCount > $hongCount && $sum % 2 == 0;
        
        if ($playid == 'hhmhong4hong') return $hongCount >= 2 && $heiCount <= 1;
        if ($playid == 'hhmhong4hei') return $heiCount >= 2 && $hongCount <= 1;
        if ($playid == 'hhmhong5hei') return $heiCount == 3;
        
        
        if (preg_match('/^cp(\d)(\d)$/', $playid, $m)) {
            $n1 = intval($m[1]);
            $n2 = intval($m[2]);
            return in_array($n1, $nums) && in_array($n2, $nums) && $n1 != $n2;
        }
        
        
        if (preg_match('/^dp(\d)\1$/', $playid, $m)) {
            $n = intval($m[1]);
            return $hasPair && $pairNum == $n;
        }
        
        
        if (preg_match('/^ds(\d+)$/', $playid, $m)) {
            return $sum == intval($m[1]);
        }
        
        
        if ($playid == 'wsqsqqq') return $isTriple;  
        
        if (preg_match('/^wsqs(\d)\1\1$/', $playid, $m)) {
            return $isTriple && $nums[0] == intval($m[1]);
        }
        
        Log::warning("K3 未匹配玩法: playid={$playid}, tzcode={$tzcode}");
        return false;
    }
    
    
    private function checkPk10Win($opencode, $playid, $tzcode)
    {
        $nums = array_map('intval', explode(',', $opencode));
        
        
        
        $gyhSum = $nums[0] + $nums[1];
        
        
        if ($playid == 'pk10gyhd') return $gyhSum >= 12;  
        if ($playid == 'pk10gyhx') return $gyhSum <= 11;  
        if ($playid == 'pk10gyhdd') return $gyhSum % 2 == 1;  
        if ($playid == 'pk10gyhss') return $gyhSum % 2 == 0;  
        
        
        if (preg_match('/^pk10gyhz(\d+)$/', $playid, $m)) {
            return $gyhSum == intval($m[1]);
        }
        
        
        
        if (preg_match('/^pk10mc(\d+)(da|xiao|dan|shuang)$/', $playid, $m)) {
            $position = intval($m[1]) - 1;  
            $type = $m[2];  
            
            if ($position < 0 || $position >= count($nums)) {
                return false;
            }
            
            $num = $nums[$position];
            
            if ($type == 'da') return $num >= 6;  
            if ($type == 'xiao') return $num <= 5;  
            if ($type == 'dan') return $num % 2 == 1;  
            if ($type == 'shuang') return $num % 2 == 0;  
        }
        
        
        if (preg_match('/^pk10mc(\d+)hm(\d+)$/', $playid, $m)) {
            $position = intval($m[1]) - 1;  
            $number = intval($m[2]);  
            
            if ($position < 0 || $position >= count($nums)) {
                return false;
            }
            
            return $nums[$position] == $number;
        }
        
        
        
        
        
        if (preg_match('/^pk10mc(\d+)(long|hu)$/', $playid, $m)) {
            $pair = intval($m[1]);  
            $type = $m[2];  
            
            if ($pair < 1 || $pair > 5) {
                return false;
            }
            
            $frontPos = $pair - 1;  
            $backPos = 10 - $pair;  
            
            $frontNum = $nums[$frontPos];  
            $backNum = $nums[$backPos];    
            
            if ($type == 'long') {
                return $frontNum > $backNum;  
            }
            if ($type == 'hu') {
                return $frontNum < $backNum;  
            }
        }
        
        
        if (preg_match('/^pk10lh(\d+)(long|hu|he)?$/', $playid, $m)) {
            $pair = intval($m[1]);  
            $type = isset($m[2]) ? $m[2] : '';
            
            $frontPos = $pair - 1;  
            $backPos = 10 - $pair;  
            
            if ($frontPos < 0 || $backPos >= count($nums)) {
                return false;
            }
            
            $frontNum = $nums[$frontPos];
            $backNum = $nums[$backPos];
            
            
            if ($tzcode == 'long' || $type == 'long') {
                return $frontNum > $backNum;  
            }
            if ($tzcode == 'hu' || $type == 'hu') {
                return $frontNum < $backNum;  
            }
            if ($tzcode == 'he' || $type == 'he') {
                return $frontNum == $backNum;  
            }
        }
        
        
        
        if (strpos($playid, 'lmp_') === 0 || strpos($playid, 'gyh_') === 0) {
            return \app\library\WinChecker::checkPk10Double($opencode, $playid, $tzcode);
        }
        
        
        if (strpos($playid, 'yzwm_') === 0) {
            
            if (preg_match('/d(\d+)m_hm(\d+)/', $playid, $matches)) {
                $position = intval($matches[1]) - 1; 
                $number = intval($matches[2]); 
                return intval($nums[$position]) == $number;
            }
        }
        
        
        if ($playid == 'qian2_zhixuan' || $playid == 'q2_zx' || $playid == 'pk10q2zx') {
            $open2 = implode(',', array_slice($nums, 0, 2));
            return $open2 === $tzcode;
        }
        
        if ($playid == 'qian3_zhixuan' || $playid == 'q3_zx' || $playid == 'pk10q3zx') {
            $open3 = implode(',', array_slice($nums, 0, 3));
            return $open3 === $tzcode;
        }
        
        
        if ($playid == 'pk10q4zx') {
            $open4 = implode(',', array_slice($nums, 0, 4));
            return $open4 === $tzcode;
        }
        
        
        if ($playid == 'pk10q5zx') {
            $open5 = implode(',', array_slice($nums, 0, 5));
            return $open5 === $tzcode;
        }
        
        Log::warning("PK10 未匹配玩法: playid={$playid}, tzcode={$tzcode}");
        return false;
    }
    
    
    private function checkX5Win($opencode, $playid, $tzcode)
    {
        $nums = array_map('intval', explode(',', $opencode));
        $tzNums = array_map('intval', explode(',', $tzcode));
        
        
        
        if ($playid == 'x5qsfs' || $playid == 'x5qsds') {
            $open3 = array_slice($nums, 0, 3);
            return $open3 == array_slice($tzNums, 0, 3);
        }
        
        
        
        if ($playid == 'x5zsfs' || $playid == 'x5zsds') {
            $open3 = array_slice($nums, 1, 3);  
            return $open3 == array_slice($tzNums, 0, 3);
        }
        
        
        
        if ($playid == 'x5hsfs' || $playid == 'x5hsds') {
            $open3 = array_slice($nums, 2, 3);  
            return $open3 == array_slice($tzNums, 0, 3);
        }
        
        
        
        if ($playid == 'x5qszx' || $playid == 'x5qsdt') {
            $open3 = array_slice($nums, 0, 3);
            sort($open3);
            $tz3 = array_slice($tzNums, 0, 3);
            sort($tz3);
            return $open3 == $tz3;
        }
        
        
        
        if ($playid == 'x5zszx' || $playid == 'x5zsdt') {
            $open3 = array_slice($nums, 1, 3);
            sort($open3);
            $tz3 = array_slice($tzNums, 0, 3);
            sort($tz3);
            return $open3 == $tz3;
        }
        
        
        
        if ($playid == 'x5hszx' || $playid == 'x5hsdt') {
            $open3 = array_slice($nums, 2, 3);
            sort($open3);
            $tz3 = array_slice($tzNums, 0, 3);
            sort($tz3);
            return $open3 == $tz3;
        }
        
        
        
        if ($playid == 'x5qefs' || $playid == 'x5qeds') {
            return $nums[0] == $tzNums[0] && $nums[1] == $tzNums[1];
        }
        
        
        
        if ($playid == 'x5hefs' || $playid == 'x5heds') {
            return $nums[3] == $tzNums[0] && $nums[4] == $tzNums[1];
        }
        
        
        
        if ($playid == 'x5qezx' || $playid == 'x5qedt') {
            $open2 = array_slice($nums, 0, 2);
            return in_array($tzNums[0], $open2) && in_array($tzNums[1], $open2);
        }
        
        
        
        if ($playid == 'x5hezx' || $playid == 'x5hedt') {
            $open2 = array_slice($nums, 3, 2);
            return in_array($tzNums[0], $open2) && in_array($tzNums[1], $open2);
        }
        
        
        
        if (preg_match('/^x5rx(\d+)z(\d+)$/', $playid, $m)) {
            $selectCount = intval($m[1]);
            $winCount = intval($m[2]);
            $matched = 0;
            foreach ($tzNums as $tzNum) {
                if (in_array($tzNum, $nums)) $matched++;
            }
            return $matched >= $winCount;
        }
        
        
        if (preg_match('/^x5rxds(\d+)z(\d+)$/', $playid, $m)) {
            $selectCount = intval($m[1]);
            $winCount = intval($m[2]);
            $matched = 0;
            foreach ($tzNums as $tzNum) {
                if (in_array($tzNum, $nums)) $matched++;
            }
            return $matched >= $winCount;
        }
        
        
        
        if ($playid == 'x5dwd') {
            
            if (strpos($tzcode, ':') !== false) {
                list($pos, $num) = explode(':', $tzcode);
                $pos = intval($pos) - 1;
                $num = intval($num);
                return isset($nums[$pos]) && $nums[$pos] == $num;
            }
            
            return in_array(intval($tzcode), $nums);
        }
        
        
        
        if ($playid == 'x5bdwqs') {
            $open3 = array_slice($nums, 0, 3);
            return in_array($tzNums[0], $open3);
        }
        
        
        if ($playid == 'x5bdwzs') {
            $open3 = array_slice($nums, 1, 3);
            return in_array($tzNums[0], $open3);
        }
        
        
        if ($playid == 'x5bdwhs') {
            $open3 = array_slice($nums, 2, 3);
            return in_array($tzNums[0], $open3);
        }
        
        
        
        $sum = array_sum($nums);
        $sumTail = $sum % 10;  
        
        
        if ($playid == 'x5niuniu_nn') return $sumTail == 0;
        
        if (preg_match('/^x5niuniu_n(\d)$/', $playid, $m)) {
            return $sumTail == intval($m[1]);
        }
        
        if ($playid == 'x5niuniu_mn') return $sumTail != 0;
        
        
        $oddCount = 0;
        $evenCount = 0;
        foreach ($nums as $n) {
            if ($n % 2 == 1) $oddCount++;
            else $evenCount++;
        }
        if ($playid == 'x5dds_5d0s') return $oddCount == 5 && $evenCount == 0;
        if ($playid == 'x5dds_4d1s') return $oddCount == 4 && $evenCount == 1;
        if ($playid == 'x5dds_3d2s') return $oddCount == 3 && $evenCount == 2;
        if ($playid == 'x5dds_2d3s') return $oddCount == 2 && $evenCount == 3;
        if ($playid == 'x5dds_1d4s') return $oddCount == 1 && $evenCount == 4;
        if ($playid == 'x5dds_0d5s') return $oddCount == 0 && $evenCount == 5;
        
        
        $sortedNums = $nums;
        sort($sortedNums);
        $midNum = $sortedNums[2];  
        if (preg_match('/^x5czw_(\d+)$/', $playid, $m)) {
            return $midNum == intval($m[1]);
        }
        
        
        
        if ($playid == 'x5lh_tw_long') return $nums[0] > $nums[4];
        if ($playid == 'x5lh_tw_hu') return $nums[0] < $nums[4];
        
        if ($playid == 'x5lh_q2_long') return $nums[0] > $nums[1];
        if ($playid == 'x5lh_q2_hu') return $nums[0] < $nums[1];
        
        if ($playid == 'x5lh_h2_long') return $nums[3] > $nums[4];
        if ($playid == 'x5lh_h2_hu') return $nums[3] < $nums[4];
        
        
        if (preg_match('/^x5bbc_(\d+)$/', $playid, $m)) {
            $bbcNum = intval($m[1]);
            return !in_array($bbcNum, $nums);  
        }
        
        
        
        if (preg_match('/^x5rxdt(\d+)z(\d+)$/', $playid, $m)) {
            
            if (strpos($tzcode, '|') !== false) {
                $parts = explode('|', $tzcode);
                $danStr = str_replace('dan:', '', $parts[0]);
                $tuoStr = str_replace('tuo:', '', $parts[1]);
                $danNums = array_map('intval', explode(',', $danStr));
                $tuoNums = array_map('intval', explode(',', $tuoStr));
                
                
                foreach ($danNums as $d) {
                    if (!in_array($d, $nums)) return false;
                }
                
                $tuoMatched = 0;
                foreach ($tuoNums as $t) {
                    if (in_array($t, $nums)) $tuoMatched++;
                }
                
                $needMatch = intval($m[2]) - count($danNums);
                return $tuoMatched >= $needMatch;
            }
            return false;
        }
        
        
        
        if (strpos($playid, 'sum_') === 0) {
            return \app\library\WinChecker::checkX5Double($opencode, $playid, $tzcode);
        }
        
        
        if ($playid == 'qian1' || $playid == 'q1') {
            return $nums[0] == intval($tzcode);
        }
        
        
        if ($playid == 'qian2_zhixuan' || $playid == 'q2_zx') {
            return $nums[0] == $tzNums[0] && $nums[1] == $tzNums[1];
        }
        
        
        if ($playid == 'qian2_zuxuan' || $playid == 'q2_zu') {
            return (in_array($tzNums[0], array_slice($nums, 0, 2)) && 
                    in_array($tzNums[1], array_slice($nums, 0, 2)));
        }
        
        
        if ($playid == 'qian3_zhixuan' || $playid == 'q3_zx') {
            return $nums[0] == $tzNums[0] && $nums[1] == $tzNums[1] && $nums[2] == $tzNums[2];
        }
        
        
        if ($playid == 'qian3_zuxuan' || $playid == 'q3_zu') {
            $open3 = array_slice($nums, 0, 3);
            sort($open3);
            sort($tzNums);
            return implode(',', $open3) === implode(',', $tzNums);
        }
        
        
        if ($playid == 'renxuan1' || $playid == 'rx1') {
            return in_array(intval($tzcode), $nums);
        }
        
        
        if ($playid == 'renxuan2' || $playid == 'rx2') {
            return in_array($tzNums[0], $nums) && in_array($tzNums[1], $nums);
        }
        
        
        if ($playid == 'renxuan3' || $playid == 'rx3') {
            $matched = 0;
            foreach ($tzNums as $tzNum) {
                if (in_array($tzNum, $nums)) $matched++;
            }
            return $matched >= 3;
        }
        
        Log::warning("X5 未匹配玩法: playid={$playid}, tzcode={$tzcode}");
        return false;
    }
    
    
    private function checkLhcWin($opencode, $playid, $tzcode)
    {
        $nums = array_map('intval', explode(',', $opencode));
        $zhengMa = array_slice($nums, 0, 6);  
        $teMa = intval($nums[6]);              
        $allNums = $nums;                      
        
        
        
        $getShengXiao = function($num) {
            $zodiacList = ['shu', 'niu', 'hu', 'tu', 'long', 'she', 'ma', 'yang', 'hou', 'ji', 'gou', 'zhu'];
            
            $baseYear = 2024;
            $baseZodiacIndex = 4; 
            $offset = ($baseZodiacIndex - ($num - 1) % 12 + 12) % 12;
            return $zodiacList[$offset];
        };
        
        
        $getBose = function($num) {
            $hong = [1,2,7,8,12,13,18,19,23,24,29,30,34,35,40,45,46];
            $lan = [3,4,9,10,14,15,20,25,26,31,36,37,41,42,47,48];
            $lv = [5,6,11,16,17,21,22,27,28,32,33,38,39,43,44,49];
            if (in_array($num, $hong)) return 'hong';
            if (in_array($num, $lan)) return 'lan';
            if (in_array($num, $lv)) return 'lv';
            return '';
        };
        
        
        $getWuXing = function($num) {
            $jin = [2,3,10,11,24,25,32,33,40,41];
            $mu = [4,5,18,19,26,27,34,35,48,49];
            $shui = [6,7,12,13,20,21,28,29,42,43];
            $huo = [8,9,14,15,22,23,36,37,44,45];
            $tu = [1,16,17,30,31,38,39,46,47];
            if (in_array($num, $jin)) return 'jin';
            if (in_array($num, $mu)) return 'mu';
            if (in_array($num, $shui)) return 'shui';
            if (in_array($num, $huo)) return 'huo';
            if (in_array($num, $tu)) return 'tu';
            return '';
        };
        
        
        if ($playid == 'tmzx') {
            $tzNums = array_map('intval', explode(',', $tzcode));
            return in_array($teMa, $tzNums);
        }
        
        
        if (strpos($playid, 'tmlm') === 0) {
            $tmSum = floor($teMa / 10) + ($teMa % 10);  
            $tmWei = $teMa % 10;  
            
            if ($playid == 'tmlmda') return $teMa >= 25;
            if ($playid == 'tmlmxiao') return $teMa >= 1 && $teMa <= 24;
            if ($playid == 'tmlmdan') return $teMa % 2 == 1;
            if ($playid == 'tmlmshuang') return $teMa % 2 == 0;
            if ($playid == 'tmlmdadan') return $teMa >= 25 && $teMa % 2 == 1;
            if ($playid == 'tmlmdashuang') return $teMa >= 25 && $teMa % 2 == 0;
            if ($playid == 'tmlmxiaodan') return $teMa <= 24 && $teMa % 2 == 1;
            if ($playid == 'tmlmxiaoshuang') return $teMa <= 24 && $teMa % 2 == 0;
            if ($playid == 'tmlmheda') return $tmSum >= 7;
            if ($playid == 'tmlmhexiao') return $tmSum <= 6;
            if ($playid == 'tmlmhedan') return $tmSum % 2 == 1;
            if ($playid == 'tmlmheshuang') return $tmSum % 2 == 0;
            if ($playid == 'tmlmweida') return $tmWei >= 5;
            if ($playid == 'tmlmweixiao') return $tmWei <= 4;
            if ($playid == 'tmlmhongbo') return $getBose($teMa) == 'hong';
            if ($playid == 'tmlmlvbo') return $getBose($teMa) == 'lv';
            if ($playid == 'tmlmlanbo') return $getBose($teMa) == 'lan';
            
            $jiaqin = ['niu', 'ma', 'yang', 'ji', 'gou', 'zhu'];
            if ($playid == 'tmlmjiaqin') return in_array($getShengXiao($teMa), $jiaqin);
            if ($playid == 'tmlmyeshou') return !in_array($getShengXiao($teMa), $jiaqin);
        }
        
        
        if ($playid == 'zmrx') {
            $tzNums = array_map('intval', explode(',', $tzcode));
            foreach ($tzNums as $tz) {
                if (in_array($tz, $zhengMa)) return true;
            }
            return false;
        }
        
        
        if (preg_match('/^zm([1-6])t$/', $playid, $m)) {
            $pos = intval($m[1]) - 1;
            $tzNums = array_map('intval', explode(',', $tzcode));
            return in_array($zhengMa[$pos], $tzNums);
        }
        
        
        if (preg_match('/^zm([1-6])lm(.+)$/', $playid, $m)) {
            $pos = intval($m[1]) - 1;
            $num = $zhengMa[$pos];
            $type = $m[2];
            $numSum = floor($num / 10) + ($num % 10);
            $numWei = $num % 10;
            
            if ($type == 'da') return $num >= 25;
            if ($type == 'xiao') return $num >= 1 && $num <= 24;
            if ($type == 'dan') return $num % 2 == 1;
            if ($type == 'shuang') return $num % 2 == 0;
            if ($type == 'dadan') return $num >= 25 && $num % 2 == 1;
            if ($type == 'dashuang') return $num >= 25 && $num % 2 == 0;
            if ($type == 'xiaodan') return $num <= 24 && $num % 2 == 1;
            if ($type == 'xiaoshuang') return $num <= 24 && $num % 2 == 0;
            if ($type == 'heda') return $numSum >= 7;
            if ($type == 'hexiao') return $numSum <= 6;
            if ($type == 'hedan') return $numSum % 2 == 1;
            if ($type == 'heshuang') return $numSum % 2 == 0;
            if ($type == 'weida') return $numWei >= 5;
            if ($type == 'weixiao') return $numWei <= 4;
            if ($type == 'hongbo') return $getBose($num) == 'hong';
            if ($type == 'lvbo') return $getBose($num) == 'lv';
            if ($type == 'lanbo') return $getBose($num) == 'lan';
            $jiaqin = ['niu', 'ma', 'yang', 'ji', 'gou', 'zhu'];
            if ($type == 'jiaqin') return in_array($getShengXiao($num), $jiaqin);
            if ($type == 'yeshou') return !in_array($getShengXiao($num), $jiaqin);
        }
        
        
        $tzNums = array_map('intval', explode(',', $tzcode));
        
        if ($playid == 'lm4qz') {
            $matched = count(array_intersect($tzNums, $allNums));
            return $matched >= 4;
        }
        
        if ($playid == 'lm3qz') {
            $matched = count(array_intersect($tzNums, $allNums));
            return $matched >= 3;
        }
        
        if ($playid == 'lm3z2') {
            $matched = count(array_intersect($tzNums, $allNums));
            return $matched >= 2;
        }
        
        if ($playid == 'lm2qz') {
            $matched = count(array_intersect($tzNums, $allNums));
            return $matched >= 2;
        }
        
        if ($playid == 'lm2zt') {
            return in_array($teMa, $tzNums) && count(array_intersect($tzNums, $zhengMa)) >= 1;
        }
        
        if ($playid == 'lmtc') {
            return in_array($teMa, $tzNums) && count(array_intersect($tzNums, $zhengMa)) >= 1;
        }
        
        
        $tmBose = $getBose($teMa);
        if ($playid == 'hongda') return $tmBose == 'hong' && $teMa >= 25;
        if ($playid == 'hongxiao') return $tmBose == 'hong' && $teMa <= 24;
        if ($playid == 'hongdan') return $tmBose == 'hong' && $teMa % 2 == 1;
        if ($playid == 'hongshuang') return $tmBose == 'hong' && $teMa % 2 == 0;
        if ($playid == 'lvda') return $tmBose == 'lv' && $teMa >= 25;
        if ($playid == 'lvxiao') return $tmBose == 'lv' && $teMa <= 24;
        if ($playid == 'lvdan') return $tmBose == 'lv' && $teMa % 2 == 1;
        if ($playid == 'lvshuang') return $tmBose == 'lv' && $teMa % 2 == 0;
        if ($playid == 'landa') return $tmBose == 'lan' && $teMa >= 25;
        if ($playid == 'lanxiao') return $tmBose == 'lan' && $teMa <= 24;
        if ($playid == 'landan') return $tmBose == 'lan' && $teMa % 2 == 1;
        if ($playid == 'lanshuang') return $tmBose == 'lan' && $teMa % 2 == 0;
        
        $tmSum = floor($teMa / 10) + ($teMa % 10);
        if ($playid == 'honghedan') return $tmBose == 'hong' && $tmSum % 2 == 1;
        if ($playid == 'hongheshuang') return $tmBose == 'hong' && $tmSum % 2 == 0;
        if ($playid == 'lvhedan') return $tmBose == 'lv' && $tmSum % 2 == 1;
        if ($playid == 'lvheshuang') return $tmBose == 'lv' && $tmSum % 2 == 0;
        if ($playid == 'lanhedan') return $tmBose == 'lan' && $tmSum % 2 == 1;
        if ($playid == 'lanheshuang') return $tmBose == 'lan' && $tmSum % 2 == 0;
        
        
        if (preg_match('/^sxtx(.+)$/', $playid, $m)) {
            $sx = $m[1];
            return $getShengXiao($teMa) == $sx;
        }
        
        
        if (preg_match('/^sx1x(.+)$/', $playid, $m)) {
            $sx = $m[1];
            foreach ($allNums as $num) {
                if ($getShengXiao($num) == $sx) return true;
            }
            return false;
        }
        
        
        if (preg_match('/^sxzx(.+)$/', $playid, $m)) {
            $sx = $m[1];
            foreach ($zhengMa as $num) {
                if ($getShengXiao($num) == $sx) return true;
            }
            return false;
        }
        
        
        if (preg_match('/^sxhx(\d+)$/', $playid, $m)) {
            
            $tmSx = $getShengXiao($teMa);
            
            $selectedSx = explode(',', $tzcode);
            return in_array($tmSx, $selectedSx);
        }
        
        
        
        $allSx = array_unique(array_map($getShengXiao, $allNums));
        $sxCount = count($allSx);
        if ($playid == 'zx2xiao') return $sxCount == 2;
        if ($playid == 'zx3xiao') return $sxCount == 3;
        if ($playid == 'zx4xiao') return $sxCount == 4;
        if ($playid == 'zx5xiao') return $sxCount == 5;
        if ($playid == 'zx6xiao') return $sxCount == 6;
        if ($playid == 'zx7xiao') return $sxCount == 7;
        if ($playid == 'zxdan') return $sxCount % 2 == 1;
        if ($playid == 'zxshuang') return $sxCount % 2 == 0;
        
        
        if (preg_match('/^sx([234])xl$/', $playid, $m)) {
            $need = intval($m[1]);
            $selectedSx = explode(',', $tzcode);
            $matched = 0;
            foreach ($selectedSx as $sx) {
                foreach ($allNums as $num) {
                    if ($getShengXiao($num) == $sx) {
                        $matched++;
                        break;
                    }
                }
            }
            return $matched >= $need;
        }
        
        
        $tmTou = floor($teMa / 10);  
        $tmWei = $teMa % 10;         
        if ($playid == 'lingtou') return $tmTou == 0;
        if ($playid == 'yitou') return $tmTou == 1;
        if ($playid == 'ertou') return $tmTou == 2;
        if ($playid == 'santou') return $tmTou == 3;
        if ($playid == 'sitou') return $tmTou == 4;
        if ($playid == 'lingwei') return $tmWei == 0;
        if ($playid == 'yiwei') return $tmWei == 1;
        if ($playid == 'erwei') return $tmWei == 2;
        if ($playid == 'sanwei') return $tmWei == 3;
        if ($playid == 'siwei') return $tmWei == 4;
        if ($playid == 'wuwei') return $tmWei == 5;
        if ($playid == 'liuwei') return $tmWei == 6;
        if ($playid == 'qiwei') return $tmWei == 7;
        if ($playid == 'bawei') return $tmWei == 8;
        if ($playid == 'jiuwei') return $tmWei == 9;
        
        
        if (preg_match('/^ws([234])wl$/', $playid, $m)) {
            $need = intval($m[1]);
            $selectedWei = array_map('intval', explode(',', $tzcode));
            $matchedWei = [];
            foreach ($allNums as $num) {
                $wei = $num % 10;
                if (in_array($wei, $selectedWei)) {
                    $matchedWei[$wei] = true;
                }
            }
            return count($matchedWei) >= $need;
        }
        
        
        if ($playid == 'hongbo') return $getBose($teMa) == 'hong';
        if ($playid == 'lvbo') return $getBose($teMa) == 'lv';
        if ($playid == 'lanbo') return $getBose($teMa) == 'lan';
        
        
        if ($playid == 'jin') return $getWuXing($teMa) == 'jin';
        if ($playid == 'mu') return $getWuXing($teMa) == 'mu';
        if ($playid == 'shui') return $getWuXing($teMa) == 'shui';
        if ($playid == 'huo') return $getWuXing($teMa) == 'huo';
        if ($playid == 'tu') return $getWuXing($teMa) == 'tu';
        
        
        if (preg_match('/^bz(\d+)bz$/', $playid, $m)) {
            $tzNums = array_map('intval', explode(',', $tzcode));
            
            $matched = count(array_intersect($tzNums, $allNums));
            return $matched == 0;
        }
        
        Log::warning("LHC 未匹配玩法: playid={$playid}, tzcode={$tzcode}");
        return false;
    }
    
    
    private function checkDwcWinWithCount($opencode, $playid, $tzcode)
    {
        try {
            
            
            $ranks = explode(',', $opencode);
            if (count($ranks) != 6) {
                Log::warning("动物彩Mở thưởng号码格式Lỗi: {$opencode}");
                return ['count' => 0];
            }
            
            
            
            $rankToAnimal = [];
            for ($i = 0; $i < 6; $i++) {
                $rankToAnimal['rank' . ($i + 1)] = intval($ranks[$i]);
            }
            
            
            $bets = explode('|', $tzcode);
            $winCount = 0;
            
            foreach ($bets as $bet) {
                
                $parts = explode(':', $bet);
                if (count($parts) < 2) {
                    continue;
                }
                
                $rankId = trim($parts[0]);      
                $animalId = intval($parts[1]);  
                
                
                if (isset($rankToAnimal[$rankId]) && $rankToAnimal[$rankId] == $animalId) {
                    $winCount++;
                }
            }
            
            return ['count' => $winCount];
            
        } catch (\Exception $e) {
            Log::error("动物彩Trúng thưởng判断异常: " . $e->getMessage());
            return ['count' => 0];
        }
    }
    
    
    private function settleRobotBets()
    {
        try {
            
            $robotBets = Db::table('caipiao_touzhu as t')
                ->leftJoin('caipiao_kaijiang as k', function($join) {
                    $join->on('t.cpname', '=', 'k.name')
                         ->on('t.expect', '=', 'k.expect');
                })
                ->select('t.*', 'k.opencode')
                ->where('t.isdraw', 0)
                ->where('t.source', 'robot')
                ->whereNotNull('k.opencode')
                ->where('k.opencode', '!=', '')
                ->where('k.opentime', '<=', time())
                ->limit(100)
                ->get();
            
            if ($robotBets->isEmpty()) {
                return;
            }
            
            foreach ($robotBets as $bet) {
                try {
                    
                    $isWin = false;
                    $winAmount = 0;
                    
                    if ($bet->playid === 'xy28_combined') {
                        
                        $betItems = json_decode($bet->tzcode, true);
                        if (!empty($betItems)) {
                            $nums = explode(',', $bet->opencode);
                            $sum = array_sum(array_slice(array_map('intval', $nums), 0, 3));
                            
                            foreach ($betItems as $item) {
                                $playid = $item['playid'];
                                $betAmount = floatval($item['amount'] ?? 0);
                                
                                if ($this->checkXy28Win($bet->opencode, $playid, '')) {
                                    $isWin = true;
                                    
                                    $play = Db::table('caipiao_wanfa')->where('playid', $playid)->first();
                                    $rate = $play ? floatval($play->rate) : 1.98;
                                    $winAmount += $betAmount * $rate;
                                }
                            }
                        }
                    } else {
                        
                        $isWin = $this->checkXy28Win($bet->opencode, $bet->playid, $bet->tzcode);
                        if ($isWin) {
                            $play = Db::table('caipiao_wanfa')->where('playid', $bet->playid)->first();
                            $rate = $play ? floatval($play->rate) : 1.98;
                            $winAmount = $bet->amount * $rate;
                        }
                    }
                    
                    
                    Db::table('caipiao_touzhu')
                        ->where('id', $bet->id)
                        ->update([
                            'isdraw' => $isWin ? 1 : -1,
                            'okamount' => $winAmount,
                            'opencode' => $bet->opencode,
                            'winorno' => $isWin ? 1 : 0,
                        ]);
                    
                } catch (\Exception $e) {
                    Log::error("机器人Đặt cược结算Thất bại ID:{$bet->id}: " . $e->getMessage());
                }
            }
            
        } catch (\Exception $e) {
            Log::error("机器人Đặt cược批量结算异常: " . $e->getMessage());
        }
    }
    
    
    private function mapPlayId($playid)
    {
        $map = [
            'big' => 'c_big',
            'small' => 'c_small',
            'odd' => 'c_odd',
            'even' => 'c_even',
            'big_odd' => 'c_big_odd',
            'big_even' => 'c_big_even',
            'small_odd' => 'c_small_odd',
            'small_even' => 'c_small_even',
            'extreme_big' => 'c_jida',
            'extreme_small' => 'c_jixiao',
            'jida' => 'c_jida',
            'jixiao' => 'c_jixiao',
            'long' => 'c_long',
            'hu' => 'c_hu',
            'bao' => 'c_bao',
            'baozi' => 'c_baozi',
            'duizi' => 'c_duizi',
            'shunzi' => 'c_shunzi',
        ];
        
        return $map[$playid] ?? $playid;
    }
    
    
    private function getDefaultOdds($playid)
    {
        
        $defaultOdds = [
            
            'big' => 1.98, 'small' => 1.98, 'odd' => 1.98, 'even' => 1.98,
            'c_big' => 1.98, 'c_small' => 1.98, 'c_odd' => 1.98, 'c_even' => 1.98,
            
            'big_odd' => 4.20, 'big_even' => 4.60, 'small_odd' => 4.60, 'small_even' => 4.20,
            'c_big_odd' => 4.20, 'c_big_even' => 4.60, 'c_small_odd' => 4.60, 'c_small_even' => 4.20,
            
            'extreme_big' => 15.00, 'extreme_small' => 15.00,
            'c_jida' => 15.00, 'c_jixiao' => 15.00, 'jida' => 15.00, 'jixiao' => 15.00,
            
            'long' => 2.85, 'hu' => 2.85, 'bao' => 2.85,
            'c_long' => 2.85, 'c_hu' => 2.85, 'c_bao' => 2.85,
            
            'baozi' => 66.00, 'duizi' => 3.20, 'shunzi' => 14.00,
            'c_baozi' => 66.00, 'c_duizi' => 3.20, 'c_shunzi' => 14.00,
        ];
        
        
        if (preg_match('/^(hz_)?(\d+)$/', $playid, $m)) {
            $num = intval($m[2]);
            $hzOdds = [
                0 => 488, 1 => 128, 2 => 88, 3 => 58, 4 => 48, 5 => 38,
                6 => 28, 7 => 28, 8 => 28, 9 => 28, 10 => 28, 11 => 28,
                12 => 28, 13 => 28, 14 => 28, 15 => 28, 16 => 28, 17 => 28,
                18 => 28, 19 => 28, 20 => 28, 21 => 28, 22 => 38, 23 => 48,
                24 => 58, 25 => 88, 26 => 128, 27 => 488
            ];
            return $hzOdds[$num] ?? 28;
        }
        
        return $defaultOdds[$playid] ?? 1.98; 
    }
}

