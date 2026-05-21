<?php

declare(strict_types=1);

namespace app\validator;

use app\exception\YuebaoException;
use app\constant\YuebaoConstant;


class YuebaoValidator
{
    
    public static function validateUserId(int $uid): void
    {
        if ($uid <= 0) {
            throw YuebaoException::notLoggedIn();
        }
    }

    
    public static function validateTransferIn(array $data): void
    {
        
        $productId = $data['product_id'] ?? ($data['productId'] ?? 0);
        if (empty($productId) || !is_numeric($productId) || (int)$productId <= 0) {
            throw YuebaoException::productRequired();
        }

        
        self::validateAmount($data['amount'] ?? 0);
    }

    
    public static function validateTransferOut(array $data): void
    {
        
        self::validateAmount($data['amount'] ?? 0);
    }

    
    public static function validateAmount($amount): float
    {
        if (!is_numeric($amount)) {
            throw YuebaoException::invalidAmount();
        }

        $amount = (float)$amount;

        if ($amount <= 0) {
            throw YuebaoException::amountMustPositive();
        }

        
        if (round($amount, 2) !== $amount) {
            throw YuebaoException::invalidAmount();
        }

        return $amount;
    }

    
    public static function validateMinAmount(float $amount, float $minAmount): void
    {
        if ($amount < $minAmount) {
            throw YuebaoException::amountBelowMinimum($minAmount);
        }
    }

    
    public static function validateMaxAmount(float $amount, float $maxAmount): void
    {
        if ($maxAmount > 0 && $amount > $maxAmount) {
            throw YuebaoException::amountExceedsMaximum($maxAmount);
        }
    }

    
    public static function validateBalance(float $required, float $available): void
    {
        if ($available < $required) {
            throw YuebaoException::insufficientBalance($required, $available);
        }
    }

    
    public static function validateCurrentBalance(float $required, float $available): void
    {
        if ($available < $required) {
            throw YuebaoException::insufficientCurrentBalance($required, $available);
        }
    }

    
    public static function validateProductId(int $productId): void
    {
        if ($productId <= 0) {
            throw YuebaoException::productRequired();
        }
    }

    
    public static function validatePagination(array $params): array
    {
        $page = isset($params['page']) ? max(1, (int)$params['page']) : 1;
        
        $pageSize = $params['page_size'] ?? $params['pageSize'] ?? YuebaoConstant::DEFAULT_PAGE_SIZE;
        $pageSize = min(max(1, (int)$pageSize), YuebaoConstant::MAX_PAGE_SIZE);

        return [
            'page'      => $page,
            'page_size' => $pageSize,
            'offset'    => ($page - 1) * $pageSize,
        ];
    }

    
    public static function validateRecordParams(array $params): array
    {
        $pagination = self::validatePagination($params);

        $type = $params['type'] ?? 'all';
        $productType = $params['product_type'] ?? '';
        
        $dateRange = $params['date_range'] ?? $params['dateRange'] ?? '';

        
        $validTypes = ['all', 'transfer', 'income', 'deposit', 'withdraw', 'claim', 'interest'];
        if (!in_array($type, $validTypes)) {
            $type = 'all';
        }

        
        $validProductTypes = ['', 'current', 'fixed'];
        if (!in_array($productType, $validProductTypes)) {
            $productType = '';
        }

        
        $validDateRanges = ['', 'today', 'yesterday', 'week', 'month'];
        if (!in_array($dateRange, $validDateRanges)) {
            $dateRange = '';
        }

        return array_merge($pagination, [
            'type'         => $type,
            'product_type' => $productType,
            'date_range'   => $dateRange,
        ]);
    }

    
    public static function validateHoldingParams(array $params): array
    {
        $status = $params['status'] ?? 'active';

        
        $validStatuses = ['active', 'completed', 'all'];
        if (!in_array($status, $validStatuses)) {
            $status = 'active';
        }

        return ['status' => $status];
    }

    
    public static function validateAnalysisDays($days): int
    {
        $days = is_numeric($days) ? (int)$days : 7;
        
        
        return min(max($days, 1), 90);
    }

    
    public static function validateEmail(string $email): string
    {
        $email = trim($email);
        
        if (empty($email)) {
            throw YuebaoException::paramRequired('邮箱地址');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw YuebaoException::invalidEmail();
        }

        return $email;
    }

    
    public static function validateExportParams(array $data): array
    {
        $email = self::validateEmail($data['email'] ?? '');

        $dateRange = isset($data['date_range']) ? (int)$data['date_range'] : 3;
        
        
        $dateRange = min(max($dateRange, 1), 12);

        return [
            'email'      => $email,
            'date_range' => $dateRange,
        ];
    }

    
    public static function validateFundPassword(string $inputPassword, string $storedPassword, bool $isRequired = false): void
    {
        
        if (empty($storedPassword) && !$isRequired) {
            return;
        }

        
        if (!empty($storedPassword)) {
            if (empty($inputPassword)) {
                throw YuebaoException::paramRequired('资金Mật khẩu');
            }

            
            $isValid = false;
            if (strlen($storedPassword) === 32) {
                
                $isValid = hash_equals($storedPassword, md5($inputPassword));
            } elseif (strlen($storedPassword) === 60 && substr($storedPassword, 0, 4) === '$2y$') {
                
                $isValid = password_verify($inputPassword, $storedPassword);
            } else {
                
                $isValid = hash_equals($storedPassword, md5($inputPassword));
            }
            
            if (!$isValid) {
                throw YuebaoException::wrongFundPassword();
            }
        }
    }
}
