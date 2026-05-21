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
     * Lấy trò chơilịch sử列表
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
        
        // 直接Tra cứuĐặt cược表（老版本不做关联）
        $query = Db::table('caipiao_touzhu as t')
            ->select('t.*');
        
        // 排除机器人Đặt cượclịch sử
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
        
        // 状态筛选（空字符串hoặc999表示全部）
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
        
        // 调试：lịch sửSQL
        \support\Log::info('游戏记录查询条件: ' . json_encode([
            'cpname' => $cpname,
            'expect' => $expect,
            'orderno' => $orderno,
            'username' => $username,
            'status' => $status,
            'startTime' => $startTime,
            'endTime' => $endTime
        ], JSON_UNESCAPED_UNICODE));
        
        // Lấy总数
        $count = $query->count();
        
        \support\Log::info('游戏记录总数: ' . $count);
        
        // 分页
        $offset = ($page - 1) * $limit;
        $list = $query->offset($offset)->limit($limit)->get();
        
        // 处理dữ liệu
        $result = [];
        foreach ($list as $item) {
            $row = (array)$item;
            
            // 单号：trano
            $row['orderno'] = $row['trano'] ?? '';
            
            // Tên người dùng：username（直接在Đặt cược表）
            // 已有此字段
            
            // Xổ số名称：cptitle（直接在Đặt cược表）
            $row['lottery_name'] = $row['cptitle'] ?? '';
            
            // 玩法名称：playtitle
            $row['play_name'] = $row['playtitle'] ?? '';
            
            // 玩法ID：playid（用于前端判断玩法类型）
            $row['play_id'] = $row['playid'] ?? '';
            
            // 注数：itemcount
            $row['betcount'] = $row['itemcount'] ?? 0;
            
            // Tiền thưởng/赔率：mode
            $row['odds'] = $row['mode'] ?? 0;
            
            // Đặt cược后Số tiền：amountafter
            $row['after_balance'] = $row['amountafter'] ?? 0;
            
            // Trúng thưởngSố tiền：okamount
            $row['winamount'] = $row['okamount'] ?? 0;
            
            // Trúng thưởng注数：okcount
            $row['wincount'] = $row['okcount'] ?? 0;
            
            // Trúng thưởng倍数：beishu
            $row['multiple'] = $row['beishu'] ?? 0;
            
            // 元角分：yjf
            $row['mode_text'] = $row['yjf'] ?? 1;
            
            // 号码：tzcode
            $row['betcontent'] = $row['tzcode'] ?? '';
            
            // Mở thưởng号：opencode
            // 已有此字段
            
            // 格式化Đặt cượcThời gian
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
                $row['status_text'] = 'Trúng thưởng';
            } elseif ($row['isdraw'] == -1) {
                $row['status_text'] = '未Trúng thưởng';
            } elseif ($row['isdraw'] == -2) {
                $row['status_text'] = '撤单';
            } else {
                $row['status_text'] = '未Mở thưởng';
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
            return $this->json(1, 'Tham số không hợp lệ');
        }
        
        // Tra cứuĐặt cượclịch sử
        $bet = Db::table('caipiao_touzhu')->where('id', $id)->first();
        
        if (!$bet) {
            return $this->json(1, 'Đặt cượclịch sửkhông tồn tại');
        }
        
        // 检查是否已Mở thưởng
        if ($bet->isdraw != 0) {
            return $this->json(1, '该订单已Mở thưởng，无法撤单');
        }
        
        // 更新状态为撤单
        $result = Db::table('caipiao_touzhu')
            ->where('id', $id)
            ->update(['isdraw' => -2]);
        
        if ($result !== false) {
            // 退还Số tiền给Người dùng
            Db::table('caipiao_member')
                ->where('id', $bet->uid)
                ->increment('balance', $bet->amount);
            
            \support\Log::info('撤单Thành công - 订单ID: ' . $id . ', Số tiền: ' . $bet->amount);
            return $this->json(0, '撤单Thành công');
        } else {
            return $this->json(1, '撤单Thất bại');
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
     * Lấy注单异常检测列表
     */
    public function checkAnomalyOrderList(Request $request)
    {
        $cpname = $request->get('cpname', '');
        $username = $request->get('username', '');
        $shijiancha = $request->get('shijiancha', 130); // 默认130秒
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 20);
        
        // 核心SQL：Tra cứuĐặt cượcThời gian和Mở thưởngThời gian差异小于等于指定值的注单
        // 这些注单可能存在异常（在Mở thưởng前很短Thời gian内Đặt cược）
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
            // 排除机器人Đặt cượclịch sử
            ->where(function($q) {
                $q->whereNull('a.source')
                  ->orWhere('a.source', '!=', 'robot');
            });
        
        // 彩种筛选
        if (!empty($cpname)) {
            $query->where('a.cpname', $cpname);
        }
        
        // Tên người dùng筛选
        if (!empty($username)) {
            $query->where('a.username', 'like', '%' . $username . '%');
        }
        
        // Thời gian差筛选：Mở thưởngThời gian - Đặt cượcThời gian <= 指定秒数
        if ($shijiancha > 0) {
            $query->whereRaw('b.opentime - a.oddtime <= ?', [$shijiancha]);
        }
        
        // 按ID降序
        $query->orderBy('a.id', 'desc');
        
        // Lấy总数
        $count = $query->count();
        
        // 分页
        $offset = ($page - 1) * $limit;
        $list = $query->offset($offset)->limit($limit)->get();
        
        // 处理dữ liệu
        $result = [];
        foreach ($list as $item) {
            $row = (array)$item;
            
            // 单号
            $row['orderno'] = $row['trano'] ?? '';
            
            // Tên người dùng
            // 已有 username 字段
            
            // Xổ số名称
            $row['lottery_name'] = $row['cptitle'] ?? '';
            
            // 期号
            // 已有 expect 字段
            
            // 玩法
            $row['play_name'] = $row['playtitle'] ?? '';
            
            // Đặt cượcSố tiền
            // 已有 amount 字段
            
            // Trúng thưởngSố tiền
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
                $row['status_text'] = '未Mở thưởng';
                $row['status_class'] = 'c-333';
            }
            
            // 格式化Đặt cượcThời gian
            if (isset($row['oddtime']) && is_numeric($row['oddtime'])) {
                $row['oddtime_text'] = date('m-d H:i:s', $row['oddtime']);
            }
            
            // 格式化Mở thưởngThời gian
            if (isset($row['opentime']) && is_numeric($row['opentime'])) {
                $row['opentime_text'] = date('m-d H:i:s', $row['opentime']);
            }
            
            // 计算Thời gian差（秒）
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
     * nền tảng trò chơi管理 API（Art Design Pro 前端专用）
     * ============================================
     */

    /**
     * Trò chơidanh sách nền tảng
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
            // Xổ số、Thể thao、电竞等直接进入大厅的类型：根据平台表的 type 筛选
            $directTypes = ['lottery', 'sport', 'esport'];
            if (in_array($type, $directTypes)) {
                $query->where('type', $type);
            } else {
                // 其他类型：根据Trò chơi表的类型筛选（支持综合平台多分类显示）
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
                    // 没有Trò chơi的分类，回退到按平台类型筛选
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
            'live' => 'Live Casino视讯',
            'slot' => 'Điện tửTrò chơi',
            'chess' => 'BàiTrò chơi',
            'sport' => 'Thể thaoĐặt cược',
            'lottery' => 'Xổ sốTrò chơi',
            'fishing' => 'Bắn cáTrò chơi',
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
     * nền tảng trò chơiChi tiết
     * GET /app/admin/api/game/platform-detail
     */
    public function platformDetail(Request $request)
    {
        $id = $request->get('id');
        if (!$id) {
            return json(['code' => 1, 'msg' => 'Tham số không hợp lệ']);
        }

        $item = Db::table('caipiao_game_platform')->where('id', $id)->first();
        if (!$item) {
            return json(['code' => 1, 'msg' => '平台không tồn tại']);
        }

        return json(['code' => 0, 'msg' => 'ok', 'data' => (array)$item]);
    }

    /**
     * Thêmnền tảng trò chơi
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
            return json(['code' => 1, 'msg' => '平台代码không được để trống']);
        }

        if (!$name) {
            return json(['code' => 1, 'msg' => '平台名称không được để trống']);
        }

        // 检查代码是否đã tồn tại
        $exists = Db::table('caipiao_game_platform')->where('code', $code)->exists();
        if ($exists) {
            return json(['code' => 1, 'msg' => '平台代码đã tồn tại']);
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

        return json(['code' => 0, 'msg' => 'ThêmThành công', 'data' => ['id' => $id]]);
    }

    /**
     * 编辑nền tảng trò chơi
     * POST /app/admin/api/game/platform-edit
     */
    public function platformEdit(Request $request)
    {
        $id = $request->post('id');
        if (!$id) {
            return json(['code' => 1, 'msg' => 'Tham số không hợp lệ']);
        }

        $item = Db::table('caipiao_game_platform')->where('id', $id)->first();
        if (!$item) {
            return json(['code' => 1, 'msg' => '平台không tồn tại']);
        }

        $data = ['updated_at' => time()];

        $code = $request->post('code');
        if ($code !== null) {
            $code = strtoupper(trim($code));
            // 检查代码是否被其他lịch sử使用
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

        return json(['code' => 0, 'msg' => 'SửaThành công']);
    }

    /**
     * Xóanền tảng trò chơi
     * POST /app/admin/api/game/platform-delete
     */
    public function platformDelete(Request $request)
    {
        $id = $request->post('id');
        $ids = $request->post('ids', []);

        if (!$id && empty($ids)) {
            return json(['code' => 1, 'msg' => 'Tham số không hợp lệ']);
        }

        $deleteIds = $id ? [$id] : $ids;

        // 检查是否有Trò chơi使用该平台
        $codes = Db::table('caipiao_game_platform')
            ->whereIn('id', $deleteIds)
            ->pluck('code')
            ->toArray();

        $usedCount = Db::table('caipiao_game')
            ->whereIn('platform', $codes)
            ->count();

        if ($usedCount > 0) {
            return json(['code' => 1, 'msg' => '该平台下有Trò chơi，无法Xóa']);
        }

        Db::table('caipiao_game_platform')->whereIn('id', $deleteIds)->delete();

        return json(['code' => 0, 'msg' => 'XóaThành công']);
    }

    /**
     * 更新nền tảng trò chơi状态
     * POST /app/admin/api/game/platform-status
     */
    public function platformStatus(Request $request)
    {
        $id = $request->post('id');
        $status = $request->post('status');

        if (!$id || !$status) {
            return json(['code' => 1, 'msg' => 'Tham số không hợp lệ']);
        }

        $allowedStatus = ['online', 'offline', 'maintenance'];
        if (!in_array($status, $allowedStatus)) {
            return json(['code' => 1, 'msg' => '无效的状态值']);
        }

        Db::table('caipiao_game_platform')->where('id', $id)->update([
            'status' => $status,
            'updated_at' => time(),
        ]);

        return json(['code' => 0, 'msg' => '状态更新Thành công']);
    }

    /**
     * Tra cứu平台Số dư（模拟）
     * GET /app/admin/api/game/platform-balance
     */
    public function platformBalance(Request $request)
    {
        $id = $request->get('id');
        $code = $request->get('code');

        if (!$id && !$code) {
            return json(['code' => 1, 'msg' => 'Tham số không hợp lệ']);
        }

        // Tra cứu平台
        $query = Db::table('caipiao_game_platform');
        if ($id) {
            $query->where('id', $id);
        } else {
            $query->where('code', $code);
        }
        $platform = $query->first();

        if (!$platform) {
            return json(['code' => 1, 'msg' => '平台không tồn tại']);
        }

        // 从 caipiao_game_balance 表Tra cứuSố dư
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
     * Tra cứu所有平台Số dư
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
            ['value' => 'live', 'label' => 'Live Casino视讯'],
            ['value' => 'slot', 'label' => 'Điện tửTrò chơi'],
            ['value' => 'chess', 'label' => 'BàiTrò chơi'],
            ['value' => 'sport', 'label' => 'Thể thaoĐặt cược'],
            ['value' => 'lottery', 'label' => 'Xổ sốTrò chơi'],
            ['value' => 'fishing', 'label' => 'Bắn cáTrò chơi'],
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
     * 第三方Trò chơiĐặt cượclịch sử API
     * ============================================
     */

    /**
     * Đặt cượclịch sử列表
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
            'cancelled' => 'Đã hủy',
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
     * Đặt cượclịch sửChi tiết
     * GET /app/admin/api/game/bet-detail
     */
    public function betDetail(Request $request)
    {
        $id = $request->get('id');
        if (!$id) {
            return json(['code' => 1, 'msg' => 'Tham số không hợp lệ']);
        }

        $item = Db::table('caipiao_game_bet as b')
            ->leftJoin('caipiao_member as m', 'b.uid', '=', 'm.id')
            ->select('b.*', 'm.username', 'm.nickname')
            ->where('b.id', $id)
            ->first();

        if (!$item) {
            return json(['code' => 1, 'msg' => 'lịch sửkhông tồn tại']);
        }

        $row = (array)$item;
        $row['bet_time_text'] = $row['bet_time'] ? date('Y-m-d H:i:s', $row['bet_time']) : '';
        $row['settle_time_text'] = $row['settle_time'] ? date('Y-m-d H:i:s', $row['settle_time']) : '';

        return json(['code' => 0, 'msg' => 'ok', 'data' => $row]);
    }

    /**
     * Đặt cược状态选项
     * GET /app/admin/api/game/bet-status-options
     */
    public function betStatusOptions(Request $request)
    {
        $statuses = [
            ['value' => 'pending', 'label' => '未结算'],
            ['value' => 'settled', 'label' => '已结算'],
            ['value' => 'cancelled', 'label' => 'Đã hủy'],
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
        $statusMap = [0 => 'Đang xử lý', 1 => 'Thành công', 2 => 'Thất bại'];

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
     * 额度转让Chi tiết
     * GET /app/admin/api/game/transfer-detail
     */
    public function transferDetail(Request $request)
    {
        $id = $request->get('id');
        if (!$id) {
            return json(['code' => 1, 'msg' => 'Tham số không hợp lệ']);
        }

        $item = Db::table('caipiao_game_transfer as t')
            ->leftJoin('caipiao_member as m', 't.uid', '=', 'm.id')
            ->select('t.*', 'm.username', 'm.nickname')
            ->where('t.id', $id)
            ->first();

        if (!$item) {
            return json(['code' => 1, 'msg' => 'lịch sửkhông tồn tại']);
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

        // 根据Tên người dùngLấyuid
        if (!$uid && $username) {
            $member = Db::table('caipiao_member')->where('username', $username)->first();
            if (!$member) {
                return json(['code' => 1, 'msg' => 'Người dùng không tồn tại']);
            }
            $uid = $member->id;
        }

        if (!$uid) {
            return json(['code' => 1, 'msg' => '请指定Người dùng']);
        }

        if (!$platform) {
            return json(['code' => 1, 'msg' => 'Vui lòng chọn平台']);
        }

        if (!in_array($type, ['in', 'out'])) {
            return json(['code' => 1, 'msg' => '无效的Chuyển khoản类型']);
        }

        if ($amount <= 0) {
            return json(['code' => 1, 'msg' => 'Số tiền必须大于0']);
        }

        // Tra cứuNgười dùng
        $member = Db::table('caipiao_member')->where('id', $uid)->first();
        if (!$member) {
            return json(['code' => 1, 'msg' => 'Người dùng không tồn tại']);
        }

        // Tra cứu平台
        $platformInfo = Db::table('caipiao_game_platform')->where('code', $platform)->first();
        if (!$platformInfo) {
            return json(['code' => 1, 'msg' => '平台không tồn tại']);
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
                    return json(['code' => 1, 'msg' => 'Số dư không đủ']);
                }
                $afterBalance = $beforeBalance - $amount;
                Db::table('caipiao_member')->where('id', $uid)->decrement('balance', $amount);
            } else {
                // 转出平台：转入中心钱包
                $afterBalance = $beforeBalance + $amount;
                Db::table('caipiao_member')->where('id', $uid)->increment('balance', $amount);
            }

            // lịch sửChuyển khoản
            Db::table('caipiao_game_transfer')->insert([
                'order_no' => $orderNo,
                'uid' => $uid,
                'platform' => $platform,
                'type' => $type,
                'amount' => $amount,
                'before_balance' => $beforeBalance,
                'after_balance' => $afterBalance,
                'game_balance' => 0, // 实际需要调用第三方APILấy
                'status' => 1, // 直接Thành công
                'created_at' => time(),
            ]);

            Db::commit();

            return json([
                'code' => 0,
                'msg' => 'Chuyển khoảnThành công',
                'data' => [
                    'order_no' => $orderNo,
                    'before_balance' => $beforeBalance,
                    'after_balance' => $afterBalance,
                ]
            ]);
        } catch (\Exception $e) {
            Db::rollBack();
            return json(['code' => 1, 'msg' => 'Chuyển khoảnThất bại：' . $e->getMessage()]);
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
            ['value' => 0, 'label' => 'Đang xử lý'],
            ['value' => 1, 'label' => 'Thành công'],
            ['value' => 2, 'label' => 'Thất bại'],
        ];

        return json(['code' => 0, 'msg' => 'ok', 'data' => $statuses]);
    }

    /**
     * 一键回收Người dùng所有平台Số dư
     * POST /app/admin/api/game/transfer-recall-all
     */
    public function transferRecallAll(Request $request)
    {
        $uid = $request->post('uid');
        $username = $request->post('username');

        if (!$uid && $username) {
            $member = Db::table('caipiao_member')->where('username', $username)->first();
            if (!$member) {
                return json(['code' => 1, 'msg' => 'Người dùng không tồn tại']);
            }
            $uid = $member->id;
        }

        if (!$uid) {
            return json(['code' => 1, 'msg' => '请指定Người dùng']);
        }

        // TODO: 调用各平台API回收Số dư
        // 这里只是示意，实际需要对接各平台接口

        return json(['code' => 0, 'msg' => '一键回收已发起，请稍后查看结果']);
    }

    /**
     * Lấy平台下的Trò chơi列表
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
     * LưuTrò chơi
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
            return json(['code' => 1, 'msg' => 'Trò chơiID、名称和平台không được để trống']);
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

        return json(['code' => 0, 'msg' => 'LưuThành công']);
    }

    /**
     * Cài đặtTrò chơi状态
     * POST /app/admin/api/game/game-status
     */
    public function gameStatus(Request $request)
    {
        $id = $request->post('id');
        $status = $request->post('status', 'online');

        if (!$id) {
            return json(['code' => 1, 'msg' => 'Tham số không hợp lệ']);
        }

        Db::table('caipiao_game')->where('id', $id)->update([
            'status' => $status,
            'updated_at' => time()
        ]);

        return json(['code' => 0, 'msg' => 'Thao tác thành công']);
    }

    /**
     * Cài đặtTrò chơi热门状态
     * POST /app/admin/api/game/game-hot
     */
    public function gameHot(Request $request)
    {
        $id = $request->post('id');
        $hot = (int)$request->post('hot', 0);

        if (!$id) {
            return json(['code' => 1, 'msg' => 'Tham số không hợp lệ']);
        }

        Db::table('caipiao_game')->where('id', $id)->update([
            'hot' => $hot,
            'updated_at' => time()
        ]);

        return json(['code' => 0, 'msg' => 'Thao tác thành công']);
    }

    /**
     * XóaTrò chơi
     * POST /app/admin/api/game/game-delete
     */
    public function gameDelete(Request $request)
    {
        $id = $request->post('id');

        if (!$id) {
            return json(['code' => 1, 'msg' => 'Tham số không hợp lệ']);
        }

        Db::table('caipiao_game')->where('id', $id)->delete();

        return json(['code' => 0, 'msg' => 'XóaThành công']);
    }
    
    /**
     * 同步 NG 平台到本地dữ liệu库
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
            return json(['code' => 1, 'msg' => '同步Thất bại: ' . $e->getMessage()]);
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
            return json(['code' => 1, 'msg' => '同步Thất bại: ' . $e->getMessage()]);
        }
    }
    
    /**
     * 更新平台信息
     * POST /app/admin/api/game/update-platform
     */
    public function updatePlatform(Request $request)
    {
        $id = $request->post('id');
        
        // lịch sử请求日志
        \support\Log::info('updatePlatform请求', ['id' => $id, 'post' => $request->post()]);
        
        if (empty($id)) {
            return json(['code' => 1, 'msg' => '平台IDkhông được để trống']);
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
            return json(['code' => 1, 'msg' => '没有要更新的dữ liệu']);
        }
        
        try {
            Db::table('caipiao_game_platform')
                ->where('id', $id)
                ->update($updateData);
            
            return json(['code' => 0, 'msg' => '更新Thành công']);
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => '更新Thất bại: ' . $e->getMessage()]);
        }
    }
    
    /**
     * 删除平台
     */
    public function deletePlatform(Request $request)
    {
        $id = $request->post('id');
        
        if (empty($id)) {
            return json(['code' => 1, 'msg' => 'Tham số không hợp lệ']);
        }
        
        try {
            // 先Xóa该平台下的所有Trò chơi
            Db::table('caipiao_game')
                ->where('platform', function($query) use ($id) {
                    $query->select('code')->from('caipiao_game_platform')->where('id', $id);
                })
                ->delete();
            
            // Xóa平台
            Db::table('caipiao_game_platform')
                ->where('id', $id)
                ->delete();
            
            return json(['code' => 0, 'msg' => 'XóaThành công']);
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => 'XóaThất bại: ' . $e->getMessage()]);
        }
    }
}
