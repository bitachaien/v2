<?php

declare(strict_types=1);

namespace plugin\admin\app\controller;

use support\Request;
use support\Response;
use support\Db;
use app\common\Result;

/**
 * 晋级奖励管理控制器
 *
 * 提供晋级奖励的后台管理功能
 *
 * @package plugin\admin\app\controller
 * @author  System
 * @version 1.0.0
 */
class LevelRewardController extends Base
{
    /**
     * 表名常量
     */
    private const TABLE_LEVEL_CONFIG = 'yzz_level_config';
    private const TABLE_LEVEL_REWARD_RECORD = 'yzz_level_reward_record';
    private const TABLE_USER_LEVEL = 'yzz_user_level';
    private const TABLE_MEMBER = 'caipiao_member';

    // ==================== Cấp độ配置管理 ====================

    /**
     * Cấp độ配置列表页面
     *
     * @param Request $request
     * @return Response
     */
    public function config(Request $request): Response
    {
        return view('level-reward/config');
    }

    /**
     * LấyCấp độ配置列表dữ liệu
     *
     * @param Request $request
     * @return Response
     */
    public function configList(Request $request): Response
    {
        $list = Db::table(self::TABLE_LEVEL_CONFIG)
            ->orderBy('sort_order', 'asc')
            ->get();

        return json([
            'code' => 0,
            'msg'  => 'success',
            'data' => $list,
            'count'=> $list->count(),
        ]);
    }

    /**
     * LưuCấp độ配置
     *
     * @param Request $request
     * @return Response
     */
    public function configSave(Request $request): Response
    {
        $id = (int)$request->post('id', 0);
        $data = [
            'level_name'          => $request->post('level_name', ''),
            'required_points'     => (int)$request->post('required_points', 0),
            'reward_amount'       => (float)$request->post('reward_amount', 0),
            'daily_withdraw_limit'=> (float)$request->post('daily_withdraw_limit', 0),
            'rebate_rate'         => (float)$request->post('rebate_rate', 0),
            'sort_order'          => (int)$request->post('sort_order', 0),
            'is_enabled'          => (int)$request->post('is_enabled', 1),
            'remark'              => $request->post('remark', ''),
        ];

        try {
            if ($id > 0) {
                Db::table(self::TABLE_LEVEL_CONFIG)->where('id', $id)->update($data);
                return json(['code' => 0, 'msg' => '更新Thành công']);
            } else {
                $data['level_id'] = (int)$request->post('level_id', 0);
                Db::table(self::TABLE_LEVEL_CONFIG)->insert($data);
                return json(['code' => 0, 'msg' => 'ThêmThành công']);
            }
        } catch (\Throwable $e) {
            return json(['code' => 1, 'msg' => 'Thao tác thất bại: ' . $e->getMessage()]);
        }
    }

    /**
     * XóaCấp độ配置
     *
     * @param Request $request
     * @return Response
     */
    public function configDelete(Request $request): Response
    {
        $id = (int)$request->post('id', 0);

        try {
            Db::table(self::TABLE_LEVEL_CONFIG)->where('id', $id)->delete();
            return json(['code' => 0, 'msg' => 'XóaThành công']);
        } catch (\Throwable $e) {
            return json(['code' => 1, 'msg' => 'XóaThất bại: ' . $e->getMessage()]);
        }
    }

    // ==================== Thăng cấplịch sử管理 ====================

    /**
     * Thăng cấplịch sử列表页面
     *
     * @param Request $request
     * @return Response
     */
    public function record(Request $request): Response
    {
        return view('level-reward/record');
    }

    /**
     * LấyThăng cấplịch sử列表dữ liệu
     *
     * @param Request $request
     * @return Response
     */
    public function recordList(Request $request): Response
    {
        $page = (int)$request->get('page', 1);
        $limit = (int)$request->get('limit', 20);
        $username = $request->get('username', '');
        $status = $request->get('status', '');
        $startDate = $request->get('start_date', '');
        $endDate = $request->get('end_date', '');

        $query = Db::table(self::TABLE_LEVEL_REWARD_RECORD);

        if ($username) {
            $query->where('username', 'like', "%{$username}%");
        }
        if ($status !== '') {
            $query->where('status', (int)$status);
        }
        if ($startDate) {
            $query->where('gmt_create', '>=', $startDate . ' 00:00:00');
        }
        if ($endDate) {
            $query->where('gmt_create', '<=', $endDate . ' 23:59:59');
        }

        $total = $query->count();
        $list = $query->orderBy('id', 'desc')
            ->offset(($page - 1) * $limit)
            ->limit($limit)
            ->get();

        return json([
            'code'  => 0,
            'msg'   => 'success',
            'data'  => $list,
            'count' => $total,
        ]);
    }

    // ==================== Người dùngCấp độ管理 ====================

    /**
     * Người dùngCấp độ列表页面
     *
     * @param Request $request
     * @return Response
     */
    public function userLevel(Request $request): Response
    {
        return view('level-reward/user-level');
    }

    /**
     * LấyNgười dùngCấp độ列表dữ liệu
     *
     * @param Request $request
     * @return Response
     */
    public function userLevelList(Request $request): Response
    {
        $page = (int)$request->get('page', 1);
        $limit = (int)$request->get('limit', 20);
        $userId = $request->get('user_id', '');

        $query = Db::table(self::TABLE_USER_LEVEL . ' as ul')
            ->leftJoin(self::TABLE_MEMBER . ' as m', 'ul.user_id', '=', 'm.id')
            ->select('ul.*', 'm.username', 'm.balance');

        if ($userId) {
            $query->where('ul.user_id', (int)$userId);
        }

        $total = $query->count();
        $list = $query->orderBy('ul.id', 'desc')
            ->offset(($page - 1) * $limit)
            ->limit($limit)
            ->get();

        return json([
            'code'  => 0,
            'msg'   => 'success',
            'data'  => $list,
            'count' => $total,
        ]);
    }

    /**
     * 手动发放Thăng cấp奖励
     *
     * @param Request $request
     * @return Response
     */
    public function grantReward(Request $request): Response
    {
        $userId = (int)$request->post('user_id', 0);
        $amount = (float)$request->post('amount', 0);
        $remark = $request->post('remark', '管理员手动发放');

        if ($userId <= 0) {
            return json(['code' => 1, 'msg' => 'Người dùngID无效']);
        }
        if ($amount <= 0) {
            return json(['code' => 1, 'msg' => 'Số tiền必须大于0']);
        }

        Db::beginTransaction();
        try {
            // LấyNgười dùng信息
            $user = Db::table(self::TABLE_MEMBER)->where('id', $userId)->first();
            if (!$user) {
                throw new \Exception('Người dùng không tồn tại');
            }

            // 更新待领取奖励
            Db::table(self::TABLE_USER_LEVEL)->updateOrInsert(
                ['user_id' => $userId],
                ['pending_reward' => Db::raw("pending_reward + {$amount}")]
            );

            // 同步更新旧表
            Db::table(self::TABLE_MEMBER)
                ->where('id', $userId)
                ->update(['jinjijilu' => Db::raw("jinjijilu + {$amount}")]);

            Db::commit();
            return json(['code' => 0, 'msg' => "Thành công发放 {$amount} 元Thăng cấp奖励"]);

        } catch (\Throwable $e) {
            Db::rollBack();
            return json(['code' => 1, 'msg' => '发放Thất bại: ' . $e->getMessage()]);
        }
    }

    /**
     * 批量发放晋级奖励
     *
     * @param Request $request
     * @return Response
     */
    public function batchGrantReward(Request $request): Response
    {
        $userIds = $request->post('user_ids', []);
        $amount = (float)$request->post('amount', 0);

        if (empty($userIds)) {
            return json(['code' => 1, 'msg' => 'Vui lòng chọnNgười dùng']);
        }
        if ($amount <= 0) {
            return json(['code' => 1, 'msg' => 'Số tiền必须大于0']);
        }

        if (is_string($userIds)) {
            $userIds = explode(',', $userIds);
        }

        $success = 0;
        $fail = 0;

        foreach ($userIds as $userId) {
            $userId = (int)$userId;
            if ($userId <= 0) continue;

            try {
                Db::table(self::TABLE_USER_LEVEL)->updateOrInsert(
                    ['user_id' => $userId],
                    ['pending_reward' => Db::raw("pending_reward + {$amount}")]
                );
                Db::table(self::TABLE_MEMBER)
                    ->where('id', $userId)
                    ->update(['jinjijilu' => Db::raw("jinjijilu + {$amount}")]);
                $success++;
            } catch (\Throwable $e) {
                $fail++;
            }
        }

        return json([
            'code' => 0,
            'msg'  => "批量发放完成：Thành công {$success} 个，Thất bại {$fail} 个"
        ]);
    }

    // ==================== 统计dữ liệu ====================

    /**
     * Lấy统计dữ liệu
     *
     * @param Request $request
     * @return Response
     */
    public function stats(Request $request): Response
    {
        $todayStart = date('Y-m-d 00:00:00');
        $todayEnd = date('Y-m-d 23:59:59');

        // Hôm nay领取Số tiền
        $todayAmount = Db::table(self::TABLE_LEVEL_REWARD_RECORD)
            ->where('claim_time', '>=', $todayStart)
            ->where('claim_time', '<=', $todayEnd)
            ->where('status', 1)
            ->sum('reward_amount') ?? 0;

        // Hôm nay领取次数
        $todayCount = Db::table(self::TABLE_LEVEL_REWARD_RECORD)
            ->where('claim_time', '>=', $todayStart)
            ->where('claim_time', '<=', $todayEnd)
            ->where('status', 1)
            ->count();

        // 总领取Số tiền
        $totalAmount = Db::table(self::TABLE_LEVEL_REWARD_RECORD)
            ->where('status', 1)
            ->sum('reward_amount') ?? 0;

        // 待领取总额
        $pendingAmount = Db::table(self::TABLE_USER_LEVEL)
            ->sum('pending_reward') ?? 0;

        return json([
            'code' => 0,
            'data' => [
                'today_amount'   => number_format((float)$todayAmount, 2),
                'today_count'    => $todayCount,
                'total_amount'   => number_format((float)$totalAmount, 2),
                'pending_amount' => number_format((float)$pendingAmount, 2),
            ]
        ]);
    }

    /**
     * Lấy状态选项
     *
     * @param Request $request
     * @return Response
     */
    public function statusOptions(Request $request): Response
    {
        return json([
            'code' => 0,
            'data' => [
                ['value' => 0, 'label' => '待领取'],
                ['value' => 1, 'label' => '已领取'],
                ['value' => 2, 'label' => '已过期'],
            ]
        ]);
    }
}
