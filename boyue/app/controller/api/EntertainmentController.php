<?php

namespace app\controller\api;

use support\Request;
use support\Db;

class EntertainmentController
{
    
    public function platforms(Request $request)
    {
        try {
            $category = $request->get('category'); 
            
            
            $allPlatforms = [
                [
                    'id' => 1,
                    'code' => 'kaiyuan',
                    'name' => '开元Bài',
                    'category' => 'chess',
                    'desc' => '经典Bài,畅玩不停',
                    'image' => '/images/entertainment/kaiyuan.png',
                    'status' => 1,
                    'sort' => 1
                ],
                [
                    'id' => 2,
                    'code' => 'ag',
                    'name' => 'AGLive Casino',
                    'category' => 'live',
                    'desc' => '美女荷官,Live Casino互动',
                    'image' => '/images/entertainment/ag.png',
                    'status' => 1,
                    'sort' => 1
                ],
                [
                    'id' => 3,
                    'code' => 'bbin',
                    'name' => 'BBINLive Casino',
                    'category' => 'live',
                    'desc' => 'Live Casino视讯,精彩刺激',
                    'image' => '/images/entertainment/bbin.png',
                    'status' => 1,
                    'sort' => 2
                ],
                [
                    'id' => 4,
                    'code' => 'crown',
                    'name' => '皇冠Thể thao',
                    'category' => 'sports',
                    'desc' => '全球赛事,实时Đặt cược',
                    'image' => '/images/entertainment/crown.png',
                    'status' => 1,
                    'sort' => 1
                ],
            ];
            
            
            if ($category) {
                $platforms = array_filter($allPlatforms, function($platform) use ($category) {
                    return $platform['category'] === $category && $platform['status'] === 1;
                });
                $platforms = array_values($platforms);
            } else {
                $platforms = array_filter($allPlatforms, function($platform) {
                    return $platform['status'] === 1;
                });
                $platforms = array_values($platforms);
            }
            
            return json([
                'code' => 0,
                'sign' => true,
                'message' => 'ok',
                'data' => [
                    'platforms' => $platforms
                ]
            ]);
            
        } catch (\Exception $e) {
            \support\Log::error('Lấy trò chơidanh sách nền tảngThất bại: ' . $e->getMessage());
            return json([
                'code' => 500,
                'sign' => false,
                'message' => 'Lấy dữ liệuThất bại：' . $e->getMessage(),
                'data' => null
            ]);
        }
    }

    
    public function enter(Request $request)
    {
        try {
            
            if (!isset($request->userId)) {
                return json([
                    'code' => 401,
                    'sign' => false,
                    'message' => 'Vui lòng đăng nhập',
                    'data' => null
                ]);
            }

            $userId = $request->userId;
            $platformCode = $request->post('platform_code');
            $gameCode = $request->post('game_code', 'live');
            $device = $request->post('device', 'mobile');

            if (!$platformCode) {
                return json([
                    'code' => 400,
                    'sign' => false,
                    'message' => '参数不完整',
                    'data' => null
                ]);
            }

            
            
            $token = md5($userId . $platformCode . time());
            $gameUrl = "https://{$platformCode}.example.com/game?token={$token}&lang=zh-cn&device={$device}";

            return json([
                'code' => 0,
                'sign' => true,
                'message' => 'ok',
                'data' => [
                    'type' => 'url',
                    'url' => $gameUrl,
                    'method' => 'GET',
                    'expires_at' => time() + 3600
                ]
            ]);
            
        } catch (\Exception $e) {
            \support\Log::error('进入Trò chơiThất bại: ' . $e->getMessage());
            return json([
                'code' => 500,
                'sign' => false,
                'message' => '进入Trò chơiThất bại：' . $e->getMessage(),
                'data' => null
            ]);
        }
    }

    
    public function balance(Request $request)
    {
        try {
            
            if (!isset($request->userId)) {
                return json([
                    'code' => 401,
                    'sign' => false,
                    'message' => 'Vui lòng đăng nhập',
                    'data' => null
                ]);
            }

            $userId = $request->userId;
            $platformCode = $request->get('platform_code');

            if (!$platformCode) {
                return json([
                    'code' => 400,
                    'sign' => false,
                    'message' => '参数不完整',
                    'data' => null
                ]);
            }

            
            
            $balance = 1000.00;

            return json([
                'code' => 0,
                'sign' => true,
                'message' => 'ok',
                'data' => [
                    'platform' => $platformCode,
                    'balance' => $balance,
                    'currency' => 'CNY'
                ]
            ]);
            
        } catch (\Exception $e) {
            \support\Log::error('Lấy trò chơiSố dưThất bại: ' . $e->getMessage());
            return json([
                'code' => 500,
                'sign' => false,
                'message' => 'Lấy số dưThất bại：' . $e->getMessage(),
                'data' => null
            ]);
        }
    }

    
    public function transferIn(Request $request)
    {
        try {
            
            if (!isset($request->userId)) {
                return json([
                    'code' => 401,
                    'sign' => false,
                    'message' => 'Vui lòng đăng nhập',
                    'data' => null
                ]);
            }

            $userId = $request->userId;
            $platformCode = $request->post('platform_code');
            $amount = $request->post('amount');

            if (!$platformCode || !$amount || $amount <= 0) {
                return json([
                    'code' => 400,
                    'sign' => false,
                    'message' => '参数不完整hoặcSố tiền无效',
                    'data' => null
                ]);
            }

            
            
            
            
            
            
            $orderId = 'ZZ' . date('YmdHis') . rand(1000, 9999);

            return json([
                'code' => 0,
                'sign' => true,
                'message' => '转入Thành công',
                'data' => [
                    'order_id' => $orderId,
                    'amount' => $amount,
                    'main_balance' => 900.00,
                    'platform_balance' => 1000.00 + $amount
                ]
            ]);
            
        } catch (\Exception $e) {
            \support\Log::error('Chuyển khoảnThất bại: ' . $e->getMessage());
            return json([
                'code' => 500,
                'sign' => false,
                'message' => 'Chuyển khoảnThất bại：' . $e->getMessage(),
                'data' => null
            ]);
        }
    }

    
    public function transferOut(Request $request)
    {
        try {
            
            if (!isset($request->userId)) {
                return json([
                    'code' => 401,
                    'sign' => false,
                    'message' => 'Vui lòng đăng nhập',
                    'data' => null
                ]);
            }

            $userId = $request->userId;
            $platformCode = $request->post('platform_code');
            $amount = $request->post('amount');

            if (!$platformCode || !$amount || $amount <= 0) {
                return json([
                    'code' => 400,
                    'sign' => false,
                    'message' => '参数不完整hoặcSố tiền无效',
                    'data' => null
                ]);
            }

            
            
            
            
            
            $orderId = 'ZZ' . date('YmdHis') . rand(1000, 9999);

            return json([
                'code' => 0,
                'sign' => true,
                'message' => '转出Thành công',
                'data' => [
                    'order_id' => $orderId,
                    'amount' => $amount,
                    'main_balance' => 1000.00 + $amount,
                    'platform_balance' => 1000.00 - $amount
                ]
            ]);
            
        } catch (\Exception $e) {
            \support\Log::error('Chuyển khoảnThất bại: ' . $e->getMessage());
            return json([
                'code' => 500,
                'sign' => false,
                'message' => 'Chuyển khoảnThất bại：' . $e->getMessage(),
                'data' => null
            ]);
        }
    }

    
    public function transferAllOut(Request $request)
    {
        try {
            
            if (!isset($request->userId)) {
                return json([
                    'code' => 401,
                    'sign' => false,
                    'message' => 'Vui lòng đăng nhập',
                    'data' => null
                ]);
            }

            $userId = $request->userId;

            
            
            
            
            
            $platforms = [
                ['platform' => 'ag', 'amount' => 100.00, 'success' => true],
                ['platform' => 'bbin', 'amount' => 50.00, 'success' => true],
            ];
            
            $totalAmount = array_sum(array_column($platforms, 'amount'));

            return json([
                'code' => 0,
                'sign' => true,
                'message' => '回收Thành công',
                'data' => [
                    'platforms' => $platforms,
                    'total_amount' => $totalAmount,
                    'main_balance' => 1000.00 + $totalAmount
                ]
            ]);
            
        } catch (\Exception $e) {
            \support\Log::error('一键回收Thất bại: ' . $e->getMessage());
            return json([
                'code' => 500,
                'sign' => false,
                'message' => '回收Thất bại：' . $e->getMessage(),
                'data' => null
            ]);
        }
    }
}

