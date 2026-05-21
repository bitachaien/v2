<?php

namespace plugin\admin\app\controller;

use support\Request;
use support\Db;

class LoginLogController extends Base
{
    /**
     * 登录日志列表页面
     */
    public function index(Request $request)
    {
        return view('login-log/index');
    }
    
    /**
     * LấyĐăng nhập日志列表dữ liệu
     */
    public function list(Request $request)
    {
        $username = $request->get('username', '');
        $loginip = $request->get('loginip', '');
        $sDate = $request->get('sDate', '');
        $eDate = $request->get('eDate', '');
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 20);
        
        $query = Db::table('caipiao_memberlog');
        
        if ($username) {
            $query->where('username', $username);
        }
        
        if ($loginip) {
            $query->where('ip', $loginip);
        }
        
        if ($sDate) {
            $query->where('time', '>=', strtotime($sDate));
        }
        
        if ($eDate) {
            $query->where('time', '<=', strtotime($eDate) + 86400);
        }
        
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
            if (isset($row['time']) && is_numeric($row['time'])) {
                $row['time_text'] = date('Y-m-d H:i:s', $row['time']);
            } else {
                $row['time_text'] = '';
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
}

