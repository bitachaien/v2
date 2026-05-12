<?php

namespace plugin\admin\app\controller;

use support\Request;
use support\Db;
use plugin\admin\app\model\YzzMenu;

/**
 * YZZ 菜单管理 API 控制器
 */
class YzzMenuController extends Base
{
    /**
     * 不需要鉴权的方法
     */
    protected $noNeedAuth = ['tree', 'list'];

    /**
     * 获取菜单树（前端路由用）
     * GET /app/admin/api/yzz-menu/tree
     */
    public function tree(Request $request)
    {
        $status = $request->get('status', 1); // 默认只返回启用的菜单

        $query = Db::table('yzz_menus')->orderBy('sort', 'asc')->orderBy('id', 'asc');

        if ($status !== '' && $status !== null) {
            $query->where('status', $status);
        }

        $menus = $query->get()->toArray();

        // 构建树形结构
        $tree = $this->buildTree($menus);

        return $this->json(0, 'ok', $tree);
    }

    /**
     * 获取完整菜单树（包含隐藏菜单，菜单管理用）
     * GET /app/admin/api/yzz-menu/full-tree
     */
    public function fullTree(Request $request)
    {
        $menus = Db::table('yzz_menus')
            ->orderBy('sort', 'asc')
            ->orderBy('id', 'asc')
            ->get()
            ->toArray();

        $tree = $this->buildTree($menus, true);

        return $this->json(0, 'ok', $tree);
    }

    /**
     * 获取菜单列表（扁平结构）
     * GET /app/admin/api/yzz-menu/list
     */
    public function list(Request $request)
    {
        $status = $request->get('status', '');
        $pid = $request->get('pid', '');

        $query = Db::table('yzz_menus')->orderBy('sort', 'asc')->orderBy('id', 'asc');

        if ($status !== '') {
            $query->where('status', $status);
        }

        if ($pid !== '') {
            $query->where('pid', $pid);
        }

        $list = $query->get();

        $result = [];
        foreach ($list as $item) {
            $row = (array)$item;
            $row['type_text'] = $this->getTypeText($row['type']);
            $row['status_text'] = $row['status'] == 1 ? '启用' : '禁用';
            $result[] = $row;
        }

        return $this->json(0, 'ok', $result, count($result));
    }

    /**
     * 菜单详情
     * GET /app/admin/api/yzz-menu/detail
     */
    public function detail(Request $request)
    {
        $id = $request->get('id');
        if (!$id) {
            return $this->json(1, '参数错误');
        }

        $menu = Db::table('yzz_menus')->where('id', $id)->first();
        if (!$menu) {
            return $this->json(1, '菜单不存在');
        }

        return $this->json(0, 'ok', (array)$menu);
    }

    /**
     * 添加菜单
     * POST /app/admin/api/yzz-menu/add
     */
    public function add(Request $request)
    {
        $data = $this->validateMenuData($request);
        if (is_string($data)) {
            return $this->json(1, $data);
        }

        // 检查 name 是否重复
        $exists = Db::table('yzz_menus')->where('name', $data['name'])->first();
        if ($exists) {
            return $this->json(1, '菜单名称已存在');
        }

        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');

        $id = Db::table('yzz_menus')->insertGetId($data);

        return $this->json(0, '添加成功', ['id' => $id]);
    }

    /**
     * 编辑菜单
     * POST /app/admin/api/yzz-menu/edit
     */
    public function edit(Request $request)
    {
        $id = $request->post('id');
        if (!$id) {
            return $this->json(1, '参数错误');
        }

        $menu = Db::table('yzz_menus')->where('id', $id)->first();
        if (!$menu) {
            return $this->json(1, '菜单不存在');
        }

        $data = $this->validateMenuData($request, $id);
        if (is_string($data)) {
            return $this->json(1, $data);
        }

        // 检查 name 是否重复
        $exists = Db::table('yzz_menus')
            ->where('name', $data['name'])
            ->where('id', '!=', $id)
            ->first();
        if ($exists) {
            return $this->json(1, '菜单名称已存在');
        }

        $data['updated_at'] = date('Y-m-d H:i:s');

        Db::table('yzz_menus')->where('id', $id)->update($data);

        return $this->json(0, '修改成功');
    }

    /**
     * 删除菜单
     * POST /app/admin/api/yzz-menu/delete
     */
    public function delete(Request $request)
    {
        $id = $request->post('id');
        if (!$id) {
            return $this->json(1, '参数错误');
        }

        // 检查是否有子菜单
        $childCount = Db::table('yzz_menus')->where('pid', $id)->count();
        if ($childCount > 0) {
            return $this->json(1, '该菜单下有子菜单，无法删除');
        }

        Db::table('yzz_menus')->where('id', $id)->delete();

        return $this->json(0, '删除成功');
    }

    /**
     * 更新菜单状态
     * POST /app/admin/api/yzz-menu/status
     */
    public function status(Request $request)
    {
        $id = $request->post('id');
        $status = $request->post('status');

        if (!$id || $status === null) {
            return $this->json(1, '参数错误');
        }

        Db::table('yzz_menus')->where('id', $id)->update([
            'status' => $status,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return $this->json(0, '状态更新成功');
    }

    /**
     * 更新菜单排序
     * POST /app/admin/api/yzz-menu/sort
     */
    public function sort(Request $request)
    {
        $data = $request->post('data', []);

        if (empty($data)) {
            return $this->json(1, '参数错误');
        }

        foreach ($data as $item) {
            if (isset($item['id']) && isset($item['sort'])) {
                Db::table('yzz_menus')->where('id', $item['id'])->update([
                    'sort' => $item['sort'],
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
            }
        }

        return $this->json(0, '排序更新成功');
    }

    /**
     * 获取父级菜单选项（下拉用）
     * GET /app/admin/api/yzz-menu/parent-options
     */
    public function parentOptions(Request $request)
    {
        $excludeId = $request->get('exclude_id', 0); // 排除自身及子菜单

        $query = Db::table('yzz_menus')
            ->where('type', 0) // 只返回目录类型
            ->orderBy('sort', 'asc');

        $list = $query->get();

        $result = [
            ['value' => 0, 'label' => '顶级菜单']
        ];

        foreach ($list as $item) {
            if ($excludeId && $item->id == $excludeId) {
                continue;
            }
            $result[] = [
                'value' => $item->id,
                'label' => $item->title,
            ];
        }

        return $this->json(0, 'ok', $result);
    }

    /**
     * 批量导入菜单（JSON格式）
     * POST /app/admin/api/yzz-menu/import
     */
    public function import(Request $request)
    {
        $menus = $request->post('menus', []);
        $clear = $request->post('clear', false); // 是否清空现有菜单

        if (empty($menus)) {
            return $this->json(1, '菜单数据不能为空');
        }

        try {
            Db::beginTransaction();

            if ($clear) {
                Db::table('yzz_menus')->truncate();
            }

            $count = $this->importMenus($menus);

            Db::commit();

            return $this->json(0, "导入成功，共 {$count} 条菜单");
        } catch (\Exception $e) {
            Db::rollBack();
            return $this->json(1, '导入失败: ' . $e->getMessage());
        }
    }

    /**
     * 构建菜单树
     */
    private function buildTree(array $menus, bool $includeHidden = false, int $parentId = 0): array
    {
        $tree = [];
        foreach ($menus as $menu) {
            $menu = (array)$menu;
            if ($menu['pid'] == $parentId) {
                // 跳过隐藏菜单（除非指定包含）
                if (!$includeHidden && $menu['status'] == 0) {
                    continue;
                }

                $children = $this->buildTree($menus, $includeHidden, $menu['id']);

                $item = [
                    'id' => (int)$menu['id'],
                    'pid' => (int)$menu['pid'],
                    'name' => $menu['name'],
                    'title' => $menu['title'],
                    'icon' => $menu['icon'] ?? '',
                    'path' => $menu['path'] ?? '',
                    'component' => $menu['component'] ?? '',
                    'type' => (int)$menu['type'],
                    'sort' => (int)$menu['sort'],
                    'status' => (int)$menu['status'],
                ];

                // 隐藏菜单标记
                if ($menu['status'] == 0) {
                    $item['isHide'] = true;
                }

                if (!empty($children)) {
                    $item['children'] = $children;
                }

                $tree[] = $item;
            }
        }
        return $tree;
    }

    /**
     * 递归导入菜单
     */
    private function importMenus(array $menus, int $parentId = 0): int
    {
        $count = 0;
        $now = date('Y-m-d H:i:s');

        foreach ($menus as $menu) {
            $data = [
                'pid' => $parentId,
                'name' => $menu['name'],
                'title' => $menu['title'],
                'icon' => $menu['icon'] ?? null,
                'path' => $menu['path'] ?? null,
                'component' => $menu['component'] ?? null,
                'type' => $menu['type'] ?? 1,
                'sort' => $menu['sort'] ?? 0,
                'status' => $menu['status'] ?? 1,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            // 如果有指定 ID 则使用
            if (!empty($menu['id'])) {
                $data['id'] = $menu['id'];
            }

            $id = Db::table('yzz_menus')->insertGetId($data);
            $count++;

            // 递归导入子菜单
            if (!empty($menu['children'])) {
                $count += $this->importMenus($menu['children'], $id);
            }
        }

        return $count;
    }

    /**
     * 验证菜单数据
     */
    private function validateMenuData(Request $request, int $id = 0): array|string
    {
        $name = $request->post('name');
        $title = $request->post('title');

        if (!$name) {
            return '菜单名称不能为空';
        }

        if (!$title) {
            return '菜单标题不能为空';
        }

        return [
            'pid' => $request->post('pid', 0),
            'name' => $name,
            'title' => $title,
            'icon' => $request->post('icon', ''),
            'path' => $request->post('path', ''),
            'component' => $request->post('component', ''),
            'type' => $request->post('type', 1),
            'sort' => $request->post('sort', 0),
            'status' => $request->post('status', 1),
        ];
    }

    /**
     * 获取类型文本
     */
    private function getTypeText(int $type): string
    {
        return match ($type) {
            0 => '目录',
            1 => '菜单',
            2 => '按钮',
            default => '未知',
        };
    }
}
