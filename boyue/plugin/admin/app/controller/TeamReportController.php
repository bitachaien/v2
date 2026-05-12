<?php

namespace plugin\admin\app\controller;

use support\Request;
use support\Db;

class TeamReportController extends Base
{
    /**
     * 团队报表页面
     */
    public function index(Request $request)
    {
        return view('team-report/index');
    }
    
    /**
     * 获取团队报表数据
     */
    public function getData(Request $request)
    {
        $startime = $request->get('startime', date('Y-m-d'));
        $endtime = $request->get('endtime', date('Y-m-d'));
        $loginname = $request->get('loginname', '');
        
        // 转换时间戳
        $startTimestamp = strtotime($startime . ' 00:00:00');
        $endTimestamp = strtotime($endtime . ' 23:59:59');
        
        // 检查时间范围
        $days = floor(($endTimestamp - $startTimestamp) / 86400);
        if ($days > 60) {
            return json(['code' => 1, 'msg' => '只能查询60天内的数据']);
        }
        
        // 获取用户及下级ID列表
        $chiduids = [];
        $username = '';
        if (!empty($loginname)) {
            $user = Db::table('caipiao_member')
                ->where('username', $loginname)
                ->where('isnb', 0)
                ->first();
            
            if ($user) {
                $username = $user->username;
                $chiduids = $this->getDownList($user->id);
                $chiduids[] = $user->id;
            }
        }
        
        // 按天统计
        $list = [];
        for ($i = 0; $i <= $days; $i++) {
            $date = date("Y-m-d", $endTimestamp - 86400 * $i);
            $dayStart = strtotime($date . ' 00:00:00');
            $dayEnd = strtotime($date . ' 23:59:59');
            
            // 充值金额（自动充值 + 手动加 - 手动减）
            $zdchongzhi = $this->getRechargeAmount($chiduids, $dayStart, $dayEnd, 1, null);
            $sdjiachongzhi = $this->getRechargeAmount($chiduids, $dayStart, $dayEnd, 2, 1);
            $sdjianchongzhi = $this->getRechargeAmount($chiduids, $dayStart, $dayEnd, 2, -1);
            $dayRechargeMoney = $zdchongzhi + $sdjiachongzhi - $sdjianchongzhi;
            
            // 提款金额
            $dayDrawMoney = $this->getWithdrawAmount($chiduids, $dayStart, $dayEnd);
            
            // 投注额
            $dayBetMoney = $this->getBetAmount($chiduids, $dayStart, $dayEnd);
            
            // 中奖额
            $dayWinMoney = $this->getWinAmount($chiduids, $dayStart, $dayEnd);
            
            // 返点额
            $dayRebateMoney = $this->getRebateAmount($chiduids, $dayStart, $dayEnd);
            
            // 盈亏 = 充值 - 提款 - (中奖 + 返点 - 投注)
            $dayProfit = $dayRechargeMoney - $dayDrawMoney - ($dayWinMoney + $dayRebateMoney - $dayBetMoney);
            
            $list[] = [
                'date' => $date,
                'recharge' => round($dayRechargeMoney, 2),
                'withdraw' => round($dayDrawMoney, 2),
                'bet' => round($dayBetMoney, 2),
                'win' => round($dayWinMoney, 2),
                'rebate' => round($dayRebateMoney, 2),
                'profit' => round($dayProfit, 2)
            ];
        }
        
        return json([
            'code' => 0,
            'msg' => 'success',
            'count' => count($list),
            'data' => $list,
            'username' => $username
        ]);
    }
    
    /**
     * 获取下级ID列表
     */
    private function getDownList($pid)
    {
        $ids = [];
        $children = Db::table('caipiao_member')
            ->where('parentid', $pid)
            ->where('isnb', 0)
            ->pluck('id');
        
        foreach ($children as $childId) {
            $ids[] = $childId;
            $childIds = $this->getDownList($childId);
            $ids = array_merge($ids, $childIds);
        }
        
        return $ids;
    }
    
    /**
     * 获取充值金额
     */
    private function getRechargeAmount($uids, $start, $end, $isauto, $sdtype = null)
    {
        $query = Db::table('caipiao_recharge')
            ->where('state', 1)
            ->where('oddtime', '>=', $start)
            ->where('oddtime', '<=', $end)
            ->where('isauto', $isauto);
        
        if (!empty($uids)) {
            $query->whereIn('uid', $uids);
        }
        
        if ($sdtype !== null) {
            $query->where('sdtype', $sdtype);
        }
        
        return $query->sum('amount') ?: 0;
    }
    
    /**
     * 获取提款金额
     */
    private function getWithdrawAmount($uids, $start, $end)
    {
        $query = Db::table('caipiao_withdraw')
            ->where('state', 1)
            ->where('oddtime', '>=', $start)
            ->where('oddtime', '<=', $end);
        
        if (!empty($uids)) {
            $query->whereIn('uid', $uids);
        }
        
        return $query->sum('amount') ?: 0;
    }
    
    /**
     * 获取投注额（排除机器人投注）
     */
    private function getBetAmount($uids, $start, $end)
    {
        $query = Db::table('caipiao_touzhu')
            ->whereIn('isdraw', [1, -1])
            ->where('oddtime', '>=', $start)
            ->where('oddtime', '<=', $end)
            ->where(function($q) { $q->whereNull('source')->orWhere('source', '!=', 'robot'); });
        
        if (!empty($uids)) {
            $query->whereIn('uid', $uids);
        }
        
        return $query->sum('amount') ?: 0;
    }
    
    /**
     * 获取中奖额
     */
    private function getWinAmount($uids, $start, $end)
    {
        $query = Db::table('caipiao_fuddetail')
            ->where('type', 'reward')
            ->where('oddtime', '>=', $start)
            ->where('oddtime', '<=', $end);
        
        if (!empty($uids)) {
            $query->whereIn('uid', $uids);
        }
        
        return $query->sum('amount') ?: 0;
    }
    
    /**
     * 获取返点额
     */
    private function getRebateAmount($uids, $start, $end)
    {
        $query = Db::table('caipiao_fuddetail')
            ->where('type', 'fanshui')
            ->where('oddtime', '>=', $start)
            ->where('oddtime', '<=', $end);
        
        if (!empty($uids)) {
            $query->whereIn('uid', $uids);
        }
        
        return $query->sum('amount') ?: 0;
    }
}

