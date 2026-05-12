<?php

namespace plugin\admin\app\controller;

use support\Request;
use support\Db;
use support\Log;

class RobotController
{
    /**
     * 机器人设置 - 管理机器人会员
     */
    public function setting(Request $request)
    {
        // 判断是否是 AJAX 请求（layui table 会带 page 参数）
        $page = $request->get('page');
        
        if (empty($page)) {
            return view('robot/setting');
        }
        
        // 处理数据请求
        $limit = $request->get('limit', 20);
        $username = $request->get('username', '');
        
        $query = Db::table('caipiao_member')->where('isnb', 1);
        
        if (!empty($username)) {
            $query->where('username', 'like', "%$username%");
        }
        
        $total = $query->count();
        
        $list = $query->orderBy('id', 'desc')
            ->offset(($page - 1) * $limit)
            ->limit($limit)
            ->get();
        
        $data = [];
        foreach ($list as $item) {
            $item = (array)$item;
            $data[] = [
                'id' => $item['id'],
                'username' => $item['username'] ?? '',
                'balance' => $item['balance'] ?? 0,
                'groupid' => $item['groupid'] ?? 0,
                'regtime' => isset($item['regtime']) ? date('Y-m-d H:i:s', $item['regtime']) : '-',
                'isonline' => ($item['isonline'] ?? 0) == 1 ? '在线' : '离线',
                'isonline_raw' => $item['isonline'] ?? 0,
            ];
        }
        
        return json([
            'code' => 0,
            'msg' => '获取成功',
            'count' => $total,
            'data' => $data
        ]);
    }
    
    /**
     * 添加机器人
     */
    public function addRobot(Request $request)
    {
        if ($request->method() === 'GET') {
            // 获取会员组列表
            $groups = Db::table('caipiao_membergroup')
                ->select('groupid', 'groupname')
                ->where('groupstatus', 1)
                ->orderBy('listorder', 'asc')
                ->get();
            return view('robot/add', ['groups' => $groups]);
        }
        
        $username = $request->post('username');
        $password = $request->post('password');
        $groupid = $request->post('groupid', 1);
        
        if (empty($username) || empty($password)) {
            return json(['code' => 1, 'msg' => '用户名和密码不能为空']);
        }
        
        // 检查用户名是否存在
        $exists = Db::table('caipiao_member')->where('username', $username)->exists();
        if ($exists) {
            return json(['code' => 1, 'msg' => '用户名已存在']);
        }
        
        $now = time();
        $data = [
            'username' => $username,
            'password' => md5($password),
            'groupid' => $groupid,
            'isnb' => 1, // 标记为机器人
            'nickname' => $username,
            'proxy' => 0,
            'email' => '',
            'phone' => '',
            'userbankname' => '',
            'tradepassword' => 'd93a5def7511da3d0f2d171d9c344e91',
            'sex' => 1,
            'balance' => 0,
            'point' => 0,
            'xima' => 0,
            'fandian' => '',
            'tel' => '',
            'qq' => '',
            'loginip' => '',
            'iparea' => '',
            'regtime' => $now,
            'regip' => $request->getRealIp(),
            'source' => 'admin',
            'logintime' => 0,
            'loginsource' => 'pc',
            'onlinetime' => 0,
            'islock' => 0,
            'birthday' => '1990-01-01',
            'record' => 0,
            'yebmoney' => 0,
            'money' => 0,
            'yebtime' => 0,
            'yeblixi' => 0,
            'dyebmoney' => 0,
            'ngbalance' => 0,
            'jinjijilu' => 1,
            'parentid' => 0,
        ];
        
        $id = Db::table('caipiao_member')->insertGetId($data);
        
        if ($id) {
            return json(['code' => 0, 'msg' => '添加成功']);
        }
        
        return json(['code' => 1, 'msg' => '添加失败']);
    }
    
    /**
     * 删除机器人
     */
    public function deleteRobot(Request $request)
    {
        $id = $request->post('id');
        
        if (empty($id)) {
            return json(['code' => 1, 'msg' => '参数错误']);
        }
        
        // 只能删除机器人账号
        $member = Db::table('caipiao_member')->where('id', $id)->where('isnb', 1)->first();
        
        if (!$member) {
            return json(['code' => 1, 'msg' => '机器人不存在']);
        }
        
        $result = Db::table('caipiao_member')->where('id', $id)->delete();
        
        if ($result) {
            return json(['code' => 0, 'msg' => '删除成功']);
        }
        
        return json(['code' => 1, 'msg' => '删除失败']);
    }
    
    /**
     * 发单设置 - 配置各彩种合买参数
     */
    public function fadan(Request $request)
    {
        if ($request->method() === 'GET') {
            // 获取所有彩种
            $caipiaoList = Db::table('caipiao_caipiao')
                ->where('isopen', 1)
                ->where('iswh', 0)
                ->orderBy('allsort', 'asc')
                ->orderBy('id', 'desc')
                ->get();
            
            // 处理玩法类型
            foreach ($caipiaoList as $key => $val) {
                $val = (array)$val;
                $wanfaStr = $val['hemai_wanfa_type'] ?? '';
                $val['hemai_wanfa_type'] = !empty($wanfaStr) ? explode(',', $wanfaStr) : [];
                $caipiaoList[$key] = $val;
            }
            
            // 获取各彩种类型的玩法
            $wanfaObj = new \Lib\wanfa_fadan();
            
            return view('robot/fadan', [
                'list' => $caipiaoList,
                'ssc' => $wanfaObj->ssc(),
                'x5' => $wanfaObj->x5(),
                'xy28' => $wanfaObj->xy28(),
                'k3' => $wanfaObj->k3(),
                'pk10' => $wanfaObj->pk10(),
                'dpc' => $wanfaObj->dpc(),
                'lhc' => $wanfaObj->lhc(),
                'keno' => $wanfaObj->keno(),
            ]);
        }
    }
    
    /**
     * 修改合买状态
     */
    public function changeHemaiStatus(Request $request)
    {
        $cpname = $request->post('cpname');
        $status = $request->post('cp_hemai_status');
        
        Db::table('caipiao_caipiao')
            ->where('name', $cpname)
            ->update(['hemai_status' => $status]);
        
        return json(['code' => 0, 'msg' => '操作成功']);
    }
    
    /**
     * 修改合买配置
     */
    public function changeHemaiValue(Request $request)
    {
        $cpname = $request->post('cpname');
        $data = $request->post();
        unset($data['cpname']);
        
        Db::table('caipiao_caipiao')
            ->where('name', $cpname)
            ->update($data);
        
        return json(['code' => 0, 'msg' => '操作成功']);
    }
    
    /**
     * 合买列表
     */
    public function hemai(Request $request)
    {
        // 判断是否是 AJAX 请求（layui table 会带 page 参数）
        $page = $request->get('page');
        
        if (empty($page)) {
            // 获取彩种列表
            $caipiaoList = Db::table('caipiao_caipiao')
                ->orderBy('typeid', 'asc')
                ->orderBy('id', 'desc')
                ->get();
            
            return view('robot/hemai', ['caipiaoList' => $caipiaoList]);
        }
        
        // 处理数据请求
        $limit = $request->get('limit', 20);
        
        $cpname = $request->get('cpname', '');
        $username = $request->get('username', '');
        $trano = $request->get('trano', '');
        $qihao = $request->get('qihao', '');
        $status = $request->get('status', 999);
        $isnb = $request->get('isnb', 999);
        $sDate = $request->get('sDate', '');
        $eDate = $request->get('eDate', '');
        $listorder = $request->get('listorder', '');
        
        $query = Db::table('caipiao_touzhu as t')
            ->leftJoin('caipiao_member as m', 't.uid', '=', 'm.id')
            ->where('t.ishemai', 1);
        
        // 筛选条件
        if (!empty($cpname)) {
            $query->where('t.cpname', $cpname);
        }
        
        if (!empty($username)) {
            $query->where('t.username', 'like', "%$username%");
        }
        
        if (!empty($trano)) {
            $query->where('t.trano', 'like', "%$trano%");
        }
        
        if (!empty($qihao)) {
            $query->where('t.expect', $qihao);
        }
        
        if ($status != 999) {
            $query->where('t.isdraw', $status);
        }
        
        if ($isnb != 999) {
            $query->where('m.isnb', $isnb);
        }
        
        if (!empty($sDate)) {
            $sTime = strtotime($sDate);
            $query->where('t.oddtime', '>=', $sTime);
        }
        
        if (!empty($eDate)) {
            $eTime = strtotime($eDate . ' 23:59:59');
            $query->where('t.oddtime', '<=', $eTime);
        }
        
        // 排序
        if ($listorder == 1) {
            $query->orderBy('t.oddtime', 'desc');
        } elseif ($listorder == 2) {
            $query->orderBy('t.oddtime', 'asc');
        } elseif ($listorder == 3) {
            $query->orderBy('t.amount', 'desc');
        } elseif ($listorder == 4) {
            $query->orderBy('t.amount', 'asc');
        } else {
            $query->orderBy('t.id', 'desc');
        }
        
        $total = $query->count();
        
        $list = $query->select('t.*', 'm.isnb')
            ->offset(($page - 1) * $limit)
            ->limit($limit)
            ->get();
        
        $data = [];
        foreach ($list as $item) {
            $item = (array)$item;
            
            // 状态值和文本
            $isdraw = $item['isdraw'];
            $statusText = '';
            if ($isdraw == 1) {
                $statusText = '已中奖';
            } elseif ($isdraw == 0) {
                $statusText = '未开奖';
            } elseif ($isdraw == -1) {
                $statusText = '未中奖';
            } elseif ($isdraw == -2) {
                $statusText = '撤单';
            }
            
            // 类型显示
            $typeText = $item['ishemai'] == 1 ? '合买' : '代购';
            
            // 号码显示
            $tzcode = $item['tzcode'] ?? '';
            $tzcodeDisplay = strlen($tzcode) <= 20 ? $tzcode : '查看';
            
            $data[] = [
                'id' => $item['id'],
                'trano' => $item['trano'],
                'username' => $item['username'],
                'cptitle' => $item['cptitle'],
                'expect' => $item['expect'],
                'playtitle' => $item['playtitle'],
                'itemcount' => $item['itemcount'],
                'mode' => $item['mode'],
                'amount' => $item['amount'],
                'amountafter' => $item['amountafter'],
                'okamount' => $item['okamount'],
                'okcount' => $item['okcount'],
                'beishu' => $item['beishu'],
                'yjf' => $item['yjf'],
                'tzcode' => $tzcodeDisplay,
                'tzcode_full' => $tzcode,
                'opencode' => $item['opencode'] ?? '',
                'type' => $typeText,
                'status' => $statusText,
                'status_raw' => $isdraw,
                'oddtime' => date('m-d H:i:s', $item['oddtime']),
                'isnb' => $item['isnb'] ?? 0,
            ];
        }
        
        return json([
            'code' => 0,
            'msg' => '获取成功',
            'count' => $total,
            'data' => $data
        ]);
    }
    
    /**
     * 合买详情
     */
    public function hemaiDetail(Request $request)
    {
        $id = $request->get('id');
        
        if (empty($id)) {
            return view('robot/hemai-detail', ['list' => [], 'error' => '参数错误']);
        }
        
        // 先查询主订单信息
        $mainOrder = Db::table('caipiao_touzhu')
            ->where('id', $id)
            ->first();
        
        if (!$mainOrder) {
            return view('robot/hemai-detail', ['list' => [], 'error' => '订单不存在']);
        }
        
        // 查询合买参与记录
        $detail = Db::table('caipiao_touzhuhm')
            ->where('touzhuid', $id)
            ->get();
        
        return view('robot/hemai-detail', [
            'list' => $detail,
            'mainOrder' => $mainOrder,
            'error' => ''
        ]);
    }
}

