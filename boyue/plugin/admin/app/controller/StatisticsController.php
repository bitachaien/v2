<?php

namespace plugin\admin\app\controller;

use plugin\admin\app\controller\Base;
use support\Request;
use support\Db;

/**
 * 数据统计控制器
 */
class StatisticsController extends Base
{
    /**
     * 统计概况
     */
    public function overview(Request $request)
    {
        // 如果不是AJAX请求，返回视图
        if (!$request->header('x-requested-with')) {
            return view('statistics/overview');
        }
        
        // 获取查询参数
        $username = $request->get('username', '');
        $sDate = $request->get('sDate', '');
        $eDate = $request->get('eDate', '');
        $typeid = $request->get('typeid', '');
        
        // 排除内部会员
        $testUsers = Db::table('caipiao_member')
            ->where('isnb', 1)
            ->pluck('id')
            ->toArray();
        
        // 构建基础查询条件
        $baseWhere = function ($query) use ($testUsers, $username, $sDate, $eDate) {
            if (!empty($testUsers)) {
                $query->whereNotIn('uid', $testUsers);
            }
            if (!empty($username)) {
                $query->where('username', $username);
            }
            if (!empty($sDate)) {
                $query->where('oddtime', '>=', strtotime($sDate));
            }
            if (!empty($eDate)) {
                $query->where('oddtime', '<=', strtotime($eDate) + 86400);
            }
        };
        
        // ==================== 盈亏统计 ====================
        
        // 自动充值
        $zidchongzhiall = Db::table('caipiao_recharge')
            ->where('state', 1)
            ->where('isauto', 1)
            ->where($baseWhere)
            ->sum('amount') ?? 0;
        
        // 手动充值加
        $sdjiachongzhiall = Db::table('caipiao_recharge')
            ->where('state', 1)
            ->where('isauto', 2)
            ->where('sdtype', 1)
            ->where($baseWhere)
            ->sum('amount') ?? 0;
        
        // 手动充值减
        $sdjianchongzhiall = Db::table('caipiao_recharge')
            ->where('state', 1)
            ->where('isauto', 2)
            ->where('sdtype', -1)
            ->where($baseWhere)
            ->sum('amount') ?? 0;
        
        // 提款
        $tikuanall = Db::table('caipiao_withdraw')
            ->where('state', 1)
            ->where($baseWhere)
            ->sum('amount') ?? 0;
        
        // 投注（排除机器人投注）
        $touzhuall = Db::table('caipiao_touzhu')
            ->whereIn('isdraw', [1, -1])
            ->where($baseWhere)
            ->where(function($q) { $q->whereNull('source')->orWhere('source', '!=', 'robot'); })
            ->sum('amount') ?? 0;
        
        // 中奖（排除机器人投注）
        $zhongjiangall = Db::table('caipiao_touzhu')
            ->where('isdraw', 1)
            ->where($baseWhere)
            ->where(function($q) { $q->whereNull('source')->orWhere('source', '!=', 'robot'); })
            ->sum('okamount') ?? 0;
        
        // 活动
        $huodongall = Db::table('caipiao_fuddetail')
            ->whereIn('type', ['pointexchange', 'activity_bindcard', 'activity_czzs', 
                              'activity_rxf', 'activity_rks', 'activity_yxf', 'activity_yks'])
            ->where($baseWhere)
            ->sum('amount') ?? 0;
        
        $yingkuis = [
            'zidchongzhiall' => $zidchongzhiall,
            'sdjiachongzhiall' => $sdjiachongzhiall,
            'sdjianchongzhiall' => $sdjianchongzhiall,
            'tikuanall' => $tikuanall,
            'touzhuall' => $touzhuall,
            'zhongjiangall' => $zhongjiangall,
            'huodongall' => $huodongall,
            'ctyingkui' => ($zidchongzhiall + $sdjiachongzhiall - $sdjianchongzhiall) - $tikuanall,
            'tzyingkui' => $touzhuall - $zhongjiangall,
        ];
        
        // ==================== 用户统计 ====================
        
        $memberBaseWhere = function ($query) use ($testUsers) {
            if (!empty($testUsers)) {
                $query->whereNotIn('id', $testUsers);
            }
        };
        
        // 所有用户
        $usercountall = Db::table('caipiao_member')
            ->where($memberBaseWhere)
            ->count();
        
        // 代理数
        $userdailiall = Db::table('caipiao_member')
            ->where($memberBaseWhere)
            ->where('proxy', 1)
            ->count();
        
        // 普通用户
        $userputongall = $usercountall - $userdailiall;
        
        // 在线数（最近30分钟登录）
        $useronlineall = Db::table('caipiao_member')
            ->where($memberBaseWhere)
            ->where('onlinetime', '>=', time() - 1800)
            ->count();
        
        // 可用余额
        $userbalanceall = Db::table('caipiao_member')
            ->where($memberBaseWhere)
            ->sum('balance') ?? 0;
        
        $usertongji = [
            'usercountall' => $usercountall,
            'userdailiall' => $userdailiall,
            'userputongall' => $userputongall,
            'useronlineall' => $useronlineall,
            'userbalanceall' => $userbalanceall,
        ];
        
        // ==================== 彩票统计 ====================
        
        $cpQuery = Db::table('caipiao_caipiao')->where('isopen', 1);
        
        // 如果有typeid筛选
        if (!empty($typeid)) {
            $cpQuery->where('typeid', $typeid);
        }
        
        $cplist = $cpQuery->orderBy('typeid')->get()->toArray();
        
        $cpxiaoji = ['touzhuall' => 0, 'zhongjiangall' => 0];
        
        foreach ($cplist as $key => $cp) {
            // 投注统计（排除机器人投注）
            $touzhu = Db::table('caipiao_touzhu')
                ->where('cpname', $cp->name)
                ->whereIn('isdraw', [1, -1])
                ->where($baseWhere)
                ->where(function($q) { $q->whereNull('source')->orWhere('source', '!=', 'robot'); })
                ->sum('amount') ?? 0;
            
            // 中奖统计（排除机器人投注）
            $zhongjiang = Db::table('caipiao_touzhu')
                ->where('cpname', $cp->name)
                ->where('isdraw', 1)
                ->where($baseWhere)
                ->where(function($q) { $q->whereNull('source')->orWhere('source', '!=', 'robot'); })
                ->sum('okamount') ?? 0;
            
            $cplist[$key]->touzhuall = $touzhu;
            $cplist[$key]->zhongjiangall = $zhongjiang;
            $cplist[$key]->yingkui = $touzhu - $zhongjiang;
            
            $cpxiaoji['touzhuall'] += $touzhu;
            $cpxiaoji['zhongjiangall'] += $zhongjiang;
        }
        
        $cpxiaoji['yingkui'] = $cpxiaoji['touzhuall'] - $cpxiaoji['zhongjiangall'];
        
        return $this->json(0, 'success', [
            'yingkuis' => $yingkuis,
            'usertongji' => $usertongji,
            'cplist' => $cplist,
            'cpxiaoji' => $cpxiaoji,
            'username' => $username,
            'sDate' => $sDate,
            'eDate' => $eDate,
        ]);
    }
    
    /**
     * 盈亏统计
     */
    public function profit(Request $request)
    {
        // 如果不是AJAX请求，返回视图
        if (!$request->header('x-requested-with')) {
            return view('statistics/profit');
        }
        
        // 获取参数
        $sDate = $request->get('sDate', date('Ymd', strtotime('-6 days')));
        $eDate = $request->get('eDate', date('Ymd'));
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 20);
        
        // 排除内部会员
        $testUsers = Db::table('caipiao_member')
            ->where('isnb', 1)
            ->pluck('id')
            ->toArray();
        
        // 计算日期范围
        $startTime = strtotime($sDate);
        $endTime = strtotime($eDate);
        $days = ceil(($endTime - $startTime) / 86400);
        
        $list = [];
        
        // 按天统计
        for ($i = 0; $i <= $days; $i++) {
            $datetime = date('Y-m-d', $endTime - 86400 * $i);
            $dayStart = strtotime($datetime);
            $dayEnd = $dayStart + 86400 - 1;
            
            // 自动充值
            $zdchongzhiall = Db::table('caipiao_recharge')
                ->where('state', 1)
                ->where('isauto', 1)
                ->where('oddtime', '>=', $dayStart)
                ->where('oddtime', '<=', $dayEnd)
                ->when(!empty($testUsers), function($query) use ($testUsers) {
                    return $query->whereNotIn('uid', $testUsers);
                })
                ->sum('amount') ?? 0;
            
            // 手动充值加
            $sdjiachongzhiall = Db::table('caipiao_recharge')
                ->where('state', 1)
                ->where('isauto', 2)
                ->where('sdtype', 1)
                ->where('oddtime', '>=', $dayStart)
                ->where('oddtime', '<=', $dayEnd)
                ->when(!empty($testUsers), function($query) use ($testUsers) {
                    return $query->whereNotIn('uid', $testUsers);
                })
                ->sum('amount') ?? 0;
            
            // 手动充值减
            $sdjianchongzhiall = Db::table('caipiao_recharge')
                ->where('state', 1)
                ->where('isauto', 2)
                ->where('sdtype', -1)
                ->where('oddtime', '>=', $dayStart)
                ->where('oddtime', '<=', $dayEnd)
                ->when(!empty($testUsers), function($query) use ($testUsers) {
                    return $query->whereNotIn('uid', $testUsers);
                })
                ->sum('amount') ?? 0;
            
            // 提款
            $tikuanall = Db::table('caipiao_withdraw')
                ->where('state', 1)
                ->where('oddtime', '>=', $dayStart)
                ->where('oddtime', '<=', $dayEnd)
                ->when(!empty($testUsers), function($query) use ($testUsers) {
                    return $query->whereNotIn('uid', $testUsers);
                })
                ->sum('amount') ?? 0;
            
            // 投注（排除机器人投注）
            $touzhuall = Db::table('caipiao_touzhu')
                ->whereIn('isdraw', [1, -1])
                ->where('oddtime', '>=', $dayStart)
                ->where('oddtime', '<=', $dayEnd)
                ->when(!empty($testUsers), function($query) use ($testUsers) {
                    return $query->whereNotIn('uid', $testUsers);
                })
                ->where(function($q) { $q->whereNull('source')->orWhere('source', '!=', 'robot'); })
                ->sum('amount') ?? 0;
            
            // 中奖（排除机器人投注）
            $zhongjiangall = Db::table('caipiao_touzhu')
                ->where('isdraw', 1)
                ->where('oddtime', '>=', $dayStart)
                ->where('oddtime', '<=', $dayEnd)
                ->when(!empty($testUsers), function($query) use ($testUsers) {
                    return $query->whereNotIn('uid', $testUsers);
                })
                ->where(function($q) { $q->whereNull('source')->orWhere('source', '!=', 'robot'); })
                ->sum('okamount') ?? 0;
            
            // 活动
            $huodongall = Db::table('caipiao_fuddetail')
                ->whereIn('type', ['pointexchange', 'fanshui', 'yongjinshenhe', 'jinjishenhe', 
                                  'activity_bindcard', 'activity_czzs', 'activity_rxf', 
                                  'activity_rks', 'activity_yxf', 'activity_yks'])
                ->where('oddtime', '>=', $dayStart)
                ->where('oddtime', '<=', $dayEnd)
                ->when(!empty($testUsers), function($query) use ($testUsers) {
                    return $query->whereNotIn('uid', $testUsers);
                })
                ->sum('amount') ?? 0;
            
            $list[] = [
                'date' => $datetime,
                'zdchongzhiall' => $zdchongzhiall,
                'sdjiachongzhiall' => $sdjiachongzhiall,
                'sdjianchongzhiall' => $sdjianchongzhiall,
                'tikuanall' => $tikuanall,
                'ctyingkui' => ($zdchongzhiall + $sdjiachongzhiall - $sdjianchongzhiall) - $tikuanall,
                'touzhuall' => $touzhuall,
                'zhongjiangall' => $zhongjiangall,
                'huodongall' => $huodongall,
                'tzyingkui' => $touzhuall - $zhongjiangall,
            ];
        }
        
        $total = count($list);
        
        // 分页
        $offset = ($page - 1) * $limit;
        $pageList = array_slice($list, $offset, $limit);
        
        return $this->json(0, 'success', [
            'list' => $pageList,
            'total' => $total
        ]);
    }
    
    /**
     * 用户统计
     */
    public function user(Request $request)
    {
        // 如果不是AJAX请求，返回视图
        if (!$request->header('x-requested-with')) {
            return view('statistics/user');
        }
        
        // 获取参数
        $sDate = $request->get('sDate', date('Ymd', strtotime('-1 days')));
        $eDate = $request->get('eDate', date('Ymd'));
        $username = $request->get('username', '');
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 20);
        
        // 查询条件
        $query = Db::table('caipiao_member')->where('isnb', 0);
        
        if (!empty($username)) {
            $query->where('username', $username);
        }
        
        // 获取会员列表
        $total = $query->count();
        $offset = ($page - 1) * $limit;
        $members = $query->orderBy('id', 'desc')
            ->select(['id', 'username'])
            ->offset($offset)
            ->limit($limit)
            ->get()
            ->toArray();
        
        $list = [];
        $dateRange = $sDate . '~' . $eDate;
        $startTime = strtotime($sDate);
        $endTime = strtotime($eDate) + 86400 - 1;
        
        foreach ($members as $member) {
            // 自动充值
            $zdchongzhiall = Db::table('caipiao_recharge')
                ->where('uid', $member->id)
                ->where('state', 1)
                ->where('isauto', 1)
                ->where('oddtime', '>=', $startTime)
                ->where('oddtime', '<=', $endTime)
                ->sum('amount') ?? 0;
            
            // 手动加款
            $sdjiachongzhiall = Db::table('caipiao_recharge')
                ->where('uid', $member->id)
                ->where('state', 1)
                ->where('isauto', 2)
                ->where('sdtype', 1)
                ->where('oddtime', '>=', $startTime)
                ->where('oddtime', '<=', $endTime)
                ->sum('amount') ?? 0;
            
            // 手动减款
            $sdjianchongzhiall = Db::table('caipiao_recharge')
                ->where('uid', $member->id)
                ->where('state', 1)
                ->where('isauto', 2)
                ->where('sdtype', -1)
                ->where('oddtime', '>=', $startTime)
                ->where('oddtime', '<=', $endTime)
                ->sum('amount') ?? 0;
            
            // 提款
            $tikuanall = Db::table('caipiao_withdraw')
                ->where('uid', $member->id)
                ->where('state', 1)
                ->where('oddtime', '>=', $startTime)
                ->where('oddtime', '<=', $endTime)
                ->sum('amount') ?? 0;
            
            // 投注（排除机器人投注）
            $touzhuall = Db::table('caipiao_touzhu')
                ->where('uid', $member->id)
                ->whereIn('isdraw', [1, -1])
                ->where('oddtime', '>=', $startTime)
                ->where('oddtime', '<=', $endTime)
                ->where(function($q) { $q->whereNull('source')->orWhere('source', '!=', 'robot'); })
                ->sum('amount') ?? 0;
            
            // 中奖（排除机器人投注）
            $zhongjiangall = Db::table('caipiao_touzhu')
                ->where('uid', $member->id)
                ->where('isdraw', 1)
                ->where('oddtime', '>=', $startTime)
                ->where('oddtime', '<=', $endTime)
                ->where(function($q) { $q->whereNull('source')->orWhere('source', '!=', 'robot'); })
                ->sum('okamount') ?? 0;
            
            // 活动
            $huodongall = Db::table('caipiao_fuddetail')
                ->where('uid', $member->id)
                ->whereIn('type', ['pointexchange', 'fanshui', 'yongjinshenhe', 'jinjishenhe',
                                  'activity_bindcard', 'activity_czzs', 'activity_rxf',
                                  'activity_rks', 'activity_yxf', 'activity_yks'])
                ->where('oddtime', '>=', $startTime)
                ->where('oddtime', '<=', $endTime)
                ->sum('amount') ?? 0;
            
            $list[] = [
                'username' => $member->username,
                'zdchongzhiall' => $zdchongzhiall,
                'sdjiachongzhiall' => $sdjiachongzhiall,
                'sdjianchongzhiall' => $sdjianchongzhiall,
                'tikuanall' => $tikuanall,
                'ctyingkui' => ($zdchongzhiall + $sdjiachongzhiall - $sdjianchongzhiall) - $tikuanall,
                'touzhuall' => $touzhuall,
                'zhongjiangall' => $zhongjiangall,
                'huodongall' => $huodongall,
                'tzyingkui' => $touzhuall - $zhongjiangall,
                'date' => $dateRange,
            ];
        }
        
        return $this->json(0, 'success', [
            'list' => $list,
            'total' => $total
        ]);
    }
    
    /**
     * 团队概况
     */
    public function team(Request $request)
    {
        // 如果不是AJAX请求，返回视图
        if (!$request->header('x-requested-with')) {
            return view('statistics/team');
        }
        
        // 获取参数
        $sDate = $request->get('sDate', '');
        $eDate = $request->get('eDate', '');
        $username = $request->get('username', '');
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 20);
        $pid = $request->get('pid', 0);
        
        // 查询条件
        $query = Db::table('caipiao_member')->where('isnb', 0);
        
        if (!empty($pid)) {
            $query->where('parentid', $pid);
        } else {
            $query->where('proxy', 1); // 只查代理
        }
        
        if (!empty($username)) {
            $query->where('username', $username);
        }
        
        // 获取代理列表
        $total = $query->count();
        $offset = ($page - 1) * $limit;
        $agents = $query->orderBy('id', 'desc')
            ->select(['id', 'username', 'proxy'])
            ->offset($offset)
            ->limit($limit)
            ->get()
            ->toArray();
        
        $list = [];
        $startTime = !empty($sDate) ? strtotime($sDate) : 0;
        $endTime = !empty($eDate) ? strtotime($eDate) + 86400 - 1 : time();
        
        foreach ($agents as $agent) {
            // 获取所有下线用户ID
            $downlineIds = $this->getDownlineIds($agent->id);
            $downlineIds[] = $agent->id; // 包含自己
            
            // 团队总数
            $totalcount = count($downlineIds) - 1; // 不包含自己
            
            // 团队代理数
            $agentcount = Db::table('caipiao_member')
                ->whereIn('id', $downlineIds)
                ->where('id', '!=', $agent->id)
                ->where('proxy', 1)
                ->count();
            
            // 普通用户数
            $usercount = $totalcount - $agentcount;
            
            // 在线数（最近30分钟）
            $onlinecount = Db::table('caipiao_member')
                ->whereIn('id', $downlineIds)
                ->where('onlinetime', '>=', time() - 1800)
                ->count();
            
            // 自动充值
            $zdrecharge = Db::table('caipiao_recharge')
                ->whereIn('uid', $downlineIds)
                ->where('state', 1)
                ->where('isauto', 1)
                ->when($startTime, function($query) use ($startTime) {
                    return $query->where('oddtime', '>=', $startTime);
                })
                ->when($endTime, function($query) use ($endTime) {
                    return $query->where('oddtime', '<=', $endTime);
                })
                ->sum('amount') ?? 0;
            
            // 手动加款
            $sdjiarecharge = Db::table('caipiao_recharge')
                ->whereIn('uid', $downlineIds)
                ->where('state', 1)
                ->where('isauto', 2)
                ->where('sdtype', 1)
                ->when($startTime, function($query) use ($startTime) {
                    return $query->where('oddtime', '>=', $startTime);
                })
                ->when($endTime, function($query) use ($endTime) {
                    return $query->where('oddtime', '<=', $endTime);
                })
                ->sum('amount') ?? 0;
            
            // 手动减款
            $sdjianrecharge = Db::table('caipiao_recharge')
                ->whereIn('uid', $downlineIds)
                ->where('state', 1)
                ->where('isauto', 2)
                ->where('sdtype', -1)
                ->when($startTime, function($query) use ($startTime) {
                    return $query->where('oddtime', '>=', $startTime);
                })
                ->when($endTime, function($query) use ($endTime) {
                    return $query->where('oddtime', '<=', $endTime);
                })
                ->sum('amount') ?? 0;
            
            // 提款
            $withdraw = Db::table('caipiao_withdraw')
                ->whereIn('uid', $downlineIds)
                ->where('state', 1)
                ->when($startTime, function($query) use ($startTime) {
                    return $query->where('oddtime', '>=', $startTime);
                })
                ->when($endTime, function($query) use ($endTime) {
                    return $query->where('oddtime', '<=', $endTime);
                })
                ->sum('amount') ?? 0;
            
            // 投注（排除机器人投注）
            $touzhu = Db::table('caipiao_touzhu')
                ->whereIn('uid', $downlineIds)
                ->whereIn('isdraw', [1, -1])
                ->when($startTime, function($query) use ($startTime) {
                    return $query->where('oddtime', '>=', $startTime);
                })
                ->when($endTime, function($query) use ($endTime) {
                    return $query->where('oddtime', '<=', $endTime);
                })
                ->where(function($q) { $q->whereNull('source')->orWhere('source', '!=', 'robot'); })
                ->sum('amount') ?? 0;
            
            // 中奖（排除机器人投注）
            $zhongjiang = Db::table('caipiao_touzhu')
                ->whereIn('uid', $downlineIds)
                ->where('isdraw', 1)
                ->when($startTime, function($query) use ($startTime) {
                    return $query->where('oddtime', '>=', $startTime);
                })
                ->when($endTime, function($query) use ($endTime) {
                    return $query->where('oddtime', '<=', $endTime);
                })
                ->where(function($q) { $q->whereNull('source')->orWhere('source', '!=', 'robot'); })
                ->sum('okamount') ?? 0;
            
            // 活动
            $huodong = Db::table('caipiao_fuddetail')
                ->whereIn('uid', $downlineIds)
                ->whereIn('type', ['pointexchange', 'fanshui', 'yongjinshenhe', 'jinjishenhe',
                                  'activity_bindcard', 'activity_czzs', 'activity_rxf',
                                  'activity_rks', 'activity_yxf', 'activity_yks'])
                ->when($startTime, function($query) use ($startTime) {
                    return $query->where('oddtime', '>=', $startTime);
                })
                ->when($endTime, function($query) use ($endTime) {
                    return $query->where('oddtime', '<=', $endTime);
                })
                ->sum('amount') ?? 0;
            
            $list[] = [
                'username' => $agent->username,
                'totalcount' => $totalcount,
                'agentcount' => $agentcount,
                'usercount' => $usercount,
                'onlinecount' => $onlinecount,
                'zdrecharge' => $zdrecharge,
                'sdjiarecharge' => $sdjiarecharge,
                'sdjianrecharge' => $sdjianrecharge,
                'withdraw' => $withdraw,
                'ctyingkui' => ($zdrecharge + $sdjiarecharge - $sdjianrecharge) - $withdraw,
                'touzhu' => $touzhu,
                'zhongjiang' => $zhongjiang,
                'huodong' => $huodong,
                'tzyingkui' => $touzhu - $zhongjiang,
            ];
        }
        
        return $this->json(0, 'success', [
            'list' => $list,
            'total' => $total
        ]);
    }
    
    /**
     * 递归获取所有下级ID
     */
    private function getDownlineIds($parentId, &$result = [])
    {
        $children = Db::table('caipiao_member')
            ->where('parentid', $parentId)
            ->where('isnb', 0)
            ->pluck('id')
            ->toArray();
        
        foreach ($children as $childId) {
            $result[] = $childId;
            $this->getDownlineIds($childId, $result);
        }
        
        return $result;
    }
    
    /**
     * 彩种投注统计
     */
    public function lottery_bet(Request $request)
    {
        // 如果不是AJAX请求，返回视图
        if (!$request->header('x-requested-with')) {
            return view('statistics/lottery_bet');
        }
        
        // 获取参数
        $sDate = $request->get('sDate', '');
        $eDate = $request->get('eDate', '');
        $username = $request->get('username', '');
        
        $startTime = !empty($sDate) ? strtotime($sDate) : 0;
        $endTime = !empty($eDate) ? strtotime($eDate) + 86400 - 1 : time();
        
        // 构建查询条件（排除机器人投注）
        $query = Db::table('caipiao_touzhu as t')
            ->leftJoin('caipiao_caipiao as c', 't.cpname', '=', 'c.name')
            ->leftJoin('caipiao_member as m', 't.uid', '=', 'm.id')
            ->where('m.isnb', 0)
            ->whereIn('t.isdraw', [1, -1])
            ->where(function($q) { $q->whereNull('t.source')->orWhere('t.source', '!=', 'robot'); });
        
        if (!empty($username)) {
            $query->where('m.username', $username);
        }
        
        if ($startTime) {
            $query->where('t.oddtime', '>=', $startTime);
        }
        
        if ($endTime) {
            $query->where('t.oddtime', '<=', $endTime);
        }
        
        // 按彩种分组统计
        $results = $query->select([
                Db::raw('c.id as lottery_id'),
                Db::raw('c.title as lottery_name'),
                Db::raw('t.cpname as lottery_code'),
                Db::raw('SUM(t.amount) as bet_amount'),
                Db::raw('SUM(CASE WHEN t.isdraw = 1 THEN t.okamount ELSE 0 END) as win_amount')
            ])
            ->groupBy('t.cpname', 'c.id', 'c.title')
            ->orderBy('bet_amount', 'desc')
            ->get()
            ->toArray();
        
        // 计算汇总和详细列表
        $totalBet = 0;
        $totalWin = 0;
        $list = [];
        
        foreach ($results as $row) {
            $betAmount = floatval($row->bet_amount ?? 0);
            $winAmount = floatval($row->win_amount ?? 0);
            $profit = $betAmount - $winAmount;
            
            $totalBet += $betAmount;
            $totalWin += $winAmount;
            
            $list[] = [
                'lotteryId' => $row->lottery_id,
                'lotteryName' => $row->lottery_name ?? '未知彩种',
                'betAmount' => $betAmount,
                'winAmount' => $winAmount,
                'profit' => $profit
            ];
        }
        
        $summary = [
            'totalBet' => $totalBet,
            'totalWin' => $totalWin,
            'totalProfit' => $totalBet - $totalWin,
            'totalLottery' => count($list)
        ];
        
        return $this->json(0, 'success', [
            'summary' => $summary,
            'list' => $list
        ]);
    }
    
    /**
     * 充值提款统计
     */
    public function recharge(Request $request)
    {
        // 如果不是AJAX请求，返回视图
        if (!$request->header('x-requested-with')) {
            return view('statistics/recharge');
        }
        
        // 获取参数
        $sDate = $request->get('sDate', date('Ymd', strtotime('-6 days')));
        $eDate = $request->get('eDate', date('Ymd'));
        $username = $request->get('username', '');
        
        $startTime = strtotime($sDate);
        $endTime = strtotime($eDate) + 86400 - 1;
        $days = ceil(($endTime - $startTime) / 86400);
        
        // 按日期分组统计
        $list = [];
        $summary = [
            'totalRecharge' => 0,
            'totalWithdraw' => 0,
            'totalProfit' => 0,
            'autoRecharge' => 0,
            'manualAdd' => 0,
            'manualSub' => 0,
            'rechargeUsers' => 0,
            'withdrawUsers' => 0
        ];
        
        for ($i = 0; $i <= $days; $i++) {
            $date = date('Y-m-d', $endTime - 86400 * $i);
            $dayStart = strtotime($date);
            $dayEnd = $dayStart + 86400 - 1;
            
            // 构建基础查询
            $baseQuery = Db::table('caipiao_member')->where('isnb', 0);
            if (!empty($username)) {
                $baseQuery->where('username', $username);
            }
            $memberIds = $baseQuery->pluck('id')->toArray();
            
            if (empty($memberIds)) {
                continue;
            }
            
            // 自动充值
            $autoRecharge = Db::table('caipiao_recharge')
                ->whereIn('uid', $memberIds)
                ->where('state', 1)
                ->where('isauto', 1)
                ->where('oddtime', '>=', $dayStart)
                ->where('oddtime', '<=', $dayEnd)
                ->sum('amount') ?? 0;
            
            // 手动加款
            $manualAdd = Db::table('caipiao_recharge')
                ->whereIn('uid', $memberIds)
                ->where('state', 1)
                ->where('isauto', 2)
                ->where('sdtype', 1)
                ->where('oddtime', '>=', $dayStart)
                ->where('oddtime', '<=', $dayEnd)
                ->sum('amount') ?? 0;
            
            // 手动减款
            $manualSub = Db::table('caipiao_recharge')
                ->whereIn('uid', $memberIds)
                ->where('state', 1)
                ->where('isauto', 2)
                ->where('sdtype', -1)
                ->where('oddtime', '>=', $dayStart)
                ->where('oddtime', '<=', $dayEnd)
                ->sum('amount') ?? 0;
            
            // 提款
            $withdraw = Db::table('caipiao_withdraw')
                ->whereIn('uid', $memberIds)
                ->where('state', 1)
                ->where('oddtime', '>=', $dayStart)
                ->where('oddtime', '<=', $dayEnd)
                ->sum('amount') ?? 0;
            
            $recharge = $autoRecharge + $manualAdd - $manualSub;
            $profit = $recharge - $withdraw;
            
            $list[] = [
                'date' => date('m-d', $dayStart),
                'fullDate' => $date,
                'autoRecharge' => $autoRecharge,
                'manualAdd' => $manualAdd,
                'manualSub' => $manualSub,
                'recharge' => $recharge,
                'withdraw' => $withdraw,
                'profit' => $profit
            ];
            
            $summary['autoRecharge'] += $autoRecharge;
            $summary['manualAdd'] += $manualAdd;
            $summary['manualSub'] += $manualSub;
            $summary['totalRecharge'] += $recharge;
            $summary['totalWithdraw'] += $withdraw;
            $summary['totalProfit'] += $profit;
        }
        
        // 统计充值人数（有充值记录的用户数）
        $rechargeQuery = Db::table('caipiao_recharge as r')
            ->join('caipiao_member as m', 'r.uid', '=', 'm.id')
            ->where('m.isnb', 0)
            ->where('r.state', 1)
            ->where('r.oddtime', '>=', $startTime)
            ->where('r.oddtime', '<=', $endTime);
        
        if (!empty($username)) {
            $rechargeQuery->where('m.username', $username);
        }
        
        $summary['rechargeUsers'] = $rechargeQuery->distinct()->count('r.uid');
        
        // 统计提款人数
        $withdrawQuery = Db::table('caipiao_withdraw as w')
            ->join('caipiao_member as m', 'w.uid', '=', 'm.id')
            ->where('m.isnb', 0)
            ->where('w.state', 1)
            ->where('w.oddtime', '>=', $startTime)
            ->where('w.oddtime', '<=', $endTime);
        
        if (!empty($username)) {
            $withdrawQuery->where('m.username', $username);
        }
        
        $summary['withdrawUsers'] = $withdrawQuery->distinct()->count('w.uid');
        
        return $this->json(0, 'success', [
            'summary' => $summary,
            'list' => $list
        ]);
    }
}

