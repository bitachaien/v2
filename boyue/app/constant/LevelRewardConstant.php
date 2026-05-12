<?php

declare(strict_types=1);

namespace app\constant;


class LevelRewardConstant
{
    

    
    public const LEVEL_CONFIG = [
        1 => ['name' => 'VIP1', 'reward' => 1,    'required' => 100,     'jump_reward' => 1],
        2 => ['name' => 'VIP2', 'reward' => 5,    'required' => 500,     'jump_reward' => 6],
        3 => ['name' => 'VIP3', 'reward' => 10,   'required' => 1000,    'jump_reward' => 16],
        4 => ['name' => 'VIP4', 'reward' => 50,   'required' => 5000,    'jump_reward' => 66],
        5 => ['name' => 'VIP5', 'reward' => 100,  'required' => 10000,   'jump_reward' => 166],
        6 => ['name' => 'VIP6', 'reward' => 200,  'required' => 50000,   'jump_reward' => 366],
        7 => ['name' => 'VIP7', 'reward' => 500,  'required' => 100000,  'jump_reward' => 866],
        8 => ['name' => 'VIP8', 'reward' => 800,  'required' => 500000,  'jump_reward' => 1666],
        9 => ['name' => 'VIP9', 'reward' => 1000, 'required' => 1000000, 'jump_reward' => 2666],
    ];

    
    public const MAX_LEVEL = 9;

    
    public const MAX_REWARD = 1000;

    

    
    public const STATUS_PENDING = 0;

    
    public const STATUS_APPROVED = 1;

    
    public const STATUS_REJECTED = -1;

    
    public const STATUS_MAP = [
        self::STATUS_PENDING  => '审核中',
        self::STATUS_APPROVED => '已通过',
        self::STATUS_REJECTED => '未通过',
    ];

    

    
    public const RECORD_TYPE_LEVEL_UP = 'level_up';

    
    public const RECORD_TYPE_JUMP = 'jump';

    
    public const RECORD_TYPE_MAP = [
        self::RECORD_TYPE_LEVEL_UP => '晋级奖励',
        self::RECORD_TYPE_JUMP     => '跳级奖励',
    ];

    

    
    public const MONEYLOG_TYPE = 'jinjishenhe';

    
    public const MONEYLOG_TYPE_NAME = '晋级奖励';

    

    
    public const DEFAULT_PAGE_SIZE = 20;

    
    public const MAX_PAGE_SIZE = 100;

    

    
    public const ORDER_PREFIX = 'LR';

    

    
    public const TABLE_LEVEL_CONFIG = 'yzz_level_config';

    
    public const TABLE_LEVEL_REWARD_RECORD = 'yzz_level_reward_record';

    
    public const TABLE_USER_LEVEL = 'yzz_user_level';

    
    public const TABLE_LEVEL_CHANGE_LOG = 'yzz_level_change_log';

    

    
    public const TABLE_MEMBER = 'caipiao_member';

    
    public const TABLE_MEMBER_GROUP = 'caipiao_membergroup';

    
    public const TABLE_LEVEL_RECORD = 'caipiao_jinjijilu';

    
    public const TABLE_MONEY_LOG = 'caipiao_fuddetail';

    
    public const TABLE_BET = 'caipiao_touzhu';

    

    
    public static function getLevelConfig(int $levelId): ?array
    {
        return self::LEVEL_CONFIG[$levelId] ?? null;
    }

    
    public static function getLevelReward(int $levelId): float
    {
        return (float)(self::LEVEL_CONFIG[$levelId]['reward'] ?? 0);
    }

    
    public static function getLevelName(int $levelId): string
    {
        return self::LEVEL_CONFIG[$levelId]['name'] ?? 'VIP' . $levelId;
    }

    
    public static function getRequiredPoints(int $levelId): int
    {
        return (int)(self::LEVEL_CONFIG[$levelId]['required'] ?? 0);
    }

    
    public static function calculateJumpReward(int $fromLevel, int $toLevel): float
    {
        if ($toLevel <= $fromLevel) {
            return 0;
        }

        $reward = 0;
        for ($i = $fromLevel + 1; $i <= $toLevel; $i++) {
            $reward += self::getLevelReward($i);
        }
        return $reward;
    }

    
    public static function generateOrderId(): string
    {
        return self::ORDER_PREFIX . date('YmdHis') . str_pad((string)mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);
    }

    
    public static function getAllLevelConfigs(): array
    {
        $list = [];
        foreach (self::LEVEL_CONFIG as $id => $config) {
            $list[] = [
                'groupid'     => $id,
                'groupname'   => $config['name'],
                'shengjiedu'  => $config['required'],
                'jjje'        => $config['reward'],
                'tiaoji'      => $config['jump_reward'],
            ];
        }
        return $list;
    }
}
