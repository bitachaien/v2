<?php

declare(strict_types=1);

namespace app\service;

use support\Db;
use support\Log;


class UserLevelService
{
    
    private const TABLE_MEMBER = 'caipiao_member';

    
    private const TABLE_MEMBER_GROUP = 'caipiao_membergroup';

    
    private const TABLE_TOUZHU = 'caipiao_touzhu';

    
    public static function checkAndUpgrade(int $userId): array
    {
        $result = [
            'upgraded' => false,
            'from_level' => 0,
            'to_level' => 0,
            'reward' => 0,
        ];

        try {
            
            $user = Db::table(self::TABLE_MEMBER)->where('id', $userId)->first();
            if (!$user) {
                Log::warning('用户等级检查失败: 用户不存在', ['uid' => $userId]);
                return $result;
            }

            $currentLevelId = (int)($user->groupid ?? 1);
            $result['from_level'] = $currentLevelId;

            
            $totalBetting = Db::table(self::TABLE_TOUZHU)
                ->where('uid', $userId)
                ->sum('amount') ?? 0;

            
            $levelConfigs = Db::table(self::TABLE_MEMBER_GROUP)
                ->orderBy('groupid', 'asc')
                ->get();

            if ($levelConfigs->isEmpty()) {
                Log::warning('用户等级检查失败: 未找到等级配置');
                return $result;
            }

            
            
            $targetLevelId = 1;
            $targetLevel = null;
            $cumulativeRequired = 0; 
            
            foreach ($levelConfigs as $config) {
                
                if (!is_numeric($config->shengjiedu)) {
                    continue; 
                }
                $cumulativeRequired += (int)$config->shengjiedu;
                if ($totalBetting >= $cumulativeRequired) {
                    $targetLevelId = (int)$config->groupid;
                    $targetLevel = $config;
                }
            }

            
            if ($targetLevelId > $currentLevelId) {
                
                $totalReward = self::calculateUpgradeReward($currentLevelId, $targetLevelId, $levelConfigs);
                
                
                Db::beginTransaction();
                try {
                    
                    Db::table(self::TABLE_MEMBER)
                        ->where('id', $userId)
                        ->update([
                            'groupid' => $targetLevelId,
                            'jinjijilu' => Db::raw("jinjijilu + {$totalReward}"), 
                        ]);

                    Db::commit();

                    $result['upgraded'] = true;
                    $result['to_level'] = $targetLevelId;
                    $result['reward'] = $totalReward;

                    Log::info('用户等级升级成功', [
                        'uid' => $userId,
                        'username' => $user->username,
                        'from_level' => $currentLevelId,
                        'to_level' => $targetLevelId,
                        'total_betting' => $totalBetting,
                        'reward' => $totalReward,
                    ]);

                } catch (\Throwable $e) {
                    Db::rollBack();
                    Log::error('用户等级升级失败', [
                        'uid' => $userId,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            return $result;

        } catch (\Throwable $e) {
            Log::error('用户等级检查异常', [
                'uid' => $userId,
                'error' => $e->getMessage(),
            ]);
            return $result;
        }
    }

    
    private static function calculateUpgradeReward(int $fromLevel, int $toLevel, $levelConfigs): float
    {
        $totalReward = 0;

        foreach ($levelConfigs as $config) {
            $levelId = (int)$config->groupid;
            
            if ($levelId > $fromLevel && $levelId <= $toLevel) {
                $reward = (float)($config->jjje ?? 0); 
                $totalReward += $reward;
            }
        }

        return $totalReward;
    }

    
    public static function getUserLevelInfo(int $userId): ?array
    {
        try {
            $user = Db::table(self::TABLE_MEMBER . ' as m')
                ->leftJoin(self::TABLE_MEMBER_GROUP . ' as g', 'm.groupid', '=', 'g.groupid')
                ->where('m.id', $userId)
                ->select('m.id', 'm.username', 'm.groupid', 'm.jinjijilu', 'g.groupname', 'g.shengjiedu', 'g.jjje')
                ->first();

            if (!$user) {
                return null;
            }

            
            $totalBetting = Db::table(self::TABLE_TOUZHU)
                ->where('uid', $userId)
                ->sum('amount') ?? 0;

            
            $allLevels = Db::table(self::TABLE_MEMBER_GROUP)
                ->orderBy('groupid', 'asc')
                ->get();
            
            $cumulativeRequired = 0; 
            $nextLevelRequired = 0;  
            $nextLevel = null;
            
            foreach ($allLevels as $level) {
                if (!is_numeric($level->shengjiedu)) {
                    continue;
                }
                $cumulativeRequired += (int)$level->shengjiedu;
                
                if ((int)$level->groupid == $user->groupid) {
                    
                    continue;
                }
                if ((int)$level->groupid > $user->groupid && !$nextLevel) {
                    $nextLevel = $level;
                    $nextLevelRequired = $cumulativeRequired;
                    break;
                }
            }

            $progress = 0;
            if ($nextLevel && $nextLevelRequired > 0) {
                $progress = min(100, ($totalBetting / $nextLevelRequired) * 100);
            } elseif (!$nextLevel) {
                $progress = 100; 
            }

            return [
                'userId' => $user->id,
                'username' => $user->username,
                'currentLevelId' => (int)$user->groupid,
                'currentLevelName' => $user->groupname ?? 'VIP' . $user->groupid,
                'totalBetting' => (float)$totalBetting,
                'pendingReward' => (float)($user->jinjijilu ?? 0),
                'nextLevelId' => $nextLevel ? (int)$nextLevel->groupid : null,
                'nextLevelName' => $nextLevel ? $nextLevel->groupname : null,
                'nextLevelRequired' => $nextLevelRequired, 
                'progress' => round($progress, 2),
            ];

        } catch (\Throwable $e) {
            Log::error('获取用户等级信息失败', [
                'uid' => $userId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }
}
