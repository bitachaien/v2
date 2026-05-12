<?php

namespace app\controller\admin;

use support\Request;
use support\Db;

class GameCategoryController
{
    
    public function index(Request $request)
    {
        try {
            $query = Db::table('caipiao_game_category');

            
            if ($request->input('status') !== null && $request->input('status') !== '') {
                $query->where('status', $request->input('status'));
            }

            
            if ($keyword = $request->input('keyword')) {
                $query->where(function($q) use ($keyword) {
                    $q->where('name', 'like', "%{$keyword}%")
                      ->orWhere('code', 'like', "%{$keyword}%");
                });
            }

            $list = $query->orderBy('sort', 'asc')
                          ->orderBy('id', 'asc')
                          ->get()
                          ->toArray();

            return json([
                'code' => 0,
                'msg' => 'success',
                'data' => [
                    'list' => $list,
                    'total' => count($list)
                ]
            ]);
        } catch (\Exception $e) {
            return json([
                'code' => 1,
                'msg' => '获取分类列表失败: ' . $e->getMessage()
            ]);
        }
    }

    
    public function detail(Request $request)
    {
        try {
            $id = $request->input('id');
            if (!$id) {
                return json(['code' => 1, 'msg' => '缺少ID参数']);
            }

            $category = Db::table('caipiao_game_category')->where('id', $id)->first();
            if (!$category) {
                return json(['code' => 1, 'msg' => '分类不存在']);
            }

            return json([
                'code' => 0,
                'msg' => 'success',
                'data' => $category
            ]);
        } catch (\Exception $e) {
            return json([
                'code' => 1,
                'msg' => '获取详情失败: ' . $e->getMessage()
            ]);
        }
    }

    
    public function save(Request $request)
    {
        try {
            $id = $request->input('id', 0);
            $data = [
                'code' => $request->input('code'),
                'name' => $request->input('name'),
                'icon' => $request->input('icon', ''),
                'path' => $request->input('path', ''),
                'sort' => $request->input('sort', 0),
                'status' => $request->input('status', 1),
                'updated_at' => time()
            ];

            if (!$data['code'] || !$data['name']) {
                return json(['code' => 1, 'msg' => '分类代码和名称不能为空']);
            }

            
            $exists = Db::table('caipiao_game_category')
                ->where('code', $data['code'])
                ->where('id', '!=', $id)
                ->exists();
            
            if ($exists) {
                return json(['code' => 1, 'msg' => '分类代码已存在']);
            }

            if ($id > 0) {
                
                Db::table('caipiao_game_category')->where('id', $id)->update($data);
                $msg = '更新成功';
            } else {
                
                $data['created_at'] = time();
                Db::table('caipiao_game_category')->insert($data);
                $msg = '添加成功';
            }

            return json([
                'code' => 0,
                'msg' => $msg
            ]);
        } catch (\Exception $e) {
            return json([
                'code' => 1,
                'msg' => '保存失败: ' . $e->getMessage()
            ]);
        }
    }

    
    public function setStatus(Request $request)
    {
        try {
            $id = $request->input('id');
            $status = $request->input('status');

            if (!$id) {
                return json(['code' => 1, 'msg' => '缺少ID参数']);
            }

            Db::table('caipiao_game_category')
                ->where('id', $id)
                ->update([
                    'status' => $status,
                    'updated_at' => time()
                ]);

            return json([
                'code' => 0,
                'msg' => '操作成功'
            ]);
        } catch (\Exception $e) {
            return json([
                'code' => 1,
                'msg' => '操作失败: ' . $e->getMessage()
            ]);
        }
    }

    
    public function updateSort(Request $request)
    {
        try {
            $id = $request->input('id');
            $sort = $request->input('sort', 0);

            if (!$id) {
                return json(['code' => 1, 'msg' => '缺少ID参数']);
            }

            Db::table('caipiao_game_category')
                ->where('id', $id)
                ->update([
                    'sort' => $sort,
                    'updated_at' => time()
                ]);

            return json([
                'code' => 0,
                'msg' => '操作成功'
            ]);
        } catch (\Exception $e) {
            return json([
                'code' => 1,
                'msg' => '操作失败: ' . $e->getMessage()
            ]);
        }
    }

    
    public function delete(Request $request)
    {
        try {
            $id = $request->input('id');
            if (!$id) {
                return json(['code' => 1, 'msg' => '缺少ID参数']);
            }

            Db::table('caipiao_game_category')->where('id', $id)->delete();

            return json([
                'code' => 0,
                'msg' => '删除成功'
            ]);
        } catch (\Exception $e) {
            return json([
                'code' => 1,
                'msg' => '删除失败: ' . $e->getMessage()
            ]);
        }
    }
}
