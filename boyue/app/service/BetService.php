<?php

namespace app\service;

use support\Db;
use support\Log;
use app\repository\LotteryRepository;

class BetService
{
    
    public static function placeBet(array $params): array
    {
        $userId = $params['userId'];
        $user = $params['user'];
        $lotteryCode = $params['lotteryCode'];
        $caipiao = $params['caipiao'];
        $issue = $params['issue'];
        $bets = $params['bets'];
        $totalAmount = $params['totalAmount'];
        $source = $params['source'] ?? 'h5';
        $ip = $params['ip'] ?? '';
        
        Db::beginTransaction();
        
        try {
            
            $lockedUser = LotteryRepository::getUserForUpdate($userId);
            $actualBalance = (float)($lockedUser->balance ?? 0);
            
            if ($actualBalance < $totalAmount) {
                Db::rollBack();
                return ['success' => false, 'error' => 'Số dư không đủ（并发检查）', 'code' => 1003];
            }
            
            
            $orderId = 'K3' . date('YmdHis') . str_pad($userId, 3, '0', STR_PAD_LEFT);
            $balanceCursor = $actualBalance;
            $now = time();
            
            
            LotteryRepository::decrementBalance($userId, $totalAmount);
            
            
            foreach ($bets as $index => $bet) {
                $playId = $bet['playId'] ?? '';
                $content = $bet['content'] ?? '';
                $amount = (float)($bet['amount'] ?? 0);
                
                
                if ($amount < 1) continue;
                if ($amount > 100000) {
                    throw new \Exception('单注Số tiền不能超过10万');
                }
                $amount = round($amount, 2);
                
                
                $actualPlayId = K3Service::convertPlayId($playId, $content);
                
                
                $play = LotteryRepository::getPlay($actualPlayId);
                if (!$play || $play->isopen != 1) {
                    throw new \Exception('玩法không khả dụng: ' . $actualPlayId);
                }
                
                $betTrano = $orderId . sprintf('%02d', $index + 1);
                $balanceCursor -= $amount;
                
                
                LotteryRepository::createBet([
                    'uid' => $userId,
                    'username' => $user->username,
                    'trano' => $betTrano,
                    'cpname' => $lotteryCode,
                    'cptitle' => $caipiao->title,
                    'typeid' => $caipiao->typeid,
                    'expect' => $issue,
                    'playid' => $actualPlayId,
                    'playtitle' => $play->title,
                    'tzcode' => $content,
                    'amount' => $amount,
                    'amountafter' => $balanceCursor,
                    'beishu' => 1,
                    'mode' => 1,
                    'itemcount' => 1,
                    'yjf' => '元',
                    'ishemai' => 0,
                    'isdraw' => 0,
                    'okamount' => 0,
                    'okcount' => 0,
                    'oddtime' => $now,
                    'source' => $source
                ]);
            }
            
            
            LotteryRepository::createMoneyLog([
                'uid' => $userId,
                'username' => $user->username,
                'type' => 1,
                'typename' => 'Đặt cược',
                'trano' => $orderId,
                'amount' => -$totalAmount,
                'before' => $actualBalance,
                'after' => $actualBalance - $totalAmount,
                'remark' => "Đặt cược-{$caipiao->title}-{$issue}",
                'addtime' => $now,
                'ip' => $ip
            ]);
            
            Db::commit();
            
            return [
                'success' => true,
                'data' => [
                    'orderId' => $orderId,
                    'issue' => $issue,
                    'totalBets' => count($bets),
                    'totalAmount' => $totalAmount,
                    'balance' => $actualBalance - $totalAmount
                ]
            ];
            
        } catch (\Exception $e) {
            Db::rollBack();
            Log::error('Đặt cược事务Thất bại: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage(), 'code' => 9999];
        }
    }
    
    
    public static function validateParams(array $params): array
    {
        if (empty($params['lotteryCode'])) {
            return ['valid' => false, 'error' => '彩种代码không được để trống'];
        }
        if (empty($params['issue'])) {
            return ['valid' => false, 'error' => '期号không được để trống'];
        }
        if (empty($params['bets']) || !is_array($params['bets'])) {
            return ['valid' => false, 'error' => 'Đặt cược内容không được để trống'];
        }
        if (($params['totalAmount'] ?? 0) <= 0) {
            return ['valid' => false, 'error' => 'Đặt cượcSố tiền必须大于0'];
        }
        if (count($params['bets']) > 100) {
            return ['valid' => false, 'error' => '单次最多100注'];
        }
        
        return ['valid' => true];
    }
    
    
    public static function validateUser(int $userId, float $totalAmount): array
    {
        $user = LotteryRepository::getUser($userId);
        
        if (!$user) {
            return ['valid' => false, 'error' => 'Người dùng không tồn tại', 'code' => 2001];
        }
        
        $balance = (float)($user->balance ?? 0);
        if ($balance < $totalAmount) {
            return ['valid' => false, 'error' => 'Số dư không đủ', 'code' => 1003];
        }
        
        return ['valid' => true, 'user' => $user, 'balance' => $balance];
    }
    
    
    public static function validateTiming(string $lotteryCode, string $issue): array
    {
        $lotteryCode = K3Service::normalizeCode($lotteryCode);
        
        
        $caipiao = K3Service::getLotteryConfig($lotteryCode);
        if (!$caipiao || $caipiao->isopen != 1) {
            return ['valid' => false, 'error' => '彩种đã đóng', 'code' => 1004];
        }
        
        $now = time();
        $issueInfo = K3Service::calculateCurrentIssue($caipiao, $now);
        
        
        if ($issue != $issueInfo['currFullExpect']) {
            return [
                'valid' => false, 
                'error' => '期号已截止', 
                'code' => 1002,
                'currentIssue' => $issueInfo['currFullExpect']
            ];
        }
        
        
        $exists = Db::table('caipiao_kaijiang')
            ->where('name', $lotteryCode)
            ->where('expect', $issue)
            ->where('opentime', '<=', $now)
            ->exists();
        
        if ($exists) {
            return ['valid' => false, 'error' => '该期已Mở thưởng', 'code' => 1002];
        }
        
        
        $closeSeconds = ($caipiao->ftime ?? 10) + 3; 
        if ($issueInfo['remainTime'] <= $closeSeconds) {
            return ['valid' => false, 'error' => '期号已封盘', 'code' => 1002];
        }
        
        return [
            'valid' => true, 
            'caipiao' => $caipiao, 
            'issueInfo' => $issueInfo,
            'lotteryCode' => $lotteryCode
        ];
    }
}
