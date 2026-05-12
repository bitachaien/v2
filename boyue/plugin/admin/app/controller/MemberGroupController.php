<?php
namespace plugin\admin\app\controller;

use support\Request;
use support\Db;

/**
 * 会员组管理控制器
 */
class MemberGroupController extends Base
{
    /**
     * 会员组列表页面
     */
    public function index(Request $request)
    {
        return view('membergroup/index');
    }

    /**
     * 获取会员组列表
     */
    public function list(Request $request)
    {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 20);
        
        // 查询会员组列表
        $query = Db::table('caipiao_membergroup')
            ->orderBy('groupid', 'asc');
        
        // 获取总数
        $count = $query->count();
        
        // 分页
        $offset = ($page - 1) * $limit;
        $list = $query->offset($offset)->limit($limit)->get();
        
        // 处理数据
        $result = [];
        foreach ($list as $item) {
            $row = (array)$item;
            $result[] = $row;
        }
        
        // 返回格式（兼容 Layui 和 Art Design Pro）
        return $this->json(0, 'success', [
            'list' => $result,
            'total' => $count
        ]);
    }

    /**
     * 添加会员组页面
     */
    public function add(Request $request)
    {
        if ($request->method() === 'POST') {
            return $this->save($request);
        }
        
        return view('membergroup/add');
    }

    /**
     * 编辑会员组页面
     */
    public function edit(Request $request)
    {
        if ($request->method() === 'POST') {
            return $this->save($request);
        }
        
        $id = $request->get('id');
        
        if (!$id) {
            return '<script>alert("参数错误");history.back();</script>';
        }
        
        // 查询会员组信息
        $info = Db::table('caipiao_membergroup')->where('groupid', $id)->first();
        
        if (!$info) {
            return '<script>alert("会员组不存在");history.back();</script>';
        }
        
        return view('membergroup/add', ['info' => (array)$info]);
    }

    /**
     * 保存会员组
     */
    public function save(Request $request)
    {
        // 兼容 id 和 groupid 两种参数名
        $id = $request->post('id') ?: $request->post('groupid');
        $groupname = $request->post('groupname');
        $touhan = $request->post('touhan');
        $fanshui = $request->post('fanshui');
        $shengjiedu = $request->post('shengjiedu');
        $jjje = $request->post('jjje');
        $freeWithdrawTimes = $request->post('free_withdraw_times');
        $weeklyBetting = $request->post('weekly_betting');
        $weeklyBonus = $request->post('weekly_bonus');
        $monthlyBetting = $request->post('monthly_betting');
        $monthlyBonus = $request->post('monthly_bonus');
        
        if (empty($groupname)) {
            return $this->json(1, '会员组名称不能为空');
        }
        
        $data = [
            'groupname' => $groupname,
            'touhan' => $touhan ?? '',
            'fanshui' => $fanshui ?? '0',
            'shengjiedu' => $shengjiedu ?? '0',
            'jjje' => $jjje ?? '0',
            'free_withdraw_times' => $freeWithdrawTimes ?? 0,
            'weekly_betting' => $weeklyBetting ?? 0,
            'weekly_bonus' => $weeklyBonus ?? 0,
            'monthly_betting' => $monthlyBetting ?? 0,
            'monthly_bonus' => $monthlyBonus ?? 0,
            'addtime' => time(),
        ];
        
        if ($id) {
            // 更新
            unset($data['addtime']);
            $result = Db::table('caipiao_membergroup')
                ->where('groupid', $id)
                ->update($data);
            
            if ($result !== false) {
                return $this->json(0, '修改成功');
            } else {
                return $this->json(1, '修改失败');
            }
        } else {
            // 添加
            // 获取当前最大排序值，新增排在最后
            $maxOrder = Db::table('caipiao_membergroup')->max('listorder') ?? 0;
            
            $data['groupstatus'] = 1;
            $data['isagent'] = 0;
            $data['isdefautreg'] = 0;
            $data['lowest'] = 2;
            $data['highest'] = 0;
            $data['listorder'] = $maxOrder + 1;
            $data['level'] = 0;
            
            $result = Db::table('caipiao_membergroup')->insert($data);
            
            if ($result) {
                return $this->json(0, '添加成功');
            } else {
                return $this->json(1, '添加失败');
            }
        }
    }

    /**
     * 删除会员组
     */
    public function delete(Request $request)
    {
        $id = $request->post('id');
        
        if (!$id) {
            return $this->json(1, '参数错误');
        }
        
        // 检查是否有会员使用该组
        $memberCount = Db::table('caipiao_member')->where('groupid', $id)->count();
        
        if ($memberCount > 0) {
            return $this->json(1, '该会员组下还有会员，无法删除');
        }
        
        $result = Db::table('caipiao_membergroup')->where('groupid', $id)->delete();
        
        if ($result) {
            return $this->json(0, '删除成功');
        } else {
            return $this->json(1, '删除失败');
        }
    }

    /**
     * 限额设置页面
     */
    public function setLimit(Request $request)
    {
        $id = $request->get('id');
        
        if (!$id) {
            return $this->json(1, '参数错误');
        }
        
        // 查询会员组信息
        $info = Db::table('caipiao_membergroup')->where('groupid', $id)->first();
        
        if (!$info) {
            return $this->json(1, '会员组不存在');
        }
        
        $info = (array)$info;
        
        // 解析配置
        $configs = [];
        if (!empty($info['configs'])) {
            $configs = @unserialize($info['configs']);
            if ($configs === false) {
                $configs = [];
            }
        }
        
        // 获取K3玩法列表（默认以K3为例）
        $plays = Db::table('caipiao_wanfa')
            ->where('typeid', 'k3')
            ->orderBy('id', 'asc')
            ->get();
        
        return view('membergroup/set-limit', [
            'info' => $info,
            'configs' => $configs,
            'plays' => $plays
        ]);
    }

    /**
     * 保存限额配置
     */
    public function saveLimitConfig(Request $request)
    {
        $id = $request->post('id');
        $configs = $request->post('configs', []);
        
        if (!$id) {
            return $this->json(1, '参数错误');
        }
        
        // 处理配置数据（转为整数）
        $configData = [];
        foreach ($configs as $key => $value) {
            $configData[$key] = intval($value);
        }
        
        // 序列化配置
        $configStr = serialize($configData);
        
        $result = Db::table('caipiao_membergroup')
            ->where('groupid', $id)
            ->update(['configs' => $configStr]);
        
        if ($result !== false) {
            return $this->json(0, '设置成功');
        } else {
            return $this->json(1, '设置失败');
        }
    }

    /**
     * 获取限额配置 (Art Design Pro 前端专用)
     * GET /app/admin/membergroup/get-limit
     */
    public function getLimit(Request $request)
    {
        $id = $request->get('id');
        
        if (!$id) {
            return $this->json(1, '参数错误');
        }
        
        // 查询会员组信息
        $info = Db::table('caipiao_membergroup')->where('groupid', $id)->first();
        
        if (!$info) {
            return $this->json(1, '会员组不存在');
        }
        
        $info = (array)$info;
        
        // 解析配置
        $configs = [];
        if (!empty($info['configs'])) {
            $configs = @unserialize($info['configs']);
            if ($configs === false) {
                $configs = [];
            }
        }
        
        // 获取玩法列表
        $plays = Db::table('caipiao_wanfa')
            ->orderBy('typeid', 'asc')
            ->orderBy('id', 'asc')
            ->get()
            ->toArray();
        
        return $this->json(0, 'ok', [
            'info' => $info,
            'configs' => $configs,
            'plays' => $plays
        ]);
    }
}

