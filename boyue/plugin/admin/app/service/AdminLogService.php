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
        // Đăng nhập相关
        'login' => 'Đăng nhập',
        'logout' => 'Đăng xuất',
        
        // Thành viên管理
        'member_add' => 'ThêmThành viên',
        'member_edit' => '编辑Thành viên',
        'member_delete' => 'XóaThành viên',
        'member_status' => 'Thành viên状态变更',
        'member_balance' => 'Thành viênSố dư调整',
        
        // Nạp tiềnRút tiền
        'recharge_approve' => 'Nạp tiền审核通过',
        'recharge_reject' => 'Nạp tiền审核拒绝',
        'withdraw_approve' => 'Rút tiền审核通过',
        'withdraw_reject' => 'Rút tiền审核拒绝',
        
        // Xổ số相关
        'lottery_add' => 'Thêm彩种',
        'lottery_edit' => '编辑彩种',
        'lottery_status' => '彩种状态变更',
        'bet_cancel' => '撤销注单',
        'result_set' => 'Cài đặtMở thưởng结果',
        
        // 系统管理
        'admin_add' => 'Thêm管理员',
        'admin_edit' => '编辑管理员',
        'admin_delete' => 'Xóa管理员',
        'role_add' => 'Thêm角色',
        'role_edit' => '编辑角色',
        'role_delete' => 'Xóa角色',
        'setting_update' => '系统Cài đặt更新',
        
        // 营销管理
        'banner_add' => 'Thêm轮播图',
        'banner_edit' => '编辑轮播图',
        'banner_delete' => 'Xóa轮播图',
        'notice_add' => 'ThêmCông bố',
        'notice_edit' => '编辑Công bố',
        'activity_add' => 'ThêmHoạt động',
        'activity_edit' => '编辑Hoạt động',
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
                
                // Lấy请求参数（过滤敏感信息）
                $requestParams = array_merge($request->get(), $request->post());
                $requestParams = self::filterSensitiveParams($requestParams);
                $params = json_encode($requestParams, JSON_UNESCAPED_UNICODE);
            }
            
            // Lấy IP 归属地
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
            // 日志lịch sử thất bại不影响业务
            \support\Log::error('AdminLogService::log error: ' . $e->getMessage());
        }
    }

    /**
     * 快捷lịch sử方法
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
            
            // Thành viên
            '/app/admin/member/add' => 'member_add',
            '/app/admin/member/edit' => 'member_edit',
            '/app/admin/member/delete' => 'member_delete',
            
            // 轮播图
            '/app/admin/system/banner-add' => 'banner_add',
            '/app/admin/system/banner-edit' => 'banner_edit',
            '/app/admin/system/banner-delete' => 'banner_delete',
            
            // Xổ số
            '/app/admin/api/lottery/save' => 'lottery_edit',
            '/app/admin/api/lottery/delete' => 'lottery_delete',
            
            // Đăng nhập
            '/app/admin/account/login' => 'login',
            '/app/admin/account/logout' => 'logout',
        ];

        return $pathMap[$path] ?? 'operation';
    }
}
