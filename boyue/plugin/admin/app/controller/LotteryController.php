<?php

namespace plugin\admin\app\controller;

use support\Request;
use support\Db;

class LotteryController extends Base
{
    /**
     * 彩种列表（兼容旧版视图）
     */
    public function index(Request $request)
    {
        // 如果不是AJAX请求，返回视图
        if (!$request->header('x-requested-with')) {
            return view('lottery/index');
        }
        
        return $this->getLotteryData($request);
    }
    
    /**
     * 彩种列表 API（Art Design Pro 前端专用，纯 JSON）
     */
    public function apiIndex(Request $request)
    {
        return $this->getLotteryData($request);
    }
    
    /**
     * 获取彩种列表数据（公共方法）
     */
    private function getLotteryData(Request $request)
    {
        // 获取筛选参数
        $typeid = $request->get('typeid', '');
        $lotterytype = $request->get('lotterytype', '');
        $isopen = $request->get('isopen', '');
        $keyword = $request->get('keyword', '');
        // 兼容 current/size 和 page/limit 两种分页参数
        $page = $request->get('current', $request->get('page', 1));
        $limit = $request->get('size', $request->get('limit', 20));
        
        // 构建查询
        $query = Db::table('caipiao_caipiao');
        
        if (!empty($typeid)) {
            $query->where('typeid', $typeid);
        }
        
        if ($lotterytype !== '') {
            $query->where('lotterytype', $lotterytype);
        }
        
        if ($isopen !== '') {
            $query->where('isopen', $isopen);
        }
        
        if (!empty($keyword)) {
            $query->where(function ($q) use ($keyword) {
                $q->where('title', 'like', '%' . $keyword . '%')
                  ->orWhere('name', 'like', '%' . $keyword . '%');
            });
        }
        
        // 统计数据
        // iswh=1表示维护中, iswh=0表示正常; isopen=1表示启用, isopen=0表示禁用
        $stats = [
            'total' => Db::table('caipiao_caipiao')->count(),
            'active' => Db::table('caipiao_caipiao')->where('isopen', 1)->where('iswh', 0)->count(),  // 启用且不在维护
            'maintenance' => Db::table('caipiao_caipiao')->where('iswh', 1)->count(),  // 维护中
            'disabled' => Db::table('caipiao_caipiao')->where('isopen', 0)->count(),  // 禁用
        ];
        
        // 分页查询（按老版本排序：先按listorder，再按id）
        $total = $query->count();
        $offset = ($page - 1) * $limit;
        $list = $query->orderBy('listorder', 'asc')
            ->orderBy('id', 'asc')
            ->offset($offset)
            ->limit($limit)
            ->get()
            ->toArray();
        
        // 添加分类名称和期数
        $typeNames = [
            'ssc' => '时时彩',
            'k3' => '快三',
            'x5' => '11选5',
            'keno' => '快乐彩',
            'xy28' => '幸运28',
            'pk10' => 'PK10',
            'dpc' => '低频彩',
            'lhc' => '六合彩',
        ];
        
        foreach ($list as &$item) {
            $item = (array)$item;
            $item['typeid_name'] = $typeNames[$item['typeid']] ?? $item['typeid'];
            
            // 查询期数（从开奖表统计，使用name字段关联）
            $periods = Db::table('caipiao_kaijiang')
                ->where('name', $item['name'])
                ->count();
            $item['periods'] = $periods;
        }
        
        return $this->json(0, 'success', [
            'list' => $list,
            'total' => $total,
            'stats' => $stats
        ]);
    }
    
    /**
     * 编辑页面
     */
    public function edit(Request $request)
    {
        $id = $request->get('id');
        
        if (empty($id)) {
            return $this->json(1, '参数错误');
        }
        
        $lottery = Db::table('caipiao_caipiao')->where('id', $id)->first();
        
        if (!$lottery) {
            return $this->json(1, '彩种不存在');
        }
        
        return view('lottery/edit', ['lottery' => (array)$lottery]);
    }
    
    /**
     * 获取彩种详情
     */
    public function detail(Request $request)
    {
        $id = $request->get('id');
        
        if (empty($id)) {
            return $this->json(1, '参数错误');
        }
        
        $lottery = Db::table('caipiao_caipiao')->where('id', $id)->first();
        
        if (!$lottery) {
            return $this->json(1, '彩种不存在');
        }
        
        return $this->json(0, 'success', (array)$lottery);
    }
    
    /**
     * 保存彩种
     */
    public function save(Request $request)
    {
        $id = $request->post('id');
        $name = $request->post('name');
        
        $data = [
            'typeid' => $request->post('typeid'),
            'title' => $request->post('title'),
            'ftime' => $request->post('ftime', 10),
            'qishu' => $request->post('qishu', 1440),
            'ftitle' => $request->post('ftitle', ''),
            'issys' => $request->post('issys', 0),
            'issd' => $request->post('issd', 0),
            'is_hot' => $request->post('is_hot', 0),
            'closetime1' => $request->post('closetime1', '00:00:00'),
            'closetime2' => $request->post('closetime2', '00:00:00'),
            'expecttime' => $request->post('expecttime', 1),
            'isopen' => $request->post('isopen', 1),
            'iswh' => $request->post('iswh', 1),
        ];
        
        // 验证必填项
        if (empty($data['title'])) {
            return $this->json(1, '彩种名称不能为空');
        }
        
        if (empty($data['typeid'])) {
            return $this->json(1, '彩种分类不能为空');
        }
        
        if ($id) {
            // 更新
            $result = Db::table('caipiao_caipiao')->where('id', $id)->update($data);
        } else {
            // 新增
            if (empty($name)) {
                return $this->json(1, '彩种代码不能为空');
            }
            
            // 检查彩种代码是否已存在
            $exists = Db::table('caipiao_caipiao')->where('name', $name)->exists();
            if ($exists) {
                return $this->json(1, '彩种代码已存在');
            }
            
            // 设置新增必需的字段
            $data['name'] = $name;
            $data['firsttime'] = '00:00:00';
            $data['endtime'] = '23:59:59';
            $data['listorder'] = 999;
            $data['allsort'] = 0;
            $data['isgf'] = 0;
            $data['isct'] = 0;
            $data['hemai_status'] = 0;
            
            $result = Db::table('caipiao_caipiao')->insertGetId($data);
            
            if (!$result) {
                return $this->json(1, '新增失败');
            }
        }
        
        return $this->json(0, '保存成功');
    }
    
    /**
     * 设置状态
     */
    public function setStatus(Request $request)
    {
        $id = $request->post('id');
        $field = $request->post('field');
        $value = $request->post('value');
        
        if (empty($id) || !in_array($field, ['isopen', 'iswh'])) {
            return $this->json(1, '参数错误');
        }
        
        Db::table('caipiao_caipiao')
            ->where('id', $id)
            ->update([$field => $value]);
        
        return $this->json(0, '操作成功');
    }
    
    /**
     * 删除彩种
     */
    public function delete(Request $request)
    {
        $id = $request->post('id');
        
        if (empty($id)) {
            return $this->json(1, '参数错误');
        }
        
        // 获取彩种信息
        $lottery = Db::table('caipiao_caipiao')->where('id', $id)->first();
        if (!$lottery) {
            return $this->json(1, '彩种不存在');
        }
        
        // 检查是否有投注记录（使用cpname字段关联）
        $hasRecords = Db::table('caipiao_touzhu')
            ->where('cpname', $lottery->name)
            ->exists();
        
        if ($hasRecords) {
            return $this->json(1, '该彩种有投注记录，无法删除');
        }
        
        Db::table('caipiao_caipiao')->where('id', $id)->delete();
        
        return $this->json(0, '删除成功');
    }
    
    /**
     * 玩法管理
     */
    public function play(Request $request)
    {
        return view('lottery/play-new');
    }
    
    /**
     * 玩法列表数据
     */
    public function playList(Request $request)
    {
        $typeid = $request->get('typeid', '');
        $isopen = $request->get('isopen', '');
        $keyword = $request->get('keyword', '');
        // 兼容 current/size 和 page/limit 两种分页参数
        $page = (int)$request->get('current', $request->get('page', 1));
        $limit = (int)$request->get('size', $request->get('limit', 20));
        
        $query = Db::table('caipiao_wanfa');
        
        if (!empty($typeid)) {
            $query->where('typeid', $typeid);
        }
        if ($isopen !== '') {
            $query->where('isopen', $isopen);
        }
        if (!empty($keyword)) {
            $query->where(function ($q) use ($keyword) {
                $q->where('title', 'like', '%' . $keyword . '%')
                  ->orWhere('playid', 'like', '%' . $keyword . '%');
            });
        }
        
        $total = (clone $query)->count();
        $offset = ($page - 1) * $limit;
        $list = $query->orderBy('typeid')->orderBy('id')
            ->offset($offset)->limit($limit)->get()->toArray();
        
        // 转换为数组格式
        foreach ($list as &$item) {
            $item = (array)$item;
        }
        
        return $this->json(0, 'success', [
            'list' => $list,
            'total' => $total
        ]);
    }
    
    /**
     * 获取玩法详情
     */
    public function playDetail(Request $request)
    {
        $id = $request->get('id');
        if (!$id) {
            return $this->json(1, '缺少参数 id');
        }
        
        $play = Db::table('caipiao_wanfa')->where('id', $id)->first();
        if (!$play) {
            return $this->json(1, '玩法不存在');
        }
        
        return $this->json(0, 'success', (array)$play);
    }
    
    /**
     * 玩法状态切换
     */
    public function playStatus(Request $request)
    {
        $id = $request->post('id');
        $isopen = $request->post('isopen');
        
        Db::table('caipiao_wanfa')->where('id', $id)->update(['isopen' => $isopen]);
        return $this->json(0, '操作成功');
    }
    
    /**
     * 玩法保存（单字段）
     */
    public function playSave(Request $request)
    {
        $id = $request->post('id');
        $field = $request->post('field');
        $value = $request->post('value');
        
        $allowFields = ['rate', 'maxprize', 'minxf', 'maxxf', 'maxzs', 'fandian'];
        if (!in_array($field, $allowFields)) {
            return $this->json(1, '不允许修改该字段');
        }
        
        Db::table('caipiao_wanfa')->where('id', $id)->update([$field => $value]);
        return $this->json(0, '保存成功');
    }
    
    /**
     * 开奖期数管理
     */
    public function issue(Request $request)
    {
        return view('lottery/issue');
    }
    
    /**
     * 开奖结果管理
     */
    public function result(Request $request)
    {
        return view('lottery/result');
    }

    /**
     * 获取开奖列表数据
     */
    /**
     * 获取彩种列表（用于下拉选择）
     */
    public function getLotteryList(Request $request)
    {
        // 使用静态缓存
        static $cache = null;
        if ($cache !== null) {
            return $this->json(0, 'success', $cache);
        }
        
        $list = Db::table('caipiao_caipiao')
            ->select('id', 'name', 'title', 'typeid', 'issys')
            ->where('isopen', 1)
            ->orderBy('typeid')
            ->orderBy('listorder')
            ->get()
            ->toArray();
        
        // 转换为数组格式
        foreach ($list as &$item) {
            $item = (array)$item;
        }
        
        $cache = $list;
        return $this->json(0, 'success', $list);
    }

    public function resultList(Request $request)
    {
        $lotteryId = $request->get('lottery_id', '');
        $expect = $request->get('expect', '');
        $page = (int)$request->get('page', 1);
        $limit = (int)$request->get('limit', 20);
        
        // 彩种名称映射（静态缓存）
        static $lotteryMap = null;
        if ($lotteryMap === null) {
            $lotteries = Db::table('caipiao_caipiao')->select('id', 'name', 'title', 'issys')->get();
            $lotteryMap = [];
            foreach ($lotteries as $item) {
                $lotteryMap[$item->name] = ['id' => $item->id, 'title' => $item->title, 'issys' => $item->issys];
            }
        }
        
        // 构建查询
        $query = Db::table('caipiao_kaijiang');
        
        if (!empty($lotteryId)) {
            $lotteryName = Db::table('caipiao_caipiao')->where('id', $lotteryId)->value('name');
            if ($lotteryName) {
                $query->where('name', $lotteryName);
            }
        }
        
        if (!empty($expect)) {
            $query->where('expect', $expect);
        }
        
        // 分页查询（多查1条判断是否有下一页）
        $offset = ($page - 1) * $limit;
        $list = $query->orderByDesc('id')->offset($offset)->limit($limit + 1)->get();
        
        // 判断是否有下一页
        $hasMore = count($list) > $limit;
        if ($hasMore) {
            $list = $list->slice(0, $limit);
        }
        
        // 估算总数：当前页起始 + 实际条数 + (有下一页则+1000)
        $count = $offset + count($list) + ($hasMore ? 1000 : 0);
        
        // 处理数据
        $result = [];
        foreach ($list as $item) {
            $row = (array)$item;
            $lottery = $lotteryMap[$row['name']] ?? null;
            $row['lottery_id'] = $lottery['id'] ?? 0;
            $row['lottery_name'] = $lottery['title'] ?? $row['name'];
            $row['issys'] = $lottery['issys'] ?? 0;
            if (isset($row['opentime']) && is_numeric($row['opentime'])) {
                $row['opentime'] = date('Y-m-d H:i:s', $row['opentime']);
            }
            $row['source_text'] = $row['issys'] == 1 ? '系统彩' : '第三方';
            $result[] = $row;
        }
        
        return $this->json(0, 'success', [
            'list' => $result,
            'total' => $count
        ]);
    }

    /**
     * 添加开奖页面
     */
    public function resultAdd(Request $request)
    {
        return view('lottery/result-add');
    }

    /**
     * 编辑开奖页面
     */
    public function resultEdit(Request $request)
    {
        $id = $request->get('id');
        $result = Db::table('caipiao_kaijiang')->where('id', $id)->first();
        
        if (!$result) {
            return $this->error('开奖记录不存在');
        }
        
        return view('lottery/result-edit', ['result' => $result]);
    }
    
    /**
     * 添加开奖结果 API（Art Design Pro 前端专用）
     */
    public function apiResultAdd(Request $request)
    {
        $lotteryId = $request->post('lottery_id');
        $expect = $request->post('expect');
        $opencode = $request->post('opencode');
        $opentime = $request->post('opentime');
        
        if (!$lotteryId) {
            return $this->json(1, '请选择彩种');
        }
        if (!$expect) {
            return $this->json(1, '请输入期号');
        }
        if (!$opencode) {
            return $this->json(1, '请输入开奖号码');
        }
        
        // 根据 lottery_id 获取 name
        $lottery = Db::table('caipiao_caipiao')->where('id', $lotteryId)->first();
        if (!$lottery) {
            return $this->json(1, '彩种不存在');
        }
        
        // 检查期号是否已存在
        $exists = Db::table('caipiao_kaijiang')
            ->where('name', $lottery->name)
            ->where('expect', $expect)
            ->exists();
        if ($exists) {
            return $this->json(1, '该期号已存在');
        }
        
        // 开奖时间转时间戳
        if ($opentime && !is_numeric($opentime)) {
            $opentime = strtotime($opentime);
        } else {
            $opentime = time();
        }
        
        $data = [
            'name' => $lottery->name,
            'expect' => $expect,
            'opencode' => $opencode,
            'opentime' => $opentime,
            'addtime' => time(),
        ];
        
        $id = Db::table('caipiao_kaijiang')->insertGetId($data);
        
        if ($id) {
            return $this->json(0, '添加成功');
        } else {
            return $this->json(1, '添加失败');
        }
    }
    
    /**
     * 编辑开奖结果 API（Art Design Pro 前端专用）
     */
    public function apiResultEdit(Request $request)
    {
        $id = $request->post('id');
        $expect = $request->post('expect');
        $opencode = $request->post('opencode');
        $opentime = $request->post('opentime');
        
        if (!$id) {
            return $this->json(1, '缺少记录ID');
        }
        
        // 检查记录是否存在
        $record = Db::table('caipiao_kaijiang')->where('id', $id)->first();
        if (!$record) {
            return $this->json(1, '记录不存在');
        }
        
        // 开奖时间转时间戳
        if ($opentime && !is_numeric($opentime)) {
            $opentime = strtotime($opentime);
        }
        
        $data = [
            'expect' => $expect,
            'opencode' => $opencode,
            'opentime' => $opentime,
        ];
        
        $result = Db::table('caipiao_kaijiang')->where('id', $id)->update($data);
        
        if ($result !== false) {
            return $this->json(0, '保存成功');
        } else {
            return $this->json(1, '保存失败');
        }
    }

    /**
     * 保存开奖记录（旧版兼容）
     */
    public function resultSave(Request $request)
    {
        $id = $request->post('id');
        $name = $request->post('name');
        $expect = $request->post('expect');
        $opencode = $request->post('opencode');
        $opentime = $request->post('opentime');
        
        // 开奖时间转时间戳
        if ($opentime && !is_numeric($opentime)) {
            $opentime = strtotime($opentime);
        }
        
        $data = [
            'expect' => $expect,
            'opencode' => $opencode,
            'opentime' => $opentime,
        ];
        
        if ($id) {
            // 更新（不更新name）
            $result = Db::table('caipiao_kaijiang')
                ->where('id', $id)
                ->update($data);
            
            if ($result !== false) {
                return $this->json(0, '保存成功');
            } else {
                return $this->json(1, '保存失败');
            }
        } else {
            // 新增
            $data['name'] = $name;
            $data['addtime'] = time();
            $id = Db::table('caipiao_kaijiang')->insertGetId($data);
            
            if ($id) {
                return $this->json(0, '添加成功');
            } else {
                return $this->json(1, '添加失败');
            }
        }
    }

    /**
     * 重置开奖
     */
    public function resultReset(Request $request)
    {
        $id = $request->post('id');
        
        \support\Log::info('重置开奖 - ID: ' . $id);
        
        // TODO: 实现重置开奖逻辑
        // 1. 查询该期开奖记录
        // 2. 重新计算未开奖的投注
        // 3. 更新开奖状态
        
        return $this->json(0, '重置成功');
    }

    /**
     * 删除开奖
     */
    public function resultDelete(Request $request)
    {
        $id = $request->post('id');
        
        \support\Log::info('删除开奖 - ID: ' . $id);
        
        $result = Db::table('caipiao_kaijiang')->where('id', $id)->delete();
        
        if ($result) {
            return $this->json(0, '删除成功');
        } else {
            return $this->json(1, '删除失败');
        }
    }

    /**
     * 系统彩预开奖页面
     */
    public function preResult(Request $request)
    {
        return view('lottery/pre-result');
    }

    /**
     * 获取预开奖列表
     */
    public function preResultList(Request $request)
    {
        $lotteryName = $request->get('lottery_name', '');
        
        if (empty($lotteryName)) {
            return $this->json(1, '请选择彩种');
        }
        
        // 获取彩种信息（只获取系统彩）
        $lottery = Db::table('caipiao_caipiao')
            ->where('name', $lotteryName)
            ->where('issys', 1)
            ->first();
        
        if (!$lottery) {
            return $this->json(1, '彩种不存在或不是系统彩');
        }
        
        // 根据老版本逻辑生成未来20期
        $issues = $this->generateFutureIssues($lottery, 20);
        
        // 查询已保存的预开奖数据并替换
        try {
            $expects = array_column($issues, 'expect');
            $savedIssues = Db::table('caipiao_yukaijiang')
                ->where('name', $lotteryName)
                ->where('hid', 0)  // 只查询未隐藏的记录
                ->whereIn('expect', $expects)
                ->get();
            
            $savedMap = [];
            foreach ($savedIssues as $saved) {
                $savedMap[$saved->expect] = $saved;
            }
            
            // 用已保存的数据替换生成的数据
            foreach ($issues as &$issue) {
                if (isset($savedMap[$issue['expect']])) {
                    $issue['opencode'] = $savedMap[$issue['expect']]->opencode ?? $issue['opencode'];
                    $issue['admin'] = $savedMap[$issue['expect']]->stateadmin ?? '';  // 使用 stateadmin 字段
                }
            }
        } catch (\Exception $e) {
            \support\Log::info('查询预开奖表失败: ' . $e->getMessage());
        }
        
        return $this->json(0, 'success', [
            'lottery' => (array)$lottery,
            'issues' => $issues
        ]);
    }

    /**
     * 生成未来期号（按老版本逻辑）
     */
    private function generateFutureIssues($lottery, $count = 20)
    {
        $issues = [];
        
        // expecttime: 开奖间隔（分钟）
        $expecttime = $lottery->expecttime ?? 5;
        $_expecttime = $expecttime * 60; // 转换为秒
        
        // 一天的总时间（从closetime1到closetime2）
        $closetime1 = $lottery->closetime1 ?? '00:00:00';
        $closetime2 = $lottery->closetime2 ?? '23:59:59';
        
        $totalopentimes = abs(strtotime($closetime2) - strtotime($closetime1));
        $totalcount = floor($totalopentimes / $_expecttime); // 每天总期数
        $_length = $totalcount >= 1000 ? 4 : 3; // 期号长度
        
        $jgtime = $_expecttime; // 间隔时间（秒）
        $_t = time();
        $_t1 = strtotime(date('Y-m-d ') . $closetime1, $_t);
        
        // 计算当前期号
        if ($_t < $_t1) {
            $actNo_t = $totalcount;
        } else {
            $actNo_t = ($_t - strtotime(date('Y-m-d ') . $closetime1, $_t)) / $_expecttime;
        }
        $actNo_t = floor($actNo_t);
        $actNo = is_numeric($actNo_t) ? ($actNo_t == $totalcount ? 1 : $actNo_t + 1) : ceil($actNo_t);
        
        // 生成未来20期
        if ($actNo > $totalcount) {
            // 已经过了今天的收盘时间，生成明天的前10期
            if ($_t > strtotime($closetime2)) {
                $_datetime = strtotime(date("Y-m-d", strtotime("+1 day")));
                for ($j = 1; $j <= 10; $j++) {
                    $rand_keys = $this->returnRandomKeys($lottery->typeid);
                    $opentime = date('Y-m-d H:i:s', strtotime($closetime1) + $j * $jgtime + 86400);
                    $expect = str_pad($j, $_length, 0, STR_PAD_LEFT);
                    
                    $issues[] = [
                        'expect' => date('Ymd', $_datetime) . $expect,
                        'opencode' => implode(',', $rand_keys),
                        'opentime' => $opentime,
                        'admin' => ''
                    ];
                }
            } else {
                // 还未到收盘时间，生成今天的最后10期
                for ($j = $totalcount - 9; $j <= $totalcount; $j++) {
                    $rand_keys = $this->returnRandomKeys($lottery->typeid);
                    $opentime = date('Y-m-d H:i:s', strtotime($closetime1) + $j * $jgtime);
                    $expect = str_pad($j, $_length, 0, STR_PAD_LEFT);
                    
                    $issues[] = [
                        'expect' => date('Ymd') . $expect,
                        'opencode' => implode(',', $rand_keys),
                        'opentime' => $opentime,
                        'admin' => ''
                    ];
                }
            }
        } else {
            // 当前在开奖时间内，生成从当前期开始的20期
            if ($actNo + 19 <= $totalcount) {
                for ($j = $actNo; $j <= $actNo + 19; $j++) {
                    $rand_keys = $this->returnRandomKeys($lottery->typeid);
                    $opentime = date('Y-m-d H:i:s', strtotime($closetime2) - ($totalcount - $j) * $jgtime);
                    $expect = str_pad($j, $_length, 0, STR_PAD_LEFT);
                    
                    $issues[] = [
                        'expect' => date('Ymd') . $expect,
                        'opencode' => implode(',', $rand_keys),
                        'opentime' => $opentime,
                        'admin' => ''
                    ];
                }
            } else {
                // 剩余期数不足20期，生成到今天结束
                for ($j = $actNo; $j <= $totalcount; $j++) {
                    $rand_keys = $this->returnRandomKeys($lottery->typeid);
                    $jj = $totalcount - $j;
                    $opentime = date('Y-m-d H:i:s', strtotime($closetime2) - $jj * $jgtime);
                    $expect = str_pad($j, $_length, 0, STR_PAD_LEFT);
                    
                    $issues[] = [
                        'expect' => date('Ymd') . $expect,
                        'opencode' => implode(',', $rand_keys),
                        'opentime' => $opentime,
                        'admin' => ''
                    ];
                }
            }
        }
        
        return $issues;
    }
    
    /**
     * 根据彩种类型生成随机开奖号码
     */
    private function returnRandomKeys($typeid)
    {
        $rand_keys = [];
        
        switch ($typeid) {
            case 'k3':
                // 快3：3个球，1-6
                $numbers = range(1, 6);
                shuffle($numbers);
                $rand_keys = array_slice($numbers, 0, 3);
                sort($rand_keys);
                break;
            case 'xy28':
                // 幸运28：3个球，0-9
                $numbers = range(0, 9);
                shuffle($numbers);
                $rand_keys = array_slice($numbers, 0, 3);
                break;
            case 'ssc':
                // 时时彩：5个球，0-9
                for ($i = 0; $i < 5; $i++) {
                    $rand_keys[] = rand(0, 9);
                }
                break;
            case 'x5':
            case '11x5':
                // 11选5：5个球，01-11
                $numbers = range(1, 11);
                shuffle($numbers);
                $selected = array_slice($numbers, 0, 5);
                foreach ($selected as $num) {
                    $rand_keys[] = str_pad($num, 2, '0', STR_PAD_LEFT);
                }
                break;
            case 'pk10':
                // PK10：10个球，01-10
                $numbers = range(1, 10);
                shuffle($numbers);
                foreach ($numbers as $num) {
                    $rand_keys[] = str_pad($num, 2, '0', STR_PAD_LEFT);
                }
                break;
            case 'lhc':
                // 六合彩：7个球，01-49
                $numbers = range(1, 49);
                shuffle($numbers);
                $selected = array_slice($numbers, 0, 7);
                foreach ($selected as $num) {
                    $rand_keys[] = str_pad($num, 2, '0', STR_PAD_LEFT);
                }
                break;
            default:
                // 默认5个球，0-9
                for ($i = 0; $i < 5; $i++) {
                    $rand_keys[] = rand(0, 9);
                }
                break;
        }
        
        return $rand_keys;
    }

    /**
     * 保存预开奖
     */
    public function preResultSave(Request $request)
    {
        $expect = $request->post('expect');
        $name = $request->post('name');
        $opentime = $request->post('opentime');
        
        // 收集所有20个球的值并组合成开奖号码
        $opencodes = [];
        for ($i = 1; $i <= 20; $i++) {
            $code = $request->post('opencode' . $i, '');
            if ($code !== '') {
                $opencodes[] = $code;
            }
        }
        
        if (empty($opencodes)) {
            return $this->json(1, '请选择开奖号码');
        }
        
        $opencode = implode(',', $opencodes);
        
        // 开奖时间转时间戳
        if ($opentime && !is_numeric($opentime)) {
            $opentimeTimestamp = strtotime($opentime);
        } else {
            $opentimeTimestamp = $opentime;
        }
        
        // 只操作预开奖表（按老版本逻辑）
        try {
            // 检查预开奖表中是否已存在该记录
            $existingRecord = Db::table('caipiao_yukaijiang')
                ->where('name', $name)
                ->where('expect', $expect)
                ->first();
            
            if ($existingRecord) {
                // 存在则更新开奖号码和开奖时间
                $result = Db::table('caipiao_yukaijiang')
                    ->where('name', $name)
                    ->where('expect', $expect)
                    ->update([
                        'opencode' => $opencode,
                        'opentime' => $opentimeTimestamp
                    ]);
                
                if ($result !== false) {
                    return $this->json(0, '保存成功', ['admin' => 'admin']);
                } else {
                    return $this->json(1, '更新失败');
                }
            } else {
                // 不存在则插入新记录
                $data = [
                    'name' => $name,
                    'expect' => $expect,
                    'opencode' => $opencode,
                    'opentime' => $opentimeTimestamp,
                    'stateadmin' => 'admin',
                    'hid' => 0
                ];
                
                $id = Db::table('caipiao_yukaijiang')->insertGetId($data);
                
                if ($id) {
                    return $this->json(0, '保存成功', ['admin' => 'admin']);
                } else {
                    return $this->json(1, '插入失败');
                }
            }
        } catch (\Exception $e) {
            \support\Log::error('预开奖保存失败: ' . $e->getMessage());
            return $this->json(1, '保存失败: ' . $e->getMessage());
        }
    }

    /**
     * 预开奖历史页面
     */
    public function preResultHistory(Request $request)
    {
        return view('lottery/pre-result-history');
    }

    /**
     * 获取预开奖历史列表
     */
    public function preResultHistoryList(Request $request)
    {
        $lotteryName = $request->get('lottery_name', '');
        $expect = $request->get('expect', '');
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 20);
        
        if (empty($lotteryName)) {
            return $this->json(1, '请选择彩种');
        }
        
        // 获取彩种信息
        $lottery = Db::table('caipiao_caipiao')
            ->where('name', $lotteryName)
            ->where('issys', 1)
            ->first();
        
        if (!$lottery) {
            return $this->json(1, '彩种不存在或不是系统彩');
        }
        
        try {
            // 查询预开奖历史记录（只查询未隐藏的记录）
            $query = Db::table('caipiao_yukaijiang')
                ->where('name', $lotteryName)
                ->where('hid', 0);
            
            // 如果有期号搜索
            if (!empty($expect)) {
                $query->where('expect', 'like', '%' . $expect . '%');
            }
            
            // 获取总数
            $count = $query->count();
            
            // 分页查询
            $offset = ($page - 1) * $limit;
            $list = $query->orderBy('opentime', 'desc')
                ->orderBy('expect', 'desc')
                ->offset($offset)
                ->limit($limit)
                ->get();
            
            $result = [];
            foreach ($list as $item) {
                $row = (array)$item;
                $row['lottery_title'] = $lottery->title;
                $row['admin'] = $row['stateadmin'] ?? '';  // 使用 stateadmin 字段
                // 格式化开奖时间
                if (isset($row['opentime']) && is_numeric($row['opentime'])) {
                    $row['opentime'] = date('Y-m-d H:i:s', $row['opentime']);
                }
                $result[] = $row;
            }
            
            return $this->json(0, 'success', [
                'list' => $result,
                'total' => $count
            ]);
            
        } catch (\Exception $e) {
            \support\Log::info('查询预开奖历史失败: ' . $e->getMessage());
            return $this->json(1, '查询失败: ' . $e->getMessage());
        }
    }
}

