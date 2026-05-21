<?php

declare(strict_types=1);

namespace app\constant;


class YuebaoConstant
{
    
    
    
    public const PRODUCT_TYPE_CURRENT = 'current';

    
    public const PRODUCT_TYPE_FIXED = 'fixed';

    
    public const PRODUCT_TYPE_MAP = [
        self::PRODUCT_TYPE_CURRENT => '活期',
        self::PRODUCT_TYPE_FIXED   => '定期',
    ];

    
    
    
    public const PRODUCT_STATUS_DISABLED = 0;

    
    public const PRODUCT_STATUS_ENABLED = 1;

    
    public const PRODUCT_STATUS_MAP = [
        self::PRODUCT_STATUS_DISABLED => '禁用',
        self::PRODUCT_STATUS_ENABLED  => '启用',
    ];

    
    
    
    public const HOLDING_STATUS_RUNNING = 'running';

    
    public const HOLDING_STATUS_SETTLED = 'settled';

    
    public const HOLDING_STATUS_DONE = 'done';

    
    public const HOLDING_STATUS_CANCELED = 'canceled';

    
    public const HOLDING_STATUS_MAP = [
        self::HOLDING_STATUS_RUNNING  => '运行中',
        self::HOLDING_STATUS_SETTLED  => '已结算',
        self::HOLDING_STATUS_DONE     => 'Đã hoàn thành',
        self::HOLDING_STATUS_CANCELED => 'Đã hủy',
    ];

    
    
    
    public const RECORD_TYPE_DEPOSIT = 'deposit';

    
    public const RECORD_TYPE_WITHDRAW = 'withdraw';

    
    public const RECORD_TYPE_INCOME = 'income';

    
    public const RECORD_TYPE_TRANSFER_IN = 'transfer_in';

    
    public const RECORD_TYPE_TRANSFER_OUT = 'transfer_out';

    
    public const RECORD_TYPE_SETTLE = 'settle';

    
    public const RECORD_TYPE_MAP = [
        self::RECORD_TYPE_DEPOSIT      => '转入',
        self::RECORD_TYPE_WITHDRAW     => '转出',
        self::RECORD_TYPE_INCOME       => '收益',
        self::RECORD_TYPE_TRANSFER_IN  => '转入',
        self::RECORD_TYPE_TRANSFER_OUT => '转出',
        self::RECORD_TYPE_SETTLE       => '定期结算',
    ];

    
    
    
    public const RECORD_STATUS_SUCCESS = 'success';

    
    public const RECORD_STATUS_FAILED = 'failed';

    
    public const RECORD_STATUS_PENDING = 'pending';

    
    public const RECORD_STATUS_MAP = [
        self::RECORD_STATUS_SUCCESS => 'Thành công',
        self::RECORD_STATUS_FAILED  => 'Thất bại',
        self::RECORD_STATUS_PENDING => 'Đang xử lý',
    ];

    
    
    
    public const MONEYLOG_TYPE_TRANSFER_IN = 11;

    
    public const MONEYLOG_TYPE_TRANSFER_OUT = 12;

    
    public const MONEYLOG_TYPE_INCOME = 13;

    
    
    
    public const DEFAULT_PAGE_SIZE = 20;

    
    public const DEFAULT_API_LIMIT = 10;

    
    public const MAX_PAGE_SIZE = 100;

    
    
    
    public const ORDER_PREFIX_IN = 'YBIN';

    
    public const ORDER_PREFIX_OUT = 'YBOUT';

    
    public const ORDER_PREFIX_INCOME = 'YBINC';

    

    
    public static function getProductTypeText(string $type): string
    {
        return self::PRODUCT_TYPE_MAP[$type] ?? '未知';
    }

    
    public static function getProductStatusText(int $status): string
    {
        return self::PRODUCT_STATUS_MAP[$status] ?? '未知';
    }

    
    public static function getHoldingStatusText(string $status): string
    {
        return self::HOLDING_STATUS_MAP[$status] ?? '未知';
    }

    
    public static function getRecordTypeText(string $type): string
    {
        return self::RECORD_TYPE_MAP[$type] ?? '未知';
    }

    
    public static function getRecordStatusText(string $status): string
    {
        return self::RECORD_STATUS_MAP[$status] ?? '未知';
    }

    
    public static function isValidProductType(string $type): bool
    {
        return array_key_exists($type, self::PRODUCT_TYPE_MAP);
    }

    
    public static function isValidProductStatus(int $status): bool
    {
        return array_key_exists($status, self::PRODUCT_STATUS_MAP);
    }

    
    public static function isCurrentProduct(string $type): bool
    {
        return $type === self::PRODUCT_TYPE_CURRENT;
    }

    
    public static function isFixedProduct(string $type): bool
    {
        return $type === self::PRODUCT_TYPE_FIXED;
    }

    
    public static function generateOrderId(string $prefix): string
    {
        
        $microtime = microtime(true);
        $timestamp = date('YmdHis');
        $micro = sprintf('%06d', ($microtime - floor($microtime)) * 1000000);
        $random = mt_rand(1000, 9999);
        
        return $prefix . $timestamp . $micro . $random;
    }
}
