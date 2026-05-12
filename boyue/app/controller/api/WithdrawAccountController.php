<?php

namespace app\controller\api;

use support\Request;
use support\Db;

class WithdrawAccountController
{
    
    public function index(Request $request)
    {
        $userId = $request->userId ?? 0;
        
        if (!$userId) {
            return json([
                'code' => 401,
                'message' => '未登录或登录已过期',
                'data' => null
            ]);
        }
        
        try {
            $accounts = Db::table('caipiao_withdraw_account')
                ->where('uid', $userId)
                ->where('status', 1)
                ->orderBy('is_default', 'desc')
                ->orderBy('created_at', 'desc')
                ->get();
            
            $list = [];
            foreach ($accounts as $account) {
                $item = [
                    'id' => $account->id,
                    'type' => $account->type,
                    'isDefault' => $account->is_default == 1,
                    'createdAt' => date('Y-m-d H:i:s', $account->created_at)
                ];
                
                
                switch ($account->type) {
                    case 'bank':
                        $item['bankName'] = $account->bank_name;
                        $item['bankAccount'] = $account->bank_account;
                        $item['accountName'] = $account->account_name;
                        $item['bankBranch'] = $account->bank_branch;
                        break;
                    case 'usdt':
                        $item['address'] = $account->usdt_address;
                        $item['network'] = $account->usdt_network;
                        break;
                    case 'alipay':
                    case 'wechat':
                        $item['account'] = $account->bank_account;
                        $item['accountName'] = $account->account_name;
                        break;
                }
                
                $list[] = $item;
            }
            
            return json([
                'code' => 0,
                'message' => '获取成功',
                'data' => [
                    'list' => $list
                ]
            ]);
            
        } catch (\Exception $e) {
            \support\Log::error('获取提现账户失败: ' . $e->getMessage());
            return json([
                'code' => 500,
                'message' => '获取数据失败：' . $e->getMessage(),
                'data' => null
            ]);
        }
    }
    
    
    public function addBank(Request $request)
    {
        $userId = $request->userId ?? 0;
        
        if (!$userId) {
            return json([
                'code' => 401,
                'message' => '未登录或登录已过期',
                'data' => null
            ]);
        }
        
        try {
            $bankName = $request->post('bankName', '');
            $bankAccount = $request->post('bankAccount', '');
            $accountName = $request->post('accountName', '');
            $bankBranch = $request->post('bankBranch', '');
            $isDefault = $request->post('isDefault', false);
            
            
            if (empty($bankName) || empty($bankAccount) || empty($accountName)) {
                return json([
                    'code' => 1003,
                    'message' => '请填写完整的银行卡信息',
                    'data' => null
                ]);
            }
            
            
            $exists = Db::table('caipiao_withdraw_account')
                ->where('uid', $userId)
                ->where('type', 'bank')
                ->where('bank_account', $bankAccount)
                ->where('status', 1)
                ->exists();
            
            if ($exists) {
                return json([
                    'code' => 1004,
                    'message' => '该银行卡已添加',
                    'data' => null
                ]);
            }
            
            
            if ($isDefault) {
                Db::table('caipiao_withdraw_account')
                    ->where('uid', $userId)
                    ->update(['is_default' => 0]);
            }
            
            $now = time();
            $id = Db::table('caipiao_withdraw_account')->insertGetId([
                'uid' => $userId,
                'type' => 'bank',
                'bank_name' => $bankName,
                'bank_account' => $bankAccount,
                'account_name' => $accountName,
                'bank_branch' => $bankBranch,
                'is_default' => $isDefault ? 1 : 0,
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now
            ]);
            
            return json([
                'code' => 0,
                'message' => '添加成功',
                'data' => [
                    'id' => $id,
                    'type' => 'bank',
                    'bankName' => $bankName,
                    'bankAccount' => $bankAccount,
                    'accountName' => $accountName,
                    'bankBranch' => $bankBranch,
                    'isDefault' => (bool)$isDefault,
                    'createdAt' => date('Y-m-d H:i:s', $now)
                ]
            ]);
            
        } catch (\Exception $e) {
            \support\Log::error('添加银行卡失败: ' . $e->getMessage());
            return json([
                'code' => 500,
                'message' => '添加失败：' . $e->getMessage(),
                'data' => null
            ]);
        }
    }
    
    
    public function addUsdt(Request $request)
    {
        $userId = $request->userId ?? 0;
        
        if (!$userId) {
            return json([
                'code' => 401,
                'message' => '未登录或登录已过期',
                'data' => null
            ]);
        }
        
        try {
            $address = $request->post('address', '');
            $network = $request->post('network', 'TRC20');
            $isDefault = $request->post('isDefault', false);
            
            
            if (empty($address)) {
                return json([
                    'code' => 1003,
                    'message' => '请填写USDT地址',
                    'data' => null
                ]);
            }
            
            
            $exists = Db::table('caipiao_withdraw_account')
                ->where('uid', $userId)
                ->where('type', 'usdt')
                ->where('usdt_address', $address)
                ->where('status', 1)
                ->exists();
            
            if ($exists) {
                return json([
                    'code' => 1004,
                    'message' => '该USDT地址已添加',
                    'data' => null
                ]);
            }
            
            
            if ($isDefault) {
                Db::table('caipiao_withdraw_account')
                    ->where('uid', $userId)
                    ->update(['is_default' => 0]);
            }
            
            $now = time();
            $id = Db::table('caipiao_withdraw_account')->insertGetId([
                'uid' => $userId,
                'type' => 'usdt',
                'usdt_address' => $address,
                'usdt_network' => $network,
                'is_default' => $isDefault ? 1 : 0,
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now
            ]);
            
            return json([
                'code' => 0,
                'message' => '添加成功',
                'data' => [
                    'id' => $id,
                    'type' => 'usdt',
                    'address' => $address,
                    'network' => $network,
                    'isDefault' => (bool)$isDefault,
                    'createdAt' => date('Y-m-d H:i:s', $now)
                ]
            ]);
            
        } catch (\Exception $e) {
            \support\Log::error('添加USDT地址失败: ' . $e->getMessage());
            return json([
                'code' => 500,
                'message' => '添加失败：' . $e->getMessage(),
                'data' => null
            ]);
        }
    }
    
    
    public function addAlipay(Request $request)
    {
        return $this->addThirdPartyAccount($request, 'alipay', '支付宝');
    }
    
    
    public function addWechat(Request $request)
    {
        return $this->addThirdPartyAccount($request, 'wechat', '微信');
    }
    
    
    private function addThirdPartyAccount(Request $request, $type, $typeName)
    {
        $userId = $request->userId ?? 0;
        
        if (!$userId) {
            return json([
                'code' => 401,
                'message' => '未登录或登录已过期',
                'data' => null
            ]);
        }
        
        try {
            $account = $request->post('account', '');
            $accountName = $request->post('accountName', '');
            $isDefault = $request->post('isDefault', false);
            
            
            if (empty($account) || empty($accountName)) {
                return json([
                    'code' => 1003,
                    'message' => "请填写完整的{$typeName}信息",
                    'data' => null
                ]);
            }
            
            
            $exists = Db::table('caipiao_withdraw_account')
                ->where('uid', $userId)
                ->where('type', $type)
                ->where('bank_account', $account)
                ->where('status', 1)
                ->exists();
            
            if ($exists) {
                return json([
                    'code' => 1004,
                    'message' => "该{$typeName}账户已添加",
                    'data' => null
                ]);
            }
            
            
            if ($isDefault) {
                Db::table('caipiao_withdraw_account')
                    ->where('uid', $userId)
                    ->update(['is_default' => 0]);
            }
            
            $now = time();
            $id = Db::table('caipiao_withdraw_account')->insertGetId([
                'uid' => $userId,
                'type' => $type,
                'bank_account' => $account,
                'account_name' => $accountName,
                'is_default' => $isDefault ? 1 : 0,
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now
            ]);
            
            return json([
                'code' => 0,
                'message' => '添加成功',
                'data' => [
                    'id' => $id,
                    'type' => $type,
                    'account' => $account,
                    'accountName' => $accountName,
                    'isDefault' => (bool)$isDefault,
                    'createdAt' => date('Y-m-d H:i:s', $now)
                ]
            ]);
            
        } catch (\Exception $e) {
            \support\Log::error("添加{$typeName}账户失败: " . $e->getMessage());
            return json([
                'code' => 500,
                'message' => '添加失败：' . $e->getMessage(),
                'data' => null
            ]);
        }
    }
    
    
    public function setDefault(Request $request, $id)
    {
        $userId = $request->userId ?? 0;
        
        if (!$userId) {
            return json([
                'code' => 401,
                'message' => '未登录或登录已过期',
                'data' => null
            ]);
        }
        
        try {
            
            $account = Db::table('caipiao_withdraw_account')
                ->where('id', $id)
                ->where('uid', $userId)
                ->where('status', 1)
                ->first();
            
            if (!$account) {
                return json([
                    'code' => 1005,
                    'message' => '账户不存在',
                    'data' => null
                ]);
            }
            
            
            Db::table('caipiao_withdraw_account')
                ->where('uid', $userId)
                ->update(['is_default' => 0]);
            
            
            Db::table('caipiao_withdraw_account')
                ->where('id', $id)
                ->update([
                    'is_default' => 1,
                    'updated_at' => time()
                ]);
            
            return json([
                'code' => 0,
                'message' => '设置成功',
                'data' => null
            ]);
            
        } catch (\Exception $e) {
            \support\Log::error('设置默认账户失败: ' . $e->getMessage());
            return json([
                'code' => 500,
                'message' => '设置失败：' . $e->getMessage(),
                'data' => null
            ]);
        }
    }
    
    
    public function delete(Request $request, $id)
    {
        $userId = $request->userId ?? 0;
        
        if (!$userId) {
            return json([
                'code' => 401,
                'message' => '未登录或登录已过期',
                'data' => null
            ]);
        }
        
        try {
            
            $account = Db::table('caipiao_withdraw_account')
                ->where('id', $id)
                ->where('uid', $userId)
                ->where('status', 1)
                ->first();
            
            if (!$account) {
                return json([
                    'code' => 1005,
                    'message' => '账户不存在',
                    'data' => null
                ]);
            }
            
            
            if ($account->is_default == 1) {
                return json([
                    'code' => 1006,
                    'message' => '不能删除默认账户，请先设置其他账户为默认',
                    'data' => null
                ]);
            }
            
            
            Db::table('caipiao_withdraw_account')
                ->where('id', $id)
                ->update([
                    'status' => 0,
                    'updated_at' => time()
                ]);
            
            return json([
                'code' => 0,
                'message' => '删除成功',
                'data' => null
            ]);
            
        } catch (\Exception $e) {
            \support\Log::error('删除提现账户失败: ' . $e->getMessage());
            return json([
                'code' => 500,
                'message' => '删除失败：' . $e->getMessage(),
                'data' => null
            ]);
        }
    }
}
