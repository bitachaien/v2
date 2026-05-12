<?php

declare(strict_types=1);

namespace app\exception;


class YuebaoException extends BusinessException
{
    

    
    public static function notLoggedIn(): self
    {
        return new static('请先登录', 401);
    }

    
    public static function userNotFound(int $uid = 0): self
    {
        $msg = $uid > 0 ? "用户不存在 [ID:{$uid}]" : '用户不存在';
        return new static($msg, self::CODE_NOT_FOUND);
    }

    
    public static function wrongFundPassword(): self
    {
        return new static('资金密码错误', self::CODE_PARAM_ERROR);
    }

    
    public static function insufficientBalance(float $required = 0, float $available = 0): self
    {
        if ($required > 0 && $available >= 0) {
            return new static("余额不足，需要 {$required} 元，可用 {$available} 元", self::CODE_ERROR);
        }
        return new static('余额不足', self::CODE_ERROR);
    }

    

    
    public static function productNotFound(int $productId = 0): self
    {
        $msg = $productId > 0 ? "产品不存在或已下架 [ID:{$productId}]" : '产品不存在或已下架';
        return new static($msg, self::CODE_NOT_FOUND);
    }

    
    public static function productDisabled(string $productName = ''): self
    {
        $msg = $productName ? "产品 [{$productName}] 已下架" : '产品已下架';
        return new static($msg, self::CODE_ERROR);
    }

    

    
    public static function amountMustPositive(): self
    {
        return new static('金额必须大于0', self::CODE_PARAM_ERROR);
    }

    
    public static function invalidAmount(): self
    {
        return new static('请输入正确的金额', self::CODE_PARAM_ERROR);
    }

    
    public static function amountBelowMinimum(float $minAmount): self
    {
        return new static("最低转入金额为 {$minAmount} 元", self::CODE_PARAM_ERROR);
    }

    
    public static function amountExceedsMaximum(float $maxAmount): self
    {
        return new static("单笔最大金额为 {$maxAmount} 元", self::CODE_PARAM_ERROR);
    }

    
    public static function insufficientCurrentBalance(float $required = 0, float $available = 0): self
    {
        if ($required > 0 && $available >= 0) {
            return new static("活期余额不足，需要 {$required} 元，可用 {$available} 元", self::CODE_ERROR);
        }
        return new static('活期余额不足', self::CODE_ERROR);
    }

    
    public static function insufficientYuebaoBalance(): self
    {
        return new static('余额宝余额不足，请稍后重试', self::CODE_ERROR);
    }

    
    public static function productRequired(): self
    {
        return new static('请选择理财产品', self::CODE_PARAM_ERROR);
    }

    

    
    public static function holdingNotFound(string $orderId = ''): self
    {
        $msg = $orderId ? "持仓记录不存在 [订单号:{$orderId}]" : '持仓记录不存在';
        return new static($msg, self::CODE_NOT_FOUND);
    }

    
    public static function holdingNotExpired(string $endDate): self
    {
        return new static("定期持仓未到期，到期日期：{$endDate}", self::CODE_ERROR);
    }

    

    
    public static function transferInFailed(string $reason = ''): self
    {
        $msg = $reason ? "转入失败：{$reason}" : '转入失败，请稍后重试';
        return new static($msg, self::CODE_ERROR);
    }

    
    public static function transferOutFailed(string $reason = ''): self
    {
        $msg = $reason ? "转出失败：{$reason}" : '转出失败，请稍后重试';
        return new static($msg, self::CODE_ERROR);
    }

    
    public static function operationFailed(string $operation = '操作'): self
    {
        return new static("{$operation}失败，请稍后重试", self::CODE_ERROR);
    }

    

    
    public static function paramRequired(string $paramName): self
    {
        return new static("{$paramName}不能为空", self::CODE_PARAM_ERROR);
    }

    
    public static function invalidParam(string $paramName): self
    {
        return new static("{$paramName}格式错误", self::CODE_PARAM_ERROR);
    }

    
    public static function invalidId(): self
    {
        return new static('ID参数错误', self::CODE_PARAM_ERROR);
    }

    
    public static function invalidEmail(): self
    {
        return new static('请输入正确的邮箱地址', self::CODE_PARAM_ERROR);
    }

    

    
    public static function transferInRateLimited(): self
    {
        return new static('操作过于频繁，请稍后再试', 429);
    }

    
    public static function transferOutRateLimited(): self
    {
        return new static('操作过于频繁，请稍后再试', 429);
    }
}
