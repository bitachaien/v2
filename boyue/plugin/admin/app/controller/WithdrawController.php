<?php

namespace plugin\admin\app\controller;

use support\Request;
use support\Db;

/**
 * 提现管理
 */
class WithdrawController
{
    /**
     * 提现列表页面
     */
    public function index(Request $request)
    {
        return view('withdraw/index');
    }

    /**
     * 获取提现列表数据
     */
    public function list(Request $request)
    {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 20);
        
        // 搜索条件
        $state = $request->get('state', '');
        $sDate = $request->get('sDate', '');
        $eDate = $request->get('eDate', '');
        $sAmout = $request->get('sAmout', '');
        $eAmout = $request->get('eAmout', '');
        $username = $request->get('username', '');
        $trano = $request->get('trano', '');
        $uid = $request->get('uid', '');

        $query = Db::table('caipiao_withdraw as w')
            ->select('w.*');

        // 状态筛选
        if ($state !== '') {
            $query->where('w.state', $state);
        }

        // 时间筛选
        if ($sDate) {
            $startTime = strtotime($sDate);
            $query->where('w.oddtime', '>=', $startTime);
        }
        if ($eDate) {
            $endTime = strtotime($eDate . ' 23:59:59');
            $query->where('w.oddtime', '<=', $endTime);
        }

        // 金额筛选
        if ($sAmout !== '') {
            $query->where('w.amount', '>=', $sAmout);
        }
        if ($eAmout !== '') {
            $query->where('w.amount', '<=', $eAmout);
        }

        // 用户名筛选
        if ($username) {
            $query->where('w.username', 'like', "%{$username}%");
        }

        // 单号筛选
        if ($trano) {
            $query->where('w.trano', 'like', "%{$trano}%");
        }

        // uid筛选
        if ($uid) {
            $query->where('w.uid', $uid);
        }

        // 获取总数
        $count = $query->count();

        // 分页查询
        $list = $query->orderBy('w.id', 'desc')
            ->offset(($page - 1) * $limit)
            ->limit($limit)
            ->get()
            ->toArray();

        // 处理数据
        $result = [];
        // 状态映射: 0=出款中, 1=提现成功, 2=提现拒绝, 3=提现取消
        $stateMap = [
            0 => '出款中',
            1 => '提现成功',
            2 => '提现拒绝',
            3 => '提现取消'
        ];
        foreach ($list as $item) {
            $row = (array)$item;
            // 格式化时间
            $row['oddtime'] = date('Y-m-d H:i:s', $row['oddtime']);
            
            // 状态文字
            $row['state_text'] = $stateMap[$row['state']] ?? '未知';
            $result[] = $row;
        }

        // 计算统计数据
        $statistics = Db::table('caipiao_withdraw as w')
            ->when($state !== '', function($q) use ($state) {
                return $q->where('state', $state);
            })
            ->when($sDate, function($q) use ($sDate) {
                return $q->where('oddtime', '>=', strtotime($sDate));
            })
            ->when($eDate, function($q) use ($eDate) {
                return $q->where('oddtime', '<=', strtotime($eDate . ' 23:59:59'));
            })
            ->when($username, function($q) use ($username) {
                return $q->where('username', 'like', "%{$username}%");
            })
            ->selectRaw('
                SUM(amount) as total_amount,
                COUNT(*) as total_count,
                SUM(CASE WHEN state = 1 THEN amount ELSE 0 END) as success_amount
            ')
            ->first();

        return json([
            'code' => 0,
            'msg' => '',
            'count' => $count,
            'data' => $result,
            'statistics' => [
                'total_amount' => $statistics->total_amount ?? 0,
                'total_count' => $statistics->total_count ?? 0,
                'success_amount' => $statistics->success_amount ?? 0
            ]
        ]);
    }

    /**
     * 审核通过
     */
    public function approve(Request $request)
    {
        $id = $request->post('id');
        
        if (!$id) {
            return json(['code' => 1, 'msg' => '参数错误']);
        }

        // 获取提现记录
        $withdraw = Db::table('caipiao_withdraw')->where('id', $id)->first();
        
        if (!$withdraw) {
            return json(['code' => 1, 'msg' => '提现记录不存在']);
        }

        if ($withdraw->state != 0) {
            return json(['code' => 1, 'msg' => '该提现已处理']);
        }

        // 更新状态
        Db::table('caipiao_withdraw')
            ->where('id', $id)
            ->update([
                'state' => 1,
                'updatetime' => time()
            ]);

        return json(['code' => 0, 'msg' => '审核通过']);
    }

    /**
     * 退回
     */
    public function reject(Request $request)
    {
        $id = $request->post('id');
        
        if (!$id) {
            return json(['code' => 1, 'msg' => '参数错误']);
        }

        // 获取提现记录
        $withdraw = Db::table('caipiao_withdraw')->where('id', $id)->first();
        
        if (!$withdraw) {
            return json(['code' => 1, 'msg' => '提现记录不存在']);
        }

        if ($withdraw->state != 0) {
            return json(['code' => 1, 'msg' => '该提现已处理']);
        }

        // 开启事务
        Db::beginTransaction();
        
        try {
            // 获取用户当前余额
            $member = Db::table('caipiao_member')->where('id', $withdraw->uid)->first();
            $oldBalance = floatval($member->balance ?? 0);
            $newBalance = $oldBalance + floatval($withdraw->amount);
            
            // 退回金额
            Db::table('caipiao_member')
                ->where('id', $withdraw->uid)
                ->update(['balance' => $newBalance]);

            // 更新状态
            Db::table('caipiao_withdraw')
                ->where('id', $id)
                ->update([
                    'state' => 2,
                    'updatetime' => time()
                ]);

            // 添加账变记录
            $trano = 'FD' . date('YmdHis') . rand(1000, 9999);
            Db::table('caipiao_fuddetail')->insert([
                'trano' => $trano,
                'uid' => $withdraw->uid,
                'username' => $withdraw->username,
                'type' => 'withdraw_reject',
                'typename' => '提现退回',
                'amount' => $withdraw->amount,
                'amountbefor' => $oldBalance,
                'amountafter' => $newBalance,
                'oddtime' => time(),
                'remark' => '提现退回，单号：' . $withdraw->trano
            ]);

            Db::commit();
            return json(['code' => 0, 'msg' => '退回成功']);
            
        } catch (\Exception $e) {
            Db::rollBack();
            return json(['code' => 1, 'msg' => '退回失败：' . $e->getMessage()]);
        }
    }
}

