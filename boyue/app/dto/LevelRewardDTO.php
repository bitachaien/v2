<?php

declare(strict_types=1);

namespace app\dto;

use app\constant\LevelRewardConstant;


class LevelRewardDTO
{
    
    public static function levelInfoToApi(object $user, float $rewardAmount, bool $canClaim, float $progress = 0): array
    {
        $currentLevelId = (int)($user->groupid ?? 1);
        $currentLevel = $user->groupname ?? LevelRewardConstant::getLevelName($currentLevelId);
        $nextLevelId = min($currentLevelId + 1, LevelRewardConstant::MAX_LEVEL);

        return [
            'currentLevel'   => $currentLevel,
            'currentLevelId' => $currentLevelId,
            'nextLevel'      => LevelRewardConstant::getLevelName($nextLevelId),
            'nextLevelId'    => $nextLevelId,
            'rewardAmount'   => number_format($rewardAmount, 2, '.', ''),
            'canClaim'       => $canClaim,
            'progress'       => round($progress, 2),
            'isMaxLevel'     => $currentLevelId >= LevelRewardConstant::MAX_LEVEL,
        ];
    }

    
    public static function recordToApi(object $record): array
    {
        
        if (isset($record->user_id)) {
            return [
                'id'            => (int)($record->id ?? 0),
                'orderNo'       => $record->order_no ?? '',
                'fromLevelId'   => (int)($record->from_level_id ?? 0),
                'fromLevelName' => $record->from_level_name ?? '',
                'toLevelId'     => (int)($record->to_level_id ?? 0),
                'toLevelName'   => $record->to_level_name ?? '',
                'rewardAmount'  => number_format((float)($record->reward_amount ?? 0), 2, '.', ''),
                'balanceBefore' => number_format((float)($record->balance_before ?? 0), 2, '.', ''),
                'balanceAfter'  => number_format((float)($record->balance_after ?? 0), 2, '.', ''),
                'status'        => (int)($record->status ?? 0),
                'statusText'    => LevelRewardConstant::STATUS_MAP[$record->status ?? 0] ?? '未知',
                'claimTime'     => $record->claim_time ?? '',
                'gmtCreate'     => $record->gmt_create ?? '',
            ];
        }
        
        
        return [
            'id'            => (int)($record->id ?? 0),
            'orderNo'       => '',
            'fromLevelId'   => 0,
            'fromLevelName' => '',
            'toLevelId'     => (int)($record->groupid ?? 0),
            'toLevelName'   => $record->groupname ?? '',
            'rewardAmount'  => number_format((float)($record->jlje ?? 0), 2, '.', ''),
            'balanceBefore' => '0.00',
            'balanceAfter'  => '0.00',
            'status'        => (int)($record->shenhe ?? 0),
            'statusText'    => LevelRewardConstant::STATUS_MAP[$record->shenhe ?? 0] ?? '未知',
            'claimTime'     => $record->oddtime ? date('Y-m-d H:i:s', $record->oddtime) : '',
            'gmtCreate'     => $record->oddtime ? date('Y-m-d H:i:s', $record->oddtime) : '',
        ];
    }

    
    public static function recordListToApi(iterable $records): array
    {
        $list = [];
        foreach ($records as $record) {
            $list[] = self::recordToApi($record);
        }
        return $list;
    }

    
    public static function claimResultToApi(string $orderId, float $amount, string $levelName = ''): array
    {
        return [
            'orderId'   => $orderId,
            'amount'    => number_format($amount, 2, '.', ''),
            'levelName' => $levelName,
            'claimTime' => date('Y-m-d H:i:s'),
        ];
    }

    
    public static function levelConfigsToApi(): array
    {
        return LevelRewardConstant::getAllLevelConfigs();
    }

    
    public static function paginatedResult(array $list, int $total, int $page, int $pageSize): array
    {
        return [
            'list'      => $list,
            'total'     => $total,
            'page'      => $page,
            'page_size' => $pageSize,
            'totalPage' => (int)ceil($total / $pageSize),
        ];
    }
}
