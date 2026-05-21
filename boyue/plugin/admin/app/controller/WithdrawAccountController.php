<?php

namespace plugin\admin\app\controller;

use plugin\admin\app\common\Util;
use support\Request;
use support\Response;
use support\Db;

/**
 * 提现tài khoản管理
 */
class WithdrawAccountController
{
    /**
     * 不需要登录的方法
     * @var string[]
     */
    protected $noNeedLogin = [];

    /**
     * 不需要鉴权的方法
     * @var string[]
     */
    protected $noNeedAuth = [];

    /**
     * 提现tài khoản列表
     * @param Request $request
     * @return Response
     */
    public function index(Request $request): Response
    {
        return raw_view('withdraw-account/index');
    }

    /**
     * Tra cứu
     * @param Request $request
     * @return Response
     */
    public function select(Request $request): Response
    {
        $uid = $request->get('uid');
        $username = $request->get('username', '');
        $type = $request->get('type', '');
        $status = $request->get('status', '');
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 20);
        
        $query = Db::table('caipiao_withdraw_account')
            ->leftJoin('caipiao_member', 'caipiao_withdraw_account.uid', '=', 'caipiao_member.id')
            ->select(
                'caipiao_withdraw_account.*',
                'caipiao_member.username'
            );

        // Người dùngID筛选
        if ($uid !== null && $uid !== '') {
            $query->where('caipiao_withdraw_account.uid', (int)$uid);
        }

        // Tên người dùngTìm kiếm
        if (!empty($username)) {
            $query->where('caipiao_member.username', 'like', '%' . $username . '%');
        }

        // tài khoản类型筛选
        if (!empty($type)) {
            $query->where('caipiao_withdraw_account.type', $type);
        }

        // 状态筛选（默认只显示启用的tài khoản）
        if ($status !== '') {
            $query->where('caipiao_withdraw_account.status', $status);
        } else {
            // 默认只显示启用的
            $query->where('caipiao_withdraw_account.status', 1);
        }

        $total = $query->count();
        $query->orderBy('caipiao_withdraw_account.id', 'desc');
        
        $records = $query->offset(($page - 1) * $limit)->limit($limit)->get();

        // 格式化dữ liệu
        foreach ($records as &$record) {
            $record->type_text = $this->getTypeText($record->type);
            $record->status_text = $record->status == 1 ? '启用' : '禁用';
            $record->is_default_text = $record->is_default == 1 ? '是' : '否';
            $record->created_at_text = date('Y-m-d H:i:s', $record->created_at);
            
            // 根据类型显示不同的tài khoản信息
            switch ($record->type) {
                case 'bank':
                    $record->account_info = $record->bank_name . ' - ' . $record->bank_account;
                    break;
                case 'usdt':
                    $record->account_info = $record->usdt_network . ' - ' . substr($record->usdt_address, 0, 10) . '...';
                    break;
                case 'alipay':
                case 'wechat':
                    $record->account_info = $record->bank_account;
                    break;
                default:
                    $record->account_info = '';
            }
        }

        return json(['code' => 0, 'msg' => 'ok', 'count' => $total, 'data' => $records]);
    }

    /**
     * Thêm
     * @param Request $request
     * @return Response
     */
    public function insert(Request $request): Response
    {
        if ($request->method() === 'POST') {
            $data = $request->post();
            
            // 验证必填字段
            if (empty($data['uid'])) {
                return json(['code' => 1, 'msg' => 'Vui lòng chọnNgười dùng']);
            }
            if (empty($data['type'])) {
                return json(['code' => 1, 'msg' => 'Vui lòng chọntài khoản类型']);
            }

            // 根据类型验证不同字段
            switch ($data['type']) {
                case 'bank':
                    if (empty($data['bank_name']) || empty($data['bank_account']) || empty($data['account_name'])) {
                        return json(['code' => 1, 'msg' => '请填写完整的Thẻ ngân hàng信息']);
                    }
                    break;
                case 'usdt':
                    if (empty($data['usdt_address']) || empty($data['usdt_network'])) {
                        return json(['code' => 1, 'msg' => '请填写完整的USDT信息']);
                    }
                    break;
                case 'alipay':
                case 'wechat':
                    if (empty($data['bank_account']) || empty($data['account_name'])) {
                        return json(['code' => 1, 'msg' => '请填写完整的tài khoản信息']);
                    }
                    break;
            }

            // 检查是否đã tồn tại
            $exists = false;
            if ($data['type'] === 'bank') {
                $exists = Db::table('caipiao_withdraw_account')
                    ->where('uid', $data['uid'])
                    ->where('type', 'bank')
                    ->where('bank_account', $data['bank_account'])
                    ->where('status', 1)
                    ->exists();
            } elseif ($data['type'] === 'usdt') {
                $exists = Db::table('caipiao_withdraw_account')
                    ->where('uid', $data['uid'])
                    ->where('type', 'usdt')
                    ->where('usdt_address', $data['usdt_address'])
                    ->where('status', 1)
                    ->exists();
            } else {
                $exists = Db::table('caipiao_withdraw_account')
                    ->where('uid', $data['uid'])
                    ->where('type', $data['type'])
                    ->where('bank_account', $data['bank_account'])
                    ->where('status', 1)
                    ->exists();
            }

            if ($exists) {
                return json(['code' => 1, 'msg' => '该tài khoảnđã tồn tại']);
            }

            // 如果Cài đặt为默认，Hủy该Người dùng其他默认tài khoản
            if (!empty($data['is_default'])) {
                Db::table('caipiao_withdraw_account')
                    ->where('uid', $data['uid'])
                    ->update(['is_default' => 0]);
            }

            $now = time();
            $insertData = [
                'uid' => $data['uid'],
                'type' => $data['type'],
                'bank_name' => $data['bank_name'] ?? '',
                'bank_account' => $data['bank_account'] ?? '',
                'account_name' => $data['account_name'] ?? '',
                'bank_branch' => $data['bank_branch'] ?? '',
                'usdt_address' => $data['usdt_address'] ?? '',
                'usdt_network' => $data['usdt_network'] ?? '',
                'qr_code' => $data['qr_code'] ?? '',
                'is_default' => !empty($data['is_default']) ? 1 : 0,
                'status' => $data['status'] ?? 1,
                'created_at' => $now,
                'updated_at' => $now
            ];

            Db::table('caipiao_withdraw_account')->insert($insertData);

            return json(['code' => 0, 'msg' => 'ThêmThành công']);
        }

        return raw_view('withdraw-account/insert');
    }

    /**
     * 更新
     * @param Request $request
     * @return Response
     */
    public function update(Request $request): Response
    {
        if ($request->method() === 'GET') {
            $id = $request->get('id');
            $account = Db::table('caipiao_withdraw_account')->where('id', $id)->first();
            if (!$account) {
                return json(['code' => 1, 'msg' => 'tài khoảnkhông tồn tại']);
            }
            return raw_view('withdraw-account/update', ['account' => $account]);
        }

        $id = $request->post('id');
        $data = $request->post();

        if (!$id) {
            return json(['code' => 1, 'msg' => 'Tham số không hợp lệ']);
        }

        $account = Db::table('caipiao_withdraw_account')->where('id', $id)->first();
        if (!$account) {
            return json(['code' => 1, 'msg' => 'tài khoảnkhông tồn tại']);
        }

        // 如果Cài đặt为默认，Hủy该Người dùng其他默认tài khoản
        if (!empty($data['is_default']) && $data['is_default'] != $account->is_default) {
            Db::table('caipiao_withdraw_account')
                ->where('uid', $account->uid)
                ->where('id', '!=', $id)
                ->update(['is_default' => 0]);
        }

        $updateData = [
            'bank_name' => $data['bank_name'] ?? '',
            'bank_account' => $data['bank_account'] ?? '',
            'account_name' => $data['account_name'] ?? '',
            'bank_branch' => $data['bank_branch'] ?? '',
            'usdt_address' => $data['usdt_address'] ?? '',
            'usdt_network' => $data['usdt_network'] ?? '',
            'qr_code' => $data['qr_code'] ?? '',
            'is_default' => !empty($data['is_default']) ? 1 : 0,
            'status' => $data['status'] ?? 1,
            'updated_at' => time()
        ];

        Db::table('caipiao_withdraw_account')->where('id', $id)->update($updateData);

        return json(['code' => 0, 'msg' => '更新Thành công']);
    }

    /**
     * 删除
     * @param Request $request
     * @return Response
     */
    public function delete(Request $request): Response
    {
        $ids = (array)$request->post('ids', []);
        
        if (empty($ids)) {
            return json(['code' => 1, 'msg' => 'Tham số không hợp lệ']);
        }

        // 检查是否有默认tài khoản
        $hasDefault = Db::table('caipiao_withdraw_account')
            ->whereIn('id', $ids)
            ->where('is_default', 1)
            ->exists();

        if ($hasDefault) {
            return json(['code' => 1, 'msg' => '不能Xóa默认tài khoản']);
        }

        // 软Xóa
        Db::table('caipiao_withdraw_account')
            ->whereIn('id', $ids)
            ->update([
                'status' => 0,
                'updated_at' => time()
            ]);

        return json(['code' => 0, 'msg' => 'XóaThành công']);
    }

    /**
     * Cài đặt默认tài khoản
     * @param Request $request
     * @return Response
     */
    public function setDefault(Request $request): Response
    {
        $id = $request->post('id');
        
        if (!$id) {
            return json(['code' => 1, 'msg' => 'Tham số không hợp lệ']);
        }

        $account = Db::table('caipiao_withdraw_account')->where('id', $id)->first();
        if (!$account) {
            return json(['code' => 1, 'msg' => 'tài khoảnkhông tồn tại']);
        }

        // Hủy该Người dùng其他默认tài khoản
        Db::table('caipiao_withdraw_account')
            ->where('uid', $account->uid)
            ->update(['is_default' => 0]);

        // Cài đặt当前tài khoản为默认
        Db::table('caipiao_withdraw_account')
            ->where('id', $id)
            ->update([
                'is_default' => 1,
                'updated_at' => time()
            ]);

        return json(['code' => 0, 'msg' => 'Cài đặtThành công']);
    }

    /**
     * Lấytài khoản类型文本
     */
    private function getTypeText($type)
    {
        $map = [
            'bank' => 'Thẻ ngân hàng',
            'usdt' => 'USDT',
            'alipay' => 'Alipay',
            'wechat' => 'WeChat'
        ];
        return $map[$type] ?? $type;
    }
}
