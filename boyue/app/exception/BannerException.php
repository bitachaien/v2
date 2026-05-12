<?php

declare(strict_types=1);

namespace app\exception;


class BannerException extends BusinessException
{
    
    public static function notExists(int $id): self
    {
        return new static("轮播图不存在 [ID:{$id}]", self::CODE_NOT_FOUND);
    }

    
    public static function titleRequired(): self
    {
        return new static('轮播图标题不能为空', self::CODE_PARAM_ERROR);
    }

    
    public static function imageRequired(): self
    {
        return new static('轮播图图片不能为空', self::CODE_PARAM_ERROR);
    }

    
    public static function invalidPlatform(int $platform): self
    {
        return new static("无效的平台类型 [{$platform}]", self::CODE_PARAM_ERROR);
    }

    
    public static function invalidStatus(int $status): self
    {
        return new static("无效的状态值 [{$status}]", self::CODE_PARAM_ERROR);
    }

    
    public static function createFailed(): self
    {
        return new static('轮播图创建失败', self::CODE_ERROR);
    }

    
    public static function updateFailed(): self
    {
        return new static('轮播图更新失败', self::CODE_ERROR);
    }

    
    public static function deleteFailed(): self
    {
        return new static('轮播图删除失败', self::CODE_ERROR);
    }

    
    public static function invalidTimeRange(): self
    {
        return new static('开始时间不能大于结束时间', self::CODE_PARAM_ERROR);
    }

    
    public static function invalidId(): self
    {
        return new static('轮播图ID参数错误', self::CODE_PARAM_ERROR);
    }
}
