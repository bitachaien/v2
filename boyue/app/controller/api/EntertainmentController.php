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
                    'name' => '开元棋牌',
                    'category' => 'chess',
                    'desc' => '经典棋牌,畅玩不停',
                    'image' => '/images/entertainment/kaiyuan.png',
                    'status' => 1,
                    'sort' => 1
                ],
                [
                    'id' => 2,
                    'code' => 'ag',
                    'name' => 'AG真人',
                    'category' => 'live',
                    'desc' => '美女荷官,真人互动',
                    'image' => '/images/entertainment/ag.png',
                    'status' => 1,
                    'sort' => 1
                ],
                [
                    'id' => 3,
                    'code' => 'bbin',
                    'name' => 'BBIN真人',
                    'category' => 'live',
                    'desc' => '真人视讯,精彩刺激',
                    'image' => '/images/entertainment/bbin.png',
                    'status' => 1,
                    'sort' => 2
                ],
                [
                    'id' => 4,
                    'code' => 'crown',
                    'name' => '皇冠体育',
                    'category' => 'sports',
                    'desc' => '全球赛事,实时投注',
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
            \support\Log::error('获取游戏平台列表失败: ' . $e->getMessage());
            return json([
                'code' => 500,
                'sign' => false,
                'message' => '获取数据失败：' . $e->getMessage(),
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
                    'message' => '请先登录',
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
            \support\Log::error('进入游戏失败: ' . $e->getMessage());
            return json([
                'code' => 500,
                'sign' => false,
                'message' => '进入游戏失败：' . $e->getMessage(),
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
                    'message' => '请先登录',
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
            \support\Log::error('获取游戏余额失败: ' . $e->getMessage());
            return json([
                'code' => 500,
                'sign' => false,
                'message' => '获取余额失败：' . $e->getMessage(),
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
                    'message' => '请先登录',
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
                    'message' => '参数不完整或金额无效',
                    'data' => null
                ]);
            }

            
            
            
            
            
            
            $orderId = 'ZZ' . date('YmdHis') . rand(1000, 9999);

            return json([
                'code' => 0,
                'sign' => true,
                'message' => '转入成功',
                'data' => [
                    'order_id' => $orderId,
                    'amount' => $amount,
                    'main_balance' => 900.00,
                    'platform_balance' => 1000.00 + $amount
                ]
            ]);
            
        } catch (\Exception $e) {
            \support\Log::error('转账失败: ' . $e->getMessage());
            return json([
                'code' => 500,
                'sign' => false,
                'message' => '转账失败：' . $e->getMessage(),
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
                    'message' => '请先登录',
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
                    'message' => '参数不完整或金额无效',
                    'data' => null
                ]);
            }

            
            
            
            
            
            $orderId = 'ZZ' . date('YmdHis') . rand(1000, 9999);

            return json([
                'code' => 0,
                'sign' => true,
                'message' => '转出成功',
                'data' => [
                    'order_id' => $orderId,
                    'amount' => $amount,
                    'main_balance' => 1000.00 + $amount,
                    'platform_balance' => 1000.00 - $amount
                ]
            ]);
            
        } catch (\Exception $e) {
            \support\Log::error('转账失败: ' . $e->getMessage());
            return json([
                'code' => 500,
                'sign' => false,
                'message' => '转账失败：' . $e->getMessage(),
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
                    'message' => '请先登录',
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
                'message' => '回收成功',
                'data' => [
                    'platforms' => $platforms,
                    'total_amount' => $totalAmount,
                    'main_balance' => 1000.00 + $totalAmount
                ]
            ]);
            
        } catch (\Exception $e) {
            \support\Log::error('一键回收失败: ' . $e->getMessage());
            return json([
                'code' => 500,
                'sign' => false,
                'message' => '回收失败：' . $e->getMessage(),
                'data' => null
            ]);
        }
    }
}

