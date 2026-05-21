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
     * LấyThông báo列表dữ liệu
     */
    public function list(Request $request)
    {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 20);
        
        $query = Db::table('caipiao_notice');
        
        // Lấy总数
        $count = $query->count();
        
        // 分页
        $offset = ($page - 1) * $limit;
        $list = $query->orderBy('id', 'desc')
                     ->offset($offset)
                     ->limit($limit)
                     ->get();
        
        // 处理dữ liệu
        $result = [];
        foreach ($list as $item) {
            $row = (array)$item;
            
            // 格式化Thời gian
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
     * ThêmThông báo
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
                return json(['code' => 1, 'msg' => '标题和内容không được để trống']);
            }
            
            $id = Db::table('caipiao_notice')->insertGetId($data);
            
            if ($id) {
                return json(['code' => 0, 'msg' => 'ThêmThành công']);
            } else {
                return json(['code' => 1, 'msg' => 'ThêmThất bại']);
            }
        }
        
        return view('notice/add');
    }
    
    /**
     * 编辑Thông báo
     */
    public function edit(Request $request)
    {
        $id = $request->get('id');
        
        if (!$id) {
            return '<script>alert("Tham số không hợp lệ");history.back();</script>';
        }
        
        $info = Db::table('caipiao_notice')->where('id', $id)->first();
        
        if (!$info) {
            return '<script>alert("Thông báokhông tồn tại");history.back();</script>';
        }
        
        if ($request->method() === 'POST') {
            $data = [
                'title' => $request->post('title'),
                'content' => $request->post('content'),
                'users' => $request->post('users', '')
            ];
            
            if (!$data['title'] || !$data['content']) {
                return json(['code' => 1, 'msg' => '标题和内容không được để trống']);
            }
            
            $result = Db::table('caipiao_notice')
                ->where('id', $id)
                ->update($data);
            
            if ($result !== false) {
                return json(['code' => 0, 'msg' => 'SửaThành công']);
            } else {
                return json(['code' => 1, 'msg' => 'SửaThất bại']);
            }
        }
        
        return view('notice/edit', ['info' => (array)$info]);
    }
    
    /**
     * XóaThông báo
     */
    public function delete(Request $request)
    {
        $id = $request->post('id');
        
        if (!$id) {
            return json(['code' => 1, 'msg' => 'Tham số không hợp lệ']);
        }
        
        $result = Db::table('caipiao_notice')->where('id', $id)->delete();
        
        if ($result) {
            return json(['code' => 0, 'msg' => 'XóaThành công']);
        } else {
            return json(['code' => 1, 'msg' => 'XóaThất bại']);
        }
    }
}

