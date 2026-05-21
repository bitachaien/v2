<?php

namespace app\controller\admin;

use support\Request;
use support\Db;

class ActivityRewardController
{
    
    public function list(Request $request)
    {
        try {
            $activityId = $request->get('activity_id', 0);
            $rewardType = $request->get('reward_type', '');
            
            $query = Db::table('caipiao_activity_reward as r')
                ->leftJoin('caipiao_huodong as a', 'r.activity_id', '=', 'a.id')
                ->select('r.*', 'a.title as activity_title');
            
            if ($activityId > 0) {
                $query->where('r.activity_id', $activityId);
            }
            
            if ($rewardType) {
                $query->where('r.reward_type', $rewardType);
            }
            
            $list = $query->orderBy('r.activity_id', 'asc')
                ->orderBy('r.sort', 'asc')
                ->get();
            
            $data = [];
            foreach ($list as $item) {
                $data[] = [
                    'id' => $item->id,
                    'activityId' => $item->activity_id,
                    'activityTitle' => $item->activity_title ?? '',
                    'rewardType' => $item->reward_type,
                    'levelName' => $item->level_name,
                    'conditionMin' => (float)$item->condition_min,
                    'conditionMax' => (float)$item->condition_max,
                    'rewardAmount' => (float)$item->reward_amount,
                    'rewardRate' => (float)$item->reward_rate,
                    'conditionType' => $item->condition_type,
                    'conditionValue' => $item->condition_value,
                    'needApply' => (bool)$item->need_apply,
                    'limitTimes' => $item->limit_times,
                    'limitPeriod' => $item->limit_period,
                    'sort' => $item->sort,
                    'status' => $item->status,
                    'createdAt' => $item->created_at,
                    'updatedAt' => $item->updated_at
                ];
            }
            
            return json([
                'code' => 0,
                'msg' => 'Lấy dữ liệu thành công',
                'count' => count($data),
                'data' => $data
            ]);
            
        } catch (\Exception $e) {
            \support\Log::error('LấyHoạt động奖励列表Thất bại: ' . $e->getMessage());
            return json([
                'code' => 1,
                'msg' => 'Lấy dữ liệu thất bại：' . $e->getMessage(),
                'data' => []
            ]);
        }
    }
    
    
    public function detail(Request $request)
    {
        $id = $request->get('id', 0);
        
        if (!$id) {
            return json(['code' => 1, 'msg' => 'Tham số không hợp lệ', 'data' => null]);
        }
        
        try {
            $reward = Db::table('caipiao_activity_reward')
                ->where('id', $id)
                ->first();
            
            if (!$reward) {
                return json(['code' => 1, 'msg' => '配置không tồn tại', 'data' => null]);
            }
            
            $data = [
                'id' => $reward->id,
                'activityId' => $reward->activity_id,
                'rewardType' => $reward->reward_type,
                'levelName' => $reward->level_name,
                'conditionMin' => (float)$reward->condition_min,
                'conditionMax' => (float)$reward->condition_max,
                'rewardAmount' => (float)$reward->reward_amount,
                'rewardRate' => (float)$reward->reward_rate,
                'conditionType' => $reward->condition_type,
                'conditionValue' => $reward->condition_value,
                'needApply' => (bool)$reward->need_apply,
                'limitTimes' => $reward->limit_times,
                'limitPeriod' => $reward->limit_period,
                'sort' => $reward->sort,
                'status' => $reward->status
            ];
            
            return json(['code' => 0, 'msg' => 'Lấy dữ liệu thành công', 'data' => $data]);
            
        } catch (\Exception $e) {
            \support\Log::error('Lấy奖励Chi tiếtThất bại: ' . $e->getMessage());
            return json(['code' => 1, 'msg' => 'Lấy dữ liệu thất bại：' . $e->getMessage(), 'data' => null]);
        }
    }
    
    
    public function add(Request $request)
    {
        try {
            $rewardType = $request->post('reward_type', '');
            $conditionMin = $request->post('condition_min', 0);
            $conditionValue = $request->post('condition_value', '');
            $levelName = $request->post('level_name', '');
            
            
            if (empty($levelName)) {
                if ($rewardType === 'lucky_order') {
                    $levelName = '尾号' . $conditionValue;
                } elseif ($rewardType === 'loss_rescue') {
                    $levelName = '亏损≥' . number_format($conditionMin, 0);
                } else {
                    $levelName = 'Đặt cược≥' . number_format($conditionMin, 0);
                }
            }
            
            $data = [
                'activity_id' => $request->post('activity_id', 0),
                'reward_type' => $rewardType,
                'level_name' => $levelName,
                'condition_min' => $conditionMin,
                'condition_max' => $request->post('condition_max', 0),
                'reward_amount' => $request->post('reward_amount', 0),
                'reward_rate' => $request->post('reward_rate', 0),
                'condition_type' => $request->post('condition_type', 'amount'),
                'condition_value' => $conditionValue,
                'need_apply' => $request->post('need_apply', 0),
                'limit_times' => $request->post('limit_times', 1),
                'limit_period' => $request->post('limit_period', 'once'),
                'sort' => $request->post('sort', 0),
                'status' => $request->post('status', 1),
                'created_at' => time(),
                'updated_at' => time()
            ];
            
            
            if (!$data['activity_id'] || !$data['reward_type']) {
                return json(['code' => 1, 'msg' => '请填写完整信息', 'data' => null]);
            }
            
            $id = Db::table('caipiao_activity_reward')->insertGetId($data);
            
            return json(['code' => 0, 'msg' => 'ThêmThành công', 'data' => ['id' => $id]]);
            
        } catch (\Exception $e) {
            \support\Log::error('Thêm奖励cấu hình thất bại: ' . $e->getMessage());
            return json(['code' => 1, 'msg' => 'ThêmThất bại：' . $e->getMessage(), 'data' => null]);
        }
    }
    
    
    public function edit(Request $request)
    {
        $id = $request->post('id', 0);
        
        if (!$id) {
            return json(['code' => 1, 'msg' => 'Tham số không hợp lệ', 'data' => null]);
        }
        
        try {
            $data = [
                'activity_id' => $request->post('activity_id'),
                'reward_type' => $request->post('reward_type'),
                'level_name' => $request->post('level_name'),
                'condition_min' => $request->post('condition_min'),
                'condition_max' => $request->post('condition_max'),
                'reward_amount' => $request->post('reward_amount'),
                'reward_rate' => $request->post('reward_rate'),
                'condition_type' => $request->post('condition_type'),
                'condition_value' => $request->post('condition_value'),
                'need_apply' => $request->post('need_apply'),
                'limit_times' => $request->post('limit_times'),
                'limit_period' => $request->post('limit_period'),
                'sort' => $request->post('sort'),
                'status' => $request->post('status'),
                'updated_at' => time()
            ];
            
            
            $data = array_filter($data, function($value) {
                return $value !== null && $value !== '';
            });
            
            if (empty($data)) {
                return json(['code' => 1, 'msg' => '没有需要更新的dữ liệu', 'data' => null]);
            }
            
            Db::table('caipiao_activity_reward')
                ->where('id', $id)
                ->update($data);
            
            return json(['code' => 0, 'msg' => '更新Thành công', 'data' => null]);
            
        } catch (\Exception $e) {
            \support\Log::error('编辑奖励cấu hình thất bại: ' . $e->getMessage());
            return json(['code' => 1, 'msg' => '更新Thất bại：' . $e->getMessage(), 'data' => null]);
        }
    }
    
    
    public function delete(Request $request)
    {
        $id = $request->post('id', 0);
        
        if (!$id) {
            return json(['code' => 1, 'msg' => 'Tham số không hợp lệ', 'data' => null]);
        }
        
        try {
            Db::table('caipiao_activity_reward')->where('id', $id)->delete();
            
            return json(['code' => 0, 'msg' => 'XóaThành công', 'data' => null]);
            
        } catch (\Exception $e) {
            \support\Log::error('Xóa奖励cấu hình thất bại: ' . $e->getMessage());
            return json(['code' => 1, 'msg' => 'XóaThất bại：' . $e->getMessage(), 'data' => null]);
        }
    }
    
    
    public function participationList(Request $request)
    {
        try {
            $page = $request->get('page', 1);
            $limit = $request->get('limit', 20);
            $activityId = $request->get('activity_id', 0);
            $status = $request->get('status', '');
            $username = $request->get('username', '');
            
            $query = Db::table('caipiao_activity_participation as p')
                ->leftJoin('caipiao_huodong as a', 'p.activity_id', '=', 'a.id')
                ->leftJoin('caipiao_activity_reward as r', 'p.reward_id', '=', 'r.id');
            
            if ($activityId > 0) {
                $query->where('p.activity_id', $activityId);
            }
            
            if ($status !== '') {
                $query->where('p.status', $status);
            }
            
            if ($username) {
                $query->where('p.username', 'like', "%{$username}%");
            }
            
            $total = $query->count();
            
            $list = $query->orderBy('p.apply_time', 'desc')
                ->offset(($page - 1) * $limit)
                ->limit($limit)
                ->select(
                    'p.*', 
                    'a.title as activity_title',
                    'r.level_name'
                )
                ->get();
            
            $data = [];
            foreach ($list as $item) {
                $statusText = ['Chờ duyệt', '已发放', 'Đã từ chối'][$item->status] ?? '未知';
                
                $data[] = [
                    'id' => $item->id,
                    'activityId' => $item->activity_id,
                    'activityTitle' => $item->activity_title ?? '',
                    'rewardId' => $item->reward_id,
                    'levelName' => $item->level_name ?? '',
                    'uid' => $item->uid,
                    'username' => $item->username,
                    'rewardType' => $item->reward_type,
                    'conditionValue' => $item->condition_value,
                    'rewardAmount' => (float)$item->reward_amount,
                    'status' => $item->status,
                    'statusText' => $statusText,
                    'trano' => $item->trano,
                    'applyTime' => $item->apply_time,
                    'auditTime' => $item->audit_time,
                    'auditAdmin' => $item->audit_admin,
                    'auditRemark' => $item->audit_remark,
                    'ip' => $item->ip
                ];
            }
            
            return json([
                'code' => 0,
                'msg' => 'Lấy dữ liệu thành công',
                'count' => $total,
                'data' => $data
            ]);
            
        } catch (\Exception $e) {
            \support\Log::error('Lấy参与lịch sử thất bại: ' . $e->getMessage());
            return json([
                'code' => 1,
                'msg' => 'Lấy dữ liệu thất bại：' . $e->getMessage(),
                'count' => 0,
                'data' => []
            ]);
        }
    }
    
    
    public function audit(Request $request)
    {
        $ids = $request->post('ids', []); 
        $status = $request->post('status', 1); 
        $remark = $request->post('remark', '');
        $adminName = $request->adminName ?? 'admin';
        
        if (empty($ids) || !in_array($status, [1, 2])) {
            return json(['code' => 1, 'msg' => 'Tham số không hợp lệ', 'data' => null]);
        }
        
        try {
            $records = Db::table('caipiao_activity_participation')
                ->whereIn('id', $ids)
                ->where('status', 0) 
                ->get();
            
            if ($records->isEmpty()) {
                return json(['code' => 1, 'msg' => '没有可审核的lịch sử', 'data' => null]);
            }
            
            $successCount = 0;
            
            foreach ($records as $record) {
                
                Db::table('caipiao_activity_participation')
                    ->where('id', $record->id)
                    ->update([
                        'status' => $status,
                        'audit_time' => time(),
                        'audit_admin' => $adminName,
                        'audit_remark' => $remark
                    ]);
                
                
                if ($status == 1) {
                    $this->grantReward($record);
                }
                
                $successCount++;
            }
            
            return json([
                'code' => 0,
                'msg' => "审核Thành công，共处理{$successCount}条lịch sử",
                'data' => ['count' => $successCount]
            ]);
            
        } catch (\Exception $e) {
            \support\Log::error('审核参与lịch sử thất bại: ' . $e->getMessage());
            return json(['code' => 1, 'msg' => '审核Thất bại：' . $e->getMessage(), 'data' => null]);
        }
    }
    
    
    public function statistics(Request $request)
    {
        try {
            $activityId = $request->get('activity_id', 0);
            $startDate = $request->get('start_date', '');
            $endDate = $request->get('end_date', '');
            
            $query = Db::table('caipiao_activity_participation');
            
            if ($activityId > 0) {
                $query->where('activity_id', $activityId);
            }
            
            if ($startDate) {
                $query->where('apply_time', '>=', strtotime($startDate . ' 00:00:00'));
            }
            
            if ($endDate) {
                $query->where('apply_time', '<=', strtotime($endDate . ' 23:59:59'));
            }
            
            
            $totalCount = $query->count();
            $pendingCount = (clone $query)->where('status', 0)->count();
            $approvedCount = (clone $query)->where('status', 1)->count();
            $rejectedCount = (clone $query)->where('status', 2)->count();
            $totalAmount = (clone $query)->where('status', 1)->sum('reward_amount') ?? 0;
            
            
            $byActivity = Db::table('caipiao_activity_participation as p')
                ->leftJoin('caipiao_huodong as a', 'p.activity_id', '=', 'a.id')
                ->select(
                    'p.activity_id',
                    'a.title as activity_title',
                    Db::raw('COUNT(*) as count'),
                    Db::raw('SUM(CASE WHEN p.status = 1 THEN p.reward_amount ELSE 0 END) as total_amount')
                )
                ->groupBy('p.activity_id', 'a.title')
                ->get();
            
            
            $byDate = Db::table('caipiao_activity_participation')
                ->select(
                    Db::raw('FROM_UNIXTIME(apply_time, "%Y-%m-%d") as date'),
                    Db::raw('COUNT(*) as count'),
                    Db::raw('SUM(CASE WHEN status = 1 THEN reward_amount ELSE 0 END) as amount')
                )
                ->groupBy('date')
                ->orderBy('date', 'desc')
                ->limit(30)
                ->get();
            
            return json([
                'code' => 0,
                'msg' => 'Lấy dữ liệu thành công',
                'data' => [
                    'overview' => [
                        'totalCount' => $totalCount,
                        'pendingCount' => $pendingCount,
                        'approvedCount' => $approvedCount,
                        'rejectedCount' => $rejectedCount,
                        'totalAmount' => number_format($totalAmount, 2, '.', '')
                    ],
                    'byActivity' => $byActivity->toArray(),
                    'byDate' => $byDate->toArray()
                ]
            ]);
            
        } catch (\Exception $e) {
            \support\Log::error('Lấy统计dữ liệuThất bại: ' . $e->getMessage());
            return json(['code' => 1, 'msg' => 'Lấy dữ liệu thất bại：' . $e->getMessage(), 'data' => null]);
        }
    }
    
    
    protected function grantReward($record)
    {
        $orderId = 'AR' . date('YmdHis') . rand(1000, 9999);
        
        
        $user = Db::table('caipiao_member')->where('id', $record->uid)->first();
        if (!$user) {
            return;
        }
        
        
        Db::table('caipiao_activity_participation')
            ->where('id', $record->id)
            ->update(['trano' => $orderId]);
        
        
        Db::table('caipiao_member')
            ->where('id', $record->uid)
            ->increment('balance', $record->reward_amount);
        
        
        Db::table('caipiao_fuddetail')->insert([
            'uid' => $record->uid,
            'username' => $record->username,
            'type' => 'activity_reward',
            'typename' => 'Hoạt động奖励',
            'trano' => $orderId,
            'amount' => $record->reward_amount,
            'before' => $user->balance,
            'after' => $user->balance + $record->reward_amount,
            'oddtime' => time(),
            'remark' => 'Hoạt động奖励-' . $record->reward_type
        ]);
    }
}

