<?php

namespace plugin\admin\app\controller;

use support\Request;
use support\Db;
use support\Redis;

/**
 * 聊天室28后台管理 API 控制器
 */
class Chat28ApiController extends Base
{
    /**
     * 不需要鉴权的方法
     */
    protected $noNeedAuth = [];

    /**
     * ============================================
     * 机器人配置管理
     * ============================================
     */

    /**
     * 获取机器人配置列表
     * GET /app/admin/api/chat28/config/list
     */
    public function configList(Request $request)
    {
        $list = Db::table('caipiao_robot_config')
            ->orderBy('id', 'desc')
            ->get();

        $result = [];
        foreach ($list as $item) {
            $item = (array)$item;
            $result[] = [
                'id' => $item['id'],
                'name' => $item['name'],
                'lottery_codes' => $item['lottery_codes'] ?? '',
                'lottery_codes_arr' => array_filter(explode(',', $item['lottery_codes'] ?? '')),
                'is_enabled' => (int)($item['is_enabled'] ?? 0),
                'min_bet_amount' => floatval($item['min_bet_amount'] ?? 10),
                'max_bet_amount' => floatval($item['max_bet_amount'] ?? 500),
                'bet_interval_min' => (int)($item['bet_interval_min'] ?? 30),
                'bet_interval_max' => (int)($item['bet_interval_max'] ?? 120),
                'bet_count_min' => (int)($item['bet_count_min'] ?? 1),
                'bet_count_max' => (int)($item['bet_count_max'] ?? 5),
                'play_types' => $item['play_types'] ?? '',
                'created_at' => $item['created_at'] ?? '',
                'updated_at' => $item['updated_at'] ?? '',
            ];
        }

        return $this->json(0, 'success', [
            'total' => count($result),
            'list' => $result,
        ]);
    }

    /**
     * 获取机器人配置详情
     * GET /app/admin/api/chat28/config/detail
     */
    public function configDetail(Request $request)
    {
        $id = $request->get('id');

        if (empty($id)) {
            return $this->json(1, '参数错误');
        }

        $config = Db::table('caipiao_robot_config')->where('id', $id)->first();

        if (!$config) {
            return $this->json(1, '配置不存在');
        }

        $config = (array)$config;

        return $this->json(0, 'success', [
            'id' => $config['id'],
            'name' => $config['name'],
            'lottery_codes' => $config['lottery_codes'] ?? '',
            'lottery_codes_arr' => array_filter(explode(',', $config['lottery_codes'] ?? '')),
            'is_enabled' => (int)($config['is_enabled'] ?? 0),
            'min_bet_amount' => floatval($config['min_bet_amount'] ?? 10),
            'max_bet_amount' => floatval($config['max_bet_amount'] ?? 500),
            'bet_interval_min' => (int)($config['bet_interval_min'] ?? 30),
            'bet_interval_max' => (int)($config['bet_interval_max'] ?? 120),
            'bet_count_min' => (int)($config['bet_count_min'] ?? 1),
            'bet_count_max' => (int)($config['bet_count_max'] ?? 5),
            'play_types' => $config['play_types'] ?? '',
        ]);
    }

    /**
     * 创建机器人配置
     * POST /app/admin/api/chat28/config/create
     */
    public function configCreate(Request $request)
    {
        $name = $request->post('name');
        
        if (empty($name)) {
            return $this->json(1, '配置名称不能为空');
        }

        $lotteryCodes = $request->post('lottery_codes', '');
        if (is_array($lotteryCodes)) {
            $lotteryCodes = implode(',', $lotteryCodes);
        }

        $data = [
            'name' => $name,
            'lottery_codes' => $lotteryCodes,
            'is_enabled' => (int)$request->post('is_enabled', 0),
            'min_bet_amount' => floatval($request->post('min_bet_amount', 10)),
            'max_bet_amount' => floatval($request->post('max_bet_amount', 500)),
            'bet_interval_min' => (int)$request->post('bet_interval_min', 30),
            'bet_interval_max' => (int)$request->post('bet_interval_max', 120),
            'bet_count_min' => (int)$request->post('bet_count_min', 1),
            'bet_count_max' => (int)$request->post('bet_count_max', 5),
            'play_types' => $request->post('play_types', ''),
            'created_at' => date('Y-m-d H:i:s'),
        ];

        $id = Db::table('caipiao_robot_config')->insertGetId($data);

        if ($id) {
            return $this->json(0, '创建成功', ['id' => $id]);
        }

        return $this->json(1, '创建失败');
    }

    /**
     * 更新机器人配置
     * POST /app/admin/api/chat28/config/update
     */
    public function configUpdate(Request $request)
    {
        $id = $request->post('id');
        
        if (empty($id)) {
            return $this->json(1, '参数错误');
        }

        $config = Db::table('caipiao_robot_config')->where('id', $id)->first();
        if (!$config) {
            return $this->json(1, '配置不存在');
        }

        $lotteryCodes = $request->post('lottery_codes', '');
        if (is_array($lotteryCodes)) {
            $lotteryCodes = implode(',', $lotteryCodes);
        }

        $updateData = [];
        
        $fields = ['name', 'is_enabled', 'min_bet_amount', 'max_bet_amount', 
                   'bet_interval_min', 'bet_interval_max', 'bet_count_min', 
                   'bet_count_max', 'play_types'];
        
        foreach ($fields as $field) {
            $value = $request->post($field);
            if ($value !== null) {
                $updateData[$field] = $value;
            }
        }
        
        if ($lotteryCodes !== null) {
            $updateData['lottery_codes'] = $lotteryCodes;
        }

        if (empty($updateData)) {
            return $this->json(1, '没有需要更新的字段');
        }

        Db::table('caipiao_robot_config')
            ->where('id', $id)
            ->update($updateData);

        return $this->json(0, '更新成功');
    }

    /**
     * 切换配置启用状态
     * POST /app/admin/api/chat28/config/toggle
     */
    public function configToggle(Request $request)
    {
        $id = $request->post('id');
        
        if (empty($id)) {
            return $this->json(1, '参数错误');
        }

        $config = Db::table('caipiao_robot_config')->where('id', $id)->first();
        if (!$config) {
            return $this->json(1, '配置不存在');
        }

        // 如果要启用，先禁用其他配置（只允许一个配置启用）
        if ($config->is_enabled == 0) {
            Db::table('caipiao_robot_config')->update(['is_enabled' => 0]);
        }

        Db::table('caipiao_robot_config')
            ->where('id', $id)
            ->update(['is_enabled' => $config->is_enabled == 1 ? 0 : 1]);

        return $this->json(0, '操作成功');
    }

    /**
     * 删除配置
     * POST /app/admin/api/chat28/config/delete
     */
    public function configDelete(Request $request)
    {
        $id = $request->post('id');
        
        if (empty($id)) {
            return $this->json(1, '参数错误');
        }

        Db::table('caipiao_robot_config')->where('id', $id)->delete();

        return $this->json(0, '删除成功');
    }

    /**
     * ============================================
     * 机器人会员管理
     * ============================================
     */

    /**
     * 获取机器人列表
     * GET /app/admin/api/chat28/robot/list
     */
    public function robotList(Request $request)
    {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 20);
        $username = $request->get('username', '');
        $status = $request->get('status', '');

        $query = Db::table('caipiao_member')
            ->where('is_robot', 1);

        if (!empty($username)) {
            $query->where(function($q) use ($username) {
                $q->where('username', 'like', "%{$username}%")
                  ->orWhere('nickname', 'like', "%{$username}%");
            });
        }

        if ($status !== '') {
            $query->where('islock', $status == 'locked' ? 1 : 0);
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
                'username' => $item['username'] ?? '',
                'nickname' => $item['nickname'] ?? '',
                'face' => $item['face'] ?? '',
                'balance' => floatval($item['balance'] ?? 0),
                'islock' => (int)($item['islock'] ?? 0),
                'islock_text' => ($item['islock'] ?? 0) == 1 ? '禁用' : '正常',
                'regtime' => isset($item['regtime']) ? date('Y-m-d H:i:s', $item['regtime']) : '',
                'logintime' => isset($item['logintime']) && $item['logintime'] > 0 
                    ? date('Y-m-d H:i:s', $item['logintime']) : '从未登录',
            ];
        }

        return $this->json(0, 'success', [
            'total' => $total,
            'list' => $result,
        ]);
    }

    /**
     * 创建机器人
     * POST /app/admin/api/chat28/robot/create
     */
    public function robotCreate(Request $request)
    {
        $username = $request->post('username');
        $nickname = $request->post('nickname');
        $balance = floatval($request->post('balance', 10000));

        if (empty($username)) {
            return $this->json(1, '用户名不能为空');
        }

        // 检查用户名是否存在
        $exists = Db::table('caipiao_member')->where('username', $username)->exists();
        if ($exists) {
            return $this->json(1, '用户名已存在');
        }

        $now = time();
        $data = [
            'username' => $username,
            'nickname' => $nickname ?: $username,
            'password' => md5('robot123456'),
            'is_robot' => 1,
            'balance' => $balance,
            'face' => $request->post('face', ''),
            'groupid' => 1,
            'proxy' => 0,
            'islock' => 0,
            'regtime' => $now,
            'regip' => $request->getRealIp(),
            'source' => 'admin_robot',
        ];

        $id = Db::table('caipiao_member')->insertGetId($data);

        if ($id) {
            return $this->json(0, '创建成功', ['id' => $id]);
        }

        return $this->json(1, '创建失败');
    }

    /**
     * 批量创建机器人
     * POST /app/admin/api/chat28/robot/batch-create
     */
    public function robotBatchCreate(Request $request)
    {
        $prefix = $request->post('prefix', 'robot_');
        $count = min((int)$request->post('count', 10), 100); // 最多100个
        $balance = floatval($request->post('balance', 10000));

        $created = 0;
        $now = time();

        for ($i = 1; $i <= $count; $i++) {
            $username = $prefix . str_pad($i, 4, '0', STR_PAD_LEFT) . '_' . mt_rand(100, 999);
            
            // 检查是否存在
            $exists = Db::table('caipiao_member')->where('username', $username)->exists();
            if ($exists) {
                continue;
            }

            $data = [
                'username' => $username,
                'nickname' => '用户' . mt_rand(10000, 99999),
                'password' => md5('robot123456'),
                'is_robot' => 1,
                'balance' => $balance + mt_rand(-2000, 5000),
                'face' => '',
                'groupid' => 1,
                'proxy' => 0,
                'islock' => 0,
                'regtime' => $now,
                'regip' => $request->getRealIp(),
                'source' => 'admin_robot_batch',
            ];

            try {
                Db::table('caipiao_member')->insert($data);
                $created++;
            } catch (\Exception $e) {
                continue;
            }
        }

        return $this->json(0, "成功创建 {$created} 个机器人");
    }

    /**
     * 更新机器人
     * POST /app/admin/api/chat28/robot/update
     */
    public function robotUpdate(Request $request)
    {
        $id = $request->post('id');
        
        if (empty($id)) {
            return $this->json(1, '参数错误');
        }

        $robot = Db::table('caipiao_member')
            ->where('id', $id)
            ->where('is_robot', 1)
            ->first();

        if (!$robot) {
            return $this->json(1, '机器人不存在');
        }

        $updateData = [];
        
        $nickname = $request->post('nickname');
        $face = $request->post('face');
        $balance = $request->post('balance');
        $islock = $request->post('islock');

        if ($nickname !== null) $updateData['nickname'] = $nickname;
        if ($face !== null) $updateData['face'] = $face;
        if ($balance !== null) $updateData['balance'] = floatval($balance);
        if ($islock !== null) $updateData['islock'] = (int)$islock;

        if (empty($updateData)) {
            return $this->json(1, '没有需要更新的字段');
        }

        Db::table('caipiao_member')
            ->where('id', $id)
            ->update($updateData);

        return $this->json(0, '更新成功');
    }

    /**
     * 删除机器人
     * POST /app/admin/api/chat28/robot/delete
     */
    public function robotDelete(Request $request)
    {
        $id = $request->post('id');
        
        if (empty($id)) {
            return $this->json(1, '参数错误');
        }

        // 只能删除机器人
        $affected = Db::table('caipiao_member')
            ->where('id', $id)
            ->where('is_robot', 1)
            ->delete();

        if ($affected) {
            return $this->json(0, '删除成功');
        }

        return $this->json(1, '机器人不存在或删除失败');
    }

    /**
     * 批量充值
     * POST /app/admin/api/chat28/robot/batch-recharge
     */
    public function robotBatchRecharge(Request $request)
    {
        $amount = floatval($request->post('amount', 10000));
        $minBalance = floatval($request->post('min_balance', 1000));

        // 给余额低于阈值的机器人充值
        $affected = Db::table('caipiao_member')
            ->where('is_robot', 1)
            ->where('balance', '<', $minBalance)
            ->update(['balance' => Db::raw("balance + {$amount}")]);

        return $this->json(0, "成功为 {$affected} 个机器人充值");
    }

    /**
     * ============================================
     * 聊天消息管理
     * ============================================
     */

    /**
     * 获取聊天消息列表
     * GET /app/admin/api/chat28/message/list
     */
    public function messageList(Request $request)
    {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 20);
        $lotteryCode = $request->get('lottery_code', '');
        $username = $request->get('username', '');
        $messageType = $request->get('message_type', '');
        $sDate = $request->get('sDate', '');
        $eDate = $request->get('eDate', '');

        $query = Db::table('caipiao_lottery_chat');

        if (!empty($lotteryCode)) {
            $query->where('lottery_code', $lotteryCode);
        }

        if (!empty($username)) {
            $query->where('user_name', 'like', "%{$username}%");
        }

        if (!empty($messageType)) {
            $query->where('message_type', $messageType);
        }

        if (!empty($sDate)) {
            $query->where('created_at', '>=', $sDate . ' 00:00:00');
        }

        if (!empty($eDate)) {
            $query->where('created_at', '<=', $eDate . ' 23:59:59');
        }

        $total = $query->count();

        $list = $query->orderBy('id', 'desc')
            ->offset(($page - 1) * $limit)
            ->limit($limit)
            ->get();

        $result = [];
        foreach ($list as $item) {
            $item = (array)$item;
            $isSystem = ($item['message_type'] ?? '') === 'system' || ($item['user_id'] ?? 0) == 0;
            $result[] = [
                'id' => $item['id'],
                'lottery_code' => $item['lottery_code'] ?? '',
                'issue' => $item['issue'] ?? '',
                'user_id' => $item['user_id'] ?? 0,
                'user_name' => $item['user_name'] ?? '',
                'avatar' => $item['avatar'] ?? '',
                'content' => $item['content'] ?? '',
                'message_type' => $item['message_type'] ?? 'text',
                'message_type_text' => $this->getMessageTypeText($item['message_type'] ?? 'text'),
                'is_system' => $isSystem ? 1 : 0,
                'created_at' => $item['created_at'] ?? '',
            ];
        }

        return $this->json(0, 'success', [
            'total' => $total,
            'list' => $result,
        ]);
    }

    /**
     * 删除消息
     * POST /app/admin/api/chat28/message/delete
     */
    public function messageDelete(Request $request)
    {
        $id = $request->post('id');
        
        if (empty($id)) {
            return $this->json(1, '参数错误');
        }

        // 获取消息详情用于推送撤回
        $message = Db::table('caipiao_lottery_chat')->where('id', $id)->first();
        
        if ($message) {
            // 删除消息
            Db::table('caipiao_lottery_chat')->where('id', $id)->delete();
            
            // 推送消息撤回通知到 WebSocket
            try {
                $redis = Redis::connection('default')->client();
                $recallMsg = [
                    'target' => 'lottery',
                    'lotteryCode' => $message->lottery_code,
                    'type' => 'message_recall',
                    'data' => [
                        'messageId' => $id,
                        'lotteryCode' => $message->lottery_code
                    ]
                ];
                $redis->rPush('websocket_push_queue', json_encode($recallMsg));
            } catch (\Exception $e) {
                // 忽略推送失败
            }
        }

        return $this->json(0, '删除成功');
    }

    /**
     * 发送系统消息
     * POST /app/admin/api/chat28/message/send-system
     */
    public function sendSystemMessage(Request $request)
    {
        $lotteryCode = $request->post('lottery_code');
        $content = $request->post('content');
        
        if (empty($lotteryCode) || empty($content)) {
            return $this->json(1, '参数不完整');
        }

        // 获取当前期号
        $currentIssue = Db::table('caipiao_kaijiang')
            ->where('name', $lotteryCode)
            ->where('isdraw', 0)
            ->orderBy('expect', 'desc')
            ->value('expect') ?? '';

        $now = date('Y-m-d H:i:s');
        
        // 保存到数据库
        $data = [
            'lottery_code' => $lotteryCode,
            'issue' => $currentIssue,
            'user_id' => 0,
            'user_name' => '系统',
            'avatar' => '',
            'content' => $content,
            'message_type' => 'system',
            'created_at' => $now,
        ];
        
        $id = Db::table('caipiao_lottery_chat')->insertGetId($data);

        // 推送到 WebSocket
        try {
            $redis = Redis::connection('default')->client();
            $chatMsg = [
                'target' => 'lottery',
                'lotteryCode' => $lotteryCode,
                'type' => 'lottery_chat_message',
                'data' => [
                    'id' => $id,
                    'lotteryCode' => $lotteryCode,
                    'issue' => $currentIssue,
                    'userId' => 0,
                    'userName' => '系统',
                    'avatar' => '',
                    'content' => $content,
                    'messageType' => 'system',
                    'time' => $now,
                    'isSystem' => true,
                ]
            ];
            $redis->rPush('websocket_push_queue', json_encode($chatMsg));
        } catch (\Exception $e) {
            // 忽略推送失败
        }

        return $this->json(0, '发送成功', ['id' => $id]);
    }

    /**
     * ============================================
     * 统计数据
     * ============================================
     */

    /**
     * 获取聊天室统计
     * GET /app/admin/api/chat28/stats
     */
    public function stats(Request $request)
    {
        $lotteryCode = $request->get('lottery_code', 'yfxy28');

        // 机器人数量
        $robotCount = Db::table('caipiao_member')
            ->where('is_robot', 1)
            ->where('islock', 0)
            ->count();

        // 今日消息数
        $todayMessages = Db::table('caipiao_lottery_chat')
            ->where('lottery_code', $lotteryCode)
            ->where('created_at', '>=', date('Y-m-d 00:00:00'))
            ->count();

        // 今日投注数
        $todayBets = Db::table('caipiao_touzhu')
            ->where('cpname', $lotteryCode)
            ->where('oddtime', '>=', strtotime(date('Y-m-d')))
            ->count();

        // 今日投注金额
        $todayAmount = Db::table('caipiao_touzhu')
            ->where('cpname', $lotteryCode)
            ->where('oddtime', '>=', strtotime(date('Y-m-d')))
            ->sum('amount');

        // 配置状态
        $configEnabled = Db::table('caipiao_robot_config')
            ->where('is_enabled', 1)
            ->exists();

        return $this->json(0, 'success', [
            'robot_count' => $robotCount,
            'today_messages' => $todayMessages,
            'today_bets' => $todayBets,
            'today_amount' => round($todayAmount, 2),
            'config_enabled' => $configEnabled,
        ]);
    }

    /**
     * 获取彩种选项
     * GET /app/admin/api/chat28/lottery-options
     */
    public function lotteryOptions(Request $request)
    {
        // 只返回28类彩种
        $list = Db::table('caipiao_caipiao')
            ->where('typeid', 'xy28')
            ->where('isopen', 1)
            ->select('name', 'title')
            ->orderBy('id', 'asc')
            ->get();

        $options = [];
        foreach ($list as $item) {
            $options[] = [
                'value' => $item->name,
                'label' => $item->title,
            ];
        }

        return $this->json(0, 'ok', $options);
    }

    /**
     * 获取机器人聊天配置
     * GET /app/admin/api/chat28/bot-config
     */
    public function botConfig(Request $request)
    {
        $prefix = 'chat28_bot_';
        $rows = Db::table('caipiao_system_config')
            ->where('config_key', 'like', $prefix . '%')
            ->pluck('config_value', 'config_key')
            ->toArray();

        $config = [];
        foreach ($rows as $key => $value) {
            $config[str_replace($prefix, '', $key)] = $value;
        }

        return $this->json(0, 'success', [
            'robot_name' => $config['robot_name'] ?? '系统助手',
            'robot_avatar' => $config['robot_avatar'] ?? '',
            'bill_enabled' => (int)($config['bill_enabled'] ?? 1),
            'result_enabled' => (int)($config['result_enabled'] ?? 1),
            'sealed_notice_enabled' => (int)($config['sealed_notice_enabled'] ?? 1),
            'draw_notice_enabled' => (int)($config['draw_notice_enabled'] ?? 1),
            'msg_pre_sealed' => $config['msg_pre_sealed'] ?? '即将封盘，请抓紧时间投注',
            'msg_sealed_line' => $config['msg_sealed_line'] ?? '已封盘，停止下注',
            'msg_no_talk' => $config['msg_no_talk'] ?? '开奖期间禁止发言',
            'msg_draw_coming' => $config['msg_draw_coming'] ?? '开奖结果即将揭晓'
        ]);
    }

    /**
     * 保存机器人聊天配置
     * POST /app/admin/api/chat28/bot-config-save
     */
    public function botConfigSave(Request $request)
    {
        $prefix = 'chat28_bot_';
        $fields = [
            'robot_name', 'robot_avatar', 'bill_enabled', 'result_enabled',
            'sealed_notice_enabled', 'draw_notice_enabled',
            'msg_pre_sealed', 'msg_sealed_line', 'msg_no_talk', 'msg_draw_coming'
        ];

        foreach ($fields as $field) {
            $value = $request->post($field);
            if ($value !== null) {
                $configKey = $prefix . $field;
                $exists = Db::table('caipiao_system_config')
                    ->where('config_key', $configKey)
                    ->exists();
                
                if ($exists) {
                    Db::table('caipiao_system_config')
                        ->where('config_key', $configKey)
                        ->update(['config_value' => (string)$value, 'updated_at' => time()]);
                } else {
                    Db::table('caipiao_system_config')
                        ->insert([
                            'config_key' => $configKey,
                            'config_value' => (string)$value,
                            'remark' => '聊天室机器人配置',
                            'updated_at' => time()
                        ]);
                }
            }
        }

        return $this->json(0, '保存成功');
    }

    /**
     * 获取消息类型文本
     */
    private function getMessageTypeText(string $type): string
    {
        return match($type) {
            'bet' => '投注',
            'result' => '开奖',
            'system' => '系统',
            'text' => '文字',
            default => $type
        };
    }
}

