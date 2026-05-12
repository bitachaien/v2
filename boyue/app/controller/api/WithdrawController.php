<?php

namespace app\controller\api;

use support\Request;
use support\Db;
use app\service\ConfigCacheService;

class WithdrawController
{
    
    public function config(Request $request)
    {
        $userId = $request->userId ?? 0;
        
        if (!$userId) {
            return json(['code' => 401, 'message' => '请先登录', 'data' => null]);
        }
        
        try {
            
            $user = Db::table('caipiao_member')->where('id', $userId)->first();
            if (!$user) {
                return json(['code' => 400, 'message' => '用户不存在', 'data' => null]);
            }
            
            
            $minWithdraw = $this->getConfig('tikuanMin') ?: 100;
            $maxWithdraw = $this->getConfig('tikuanMax') ?: 500000;
            $withdrawFeeRate = $this->getConfig('tikuanfeerate') ?: 0; 
            $withdrawFeeFixed = $this->getConfig('tikuanfeefixed') ?: 0; 
            $dailyWithdrawTimes = $this->getConfig('tikuancishu') ?: 10;
            
            
            $todayStart = strtotime(date('Y-m-d 00:00:00'));
            $todayCount = Db::table('caipiao_withdraw')
                ->where('uid', $userId)
                ->where('oddtime', '>=', $todayStart)
                ->count();
            
            
            $balance = floatval($user->balance);
            $withdrawable = $balance; 
            $frozen = floatval($user->ngbalance ?? 0); 
            
            return json([
                'code' => 0,
                'message' => '获取成功',
                'data' => [
                    'name' => $user->userbankname ?: ($user->nickname ?: substr($user->username, 0, 1) . '**'),
                    'level' => $user->groupid ?? 1,
                    'balance' => $balance,
                    'withdrawable' => $withdrawable,
                    'frozen' => $frozen,
                    'minAmount' => floatval($minWithdraw),
                    'maxAmount' => floatval($maxWithdraw),
                    'feeRate' => floatval($withdrawFeeRate),
                    'feeFixed' => floatval($withdrawFeeFixed),
                    'dailyTimes' => intval($dailyWithdrawTimes),
                    'dailyRemainTimes' => max(0, $dailyWithdrawTimes - $todayCount),
                ]
            ]);
            
        } catch (\Exception $e) {
            \support\Log::error('获取提现配置失败: ' . $e->getMessage() . ' | Trace: ' . $e->getTraceAsString());
            return json(['code' => 500, 'message' => '获取数据失败: ' . $e->getMessage(), 'data' => null]);
        }
    }
    
    
    public function accounts(Request $request)
    {
        $userId = $request->userId ?? 0;
        
        if (!$userId) {
            return json(['code' => 401, 'message' => '请先登录', 'data' => null]);
        }
        
        try {
            
            $accounts = Db::table('caipiao_withdraw_account')
                ->where('uid', $userId)
                ->where('status', 1)
                ->orderBy('is_default', 'desc')
                ->orderBy('id', 'desc')
                ->get();
            
            $result = [];
            foreach ($accounts as $acc) {
                $cardNo = '';
                $fullAddress = '';
                $bankName = '';
                $network = '';
                
                if ($acc->type === 'bank') {
                    $bankName = $acc->bank_name ?: '银行卡';
                    $fullAddress = $acc->bank_account ?: '';
                    $cardNo = $acc->bank_account ? substr($acc->bank_account, -4) : '';
                } elseif ($acc->type === 'usdt') {
                    $network = $acc->usdt_network ?: 'TRC20';
                    $bankName = 'USDT-' . $network;
                    $fullAddress = $acc->usdt_address ?: '';
                    $cardNo = $acc->usdt_address ? substr($acc->usdt_address, -4) : '';
                } else {
                    $bankName = $acc->type === 'alipay' ? '支付宝' : '微信';
                    $fullAddress = $acc->bank_account ?: '';
                    $cardNo = $acc->bank_account ? substr($acc->bank_account, -4) : '';
                }
                
                $result[] = [
                    'id' => $acc->id,
                    'type' => $acc->type,
                    'bankName' => $bankName,
                    'accountName' => $acc->account_name,
                    'cardNoLast4' => $cardNo,
                    'fullAddress' => $fullAddress,
                    'network' => $network,
                    'isDefault' => $acc->is_default == 1,
                    'qrCode' => $acc->qr_code ?? '',
                ];
            }
            
            
            if (empty($result)) {
                $oldAccounts = Db::table('caipiao_banklist')
                    ->where('uid', $userId)
                    ->where('state', 1)
                    ->orderBy('isdefault', 'desc')
                    ->get();
                    
                foreach ($oldAccounts as $acc) {
                    $result[] = [
                        'id' => 'old_' . $acc->id,
                        'type' => 'bank',
                        'bankName' => $acc->bankname,
                        'accountName' => $acc->accountname,
                        'cardNoLast4' => $acc->banknumber ? substr($acc->banknumber, -4) : '',
                        'isDefault' => $acc->isdefault == 1,
                    ];
                }
            }
            
            return json([
                'code' => 0,
                'message' => '获取成功',
                'data' => $result
            ]);
            
        } catch (\Exception $e) {
            \support\Log::error('获取提现账户失败: ' . $e->getMessage());
            return json(['code' => 500, 'message' => '获取数据失败', 'data' => null]);
        }
    }
    
    
    public function submit(Request $request)
    {
        $userId = $request->userId ?? 0;
        
        if (!$userId) {
            return json(['code' => 401, 'message' => '请先登录', 'data' => null]);
        }
        
        try {
            $accountId = $request->post('accountId');
            $amount = floatval($request->post('amount', 0));
            $fundPassword = $request->post('fundPassword', '');
            
            if (empty($accountId)) {
                return json(['code' => 400, 'message' => '请选择提现账户', 'data' => null]);
            }
            
            if ($amount <= 0) {
                return json(['code' => 400, 'message' => '请输入正确的金额', 'data' => null]);
            }
            
            
            if ($amount != floor($amount)) {
                return json(['code' => 400, 'message' => '提现金额必须为整数', 'data' => null]);
            }
            
            
            $user = Db::table('caipiao_member')->where('id', $userId)->first();
            if (!$user) {
                return json(['code' => 400, 'message' => '用户不存在', 'data' => null]);
            }
            
            
            if (empty($user->tradepassword)) {
                
                return json(['code' => 400, 'message' => '请先设置资金密码', 'data' => ['needSetFundPwd' => true]]);
            }
            if (empty($fundPassword)) {
                return json(['code' => 400, 'message' => '请输入资金密码', 'data' => null]);
            }
            if (md5($fundPassword) !== $user->tradepassword) {
                return json(['code' => 400, 'message' => '资金密码错误', 'data' => null]);
            }
            
            
            $minWithdraw = $this->getConfig('tikuanMin') ?: 100;
            $maxWithdraw = $this->getConfig('tikuanMax') ?: 500000;
            
            if ($amount < $minWithdraw) {
                return json(['code' => 400, 'message' => "最低提现金额为 {$minWithdraw} 元", 'data' => null]);
            }
            if ($amount > $maxWithdraw) {
                return json(['code' => 400, 'message' => "最高提现金额为 {$maxWithdraw} 元", 'data' => null]);
            }
            
            
            $balance = floatval($user->balance);
            if ($amount > $balance) {
                return json(['code' => 400, 'message' => '余额不足', 'data' => null]);
            }
            
            
            $dailyWithdrawTimes = $this->getConfig('tikuancishu') ?: 10;
            $todayStart = strtotime(date('Y-m-d 00:00:00'));
            $todayCount = Db::table('caipiao_withdraw')
                ->where('uid', $userId)
                ->where('oddtime', '>=', $todayStart)
                ->count();
            
            if ($todayCount >= $dailyWithdrawTimes) {
                return json(['code' => 400, 'message' => '今日提现次数已达上限', 'data' => null]);
            }
            
            
            $accountInfo = $this->getAccountInfo($accountId, $userId);
            if (!$accountInfo) {
                return json(['code' => 400, 'message' => '提现账户不存在', 'data' => null]);
            }
            
            
            $feeRate = floatval($this->getConfig('tikuanfeerate') ?: 0);
            $feeFixed = floatval($this->getConfig('tikuanfeefixed') ?: 0);
            $fee = $amount * $feeRate / 100 + $feeFixed;
            $actualAmount = $amount - $fee;
            
            
            $trano = date('YmdHis') . str_pad($userId, 6, '0', STR_PAD_LEFT) . mt_rand(1000, 9999);
            
            
            Db::beginTransaction();
            
            try {
                
                $lockedUser = Db::table('caipiao_member')
                    ->where('id', $userId)
                    ->lockForUpdate()
                    ->first();
                
                
                $actualBalance = floatval($lockedUser->balance);
                if ($actualBalance < $amount) {
                    Db::rollBack();
                    return json(['code' => 400, 'message' => '余额不足（并发检查）', 'data' => null]);
                }
                
                
                $newBalance = $actualBalance - $amount;
                Db::table('caipiao_member')
                    ->where('id', $userId)
                    ->update(['balance' => $newBalance]);
                
                
                Db::table('caipiao_withdraw')->insert([
                    'uid' => $userId,
                    'username' => $user->username,
                    'trano' => $trano,
                    'amount' => $amount,
                    'actualamount' => $actualAmount,
                    'oldaccountmoney' => $actualBalance,
                    'newaccountmoney' => $newBalance,
                    'fee' => $fee,
                    'accountname' => $accountInfo['accountName'],
                    'bankname' => $accountInfo['bankName'],
                    'bankbranch' => $accountInfo['bankBranch'] ?? '',
                    'paytype' => $accountInfo['type'],
                    'paytypename' => $accountInfo['typeName'],
                    'banknumber' => $accountInfo['bankAccount'],
                    'qr_code' => $accountInfo['qrCode'] ?? '',
                    'remark' => '',
                    'oddtime' => time(),
                    'state' => 0, 
                    'stateadmin' => '',
                    'updatetime' => time(),
                ]);
                
                
                Db::table('caipiao_fuddetail')->insert([
                    'trano' => $trano,
                    'uid' => $userId,
                    'username' => $user->username,
                    'type' => 'withdraw',
                    'typename' => '提现',
                    'amount' => -$amount,
                    'amountbefor' => $actualBalance,
                    'amountafter' => $newBalance,
                    'oddtime' => time(),
                    'remark' => '提现申请',
                ]);
                
                Db::commit();
                
                return json([
                    'code' => 0,
                    'message' => '提现申请已提交',
                    'data' => [
                        'trano' => $trano,
                        'amount' => $amount,
                        'fee' => $fee,
                        'actualAmount' => $actualAmount,
                    ]
                ]);
                
            } catch (\Exception $e) {
                Db::rollBack();
                throw $e;
            }
            
        } catch (\Exception $e) {
            \support\Log::error('提现失败: ' . $e->getMessage());
            return json(['code' => 500, 'message' => '提现失败: ' . $e->getMessage(), 'data' => null]);
        }
    }
    
    
    public function records(Request $request)
    {
        $userId = $request->userId ?? 0;
        
        if (!$userId) {
            return json(['code' => 401, 'message' => '请先登录', 'data' => null]);
        }
        
        try {
            $page = intval($request->get('page', 1));
            $pageSize = intval($request->get('pageSize', $request->get('limit', 20)));
            $state = $request->get('state', '');
            $startDate = $request->get('startDate', '');
            $endDate = $request->get('endDate', '');
            
            $query = Db::table('caipiao_withdraw')
                ->where('uid', $userId);
            
            
            if ($state !== '' && $state !== null) {
                $query->where('state', $state);
            }
            
            
            if ($startDate) {
                $startTime = strtotime($startDate . ' 00:00:00');
                $query->where('oddtime', '>=', $startTime);
            }
            if ($endDate) {
                $endTime = strtotime($endDate . ' 23:59:59');
                $query->where('oddtime', '<=', $endTime);
            }
            
            $total = $query->count();
            
            
            $totalAmount = (clone $query)->sum('amount') ?: 0;
            
            $list = $query->orderBy('id', 'desc')
                ->offset(($page - 1) * $pageSize)
                ->limit($pageSize)
                ->get();
            
            $result = [];
            
            $stateMap = [0 => '出款中', 1 => '提现成功', 2 => '提现拒绝', 3 => '提现取消'];
            
            foreach ($list as $item) {
                $result[] = [
                    'id' => $item->id,
                    'trano' => $item->trano,
                    'orderNo' => $item->trano,
                    'amount' => floatval($item->amount),
                    'actualAmount' => floatval($item->actualamount),
                    'fee' => floatval($item->fee),
                    'type' => $item->paytype ?: 'bank',
                    'bankName' => $item->bankname,
                    'bankNumber' => $item->banknumber ? '****' . substr($item->banknumber, -4) : '',
                    'address' => $item->banknumber ?: '',
                    'state' => intval($item->state),
                    'stateText' => $stateMap[$item->state] ?? '未知',
                    'createTime' => date('Y-m-d H:i', $item->oddtime),
                    'updateTime' => $item->updatetime ? date('Y-m-d H:i', $item->updatetime) : null,
                    'remark' => $item->remark,
                ];
            }
            
            return json([
                'code' => 0,
                'message' => '获取成功',
                'data' => [
                    'list' => $result,
                    'total' => $total,
                    'totalAmount' => floatval($totalAmount),
                    'page' => $page,
                    'pageSize' => $pageSize,
                ]
            ]);
            
        } catch (\Exception $e) {
            \support\Log::error('获取提现记录失败: ' . $e->getMessage());
            return json(['code' => 500, 'message' => '获取数据失败', 'data' => null]);
        }
    }
    
    
    public function cancel(Request $request)
    {
        $userId = $request->userId ?? 0;
        
        if (!$userId) {
            return json(['code' => 401, 'message' => '请先登录', 'data' => null]);
        }
        
        try {
            $id = $request->post('id');
            
            if (!$id) {
                return json(['code' => 400, 'message' => '参数错误', 'data' => null]);
            }
            
            
            $withdraw = Db::table('caipiao_withdraw')
                ->where('id', $id)
                ->where('uid', $userId)
                ->first();
            
            if (!$withdraw) {
                return json(['code' => 400, 'message' => '提现记录不存在', 'data' => null]);
            }
            
            
            if ($withdraw->state != 0) {
                return json(['code' => 400, 'message' => '只能取消出款中的订单', 'data' => null]);
            }
            
            
            Db::beginTransaction();
            
            try {
                
                $user = Db::table('caipiao_member')
                    ->where('id', $userId)
                    ->lockForUpdate()
                    ->first();
                $oldBalance = floatval($user->balance);
                $newBalance = $oldBalance + floatval($withdraw->amount);
                
                
                Db::table('caipiao_member')
                    ->where('id', $userId)
                    ->update(['balance' => $newBalance]);
                
                
                Db::table('caipiao_withdraw')
                    ->where('id', $id)
                    ->update([
                        'state' => 3, 
                        'remark' => '用户自己取消',
                        'updatetime' => time()
                    ]);
                
                
                $trano = 'TX' . date('YmdHis') . rand(1000, 9999);
                Db::table('caipiao_fuddetail')->insert([
                    'trano' => $trano,
                    'uid' => $userId,
                    'username' => $user->username,
                    'type' => 'withdraw_cancel',
                    'typename' => '提现取消',
                    'amount' => $withdraw->amount,
                    'amountbefor' => $oldBalance,
                    'amountafter' => $newBalance,
                    'oddtime' => time(),
                    'remark' => '用户取消提现，原单号：' . $withdraw->trano
                ]);
                
                Db::commit();
                
                return json(['code' => 0, 'message' => '取消成功', 'data' => null]);
                
            } catch (\Exception $e) {
                Db::rollBack();
                throw $e;
            }
            
        } catch (\Exception $e) {
            \support\Log::error('取消提现失败: ' . $e->getMessage());
            return json(['code' => 500, 'message' => '取消失败: ' . $e->getMessage(), 'data' => null]);
        }
    }
    
    
    private function getAccountInfo($accountId, $userId)
    {
        
        if (is_numeric($accountId)) {
            $acc = Db::table('caipiao_withdraw_account')
                ->where('id', $accountId)
                ->where('uid', $userId)
                ->first();
            
            if ($acc) {
                $typeName = [
                    'bank' => '银行卡',
                    'alipay' => '支付宝',
                    'wechat' => '微信',
                    'usdt' => 'USDT',
                ][$acc->type] ?? '其他';
                
                return [
                    'type' => $acc->type,
                    'typeName' => $typeName,
                    'bankName' => $acc->bank_name ?: $typeName,
                    'bankAccount' => $acc->type === 'usdt' ? $acc->usdt_address : $acc->bank_account,
                    'accountName' => $acc->account_name,
                    'bankBranch' => $acc->bank_branch ?? '',
                    'qrCode' => $acc->qr_code ?? '',
                ];
            }
        }
        
        
        if (strpos($accountId, 'old_') === 0) {
            $oldId = intval(substr($accountId, 4));
            $acc = Db::table('caipiao_banklist')
                ->where('id', $oldId)
                ->where('uid', $userId)
                ->first();
            
            if ($acc) {
                return [
                    'type' => 'bank',
                    'typeName' => '银行卡',
                    'bankName' => $acc->bankname,
                    'bankAccount' => $acc->banknumber,
                    'accountName' => $acc->accountname,
                    'bankBranch' => $acc->bankbranch ?? '',
                ];
            }
        }
        
        return null;
    }
    
    
    public function addAccount(Request $request)
    {
        $userId = $request->userId ?? 0;
        
        if (!$userId) {
            return json(['code' => 401, 'message' => '请先登录', 'data' => null]);
        }
        
        try {
            $type = $request->post('type', 'bank'); 
            $fundPassword = $request->post('fundPassword', '');
            
            
            $user = Db::table('caipiao_member')->where('id', $userId)->first();
            if (!$user) {
                return json(['code' => 400, 'message' => '用户不存在', 'data' => null]);
            }
            
            
            if (empty($user->tradepassword)) {
                
                return json(['code' => 400, 'message' => '请先设置资金密码', 'data' => ['needSetFundPwd' => true]]);
            }
            if (empty($fundPassword)) {
                return json(['code' => 400, 'message' => '请输入资金密码', 'data' => null]);
            }
            if (md5($fundPassword) !== $user->tradepassword) {
                return json(['code' => 400, 'message' => '资金密码错误', 'data' => null]);
            }
            
            $data = [
                'uid' => $userId,
                'type' => $type,
                'status' => 1,
                'is_default' => $request->post('isDefault') ? 1 : 0,
                'created_at' => date('Y-m-d H:i:s'),
            ];
            
            if ($type === 'bank') {
                $bankName = $request->post('bankName', '');
                $bankAccount = $request->post('bankAccount', '');
                $accountName = $request->post('accountName', '') ?: $user->userbankname;
                $bankBranch = $request->post('bankBranch', '');
                
                if (empty($bankName)) {
                    return json(['code' => 400, 'message' => '请选择开户银行', 'data' => null]);
                }
                if (empty($bankAccount)) {
                    return json(['code' => 400, 'message' => '请输入银行卡号', 'data' => null]);
                }
                
                
                $exists = Db::table('caipiao_withdraw_account')
                    ->where('uid', $userId)
                    ->where('type', 'bank')
                    ->where('bank_account', $bankAccount)
                    ->exists();
                if ($exists) {
                    return json(['code' => 400, 'message' => '该银行卡已经添加过', 'data' => null]);
                }
                
                $data['bank_name'] = $bankName;
                $data['bank_account'] = $bankAccount;
                $data['account_name'] = $accountName;
                $data['bank_branch'] = $bankBranch;
                
            } elseif ($type === 'usdt') {
                $network = $request->post('network', 'TRC20');
                $address = $request->post('address', '');
                
                if (empty($address)) {
                    return json(['code' => 400, 'message' => '请输入USDT地址', 'data' => null]);
                }
                
                
                $exists = Db::table('caipiao_withdraw_account')
                    ->where('uid', $userId)
                    ->where('type', 'usdt')
                    ->where('usdt_address', $address)
                    ->exists();
                if ($exists) {
                    return json(['code' => 400, 'message' => '该地址已经添加过', 'data' => null]);
                }
                
                $data['usdt_network'] = $network;
                $data['usdt_address'] = $address;
                $data['bank_name'] = 'USDT-' . $network;
                $data['account_name'] = $request->post('remark', 'USDT提现');
                
            } elseif ($type === 'alipay' || $type === 'wechat') {
                $account = $request->post('account', '');
                $accountName = $request->post('accountName', '') ?: $user->userbankname;
                $qrCode = $request->post('qrCode', '');
                
                
                if ($type === 'alipay' && empty($account)) {
                    return json(['code' => 400, 'message' => '请输入支付宝账号', 'data' => null]);
                }
                if (empty($qrCode)) {
                    return json(['code' => 400, 'message' => '请上传收款码', 'data' => null]);
                }
                
                $data['bank_account'] = $account ?: ($type === 'wechat' ? 'wechat_qr' : 'alipay_qr');
                $data['account_name'] = $accountName;
                $data['bank_name'] = $type === 'alipay' ? '支付宝' : '微信';
                $data['qr_code'] = $qrCode;
                
            } else {
                return json(['code' => 400, 'message' => '不支持的账户类型', 'data' => null]);
            }
            
            
            if ($data['is_default']) {
                Db::table('caipiao_withdraw_account')
                    ->where('uid', $userId)
                    ->update(['is_default' => 0]);
            }
            
            $id = Db::table('caipiao_withdraw_account')->insertGetId($data);
            
            return json([
                'code' => 0,
                'message' => '添加成功',
                'data' => ['id' => $id]
            ]);
            
        } catch (\Exception $e) {
            \support\Log::error('添加提现账户失败: ' . $e->getMessage());
            return json(['code' => 500, 'message' => '添加失败: ' . $e->getMessage(), 'data' => null]);
        }
    }
    
    
    public function deleteAccount(Request $request)
    {
        $userId = $request->userId ?? 0;
        
        if (!$userId) {
            return json(['code' => 401, 'message' => '请先登录', 'data' => null]);
        }
        
        try {
            $id = $request->post('id');
            
            if (!$id) {
                return json(['code' => 400, 'message' => '参数错误', 'data' => null]);
            }
            
            $result = Db::table('caipiao_withdraw_account')
                ->where('id', $id)
                ->where('uid', $userId)
                ->delete();
            
            if ($result) {
                return json(['code' => 0, 'message' => '删除成功', 'data' => null]);
            } else {
                return json(['code' => 400, 'message' => '删除失败', 'data' => null]);
            }
            
        } catch (\Exception $e) {
            return json(['code' => 500, 'message' => '删除失败', 'data' => null]);
        }
    }
    
    
    public function setDefaultAccount(Request $request)
    {
        $userId = $request->userId ?? 0;
        
        if (!$userId) {
            return json(['code' => 401, 'message' => '请先登录', 'data' => null]);
        }
        
        try {
            $id = $request->post('id');
            
            if (!$id) {
                return json(['code' => 400, 'message' => '参数错误', 'data' => null]);
            }
            
            
            Db::table('caipiao_withdraw_account')
                ->where('uid', $userId)
                ->update(['is_default' => 0]);
            
            
            $result = Db::table('caipiao_withdraw_account')
                ->where('id', $id)
                ->where('uid', $userId)
                ->update(['is_default' => 1]);
            
            if ($result) {
                return json(['code' => 0, 'message' => '设置成功', 'data' => null]);
            } else {
                return json(['code' => 400, 'message' => '设置失败', 'data' => null]);
            }
            
        } catch (\Exception $e) {
            return json(['code' => 500, 'message' => '设置失败', 'data' => null]);
        }
    }
    
    
    public function uploadQrCode(Request $request)
    {
        $userId = $request->userId ?? 0;
        
        if (!$userId) {
            return json(['code' => 401, 'message' => '请先登录', 'data' => null]);
        }
        
        try {
            $file = $request->file('file');
            if (!$file || !$file->isValid()) {
                return json(['code' => 400, 'message' => '请选择要上传的图片', 'data' => null]);
            }
            
            
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $uploadMimeType = $file->getUploadMimeType();
            if (!in_array($uploadMimeType, $allowedTypes)) {
                return json(['code' => 400, 'message' => '只支持 JPG、PNG、GIF、WEBP 格式图片', 'data' => null]);
            }
            
            
            if ($file->getSize() > 5 * 1024 * 1024) {
                return json(['code' => 400, 'message' => '图片大小不能超过5MB', 'data' => null]);
            }
            
            
            $ext = $file->getUploadExtension() ?: 'jpg';
            $filename = 'qrcode/' . date('Ymd') . '/' . uniqid() . '_' . $userId . '.' . $ext;
            
            
            $dir = public_path() . '/uploads/qrcode/' . date('Ymd');
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            
            
            $savePath = public_path() . '/uploads/' . $filename;
            $file->move($savePath);
            
            
            $host = $request->host();
            $scheme = $request->header('X-Forwarded-Proto') ?: ($request->header('scheme') ?: 'http');
            $url = $scheme . '://' . $host . '/uploads/' . $filename;
            
            return json([
                'code' => 0,
                'message' => '上传成功',
                'data' => [
                    'url' => $url
                ]
            ]);
            
        } catch (\Exception $e) {
            \support\Log::error('上传收款码失败: ' . $e->getMessage());
            return json(['code' => 500, 'message' => '上传失败: ' . $e->getMessage(), 'data' => null]);
        }
    }
    
    
    private function getConfig($name)
    {
        return ConfigCacheService::get($name);
    }
}
