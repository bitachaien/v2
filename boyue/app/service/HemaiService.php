<?php

namespace app\service;

use support\Db;
use support\Log;

class HemaiService
{
    
    public function getList($params)
    {
        $status = $params['status'] ?? 'all';
        $lottery = $params['lottery'] ?? 'all';
        $page = max(1, intval($params['page'] ?? 1));
        $pageSize = min(50, max(1, intval($params['pageSize'] ?? 10)));
        $uid = $params['uid'] ?? 0;
        
        
        $query = Db::table('caipiao_hemai');
        
        
        $defaultOrder = 'createtime';
        $defaultDirection = 'desc';
        
        
        switch ($status) {
            case 'soon':
                
                $now = time() * 1000;
                $tenMinutesLater = $now + (10 * 60 * 1000);
                $query->where('status', 0)
                    ->where('endtime', '>', $now)
                    ->where('endtime', '<=', $tenMinutesLater)
                    ->whereRaw('(buytotal - buyhave) / buytotal >= 0.8');
                
                $defaultOrder = 'endtime';
                $defaultDirection = 'asc';
                break;
                
            case 'hot':
                
                $query->where('status', 0)
                    ->where('amount', '>=', 500); 
                
                $defaultOrder = 'buyed';
                $defaultDirection = 'desc';
                break;
                
            case 'mine':
                
                if ($uid > 0) {
                    $query->where('uid', $uid);
                } else {
                    
                    return [
                        'list' => [],
                        'total' => 0,
                        'page' => $page,
                        'pageSize' => $pageSize
                    ];
                }
                break;
                
            default:
                
                $query->where('status', 0);
                break;
        }
        
        
        if ($lottery != 'all') {
            $query->where('cpname', $lottery);
        }
        
        
        $total = clone $query;
        $total = $total->count();
        
        
        $list = $query->orderBy($defaultOrder, $defaultDirection)
            ->offset(($page - 1) * $pageSize)
            ->limit($pageSize)
            ->get();
        
        
        $data = [];
        foreach ($list as $item) {
            
            $progress = 0;
            if ($item->buytotal > 0) {
                $progress = round(($item->buytotal - $item->buyhave) / $item->buytotal * 100);
            }
            
            
            $userStats = $this->getUserStats($item->uid);
            
            
            $isdrawText = 'Đang diễn ra';
            if ($item->status == 1) {
                $isdrawText = '已截止';
            } elseif ($item->status == 2) {
                $isdrawText = '已Mở thưởng';
            } elseif ($item->status == 3) {
                $isdrawText = 'Đã hủy';
            }
            
            $data[] = [
                'id' => $item->id,
                'cptitle' => $item->cptitle,
                'cpid' => $item->cpname,
                'expect' => $item->expect,
                'playtitle' => $item->playtitle,
                'amount' => number_format($item->amount, 2, '.', ''),
                'hemaipic' => number_format($item->hemaipic, 2, '.', ''),
                'buyhave' => $item->buyhave,
                'buytotal' => $item->buytotal,
                'progress' => $progress,
                'isdraw' => $isdrawText,
                'baodi' => $item->baodi,
                'username' => $item->username,
                'level' => $userStats['level'],
                'winRate' => $userStats['last_5_win'],
                'followerCount' => $userStats['follower_count'],
                'endtime' => $item->endtime,
                'tzcode' => $item->tzcode,
                'opencode' => $item->opencode ?? '',
                'content' => $item->content ?? '',
            ];
        }
        
        return [
            'list' => $data,
            'total' => $total,
            'page' => $page,
            'pageSize' => $pageSize
        ];
    }
    
    
    public function getDetail($id)
    {
        $item = Db::table('caipiao_hemai')
            ->where('id', $id)
            ->first();
        
        if (!$item) {
            return null;
        }
        
        
        $progress = 0;
        if ($item->buytotal > 0) {
            $progress = round(($item->buytotal - $item->buyhave) / $item->buytotal * 100);
        }
        
        
        $userStats = $this->getUserStats($item->uid);
        
        
        $isdrawText = 'Đang diễn ra';
        if ($item->status == 1) {
            $isdrawText = '已截止';
        } elseif ($item->status == 2) {
            $isdrawText = '已Mở thưởng';
        } elseif ($item->status == 3) {
            $isdrawText = 'Đã hủy';
        }
        
        
        Db::table('caipiao_hemai')
            ->where('id', $id)
            ->increment('views', 1);
        
        
        $tzcodeMasked = $this->maskTzcode($item->tzcode, $item->public_type ?? 1, $item->status);
        
        return [
            'id' => $item->id,
            'cptitle' => $item->cptitle,
            'cpid' => $item->cpname,
            'expect' => $item->expect,
            'playtitle' => $item->playtitle,
            'amount' => number_format($item->amount, 2, '.', ''),
            'hemaipic' => number_format($item->hemaipic, 2, '.', ''),
            'buyhave' => $item->buyhave,
            'buytotal' => $item->buytotal,
            'progress' => $progress,
            'isdraw' => $isdrawText,
            'baodi' => $item->baodi,
            'commission' => $item->commission ?? 0,
            'public_type' => $item->public_type ?? 1,
            'views' => ($item->views ?? 0) + 1,
            'username' => $item->username,
            'level' => $userStats['level'],
            'winRate' => $userStats['last_5_win'],
            'endtime' => $item->endtime,
            'tzcode' => $tzcodeMasked,
            'tzcode_full' => $item->tzcode, 
            'opencode' => $item->opencode ?? '',
            'content' => $item->content ?? '',
            'isWin' => $item->isdraw == 1,
            'winAmount' => number_format($item->winamount, 2, '.', ''),
        ];
    }
    
    
    public function getUsers($id)
    {
        $joins = Db::table('caipiao_hemai_join')
            ->where('hemai_id', $id)
            ->orderBy('jointime', 'desc')
            ->get();
        
        $list = [];
        $totalBought = 0;
        
        foreach ($joins as $join) {
            $list[] = [
                'username' => $this->maskUsername($join->username),
                'num' => $join->num,
                'amount' => number_format($join->amount, 2, '.', ''),
                'time' => $join->jointime,
            ];
            $totalBought += $join->num;
        }
        
        return [
            'list' => $list,
            'totalBought' => $totalBought
        ];
    }
    
    
    public function buy($id, $uid, $username, $num, $ip)
    {
        
        $hemai = Db::table('caipiao_hemai')
            ->where('id', $id)
            ->first();
        
        if (!$hemai) {
            throw new \Exception('合买方案không tồn tại');
        }
        
        
        if ($hemai->status != 0) {
            throw new \Exception('该方案已结束');
        }
        
        
        $now = time() * 1000;
        if ($hemai->endtime <= $now) {
            throw new \Exception('该方案已截止');
        }
        
        
        if ($hemai->buyhave < $num) {
            throw new \Exception('剩余份数不足');
        }
        
        
        if ($hemai->uid == $uid) {
            throw new \Exception('不能认购自己发起的方案');
        }
        
        
        $amount = $hemai->hemaipic * $num;
        
        
        $user = Db::table('caipiao_member')
            ->where('id', $uid)
            ->first();
        
        if (!$user) {
            throw new \Exception('Người dùng không tồn tại');
        }
        
        
        if ($user->balance < $amount) {
            throw new \Exception('Số dư không đủ');
        }
        
        
        Db::beginTransaction();
        
        try {
            
            Db::table('caipiao_member')
                ->where('id', $uid)
                ->decrement('balance', $amount);
            
            
            Db::table('caipiao_hemai')
                ->where('id', $id)
                ->decrement('buyhave', $num);
            
            
            Db::table('caipiao_hemai')
                ->where('id', $id)
                ->increment('buyed', $num);
            
            
            $joinId = Db::table('caipiao_hemai_join')->insertGetId([
                'hemai_id' => $id,
                'uid' => $uid,
                'username' => $username,
                'num' => $num,
                'amount' => $amount,
                'status' => 0,
                'jointime' => $now,
                'ip' => $ip,
            ]);
            
            
            $orderId = 'HM' . date('YmdHis') . str_pad($joinId, 6, '0', STR_PAD_LEFT);
            
            
            Db::table('caipiao_moneylog')->insert([
                'uid' => $uid,
                'username' => $username,
                'type' => 10, 
                'typename' => '合买认购',
                'trano' => $orderId,
                'amount' => -$amount,
                'before' => $user->balance,
                'after' => $user->balance - $amount,
                'remark' => "认购合买方案#{$id}，{$num}份",
                'addtime' => time(),
                'ip' => $ip,
            ]);
            
            
            $this->updateUserStats($hemai->uid);
            
            Db::commit();
            
            return [
                'orderId' => $orderId,
                'amount' => number_format($amount, 2, '.', ''),
                'balance' => number_format($user->balance - $amount, 2, '.', ''),
            ];
            
        } catch (\Exception $e) {
            Db::rollBack();
            throw $e;
        }
    }
    
    
    public function create($data)
    {
        
        $required = ['uid', 'username', 'lottery', 'expect', 'playtitle', 'totalAmount', 'perShare', 'totalShares', 'tzcode'];
        foreach ($required as $field) {
            if (!isset($data[$field]) || $data[$field] === '') {
                throw new \Exception("缺少必填字段: {$field}");
            }
        }
        
        
        $caipiao = Db::table('caipiao_caipiao')
            ->where('name', $data['lottery'])
            ->first();
        
        if (!$caipiao) {
            throw new \Exception('彩种không tồn tại');
        }
        
        
        $lotteryController = new \app\controller\api\LotteryController();
        $issueInfo = $this->calculateCurrentIssue($caipiao, time());
        $endtime = ($issueInfo['issueTime'] + $caipiao->expecttime * 60 - 600) * 1000; 
        
        
        Db::beginTransaction();
        
        try {
            
            $hemaiId = Db::table('caipiao_hemai')->insertGetId([
                'uid' => $data['uid'],
                'username' => $data['username'],
                'cpname' => $data['lottery'],
                'cptitle' => $caipiao->title,
                'typeid' => $caipiao->typeid,
                'expect' => $data['expect'],
                'playid' => $data['playid'] ?? '',
                'playtitle' => $data['playtitle'],
                'tzcode' => $data['tzcode'],
                'amount' => $data['totalAmount'],
                'hemaipic' => $data['perShare'],
                'buytotal' => $data['totalShares'],
                'buyhave' => $data['totalShares'],
                'buyed' => 0,
                'baodi' => $data['baodi'] ?? 0,
                'baodi_amount' => ($data['totalAmount'] * ($data['baodi'] ?? 0) / 100),
                'commission' => $data['commission'] ?? 0,
                'public_type' => $data['public'] ?? 1,
                'views' => 0,
                'content' => $data['content'] ?? '',
                'status' => 0,
                'isdraw' => 0,
                'endtime' => $endtime,
                'createtime' => time(),
                'ip' => $data['ip'] ?? '',
            ]);
            
            Db::commit();
            
            return $hemaiId;
            
        } catch (\Exception $e) {
            Db::rollBack();
            throw $e;
        }
    }
    
    
    public function cancel($id, $uid)
    {
        $hemai = Db::table('caipiao_hemai')
            ->where('id', $id)
            ->first();
        
        if (!$hemai) {
            throw new \Exception('方案không tồn tại');
        }
        
        if ($hemai->uid != $uid) {
            throw new \Exception('只能Hủy自己的方案');
        }
        
        if ($hemai->status != 0) {
            throw new \Exception('该方案已结束，无法Hủy');
        }
        
        if ($hemai->buyed > 0) {
            throw new \Exception('已有人认购，无法Hủy');
        }
        
        
        Db::table('caipiao_hemai')
            ->where('id', $id)
            ->update(['status' => 3]);
        
        return true;
    }
    
    
    public function getMyRecords($uid, $type, $page, $pageSize)
    {
        $page = max(1, intval($page));
        $pageSize = min(50, max(1, intval($pageSize)));
        
        if ($type == 'create') {
            
            $query = Db::table('caipiao_hemai')
                ->where('uid', $uid);
            
            $total = $query->count();
            
            $list = $query->orderBy('createtime', 'desc')
                ->offset(($page - 1) * $pageSize)
                ->limit($pageSize)
                ->get();
            
            $data = [];
            foreach ($list as $item) {
                $progress = 0;
                if ($item->buytotal > 0) {
                    $progress = round(($item->buytotal - $item->buyhave) / $item->buytotal * 100);
                }
                
                $data[] = [
                    'id' => $item->id,
                    'cptitle' => $item->cptitle,
                    'expect' => $item->expect,
                    'playtitle' => $item->playtitle,
                    'amount' => number_format($item->amount, 2, '.', ''),
                    'progress' => $progress,
                    'status' => $this->getStatusText($item->status),
                    'createtime' => date('Y-m-d H:i:s', $item->createtime),
                ];
            }
            
        } else {
            
            $query = Db::table('caipiao_hemai_join as j')
                ->leftJoin('caipiao_hemai as h', 'j.hemai_id', '=', 'h.id')
                ->where('j.uid', $uid)
                ->select('j.*', 'h.cptitle', 'h.expect', 'h.playtitle', 'h.status');
            
            $total = $query->count();
            
            $list = $query->orderBy('j.jointime', 'desc')
                ->offset(($page - 1) * $pageSize)
                ->limit($pageSize)
                ->get();
            
            $data = [];
            foreach ($list as $item) {
                $data[] = [
                    'id' => $item->hemai_id,
                    'cptitle' => $item->cptitle,
                    'expect' => $item->expect,
                    'playtitle' => $item->playtitle,
                    'num' => $item->num,
                    'amount' => number_format($item->amount, 2, '.', ''),
                    'winamount' => number_format($item->winamount, 2, '.', ''),
                    'status' => $this->getStatusText($item->status),
                    'jointime' => date('Y-m-d H:i:s', $item->jointime / 1000),
                ];
            }
        }
        
        return [
            'list' => $data,
            'total' => $total,
            'page' => $page,
            'pageSize' => $pageSize
        ];
    }
    
    
    private function getUserStats($uid)
    {
        $stats = Db::table('caipiao_member_stats')
            ->where('uid', $uid)
            ->first();
        
        if ($stats) {
            return [
                'level' => $stats->level,
                'follower_count' => $stats->follower_count,
                'last_5_win' => $stats->last_5_win,
            ];
        }
        
        
        return [
            'level' => 1,
            'follower_count' => 0,
            'last_5_win' => 0,
        ];
    }
    
    
    private function updateUserStats($uid)
    {
        
        $followerCount = Db::table('caipiao_hemai_join as j')
            ->leftJoin('caipiao_hemai as h', 'j.hemai_id', '=', 'h.id')
            ->where('h.uid', $uid)
            ->distinct()
            ->count('j.uid');
        
        
        $hemaiCount = Db::table('caipiao_hemai')
            ->where('uid', $uid)
            ->count();
        
        
        $winCount = Db::table('caipiao_hemai')
            ->where('uid', $uid)
            ->where('isdraw', 1)
            ->count();
        
        
        $last5Win = Db::table('caipiao_hemai')
            ->where('uid', $uid)
            ->where('status', 2) 
            ->orderBy('createtime', 'desc')
            ->limit(5)
            ->where('isdraw', 1)
            ->count();
        
        
        $totalAmount = Db::table('caipiao_hemai')
            ->where('uid', $uid)
            ->sum('amount');
        
        $totalWin = Db::table('caipiao_hemai')
            ->where('uid', $uid)
            ->sum('winamount');
        
        
        $level = 1;
        if ($hemaiCount >= 100) {
            $level = 5;
        } elseif ($hemaiCount >= 50) {
            $level = 4;
        } elseif ($hemaiCount >= 20) {
            $level = 3;
        } elseif ($hemaiCount >= 5) {
            $level = 2;
        }
        
        
        $exists = Db::table('caipiao_member_stats')
            ->where('uid', $uid)
            ->exists();
        
        if ($exists) {
            Db::table('caipiao_member_stats')
                ->where('uid', $uid)
                ->update([
                    'level' => $level,
                    'follower_count' => $followerCount,
                    'hemai_count' => $hemaiCount,
                    'hemai_win_count' => $winCount,
                    'last_5_win' => $last5Win,
                    'total_amount' => $totalAmount,
                    'total_win' => $totalWin,
                    'updatetime' => time(),
                ]);
        } else {
            Db::table('caipiao_member_stats')->insert([
                'uid' => $uid,
                'level' => $level,
                'follower_count' => $followerCount,
                'hemai_count' => $hemaiCount,
                'hemai_win_count' => $winCount,
                'last_5_win' => $last5Win,
                'total_amount' => $totalAmount,
                'total_win' => $totalWin,
                'updatetime' => time(),
            ]);
        }
    }
    
    
    private function calculateCurrentIssue($caipiao, $now)
    {
        $today = date('Y-m-d');
        
        
        $firsttime = strtotime($today . ' ' . $caipiao->firsttime);
        $endtime = strtotime($today . ' ' . $caipiao->endtime);
        
        
        if ($endtime < $firsttime) {
            $endtime += 86400;
        }
        
        
        $expecttime = $caipiao->expecttime;
        
        
        if ($now < $firsttime) {
            $currIssueNum = 1;
            $issueTime = $firsttime;
        } else if ($now > $endtime) {
            
            $currIssueNum = 1;
            $issueTime = $firsttime + 86400;
        } else {
            
            $passedMinutes = ($now - $firsttime) / 60;
            $currIssueNum = floor($passedMinutes / $expecttime) + 1;
            $issueTime = $firsttime + ($currIssueNum - 1) * $expecttime * 60;
            
            
            if ($now >= $issueTime + $expecttime * 60) {
                $currIssueNum++;
                $issueTime += $expecttime * 60;
            }
        }
        
        
        $datePrefix = date('Ymd', $issueTime);
        $currExpect = str_pad($currIssueNum, 4, '0', STR_PAD_LEFT);
        $currFullExpect = $datePrefix . '-' . $currExpect;
        
        
        $nextIssueTime = $issueTime + $expecttime * 60;
        $remainTime = $nextIssueTime - $now;
        
        
        $openRemainTime = max(0, $remainTime - 10);
        
        return [
            'currExpect' => $currExpect,
            'currFullExpect' => $currFullExpect,
            'remainTime' => max(0, $remainTime),
            'openRemainTime' => $openRemainTime,
            'issueTime' => $issueTime
        ];
    }
    
    
    private function getStatusText($status)
    {
        $map = [
            0 => 'Đang diễn ra',
            1 => '已截止',
            2 => '已Mở thưởng',
            3 => 'Đã hủy',
        ];
        
        return $map[$status] ?? '未知';
    }
    
    
    public function cancelJoin($hemaiId, $uid)
    {
        
        $join = Db::table('caipiao_hemai_join')
            ->where('hemai_id', $hemaiId)
            ->where('uid', $uid)
            ->where('status', 0) 
            ->first();
        
        if (!$join) {
            throw new \Exception('未找到参与lịch sửhoặc已经撤销');
        }
        
        
        $hemai = Db::table('caipiao_hemai')
            ->where('id', $hemaiId)
            ->first();
        
        if (!$hemai) {
            throw new \Exception('合买方案không tồn tại');
        }
        
        
        if ($hemai->status != 0) {
            throw new \Exception('方案已结束，无法撤资');
        }
        
        
        $now = time() * 1000;
        if ($hemai->endtime - $now < 10 * 60 * 1000) {
            throw new \Exception('距离截止Thời gian不足10分钟，无法撤资');
        }
        
        
        Db::beginTransaction();
        
        try {
            
            Db::table('caipiao_hemai_join')
                ->where('id', $join->id)
                ->update(['status' => 2]); 
            
            
            Db::table('caipiao_hemai')
                ->where('id', $hemaiId)
                ->increment('buyhave', $join->num);
            
            
            Db::table('caipiao_hemai')
                ->where('id', $hemaiId)
                ->decrement('buyed', $join->num);
            
            
            Db::table('caipiao_member')
                ->where('id', $uid)
                ->increment('balance', $join->amount);
            
            
            $user = Db::table('caipiao_member')
                ->where('id', $uid)
                ->first();
            
            
            Db::table('caipiao_moneylog')->insert([
                'uid' => $uid,
                'username' => $join->username,
                'type' => 9, 
                'typename' => '合买撤资',
                'trano' => 'TZ' . date('YmdHis') . $join->id,
                'amount' => $join->amount,
                'before' => $user->balance - $join->amount,
                'after' => $user->balance,
                'remark' => "撤销合买方案#{$hemaiId}认购，退还{$join->num}份",
                'addtime' => time(),
                'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
            ]);
            
            Db::commit();
            
            return true;
            
        } catch (\Exception $e) {
            Db::rollBack();
            throw $e;
        }
    }
    
    
    private function maskTzcode($tzcode, $publicType, $status)
    {
        
        if ($publicType == 1) {
            return $tzcode;
        }
        
        
        if ($publicType == 2) {
            return '***保密***';
        }
        
        
        if ($publicType == 3) {
            if ($status >= 1) { 
                return $tzcode;
            } else {
                return '***截止后公开***';
            }
        }
        
        return $tzcode;
    }
    
    
    private function maskUsername($username)
    {
        $len = mb_strlen($username);
        if ($len <= 2) {
            return $username;
        }
        
        $first = mb_substr($username, 0, 1);
        $last = mb_substr($username, -1, 1);
        $stars = str_repeat('*', min(3, $len - 2));
        
        return $first . $stars . $last;
    }
}
