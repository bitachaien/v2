<?php

namespace app\controller\api;

use support\Request;
use app\service\RebateService;

class RebateController
{
    protected RebateService $rebateService;
    
    public function __construct()
    {
        $this->rebateService = new RebateService();
    }

    
    public function list(Request $request)
    {
        $userId = $request->userId ?? 0;
        
        if (!$userId) {
            return json([
                'code' => 401,
                'message' => '未Đăng nhậphoặcĐăng nhập已过期',
                'data' => null
            ]);
        }
        
        $params = [
            'page' => $request->get('page', 1),
            'pageSize' => $request->get('pageSize', 10),
            'type' => $request->get('type', 0),  
            'status' => $request->get('status', ''),  
            'gametype' => $request->get('gametype', 0),
            'date' => $request->get('date', 0)  
        ];
        
        $result = $this->rebateService->getRebateList($userId, $params);
        
        return json([
            'code' => 0,
            'message' => 'Lấy dữ liệu thành công',
            'data' => $result
        ]);
    }

    
    public function summary(Request $request)
    {
        $userId = $request->userId ?? 0;
        
        if (!$userId) {
            return json([
                'code' => 401,
                'message' => '未Đăng nhậphoặcĐăng nhập已过期',
                'data' => null
            ]);
        }
        
        $type = intval($request->get('type', 0));  
        
        $stats = $this->rebateService->getUserRebateStats($userId, $type);
        
        return json([
            'code' => 0,
            'message' => 'Lấy dữ liệu thành công',
            'data' => $stats
        ]);
    }

    
    public function claim(Request $request)
    {
        $userId = $request->userId ?? 0;
        
        if (!$userId) {
            return json([
                'code' => 401,
                'message' => '未Đăng nhậphoặcĐăng nhập已过期',
                'data' => null
            ]);
        }
        
        $type = intval($request->post('type', 0));  
        $ids = $request->post('ids', []);  
        
        if ($type == 2) {
            
            $result = $this->rebateService->claimAgentRebate($userId, $ids);
        } else {
            
            $result = $this->claimPlayerRebate($userId, $ids);
        }
        
        return json($result);
    }

    
    protected function claimPlayerRebate(int $userId, array $ids = []): array
    {
        try {
            $query = \support\Db::table('caipiao_fanshui')
                ->where('uid', $userId)
                ->where('fanshui_type', 1)  
                ->where('can_claim', 1)
                ->where('shenhe', 0);
            
            if (!empty($ids)) {
                $query->whereIn('id', $ids);
            }
            
            $records = $query->get();
            
            if ($records->isEmpty()) {
                return ['code' => 400, 'message' => '暂无可领取的反水', 'data' => null];
            }
            
            $totalAmount = 0;
            $claimedIds = [];
            
            foreach ($records as $record) {
                $totalAmount += floatval($record->amount);
                $claimedIds[] = $record->id;
            }
            
            
            \support\Db::table('caipiao_fanshui')
                ->whereIn('id', $claimedIds)
                ->update(['shenhe' => 1]);
            
            
            $user = \support\Db::table('caipiao_member')->where('id', $userId)->first();
            if ($user) {
                \support\Db::table('caipiao_member')
                    ->where('id', $userId)
                    ->increment('balance', $totalAmount);
                
                \support\Db::table('caipiao_fuddetail')->insert([
                    'uid' => $userId,
                    'username' => $user->username,
                    'type' => 'fanshui',
                    'typename' => '玩家反水领取',
                    'trano' => 'PR' . date('YmdHis') . rand(1000, 9999),
                    'amount' => $totalAmount,
                    'before' => $user->balance,
                    'after' => $user->balance + $totalAmount,
                    'oddtime' => time(),
                    'remark' => '玩家反水领取，共 ' . count($claimedIds) . ' 笔'
                ]);
            }
            
            return [
                'code' => 0,
                'message' => '领取Thành công',
                'data' => [
                    'amount' => round($totalAmount, 2),
                    'count' => count($claimedIds)
                ]
            ];
            
        } catch (\Exception $e) {
            \support\Log::error('玩家反水领取Thất bại: ' . $e->getMessage());
            return ['code' => 500, 'message' => '领取Thất bại: ' . $e->getMessage(), 'data' => null];
        }
    }

    
    public function rates(Request $request)
    {
        $userId = $request->userId ?? 0;
        
        if (!$userId) {
            return json([
                'code' => 401,
                'message' => '未Đăng nhậphoặcĐăng nhập已过期',
                'data' => null
            ]);
        }
        
        try {
            $user = \support\Db::table('caipiao_member as m')
                ->leftJoin('caipiao_membergroup as g', 'm.groupid', '=', 'g.groupid')
                ->where('m.id', $userId)
                ->select('m.groupid', 'g.groupname', 
                    'g.fs_lottery', 'g.fs_realperson', 'g.fs_sport', 
                    'g.fs_esport', 'g.fs_chess', 'g.fs_electron', 'g.fs_fish')
                ->first();
            
            if (!$user) {
                return json(['code' => 404, 'message' => 'Người dùng không tồn tại', 'data' => null]);
            }
            
            $rates = [
                ['type' => 1, 'name' => 'Live Casino', 'rate' => floatval($user->fs_realperson)],
                ['type' => 2, 'name' => 'Bắn cá', 'rate' => floatval($user->fs_fish)],
                ['type' => 3, 'name' => 'Điện tử', 'rate' => floatval($user->fs_electron)],
                ['type' => 4, 'name' => 'Xổ số', 'rate' => floatval($user->fs_lottery)],
                ['type' => 5, 'name' => 'Thể thao', 'rate' => floatval($user->fs_sport)],
                ['type' => 6, 'name' => 'Bài', 'rate' => floatval($user->fs_chess)],
                ['type' => 7, 'name' => '电竞', 'rate' => floatval($user->fs_esport)],
            ];
            
            return json([
                'code' => 0,
                'message' => 'Lấy dữ liệu thành công',
                'data' => [
                    'vipLevel' => $user->groupname ?? 'VIP1',
                    'rates' => $rates
                ]
            ]);
            
        } catch (\Exception $e) {
            return json(['code' => 500, 'message' => 'Lấy dữ liệu thất bại: ' . $e->getMessage(), 'data' => null]);
        }
    }

    
    public function tierRates(Request $request)
    {
        try {
            $categoryCode = $request->get('category_code', 'slot');
            $vendorCode = $request->get('vendor_code', '');
            
            
            $platformNames = \support\Db::table('caipiao_game_platform')
                ->pluck('name', 'code')
                ->toArray();
            
            $list = [];
            
            if (!empty($vendorCode)) {
                
                $configs = \support\Db::table('caipiao_rebate_tier_config')
                    ->where('category_code', $categoryCode)
                    ->where('vendor_code', $vendorCode)
                    ->where('status', 1)
                    ->orderBy('min_bet')
                    ->get();
                
                
                if ($configs->isEmpty()) {
                    $configs = \support\Db::table('caipiao_rebate_tier_config')
                        ->where('category_code', $categoryCode)
                        ->where('vendor_code', '*')
                        ->where('status', 1)
                        ->orderBy('min_bet')
                        ->get();
                }
                
                foreach ($configs as $cfg) {
                    $list[] = [
                        'vendorCode' => $vendorCode,
                        'vendorName' => $platformNames[$vendorCode] ?? $vendorCode,
                        'minBet' => floatval($cfg->min_bet),
                        'rate' => floatval($cfg->rate)
                    ];
                }
            } else {
                
                $configs = \support\Db::table('caipiao_rebate_tier_config')
                    ->where('category_code', $categoryCode)
                    ->where('vendor_code', '*')
                    ->where('status', 1)
                    ->orderBy('min_bet')
                    ->get();
                
                foreach ($configs as $cfg) {
                    $list[] = [
                        'vendorCode' => '*',
                        'vendorName' => '通用',
                        'minBet' => floatval($cfg->min_bet),
                        'rate' => floatval($cfg->rate)
                    ];
                }
            }
            
            
            $vendorCodes = \support\Db::table('caipiao_rebate_tier_config')
                ->where('category_code', $categoryCode)
                ->where('status', 1)
                ->where('vendor_code', '!=', '*')
                ->distinct()
                ->pluck('vendor_code');
            
            $vendors = [];
            foreach ($vendorCodes as $code) {
                $vendors[] = [
                    'code' => $code,
                    'name' => $platformNames[$code] ?? $code
                ];
            }
            
            return json([
                'code' => 0,
                'message' => 'Lấy dữ liệu thành công',
                'data' => [
                    'list' => $list,
                    'vendors' => $vendors
                ]
            ]);
            
        } catch (\Exception $e) {
            return json(['code' => 500, 'message' => 'Lấy dữ liệu thất bại: ' . $e->getMessage(), 'data' => null]);
        }
    }
}

