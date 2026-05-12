<?php

declare(strict_types=1);

namespace app\validator;

use app\exception\LevelRewardException;
use app\constant\LevelRewardConstant;


class LevelRewardValidator
{
    
    public static function validateUserId(int $uid): void
    {
        if ($uid <= 0) {
            throw LevelRewardException::invalidUserId();
        }
    }

    
    public static function validatePagination(array $params): array
    {
        $page = isset($params['page']) ? (int)$params['page'] : 1;
        $pageSize = isset($params['page_size']) 
            ? (int)$params['page_size'] 
            : (isset($params['pageSize']) ? (int)$params['pageSize'] : LevelRewardConstant::DEFAULT_PAGE_SIZE);

        
        $page = max(1, $page);
        $pageSize = max(1, min($pageSize, LevelRewardConstant::MAX_PAGE_SIZE));

        return [
            'page'      => $page,
            'page_size' => $pageSize,
            'offset'    => ($page - 1) * $pageSize,
        ];
    }

    
    public static function validateCanClaim(object $user): void
    {
        
        $pendingReward = $user->pending_reward ?? $user->jinjijilu ?? 0;
        if ($pendingReward <= 0) {
            throw LevelRewardException::noRewardAvailable();
        }
    }

    
    public static function validateLevelId(int $levelId): void
    {
        if ($levelId < 1 || $levelId > LevelRewardConstant::MAX_LEVEL) {
            throw LevelRewardException::levelInsufficient();
        }
    }
}
