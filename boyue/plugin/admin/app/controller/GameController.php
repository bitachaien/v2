<?php
namespace plugin\admin\app\controller;

use support\Request;
use support\Db;

/**
 * 游戏记录控制器
 */
class GameController extends Base
{
    /**
     * 游戏记录页面
     */
    public function record(Request $request)
    {
        return view('game/record');
    }

    /**
     * 获取游戏记录列表
     */
    public function recordList(Request $request)
    {
        $isnb = $request->get('isnb', '');
        $cpname = $request->get('cpname', '');
        $expect = $request->get('expect', '');
        $orderno = $request->get('orderno', '');
        $username = $request->get('username', '');
        $status = $request->get('status', '');
        $orderby = $request->get('orderby', '');
        $startTime = $request->get('start_time', '');
        $endTime = $request->get('end_time', '');
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 20);
        
        // 直接查询投注表（老版本不做关联）
        $query = Db::table('caipiao_touzhu as t')
            ->select('t.*');
        
        // 排除机器人投注记录
        $query->where(function($q) {
            $q->whereNull('t.source')
              ->orWhere('t.source', '!=', 'robot');
        });
        
        // 筛选条件
        if (!empty($cpname)) {
            $query->where('t.cpname', $cpname);
        }
        
        if (!empty($expect)) {
            $query->where('t.expect', 'like', '%' . $expect . '%');
        }
        
        if (!empty($orderno)) {
            $query->where('t.trano', 'like', '%' . $orderno . '%');
        }
        
        if (!empty($username)) {
            $query->where('t.username', 'like', '%' . $username . '%');
        }
        
        // 状态筛选（空字符串或999表示全部）
        if ($status !== '' && $status != '999') {
            $query->where('t.isdraw', $status);
        }
        
        if (!empty($startTime)) {
            $startTimestamp = strtotime($startTime);
            if ($startTimestamp) {
                $query->where('t.oddtime', '>=', $startTimestamp);
            }
        }
        
        if (!empty($endTime)) {
            $endTimestamp = strtotime($endTime);
            if ($endTimestamp) {
                $query->where('t.oddtime', '<=', $endTimestamp);
            }
        }
        
        // 排序
        switch ($orderby) {
            case 'time_desc':
                $query->orderBy('t.oddtime', 'desc');
                break;
            case 'time_asc':
                $query->orderBy('t.oddtime', 'asc');
                break;
            case 'amount_desc':
                $query->orderBy('t.amount', 'desc');
                break;
            case 'amount_asc':
                $query->orderBy('t.amount', 'asc');
                break;
            default:
                $query->orderBy('t.id', 'desc');
                break;
        }
        
        // 调试：记录SQL
        \support\Log::info('游戏记录查询条件: ' . json_encode([
            'cpname' => $cpname,
            'expect' => $expect,
            'orderno' => $orderno,
            'username' => $username,
            'status' => $status,
            'startTime' => $startTime,
            'endTime' => $endTime
        ], JSON_UNESCAPED_UNICODE));
        
        // 获取总数
        $count = $query->count();
        
        \support\Log::info('游戏记录总数: ' . $count);
        
        // 分页
        $offset = ($page - 1) * $limit;
        $list = $query->offset($offset)->limit($limit)->get();
        
        // 处理数据
        $result = [];
        foreach ($list as $item) {
            $row = (array)$item;
            
            // 单号：trano
            $row['orderno'] = $row['trano'] ?? '';
            
            // 用户名：username（直接在投注表）
            // 已有此字段
            
            // 彩票名称：cptitle（直接在投注表）
            $row['lottery_name'] = $row['cptitle'] ?? '';
            
            // 玩法名称：playtitle
            $row['play_name'] = $row['playtitle'] ?? '';
            
            // 玩法ID：playid（用于前端判断玩法类型）
            $row['play_id'] = $row['playid'] ?? '';
            
            // 注数：itemcount
            $row['betcount'] = $row['itemcount'] ?? 0;
            
            // 奖金/赔率：mode
            $row['odds'] = $row['mode'] ?? 0;
            
            // 投注后金额：amountafter
            $row['after_balance'] = $row['amountafter'] ?? 0;
            
            // 中奖金额：okamount
            $row['winamount'] = $row['okamount'] ?? 0;
            
            // 中奖注数：okcount
            $row['wincount'] = $row['okcount'] ?? 0;
            
            // 中奖倍数：beishu
            $row['multiple'] = $row['beishu'] ?? 0;
            
            // 元角分：yjf
            $row['mode_text'] = $row['yjf'] ?? 1;
            
            // 号码：tzcode
            $row['betcontent'] = $row['tzcode'] ?? '';
            
            // 开奖号：opencode
            // 已有此字段
            
            // 格式化投注时间
            if (isset($row['oddtime']) && is_numeric($row['oddtime'])) {
                $row['bettime'] = date('m-d H:i:s', $row['oddtime']);
            }
            
            // 类型文本：判断是否合买
            if (isset($row['ishemai']) && $row['ishemai'] == 1) {
                $row['type_text'] = '合买';
            } else {
                $row['type_text'] = '代购';
            }
            
            // 状态文本
            if ($row['isdraw'] == 1) {
                $row['status_text'] = '中奖';
            } elseif ($row['isdraw'] == -1) {
                $row['status_text'] = '未中奖';
            } elseif ($row['isdraw'] == -2) {
                $row['status_text'] = '撤单';
            } else {
                $row['status_text'] = '未开奖';
            }
            
            $result[] = $row;
        }
        
        // Layui table 需要的格式
        return json([
            'code' => 0,
            'msg' => 'success',
            'data' => $result,
            'count' => $count
        ]);
    }

    /**
     * 撤单操作
     */
    public function cancel(Request $request)
    {
        $id = $request->post('id');
        
        if (!$id) {
            return $this->json(1, '参数错误');
        }
        
        // 查询投注记录
        $bet = Db::table('caipiao_touzhu')->where('id', $id)->first();
        
        if (!$bet) {
            return $this->json(1, '投注记录不存在');
        }
        
        // 检查是否已开奖
        if ($bet->isdraw != 0) {
            return $this->json(1, '该订单已开奖，无法撤单');
        }
        
        // 更新状态为撤单
        $result = Db::table('caipiao_touzhu')
            ->where('id', $id)
            ->update(['isdraw' => -2]);
        
        if ($result !== false) {
            // 退还金额给用户
            Db::table('caipiao_member')
                ->where('id', $bet->uid)
                ->increment('balance', $bet->amount);
            
            \support\Log::info('撤单成功 - 订单ID: ' . $id . ', 金额: ' . $bet->amount);
            return $this->json(0, '撤单成功');
        } else {
            return $this->json(1, '撤单失败');
        }
    }

    /**
     * 注单异常检测页面
     */
    public function checkAnomalyOrder(Request $request)
    {
        return view('game/check-anomaly');
    }

    /**
     * 获取注单异常检测列表
     */
    public function checkAnomalyOrderList(Request $request)
    {
        $cpname = $request->get('cpname', '');
        $username = $request->get('username', '');
        $shijiancha = $request->get('shijiancha', 130); // 默认130秒
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 20);
        
        // 核心SQL：查询投注时间和开奖时间差异小于等于指定值的注单
        // 这些注单可能存在异常（在开奖前很短时间内投注）
        $query = Db::table('caipiao_touzhu as a')
            ->leftJoin('caipiao_kaijiang as b', function($join) {
                $join->on('a.cpname', '=', 'b.name')
                     ->on('a.expect', '=', 'b.expect');
            })
            ->leftJoin('caipiao_caipiao as c', 'a.cpname', '=', 'c.name')
            ->select(
                'a.*',
                'b.name as bname',
                'b.opentime',
                'b.expect as bexpect',
                'c.ftime',
                'c.issys',
                'c.name as cname'
            )
            ->whereRaw('b.name IS NOT NULL AND b.name != ""')
            // 排除机器人投注记录
            ->where(function($q) {
                $q->whereNull('a.source')
                  ->orWhere('a.source', '!=', 'robot');
            });
        
        // 彩种筛选
        if (!empty($cpname)) {
            $query->where('a.cpname', $cpname);
        }
        
        // 用户名筛选
        if (!empty($username)) {
            $query->where('a.username', 'like', '%' . $username . '%');
        }
        
        // 时间差筛选：开奖时间 - 投注时间 <= 指定秒数
        if ($shijiancha > 0) {
            $query->whereRaw('b.opentime - a.oddtime <= ?', [$shijiancha]);
        }
        
        // 按ID降序
        $query->orderBy('a.id', 'desc');
        
        // 获取总数
        $count = $query->count();
        
        // 分页
        $offset = ($page - 1) * $limit;
        $list = $query->offset($offset)->limit($limit)->get();
        
        // 处理数据
        $result = [];
        foreach ($list as $item) {
            $row = (array)$item;
            
            // 单号
            $row['orderno'] = $row['trano'] ?? '';
            
            // 用户名
            // 已有 username 字段
            
            // 彩票名称
            $row['lottery_name'] = $row['cptitle'] ?? '';
            
            // 期号
            // 已有 expect 字段
            
            // 玩法
            $row['play_name'] = $row['playtitle'] ?? '';
            
            // 投注金额
            // 已有 amount 字段
            
            // 中奖金额
            // 已有 okamount 字段
            
            // 状态文本（不包含HTML，由前端模板处理样式）
            if ($row['isdraw'] == 1) {
                $row['status_text'] = '中';
                $row['status_class'] = 'c-green';
            } elseif ($row['isdraw'] == -1) {
                $row['status_text'] = '未中';
                $row['status_class'] = 'c-red';
            } elseif ($row['isdraw'] == -2) {
                $row['status_text'] = '撤';
                $row['status_class'] = 'c-666';
            } else {
                $row['status_text'] = '未开奖';
                $row['status_class'] = 'c-333';
            }
            
            // 格式化投注时间
            if (isset($row['oddtime']) && is_numeric($row['oddtime'])) {
                $row['oddtime_text'] = date('m-d H:i:s', $row['oddtime']);
            }
            
            // 格式化开奖时间
            if (isset($row['opentime']) && is_numeric($row['opentime'])) {
                $row['opentime_text'] = date('m-d H:i:s', $row['opentime']);
            }
            
            // 计算时间差（秒）
            if (isset($row['opentime']) && isset($row['oddtime'])) {
                $row['time_diff'] = $row['opentime'] - $row['oddtime'];
            } else {
                $row['time_diff'] = 0;
            }
            
            $result[] = $row;
        }
        
        // Layui table 需要的格式
        return json([
            'code' => 0,
            'msg' => 'success',
            'data' => $result,
            'count' => $count
        ]);
    }

    /**
     * ============================================
     * 游戏平台管理 API（Art Design Pro 前端专用）
     * ============================================
     */

    /**
     * 游戏平台列表
     * GET /app/admin/api/game/platform-list
     */
    public function platformList(Request $request)
    {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 20);
        $name = $request->get('name', '');
        $type = $request->get('type', '');
        $status = $request->get('status', '');

        $query = Db::table('caipiao_game_platform');

        if ($name) {
            $query->where(function($q) use ($name) {
                $q->where('name', 'like', "%{$name}%")
                  ->orWhere('code', 'like', "%{$name}%");
            });
        }

        if ($type) {
            // 彩票、体育、电竞等直接进入大厅的类型：根据平台表的 type 筛选
            $directTypes = ['lottery', 'sport', 'esport'];
            if (in_array($type, $directTypes)) {
                $query->where('type', $type);
            } else {
                // 其他类型：根据游戏表的类型筛选（支持综合平台多分类显示）
                $gameTypeMap = ['fish' => 'fishing'];
                $dbType = $gameTypeMap[$type] ?? $type;
                
                $platformCodes = Db::table('caipiao_game')
                    ->where('type', $dbType)
                    ->distinct()
                    ->pluck('platform')
                    ->toArray();
                
                if (!empty($platformCodes)) {
                    $query->whereIn('code', $platformCodes);
                } else {
                    // 没有游戏的分类，回退到按平台类型筛选
                    $query->where('type', $type);
                }
            }
        }

        if ($status) {
            $query->where('status', $status);
        }

        $count = $query->count();

        $offset = ($page - 1) * $limit;
        $list = $query->orderBy('sort', 'desc')
            ->orderBy('id', 'asc')
            ->offset($offset)
            ->limit($limit)
            ->get();

        $typeMap = [
            'live' => '真人视讯',
            'slot' => '电子游戏',
            'chess' => '棋牌游戏',
            'sport' => '体育投注',
            'lottery' => '彩票游戏',
            'fishing' => '捕鱼游戏',
        ];

        $statusMap = [
            'online' => '正常',
            'offline' => '下线',
            'maintenance' => '维护中',
        ];

        $result = [];
        foreach ($list as $item) {
            $row = (array)$item;
            $row['type_text'] = $typeMap[$row['type']] ?? $row['type'];
            $row['status_text'] = $statusMap[$row['status']] ?? $row['status'];
            $row['created_at_text'] = $row['created_at'] ? date('Y-m-d H:i:s', $row['created_at']) : '';
            $row['updated_at_text'] = $row['updated_at'] ? date('Y-m-d H:i:s', $row['updated_at']) : '';
            $result[] = $row;
        }

        return json([
            'code' => 0,
            'msg' => 'ok',
            'data' => $result,
            'count' => $count
        ]);
    }

    /**
     * 游戏平台详情
     * GET /app/admin/api/game/platform-detail
     */
    public function platformDetail(Request $request)
    {
        $id = $request->get('id');
        if (!$id) {
            return json(['code' => 1, 'msg' => '参数错误']);
        }

        $item = Db::table('caipiao_game_platform')->where('id', $id)->first();
        if (!$item) {
            return json(['code' => 1, 'msg' => '平台不存在']);
        }

        return json(['code' => 0, 'msg' => 'ok', 'data' => (array)$item]);
    }

    /**
     * 添加游戏平台
     * POST /app/admin/api/game/platform-add
     */
    public function platformAdd(Request $request)
    {
        $code = strtoupper(trim($request->post('code', '')));
        $name = $request->post('name');
        $type = $request->post('type', 'live');
        $icon = $request->post('icon', '');
        $status = $request->post('status', 'online');
        $hot = $request->post('hot', 0);
        $recommend = $request->post('recommend', 0);
        $sort = $request->post('sort', 0);
        $apiUrl = $request->post('api_url', '');
        $apiKey = $request->post('api_key', '');
        $apiSecret = $request->post('api_secret', '');

        if (!$code) {
            return json(['code' => 1, 'msg' => '平台代码不能为空']);
        }

        if (!$name) {
            return json(['code' => 1, 'msg' => '平台名称不能为空']);
        }

        // 检查代码是否已存在
        $exists = Db::table('caipiao_game_platform')->where('code', $code)->exists();
        if ($exists) {
            return json(['code' => 1, 'msg' => '平台代码已存在']);
        }

        $now = time();
        $id = Db::table('caipiao_game_platform')->insertGetId([
            'code' => $code,
            'name' => $name,
            'type' => $type,
            'icon' => $icon,
            'status' => $status,
            'hot' => $hot,
            'recommend' => $recommend,
            'sort' => $sort,
            'api_url' => $apiUrl,
            'api_key' => $apiKey,
            'api_secret' => $apiSecret,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return json(['code' => 0, 'msg' => '添加成功', 'data' => ['id' => $id]]);
    }

    /**
     * 编辑游戏平台
     * POST /app/admin/api/game/platform-edit
     */
    public function platformEdit(Request $request)
    {
        $id = $request->post('id');
        if (!$id) {
            return json(['code' => 1, 'msg' => '参数错误']);
        }

        $item = Db::table('caipiao_game_platform')->where('id', $id)->first();
        if (!$item) {
            return json(['code' => 1, 'msg' => '平台不存在']);
        }

        $data = ['updated_at' => time()];

        $code = $request->post('code');
        if ($code !== null) {
            $code = strtoupper(trim($code));
            // 检查代码是否被其他记录使用
            $exists = Db::table('caipiao_game_platform')
                ->where('code', $code)
                ->where('id', '!=', $id)
                ->exists();
            if ($exists) {
                return json(['code' => 1, 'msg' => '平台代码已被使用']);
            }
            $data['code'] = $code;
        }

        $name = $request->post('name');
        if ($name !== null) $data['name'] = $name;

        $type = $request->post('type');
        if ($type !== null) $data['type'] = $type;

        $icon = $request->post('icon');
        if ($icon !== null) $data['icon'] = $icon;

        $status = $request->post('status');
        if ($status !== null) $data['status'] = $status;

        $hot = $request->post('hot');
        if ($hot !== null) $data['hot'] = $hot;

        $recommend = $request->post('recommend');
        if ($recommend !== null) $data['recommend'] = $recommend;

        $sort = $request->post('sort');
        if ($sort !== null) $data['sort'] = $sort;

        $apiUrl = $request->post('api_url');
        if ($apiUrl !== null) $data['api_url'] = $apiUrl;

        $apiKey = $request->post('api_key');
        if ($apiKey !== null) $data['api_key'] = $apiKey;

        $apiSecret = $request->post('api_secret');
        if ($apiSecret !== null) $data['api_secret'] = $apiSecret;

        Db::table('caipiao_game_platform')->where('id', $id)->update($data);

        return json(['code' => 0, 'msg' => '修改成功']);
    }

    /**
     * 删除游戏平台
     * POST /app/admin/api/game/platform-delete
     */
    public function platformDelete(Request $request)
    {
        $id = $request->post('id');
        $ids = $request->post('ids', []);

        if (!$id && empty($ids)) {
            return json(['code' => 1, 'msg' => '参数错误']);
        }

        $deleteIds = $id ? [$id] : $ids;

        // 检查是否有游戏使用该平台
        $codes = Db::table('caipiao_game_platform')
            ->whereIn('id', $deleteIds)
            ->pluck('code')
            ->toArray();

        $usedCount = Db::table('caipiao_game')
            ->whereIn('platform', $codes)
            ->count();

        if ($usedCount > 0) {
            return json(['code' => 1, 'msg' => '该平台下有游戏，无法删除']);
        }

        Db::table('caipiao_game_platform')->whereIn('id', $deleteIds)->delete();

        return json(['code' => 0, 'msg' => '删除成功']);
    }

    /**
     * 更新游戏平台状态
     * POST /app/admin/api/game/platform-status
     */
    public function platformStatus(Request $request)
    {
        $id = $request->post('id');
        $status = $request->post('status');

        if (!$id || !$status) {
            return json(['code' => 1, 'msg' => '参数错误']);
        }

        $allowedStatus = ['online', 'offline', 'maintenance'];
        if (!in_array($status, $allowedStatus)) {
            return json(['code' => 1, 'msg' => '无效的状态值']);
        }

        Db::table('caipiao_game_platform')->where('id', $id)->update([
            'status' => $status,
            'updated_at' => time(),
        ]);

        return json(['code' => 0, 'msg' => '状态更新成功']);
    }

    /**
     * 查询平台余额（模拟）
     * GET /app/admin/api/game/platform-balance
     */
    public function platformBalance(Request $request)
    {
        $id = $request->get('id');
        $code = $request->get('code');

        if (!$id && !$code) {
            return json(['code' => 1, 'msg' => '参数错误']);
        }

        // 查询平台
        $query = Db::table('caipiao_game_platform');
        if ($id) {
            $query->where('id', $id);
        } else {
            $query->where('code', $code);
        }
        $platform = $query->first();

        if (!$platform) {
            return json(['code' => 1, 'msg' => '平台不存在']);
        }

        // 从 caipiao_game_balance 表查询余额
        $balance = Db::table('caipiao_game_balance')
            ->where('platform', $platform->code)
            ->first();

        return json([
            'code' => 0,
            'msg' => 'ok',
            'data' => [
                'platform' => $platform->code,
                'name' => $platform->name,
                'balance' => $balance->balance ?? 0,
                'updated_at' => $balance ? date('Y-m-d H:i:s', $balance->updated_at ?? 0) : '',
            ]
        ]);
    }

    /**
     * 查询所有平台余额
     * GET /app/admin/api/game/platform-balance-all
     */
    public function platformBalanceAll(Request $request)
    {
        $platforms = Db::table('caipiao_game_platform')
            ->where('status', 'online')
            ->orderBy('sort', 'desc')
            ->get(['id', 'code', 'name', 'type']);

        $result = [];
        foreach ($platforms as $platform) {
            $balance = Db::table('caipiao_game_balance')
                ->where('platform', $platform->code)
                ->first();

            $result[] = [
                'id' => $platform->id,
                'code' => $platform->code,
                'name' => $platform->name,
                'type' => $platform->type,
                'balance' => $balance->balance ?? 0,
            ];
        }

        return json(['code' => 0, 'msg' => 'ok', 'data' => $result]);
    }

    /**
     * 平台类型选项
     * GET /app/admin/api/game/platform-type-options
     */
    public function platformTypeOptions(Request $request)
    {
        $types = [
            ['value' => 'live', 'label' => '真人视讯'],
            ['value' => 'slot', 'label' => '电子游戏'],
            ['value' => 'chess', 'label' => '棋牌游戏'],
            ['value' => 'sport', 'label' => '体育投注'],
            ['value' => 'lottery', 'label' => '彩票游戏'],
            ['value' => 'fishing', 'label' => '捕鱼游戏'],
        ];

        return json(['code' => 0, 'msg' => 'ok', 'data' => $types]);
    }

    /**
     * 平台状态选项
     * GET /app/admin/api/game/platform-status-options
     */
    public function platformStatusOptions(Request $request)
    {
        $statuses = [
            ['value' => 'online', 'label' => '正常'],
            ['value' => 'offline', 'label' => '下线'],
            ['value' => 'maintenance', 'label' => '维护中'],
        ];

        return json(['code' => 0, 'msg' => 'ok', 'data' => $statuses]);
    }

    /**
     * 平台下拉选项（用于选择框）
     * GET /app/admin/api/game/platform-options
     */
    public function platformOptions(Request $request)
    {
        $type = $request->get('type', '');

        $query = Db::table('caipiao_game_platform')
            ->where('status', 'online');

        if ($type) {
            $query->where('type', $type);
        }

        $platforms = $query->orderBy('sort', 'desc')
            ->get(['code as value', 'name as label', 'type']);

        return json(['code' => 0, 'msg' => 'ok', 'data' => $platforms]);
    }

    /**
     * ============================================
     * 第三方游戏投注记录 API
     * ============================================
     */

    /**
     * 投注记录列表
     * GET /app/admin/api/game/bet-list
     */
    public function betList(Request $request)
    {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 20);
        $username = $request->get('username', '');
        $platform = $request->get('platform', '');
        $orderNo = $request->get('order_no', '');
        $status = $request->get('status', '');
        $startTime = $request->get('start_time', '');
        $endTime = $request->get('end_time', '');

        $query = Db::table('caipiao_game_bet as b')
            ->leftJoin('caipiao_member as m', 'b.uid', '=', 'm.id')
            ->select('b.*', 'm.username', 'm.nickname');

        if ($username) {
            $query->where(function($q) use ($username) {
                $q->where('m.username', 'like', "%{$username}%")
                  ->orWhere('m.nickname', 'like', "%{$username}%");
            });
        }

        if ($platform) {
            $query->where('b.platform', $platform);
        }

        if ($orderNo) {
            $query->where('b.order_no', 'like', "%{$orderNo}%");
        }

        if ($status) {
            $query->where('b.status', $status);
        }

        if ($startTime) {
            $query->where('b.bet_time', '>=', strtotime($startTime));
        }

        if ($endTime) {
            $query->where('b.bet_time', '<=', strtotime($endTime . ' 23:59:59'));
        }

        $count = $query->count();

        // 统计
        $statsQuery = clone $query;
        $stats = $statsQuery->selectRaw('SUM(b.bet_amount) as total_bet, SUM(b.win_amount) as total_win, SUM(b.profit) as total_profit')->first();

        $offset = ($page - 1) * $limit;
        $list = $query->orderBy('b.id', 'desc')
            ->offset($offset)
            ->limit($limit)
            ->get();

        $statusMap = [
            'pending' => '未结算',
            'settled' => '已结算',
            'cancelled' => '已取消',
        ];

        $result = [];
        foreach ($list as $item) {
            $row = (array)$item;
            $row['status_text'] = $statusMap[$row['status']] ?? $row['status'];
            $row['bet_time_text'] = $row['bet_time'] ? date('Y-m-d H:i:s', $row['bet_time']) : '';
            $row['settle_time_text'] = $row['settle_time'] ? date('Y-m-d H:i:s', $row['settle_time']) : '';
            $row['created_at_text'] = $row['created_at'] ? date('Y-m-d H:i:s', $row['created_at']) : '';
            $result[] = $row;
        }

        return json([
            'code' => 0,
            'msg' => 'ok',
            'data' => $result,
            'count' => $count,
            'stats' => [
                'total_bet' => round($stats->total_bet ?? 0, 2),
                'total_win' => round($stats->total_win ?? 0, 2),
                'total_profit' => round($stats->total_profit ?? 0, 2),
            ]
        ]);
    }

    /**
     * 投注记录详情
     * GET /app/admin/api/game/bet-detail
     */
    public function betDetail(Request $request)
    {
        $id = $request->get('id');
        if (!$id) {
            return json(['code' => 1, 'msg' => '参数错误']);
        }

        $item = Db::table('caipiao_game_bet as b')
            ->leftJoin('caipiao_member as m', 'b.uid', '=', 'm.id')
            ->select('b.*', 'm.username', 'm.nickname')
            ->where('b.id', $id)
            ->first();

        if (!$item) {
            return json(['code' => 1, 'msg' => '记录不存在']);
        }

        $row = (array)$item;
        $row['bet_time_text'] = $row['bet_time'] ? date('Y-m-d H:i:s', $row['bet_time']) : '';
        $row['settle_time_text'] = $row['settle_time'] ? date('Y-m-d H:i:s', $row['settle_time']) : '';

        return json(['code' => 0, 'msg' => 'ok', 'data' => $row]);
    }

    /**
     * 投注状态选项
     * GET /app/admin/api/game/bet-status-options
     */
    public function betStatusOptions(Request $request)
    {
        $statuses = [
            ['value' => 'pending', 'label' => '未结算'],
            ['value' => 'settled', 'label' => '已结算'],
            ['value' => 'cancelled', 'label' => '已取消'],
        ];

        return json(['code' => 0, 'msg' => 'ok', 'data' => $statuses]);
    }

    /**
     * ============================================
     * 额度转让 API
     * ============================================
     */

    /**
     * 额度转让列表
     * GET /app/admin/api/game/transfer-list
     */
    public function transferList(Request $request)
    {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 20);
        $username = $request->get('username', '');
        $platform = $request->get('platform', '');
        $type = $request->get('type', '');
        $orderNo = $request->get('order_no', '');
        $status = $request->get('status', '');
        $startTime = $request->get('start_time', '');
        $endTime = $request->get('end_time', '');

        $query = Db::table('caipiao_game_transfer as t')
            ->leftJoin('caipiao_member as m', 't.uid', '=', 'm.id')
            ->select('t.*', 'm.username', 'm.nickname');

        if ($username) {
            $query->where(function($q) use ($username) {
                $q->where('m.username', 'like', "%{$username}%")
                  ->orWhere('m.nickname', 'like', "%{$username}%");
            });
        }

        if ($platform) {
            $query->where('t.platform', $platform);
        }

        if ($type) {
            $query->where('t.type', $type);
        }

        if ($orderNo) {
            $query->where('t.order_no', 'like', "%{$orderNo}%");
        }

        if ($status !== '') {
            $query->where('t.status', $status);
        }

        if ($startTime) {
            $query->where('t.created_at', '>=', strtotime($startTime));
        }

        if ($endTime) {
            $query->where('t.created_at', '<=', strtotime($endTime . ' 23:59:59'));
        }

        $count = $query->count();

        // 统计
        $statsQuery = clone $query;
        $stats = $statsQuery->selectRaw('
            SUM(CASE WHEN t.type = "in" THEN t.amount ELSE 0 END) as total_in,
            SUM(CASE WHEN t.type = "out" THEN t.amount ELSE 0 END) as total_out
        ')->first();

        $offset = ($page - 1) * $limit;
        $list = $query->orderBy('t.id', 'desc')
            ->offset($offset)
            ->limit($limit)
            ->get();

        $typeMap = ['in' => '转入', 'out' => '转出'];
        $statusMap = [0 => '处理中', 1 => '成功', 2 => '失败'];

        $result = [];
        foreach ($list as $item) {
            $row = (array)$item;
            $row['type_text'] = $typeMap[$row['type']] ?? $row['type'];
            $row['status_text'] = $statusMap[$row['status']] ?? '未知';
            $row['created_at_text'] = $row['created_at'] ? date('Y-m-d H:i:s', $row['created_at']) : '';
            $result[] = $row;
        }

        return json([
            'code' => 0,
            'msg' => 'ok',
            'data' => $result,
            'count' => $count,
            'stats' => [
                'total_in' => round($stats->total_in ?? 0, 2),
                'total_out' => round($stats->total_out ?? 0, 2),
            ]
        ]);
    }

    /**
     * 额度转让详情
     * GET /app/admin/api/game/transfer-detail
     */
    public function transferDetail(Request $request)
    {
        $id = $request->get('id');
        if (!$id) {
            return json(['code' => 1, 'msg' => '参数错误']);
        }

        $item = Db::table('caipiao_game_transfer as t')
            ->leftJoin('caipiao_member as m', 't.uid', '=', 'm.id')
            ->select('t.*', 'm.username', 'm.nickname')
            ->where('t.id', $id)
            ->first();

        if (!$item) {
            return json(['code' => 1, 'msg' => '记录不存在']);
        }

        $row = (array)$item;
        $row['created_at_text'] = $row['created_at'] ? date('Y-m-d H:i:s', $row['created_at']) : '';

        return json(['code' => 0, 'msg' => 'ok', 'data' => $row]);
    }

    /**
     * 手动额度转让（管理员操作）
     * POST /app/admin/api/game/transfer-manual
     */
    public function transferManual(Request $request)
    {
        $uid = $request->post('uid');
        $username = $request->post('username');
        $platform = $request->post('platform');
        $type = $request->post('type'); // in=转入平台, out=转出平台
        $amount = floatval($request->post('amount', 0));

        // 根据用户名获取uid
        if (!$uid && $username) {
            $member = Db::table('caipiao_member')->where('username', $username)->first();
            if (!$member) {
                return json(['code' => 1, 'msg' => '用户不存在']);
            }
            $uid = $member->id;
        }

        if (!$uid) {
            return json(['code' => 1, 'msg' => '请指定用户']);
        }

        if (!$platform) {
            return json(['code' => 1, 'msg' => '请选择平台']);
        }

        if (!in_array($type, ['in', 'out'])) {
            return json(['code' => 1, 'msg' => '无效的转账类型']);
        }

        if ($amount <= 0) {
            return json(['code' => 1, 'msg' => '金额必须大于0']);
        }

        // 查询用户
        $member = Db::table('caipiao_member')->where('id', $uid)->first();
        if (!$member) {
            return json(['code' => 1, 'msg' => '用户不存在']);
        }

        // 查询平台
        $platformInfo = Db::table('caipiao_game_platform')->where('code', $platform)->first();
        if (!$platformInfo) {
            return json(['code' => 1, 'msg' => '平台不存在']);
        }

        Db::beginTransaction();
        try {
            $orderNo = 'GT' . date('YmdHis') . mt_rand(1000, 9999);
            $beforeBalance = $member->balance;
            $afterBalance = $beforeBalance;

            if ($type == 'in') {
                // 转入平台：从中心钱包扣款
                if ($beforeBalance < $amount) {
                    Db::rollBack();
                    return json(['code' => 1, 'msg' => '余额不足']);
                }
                $afterBalance = $beforeBalance - $amount;
                Db::table('caipiao_member')->where('id', $uid)->decrement('balance', $amount);
            } else {
                // 转出平台：转入中心钱包
                $afterBalance = $beforeBalance + $amount;
                Db::table('caipiao_member')->where('id', $uid)->increment('balance', $amount);
            }

            // 记录转账
            Db::table('caipiao_game_transfer')->insert([
                'order_no' => $orderNo,
                'uid' => $uid,
                'platform' => $platform,
                'type' => $type,
                'amount' => $amount,
                'before_balance' => $beforeBalance,
                'after_balance' => $afterBalance,
                'game_balance' => 0, // 实际需要调用第三方API获取
                'status' => 1, // 直接成功
                'created_at' => time(),
            ]);

            Db::commit();

            return json([
                'code' => 0,
                'msg' => '转账成功',
                'data' => [
                    'order_no' => $orderNo,
                    'before_balance' => $beforeBalance,
                    'after_balance' => $afterBalance,
                ]
            ]);
        } catch (\Exception $e) {
            Db::rollBack();
            return json(['code' => 1, 'msg' => '转账失败：' . $e->getMessage()]);
        }
    }

    /**
     * 额度转让类型选项
     * GET /app/admin/api/game/transfer-type-options
     */
    public function transferTypeOptions(Request $request)
    {
        $types = [
            ['value' => 'in', 'label' => '转入平台'],
            ['value' => 'out', 'label' => '转出平台'],
        ];

        return json(['code' => 0, 'msg' => 'ok', 'data' => $types]);
    }

    /**
     * 额度转让状态选项
     * GET /app/admin/api/game/transfer-status-options
     */
    public function transferStatusOptions(Request $request)
    {
        $statuses = [
            ['value' => 0, 'label' => '处理中'],
            ['value' => 1, 'label' => '成功'],
            ['value' => 2, 'label' => '失败'],
        ];

        return json(['code' => 0, 'msg' => 'ok', 'data' => $statuses]);
    }

    /**
     * 一键回收用户所有平台余额
     * POST /app/admin/api/game/transfer-recall-all
     */
    public function transferRecallAll(Request $request)
    {
        $uid = $request->post('uid');
        $username = $request->post('username');

        if (!$uid && $username) {
            $member = Db::table('caipiao_member')->where('username', $username)->first();
            if (!$member) {
                return json(['code' => 1, 'msg' => '用户不存在']);
            }
            $uid = $member->id;
        }

        if (!$uid) {
            return json(['code' => 1, 'msg' => '请指定用户']);
        }

        // TODO: 调用各平台API回收余额
        // 这里只是示意，实际需要对接各平台接口

        return json(['code' => 0, 'msg' => '一键回收已发起，请稍后查看结果']);
    }

    /**
     * 获取平台下的游戏列表
     * GET /app/admin/api/game/game-list
     */
    public function gameList(Request $request)
    {
        $platform = $request->get('platform', '');
        $keyword = $request->get('keyword', '');
        $page = max(1, (int)$request->get('page', 1));
        $limit = max(1, min(100, (int)$request->get('limit', 20)));

        $query = Db::table('caipiao_game');

        if (!empty($platform)) {
            $query->where('platform', $platform);
        }

        if (!empty($keyword)) {
            $query->where(function($q) use ($keyword) {
                $q->where('game_id', 'like', "%{$keyword}%")
                  ->orWhere('name', 'like', "%{$keyword}%");
            });
        }

        $total = $query->count();

        $list = $query->orderBy('sort', 'asc')
            ->orderBy('id', 'desc')
            ->offset(($page - 1) * $limit)
            ->limit($limit)
            ->get();

        $data = [];
        foreach ($list as $item) {
            $data[] = [
                'id' => $item->id,
                'game_id' => $item->game_id,
                'name' => $item->name,
                'platform' => $item->platform,
                'type' => $item->type,
                'icon' => $item->icon,
                'cover' => $item->cover,
                'hot' => $item->hot,
                'new' => $item->new ?? 0,
                'status' => $item->status,
                'sort' => $item->sort,
            ];
        }

        return json([
            'code' => 0,
            'msg' => 'success',
            'data' => [
                'list' => $data,
                'total' => $total
            ],
            'count' => $total
        ]);
    }

    /**
     * 保存游戏
     * POST /app/admin/api/game/game-save
     */
    public function gameSave(Request $request)
    {
        $id = $request->post('id', 0);
        $gameId = $request->post('game_id', '');
        $name = $request->post('name', '');
        $platform = $request->post('platform', '');
        $type = $request->post('type', '');
        $icon = $request->post('icon', '');
        $cover = $request->post('cover', '');
        $hot = (int)$request->post('hot', 0);
        $status = $request->post('status', 'online');
        $sort = (int)$request->post('sort', 0);

        if (empty($gameId) || empty($name) || empty($platform)) {
            return json(['code' => 1, 'msg' => '游戏ID、名称和平台不能为空']);
        }

        $data = [
            'game_id' => $gameId,
            'name' => $name,
            'platform' => $platform,
            'type' => $type,
            'icon' => $icon,
            'cover' => $cover,
            'hot' => $hot,
            'status' => $status,
            'sort' => $sort,
            'updated_at' => time(),
        ];

        if ($id) {
            Db::table('caipiao_game')->where('id', $id)->update($data);
        } else {
            $data['created_at'] = time();
            Db::table('caipiao_game')->insert($data);
        }

        return json(['code' => 0, 'msg' => '保存成功']);
    }

    /**
     * 设置游戏状态
     * POST /app/admin/api/game/game-status
     */
    public function gameStatus(Request $request)
    {
        $id = $request->post('id');
        $status = $request->post('status', 'online');

        if (!$id) {
            return json(['code' => 1, 'msg' => '参数错误']);
        }

        Db::table('caipiao_game')->where('id', $id)->update([
            'status' => $status,
            'updated_at' => time()
        ]);

        return json(['code' => 0, 'msg' => '操作成功']);
    }

    /**
     * 设置游戏热门状态
     * POST /app/admin/api/game/game-hot
     */
    public function gameHot(Request $request)
    {
        $id = $request->post('id');
        $hot = (int)$request->post('hot', 0);

        if (!$id) {
            return json(['code' => 1, 'msg' => '参数错误']);
        }

        Db::table('caipiao_game')->where('id', $id)->update([
            'hot' => $hot,
            'updated_at' => time()
        ]);

        return json(['code' => 0, 'msg' => '操作成功']);
    }

    /**
     * 删除游戏
     * POST /app/admin/api/game/game-delete
     */
    public function gameDelete(Request $request)
    {
        $id = $request->post('id');

        if (!$id) {
            return json(['code' => 1, 'msg' => '参数错误']);
        }

        Db::table('caipiao_game')->where('id', $id)->delete();

        return json(['code' => 0, 'msg' => '删除成功']);
    }
    
    /**
     * 同步 NG 平台到本地数据库
     * POST /app/admin/api/game/sync-ng-platforms
     */
    public function syncNGPlatforms(Request $request)
    {
        try {
            $service = new \app\service\GamePlatformService();
            $result = $service->syncNGPlatformsToDb();
            
            if ($result['success']) {
                return json(['code' => 0, 'msg' => $result['msg'], 'data' => ['count' => $result['count']]]);
            }
            
            return json(['code' => 1, 'msg' => $result['msg']]);
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => '同步失败: ' . $e->getMessage()]);
        }
    }
    
    /**
     * 同步 NG 游戏到本地数据库
     * POST /app/admin/api/game/sync-ng-games
     */
    public function syncNGGames(Request $request)
    {
        $platType = $request->post('platType', '');
        
        if (empty($platType)) {
            return json(['code' => 1, 'msg' => '请指定平台代码']);
        }
        
        try {
            $service = new \app\service\GamePlatformService();
            $result = $service->syncNGGames($platType);
            
            if ($result['success']) {
                return json([
                    'code' => 0, 
                    'msg' => $result['msg'], 
                    'data' => [
                        'count' => $result['count'],
                        'games' => $result['data'] ?? []
                    ]
                ]);
            }
            
            return json(['code' => 1, 'msg' => $result['msg']]);
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => '同步失败: ' . $e->getMessage()]);
        }
    }
    
    /**
     * 更新平台信息
     * POST /app/admin/api/game/update-platform
     */
    public function updatePlatform(Request $request)
    {
        $id = $request->post('id');
        
        // 记录请求日志
        \support\Log::info('updatePlatform请求', ['id' => $id, 'post' => $request->post()]);
        
        if (empty($id)) {
            return json(['code' => 1, 'msg' => '平台ID不能为空']);
        }
        
        $updateData = [];
        
        // 可更新的字段
        $allowedFields = ['name', 'slot_name', 'live_name', 'chess_name', 'fishing_name', 'icon', 'banner', 'mobile_icon', 'mobile_banner', 'slot_banner', 'live_banner', 'chess_banner', 'fishing_banner', 'sort', 'hot', 'status'];
        foreach ($allowedFields as $field) {
            $value = $request->post($field);
            if ($value !== null && $value !== '') {
                $updateData[$field] = $value;
            }
        }
        
        if (empty($updateData)) {
            return json(['code' => 1, 'msg' => '没有要更新的数据']);
        }
        
        try {
            Db::table('caipiao_game_platform')
                ->where('id', $id)
                ->update($updateData);
            
            return json(['code' => 0, 'msg' => '更新成功']);
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => '更新失败: ' . $e->getMessage()]);
        }
    }
    
    /**
     * 删除平台
     */
    public function deletePlatform(Request $request)
    {
        $id = $request->post('id');
        
        if (empty($id)) {
            return json(['code' => 1, 'msg' => '参数错误']);
        }
        
        try {
            // 先删除该平台下的所有游戏
            Db::table('caipiao_game')
                ->where('platform', function($query) use ($id) {
                    $query->select('code')->from('caipiao_game_platform')->where('id', $id);
                })
                ->delete();
            
            // 删除平台
            Db::table('caipiao_game_platform')
                ->where('id', $id)
                ->delete();
            
            return json(['code' => 0, 'msg' => '删除成功']);
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => '删除失败: ' . $e->getMessage()]);
        }
    }
}
