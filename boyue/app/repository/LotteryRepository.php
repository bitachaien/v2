<?php

namespace app\repository;

use support\Db;

class LotteryRepository
{
    
    public static function getLottery(string $name, string $typeid = null)
    {
        $query = Db::table('caipiao_caipiao')->where('name', $name);
        if ($typeid) {
            $query->where('typeid', $typeid);
        }
        return $query->first();
    }
    
    
    public static function getLotteryList(string $typeid, bool $onlyOpen = true): array
    {
        $query = Db::table('caipiao_caipiao')->where('typeid', $typeid);
        if ($onlyOpen) {
            $query->where('isopen', 1);
        }
        return $query->orderBy('id', 'asc')->get()->toArray();
    }
    
    
    public static function getKaijiang(string $name, string $expect)
    {
        return Db::table('caipiao_kaijiang')
            ->where('name', $name)
            ->where('expect', $expect)
            ->first();
    }
    
    
    public static function getLatestKaijiang(string $name, int $limit = 1)
    {
        $query = Db::table('caipiao_kaijiang')
            ->where('name', $name)
            ->where('opentime', '<=', time())
            ->where('opencode', '!=', '')
            ->orderBy('expect', 'desc');
        
        if ($limit === 1) {
            return $query->first();
        }
        return $query->limit($limit)->get()->toArray();
    }
    
    
    public static function getKaijiangHistory(string $name, int $limit = 30): array
    {
        return Db::table('caipiao_kaijiang')
            ->where('name', $name)
            ->where('opentime', '<=', time())
            ->where('opencode', '!=', '')
            ->orderBy('expect', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }
    
    
    public static function getPlay(string $playid)
    {
        return Db::table('caipiao_wanfa')
            ->where('playid', $playid)
            ->first();
    }
    
    
    public static function getPlayList(string $typeid, bool $onlyOpen = true): array
    {
        $query = Db::table('caipiao_wanfa')->where('typeid', $typeid);
        if ($onlyOpen) {
            $query->where('isopen', 1);
        }
        return $query->orderBy('sort', 'asc')->get()->toArray();
    }
    
    
    public static function getUserForUpdate(int $userId)
    {
        return Db::table('caipiao_member')
            ->where('id', $userId)
            ->lockForUpdate()
            ->first();
    }
    
    
    public static function getUser(int $userId)
    {
        return Db::table('caipiao_member')
            ->where('id', $userId)
            ->first();
    }
    
    
    public static function decrementBalance(int $userId, float $amount): int
    {
        return Db::table('caipiao_member')
            ->where('id', $userId)
            ->decrement('balance', $amount);
    }
    
    
    public static function incrementBalance(int $userId, float $amount): int
    {
        return Db::table('caipiao_member')
            ->where('id', $userId)
            ->increment('balance', $amount);
    }
    
    
    public static function createBet(array $data): int
    {
        return Db::table('caipiao_touzhu')->insertGetId($data);
    }
    
    
    public static function createMoneyLog(array $data): int
    {
        return Db::table('caipiao_moneylog')->insertGetId($data);
    }
    
    
    public static function kaijiangExists(string $name, string $expect): bool
    {
        return Db::table('caipiao_kaijiang')
            ->where('name', $name)
            ->where('expect', $expect)
            ->exists();
    }
}
