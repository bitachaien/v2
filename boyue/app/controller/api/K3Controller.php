<?php

namespace app\controller\api;

use support\Request;
use support\Response;
use support\Db;
use support\Log;
use app\service\WebSocketPusher;
use app\service\K3Service;
use app\service\BetService;
use app\repository\LotteryRepository;

class K3Controller
{
    
    public function list(Request $request): Response
    {
        try {
            $type = $request->get('type', '');
            
            if ($type !== 'k3') {
                return json(['code' => 1001, 'message' => 'Tham số không hợp lệ']);
            }
            
            
            $list = LotteryRepository::getLotteryList('k3');
            
            $data = array_map(fn($item) => [
                'code' => $item->name,
                'name' => $item->title,
                'status' => $item->isopen
            ], $list);
            
            return json(['code' => 0, 'data' => $data]);
            
        } catch (\Exception $e) {
            Log::error('Lấy彩种列表Thất bại: ' . $e->getMessage());
            return json(['code' => 9999, 'message' => 'Lỗi hệ thống']);
        }
    }
    
    
    public function current(Request $request): Response
    {
        try {
            $code = $request->get('code', '');
            
            if (empty($code)) {
                return json(['code' => 1001, 'message' => 'Tham số không hợp lệ']);
            }
            
            
            $data = K3Service::getCurrentInfo($code);
            
            if (!$data) {
                return json(['code' => 1001, 'message' => '彩种không tồn tại']);
            }
            
            return json(['code' => 0, 'data' => $data]);
            
        } catch (\Exception $e) {
            Log::error('Lấy当前期号Thất bại: ' . $e->getMessage());
            return json(['code' => 9999, 'message' => 'Lỗi hệ thống']);
        }
    }
    
    
    public function history(Request $request): Response
    {
        try {
            $code = $request->get('code', '');
            $limit = max(1, min(100, (int)$request->get('limit', 30)));
            
            if (empty($code)) {
                return json(['code' => 1001, 'message' => 'Tham số không hợp lệ']);
            }
            
            
            $data = K3Service::getHistory($code, $limit);
            
            return json(['code' => 0, 'data' => $data]);
            
        } catch (\Exception $e) {
            Log::error('Lấy历史Mở thưởngThất bại: ' . $e->getMessage());
            return json(['code' => 9999, 'message' => 'Lỗi hệ thống']);
        }
    }
    
    
    public function odds(Request $request): Response
    {
        try {
            $code = $request->get('code', '');
            
            if (empty($code)) {
                return json(['code' => 1001, 'message' => 'Tham số không hợp lệ']);
            }
            
            
            $code = $this->normalizeK3Code($code);
            
            
            $caipiao = Db::table('caipiao_caipiao')
                ->where('name', $code)
                ->where('typeid', 'k3')
                ->first();
            
            if (!$caipiao) {
                return json(['code' => 1001, 'message' => '彩种không tồn tại']);
            }
            
            
            $plays = Db::table('caipiao_wanfa')
                ->where('typeid', 'k3')
                ->where('isopen', 1)
                ->orderBy('id', 'asc')
                ->get();
            
            
            $groups = $this->buildK3PlayGroups($plays);
            
            return json([
                'code' => 0,
                'data' => [
                    'updateTime' => date('Y-m-d H:i:s'),
                    'groups' => $groups
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Lấy玩法赔率Thất bại: ' . $e->getMessage());
            return json(['code' => 9999, 'message' => 'Lỗi hệ thống']);
        }
    }
    
    
    public function bet(Request $request): Response
    {
        try {
            
            $userId = $request->userId ?? 0;
            if (!$userId) {
                return json(['code' => 2001, 'message' => '未Đăng nhập']);
            }
            
            
            $lotteryCode = $request->post('lotteryCode', '');
            $issue = $request->post('issue', '');
            $bets = $request->post('bets', []);
            $totalAmount = (float)$request->post('totalAmount', 0);
            $source = $request->post('source', 'h5');
            
            
            $paramCheck = BetService::validateParams([
                'lotteryCode' => $lotteryCode,
                'issue' => $issue,
                'bets' => $bets,
                'totalAmount' => $totalAmount
            ]);
            if (!$paramCheck['valid']) {
                return json(['code' => 1001, 'message' => $paramCheck['error']]);
            }
            
            
            $userCheck = BetService::validateUser($userId, $totalAmount);
            if (!$userCheck['valid']) {
                return json(['code' => $userCheck['code'], 'message' => $userCheck['error']]);
            }
            
            
            $timingCheck = BetService::validateTiming($lotteryCode, $issue);
            if (!$timingCheck['valid']) {
                return json([
                    'code' => $timingCheck['code'], 
                    'message' => $timingCheck['error'],
                    'data' => ['currentIssue' => $timingCheck['currentIssue'] ?? null]
                ]);
            }
            
            
            $result = BetService::placeBet([
                'userId' => $userId,
                'user' => $userCheck['user'],
                'lotteryCode' => $timingCheck['lotteryCode'],
                'caipiao' => $timingCheck['caipiao'],
                'issue' => $issue,
                'bets' => $bets,
                'totalAmount' => $totalAmount,
                'source' => $source,
                'ip' => $request->getRealIp()
            ]);
            
            if (!$result['success']) {
                return json(['code' => $result['code'] ?? 9999, 'message' => $result['error']]);
            }
            
            
            $this->pushBetNotification($userId, $result['data'], $bets, $timingCheck['caipiao']);
            
            return json(['code' => 0, 'message' => 'Đặt cượcThành công', 'data' => $result['data']]);
            
        } catch (\Exception $e) {
            Log::error('Đặt cượcThất bại: ' . $e->getMessage());
            return json(['code' => 9999, 'message' => 'Lỗi hệ thống']);
        }
    }
    
    
    private function pushBetNotification(int $userId, array $data, array $bets, $caipiao): void
    {
        try {
            $betContents = array_map(fn($b) => $b['content'] ?? '', $bets);
            $firstPlayId = K3Service::convertPlayId($bets[0]['playId'] ?? '', $bets[0]['content'] ?? '');
            $firstPlay = LotteryRepository::getPlay($firstPlayId);
            
            WebSocketPusher::pushBetCreated($userId, [
                'orderNo' => $data['orderId'],
                'gameName' => $caipiao->title,
                'issue' => $data['issue'],
                'playCategory' => $this->extractPlayCategory($firstPlayId),
                'playType' => $firstPlay->title ?? '快三',
                'betContent' => implode(',', $betContents),
                'betAmount' => $data['totalAmount'],
                'betTime' => date('Y-m-d H:i:s'),
                'balanceAfter' => $data['balance'],
            ]);
            
            WebSocketPusher::pushBalanceUpdate($userId, [
                'balance' => $data['balance'],
                'change' => -$data['totalAmount'],
                'reason' => 'bet_place',
                'orderId' => $data['orderId'],
            ]);
        } catch (\Exception $e) {
            Log::warning('[WebSocket] 推送Thất bại: ' . $e->getMessage());
        }
    }
    
    
    public function hemai(Request $request): Response
    {
        try {
            
            $userId = $request->userId ?? 0;
            if (!$userId) {
                return json(['code' => 2001, 'message' => '未Đăng nhập']);
            }
            
            
            $lotteryCode = $request->post('lotteryCode', '');
            $issue = $request->post('issue', '');
            $bets = $request->post('bets', []);
            $totalAmount = (float)$request->post('totalAmount', 0);
            $hemaiConfig = $request->post('hemaiConfig', []);
            
            
            $lotteryCode = $this->normalizeK3Code($lotteryCode);
            
            
            if (empty($hemaiConfig)) {
                return json(['code' => 1001, 'message' => '合买配置Lỗi']);
            }
            
            $totalShares = (int)($hemaiConfig['totalShares'] ?? 0);
            $selfShares = (int)($hemaiConfig['selfShares'] ?? 0);
            $commission = (int)($hemaiConfig['commission'] ?? 0);
            
            if ($totalShares <= 0 || $selfShares <= 0 || $selfShares > $totalShares) {
                return json(['code' => 1001, 'message' => '份数配置Lỗi']);
            }
            
            
            $sharePrice = $totalAmount / $totalShares;
            $selfAmount = $sharePrice * $selfShares;
            
            
            $user = Db::table('caipiao_member')->where('id', $userId)->first();
            $userBalance = (float)($user->balance ?? 0);
            if (!$user || $userBalance < $selfAmount) {
                return json(['code' => 1003, 'message' => 'Số dư không đủ']);
            }
            
            
            $caipiao = Db::table('caipiao_caipiao')
                ->where('name', $lotteryCode)
                ->where('typeid', 'k3')
                ->first();
            
            if (!$caipiao) {
                return json(['code' => 1001, 'message' => '彩种không tồn tại']);
            }
            
            
            $now = time();
            $issueInfo = $this->calculateCurrentIssue($caipiao, $now);
            $endtime = ($issueInfo['issueTime'] + $caipiao->expecttime * 60 - 600) * 1000; 
            
            
            $tzcode = '';
            if (!empty($bets)) {
                $tzcodeArr = [];
                foreach ($bets as $bet) {
                    $playId = $bet['playId'] ?? '';
                    $content = $bet['content'] ?? '';
                    $amount = $bet['amount'] ?? 0;
                    $tzcodeArr[] = "{$playId}:{$content}:{$amount}";
                }
                $tzcode = implode('|', $tzcodeArr);
            }
            
            Db::beginTransaction();
            
            try {
                
                $hemaiId = Db::table('caipiao_hemai')->insertGetId([
                    'uid' => $userId,
                    'username' => $user->username,
                    'cpname' => $lotteryCode,
                    'cptitle' => $caipiao->title,
                    'typeid' => $caipiao->typeid ?? 'k3',
                    'expect' => $issue,
                    'playid' => $bets[0]['playId'] ?? '',
                    'playtitle' => '快三合买',
                    'tzcode' => $tzcode,
                    'amount' => $totalAmount,
                    'hemaipic' => $sharePrice,
                    'buytotal' => $totalShares,
                    'buyhave' => $totalShares - $selfShares, 
                    'buyed' => $selfShares, 
                    'baodi' => $commission, 
                    'baodi_amount' => $totalAmount * $commission / 100,
                    'content' => $hemaiConfig['description'] ?? '',
                    'status' => 0, 
                    'isdraw' => 0,
                    'endtime' => $endtime,
                    'createtime' => $now,
                    'ip' => $request->getRealIp() ?? ''
                ]);
                
                
                if ($selfShares > 0) {
                    Db::table('caipiao_hemai_join')->insert([
                        'hemai_id' => $hemaiId,
                        'uid' => $userId,
                        'username' => $user->username,
                        'num' => $selfShares,
                        'amount' => $selfAmount,
                        'winamount' => 0,
                        'status' => 0,
                        'jointime' => $now * 1000,
                        'ip' => $request->getRealIp() ?? ''
                    ]);
                }
                
                
                Db::table('caipiao_member')
                    ->where('id', $userId)
                    ->decrement('balance', $selfAmount);
                
                
                Db::table('caipiao_moneylog')->insert([
                    'uid' => $userId,
                    'username' => $user->username,
                    'type' => 10,
                    'typename' => '合买发起',
                    'trano' => 'HM' . date('YmdHis') . str_pad($hemaiId, 6, '0', STR_PAD_LEFT),
                    'amount' => -$selfAmount,
                    'before' => $userBalance,
                    'after' => $userBalance - $selfAmount,
                    'remark' => "发起合买-{$caipiao->title}-{$issue}，自购{$selfShares}份",
                    'addtime' => $now,
                    'ip' => $request->getRealIp() ?? ''
                ]);
                
                Db::commit();
                
                return json([
                    'code' => 0,
                    'message' => '合买发起Thành công',
                    'data' => [
                        'hemaiId' => $hemaiId,
                        'orderId' => 'HM' . date('YmdHis') . str_pad($hemaiId, 6, '0', STR_PAD_LEFT),
                        'sharePrice' => $sharePrice,
                        'remainShares' => $totalShares - $selfShares,
                        'expireTime' => date('Y-m-d H:i:s', $endtime / 1000)
                    ]
                ]);
                
            } catch (\Exception $e) {
                Db::rollBack();
                throw $e;
            }
            
        } catch (\Exception $e) {
            Log::error('合买发起Thất bại: ' . $e->getMessage());
            return json(['code' => 9999, 'message' => $e->getMessage()]);
        }
    }
    
    
    private function calculateCurrentIssue($caipiao, $now)
    {
        $today = date('Y-m-d', $now);
        $firsttime = strtotime($today . ' ' . ($caipiao->firsttime ?: '00:00:00'));
        
        $expecttime = $caipiao->expecttime;
        
        
        if (empty($expecttime) || $expecttime <= 0) {
            Log::error('彩种配置Lỗi: expecttime 无效', [
                'name' => $caipiao->name ?? '',
                'expecttime' => $expecttime
            ]);
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
        $currFullExpect = $datePrefix . $currExpect;
        
        $nextIssueTime = $issueTime + $expecttime * 60;
        $remainTime = $nextIssueTime - $now;
        
        return [
            'currExpect' => $currExpect,
            'currFullExpect' => $currFullExpect,
            'remainTime' => max(0, $remainTime),
            'issueTime' => $issueTime
        ];
    }
    
    
    private function buildK3PlayGroups($plays)
    {
        
        $groups = [
            [
                'groupId' => 'hz',
                'title' => '和值',
                'columns' => 4,
                'plays' => []
            ],
            [
                'groupId' => 'sth',
                'title' => '三同号',
                'columns' => 4,
                'plays' => []
            ],
            [
                'groupId' => 'sbth',
                'title' => '三不同号',
                'columns' => 6,
                'selectCount' => 3,
                'selectType' => 'multi',
                'plays' => []
            ],
            [
                'groupId' => 'slh',
                'title' => '三连号',
                'columns' => 2,
                'plays' => []
            ],
            [
                'groupId' => 'eth',
                'title' => '二同号',
                'columns' => 2,
                'plays' => []
            ],
            [
                'groupId' => 'ebth',
                'title' => '二不同号',
                'columns' => 6,
                'selectCount' => 2,
                'selectType' => 'multi',
                'plays' => []
            ],
            [
                'groupId' => 'sjdx',
                'title' => '三军/大小',
                'columns' => 4,
                'plays' => []
            ],
            [
                'groupId' => 'wsqs',
                'title' => '围骰/全骰',
                'columns' => 3,
                'plays' => []
            ],
            [
                'groupId' => 'cp',
                'title' => '长牌',
                'columns' => 5,
                'plays' => []
            ],
            [
                'groupId' => 'dp',
                'title' => '短牌',
                'columns' => 3,
                'plays' => []
            ],
            [
                'groupId' => 'ds',
                'title' => '点数',
                'columns' => 5,
                'plays' => []
            ],
            [
                'groupId' => 'hhm',
                'title' => '红黑码',
                'columns' => 6,
                'plays' => []
            ]
        ];
        
        
        foreach ($plays as $play) {
            $playData = [
                'playId' => $play->playid,
                'name' => $play->title,
                'odds' => $play->rate ?? '1.98',
                'enabled' => $play->isopen == 1
            ];
            
            
            $playid = $play->playid;
            
            
            if (preg_match('/^k3hz/', $playid)) {
                $playData['display'] = 'text';
                $groups[0]['plays'][] = $playData;
            }
            
            else if (preg_match('/^k3sth/', $playid)) {
                if ($playid == 'k3sthtx') {
                    $playData['display'] = 'text';
                } else if ($playid == 'k3sthdx') {
                    $playData['display'] = 'text';
                } else {
                    $playData['display'] = 'triple';
                }
                $groups[1]['plays'][] = $playData;
            }
            
            else if ($playid === 'k3sbthbz') {
                $playData['display'] = 'number-picker';
                $playData['options'] = [
                    ['value' => 1, 'label' => '1'],
                    ['value' => 2, 'label' => '2'],
                    ['value' => 3, 'label' => '3'],
                    ['value' => 4, 'label' => '4'],
                    ['value' => 5, 'label' => '5'],
                    ['value' => 6, 'label' => '6'],
                ];
                $playData['selectCount'] = 3;
                $groups[2]['plays'][] = $playData;
            }
            
            else if (preg_match('/^k3slh/', $playid)) {
                $playData['display'] = 'text';
                $groups[3]['plays'][] = $playData;
            }
            
            else if ($playid === 'k3ethfx') {
                $playData['display'] = 'text';
                $playData['name'] = '二同号通选';
                $groups[4]['plays'][] = $playData;
            }
            else if ($playid === 'k3ethdx') {
                $playData['display'] = 'dual-picker';
                $playData['pairOptions'] = [
                    ['value' => 1, 'label' => '11*'],
                    ['value' => 2, 'label' => '22*'],
                    ['value' => 3, 'label' => '33*'],
                    ['value' => 4, 'label' => '44*'],
                    ['value' => 5, 'label' => '55*'],
                    ['value' => 6, 'label' => '66*'],
                ];
                $playData['diffOptions'] = [
                    ['value' => 1, 'label' => '1'],
                    ['value' => 2, 'label' => '2'],
                    ['value' => 3, 'label' => '3'],
                    ['value' => 4, 'label' => '4'],
                    ['value' => 5, 'label' => '5'],
                    ['value' => 6, 'label' => '6'],
                ];
                $groups[4]['plays'][] = $playData;
            }
            
            else if ($playid === 'k3ebthbz') {
                $playData['display'] = 'number-picker';
                $playData['options'] = [
                    ['value' => 1, 'label' => '1'],
                    ['value' => 2, 'label' => '2'],
                    ['value' => 3, 'label' => '3'],
                    ['value' => 4, 'label' => '4'],
                    ['value' => 5, 'label' => '5'],
                    ['value' => 6, 'label' => '6'],
                ];
                $playData['selectCount'] = 2;
                $groups[5]['plays'][] = $playData;
            }
            
            else if (preg_match('/^sjdx[1-6]$/', $playid)) {
                $playData['display'] = 'dice';
                $groups[6]['plays'][] = $playData;
            } else if (in_array($playid, ['sjdxd', 'sjdxs'])) {
                $playData['display'] = 'text';
                $groups[6]['plays'][] = $playData;
            }
            
            else if (preg_match('/^wsqs/', $playid)) {
                $playData['display'] = strpos($playid, 'qqq') !== false ? 'text' : 'triple';
                $groups[7]['plays'][] = $playData;
            }
            
            else if (preg_match('/^cp\d{2}$/', $playid)) {
                $playData['display'] = 'pair';
                $groups[8]['plays'][] = $playData;
            }
            
            else if (preg_match('/^dp\d{2}$/', $playid)) {
                $playData['display'] = 'double';
                $groups[9]['plays'][] = $playData;
            }
            
            else if (preg_match('/^ds\d+$/', $playid)) {
                $playData['display'] = 'text';
                $groups[10]['plays'][] = $playData;
            }
            
            
            else if (preg_match('/^hhm[1-6]$/', $playid)) {
                
                $num = substr($playid, 3, 1);
                $playData['name'] = $num;
                $playData['display'] = 'text';
                $groups[11]['plays'][] = $playData;
            }
            else if (preg_match('/^hhm/', $playid)) {
                $playData['display'] = 'text';
                $groups[11]['plays'][] = $playData;
            }
        }
        
        
        return array_values(array_filter($groups, function($group) {
            return !empty($group['plays']);
        }));
    }
    
    
    private function extractPlayCategory(string $playId): string
    {
        $categoryMap = [
            'k3hz' => '和值',
            'k3sth' => '三同号',
            'k3eth' => '二同号',
            'k3ebth' => '二不同号',
            'k3sbth' => '三不同号',
            'k3sl' => '三连号',
            'k3ds' => '单双',
            'k3dx' => '大小',
        ];
        
        foreach ($categoryMap as $prefix => $category) {
            if (str_starts_with($playId, $prefix)) {
                return $category;
            }
        }
        
        return '快三';
    }
    
    
    private function convertPlayId(string $playId, string $content): string
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
    
    
    private function normalizeK3Code(string $code): string
    {
        
        if (preg_match('/^(\d+)(f)(k3)$/i', $code, $matches)) {
            return 'f' . $matches[1] . 'k3'; 
        }
        
        
        if (preg_match('/^10f(k3)$/i', $code)) {
            return 'sfks';
        }
        
        
        return strtolower($code);
    }
}
