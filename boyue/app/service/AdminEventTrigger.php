<?php

namespace app\service;

use support\Log;

class AdminEventTrigger
{
    
    const LARGE_DEPOSIT_THRESHOLD = 50000;   
    const LARGE_WITHDRAW_THRESHOLD = 30000;  
    
    
    
    
    public static function onNewDeposit($deposit): void
    {
        try {
            $deposit = (array)$deposit;
            
            
            AdminWebSocketPusher::pushNewDeposit([
                'id' => $deposit['id'] ?? 0,
                'username' => $deposit['username'] ?? '',
                'amount' => $deposit['money'] ?? $deposit['amount'] ?? 0,
                'channel' => $deposit['payname'] ?? $deposit['channel'] ?? '',
            ]);
            
            
            AdminWebSocketPusher::pushRealtimeActivity([
                'type' => 'recharge',
                'username' => $deposit['username'] ?? '',
                'amount' => $deposit['money'] ?? $deposit['amount'] ?? 0,
            ]);
            
            
            $amount = floatval($deposit['money'] ?? $deposit['amount'] ?? 0);
            if ($amount >= self::LARGE_DEPOSIT_THRESHOLD) {
                AdminWebSocketPusher::pushLargeAmountAlert([
                    'type' => 'deposit',
                    'username' => $deposit['username'] ?? '',
                    'amount' => $amount,
                    'warning' => "大额充值 {$amount} 元",
                ]);
            }
            
        } catch (\Exception $e) {
            Log::error('[AdminEvent] onNewDeposit error: ' . $e->getMessage());
        }
    }
    
    
    public static function onNewWithdraw($withdraw): void
    {
        try {
            $withdraw = (array)$withdraw;
            $amount = floatval($withdraw['money'] ?? $withdraw['amount'] ?? 0);
            
            
            $riskLevel = 'low';
            if ($amount >= self::LARGE_WITHDRAW_THRESHOLD) {
                $riskLevel = 'high';
            } elseif ($amount >= self::LARGE_WITHDRAW_THRESHOLD / 2) {
                $riskLevel = 'medium';
            }
            
            
            AdminWebSocketPusher::pushNewWithdraw([
                'id' => $withdraw['id'] ?? 0,
                'username' => $withdraw['username'] ?? '',
                'amount' => $amount,
                'riskLevel' => $riskLevel,
            ]);
            
            
            AdminWebSocketPusher::pushRealtimeActivity([
                'type' => 'withdraw',
                'username' => $withdraw['username'] ?? '',
                'amount' => $amount,
            ]);
            
            
            if ($amount >= self::LARGE_WITHDRAW_THRESHOLD) {
                AdminWebSocketPusher::pushLargeAmountAlert([
                    'type' => 'withdraw',
                    'username' => $withdraw['username'] ?? '',
                    'amount' => $amount,
                    'warning' => "大额提现 {$amount} 元",
                ]);
            }
            
        } catch (\Exception $e) {
            Log::error('[AdminEvent] onNewWithdraw error: ' . $e->getMessage());
        }
    }
    
    
    
    
    public static function onNewBet($bet): void
    {
        try {
            $bet = (array)$bet;
            
            AdminWebSocketPusher::pushRealtimeActivity([
                'type' => 'bet',
                'username' => $bet['username'] ?? '',
                'amount' => $bet['money'] ?? $bet['betAmount'] ?? 0,
                'extra' => [
                    'lottery' => $bet['lottery_name'] ?? '',
                    'issue' => $bet['expect'] ?? '',
                ],
            ]);
            
        } catch (\Exception $e) {
            Log::error('[AdminEvent] onNewBet error: ' . $e->getMessage());
        }
    }
    
    
    public static function onWin($bet): void
    {
        try {
            $bet = (array)$bet;
            
            AdminWebSocketPusher::pushRealtimeActivity([
                'type' => 'win',
                'username' => $bet['username'] ?? '',
                'amount' => $bet['winmoney'] ?? $bet['winAmount'] ?? 0,
                'extra' => [
                    'lottery' => $bet['lottery_name'] ?? '',
                    'issue' => $bet['expect'] ?? '',
                ],
            ]);
            
        } catch (\Exception $e) {
            Log::error('[AdminEvent] onWin error: ' . $e->getMessage());
        }
    }
    
    
    
    
    public static function onLotteryOpen(string $lotteryCode, string $issue, string $numbers, string $lotteryName = ''): void
    {
        try {
            AdminWebSocketPusher::pushLotteryOpen([
                'lotteryCode' => $lotteryCode,
                'lotteryName' => $lotteryName,
                'issue' => $issue,
                'numbers' => $numbers,
            ]);
            
        } catch (\Exception $e) {
            Log::error('[AdminEvent] onLotteryOpen error: ' . $e->getMessage());
        }
    }
    
    
    public static function onLotteryClose(string $lotteryCode, string $issue): void
    {
        try {
            AdminWebSocketPusher::pushLotteryClose([
                'lotteryCode' => $lotteryCode,
                'issue' => $issue,
                'status' => 'closed',
            ]);
            
        } catch (\Exception $e) {
            Log::error('[AdminEvent] onLotteryClose error: ' . $e->getMessage());
        }
    }
    
    
    
    
    public static function onRiskAlert(int $userId, string $rule, string $level = 'medium', string $detail = ''): void
    {
        try {
            
            $user = \support\Db::table('caipiao_member')->where('id', $userId)->first();
            
            AdminWebSocketPusher::pushRiskAlert([
                'userId' => $userId,
                'username' => $user->username ?? '',
                'rule' => $rule,
                'level' => $level,
                'detail' => $detail,
            ]);
            
        } catch (\Exception $e) {
            Log::error('[AdminEvent] onRiskAlert error: ' . $e->getMessage());
        }
    }
    
    
    public static function onUserBanned(string $username, string $reason, string $operator = 'system'): void
    {
        try {
            AdminWebSocketPusher::pushUserBanned([
                'username' => $username,
                'reason' => $reason,
                'operator' => $operator,
            ]);
            
        } catch (\Exception $e) {
            Log::error('[AdminEvent] onUserBanned error: ' . $e->getMessage());
        }
    }
    
    
    
    
    public static function onRobotLog(int $botId, string $message, string $level = 'info'): void
    {
        try {
            AdminWebSocketPusher::pushRobotLog([
                'botId' => $botId,
                'message' => $message,
                'level' => $level,
            ]);
            
        } catch (\Exception $e) {
            Log::error('[AdminEvent] onRobotLog error: ' . $e->getMessage());
        }
    }
    
    
    public static function onRobotStatusChange(int $botId, string $status, string $message = ''): void
    {
        try {
            AdminWebSocketPusher::pushRobotStatus([
                'botId' => $botId,
                'status' => $status,
                'message' => $message,
            ]);
            
        } catch (\Exception $e) {
            Log::error('[AdminEvent] onRobotStatusChange error: ' . $e->getMessage());
        }
    }
    
    
    
    
    public static function onSystemNotice(string $title, string $content, string $level = 'info'): void
    {
        try {
            AdminWebSocketPusher::pushSystemNotice([
                'title' => $title,
                'content' => $content,
                'level' => $level,
            ]);
            
        } catch (\Exception $e) {
            Log::error('[AdminEvent] onSystemNotice error: ' . $e->getMessage());
        }
    }
}
