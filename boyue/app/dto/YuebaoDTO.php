<?php

declare(strict_types=1);

namespace app\dto;

use app\constant\YuebaoConstant;


class YuebaoDTO
{
    

    
    public static function productFromModel(object $model): array
    {
        return [
            'id'            => (int)$model->id,
            'name'          => (string)($model->name ?? ''),
            'type'          => (string)($model->type ?? 'current'),
            'type_text'     => YuebaoConstant::getProductTypeText($model->type ?? 'current'),
            'rate'          => (float)($model->rate ?? 0),
            'rate_desc'     => (string)($model->rate_desc ?? ''),
            'duration_days' => (int)($model->duration_days ?? 0),
            'min_amount'    => number_format((float)($model->min_amount ?? 0), 2, '.', ''),
            'max_amount'    => number_format((float)($model->max_amount ?? 0), 2, '.', ''),
            'status'        => (int)($model->status ?? 0),
            'status_text'   => YuebaoConstant::getProductStatusText((int)($model->status ?? 0)),
            'sort'          => (int)($model->sort ?? 0),
        ];
    }

    
    public static function productToApi(object $model): array
    {
        return [
            'id'            => (int)$model->id,
            'name'          => (string)($model->name ?? ''),
            'type'          => (string)($model->type ?? 'current'),
            'rate'          => (float)($model->rate ?? 0),
            'rate_desc'     => (string)($model->rate_desc ?? ''),
            'duration_days' => (int)($model->duration_days ?? 0),
            'min_amount'    => number_format((float)($model->min_amount ?? 0), 2, '.', ''),
        ];
    }

    

    
    public static function statsFromModel(object $model): array
    {
        $currentAmount = (float)($model->current_amount ?? 0);
        $fixedAmount = (float)($model->fixed_amount ?? 0);
        $totalAmount = $currentAmount + $fixedAmount;

        return [
            'total_amount'       => number_format($totalAmount, 2, '.', ''),
            'current_amount'     => number_format($currentAmount, 2, '.', ''),
            'fixed_amount'       => number_format($fixedAmount, 2, '.', ''),
            'total_interest'     => number_format((float)($model->total_interest ?? 0), 2, '.', ''),
            'yesterday_interest' => number_format((float)($model->yesterday_interest ?? 0), 2, '.', ''),
            'seven_day_rate'     => number_format((float)($model->seven_day_rate ?? 0) * 100, 2, '.', ''),
            'is_open'            => (bool)($model->is_open ?? false),
        ];
    }

    
    public static function emptyStats(): array
    {
        return [
            'total_amount'       => '0.00',
            'current_amount'     => '0.00',
            'fixed_amount'       => '0.00',
            'total_interest'     => '0.00',
            'yesterday_interest' => '0.00',
            'seven_day_rate'     => '0.00',
            'is_open'            => false,
        ];
    }

    

    
    public static function holdingFromModel(object $model): array
    {
        return [
            'id'                => (string)($model->order_id ?? ''),
            'order_id'          => (string)($model->order_id ?? ''),
            'product_id'        => (int)($model->product_id ?? 0),
            'product_name'      => (string)($model->product_name ?? ''),
            'amount'            => number_format((float)($model->amount ?? 0), 2, '.', ''),
            'rate'              => (float)($model->rate ?? 0),
            'duration_days'     => (int)($model->duration_days ?? 0),
            'expected_interest' => number_format((float)($model->expected_interest ?? 0), 2, '.', ''),
            'actual_interest'   => number_format((float)($model->actual_interest ?? 0), 2, '.', ''),
            'status'            => (string)($model->status ?? ''),
            'status_text'       => YuebaoConstant::getHoldingStatusText($model->status ?? ''),
            'create_time'       => isset($model->create_time) ? date('Y-m-d H:i:s', (int)$model->create_time) : '',
            'end_time'          => isset($model->end_time) && $model->end_time > 0 
                ? date('Y-m-d H:i:s', (int)$model->end_time) 
                : '',
            'settle_time'       => isset($model->settle_time) && $model->settle_time > 0 
                ? date('Y-m-d H:i:s', (int)$model->settle_time) 
                : '',
        ];
    }

    

    
    public static function recordFromModel(object $model): array
    {
        return [
            'id'            => (string)($model->order_id ?? ''),
            'order_id'      => (string)($model->order_id ?? ''),
            'type'          => (string)($model->type ?? ''),
            'type_text'     => YuebaoConstant::getRecordTypeText($model->type ?? ''),
            'title'         => self::getRecordTitle($model),
            'product_id'    => (int)($model->product_id ?? 0),
            'product_name'  => (string)($model->product_name ?? ''),
            'amount'        => number_format((float)($model->amount ?? 0), 2, '.', ''),
            'before_balance'=> number_format((float)($model->before_balance ?? 0), 2, '.', ''),
            'after_balance' => number_format((float)($model->after_balance ?? 0), 2, '.', ''),
            'before_yebao'  => number_format((float)($model->before_yebao ?? 0), 2, '.', ''),
            'after_yebao'   => number_format((float)($model->after_yebao ?? 0), 2, '.', ''),
            'balance_after' => number_format((float)($model->after_yebao ?? 0), 2, '.', ''),
            'status'        => (string)($model->status ?? ''),
            'status_text'   => YuebaoConstant::getRecordStatusText($model->status ?? ''),
            'remark'        => (string)($model->remark ?? ''),
            'create_time'   => isset($model->create_time) ? date('Y-m-d H:i:s', (int)$model->create_time) : '',
        ];
    }

    
    public static function getRecordTitle(object $record): string
    {
        $type = $record->type ?? '';
        $productName = $record->product_name ?? '';

        switch ($type) {
            case YuebaoConstant::RECORD_TYPE_INCOME:
            case 'interest':
                return '利息宝-收益';
            case 'claim':
                return '利息宝-领取收益';
            case 'settle':
                return '利息宝-结算';
            case YuebaoConstant::RECORD_TYPE_DEPOSIT:
            case YuebaoConstant::RECORD_TYPE_TRANSFER_IN:
                return $productName ? "利息宝-转入({$productName})" : '利息宝-转入';
            case YuebaoConstant::RECORD_TYPE_WITHDRAW:
            case YuebaoConstant::RECORD_TYPE_TRANSFER_OUT:
                return '利息宝-转出';
            default:
                return '利息宝';
        }
    }

    

    
    public static function incomeTrend(array $incomeByDate, int $days): array
    {
        $dates = [];
        $values = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = date('m-d', time() - $i * 86400);
            $dates[] = $date;
            $values[] = (float)($incomeByDate[$date] ?? 0);
        }

        return [
            'dates'  => $dates,
            'values' => $values,
        ];
    }

    
    public static function assetComposition(object $stats): array
    {
        return [
            'current'  => number_format((float)($stats->current_amount ?? 0), 2, '.', ''),
            'fixed'    => number_format((float)($stats->fixed_amount ?? 0), 2, '.', ''),
            'interest' => number_format((float)($stats->total_interest ?? 0), 2, '.', ''),
        ];
    }

    

    
    public static function transferInResult(string $orderId, float $balanceAfter): array
    {
        return [
            'order_id'      => $orderId,
            'balance_after' => number_format($balanceAfter, 2, '.', ''),
        ];
    }

    
    public static function transferOutResult(string $orderId, float $balanceAfter): array
    {
        return [
            'order_id'      => $orderId,
            'balance_after' => number_format($balanceAfter, 2, '.', ''),
        ];
    }

    

    
    public static function formatMoney($amount): string
    {
        return number_format((float)$amount, 2, '.', '');
    }

    
    public static function formatTime(?int $timestamp, string $format = 'Y-m-d H:i:s'): string
    {
        if (empty($timestamp) || $timestamp <= 0) {
            return '';
        }
        return date($format, $timestamp);
    }
}
