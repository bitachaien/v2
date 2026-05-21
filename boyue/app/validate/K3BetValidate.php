<?php

namespace app\validate;

class K3BetValidate
{
    
    protected static $rules = [
        'lotteryCode' => ['required', 'string', 'max:20', 'regex:/^[a-z0-9]+$/'],
        'issue' => ['required', 'string', 'length:12', 'regex:/^\d{12}$/'],
        'totalAmount' => ['required', 'numeric', 'min:1', 'max:1000000'],
        'bets' => ['required', 'array', 'min:1', 'max:100'],
    ];
    
    
    protected static $messages = [
        'lotteryCode.required' => '彩种代码không được để trống',
        'lotteryCode.regex' => '彩种代码格式Lỗi',
        'issue.required' => '期号không được để trống',
        'issue.regex' => '期号格式Lỗi',
        'totalAmount.min' => 'Đặt cượcSố tiền最小1元',
        'totalAmount.max' => 'Đặt cượcSố tiền最大100万',
        'bets.min' => '至少需要一注Đặt cược',
        'bets.max' => '单次最多100注',
    ];
    
    
    public static function validate(array $data): array
    {
        $errors = [];
        
        
        if (empty($data['lotteryCode'])) {
            $errors['lotteryCode'] = self::$messages['lotteryCode.required'];
        } elseif (!preg_match('/^[a-z0-9]+$/', $data['lotteryCode'])) {
            $errors['lotteryCode'] = self::$messages['lotteryCode.regex'];
        }
        
        
        if (empty($data['issue'])) {
            $errors['issue'] = self::$messages['issue.required'];
        } elseif (!preg_match('/^\d{12}$/', $data['issue'])) {
            $errors['issue'] = self::$messages['issue.regex'];
        }
        
        
        $amount = $data['totalAmount'] ?? 0;
        if (!is_numeric($amount) || $amount < 1) {
            $errors['totalAmount'] = self::$messages['totalAmount.min'];
        } elseif ($amount > 1000000) {
            $errors['totalAmount'] = self::$messages['totalAmount.max'];
        }
        
        
        $bets = $data['bets'] ?? [];
        if (!is_array($bets) || count($bets) < 1) {
            $errors['bets'] = self::$messages['bets.min'];
        } elseif (count($bets) > 100) {
            $errors['bets'] = self::$messages['bets.max'];
        }
        
        
        foreach ($bets as $index => $bet) {
            $betErrors = self::validateSingleBet($bet, $index);
            if (!empty($betErrors)) {
                $errors["bet_{$index}"] = $betErrors;
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    
    private static function validateSingleBet(array $bet, int $index): array
    {
        $errors = [];
        
        
        if (empty($bet['playId']) || !is_string($bet['playId'])) {
            $errors[] = "第{$index}注: 玩法ID无效";
        }
        
        
        $amount = $bet['amount'] ?? 0;
        if (!is_numeric($amount) || $amount < 1) {
            $errors[] = "第{$index}注: Số tiền最小1元";
        } elseif ($amount > 100000) {
            $errors[] = "第{$index}注: 单注Số tiền最大10万";
        }
        
        
        if (is_numeric($amount) && floor($amount * 100) != $amount * 100) {
            $errors[] = "第{$index}注: Số tiền精度最多2位小数";
        }
        
        return $errors;
    }
}
