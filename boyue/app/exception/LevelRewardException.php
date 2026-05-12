<?php

declare(strict_types=1);

namespace app\exception;

use app\common\Result;


class LevelRewardException extends BusinessException
{
    
    public const CODE_ERROR = Result::CODE_ERROR;
    public const CODE_NOT_FOUND = Result::CODE_NOT_FOUND;
    public const CODE_PARAM_ERROR = Result::CODE_PARAM_ERROR;
    

    
    public static function notLoggedIn(): self
    {
        return new static('请先登录', 1401);
    }

    
    public static function userNotFound(int $uid = 0): self
    {
        $msg = $uid > 0 ? "用户不存在 [ID:{$uid}]" : '用户不存在';
        return new static($msg, self::CODE_NOT_FOUND);
    }

    

    
    public static function noRewardAvailable(): self
    {
        return new static('暂无可领取的晋级奖励', self::CODE_ERROR);
    }

    
    public static function rewardAlreadyClaimed(): self
    {
        return new static('该晋级奖励已领取', self::CODE_ERROR);
    }

    
    public static function levelInsufficient(string $requiredLevel = ''): self
    {
        $msg = $requiredLevel ? "等级不足，需要达到 {$requiredLevel}" : '等级不足';
        return new static($msg, self::CODE_ERROR);
    }

    
    public static function alreadyMaxLevel(): self
    {
        return new static('您已是最高等级', self::CODE_ERROR);
    }

    

    
    public static function recordNotFound(int $recordId = 0): self
    {
        $msg = $recordId > 0 ? "晋级记录不存在 [ID:{$recordId}]" : '晋级记录不存在';
        return new static($msg, self::CODE_NOT_FOUND);
    }

    

    
    public static function claimFailed(string $reason = ''): self
    {
        $msg = $reason ? "领取失败：{$reason}" : '领取失败，请稍后重试';
        return new static($msg, self::CODE_ERROR);
    }

    
    public static function fetchFailed(string $reason = ''): self
    {
        $msg = $reason ? "获取数据失败：{$reason}" : '获取数据失败，请稍后重试';
        return new static($msg, self::CODE_ERROR);
    }

    

    
    public static function invalidUserId(): self
    {
        return new static('用户ID无效', self::CODE_PARAM_ERROR);
    }

    
    public static function invalidPagination(): self
    {
        return new static('分页参数无效', self::CODE_PARAM_ERROR);
    }
}
