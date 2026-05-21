<?php

namespace app\controller\api;

use support\Request;
use app\model\Order;
use app\model\OrderItem;
use app\model\Lottery;
use app\model\Account;
use app\model\AccountTransaction;
use Illuminate\Support\Facades\DB;

class OrderController
{
    
    public function create(Request $request)
    {
        $userId = $request->userId ?? 0;
        
        if (!$userId) {
            return json(['code' => 401, 'message' => 'unauthorized', 'data' => null]);
        }
        
        $lotteryKey = $request->post('lottery_key');
        $expect = $request->post('expect');
        $playId = $request->post('play_id');
        $unit = $request->post('unit', 'yuan');
        $bets = (int)$request->post('bets', 0);
        $multiple = (int)$request->post('multiple', 1);
        $code = $request->post('code');
        $amount = (float)$request->post('amount', 0);
        
        
        if (!$lotteryKey || !$expect || !$playId || !$code || $amount <= 0) {
            return json(['code' => 400, 'message' => 'invalid_parameters', 'data' => null]);
        }
        
        
        $lottery = Lottery::where('key', $lotteryKey)->first();
        if (!$lottery) {
            return json(['code' => 404, 'message' => 'lottery_not_found', 'data' => null]);
        }
        
        
        $orderNo = 'O' . date('YmdHis') . str_pad($userId, 6, '0', STR_PAD_LEFT) . mt_rand(1000, 9999);
        
        try {
            
            \Illuminate\Support\Facades\DB::beginTransaction();
            
            
            $account = Account::where('user_id', $userId)->lockForUpdate()->first();
            
            if (!$account || $account->balance < $amount) {
                \Illuminate\Support\Facades\DB::rollBack();
                return json(['code' => 400, 'message' => 'insufficient_balance', 'data' => null]);
            }
            
            $balanceBefore = $account->balance;
            $account->balance -= $amount;
            $account->save();
            
            
            $order = Order::create([
                'order_no' => $orderNo,
                'user_id' => $userId,
                'lottery_id' => $lottery->id,
                'lottery_name' => $lottery->title,
                'type_id' => '',
                'play_id' => $playId,
                'play_title' => $playId,
                'expect' => $expect,
                'unit' => $unit,
                'bets' => $bets,
                'multiple' => $multiple,
                'code' => $code,
                'amount' => $amount,
                'bonus' => 0,
                'status' => 'pending',
                'placed_at' => date('Y-m-d H:i:s'),
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            
            
            AccountTransaction::create([
                'user_id' => $userId,
                'type' => 'bet',
                'amount' => -$amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $account->balance,
                'ref_type' => 'order',
                'ref_id' => $order->id,
                'remark' => "Đặt cược订单: {$orderNo}",
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            
            \Illuminate\Support\Facades\DB::commit();
            
            return json([
                'code' => 0,
                'message' => 'ok',
                'data' => ['order_no' => $orderNo]
            ]);
            
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            throw $e;
        }
    }
    
    
    public function index(Request $request)
    {
        $userId = $request->userId ?? 0;
        $page = max(1, (int)$request->get('page', 1));
        $pageSize = min(100, max(10, (int)$request->get('page_size', 20)));
        $status = $request->get('status');
        
        $query = Order::where('user_id', $userId)
            ->orderBy('created_at', 'desc');
        
        if ($status) {
            $query->where('status', $status);
        }
        
        $total = $query->count();
        $list = $query->skip(($page - 1) * $pageSize)
            ->take($pageSize)
            ->get();
        
        return json([
            'code' => 0,
            'message' => 'ok',
            'data' => [
                'list' => $list,
                'total' => $total,
                'page' => $page,
                'page_size' => $pageSize
            ]
        ]);
    }
    
    
    public function show(Request $request, $orderNo)
    {
        $userId = $request->userId ?? 0;
        
        $order = Order::where('order_no', $orderNo)
            ->where('user_id', $userId)
            ->with('items')
            ->first();
        
        if (!$order) {
            return json(['code' => 404, 'message' => 'order_not_found', 'data' => null]);
        }
        
        return json([
            'code' => 0,
            'message' => 'ok',
            'data' => $order
        ]);
    }
    
    
    public function cancel(Request $request, $orderNo)
    {
        $userId = $request->userId ?? 0;
        
        $order = Order::where('order_no', $orderNo)
            ->where('user_id', $userId)
            ->first();
        
        if (!$order) {
            return json(['code' => 404, 'message' => 'order_not_found', 'data' => null]);
        }
        
        if ($order->status != 'pending') {
            return json(['code' => 400, 'message' => 'order_cannot_cancel', 'data' => null]);
        }
        
        try {
            \Illuminate\Support\Facades\DB::beginTransaction();
            
            
            $account = Account::where('user_id', $userId)->lockForUpdate()->first();
            $balanceBefore = $account->balance;
            $account->balance += $order->amount;
            $account->save();
            
            
            $order->status = 'canceled';
            $order->save();
            
            
            AccountTransaction::create([
                'user_id' => $userId,
                'type' => 'adjust',
                'amount' => $order->amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $account->balance,
                'ref_type' => 'order',
                'ref_id' => $order->id,
                'remark' => "撤单退款: {$orderNo}",
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            
            \Illuminate\Support\Facades\DB::commit();
            
            return json(['code' => 0, 'message' => 'ok', 'data' => null]);
            
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            throw $e;
        }
    }
    
    
    public function bets(Request $request)
    {
        try {
            
            $userId = $request->userId ?? 0;
            if (!$userId) {
                return json(['code' => 2001, 'message' => '未Đăng nhập']);
            }
            
            
            $lotteryType = $request->get('lotteryType', '');
            $status = $request->get('status', 'all');
            $page = max(1, (int)$request->get('page', 1));
            $pageSize = max(1, min(100, (int)$request->get('pageSize', 20)));
            
            
            $query = \support\Db::table('orders')
                ->where('user_id', $userId);
            
            
            if (!empty($lotteryType)) {
                
                if ($lotteryType === 'k3') {
                    $query->where('type_id', 'k3');
                }
            }
            
            
            if ($status !== 'all') {
                $query->where('status', $status);
            }
            
            
            $total = $query->count();
            
            
            $list = $query->orderBy('id', 'desc')
                ->offset(($page - 1) * $pageSize)
                ->limit($pageSize)
                ->get();
            
            
            $data = [];
            foreach ($list as $item) {
                
                $openCode = null;
                $winAmount = null;
                
                if ($item->status === 'won' || $item->status === 'lost') {
                    
                    
                    $winAmount = (float)$item->bonus;
                }
                
                $data[] = [
                    'orderId' => $item->order_no,
                    'lotteryCode' => $item->type_id,
                    'lotteryName' => $item->lottery_name,
                    'issue' => $item->expect,
                    'playId' => $item->play_id,
                    'playName' => $item->play_title,
                    'content' => $item->code,
                    'amount' => (float)$item->amount,
                    'odds' => '0',  
                    'status' => $item->status,
                    'createTime' => $item->placed_at,
                    'openCode' => $openCode,
                    'winAmount' => $winAmount
                ];
            }
            
            return json([
                'code' => 0,
                'data' => [
                    'total' => $total,
                    'page' => $page,
                    'pageSize' => $pageSize,
                    'list' => $data
                ]
            ]);
            
        } catch (\Exception $e) {
            \support\Log::error('LấyĐặt cượclịch sử thất bại: ' . $e->getMessage());
            return json(['code' => 9999, 'message' => 'Lỗi hệ thống']);
        }
    }
}

