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
     * 获取登录日志列表数据
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

