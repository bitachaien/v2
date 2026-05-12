<?php

namespace plugin\admin\app\controller;

use support\Request;
use support\Db;
use support\Log;

/**
 * 运维管理 API 控制器 (Art Design Pro 前端专用)
 */
class MaintenanceApiController extends Base
{
    /**
     * 不需要鉴权的方法
     */
    protected $noNeedAuth = [];

    /**
     * 清理类型配置
     */
    private $cleanTypes = [
        'user_inactive' => [
            'name' => '长期未登录且余额低会员',
            'table' => 'caipiao_member',
        ],
        'user_register' => [
            'name' => '注册后长期未登录',
            'table' => 'caipiao_member',
        ],
        'user_test' => [
            'name' => '内部测试账户',
            'table' => 'caipiao_member',
        ],
        'lottery_result' => [
            'name' => '开奖数据',
            'table' => 'caipiao_kaijiang',
        ],
        'bet_record' => [
            'name' => '投注记录',
            'table' => 'caipiao_touzhu',
        ],
        'recharge_record' => [
            'name' => '充值记录',
            'table' => 'caipiao_chongzhi',
        ],
        'withdraw_record' => [
            'name' => '提现记录',
            'table' => 'caipiao_tikuan',
        ],
        'balance_log' => [
            'name' => '账变记录',
            'table' => 'caipiao_fuddetail',
        ],
        'member_log' => [
            'name' => '会员日志',
            'table' => 'caipiao_memberlog',
        ],
        'admin_log' => [
            'name' => '管理员日志',
            'table' => 'wa_admin_log',
        ],
    ];

    /**
     * 执行数据清理
     * POST /app/admin/api/maintenance/clear-data
     */
    public function clearData(Request $request)
    {
        $type = $request->post('type', '');
        $days = (int)$request->post('days', 30);
        $amount = (float)$request->post('amount', 1);
        $state = $request->post('state', 999);
        $isInner = (int)$request->post('is_inner', 0);

        // 验证清理类型
        if (empty($type) || !isset($this->cleanTypes[$type])) {
            return $this->json(1, '无效的清理类型');
        }

        if ($days < 1) {
            return $this->json(1, '天数必须大于0');
        }

        $config = $this->cleanTypes[$type];
        $cutoffTime = time() - ($days * 86400);
        $count = 0;

        try {
            Db::beginTransaction();

            switch ($type) {
                case 'user_inactive':
                    // 长期未登录且余额低会员
                    $count = Db::table('caipiao_member')
                        ->where('lastlogintime', '<', $cutoffTime)
                        ->where('balance', '<=', $amount)
                        ->where('isnb', 0) // 排除机器人
                        ->delete();
                    break;

                case 'user_register':
                    // 注册后长期未登录（从未登录或注册后未登录）
                    $count = Db::table('caipiao_member')
                        ->where('regtime', '<', $cutoffTime)
                        ->where(function($query) {
                            $query->whereNull('lastlogintime')
                                  ->orWhere('lastlogintime', 0);
                        })
                        ->where('isnb', 0)
                        ->delete();
                    break;

                case 'user_test':
                    // 内部测试账户
                    $query = Db::table('caipiao_member')
                        ->where('regtime', '<', $cutoffTime);
                    if ($isInner == 1) {
                        $query->where('isnb', 1); // 只删除机器人/测试账户
                    }
                    $count = $query->delete();
                    break;

                case 'lottery_result':
                    // 开奖数据
                    $count = Db::table('caipiao_kaijiang')
                        ->where('addtime', '<', $cutoffTime)
                        ->delete();
                    break;

                case 'bet_record':
                    // 投注记录
                    $query = Db::table('caipiao_touzhu')
                        ->where('oddtime', '<', $cutoffTime);
                    if ($state != 999) {
                        $query->where('isdraw', $state);
                    }
                    $count = $query->delete();
                    break;

                case 'recharge_record':
                    // 充值记录
                    $query = Db::table('caipiao_chongzhi')
                        ->where('addtime', '<', $cutoffTime);
                    if ($state != 999) {
                        $query->where('status', $state);
                    }
                    $count = $query->delete();
                    break;

                case 'withdraw_record':
                    // 提现记录
                    $query = Db::table('caipiao_tikuan')
                        ->where('addtime', '<', $cutoffTime);
                    if ($state != 999) {
                        $query->where('status', $state);
                    }
                    $count = $query->delete();
                    break;

                case 'balance_log':
                    // 账变记录
                    $count = Db::table('caipiao_fuddetail')
                        ->where('addtime', '<', $cutoffTime)
                        ->delete();
                    break;

                case 'member_log':
                    // 会员日志
                    $count = Db::table('caipiao_memberlog')
                        ->where('addtime', '<', $cutoffTime)
                        ->delete();
                    break;

                case 'admin_log':
                    // 管理员日志
                    $count = Db::table('wa_admin_log')
                        ->where('created_at', '<', date('Y-m-d H:i:s', $cutoffTime))
                        ->delete();
                    break;
            }

            Db::commit();

            // 记录操作日志
            $admin = admin();
            Log::info("数据清理操作", [
                'admin' => $admin['username'] ?? 'unknown',
                'type' => $type,
                'type_name' => $config['name'],
                'days' => $days,
                'deleted_count' => $count,
            ]);

            return $this->json(0, "清理完成，共删除 {$count} 条{$config['name']}", [
                'deleted_count' => $count,
            ]);

        } catch (\Exception $e) {
            Db::rollBack();
            Log::error("数据清理失败: " . $e->getMessage());
            return $this->json(1, '清理失败: ' . $e->getMessage());
        }
    }

    /**
     * 获取清理类型选项
     * GET /app/admin/api/maintenance/clear-type-options
     */
    public function clearTypeOptions(Request $request)
    {
        $options = [];
        foreach ($this->cleanTypes as $key => $config) {
            $options[] = [
                'value' => $key,
                'label' => $config['name'],
            ];
        }
        return $this->json(0, 'ok', $options);
    }

    /**
     * 获取数据统计（预览清理数量）
     * GET /app/admin/api/maintenance/preview-count
     */
    public function previewCount(Request $request)
    {
        $type = $request->get('type', '');
        $days = (int)$request->get('days', 30);
        $amount = (float)$request->get('amount', 1);
        $state = $request->get('state', 999);
        $isInner = (int)$request->get('is_inner', 0);

        if (empty($type) || !isset($this->cleanTypes[$type])) {
            return $this->json(1, '无效的清理类型');
        }

        $cutoffTime = time() - ($days * 86400);
        $count = 0;

        try {
            switch ($type) {
                case 'user_inactive':
                    $count = Db::table('caipiao_member')
                        ->where('lastlogintime', '<', $cutoffTime)
                        ->where('balance', '<=', $amount)
                        ->where('isnb', 0)
                        ->count();
                    break;

                case 'user_register':
                    $count = Db::table('caipiao_member')
                        ->where('regtime', '<', $cutoffTime)
                        ->where(function($query) {
                            $query->whereNull('lastlogintime')
                                  ->orWhere('lastlogintime', 0);
                        })
                        ->where('isnb', 0)
                        ->count();
                    break;

                case 'user_test':
                    $query = Db::table('caipiao_member')
                        ->where('regtime', '<', $cutoffTime);
                    if ($isInner == 1) {
                        $query->where('isnb', 1);
                    }
                    $count = $query->count();
                    break;

                case 'lottery_result':
                    $count = Db::table('caipiao_kaijiang')
                        ->where('addtime', '<', $cutoffTime)
                        ->count();
                    break;

                case 'bet_record':
                    $query = Db::table('caipiao_touzhu')
                        ->where('oddtime', '<', $cutoffTime);
                    if ($state != 999) {
                        $query->where('isdraw', $state);
                    }
                    $count = $query->count();
                    break;

                case 'recharge_record':
                    $query = Db::table('caipiao_chongzhi')
                        ->where('addtime', '<', $cutoffTime);
                    if ($state != 999) {
                        $query->where('status', $state);
                    }
                    $count = $query->count();
                    break;

                case 'withdraw_record':
                    $query = Db::table('caipiao_tikuan')
                        ->where('addtime', '<', $cutoffTime);
                    if ($state != 999) {
                        $query->where('status', $state);
                    }
                    $count = $query->count();
                    break;

                case 'balance_log':
                    $count = Db::table('caipiao_fuddetail')
                        ->where('addtime', '<', $cutoffTime)
                        ->count();
                    break;

                case 'member_log':
                    $count = Db::table('caipiao_memberlog')
                        ->where('addtime', '<', $cutoffTime)
                        ->count();
                    break;

                case 'admin_log':
                    $count = Db::table('wa_admin_log')
                        ->where('created_at', '<', date('Y-m-d H:i:s', $cutoffTime))
                        ->count();
                    break;
            }

            return $this->json(0, 'ok', [
                'count' => $count,
                'type_name' => $this->cleanTypes[$type]['name'],
            ]);

        } catch (\Exception $e) {
            return $this->json(1, '查询失败: ' . $e->getMessage());
        }
    }

    /**
     * 获取状态选项（用于投注/充值/提现记录）
     * GET /app/admin/api/maintenance/state-options
     */
    public function stateOptions(Request $request)
    {
        $type = $request->get('type', '');

        $options = [
            ['value' => 999, 'label' => '全部'],
        ];

        switch ($type) {
            case 'bet_record':
                $options = array_merge($options, [
                    ['value' => 0, 'label' => '未开奖'],
                    ['value' => 1, 'label' => '已中奖'],
                    ['value' => -1, 'label' => '未中奖'],
                    ['value' => -2, 'label' => '已撤单'],
                ]);
                break;

            case 'recharge_record':
            case 'withdraw_record':
                $options = array_merge($options, [
                    ['value' => 0, 'label' => '待处理'],
                    ['value' => 1, 'label' => '已完成'],
                    ['value' => -1, 'label' => '已拒绝'],
                ]);
                break;
        }

        return $this->json(0, 'ok', $options);
    }

    /**
     * ============================================
     * 公告管理
     * ============================================
     */

    /**
     * 获取公告列表
     * GET /app/admin/api/maintenance/notice/list
     */
    public function noticeList(Request $request)
    {
        $page = (int)$request->get('page', 1);
        $limit = (int)$request->get('limit', 20);
        $title = $request->get('title', '');
        $type = $request->get('type', '');

        $query = Db::table('caipiao_gonggao');

        // 标题模糊搜索
        if (!empty($title)) {
            $query->where('title', 'like', "%{$title}%");
        }

        // 类型筛选
        if (!empty($type)) {
            $query->where('type', $type);
        }

        $total = $query->count();

        $list = $query->orderBy('id', 'desc')
            ->offset(($page - 1) * $limit)
            ->limit($limit)
            ->get();

        $result = [];
        foreach ($list as $item) {
            $item = (array)$item;
            $result[] = [
                'id' => $item['id'],
                'title' => $item['title'],
                'content' => $item['content'],
                'type' => $item['type'] ?? 'system',
                'add_time' => isset($item['oddtime']) ? date('Y-m-d H:i:s', $item['oddtime']) : '',
            ];
        }

        return $this->json(0, 'success', [
            'total' => $total,
            'list' => $result,
        ]);
    }

    /**
     * 创建公告
     * POST /app/admin/api/maintenance/notice/create
     */
    public function noticeCreate(Request $request)
    {
        $title = $request->post('title', '');
        $content = $request->post('content', '');
        $type = $request->post('type', 'system');

        if (empty($title)) {
            return $this->json(1, '公告标题不能为空');
        }

        if (empty($content)) {
            return $this->json(1, '公告内容不能为空');
        }

        try {
            $id = Db::table('caipiao_gonggao')->insertGetId([
                'title' => $title,
                'content' => $content,
                'type' => $type,
                'oddtime' => time(),
            ]);

            return $this->json(0, '创建成功', ['id' => $id]);

        } catch (\Exception $e) {
            Log::error("创建公告失败: " . $e->getMessage());
            return $this->json(1, '创建失败: ' . $e->getMessage());
        }
    }

    /**
     * 更新公告
     * POST /app/admin/api/maintenance/notice/update
     */
    public function noticeUpdate(Request $request)
    {
        $id = $request->post('id');
        $title = $request->post('title', '');
        $content = $request->post('content', '');
        $type = $request->post('type', '');

        if (empty($id)) {
            return $this->json(1, '公告ID不能为空');
        }

        // 检查公告是否存在
        $notice = Db::table('caipiao_gonggao')->where('id', $id)->first();
        if (!$notice) {
            return $this->json(1, '公告不存在');
        }

        $updateData = [];
        if (!empty($title)) {
            $updateData['title'] = $title;
        }
        if (!empty($content)) {
            $updateData['content'] = $content;
        }
        if (!empty($type)) {
            $updateData['type'] = $type;
        }

        if (empty($updateData)) {
            return $this->json(1, '没有需要更新的内容');
        }

        try {
            Db::table('caipiao_gonggao')
                ->where('id', $id)
                ->update($updateData);

            return $this->json(0, '更新成功');

        } catch (\Exception $e) {
            Log::error("更新公告失败: " . $e->getMessage());
            return $this->json(1, '更新失败: ' . $e->getMessage());
        }
    }

    /**
     * 删除公告
     * POST /app/admin/api/maintenance/notice/delete
     */
    public function noticeDelete(Request $request)
    {
        $id = $request->post('id');

        if (empty($id)) {
            return $this->json(1, '公告ID不能为空');
        }

        // 检查公告是否存在
        $notice = Db::table('caipiao_gonggao')->where('id', $id)->first();
        if (!$notice) {
            return $this->json(1, '公告不存在');
        }

        try {
            // 删除公告
            Db::table('caipiao_gonggao')->where('id', $id)->delete();
            // 同时删除已读记录
            Db::table('caipiao_notice_read')->where('notice_id', $id)->delete();

            return $this->json(0, '删除成功');

        } catch (\Exception $e) {
            Log::error("删除公告失败: " . $e->getMessage());
            return $this->json(1, '删除失败: ' . $e->getMessage());
        }
    }

    /**
     * 获取公告类型选项
     * GET /app/admin/api/maintenance/notice/type-options
     */
    public function noticeTypeOptions(Request $request)
    {
        $options = [
            ['value' => 'system', 'label' => '系统公告'],
            ['value' => 'activity', 'label' => '活动公告'],
            ['value' => 'notice', 'label' => '通知公告'],
        ];
        return $this->json(0, 'ok', $options);
    }

    /**
     * ============================================
     * 计划任务管理
     * ============================================
     */

    /**
     * 计划任务配置
     */
    private $taskConfig = [
        // 活动任务（时间配置）
        'jihua_rixiaofei' => [
            'name' => '日消费赠送活动',
            'remark' => '赠送前一天消费达标用户',
            'type' => 'time',
        ],
        'jihua_rikuisun' => [
            'name' => '日亏损赠送活动',
            'remark' => '补偿前一天亏损用户',
            'type' => 'time',
        ],
        'jihua_yuexiaofei' => [
            'name' => '月消费赠送活动',
            'remark' => '每月1号执行，赠送上月消费达标用户',
            'type' => 'time',
        ],
        'jihua_yuekuisun' => [
            'name' => '月亏损赠送活动',
            'remark' => '每月1号执行，补偿上月亏损用户',
            'type' => 'time',
        ],
        'jihua_dailifandian' => [
            'name' => '代理返点结算',
            'remark' => '结算代理返点',
            'type' => 'time',
        ],
        // 数据清理任务（天数配置）
        'jihua_kaijiang' => [
            'name' => '开奖数据清理',
            'remark' => '清理N天前的开奖数据',
            'type' => 'days',
        ],
        'jihua_touzhu' => [
            'name' => '投注数据清理',
            'remark' => '清理N天前的投注记录',
            'type' => 'days',
        ],
        'jihua_fuddetail' => [
            'name' => '账变数据清理',
            'remark' => '清理N天前的账变记录',
            'type' => 'days',
        ],
        'jihua_memlog' => [
            'name' => '会员日志清理',
            'remark' => '清理N天前的会员日志',
            'type' => 'days',
        ],
        'jihua_adminlog' => [
            'name' => '管理员日志清理',
            'remark' => '清理N天前的管理员日志',
            'type' => 'days',
        ],
        'jihua_fandian' => [
            'name' => '返点数据清理',
            'remark' => '清理N天前的返点数据',
            'type' => 'days',
        ],
        'jihua_jinjijiangli' => [
            'name' => '晋级奖励清理',
            'remark' => '清理N天前的晋级奖励',
            'type' => 'days',
        ],
        'jihua_fanshui' => [
            'name' => '返水数据清理',
            'remark' => '清理N天前的返水数据',
            'type' => 'days',
        ],
    ];

    /**
     * 获取计划任务列表
     * GET /app/admin/api/maintenance/task/list
     */
    public function taskList(Request $request)
    {
        $list = [];

        foreach ($this->taskConfig as $key => $config) {
            $item = [
                'name' => $config['name'],
                'time_key' => $key,
                'remark' => $config['remark'],
                'type' => $config['type'],
            ];

            if ($config['type'] === 'time') {
                // 时间配置类型
                $hourValue = Db::table('caipiao_setting')
                    ->where('name', $key . '_shi')
                    ->value('value');
                $minValue = Db::table('caipiao_setting')
                    ->where('name', $key . '_fen')
                    ->value('value');

                $item['time_value_hour'] = (int)($hourValue ?? 0);
                $item['time_value_minute'] = (int)($minValue ?? 0);
            } else {
                // 天数配置类型
                $daysValue = Db::table('caipiao_setting')
                    ->where('name', $key . '_days')
                    ->value('value');

                $item['days_value'] = (int)($daysValue ?? 30);
            }

            $list[] = $item;
        }

        return $this->json(0, 'success', [
            'list' => $list,
        ]);
    }

    /**
     * 更新计划任务
     * POST /app/admin/api/maintenance/task/update
     */
    public function taskUpdate(Request $request)
    {
        $name = $request->post('name', '');
        $hour = $request->post('hour');
        $minute = $request->post('minute');
        $days = $request->post('days');

        if (empty($name)) {
            return $this->json(1, '任务标识不能为空');
        }

        if (!isset($this->taskConfig[$name])) {
            return $this->json(1, '无效的任务标识');
        }

        $config = $this->taskConfig[$name];

        try {
            if ($config['type'] === 'time') {
                // 更新时间配置
                if ($hour === null || $minute === null) {
                    return $this->json(1, '请提供小时和分钟参数');
                }

                $hour = (int)$hour;
                $minute = (int)$minute;

                if ($hour < 0 || $hour > 23) {
                    return $this->json(1, '小时必须在 0-23 之间');
                }
                if ($minute < 0 || $minute > 59) {
                    return $this->json(1, '分钟必须在 0-59 之间');
                }

                // 更新小时
                $this->updateSetting($name . '_shi', $hour);
                // 更新分钟
                $this->updateSetting($name . '_fen', $minute);

            } else {
                // 更新天数配置
                if ($days === null) {
                    return $this->json(1, '请提供天数参数');
                }

                $days = (int)$days;
                if ($days < 1) {
                    return $this->json(1, '天数必须大于 0');
                }

                $this->updateSetting($name . '_days', $days);
            }

            return $this->json(0, '更新成功');

        } catch (\Exception $e) {
            Log::error("更新计划任务失败: " . $e->getMessage());
            return $this->json(1, '更新失败: ' . $e->getMessage());
        }
    }

    /**
     * 更新设置项
     */
    private function updateSetting($name, $value)
    {
        $exists = Db::table('caipiao_setting')->where('name', $name)->first();

        if ($exists) {
            Db::table('caipiao_setting')
                ->where('name', $name)
                ->update(['value' => $value]);
        } else {
            Db::table('caipiao_setting')->insert([
                'name' => $name,
                'value' => $value,
            ]);
        }
    }

    

    /**
     * ============================================
     * 服务器详细监控
     * ============================================
     */

    /**
     * 获取服务器监控信息
     * GET /app/admin/api/monitor/server
     */
    public function monitorServer(Request $request)
    {
        try {
            $data = [
                'cpu' => $this->getDetailedCpuInfo(),
                'mem' => $this->getDetailedMemInfo(),
                'php' => $this->getPhpInfo(),
                'sys' => $this->getSysInfo(),
                'sysFiles' => $this->getSysFiles(),
            ];

            return $this->json(0, 'success', $data);

        } catch (\Exception $e) {
            Log::error("获取服务器监控信息失败: " . $e->getMessage());
            return $this->json(1, '获取失败: ' . $e->getMessage());
        }
    }

    /**
     * 获取详细 CPU 信息
     */
    private function getDetailedCpuInfo()
    {
        // 获取 CPU 核心数
        $cpuNum = 1;
        if (is_readable('/proc/cpuinfo')) {
            $cpuInfo = file_get_contents('/proc/cpuinfo');
            $cpuNum = substr_count($cpuInfo, 'processor');
        }

        // 获取 CPU 使用率详情
        $stat1 = file_get_contents('/proc/stat');
        usleep(100000);
        $stat2 = file_get_contents('/proc/stat');

        $info1 = $this->parseCpuStat($stat1);
        $info2 = $this->parseCpuStat($stat2);

        $diffUser = $info2['user'] - $info1['user'];
        $diffNice = $info2['nice'] - $info1['nice'];
        $diffSys = $info2['system'] - $info1['system'];
        $diffIdle = $info2['idle'] - $info1['idle'];
        $diffIowait = $info2['iowait'] - $info1['iowait'];
        $diffTotal = $diffUser + $diffNice + $diffSys + $diffIdle + $diffIowait;

        if ($diffTotal == 0) {
            $diffTotal = 1;
        }

        return [
            'cpuNum' => $cpuNum,
            'total' => round((1 - $diffIdle / $diffTotal) * 100, 1),
            'sys' => round($diffSys / $diffTotal * 100, 1),
            'used' => round(($diffUser + $diffNice) / $diffTotal * 100, 1),
            'wait' => round($diffIowait / $diffTotal * 100, 1),
            'free' => round($diffIdle / $diffTotal * 100, 1),
        ];
    }

    /**
     * 解析 CPU 统计信息
     */
    private function parseCpuStat($stat)
    {
        $lines = explode("\n", $stat);
        $cpuLine = $lines[0];
        $parts = preg_split('/\s+/', trim($cpuLine));

        return [
            'user' => (int)($parts[1] ?? 0),
            'nice' => (int)($parts[2] ?? 0),
            'system' => (int)($parts[3] ?? 0),
            'idle' => (int)($parts[4] ?? 0),
            'iowait' => (int)($parts[5] ?? 0),
        ];
    }

    /**
     * 获取详细内存信息
     */
    private function getDetailedMemInfo()
    {
        $memInfo = file_get_contents('/proc/meminfo');
        
        preg_match('/MemTotal:\s+(\d+)/', $memInfo, $totalMatch);
        preg_match('/MemFree:\s+(\d+)/', $memInfo, $freeMatch);
        preg_match('/MemAvailable:\s+(\d+)/', $memInfo, $availMatch);
        preg_match('/Buffers:\s+(\d+)/', $memInfo, $buffersMatch);
        preg_match('/Cached:\s+(\d+)/', $memInfo, $cachedMatch);

        $totalKb = (int)($totalMatch[1] ?? 0);
        $freeKb = (int)($freeMatch[1] ?? 0);
        $availKb = (int)($availMatch[1] ?? $freeKb);
        $buffersKb = (int)($buffersMatch[1] ?? 0);
        $cachedKb = (int)($cachedMatch[1] ?? 0);

        $usedKb = $totalKb - $availKb;
        $totalGb = round($totalKb / 1024 / 1024, 2);
        $usedGb = round($usedKb / 1024 / 1024, 2);
        $freeGb = round($availKb / 1024 / 1024, 2);
        $usage = $totalKb > 0 ? round($usedKb / $totalKb * 100, 2) : 0;

        return [
            'total' => $totalGb,
            'used' => $usedGb,
            'free' => $freeGb,
            'usage' => $usage,
        ];
    }

    /**
     * 获取 PHP 信息（替代 JVM）
     */
    private function getPhpInfo()
    {
        // 获取 webman 启动时间
        $startTime = null;
        $runTime = '';

        // 尝试获取主进程启动时间
        $pid = getmypid();
        if ($pid && is_readable("/proc/$pid/stat")) {
            $stat = file_get_contents("/proc/$pid/stat");
            $parts = explode(' ', $stat);
            if (isset($parts[21])) {
                $startTicks = (int)$parts[21];
                $uptime = file_get_contents('/proc/uptime');
                $uptimeSeconds = (float)explode(' ', $uptime)[0];
                $hertz = 100; // CLK_TCK 通常是 100
                $processStartSeconds = $uptimeSeconds - ($startTicks / $hertz);
                $bootTime = time() - $uptimeSeconds;
                $startTimestamp = $bootTime + ($startTicks / $hertz);
                $startTime = date('Y-m-d H:i:s', (int)$startTimestamp);
                $runTime = $this->formatRunTime(time() - (int)$startTimestamp);
            }
        }

        // 内存使用
        $memUsage = memory_get_usage(true);
        $memPeak = memory_get_peak_usage(true);
        $memLimit = $this->parseMemoryLimit(ini_get('memory_limit'));

        return [
            'total' => round($memUsage / 1024 / 1024, 2),
            'max' => round($memLimit / 1024 / 1024, 2),
            'free' => round(($memLimit - $memUsage) / 1024 / 1024, 2),
            'usage' => $memLimit > 0 ? round($memUsage / $memLimit * 100, 2) : 0,
            'version' => PHP_VERSION,
            'home' => PHP_BINDIR,
            'name' => 'Webman/' . (\Workerman\Worker::VERSION ?? 'Unknown'),
            'startTime' => $startTime ?? date('Y-m-d H:i:s'),
            'runTime' => $runTime ?: '刚启动',
            'sapi' => php_sapi_name(),
        ];
    }

    /**
     * 解析内存限制
     */
    private function parseMemoryLimit($limit)
    {
        if ($limit == -1) {
            return PHP_INT_MAX;
        }
        
        $unit = strtolower(substr($limit, -1));
        $value = (int)$limit;

        switch ($unit) {
            case 'g':
                $value *= 1024 * 1024 * 1024;
                break;
            case 'm':
                $value *= 1024 * 1024;
                break;
            case 'k':
                $value *= 1024;
                break;
        }

        return $value;
    }

    /**
     * 格式化运行时间
     */
    private function formatRunTime($seconds)
    {
        $days = floor($seconds / 86400);
        $hours = floor(($seconds % 86400) / 3600);
        $minutes = floor(($seconds % 3600) / 60);

        $parts = [];
        if ($days > 0) {
            $parts[] = "{$days}天";
        }
        if ($hours > 0) {
            $parts[] = "{$hours}小时";
        }
        if ($minutes > 0) {
            $parts[] = "{$minutes}分钟";
        }

        return implode('', $parts) ?: '不足1分钟';
    }

    /**
     * 获取系统信息
     */
    private function getSysInfo()
    {
        $hostname = gethostname() ?: 'Unknown';
        
        // 获取 IP 地址
        $ip = '127.0.0.1';
        $interfaces = @file_get_contents('/proc/net/fib_trie');
        if (function_exists('shell_exec')) {
            $ip = trim(shell_exec("hostname -I 2>/dev/null | awk '{print \$1}'") ?? '127.0.0.1');
        }

        return [
            'computerName' => $hostname,
            'computerIp' => $ip ?: '127.0.0.1',
            'userDir' => getcwd(),
            'osName' => php_uname('s') . ' ' . php_uname('r'),
            'osArch' => php_uname('m'),
        ];
    }

    /**
     * 获取磁盘信息
     */
    private function getSysFiles()
    {
        $sysFiles = [];

        // 获取挂载点
        $mounts = [];
        if (is_readable('/proc/mounts')) {
            $mountsContent = file_get_contents('/proc/mounts');
            $lines = explode("\n", $mountsContent);
            foreach ($lines as $line) {
                $parts = preg_split('/\s+/', $line);
                if (count($parts) >= 3) {
                    $device = $parts[0];
                    $mountPoint = $parts[1];
                    $fsType = $parts[2];

                    // 只显示主要的文件系统
                    if (strpos($device, '/dev/') === 0 && !in_array($fsType, ['squashfs', 'tmpfs', 'devtmpfs'])) {
                        $mounts[] = [
                            'device' => $device,
                            'mountPoint' => $mountPoint,
                            'fsType' => $fsType,
                        ];
                    }
                }
            }
        }

        // 如果没有读取到挂载点，使用根目录
        if (empty($mounts)) {
            $mounts[] = [
                'device' => '/dev/root',
                'mountPoint' => '/',
                'fsType' => 'ext4',
            ];
        }

        foreach ($mounts as $mount) {
            $mountPoint = $mount['mountPoint'];
            
            $total = @disk_total_space($mountPoint);
            $free = @disk_free_space($mountPoint);

            if ($total === false || $total == 0) {
                continue;
            }

            $used = $total - $free;
            $usage = round($used / $total * 100, 2);

            $sysFiles[] = [
                'dirName' => $mountPoint,
                'sysTypeName' => strtoupper($mount['fsType']),
                'typeName' => $mount['device'],
                'total' => $this->formatBytes($total),
                'free' => $this->formatBytes($free),
                'used' => $this->formatBytes($used),
                'usage' => $usage,
            ];
        }

        return $sysFiles;
    }

    /**
     * 格式化字节数
     */
    private function formatBytes($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 1) . ' ' . $units[$i];
    }

    /**
     * ============================================
     * 在线用户管理
     * ============================================
     */

    /**
     * 获取在线用户列表
     * GET /app/admin/api/monitor/online/list
     */
    public function onlineList(Request $request)
    {
        $page = (int)$request->get('page', 1);
        $limit = (int)$request->get('limit', 20);
        $ipaddr = $request->get('ipaddr', '');
        $loginName = $request->get('loginName', '');

        // 清理过期的在线记录（超过30分钟未活动的）
        $expireTime = date('Y-m-d H:i:s', time() - 1800);
        Db::table('yzz_online_admin')
            ->where('last_access_time', '<', $expireTime)
            ->delete();

        $query = Db::table('yzz_online_admin');

        if (!empty($ipaddr)) {
            $query->where('ipaddr', 'like', "%{$ipaddr}%");
        }

        if (!empty($loginName)) {
            $query->where('login_name', 'like', "%{$loginName}%");
        }

        $total = $query->count();

        $list = $query->orderBy('last_access_time', 'desc')
            ->offset(($page - 1) * $limit)
            ->limit($limit)
            ->get();

        $result = [];
        foreach ($list as $item) {
            $item = (array)$item;
            $result[] = [
                'sessionId' => $item['session_id'],
                'loginName' => $item['login_name'],
                'deptName' => $item['dept_name'] ?? '',
                'ipaddr' => $item['ipaddr'],
                'loginLocation' => $item['login_location'] ?? '',
                'browser' => $item['browser'] ?? '',
                'os' => $item['os'] ?? '',
                'status' => $item['status'] ?? 'on_line',
                'startTimestamp' => $item['start_timestamp'],
                'lastAccessTime' => $item['last_access_time'],
            ];
        }

        return $this->json(0, 'success', [
            'total' => $total,
            'list' => $result,
        ]);
    }

    /**
     * 强退用户
     * POST /app/admin/api/monitor/online/batchForceLogout
     */
    public function batchForceLogout(Request $request)
    {
        $ids = $request->post('ids', '');

        if (empty($ids)) {
            return $this->json(1, '请选择要强退的用户');
        }

        $sessionIds = array_filter(explode(',', $ids));

        if (empty($sessionIds)) {
            return $this->json(1, '无效的会话ID');
        }

        try {
            // 删除在线记录
            $deleted = Db::table('yzz_online_admin')
                ->whereIn('session_id', $sessionIds)
                ->delete();

            // 同时使 JWT Token 失效（如果有黑名单机制的话）
            // 这里可以将 token 加入黑名单

            return $this->json(0, "成功强退 {$deleted} 个用户");

        } catch (\Exception $e) {
            Log::error("强退用户失败: " . $e->getMessage());
            return $this->json(1, '操作失败: ' . $e->getMessage());
        }
    }

    /**
     * 记录用户上线（供登录时调用）
     */
    public static function recordOnline($adminId, $username, $request)
    {
        $sessionId = md5($adminId . time() . mt_rand(1000, 9999));
        $userAgent = $request->header('User-Agent', '');

        // 解析浏览器和操作系统
        $browser = self::parseBrowser($userAgent);
        $os = self::parseOs($userAgent);

        // 获取 IP
        $ip = $request->getRealIp();

        // 获取登录地点（简单实现，可以接入 IP 库）
        $location = self::getIpLocation($ip);

        $now = date('Y-m-d H:i:s');

        // 删除该用户之前的在线记录
        Db::table('yzz_online_admin')
            ->where('admin_id', $adminId)
            ->delete();

        // 插入新记录
        Db::table('yzz_online_admin')->insert([
            'session_id' => $sessionId,
            'admin_id' => $adminId,
            'login_name' => $username,
            'dept_name' => '',
            'ipaddr' => $ip,
            'login_location' => $location,
            'browser' => $browser,
            'os' => $os,
            'status' => 'on_line',
            'start_timestamp' => $now,
            'last_access_time' => $now,
        ]);

        return $sessionId;
    }

    /**
     * 更新最后访问时间
     */
    public static function updateLastAccess($adminId)
    {
        Db::table('yzz_online_admin')
            ->where('admin_id', $adminId)
            ->update(['last_access_time' => date('Y-m-d H:i:s')]);
    }

    /**
     * 记录用户下线
     */
    public static function recordOffline($adminId)
    {
        Db::table('yzz_online_admin')
            ->where('admin_id', $adminId)
            ->delete();
    }

    /**
     * 解析浏览器
     */
    private static function parseBrowser($userAgent)
    {
        if (preg_match('/Edg\/([0-9.]+)/', $userAgent)) {
            return 'Edge';
        } elseif (preg_match('/Chrome\/([0-9.]+)/', $userAgent)) {
            return 'Chrome';
        } elseif (preg_match('/Firefox\/([0-9.]+)/', $userAgent)) {
            return 'Firefox';
        } elseif (preg_match('/Safari\/([0-9.]+)/', $userAgent) && !preg_match('/Chrome/', $userAgent)) {
            return 'Safari';
        } elseif (preg_match('/MSIE|Trident/', $userAgent)) {
            return 'IE';
        }
        return 'Unknown';
    }

    /**
     * 解析操作系统
     */
    private static function parseOs($userAgent)
    {
        if (preg_match('/Windows NT 10/', $userAgent)) {
            return 'Windows 10';
        } elseif (preg_match('/Windows NT 6\.3/', $userAgent)) {
            return 'Windows 8.1';
        } elseif (preg_match('/Windows NT 6\.2/', $userAgent)) {
            return 'Windows 8';
        } elseif (preg_match('/Windows NT 6\.1/', $userAgent)) {
            return 'Windows 7';
        } elseif (preg_match('/Mac OS X/', $userAgent)) {
            return 'Mac OS';
        } elseif (preg_match('/Linux/', $userAgent)) {
            if (preg_match('/Android/', $userAgent)) {
                return 'Android';
            }
            return 'Linux';
        } elseif (preg_match('/iPhone|iPad/', $userAgent)) {
            return 'iOS';
        }
        return 'Unknown';
    }

    /**
     * 获取 IP 地点（简单实现）
     */
    private static function getIpLocation($ip)
    {
        if ($ip === '127.0.0.1' || $ip === '::1') {
            return '本地';
        }
        if (preg_match('/^(10\.|172\.(1[6-9]|2[0-9]|3[01])\.|192\.168\.)/', $ip)) {
            return '内网';
        }
        return '未知';
    }
}
