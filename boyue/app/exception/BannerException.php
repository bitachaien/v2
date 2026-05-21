<?php

declare(strict_types=1);

namespace app\exception;


class BannerException extends BusinessException
{
    
    public static function notExists(int $id): self
    {
        return new static("轮播图không tồn tại [ID:{$id}]", self::CODE_NOT_FOUND);
    }

    
    public static function titleRequired(): self
    {
        return new static('轮播图标题không được để trống', self::CODE_PARAM_ERROR);
    }

    
    public static function imageRequired(): self
    {
        return new static('轮播图图片không được để trống', self::CODE_PARAM_ERROR);
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
        return new static('轮播图创建Thất bại', self::CODE_ERROR);
    }

    
    public static function updateFailed(): self
    {
        return new static('轮播图更新Thất bại', self::CODE_ERROR);
    }

    
    public static function deleteFailed(): self
    {
        return new static('轮播图XóaThất bại', self::CODE_ERROR);
    }

    
    public static function invalidTimeRange(): self
    {
        return new static('开始Thời gian不能大于结束Thời gian', self::CODE_PARAM_ERROR);
    }

    
    public static function invalidId(): self
    {
        return new static('轮播图IDTham số không hợp lệ', self::CODE_PARAM_ERROR);
    }
}
