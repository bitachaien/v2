<?php

namespace plugin\admin\app\service;

use support\Db;
use support\Request;

/**
 * 管理员操作日志服务
 */
class AdminLogService
{
    /**
     * 操作类型映射
     */
    const TYPE_MAP = [
        // 登录相关
        'login' => '登录',
        'logout' => '退出',
        
        // 会员管理
        'member_add' => '添加会员',
        'member_edit' => '编辑会员',
        'member_delete' => '删除会员',
        'member_status' => '会员状态变更',
        'member_balance' => '会员余额调整',
        
        // 充值提现
        'recharge_approve' => '充值审核通过',
        'recharge_reject' => '充值审核拒绝',
        'withdraw_approve' => '提现审核通过',
        'withdraw_reject' => '提现审核拒绝',
        
        // 彩票相关
        'lottery_add' => '添加彩种',
        'lottery_edit' => '编辑彩种',
        'lottery_status' => '彩种状态变更',
        'bet_cancel' => '撤销注单',
        'result_set' => '设置开奖结果',
        
        // 系统管理
        'admin_add' => '添加管理员',
        'admin_edit' => '编辑管理员',
        'admin_delete' => '删除管理员',
        'role_add' => '添加角色',
        'role_edit' => '编辑角色',
        'role_delete' => '删除角色',
        'setting_update' => '系统设置更新',
        
        // 营销管理
        'banner_add' => '添加轮播图',
        'banner_edit' => '编辑轮播图',
        'banner_delete' => '删除轮播图',
        'notice_add' => '添加公告',
        'notice_edit' => '编辑公告',
        'activity_add' => '添加活动',
        'activity_edit' => '编辑活动',
    ];

    /**
     * 记录操作日志
     * 
     * @param string $type 操作类型
     * @param string $info 操作描述
     * @param Request|null $request 请求对象
     * @param array $extra 额外数据
     */
    public static function log(string $type, string $info, ?Request $request = null, array $extra = []): void
    {
        try {
            $adminId = admin_id() ?? 0;
            $adminName = admin('username') ?? 'system';
            
            $ip = '';
            $path = '';
            $method = '';
            $params = '';
            
            if ($request) {
                $ip = $request->getRealIp();
                $path = $request->path();
                $method = $request->method();
                
                // 获取请求参数（过滤敏感信息）
                $requestParams = array_merge($request->get(), $request->post());
                $requestParams = self::filterSensitiveParams($requestParams);
                $params = json_encode($requestParams, JSON_UNESCAPED_UNICODE);
            }
            
            // 获取 IP 归属地
            $iparea = '';
            if ($ip) {
                $iparea = IpLocationService::getLocation($ip);
            }
            
            Db::table('caipiao_adminlog')->insert([
                'userid' => $adminId,
                'username' => $adminName,
                'type' => $type,
                'info' => mb_substr($info, 0, 60),
                'path' => $path,
                'method' => $method,
                'params' => $params,
                'ip' => $ip,
                'iparea' => $iparea,
                'time' => time(),
            ]);
        } catch (\Exception $e) {
            // 日志记录失败不影响业务
            \support\Log::error('AdminLogService::log error: ' . $e->getMessage());
        }
    }

    /**
     * 快捷记录方法
     */
    public static function record(Request $request, string $type, string $info = ''): void
    {
        if (empty($info) && isset(self::TYPE_MAP[$type])) {
            $info = self::TYPE_MAP[$type];
        }
        self::log($type, $info, $request);
    }

    /**
     * 过滤敏感参数
     */
    private static function filterSensitiveParams(array $params): array
    {
        $sensitiveKeys = ['password', 'pwd', 'token', 'secret', 'key', 'trade_password'];
        
        foreach ($params as $key => $value) {
            if (in_array(strtolower($key), $sensitiveKeys)) {
                $params[$key] = '******';
            }
        }
        
        return $params;
    }

    /**
     * 根据路径自动判断操作类型
     */
    public static function getTypeByPath(string $path, string $method): string
    {
        $pathMap = [
            // 管理员
            '/app/admin/api/admin/add' => 'admin_add',
            '/app/admin/api/admin/edit' => 'admin_edit',
            '/app/admin/api/admin/delete' => 'admin_delete',
            '/app/admin/api/admin/role-add' => 'role_add',
            '/app/admin/api/admin/role-edit' => 'role_edit',
            '/app/admin/api/admin/role-delete' => 'role_delete',
            '/app/admin/api/admin/role-rules' => 'role_edit',
            
            // 会员
            '/app/admin/member/add' => 'member_add',
            '/app/admin/member/edit' => 'member_edit',
            '/app/admin/member/delete' => 'member_delete',
            
            // 轮播图
            '/app/admin/system/banner-add' => 'banner_add',
            '/app/admin/system/banner-edit' => 'banner_edit',
            '/app/admin/system/banner-delete' => 'banner_delete',
            
            // 彩票
            '/app/admin/api/lottery/save' => 'lottery_edit',
            '/app/admin/api/lottery/delete' => 'lottery_delete',
            
            // 登录
            '/app/admin/account/login' => 'login',
            '/app/admin/account/logout' => 'logout',
        ];

        return $pathMap[$path] ?? 'operation';
    }
}
