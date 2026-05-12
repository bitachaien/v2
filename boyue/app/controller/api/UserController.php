<?php

namespace app\controller\api;

use support\Request;
use support\Db;

class UserController
{
    public function todayStats(Request $request)
    {
        $userId = $request->userId ?? 0;
        
        if (!$userId) {
            return json(['code' => 401, 'message' => '未登录或登录已过期', 'data' => null]);
        }
        
        try {
            $todayStart = strtotime(date('Y-m-d 00:00:00'));
            $todayEnd = strtotime(date('Y-m-d 23:59:59'));
            
            $betStats = Db::table('caipiao_touzhu')
                ->where('uid', $userId)
                ->where('oddtime', '>=', $todayStart)
                ->where('oddtime', '<=', $todayEnd)
                ->selectRaw('
                    SUM(amount) as bet,
                    SUM(CASE WHEN isdraw = 1 THEN okamount ELSE 0 END) as winAmount,
                    SUM(CASE WHEN isdraw != 0 THEN amount ELSE 0 END) as validBet
                ')
                ->first();
            
            $recharge = Db::table('caipiao_recharge')
                ->where('uid', $userId)
                ->where('oddtime', '>=', $todayStart)
                ->where('oddtime', '<=', $todayEnd)
                ->where('state', 1)
                ->sum('amount');
            
            $withdraw = Db::table('caipiao_withdraw')
                ->where('uid', $userId)
                ->where('oddtime', '>=', $todayStart)
                ->where('oddtime', '<=', $todayEnd)
                ->where('state', 1)
                ->sum('amount');
            
            $fanshui = 0;
            try {
                $fanshui = Db::table('caipiao_rebate')
                    ->where('userid', $userId)
                    ->where('date', date('Y-m-d'))
                    ->where('status', 'settled')
                    ->sum('rebate_amount');
            } catch (\Exception $e) {}
            
            $bet = $betStats->bet ?? 0;
            $winAmount = $betStats->winAmount ?? 0;
            $validBet = $betStats->validBet ?? 0;
            $profit = $winAmount - $bet;
            
            return json([
                'code' => 0,
                'message' => '获取成功',
                'data' => [
                    'profit' => number_format($profit, 2, '.', ''),
                    'fanshui' => number_format($fanshui, 2, '.', ''),
                    'recharge' => number_format($recharge ?? 0, 2, '.', ''),
                    'withdraw' => number_format($withdraw ?? 0, 2, '.', ''),
                    'bet' => number_format($bet, 2, '.', ''),
                    'winAmount' => number_format($winAmount, 2, '.', ''),
                    'validBet' => number_format($validBet, 2, '.', '')
                ]
            ]);
            
        } catch (\Exception $e) {
            return json(['code' => 500, 'message' => '获取数据失败', 'data' => null]);
        }
    }
    
    public function todayStatsDetail(Request $request)
    {
        $userId = $request->userId ?? 0;
        
        if (!$userId) {
            return json(['code' => 401, 'message' => '未登录或登录已过期', 'data' => null]);
        }
        
        try {
            $date = $request->get('date', date('Y-m-d'));
            $wallet = $request->get('wallet', 'main');
            
            $startTime = strtotime($date . ' 00:00:00');
            $endTime = strtotime($date . ' 23:59:59');
            
            $user = Db::table('caipiao_member')->where('id', $userId)->first();
            if (!$user) {
                return json(['code' => 404, 'message' => '用户不存在', 'data' => null]);
            }
            
            $betStats = Db::table('caipiao_touzhu')
                ->where('uid', $userId)
                ->where('oddtime', '>=', $startTime)
                ->where('oddtime', '<=', $endTime)
                ->selectRaw('
                    SUM(amount) as bet,
                    SUM(CASE WHEN isdraw = 1 THEN okamount ELSE 0 END) as winAmount,
                    SUM(CASE WHEN isdraw != 0 THEN amount ELSE 0 END) as validBet,
                    COUNT(*) as betCount
                ')
                ->first();
            
            $recharge = Db::table('caipiao_recharge')
                ->where('uid', $userId)
                ->where('oddtime', '>=', $startTime)
                ->where('oddtime', '<=', $endTime)
                ->where('state', 1)
                ->sum('amount');
            
            $withdraw = Db::table('caipiao_withdraw')
                ->where('uid', $userId)
                ->where('oddtime', '>=', $startTime)
                ->where('oddtime', '<=', $endTime)
                ->where('state', 1)
                ->sum('amount');
            
            $betByLottery = Db::table('caipiao_touzhu')
                ->where('uid', $userId)
                ->where('oddtime', '>=', $startTime)
                ->where('oddtime', '<=', $endTime)
                ->selectRaw('
                    cpname as lotteryCode,
                    cptitle as lotteryName,
                    SUM(amount) as betAmount,
                    COUNT(*) as betCount,
                    SUM(CASE WHEN isdraw = 1 THEN okamount ELSE 0 END) as winAmount
                ')
                ->groupBy('cpname', 'cptitle')
                ->get();
            
            $betByLotteryData = [];
            foreach ($betByLottery as $item) {
                $betByLotteryData[] = [
                    'lotteryCode' => $item->lotteryCode,
                    'lotteryName' => $item->lotteryName,
                    'betAmount' => number_format($item->betAmount, 2, '.', ''),
                    'betCount' => $item->betCount,
                    'winAmount' => number_format($item->winAmount, 2, '.', ''),
                    'pl' => number_format($item->winAmount - $item->betAmount, 2, '.', '')
                ];
            }
            
            $bet = $betStats->bet ?? 0;
            $winAmount = $betStats->winAmount ?? 0;
            $pl = $winAmount - $bet;
            
            return json([
                'code' => 0,
                'message' => '获取成功',
                'data' => [
                    'date' => $date,
                    'wallet' => $wallet,
                    'username' => $user->username,
                    'vipLevel' => $user->groupid ?? 1,
                    'balance' => number_format($user->balance ?? 0, 2, '.', ''),
                    'pl' => number_format($pl, 2, '.', ''),
                    'recharge' => number_format($recharge ?? 0, 2, '.', ''),
                    'withdraw' => number_format($withdraw ?? 0, 2, '.', ''),
                    'bet' => number_format($bet, 2, '.', ''),
                    'validBet' => number_format($betStats->validBet ?? 0, 2, '.', ''),
                    'winAmount' => number_format($winAmount, 2, '.', ''),
                    'rebate' => '0.00',
                    'bonus' => '0.00',
                    'transfer' => ['in' => '0.00', 'out' => '0.00'],
                    'betByLottery' => $betByLotteryData
                ]
            ]);
            
        } catch (\Exception $e) {
            return json(['code' => 500, 'message' => '获取数据失败', 'data' => null]);
        }
    }

    public function devices(Request $request)
    {
        $userId = $request->userId ?? 0;
        
        if (!$userId) {
            return json(['code' => 401, 'message' => '未登录', 'data' => null]);
        }
        
        try {
            $devices = Db::table('caipiao_user_device')
                ->where('user_id', $userId)
                ->orderBy('is_current', 'desc')
                ->orderBy('last_login_at', 'desc')
                ->limit(10)
                ->get();
            
            $list = [];
            foreach ($devices as $d) {
                $list[] = [
                    'id' => $d->id,
                    'device_id' => $d->device_id,
                    'client_version' => $d->client_version,
                    'browser_type' => $d->browser_type,
                    'os_type' => $d->os_type,
                    'os_version' => $d->os_version,
                    'device_brand' => $d->device_brand,
                    'device_model' => $d->device_model,
                    'ip' => $d->ip,
                    'ip_region' => $d->ip_region,
                    'is_current' => (bool)$d->is_current,
                    'last_login_at' => $d->last_login_at
                ];
            }
            
            return json(['code' => 0, 'message' => 'success', 'data' => ['list' => $list]]);
            
        } catch (\Exception $e) {
            return json(['code' => 500, 'message' => '获取失败']);
        }
    }

    public static function recordDevice(int $userId, Request $request)
    {
        try {
            $userAgent = $request->header('User-Agent', '');
            $ip = $request->getRealIp();
            $deviceInfo = self::parseUserAgent($userAgent);
            $deviceId = md5($userAgent . substr($ip, 0, strrpos($ip, '.')));
            
            Db::table('caipiao_user_device')
                ->where('user_id', $userId)
                ->update(['is_current' => 0]);
            
            Db::table('caipiao_user_device')->updateOrInsert(
                ['user_id' => $userId, 'device_id' => $deviceId],
                [
                    'client_version' => 'H5',
                    'browser_type' => $deviceInfo['browser'],
                    'os_type' => $deviceInfo['os'],
                    'os_version' => $deviceInfo['os_version'],
                    'device_brand' => $deviceInfo['brand'],
                    'device_model' => $deviceInfo['model'],
                    'ip' => $ip,
                    'ip_region' => self::getIpRegion($ip),
                    'is_current' => 1,
                    'last_login_at' => time(),
                    'updated_at' => time()
                ]
            );
            
        } catch (\Exception $e) {}
    }

    private static function parseUserAgent(string $ua): array
    {
        $result = ['browser' => '未知', 'os' => '未知', 'os_version' => '未知', 'brand' => '未知', 'model' => '未知'];
        
        if (preg_match('/Edg\/([\d.]+)/', $ua, $m)) {
            $result['browser'] = 'Edge v' . $m[1];
        } elseif (preg_match('/Edge\/([\d.]+)/', $ua, $m)) {
            $result['browser'] = 'Edge v' . $m[1];
        } elseif (preg_match('/CriOS\/([\d.]+)/', $ua, $m)) {
            $result['browser'] = 'Chrome v' . $m[1];
        } elseif (preg_match('/Chrome\/([\d.]+)/', $ua, $m)) {
            $result['browser'] = 'Chrome v' . $m[1];
        } elseif (preg_match('/FxiOS\/([\d.]+)/', $ua, $m)) {
            $result['browser'] = 'Firefox v' . $m[1];
        } elseif (preg_match('/Firefox\/([\d.]+)/', $ua, $m)) {
            $result['browser'] = 'Firefox v' . $m[1];
        } elseif (preg_match('/Version\/([\d.]+).*Safari/', $ua, $m)) {
            $result['browser'] = 'Safari v' . $m[1];
        } elseif (preg_match('/Safari\/([\d.]+)/', $ua, $m)) {
            $result['browser'] = 'Safari v' . $m[1];
        }
        
        if (preg_match('/iPhone.*OS ([\d_]+)/', $ua, $m)) {
            $result['os'] = 'iOS';
            $result['os_version'] = 'iOS ' . str_replace('_', '.', $m[1]);
            $result['brand'] = 'Apple';
            $result['model'] = 'iPhone';
        } elseif (preg_match('/iPad.*OS ([\d_]+)/', $ua, $m)) {
            $result['os'] = 'iPadOS';
            $result['os_version'] = 'iPadOS ' . str_replace('_', '.', $m[1]);
            $result['brand'] = 'Apple';
            $result['model'] = 'iPad';
        } elseif (preg_match('/Android ([\d.]+)/', $ua, $m)) {
            $result['os'] = 'Android';
            $result['os_version'] = 'Android ' . $m[1];
            if (preg_match('/;\s*([^;)]+)\s+Build/', $ua, $mm)) {
                $result['model'] = trim($mm[1]);
                if (preg_match('/^(Xiaomi|HUAWEI|OPPO|vivo|Samsung|OnePlus|Redmi)/i', $mm[1], $brand)) {
                    $result['brand'] = ucfirst(strtolower($brand[1]));
                }
            }
        } elseif (preg_match('/Windows NT ([\d.]+)/', $ua, $m)) {
            $result['os'] = 'Windows';
            $winVer = ['10.0' => '10/11', '6.3' => '8.1', '6.2' => '8', '6.1' => '7'];
            $result['os_version'] = 'Windows ' . ($winVer[$m[1]] ?? $m[1]);
            $result['brand'] = 'PC';
            $result['model'] = $result['browser'];
        } elseif (preg_match('/Mac OS X ([\d_]+)/', $ua, $m)) {
            $result['os'] = 'macOS';
            $result['os_version'] = 'macOS ' . str_replace('_', '.', $m[1]);
            $result['brand'] = 'Apple';
            $result['model'] = 'Mac';
        } elseif (preg_match('/Linux/', $ua)) {
            $result['os'] = 'Linux';
            $result['os_version'] = 'Linux';
            $result['brand'] = 'PC';
            $result['model'] = $result['browser'];
        }
        
        return $result;
    }

    private static function getIpRegion(string $ip): string
    {
        try {
            $url = "http://ip-api.com/json/{$ip}?lang=zh-CN";
            $ctx = stream_context_create(['http' => ['timeout' => 2]]);
            $response = @file_get_contents($url, false, $ctx);
            if ($response) {
                $data = json_decode($response, true);
                if ($data && $data['status'] === 'success') {
                    return $data['country'] . '/' . ($data['regionName'] ?? '') . '-' . ($data['city'] ?? '');
                }
            }
        } catch (\Exception $e) {}
        
        return $ip;
    }
}
