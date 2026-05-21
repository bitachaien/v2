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
            return json(['code' => 401, 'message' => 'Vui lòng đăng nhập', 'data' => null]);
        }
        
        try {
            
            $user = Db::table('caipiao_member')->where('id', $userId)->first();
            if (!$user) {
                return json(['code' => 400, 'message' => 'Người dùng không tồn tại', 'data' => null]);
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
                'message' => 'Lấy dữ liệu thành công',
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
            \support\Log::error('Lấy cấu hình rút tiền thất bại: ' . $e->getMessage() . ' | Trace: ' . $e->getTraceAsString());
            return json(['code' => 500, 'message' => 'Lấy dữ liệu thất bại: ' . $e->getMessage(), 'data' => null]);
        }
    }
    
    
    public function accounts(Request $request)
    {
        $userId = $request->userId ?? 0;
        
        if (!$userId) {
            return json(['code' => 401, 'message' => 'Vui lòng đăng nhập', 'data' => null]);
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
                    $bankName = $acc->bank_name ?: 'Thẻ ngân hàng';
                    $fullAddress = $acc->bank_account ?: '';
                    $cardNo = $acc->bank_account ? substr($acc->bank_account, -4) : '';
                } elseif ($acc->type === 'usdt') {
                    $network = $acc->usdt_network ?: 'TRC20';
                    $bankName = 'USDT-' . $network;
                    $fullAddress = $acc->usdt_address ?: '';
                    $cardNo = $acc->usdt_address ? substr($acc->usdt_address, -4) : '';
                } else {
                    $bankName = $acc->type === 'alipay' ? 'Alipay' : 'WeChat';
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
                'message' => 'Lấy dữ liệu thành công',
                'data' => $result
            ]);
            
        } catch (\Exception $e) {
            \support\Log::error('Lấy rút tiềntài khoảnThất bại: ' . $e->getMessage());
            return json(['code' => 500, 'message' => 'Lấy dữ liệuThất bại', 'data' => null]);
        }
    }
    
    
    public function submit(Request $request)
    {
        $userId = $request->userId ?? 0;
        
        if (!$userId) {
            return json(['code' => 401, 'message' => 'Vui lòng đăng nhập', 'data' => null]);
        }
        
        try {
            $accountId = $request->post('accountId');
            $amount = floatval($request->post('amount', 0));
            $fundPassword = $request->post('fundPassword', '');
            
            if (empty($accountId)) {
                return json(['code' => 400, 'message' => 'Vui lòng chọnRút tiềntài khoản', 'data' => null]);
            }
            
            if ($amount <= 0) {
                return json(['code' => 400, 'message' => '请输入正确的Số tiền', 'data' => null]);
            }
            
            
            if ($amount != floor($amount)) {
                return json(['code' => 400, 'message' => 'Rút tiềnSố tiền必须为整数', 'data' => null]);
            }
            
            
            $user = Db::table('caipiao_member')->where('id', $userId)->first();
            if (!$user) {
                return json(['code' => 400, 'message' => 'Người dùng không tồn tại', 'data' => null]);
            }
            
            
            if (empty($user->tradepassword)) {
                
                return json(['code' => 400, 'message' => '请先Cài đặt资金Mật khẩu', 'data' => ['needSetFundPwd' => true]]);
            }
            if (empty($fundPassword)) {
                return json(['code' => 400, 'message' => '请输入资金Mật khẩu', 'data' => null]);
            }
            if (md5($fundPassword) !== $user->tradepassword) {
                return json(['code' => 400, 'message' => '资金Mật khẩu sai', 'data' => null]);
            }
            
            
            $minWithdraw = $this->getConfig('tikuanMin') ?: 100;
            $maxWithdraw = $this->getConfig('tikuanMax') ?: 500000;
            
            if ($amount < $minWithdraw) {
                return json(['code' => 400, 'message' => "最低Rút tiềnSố tiền为 {$minWithdraw} 元", 'data' => null]);
            }
            if ($amount > $maxWithdraw) {
                return json(['code' => 400, 'message' => "最高Rút tiềnSố tiền为 {$maxWithdraw} 元", 'data' => null]);
            }
            
            
            $balance = floatval($user->balance);
            if ($amount > $balance) {
                return json(['code' => 400, 'message' => 'Số dư không đủ', 'data' => null]);
            }
            
            
            $dailyWithdrawTimes = $this->getConfig('tikuancishu') ?: 10;
            $todayStart = strtotime(date('Y-m-d 00:00:00'));
            $todayCount = Db::table('caipiao_withdraw')
                ->where('uid', $userId)
                ->where('oddtime', '>=', $todayStart)
                ->count();
            
            if ($todayCount >= $dailyWithdrawTimes) {
                return json(['code' => 400, 'message' => 'Hôm nayRút tiền次数已达上限', 'data' => null]);
            }
            
            
            $accountInfo = $this->getAccountInfo($accountId, $userId);
            if (!$accountInfo) {
                return json(['code' => 400, 'message' => 'Rút tiềntài khoảnkhông tồn tại', 'data' => null]);
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
                    return json(['code' => 400, 'message' => 'Số dư không đủ（并发检查）', 'data' => null]);
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
                    'typename' => 'Rút tiền',
                    'amount' => -$amount,
                    'amountbefor' => $actualBalance,
                    'amountafter' => $newBalance,
                    'oddtime' => time(),
                    'remark' => 'Rút tiền申请',
                ]);
                
                Db::commit();
                
                return json([
                    'code' => 0,
                    'message' => 'Rút tiền申请已Gửi',
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
            \support\Log::error('Rút tiềnThất bại: ' . $e->getMessage());
            return json(['code' => 500, 'message' => 'Rút tiềnThất bại: ' . $e->getMessage(), 'data' => null]);
        }
    }
    
    
    public function records(Request $request)
    {
        $userId = $request->userId ?? 0;
        
        if (!$userId) {
            return json(['code' => 401, 'message' => 'Vui lòng đăng nhập', 'data' => null]);
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
            
            $stateMap = [0 => '出款中', 1 => 'Rút tiềnThành công', 2 => 'Rút tiền拒绝', 3 => 'Rút tiềnHủy'];
            
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
                'message' => 'Lấy dữ liệu thành công',
                'data' => [
                    'list' => $result,
                    'total' => $total,
                    'totalAmount' => floatval($totalAmount),
                    'page' => $page,
                    'pageSize' => $pageSize,
                ]
            ]);
            
        } catch (\Exception $e) {
            \support\Log::error('Lấy rút tiềnlịch sử thất bại: ' . $e->getMessage());
            return json(['code' => 500, 'message' => 'Lấy dữ liệuThất bại', 'data' => null]);
        }
    }
    
    
    public function cancel(Request $request)
    {
        $userId = $request->userId ?? 0;
        
        if (!$userId) {
            return json(['code' => 401, 'message' => 'Vui lòng đăng nhập', 'data' => null]);
        }
        
        try {
            $id = $request->post('id');
            
            if (!$id) {
                return json(['code' => 400, 'message' => 'Tham số không hợp lệ', 'data' => null]);
            }
            
            
            $withdraw = Db::table('caipiao_withdraw')
                ->where('id', $id)
                ->where('uid', $userId)
                ->first();
            
            if (!$withdraw) {
                return json(['code' => 400, 'message' => 'Rút tiềnlịch sửkhông tồn tại', 'data' => null]);
            }
            
            
            if ($withdraw->state != 0) {
                return json(['code' => 400, 'message' => '只能Hủy出款中的订单', 'data' => null]);
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
                        'remark' => 'Người dùng自己Hủy',
                        'updatetime' => time()
                    ]);
                
                
                $trano = 'TX' . date('YmdHis') . rand(1000, 9999);
                Db::table('caipiao_fuddetail')->insert([
                    'trano' => $trano,
                    'uid' => $userId,
                    'username' => $user->username,
                    'type' => 'withdraw_cancel',
                    'typename' => 'Rút tiềnHủy',
                    'amount' => $withdraw->amount,
                    'amountbefor' => $oldBalance,
                    'amountafter' => $newBalance,
                    'oddtime' => time(),
                    'remark' => 'Người dùngHủyRút tiền，原单号：' . $withdraw->trano
                ]);
                
                Db::commit();
                
                return json(['code' => 0, 'message' => 'HủyThành công', 'data' => null]);
                
            } catch (\Exception $e) {
                Db::rollBack();
                throw $e;
            }
            
        } catch (\Exception $e) {
            \support\Log::error('HủyRút tiềnThất bại: ' . $e->getMessage());
            return json(['code' => 500, 'message' => 'HủyThất bại: ' . $e->getMessage(), 'data' => null]);
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
                    'bank' => 'Thẻ ngân hàng',
                    'alipay' => 'Alipay',
                    'wechat' => 'WeChat',
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
                    'typeName' => 'Thẻ ngân hàng',
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
            return json(['code' => 401, 'message' => 'Vui lòng đăng nhập', 'data' => null]);
        }
        
        try {
            $type = $request->post('type', 'bank'); 
            $fundPassword = $request->post('fundPassword', '');
            
            
            $user = Db::table('caipiao_member')->where('id', $userId)->first();
            if (!$user) {
                return json(['code' => 400, 'message' => 'Người dùng không tồn tại', 'data' => null]);
            }
            
            
            if (empty($user->tradepassword)) {
                
                return json(['code' => 400, 'message' => '请先Cài đặt资金Mật khẩu', 'data' => ['needSetFundPwd' => true]]);
            }
            if (empty($fundPassword)) {
                return json(['code' => 400, 'message' => '请输入资金Mật khẩu', 'data' => null]);
            }
            if (md5($fundPassword) !== $user->tradepassword) {
                return json(['code' => 400, 'message' => '资金Mật khẩu sai', 'data' => null]);
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
                    return json(['code' => 400, 'message' => 'Vui lòng chọn开户银行', 'data' => null]);
                }
                if (empty($bankAccount)) {
                    return json(['code' => 400, 'message' => '请输入Thẻ ngân hàng号', 'data' => null]);
                }
                
                
                $exists = Db::table('caipiao_withdraw_account')
                    ->where('uid', $userId)
                    ->where('type', 'bank')
                    ->where('bank_account', $bankAccount)
                    ->exists();
                if ($exists) {
                    return json(['code' => 400, 'message' => '该Thẻ ngân hàng已经Thêm过', 'data' => null]);
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
                    return json(['code' => 400, 'message' => '该地址已经Thêm过', 'data' => null]);
                }
                
                $data['usdt_network'] = $network;
                $data['usdt_address'] = $address;
                $data['bank_name'] = 'USDT-' . $network;
                $data['account_name'] = $request->post('remark', 'USDTRút tiền');
                
            } elseif ($type === 'alipay' || $type === 'wechat') {
                $account = $request->post('account', '');
                $accountName = $request->post('accountName', '') ?: $user->userbankname;
                $qrCode = $request->post('qrCode', '');
                
                
                if ($type === 'alipay' && empty($account)) {
                    return json(['code' => 400, 'message' => '请输入AlipayTài khoản', 'data' => null]);
                }
                if (empty($qrCode)) {
                    return json(['code' => 400, 'message' => '请上传收款码', 'data' => null]);
                }
                
                $data['bank_account'] = $account ?: ($type === 'wechat' ? 'wechat_qr' : 'alipay_qr');
                $data['account_name'] = $accountName;
                $data['bank_name'] = $type === 'alipay' ? 'Alipay' : 'WeChat';
                $data['qr_code'] = $qrCode;
                
            } else {
                return json(['code' => 400, 'message' => '不支持的tài khoản类型', 'data' => null]);
            }
            
            
            if ($data['is_default']) {
                Db::table('caipiao_withdraw_account')
                    ->where('uid', $userId)
                    ->update(['is_default' => 0]);
            }
            
            $id = Db::table('caipiao_withdraw_account')->insertGetId($data);
            
            return json([
                'code' => 0,
                'message' => 'ThêmThành công',
                'data' => ['id' => $id]
            ]);
            
        } catch (\Exception $e) {
            \support\Log::error('ThêmRút tiềntài khoảnThất bại: ' . $e->getMessage());
            return json(['code' => 500, 'message' => 'ThêmThất bại: ' . $e->getMessage(), 'data' => null]);
        }
    }
    
    
    public function deleteAccount(Request $request)
    {
        $userId = $request->userId ?? 0;
        
        if (!$userId) {
            return json(['code' => 401, 'message' => 'Vui lòng đăng nhập', 'data' => null]);
        }
        
        try {
            $id = $request->post('id');
            
            if (!$id) {
                return json(['code' => 400, 'message' => 'Tham số không hợp lệ', 'data' => null]);
            }
            
            $result = Db::table('caipiao_withdraw_account')
                ->where('id', $id)
                ->where('uid', $userId)
                ->delete();
            
            if ($result) {
                return json(['code' => 0, 'message' => 'XóaThành công', 'data' => null]);
            } else {
                return json(['code' => 400, 'message' => 'XóaThất bại', 'data' => null]);
            }
            
        } catch (\Exception $e) {
            return json(['code' => 500, 'message' => 'XóaThất bại', 'data' => null]);
        }
    }
    
    
    public function setDefaultAccount(Request $request)
    {
        $userId = $request->userId ?? 0;
        
        if (!$userId) {
            return json(['code' => 401, 'message' => 'Vui lòng đăng nhập', 'data' => null]);
        }
        
        try {
            $id = $request->post('id');
            
            if (!$id) {
                return json(['code' => 400, 'message' => 'Tham số không hợp lệ', 'data' => null]);
            }
            
            
            Db::table('caipiao_withdraw_account')
                ->where('uid', $userId)
                ->update(['is_default' => 0]);
            
            
            $result = Db::table('caipiao_withdraw_account')
                ->where('id', $id)
                ->where('uid', $userId)
                ->update(['is_default' => 1]);
            
            if ($result) {
                return json(['code' => 0, 'message' => 'Cài đặtThành công', 'data' => null]);
            } else {
                return json(['code' => 400, 'message' => 'Cài đặtThất bại', 'data' => null]);
            }
            
        } catch (\Exception $e) {
            return json(['code' => 500, 'message' => 'Cài đặtThất bại', 'data' => null]);
        }
    }
    
    
    public function uploadQrCode(Request $request)
    {
        $userId = $request->userId ?? 0;
        
        if (!$userId) {
            return json(['code' => 401, 'message' => 'Vui lòng đăng nhập', 'data' => null]);
        }
        
        try {
            $file = $request->file('file');
            if (!$file || !$file->isValid()) {
                return json(['code' => 400, 'message' => 'Vui lòng chọn要上传的图片', 'data' => null]);
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
                'message' => '上传Thành công',
                'data' => [
                    'url' => $url
                ]
            ]);
            
        } catch (\Exception $e) {
            \support\Log::error('上传收款码Thất bại: ' . $e->getMessage());
            return json(['code' => 500, 'message' => '上传Thất bại: ' . $e->getMessage(), 'data' => null]);
        }
    }
    
    
    private function getConfig($name)
    {
        return ConfigCacheService::get($name);
    }
}
