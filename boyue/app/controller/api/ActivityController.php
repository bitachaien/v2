<?php

namespace app\controller\api;

use support\Request;
use support\Db;

class ActivityController
{
    
    public function categories(Request $request)
    {
        try {
            
            $list = Db::table('caipiao_activity_category')
                ->where('status', 1)
                ->orderBy('sort', 'desc')
                ->orderBy('id', 'asc')
                ->select('id', 'name', 'code', 'icon', 'status', 'sort')
                ->get();

            return json([
                'code' => 0,
                'msg' => 'Lấy dữ liệu thành công',
                'data' => $list
            ]);
        } catch (\Exception $e) {
            \support\Log::error('LấyHoạt động分类Thất bại: ' . $e->getMessage());
            return json([
                'code' => 500,
                'msg' => 'Lấy dữ liệuThất bại：' . $e->getMessage(),
                'data' => []
            ]);
        }
    }

    
    public function list(Request $request)
    {
        try {
            
            $query = Db::table('caipiao_huodong');
            
            
            $query->where('status', 1);
            
            
            $activities = $query->orderBy('sort', 'desc')
                ->orderBy('id', 'desc')
                ->get();
            
            $list = [];
            $now = time();
            
            foreach ($activities as $activity) {
                
                $status = 'ongoing';  
                if ($activity->end_time && $activity->end_time < $now) {
                    $status = 'expired';  
                } elseif ($activity->start_time && $activity->start_time > $now) {
                    $status = 'upcoming';  
                }
                
                
                $category = $activity->category ?? '';
                if (!empty($category)) {
                    $decoded = json_decode($category, true);
                    if (is_array($decoded)) {
                        $categoryArr = $decoded;
                    } elseif (strpos($category, ',') !== false) {
                        
                        $categoryArr = array_map('trim', explode(',', $category));
                    } else {
                        $categoryArr = [$category];
                    }
                } else {
                    $categoryArr = [];
                }
                
                $list[] = [
                    'id' => $activity->id,
                    'title' => $activity->title ?? '',
                    'desc' => $activity->desc ?? '',
                    'startDate' => $activity->start_date ?? '',
                    'endDate' => $activity->end_date ?? '',
                    'status' => $status,
                    'banner' => $activity->banner ?? '',
                    'type' => $activity->type ?? 'other',
                    'type_code' => $activity->type_code ?? $activity->type ?? 'other',
                    'category' => $categoryArr,
                    'sort' => $activity->sort ?? 0,
                    'jump_type' => $activity->jump_type ?? 0,
                    'jump_url' => $activity->jump_url ?? ''
                ];
            }
            
            return json([
                'code' => 0,
                'message' => 'Lấy dữ liệu thành công',
                'data' => [
                    'list' => $list
                ]
            ]);
            
        } catch (\Exception $e) {
            \support\Log::error('LấyHoạt động列表Thất bại: ' . $e->getMessage());
            return json([
                'code' => 500,
                'message' => 'Lấy dữ liệuThất bại：' . $e->getMessage(),
                'data' => null
            ]);
        }
    }
    
    
    public function dailyReward(Request $request)
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
            
            $user = Db::table('caipiao_member as m')
                ->leftJoin('caipiao_membergroup as g', 'm.groupid', '=', 'g.groupid')
                ->where('m.id', $userId)
                ->select('m.*', 'g.groupname', 'g.fanshui as fanshui_config')
                ->first();
            
            if (!$user) {
                return json([
                    'code' => 404,
                    'message' => 'Người dùng không tồn tại',
                    'data' => null
                ]);
            }
            
            
            $yesterday = strtotime(date('Y-m-d 00:00:00', strtotime('-1 day')));
            $todayStart = strtotime(date('Y-m-d 00:00:00'));
            
            $betAmount = Db::table('caipiao_touzhu')
                ->where('uid', $userId)
                ->where('oddtime', '>=', $yesterday)
                ->where('oddtime', '<', $todayStart)
                ->sum('amount') ?? 0;
            
            
            $fanshuibili = $this->parseFanshuiRate($user->fanshui_config ?? '', $betAmount);
            
            
            $jiajiang = $betAmount * ($fanshuibili / 100);
            
            
            $todayRecord = Db::table('caipiao_fanshui')
                ->where('uid', $userId)
                ->where('oddtime', '>=', $todayStart)
                ->first();
            
            $canClaim = !$todayRecord && $jiajiang > 0;
            
            return json([
                'code' => 0,
                'message' => 'Lấy dữ liệu thành công',
                'data' => [
                    'countamount' => number_format($betAmount, 2, '.', ''),
                    'fanshuibili' => (string)$fanshuibili,
                    'jiajiang' => number_format($jiajiang, 2, '.', ''),
                    'userLevel' => $user->groupname ?? 'VIP0',
                    'userLevelId' => $user->groupid ?? 0,
                    'canClaim' => $canClaim
                ]
            ]);
            
        } catch (\Exception $e) {
            \support\Log::error('Lấy每日加奖信息Thất bại: ' . $e->getMessage());
            return json([
                'code' => 500,
                'message' => 'Lấy dữ liệuThất bại：' . $e->getMessage(),
                'data' => null
            ]);
        }
    }
    
    
    public function levelReward(Request $request)
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
            
            $user = Db::table('caipiao_member as m')
                ->leftJoin('caipiao_membergroup as g', 'm.groupid', '=', 'g.groupid')
                ->where('m.id', $userId)
                ->select('m.*', 'g.groupname')
                ->first();
            
            if (!$user) {
                return json([
                    'code' => 404,
                    'message' => 'Người dùng không tồn tại',
                    'data' => null
                ]);
            }
            
            $currentLevelId = $user->groupid ?? 0;
            $currentLevel = $user->groupname ?? 'VIP0';
            
            
            $levelConfig = [
                1 => ['name' => 'VIP1', 'next' => 'VIP2', 'reward' => 18, 'required' => 1000],
                2 => ['name' => 'VIP2', 'next' => 'VIP3', 'reward' => 38, 'required' => 5000],
                3 => ['name' => 'VIP3', 'next' => 'VIP4', 'reward' => 58, 'required' => 10000],
                4 => ['name' => 'VIP4', 'next' => 'VIP5', 'reward' => 88, 'required' => 50000],
                5 => ['name' => 'VIP5', 'next' => 'VIP6', 'reward' => 128, 'required' => 100000],
                6 => ['name' => 'VIP6', 'next' => 'VIP7', 'reward' => 188, 'required' => 200000],
                7 => ['name' => 'VIP7', 'next' => 'VIP8', 'reward' => 288, 'required' => 500000],
                8 => ['name' => 'VIP8', 'next' => 'VIP9', 'reward' => 588, 'required' => 1000000],
                9 => ['name' => 'VIP9', 'next' => 'VIP10', 'reward' => 888, 'required' => 2000000],
            ];
            
            $config = $levelConfig[$currentLevelId] ?? ['name' => $currentLevel, 'next' => 'VIP' . ($currentLevelId + 1), 'reward' => 18, 'required' => 1000];
            
            
            $totalBet = Db::table('caipiao_touzhu')
                ->where('uid', $userId)
                ->sum('amount') ?? 0;
            
            $progress = min(100, ($totalBet / $config['required']) * 100);
            
            
            $canClaim = $user->jinjijilu > 0;
            
            return json([
                'code' => 0,
                'message' => 'Lấy dữ liệu thành công',
                'data' => [
                    'currentLevel' => $currentLevel,
                    'currentLevelId' => $currentLevelId,
                    'nextLevel' => $config['next'],
                    'rewardAmount' => number_format($config['reward'], 2, '.', ''),
                    'canClaim' => $canClaim,
                    'progress' => round($progress, 2)
                ]
            ]);
            
        } catch (\Exception $e) {
            \support\Log::error('LấyThăng cấp奖励信息Thất bại: ' . $e->getMessage());
            return json([
                'code' => 500,
                'message' => 'Lấy dữ liệuThất bại：' . $e->getMessage(),
                'data' => null
            ]);
        }
    }
    
    
    public function claimDailyReward(Request $request)
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
            
            $user = Db::table('caipiao_member as m')
                ->leftJoin('caipiao_membergroup as g', 'm.groupid', '=', 'g.groupid')
                ->where('m.id', $userId)
                ->select('m.*', 'g.groupname', 'g.fanshui as fanshui_config')
                ->first();
            
            if (!$user) {
                return json([
                    'code' => 404,
                    'message' => 'Người dùng không tồn tại',
                    'data' => null
                ]);
            }
            
            
            $yesterday = strtotime(date('Y-m-d 00:00:00', strtotime('-1 day')));
            $todayStart = strtotime(date('Y-m-d 00:00:00'));
            
            $betAmount = Db::table('caipiao_touzhu')
                ->where('uid', $userId)
                ->where('oddtime', '>=', $yesterday)
                ->where('oddtime', '<', $todayStart)
                ->sum('amount') ?? 0;
            
            if ($betAmount <= 0) {
                return json([
                    'code' => 400,
                    'message' => 'Hôm qua无Đặt cược，无法领取加奖',
                    'data' => null
                ]);
            }
            
            
            $todayRecord = Db::table('caipiao_fanshui')
                ->where('uid', $userId)
                ->where('oddtime', '>=', $todayStart)
                ->first();
            
            if ($todayRecord) {
                return json([
                    'code' => 400,
                    'message' => 'Hôm nay已领取，请明日再来',
                    'data' => null
                ]);
            }
            
            
            $fanshuibili = $this->parseFanshuiRate($user->fanshui_config ?? '', $betAmount);
            $amount = $betAmount * ($fanshuibili / 100);
            
            if ($amount <= 0) {
                return json([
                    'code' => 400,
                    'message' => '加奖Số tiền不足，无法领取',
                    'data' => null
                ]);
            }
            
            
            $orderId = 'DR' . date('YmdHis') . rand(1000, 9999);
            
            
            Db::table('caipiao_fanshui')->insert([
                'trano' => $orderId,
                'uid' => $userId,
                'username' => $user->username,
                'groupname' => $user->groupname ?? 'VIP0',
                'bili' => $fanshuibili,
                'touzhuedu' => $betAmount,
                'amount' => $amount,
                'oddtime' => time(),
                'addtime' => time(),
                'shenhe' => 1, 
                'status' => 1
            ]);
            
            
            Db::table('caipiao_member')
                ->where('id', $userId)
                ->increment('balance', $amount);
            
            
            Db::table('caipiao_fuddetail')->insert([
                'uid' => $userId,
                'username' => $user->username,
                'type' => 'fanshui',
                'typename' => '每日加奖',
                'trano' => $orderId,
                'amount' => $amount,
                'before' => $user->balance,
                'after' => $user->balance + $amount,
                'oddtime' => time(),
                'remark' => "Hôm quaĐặt cược{$betAmount}元，加奖比例{$fanshuibili}%"
            ]);
            
            return json([
                'code' => 0,
                'message' => '领取Thành công',
                'data' => [
                    'amount' => number_format($amount, 2, '.', ''),
                    'orderId' => $orderId
                ]
            ]);
            
        } catch (\Exception $e) {
            \support\Log::error('领取每日加奖Thất bại: ' . $e->getMessage());
            return json([
                'code' => 500,
                'message' => '领取Thất bại：' . $e->getMessage(),
                'data' => null
            ]);
        }
    }
    
    
    public function claimLevelReward(Request $request)
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
            
            $user = Db::table('caipiao_member as m')
                ->leftJoin('caipiao_membergroup as g', 'm.groupid', '=', 'g.groupid')
                ->where('m.id', $userId)
                ->select('m.*', 'g.groupname')
                ->first();
            
            if (!$user) {
                return json([
                    'code' => 404,
                    'message' => 'Người dùng không tồn tại',
                    'data' => null
                ]);
            }
            
            
            if (!$user->jinjijilu || $user->jinjijilu <= 0) {
                return json([
                    'code' => 400,
                    'message' => '暂无可领取的Thăng cấp奖励',
                    'data' => null
                ]);
            }
            
            
            $amount = (float)$user->jinjijilu;
            
            
            $orderId = 'LR' . date('YmdHis') . rand(1000, 9999);
            
            
            Db::table('caipiao_member')
                ->where('id', $userId)
                ->update([
                    'balance' => $user->balance + $amount,
                    'jinjijilu' => 0  
                ]);
            
            
            Db::table('caipiao_fuddetail')->insert([
                'uid' => $userId,
                'username' => $user->username,
                'type' => 'jinjishenhe',
                'typename' => 'Thăng cấp奖励',
                'trano' => $orderId,
                'amount' => $amount,
                'before' => $user->balance,
                'after' => $user->balance + $amount,
                'oddtime' => time(),
                'remark' => "Thăng cấp至{$user->groupname}奖励"
            ]);
            
            return json([
                'code' => 0,
                'message' => '领取Thành công',
                'data' => [
                    'amount' => number_format($amount, 2, '.', ''),
                    'orderId' => $orderId
                ]
            ]);
            
        } catch (\Exception $e) {
            \support\Log::error('领取Thăng cấp奖励Thất bại: ' . $e->getMessage());
            return json([
                'code' => 500,
                'message' => '领取Thất bại：' . $e->getMessage(),
                'data' => null
            ]);
        }
    }
    
    
    public function rewardHistory(Request $request)
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
            $page = $request->get('page', 1);
            $pageSize = $request->get('pageSize', 20);
            
            $query = Db::table('caipiao_fanshui')
                ->where('uid', $userId);
            
            $total = $query->count();
            
            $list = $query->orderBy('oddtime', 'desc')
                ->offset(($page - 1) * $pageSize)
                ->limit($pageSize)
                ->get();
            
            $data = [];
            foreach ($list as $item) {
                $data[] = [
                    'id' => $item->id,
                    'groupname' => $item->groupname ?? '',
                    'touzhuedu' => number_format($item->touzhuedu ?? 0, 2, '.', ''),
                    'bili' => (string)($item->bili ?? 0),
                    'amount' => number_format($item->amount ?? 0, 2, '.', ''),
                    'oddtime' => $item->oddtime ?? time(),
                    'shenhe' => $item->shenhe ?? 0
                ];
            }
            
            return json([
                'code' => 0,
                'message' => 'Lấy dữ liệu thành công',
                'data' => [
                    'total' => $total,
                    'page' => (int)$page,
                    'pageSize' => (int)$pageSize,
                    'list' => $data
                ]
            ]);
            
        } catch (\Exception $e) {
            \support\Log::error('Lấy加奖lịch sử thất bại: ' . $e->getMessage());
            return json([
                'code' => 500,
                'message' => 'Lấy dữ liệuThất bại：' . $e->getMessage(),
                'data' => null
            ]);
        }
    }
    
    
    public function detail(Request $request, $id)
    {
        try {
            
            $activity = Db::table('caipiao_huodong')
                ->where('id', $id)
                ->first();
            
            if (!$activity) {
                return json([
                    'code' => 404,
                    'message' => 'Hoạt độngkhông tồn tại',
                    'data' => null
                ]);
            }
            
            
            $now = time();
            $status = 'ongoing';
            if ($activity->end_time && $activity->end_time < $now) {
                $status = 'expired';
            } elseif ($activity->start_time && $activity->start_time > $now) {
                $status = 'upcoming';
            }
            
            
            $images = [];
            if ($activity->images) {
                $decoded = json_decode($activity->images, true);
                $images = is_array($decoded) ? $decoded : [];
            }
            
            
            $rewards = Db::table('caipiao_activity_reward')
                ->where('activity_id', $id)
                ->where('status', 1)
                ->orderBy('sort', 'asc')
                ->get();
            
            $rewardsData = [];
            foreach ($rewards as $reward) {
                $rewardsData[] = [
                    'id' => $reward->id,
                    'rewardType' => $reward->reward_type ?? '',
                    'levelName' => $reward->level_name ?? '',
                    'conditionMin' => $reward->condition_min ?? 0,
                    'conditionMax' => $reward->condition_max ?? 0,
                    'rewardAmount' => $reward->reward_amount ?? 0,
                    'rewardRate' => $reward->reward_rate ?? 0,
                    'conditionType' => $reward->condition_type ?? 'amount',
                    'conditionValue' => $reward->condition_value ?? '',
                    'needApply' => $reward->need_apply ?? 0,
                    'limitTimes' => $reward->limit_times ?? 1,
                    'limitPeriod' => $reward->limit_period ?? 'once',
                ];
            }
            
            
            $content = $activity->content ?? '';
            
            $content = html_entity_decode($content, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            
            
            $terms = $activity->terms ?? '';
            if ($terms) {
                $terms = html_entity_decode($terms, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            }
            
            
            $category = $activity->category ?? '';
            if (!empty($category)) {
                $decoded = json_decode($category, true);
                if (is_array($decoded)) {
                    $categoryArr = $decoded;
                } elseif (strpos($category, ',') !== false) {
                    $categoryArr = array_map('trim', explode(',', $category));
                } else {
                    $categoryArr = [$category];
                }
            } else {
                $categoryArr = [];
            }
            
            $activityData = [
                'id' => $activity->id,
                'title' => $activity->title ?? '',
                'desc' => $activity->desc ?? '',
                'content' => $content,
                'terms' => $terms,
                'startDate' => $activity->start_date ?? '',
                'endDate' => $activity->end_date ?? '',
                'status' => $status,
                'banner' => $activity->banner ?? '',
                'images' => $images,
                'rules' => $content,  
                'type' => $activity->type ?? 'other',
                'typeCode' => $activity->type_code ?? '',
                'category' => $categoryArr,  
                'rewards' => $rewardsData,
                'requiredDeposit' => $activity->required_deposit ?? 0,
                'requiredBet' => $activity->required_bet ?? 0
            ];
            
            return json([
                'code' => 0,
                'message' => 'Lấy dữ liệu thành công',
                'data' => $activityData
            ]);
            
        } catch (\Exception $e) {
            \support\Log::error('LấyHoạt độngChi tiếtThất bại: ' . $e->getMessage());
            return json([
                'code' => 500,
                'message' => 'Lấy dữ liệuThất bại：' . $e->getMessage(),
                'data' => null
            ]);
        }
    }
    
    
    protected function parseFanshuiRate(string $fanshuiConfig, float $betAmount): float
    {
        if (empty($fanshuiConfig) || $betAmount <= 0) {
            return 0;
        }
        
        $segments = explode(';', $fanshuiConfig);
        $matchedRate = 0;
        
        foreach ($segments as $seg) {
            $seg = trim($seg);
            if (empty($seg)) continue;
            
            $parts = explode('|', $seg);
            if (count($parts) !== 2) continue;
            
            $range = trim($parts[0]);
            $rate = floatval(trim($parts[1]));
            
            $rangeParts = explode('-', $range);
            if (count($rangeParts) !== 2) continue;
            
            $min = floatval(trim($rangeParts[0]));
            $max = floatval(trim($rangeParts[1]));
            
            
            if ($betAmount >= $min && $betAmount < $max) {
                $matchedRate = $rate;
                break;
            }
            
            
            if ($betAmount >= $max) {
                $matchedRate = $rate;
            }
        }
        
        return $matchedRate;
    }
    
    
    public function rebateByVendor(Request $request)
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
            
            $user = Db::table('caipiao_member as m')
                ->leftJoin('caipiao_membergroup as g', 'm.groupid', '=', 'g.groupid')
                ->where('m.id', $userId)
                ->select('m.*', 'g.groupname', 'g.fanshui as fanshui_config')
                ->first();
            
            if (!$user) {
                return json([
                    'code' => 404,
                    'message' => 'Người dùng không tồn tại',
                    'data' => null
                ]);
            }
            
            
            $categories = Db::table('caipiao_rebate_category_config')
                ->where('status', 1)
                ->orderBy('sort', 'asc')
                ->get();
            
            
            $todayStart = strtotime(date('Y-m-d 00:00:00'));
            
            
            $betStats = Db::table('caipiao_game_bet')
                ->select(Db::raw('platform, game_type, SUM(valid_amount) as total_bet'))
                ->where('uid', $userId)
                ->where('bet_time', '>=', $todayStart)
                ->where('status', 'settled')
                ->groupBy('platform', 'game_type')
                ->get();
            
            
            $betMap = [];
            foreach ($betStats as $stat) {
                $key = $stat->game_type . '_' . $stat->platform;
                $betMap[$key] = floatval($stat->total_bet);
            }
            
            
            $claimedRecords = Db::table('caipiao_fanshui')
                ->where('uid', $userId)
                ->where('oddtime', '>=', $todayStart)
                ->whereNotNull('vendor_code')
                ->where('vendor_code', '!=', '')
                ->pluck('vendor_code')
                ->toArray();
            
            
            $platforms = Db::table('caipiao_game_platform')
                ->where('status', 'online')
                ->get()
                ->keyBy('code');
            
            
            $actualVendorCategories = Db::table('caipiao_game')
                ->select('platform', 'type')
                ->where('status', 'online')
                ->groupBy('platform', 'type')
                ->get();
            
            
            $vendorCategoryMap = [];
            foreach ($actualVendorCategories as $vc) {
                $vendorCategoryMap[$vc->type . '_' . $vc->platform] = true;
            }
            
            $result = [];
            $totalClaimable = 0;
            
            foreach ($categories as $cat) {
                $categoryCode = $cat->category_code;
                $categoryVendors = [];
                
                foreach ($platforms as $platform) {
                    
                    $mapKey = $categoryCode . '_' . $platform->code;
                    if (!isset($vendorCategoryMap[$mapKey])) continue;
                    
                    $vendorName = $platform->name;
                    $key = $categoryCode . '_' . $platform->code;
                    $validBet = $betMap[$key] ?? 0;
                    
                    
                    $rate = $this->parseFanshuiRate($user->fanshui_config ?? '', $validBet);
                    $claimable = $validBet * ($rate / 100);
                    
                    
                    $vendorKey = $categoryCode . '_' . $platform->code;
                    $claimed = in_array($vendorKey, $claimedRecords);
                    
                    if ($claimed) {
                        $claimable = 0;
                    }
                    
                    $categoryVendors[] = [
                        'code' => $platform->code,
                        'name' => $vendorName,
                        'logo' => $platform->icon ?? '',
                        'validBet' => round($validBet, 2),
                        'rate' => $rate . '%',
                        'claimable' => round($claimable, 2),
                        'claimed' => $claimed,
                        'progress' => $validBet > 0 ? min(100, ($validBet / 10000) * 100) : 0,
                        'nextTarget' => '1万',
                        'nextRate' => ($rate + 0.1) . '%'
                    ];
                    
                    $totalClaimable += $claimable;
                }
                
                $result[] = [
                    'code' => $categoryCode,
                    'name' => $cat->category_name,
                    'icon' => $cat->icon ?? '',
                    'vendors' => $categoryVendors
                ];
            }
            
            return json([
                'code' => 0,
                'message' => 'Lấy dữ liệu thành công',
                'data' => [
                    'totalClaimable' => round($totalClaimable, 2),
                    'userLevel' => $user->groupname ?? 'VIP0',
                    'categories' => $result
                ]
            ]);
            
        } catch (\Exception $e) {
            \support\Log::error('Lấy trò chơi商反水dữ liệuThất bại: ' . $e->getMessage());
            return json([
                'code' => 500,
                'message' => 'Lấy dữ liệuThất bại：' . $e->getMessage(),
                'data' => null
            ]);
        }
    }
    
    
    public function claimVendorRebate(Request $request)
    {
        $userId = $request->userId ?? 0;
        
        if (!$userId) {
            return json([
                'code' => 401,
                'message' => '未Đăng nhậphoặcĐăng nhập已过期',
                'data' => null
            ]);
        }
        
        $categoryCode = $request->post('category_code', '');
        $vendorCode = $request->post('vendor_code', '');
        $claimAll = $request->post('claim_all', false);
        
        try {
            
            $user = Db::table('caipiao_member as m')
                ->leftJoin('caipiao_membergroup as g', 'm.groupid', '=', 'g.groupid')
                ->where('m.id', $userId)
                ->select('m.*', 'g.groupname', 'g.fanshui as fanshui_config')
                ->first();
            
            if (!$user) {
                return json([
                    'code' => 404,
                    'message' => 'Người dùng không tồn tại',
                    'data' => null
                ]);
            }
            
            $todayStart = strtotime(date('Y-m-d 00:00:00'));
            $totalClaimed = 0;
            $claimedItems = [];
            
            if ($claimAll) {
                
                $betStats = Db::table('caipiao_game_bet')
                    ->select(Db::raw('platform, game_type, SUM(valid_amount) as total_bet'))
                    ->where('uid', $userId)
                    ->where('bet_time', '>=', $todayStart)
                    ->where('status', 'settled')
                    ->groupBy('platform', 'game_type')
                    ->get();
                
                
                $claimedRecords = Db::table('caipiao_fanshui')
                    ->where('uid', $userId)
                    ->where('oddtime', '>=', $todayStart)
                    ->whereNotNull('vendor_code')
                    ->where('vendor_code', '!=', '')
                    ->pluck('vendor_code')
                    ->toArray();
                
                foreach ($betStats as $stat) {
                    $vendorKey = $stat->game_type . '_' . $stat->platform;
                    if (in_array($vendorKey, $claimedRecords)) {
                        continue; 
                    }
                    
                    $validBet = floatval($stat->total_bet);
                    if ($validBet <= 0) continue;
                    
                    $rate = $this->parseFanshuiRate($user->fanshui_config ?? '', $validBet);
                    $amount = $validBet * ($rate / 100);
                    
                    if ($amount <= 0) continue;
                    
                    
                    $orderId = 'VR' . date('YmdHis') . rand(1000, 9999);
                    Db::table('caipiao_fanshui')->insert([
                        'trano' => $orderId,
                        'uid' => $userId,
                        'username' => $user->username,
                        'groupname' => $user->groupname ?? 'VIP0',
                        'bili' => $rate,
                        'touzhuedu' => $validBet,
                        'amount' => $amount,
                        'oddtime' => time(),
                        'addtime' => time(),
                        'shenhe' => 1,
                        'status' => 1,
                        'vendor_code' => $vendorKey,
                        'category_code' => $stat->game_type,
                        'yongjinfw' => ''
                    ]);
                    
                    $totalClaimed += $amount;
                    $claimedItems[] = [
                        'vendor' => $stat->platform,
                        'category' => $stat->game_type,
                        'amount' => round($amount, 2)
                    ];
                }
            } else {
                
                if (empty($categoryCode) || empty($vendorCode)) {
                    return json([
                        'code' => 400,
                        'message' => '请指定Trò chơi分类和Trò chơi商',
                        'data' => null
                    ]);
                }
                
                $vendorKey = $categoryCode . '_' . $vendorCode;
                
                
                $exists = Db::table('caipiao_fanshui')
                    ->where('uid', $userId)
                    ->where('oddtime', '>=', $todayStart)
                    ->where('vendor_code', $vendorKey)
                    ->first();
                
                if ($exists) {
                    return json([
                        'code' => 400,
                        'message' => 'Hôm nay已领取该Trò chơi商反水',
                        'data' => null
                    ]);
                }
                
                
                $validBet = Db::table('caipiao_game_bet')
                    ->where('uid', $userId)
                    ->where('platform', $vendorCode)
                    ->where('game_type', $categoryCode)
                    ->where('bet_time', '>=', $todayStart)
                    ->where('status', 'settled')
                    ->sum('valid_amount') ?? 0;
                
                if ($validBet <= 0) {
                    return json([
                        'code' => 400,
                        'message' => 'Hôm nay无有效Đặt cược，无法领取',
                        'data' => null
                    ]);
                }
                
                $rate = $this->parseFanshuiRate($user->fanshui_config ?? '', $validBet);
                $amount = $validBet * ($rate / 100);
                
                if ($amount <= 0) {
                    return json([
                        'code' => 400,
                        'message' => '反水Số tiền不足，无法领取',
                        'data' => null
                    ]);
                }
                
                
                $orderId = 'VR' . date('YmdHis') . rand(1000, 9999);
                Db::table('caipiao_fanshui')->insert([
                    'trano' => $orderId,
                    'uid' => $userId,
                    'username' => $user->username,
                    'groupname' => $user->groupname ?? 'VIP0',
                    'bili' => $rate,
                    'touzhuedu' => $validBet,
                    'amount' => $amount,
                    'oddtime' => time(),
                    'addtime' => time(),
                    'shenhe' => 1,
                    'status' => 1,
                    'vendor_code' => $vendorKey,
                    'category_code' => $categoryCode,
                    'yongjinfw' => ''
                ]);
                
                $totalClaimed = $amount;
                $claimedItems[] = [
                    'vendor' => $vendorCode,
                    'category' => $categoryCode,
                    'amount' => round($amount, 2)
                ];
            }
            
            if ($totalClaimed > 0) {
                
                Db::table('caipiao_member')
                    ->where('id', $userId)
                    ->increment('balance', $totalClaimed);
                
                
                Db::table('caipiao_fuddetail')->insert([
                    'uid' => $userId,
                    'username' => $user->username,
                    'type' => 'fanshui',
                    'typename' => 'Trò chơi商Hoàn trả',
                    'trano' => $orderId ?? 'VR' . date('YmdHis') . rand(1000, 9999),
                    'amount' => $totalClaimed,
                    'before' => $user->balance,
                    'after' => $user->balance + $totalClaimed,
                    'oddtime' => time(),
                    'remark' => $claimAll ? '一键领取Trò chơi商Hoàn trả' : "领取{$categoryCode}_{$vendorCode}Hoàn trả"
                ]);
            }
            
            return json([
                'code' => 0,
                'message' => $totalClaimed > 0 ? '领取Thành công' : '暂无可领取的Hoàn trả',
                'data' => [
                    'totalClaimed' => round($totalClaimed, 2),
                    'items' => $claimedItems
                ]
            ]);
            
        } catch (\Exception $e) {
            \support\Log::error('领取Trò chơi商反水Thất bại: ' . $e->getMessage());
            return json([
                'code' => 500,
                'message' => '领取Thất bại：' . $e->getMessage(),
                'data' => null
            ]);
        }
    }
}
