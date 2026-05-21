<?php

namespace app\controller\api;

use support\Request;
use support\Response;
use support\Db;
use support\Log;

class GameController
{
    const BYLOT_PLATFORM = [
        'code' => 'BYLOT',
        'name' => 'Xổ Số Boyue',
        'type' => 'lottery',
        'kind' => 'local',
        'icon' => '/assets/img/provider-boyue.png',
        'status' => 'online',
        'hot' => true,
        'recommend' => true,
        'sort' => 0
    ];
    
    public function platforms(Request $request): Response
    {
        try {
            $type = $request->get('type', '');
            
            $query = Db::table('caipiao_game_platform')
                ->where('status', 'online')
                ->where('code', '!=', 'NG');
            
            if (!empty($type)) {
                $directTypes = ['lottery', 'sport', 'esport'];
                if (in_array($type, $directTypes)) {
                    $query->where('type', $type);
                } else {
                    $gameTypeMap = ['fish' => 'fishing'];
                    $dbType = $gameTypeMap[$type] ?? $type;
                    
                    $platformCodes = Db::table('caipiao_game')
                        ->where('type', $dbType)
                        ->where('status', 'online')
                        ->distinct()
                        ->pluck('platform');
                    
                    if (empty($platformCodes)) {
                        return json(['code' => 0, 'message' => 'success', 'data' => ['list' => []]]);
                    }
                    
                    $query->whereIn('code', $platformCodes);
                }
            }
            
            $platforms = $query->orderBy('sort', 'asc')
                ->orderBy('id', 'asc')
                ->get();
            
            $list = [];
            
            foreach ($platforms as $platform) {
                $typeBannerMap = [
                    'slot' => $platform->slot_banner,
                    'live' => $platform->live_banner,
                    'chess' => $platform->chess_banner,
                    'fish' => $platform->fishing_banner,
                    'fishing' => $platform->fishing_banner,
                ];
                $typeNameMap = [
                    'slot' => $platform->slot_name,
                    'live' => $platform->live_name,
                    'chess' => $platform->chess_name,
                    'fish' => $platform->fishing_name,
                    'fishing' => $platform->fishing_name,
                ];
                $typeBanner = $typeBannerMap[$type] ?? null;
                $typeName = $typeNameMap[$type] ?? null;
                
                $list[] = [
                    'id' => $platform->id,
                    'code' => $platform->code,
                    'name' => $typeName ?: $platform->name,
                    'type' => $platform->type,
                    'kind' => $platform->kind ?? 'third',
                    'icon' => $platform->icon,
                    'banner' => $platform->banner,
                    'mobile_icon' => $platform->mobile_icon,
                    'mobile_banner' => $typeBanner ?: $platform->mobile_banner,
                    'status' => $platform->status,
                    'hot' => (bool)$platform->hot,
                    'recommend' => (bool)$platform->recommend,
                    'sort' => $platform->sort
                ];
            }
            
            return json([
                'code' => 0,
                'message' => 'success',
                'data' => [
                    'list' => $list
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Lấy danh sách nền tảng trò chơi thất bại: ' . $e->getMessage());
            return json(['code' => 500, 'message' => 'Lỗi máy chủ']);
        }
    }
    
    public function search(Request $request): Response
    {
        try {
            $keyword = trim($request->get('keyword', ''));
            $type = $request->get('type', '');
            $platform = $request->get('platform', '');
            $limit = min(50, max(1, (int)$request->get('limit', 20)));
            
            if (empty($keyword) || mb_strlen($keyword) < 1) {
                return json([
                    'code' => 0,
                    'message' => 'success',
                    'data' => ['list' => [], 'total' => 0]
                ]);
            }
            
            $query = Db::table('caipiao_game')
                ->where('status', 'online')
                ->where('name', 'like', "%{$keyword}%");
            
            if (!empty($type)) {
                $gameTypeMap = ['fish' => 'fishing'];
                $dbType = $gameTypeMap[$type] ?? $type;
                $query->where('type', $dbType);
            }
            
            if (!empty($platform)) {
                $query->where('platform', $platform);
            }
            
            $total = $query->count();
            
            $games = $query->orderBy('sort', 'asc')
                ->orderBy('id', 'asc')
                ->limit($limit)
                ->get();
            
            $list = [];
            foreach ($games as $game) {
                $icon = !empty($game->icon) ? $game->icon : $game->cover;
                $list[] = [
                    'gameId' => $game->game_id,
                    'name' => $game->name,
                    'platform' => $game->platform,
                    'type' => $game->type,
                    'icon' => $icon,
                    'cover' => $game->cover ?: $icon
                ];
            }
            
            return json([
                'code' => 0,
                'message' => 'success',
                'data' => [
                    'list' => $list,
                    'total' => $total
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Trò chơiTìm kiếmThất bại: ' . $e->getMessage());
            return json(['code' => 500, 'message' => 'Lỗi máy chủ']);
        }
    }
    
    public function list(Request $request): Response
    {
        try {
            $platform = $request->get('platform', '');
            $type = $request->get('type', '');
            $page = max(1, (int)$request->get('page', 1));
            $pageSize = (int)$request->get('limit', $request->get('pageSize', 20));
            $pageSize = max(1, min(5000, $pageSize));
            
            if ($platform === self::BYLOT_PLATFORM['code']) {
                return $this->getLocalLotteryList($type, $page, $pageSize);
            }
            
            $query = Db::table('caipiao_game')->where('status', 'online');
            
            if (!empty($platform)) {
                $platformInfo = Db::table('caipiao_game_platform')
                    ->where('code', $platform)
                    ->first();
                
                if (!$platformInfo) {
                    return json(['code' => 1003, 'message' => '平台không tồn tại']);
                }
                $query->where('platform', $platform);
            }
            
            if (!empty($type)) {
                $gameTypeMap = ['fish' => 'fishing'];
                $dbType = $gameTypeMap[$type] ?? $type;
                $query->where('type', $dbType);
            }
            
            $total = $query->count();
            
            $games = $query->orderBy('sort', 'asc')
                ->orderBy('id', 'asc')
                ->offset(($page - 1) * $pageSize)
                ->limit($pageSize)
                ->get();
            
            $list = [];
            foreach ($games as $game) {
                $list[] = [
                    'id' => $game->id,
                    'gameId' => $game->game_id,
                    'code' => $game->game_id,
                    'name' => $game->name,
                    'platform' => $game->platform,
                    'type' => $game->type,
                    'icon' => $game->icon,
                    'cover' => $game->cover,
                    'hot' => (bool)$game->hot,
                    'new' => (bool)$game->new,
                    'status' => $game->status
                ];
            }
            
            return json([
                'code' => 0,
                'message' => 'success',
                'data' => [
                    'list' => $list,
                    'total' => $total,
                    'page' => $page,
                    'pageSize' => $pageSize
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Lấy trò chơi列表Thất bại: ' . $e->getMessage());
            return json(['code' => 500, 'message' => 'Lỗi máy chủ']);
        }
    }
    
    private function getLocalLotteryList(string $type, int $page, int $pageSize): Response
    {
        $routeMap = [
            'ssc' => '/ssc',
            'pk10' => '/pk10',
            'k3' => '/k3',
            'x5' => '/x5',
            'xy28' => '/xy28',
            'lhc' => '/lhc',
            'keno' => '/keno',
            '3d' => '/3d',
            'dpc' => '/dpc',
            'dwc' => '/animal',
        ];
        
        $coverMap = [
            'ssc' => '/assets/images/lottery/icons/game-ssc.png',
            'pk10' => '/assets/images/lottery/icons/game-pk10.png',
            'k3' => '/assets/images/lottery/icons/game-k3.png',
            'x5' => '/assets/images/lottery/icons/game-x5.png',
            'xy28' => '/assets/images/lottery/icons/game-xy28.png',
            'lhc' => '/assets/images/lottery/icons/game-lhc.png',
            'keno' => '/assets/images/lottery/icons/game-keno.png',
            '3d' => '/assets/images/lottery/icons/game-3d.png',
            'dpc' => '/assets/images/lottery/icons/game-dpc.png',
            'dwc' => '/assets/images/lottery/icons/game-dwc.png',
        ];
        
        $query = Db::table('caipiao_caipiao')
            ->where('isopen', 1);
        
        if (!empty($type)) {
            $query->where('typeid', $type);
        }
        
        $total = $query->count();
        
        $lotteries = $query->orderBy('listorder', 'asc')
            ->orderBy('id', 'asc')
            ->offset(($page - 1) * $pageSize)
            ->limit($pageSize)
            ->get();
        
        $list = [];
        foreach ($lotteries as $lottery) {
            $typeId = $lottery->typeid;
            $routePrefix = $routeMap[$typeId] ?? '/lottery';
            
            $list[] = [
                'id' => $lottery->id,
                'gameId' => $lottery->name,
                'code' => $lottery->name,
                'name' => $lottery->title,
                'platform' => self::BYLOT_PLATFORM['code'],
                'type' => $typeId,
                'icon' => $lottery->icon ?? ($coverMap[$typeId] ?? '/assets/images/lottery/icons/game-default.png'),
                'cover' => $lottery->icon ?? ($coverMap[$typeId] ?? '/assets/images/lottery/icons/game-default.png'),
                'route' => $routePrefix . '/' . $lottery->name,
                'hot' => (bool)$lottery->is_hot,
                'new' => false,
                'status' => 'online'
            ];
        }
        
        return json([
            'code' => 0,
            'message' => 'success',
            'data' => [
                'list' => $list,
                'total' => $total,
                'page' => $page,
                'pageSize' => $pageSize
            ]
        ]);
    }
    
    public function enter(Request $request): Response
    {
        try {
            $userId = $request->userId ?? 0;
            
            if (!$userId) {
                return json(['code' => 401, 'message' => '未Đăng nhậphoặcToken过期']);
            }
            
            $user = Db::table('caipiao_member')->where('id', $userId)->first();
            
            if (!$user) {
                return json(['code' => 401, 'message' => 'Người dùng không tồn tại']);
            }
            
            $platform = $request->post('platform', '');
            $gameId = $request->post('gameId', '');
            $device = $request->post('device', 'mobile');
            
            if (empty($platform) || empty($gameId)) {
                return json(['code' => 1, 'message' => 'Tham số không hợp lệ']);
            }
            
            if (!in_array($device, ['mobile', 'pc'])) {
                $device = 'mobile';
            }
            
            if ($platform === self::BYLOT_PLATFORM['code']) {
                return $this->enterLocalLottery($user, $gameId);
            }
            
            $platformInfo = Db::table('caipiao_game_platform')
                ->where('code', $platform)
                ->first();
            
            if (!$platformInfo) {
                return json(['code' => 1003, 'message' => '平台không tồn tại']);
            }
            
            if ($platformInfo->status !== 'online') {
                return json(['code' => 1002, 'message' => 'Trò chơi维护中']);
            }
            
            $game = null;
            $gameName = $platformInfo->name . '大厅';
            
            if ($gameId !== 'lobby') {
                $game = Db::table('caipiao_game')
                    ->where('game_id', $gameId)
                    ->first();
                
                if (!$game) {
                    return json(['code' => 1004, 'message' => 'Trò chơikhông tồn tại']);
                }
                
                if ($game->status !== 'online') {
                    return json(['code' => 1002, 'message' => 'Trò chơi维护中']);
                }
                
                $gameName = $game->name;
            }
            
            $balance = $user->balance;
            
            $gamePlatformService = new \app\service\GamePlatformService();
            
            try {
                $gameUrl = $gamePlatformService->getGameUrl($user, $platform, $gameId, $device);
            } catch (\Exception $e) {
                Log::error("获取游戏URL失败: " . $e->getMessage());
                return json(['code' => 1006, 'message' => $e->getMessage()]);
            }
            
            return json([
                'code' => 0,
                'message' => 'success',
                'data' => [
                    'openType' => 'external',
                    'url' => $gameUrl,
                    'balance' => number_format($balance, 2, '.', ''),
                    'gameId' => $gameId,
                    'gameName' => $gameName
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('进入Trò chơiThất bại: ' . $e->getMessage());
            return json(['code' => 500, 'message' => 'Lỗi máy chủ']);
        }
    }
    
    private function enterLocalLottery($user, string $gameId): Response
    {
        $routeMap = [
            'ssc' => '/ssc',
            'pk10' => '/pk10',
            'k3' => '/k3',
            'x5' => '/x5',
            'xy28' => '/xy28',
            'lhc' => '/lhc',
            'keno' => '/keno',
            '3d' => '/3d',
            'dpc' => '/dpc',
            'dwc' => '/animal',
        ];
        
        $lottery = Db::table('caipiao_caipiao')
            ->where('name', $gameId)
            ->first();
        
        if (!$lottery) {
            return json(['code' => 1004, 'message' => '彩种không tồn tại']);
        }
        
        if ($lottery->isopen != 1) {
            return json(['code' => 1002, 'message' => '彩种维护中']);
        }
        
        $typeId = $lottery->typeid;
        $routePrefix = $routeMap[$typeId] ?? '/lottery';
        $localUrl = $routePrefix . '/' . $lottery->name;
        
        return json([
            'code' => 0,
            'message' => 'success',
            'data' => [
                'openType' => 'internal',
                'url' => $localUrl,
                'balance' => number_format($user->balance, 2, '.', ''),
                'gameId' => $gameId,
                'gameName' => $lottery->title
            ]
        ]);
    }
    
    public function balance(Request $request, $platform): Response
    {
        try {
            $userId = $request->userId ?? 0;
            
            if (!$userId) {
                return json(['code' => 401, 'message' => '未Đăng nhậphoặcToken过期']);
            }
            
            if ($platform === self::BYLOT_PLATFORM['code']) {
                $member = Db::table('caipiao_member')->where('id', $userId)->first();
                return json([
                    'code' => 0,
                    'message' => 'success',
                    'data' => [
                        'platform' => $platform,
                        'balance' => number_format($member ? $member->balance : 0, 2, '.', ''),
                        'currency' => 'CNY',
                        'transferSupported' => false
                    ]
                ]);
            }
            
            $platformInfo = Db::table('caipiao_game_platform')
                ->where('code', $platform)
                ->first();
            
            if (!$platformInfo) {
                return json(['code' => 1003, 'message' => '平台không tồn tại']);
            }
            
            $gameBalance = Db::table('caipiao_game_balance')
                ->where('uid', $userId)
                ->where('platform', $platform)
                ->first();
            
            $balance = $gameBalance ? $gameBalance->balance : '0.00';
            
            return json([
                'code' => 0,
                'message' => 'success',
                'data' => [
                    'platform' => $platform,
                    'balance' => number_format($balance, 2, '.', ''),
                    'currency' => 'CNY',
                    'transferSupported' => true
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Lấy trò chơiSố dưThất bại: ' . $e->getMessage());
            return json(['code' => 500, 'message' => 'Lỗi máy chủ']);
        }
    }
    
    public function transferIn(Request $request): Response
    {
        try {
            $userId = $request->userId ?? 0;
            
            if (!$userId) {
                return json(['code' => 401, 'message' => '未Đăng nhậphoặcToken过期']);
            }
            
            $platform = $request->post('platform', '');
            $amount = $request->post('amount', '');
            
            if (empty($platform) || empty($amount)) {
                return json(['code' => 1, 'message' => 'Tham số không hợp lệ']);
            }
            
            if ($platform === self::BYLOT_PLATFORM['code']) {
                return json([
                    'code' => 1007,
                    'message' => '本地Xổ số平台不需要Chuyển khoản，直接使用主tài khoảnSố dư'
                ]);
            }
            
            $amount = floatval($amount);
            if ($amount <= 0) {
                return json(['code' => 1, 'message' => 'Số tiền必须大于0']);
            }
            
            $platformInfo = Db::table('caipiao_game_platform')
                ->where('code', $platform)
                ->first();
            
            if (!$platformInfo) {
                return json(['code' => 1003, 'message' => '平台không tồn tại']);
            }
            
            Db::beginTransaction();
            
            try {
                $member = Db::table('caipiao_member')
                    ->where('id', $userId)
                    ->lockForUpdate()
                    ->first();
                
                if ($member->balance < $amount) {
                    Db::rollBack();
                    return json(['code' => 1001, 'message' => 'Số dư không đủ']);
                }
                
                $beforeBalance = $member->balance;
                $afterBalance = $beforeBalance - $amount;
                
                Db::table('caipiao_member')
                    ->where('id', $userId)
                    ->update(['balance' => $afterBalance]);
                
                $gameBalance = Db::table('caipiao_game_balance')
                    ->where('uid', $userId)
                    ->where('platform', $platform)
                    ->first();
                
                if ($gameBalance) {
                    $newGameBalance = $gameBalance->balance + $amount;
                    Db::table('caipiao_game_balance')
                        ->where('id', $gameBalance->id)
                        ->update([
                            'balance' => $newGameBalance,
                            'updated_at' => time()
                        ]);
                } else {
                    $newGameBalance = $amount;
                    Db::table('caipiao_game_balance')->insert([
                        'uid' => $userId,
                        'platform' => $platform,
                        'balance' => $amount,
                        'updated_at' => time()
                    ]);
                }
                
                $orderNo = 'TF' . date('YmdHis') . rand(1000, 9999);
                Db::table('caipiao_game_transfer')->insert([
                    'order_no' => $orderNo,
                    'uid' => $userId,
                    'platform' => $platform,
                    'type' => 'in',
                    'amount' => $amount,
                    'before_balance' => $beforeBalance,
                    'after_balance' => $afterBalance,
                    'game_balance' => $newGameBalance,
                    'status' => 1,
                    'created_at' => time()
                ]);
                
                Db::commit();
                
                return json([
                    'code' => 0,
                    'message' => 'Chuyển khoảnThành công',
                    'data' => [
                        'orderNo' => $orderNo,
                        'platform' => $platform,
                        'amount' => number_format($amount, 2, '.', ''),
                        'beforeBalance' => number_format($beforeBalance, 2, '.', ''),
                        'afterBalance' => number_format($afterBalance, 2, '.', ''),
                        'gameBalance' => number_format($newGameBalance, 2, '.', '')
                    ]
                ]);
                
            } catch (\Exception $e) {
                Db::rollBack();
                throw $e;
            }
            
        } catch (\Exception $e) {
            Log::error('Chuyển khoản到nền tảng trò chơiThất bại: ' . $e->getMessage());
            return json(['code' => 1005, 'message' => 'Chuyển khoảnThất bại']);
        }
    }
    
    public function transferOut(Request $request): Response
    {
        try {
            $userId = $request->userId ?? 0;
            
            if (!$userId) {
                return json(['code' => 401, 'message' => '未Đăng nhậphoặcToken过期']);
            }
            
            $platform = $request->post('platform', '');
            $amount = $request->post('amount', '');
            
            if (empty($platform) || empty($amount)) {
                return json(['code' => 1, 'message' => 'Tham số không hợp lệ']);
            }
            
            if ($platform === self::BYLOT_PLATFORM['code']) {
                return json([
                    'code' => 1007,
                    'message' => '本地Xổ số平台不需要Chuyển khoản，直接使用主tài khoảnSố dư'
                ]);
            }
            
            $amount = floatval($amount);
            if ($amount <= 0) {
                return json(['code' => 1, 'message' => 'Số tiền必须大于0']);
            }
            
            $platformInfo = Db::table('caipiao_game_platform')
                ->where('code', $platform)
                ->first();
            
            if (!$platformInfo) {
                return json(['code' => 1003, 'message' => '平台không tồn tại']);
            }
            
            Db::beginTransaction();
            
            try {
                $gameBalance = Db::table('caipiao_game_balance')
                    ->where('uid', $userId)
                    ->where('platform', $platform)
                    ->lockForUpdate()
                    ->first();
                
                if (!$gameBalance || $gameBalance->balance < $amount) {
                    Db::rollBack();
                    return json(['code' => 1001, 'message' => 'Trò chơiSố dư không đủ']);
                }
                
                $newGameBalance = $gameBalance->balance - $amount;
                
                Db::table('caipiao_game_balance')
                    ->where('id', $gameBalance->id)
                    ->update([
                        'balance' => $newGameBalance,
                        'updated_at' => time()
                    ]);
                
                $member = Db::table('caipiao_member')
                    ->where('id', $userId)
                    ->first();
                
                $beforeBalance = $member->balance;
                $afterBalance = $beforeBalance + $amount;
                
                Db::table('caipiao_member')
                    ->where('id', $userId)
                    ->update(['balance' => $afterBalance]);
                
                $orderNo = 'TF' . date('YmdHis') . rand(1000, 9999);
                Db::table('caipiao_game_transfer')->insert([
                    'order_no' => $orderNo,
                    'uid' => $userId,
                    'platform' => $platform,
                    'type' => 'out',
                    'amount' => $amount,
                    'before_balance' => $beforeBalance,
                    'after_balance' => $afterBalance,
                    'game_balance' => $newGameBalance,
                    'status' => 1,
                    'created_at' => time()
                ]);
                
                Db::commit();
                
                return json([
                    'code' => 0,
                    'message' => 'Chuyển khoảnThành công',
                    'data' => [
                        'orderNo' => $orderNo,
                        'platform' => $platform,
                        'amount' => number_format($amount, 2, '.', ''),
                        'beforeBalance' => number_format($beforeBalance, 2, '.', ''),
                        'afterBalance' => number_format($afterBalance, 2, '.', ''),
                        'gameBalance' => number_format($newGameBalance, 2, '.', '')
                    ]
                ]);
                
            } catch (\Exception $e) {
                Db::rollBack();
                throw $e;
            }
            
        } catch (\Exception $e) {
            Log::error('从nền tảng trò chơi转出Thất bại: ' . $e->getMessage());
            return json(['code' => 1005, 'message' => 'Chuyển khoảnThất bại']);
        }
    }
    
    public function recallAll(Request $request): Response
    {
        try {
            $userId = $request->userId ?? 0;
            
            if (!$userId) {
                return json(['code' => 401, 'message' => '未Đăng nhậphoặcToken过期']);
            }
            
            $gameBalances = Db::table('caipiao_game_balance')
                ->where('uid', $userId)
                ->where('balance', '>', 0)
                ->get();
            
            if ($gameBalances->isEmpty()) {
                return json([
                    'code' => 0,
                    'message' => '没有可回收的Số dư',
                    'data' => [
                        'totalAmount' => '0.00',
                        'details' => []
                    ]
                ]);
            }
            
            Db::beginTransaction();
            
            try {
                $totalAmount = 0;
                $details = [];
                
                foreach ($gameBalances as $gameBalance) {
                    if ($gameBalance->balance > 0) {
                        $amount = $gameBalance->balance;
                        $totalAmount += $amount;
                        
                        Db::table('caipiao_game_balance')
                            ->where('id', $gameBalance->id)
                            ->update([
                                'balance' => 0,
                                'updated_at' => time()
                            ]);
                        
                        $orderNo = 'TF' . date('YmdHis') . rand(1000, 9999);
                        Db::table('caipiao_game_transfer')->insert([
                            'order_no' => $orderNo,
                            'uid' => $userId,
                            'platform' => $gameBalance->platform,
                            'type' => 'out',
                            'amount' => $amount,
                            'before_balance' => 0,
                            'after_balance' => 0,
                            'game_balance' => 0,
                            'status' => 1,
                            'created_at' => time()
                        ]);
                        
                        $details[] = [
                            'platform' => $gameBalance->platform,
                            'amount' => number_format($amount, 2, '.', '')
                        ];
                    }
                }
                
                if ($totalAmount > 0) {
                    Db::table('caipiao_member')
                        ->where('id', $userId)
                        ->increment('balance', $totalAmount);
                }
                
                Db::commit();
                
                return json([
                    'code' => 0,
                    'message' => '回收Thành công',
                    'data' => [
                        'totalAmount' => number_format($totalAmount, 2, '.', ''),
                        'details' => $details
                    ]
                ]);
                
            } catch (\Exception $e) {
                Db::rollBack();
                throw $e;
            }
            
        } catch (\Exception $e) {
            Log::error('一键回收Thất bại: ' . $e->getMessage());
            return json(['code' => 500, 'message' => 'Lỗi máy chủ']);
        }
    }
    
    public function records(Request $request): Response
    {
        try {
            $userId = $request->userId ?? 0;
            
            if (!$userId) {
                return json(['code' => 401, 'message' => '未Đăng nhậphoặcToken过期']);
            }
            
            $platform = $request->get('platform', '');
            $type = $request->get('type', '');
            $startDate = $request->get('startDate', '');
            $endDate = $request->get('endDate', '');
            $page = max(1, (int)$request->get('page', 1));
            $pageSize = max(1, min(100, (int)$request->get('pageSize', 20)));
            
            $query = Db::table('caipiao_game_bet')
                ->where('uid', $userId);
            
            if (!empty($platform)) {
                $query->where('platform', $platform);
            }
            
            if (!empty($type)) {
                $query->whereIn('platform', function($query) use ($type) {
                    $query->select('code')
                        ->from('caipiao_game_platform')
                        ->where('type', $type);
                });
            }
            
            if (!empty($startDate)) {
                $query->where('bet_time', '>=', strtotime($startDate));
            }
            
            if (!empty($endDate)) {
                $query->where('bet_time', '<=', strtotime($endDate . ' 23:59:59'));
            }
            
            $total = $query->count();
            
            $records = $query->orderBy('bet_time', 'desc')
                ->offset(($page - 1) * $pageSize)
                ->limit($pageSize)
                ->get();
            
            $summaryQuery = Db::table('caipiao_game_bet')
                ->where('uid', $userId);
            
            if (!empty($platform)) {
                $summaryQuery->where('platform', $platform);
            }
            if (!empty($startDate)) {
                $summaryQuery->where('bet_time', '>=', strtotime($startDate));
            }
            if (!empty($endDate)) {
                $summaryQuery->where('bet_time', '<=', strtotime($endDate . ' 23:59:59'));
            }
            
            $summary = $summaryQuery->selectRaw('
                SUM(bet_amount) as total_bet,
                SUM(win_amount) as total_win,
                SUM(profit) as total_profit
            ')->first();
            
            $list = [];
            foreach ($records as $record) {
                $list[] = [
                    'id' => $record->id,
                    'orderNo' => $record->order_no,
                    'platform' => $record->platform,
                    'platformName' => $record->platform_name,
                    'gameId' => $record->game_id,
                    'gameName' => $record->game_name,
                    'betAmount' => number_format($record->bet_amount, 2, '.', ''),
                    'winAmount' => number_format($record->win_amount, 2, '.', ''),
                    'profit' => number_format($record->profit, 2, '.', ''),
                    'status' => $record->status,
                    'betTime' => date('Y-m-d H:i:s', $record->bet_time),
                    'settleTime' => $record->settle_time ? date('Y-m-d H:i:s', $record->settle_time) : ''
                ];
            }
            
            return json([
                'code' => 0,
                'message' => 'success',
                'data' => [
                    'list' => $list,
                    'total' => $total,
                    'page' => $page,
                    'pageSize' => $pageSize,
                    'summary' => [
                        'totalBet' => number_format($summary->total_bet ?? 0, 2, '.', ''),
                        'totalWin' => number_format($summary->total_win ?? 0, 2, '.', ''),
                        'totalProfit' => number_format($summary->total_profit ?? 0, 2, '.', '')
                    ]
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Lấy trò chơilịch sử thất bại: ' . $e->getMessage());
            return json(['code' => 500, 'message' => 'Lỗi máy chủ']);
        }
    }
    
    public function hot(Request $request): Response
    {
        try {
            $type = $request->get('type', '');
            $limit = max(1, min(50, (int)$request->get('limit', 10)));
            
            $query = Db::table('caipiao_game as g')
                ->leftJoin('caipiao_game_platform as p', 'g.platform', '=', 'p.code')
                ->select('g.*', 'p.name as platform_name')
                ->where('g.hot', 1)
                ->where('g.status', 'online');
            
            if (!empty($type)) {
                $query->where('g.type', $type);
            }
            
            $games = $query->orderBy('g.sort', 'asc')
                ->limit($limit)
                ->get();
            
            $list = [];
            foreach ($games as $game) {
                $icon = !empty($game->icon) ? $game->icon : $game->cover;
                $list[] = [
                    'gameId' => $game->game_id,
                    'name' => $game->name,
                    'platform' => $game->platform,
                    'platformName' => $game->platform_name,
                    'type' => $game->type,
                    'icon' => $icon,
                    'cover' => $game->cover,
                    'hot' => true
                ];
            }
            
            return json([
                'code' => 0,
                'message' => 'success',
                'data' => [
                    'list' => $list
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Lấy热门Trò chơiThất bại: ' . $e->getMessage());
            return json(['code' => 500, 'message' => 'Lỗi máy chủ']);
        }
    }
    
    public function addFavorite(Request $request): Response
    {
        try {
            $userId = $request->userId;
            $platform = $request->post('platform', '');
            $gameId = $request->post('gameId', '');
            $type = $request->post('type', '');
            
            if (empty($platform) || empty($gameId)) {
                return json(['code' => 400, 'message' => 'Tham số không hợp lệ']);
            }
            
            $exists = Db::table('caipiao_game_favorite')
                ->where('user_id', $userId)
                ->where('platform', $platform)
                ->where('game_id', $gameId)
                ->exists();
            
            if ($exists) {
                return json(['code' => 400, 'message' => '已收藏']);
            }
            
            $game = Db::table('caipiao_game')
                ->where('platform', $platform)
                ->where('game_id', $gameId)
                ->first();
            
            $gameType = $game ? $game->type : $type;
            $gameName = $game ? $game->name : $gameId;
            $gameCover = $game ? $game->cover : '';
            $gameIcon = $game ? (!empty($game->icon) ? $game->icon : $game->cover) : '';
            
            Db::table('caipiao_game_favorite')->insert([
                'user_id' => $userId,
                'platform' => $platform,
                'game_id' => $gameId,
                'game_type' => $gameType,
                'game_name' => $gameName,
                'game_icon' => $gameIcon,
                'game_cover' => $gameCover,
                'created_at' => time()
            ]);
            
            return json(['code' => 0, 'message' => '收藏Thành công']);
            
        } catch (\Exception $e) {
            Log::error('收藏Trò chơiThất bại: ' . $e->getMessage());
            return json(['code' => 500, 'message' => 'Lỗi máy chủ']);
        }
    }
    
    public function removeFavorite(Request $request): Response
    {
        try {
            $userId = $request->userId;
            $platform = $request->post('platform', '');
            $gameId = $request->post('gameId', '');
            
            if (empty($platform) || empty($gameId)) {
                return json(['code' => 400, 'message' => 'Tham số không hợp lệ']);
            }
            
            Db::table('caipiao_game_favorite')
                ->where('user_id', $userId)
                ->where('platform', $platform)
                ->where('game_id', $gameId)
                ->delete();
            
            return json(['code' => 0, 'message' => 'Hủy收藏Thành công']);
            
        } catch (\Exception $e) {
            Log::error('Hủy收藏Thất bại: ' . $e->getMessage());
            return json(['code' => 500, 'message' => 'Lỗi máy chủ']);
        }
    }
    
    public function getFavorites(Request $request): Response
    {
        try {
            $userId = $request->userId;
            $type = $request->get('type', '');
            
            $query = Db::table('caipiao_game_favorite')
                ->where('user_id', $userId);
            
            if (!empty($type)) {
                $gameTypeMap = ['fish' => 'fishing'];
                $dbType = $gameTypeMap[$type] ?? $type;
                $query->where('game_type', $dbType);
            }
            
            $favorites = $query->orderBy('created_at', 'desc')->get();
            
            $list = [];
            foreach ($favorites as $fav) {
                $icon = !empty($fav->game_icon) ? $fav->game_icon : $fav->game_cover;
                
                if (empty($icon)) {
                    $game = Db::table('caipiao_game')
                        ->where('platform', $fav->platform)
                        ->where('game_id', $fav->game_id)
                        ->first();
                    if ($game) {
                        $icon = !empty($game->icon) ? $game->icon : $game->cover;
                    }
                }
                
                $list[] = [
                    'gameId' => $fav->game_id,
                    'name' => $fav->game_name,
                    'platform' => $fav->platform,
                    'type' => $fav->game_type,
                    'icon' => $icon,
                    'cover' => $fav->game_cover
                ];
            }
            
            return json([
                'code' => 0,
                'message' => 'success',
                'data' => ['list' => $list]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Lấy收藏列表Thất bại: ' . $e->getMessage());
            return json(['code' => 500, 'message' => 'Lỗi máy chủ']);
        }
    }
    
    public function addRecent(Request $request): Response
    {
        try {
            $userId = $request->userId;
            $platform = $request->post('platform', '');
            $gameId = $request->post('gameId', '');
            $type = $request->post('type', '');
            
            if (empty($platform) || empty($gameId)) {
                return json(['code' => 400, 'message' => 'Tham số không hợp lệ']);
            }
            
            $game = Db::table('caipiao_game')
                ->where('platform', $platform)
                ->where('game_id', $gameId)
                ->first();
            
            $gameType = $game ? $game->type : $type;
            $gameName = $game ? $game->name : $gameId;
            $gameCover = $game ? $game->cover : '';
            $gameIcon = $game ? (!empty($game->icon) ? $game->icon : $game->cover) : '';
            
            $exists = Db::table('caipiao_game_recent')
                ->where('user_id', $userId)
                ->where('platform', $platform)
                ->where('game_id', $gameId)
                ->first();
            
            if ($exists) {
                $updateData = [
                    'game_type' => $gameType,
                    'updated_at' => time()
                ];
                if (empty($exists->game_icon) && !empty($gameIcon)) {
                    $updateData['game_icon'] = $gameIcon;
                    $updateData['game_cover'] = $gameCover;
                }
                Db::table('caipiao_game_recent')
                    ->where('id', $exists->id)
                    ->update($updateData);
            } else {
                Db::table('caipiao_game_recent')->insert([
                    'user_id' => $userId,
                    'platform' => $platform,
                    'game_id' => $gameId,
                    'game_type' => $gameType,
                    'game_name' => $gameName,
                    'game_icon' => $gameIcon,
                    'game_cover' => $gameCover,
                    'created_at' => time(),
                    'updated_at' => time()
                ]);
            }
            
            $recentIds = Db::table('caipiao_game_recent')
                ->where('user_id', $userId)
                ->orderBy('updated_at', 'desc')
                ->limit(50)
                ->pluck('id');
            
            if (count($recentIds) > 0) {
                Db::table('caipiao_game_recent')
                    ->where('user_id', $userId)
                    ->whereNotIn('id', $recentIds->toArray())
                    ->delete();
            }
            
            return json(['code' => 0, 'message' => 'success']);
            
        } catch (\Exception $e) {
            Log::error('lịch sử最近Trò chơiThất bại: ' . $e->getMessage());
            return json(['code' => 500, 'message' => 'Lỗi máy chủ']);
        }
    }
    
    public function getRecent(Request $request): Response
    {
        try {
            $userId = $request->userId;
            $type = $request->get('type', '');
            $limit = min(50, max(1, (int)$request->get('limit', 20)));
            
            $query = Db::table('caipiao_game_recent')
                ->where('user_id', $userId);
            
            if (!empty($type)) {
                $gameTypeMap = ['fish' => 'fishing'];
                $dbType = $gameTypeMap[$type] ?? $type;
                $query->where('game_type', $dbType);
            }
            
            $recents = $query->orderBy('updated_at', 'desc')
                ->limit($limit)
                ->get();
            
            $list = [];
            foreach ($recents as $recent) {
                $icon = !empty($recent->game_icon) ? $recent->game_icon : $recent->game_cover;
                
                if (empty($icon)) {
                    $game = Db::table('caipiao_game')
                        ->where('platform', $recent->platform)
                        ->where('game_id', $recent->game_id)
                        ->first();
                    if ($game) {
                        $icon = !empty($game->icon) ? $game->icon : $game->cover;
                    }
                }
                
                $list[] = [
                    'gameId' => $recent->game_id,
                    'name' => $recent->game_name,
                    'platform' => $recent->platform,
                    'type' => $recent->game_type,
                    'icon' => $icon,
                    'cover' => $recent->game_cover
                ];
            }
            
            return json([
                'code' => 0,
                'message' => 'success',
                'data' => ['list' => $list]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Lấy最近Trò chơiThất bại: ' . $e->getMessage());
            return json(['code' => 500, 'message' => 'Lỗi máy chủ']);
        }
    }
    
    public function categories(Request $request): Response
    {
        $iconImgMap = [
            'hot' => '/assets/img/icon_dtfl_rm_1.avif',
            'slot' => '/assets/img/icon_dtfl_dz_1.avif',
            'live' => '/assets/img/icon_dtfl_zr_1.avif',
            'fish' => '/assets/img/icon_dtfl_by_1.avif',
            'chess' => '/assets/img/icon_dtfl_qp_1.avif',
            'lottery' => '/assets/img/icon_dtfl_cp_1.avif',
            'sport' => '/assets/img/icon_dtfl_ty_1.avif',
            'esport' => '/assets/img/icon_dtfl_dj_1.avif',
            'blockchain' => '/assets/img/icon_dtfl_qkl_1.avif'
        ];
        
        $displayNameMap = [
            'hot' => '热门Trò chơi',
            'slot' => 'Điện tửTrò chơi',
            'live' => 'Live Casino视讯',
            'fish' => 'Bắn cáTrò chơi',
            'chess' => 'BàiTrò chơi',
            'lottery' => 'Xổ sốTrò chơi',
            'esport' => 'Điện tử竞技',
            'sport' => 'Thể thao竞技',
            'blockchain' => '区块链Trò chơi'
        ];
        
        try {
            $defaultCategories = [
                ['id' => 0, 'code' => 'hot', 'name' => '热门', 'displayName' => '热门Trò chơi', 'iconImg' => $iconImgMap['hot'], 'path' => '', 'sort' => 0],
                ['id' => 1, 'code' => 'slot', 'name' => 'Điện tử', 'displayName' => 'Điện tửTrò chơi', 'iconImg' => $iconImgMap['slot'], 'path' => '/game/slot', 'sort' => 1],
                ['id' => 2, 'code' => 'live', 'name' => 'Live Casino', 'displayName' => 'Live Casino视讯', 'iconImg' => $iconImgMap['live'], 'path' => '/game/live', 'sort' => 2],
                ['id' => 3, 'code' => 'fish', 'name' => 'Bắn cá', 'displayName' => 'Bắn cáTrò chơi', 'iconImg' => $iconImgMap['fish'], 'path' => '/game/fish', 'sort' => 3],
                ['id' => 4, 'code' => 'chess', 'name' => 'Bài', 'displayName' => 'BàiTrò chơi', 'iconImg' => $iconImgMap['chess'], 'path' => '/game/chess', 'sort' => 4],
                ['id' => 5, 'code' => 'lottery', 'name' => 'Xổ số', 'displayName' => 'Xổ sốTrò chơi', 'iconImg' => $iconImgMap['lottery'], 'path' => '/game/lottery', 'sort' => 5],
                ['id' => 6, 'code' => 'blockchain', 'name' => '区块链', 'displayName' => '区块链Trò chơi', 'iconImg' => $iconImgMap['blockchain'], 'path' => '/game/blockchain', 'sort' => 7]
            ];
            
            
            $dbCategories = null;
            try {
                $dbCategories = Db::table('caipiao_game_category')
                    ->where('status', 1)
                    ->orderBy('sort', 'asc')
                    ->get();
            } catch (\Exception $e) {
                
                $dbCategories = null;
            }
            
            if ($dbCategories && count($dbCategories) > 0) {
                $categories = [];
                $hasHot = false;
                foreach ($dbCategories as $cat) {
                    if ($cat->code === 'hot') {
                        $hasHot = true;
                    }
                }
                if (!$hasHot) {
                    $categories[] = [
                        'id' => 0,
                        'code' => 'hot',
                        'name' => '热门',
                        'displayName' => '热门Trò chơi',
                        'iconImg' => $iconImgMap['hot'],
                        'path' => '',
                        'sort' => 0
                    ];
                }
                foreach ($dbCategories as $cat) {
                    $code = $cat->code;
                    $iconImg = $iconImgMap[$code] ?? '/assets/img/icon_dtfl_rm_1.avif';
                    if (isset($cat->icon_img) && !empty($cat->icon_img)) {
                        $iconImg = $cat->icon_img;
                    }
                    $categories[] = [
                        'id' => $cat->id,
                        'code' => $code,
                        'name' => $cat->name,
                        'displayName' => $displayNameMap[$code] ?? $cat->name . 'Trò chơi',
                        'iconImg' => $iconImg,
                        'path' => $cat->path ?? '/game/' . $code,
                        'sort' => $cat->sort
                    ];
                }
            } else {
                $categories = $defaultCategories;
            }
            
            return json([
                'code' => 0,
                'message' => 'success',
                'data' => [
                    'list' => $categories
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Lấy trò chơi分类Thất bại: ' . $e->getMessage());
            return json([
                'code' => 0,
                'message' => 'success',
                'data' => [
                    'list' => [
                        ['id' => 0, 'code' => 'hot', 'name' => '热门', 'displayName' => '热门Trò chơi', 'iconImg' => '/assets/img/icon_dtfl_rm_1.avif', 'path' => '', 'sort' => 0],
                        ['id' => 1, 'code' => 'slot', 'name' => 'Điện tử', 'displayName' => 'Điện tửTrò chơi', 'iconImg' => '/assets/img/icon_dtfl_dz_1.avif', 'path' => '/game/slot', 'sort' => 1],
                        ['id' => 2, 'code' => 'live', 'name' => 'Live Casino', 'displayName' => 'Live Casino视讯', 'iconImg' => '/assets/img/icon_dtfl_zr_1.avif', 'path' => '/game/live', 'sort' => 2],
                        ['id' => 3, 'code' => 'fish', 'name' => 'Bắn cá', 'displayName' => 'Bắn cáTrò chơi', 'iconImg' => '/assets/img/icon_dtfl_by_1.avif', 'path' => '/game/fish', 'sort' => 3],
                        ['id' => 4, 'code' => 'chess', 'name' => 'Bài', 'displayName' => 'BàiTrò chơi', 'iconImg' => '/assets/img/icon_dtfl_qp_1.avif', 'path' => '/game/chess', 'sort' => 4],
                        ['id' => 5, 'code' => 'lottery', 'name' => 'Xổ số', 'displayName' => 'Xổ sốTrò chơi', 'iconImg' => '/assets/img/icon_dtfl_cp_1.avif', 'path' => '/game/lottery', 'sort' => 5],
                        ['id' => 6, 'code' => 'blockchain', 'name' => '区块链', 'displayName' => '区块链Trò chơi', 'iconImg' => '/assets/img/icon_dtfl_qkl_1.avif', 'path' => '/game/blockchain', 'sort' => 7]
                    ]
                ]
            ]);
        }
    }
    
    public function platformBalances(Request $request): Response
    {
        try {
            $userId = $request->userId ?? 0;
            $user = Db::table('caipiao_member')->where('id', $userId)->first();
            
            if (!$user) {
                return json(['code' => 401, 'message' => 'Vui lòng đăng nhập']);
            }
            
            $platforms = Db::table('caipiao_game_platform')
                ->where('status', 'online')
                ->whereNotIn('code', ['BYLOT', 'NG'])
                ->orderBy('sort', 'asc')
                ->get();
            
            $cachedBalances = Db::table('caipiao_game_balance')
                ->where('uid', $userId)
                ->pluck('balance', 'platform');
            
            $platformBalances = [];
            
            foreach ($platforms as $platform) {
                $balance = $cachedBalances[$platform->code] ?? 0;
                
                $platformBalances[] = [
                    'code' => $platform->code,
                    'name' => $platform->name,
                    'icon' => $platform->icon ?? '',
                    'type' => $platform->type ?? 'slot',
                    'balance' => $balance
                ];
            }
            
            return json([
                'code' => 0,
                'message' => 'success',
                'data' => [
                    'mainBalance' => $user->balance,
                    'platforms' => $platformBalances
                ]
            ]);
            
        } catch (\Exception $e) {
            return json(['code' => 500, 'message' => 'Lỗi máy chủ']);
        }
    }
    
    public function refreshPlatformBalances(Request $request): Response
    {
        try {
            $userId = $request->userId ?? 0;
            $user = Db::table('caipiao_member')->where('id', $userId)->first();
            
            if (!$user) {
                return json(['code' => 401, 'message' => 'Vui lòng đăng nhập']);
            }
            
            $enteredPlatforms = Db::table('caipiao_game_recent')
                ->where('user_id', $userId)
                ->distinct()
                ->pluck('platform')
                ->toArray();
            
            $balancePlatforms = Db::table('caipiao_game_balance')
                ->where('uid', $userId)
                ->distinct()
                ->pluck('platform')
                ->toArray();
            
            $enteredPlatforms = array_unique(array_merge($enteredPlatforms, $balancePlatforms));
            
            if (empty($enteredPlatforms)) {
                return json([
                    'code' => 0,
                    'message' => '暂无进入过的平台',
                    'data' => [
                        'mainBalance' => $user->balance,
                        'platforms' => []
                    ]
                ]);
            }
            
            $platforms = Db::table('caipiao_game_platform')
                ->where('status', 'online')
                ->whereIn('code', $enteredPlatforms)
                ->orderBy('sort', 'asc')
                ->get();
            
            $ngConfig = Db::table('caipiao_game_platform')
                ->where('code', 'NG')
                ->first();
            
            $apiUrl = $ngConfig->api_url ?? env('NG_API_URL', 'https://api.ng777.net');
            $sn = $ngConfig->api_key ?? env('NG_SN', '');
            $secretKey = $ngConfig->api_secret ?? env('NG_SECRET_KEY', '');
            
            $platformBalances = [];
            
            foreach ($platforms as $platform) {
                $balance = 0;
                
                try {
                    $balance = $this->getNGPlatformBalance($user, $platform->code, $apiUrl, $sn, $secretKey);
                    
                    $exists = Db::table('caipiao_game_balance')
                        ->where('uid', $userId)
                        ->where('platform', $platform->code)
                        ->exists();
                    
                    if ($exists) {
                        Db::table('caipiao_game_balance')
                            ->where('uid', $userId)
                            ->where('platform', $platform->code)
                            ->update(['balance' => $balance, 'updated_at' => time()]);
                    } elseif ($balance > 0) {
                        Db::table('caipiao_game_balance')->insert([
                            'uid' => $userId,
                            'platform' => $platform->code,
                            'balance' => $balance,
                            'created_at' => time(),
                            'updated_at' => time()
                        ]);
                    }
                } catch (\Exception $e) {
                }
                
                $platformBalances[] = [
                    'code' => $platform->code,
                    'name' => $platform->name,
                    'icon' => $platform->icon ?? '',
                    'type' => $platform->type ?? 'slot',
                    'balance' => $balance
                ];
            }
            
            return json([
                'code' => 0,
                'message' => 'Làm mớiThành công',
                'data' => [
                    'mainBalance' => $user->balance,
                    'platforms' => $platformBalances
                ]
            ]);
            
        } catch (\Exception $e) {
            return json(['code' => 500, 'message' => 'Lỗi máy chủ']);
        }
    }
    
    private function getNGPlatformBalance($user, $platType, $apiUrl, $sn, $secretKey): float
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyz';
        $random = '';
        for ($i = 0; $i < 32; $i++) {
            $random .= $characters[random_int(0, 35)];
        }
        $sign = strtolower(md5($random . $sn . $secretKey));
        
        $params = json_encode([
            'playerId' => $user->username,
            'platType' => strtolower($platType),
            'currency' => 'CNY'
        ]);
        
        $ch = curl_init($apiUrl . '/api/server/balance');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($params),
            'sign: ' . $sign,
            'random: ' . $random,
            'sn: ' . $sn
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        $data = json_decode($response, true);
        if (isset($data['code']) && $data['code'] == '10000' && isset($data['data']['balance'])) {
            return floatval($data['data']['balance']);
        }
        
        return 0;
    }
    
    public function recoverAll(Request $request): Response
    {
        try {
            $userId = $request->userId ?? 0;
            $user = Db::table('caipiao_member')->where('id', $userId)->first();
            
            if (!$user) {
                return json(['code' => 401, 'message' => 'Vui lòng đăng nhập']);
            }
            
            $ngConfig = Db::table('caipiao_game_platform')
                ->where('code', 'NG')
                ->first();
            
            $apiUrl = $ngConfig->api_url ?? env('NG_API_URL', 'https://api.ng777.net');
            $sn = $ngConfig->api_key ?? env('NG_SN', '');
            $secretKey = $ngConfig->api_secret ?? env('NG_SECRET_KEY', '');
            
            $characters = '0123456789abcdefghijklmnopqrstuvwxyz';
            $random = '';
            for ($i = 0; $i < 32; $i++) {
                $random .= $characters[random_int(0, 35)];
            }
            $sign = strtolower(md5($random . $sn . $secretKey));
            
            $params = json_encode([
                'playerId' => $user->username,
                'currency' => 'CNY'
            ]);
            
            $ch = curl_init($apiUrl . '/api/server/transferAll');
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Content-Length: ' . strlen($params),
                'sign: ' . $sign,
                'random: ' . $random,
                'sn: ' . $sn
            ]);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 60);
            
            $response = curl_exec($ch);
            curl_close($ch);
            
            $data = json_decode($response, true);
            
            if (isset($data['code']) && $data['code'] == '10000') {
                $totalAmount = floatval($data['data']['balanceAll'] ?? 0);
                
                if ($totalAmount < 1) {
                    return json(['code' => 1, 'message' => '没有可找回的Số dư']);
                }
                
                Db::beginTransaction();
                try {
                    $beforeBalance = $user->balance;
                    $afterBalance = $beforeBalance + $totalAmount;
                    
                    Db::table('caipiao_member')
                        ->where('id', $user->id)
                        ->increment('balance', $totalAmount);
                    
                    Db::table('caipiao_game_balance')
                        ->where('uid', $userId)
                        ->update(['balance' => 0, 'updated_at' => time()]);
                    
                    $orderNo = 'RA' . date('YmdHis') . mt_rand(1000, 9999);
                    Db::table('caipiao_game_transfer')->insert([
                        'order_no' => $orderNo,
                        'uid' => $userId,
                        'platform' => 'ALL',
                        'type' => 'out',
                        'amount' => $totalAmount,
                        'before_balance' => $beforeBalance,
                        'after_balance' => $afterBalance,
                        'game_balance' => 0,
                        'status' => 1,
                        'created_at' => time()
                    ]);
                    
                    Db::commit();
                } catch (\Exception $e) {
                    Db::rollBack();
                    return json(['code' => 500, 'message' => 'Lỗi hệ thống']);
                }
                
                return json([
                    'code' => 0,
                    'message' => 'success',
                    'data' => ['amount' => $totalAmount]
                ]);
            } else {
                return json([
                    'code' => 1,
                    'message' => $data['msg'] ?? '找回Thất bại'
                ]);
            }
            
        } catch (\Exception $e) {
            return json(['code' => 500, 'message' => 'Lỗi máy chủ']);
        }
    }
    
    public function recoverPlatform(Request $request): Response
    {
        try {
            $userId = $request->userId ?? 0;
            $user = Db::table('caipiao_member')->where('id', $userId)->first();
            
            if (!$user) {
                return json(['code' => 401, 'message' => 'Vui lòng đăng nhập']);
            }
            
            $platform = $request->post('platform', '');
            
            if (empty($platform)) {
                return json(['code' => 1, 'message' => 'Vui lòng chọn平台']);
            }
            
            $ngConfig = Db::table('caipiao_game_platform')
                ->where('code', 'NG')
                ->first();
            
            $apiUrl = $ngConfig->api_url ?? env('NG_API_URL', 'https://api.ng777.net');
            $sn = $ngConfig->api_key ?? env('NG_SN', '');
            $secretKey = $ngConfig->api_secret ?? env('NG_SECRET_KEY', '');
            
            $balance = $this->getNGPlatformBalance($user, $platform, $apiUrl, $sn, $secretKey);
            $amount = floor($balance);
            
            if ($amount < 1) {
                return json(['code' => 1, 'message' => 'Số dư không đủ1元']);
            }
            
            $characters = '0123456789abcdefghijklmnopqrstuvwxyz';
            $random = '';
            for ($i = 0; $i < 32; $i++) {
                $random .= $characters[random_int(0, 35)];
            }
            $sign = strtolower(md5($random . $sn . $secretKey));
            
            $orderNo = 'RP' . date('YmdHis') . mt_rand(1000, 9999);
            
            $params = json_encode([
                'playerId' => $user->username,
                'platType' => strtolower($platform),
                'currency' => 'CNY',
                'amount' => $amount,
                'type' => 2,
                'orderId' => $orderNo
            ]);
            
            $ch = curl_init($apiUrl . '/api/server/transfer');
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Content-Length: ' . strlen($params),
                'sign: ' . $sign,
                'random: ' . $random,
                'sn: ' . $sn
            ]);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            
            $response = curl_exec($ch);
            curl_close($ch);
            
            $data = json_decode($response, true);
            
            if (isset($data['code']) && $data['code'] == '10000') {
                Db::beginTransaction();
                try {
                    $beforeBalance = $user->balance;
                    $afterBalance = $beforeBalance + $amount;
                    
                    Db::table('caipiao_member')
                        ->where('id', $user->id)
                        ->increment('balance', $amount);
                    
                    Db::table('caipiao_game_balance')
                        ->where('uid', $userId)
                        ->where('platform', $platform)
                        ->update(['balance' => 0, 'updated_at' => time()]);
                    
                    Db::table('caipiao_game_transfer')->insert([
                        'order_no' => $orderNo,
                        'uid' => $userId,
                        'platform' => $platform,
                        'type' => 'out',
                        'amount' => $amount,
                        'before_balance' => $beforeBalance,
                        'after_balance' => $afterBalance,
                        'game_balance' => 0,
                        'status' => 1,
                        'created_at' => time()
                    ]);
                    
                    Db::commit();
                } catch (\Exception $e) {
                    Db::rollBack();
                    return json(['code' => 500, 'message' => 'Lỗi hệ thống']);
                }
                
                return json([
                    'code' => 0,
                    'message' => 'success',
                    'data' => ['amount' => $amount]
                ]);
            } else {
                return json([
                    'code' => 1,
                    'message' => $data['msg'] ?? '找回Thất bại'
                ]);
            }
            
        } catch (\Exception $e) {
            return json(['code' => 500, 'message' => 'Lỗi máy chủ']);
        }
    }
}
