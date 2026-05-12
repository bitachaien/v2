<?php

namespace app\service;

use support\Db;

class AntiCheatService
{
    
    public static function checkRegisterAllowed(string $ip, string $deviceId = '', int $parentId = 0): array
    {
        $result = ['allowed' => true, 'reason' => ''];
        
        
        $ipLimit = Db::table('caipiao_setting')
            ->where('name', 'register_ip_limit')
            ->value('value') ?? 5;
        
        $ipCount = Db::table('caipiao_member')
            ->where('regip', $ip)
            ->where('regtime', '>', time() - 86400)
            ->count();
        
        if ($ipCount >= $ipLimit) {
            return ['allowed' => false, 'reason' => '同一IP注册过于频繁，请稍后再试'];
        }
        
        
        if (!empty($deviceId)) {
            $deviceLimit = Db::table('caipiao_setting')
                ->where('name', 'register_device_limit')
                ->value('value') ?? 3;
            
            $deviceCount = Db::table('caipiao_member')
                ->where('device_id', $deviceId)
                ->count();
            
            if ($deviceCount >= $deviceLimit) {
                return ['allowed' => false, 'reason' => '该设备注册数量已达上限'];
            }
        }
        
        
        if ($parentId > 0) {
            $parent = Db::table('caipiao_member')->where('id', $parentId)->first();
            if ($parent && $parent->regip === $ip) {
                
                \support\Log::warning('可疑注册：推荐人与被推荐人同IP', [
                    'parent_id' => $parentId,
                    'ip' => $ip
                ]);
            }
        }
        
        return $result;
    }
    
    
    public static function isValidReferral(int $userId, int $parentId): bool
    {
        $user = Db::table('caipiao_member')->where('id', $userId)->first();
        $parent = Db::table('caipiao_member')->where('id', $parentId)->first();
        
        if (!$user || !$parent) {
            return false;
        }
        
        
        if ($user->regip === $parent->regip) {
            
            
            $userBank = Db::table('caipiao_member')
                ->where('id', $userId)
                ->value('userbankname') ?? '';
            
            $parentBank = Db::table('caipiao_member')
                ->where('id', $parentId)
                ->value('userbankname') ?? '';
            
            
            if (empty($userBank) || $userBank === $parentBank) {
                return false;
            }
        }
        
        
        $rechargeCount = Db::table('caipiao_recharge')
            ->where('uid', $userId)
            ->where('state', 1)
            ->count();
        
        if ($rechargeCount < 1) {
            return false; 
        }
        
        
        $betCount = Db::table('caipiao_touzhu')
            ->where('uid', $userId)
            ->count();
        
        if ($betCount < 5) {
            return false; 
        }
        
        return true;
    }
    
    
    public static function recordDeviceFingerprint(int $userId, string $deviceId, string $ip)
    {
        if (empty($deviceId)) {
            return;
        }
        
        Db::table('caipiao_member')
            ->where('id', $userId)
            ->update([
                'device_id' => $deviceId,
                'last_ip' => $ip
            ]);
    }
    
    
    public static function checkClaimAllowed(int $agentId): array
    {
        
        $subIds = Db::table('caipiao_member')
            ->where('parentid', $agentId)
            ->pluck('id')
            ->toArray();
        
        if (empty($subIds)) {
            return ['allowed' => true, 'suspicious' => 0];
        }
        
        
        $agent = Db::table('caipiao_member')->where('id', $agentId)->first();
        $suspiciousCount = 0;
        
        foreach ($subIds as $subId) {
            $sub = Db::table('caipiao_member')->where('id', $subId)->first();
            
            
            if ($sub && $sub->regip === $agent->regip) {
                $suspiciousCount++;
            }
        }
        
        
        $suspiciousRate = count($subIds) > 0 ? $suspiciousCount / count($subIds) : 0;
        
        if ($suspiciousRate > 0.5) {
            return [
                'allowed' => false, 
                'suspicious' => $suspiciousCount,
                'reason' => '检测到异常推广行为，请联系客服'
            ];
        }
        
        return ['allowed' => true, 'suspicious' => $suspiciousCount];
    }
}
