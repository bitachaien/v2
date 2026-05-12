<?php

namespace app\controller\api;

use support\Request;
use support\Db;
use support\Log;

class ChaseController
{
    
    public function records(Request $request)
    {
        try {
            $userId = $request->userId ?? 0;
            if (!$userId) {
                return json(['code' => 401, 'message' => '请先登录']);
            }

            $page = max(1, (int)$request->get('page', 1));
            $pageSize = min(50, max(1, (int)$request->get('pageSize', 20)));
            $status = $request->get('status', ''); 
            $lotteryCode = $request->get('lotteryCode', '');

            
            $query = Db::table('caipiao_touzhu')
                ->select([
                    'chaseno',
                    'cpname',
                    'cptitle',
                    'playid',
                    'playtitle',
                    'tzcode',
                    'stoponwin',
                    Db::raw('MIN(expect) as start_expect'),
                    Db::raw('MAX(expect) as end_expect'),
                    Db::raw('COUNT(*) as total_periods'),
                    Db::raw('SUM(CASE WHEN isdraw = 1 THEN 1 ELSE 0 END) as drawn_periods'),
                    Db::raw('SUM(CASE WHEN isdraw = 0 AND iscancel = 0 THEN 1 ELSE 0 END) as pending_periods'),
                    Db::raw('SUM(CASE WHEN iscancel = 1 THEN 1 ELSE 0 END) as cancelled_periods'),
                    Db::raw('SUM(amount) as total_amount'),
                    Db::raw('SUM(okamount) as total_win'),
                    Db::raw('MIN(oddtime) as created_at')
                ])
                ->where('uid', $userId)
                ->whereNotNull('chaseno')
                ->where('chaseno', '!=', '')
                ->groupBy('chaseno', 'cpname', 'cptitle', 'playid', 'playtitle', 'tzcode', 'stoponwin');

            
            if ($lotteryCode) {
                $query->where('cpname', $lotteryCode);
            }

            
            $allChases = $query->orderBy('created_at', 'desc')->get();

            
            $result = [];
            foreach ($allChases as $chase) {
                
                $chaseStatus = 'ongoing'; 
                if ($chase->cancelled_periods == $chase->total_periods) {
                    $chaseStatus = 'cancelled'; 
                } else if ($chase->pending_periods == 0) {
                    $chaseStatus = 'finished'; 
                }

                
                if ($status) {
                    if ($status === 'ongoing' && $chaseStatus !== 'ongoing') continue;
                    if ($status === 'finished' && $chaseStatus !== 'finished' && $chaseStatus !== 'cancelled') continue;
                }

                $result[] = [
                    'id' => $chase->chaseno,
                    'chaseNo' => $chase->chaseno,
                    'lotteryCode' => $chase->cpname,
                    'lotteryName' => $chase->cptitle,
                    'playId' => $chase->playid,
                    'playName' => $chase->playtitle,
                    'betContent' => $chase->tzcode,
                    'startIssue' => $chase->start_expect,
                    'endIssue' => $chase->end_expect,
                    'periods' => (int)$chase->total_periods,
                    'drawnPeriods' => (int)$chase->drawn_periods,
                    'pendingPeriods' => (int)$chase->pending_periods,
                    'cancelledPeriods' => (int)$chase->cancelled_periods,
                    'totalAmount' => number_format($chase->total_amount, 2, '.', ''),
                    'totalWin' => number_format($chase->total_win, 2, '.', ''),
                    'stopOnWin' => (int)$chase->stoponwin,
                    'status' => $chaseStatus,
                    'createTime' => date('Y-m-d H:i:s', $chase->created_at)
                ];
            }

            
            $total = count($result);
            $list = array_slice($result, ($page - 1) * $pageSize, $pageSize);

            return json([
                'code' => 0,
                'message' => '获取成功',
                'data' => [
                    'list' => $list,
                    'total' => $total,
                    'page' => $page,
                    'pageSize' => $pageSize
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('获取追号记录失败: ' . $e->getMessage());
            return json(['code' => 500, 'message' => '获取失败: ' . $e->getMessage()]);
        }
    }

    
    public function detail(Request $request, $chaseNo)
    {
        try {
            $userId = $request->userId ?? 0;
            if (!$userId) {
                return json(['code' => 401, 'message' => '请先登录']);
            }

            
            $bets = Db::table('caipiao_touzhu')
                ->where('uid', $userId)
                ->where('chaseno', $chaseNo)
                ->orderBy('expect', 'asc')
                ->get();

            if (count($bets) == 0) {
                return json(['code' => 404, 'message' => '追号记录不存在']);
            }

            $first = $bets[0];
            $totalAmount = 0;
            $totalWin = 0;
            $drawnCount = 0;
            $cancelledCount = 0;
            $pendingCount = 0;
            $details = [];

            foreach ($bets as $bet) {
                $totalAmount += $bet->amount;
                $totalWin += $bet->okamount ?? 0;
                
                if ($bet->iscancel ?? 0) {
                    $cancelledCount++;
                    $betStatus = 'cancelled';
                } else if ($bet->isdraw) {
                    $drawnCount++;
                    $betStatus = $bet->okamount > 0 ? 'win' : 'lose';
                } else {
                    $pendingCount++;
                    $betStatus = 'pending';
                }

                $details[] = [
                    'id' => $bet->id,
                    'trano' => $bet->trano,
                    'expect' => $bet->expect,
                    'amount' => number_format($bet->amount, 2, '.', ''),
                    'multiple' => $bet->beishu ?? 1,
                    'winAmount' => number_format($bet->okamount ?? 0, 2, '.', ''),
                    'openCode' => $bet->opencode ?? '',
                    'status' => $betStatus,
                    'drawTime' => $bet->opentime ? date('Y-m-d H:i:s', $bet->opentime) : ''
                ];
            }

            
            $overallStatus = 'ongoing';
            if ($cancelledCount == count($bets)) {
                $overallStatus = 'cancelled';
            } else if ($pendingCount == 0) {
                $overallStatus = 'finished';
            }

            return json([
                'code' => 0,
                'message' => '获取成功',
                'data' => [
                    'chaseNo' => $chaseNo,
                    'lotteryCode' => $first->cpname,
                    'lotteryName' => $first->cptitle,
                    'playName' => $first->playtitle,
                    'betContent' => $first->tzcode,
                    'totalPeriods' => count($bets),
                    'drawnPeriods' => $drawnCount,
                    'pendingPeriods' => $pendingCount,
                    'cancelledPeriods' => $cancelledCount,
                    'totalAmount' => number_format($totalAmount, 2, '.', ''),
                    'totalWin' => number_format($totalWin, 2, '.', ''),
                    'profit' => number_format($totalWin - $totalAmount, 2, '.', ''),
                    'stopOnWin' => (int)($first->stoponwin ?? 0),
                    'status' => $overallStatus,
                    'createTime' => date('Y-m-d H:i:s', $first->oddtime),
                    'details' => $details
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('获取追号详情失败: ' . $e->getMessage());
            return json(['code' => 500, 'message' => '获取失败: ' . $e->getMessage()]);
        }
    }

    
    public function cancel(Request $request, $chaseNo)
    {
        try {
            $userId = $request->userId ?? 0;
            if (!$userId) {
                return json(['code' => 401, 'message' => '请先登录']);
            }

            if (!$chaseNo) {
                return json(['code' => 400, 'message' => '追号编号不能为空']);
            }

            
            $pendingBets = Db::table('caipiao_touzhu')
                ->where('uid', $userId)
                ->where('chaseno', $chaseNo)
                ->where('isdraw', 0)
                ->where(function($q) {
                    $q->whereNull('iscancel')->orWhere('iscancel', 0);
                })
                ->get();

            if (count($pendingBets) == 0) {
                return json(['code' => 400, 'message' => '没有可取消的追号订单']);
            }

            
            $refundAmount = 0;
            foreach ($pendingBets as $bet) {
                $refundAmount += $bet->amount;
            }

            
            $user = Db::table('caipiao_member')->where('id', $userId)->first();
            if (!$user) {
                return json(['code' => 404, 'message' => '用户不存在']);
            }

            Db::beginTransaction();
            try {
                $now = time();
                $cancelledIds = [];

                
                foreach ($pendingBets as $bet) {
                    Db::table('caipiao_touzhu')
                        ->where('id', $bet->id)
                        ->update([
                            'iscancel' => 1,
                            'canceltime' => $now
                        ]);
                    $cancelledIds[] = $bet->id;
                }

                
                Db::table('caipiao_member')
                    ->where('id', $userId)
                    ->increment('balance', $refundAmount);

                
                Db::table('caipiao_moneylog')->insert([
                    'uid' => $userId,
                    'username' => $user->username,
                    'type' => 10,
                    'typename' => '撤销追号',
                    'trano' => $chaseNo,
                    'amount' => $refundAmount,
                    'before' => $user->balance,
                    'after' => $user->balance + $refundAmount,
                    'remark' => "撤销追号 {$chaseNo}，退款 " . count($pendingBets) . " 期",
                    'addtime' => $now,
                    'ip' => $request->getRealIp()
                ]);

                Db::commit();

                return json([
                    'code' => 0,
                    'message' => '取消成功',
                    'data' => [
                        'chaseNo' => $chaseNo,
                        'cancelledCount' => count($cancelledIds),
                        'refundAmount' => number_format($refundAmount, 2, '.', ''),
                        'balance' => number_format($user->balance + $refundAmount, 2, '.', '')
                    ]
                ]);

            } catch (\Exception $e) {
                Db::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            Log::error('取消追号失败: ' . $e->getMessage());
            return json(['code' => 500, 'message' => '取消失败: ' . $e->getMessage()]);
        }
    }
}

