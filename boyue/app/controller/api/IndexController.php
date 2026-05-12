<?php

namespace app\controller\api;

use support\Request;
use support\Db;

class IndexController
{
    public function index(Request $request)
    {
        try {
            $platform = $request->get('platform', 0);
            $now = time();
            
            $query = Db::table('yzz_banner')
                ->where('status', 1);
            
            if ($platform == 1 || $platform == 2) {
                $query->where(function($q) use ($platform) {
                    $q->where('platform', 0)
                      ->orWhere('platform', $platform);
                });
            }
            
            $query->where(function($q) use ($now) {
                $q->where(function($subQ) use ($now) {
                    $subQ->whereNull('start_time')
                         ->orWhere('start_time', '<=', $now);
                });
                $q->where(function($subQ) use ($now) {
                    $subQ->whereNull('end_time')
                         ->orWhere('end_time', '>=', $now);
                });
            });
            
            $banners = $query->orderBy('sort', 'desc')
                ->orderBy('id', 'desc')
                ->limit(10)
                ->get();
            
            $bannerList = [];
            foreach ($banners as $banner) {
                $bannerList[] = [
                    'id' => $banner->id,
                    'title' => $banner->title ?? '',
                    'image' => $banner->image ?? '',
                    'link' => $banner->link ?? ''
                ];
            }
            
            $notice = Db::table('caipiao_gonggao')
                ->select('id', 'title', 'oddtime')
                ->orderBy('id', 'desc')
                ->first();
            
            $hotLotteries = Db::table('caipiao_caipiao')
                ->where('isopen', 1)
                ->where('is_hot', 1)
                ->orderBy('allsort', 'asc')
                ->limit(8)
                ->get();
            
            $lotteriesWithResults = [];
            $hotNames = [];
            foreach ($hotLotteries as $lottery) {
                $lottery = (array)$lottery;
                $hotNames[] = $lottery['name'];
                $lotteriesWithResults[] = [
                    'id' => $lottery['id'],
                    'name' => $lottery['name'],
                    'title' => $lottery['title'],
                    'typeid' => $lottery['typeid'] ?? ''
                ];
            }
            
            if (count($lotteriesWithResults) < 4) {
                $remaining = 8 - count($lotteriesWithResults);
                $otherLotteries = Db::table('caipiao_caipiao')
                    ->where('isopen', 1)
                    ->whereNotIn('name', $hotNames)
                    ->orderBy('allsort', 'asc')
                    ->limit($remaining)
                    ->get();
                
                foreach ($otherLotteries as $lottery) {
                    $lottery = (array)$lottery;
                    $lotteriesWithResults[] = [
                        'id' => $lottery['id'],
                        'name' => $lottery['name'],
                        'title' => $lottery['title'],
                        'typeid' => $lottery['typeid'] ?? ''
                    ];
                }
            }
            
            return json([
                'code' => 0,
                'sign' => true,
                'message' => 'ok',
                'data' => [
                    'banners' => $bannerList,
                    'notice' => $notice ? [
                        'id' => $notice->id,
                        'title' => $notice->title,
                        'time' => $notice->oddtime
                    ] : null,
                    'lotteries' => $lotteriesWithResults,
                ]
            ]);
            
        } catch (\Exception $e) {
            \support\Log::error('首页数据获取失败: ' . $e->getMessage());
            return json([
                'code' => 500,
                'sign' => false,
                'message' => '获取数据失败',
                'data' => null
            ]);
        }
    }
    
    public function lotteryHall(Request $request)
    {
        try {
            $now = time();
            
            $lotteries = Db::table('caipiao_caipiao')
                ->where('isopen', 1)
                ->orderBy('allsort', 'asc')
                ->orderBy('id', 'desc')
                ->get();
            
            $lotteriesWithData = [];
            
            foreach ($lotteries as $lottery) {
                $lottery = (array)$lottery;
                
                $result = Db::table('caipiao_kaijiang')
                    ->where('name', $lottery['name'])
                    ->where('opentime', '<=', $now)
                    ->orderBy('id', 'desc')
                    ->first();
                
                if ($result) {
                    $balls = explode(',', $result->opencode);
                    $lottery['opencode'] = $balls;
                    $lottery['expect'] = $result->expect;
                    
                    $sum = array_sum($balls);
                    $lottery['sum'] = $sum;
                    $lottery['daxiao'] = $sum > 10 ? '大' : '小';
                    $lottery['danshuang'] = $sum % 2 == 0 ? '双' : '单';
                } else {
                    $lottery['opencode'] = [];
                    $lottery['expect'] = '';
                }
                
                $lottery['bet_count'] = Db::table('caipiao_touzhu')
                    ->where('cpname', $lottery['name'])
                    ->count();
                
                $lotteriesWithData[] = $lottery;
            }
            
            usort($lotteriesWithData, function($a, $b) {
                return $b['bet_count'] - $a['bet_count'];
            });
            
            return json([
                'code' => 0,
                'sign' => true,
                'message' => 'ok',
                'data' => [
                    'lotteries' => $lotteriesWithData,
                ]
            ]);
            
        } catch (\Exception $e) {
            \support\Log::error('彩种大厅数据获取失败: ' . $e->getMessage());
            return json([
                'code' => 500,
                'sign' => false,
                'message' => '获取数据失败',
                'data' => null
            ]);
        }
    }
    
    public function notices(Request $request)
    {
        try {
            $page = $request->get('page', 1);
            $limit = $request->get('limit', 20);
            
            $total = Db::table('caipiao_gonggao')->count();
            
            $notices = Db::table('caipiao_gonggao')
                ->select('id', 'title', 'content', 'oddtime')
                ->orderBy('id', 'desc')
                ->offset(($page - 1) * $limit)
                ->limit($limit)
                ->get();
            
            $data = [];
            foreach ($notices as $notice) {
                $data[] = [
                    'id' => $notice->id,
                    'title' => $notice->title,
                    'content' => $notice->content ?? '',
                    'time' => date('Y-m-d H:i:s', $notice->oddtime ?? time())
                ];
            }
            
            return json([
                'code' => 0,
                'sign' => true,
                'message' => 'ok',
                'count' => $total,
                'data' => $data
            ]);
            
        } catch (\Exception $e) {
            \support\Log::error('公告列表获取失败: ' . $e->getMessage());
            return json([
                'code' => 500,
                'sign' => false,
                'message' => '获取数据失败',
                'data' => null
            ]);
        }
    }
    
    public function noticeDetail(Request $request)
    {
        try {
            $id = $request->get('id');
            
            if (empty($id)) {
                return json([
                    'code' => 400,
                    'sign' => false,
                    'message' => '参数错误',
                    'data' => null
                ]);
            }
            
            $notice = Db::table('caipiao_gonggao')
                ->where('id', $id)
                ->first();
            
            if (!$notice) {
                return json([
                    'code' => 404,
                    'sign' => false,
                    'message' => '公告不存在',
                    'data' => null
                ]);
            }
            
            return json([
                'code' => 0,
                'sign' => true,
                'message' => 'ok',
                'data' => [
                    'id' => $notice->id,
                    'title' => $notice->title,
                    'content' => $notice->content ?? '',
                    'time' => date('Y-m-d H:i:s', $notice->oddtime ?? time())
                ]
            ]);
            
        } catch (\Exception $e) {
            \support\Log::error('公告详情获取失败: ' . $e->getMessage());
            return json([
                'code' => 500,
                'sign' => false,
                'message' => '获取数据失败',
                'data' => null
            ]);
        }
    }
    
    public function banners(Request $request)
    {
        try {
            $banners = Db::table('yzz_banner')
                ->where('status', 1)
                ->orderBy('sort', 'desc')
                ->orderBy('id', 'desc')
                ->get();
            
            $data = [];
            foreach ($banners as $banner) {
                $data[] = [
                    'id' => $banner->id,
                    'title' => $banner->title ?? '',
                    'image' => $banner->image ?? '',
                    'link' => $banner->link ?? '',
                    'sort' => $banner->sort ?? 0
                ];
            }
            
            return json([
                'code' => 0,
                'sign' => true,
                'message' => 'ok',
                'data' => $data
            ]);
            
        } catch (\Exception $e) {
            \support\Log::error('轮播图获取失败: ' . $e->getMessage());
            return json([
                'code' => 500,
                'sign' => false,
                'message' => '获取数据失败',
                'data' => null
            ]);
        }
    }
    
    public function config(Request $request)
    {
        try {
            $configs = Db::table('caipiao_setting')
                ->whereIn('name', [
                    'webtitle',
                    'keywords',
                    'description',
                    'kefuqq',
                    'kefuthree',
                    'defaulttjcode',
                    'sitedomain',
                    'promo_texts'
                ])
                ->get();
            
            $data = [];
            foreach ($configs as $config) {
                if ($config->name === 'promo_texts' && $config->value) {
                    $data[$config->name] = json_decode($config->value, true) ?: [];
                } else {
                    $data[$config->name] = $config->value;
                }
            }
            
            if (empty($data['sitedomain']) && !empty($data['webtitle'])) {
                if (preg_match('/(\d+\.[a-z]+)$/i', $data['webtitle'], $matches)) {
                    $data['sitedomain'] = $matches[1];
                }
            }
            
            return json([
                'code' => 0,
                'sign' => true,
                'message' => 'ok',
                'data' => $data
            ]);
            
        } catch (\Exception $e) {
            \support\Log::error('系统配置获取失败: ' . $e->getMessage());
            return json([
                'code' => 500,
                'sign' => false,
                'message' => '获取数据失败',
                'data' => null
            ]);
        }
    }
    
    public function realtimeDraws(Request $request)
    {
        try {
            $now = time();
            $limit = $request->get('limit', 10);
            
            $configRow = Db::table('caipiao_setting')
                ->where('name', 'home_lottery_config')
                ->first();
            
            $enabledLotteries = [];
            $maxDisplay = 10;
            
            if ($configRow && $configRow->value) {
                $config = json_decode($configRow->value, true);
                if ($config) {
                    $enabledLotteries = $config['enabled_lotteries'] ?? [];
                    $maxDisplay = $config['max_display'] ?? 10;
                }
            }
            
            if (!empty($enabledLotteries)) {
                $displayLimit = $maxDisplay;
                $lotteries = [];
                foreach ($enabledLotteries as $name) {
                    $lottery = Db::table('caipiao_caipiao')
                        ->where('name', $name)
                        ->where('isopen', 1)
                        ->first();
                    if ($lottery) {
                        $lotteries[] = $lottery;
                    }
                    if (count($lotteries) >= $displayLimit) {
                        break;
                    }
                }
            } else {
                $lotteries = Db::table('caipiao_caipiao')
                    ->where('isopen', 1)
                    ->orderBy('allsort', 'asc')
                    ->limit($limit)
                    ->get()
                    ->toArray();
            }
            
            $result = [];
            
            foreach ($lotteries as $lottery) {
                $lottery = (array)$lottery;
                $code = $lottery['name'];
                
                $lastResult = Db::table('caipiao_kaijiang')
                    ->where('name', $code)
                    ->where('opentime', '<=', $now)
                    ->orderBy('id', 'desc')
                    ->first();
                
                $currentExpect = Db::table('caipiao_kaijiang')
                    ->where('name', $code)
                    ->where('opentime', '>', $now)
                    ->orderBy('id', 'asc')
                    ->first();
                
                $countdown = 0;
                $status = 'done';
                $expectNo = '';
                
                if ($currentExpect) {
                    $expectNo = $currentExpect->expect;
                    $closeTime = $currentExpect->opentime - 10;
                    $countdown = max(0, $closeTime - $now);
                    
                    if ($countdown > 0) {
                        $status = 'betting';
                    } elseif ($now < $currentExpect->opentime) {
                        $status = 'closing';
                    } else {
                        $status = 'drawing';
                    }
                } elseif ($lastResult) {
                    $expectNo = $lastResult->expect;
                }
                
                $opencode = [];
                if ($lastResult && !empty($lastResult->opencode)) {
                    $opencode = array_map('trim', explode(',', $lastResult->opencode));
                }
                
                $typeid = $lottery['typeid'] ?? '';
                $type = $this->getLotteryType($typeid);
                
                $result[] = [
                    'code' => $code,
                    'name' => $lottery['title'] ?? $code,
                    'typeid' => $typeid,
                    'type' => $type,
                    'currentExpect' => $expectNo,
                    'countdown' => $countdown,
                    'status' => $status,
                    'ballCount' => $this->getBallCount($type),
                    'lastResult' => $lastResult ? [
                        'expect' => $lastResult->expect,
                        'opencode' => $opencode,
                        'opentime' => $lastResult->opentime
                    ] : null
                ];
            }
            
            return json([
                'code' => 0,
                'message' => 'ok',
                'data' => $result
            ]);
            
        } catch (\Exception $e) {
            \support\Log::error('实时开奖列表获取失败: ' . $e->getMessage());
            return json([
                'code' => 500,
                'message' => '获取数据失败',
                'data' => []
            ]);
        }
    }
    
    public function lotteryCurrentInfo(Request $request)
    {
        try {
            $code = $request->get('code', '');
            if (empty($code)) {
                return json(['code' => 400, 'message' => '缺少彩种代码', 'data' => null]);
            }
            
            $now = time();
            
            $lottery = Db::table('caipiao_caipiao')
                ->where('name', $code)
                ->first();
            
            if (!$lottery) {
                return json(['code' => 404, 'message' => '彩种不存在', 'data' => null]);
            }
            
            $currentExpect = Db::table('caipiao_kaijiang')
                ->where('name', $code)
                ->where('opentime', '>', $now)
                ->orderBy('id', 'asc')
                ->first();
            
            $lastResult = Db::table('caipiao_kaijiang')
                ->where('name', $code)
                ->where('opentime', '<=', $now)
                ->orderBy('id', 'desc')
                ->first();
            
            $countdown = 0;
            $status = 'done';
            $expectNo = '';
            $closeTime = 0;
            
            if ($currentExpect) {
                $expectNo = $currentExpect->expect;
                $closeTime = $currentExpect->opentime - 10;
                $countdown = max(0, $closeTime - $now);
                
                if ($countdown > 0) {
                    $status = 'betting';
                } elseif ($now < $currentExpect->opentime) {
                    $status = 'closing';
                } else {
                    $status = 'drawing';
                }
            }
            
            $lastOpencode = [];
            if ($lastResult && !empty($lastResult->opencode)) {
                $lastOpencode = array_map('trim', explode(',', $lastResult->opencode));
            }
            
            return json([
                'code' => 0,
                'message' => 'ok',
                'data' => [
                    'name' => $lottery->title,
                    'expect' => $expectNo,
                    'countdown' => $countdown,
                    'closeTime' => $closeTime,
                    'openTime' => $currentExpect ? $currentExpect->opentime : 0,
                    'status' => $status,
                    'lastExpect' => $lastResult ? $lastResult->expect : '',
                    'lastOpencode' => $lastOpencode,
                    'lastOpentime' => $lastResult ? $lastResult->opentime : 0
                ]
            ]);
            
        } catch (\Exception $e) {
            \support\Log::error('彩种当前期信息获取失败: ' . $e->getMessage());
            return json([
                'code' => 500,
                'message' => '获取数据失败',
                'data' => null
            ]);
        }
    }
    
    private function getLotteryType(string $typeid): string
    {
        $typeMap = [
            'k3' => 'k3',
            'ssc' => 'ssc',
            'pk10' => 'pk10',
            '11x5' => '11x5',
            'kl8' => 'kl8',
            'lhc' => 'lhc',
            'xy28' => 'xy28',
            'dpc' => 'ssc',
        ];
        
        return $typeMap[$typeid] ?? 'ssc';
    }
    
    private function getBallCount(string $type): int
    {
        $countMap = [
            'k3' => 3,
            'ssc' => 5,
            'pk10' => 10,
            '11x5' => 5,
            'kl8' => 20,
            'lhc' => 7,
            'xy28' => 3,
        ];
        
        return $countMap[$type] ?? 5;
    }
}
