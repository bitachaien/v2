<?php

namespace plugin\admin\app\controller;

use support\Request;
use support\Db;

class NoticeController extends Base
{
    /**
     * 站内通知列表页面
     */
    public function index(Request $request)
    {
        return view('notice/index');
    }
    
    /**
     * 获取通知列表数据
     */
    public function list(Request $request)
    {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 20);
        
        $query = Db::table('caipiao_notice');
        
        // 获取总数
        $count = $query->count();
        
        // 分页
        $offset = ($page - 1) * $limit;
        $list = $query->orderBy('id', 'desc')
                     ->offset($offset)
                     ->limit($limit)
                     ->get();
        
        // 处理数据
        $result = [];
        foreach ($list as $item) {
            $row = (array)$item;
            
            // 格式化时间
            if (isset($row['add_time']) && is_numeric($row['add_time'])) {
                $row['add_time_text'] = date('Y-m-d H:i:s', $row['add_time']);
            } else {
                $row['add_time_text'] = '';
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
     * 添加通知
     */
    public function add(Request $request)
    {
        if ($request->method() === 'POST') {
            $data = [
                'title' => $request->post('title'),
                'content' => $request->post('content'),
                'users' => $request->post('users', ''),
                'add_time' => time()
            ];
            
            if (!$data['title'] || !$data['content']) {
                return json(['code' => 1, 'msg' => '标题和内容不能为空']);
            }
            
            $id = Db::table('caipiao_notice')->insertGetId($data);
            
            if ($id) {
                return json(['code' => 0, 'msg' => '添加成功']);
            } else {
                return json(['code' => 1, 'msg' => '添加失败']);
            }
        }
        
        return view('notice/add');
    }
    
    /**
     * 编辑通知
     */
    public function edit(Request $request)
    {
        $id = $request->get('id');
        
        if (!$id) {
            return '<script>alert("参数错误");history.back();</script>';
        }
        
        $info = Db::table('caipiao_notice')->where('id', $id)->first();
        
        if (!$info) {
            return '<script>alert("通知不存在");history.back();</script>';
        }
        
        if ($request->method() === 'POST') {
            $data = [
                'title' => $request->post('title'),
                'content' => $request->post('content'),
                'users' => $request->post('users', '')
            ];
            
            if (!$data['title'] || !$data['content']) {
                return json(['code' => 1, 'msg' => '标题和内容不能为空']);
            }
            
            $result = Db::table('caipiao_notice')
                ->where('id', $id)
                ->update($data);
            
            if ($result !== false) {
                return json(['code' => 0, 'msg' => '修改成功']);
            } else {
                return json(['code' => 1, 'msg' => '修改失败']);
            }
        }
        
        return view('notice/edit', ['info' => (array)$info]);
    }
    
    /**
     * 删除通知
     */
    public function delete(Request $request)
    {
        $id = $request->post('id');
        
        if (!$id) {
            return json(['code' => 1, 'msg' => '参数错误']);
        }
        
        $result = Db::table('caipiao_notice')->where('id', $id)->delete();
        
        if ($result) {
            return json(['code' => 0, 'msg' => '删除成功']);
        } else {
            return json(['code' => 1, 'msg' => '删除失败']);
        }
    }
}

