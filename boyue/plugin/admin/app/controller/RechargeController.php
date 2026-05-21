<?php

namespace plugin\admin\app\controller;

use support\Request;
use support\Db;
use app\service\AdminEventTrigger;

/**
 * 充值管理
 */
class RechargeController
{
    /**
     * 充值列表页面
     */
    public function index(Request $request)
    {
        return view('recharge/index');
    }

    /**
     * Lấy nạp tiền列表dữ liệu
     */
    public function list(Request $request)
    {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 20);
        
        // Tìm kiếm条件
        $state = $request->get('state', '');
        $sDate = $request->get('sDate', '');
        $eDate = $request->get('eDate', '');
        $sAmout = $request->get('sAmout', '');
        $eAmout = $request->get('eAmout', '');
        $username = $request->get('username', '');
        $trano = $request->get('trano', '');
        $uid = $request->get('uid', '');

        $query = Db::table('caipiao_recharge')
            ->select('*');

        // 状态筛选
        if ($state !== '') {
            $query->where('state', $state);
        }

        // Thời gian筛选
        if ($sDate) {
            $startTime = strtotime($sDate);
            $query->where('oddtime', '>=', $startTime);
        }
        if ($eDate) {
            $endTime = strtotime($eDate . ' 23:59:59');
            $query->where('oddtime', '<=', $endTime);
        }

        // Số tiền筛选
        if ($sAmout !== '') {
            $query->where('amount', '>=', $sAmout);
        }
        if ($eAmout !== '') {
            $query->where('amount', '<=', $eAmout);
        }

        // Tên người dùng筛选
        if ($username) {
            $query->where('username', 'like', "%{$username}%");
        }

        // 单号筛选
        if ($trano) {
            $query->where('trano', 'like', "%{$trano}%");
        }

        // uid筛选
        if ($uid) {
            $query->where('uid', $uid);
        }

        // Lấy总数
        $count = $query->count();

        // 分页Tra cứu
        $list = $query->orderBy('id', 'desc')
            ->offset(($page - 1) * $limit)
            ->limit($limit)
            ->get();

        // 处理dữ liệu
        $data = [];
        foreach ($list as $item) {
            $row = (array)$item;
            
            // 格式化Thời gian
            $row['oddtime'] = date('m-d H:i', $row['oddtime']);
            
            // 类型文字
            $row['isauto_text'] = $row['isauto'] == 1 ? '自动' : '手动';
            
            // 状态文字
            // 0=待处理 1=Xác nhận中 2=Thành công 3=Thất bại 4=Đã hủy 5=超时
            $stateMap = [
                0 => '待处理',
                1 => 'Xác nhận中',
                2 => 'Thành công',
                3 => 'Thất bại',
                4 => 'Đã hủy',
                5 => '超时'
            ];
            $row['state_text'] = $stateMap[$row['state']] ?? '未知';
            
            $data[] = $row;
        }

        // 计算统计dữ liệu（基于筛选条件）
        $statisticsQuery = Db::table('caipiao_recharge');
        
        if ($state !== '') {
            $statisticsQuery->where('state', $state);
        }
        if ($sDate) {
            $statisticsQuery->where('oddtime', '>=', strtotime($sDate));
        }
        if ($eDate) {
            $statisticsQuery->where('oddtime', '<=', strtotime($eDate . ' 23:59:59'));
        }
        if ($username) {
            $statisticsQuery->where('username', 'like', "%{$username}%");
        }
        if ($trano) {
            $statisticsQuery->where('trano', 'like', "%{$trano}%");
        }

        // Thành công状态为2
        $statistics = $statisticsQuery->selectRaw('
            SUM(CASE WHEN state = 2 THEN amount ELSE 0 END) as total_amount,
            COUNT(CASE WHEN state = 2 THEN 1 END) as total_count,
            SUM(CASE WHEN state = 2 AND isauto = 1 THEN amount ELSE 0 END) as auto_amount,
            COUNT(CASE WHEN state = 2 AND isauto = 1 THEN 1 END) as auto_count,
            SUM(CASE WHEN state = 2 AND isauto = 0 THEN amount ELSE 0 END) as manual_amount,
            COUNT(CASE WHEN state = 2 AND isauto = 0 THEN 1 END) as manual_count
        ')->first();

        // 计算页面Thành côngSố tiền（state=2为Thành công）
        $pageSuccessAmount = array_sum(array_column(array_filter($data, function($row) {
            return $row['state'] == 2;
        }), 'amount'));

        return json([
            'code' => 0,
            'msg' => '',
            'count' => $count,
            'data' => $data,
            'statistics' => [
                'total_amount' => $statistics->total_amount ?? 0,
                'total_count' => $statistics->total_count ?? 0,
                'auto_amount' => $statistics->auto_amount ?? 0,
                'auto_count' => $statistics->auto_count ?? 0,
                'manual_amount' => $statistics->manual_amount ?? 0,
                'manual_count' => $statistics->manual_count ?? 0,
                'page_success_amount' => $pageSuccessAmount
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
            return json(['code' => 1, 'msg' => 'Tham số không hợp lệ']);
        }

        // Lấy nạp tiềnlịch sử
        $recharge = Db::table('caipiao_recharge')->where('id', $id)->first();
        
        if (!$recharge) {
            return json(['code' => 1, 'msg' => 'Nạp tiềnlịch sửkhông tồn tại']);
        }

        // 只能审核待处理(0)hoặcXác nhận中(1)的订单
        if ($recharge->state != 0 && $recharge->state != 1) {
            return json(['code' => 1, 'msg' => '该Nạp tiền已处理']);
        }

        // 开启事务
        Db::beginTransaction();
        
        try {
            // 增加Người dùngSố tiền
            $member = Db::table('caipiao_member')->where('id', $recharge->uid)->first();
            $oldMoney = $member->balance ?? 0;
            $newMoney = $oldMoney + $recharge->amount;
            
            Db::table('caipiao_member')
                ->where('id', $recharge->uid)
                ->update(['balance' => $newMoney]);

            // 更新Nạp tiền状态为Thành công(2)
            Db::table('caipiao_recharge')
                ->where('id', $id)
                ->update([
                    'state' => 2, // 2=Thành công
                    'oldaccountmoney' => $oldMoney,
                    'newaccountmoney' => $newMoney,
                    'stateadmin' => 'admin' // 这里应该从sessionLấy管理员名
                ]);

            // Thêm账变lịch sử
            Db::table('caipiao_fuddetail')->insert([
                'trano' => $recharge->trano,
                'uid' => $recharge->uid,
                'username' => $recharge->username,
                'type' => 'recharge',
                'typename' => 'Nạp tiền',
                'amount' => $recharge->amount,
                'amountbefor' => $oldMoney,
                'amountafter' => $newMoney,
                'oddtime' => time(),
                'remark' => 'Nạp tiền审核通过'
            ]);

            Db::commit();
            
            // 推送 WebSocket 实时动态
            AdminEventTrigger::onNewDeposit([
                'id' => $id,
                'username' => $recharge->username,
                'amount' => $recharge->amount,
                'channel' => $recharge->paytype ?? '',
            ]);
            
            return json(['code' => 0, 'msg' => '审核通过']);
            
        } catch (\Exception $e) {
            Db::rollBack();
            return json(['code' => 1, 'msg' => '审核Thất bại：' . $e->getMessage()]);
        }
    }

    /**
     * 拒绝/HủyNạp tiền
     * state: 3=Thất bại, 4=Đã hủy
     */
    public function reject(Request $request)
    {
        $id = $request->post('id');
        $reason = $request->post('reason', '');
        $state = $request->post('state', 4); // 默认为Hủy
        
        if (!$id) {
            return json(['code' => 1, 'msg' => 'Tham số không hợp lệ']);
        }
        
        // 限制可Cài đặt的状态
        if (!in_array($state, [3, 4])) {
            return json(['code' => 1, 'msg' => '状态Tham số không hợp lệ']);
        }

        $recharge = Db::table('caipiao_recharge')->where('id', $id)->first();
        
        if (!$recharge) {
            return json(['code' => 1, 'msg' => 'Nạp tiềnlịch sửkhông tồn tại']);
        }

        // 只能拒绝待处理(0)hoặcXác nhận中(1)的订单
        if ($recharge->state != 0 && $recharge->state != 1) {
            return json(['code' => 1, 'msg' => '该Nạp tiền已处理']);
        }

        $stateNames = [3 => 'Thất bại', 4 => 'Đã hủy'];
        
        Db::table('caipiao_recharge')
            ->where('id', $id)
            ->update([
                'state' => $state,
                'remark' => ($recharge->remark ? $recharge->remark . ' | ' : '') . '管理员拒绝' . ($reason ? ": $reason" : ''),
                'stateadmin' => 'admin'
            ]);

        return json(['code' => 0, 'msg' => "已标记为{$stateNames[$state]}"]);
    }

    /**
     * XóaNạp tiềnlịch sử
     */
    public function delete(Request $request)
    {
        $id = $request->post('id');
        
        if (!$id) {
            return json(['code' => 1, 'msg' => 'Tham số không hợp lệ']);
        }

        // Lấy nạp tiềnlịch sử
        $recharge = Db::table('caipiao_recharge')->where('id', $id)->first();
        
        if (!$recharge) {
            return json(['code' => 1, 'msg' => 'Nạp tiềnlịch sửkhông tồn tại']);
        }

        // 只允许Xóa未审核hoặcĐã hủy的lịch sử，Thành công的不能Xóa
        if ($recharge->state == 2) {
            return json(['code' => 1, 'msg' => '已审核的lịch sử不能Xóa']);
        }

        Db::table('caipiao_recharge')->where('id', $id)->delete();

        return json(['code' => 0, 'msg' => 'XóaThành công']);
    }
}

