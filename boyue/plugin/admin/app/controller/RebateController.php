<?php

namespace plugin\admin\app\controller;

use support\Request;
use support\Db;

/**
 * 反水管理控制器
 */
class RebateController
{
    /**
     * 获取反水记录列表
     * GET /app/admin/api/rebate/list
     */
    public function list(Request $request)
    {
        try {
            $page = $request->get('page', 1);
            $pageSize = $request->get('pageSize', 20);
            $username = $request->get('username', '');
            $status = $request->get('status', '');
            $startDate = $request->get('startDate', '');
            $endDate = $request->get('endDate', '');
            $type = $request->get('type', ''); // daily: 每日反水, vendor: 游戏商反水
            
            $query = Db::table('caipiao_fanshui');
            
            // 搜索条件
            if (!empty($username)) {
                $query->where('username', 'like', "%{$username}%");
            }
            
            if ($status !== '') {
                $query->where('shenhe', intval($status));
            }
            
            if (!empty($startDate)) {
                $query->where('oddtime', '>=', strtotime($startDate . ' 00:00:00'));
            }
            
            if (!empty($endDate)) {
                $query->where('oddtime', '<=', strtotime($endDate . ' 23:59:59'));
            }
            
            // 类型筛选
            if ($type === 'daily') {
                $query->where(function($q) {
                    $q->whereNull('vendor_code')
                      ->orWhere('vendor_code', '');
                });
            } elseif ($type === 'vendor') {
                $query->whereNotNull('vendor_code')
                      ->where('vendor_code', '!=', '');
            }
            
            // 统计总数
            $total = $query->count();
            
            // 分页查询
            $list = $query->orderBy('id', 'desc')
                ->offset(($page - 1) * $pageSize)
                ->limit($pageSize)
                ->get();
            
            $items = [];
            foreach ($list as $item) {
                $items[] = [
                    'id' => $item->id,
                    'trano' => $item->trano,
                    'uid' => $item->uid,
                    'username' => $item->username,
                    'groupname' => $item->groupname,
                    'bili' => $item->bili,
                    'touzhuedu' => floatval($item->touzhuedu),
                    'amount' => floatval($item->amount),
                    'oddtime' => date('Y-m-d H:i:s', $item->oddtime),
                    'shenhe' => $item->shenhe,
                    'status' => $item->status,
                    'vendor_code' => $item->vendor_code ?? '',
                    'category_code' => $item->category_code ?? '',
                    'type' => empty($item->vendor_code) ? 'daily' : 'vendor',
                    'type_label' => empty($item->vendor_code) ? '每日反水' : '游戏商反水'
                ];
            }
            
            return json([
                'code' => 0,
                'message' => '获取成功',
                'data' => [
                    'total' => $total,
                    'list' => $items
                ]
            ]);
            
        } catch (\Exception $e) {
            return json([
                'code' => 500,
                'message' => '获取失败：' . $e->getMessage(),
                'data' => null
            ]);
        }
    }
    
    /**
     * 审核反水申请
     * POST /app/admin/api/rebate/audit
     */
    public function audit(Request $request)
    {
        try {
            $id = $request->post('id', 0);
            $action = $request->post('action', ''); // pass: 通过, reject: 拒绝
            
            if (!$id || !in_array($action, ['pass', 'reject'])) {
                return json([
                    'code' => 400,
                    'message' => '参数错误',
                    'data' => null
                ]);
            }
            
            $record = Db::table('caipiao_fanshui')->where('id', $id)->first();
            
            if (!$record) {
                return json([
                    'code' => 404,
                    'message' => '记录不存在',
                    'data' => null
                ]);
            }
            
            if ($record->shenhe != 0) {
                return json([
                    'code' => 400,
                    'message' => '该记录已审核',
                    'data' => null
                ]);
            }
            
            if ($action === 'pass') {
                // 通过审核
                Db::table('caipiao_fanshui')
                    ->where('id', $id)
                    ->update(['shenhe' => 1]);
                
                // 增加用户余额
                $user = Db::table('caipiao_member')->where('id', $record->uid)->first();
                if ($user) {
                    Db::table('caipiao_member')
                        ->where('id', $record->uid)
                        ->increment('balance', $record->amount);
                    
                    // 记录资金明细
                    Db::table('caipiao_fuddetail')->insert([
                        'uid' => $record->uid,
                        'username' => $record->username,
                        'type' => 'fanshui',
                        'typename' => '反水审核通过',
                        'trano' => $record->trano,
                        'amount' => $record->amount,
                        'before' => $user->balance,
                        'after' => $user->balance + $record->amount,
                        'oddtime' => time(),
                        'remark' => '管理员审核通过'
                    ]);
                }
            } else {
                // 拒绝
                Db::table('caipiao_fanshui')
                    ->where('id', $id)
                    ->update(['shenhe' => 2]);
            }
            
            return json([
                'code' => 0,
                'message' => $action === 'pass' ? '审核通过' : '已拒绝',
                'data' => null
            ]);
            
        } catch (\Exception $e) {
            return json([
                'code' => 500,
                'message' => '操作失败：' . $e->getMessage(),
                'data' => null
            ]);
        }
    }
    
    /**
     * 批量审核
     * POST /app/admin/api/rebate/batch-audit
     */
    public function batchAudit(Request $request)
    {
        try {
            $ids = $request->post('ids', []);
            $action = $request->post('action', '');
            
            if (empty($ids) || !in_array($action, ['pass', 'reject'])) {
                return json([
                    'code' => 400,
                    'message' => '参数错误',
                    'data' => null
                ]);
            }
            
            $shenhe = $action === 'pass' ? 1 : 2;
            $count = 0;
            
            foreach ($ids as $id) {
                $record = Db::table('caipiao_fanshui')->where('id', $id)->first();
                if (!$record || $record->shenhe != 0) continue;
                
                Db::table('caipiao_fanshui')
                    ->where('id', $id)
                    ->update(['shenhe' => $shenhe]);
                
                if ($action === 'pass') {
                    $user = Db::table('caipiao_member')->where('id', $record->uid)->first();
                    if ($user) {
                        Db::table('caipiao_member')
                            ->where('id', $record->uid)
                            ->increment('balance', $record->amount);
                        
                        Db::table('caipiao_fuddetail')->insert([
                            'uid' => $record->uid,
                            'username' => $record->username,
                            'type' => 'fanshui',
                            'typename' => '反水审核通过',
                            'trano' => $record->trano,
                            'amount' => $record->amount,
                            'before' => $user->balance,
                            'after' => $user->balance + $record->amount,
                            'oddtime' => time(),
                            'remark' => '批量审核通过'
                        ]);
                    }
                }
                
                $count++;
            }
            
            return json([
                'code' => 0,
                'message' => "已处理 {$count} 条记录",
                'data' => ['count' => $count]
            ]);
            
        } catch (\Exception $e) {
            return json([
                'code' => 500,
                'message' => '操作失败：' . $e->getMessage(),
                'data' => null
            ]);
        }
    }
    
    /**
     * 获取反水统计数据
     * GET /app/admin/api/rebate/stats
     */
    public function stats(Request $request)
    {
        try {
            $todayStart = strtotime(date('Y-m-d 00:00:00'));
            $monthStart = strtotime(date('Y-m-01 00:00:00'));
            
            // 今日统计
            $todayTotal = Db::table('caipiao_fanshui')
                ->where('oddtime', '>=', $todayStart)
                ->where('shenhe', 1)
                ->sum('amount') ?? 0;
            
            $todayCount = Db::table('caipiao_fanshui')
                ->where('oddtime', '>=', $todayStart)
                ->count();
            
            $todayPending = Db::table('caipiao_fanshui')
                ->where('oddtime', '>=', $todayStart)
                ->where('shenhe', 0)
                ->count();
            
            // 本月统计
            $monthTotal = Db::table('caipiao_fanshui')
                ->where('oddtime', '>=', $monthStart)
                ->where('shenhe', 1)
                ->sum('amount') ?? 0;
            
            $monthCount = Db::table('caipiao_fanshui')
                ->where('oddtime', '>=', $monthStart)
                ->count();
            
            // 总待审核
            $totalPending = Db::table('caipiao_fanshui')
                ->where('shenhe', 0)
                ->count();
            
            // 按类型统计（今日）
            $dailyRebate = Db::table('caipiao_fanshui')
                ->where('oddtime', '>=', $todayStart)
                ->where('shenhe', 1)
                ->where(function($q) {
                    $q->whereNull('vendor_code')
                      ->orWhere('vendor_code', '');
                })
                ->sum('amount') ?? 0;
            
            $vendorRebate = Db::table('caipiao_fanshui')
                ->where('oddtime', '>=', $todayStart)
                ->where('shenhe', 1)
                ->whereNotNull('vendor_code')
                ->where('vendor_code', '!=', '')
                ->sum('amount') ?? 0;
            
            return json([
                'code' => 0,
                'message' => '获取成功',
                'data' => [
                    'today' => [
                        'total' => round($todayTotal, 2),
                        'count' => $todayCount,
                        'pending' => $todayPending,
                        'daily' => round($dailyRebate, 2),
                        'vendor' => round($vendorRebate, 2)
                    ],
                    'month' => [
                        'total' => round($monthTotal, 2),
                        'count' => $monthCount
                    ],
                    'totalPending' => $totalPending
                ]
            ]);
            
        } catch (\Exception $e) {
            return json([
                'code' => 500,
                'message' => '获取失败：' . $e->getMessage(),
                'data' => null
            ]);
        }
    }
    
    // ==================== 配置管理相关方法 ====================
    
    /**
     * 获取全局反水配置
     * GET /app/admin/api/rebate/global-config
     */
    public function globalConfig(Request $request)
    {
        try {
            $keys = ['rebate_enabled', 'rebate_cycle', 'rebate_min_amount', 'rebate_expire_days', 'rebate_turnover_times'];
            $configs = Db::table('caipiao_system_config')->whereIn('config_key', $keys)->pluck('config_value', 'config_key')->toArray();
            return json([
                'code' => 0, 'message' => '获取成功',
                'data' => [
                    'enabled' => ($configs['rebate_enabled'] ?? '1') === '1',
                    'cycle' => $configs['rebate_cycle'] ?? 'daily',
                    'minAmount' => floatval($configs['rebate_min_amount'] ?? 1),
                    'expireDays' => intval($configs['rebate_expire_days'] ?? 7),
                    'turnoverTimes' => floatval($configs['rebate_turnover_times'] ?? 1)
                ]
            ]);
        } catch (\Exception $e) {
            return json(['code' => 500, 'message' => '获取失败：' . $e->getMessage(), 'data' => null]);
        }
    }
    
    /**
     * 保存全局反水配置
     */
    public function globalConfigSave(Request $request)
    {
        try {
            $data = $request->post();
            $updates = [
                'rebate_enabled' => $data['enabled'] ? '1' : '0',
                'rebate_cycle' => $data['cycle'] ?? 'daily',
                'rebate_min_amount' => strval($data['minAmount'] ?? 1),
                'rebate_expire_days' => strval($data['expireDays'] ?? 7),
                'rebate_turnover_times' => strval($data['turnoverTimes'] ?? 1)
            ];
            foreach ($updates as $key => $value) {
                Db::table('caipiao_system_config')->updateOrInsert(['config_key' => $key], ['config_value' => $value, 'updated_at' => time()]);
            }
            return json(['code' => 0, 'message' => '保存成功', 'data' => null]);
        } catch (\Exception $e) {
            return json(['code' => 500, 'message' => '保存失败：' . $e->getMessage(), 'data' => null]);
        }
    }
    
    /**
     * 获取VIP等级反水配置（含游戏类型）
     */
    public function vipConfig(Request $request)
    {
        try {
            $groups = Db::table('caipiao_membergroup')
                ->select('groupid', 'groupname', 'fanshui', 
                    'fs_lottery', 'fs_realperson', 'fs_sport', 
                    'fs_esport', 'fs_chess', 'fs_electron', 'fs_fish')
                ->orderBy('groupid')
                ->get();
            
            $list = [];
            foreach ($groups as $g) {
                $tiers = $this->parseFanshuiConfig($g->fanshui ?? '');
                $list[] = [
                    'groupId' => $g->groupid,
                    'groupName' => $g->groupname,
                    'fanshuiRaw' => $g->fanshui ?? '',
                    'tiers' => $tiers,
                    // 游戏类型反水
                    'gameTypeRates' => [
                        ['type' => 1, 'name' => '真人', 'field' => 'fs_realperson', 'rate' => floatval($g->fs_realperson)],
                        ['type' => 2, 'name' => '捕鱼', 'field' => 'fs_fish', 'rate' => floatval($g->fs_fish)],
                        ['type' => 3, 'name' => '电子', 'field' => 'fs_electron', 'rate' => floatval($g->fs_electron)],
                        ['type' => 4, 'name' => '彩票', 'field' => 'fs_lottery', 'rate' => floatval($g->fs_lottery)],
                        ['type' => 5, 'name' => '体育', 'field' => 'fs_sport', 'rate' => floatval($g->fs_sport)],
                        ['type' => 6, 'name' => '棋牌', 'field' => 'fs_chess', 'rate' => floatval($g->fs_chess)],
                        ['type' => 7, 'name' => '电竞', 'field' => 'fs_esport', 'rate' => floatval($g->fs_esport)],
                    ]
                ];
            }
            return json(['code' => 0, 'message' => '获取成功', 'data' => ['list' => $list]]);
        } catch (\Exception $e) {
            return json(['code' => 500, 'message' => '获取失败：' . $e->getMessage(), 'data' => null]);
        }
    }
    
    /**
     * 保存VIP等级反水配置（含游戏类型）
     */
    public function vipConfigSave(Request $request)
    {
        try {
            $groupId = $request->post('groupId');
            $tiers = $request->post('tiers', []);
            $gameTypeRates = $request->post('gameTypeRates', []);
            
            // 构建旧格式的fanshui配置
            $parts = [];
            foreach ($tiers as $tier) {
                $min = $tier['min'] ?? 0;
                $max = $tier['max'] ?? 0;
                $rate = $tier['rate'] ?? 0;
                if ($max > $min && $rate > 0) {
                    $parts[] = "{$min}-{$max}|{$rate}";
                }
            }
            
            // 构建更新数据
            $updateData = ['fanshui' => implode(';', $parts)];
            
            // 添加游戏类型反水配置
            $fieldMap = [
                1 => 'fs_realperson',
                2 => 'fs_fish',
                3 => 'fs_electron',
                4 => 'fs_lottery',
                5 => 'fs_sport',
                6 => 'fs_chess',
                7 => 'fs_esport',
            ];
            
            foreach ($gameTypeRates as $item) {
                $type = intval($item['type'] ?? 0);
                $rate = floatval($item['rate'] ?? 0);
                if (isset($fieldMap[$type])) {
                    $updateData[$fieldMap[$type]] = $rate;
                }
            }
            
            Db::table('caipiao_membergroup')->where('groupid', $groupId)->update($updateData);
            
            return json(['code' => 0, 'message' => '保存成功', 'data' => null]);
        } catch (\Exception $e) {
            return json(['code' => 500, 'message' => '保存失败：' . $e->getMessage(), 'data' => null]);
        }
    }

    
    /**
 * 获取游戏商反水配置
 */
public function vendorConfig(Request $request)
{
    try {
        // 获取反水分类配置
        $categoryConfigs = Db::table('caipiao_rebate_category_config')
            ->orderBy('sort')
            ->get()
            ->keyBy('category_code');
        
        // 从 caipiao_game_platform 表获取所有平台（按type分类）
        $platforms = Db::table('caipiao_game_platform')
            ->where('status', 'online')
            ->orderBy('sort')
            ->get();
        
        // 从 caipiao_game 表获取游戏数量统计
        $gameCounts = Db::table('caipiao_game')
            ->select('platform', Db::raw('COUNT(*) as cnt'))
            ->where('status', 'online')
            ->groupBy('platform')
            ->pluck('cnt', 'platform')
            ->toArray();
        
        // 获取已配置的反水设置
        $vendorConfigs = Db::table('caipiao_rebate_vendor_config')->get()->keyBy(function($item) { 
            return $item->category_code . '_' . $item->vendor_code; 
        });
        
        // 分类映射
        $typeMapping = [
            'slot' => 'slot',
            'live' => 'live', 
            'fishing' => 'fishing',
            'chess' => 'chess',
            'lottery' => 'lottery',
            'sport' => 'sport',
            'esport' => 'esport'
        ];
        
        // 按分类组织平台数据
        $categorized = [];
        foreach ($platforms as $platform) {
            $type = $platform->type;
            
            // 如果type不在标准分类中，尝试从code推断
            if (!isset($typeMapping[$type])) {
                if (strpos($platform->code, '_LOTTERY') !== false || strpos($platform->name, '彩票') !== false) {
                    $type = 'lottery';
                } elseif (strpos($platform->code, '_CHESS') !== false || strpos($platform->name, '棋牌') !== false) {
                    $type = 'chess';
                } elseif (strpos($platform->code, '_FISH') !== false || strpos($platform->name, '捕鱼') !== false) {
                    $type = 'fishing';
                } elseif (strpos($platform->name, '体育') !== false) {
                    $type = 'sport';
                } elseif (strpos($platform->name, '电竞') !== false) {
                    $type = 'esport';
                } elseif (strpos($platform->name, '真人') !== false || strpos($platform->name, '视讯') !== false) {
                    $type = 'live';
                } else {
                    $type = 'slot'; // 默认归类为电子
                }
            }
            
            if (!isset($categorized[$type])) {
                $categorized[$type] = [];
            }
            
            $key = $type . '_' . $platform->code;
            $cfg = $vendorConfigs[$key] ?? null;
            
            $categorized[$type][] = [
                'vendorCode' => $platform->code,
                'vendorName' => $platform->name,
                'categoryCode' => $type,
                'gameCount' => $gameCounts[$platform->code] ?? 0,
                'status' => $cfg ? $cfg->status : 1,
                'baseRate' => $cfg ? floatval($cfg->base_rate) : 0.5,
                'vipBonus' => $cfg ? floatval($cfg->vip_bonus) : 0.1,
                'minBet' => $cfg ? floatval($cfg->min_bet) : 100
            ];
        }
        
        // 构建结果（包含分类开关状态）
        $result = [];
        foreach ($categoryConfigs as $code => $catCfg) {
            $result[] = [
                'code' => $code,
                'name' => $catCfg->category_name,
                'status' => intval($catCfg->status),
                'sort' => intval($catCfg->sort),
                'vendors' => $categorized[$code] ?? []
            ];
        }
        
        return json(['code' => 0, 'message' => '获取成功', 'data' => ['categories' => $result]]);
    } catch (\Exception $e) {
        return json(['code' => 500, 'message' => '获取失败：' . $e->getMessage(), 'data' => null]);
    }
}
    
    /**
     * 保存分类状态
     */
    public function categoryStatusSave(Request $request)
    {
        try {
            $code = $request->post('code');
            $status = $request->post('status', 1);
            
            Db::table('caipiao_rebate_category_config')
                ->where('category_code', $code)
                ->update(['status' => $status, 'updated_at' => time()]);
            
            return json(['code' => 0, 'message' => '保存成功', 'data' => null]);
        } catch (\Exception $e) {
            return json(['code' => 500, 'message' => '保存失败：' . $e->getMessage(), 'data' => null]);
        }
    }
    
    /**
     * 保存游戏商反水配置
     */
    public function vendorConfigSave(Request $request)
    {
        try {
            $data = $request->post();
            if (empty($data['vendorCode']) || empty($data['categoryCode'])) { return json(['code' => 400, 'message' => '参数错误', 'data' => null]); }
            Db::table('caipiao_rebate_vendor_config')->updateOrInsert(
                ['vendor_code' => $data['vendorCode'], 'category_code' => $data['categoryCode']],
                ['vendor_name' => $data['vendorName'] ?? '', 'status' => $data['status'] ?? 1, 'base_rate' => $data['baseRate'] ?? 0.5, 'vip_bonus' => $data['vipBonus'] ?? 0.1, 'min_bet' => $data['minBet'] ?? 100, 'updated_at' => time()]
            );
            return json(['code' => 0, 'message' => '保存成功', 'data' => null]);
        } catch (\Exception $e) {
            return json(['code' => 500, 'message' => '保存失败：' . $e->getMessage(), 'data' => null]);
        }
    }
    
    protected function parseFanshuiConfig(string $config): array
    {
        if (empty($config)) return [];
        $tiers = [];
        foreach (explode(';', $config) as $seg) {
            $seg = trim($seg); if (empty($seg)) continue;
            $parts = explode('|', $seg); if (count($parts) !== 2) continue;
            $range = explode('-', $parts[0]); if (count($range) !== 2) continue;
            $tiers[] = ['min' => floatval(trim($range[0])), 'max' => floatval(trim($range[1])), 'rate' => floatval(trim($parts[1]))];
        }
        return $tiers;
    }
    
    /**
     * 获取统计报表数据
     * GET /app/admin/api/rebate/report
     */
    public function report(Request $request)
    {
        try {
            $days = intval($request->get('days', 7));
            $todayStart = strtotime(date('Y-m-d 00:00:00'));
            
            // 近N天趋势数据
            $trend = [];
            for ($i = $days - 1; $i >= 0; $i--) {
                $dayStart = strtotime("-{$i} days", $todayStart);
                $dayEnd = $dayStart + 86400;
                $amount = Db::table('caipiao_fanshui')->where('oddtime', '>=', $dayStart)->where('oddtime', '<', $dayEnd)->where('shenhe', 1)->sum('amount') ?? 0;
                $count = Db::table('caipiao_fanshui')->where('oddtime', '>=', $dayStart)->where('oddtime', '<', $dayEnd)->count();
                $trend[] = ['date' => date('m-d', $dayStart), 'amount' => round($amount, 2), 'count' => $count];
            }
            
            // 按游戏类型分布
            $byCategory = Db::table('caipiao_fanshui')->select(Db::raw("CASE WHEN vendor_code = '' OR vendor_code IS NULL THEN 'daily' ELSE LEFT(vendor_code, LOCATE('_', vendor_code) - 1) END as category, SUM(amount) as total"))
                ->where('shenhe', 1)->where('oddtime', '>=', strtotime('-30 days', $todayStart))->groupBy('category')->get();
            
            // 按VIP等级分布
            $byVip = Db::table('caipiao_fanshui')->select('groupname', Db::raw('SUM(amount) as total, COUNT(*) as cnt'))
                ->where('shenhe', 1)->where('oddtime', '>=', strtotime('-30 days', $todayStart))->groupBy('groupname')->get();
            
            return json([
                'code' => 0, 'message' => '获取成功',
                'data' => [
                    'trend' => $trend,
                    'byCategory' => $byCategory,
                    'byVip' => $byVip
                ]
            ]);
        } catch (\Exception $e) {
            return json(['code' => 500, 'message' => '获取失败：' . $e->getMessage(), 'data' => null]);
        }
    }

    /**
     * 获取阶梯反水配置列表
     * GET /app/admin/api/rebate/tier-config
     */
    public function tierConfig(Request $request)
    {
        try {
            $categoryCode = $request->get('category_code', '');
            $vendorCode = $request->get('vendor_code', '');
            
            $query = Db::table('caipiao_rebate_tier_config')->orderBy('category_code')->orderBy('vendor_code')->orderBy('min_bet');
            
            if (!empty($categoryCode)) {
                $query->where('category_code', $categoryCode);
            }
            
            if (!empty($vendorCode)) {
                $query->where('vendor_code', $vendorCode);
            }
            
            $list = $query->get();
            
            // 按分类和平台分组
            $grouped = [];
            foreach ($list as $item) {
                $key = $item->category_code . '_' . $item->vendor_code;
                if (!isset($grouped[$key])) {
                    $grouped[$key] = [
                        'categoryCode' => $item->category_code,
                        'vendorCode' => $item->vendor_code,
                        'tiers' => []
                    ];
                }
                $grouped[$key]['tiers'][] = [
                    'id' => $item->id,
                    'minBet' => floatval($item->min_bet),
                    'rate' => floatval($item->rate),
                    'status' => intval($item->status)
                ];
            }
            
            // 获取分类和平台名称
            $categoryNames = [
                'slot' => '电子', 'live' => '真人', 'fishing' => '捕鱼',
                'chess' => '棋牌', 'lottery' => '彩票', 'sport' => '体育', 'esport' => '电竞'
            ];
            $platformNames = Db::table('caipiao_game_platform')->pluck('name', 'code')->toArray();
            
            $result = [];
            foreach ($grouped as $item) {
                $item['categoryName'] = $categoryNames[$item['categoryCode']] ?? $item['categoryCode'];
                $item['vendorName'] = $platformNames[$item['vendorCode']] ?? $item['vendorCode'];
                $result[] = $item;
            }
            
            return json(['code' => 0, 'message' => '获取成功', 'data' => ['list' => $result]]);
        } catch (\Exception $e) {
            return json(['code' => 500, 'message' => '获取失败：' . $e->getMessage(), 'data' => null]);
        }
    }

    /**
     * 保存阶梯反水配置
     * POST /app/admin/api/rebate/tier-config-save
     */
    public function tierConfigSave(Request $request)
    {
        try {
            $categoryCode = $request->post('category_code', '');
            $vendorCode = $request->post('vendor_code', '');
            $tiers = $request->post('tiers', []);
            
            if (empty($categoryCode) || empty($vendorCode)) {
                return json(['code' => 400, 'message' => '分类和平台不能为空', 'data' => null]);
            }
            
            $now = time();
            
            // 删除旧配置
            Db::table('caipiao_rebate_tier_config')
                ->where('category_code', $categoryCode)
                ->where('vendor_code', $vendorCode)
                ->delete();
            
            // 插入新配置
            foreach ($tiers as $tier) {
                Db::table('caipiao_rebate_tier_config')->insert([
                    'category_code' => $categoryCode,
                    'vendor_code' => $vendorCode,
                    'min_bet' => floatval($tier['minBet'] ?? 0),
                    'rate' => floatval($tier['rate'] ?? 0),
                    'status' => intval($tier['status'] ?? 1),
                    'created_at' => $now,
                    'updated_at' => $now
                ]);
            }
            
            return json(['code' => 0, 'message' => '保存成功', 'data' => null]);
        } catch (\Exception $e) {
            return json(['code' => 500, 'message' => '保存失败：' . $e->getMessage(), 'data' => null]);
        }
    }

    /**
     * 删除阶梯反水配置
     * POST /app/admin/api/rebate/tier-config-delete
     */
    public function tierConfigDelete(Request $request)
    {
        try {
            $id = intval($request->post('id', 0));
            
            if ($id > 0) {
                Db::table('caipiao_rebate_tier_config')->where('id', $id)->delete();
            }
            
            return json(['code' => 0, 'message' => '删除成功', 'data' => null]);
        } catch (\Exception $e) {
            return json(['code' => 500, 'message' => '删除失败：' . $e->getMessage(), 'data' => null]);
        }
    }

    /**
     * 获取用户累计投注统计
     * GET /app/admin/api/rebate/user-bet-stats
     */
    public function userBetStats(Request $request)
    {
        try {
            $uid = intval($request->get('uid', 0));
            $username = $request->get('username', '');
            
            $query = Db::table('caipiao_bet_stats as s')
                ->leftJoin('caipiao_member as m', 's.uid', '=', 'm.id')
                ->select('s.*', 'm.username');
            
            if ($uid > 0) {
                $query->where('s.uid', $uid);
            }
            
            if (!empty($username)) {
                $query->where('m.username', 'like', "%{$username}%");
            }
            
            $list = $query->orderBy('s.total_bet', 'desc')->limit(100)->get();
            
            return json(['code' => 0, 'message' => '获取成功', 'data' => ['list' => $list]]);
        } catch (\Exception $e) {
            return json(['code' => 500, 'message' => '获取失败：' . $e->getMessage(), 'data' => null]);
        }
    }

    /**
     * 清理非通用阶梯配置（只保留 vendor_code = '*'）
     * POST /app/admin/api/rebate/cleanup-tier-config
     */
    public function cleanupTierConfig(Request $request)
    {
        try {
            // 删除非通用配置
            $deleted = Db::table('caipiao_rebate_tier_config')
                ->where('vendor_code', '!=', '*')
                ->delete();
            
            return json([
                'code' => 0,
                'message' => "清理完成，删除了 {$deleted} 条非通用配置",
                'data' => ['deleted' => $deleted]
            ]);
        } catch (\Exception $e) {
            return json(['code' => 500, 'message' => '清理失败：' . $e->getMessage(), 'data' => null]);
        }
    }
}

