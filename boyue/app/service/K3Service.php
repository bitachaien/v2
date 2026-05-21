<?php

namespace app\service;

use support\Db;
use support\Log;

class K3Service
{
    
    public static function getCurrentInfo(string $lotteryCode): ?array
    {
        $lotteryCode = self::normalizeCode($lotteryCode);
        
        $caipiao = Db::table('caipiao_caipiao')
            ->where('name', $lotteryCode)
            ->where('typeid', 'k3')
            ->first();
        
        if (!$caipiao) {
            return null;
        }
        
        $now = time();
        $issueInfo = self::calculateCurrentIssue($caipiao, $now);
        
        
        $lastResult = self::getLastResult($lotteryCode, $issueInfo['currFullExpect']);
        
        
        $closeSeconds = $caipiao->ftime ?? 10;
        $status = $issueInfo['remainTime'] <= $closeSeconds ? 2 : 1;
        
        return [
            'lotteryCode' => $lotteryCode,
            'lotteryName' => $caipiao->title,
            'currentIssue' => $issueInfo['currFullExpect'],
            'lastIssue' => $lastResult['expect'] ?? '',
            'lastOpenCode' => $lastResult['opencode'] ?? [],
            'lastOpenTime' => $lastResult['opentime'] ?? '',
            'status' => $status,
            'endTime' => $issueInfo['issueTime'] + $caipiao->expecttime * 60,
            'serverTime' => $now,
            'countdown' => max(0, $issueInfo['remainTime']),
        ];
    }
    
    
    public static function calculateCurrentIssue($caipiao, int $now): array
    {
        $today = date('Y-m-d', $now);
        $firsttime = strtotime($today . ' ' . ($caipiao->firsttime ?: '00:00:00'));
        $expecttime = $caipiao->expecttime;
        
        
        if (empty($expecttime) || $expecttime <= 0) {
            throw new \Exception('彩种配置Lỗi，Mở thưởng间隔Thời gian无效');
        }
        
        
        $is24Hour = empty($caipiao->endtime);
        if ($is24Hour) {
            $endtime = $firsttime + 86400 - 1;
        } else {
            $endtime = strtotime($today . ' ' . $caipiao->endtime);
            if ($endtime <= $firsttime) {
                $endtime += 86400;
            }
        }
        
        
        if (!$is24Hour && $now < $firsttime) {
            $currIssueNum = 1;
            $issueTime = $firsttime;
        } else if (!$is24Hour && $now > $endtime) {
            $currIssueNum = 1;
            $issueTime = $firsttime + 86400;
        } else {
            $passedMinutes = ($now - $firsttime) / 60;
            if ($passedMinutes < 0) {
                $passedMinutes += 1440;
            }
            $currIssueNum = floor($passedMinutes / $expecttime) + 1;
            $issueTime = $firsttime + ($currIssueNum - 1) * $expecttime * 60;
        }
        
        $datePrefix = date('Ymd', $issueTime);
        $currExpect = str_pad($currIssueNum, 4, '0', STR_PAD_LEFT);
        
        return [
            'currExpect' => $currExpect,
            'currFullExpect' => $datePrefix . $currExpect,
            'remainTime' => max(0, $issueTime + $expecttime * 60 - $now),
            'issueTime' => $issueTime,
        ];
    }
    
    
    public static function getLastResult(string $lotteryCode, string $currentIssue): array
    {
        $lastKj = Db::table('caipiao_kaijiang')
            ->where('name', $lotteryCode)
            ->where('expect', '<', $currentIssue)
            ->where('opentime', '<=', time())
            ->orderBy('expect', 'desc')
            ->first();
        
        if (!$lastKj || empty($lastKj->opencode)) {
            return [];
        }
        
        return [
            'expect' => $lastKj->expect,
            'opencode' => array_map('intval', explode(',', $lastKj->opencode)),
            'opentime' => date('Y-m-d H:i:s', $lastKj->opentime),
        ];
    }
    
    
    public static function validateBet(string $lotteryCode, string $issue, int $userId): array
    {
        $lotteryCode = self::normalizeCode($lotteryCode);
        
        
        $caipiao = Db::table('caipiao_caipiao')
            ->where('name', $lotteryCode)
            ->where('typeid', 'k3')
            ->first();
        
        if (!$caipiao || $caipiao->isopen != 1) {
            return ['valid' => false, 'error' => '彩种đã đóng', 'code' => 1004];
        }
        
        $now = time();
        $issueInfo = self::calculateCurrentIssue($caipiao, $now);
        
        
        if ($issue != $issueInfo['currFullExpect']) {
            return [
                'valid' => false, 
                'error' => '期号已截止',
                'code' => 1002,
                'data' => ['currentIssue' => $issueInfo['currFullExpect']]
            ];
        }
        
        
        $existingDraw = Db::table('caipiao_kaijiang')
            ->where('name', $lotteryCode)
            ->where('expect', $issue)
            ->where('opentime', '<=', $now)
            ->first();
        
        if ($existingDraw) {
            return [
                'valid' => false,
                'error' => '该期已Mở thưởng，无法Đặt cược',
                'code' => 1002,
            ];
        }
        
        
        $closeSeconds = $caipiao->ftime ?? 10;
        $safetyBuffer = config('lottery.business.safety_buffer', 3);
        
        if ($issueInfo['remainTime'] <= $closeSeconds + $safetyBuffer) {
            return [
                'valid' => false,
                'error' => '期号已封盘',
                'code' => 1002,
            ];
        }
        
        return [
            'valid' => true,
            'data' => [
                'caipiao' => $caipiao,
                'issueInfo' => $issueInfo,
            ]
        ];
    }
    
    
    public static function convertPlayId(string $playId, string $content): string
    {
        
        if ($playId === 'k3hzzx' && is_numeric($content)) {
            $value = (int)$content;
            if ($value >= 3 && $value <= 18) {
                return 'k3hz' . $value;
            }
        }
        
        
        if ($playId === 'sthtx') {
            return 'k3sthtx';
        }
        
        
        if ($playId === 'slhtx') {
            return 'k3slhtx';
        }
        
        return $playId;
    }
    
    
    public static function getHistory(string $lotteryCode, int $limit = 30): array
    {
        $lotteryCode = self::normalizeCode($lotteryCode);
        
        
        $caipiao = self::getLotteryConfig($lotteryCode);
        $currentIssue = null;
        if ($caipiao) {
            try {
                $issueInfo = self::calculateCurrentIssue($caipiao, time());
                $currentIssue = $issueInfo['currFullExpect'];
            } catch (\Exception $e) {
                
            }
        }
        
        
        $query = Db::table('caipiao_kaijiang')
            ->where('name', $lotteryCode)
            ->where('opentime', '<=', time())
            ->where('opencode', '!=', '');
        
        if ($currentIssue) {
            $query->where('expect', '<', $currentIssue);
        }
        
        $list = $query->orderBy('expect', 'desc')->limit($limit)->get();
        
        return array_map(function($item) {
            $openCode = array_map('intval', explode(',', $item->opencode));
            $sum = array_sum($openCode);
            return [
                'issue' => $item->expect,
                'openCode' => $openCode,
                'openTime' => date('Y-m-d H:i:s', $item->opentime),
                'sum' => $sum,
                'size' => $sum > 10 ? 'big' : 'small',
                'oddEven' => $sum % 2 === 1 ? 'odd' : 'even',
            ];
        }, $list->toArray());
    }
    
    
    public static function normalizeCode(string $code): string
    {
        
        if (preg_match('/^(\d+)(f)(k3)$/i', $code, $matches)) {
            return 'f' . $matches[1] . 'k3';
        }
        
        
        if (preg_match('/^10f(k3)$/i', $code)) {
            return 'sfks';
        }
        
        return strtolower($code);
    }
    
    
    public static function getLotteryConfig(string $lotteryCode)
    {
        return Db::table('caipiao_caipiao')
            ->where('name', self::normalizeCode($lotteryCode))
            ->where('typeid', 'k3')
            ->first();
    }
    
    
    public static function getPlayInfo(string $playId)
    {
        return Db::table('caipiao_wanfa')
            ->where('playid', $playId)
            ->where('isopen', 1)
            ->first();
    }
}
