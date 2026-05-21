<?php

namespace app\service;

use support\Db;
use support\Log;

class RebateService
{
    
    const MAX_AGENT_LEVEL = 5;
    
    
    const GAME_TYPE_MAP = [
        1 => 'fs_realperson',  
        2 => 'fs_fish',        
        3 => 'fs_electron',    
        4 => 'fs_lottery',     
        5 => 'fs_sport',       
        6 => 'fs_chess',       
        7 => 'fs_esport',      
    ];
    
    const GAME_TYPE_NAMES = [
        1 => 'Live Casino',
        2 => 'Bắn cá',
        3 => 'Điện tử',
        4 => 'Xổ số',
        5 => 'Thể thao',
        6 => 'Bài',
        7 => '电竞',
    ];

    
    public function processRebate(int $uid, float $money, string $platformType, string $betid, int $gametype): array
    {
        try {
            
            $playerRebate = $this->gameFanshui($uid, $money, $platformType, $betid, $gametype);
            
            
            $userInfo = Db::table('caipiao_member')->where('id', $uid)->first();
            if ($userInfo && $userInfo->parentid > 0) {
                $playerFee = $playerRebate['rate'] ?? 0;
                $this->agentFanshui($uid, $money, $playerFee, $platformType, $betid, self::MAX_AGENT_LEVEL, 1, $gametype);
            }
            
            return [
                'code' => 0,
                'message' => '反水处理Thành công',
                'data' => $playerRebate
            ];
        } catch (\Exception $e) {
            Log::error('反水处理Thất bại: ' . $e->getMessage());
            return [
                'code' => 500,
                'message' => '反水处理Thất bại: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    
    public function gameFanshui(int $uid, float $money, string $platformType, string $betid, int $gametype, string $categoryCode = ''): array
    {
        
        $userInfo = Db::table('caipiao_member as m')
            ->leftJoin('caipiao_membergroup as g', 'm.groupid', '=', 'g.groupid')
            ->where('m.id', $uid)
            ->select('m.*', 'g.*')
            ->first();
        
        if (!$userInfo) {
            return ['code' => 404, 'message' => 'Người dùng không tồn tại', 'rate' => 0, 'amount' => 0];
        }
        
        
        if (empty($categoryCode)) {
            $categoryCode = $this->gametypeToCategory($gametype);
        }
        
        
        $vendorCode = strtoupper($platformType);
        
        
        $this->updateBetStats($uid, $categoryCode, $vendorCode, $money);
        
        
        $fee = $this->getTierRebateRate($uid, $categoryCode, $vendorCode);
        
        
        $rebateAmount = round($fee * $money / 100, 2);
        
        if ($rebateAmount > 0) {
            
            $settleTime = strtotime(date('Y-m-d 00:00:00', strtotime('+1 day')));
            
            $orderNo = date('Ymd') . '_' . $uid . '_' . time() . '_' . rand(1000, 9999);
            
            $insertData = [
                'trano' => $orderNo,
                'listorder' => 0,
                'status' => 1,
                'addtime' => time(),
                'uid' => $uid,
                'username' => $userInfo->username,
                'groupname' => $userInfo->groupname ?? 'VIP1',
                'bili' => $fee,
                'touzhuedu' => $money,
                'amount' => $rebateAmount,
                'oddtime' => time(),
                'shenhe' => 0,  
                'yongjinfw' => '',
                'vendor_code' => $vendorCode,
                'category_code' => $categoryCode,
                'gametype' => $gametype,
                'betid' => $betid,
                'platform_type' => $platformType,
                'fanshui_type' => 1,  
                'parent_uid' => 0,
                'settle_time' => $settleTime,
                'can_claim' => 0  
            ];
            
            Db::table('caipiao_fanshui')->insert($insertData);
            
            Log::info('玩家反水lịch sử创建(阶梯模式)', [
                'uid' => $uid,
                'category' => $categoryCode,
                'vendor' => $vendorCode,
                'gametype' => $gametype,
                'money' => $money,
                'rate' => $fee,
                'rebate' => $rebateAmount
            ]);
        }
        
        return [
            'code' => 0,
            'rate' => $fee,
            'amount' => $rebateAmount,
            'gametype' => $gametype,
            'gametypeName' => self::GAME_TYPE_NAMES[$gametype] ?? '未知',
            'categoryCode' => $categoryCode,
            'vendorCode' => $vendorCode
        ];
    }

    
    public function agentFanshui(int $uid, float $money, float $fee, string $platformType, string $betid, int $settlementLevel, int $layer, int $gametype): void
    {
        if ($layer > $settlementLevel) {
            return;
        }
        
        $userInfo = Db::table('caipiao_member')->where('id', $uid)->first();
        if (!$userInfo || !$userInfo->parentid) {
            return;
        }
        
        
        $parentUser = Db::table('caipiao_member as m')
            ->leftJoin('caipiao_membergroup as g', 'm.groupid', '=', 'g.groupid')
            ->where('m.id', $userInfo->parentid)
            ->select('m.*', 'g.*')
            ->first();
        
        if (!$parentUser) {
            return;
        }
        
        
        $parentFee = floatval($parentUser->fanshuifee);
        if ($parentFee <= 0) {
            
            $parentFee = $this->getFanshuiRate($parentUser, $gametype);
            
            
            Db::table('caipiao_member')
                ->where('id', $parentUser->id)
                ->update(['fanshuifee' => $parentFee]);
        }
        
        
        $rebateAmount = round(($parentFee - $fee) * $money / 100, 2);
        
        if ($rebateAmount > 0) {
            
            $settleTime = strtotime(date('Y-m-d 00:00:00', strtotime('+1 day')));
            
            $orderNo = date('Ymd') . '_agent_' . $parentUser->id . '_' . time() . '_' . rand(1000, 9999);
            
            $insertData = [
                'trano' => $orderNo,
                'listorder' => 0,
                'status' => 1,
                'addtime' => time(),
                'uid' => $parentUser->id,
                'username' => $parentUser->username,
                'groupname' => $parentUser->groupname ?? 'VIP1',
                'bili' => $parentFee - $fee,  
                'touzhuedu' => $money,
                'amount' => $rebateAmount,
                'oddtime' => time(),
                'shenhe' => 0,  
                'yongjinfw' => '',
                'vendor_code' => '',
                'category_code' => '',
                'gametype' => $gametype,
                'betid' => $betid,
                'platform_type' => $platformType,
                'fanshui_type' => 2,  
                'parent_uid' => $uid,  
                'settle_time' => $settleTime,
                'can_claim' => 0
            ];
            
            Db::table('caipiao_fanshui')->insert($insertData);
            
            Log::info('Đại lý反水lịch sử创建', [
                'agent_uid' => $parentUser->id,
                'from_uid' => $uid,
                'layer' => $layer,
                'gametype' => $gametype,
                'money' => $money,
                'rate_diff' => $parentFee - $fee,
                'rebate' => $rebateAmount
            ]);
        }
        
        
        $layer++;
        $this->agentFanshui($parentUser->id, $money, $parentFee, $platformType, $betid, $settlementLevel, $layer, $gametype);
    }

    
    public function getTierRebateRate(int $uid, string $categoryCode, string $vendorCode): float
    {
        
        $totalBet = Db::table('caipiao_bet_stats')
            ->where('uid', $uid)
            ->where('category_code', $categoryCode)
            ->where('vendor_code', $vendorCode)
            ->value('total_bet') ?? 0;
        
        
        $tierConfig = Db::table('caipiao_rebate_tier_config')
            ->where('category_code', $categoryCode)
            ->where('vendor_code', $vendorCode)
            ->where('min_bet', '<=', $totalBet)
            ->where('status', 1)
            ->orderBy('min_bet', 'desc')
            ->first();
        
        if ($tierConfig) {
            return floatval($tierConfig->rate);
        }
        
        
        $defaultConfig = Db::table('caipiao_rebate_tier_config')
            ->where('category_code', $categoryCode)
            ->where('vendor_code', '*')
            ->where('min_bet', '<=', $totalBet)
            ->where('status', 1)
            ->orderBy('min_bet', 'desc')
            ->first();
        
        if ($defaultConfig) {
            return floatval($defaultConfig->rate);
        }
        
        
        return 0;
    }

    
    public function getFanshuiRate(object $userInfo, int $gametype): float
    {
        $fieldName = self::GAME_TYPE_MAP[$gametype] ?? null;
        
        if ($fieldName && isset($userInfo->$fieldName)) {
            return floatval($userInfo->$fieldName);
        }
        
        
        if (!empty($userInfo->fanshui)) {
            return $this->parseLegacyFanshui($userInfo->fanshui, 1000);  
        }
        
        return 0;
    }

    
    public function gametypeToCategory(int $gametype): string
    {
        $map = [
            1 => 'live',      
            2 => 'fishing',   
            3 => 'slot',      
            4 => 'lottery',   
            5 => 'sport',     
            6 => 'chess',     
            7 => 'esport',    
        ];
        return $map[$gametype] ?? 'slot';
    }

    
    public function updateBetStats(int $uid, string $categoryCode, string $vendorCode, float $betAmount): void
    {
        $now = time();
        
        
        $exists = Db::table('caipiao_bet_stats')
            ->where('uid', $uid)
            ->where('category_code', $categoryCode)
            ->where('vendor_code', $vendorCode)
            ->exists();
        
        if ($exists) {
            Db::table('caipiao_bet_stats')
                ->where('uid', $uid)
                ->where('category_code', $categoryCode)
                ->where('vendor_code', $vendorCode)
                ->increment('total_bet', $betAmount);
            
            Db::table('caipiao_bet_stats')
                ->where('uid', $uid)
                ->where('category_code', $categoryCode)
                ->where('vendor_code', $vendorCode)
                ->update(['updated_at' => $now]);
        } else {
            Db::table('caipiao_bet_stats')->insert([
                'uid' => $uid,
                'category_code' => $categoryCode,
                'vendor_code' => $vendorCode,
                'total_bet' => $betAmount,
                'created_at' => $now,
                'updated_at' => $now
            ]);
        }
    }

    
    protected function parseLegacyFanshui(string $config, float $betAmount): float
    {
        if (empty($config)) {
            return 0;
        }
        
        $segments = explode(';', $config);
        $matchedRate = 0;
        
        foreach ($segments as $seg) {
            $seg = trim($seg);
            if (empty($seg)) continue;
            
            $parts = explode('|', $seg);
            if (count($parts) !== 2) continue;
            
            $range = explode('-', $parts[0]);
            if (count($range) !== 2) continue;
            
            $min = floatval(trim($range[0]));
            $max = floatval(trim($range[1]));
            $rate = floatval(trim($parts[1]));
            
            if ($betAmount >= $min && $betAmount < $max) {
                return $rate;
            }
            
            if ($betAmount >= $max) {
                $matchedRate = $rate;
            }
        }
        
        return $matchedRate;
    }

    
    public function settleRebates(): array
    {
        $now = time();
        
        
        $affected = Db::table('caipiao_fanshui')
            ->where('settle_time', '<=', $now)
            ->where('settle_time', '>', 0)
            ->where('can_claim', 0)
            ->where('shenhe', 0)
            ->update([
                'can_claim' => 1
            ]);
        
        Log::info('T+1反水结算完成', ['affected' => $affected]);
        
        return [
            'code' => 0,
            'message' => '结算完成',
            'data' => ['affected' => $affected]
        ];
    }

    
    public function claimAgentRebate(int $uid, array $ids = []): array
    {
        try {
            $query = Db::table('caipiao_fanshui')
                ->where('uid', $uid)
                ->where('fanshui_type', 2)  
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
            
            
            Db::table('caipiao_fanshui')
                ->whereIn('id', $claimedIds)
                ->update(['shenhe' => 1]);
            
            
            $user = Db::table('caipiao_member')->where('id', $uid)->first();
            if ($user) {
                Db::table('caipiao_member')
                    ->where('id', $uid)
                    ->increment('balance', $totalAmount);
                
                
                Db::table('caipiao_fuddetail')->insert([
                    'uid' => $uid,
                    'username' => $user->username,
                    'type' => 'fanshui',
                    'typename' => 'Đại lý反水领取',
                    'trano' => 'AR' . date('YmdHis') . rand(1000, 9999),
                    'amount' => $totalAmount,
                    'before' => $user->balance,
                    'after' => $user->balance + $totalAmount,
                    'oddtime' => time(),
                    'remark' => 'Đại lý反水领取，共 ' . count($claimedIds) . ' 笔'
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
            Log::error('Đại lý反水领取Thất bại: ' . $e->getMessage());
            return ['code' => 500, 'message' => '领取Thất bại: ' . $e->getMessage(), 'data' => null];
        }
    }

    
    public function getUserRebateStats(int $uid, int $type = 0): array
    {
        $query = Db::table('caipiao_fanshui')->where('uid', $uid);
        
        if ($type > 0) {
            $query->where('fanshui_type', $type);
        }
        
        
        $pending = (clone $query)->where('shenhe', 0)->where('can_claim', 0)->sum('amount') ?? 0;
        
        
        $claimable = (clone $query)->where('shenhe', 0)->where('can_claim', 1)->sum('amount') ?? 0;
        
        
        $claimed = (clone $query)->where('shenhe', 1)->sum('amount') ?? 0;
        
        return [
            'pending' => round($pending, 2),
            'claimable' => round($claimable, 2),
            'claimed' => round($claimed, 2),
            'total' => round($pending + $claimable + $claimed, 2)
        ];
    }

    
    public function getRebateList(int $uid, array $params = []): array
    {
        $page = intval($params['page'] ?? 1);
        $pageSize = intval($params['pageSize'] ?? 10);
        $type = intval($params['type'] ?? 0);  
        $status = $params['status'] ?? '';  
        $gametype = intval($params['gametype'] ?? 0);
        $dateRange = intval($params['date'] ?? 0);  
        
        $query = Db::table('caipiao_fanshui')->where('uid', $uid);
        
        if ($type > 0) {
            $query->where('fanshui_type', $type);
        }
        
        if ($gametype > 0) {
            $query->where('gametype', $gametype);
        }
        
        if ($status === 'pending') {
            $query->where('shenhe', 0)->where('can_claim', 0);
        } elseif ($status === 'claimable') {
            $query->where('shenhe', 0)->where('can_claim', 1);
        } elseif ($status === 'claimed') {
            $query->where('shenhe', 1);
        }
        
        
        if ($dateRange > 0) {
            $days = [1 => 0, 2 => 7, 3 => 15, 4 => 30][$dateRange] ?? 0;
            if ($dateRange == 1) {
                $start = strtotime(date('Y-m-d 00:00:00'));
            } else {
                $start = strtotime("-{$days} days 00:00:00");
            }
            $query->where('oddtime', '>=', $start);
        }
        
        $total = $query->count();
        
        $list = $query->orderBy('id', 'desc')
            ->offset(($page - 1) * $pageSize)
            ->limit($pageSize)
            ->get();
        
        $items = [];
        foreach ($list as $item) {
            $items[] = [
                'id' => $item->id,
                'trano' => $item->trano,
                'gametype' => $item->gametype,
                'gametypeName' => self::GAME_TYPE_NAMES[$item->gametype] ?? '未知',
                'platformType' => $item->platform_type,
                'touzhuedu' => floatval($item->touzhuedu),
                'bili' => floatval($item->bili),
                'amount' => floatval($item->amount),
                'fanshuiType' => intval($item->fanshui_type),
                'fanshuiTypeName' => $item->fanshui_type == 1 ? '玩家反水' : 'Đại lý反水',
                'shenhe' => intval($item->shenhe),
                'canClaim' => intval($item->can_claim),
                'statusName' => $this->getStatusName($item),
                'oddtime' => date('Y-m-d H:i:s', $item->oddtime),
                'settleTime' => $item->settle_time > 0 ? date('Y-m-d H:i:s', $item->settle_time) : ''
            ];
        }
        
        return [
            'total' => $total,
            'page' => $page,
            'pageSize' => $pageSize,
            'list' => $items
        ];
    }

    protected function getStatusName(object $item): string
    {
        if ($item->shenhe == 1) {
            return '已结算';
        }
        if ($item->shenhe == 2) {
            return 'Đã từ chối';
        }
        if ($item->can_claim == 1) {
            return '可领取';
        }
        return '待结算';
    }
}
