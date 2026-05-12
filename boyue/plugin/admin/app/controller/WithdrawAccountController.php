<?php

namespace plugin\admin\app\controller;

use plugin\admin\app\common\Util;
use support\Request;
use support\Response;
use support\Db;

/**
 * 提现账户管理
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
     * 提现账户列表
     * @param Request $request
     * @return Response
     */
    public function index(Request $request): Response
    {
        return raw_view('withdraw-account/index');
    }

    /**
     * 查询
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

        // 用户ID筛选
        if ($uid !== null && $uid !== '') {
            $query->where('caipiao_withdraw_account.uid', (int)$uid);
        }

        // 用户名搜索
        if (!empty($username)) {
            $query->where('caipiao_member.username', 'like', '%' . $username . '%');
        }

        // 账户类型筛选
        if (!empty($type)) {
            $query->where('caipiao_withdraw_account.type', $type);
        }

        // 状态筛选（默认只显示启用的账户）
        if ($status !== '') {
            $query->where('caipiao_withdraw_account.status', $status);
        } else {
            // 默认只显示启用的
            $query->where('caipiao_withdraw_account.status', 1);
        }

        $total = $query->count();
        $query->orderBy('caipiao_withdraw_account.id', 'desc');
        
        $records = $query->offset(($page - 1) * $limit)->limit($limit)->get();

        // 格式化数据
        foreach ($records as &$record) {
            $record->type_text = $this->getTypeText($record->type);
            $record->status_text = $record->status == 1 ? '启用' : '禁用';
            $record->is_default_text = $record->is_default == 1 ? '是' : '否';
            $record->created_at_text = date('Y-m-d H:i:s', $record->created_at);
            
            // 根据类型显示不同的账户信息
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
     * 添加
     * @param Request $request
     * @return Response
     */
    public function insert(Request $request): Response
    {
        if ($request->method() === 'POST') {
            $data = $request->post();
            
            // 验证必填字段
            if (empty($data['uid'])) {
                return json(['code' => 1, 'msg' => '请选择用户']);
            }
            if (empty($data['type'])) {
                return json(['code' => 1, 'msg' => '请选择账户类型']);
            }

            // 根据类型验证不同字段
            switch ($data['type']) {
                case 'bank':
                    if (empty($data['bank_name']) || empty($data['bank_account']) || empty($data['account_name'])) {
                        return json(['code' => 1, 'msg' => '请填写完整的银行卡信息']);
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
                        return json(['code' => 1, 'msg' => '请填写完整的账户信息']);
                    }
                    break;
            }

            // 检查是否已存在
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
                return json(['code' => 1, 'msg' => '该账户已存在']);
            }

            // 如果设置为默认，取消该用户其他默认账户
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

            return json(['code' => 0, 'msg' => '添加成功']);
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
                return json(['code' => 1, 'msg' => '账户不存在']);
            }
            return raw_view('withdraw-account/update', ['account' => $account]);
        }

        $id = $request->post('id');
        $data = $request->post();

        if (!$id) {
            return json(['code' => 1, 'msg' => '参数错误']);
        }

        $account = Db::table('caipiao_withdraw_account')->where('id', $id)->first();
        if (!$account) {
            return json(['code' => 1, 'msg' => '账户不存在']);
        }

        // 如果设置为默认，取消该用户其他默认账户
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

        return json(['code' => 0, 'msg' => '更新成功']);
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
            return json(['code' => 1, 'msg' => '参数错误']);
        }

        // 检查是否有默认账户
        $hasDefault = Db::table('caipiao_withdraw_account')
            ->whereIn('id', $ids)
            ->where('is_default', 1)
            ->exists();

        if ($hasDefault) {
            return json(['code' => 1, 'msg' => '不能删除默认账户']);
        }

        // 软删除
        Db::table('caipiao_withdraw_account')
            ->whereIn('id', $ids)
            ->update([
                'status' => 0,
                'updated_at' => time()
            ]);

        return json(['code' => 0, 'msg' => '删除成功']);
    }

    /**
     * 设置默认账户
     * @param Request $request
     * @return Response
     */
    public function setDefault(Request $request): Response
    {
        $id = $request->post('id');
        
        if (!$id) {
            return json(['code' => 1, 'msg' => '参数错误']);
        }

        $account = Db::table('caipiao_withdraw_account')->where('id', $id)->first();
        if (!$account) {
            return json(['code' => 1, 'msg' => '账户不存在']);
        }

        // 取消该用户其他默认账户
        Db::table('caipiao_withdraw_account')
            ->where('uid', $account->uid)
            ->update(['is_default' => 0]);

        // 设置当前账户为默认
        Db::table('caipiao_withdraw_account')
            ->where('id', $id)
            ->update([
                'is_default' => 1,
                'updated_at' => time()
            ]);

        return json(['code' => 0, 'msg' => '设置成功']);
    }

    /**
     * 获取账户类型文本
     */
    private function getTypeText($type)
    {
        $map = [
            'bank' => '银行卡',
            'usdt' => 'USDT',
            'alipay' => '支付宝',
            'wechat' => '微信'
        ];
        return $map[$type] ?? $type;
    }
}
