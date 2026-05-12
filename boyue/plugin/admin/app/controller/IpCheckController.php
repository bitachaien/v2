<?php

namespace plugin\admin\app\controller;

use support\Request;
use support\Db;

class IpCheckController extends Base
{
    /**
     * 同IP会员检测页面
     */
    public function index(Request $request)
    {
        return view('ip-check/index');
    }
    
    /**
     * 获取同IP会员列表数据（登录IP）
     */
    public function getData(Request $request)
    {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 20);
        $ip = $request->get('ip', '');
        $username = $request->get('username', '');
        $type = $request->get('type', 'login'); // login 或 reg
        
        $ipField = $type === 'reg' ? 'regip' : 'loginip';
        $timeField = $type === 'reg' ? 'regtime' : 'logintime';
        
        // 构建 WHERE 条件
        $where = "$ipField != '' AND $ipField IS NOT NULL";
        $params = [];
        
        if ($ip) {
            $where .= " AND $ipField LIKE ?";
            $params[] = "%$ip%";
        }
        
        // 查询相同IP的会员
        $sql = "
            SELECT 
                $ipField as ip,
                COUNT(*) as count,
                GROUP_CONCAT(username ORDER BY $timeField DESC SEPARATOR ',') as usernames,
                GROUP_CONCAT(id ORDER BY $timeField DESC SEPARATOR ',') as user_ids,
                MAX($timeField) as last_time
            FROM caipiao_member 
            WHERE $where
            GROUP BY $ipField 
            HAVING count > 1
            ORDER BY count DESC
        ";
        
        $list = Db::select($sql, $params);
        
        $result = [];
        foreach ($list as $item) {
            // 如果有用户名筛选，检查是否包含该用户
            if ($username) {
                $usernameList = explode(',', $item->usernames);
                $found = false;
                foreach ($usernameList as $u) {
                    if (stripos($u, $username) !== false) {
                        $found = true;
                        break;
                    }
                }
                if (!$found) continue;
            }
            
            $result[] = [
                'loginip' => $item->ip,
                'count' => $item->count,
                'usernames' => $item->usernames,
                'user_ids' => $item->user_ids,
                'last_login_time' => $item->last_time ? date('Y-m-d H:i:s', $item->last_time) : ''
            ];
        }
        
        // 分页
        $total = count($result);
        $offset = ($page - 1) * $limit;
        $pageData = array_slice($result, $offset, $limit);
        
        return json([
            'code' => 0,
            'msg' => 'success',
            'count' => $total,
            'data' => $pageData
        ]);
    }
    
    /**
     * 获取指定IP的会员详情列表
     */
    public function getMembersByIp(Request $request)
    {
        $ip = $request->get('ip', '');
        $type = $request->get('type', 'login'); // login 或 reg
        
        if (!$ip) {
            return json(['code' => 1, 'msg' => '请提供IP地址', 'data' => []]);
        }
        
        $ipField = $type === 'reg' ? 'regip' : 'loginip';
        
        $members = Db::table('caipiao_member')
            ->where($ipField, $ip)
            ->select(['id', 'username', 'nickname', 'userbankname', 'balance', 'logintime', 'regtime', 'loginip', 'regip', 'islock'])
            ->orderBy('logintime', 'desc')
            ->get();
        
        $result = [];
        foreach ($members as $m) {
            $result[] = [
                'id' => $m->id,
                'username' => $m->username,
                'nickname' => $m->nickname,
                'userbankname' => $m->userbankname,
                'balance' => $m->balance,
                'logintime' => $m->logintime ? date('Y-m-d H:i:s', $m->logintime) : '',
                'regtime' => $m->regtime ? date('Y-m-d H:i:s', $m->regtime) : '',
                'loginip' => $m->loginip,
                'regip' => $m->regip,
                'islock' => $m->islock
            ];
        }
        
        return json([
            'code' => 0,
            'msg' => 'success',
            'data' => $result
        ]);
    }
    
    /**
     * 获取统计数据
     */
    public function getStats(Request $request)
    {
        // 登录IP重复统计
        $loginIpStats = Db::select("
            SELECT COUNT(*) as ip_count, SUM(cnt) as user_count FROM (
                SELECT loginip, COUNT(*) as cnt 
                FROM caipiao_member 
                WHERE loginip != '' AND loginip IS NOT NULL
                GROUP BY loginip 
                HAVING cnt > 1
            ) t
        ");
        
        // 注册IP重复统计
        $regIpStats = Db::select("
            SELECT COUNT(*) as ip_count, SUM(cnt) as user_count FROM (
                SELECT regip, COUNT(*) as cnt 
                FROM caipiao_member 
                WHERE regip != '' AND regip IS NOT NULL
                GROUP BY regip 
                HAVING cnt > 1
            ) t
        ");
        
        return json([
            'code' => 0,
            'msg' => 'success',
            'data' => [
                'login_ip' => [
                    'duplicate_ip_count' => $loginIpStats[0]->ip_count ?? 0,
                    'affected_user_count' => $loginIpStats[0]->user_count ?? 0
                ],
                'reg_ip' => [
                    'duplicate_ip_count' => $regIpStats[0]->ip_count ?? 0,
                    'affected_user_count' => $regIpStats[0]->user_count ?? 0
                ]
            ]
        ]);
    }
}

