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
     * LấyĐội nhóm报表dữ liệu
     */
    public function getData(Request $request)
    {
        $startime = $request->get('startime', date('Y-m-d'));
        $endtime = $request->get('endtime', date('Y-m-d'));
        $loginname = $request->get('loginname', '');
        
        // 转换Thời gian戳
        $startTimestamp = strtotime($startime . ' 00:00:00');
        $endTimestamp = strtotime($endtime . ' 23:59:59');
        
        // 检查Thời gian范围
        $days = floor(($endTimestamp - $startTimestamp) / 86400);
        if ($days > 60) {
            return json(['code' => 1, 'msg' => '只能Tra cứu60天内的dữ liệu']);
        }
        
        // LấyNgười dùng及Cấp dướiID列表
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
            
            // Nạp tiềnSố tiền（自动Nạp tiền + 手动加 - 手动减）
            $zdchongzhi = $this->getRechargeAmount($chiduids, $dayStart, $dayEnd, 1, null);
            $sdjiachongzhi = $this->getRechargeAmount($chiduids, $dayStart, $dayEnd, 2, 1);
            $sdjianchongzhi = $this->getRechargeAmount($chiduids, $dayStart, $dayEnd, 2, -1);
            $dayRechargeMoney = $zdchongzhi + $sdjiachongzhi - $sdjianchongzhi;
            
            // Rút tiềnSố tiền
            $dayDrawMoney = $this->getWithdrawAmount($chiduids, $dayStart, $dayEnd);
            
            // Đặt cược额
            $dayBetMoney = $this->getBetAmount($chiduids, $dayStart, $dayEnd);
            
            // Trúng thưởng额
            $dayWinMoney = $this->getWinAmount($chiduids, $dayStart, $dayEnd);
            
            // 返点额
            $dayRebateMoney = $this->getRebateAmount($chiduids, $dayStart, $dayEnd);
            
            // 盈亏 = Nạp tiền - Rút tiền - (Trúng thưởng + 返点 - Đặt cược)
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
     * LấyCấp dướiID列表
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
     * Lấy nạp tiềnSố tiền
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
     * Lấy rút tiềnSố tiền
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
     * LấyĐặt cược额（排除机器人Đặt cược）
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
     * LấyTrúng thưởng额
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
     * Lấy返点额
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

