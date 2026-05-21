<?php

declare(strict_types=1);

namespace plugin\admin\app\controller;

use support\Request;
use support\Response;
use support\Db;
use app\service\BannerService;

/**
 * 营销管理控制器
 *
 * 提供营销相关的后台管理功能
 *
 * @package plugin\admin\app\controller
 * @author  System
 * @version 1.0.0
 */
class MarketingController extends Base
{
    /**
     * 轮播图服务实例
     *
     * @var BannerService
     */
    private BannerService $bannerService;

    /**
     * 构造函数
     */
    public function __construct()
    {
        $this->bannerService = new BannerService();
    }

    /**
     * ============================================
     * 轮播图管理
     * ============================================
     */

    /**
     * 轮播图列表页面
     *
     * @param Request $request 请求对象
     * @return Response
     */
    public function banner(Request $request): Response
    {
        return view('marketing/banner/index');
    }

    /**
     * Lấy轮播图列表dữ liệu
     *
     * @param Request $request 请求对象
     * @return Response
     */
    public function bannerList(Request $request): Response
    {
        $params = [
            'page'     => $request->get('page', 1),
            'limit'    => $request->get('limit', 20),
            'platform' => $request->get('platform', ''),
            'status'   => $request->get('status', ''),
        ];

        $result = $this->bannerService->getAdminList($params);

        return json($result);
    }

    /**
     * Thêm轮播图
     *
     * @param Request $request 请求对象
     * @return Response|string
     */
    public function bannerAdd(Request $request)
    {
        if ($request->method() === 'POST') {
            $data = [
                'title'      => $request->post('title'),
                'image'      => $request->post('image'),
                'link'       => $request->post('link', ''),
                'platform'   => $request->post('platform', 0),
                'sort'       => $request->post('sort', 0),
                'status'     => $request->post('status', 1),
                'start_time' => $request->post('start_time'),
                'end_time'   => $request->post('end_time'),
                'remark'     => $request->post('remark', ''),
            ];

            $result = $this->bannerService->create($data);

            return json($result);
        }

        return view('marketing/banner/add');
    }

    /**
     * 编辑轮播图
     *
     * @param Request $request 请求对象
     * @return Response|string
     */
    public function bannerEdit(Request $request)
    {
        $id = (int)($request->input('id') ?: $request->get('id'));

        if ($request->method() === 'POST') {
            $data = [
                'title'      => $request->post('title'),
                'image'      => $request->post('image'),
                'link'       => $request->post('link'),
                'platform'   => $request->post('platform'),
                'sort'       => $request->post('sort'),
                'status'     => $request->post('status'),
                'start_time' => $request->post('start_time'),
                'end_time'   => $request->post('end_time'),
                'remark'     => $request->post('remark'),
            ];

            // 过滤空值
            $data = array_filter($data, function ($value) {
                return $value !== null;
            });

            $result = $this->bannerService->update($id, $data);

            return json($result);
        }

        // GET 请求 - 返回页面
        if ($id <= 0) {
            return '<script>alert("Tham số không hợp lệ");history.back();</script>';
        }

        $info = $this->bannerService->getDetail($id);

        if (!$info) {
            return '<script>alert("轮播图không tồn tại");history.back();</script>';
        }

        return view('marketing/banner/edit', ['info' => $info]);
    }

    /**
     * Xóa轮播图
     *
     * @param Request $request 请求对象
     * @return Response
     */
    public function bannerDelete(Request $request): Response
    {
        $id = (int)$request->post('id', 0);

        $result = $this->bannerService->delete($id);

        return json($result);
    }

    /**
     * 批量Xóa轮播图
     *
     * @param Request $request 请求对象
     * @return Response
     */
    public function bannerDeleteAll(Request $request): Response
    {
        $ids = $request->post('ids', []);

        if (is_string($ids)) {
            $ids = explode(',', $ids);
        }

        $result = $this->bannerService->batchDelete($ids);

        return json($result);
    }

    /**
     * 更新轮播图状态
     *
     * @param Request $request 请求对象
     * @return Response
     */
    public function bannerStatus(Request $request): Response
    {
        $id     = (int)$request->post('id', 0);
        $status = (int)$request->post('status', 0);

        $result = $this->bannerService->updateStatus($id, $status);

        return json($result);
    }
    
    
    /**
     * ============================================
     * 其他营销功能（预留）
     * ============================================
     */
    
    /**
     * Công bố管理
     */
    public function notice(Request $request)
    {
        return view('marketing/notice/index');
    }
    
    /**
     * 站内信
     */
    public function message(Request $request)
    {
        return view('marketing/message/index');
    }
    
    /**
     * Hoạt động管理
     */
    public function activity(Request $request)
    {
        return view('marketing/activity/index');
    }
    
    /**
     * Hoàn trảCài đặt
     */
    public function rebate(Request $request)
    {
        return view('marketing/rebate/index');
    }
    
    /**
     * Thăng cấp奖励
     */
    public function level_reward(Request $request)
    {
        return view('marketing/level-reward/index');
    }

    /**
     * ============================================
     * Hoạt động管理 API（Art Design Pro 前端专用）
     * ============================================
     */

    /**
     * Hoạt động列表
     * GET /app/admin/api/activity/list
     */
    public function activityList(Request $request)
    {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 20);
        $title = $request->get('title', '');
        $type = $request->get('type', '');
        $category = $request->get('category', '');
        $status = $request->get('status', '');

        $query = Db::table('caipiao_huodong');

        if ($title) {
            $query->where('title', 'like', "%{$title}%");
        }

        if ($type) {
            // 支持按type_codehoặctype字段筛选
            $query->where(function($q) use ($type) {
                $q->where('type', $type)->orWhere('type_code', $type);
            });
        }

        if ($category) {
            // category字段存储为JSON数组hoặc逗号分隔字符串，支持模糊匹配
            $query->where('category', 'like', "%{$category}%");
        }

        if ($status !== '') {
            $query->where('status', $status);
        }

        // Lấy总数
        $count = $query->count();

        // 分页
        $offset = ($page - 1) * $limit;
        $list = $query->orderBy('sort', 'desc')
            ->orderBy('id', 'desc')
            ->offset($offset)
            ->limit($limit)
            ->get();

        // Hoạt động类型映射
        $typeMap = [
            'deposit' => 'Nạp tiềnHoạt động',
            'rebate' => 'Hoàn trảHoạt động',
            'bonus' => '彩金Hoạt động',
            'vip' => 'VIPHoạt động',
            'other' => '其他Hoạt động',
        ];

        $result = [];
        foreach ($list as $item) {
            $row = (array)$item;
            $row['type_text'] = $typeMap[$row['type']] ?? $row['type'];
            $row['status_text'] = $row['status'] == 1 ? '启用' : '禁用';
            
            // Hoạt độngThời gian
            $row['start_date_text'] = $row['start_date'] ?? '';
            $row['end_date_text'] = $row['end_date'] ?? '';
            
            if ($row['start_time']) {
                $row['start_time_text'] = date('Y-m-d H:i:s', $row['start_time']);
            } else {
                $row['start_time_text'] = '';
            }
            
            if ($row['end_time']) {
                $row['end_time_text'] = date('Y-m-d H:i:s', $row['end_time']);
            } else {
                $row['end_time_text'] = '';
            }

            // 创建/更新Thời gian
            $row['created_at_text'] = $row['created_at'] ? date('Y-m-d H:i:s', $row['created_at']) : '';
            $row['updated_at_text'] = $row['updated_at'] ? date('Y-m-d H:i:s', $row['updated_at']) : '';

            // 解析分类为数组（支持JSON数组和逗号分隔字符串）
            if (!empty($row['category'])) {
                $decoded = json_decode($row['category'], true);
                if (is_array($decoded)) {
                    $row['category'] = $decoded;
                } elseif (strpos($row['category'], ',') !== false) {
                    $row['category'] = array_map('trim', explode(',', $row['category']));
                } else {
                    $row['category'] = [$row['category']];
                }
            } else {
                $row['category'] = [];
            }

            $result[] = $row;
        }

        return json([
            'code' => 0,
            'msg' => 'success',
            'data' => $result,
            'count' => $count
        ]);
    }

    /**
     * Hoạt độngChi tiết
     * GET /app/admin/api/activity/detail
     */
    public function activityDetail(Request $request)
    {
        $id = $request->get('id');
        if (!$id) {
            return json(['code' => 1, 'msg' => 'Tham số không hợp lệ']);
        }

        $item = Db::table('caipiao_huodong')->where('id', $id)->first();
        if (!$item) {
            return json(['code' => 1, 'msg' => 'Hoạt độngkhông tồn tại']);
        }

        $data = (array)$item;
        
        // 格式化Thời gian
        if ($data['start_time']) {
            $data['start_time_text'] = date('Y-m-d H:i:s', $data['start_time']);
        }
        if ($data['end_time']) {
            $data['end_time_text'] = date('Y-m-d H:i:s', $data['end_time']);
        }

        // 解析分类为数组（支持JSON数组和逗号分隔字符串）
        if (!empty($data['category'])) {
            $decoded = json_decode($data['category'], true);
            if (is_array($decoded)) {
                $data['category'] = $decoded;
            } elseif (strpos($data['category'], ',') !== false) {
                $data['category'] = array_map('trim', explode(',', $data['category']));
            } else {
                $data['category'] = [$data['category']];
            }
        } else {
            $data['category'] = [];
        }

        return json(['code' => 0, 'msg' => 'ok', 'data' => $data]);
    }

    /**
     * ThêmHoạt động
     * POST /app/admin/api/activity/add
     */
    public function activityAdd(Request $request)
    {
        $title = $request->post('title');
        $desc = $request->post('desc', '');
        $content = $request->post('content', '');
        $terms = $request->post('terms', '');
        $banner = $request->post('banner', '');
        $images = $request->post('images', '');
        $type = $request->post('type', 'other');
        $category = $request->post('category', []); // Trò chơi分类(数组)
        $type_code = $request->post('type_code', ''); // Hoạt động类型
        $startDate = $request->post('start_date');
        $endDate = $request->post('end_date');
        $status = $request->post('status', 1);
        $sort = $request->post('sort', 0);
        $jumpType = (int)$request->post('jump_type', 0); // 跳转类型: 0=默认Chi tiết页 1=签到弹窗 2=自定义URL
        $jumpUrl = $request->post('jump_url', ''); // 跳转URL
        $requiredDeposit = (float)$request->post('required_deposit', 0); // 签到所需Nạp tiền
        $requiredBet = (float)$request->post('required_bet', 0); // 签到所需Đặt cược

        if (!$title) {
            return json(['code' => 1, 'msg' => 'Hoạt động标题không được để trống']);
        }

        $now = time();
        $startTime = $startDate ? strtotime($startDate) : null;
        $endTime = $endDate ? strtotime($endDate) : null;

        $id = Db::table('caipiao_huodong')->insertGetId([
            'title' => $title,
            'desc' => $desc,
            'content' => $content,
            'terms' => $terms,
            'banner' => $banner,
            'images' => is_array($images) ? json_encode($images) : $images,
            'type' => $type,
            'category' => is_array($category) ? json_encode($category) : $category,
            'type_code' => $type_code,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'status' => $status,
            'sort' => $sort,
            'jump_type' => $jumpType,
            'jump_url' => $jumpUrl,
            'required_deposit' => $requiredDeposit,
            'required_bet' => $requiredBet,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return json(['code' => 0, 'msg' => 'ThêmThành công', 'data' => ['id' => $id]]);
    }

    /**
     * 编辑Hoạt động
     * POST /app/admin/api/activity/edit
     */
    public function activityEdit(Request $request)
    {
        $id = $request->post('id');
        if (!$id) {
            return json(['code' => 1, 'msg' => 'Tham số không hợp lệ']);
        }

        $item = Db::table('caipiao_huodong')->where('id', $id)->first();
        if (!$item) {
            return json(['code' => 1, 'msg' => 'Hoạt độngkhông tồn tại']);
        }

        $data = ['updated_at' => time()];

        $title = $request->post('title');
        if ($title !== null) $data['title'] = $title;

        $desc = $request->post('desc');
        if ($desc !== null) $data['desc'] = $desc;

        $content = $request->post('content');
        if ($content !== null) $data['content'] = $content;

        $terms = $request->post('terms');
        if ($terms !== null) $data['terms'] = $terms;

        $banner = $request->post('banner');
        if ($banner !== null) $data['banner'] = $banner;

        $images = $request->post('images');
        if ($images !== null) {
            $data['images'] = is_array($images) ? json_encode($images) : $images;
        }

        $type = $request->post('type');
        if ($type !== null) $data['type'] = $type;

        $category = $request->post('category');
        if ($category !== null) $data['category'] = is_array($category) ? json_encode($category) : $category;

        $type_code = $request->post('type_code');
        if ($type_code !== null) $data['type_code'] = $type_code;

        $startDate = $request->post('start_date');
        if ($startDate !== null) {
            $data['start_date'] = $startDate;
            $data['start_time'] = $startDate ? strtotime($startDate) : null;
        }

        $endDate = $request->post('end_date');
        if ($endDate !== null) {
            $data['end_date'] = $endDate;
            $data['end_time'] = $endDate ? strtotime($endDate) : null;
        }

        $status = $request->post('status');
        if ($status !== null) $data['status'] = $status;

        $sort = $request->post('sort');
        if ($sort !== null) $data['sort'] = $sort;

        $jumpType = $request->post('jump_type');
        if ($jumpType !== null) $data['jump_type'] = (int)$jumpType;

        $jumpUrl = $request->post('jump_url');
        if ($jumpUrl !== null) $data['jump_url'] = $jumpUrl;

        $requiredDeposit = $request->post('required_deposit');
        if ($requiredDeposit !== null) $data['required_deposit'] = (float)$requiredDeposit;

        $requiredBet = $request->post('required_bet');
        if ($requiredBet !== null) $data['required_bet'] = (float)$requiredBet;

        Db::table('caipiao_huodong')->where('id', $id)->update($data);

        return json(['code' => 0, 'msg' => 'SửaThành công']);
    }

    /**
     * XóaHoạt động
     * POST /app/admin/api/activity/delete
     */
    public function activityDelete(Request $request)
    {
        $id = $request->post('id');
        $ids = $request->post('ids', []);

        if (!$id && empty($ids)) {
            return json(['code' => 1, 'msg' => 'Tham số không hợp lệ']);
        }

        $deleteIds = $id ? [$id] : $ids;
        
        Db::table('caipiao_huodong')->whereIn('id', $deleteIds)->delete();

        return json(['code' => 0, 'msg' => 'XóaThành công']);
    }

    /**
     * 更新Hoạt động状态
     * POST /app/admin/api/activity/status
     */
    public function activityStatus(Request $request)
    {
        $id = $request->post('id');
        $status = $request->post('status');

        if (!$id || $status === null) {
            return json(['code' => 1, 'msg' => 'Tham số không hợp lệ']);
        }

        Db::table('caipiao_huodong')->where('id', $id)->update([
            'status' => $status,
            'updated_at' => time(),
        ]);

        return json(['code' => 0, 'msg' => '状态更新Thành công']);
    }

    /**
     * Hoạt động类型选项（下拉框用）
     * GET /app/admin/api/activity/type-options
     */
    public function activityTypeOptions(Request $request)
    {
        // 返回所有Hoạt động类型（Hoạt động分类已独立到 caipiao_activity_category 表）
        $types = Db::table('caipiao_activity_type')
            ->where('status', 1)
            ->orderBy('sort', 'desc')
            ->orderBy('id', 'asc')
            ->get(['code as value', 'name as label']);

        return json(['code' => 0, 'msg' => 'ok', 'data' => $types]);
    }

    /**
     * ============================================
     * Hoạt động类型管理 API
     * ============================================
     */

    /**
     * Hoạt động类型列表
     * GET /app/admin/api/activity/type-list
     */
    public function activityTypeList(Request $request)
    {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 20);
        $name = $request->get('name', '');
        $status = $request->get('status', '');

        $query = Db::table('caipiao_activity_type');

        if ($name) {
            $query->where('name', 'like', "%{$name}%");
        }

        if ($status !== '') {
            $query->where('status', $status);
        }

        $count = $query->count();

        $offset = ($page - 1) * $limit;
        $list = $query->orderBy('sort', 'desc')
            ->orderBy('id', 'asc')
            ->offset($offset)
            ->limit($limit)
            ->get();

        $result = [];
        foreach ($list as $item) {
            $row = (array)$item;
            $row['status_text'] = $row['status'] == 1 ? '启用' : '禁用';
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
     * ThêmHoạt động类型
     * POST /app/admin/api/activity/type-add
     */
    public function activityTypeAdd(Request $request)
    {
        $name = $request->post('name');
        $code = $request->post('code');
        $icon = $request->post('icon', '');
        $sort = $request->post('sort', 0);
        $status = $request->post('status', 1);
        $remark = $request->post('remark', '');

        if (!$name) {
            return json(['code' => 1, 'msg' => '类型名称không được để trống']);
        }

        if (!$code) {
            return json(['code' => 1, 'msg' => '类型代码không được để trống']);
        }

        // 检查代码是否đã tồn tại
        $exists = Db::table('caipiao_activity_type')->where('code', $code)->exists();
        if ($exists) {
            return json(['code' => 1, 'msg' => '类型代码đã tồn tại']);
        }

        $now = time();
        $id = Db::table('caipiao_activity_type')->insertGetId([
            'name' => $name,
            'code' => $code,
            'icon' => $icon,
            'sort' => $sort,
            'status' => $status,
            'remark' => $remark,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return json(['code' => 0, 'msg' => 'ThêmThành công', 'data' => ['id' => $id]]);
    }

    /**
     * 编辑Hoạt động类型
     * POST /app/admin/api/activity/type-edit
     */
    public function activityTypeEdit(Request $request)
    {
        $id = $request->post('id');
        if (!$id) {
            return json(['code' => 1, 'msg' => 'Tham số không hợp lệ']);
        }

        $item = Db::table('caipiao_activity_type')->where('id', $id)->first();
        if (!$item) {
            return json(['code' => 1, 'msg' => '类型không tồn tại']);
        }

        $data = ['updated_at' => time()];

        $name = $request->post('name');
        if ($name !== null) $data['name'] = $name;

        $code = $request->post('code');
        if ($code !== null) {
            // 检查代码是否被其他lịch sử使用
            $exists = Db::table('caipiao_activity_type')
                ->where('code', $code)
                ->where('id', '!=', $id)
                ->exists();
            if ($exists) {
                return json(['code' => 1, 'msg' => '类型代码已被使用']);
            }
            $data['code'] = $code;
        }

        $icon = $request->post('icon');
        if ($icon !== null) $data['icon'] = $icon;

        $sort = $request->post('sort');
        if ($sort !== null) $data['sort'] = $sort;

        $status = $request->post('status');
        if ($status !== null) $data['status'] = $status;

        $remark = $request->post('remark');
        if ($remark !== null) $data['remark'] = $remark;

        Db::table('caipiao_activity_type')->where('id', $id)->update($data);

        return json(['code' => 0, 'msg' => 'SửaThành công']);
    }

    /**
     * XóaHoạt động类型
     * POST /app/admin/api/activity/type-delete
     */
    public function activityTypeDelete(Request $request)
    {
        $id = $request->post('id');
        $ids = $request->post('ids', []);

        if (!$id && empty($ids)) {
            return json(['code' => 1, 'msg' => 'Tham số không hợp lệ']);
        }

        $deleteIds = $id ? [$id] : $ids;

        // 检查是否有Hoạt động使用该类型
        $types = Db::table('caipiao_activity_type')
            ->whereIn('id', $deleteIds)
            ->pluck('code')
            ->toArray();

        $usedCount = Db::table('caipiao_huodong')
            ->whereIn('type', $types)
            ->count();

        if ($usedCount > 0) {
            return json(['code' => 1, 'msg' => '该类型下有Hoạt động，无法Xóa']);
        }

        Db::table('caipiao_activity_type')->whereIn('id', $deleteIds)->delete();

        return json(['code' => 0, 'msg' => 'XóaThành công']);
    }

    /**
     * 更新Hoạt động类型状态
     * POST /app/admin/api/activity/type-status
     */
    public function activityTypeStatus(Request $request)
    {
        $id = $request->post('id');
        $status = $request->post('status');

        if (!$id || $status === null) {
            return json(['code' => 1, 'msg' => 'Tham số không hợp lệ']);
        }

        Db::table('caipiao_activity_type')->where('id', $id)->update([
            'status' => $status,
            'updated_at' => time(),
        ]);

        return json(['code' => 0, 'msg' => '状态更新Thành công']);
    }

    /**
     * ============================================
     * 赠送Hoạt độngCài đặt API
     * ============================================
     */

    /**
     * Lấy赠送Hoạt động配置
     * GET /app/admin/api/gift/config
     */
    public function giftConfig(Request $request)
    {
        // Lấy所有Cài đặt
        $settings = Db::table('caipiao_setting')->pluck('value', 'name')->toArray();

        // Nạp tiềnHoạt động配置（5档）
        $rechargeConfig = [];
        for ($i = 0; $i <= 4; $i++) {
            $rechargeConfig[] = [
                'level' => $i,
                'money' => $settings["activity_cz{$i}_money"] ?? '',
                'percent' => $settings["activity_cz{$i}_zsmoney"] ?? '',
            ];
        }

        // 日消费Hoạt động配置（3档）
        $dailyConsumeConfig = [];
        for ($i = 0; $i <= 2; $i++) {
            $dailyConsumeConfig[] = [
                'level' => $i,
                'money' => $settings["riCommissionBase{$i}_0"] ?? '',
                'self_percent' => $settings["riCommissionBase{$i}_1"] ?? '',
                'parent_percent' => $settings["riCommissionBase{$i}_2"] ?? '',
            ];
        }

        // 月消费Hoạt động配置（3档）
        $monthlyConsumeConfig = [];
        for ($i = 0; $i <= 2; $i++) {
            $monthlyConsumeConfig[] = [
                'level' => $i,
                'money' => $settings["yueCommissionBase{$i}_0"] ?? '',
                'self_percent' => $settings["yueCommissionBase{$i}_1"] ?? '',
                'parent_percent' => $settings["yueCommissionBase{$i}_2"] ?? '',
            ];
        }

        // 日亏损Hoạt động配置（3档）
        $dailyLossConfig = [];
        for ($i = 0; $i <= 2; $i++) {
            $dailyLossConfig[] = [
                'level' => $i,
                'money' => $settings["riKuisunBase{$i}_0"] ?? '',
                'self_percent' => $settings["riKuisunBase{$i}_1"] ?? '',
                'parent_percent' => $settings["riKuisunBase{$i}_2"] ?? '',
            ];
        }

        // 月亏损Hoạt động配置（3档）
        $monthlyLossConfig = [];
        for ($i = 0; $i <= 2; $i++) {
            $monthlyLossConfig[] = [
                'level' => $i,
                'money' => $settings["yueKuisunBase{$i}_0"] ?? '',
                'self_percent' => $settings["yueKuisunBase{$i}_1"] ?? '',
                'parent_percent' => $settings["yueKuisunBase{$i}_2"] ?? '',
            ];
        }

        // Đại lý分红配置（4档）
        $agentBonusConfig = [];
        for ($i = 0; $i <= 3; $i++) {
            $agentBonusConfig[] = [
                'level' => $i,
                'money' => $settings["agentBonusBase{$i}_0"] ?? '',
                'percent' => $settings["agentBonusBase{$i}_1"] ?? '',
            ];
        }

        // 其他配置
        $otherConfig = [
            'bindcard_amount' => $settings['bindcardamount'] ?? '0',
        ];

        return json([
            'code' => 0,
            'msg' => 'ok',
            'data' => [
                'recharge' => $rechargeConfig,           // Nạp tiềnHoạt động
                'daily_consume' => $dailyConsumeConfig,   // 日消费Hoạt động
                'monthly_consume' => $monthlyConsumeConfig, // 月消费Hoạt động
                'daily_loss' => $dailyLossConfig,         // 日亏损Hoạt động
                'monthly_loss' => $monthlyLossConfig,     // 月亏损Hoạt động
                'agent_bonus' => $agentBonusConfig,       // Đại lý分红
                'other' => $otherConfig,                  // 其他
            ]
        ]);
    }

    /**
     * Lưu赠送Hoạt động配置
     * POST /app/admin/api/gift/save
     */
    public function giftSave(Request $request)
    {
        $data = $request->post();
        $updates = [];

        // Nạp tiềnHoạt động
        if (isset($data['recharge']) && is_array($data['recharge'])) {
            foreach ($data['recharge'] as $item) {
                $i = $item['level'] ?? 0;
                if (isset($item['money'])) {
                    $updates["activity_cz{$i}_money"] = $item['money'];
                }
                if (isset($item['percent'])) {
                    $updates["activity_cz{$i}_zsmoney"] = $item['percent'];
                }
            }
        }

        // 日消费Hoạt động
        if (isset($data['daily_consume']) && is_array($data['daily_consume'])) {
            foreach ($data['daily_consume'] as $item) {
                $i = $item['level'] ?? 0;
                if (isset($item['money'])) {
                    $updates["riCommissionBase{$i}_0"] = $item['money'];
                }
                if (isset($item['self_percent'])) {
                    $updates["riCommissionBase{$i}_1"] = $item['self_percent'];
                }
                if (isset($item['parent_percent'])) {
                    $updates["riCommissionBase{$i}_2"] = $item['parent_percent'];
                }
            }
        }

        // 月消费Hoạt động
        if (isset($data['monthly_consume']) && is_array($data['monthly_consume'])) {
            foreach ($data['monthly_consume'] as $item) {
                $i = $item['level'] ?? 0;
                if (isset($item['money'])) {
                    $updates["yueCommissionBase{$i}_0"] = $item['money'];
                }
                if (isset($item['self_percent'])) {
                    $updates["yueCommissionBase{$i}_1"] = $item['self_percent'];
                }
                if (isset($item['parent_percent'])) {
                    $updates["yueCommissionBase{$i}_2"] = $item['parent_percent'];
                }
            }
        }

        // 日亏损Hoạt động
        if (isset($data['daily_loss']) && is_array($data['daily_loss'])) {
            foreach ($data['daily_loss'] as $item) {
                $i = $item['level'] ?? 0;
                if (isset($item['money'])) {
                    $updates["riKuisunBase{$i}_0"] = $item['money'];
                }
                if (isset($item['self_percent'])) {
                    $updates["riKuisunBase{$i}_1"] = $item['self_percent'];
                }
                if (isset($item['parent_percent'])) {
                    $updates["riKuisunBase{$i}_2"] = $item['parent_percent'];
                }
            }
        }

        // 月亏损Hoạt động
        if (isset($data['monthly_loss']) && is_array($data['monthly_loss'])) {
            foreach ($data['monthly_loss'] as $item) {
                $i = $item['level'] ?? 0;
                if (isset($item['money'])) {
                    $updates["yueKuisunBase{$i}_0"] = $item['money'];
                }
                if (isset($item['self_percent'])) {
                    $updates["yueKuisunBase{$i}_1"] = $item['self_percent'];
                }
                if (isset($item['parent_percent'])) {
                    $updates["yueKuisunBase{$i}_2"] = $item['parent_percent'];
                }
            }
        }

        // Đại lý分红
        if (isset($data['agent_bonus']) && is_array($data['agent_bonus'])) {
            foreach ($data['agent_bonus'] as $item) {
                $i = $item['level'] ?? 0;
                if (isset($item['money'])) {
                    $updates["agentBonusBase{$i}_0"] = $item['money'];
                }
                if (isset($item['percent'])) {
                    $updates["agentBonusBase{$i}_1"] = $item['percent'];
                }
            }
        }

        // 其他
        if (isset($data['other']) && is_array($data['other'])) {
            if (isset($data['other']['bindcard_amount'])) {
                $updates['bindcardamount'] = $data['other']['bindcard_amount'];
            }
        }

        // 批量更新Cài đặt
        foreach ($updates as $name => $value) {
            Db::table('caipiao_setting')->updateOrInsert(
                ['name' => $name],
                ['name' => $name, 'value' => $value]
            );
        }

        return json(['code' => 0, 'msg' => 'LưuThành công']);
    }

    /**
     * ============================================
     * 人工赠送（给Thành viên加款）
     * ============================================
     */

    /**
     * 人工赠送彩金
     * POST /app/admin/api/gift/send
     */
    public function giftSend(Request $request)
    {
        $username = $request->post('username');
        $amount = $request->post('amount');
        $type = $request->post('type', 'adminadd'); // adminadd=加款, adminsub=扣款
        $remark = $request->post('remark', '');

        if (!$username) {
            return json(['code' => 1, 'msg' => '请输入Tên người dùng']);
        }

        $amount = floatval($amount);
        if ($amount <= 0) {
            return json(['code' => 1, 'msg' => 'Số tiền必须大于0']);
        }

        // 查找Người dùng
        $member = Db::table('caipiao_member')->where('username', $username)->first();
        if (!$member) {
            return json(['code' => 1, 'msg' => 'Người dùng không tồn tại']);
        }

        $beforeBalance = $member->balance;
        $afterBalance = $type === 'adminsub' ? $beforeBalance - $amount : $beforeBalance + $amount;

        if ($afterBalance < 0) {
            return json(['code' => 1, 'msg' => 'Số dư không đủ']);
        }

        // 生成Vòng cược号
        $trano = 'GF' . date('YmdHis') . rand(1000, 9999);

        Db::beginTransaction();
        try {
            // 更新Số dư
            Db::table('caipiao_member')
                ->where('id', $member->id)
                ->update(['balance' => $afterBalance]);

            // lịch sử账变
            Db::table('caipiao_fuddetail')->insert([
                'trano' => $trano,
                'uid' => $member->id,
                'username' => $member->username,
                'type' => $type,
                'typename' => $type === 'adminsub' ? '管理员扣款' : '管理员加款',
                'amount' => $type === 'adminsub' ? -$amount : $amount,
                'amountbefor' => $beforeBalance,
                'amountafter' => $afterBalance,
                'oddtime' => time(),
                'remark' => $remark ?: ($type === 'adminsub' ? '管理员扣款' : '管理员赠送彩金'),
            ]);

            Db::commit();

            return json([
                'code' => 0,
                'msg' => 'Thao tác thành công',
                'data' => [
                    'username' => $username,
                    'amount' => $amount,
                    'before' => $beforeBalance,
                    'after' => $afterBalance,
                    'trano' => $trano,
                ]
            ]);
        } catch (\Exception $e) {
            Db::rollBack();
            return json(['code' => 1, 'msg' => 'Thao tác thất bại：' . $e->getMessage()]);
        }
    }

    /**
     * 批量赠送彩金
     * POST /app/admin/api/gift/batch-send
     */
    public function giftBatchSend(Request $request)
    {
        $usernames = $request->post('usernames', []); // Tên người dùng数组
        $amount = $request->post('amount');
        $remark = $request->post('remark', '批量赠送彩金');

        if (empty($usernames)) {
            return json(['code' => 1, 'msg' => 'Vui lòng chọnNgười dùng']);
        }

        $amount = floatval($amount);
        if ($amount <= 0) {
            return json(['code' => 1, 'msg' => 'Số tiền必须大于0']);
        }

        $success = 0;
        $failed = 0;
        $errors = [];

        foreach ($usernames as $username) {
            $member = Db::table('caipiao_member')->where('username', $username)->first();
            if (!$member) {
                $failed++;
                $errors[] = "{$username}: Người dùng không tồn tại";
                continue;
            }

            $trano = 'GF' . date('YmdHis') . rand(1000, 9999);
            $beforeBalance = $member->balance;
            $afterBalance = $beforeBalance + $amount;

            try {
                Db::table('caipiao_member')
                    ->where('id', $member->id)
                    ->update(['balance' => $afterBalance]);

                Db::table('caipiao_fuddetail')->insert([
                    'trano' => $trano,
                    'uid' => $member->id,
                    'username' => $member->username,
                    'type' => 'adminadd',
                    'typename' => '管理员加款',
                    'amount' => $amount,
                    'amountbefor' => $beforeBalance,
                    'amountafter' => $afterBalance,
                    'oddtime' => time(),
                    'remark' => $remark,
                ]);

                $success++;
            } catch (\Exception $e) {
                $failed++;
                $errors[] = "{$username}: " . $e->getMessage();
            }
        }

        return json([
            'code' => 0,
            'msg' => "Thành công {$success} 个，Thất bại {$failed} 个",
            'data' => [
                'success' => $success,
                'failed' => $failed,
                'errors' => $errors,
            ]
        ]);
    }

    /**
     * 赠送lịch sử列表
     * GET /app/admin/api/gift/records
     */
    public function giftRecords(Request $request)
    {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 20);
        $username = $request->get('username', '');
        $type = $request->get('type', '');
        $startDate = $request->get('start_date', '');
        $endDate = $request->get('end_date', '');

        $query = Db::table('caipiao_fuddetail')
            ->whereIn('type', ['adminadd', 'adminsub', 'activity_cz', 'activity_czzs', 
                              'activity_bindcard', 'activity_rxf', 'activity_rks',
                              'activity_yxf', 'activity_yks', 'jinjishenhe']);

        if ($username) {
            $query->where('username', 'like', "%{$username}%");
        }

        if ($type) {
            $query->where('type', $type);
        }

        if ($startDate) {
            $query->where('oddtime', '>=', strtotime($startDate));
        }

        if ($endDate) {
            $query->where('oddtime', '<=', strtotime($endDate) + 86400);
        }

        $count = $query->count();

        $offset = ($page - 1) * $limit;
        $list = $query->orderBy('id', 'desc')
            ->offset($offset)
            ->limit($limit)
            ->get();

        $typeMap = [
            'adminadd' => '管理员加款',
            'adminsub' => '管理员扣款',
            'activity_cz' => 'Nạp tiềnHoạt động',
            'activity_czzs' => 'Nạp tiền赠送',
            'activity_bindcard' => '绑卡赠送',
            'activity_rxf' => '日洗返',
            'activity_rks' => '日快送',
            'activity_yxf' => '月洗返',
            'activity_yks' => '月快送',
            'jinjishenhe' => 'Thăng cấp奖励',
        ];

        $result = [];
        foreach ($list as $item) {
            $row = (array)$item;
            $row['type_text'] = $typeMap[$row['type']] ?? $row['typename'];
            $row['time_text'] = date('Y-m-d H:i:s', $row['oddtime']);
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
     * 赠送类型选项
     * GET /app/admin/api/gift/type-options
     */
    public function giftTypeOptions(Request $request)
    {
        $types = [
            ['value' => 'adminadd', 'label' => '管理员加款'],
            ['value' => 'adminsub', 'label' => '管理员扣款'],
            ['value' => 'activity_cz', 'label' => 'Nạp tiềnHoạt động'],
            ['value' => 'activity_czzs', 'label' => 'Nạp tiền赠送'],
            ['value' => 'activity_bindcard', 'label' => '绑卡赠送'],
            ['value' => 'activity_rxf', 'label' => '日洗返'],
            ['value' => 'activity_rks', 'label' => '日快送'],
            ['value' => 'activity_yxf', 'label' => '月洗返'],
            ['value' => 'activity_yks', 'label' => '月快送'],
            ['value' => 'jinjishenhe', 'label' => 'Thăng cấp奖励'],
        ];

        return json(['code' => 0, 'msg' => 'ok', 'data' => $types]);
    }
}









