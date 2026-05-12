<?php

namespace app\service;

use support\Db;
use support\Log;

class GamePlatformService
{
    
    public function getGameUrl($user, string $platform, string $gameId, string $device = 'mobile'): string
    {
        
        $platformConfig = Db::table('caipiao_game_platform')
            ->where('code', $platform)
            ->first();
        
        if (!$platformConfig) {
            throw new \Exception('平台不存在');
        }
        
        if ($platformConfig->status !== 'online') {
            throw new \Exception('游戏维护中');
        }
        
        
        
        $ngConfig = Db::table('caipiao_game_platform')
            ->where('code', 'NG')
            ->first();
        
        if (!$ngConfig) {
            throw new \Exception('NG平台配置不存在');
        }
        
        return $this->getNGGameUrl($user, $platform, $gameId, $device, $ngConfig);
    }
    
    
    private function getNGGameUrl($user, $platform, $gameId, $device, $platformConfig): string
    {
        try {
            $apiUrl = $platformConfig->api_url ?: env('NG_API_URL', 'https://api.ng777.net');
            $sn = $platformConfig->api_key ?: env('NG_SN', '');
            $secretKey = $platformConfig->api_secret ?: env('NG_SECRET_KEY', '');
            
            
            $originalPlatform = strtolower($platform);  
            $platType = $originalPlatform;
            $gameCode = ($gameId === 'lobby') ? '' : $this->getNGGameCode($gameId);
            
            
            $platformCodeMap = [
                
                'bbin_lottery' => 'bbin',
                'ob_lottery' => 'ob',
                'bg_lottery' => 'bg',
                
                'jdb_chess' => 'jdb',
                'cq9_chess' => 'cq9',
                'bbin_chess' => 'bbin',
                'ob_card' => 'ob',
                'fg_chess' => 'fg',
                'mt_chess' => 'mt',
                'bg_chess' => 'bg',
                'ww_chess' => 'ww',
                'wl_chess' => 'wl',
                
                'ob_fish' => 'ob',
                'jdb_fish' => 'jdb',
                'cq9_fish' => 'cq9',
                'fc_fish' => 'fc',
                
                'ob_sport' => 'ob',
                
            ];
            if (isset($platformCodeMap[$platType])) {
                $platType = $platformCodeMap[$platType];
            }
            
            if (empty($sn) || empty($secretKey)) {
                Log::error("NG平台配置不完整", ['sn' => $sn ? '已配置' : '未配置', 'secretKey' => $secretKey ? '已配置' : '未配置']);
                throw new \Exception('NG平台配置不完整');
            }
            
            
            $this->createNGMember($user, $apiUrl, $sn, $secretKey, $platType);
            
            
            if ($user->balance > 0) {
                $this->transferToNG($user, $apiUrl, $sn, $secretKey, $platType, $user->balance);
            }
            
            
            $random = $this->generateRandomString(32);
            $sign = strtolower(md5($random . $sn . $secretKey));
            
            
            
            $gameTypeMap = [
                'live' => '1',      
                'slot' => '2',      
                'lottery' => '3',   
                'sport' => '4',     
                'esport' => '5',    
                'fish' => '6',      
                'fishing' => '6',   
                'chess' => '7',     
            ];
            
            
            $gameType = '1';  
            if ($gameId !== 'lobby') {
                $gameType = $gameTypeMap[$this->getGameType($gameId)] ?? '2';
            } else {
                
                $livePlatforms = ['ag', 'bbin', 'bg', 'dg', 'og', 'wm', 'evo', 'sexy', 'allbet', 'we'];
                $slotPlatforms = ['pg', 'jdb', 'cq9', 'pp', 'pt', 'mg', 'jili', 'fc', 'fg', 'hb', 'ka', 'joker', 'rsg', 'rt', 'mw', 't1', 'yoo', 'wl', 'boya'];
                $chessPlatforms = ['ky', 'vg', 'leg', 'v8', 'boya', 'mt', 'ww', 'jdb_chess', 'cq9_chess', 'bbin_chess', 'ob_card', 'fg_chess', 'mt_chess', 'bg_chess', 'ww_chess', 'wl_chess'];
                $sportPlatforms = ['im', 'saba', 'bti', 'cmd', 'fb', 'panda', 'ss', 'sbo', 'ug', 'xj', 'ap', 'bl', 'ob_sport'];
                $esportPlatforms = ['tf', 'esb', 'db5'];
                $fishPlatforms = ['ag', 'bbin', 'bg', 'cq9', 'jdb', 'fc', 'wl', 'joker', 'mg'];
                $lotteryPlatforms = ['tcg', 'sgwin', 'db1', 'db2', 'db3', 'db6', 'db7', 'crown', 'boya', 'bbin_lottery', 'ob_lottery', 'bg_lottery', 'vr'];
                
                
                if (in_array($originalPlatform, $lotteryPlatforms)) {
                    $gameType = '3';  
                } elseif (in_array($originalPlatform, $esportPlatforms)) {
                    $gameType = '5';  
                } elseif (in_array($originalPlatform, $fishPlatforms)) {
                    $gameType = '6';  
                } elseif (in_array($originalPlatform, $livePlatforms)) {
                    $gameType = '1';  
                } elseif (in_array($originalPlatform, $slotPlatforms)) {
                    $gameType = '2';  
                } elseif (in_array($originalPlatform, $sportPlatforms)) {
                    $gameType = '4';  
                } elseif (in_array($originalPlatform, $chessPlatforms)) {
                    $gameType = '7';  
                }
            }
            
            $params = [
                'playerId' => $user->username,
                'platType' => $platType,
                'currency' => 'CNY',
                'ingress' => $device === 'mobile' ? 'device2' : 'device1',
                'gameType' => $gameType,
                'gameCode' => $gameCode,
            ];
            
            $jsonData = json_encode($params);
            $headers = [
                'Content-Type: application/json',
                'Content-Length: ' . strlen($jsonData),
                'sign: ' . $sign,
                'random: ' . $random,
                'sn: ' . $sn,
            ];
            
            $url = $apiUrl . '/api/server/gameUrl';
            
            $response = $this->httpPostWithHeaders($url, $jsonData, $headers);
            
            if (isset($response['code']) && $response['code'] == '10000' && isset($response['data']['url'])) {
                return $response['data']['url'];
            }
            
            
            $errorMsg = $this->getNGErrorMessage($response['code'] ?? '0', $response['msg'] ?? '');
            Log::error("NG API失败", ['code' => $response['code'] ?? '', 'msg' => $errorMsg, 'params' => $params]);
            
            throw new \Exception($errorMsg);
            
        } catch (\Exception $e) {
            Log::error("NG游戏URL生成失败: " . $e->getMessage());
            throw new \Exception('游戏进入失败: ' . $e->getMessage());
        }
    }
    
    
    public function syncNGGames(string $platType = ''): array
    {
        try {
            $platformConfig = Db::table('caipiao_game_platform')->where('code', 'NG')->first();
            
            $apiUrl = $platformConfig->api_url ?? env('NG_API_URL', 'https://api.ng777.net');
            $sn = $platformConfig->api_key ?? env('NG_SN', '');
            $secretKey = $platformConfig->api_secret ?? env('NG_SECRET_KEY', '');
            
            if (empty($sn) || empty($secretKey)) {
                return ['success' => false, 'msg' => 'NG平台配置不完整'];
            }
            
            $random = $this->generateRandomString(32);
            $sign = strtolower(md5($random . $sn . $secretKey));
            
            $params = ['platType' => strtolower($platType)];
            
            $headers = [
                'Content-Type: application/json',
                'sign: ' . $sign,
                'random: ' . $random,
                'sn: ' . $sn,
            ];
            
            $url = $apiUrl . '/api/server/gameCode';
            
            $response = $this->httpPostWithHeaders($url, $params, $headers);
            
            if (isset($response['code']) && $response['code'] == '10000' && isset($response['data'])) {
                $games = $response['data'];
                $synced = 0;
                $errors = [];
                
                Log::info("NG 同步游戏开始", ['platType' => $platType, 'total' => count($games)]);
                
                foreach ($games as $game) {
                    try {
                        $gameCode = $game['gameCode'] ?? $game['code'] ?? '';
                        if (empty($gameCode)) {
                            continue;
                        }
                        
                        $gameId = strtoupper($platType) . '_' . $gameCode;
                        
                        
                        $gameName = $gameId;
                        if (isset($game['gameName'])) {
                            if (is_array($game['gameName'])) {
                                $gameName = $game['gameName']['zh-hans'] 
                                    ?? $game['gameName']['zh-hant'] 
                                    ?? $game['gameName']['en'] 
                                    ?? $gameId;
                            } elseif (is_string($game['gameName'])) {
                                $gameName = $game['gameName'];
                            }
                        } elseif (isset($game['name'])) {
                            $gameName = is_string($game['name']) ? $game['name'] : $gameId;
                        }
                        
                        
                        $gameType = $this->mapNGGameType($game['gameType'] ?? '3');
                        
                        
                        $icon = '';
                        $cover = '';
                        
                        
                        if (!empty($game['icon']) && is_string($game['icon'])) {
                            $icon = $game['icon'];
                        }
                        if (!empty($game['img']) && is_string($game['img'])) {
                            $cover = $game['img'];
                        } elseif (!empty($game['imageUrl']) && is_string($game['imageUrl'])) {
                            $cover = $game['imageUrl'];
                        }
                        
                        
                        if (empty($icon) && empty($cover)) {
                            
                            $ngIconBase = 'https://image-uz.ng-demo.xyz/image//game_image/square';
                            $icon = "{$ngIconBase}/" . strtolower($platType) . "/zh-hans/{$gameCode}.webp";
                            $cover = $icon;
                        } elseif (empty($cover)) {
                            $cover = $icon;
                        } elseif (empty($icon)) {
                            $icon = $cover;
                        }
                        
                        
                        $exists = Db::table('caipiao_game')->where('game_id', $gameId)->exists();
                        
                        $data = [
                            'game_id' => $gameId,
                            'name' => mb_substr($gameName, 0, 100), 
                            'platform' => strtoupper($platType),
                            'type' => $gameType,
                            'icon' => $icon,
                            'cover' => $cover,
                            'status' => 'online',
                            'updated_at' => time(),
                        ];
                        
                        if ($exists) {
                            Db::table('caipiao_game')->where('game_id', $gameId)->update($data);
                        } else {
                            $data['created_at'] = time();
                            $data['sort'] = 0;
                            $data['hot'] = 0;
                            $data['new'] = 0;
                            Db::table('caipiao_game')->insert($data);
                        }
                        $synced++;
                    } catch (\Exception $e) {
                        $errors[] = ['gameCode' => $gameCode ?? 'unknown', 'error' => $e->getMessage()];
                        Log::warning("NG 同步单个游戏失败", ['game' => $game, 'error' => $e->getMessage()]);
                    }
                }
                
                Log::info("NG 同步游戏完成", ['platType' => $platType, 'synced' => $synced, 'errors' => count($errors)]);
                
                return [
                    'success' => true, 
                    'msg' => "同步成功", 
                    'count' => $synced, 
                    'total' => count($games),
                    'errors' => count($errors)
                ];
            }
            
            return ['success' => false, 'msg' => $response['msg'] ?? '同步失败', 'response' => $response];
            
        } catch (\Exception $e) {
            Log::error("NG 同步游戏失败: " . $e->getMessage());
            return ['success' => false, 'msg' => $e->getMessage()];
        }
    }
    
    
    public function getNGPlatforms(): array
    {
        
        return [
            
            ['code' => 'ag', 'name' => 'AG视讯', 'type' => 'live'],
            ['code' => 'bbin', 'name' => 'BBIN视讯', 'type' => 'live'],
            ['code' => 'bg', 'name' => 'BG视讯', 'type' => 'live'],
            ['code' => 'dg', 'name' => 'DG视讯', 'type' => 'live'],
            ['code' => 'og', 'name' => 'OG视讯', 'type' => 'live'],
            ['code' => 'wm', 'name' => 'WM视讯', 'type' => 'live'],
            ['code' => 'evo', 'name' => 'EVO视讯', 'type' => 'live'],
            ['code' => 'sexy', 'name' => 'SEXY视讯', 'type' => 'live'],
            ['code' => 'allbet', 'name' => '欧博视讯', 'type' => 'live'],
            ['code' => 'sa', 'name' => 'SA视讯', 'type' => 'live'],
            
            
            ['code' => 'pg', 'name' => 'PG电子', 'type' => 'slot'],
            ['code' => 'cq9', 'name' => 'CQ9电子', 'type' => 'slot'],
            ['code' => 'jdb', 'name' => 'JDB电子', 'type' => 'slot'],
            ['code' => 'jili', 'name' => 'JILI电子', 'type' => 'slot'],
            ['code' => 'pp', 'name' => 'PP电子', 'type' => 'slot'],
            ['code' => 'pt', 'name' => 'PT电子', 'type' => 'slot'],
            ['code' => 'mg', 'name' => 'MG电子', 'type' => 'slot'],
            ['code' => 'fc', 'name' => 'FC电子', 'type' => 'slot'],
            ['code' => 'fg', 'name' => 'FG电子', 'type' => 'slot'],
            ['code' => 'sg', 'name' => 'SG电子', 'type' => 'slot'],
            
            
            ['code' => 'im', 'name' => 'IM体育', 'type' => 'sport'],
            ['code' => 'saba', 'name' => '沙巴体育', 'type' => 'sport'],
            ['code' => 'cmd', 'name' => 'CMD体育', 'type' => 'sport'],
            ['code' => 'fb', 'name' => 'FB体育', 'type' => 'sport'],
            
            
            ['code' => 'ky', 'name' => '开元棋牌', 'type' => 'chess'],
            ['code' => 'vg', 'name' => 'VG棋牌', 'type' => 'chess'],
            ['code' => 'leg', 'name' => '乐游棋牌', 'type' => 'chess'],
            
            
            ['code' => 'cq9', 'name' => 'CQ9捕鱼', 'type' => 'fish'],
            ['code' => 'jdb', 'name' => 'JDB捕鱼', 'type' => 'fish'],
            
            
            ['code' => 'tcg', 'name' => 'TCG彩票', 'type' => 'lottery'],
            ['code' => 'ig', 'name' => 'IG彩票', 'type' => 'lottery'],
            
            
            ['code' => 'esb', 'name' => 'ESB电竞', 'type' => 'esport'],
            ['code' => 'tf', 'name' => 'TF电竞', 'type' => 'esport'],
        ];
    }
    
    
    public function syncNGPlatformsToDb(): array
    {
        $platforms = $this->getNGPlatforms();
        $synced = 0;
        
        foreach ($platforms as $p) {
            $code = strtoupper($p['code']);
            $exists = Db::table('caipiao_game_platform')->where('code', $code)->exists();
            
            $data = [
                'code' => $code,
                'name' => $p['name'],
                'type' => $p['type'],
                'kind' => 'third',
                'status' => 'online',
                'updated_at' => time(),
            ];
            
            if ($exists) {
                Db::table('caipiao_game_platform')->where('code', $code)->update($data);
            } else {
                $data['created_at'] = time();
                $data['sort'] = 0;
                $data['hot'] = 0;
                Db::table('caipiao_game_platform')->insert($data);
            }
            $synced++;
        }
        
        return ['success' => true, 'msg' => "同步成功", 'count' => $synced];
    }
    
    
    private function mapNGGameType(string $ngType): string
    {
        $map = [
            '1' => 'live',      
            '2' => 'slot',      
            '3' => 'lottery',   
            '4' => 'sport',     
            '5' => 'esport',    
            '6' => 'fishing',   
            '7' => 'chess',     
        ];
        return $map[$ngType] ?? 'slot';
    }
    
    
    private function getNGErrorMessage($code, $defaultMsg = ''): string
    {
        $errorMap = [
            '10001' => '签名错误',
            '10002' => '商户不存在',
            '10003' => '商户已禁用',
            '10004' => 'IP不在白名单',
            '10005' => '参数错误',
            '10006' => '玩家不存在',
            '10007' => '平台不支持',
            '10008' => '游戏不存在',
            '10009' => '余额不足',
            '10010' => '转账失败',
            '10101' => '玩家已存在',
            '10102' => '创建玩家失败',
        ];
        
        return $errorMap[$code] ?? ($defaultMsg ?: '未知错误');
    }
    
    
    private function generateGameLaunchPage($user, $platform, $gameCode, $gameId): string
    {
        $platformNames = [
            'KA' => '开元棋牌',
            'VG' => 'VG棋牌',
            'AG' => 'AG视讯',
            'BBIN' => 'BBIN视讯',
            'OG' => 'OG视讯',
            'DG' => 'DG视讯',
            'WM' => 'WM视讯',
            'IM' => 'IM体育',
            'BTI' => 'BTI体育',
            'SABA' => '沙巴体育',
            'CQ9' => 'CQ9电子',
            'PT' => 'PT电子',
            'MG' => 'MG电子',
            'PG' => 'PG电子',
        ];
        
        $platformName = $platformNames[$platform] ?? $platform;
        
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title>{$platformName}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 40px;
            max-width: 500px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            text-align: center;
        }
        .logo {
            font-size: 64px;
            margin-bottom: 20px;
            animation: bounce 2s infinite;
        }
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
        h1 {
            color: #333;
            font-size: 28px;
            margin-bottom: 10px;
        }
        .platform {
            color: #667eea;
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 20px;
        }
        .info {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
            text-align: left;
        }
        .info-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e9ecef;
        }
        .info-item:last-child {
            border-bottom: none;
        }
        .label {
            color: #6c757d;
            font-size: 14px;
        }
        .value {
            color: #333;
            font-weight: 500;
        }
        .notice {
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 10px;
            padding: 15px;
            margin-top: 20px;
            color: #856404;
            font-size: 14px;
            line-height: 1.6;
        }
        .btn {
            margin-top: 20px;
            padding: 15px 40px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 25px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: transform 0.2s;
        }
        .btn:hover {
            transform: scale(1.05);
        }
        .footer {
            margin-top: 30px;
            color: #6c757d;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">🎮</div>
        <h1>游戏即将启动</h1>
        <div class="platform">{$platformName}</div>
        
        <div class="info">
            <div class="info-item">
                <span class="label">游戏平台</span>
                <span class="value">{$platform}</span>
            </div>
            <div class="info-item">
                <span class="label">游戏代码</span>
                <span class="value">{$gameCode}</span>
            </div>
            <div class="info-item">
                <span class="label">游戏ID</span>
                <span class="value">{$gameId}</span>
            </div>
            <div class="info-item">
                <span class="label">用户</span>
                <span class="value">{$user->username}</span>
            </div>
        </div>
        
        <div class="notice">
            <strong>⚠️ 重要提示</strong><br>
            当前游戏平台正在对接NG API中。<br>
            <br>
            <strong>需要完成的配置：</strong><br>
            1. 登录NG商户后台获取正确的API端点<br>
            2. 添加服务器IP到NG白名单<br>
            3. 联系技术支持获取完整API文档<br>
            <br>
            商户号：ck197988<br>
            API域名：api.ng777.net
        </div>
        
        <button class="btn" onclick="window.close()">返回</button>
        
        <div class="footer">
            © 2025 Game Platform | Powered by NG API
        </div>
    </div>
</body>
</html>
HTML;
    }
    
    
    private function createNGMember($user, $apiUrl, $sn, $secretKey, $platform): void
    {
        try {
            $random = $this->generateRandomString(32);
            $sign = strtolower(md5($random . $sn . $secretKey));
            
            $params = [
                'playerId' => $user->username,
                'platType' => strtolower($platform),
                'currency' => 'CNY',
            ];
            
            $jsonData = json_encode($params);
            $headers = [
                'Content-Type: application/json',
                'Content-Length: ' . strlen($jsonData),
                'sign: ' . $sign,
                'random: ' . $random,
                'sn: ' . $sn,
            ];
            
            $url = $apiUrl . '/api/server/create';
            
            $response = $this->httpPostWithHeaders($url, $jsonData, $headers);
            
            
            if (isset($response['code']) && in_array($response['code'], ['10000', '10002', '10101', 10000, 10002, 10101])) {
                return;
            }
            
            Log::warning("NG创建会员失败", ['response' => $response]);
            
        } catch (\Exception $e) {
            
            Log::error("NG创建会员异常: " . $e->getMessage());
        }
    }
    
    
    private function transferToNG($user, $apiUrl, $sn, $secretKey, $platType, $amount): bool
    {
        try {
            $amount = floor($amount);  
            if ($amount <= 0) {
                return false;
            }
            
            $random = $this->generateRandomString(32);
            $sign = strtolower(md5($random . $sn . $secretKey));
            
            $orderId = 'T' . date('YmdHis') . mt_rand(1000, 9999);
            
            $params = [
                'playerId' => $user->username,
                'platType' => strtolower($platType),
                'currency' => 'CNY',
                'amount' => $amount,
                'type' => 1,  
                'orderId' => $orderId,
            ];
            
            $jsonData = json_encode($params);
            $headers = [
                'Content-Type: application/json',
                'Content-Length: ' . strlen($jsonData),
                'sign: ' . $sign,
                'random: ' . $random,
                'sn: ' . $sn,
            ];
            
            $url = $apiUrl . '/api/server/transfer';
            $response = $this->httpPostWithHeaders($url, $jsonData, $headers);
            
            if (isset($response['code']) && $response['code'] == '10000') {
                
                Db::table('caipiao_member')
                    ->where('id', $user->id)
                    ->decrement('balance', $amount);
                
                Log::info("NG转账成功", [
                    'playerId' => $user->username,
                    'platType' => $platType,
                    'amount' => $amount,
                    'orderId' => $orderId
                ]);
                return true;
            } else {
                Log::warning("NG转账失败", [
                    'playerId' => $user->username,
                    'platType' => $platType,
                    'amount' => $amount,
                    'response' => $response
                ]);
                return false;
            }
        } catch (\Exception $e) {
            Log::error("NG转账异常: " . $e->getMessage());
            return false;
        }
    }
    
    
    private function getGameType(string $gameId): string
    {
        
        $game = Db::table('caipiao_game')
            ->where('game_id', $gameId)
            ->first(['type']);
        
        if ($game && !empty($game->type)) {
            
            $typeMap = [
                'live' => 'live',
                'slot' => 'slot',
                'lottery' => 'lottery',
                'fishing' => 'fish',
                'fish' => 'fish',
                'sport' => 'sport',
                'chess' => 'chess',
                'esport' => 'esport',
            ];
            return $typeMap[$game->type] ?? 'slot';
        }
        
        
        $gameIdUpper = strtoupper($gameId);
        
        
        if (str_contains($gameIdUpper, 'LIVE') ||
            str_contains($gameIdUpper, 'OG_') || 
            str_contains($gameIdUpper, 'DG_') || str_contains($gameIdUpper, 'WM_') ||
            str_contains($gameIdUpper, 'EVO_') || str_contains($gameIdUpper, 'SEXY_') ||
            str_contains($gameIdUpper, 'ALLBET_') || str_contains($gameIdUpper, 'SA_') ||
            str_contains($gameIdUpper, 'BG_LIVE') || str_contains($gameIdUpper, 'XG_') ||
            str_contains($gameIdUpper, 'WE_') || str_contains($gameIdUpper, 'OB_LIVE')) {
            return 'live';
        }
        
        
        if (str_contains($gameIdUpper, 'LOTTERY') || str_contains($gameIdUpper, 'IG_') || 
            str_contains($gameIdUpper, 'TCG_') || str_contains($gameIdUpper, 'SGWIN_') ||
            str_contains($gameIdUpper, 'BG_LOTTERY') || str_contains($gameIdUpper, 'OB_LOTTERY')) {
            return 'lottery';
        }
        
        
        if (str_contains($gameIdUpper, 'FISH') || str_contains($gameIdUpper, 'FISHING') ||
            str_contains($gameIdUpper, 'OB_FISH') || str_contains($gameIdUpper, 'JDB_FISH') ||
            str_contains($gameIdUpper, 'CQ9_FISH') || str_contains($gameIdUpper, 'FC_FISH')) {
            return 'fish';
        }
        
        
        if (str_contains($gameIdUpper, 'SPORT') || str_contains($gameIdUpper, 'IM_') || 
            str_contains($gameIdUpper, 'BTI_') || str_contains($gameIdUpper, 'SABA_') ||
            str_contains($gameIdUpper, 'CMD_') || str_contains($gameIdUpper, 'SBO_') ||
            str_contains($gameIdUpper, 'SS_') || str_contains($gameIdUpper, 'UG_') ||
            str_contains($gameIdUpper, 'FB_') || str_contains($gameIdUpper, 'PANDA_') ||
            str_contains($gameIdUpper, 'OB_SPORT') || str_contains($gameIdUpper, 'XJ_')) {
            return 'sport';
        }
        
        
        if (str_contains($gameIdUpper, 'CHESS') || str_contains($gameIdUpper, 'CARD') ||
            str_contains($gameIdUpper, 'KY_') || str_contains($gameIdUpper, 'VG_') ||
            str_contains($gameIdUpper, 'LEG_') || str_contains($gameIdUpper, 'MT_') ||
            str_contains($gameIdUpper, 'NW_') || str_contains($gameIdUpper, 'WW_') ||
            str_contains($gameIdUpper, 'V8_') || str_contains($gameIdUpper, 'OB_CARD') ||
            str_contains($gameIdUpper, 'BOYA_')) {
            return 'chess';
        }
        
        
        if (str_contains($gameIdUpper, 'ESPORT') || str_contains($gameIdUpper, 'ESB_') ||
            str_contains($gameIdUpper, 'TF_') || str_contains($gameIdUpper, 'OB_ESPORT')) {
            return 'esport';
        }
        
        
        return 'slot';
    }
    
    
    private function getNGGameCode(string $gameId): string
    {
        
        if (strtolower($gameId) === 'lobby') {
            return '';
        }
        
        
        
        $pos = strpos($gameId, '_');
        if ($pos !== false) {
            $gameCode = substr($gameId, $pos + 1);
            
            
            if (!empty($gameCode) && $gameCode !== 'LOBBY') {
                return $gameCode;
            }
        }
        
        
        return $gameId;
    }
    
    
    private function getNGPlatform(string $gameId): string
    {
        $gameIdUpper = strtoupper($gameId);
        
        
        $platformMap = [
            
            'AG_' => 'ag',
            'BBIN_' => 'bbin',
            'BG_' => 'bg',
            'DG_' => 'dg',
            'OG_' => 'og',
            'WM_' => 'wm',
            'EVO_' => 'evo',
            'SEXY_' => 'sexy',
            'ALLBET_' => 'allbet',
            'SA_' => 'sa',
            'XG_' => 'xg',
            'WE_' => 'we',
            'OB_LIVE_' => 'ob',
            
            
            'CQ9_' => 'cq9',
            'PT_' => 'pt',
            'MG_' => 'mg',
            'PG_' => 'pg',
            'PP_' => 'pp',
            'JDB_' => 'jdb',
            'JILI_' => 'jili',
            'FC_' => 'fc',
            'FG_' => 'fg',
            'SG_' => 'sg',
            'MW_' => 'mw',
            'RSG_' => 'rsg',
            'RT_' => 'rt',
            'PNG_' => 'png',
            'TTG_' => 'ttg',
            'HB_' => 'hb',
            'KA_' => 'ka',
            'JOKER_' => 'joker',
            'OB_SLOT_' => 'ob',
            
            
            'IM_' => 'im',
            'SABA_' => 'saba',
            'BTI_' => 'saba',  
            'CMD_' => 'cmd',
            'SBO_' => 'sbo',
            'SS_' => 'ss',
            'UG_' => 'ug',
            'FB_' => 'fb',
            'PANDA_' => 'panda',
            'XJ_' => 'xj',
            'OB_SPORT_' => 'ob',
            
            
            'KY_' => 'ky',
            'VG_' => 'vg',
            'LEG_' => 'leg',
            'MT_' => 'mt',
            'NW_' => 'nw',
            'WW_' => 'ww',
            'V8_' => 'v8',
            'BOYA_' => 'boya',
            'OB_CARD_' => 'ob',
            
            
            'IG_' => 'ig',
            'TCG_' => 'tcg',
            'SGWIN_' => 'sgwin',
            'OB_LOTTERY_' => 'ob',
            
            
            'OB_FISH_' => 'ob',
            
            
            'ESB_' => 'esb',
            'TF_' => 'tf',
            'OB_ESPORT_' => 'ob',
            
            
            'NG_' => 'pg',
            'AP_' => 'ap',
            'VR_' => 'vr',
            'GB_' => 'gb',
            'GW_' => 'gw',
            'PS_' => 'ps',
            'R88_' => 'r88',
            'T1_' => 't1',
            'WL_' => 'wl',
            'YOO_' => 'yoo',
        ];
        
        
        foreach ($platformMap as $prefix => $platform) {
            if (str_starts_with($gameIdUpper, $prefix)) {
                return $platform;
            }
        }
        
        
        return 'pg';
    }
    
    
    private function generateRandomString(int $length = 32): string
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyz';
        $randomString = '';
        $max = strlen($characters) - 1;
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[random_int(0, $max)];
        }
        return $randomString;
    }
    
    
    private function httpPostWithHeaders(string $url, $data, array $headers): array
    {
        $jsonData = is_string($data) ? $data : json_encode($data);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        
        $response = curl_exec($ch);
        $error = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($error) {
            Log::error("HTTP请求失败", ['error' => $error, 'url' => $url]);
            throw new \Exception('HTTP请求失败: ' . $error);
        }
        
        if ($httpCode != 200) {
            Log::error("HTTP状态码错误", [
                'httpCode' => $httpCode,
                'url' => $url,
                'response' => $response
            ]);
            throw new \Exception('HTTP状态码错误: ' . $httpCode);
        }
        
        $result = json_decode($response, true);
        if (!$result) {
            Log::error("响应解析失败", ['response' => $response]);
            throw new \Exception('响应解析失败: ' . $response);
        }
        
        return $result;
    }
    
    
    private function getKYGameUrl($user, $gameId, $device, $platformConfig): string
    {
        try {
            $apiUrl = $platformConfig->api_url ?: 'https://api.ky.com';
            $apiKey = $platformConfig->api_key ?: '';
            
            
            $params = [
                's' => '0', 
                'account' => $user->username,
                'money' => 0, 
                'orderid' => date('YmdHis') . $user->id,
                'ip' => request()->getRealIp(),
                'lineCode' => 'default',
                'KindID' => $this->getKYKindID($gameId), 
            ];
            
            
            $params['key'] = $this->generateKYSign($params, $apiKey);
            
            
            $gameUrl = $apiUrl . '/Mobile/MobileLoader.aspx?' . http_build_query($params);
            
            return $gameUrl;
            
        } catch (\Exception $e) {
            Log::error("KY游戏URL生成失败: " . $e->getMessage());
            throw new \Exception('游戏进入失败');
        }
    }
    
    
    private function getAGGameUrl($user, $gameId, $device, $platformConfig): string
    {
        try {
            $apiUrl = $platformConfig->api_url ?: 'https://api.ag.com';
            $apiKey = $platformConfig->api_key ?: '';
            
            
            $params = [
                'cagent' => 'XXXX', 
                'loginname' => $user->username,
                'password' => md5($user->password),
                'dm' => $device === 'mobile' ? 1 : 0,
                'sid' => session_id(),
                'actype' => 1, 
                'lang' => 'zh-cn',
                'gameType' => $this->getAGGameType($gameId),
            ];
            
            
            $response = $this->httpPost($apiUrl . '/api/login', $params);
            
            if (isset($response['url'])) {
                return $response['url'];
            }
            
            throw new \Exception('获取游戏URL失败');
            
        } catch (\Exception $e) {
            Log::error("AG游戏URL生成失败: " . $e->getMessage());
            throw new \Exception('游戏进入失败');
        }
    }
    
    
    private function getBBINGameUrl($user, $gameId, $device, $platformConfig): string
    {
        try {
            $apiUrl = $platformConfig->api_url ?: 'https://api.bbin.com';
            $apiKey = $platformConfig->api_key ?: '';
            
            $params = [
                'website' => 'XXXX',
                'username' => $user->username,
                'gamekind' => $this->getBBINGameKind($gameId),
                'lang' => 'zh-cn',
            ];
            
            $gameUrl = $apiUrl . '/app/WebHome/V2/GameLogin?' . http_build_query($params);
            
            return $gameUrl;
            
        } catch (\Exception $e) {
            Log::error("BBIN游戏URL生成失败: " . $e->getMessage());
            throw new \Exception('游戏进入失败');
        }
    }
    
    
    private function getOGGameUrl($user, $gameId, $device, $platformConfig): string
    {
        
        $apiUrl = $platformConfig->api_url ?: 'https://api.og.com';
        return $apiUrl . '/game?token=' . $this->generateToken($user, 'OG');
    }
    
    
    private function getDGGameUrl($user, $gameId, $device, $platformConfig): string
    {
        
        $apiUrl = $platformConfig->api_url ?: 'https://api.dg.com';
        return $apiUrl . '/game?token=' . $this->generateToken($user, 'DG');
    }
    
    
    private function getWMGameUrl($user, $gameId, $device, $platformConfig): string
    {
        
        $apiUrl = $platformConfig->api_url ?: 'https://api.wm.com';
        return $apiUrl . '/game?token=' . $this->generateToken($user, 'WM');
    }
    
    
    private function getIMGameUrl($user, $gameId, $device, $platformConfig): string
    {
        
        $apiUrl = $platformConfig->api_url ?: 'https://api.imsports.com';
        return $apiUrl . '/game?token=' . $this->generateToken($user, 'IM');
    }
    
    
    private function getBTIGameUrl($user, $gameId, $device, $platformConfig): string
    {
        
        $apiUrl = $platformConfig->api_url ?: 'https://api.bti.com';
        return $apiUrl . '/game?token=' . $this->generateToken($user, 'BTI');
    }
    
    
    private function getSABAGameUrl($user, $gameId, $device, $platformConfig): string
    {
        
        $apiUrl = $platformConfig->api_url ?: 'https://api.saba.com';
        return $apiUrl . '/game?token=' . $this->generateToken($user, 'SABA');
    }
    
    
    private function getCQ9GameUrl($user, $gameId, $device, $platformConfig): string
    {
        
        $apiUrl = $platformConfig->api_url ?: 'https://api.cq9.com';
        return $apiUrl . '/game?token=' . $this->generateToken($user, 'CQ9');
    }
    
    
    private function getPTGameUrl($user, $gameId, $device, $platformConfig): string
    {
        
        $apiUrl = $platformConfig->api_url ?: 'https://api.pt.com';
        return $apiUrl . '/game?token=' . $this->generateToken($user, 'PT');
    }
    
    
    private function getMGGameUrl($user, $gameId, $device, $platformConfig): string
    {
        
        $apiUrl = $platformConfig->api_url ?: 'https://api.mg.com';
        return $apiUrl . '/game?token=' . $this->generateToken($user, 'MG');
    }
    
    
    private function getVGGameUrl($user, $gameId, $device, $platformConfig): string
    {
        
        $apiUrl = $platformConfig->api_url ?: 'https://api.vg.com';
        return $apiUrl . '/game?token=' . $this->generateToken($user, 'VG');
    }
    
    
    private function generateToken($user, $platform): string
    {
        $data = [
            'uid' => $user->id,
            'username' => $user->username,
            'platform' => $platform,
            'time' => time(),
        ];
        
        return base64_encode(json_encode($data));
    }
    
    
    private function generateKYSign(array $params, string $apiKey): string
    {
        ksort($params);
        $str = '';
        foreach ($params as $key => $value) {
            if ($key !== 'key') {
                $str .= $key . '=' . $value . '&';
            }
        }
        $str .= 'key=' . $apiKey;
        return md5($str);
    }
    
    
    private function getKYKindID(string $gameId): int
    {
        $map = [
            'KY_LOBBY' => 0,      
            'KY_DDZ' => 101,      
            'KY_MJ' => 102,       
            'KY_POKER' => 103,    
            'KY_BJL' => 104,      
        ];
        
        return $map[$gameId] ?? 0;
    }
    
    
    private function getAGGameType(string $gameId): string
    {
        $map = [
            'AG_LIVE_001' => 'LIVE',  
            'AG_LIVE_002' => 'LIVE',
            'AG_LIVE_003' => 'LIVE',
        ];
        
        return $map[$gameId] ?? 'LIVE';
    }
    
    
    private function getBBINGameKind(string $gameId): string
    {
        $map = [
            'BBIN_LIVE_001' => 'LIVE',
        ];
        
        return $map[$gameId] ?? 'LIVE';
    }
    
    
    private function httpPost(string $url, array $data): array
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new \Exception('HTTP请求失败: ' . $error);
        }
        
        return json_decode($response, true) ?: [];
    }
    
    
    public function getGameName(string $platform, string $gameId = ''): string
    {
        if ($gameId) {
            $game = Db::table('caipiao_game')
                ->where('game_id', $gameId)
                ->first();
            
            if ($game) {
                return $game->name;
            }
        }
        
        $platformInfo = Db::table('caipiao_game_platform')
            ->where('code', $platform)
            ->first();
        
        return $platformInfo ? $platformInfo->name : $platform;
    }
    
    
    public function pullBetRecords(int $page = 1, int $pageSize = 200): array
    {
        try {
            $platformConfig = Db::table('caipiao_game_platform')->where('code', 'NG')->first();
            
            $apiUrl = $platformConfig->api_url ?? env('NG_API_URL', 'https://ap.api-bet.net');
            $sn = $platformConfig->api_key ?? env('NG_SN', '');
            $secretKey = $platformConfig->api_secret ?? env('NG_SECRET_KEY', '');
            
            if (empty($sn) || empty($secretKey)) {
                return ['success' => false, 'msg' => 'NG平台配置不完整'];
            }
            
            $random = $this->generateRandomString(32);
            $sign = strtolower(md5($random . $sn . $secretKey));
            
            $params = [
                'pageNo' => $page,
                'pageSize' => $pageSize,
            ];
            
            $headers = [
                'Content-Type: application/json',
                'sign: ' . $sign,
                'random: ' . $random,
                'sn: ' . $sn,
            ];
            
            $url = rtrim($apiUrl, '/') . '/api/server/recordAll';
            
            $response = $this->httpPostWithHeaders($url, $params, $headers);
            
            if (isset($response['code']) && $response['code'] == '10000' && isset($response['data'])) {
                $records = $response['data']['list'] ?? [];
                $total = $response['data']['total'] ?? count($records);
                
                return [
                    'success' => true,
                    'msg' => '拉取成功',
                    'total' => $total,
                    'list' => $records,
                    'page' => $page,
                    'pageSize' => $pageSize
                ];
            }
            
            Log::error("NG 拉取投注记录失败", ['response' => $response]);
            return ['success' => false, 'msg' => $response['msg'] ?? '拉取失败', 'response' => $response];
            
        } catch (\Exception $e) {
            Log::error("NG 拉取投注记录异常: " . $e->getMessage());
            return ['success' => false, 'msg' => $e->getMessage()];
        }
    }
    
    
    public function syncBetRecords(): array
    {
        $result = $this->pullBetRecords();
        
        if (!$result['success']) {
            return $result;
        }
        
        return $this->saveBetRecords($result['list'] ?? []);
    }
    
    
    public function pullHistoryRecords(string $startTime, string $endTime, int $page = 1, int $pageSize = 200): array
    {
        try {
            $platformConfig = Db::table('caipiao_game_platform')->where('code', 'NG')->first();
            
            $apiUrl = $platformConfig->api_url ?? env('NG_API_URL', 'https://ap.api-bet.net');
            $sn = $platformConfig->api_key ?? env('NG_SN', '');
            $secretKey = $platformConfig->api_secret ?? env('NG_SECRET_KEY', '');
            
            if (empty($sn) || empty($secretKey)) {
                return ['success' => false, 'msg' => 'NG平台配置不完整'];
            }
            
            $random = $this->generateRandomString(32);
            $sign = strtolower(md5($random . $sn . $secretKey));
            
            $params = [
                'startTime' => $startTime,
                'endTime' => $endTime,
                'pageNo' => $page,
                'pageSize' => $pageSize,
            ];
            
            $headers = [
                'Content-Type: application/json',
                'sign: ' . $sign,
                'random: ' . $random,
                'sn: ' . $sn,
            ];
            
            $url = rtrim($apiUrl, '/') . '/api/server/recordHistory';
            
            $response = $this->httpPostWithHeaders($url, $params, $headers);
            
            if (isset($response['code']) && $response['code'] == '10000' && isset($response['data'])) {
                $records = $response['data']['list'] ?? [];
                $total = $response['data']['total'] ?? count($records);
                
                return [
                    'success' => true,
                    'msg' => '拉取成功',
                    'total' => $total,
                    'list' => $records,
                    'page' => $page,
                    'pageSize' => $pageSize
                ];
            }
            
            Log::error("NG 拉取历史记录失败", ['response' => $response]);
            return ['success' => false, 'msg' => $response['msg'] ?? '拉取失败', 'response' => $response];
            
        } catch (\Exception $e) {
            Log::error("NG 拉取历史记录异常: " . $e->getMessage());
            return ['success' => false, 'msg' => $e->getMessage()];
        }
    }
    
    
    public function syncHistoryRecords(string $startTime, string $endTime): array
    {
        $result = $this->pullHistoryRecords($startTime, $endTime);
        
        if (!$result['success']) {
            return $result;
        }
        
        return $this->saveBetRecords($result['list'] ?? []);
    }
    
    
    private function saveBetRecords(array $records): array
    {
        $synced = 0;
        $updated = 0;
        $commissionTriggered = 0;
        $errors = [];
        
        foreach ($records as $record) {
            try {
                $orderNo = $record['gameOrderId'] ?? '';
                if (empty($orderNo)) {
                    continue;
                }
                
                
                $playerId = $record['playerId'] ?? '';
                $uid = 0;
                
                
                if (strpos($playerId, '_') !== false) {
                    
                    $parts = explode('_', $playerId);
                    $uid = (int)end($parts);
                } else {
                    
                    $user = Db::table('caipiao_member')->where('username', $playerId)->first();
                    if ($user) {
                        $uid = $user->id;
                    }
                }
                
                if (!$uid) {
                    Log::warning("NG 投注记录无法解析用户", ['playerId' => $playerId]);
                    continue;
                }
                
                
                $gameTypeMap = [
                    '1' => 'live', '2' => 'slot', '3' => 'lottery',
                    '4' => 'sport', '5' => 'esport', '6' => 'fishing', '7' => 'chess',
                ];
                $gameType = $gameTypeMap[$record['gameType'] ?? '2'] ?? 'slot';
                
                $statusMap = ['0' => 'pending', '1' => 'settled', '2' => 'cancelled', '3' => 'cancelled'];
                $status = $statusMap[$record['status'] ?? '0'] ?? 'pending';
                
                $betAmount = abs(floatval($record['betAmount'] ?? 0));
                $winAmount = floatval($record['settledAmount'] ?? 0) + $betAmount;
                
                $data = [
                    'uid' => $uid,
                    'platform' => strtoupper($record['platType'] ?? ''),
                    'platform_name' => $this->getPlatformName($record['platType'] ?? ''),
                    'game_id' => $record['round'] ?? '',
                    'game_name' => $record['gameName'] ?? '',
                    'game_type' => $gameType,
                    'bet_amount' => $betAmount,
                    'valid_amount' => abs(floatval($record['validAmount'] ?? 0)),
                    'win_amount' => $winAmount,
                    'profit' => floatval($record['settledAmount'] ?? 0),
                    'bet_content' => $record['betContent'] ?? '',
                    'status' => $status,
                    'bet_time' => strtotime($record['betTime'] ?? 'now'),
                    'settle_time' => $status === 'settled' ? strtotime($record['lastUpdateTime'] ?? 'now') : 0,
                ];
                
                $exists = Db::table('caipiao_game_bet')->where('order_no', $orderNo)->first();
                $needTriggerCommission = false;
                
                if ($exists) {
                    
                    if ($exists->status !== 'settled' && $status === 'settled') {
                        $needTriggerCommission = true;
                    }
                    $data['updated_at'] = time();
                    Db::table('caipiao_game_bet')->where('order_no', $orderNo)->update($data);
                    $updated++;
                } else {
                    
                    if ($status === 'settled') {
                        $needTriggerCommission = true;
                    }
                    $data['order_no'] = $orderNo;
                    $data['created_at'] = time();
                    $data['updated_at'] = time();
                    Db::table('caipiao_game_bet')->insert($data);
                    $synced++;
                }
                
                
                if ($needTriggerCommission && $betAmount > 0) {
                    try {
                        \app\service\AgentCommissionService::onBetSettled(
                            $uid,
                            $betAmount,
                            $winAmount,
                            'third_' . $gameType  
                        );
                        $commissionTriggered++;
                    } catch (\Exception $e) {
                        
                        Log::warning("三方游戏佣金计算失败: " . $e->getMessage(), [
                            'uid' => $uid,
                            'orderNo' => $orderNo,
                            'betAmount' => $betAmount,
                            'winAmount' => $winAmount
                        ]);
                    }
                }
                
            } catch (\Exception $e) {
                $errors[] = ['orderNo' => $orderNo ?? 'unknown', 'error' => $e->getMessage()];
            }
        }
        
        
        
        return [
            'success' => true,
            'msg' => '同步完成',
            'synced' => $synced,
            'updated' => $updated,
            'commission_triggered' => $commissionTriggered,
            'total' => count($records),
            'errors' => count($errors)
        ];
    }
    
    
    private function getPlatformName(string $code): string
    {
        $platforms = [
            'pg' => 'PG电子',
            'ag' => 'AG视讯',
            'bbin' => 'BBIN',
            'cq9' => 'CQ9电子',
            'jdb' => 'JDB电子',
            'jili' => 'JILI电子',
            'pp' => 'PP电子',
            'mg' => 'MG电子',
            'pt' => 'PT电子',
            'ky' => '开元棋牌',
            'leg' => '乐游棋牌',
            'im' => 'IM体育',
            'saba' => '沙巴体育',
            'dg' => 'DG视讯',
            'wm' => 'WM视讯',
            'sexy' => 'SEXY视讯',
            'evo' => 'EVO视讯',
        ];
        
        return $platforms[strtolower($code)] ?? strtoupper($code);
    }
}
