<?php

namespace plugin\admin\app\controller;

use support\Request;
use support\Db;
use support\Log;

/**
 * 财务管理 API 控制器
 * Art Design Pro 前端专用
 */
class FinanceApiController extends Base
{
    /**
     * 不需要鉴权的方法
     * @var string[]
     */
    protected $noNeedAuth = ['*'];

    /**
     * ============================================
     * 提款银行管理
     * ============================================
     */

    /**
     * 获取银行列表
     * GET /app/admin/finance/sysbank-list
     */
    public function sysbankList(Request $request)
    {
        $page = (int)$request->get('page', 1);
        $limit = (int)$request->get('limit', 20);
        $bankname = $request->get('bankname', '');

        $query = Db::table('caipiao_sysbank');

        // 银行名称搜索
        if (!empty($bankname)) {
            $query->where('bankname', 'like', "%{$bankname}%");
        }

        $total = $query->count();

        $list = $query->orderBy('listorder', 'asc')
            ->orderBy('id', 'asc')
            ->offset(($page - 1) * $limit)
            ->limit($limit)
            ->get();

        $result = [];
        foreach ($list as $item) {
            $item = (array)$item;
            $result[] = [
                'id' => $item['id'],
                'bankcode' => $item['bankcode'],
                'bankname' => $item['bankname'],
                'banklogo' => $item['banklogo'] ?? '',
                'imgbg' => $item['imgbg'] ?? '',
                'state' => (int)$item['state'],
                'listorder' => (int)$item['listorder'],
            ];
        }

        return $this->json(0, 'success', $result, $total);
    }

    /**
     * 新增银行
     * POST /app/admin/finance/sysbank-save
     */
    public function sysbankSave(Request $request)
    {
        $bankname = $request->post('bankname', '');
        $bankcode = $request->post('bankcode', '');
        $banklogo = $request->post('banklogo', '');
        $imgbg = $request->post('imgbg', '');
        $state = (int)$request->post('state', 1);
        $listorder = (int)$request->post('listorder', 0);

        if (empty($bankname)) {
            return $this->json(1, '银行名称不能为空');
        }

        if (empty($bankcode)) {
            return $this->json(1, '银行代码不能为空');
        }

        // 检查银行代码是否已存在
        $exists = Db::table('caipiao_sysbank')
            ->where('bankcode', $bankcode)
            ->first();

        if ($exists) {
            return $this->json(1, '银行代码已存在');
        }

        try {
            $id = Db::table('caipiao_sysbank')->insertGetId([
                'bankname' => $bankname,
                'bankcode' => $bankcode,
                'banklogo' => $banklogo,
                'imgbg' => $imgbg,
                'state' => $state,
                'listorder' => $listorder,
            ]);

            return $this->json(0, '添加成功', ['id' => $id]);

        } catch (\Exception $e) {
            Log::error("新增银行失败: " . $e->getMessage());
            return $this->json(1, '添加失败: ' . $e->getMessage());
        }
    }

    /**
     * 修改银行信息
     * POST /app/admin/finance/sysbank-update
     */
    public function sysbankUpdate(Request $request)
    {
        $id = $request->post('id');

        if (empty($id)) {
            return $this->json(1, '银行ID不能为空');
        }

        $bank = Db::table('caipiao_sysbank')->where('id', $id)->first();
        if (!$bank) {
            return $this->json(1, '银行不存在');
        }

        $allowFields = ['bankname', 'bankcode', 'banklogo', 'imgbg', 'state', 'listorder'];
        $updateData = [];

        foreach ($allowFields as $field) {
            $value = $request->post($field);
            if ($value !== null) {
                $updateData[$field] = $value;
            }
        }

        if (empty($updateData)) {
            return $this->json(1, '没有需要更新的数据');
        }

        // 检查银行代码是否与其他记录重复
        if (isset($updateData['bankcode'])) {
            $exists = Db::table('caipiao_sysbank')
                ->where('bankcode', $updateData['bankcode'])
                ->where('id', '!=', $id)
                ->first();

            if ($exists) {
                return $this->json(1, '银行代码已被其他银行使用');
            }
        }

        try {
            Db::table('caipiao_sysbank')
                ->where('id', $id)
                ->update($updateData);

            return $this->json(0, '更新成功');

        } catch (\Exception $e) {
            Log::error("更新银行失败: " . $e->getMessage());
            return $this->json(1, '更新失败: ' . $e->getMessage());
        }
    }

    /**
     * 删除银行
     * POST /app/admin/finance/sysbank-delete
     */
    public function sysbankDelete(Request $request)
    {
        $id = $request->post('id');

        if (empty($id)) {
            return $this->json(1, '银行ID不能为空');
        }

        $bank = Db::table('caipiao_sysbank')->where('id', $id)->first();
        if (!$bank) {
            return $this->json(1, '银行不存在');
        }

        try {
            Db::table('caipiao_sysbank')->where('id', $id)->delete();

            return $this->json(0, '删除成功');

        } catch (\Exception $e) {
            Log::error("删除银行失败: " . $e->getMessage());
            return $this->json(1, '删除失败: ' . $e->getMessage());
        }
    }

    /**
     * 修改银行状态
     * POST /app/admin/finance/sysbank-setstate
     */
    public function sysbankSetState(Request $request)
    {
        $id = $request->post('id');
        $state = $request->post('state');

        if (empty($id)) {
            return $this->json(1, '银行ID不能为空');
        }

        if ($state === null || !in_array((int)$state, [0, 1])) {
            return $this->json(1, '状态值无效');
        }

        $bank = Db::table('caipiao_sysbank')->where('id', $id)->first();
        if (!$bank) {
            return $this->json(1, '银行不存在');
        }

        try {
            Db::table('caipiao_sysbank')
                ->where('id', $id)
                ->update(['state' => (int)$state]);

            $stateText = (int)$state === 1 ? '启用' : '禁用';
            return $this->json(0, "{$stateText}成功");

        } catch (\Exception $e) {
            Log::error("修改银行状态失败: " . $e->getMessage());
            return $this->json(1, '操作失败: ' . $e->getMessage());
        }
    }

    /**
     * ============================================
     * 会员银行卡管理
     * ============================================
     */

    /**
     * 获取会员银行卡列表
     * GET /app/admin/member/bank-list
     */
    public function memberBankList(Request $request)
    {
        try {
            $username = $request->get('username', '');
            $accountname = $request->get('accountname', '');
            $state = $request->get('state', '');
            $page = (int)$request->get('page', 1);
            $limit = (int)$request->get('limit', 20);
            
            $query = Db::table('caipiao_banklist');
        
            if ($username) {
                $query->where('username', 'like', "%{$username}%");
            }
            
            if ($accountname) {
                $query->where('accountname', 'like', "%{$accountname}%");
            }
            
            if ($state !== '') {
                $query->where('state', $state);
            }
            
            // 获取总数
            $total = $query->count();
            
            // 分页
            $list = $query->orderBy('id', 'desc')
                         ->offset(($page - 1) * $limit)
                         ->limit($limit)
                         ->get();
            
            // 处理数据
            $result = [];
            $stateText = ['审核中', '已审', '驳回'];
            foreach ($list as $item) {
                $row = (array)$item;
                $row['state_text'] = $stateText[$row['state']] ?? '未知';
                $result[] = $row;
            }
            
            return $this->json(0, 'success', $result, $total);
        } catch (\Exception $e) {
            Log::error('memberBankList 错误: ' . $e->getMessage());
            return $this->json(1, '获取列表失败: ' . $e->getMessage());
        }
    }

    /**
     * 编辑会员银行卡
     * POST /app/admin/member/bank-edit
     */
    public function memberBankEdit(Request $request)
    {
        $id = $request->post('id');
        
        if (!$id) {
            return $this->json(1, '参数错误');
        }
        
        $info = Db::table('caipiao_banklist')->where('id', $id)->first();
        
        if (!$info) {
            return $this->json(1, '银行信息不存在');
        }
        
        $info = (array)$info;
        
        $data = [];
        $allowFields = ['bankname', 'bankbranch', 'banknumber', 'accountname', 'isdefault', 'state'];
        
        foreach ($allowFields as $field) {
            $value = $request->post($field);
            if ($value !== null) {
                $data[$field] = $value;
            }
        }
        
        // 处理银行地址
        $sheng = $request->post('sheng');
        $city = $request->post('city');
        if ($sheng && $city) {
            $data['bankaddress'] = $sheng . '-' . $city;
        }
        
        if (empty($data)) {
            return $this->json(1, '没有需要更新的数据');
        }
        
        try {
            // 如果是首次审核通过，且配置了绑卡奖励
            if (isset($data['state']) && $data['state'] == 1) {
                $bindcardamount = abs(floatval($this->getConfig('bindcardamount')));
                if ($bindcardamount > 0) {
                    $cardcount = Db::table('caipiao_banklist')
                        ->where('uid', $info['uid'])
                        ->where('state', 1)
                        ->count();
                        
                    // 首次绑卡奖励
                    if (!$cardcount) {
                        $balance = $bindcardamount;
                        $member = Db::table('caipiao_member')->where('id', $info['uid'])->first();
                        $amountbefor = $member->balance;
                        
                        // 增加余额
                        Db::table('caipiao_member')
                            ->where('id', $info['uid'])
                            ->increment('balance', $balance);
                        
                        // 记录账变
                        $fuddetaildata = [
                            'trano' => date('YmdHis') . '4' . rand(1000, 9999),
                            'uid' => $info['uid'],
                            'username' => $info['username'],
                            'type' => 'activity_bindcard',
                            'typename' => '绑定银行赠送活动',
                            'amount' => abs($balance),
                            'amountbefor' => $amountbefor,
                            'amountafter' => $amountbefor + abs($balance),
                            'remark' => '绑定银行赠送',
                            'oddtime' => time()
                        ];
                        Db::table('caipiao_fuddetail')->insert($fuddetaildata);
                    }
                }
            }
            
            $result = Db::table('caipiao_banklist')
                ->where('id', $id)
                ->update($data);
            
            return $this->json(0, '修改成功');
        } catch (\Exception $e) {
            Log::error('memberBankEdit 错误: ' . $e->getMessage());
            return $this->json(1, '修改失败: ' . $e->getMessage());
        }
    }

    /**
     * 删除会员银行卡
     * POST /app/admin/member/bank-delete
     */
    public function memberBankDelete(Request $request)
    {
        $id = $request->post('id');
        
        if (!$id) {
            return $this->json(1, '参数错误');
        }
        
        try {
            $result = Db::table('caipiao_banklist')->where('id', $id)->delete();
            
            if ($result) {
                return $this->json(0, '删除成功');
            } else {
                return $this->json(1, '删除失败');
            }
        } catch (\Exception $e) {
            Log::error('memberBankDelete 错误: ' . $e->getMessage());
            return $this->json(1, '删除失败: ' . $e->getMessage());
        }
    }

    /**
     * 获取配置值
     */
    private function getConfig($name)
    {
        $config = Db::table('caipiao_setting')->where('name', $name)->value('value');
        return $config ?? '';
    }

    /**
     * ============================================
     * 提现账户管理
     * ============================================
     */

    /**
     * 获取提现账户列表
     * GET /app/admin/withdraw-account/select
     */
    public function withdrawAccountList(Request $request)
    {
        try {
            $uid = $request->get('uid');
            $username = $request->get('username', '');
            $type = $request->get('type', '');
            $status = $request->get('status', '');
            $page = (int)$request->get('page', 1);
            $limit = (int)$request->get('limit', 20);
            
            $query = Db::table('caipiao_withdraw_account as wa')
                ->leftJoin('caipiao_member as m', 'wa.uid', '=', 'm.id');
            
            // 用户ID筛选
            if ($uid !== null && $uid !== '') {
                $query->where('wa.uid', (int)$uid);
            }
            
            if ($username) {
                $query->where('m.username', 'like', "%{$username}%");
            }
            
            if ($type) {
                $query->where('wa.type', $type);
            }
            
            // 状态筛选（默认只显示启用的）
            if ($status !== '') {
                $query->where('wa.status', $status);
            } else {
                $query->where('wa.status', 1);
            }
            
            // 获取总数
            $total = $query->count();
            
            // 分页
            $list = $query->select('wa.*', 'm.username')
                         ->orderBy('wa.id', 'desc')
                         ->offset(($page - 1) * $limit)
                         ->limit($limit)
                         ->get();
            
            // 处理数据
            $result = [];
            foreach ($list as $item) {
                $row = (array)$item;
                // 构建账户信息显示
                $accountInfo = '';
                if ($row['type'] === 'bank') {
                    $accountInfo = ($row['bank_name'] ?? '') . ' ' . ($row['bank_account'] ?? '');
                } elseif ($row['type'] === 'usdt') {
                    $accountInfo = ($row['usdt_network'] ?? 'TRC20') . ': ' . ($row['usdt_address'] ?? '');
                } else {
                    $accountInfo = $row['bank_account'] ?? '';
                }
                $row['account_info'] = $accountInfo;
                $row['created_at_text'] = isset($row['created_at']) ? date('Y-m-d H:i:s', strtotime($row['created_at'])) : '-';
                $result[] = $row;
            }
            
            return $this->json(0, 'success', $result, $total);
        } catch (\Exception $e) {
            Log::error('withdrawAccountList 错误: ' . $e->getMessage());
            return $this->json(1, '获取列表失败: ' . $e->getMessage());
        }
    }

    /**
     * 添加提现账户
     * POST /app/admin/withdraw-account/insert
     */
    public function withdrawAccountInsert(Request $request)
    {
        $username = $request->post('username', '');
        $type = $request->post('type', 'bank');
        
        if (empty($username)) {
            return $this->json(1, '用户名不能为空');
        }
        
        // 查找用户
        $member = Db::table('caipiao_member')->where('username', $username)->first();
        if (!$member) {
            return $this->json(1, '用户不存在');
        }
        
        try {
            $data = [
                'uid' => $member->id,
                'type' => $type,
                'status' => 1,
                'is_default' => 0,
                'created_at' => date('Y-m-d H:i:s'),
            ];
            
            if ($type === 'bank') {
                $data['bank_name'] = $request->post('bank_name', '');
                $data['bank_account'] = $request->post('bank_account', '');
                $data['account_name'] = $request->post('account_name', '');
            } elseif ($type === 'usdt') {
                $data['usdt_address'] = $request->post('usdt_address', '');
                $data['usdt_network'] = $request->post('usdt_network', 'TRC20');
            } else {
                // 支付宝/微信
                $data['bank_account'] = $request->post('bank_account', '');
                $data['account_name'] = $request->post('account_name', '');
                $data['qr_code'] = $request->post('qr_code', '');
            }
            
            $id = Db::table('caipiao_withdraw_account')->insertGetId($data);
            
            return $this->json(0, '添加成功', ['id' => $id]);
        } catch (\Exception $e) {
            Log::error('withdrawAccountInsert 错误: ' . $e->getMessage());
            return $this->json(1, '添加失败: ' . $e->getMessage());
        }
    }

    /**
     * 修改提现账户
     * POST /app/admin/withdraw-account/update
     */
    public function withdrawAccountUpdate(Request $request)
    {
        $id = $request->post('id');
        
        if (!$id) {
            return $this->json(1, '参数错误');
        }
        
        $account = Db::table('caipiao_withdraw_account')->where('id', $id)->first();
        if (!$account) {
            return $this->json(1, '账户不存在');
        }
        
        try {
            $data = [];
            $type = $account->type;
            
            if ($type === 'bank') {
                $allowFields = ['bank_name', 'bank_account', 'account_name', 'status'];
            } elseif ($type === 'usdt') {
                $allowFields = ['usdt_address', 'usdt_network', 'status'];
            } else {
                // 支付宝/微信 - 包含收款码
                $allowFields = ['bank_account', 'account_name', 'qr_code', 'status'];
            }
            
            foreach ($allowFields as $field) {
                $value = $request->post($field);
                if ($value !== null) {
                    $data[$field] = $value;
                }
            }
            
            if (empty($data)) {
                return $this->json(1, '没有需要更新的数据');
            }
            
            $data['updated_at'] = date('Y-m-d H:i:s');
            
            Db::table('caipiao_withdraw_account')
                ->where('id', $id)
                ->update($data);
            
            return $this->json(0, '修改成功');
        } catch (\Exception $e) {
            Log::error('withdrawAccountUpdate 错误: ' . $e->getMessage());
            return $this->json(1, '修改失败: ' . $e->getMessage());
        }
    }

    /**
     * 删除提现账户
     * POST /app/admin/withdraw-account/delete
     */
    public function withdrawAccountDelete(Request $request)
    {
        $ids = $request->post('ids', []);
        
        if (empty($ids)) {
            return $this->json(1, '参数错误');
        }
        
        if (!is_array($ids)) {
            $ids = [$ids];
        }
        
        try {
            $result = Db::table('caipiao_withdraw_account')->whereIn('id', $ids)->delete();
            
            if ($result) {
                return $this->json(0, '删除成功');
            } else {
                return $this->json(1, '删除失败');
            }
        } catch (\Exception $e) {
            Log::error('withdrawAccountDelete 错误: ' . $e->getMessage());
            return $this->json(1, '删除失败: ' . $e->getMessage());
        }
    }

    /**
     * 设置默认提现账户
     * POST /app/admin/withdraw-account/setDefault
     */
    public function withdrawAccountSetDefault(Request $request)
    {
        $id = $request->post('id');
        
        if (!$id) {
            return $this->json(1, '参数错误');
        }
        
        $account = Db::table('caipiao_withdraw_account')->where('id', $id)->first();
        if (!$account) {
            return $this->json(1, '账户不存在');
        }
        
        try {
            // 取消该用户其他默认账户
            Db::table('caipiao_withdraw_account')
                ->where('uid', $account->uid)
                ->update(['is_default' => 0]);
            
            // 设置当前账户为默认
            Db::table('caipiao_withdraw_account')
                ->where('id', $id)
                ->update(['is_default' => 1]);
            
            return $this->json(0, '设置成功');
        } catch (\Exception $e) {
            Log::error('withdrawAccountSetDefault 错误: ' . $e->getMessage());
            return $this->json(1, '设置失败: ' . $e->getMessage());
        }
    }

    /**
     * ============================================
     * 通用文件上传
     * ============================================
     */

    /**
     * 通用文件上传
     * POST /app/admin/api/common/upload
     */
    public function upload(Request $request)
    {
        $file = $request->file('file');

        if (!$file || !$file->isValid()) {
            return $this->json(1, '请选择要上传的文件');
        }

        // 允许的文件类型
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $mimeType = $file->getUploadMimeType();

        if (!in_array($mimeType, $allowedTypes)) {
            return $this->json(1, '只允许上传图片文件 (jpg, png, gif, webp)');
        }

        // 文件大小限制 (5MB)
        $maxSize = 5 * 1024 * 1024;
        if ($file->getSize() > $maxSize) {
            return $this->json(1, '文件大小不能超过 5MB');
        }

        try {
            // 生成存储路径
            $dateDir = date('Y/m');
            $uploadDir = public_path() . '/uploads/' . $dateDir;

            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            // 生成文件名
            $ext = $file->getUploadExtension();
            $fileName = md5(uniqid(mt_rand(), true)) . '.' . $ext;
            $filePath = $uploadDir . '/' . $fileName;

            // 移动文件
            $file->move($filePath);

            // 生成访问 URL
            $url = '/uploads/' . $dateDir . '/' . $fileName;

            return $this->json(0, '上传成功', [
                'url' => $url,
            ]);

        } catch (\Exception $e) {
            Log::error("文件上传失败: " . $e->getMessage());
            return $this->json(1, '上传失败: ' . $e->getMessage());
        }
    }
}
