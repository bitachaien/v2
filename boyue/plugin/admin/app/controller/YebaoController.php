<?php

namespace plugin\admin\app\controller;

use support\Request;
use support\Db;
use app\constant\YuebaoConstant;

/**
 * 余额宝管理控制器
 */
class YebaoController extends Base
{
    /**
     * ============================================
     * 余额宝产品管理 API
     * ============================================
     */

    /**
     * 产品列表
     * GET /app/admin/api/yebao/product-list
     */
    public function productList(Request $request)
    {
        $page = $request->get('page', 1);
        // 兼容 page_size 和 limit
        $limit = $request->get('page_size', $request->get('limit', 20));
        $name = $request->get('name', '');
        $type = $request->get('type', '');
        $status = $request->get('status', '');

        $query = Db::table('yzz_yuebao_product');

        if ($name) {
            $query->where('name', 'like', '%' . addcslashes($name, '%_') . '%');
        }

        if ($type) {
            $query->where('type', $type);
        }

        if ($status !== '') {
            $query->where('status', $status);
        }

        $count = $query->count();

        $offset = ($page - 1) * $limit;
        $list = $query->orderBy('sort', 'desc')
            ->orderBy('id', 'asc')
            ->offset($offset)
            ->limit($limit)
            ->get();

        $typeMap = ['current' => '随存随取', 'fixed' => '定期'];
        $statusMap = [1 => '启用', 0 => '禁用'];

        $result = [];
        foreach ($list as $item) {
            $row = (array)$item;
            $row['type_text'] = $typeMap[$row['type']] ?? $row['type'];
            $row['status_text'] = $statusMap[$row['status']] ?? '未知';
            $row['rate_percent'] = bcmul($row['rate'], 100, 4) . '%';
            $row['createtime_text'] = $row['create_time'] ? date('Y-m-d H:i:s', $row['create_time']) : '';
            $result[] = $row;
        }

        return json([
            'code' => 0,
            'msg' => 'ok',
            'data' => $result,
            'count' => $count
        ]);
    }

    /**
     * 产品Chi tiết
     * GET /app/admin/api/yebao/product-detail
     */
    public function productDetail(Request $request)
    {
        $id = $request->get('id');
        if (!$id) {
            return json(['code' => 1, 'msg' => 'Tham số không hợp lệ']);
        }

        $item = Db::table('yzz_yuebao_product')->where('id', $id)->first();
        if (!$item) {
            return json(['code' => 1, 'msg' => '产品không tồn tại']);
        }

        return json(['code' => 0, 'msg' => 'ok', 'data' => (array)$item]);
    }

    /**
     * Thêm产品
     * POST /app/admin/api/yebao/product-add
     */
    public function productAdd(Request $request)
    {
        $name = $request->post('name');
        $type = $request->post('type', 'current');
        $rate = floatval($request->post('rate', 0));
        $rateDesc = $request->post('rate_desc', '');
        $durationDays = intval($request->post('duration_days', 0));
        $settleCycleHours = intval($request->post('settle_cycle_hours', 1));
        $minAmount = floatval($request->post('min_amount', 1));
        $maxInterest = floatval($request->post('max_interest', 0));
        $auditMultiple = floatval($request->post('audit_multiple', 1));
        $autoClaim = intval($request->post('auto_claim', 0));
        $status = intval($request->post('status', 1));
        $sort = intval($request->post('sort', 0));

        if (!$name) {
            return json(['code' => 1, 'msg' => '产品名称không được để trống']);
        }

        if (!in_array($type, ['current', 'fixed'])) {
            return json(['code' => 1, 'msg' => '无效的产品类型']);
        }

        if ($rate <= 0) {
            return json(['code' => 1, 'msg' => '日利率必须大于0']);
        }

        if ($type == 'fixed' && $durationDays <= 0) {
            return json(['code' => 1, 'msg' => '定期产品必须Cài đặt周期天数']);
        }

        $id = Db::table('yzz_yuebao_product')->insertGetId([
            'name' => $name,
            'type' => $type,
            'rate' => $rate,
            'rate_desc' => $rateDesc,
            'duration_days' => $durationDays,
            'settle_cycle_hours' => $settleCycleHours,
            'min_amount' => $minAmount,
            'max_interest' => $maxInterest,
            'audit_multiple' => $auditMultiple,
            'auto_claim' => $autoClaim,
            'status' => $status,
            'sort' => $sort,
            'create_time' => time(),
        ]);

        return json(['code' => 0, 'msg' => 'ThêmThành công', 'data' => ['id' => $id]]);
    }

    /**
     * 编辑产品
     * POST /app/admin/api/yebao/product-edit
     */
    public function productEdit(Request $request)
    {
        $id = $request->post('id');
        if (!$id) {
            return json(['code' => 1, 'msg' => 'Tham số không hợp lệ']);
        }

        $item = Db::table('yzz_yuebao_product')->where('id', $id)->first();
        if (!$item) {
            return json(['code' => 1, 'msg' => '产品không tồn tại']);
        }

        $data = [];

        $name = $request->post('name');
        if ($name !== null) $data['name'] = $name;

        $type = $request->post('type');
        if ($type !== null) $data['type'] = $type;

        $rate = $request->post('rate');
        if ($rate !== null) $data['rate'] = floatval($rate);

        $rateDesc = $request->post('rate_desc');
        if ($rateDesc !== null) $data['rate_desc'] = $rateDesc;

        $durationDays = $request->post('duration_days');
        if ($durationDays !== null) $data['duration_days'] = intval($durationDays);

        $settleCycleHours = $request->post('settle_cycle_hours');
        if ($settleCycleHours !== null) $data['settle_cycle_hours'] = intval($settleCycleHours);

        $minAmount = $request->post('min_amount');
        if ($minAmount !== null) $data['min_amount'] = floatval($minAmount);

        $maxInterest = $request->post('max_interest');
        if ($maxInterest !== null) $data['max_interest'] = floatval($maxInterest);

        $auditMultiple = $request->post('audit_multiple');
        if ($auditMultiple !== null) $data['audit_multiple'] = floatval($auditMultiple);

        $autoClaim = $request->post('auto_claim');
        if ($autoClaim !== null) $data['auto_claim'] = intval($autoClaim);

        $status = $request->post('status');
        if ($status !== null) $data['status'] = intval($status);

        $sort = $request->post('sort');
        if ($sort !== null) $data['sort'] = intval($sort);

        if (!empty($data)) {
            Db::table('yzz_yuebao_product')->where('id', $id)->update($data);
        }

        return json(['code' => 0, 'msg' => 'SửaThành công']);
    }

    /**
     * Xóa产品
     * POST /app/admin/api/yebao/product-delete
     */
    public function productDelete(Request $request)
    {
        $id = $request->post('id');
        $ids = $request->post('ids', []);

        if (!$id && empty($ids)) {
            return json(['code' => 1, 'msg' => 'Tham số không hợp lệ']);
        }

        $deleteIds = $id ? [$id] : $ids;

        // 检查是否有持仓使用该产品
        $usedCount = Db::table('yzz_yuebao_holding')
            ->whereIn('product_id', $deleteIds)
            ->where('status', 'running')
            ->count();

        if ($usedCount > 0) {
            return json(['code' => 1, 'msg' => '该产品有Người dùng持仓中，无法Xóa']);
        }

        Db::table('yzz_yuebao_product')->whereIn('id', $deleteIds)->delete();

        return json(['code' => 0, 'msg' => 'XóaThành công']);
    }

    /**
     * 更新产品状态
     * POST /app/admin/api/yebao/product-status
     */
    public function productStatus(Request $request)
    {
        $id = $request->post('id');
        $status = $request->post('status');

        if (!$id || $status === null) {
            return json(['code' => 1, 'msg' => 'Tham số không hợp lệ']);
        }

        Db::table('yzz_yuebao_product')->where('id', $id)->update([
            'status' => intval($status),
        ]);

        return json(['code' => 0, 'msg' => '状态更新Thành công']);
    }

    /**
     * 产品选项（下拉框）
     * GET /app/admin/api/yebao/product-options
     */
    public function productOptions(Request $request)
    {
        $type = $request->get('type', '');

        $query = Db::table('yzz_yuebao_product')
            ->where('status', 1);

        if ($type) {
            $query->where('type', $type);
        }

        $products = $query->orderBy('sort', 'desc')
            ->get(['id as value', 'name as label', 'type', 'rate', 'duration_days']);

        return json(['code' => 0, 'msg' => 'ok', 'data' => $products]);
    }

    /**
     * 产品类型选项
     * GET /app/admin/api/yebao/product-type-options
     */
    public function productTypeOptions(Request $request)
    {
        $types = [
            ['value' => 'current', 'label' => '随存随取'],
            ['value' => 'fixed', 'label' => '定期'],
        ];

        return json(['code' => 0, 'msg' => 'ok', 'data' => $types]);
    }

    /**
     * ============================================
     * 持仓lịch sử管理 API
     * ============================================
     */

    /**
     * 持仓列表
     * GET /app/admin/api/yebao/holding-list
     */
    public function holdingList(Request $request)
    {
        $page = $request->get('page', 1);
        // 兼容 page_size 和 limit
        $limit = $request->get('page_size', $request->get('limit', 20));
        $username = $request->get('username', '');
        $orderId = $request->get('order_id', '');
        $productId = $request->get('product_id', '');
        $status = $request->get('status', '');
        $startTime = $request->get('start_time', '');
        $endTime = $request->get('end_time', '');

        $query = Db::table('yzz_yuebao_holding');

        if ($username) {
            $query->where('username', 'like', '%' . addcslashes($username, '%_') . '%');
        }

        if ($orderId) {
            $query->where('order_id', 'like', '%' . addcslashes($orderId, '%_') . '%');
        }

        if ($productId) {
            $query->where('product_id', $productId);
        }

        if ($status) {
            $query->where('status', $status);
        }

        if ($startTime) {
            $query->where('create_time', '>=', strtotime($startTime));
        }

        if ($endTime) {
            $query->where('create_time', '<=', strtotime($endTime . ' 23:59:59'));
        }

        $count = $query->count();

        // 统计
        $statsQuery = clone $query;
        $stats = $statsQuery->selectRaw('
            SUM(amount) as total_amount,
            SUM(actual_interest) as total_interest,
            SUM(CASE WHEN status = "running" THEN amount ELSE 0 END) as running_amount
        ')->first();

        $offset = ($page - 1) * $limit;
        $list = $query->orderBy('id', 'desc')
            ->offset($offset)
            ->limit($limit)
            ->get();

        $statusMap = [
            'running' => '收息中',
            'settled' => '已结算',
            'cancelled' => 'Đã hủy',
        ];

        $result = [];
        foreach ($list as $item) {
            $row = (array)$item;
            $row['status_text'] = $statusMap[$row['status']] ?? $row['status'];
            // 根据duration_days判断产品类型
            $row['type'] = $row['duration_days'] > 0 ? 'fixed' : 'current';
            $row['rate_percent'] = bcmul($row['rate'], 100, 4) . '%';
            $row['createtime_text'] = $row['create_time'] ? date('Y-m-d H:i:s', $row['create_time']) : '';
            $row['endtime_text'] = $row['end_time'] ? date('Y-m-d H:i:s', $row['end_time']) : '';
            $row['settletime_text'] = $row['settle_time'] ? date('Y-m-d H:i:s', $row['settle_time']) : '';
            $result[] = $row;
        }

        return json([
            'code' => 0,
            'msg' => 'ok',
            'data' => $result,
            'count' => $count,
            'stats' => [
                'total_amount' => round($stats->total_amount ?? 0, 2),
                'total_interest' => round($stats->total_interest ?? 0, 2),
                'running_amount' => round($stats->running_amount ?? 0, 2),
            ]
        ]);
    }

    /**
     * 持仓Chi tiết
     * GET /app/admin/api/yebao/holding-detail
     */
    public function holdingDetail(Request $request)
    {
        $id = $request->get('id');
        if (!$id) {
            return json(['code' => 1, 'msg' => 'Tham số không hợp lệ']);
        }

        $item = Db::table('yzz_yuebao_holding')->where('id', $id)->first();
        if (!$item) {
            return json(['code' => 1, 'msg' => 'lịch sửkhông tồn tại']);
        }

        $row = (array)$item;
        $row['createtime_text'] = $row['create_time'] ? date('Y-m-d H:i:s', $row['create_time']) : '';
        $row['endtime_text'] = $row['end_time'] ? date('Y-m-d H:i:s', $row['end_time']) : '';

        return json(['code' => 0, 'msg' => 'ok', 'data' => $row]);
    }

    /**
     * 强制结算
     * POST /app/admin/api/yebao/holding-settle
     */
    public function holdingSettle(Request $request)
    {
        $id = $request->post('id');
        if (!$id) {
            return json(['code' => 1, 'msg' => 'Tham số không hợp lệ']);
        }

        $holding = Db::table('yzz_yuebao_holding')->where('id', $id)->first();
        if (!$holding) {
            return json(['code' => 1, 'msg' => 'lịch sửkhông tồn tại']);
        }

        if ($holding->status != 'running') {
            return json(['code' => 1, 'msg' => '该持仓已结算hoặcĐã hủy']);
        }

        Db::beginTransaction();
        try {
            $now = time();
            $interest = $holding->actual_interest;
            $totalAmount = bcadd($holding->amount, $interest, 2);

            // 更新持仓状态
            Db::table('yzz_yuebao_holding')->where('id', $id)->update([
                'status' => 'settled',
                'settle_time' => $now,
            ]);

            // 返还本金+利息到Người dùngSố dư
            $member = Db::table('caipiao_member')->where('id', $holding->uid)->first();
            $beforeBalance = $member->balance ?? 0;
            $afterBalance = bcadd($beforeBalance, $totalAmount, 2);

            Db::table('caipiao_member')->where('id', $holding->uid)->update([
                'balance' => $afterBalance,
            ]);

            // lịch sử账变
            $orderId = YuebaoConstant::generateOrderId('YBSET');
            Db::table('yzz_yuebao_record')->insert([
                'order_id' => $orderId,
                'uid' => $holding->uid,
                'username' => $holding->username,
                'type' => 'settle',
                'product_id' => $holding->product_id,
                'product_name' => $holding->product_name,
                'amount' => $totalAmount,
                'before_balance' => $beforeBalance,
                'after_balance' => $afterBalance,
                'before_yebao' => $holding->amount,
                'after_yebao' => 0,
                'status' => 'success',
                'remark' => '管理员强制结算',
                'create_time' => $now,
            ]);

            Db::commit();

            return json(['code' => 0, 'msg' => '结算Thành công', 'data' => [
                'amount' => $holding->amount,
                'interest' => $interest,
                'total' => $totalAmount,
            ]]);
        } catch (\Exception $e) {
            Db::rollBack();
            return json(['code' => 1, 'msg' => '结算Thất bại：' . $e->getMessage()]);
        }
    }

    /**
     * Xóa持仓lịch sử
     * POST /app/admin/api/yebao/holding-delete
     */
    public function holdingDelete(Request $request)
    {
        $id = $request->post('id');
        if (!$id) {
            return json(['code' => 1, 'msg' => 'Tham số không hợp lệ']);
        }

        $holding = Db::table('yzz_yuebao_holding')->where('id', $id)->first();
        if (!$holding) {
            return json(['code' => 1, 'msg' => 'lịch sửkhông tồn tại']);
        }

        if ($holding->status == 'running') {
            return json(['code' => 1, 'msg' => '收息中的持仓不能Xóa，请先结算']);
        }

        Db::table('yzz_yuebao_holding')->where('id', $id)->delete();

        return json(['code' => 0, 'msg' => 'XóaThành công']);
    }

    /**
     * 持仓统计
     * GET /app/admin/api/yebao/holding-stats
     */
    public function holdingStats(Request $request)
    {
        $stats = Db::table('yzz_yuebao_holding')
            ->selectRaw('
                COUNT(*) as total_count,
                SUM(CASE WHEN status = "running" THEN 1 ELSE 0 END) as running_count,
                SUM(CASE WHEN status = "settled" THEN 1 ELSE 0 END) as settled_count,
                SUM(amount) as total_amount,
                SUM(CASE WHEN status = "running" THEN amount ELSE 0 END) as running_amount,
                SUM(actual_interest) as total_interest
            ')
            ->first();

        return json(['code' => 0, 'msg' => 'ok', 'data' => [
            'total_count' => $stats->total_count ?? 0,
            'running_count' => $stats->running_count ?? 0,
            'settled_count' => $stats->settled_count ?? 0,
            'total_amount' => round($stats->total_amount ?? 0, 2),
            'running_amount' => round($stats->running_amount ?? 0, 2),
            'total_interest' => round($stats->total_interest ?? 0, 2),
        ]]);
    }

    /**
     * 持仓状态选项
     * GET /app/admin/api/yebao/holding-status-options
     */
    public function holdingStatusOptions(Request $request)
    {
        $statuses = [
            ['value' => 'running', 'label' => '收息中'],
            ['value' => 'settled', 'label' => '已结算'],
            ['value' => 'cancelled', 'label' => 'Đã hủy'],
        ];

        return json(['code' => 0, 'msg' => 'ok', 'data' => $statuses]);
    }

    /**
     * ============================================
     * 账变lịch sử API
     * ============================================
     */

    /**
     * 账变lịch sử列表
     * GET /app/admin/api/yebao/record-list
     */
    public function recordList(Request $request)
    {
        $page = $request->get('page', 1);
        // 兼容 page_size 和 limit
        $limit = $request->get('page_size', $request->get('limit', 20));
        $username = $request->get('username', '');
        $orderId = $request->get('order_id', '');
        $type = $request->get('type', '');
        $startTime = $request->get('start_time', '');
        $endTime = $request->get('end_time', '');

        $query = Db::table('yzz_yuebao_record');

        if ($username) {
            $query->where('username', 'like', '%' . addcslashes($username, '%_') . '%');
        }

        if ($orderId) {
            $query->where('order_id', 'like', '%' . addcslashes($orderId, '%_') . '%');
        }

        if ($type) {
            $query->where('type', $type);
        }

        if ($startTime) {
            $query->where('create_time', '>=', strtotime($startTime));
        }

        if ($endTime) {
            $query->where('create_time', '<=', strtotime($endTime . ' 23:59:59'));
        }

        $count = $query->count();

        // 统计
        $statsQuery = clone $query;
        $stats = $statsQuery->selectRaw('
            SUM(CASE WHEN type = "deposit" THEN amount ELSE 0 END) as total_deposit,
            SUM(CASE WHEN type = "withdraw" THEN amount ELSE 0 END) as total_withdraw,
            SUM(CASE WHEN type IN ("interest", "income", "claim") THEN amount ELSE 0 END) as total_interest
        ')->first();

        $offset = ($page - 1) * $limit;
        $list = $query->orderBy('id', 'desc')
            ->offset($offset)
            ->limit($limit)
            ->get();

        $typeMap = [
            'deposit' => '存入',
            'withdraw' => '取出',
            'interest' => '收益发放',
            'income' => '每日收益',
            'settle' => '定期结算',
            'claim' => '领取收益',
        ];

        $result = [];
        foreach ($list as $item) {
            $row = (array)$item;
            $row['type_text'] = $typeMap[$row['type']] ?? $row['type'];
            $row['createtime_text'] = $row['create_time'] ? date('Y-m-d H:i:s', $row['create_time']) : '';
            // claim类型显示Số dư变动，其他类型显示Số dư宝变动
            if ($row['type'] === 'claim') {
                $row['before_yebao'] = $row['before_balance'];
                $row['after_yebao'] = $row['after_balance'];
            }
            $result[] = $row;
        }

        return json([
            'code' => 0,
            'msg' => 'ok',
            'data' => $result,
            'count' => $count,
            'stats' => [
                'total_deposit' => round($stats->total_deposit ?? 0, 2),
                'total_withdraw' => round($stats->total_withdraw ?? 0, 2),
                'total_interest' => round($stats->total_interest ?? 0, 2),
            ]
        ]);
    }

    /**
     * 账变lịch sửChi tiết
     * GET /app/admin/api/yebao/record-detail
     */
    public function recordDetail(Request $request)
    {
        $id = $request->get('id');
        if (!$id) {
            return json(['code' => 1, 'msg' => 'Tham số không hợp lệ']);
        }

        $item = Db::table('yzz_yuebao_record')->where('id', $id)->first();
        if (!$item) {
            return json(['code' => 1, 'msg' => 'lịch sửkhông tồn tại']);
        }

        $row = (array)$item;
        $row['createtime_text'] = $row['create_time'] ? date('Y-m-d H:i:s', $row['create_time']) : '';

        return json(['code' => 0, 'msg' => 'ok', 'data' => $row]);
    }

    /**
     * Xóa账变lịch sử
     * POST /app/admin/api/yebao/record-delete
     */
    public function recordDelete(Request $request)
    {
        $id = $request->post('id');
        if (!$id) {
            return json(['code' => 1, 'msg' => 'Tham số không hợp lệ']);
        }

        Db::table('yzz_yuebao_record')->where('id', $id)->delete();

        return json(['code' => 0, 'msg' => 'XóaThành công']);
    }

    /**
     * 账变类型选项
     * GET /app/admin/api/yebao/record-type-options
     */
    public function recordTypeOptions(Request $request)
    {
        $types = [
            ['value' => 'deposit', 'label' => '存入'],
            ['value' => 'withdraw', 'label' => '取出'],
            ['value' => 'interest', 'label' => '收益发放'],
            ['value' => 'income', 'label' => '每日收益'],
            ['value' => 'settle', 'label' => '定期结算'],
        ];

        return json(['code' => 0, 'msg' => 'ok', 'data' => $types]);
    }

    /**
     * ============================================
     * 管理员操作 API
     * ============================================
     */

    /**
     * 管理员帮Người dùng存入
     * POST /app/admin/api/yebao/admin-deposit
     */
    public function adminDeposit(Request $request)
    {
        $username = $request->post('username');
        $productId = $request->post('product_id', 1);
        $amount = floatval($request->post('amount', 0));
        $remark = $request->post('remark', '管理员操作');

        if (!$username) {
            return json(['code' => 1, 'msg' => '请输入Tên người dùng']);
        }

        if ($amount <= 0) {
            return json(['code' => 1, 'msg' => '存入Số tiền必须大于0']);
        }

        $member = Db::table('caipiao_member')->where('username', $username)->first();
        if (!$member) {
            return json(['code' => 1, 'msg' => 'Người dùng không tồn tại']);
        }

        $product = Db::table('yzz_yuebao_product')->where('id', $productId)->where('status', 1)->first();
        if (!$product) {
            return json(['code' => 1, 'msg' => '产品không tồn tạihoặc已下架']);
        }

        if ($amount < $product->min_amount) {
            return json(['code' => 1, 'msg' => '存入Số tiền不能低于' . $product->min_amount]);
        }

        Db::beginTransaction();
        try {
            $now = time();
            $orderId = YuebaoConstant::generateOrderId(YuebaoConstant::ORDER_PREFIX_IN);
            $uid = $member->id;

            // Lấy当前Số dư宝Số dư
            $stats = Db::table('yzz_yuebao_stats')->where('uid', $uid)->first();
            $beforeYebao = $stats ? ($product->type == 'current' ? $stats->current_amount : $stats->fixed_amount) : 0;
            $afterYebao = bcadd($beforeYebao, $amount, 2);

            // 计算预期收益 (定期) - rate为期间总利率
            $expectedInterest = 0;
            $endtime = 0;
            if ($product->type == 'fixed' && $product->duration_days > 0) {
                $expectedInterest = bcmul($amount, $product->rate, 2);
                $endtime = $now + ($product->duration_days * 86400);
            }

            // 创建持仓lịch sử
            Db::table('yzz_yuebao_holding')->insert([
                'order_id' => $orderId,
                'uid' => $uid,
                'username' => $member->username,
                'product_id' => $productId,
                'product_name' => $product->name,
                'amount' => $amount,
                'rate' => $product->rate,
                'duration_days' => $product->duration_days,
                'expected_interest' => $expectedInterest,
                'actual_interest' => 0,
                'status' => 'running',
                'create_time' => $now,
                'end_time' => $endtime,
            ]);

            // 更新统计表
            if ($stats) {
                $updateData = ['update_time' => $now];
                if ($product->type == 'current') {
                    $updateData['current_amount'] = Db::raw("current_amount + {$amount}");
                } else {
                    $updateData['fixed_amount'] = Db::raw("fixed_amount + {$amount}");
                }
                Db::table('yzz_yuebao_stats')->where('uid', $uid)->update($updateData);
            } else {
                Db::table('yzz_yuebao_stats')->insert([
                    'uid' => $uid,
                    'total_interest' => 0,
                    'current_amount' => $product->type == 'current' ? $amount : 0,
                    'fixed_amount' => $product->type == 'fixed' ? $amount : 0,
                    'yesterday_interest' => 0,
                    'seven_day_rate' => 0,
                    'is_open' => 1,
                    'update_time' => $now,
                ]);
            }

            // 同步更新 member 表 (兼容老版本)
            if ($product->type == 'current') {
                Db::table('caipiao_member')->where('id', $uid)->update([
                    'yebmoney' => Db::raw("yebmoney + {$amount}"),
                    'yebtime' => $now,
                ]);
            } else {
                Db::table('caipiao_member')->where('id', $uid)->update([
                    'dyebmoney' => Db::raw("dyebmoney + {$amount}"),
                ]);
            }

            // lịch sửVòng cược
            Db::table('yzz_yuebao_record')->insert([
                'order_id' => $orderId,
                'uid' => $uid,
                'username' => $member->username,
                'type' => 'deposit',
                'product_id' => $productId,
                'product_name' => $product->name,
                'amount' => $amount,
                'before_balance' => $member->balance,
                'after_balance' => $member->balance,
                'before_yebao' => $beforeYebao,
                'after_yebao' => $afterYebao,
                'status' => 'success',
                'remark' => $remark,
                'create_time' => $now,
            ]);

            Db::commit();

            return json(['code' => 0, 'msg' => '存入Thành công', 'data' => [
                'order_id' => $orderId,
                'amount' => $amount,
            ]]);
        } catch (\Exception $e) {
            Db::rollBack();
            return json(['code' => 1, 'msg' => '存入Thất bại：' . $e->getMessage()]);
        }
    }

    /**
     * 管理员帮Người dùng取出
     * POST /app/admin/api/yebao/admin-withdraw
     */
    public function adminWithdraw(Request $request)
    {
        $username = $request->post('username');
        $amount = floatval($request->post('amount', 0));
        $type = $request->post('type', 'current'); // current=活期, fixed=定期
        $remark = $request->post('remark', '管理员操作');

        if (!$username) {
            return json(['code' => 1, 'msg' => '请输入Tên người dùng']);
        }

        if ($amount <= 0) {
            return json(['code' => 1, 'msg' => '取出Số tiền必须大于0']);
        }

        $member = Db::table('caipiao_member')->where('username', $username)->first();
        if (!$member) {
            return json(['code' => 1, 'msg' => 'Người dùng không tồn tại']);
        }

        $uid = $member->id;
        $stats = Db::table('yzz_yuebao_stats')->where('uid', $uid)->first();
        if (!$stats) {
            return json(['code' => 1, 'msg' => 'Người dùng没有Số dư宝lịch sử']);
        }

        $currentAmount = $type == 'current' ? $stats->current_amount : $stats->fixed_amount;
        if ($amount > $currentAmount) {
            return json(['code' => 1, 'msg' => 'Số dư không đủ，当前' . ($type == 'current' ? '活期' : '定期') . 'Số dư：' . $currentAmount]);
        }

        Db::beginTransaction();
        try {
            $now = time();
            $orderId = YuebaoConstant::generateOrderId(YuebaoConstant::ORDER_PREFIX_OUT);

            $beforeYebao = $currentAmount;
            $afterYebao = bcsub($currentAmount, $amount, 2);
            $beforeBalance = $member->balance;
            $afterBalance = bcadd($beforeBalance, $amount, 2);

            // 更新统计表
            $updateData = ['update_time' => $now];
            if ($type == 'current') {
                $updateData['current_amount'] = Db::raw("current_amount - {$amount}");
            } else {
                $updateData['fixed_amount'] = Db::raw("fixed_amount - {$amount}");
            }
            Db::table('yzz_yuebao_stats')->where('uid', $uid)->update($updateData);

            // 同步更新 member 表
            $memberUpdate = ['balance' => $afterBalance];
            if ($type == 'current') {
                $memberUpdate['yebmoney'] = Db::raw("yebmoney - {$amount}");
            } else {
                $memberUpdate['dyebmoney'] = Db::raw("dyebmoney - {$amount}");
            }
            Db::table('caipiao_member')->where('id', $uid)->update($memberUpdate);

            // 更新持仓lịch sử (活期按比例扣减)
            if ($type == 'current') {
                $holdings = Db::table('yzz_yuebao_holding')
                    ->where('uid', $uid)
                    ->where('status', 'running')
                    ->where('duration_days', 0)
                    ->orderBy('create_time', 'asc')
                    ->get();

                $remainingAmount = $amount;
                foreach ($holdings as $holding) {
                    if ($remainingAmount <= 0) break;
                    
                    if ($holding->amount <= $remainingAmount) {
                        Db::table('yzz_yuebao_holding')->where('id', $holding->id)->update([
                            'status' => 'settled',
                            'settle_time' => $now,
                        ]);
                        $remainingAmount = bcsub($remainingAmount, $holding->amount, 2);
                    } else {
                        Db::table('yzz_yuebao_holding')->where('id', $holding->id)->update([
                            'amount' => Db::raw("amount - {$remainingAmount}"),
                        ]);
                        $remainingAmount = 0;
                    }
                }
            }

            // lịch sửVòng cược
            Db::table('yzz_yuebao_record')->insert([
                'order_id' => $orderId,
                'uid' => $uid,
                'username' => $member->username,
                'type' => 'withdraw',
                'product_id' => null,
                'product_name' => $type == 'current' ? '活期取出' : '定期取出',
                'amount' => $amount,
                'before_balance' => $beforeBalance,
                'after_balance' => $afterBalance,
                'before_yebao' => $beforeYebao,
                'after_yebao' => $afterYebao,
                'status' => 'success',
                'remark' => $remark,
                'create_time' => $now,
            ]);

            Db::commit();

            return json(['code' => 0, 'msg' => '取出Thành công', 'data' => [
                'order_id' => $orderId,
                'amount' => $amount,
                'to_balance' => $afterBalance,
            ]]);
        } catch (\Exception $e) {
            Db::rollBack();
            return json(['code' => 1, 'msg' => '取出Thất bại：' . $e->getMessage()]);
        }
    }

    /**
     * Tra cứuNgười dùngSố dư宝信息
     * GET /app/admin/api/yebao/user-info
     */
    public function userInfo(Request $request)
    {
        $username = $request->get('username');
        $uid = $request->get('uid');

        if (!$username && !$uid) {
            return json(['code' => 1, 'msg' => '请输入Tên người dùnghoặcNgười dùngID']);
        }

        $query = Db::table('caipiao_member');
        if ($uid) {
            $query->where('id', $uid);
        } else {
            $query->where('username', $username);
        }
        $member = $query->first();

        if (!$member) {
            return json(['code' => 1, 'msg' => 'Người dùng không tồn tại']);
        }

        $stats = Db::table('yzz_yuebao_stats')->where('uid', $member->id)->first();

        // Lấy持仓列表
        $holdings = Db::table('yzz_yuebao_holding')
            ->where('uid', $member->id)
            ->where('status', 'running')
            ->get();

        return json(['code' => 0, 'msg' => 'ok', 'data' => [
            'uid' => $member->id,
            'username' => $member->username,
            'balance' => $member->balance,
            'current_amount' => $stats->current_amount ?? 0,
            'fixed_amount' => $stats->fixed_amount ?? 0,
            'total_amount' => bcadd($stats->current_amount ?? 0, $stats->fixed_amount ?? 0, 2),
            'total_interest' => $stats->total_interest ?? 0,
            'yesterday_interest' => $stats->yesterday_interest ?? 0,
            'is_open' => $stats->is_open ?? 0,
            'holdings' => $holdings,
        ]]);
    }

    /**
     * ============================================
     * 统计 API
     * ============================================
     */

    /**
     * Người dùng统计列表
     * GET /app/admin/api/yebao/user-stats
     */
    public function userStats(Request $request)
    {
        $page = $request->get('page', 1);
        // 兼容 page_size 和 limit
        $limit = $request->get('page_size', $request->get('limit', 20));
        $username = $request->get('username', '');

        $query = Db::table('yzz_yuebao_stats as s')
            ->leftJoin('caipiao_member as m', 's.uid', '=', 'm.id')
            ->select('s.*', 'm.username', 'm.nickname');

        if ($username) {
            $escapedUsername = '%' . addcslashes($username, '%_') . '%';
            $query->where(function($q) use ($escapedUsername) {
                $q->where('m.username', 'like', $escapedUsername)
                  ->orWhere('m.nickname', 'like', $escapedUsername);
            });
        }

        $count = $query->count();

        $offset = ($page - 1) * $limit;
        $list = $query->orderBy('s.total_interest', 'desc')
            ->offset($offset)
            ->limit($limit)
            ->get();

        $result = [];
        foreach ($list as $item) {
            $row = (array)$item;
            $row['total_amount'] = bcadd($row['current_amount'], $row['fixed_amount'], 2);
            $row['updatetime_text'] = $row['update_time'] ? date('Y-m-d H:i:s', $row['update_time']) : '';
            $result[] = $row;
        }

        return json([
            'code' => 0,
            'msg' => 'ok',
            'data' => $result,
            'count' => $count
        ]);
    }

    /**
     * 整体统计概览
     * GET /app/admin/api/yebao/overview
     */
    public function overview(Request $request)
    {
        // 持仓统计
        $holdingStats = Db::table('yzz_yuebao_holding')
            ->selectRaw('
                COUNT(DISTINCT uid) as user_count,
                SUM(CASE WHEN status = "running" THEN amount ELSE 0 END) as running_amount,
                SUM(CASE WHEN status = "running" THEN actual_interest ELSE 0 END) as pending_interest
            ')
            ->first();

        // 累计收益 = 已领取(从stats表) + 待领取(从holding表)
        $claimedInterest = Db::table('yzz_yuebao_stats')->sum('total_interest') ?? 0;
        $totalInterest = bcadd((string)$claimedInterest, (string)($holdingStats->pending_interest ?? 0), 2);

        // Hôm nay统计
        $todayStart = strtotime(date('Y-m-d'));
        $todayStats = Db::table('yzz_yuebao_record')
            ->where('create_time', '>=', $todayStart)
            ->selectRaw('
                SUM(CASE WHEN type = "deposit" THEN amount ELSE 0 END) as today_deposit,
                SUM(CASE WHEN type = "withdraw" THEN amount ELSE 0 END) as today_withdraw,
                SUM(CASE WHEN type IN ("interest", "income", "claim") THEN amount ELSE 0 END) as today_interest
            ')
            ->first();

        // 产品统计
        $productCount = Db::table('yzz_yuebao_product')->where('status', 1)->count();

        return json([
            'code' => 0,
            'msg' => 'ok',
            'data' => [
                'user_count' => $holdingStats->user_count ?? 0,
                'running_amount' => round($holdingStats->running_amount ?? 0, 2),
                'total_interest' => round($totalInterest, 2),
                'today_deposit' => round($todayStats->today_deposit ?? 0, 2),
                'today_withdraw' => round($todayStats->today_withdraw ?? 0, 2),
                'today_interest' => round($todayStats->today_interest ?? 0, 2),
                'product_count' => $productCount,
            ]
        ]);
    }

    /**
     * ============================================
     * 配置管理 API
     * ============================================
     */

    /**
     * Lấy利息宝配置
     * GET /app/admin/api/yebao/config
     */
    public function config(Request $request)
    {
        $config = Db::table('caipiao_option')
            ->where('name', 'yuebao_interest_config')
            ->value('value');

        $defaultConfig = [
            'settle_cycle' => '1小时',
            'settle_cycle_hours' => 1,
            'annual_rate' => '4%',
            'annual_rate_value' => 0.04,
            'min_amount' => 20,
            'max_interest' => '不限制',
            'max_interest_value' => 0,
            'claim_time' => '隔天领取',
            'claim_time_hours' => 24,
            'audit_multiple' => 1,
            'auto_claim' => 0,  // 0=手动领取 1=自动发放
        ];

        if ($config) {
            $config = json_decode($config, true);
            $config = array_merge($defaultConfig, $config);
        } else {
            $config = $defaultConfig;
        }

        return json(['code' => 0, 'msg' => 'ok', 'data' => $config]);
    }

    /**
     * Lưu利息宝配置
     * POST /app/admin/api/yebao/config-save
     */
    public function configSave(Request $request)
    {
        $data = $request->post();

        $config = [
            'settle_cycle' => $data['settle_cycle'] ?? '1小时',
            'settle_cycle_hours' => floatval($data['settle_cycle_hours'] ?? 1),
            'annual_rate' => $data['annual_rate'] ?? '4%',
            'annual_rate_value' => floatval($data['annual_rate_value'] ?? 0.04),
            'min_amount' => floatval($data['min_amount'] ?? 20),
            'max_interest' => $data['max_interest'] ?? '不限制',
            'max_interest_value' => floatval($data['max_interest_value'] ?? 0),
            'claim_time' => $data['claim_time'] ?? '隔天领取',
            'claim_time_hours' => floatval($data['claim_time_hours'] ?? 24),
            'audit_multiple' => floatval($data['audit_multiple'] ?? 1),
            'auto_claim' => intval($data['auto_claim'] ?? 0),
        ];

        $exists = Db::table('caipiao_option')
            ->where('name', 'yuebao_interest_config')
            ->exists();

        if ($exists) {
            Db::table('caipiao_option')
                ->where('name', 'yuebao_interest_config')
                ->update(['value' => json_encode($config)]);
        } else {
            Db::table('caipiao_option')->insert([
                'name' => 'yuebao_interest_config',
                'value' => json_encode($config),
            ]);
        }

        return json(['code' => 0, 'msg' => 'LưuThành công']);
    }
}
