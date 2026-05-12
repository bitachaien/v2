<?php

namespace app\controller\admin;

use support\Request;
use support\Db;

class ActivityCategoryController
{
    
    public function list(Request $request)
    {
        try {
            $page = $request->get('page', 1);
            $limit = $request->get('limit', 20);
            $name = $request->get('name', '');
            $status = $request->get('status', '');

            $query = Db::table('caipiao_activity_category');

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
        } catch (\Exception $e) {
            return json([
                'code' => 1,
                'msg' => '获取失败：' . $e->getMessage()
            ]);
        }
    }

    
    public function options(Request $request)
    {
        try {
            $list = Db::table('caipiao_activity_category')
                ->where('status', 1)
                ->orderBy('sort', 'desc')
                ->select('code as value', 'name as label')
                ->get();

            return json([
                'code' => 0,
                'msg' => 'ok',
                'data' => $list
            ]);
        } catch (\Exception $e) {
            return json([
                'code' => 1,
                'msg' => '获取失败：' . $e->getMessage()
            ]);
        }
    }

    
    public function add(Request $request)
    {
        $name = $request->post('name');
        $code = $request->post('code');
        $icon = $request->post('icon', '');
        $sort = $request->post('sort', 0);
        $status = $request->post('status', 1);
        $remark = $request->post('remark', '');

        if (!$name) {
            return json(['code' => 1, 'msg' => '分类名称不能为空']);
        }

        if (!$code) {
            return json(['code' => 1, 'msg' => '分类代码不能为空']);
        }

        $exists = Db::table('caipiao_activity_category')->where('code', $code)->exists();
        if ($exists) {
            return json(['code' => 1, 'msg' => '分类代码已存在']);
        }

        $now = time();
        $id = Db::table('caipiao_activity_category')->insertGetId([
            'name' => $name,
            'code' => $code,
            'icon' => $icon,
            'sort' => $sort,
            'status' => $status,
            'remark' => $remark,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return json(['code' => 0, 'msg' => '添加成功', 'data' => ['id' => $id]]);
    }

    
    public function edit(Request $request)
    {
        $id = $request->post('id');
        if (!$id) {
            return json(['code' => 1, 'msg' => '参数错误']);
        }

        $item = Db::table('caipiao_activity_category')->where('id', $id)->first();
        if (!$item) {
            return json(['code' => 1, 'msg' => '分类不存在']);
        }

        $data = ['updated_at' => time()];

        $name = $request->post('name');
        if ($name !== null) $data['name'] = $name;

        $code = $request->post('code');
        if ($code !== null) {
            $exists = Db::table('caipiao_activity_category')
                ->where('code', $code)
                ->where('id', '!=', $id)
                ->exists();
            if ($exists) {
                return json(['code' => 1, 'msg' => '分类代码已存在']);
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

        Db::table('caipiao_activity_category')->where('id', $id)->update($data);

        return json(['code' => 0, 'msg' => '修改成功']);
    }

    
    public function delete(Request $request)
    {
        $id = $request->post('id');
        $ids = $request->post('ids', []);

        if (!$id && empty($ids)) {
            return json(['code' => 1, 'msg' => '参数错误']);
        }

        $deleteIds = $id ? [$id] : $ids;

        $categories = Db::table('caipiao_activity_category')
            ->whereIn('id', $deleteIds)
            ->pluck('code')
            ->toArray();

        $usedCount = Db::table('caipiao_huodong')
            ->whereIn('category', $categories)
            ->count();

        if ($usedCount > 0) {
            return json(['code' => 1, 'msg' => '该分类下有活动，无法删除']);
        }

        Db::table('caipiao_activity_category')->whereIn('id', $deleteIds)->delete();

        return json(['code' => 0, 'msg' => '删除成功']);
    }

    
    public function status(Request $request)
    {
        $id = $request->post('id');
        $status = $request->post('status');

        if (!$id || $status === null) {
            return json(['code' => 1, 'msg' => '参数错误']);
        }

        Db::table('caipiao_activity_category')->where('id', $id)->update([
            'status' => $status,
            'updated_at' => time()
        ]);

        return json(['code' => 0, 'msg' => '状态更新成功']);
    }
}

