<?php

declare(strict_types=1);

namespace app\validator;

use app\constant\BannerConstant;
use app\exception\BannerException;


class BannerValidator
{
    
    public static function validateCreate(array $data): void
    {
        
        if (empty($data['title']) || !is_string($data['title'])) {
            throw BannerException::titleRequired();
        }

        if (mb_strlen($data['title']) > 100) {
            throw new BannerException('轮播图标题不能超过100个字符');
        }

        
        if (empty($data['image']) || !is_string($data['image'])) {
            throw BannerException::imageRequired();
        }

        
        if (isset($data['platform'])) {
            self::validatePlatform((int)$data['platform']);
        }

        
        if (isset($data['sort'])) {
            self::validateSort((int)$data['sort']);
        }

        
        if (isset($data['status'])) {
            self::validateStatus((int)$data['status']);
        }

        
        if (!empty($data['start_time']) && !empty($data['end_time'])) {
            self::validateTimeRange($data['start_time'], $data['end_time']);
        }

        
        if (!empty($data['link'])) {
            self::validateLink($data['link']);
        }
    }

    
    public static function validateUpdate(int $id, array $data): void
    {
        
        if ($id <= 0) {
            throw BannerException::invalidId();
        }

        
        if (isset($data['title'])) {
            if (empty($data['title']) || !is_string($data['title'])) {
                throw BannerException::titleRequired();
            }

            if (mb_strlen($data['title']) > 100) {
                throw new BannerException('轮播图标题不能超过100个字符');
            }
        }

        
        if (isset($data['image']) && (empty($data['image']) || !is_string($data['image']))) {
            throw BannerException::imageRequired();
        }

        
        if (isset($data['platform'])) {
            self::validatePlatform((int)$data['platform']);
        }

        
        if (isset($data['sort'])) {
            self::validateSort((int)$data['sort']);
        }

        
        if (isset($data['status'])) {
            self::validateStatus((int)$data['status']);
        }

        
        if (!empty($data['start_time']) && !empty($data['end_time'])) {
            self::validateTimeRange($data['start_time'], $data['end_time']);
        }

        
        if (!empty($data['link'])) {
            self::validateLink($data['link']);
        }
    }

    
    public static function validateId(int $id): void
    {
        if ($id <= 0) {
            throw BannerException::invalidId();
        }
    }

    
    public static function validatePlatform(int $platform): void
    {
        if (!BannerConstant::isValidPlatform($platform)) {
            throw BannerException::invalidPlatform($platform);
        }
    }

    
    public static function validateStatus(int $status): void
    {
        if (!BannerConstant::isValidStatus($status)) {
            throw BannerException::invalidStatus($status);
        }
    }

    
    public static function validateSort(int $sort): void
    {
        if ($sort < 0 || $sort > 9999) {
            throw new BannerException('排序值必须在0-9999之间');
        }
    }

    
    public static function validateTimeRange($startTime, $endTime): void
    {
        $start = is_numeric($startTime) ? (int)$startTime : strtotime($startTime);
        $end   = is_numeric($endTime) ? (int)$endTime : strtotime($endTime);

        if ($start === false || $end === false) {
            throw new BannerException('Thời gian格式Lỗi');
        }

        if ($start > $end) {
            throw BannerException::invalidTimeRange();
        }
    }

    
    public static function validateLink(string $link): void
    {
        
        if (mb_strlen($link) > 500) {
            throw new BannerException('链接长度不能超过500个字符');
        }

        
        if (preg_match('/^https?:\/\//', $link)) {
            if (!filter_var($link, FILTER_VALIDATE_URL)) {
                throw new BannerException('链接格式不正确');
            }
        }
    }

    
    public static function validateBatchIds(array $ids): void
    {
        if (empty($ids)) {
            throw new BannerException('Vui lòng chọn要Xóa的轮播图');
        }

        foreach ($ids as $id) {
            if (!is_numeric($id) || (int)$id <= 0) {
                throw new BannerException('ID参数格式Lỗi');
            }
        }
    }
}
