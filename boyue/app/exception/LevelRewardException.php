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
        return new static('Vui lòng đăng nhập', 1401);
    }

    
    public static function userNotFound(int $uid = 0): self
    {
        $msg = $uid > 0 ? "Người dùng không tồn tại [ID:{$uid}]" : 'Người dùng không tồn tại';
        return new static($msg, self::CODE_NOT_FOUND);
    }

    

    
    public static function noRewardAvailable(): self
    {
        return new static('暂无可领取的Thăng cấp奖励', self::CODE_ERROR);
    }

    
    public static function rewardAlreadyClaimed(): self
    {
        return new static('该Thăng cấp奖励已领取', self::CODE_ERROR);
    }

    
    public static function levelInsufficient(string $requiredLevel = ''): self
    {
        $msg = $requiredLevel ? "Cấp độ不足，需要达到 {$requiredLevel}" : 'Cấp độ不足';
        return new static($msg, self::CODE_ERROR);
    }

    
    public static function alreadyMaxLevel(): self
    {
        return new static('您已是最高Cấp độ', self::CODE_ERROR);
    }

    

    
    public static function recordNotFound(int $recordId = 0): self
    {
        $msg = $recordId > 0 ? "Thăng cấplịch sử不存在 [ID:{$recordId}]" : 'Thăng cấplịch sửkhông tồn tại';
        return new static($msg, self::CODE_NOT_FOUND);
    }

    

    
    public static function claimFailed(string $reason = ''): self
    {
        $msg = $reason ? "领取Thất bại：{$reason}" : '领取失败，请稍后重试';
        return new static($msg, self::CODE_ERROR);
    }

    
    public static function fetchFailed(string $reason = ''): self
    {
        $msg = $reason ? "Lấy dữ liệuThất bại：{$reason}" : '获取数据失败，请稍后重试';
        return new static($msg, self::CODE_ERROR);
    }

    

    
    public static function invalidUserId(): self
    {
        return new static('Người dùngID无效', self::CODE_PARAM_ERROR);
    }

    
    public static function invalidPagination(): self
    {
        return new static('分页参数无效', self::CODE_PARAM_ERROR);
    }
}
