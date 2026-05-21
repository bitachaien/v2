<?php

namespace app\controller\api;

use support\Request;
use support\Db;

class BetController
{
    
    public function records(Request $request)
    {
        $userId = $request->userId ?? 0;
        
        if (!$userId) {
            return json([
                'code' => 401,
                'message' => '未Đăng nhậphoặcĐăng nhập已过期',
                'data' => null
            ]);
        }
        
        try {
            $startTime = microtime(true);
            
            $page = max(1, (int)$request->get('page', 1));
            $pageSize = min(50, max(1, (int)$request->get('pageSize', 20))); 
            $status = $request->get('status', '');
            $startDate = $request->get('startDate', '');
            $endDate = $request->get('endDate', '');
            $lotteryCode = $request->get('lotteryCode', '');
            $timeRange = $request->get('timeRange', ''); 
            
            
            if ($timeRange && empty($startDate)) {
                switch ($timeRange) {
                    case 'today':
                        $startDate = date('Y-m-d');
                        break;
                    case 'week':
                        $startDate = date('Y-m-d', strtotime('-7 days'));
                        break;
                    case 'month':
                        $startDate = date('Y-m-d', strtotime('-30 days'));
                        break;
                    case 'all':
                        $startDate = date('Y-m-d', strtotime('-90 days')); 
                        break;
                }
            }
            
            
            $query = Db::table('caipiao_touzhu')
                ->select(['id', 'trano', 'expect', 'cptitle', 'cpname', 'playtitle', 'playid', 
                          'tzcode', 'amount', 'beishu', 'okamount', 'isdraw', 'opencode', 
                          'oddtime', 'opentime'])
                ->where('uid', $userId);
            
            
            if ($status !== '' && $status !== null) {
                $query->where('isdraw', $status);
            }
            
            
            if ($startDate) {
                $query->where('oddtime', '>=', strtotime($startDate . ' 00:00:00'));
            } else {
                
                $query->where('oddtime', '>=', strtotime('-30 days'));
            }
            if ($endDate) {
                $query->where('oddtime', '<=', strtotime($endDate . ' 23:59:59'));
            }
            
            
            if ($lotteryCode) {
                $query->where('cpname', $lotteryCode);
            }
            
            
            $t1 = microtime(true);
            
            $total = (clone $query)->count();
            
            
            $t2 = microtime(true);
            
            $list = $query->orderBy('oddtime', 'desc')
                ->offset(($page - 1) * $pageSize)
                ->limit($pageSize)
                ->get();
            
            
            $t3 = microtime(true);
            
            \support\Log::info('[BetRecords] Tra cứu耗时', [
                'userId' => $userId,
                'build' => round(($t1 - $startTime) * 1000) . 'ms',
                'count' => round(($t2 - $t1) * 1000) . 'ms',
                'select' => round(($t3 - $t2) * 1000) . 'ms',
            ]);
            
            $data = [];
            foreach ($list as $item) {
                
                $isdraw = $item->isdraw ?? 0;
                $winAmount = $item->okamount ?? 0;
                
                if ($isdraw == 0) {
                    $status = 'pending';  
                } else if ($winAmount > 0) {
                    $status = 'win';  
                } else {
                    $status = 'lose';  
                }
                
                
                $playCategory = $this->extractPlayCategory($item->playtitle ?? '', $item->playid ?? '');
                
                $data[] = [
                    'id' => $item->id,
                    'trano' => $item->trano ?? '',
                    'expect' => $item->expect ?? '',
                    'cptitle' => $item->cptitle ?? '',
                    'lotteryCode' => $item->cpname ?? '',
                    'playCategory' => $playCategory,           
                    'playType' => $item->playtitle ?? '',      
                    'betContent' => $item->tzcode ?? '',
                    'amount' => number_format($item->amount ?? 0, 2, '.', ''),
                    'multiple' => $item->beishu ?? 1,
                    'totalAmount' => number_format($item->amount ?? 0, 2, '.', ''),
                    'winAmount' => number_format($winAmount, 2, '.', ''),
                    'status' => $status,  
                    'isdraw' => (string)$isdraw,  
                    'drawNumber' => $item->opencode ?? '',
                    'oddtime' => date('Y-m-d H:i:s', $item->oddtime ?? time()),
                    'drawTime' => $item->opentime ? date('Y-m-d H:i:s', $item->opentime) : '',
                    'profit' => number_format($winAmount - ($item->amount ?? 0), 2, '.', '')
                ];
            }
            
            return json([
                'code' => 0,
                'message' => 'Lấy dữ liệu thành công',
                'data' => [
                    'total' => $total,
                    'page' => (int)$page,
                    'pageSize' => (int)$pageSize,
                    'list' => $data
                ]
            ]);
            
        } catch (\Exception $e) {
            \support\Log::error('LấyĐặt cượclịch sử thất bại: ' . $e->getMessage());
            return json([
                'code' => 500,
                'message' => 'Lấy dữ liệuThất bại：' . $e->getMessage(),
                'data' => null
            ]);
        }
    }
    
    
    public function detail(Request $request, $trano)
    {
        $userId = $request->userId ?? 0;
        
        if (!$userId) {
            return json([
                'code' => 401,
                'message' => '未Đăng nhậphoặcĐăng nhập已过期',
                'data' => null
            ]);
        }
        
        try {
            $order = Db::table('caipiao_touzhu')
                ->where('uid', $userId)
                ->where('trano', $trano)
                ->first();
            
            if (!$order) {
                return json([
                    'code' => 404,
                    'message' => '订单không tồn tại',
                    'data' => null
                ]);
            }
            
            
            $betContentFormatted = $this->formatBetContent($order->tzcode ?? '', $order->playtitle ?? '');
            
            
            $profit = ($order->okamount ?? 0) - ($order->amount ?? 0);
            
            
            $isdraw = $order->isdraw ?? 0;
            $winAmount = $order->okamount ?? 0;
            
            if ($isdraw == 0) {
                $status = 'pending';
                $statusText = '待Mở thưởng';
            } else if ($isdraw == -2) {
                $status = 'cancelled';
                $statusText = '已撤单';
            } else if ($winAmount > 0) {
                $status = 'win';
                $statusText = '已Trúng thưởng';
            } else {
                $status = 'lose';
                $statusText = '未Trúng thưởng';
            }
            
            return json([
                'code' => 0,
                'message' => 'Lấy dữ liệu thành công',
                'data' => [
                    'trano' => $order->trano ?? '',
                    'expect' => $order->expect ?? '',
                    'cptitle' => $order->cptitle ?? '',
                    'lotteryCode' => $order->cpname ?? '',
                    'playType' => $order->playtitle ?? '',
                    'betContent' => $order->tzcode ?? '',
                    'betContentFormatted' => $betContentFormatted,
                    'amount' => number_format($order->amount ?? 0, 2, '.', ''),
                    'multiple' => $order->beishu ?? 1,
                    'totalAmount' => number_format($order->amount ?? 0, 2, '.', ''),
                    'singleAmount' => number_format(($order->amount ?? 0) / ($order->itemcount ?? 1), 2, '.', ''),
                    'betCount' => $order->itemcount ?? 0,
                    'winAmount' => number_format($order->okamount ?? 0, 2, '.', ''),
                    'status' => $status,  
                    'statusText' => $statusText,  
                    'isdraw' => (string)($order->isdraw ?? 0),
                    'drawNumber' => $order->opencode ?? '',
                    'oddtime' => date('Y-m-d H:i:s', $order->oddtime ?? time()),
                    'drawTime' => $order->opentime ? date('Y-m-d H:i:s', $order->opentime) : '',
                    'orderNo' => $order->trano ?? '',
                    'ip' => $order->ip ?? '',
                    'source' => 'H5',
                    'profit' => number_format($profit, 2, '.', '')
                ]
            ]);
            
        } catch (\Exception $e) {
            \support\Log::error('LấyĐặt cượcChi tiếtThất bại: ' . $e->getMessage());
            return json([
                'code' => 500,
                'message' => 'Lấy dữ liệuThất bại：' . $e->getMessage(),
                'data' => null
            ]);
        }
    }
    
    
    private function extractPlayCategory(string $playTitle, string $playId): string
    {
        if (empty($playTitle)) {
            return '';
        }
        
        
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
        
        
        $patterns = [
            '/^(和值)/' => '和值',
            '/^(三同号)/' => '三同号',
            '/^(二同号)/' => '二同号',
            '/^(二不同)/' => '二不同号',
            '/^(三不同)/' => '三不同号',
            '/^(三连号)/' => '三连号',
            '/^(单双)/' => '单双',
            '/^(大小)/' => '大小',
        ];
        
        foreach ($patterns as $pattern => $category) {
            if (preg_match($pattern, $playTitle)) {
                return $category;
            }
        }
        
        
        return $playTitle;
    }
    
    
    private function formatBetContent($betContent, $playType)
    {
        if (empty($betContent)) {
            return '';
        }
        
        $parts = explode('|', $betContent);
        if (count($parts) > 1) {
            $formatted = [];
            $positions = ['百位', '十位', '个位', '万位', '千位'];
            foreach ($parts as $index => $part) {
                $position = $positions[$index] ?? "位置" . ($index + 1);
                $formatted[] = $position . ': ' . $part;
            }
            return implode("\n", $formatted);
        }
        
        return $betContent;
    }
}
