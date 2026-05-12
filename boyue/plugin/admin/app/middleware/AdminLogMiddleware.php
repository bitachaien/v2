<?php

namespace plugin\admin\app\middleware;

use Webman\MiddlewareInterface;
use Webman\Http\Response;
use Webman\Http\Request;
use plugin\admin\app\service\AdminLogService;

/**
 * 管理员操作日志中间件
 * 自动记录 POST/PUT/DELETE 请求
 */
class AdminLogMiddleware implements MiddlewareInterface
{
    /**
     * 需要记录日志的路径前缀
     */
    protected $logPaths = [
        '/app/admin/api/admin/',
        '/app/admin/system/',
        '/app/admin/api/lottery/',
        '/app/admin/member/',
        '/app/admin/finance/',
        '/app/admin/account/login',
        '/app/admin/account/logout',
    ];

    /**
     * 不需要记录的路径
     */
    protected $excludePaths = [
        '/app/admin/api/admin/list',
        '/app/admin/api/admin/detail',
        '/app/admin/api/admin/role-list',
        '/app/admin/api/admin/role-detail',
        '/app/admin/api/admin/rule-tree',
        '/app/admin/api/admin/rule-list',
        '/app/admin/api/admin/log-list',
        '/app/admin/api/admin/ip-location',
    ];

    public function process(Request $request, callable $handler): Response
    {
        $response = $handler($request);
        
        // 只记录写操作
        $method = $request->method();
        if (!in_array($method, ['POST', 'PUT', 'DELETE'])) {
            return $response;
        }

        $path = $request->path();
        
        // 检查是否在排除列表
        foreach ($this->excludePaths as $exclude) {
            if (strpos($path, $exclude) === 0) {
                return $response;
            }
        }
        
        // 检查是否需要记录
        $shouldLog = false;
        foreach ($this->logPaths as $logPath) {
            if (strpos($path, $logPath) === 0) {
                $shouldLog = true;
                break;
            }
        }
        
        if (!$shouldLog) {
            return $response;
        }

        // 获取操作类型和描述
        $type = AdminLogService::getTypeByPath($path, $method);
        $info = $this->getInfoFromResponse($response, $path);
        
        // 异步记录日志（不阻塞响应）
        AdminLogService::log($type, $info, $request);
        
        return $response;
    }

    /**
     * 从响应中获取操作描述
     */
    protected function getInfoFromResponse(Response $response, string $path): string
    {
        try {
            $body = $response->rawBody();
            $data = json_decode($body, true);
            
            if (isset($data['msg']) && $data['msg']) {
                return $data['msg'];
            }
            
            // 根据路径生成默认描述
            return $this->getDefaultInfo($path);
            
        } catch (\Exception $e) {
            return $this->getDefaultInfo($path);
        }
    }

    /**
     * 根据路径获取默认描述
     */
    protected function getDefaultInfo(string $path): string
    {
        $pathInfo = [
            'admin/add' => '添加管理员',
            'admin/edit' => '编辑管理员',
            'admin/delete' => '删除管理员',
            'admin/status' => '更新管理员状态',
            'role-add' => '添加角色',
            'role-edit' => '编辑角色',
            'role-delete' => '删除角色',
            'role-rules' => '更新角色权限',
            'banner-add' => '添加轮播图',
            'banner-edit' => '编辑轮播图',
            'banner-delete' => '删除轮播图',
            'lottery/save' => '保存彩种',
            'lottery/delete' => '删除彩种',
            'login' => '登录系统',
            'logout' => '退出系统',
        ];

        foreach ($pathInfo as $key => $info) {
            if (strpos($path, $key) !== false) {
                return $info;
            }
        }

        return '操作';
    }
}
