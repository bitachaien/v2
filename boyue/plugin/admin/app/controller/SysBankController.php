<?php

namespace plugin\admin\app\controller;

use support\Request;
use support\Db;

/**
 * 提款银行管理
 * 移植自 TP 老版本 SysbankController
 */
class SysBankController extends Base
{
    /**
     * 不需要鉴权的方法
     * @var string[]
     */
    protected $noNeedAuth = ['list', 'index'];
    /**
     * 数据表名
     */
    protected $table = 'caipiao_sysbank';

    /**
     * 银行列表页面
     */
    public function index(Request $request)
    {
        return view('sysbank/index');
    }

    /**
     * 获取银行列表数据 (对应老版本 manage)
     */
    public function list(Request $request)
    {
        try {
            $page = (int)$request->get('page', 1);
            $limit = (int)$request->get('limit', 20);

            // 获取总数
            $count = Db::table($this->table)->count();

            // 分页查询，按 listorder asc, id desc 排序（与老版本一致）
            $list = Db::table($this->table)
                ->orderBy('listorder', 'asc')
                ->orderBy('id', 'desc')
                ->offset(($page - 1) * $limit)
                ->limit($limit)
                ->get()
                ->map(fn($item) => (array)$item)
                ->toArray();

            return json([
                'code' => 0,
                'msg' => 'success',
                'count' => $count,
                'data' => $list
            ]);
        } catch (\Throwable $e) {
            \support\Log::error('SysBankController::list 错误: ' . $e->getMessage());
            return json(['code' => 1, 'msg' => '获取列表失败: ' . $e->getMessage()]);
        }
    }

    /**
     * 添加银行页面
     */
    public function add(Request $request)
    {
        return view('sysbank/add');
    }

    /**
     * 保存新银行 (对应老版本 bankadd POST)
     */
    public function save(Request $request)
    {
        try {
            $data = $request->post();

            // 验证必填字段
            if (empty($data['bankcode']) || empty($data['bankname'])) {
                return json(['code' => 1, 'msg' => '银行代码和银行名称不能为空']);
            }

            // 检查银行代码是否已存在
            if (Db::table($this->table)->where('bankcode', $data['bankcode'])->exists()) {
                return json(['code' => 1, 'msg' => '银行代码已存在']);
            }

            // 获取最大ID+1作为listorder（与老版本一致）
            $maxId = Db::table($this->table)->max('id') ?? 0;

            $insertData = [
                'bankcode'  => $data['bankcode'],
                'bankname'  => $data['bankname'],
                'banklogo'  => $data['banklogo'] ?? '',
                'imgbg'     => $data['imgbg'] ?? '',
                'state'     => isset($data['state']) ? (int)$data['state'] : 1,
                'listorder' => $data['listorder'] ?? ($maxId + 1)
            ];

            $result = Db::table($this->table)->insert($insertData);

            return $result 
                ? json(['code' => 0, 'msg' => '添加成功'])
                : json(['code' => 1, 'msg' => '添加失败']);
        } catch (\Throwable $e) {
            \support\Log::error('SysBankController::save 错误: ' . $e->getMessage());
            return json(['code' => 1, 'msg' => '操作失败: ' . $e->getMessage()]);
        }
    }

    /**
     * 编辑银行页面
     */
    public function edit(Request $request)
    {
        $id = $request->get('id');
        
        if (!$id) {
            return json(['code' => 1, 'msg' => '参数错误']);
        }

        $info = Db::table($this->table)->where('id', $id)->first();
        
        if (!$info) {
            return json(['code' => 1, 'msg' => '银行不存在']);
        }

        return view('sysbank/edit', ['info' => $info]);
    }

    /**
     * 更新银行信息 (对应老版本 bankedit POST)
     */
    public function update(Request $request)
    {
        try {
            $id = $request->post('id');
            $data = $request->post();

            if (!$id) {
                return json(['code' => 1, 'msg' => '参数错误']);
            }

            // 验证必填字段
            if (empty($data['bankcode']) || empty($data['bankname'])) {
                return json(['code' => 1, 'msg' => '银行代码和银行名称不能为空']);
            }

            // 检查银行代码是否与其他记录重复
            if (Db::table($this->table)->where('bankcode', $data['bankcode'])->where('id', '!=', $id)->exists()) {
                return json(['code' => 1, 'msg' => '银行代码已存在']);
            }

            $updateData = [
                'bankcode'  => $data['bankcode'],
                'bankname'  => $data['bankname'],
                'banklogo'  => $data['banklogo'] ?? '',
                'imgbg'     => $data['imgbg'] ?? '',
                'state'     => isset($data['state']) ? (int)$data['state'] : 1,
                'listorder' => $data['listorder'] ?? 0
            ];

            $result = Db::table($this->table)->where('id', $id)->update($updateData);

            return $result !== false
                ? json(['code' => 0, 'msg' => '更新成功'])
                : json(['code' => 1, 'msg' => '更新失败']);
        } catch (\Throwable $e) {
            \support\Log::error('SysBankController::update 错误: ' . $e->getMessage());
            return json(['code' => 1, 'msg' => '操作失败: ' . $e->getMessage()]);
        }
    }

    /**
     * 删除银行 (对应老版本 delete)
     */
    public function delete(Request $request)
    {
        try {
            $id = $request->post('id');
            
            if (!$id) {
                return json(['code' => 1, 'msg' => '参数错误']);
            }

            // 检查数据是否存在
            if (!Db::table($this->table)->where('id', $id)->exists()) {
                return json(['code' => 1, 'msg' => '数据不存在或已删除']);
            }

            $result = Db::table($this->table)->where('id', $id)->delete();

            return $result
                ? json(['code' => 0, 'msg' => '删除成功'])
                : json(['code' => 1, 'msg' => '删除失败']);
        } catch (\Throwable $e) {
            \support\Log::error('SysBankController::delete 错误: ' . $e->getMessage());
            return json(['code' => 1, 'msg' => '操作失败: ' . $e->getMessage()]);
        }
    }

    /**
     * 批量删除 (对应老版本 deleteall)
     */
    public function deleteAll(Request $request)
    {
        try {
            $ids = $request->post('ids', []);
            
            if (empty($ids) || !is_array($ids)) {
                return json(['code' => 1, 'msg' => '参数错误']);
            }

            $result = Db::table($this->table)->whereIn('id', $ids)->delete();

            return $result
                ? json(['code' => 0, 'msg' => '批量删除成功'])
                : json(['code' => 1, 'msg' => '批量删除失败']);
        } catch (\Throwable $e) {
            \support\Log::error('SysBankController::deleteAll 错误: ' . $e->getMessage());
            return json(['code' => 1, 'msg' => '操作失败: ' . $e->getMessage()]);
        }
    }

    /**
     * 切换状态 (对应老版本 setstatus)
     */
    public function setState(Request $request)
    {
        try {
            $id = $request->post('id');
            $state = $request->post('state');
            $name = $request->post('name', 'state');
            
            if (!$id) {
                return json(['code' => 1, 'msg' => '参数错误']);
            }

            // 只允许修改 state 字段（与老版本一致）
            if ($name !== 'state') {
                return json(['code' => 1, 'msg' => '非法操作']);
            }

            // 如果没有传 state，则自动切换
            if (!isset($state)) {
                $currentState = Db::table($this->table)->where('id', $id)->value('state');
                $state = $currentState == 1 ? 0 : 1;
            }

            $result = Db::table($this->table)
                ->where('id', $id)
                ->update(['state' => $state ? 1 : 0]);

            return $result !== false
                ? json(['code' => 0, 'msg' => '操作成功'])
                : json(['code' => 1, 'msg' => '操作失败']);
        } catch (\Throwable $e) {
            \support\Log::error('SysBankController::setState 错误: ' . $e->getMessage());
            return json(['code' => 1, 'msg' => '操作失败: ' . $e->getMessage()]);
        }
    }

    /**
     * 更新排序 (对应老版本 listorder)
     */
    public function listOrder(Request $request)
    {
        try {
            $ids = $request->post('ids', []);
            $listorder = $request->post('listorder', []);

            if (empty($ids) || !is_array($ids)) {
                return json(['code' => 1, 'msg' => '参数错误']);
            }

            $successCount = 0;
            foreach ($ids as $id) {
                if (isset($listorder[$id])) {
                    $result = Db::table($this->table)
                        ->where('id', $id)
                        ->update(['listorder' => (int)$listorder[$id]]);
                    if ($result !== false) {
                        $successCount++;
                    }
                }
            }

            return $successCount > 0
                ? json(['code' => 0, 'msg' => '排序更新成功'])
                : json(['code' => 1, 'msg' => '排序更新失败']);
        } catch (\Throwable $e) {
            \support\Log::error('SysBankController::listOrder 错误: ' . $e->getMessage());
            return json(['code' => 1, 'msg' => '操作失败: ' . $e->getMessage()]);
        }
    }
}

