<?php

namespace app\controller\api;

use support\Request;
use support\Db;
use app\service\ConfigCacheService;
use app\service\PayService;

class RechargeController
{
    private $lbpalKey = 'aKy142JUl9CzV8OrBamWExaQCGvcMp7152xRWkUUabw2F2hEfvKBHmOyzEyI6vh5yrHo1SpqnpQlkw8RAK219IdoOj2nuYdAtbh2nCf5qzFZhwWhjCqRWvnDb1UbEK7x';
    
    public function methods(Request $request)
    {
        try {
            $methods = Db::table('caipiao_payset')
                ->where('state', 1)
                ->orderBy('listorder', 'asc')
                ->get();
            
            $result = [];
            foreach ($methods as $m) {
                $configs = $this->parseConfigs($m->configs);
                
                $result[] = [
                    'id' => $m->id,
                    'type' => $m->paytype,
                    'title' => $m->paytypetitle,
                    'subtitle' => $m->ftitle,
                    'minAmount' => floatval($m->minmoney),
                    'maxAmount' => floatval($m->maxmoney),
                    'isOnline' => $m->isonline == 1,
                    'remark' => $m->remark,
                    'qrcode' => $configs['ewmurl'] ?? '',
                    'sort' => $m->listorder,
                ];
            }
            
            return json([
                'code' => 0,
                'message' => 'Lấy dữ liệu thành công',
                'data' => $result
            ]);
            
        } catch (\Exception $e) {
            \support\Log::error('Lấy phương thức nạp tiền thất bại: ' . $e->getMessage());
            return json([
                'code' => 500,
                'message' => 'Lấy dữ liệu thất bại',
                'data' => null
            ]);
        }
    }
    
    public function config(Request $request, $type)
    {
        try {
            $config = Db::table('caipiao_payset')
                ->where('paytype', $type)
                ->where('state', 1)
                ->first();
            
            if (!$config) {
                return json([
                    'code' => 404,
                    'message' => 'Phương thức thanh toán không tồn tại hoặc đã đóng',
                    'data' => null
                ]);
            }
            
            $configs = $this->parseConfigs($config->configs);
            
            $data = [
                'id' => $config->id,
                'type' => $config->paytype,
                'title' => $config->paytypetitle,
                'subtitle' => $config->ftitle,
                'minAmount' => floatval($config->minmoney),
                'maxAmount' => floatval($config->maxmoney),
                'isOnline' => $config->isonline == 1,
                'remark' => $config->remark,
            ];
            
            switch ($type) {
                case 'alipay':
                case 'weixin':
                    $data['qrcode'] = $configs['ewmurl'] ?? '';
                    $data['account'] = $configs['account'] ?? '';
                    $data['accountName'] = $configs['accountname'] ?? '';
                    break;
                    
                case 'linepay':
                    $data['bankName'] = $configs['bankname'] ?? '';
                    $data['bankCode'] = $configs['bankcode'] ?? '';
                    $data['accountName'] = $configs['accountname'] ?? '';
                    $data['bankBranch'] = $configs['bankbranch'] ?? '';
                    break;
                    
                case 'USDT':
                    $data['rate'] = floatval($configs['rate'] ?? 7.2);
                    $data['trc20Address'] = $configs['trc20'] ?? '';
                    $data['erc20Address'] = $configs['erc20'] ?? '';
                    $data['trc20Min'] = floatval($configs['trc20min'] ?? 10);
                    $data['erc20Min'] = floatval($configs['erc20min'] ?? 50);
                    break;
                    
                default:
                    $data['qrcode'] = $configs['ewmurl'] ?? '';
            }
            
            return json([
                'code' => 0,
                'message' => 'Lấy dữ liệu thành công',
                'data' => $data
            ]);
            
        } catch (\Exception $e) {
            \support\Log::error('Lấy thanh toáncấu hình thất bại: ' . $e->getMessage());
            return json([
                'code' => 500,
                'message' => 'Lấy dữ liệuThất bại',
                'data' => null
            ]);
        }
    }
    
    public function submit(Request $request)
    {
        $userId = $request->userId ?? 0;
        
        if (!$userId) {
            return json([
                'code' => 401,
                'message' => 'Vui lòng đăng nhập',
                'data' => null
            ]);
        }
        
        try {
            $paytype = $request->post('paytype');
            $amount = floatval($request->post('amount', 0));
            $payname = $request->post('payname', '');
            $chain = $request->post('chain', '');
            
            if (empty($paytype)) {
                return json(['code' => 400, 'message' => 'Vui lòng chọnPhương thức thanh toán', 'data' => null]);
            }
            
            if ($amount <= 0) {
                return json(['code' => 400, 'message' => '请输入正确的Số tiền', 'data' => null]);
            }
            
            $config = Db::table('caipiao_payset')
                ->where('paytype', $paytype)
                ->where('state', 1)
                ->first();
            
            if (!$config) {
                return json(['code' => 400, 'message' => 'Phương thức thanh toánkhông khả dụng', 'data' => null]);
            }
            
            if ($amount < $config->minmoney) {
                return json(['code' => 400, 'message' => "最低Nạp tiềnSố tiền为 {$config->minmoney} 元", 'data' => null]);
            }
            if ($amount > $config->maxmoney) {
                return json(['code' => 400, 'message' => "最高Nạp tiềnSố tiền为 {$config->maxmoney} 元", 'data' => null]);
            }
            
            $user = Db::table('caipiao_member')->where('id', $userId)->first();
            if (!$user) {
                return json(['code' => 400, 'message' => 'Người dùng không tồn tại', 'data' => null]);
            }
            
            $trano = date('YmdHis') . str_pad($userId, 6, '0', STR_PAD_LEFT) . mt_rand(1000, 9999);
            
            $orderId = Db::table('caipiao_recharge')->insertGetId([
                'uid' => $userId,
                'username' => $user->username,
                'paytype' => $paytype,
                'paytypetitle' => $config->paytypetitle,
                'paytypename' => $config->ftitle,
                'trano' => $trano,
                'threetrano' => '',
                'amount' => $amount,
                'fee' => 0,
                'actualamount' => $amount,
                'actualfee' => 0,
                'oldaccountmoney' => $user->balance ?? 0,
                'newaccountmoney' => 0,
                'remark' => $chain ? "链类型: $chain" : '',
                'payname' => $payname,
                'fuyanma' => substr($trano, -6),
                'isauto' => $config->isonline == 1 ? 1 : 0,
                'sdtype' => 0,
                'state' => 0,
                'oddtime' => time(),
                'stateadmin' => '',
            ]);
            
            $configs = $this->parseConfigs($config->configs);
            
            $responseData = [
                'orderId' => $orderId,
                'trano' => $trano,
                'amount' => $amount,
                'paytype' => $paytype,
                'fuyanma' => substr($trano, -6),
            ];
            
            if ($paytype == 'alipay' || $paytype == 'weixin') {
                $responseData['qrcode'] = $configs['ewmurl'] ?? '';
            } elseif ($paytype == 'linepay') {
                $responseData['bankName'] = $configs['bankname'] ?? '';
                $responseData['bankCode'] = $configs['bankcode'] ?? '';
                $responseData['accountName'] = $configs['accountname'] ?? '';
                $responseData['bankBranch'] = $configs['bankbranch'] ?? '';
            } elseif ($paytype == 'USDT') {
                $responseData['address'] = $chain == 'ERC20' ? ($configs['erc20'] ?? '') : ($configs['trc20'] ?? '');
                $responseData['chain'] = $chain;
            }
            
            if($paytype == 'alipay' || $paytype == 'weixin'){
                $responseData['channel_code'] = $request->post('channelCode', '');
                return $this->excutePay($responseData);
            }else{
                return json([
                    'code' => 0,
                    'message' => '订单创建Thành công',
                    'data' => $responseData
                ]);
            }
            
        } catch (\Exception $e) {
            \support\Log::error('创建Nạp tiền订单Thất bại: ' . $e->getMessage());
            return json([
                'code' => 500,
                'message' => '创建订单Thất bại: ' . $e->getMessage(),
                'data' => null
            ]);
        }
    }
    
    private function excutePay($data){
        $params = [
            'mchId'=> 10545,
            'mchOrderNo'=>$data['trano'],
            'amount'=> intval($data['amount'])*100,
            // 'channel'=>$data['channel_code'],
            'productId'=> $data['channel_code'],
            'notifyUrl'=>env('WAP_URL').'/api/v1/lbpalNotify',
            'returnUrl'=>env('WAP_URL'),
        ];
        $payService = new PayService();
        $params['sign'] = $payService->lbpalSign($params,$this->lbpalKey);
        // $this->write_log($params);
        $res = $payService->curl_request('http://pay.lbpal.click/api/pay/create_order',http_build_query($params),'POST',array("Content-Type:application/x-www-form-urlencoded"));
        // $this->write_log($res);
        $message = 'GửiThất bại';
        if($res){
            $result = json_decode($res,true);
            if($result && $result['retCode'] == 'SUCCESS'){
                return json(['code'=>0,'data' => ['pay_url' => $result['payParams']['payUrl']]]);
            }
            $message = $result['retMsg'];
        }
        return json(['code' => 200,'message' => $message]);
    }
    
    public function confirm(Request $request)
    {
        $userId = $request->userId ?? 0;
        
        if (!$userId) {
            return json(['code' => 401, 'message' => 'Vui lòng đăng nhập', 'data' => null]);
        }
        
        try {
            $trano = $request->post('trano');
            
            if (empty($trano)) {
                return json(['code' => 400, 'message' => '订单号không được để trống', 'data' => null]);
            }
            
            $order = Db::table('caipiao_recharge')
                ->where('trano', $trano)
                ->where('uid', $userId)
                ->first();
            
            if (!$order) {
                return json(['code' => 404, 'message' => '订单không tồn tại', 'data' => null]);
            }
            
            if ($order->state != 0) {
                return json(['code' => 400, 'message' => '订单状态异常', 'data' => null]);
            }
            
            Db::table('caipiao_recharge')
                ->where('id', $order->id)
                ->update([
                    'state' => 1,
                    'sdtype' => 1,
                    'remark' => ($order->remark ? $order->remark . ' | ' : '') . 'Người dùng已Xác nhậnThanh toán',
                ]);
            
            return json([
                'code' => 0,
                'message' => 'Xác nhậnThành công，请等Chờ duyệt',
                'data' => null
            ]);
            
        } catch (\Exception $e) {
            return json(['code' => 500, 'message' => 'Thao tác thất bại', 'data' => null]);
        }
    }
    
    private static $stateMap = [
        0 => ['status' => 'pending', 'name' => '待处理'],
        1 => ['status' => 'confirming', 'name' => 'Xác nhận中'],
        2 => ['status' => 'success', 'name' => 'Thành công'],
        3 => ['status' => 'failed', 'name' => 'Thất bại'],
        4 => ['status' => 'cancelled', 'name' => 'Đã hủy'],
        5 => ['status' => 'timeout', 'name' => '超时'],
    ];
    
    private static function getStateInfo($state)
    {
        return self::$stateMap[$state] ?? ['status' => 'pending', 'name' => '待处理'];
    }
    
    public function status(Request $request, $trano)
    {
        $userId = $request->userId ?? 0;
        
        if (!$userId) {
            return json(['code' => 401, 'message' => 'Vui lòng đăng nhập', 'data' => null]);
        }
        
        try {
            $order = Db::table('caipiao_recharge')
                ->where('trano', $trano)
                ->where('uid', $userId)
                ->first();
            
            if (!$order) {
                return json(['code' => 404, 'message' => '订单không tồn tại', 'data' => null]);
            }
            
            $stateInfo = self::getStateInfo($order->state);
            
            return json([
                'code' => 0,
                'message' => 'Lấy dữ liệu thành công',
                'data' => [
                    'trano' => $order->trano,
                    'amount' => floatval($order->amount),
                    'state' => $order->state,
                    'status' => $stateInfo['status'],
                    'stateName' => $stateInfo['name'],
                    'createTime' => $order->oddtime * 1000,
                ]
            ]);
            
        } catch (\Exception $e) {
            return json(['code' => 500, 'message' => 'Lấy状态Thất bại', 'data' => null]);
        }
    }
    
    public function records(Request $request)
    {
        $userId = $request->userId ?? 0;
        
        if (!$userId) {
            return json(['code' => 401, 'message' => 'Vui lòng đăng nhập', 'data' => null]);
        }
        
        try {
            $page = (int)$request->get('page', 1);
            $pageSize = (int)$request->get('pageSize', 20);
            $state = $request->get('state');
            $startTime = $request->get('startTime');
            $endTime = $request->get('endTime');
            
            $query = Db::table('caipiao_recharge')
                ->where('uid', $userId);
            
            if ($state !== null && $state !== '') {
                $query->where('state', (int)$state);
            }
            
            if ($startTime) {
                $query->where('oddtime', '>=', (int)($startTime / 1000));
            }
            if ($endTime) {
                $query->where('oddtime', '<=', (int)($endTime / 1000));
            }
            
            $total = $query->count();
            
            $records = $query->orderBy('id', 'desc')
                ->offset(($page - 1) * $pageSize)
                ->limit($pageSize)
                ->get();
            
            $list = [];
            foreach ($records as $r) {
                $stateInfo = self::getStateInfo($r->state);
                $list[] = [
                    'id' => $r->id,
                    'trano' => $r->trano,
                    'paytype' => $r->paytype,
                    'paytypeName' => $r->paytypetitle,
                    'amount' => floatval($r->amount),
                    'state' => $r->state,
                    'status' => $stateInfo['status'],
                    'stateName' => $stateInfo['name'],
                    'createTime' => $r->oddtime * 1000,
                    'remark' => $r->remark,
                ];
            }
            
            return json([
                'code' => 0,
                'message' => 'Lấy dữ liệu thành công',
                'data' => [
                    'total' => $total,
                    'page' => $page,
                    'pageSize' => $pageSize,
                    'list' => $list
                ]
            ]);
            
        } catch (\Exception $e) {
            return json(['code' => 500, 'message' => 'Lấylịch sử thất bại', 'data' => null]);
        }
    }
    
    private function parseConfigs($data): array
    {
        if (empty($data)) {
            return [];
        }
        
        $decoded = json_decode($data, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }
        
        try {
            $unserialized = @unserialize($data, ['allowed_classes' => false]);
            if (is_array($unserialized)) {
                return $unserialized;
            }
        } catch (\Exception $e) {
            \support\Log::warning('parseConfigs failed: ' . $e->getMessage());
        }
        
        return [];
    }
    
    public function lbpalNotify(Request $request){
	    $data = $request->all();
	    \support\Log::error('Nạp tiền和回调dữ liệu: ' . json_encode($data,JSON_UNESCAPED_UNICODE));
        if(!is_array($data) || count($data) == 0) exit('error');
		$recharge = Db::table('caipiao_recharge')->where('trano', $data['mchOrderNo'])->where('state', 0)->first();
		if(!$recharge){
			echo 'order error';
			exit;			
		}
		$user = Db::table('caipiao_member')->where('id', $recharge->uid)->first();
		if(!$user){
			echo 'user error';
			exit;			
		}
		Db::table('caipiao_recharge')->where('id',$recharge->id)->update(['state'=>1]);
		$user->increment('balance',$recharge->balance);
		echo 'success';
		exit;
	}
}
