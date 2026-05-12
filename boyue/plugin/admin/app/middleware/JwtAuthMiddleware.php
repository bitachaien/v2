<?php

namespace plugin\admin\app\middleware;

use plugin\admin\app\service\AdminJwtService;
use plugin\admin\app\model\Admin;
use plugin\admin\app\model\AdminRole;
use Webman\Http\Request;
use Webman\Http\Response;
use Webman\MiddlewareInterface;

/**
 * 后台 JWT 认证中间件
 */
class JwtAuthMiddleware implements MiddlewareInterface
{
    public function process(Request $request, callable $handler): Response
    {
        $controller = $request->controller;
        $action = $request->action;
        
        // 获取控制器鉴权信息
        if ($controller) {
            $class = new \ReflectionClass($controller);
            $properties = $class->getDefaultProperties();
            $noNeedLogin = $properties['noNeedLogin'] ?? [];
            
            // 不需要登录的方法直接放行
            if (in_array($action, $noNeedLogin)) {
                return $handler($request);
            }
        }
        
        // 获取 token
        $token = $this->getToken($request);
        
        if (empty($token)) {
            return $this->unauthorized('请登录', 401);
        }
        
        // 验证 JWT
        $result = AdminJwtService::validateToken($token);
        
        if (!$result['valid']) {
            $code = str_contains($result['error'] ?? '', '过期') ? 401 : 401;
            return $this->unauthorized($result['error'] ?? '认证失败', $code);
        }
        
        $payload = $result['payload'];
        
        // 从数据库获取最新的管理员信息
        $admin = Admin::find($payload->admin_id);
        if (!$admin) {
            return $this->unauthorized('账户不存在', 401);
        }
        
        if ($admin->status != 0) {
            return $this->unauthorized('账户已被禁用', 401);
        }
        
        // 获取角色
        $roles = AdminRole::where('admin_id', $admin->id)->pluck('role_id')->toArray();
        
        // 将管理员信息存入请求
        $adminData = $admin->toArray();
        $adminData['roles'] = $roles;
        $adminData['password'] = md5($adminData['password']);
        
        $request->adminId = $admin->id;
        $request->adminUser = $adminData;
        $request->jwtPayload = $payload;
        
        // 同时存入 session 以兼容现有代码
        $request->session()->set('admin', $adminData);
        
        return $handler($request);
    }
    
    /**
     * 从请求中获取 Token
     */
    protected function getToken(Request $request): string
    {
        // 优先从 Authorization 头获取
        $token = $request->header('Authorization', '');
        if (str_starts_with($token, 'Bearer ')) {
            return substr($token, 7);
        }
        
        // 其他方式
        if (empty($token)) {
            $token = $request->header('token', '');
        }
        if (empty($token)) {
            $token = $request->get('token', '');
        }
        if (empty($token)) {
            $token = $request->post('token', '');
        }
        
        return $token;
    }
    
    /**
     * 返回未授权响应
     */
    protected function unauthorized(string $msg, int $code = 401): Response
    {
        return json([
            'code' => $code,
            'msg' => $msg,
            'data' => []
        ]);
    }
}
