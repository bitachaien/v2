<?php
namespace plugin\admin\app\middleware;

use plugin\admin\api\Auth;
use plugin\admin\app\service\AdminJwtService;
use plugin\admin\app\model\Admin;
use plugin\admin\app\model\AdminRole;
use ReflectionException;
use support\exception\BusinessException;
use Webman\Http\Request;
use Webman\Http\Response;
use Webman\MiddlewareInterface;

class AccessControl implements MiddlewareInterface
{
    /**
     * @param Request $request
     * @param callable $handler
     * @return Response
     * @throws ReflectionException|BusinessException
     */
    public function process(Request $request, callable $handler): Response
    {
        try {
            // OPTIONS 预检请求直接放行（CORS 由全局中间件处理）
            if ($request->method() === 'OPTIONS') {
                return response('')->withHeaders([
                    'Access-Control-Allow-Origin' => $request->header('origin', '*'),
                    'Access-Control-Allow-Credentials' => 'true',
                    'Access-Control-Allow-Headers' => 'Authorization, Content-Type, X-Requested-With, Accept',
                    'Access-Control-Allow-Methods' => 'GET, POST, PUT, DELETE, OPTIONS, PATCH',
                    'Access-Control-Max-Age' => '86400',
                ]);
            }
            
            // 先尝试从 JWT 解析Người dùng信息
            $this->tryJwtAuth($request);
            
            $controller = $request->controller;
            $action = $request->action;

            $code = 0;
            $msg = '';
            if (!Auth::canAccess($controller, $action, $code, $msg)) {
                // 判断是否为 API 请求（AJAX、JSON 请求、hoặc API 路径）
                $path = $request->path();
                $isApiRequest = $request->expectsJson() 
                    || $request->header('X-Requested-With') === 'XMLHttpRequest'
                    || str_contains($path, '/api/')
                    || str_ends_with($path, '-list')
                    || str_ends_with($path, '-save')
                    || str_ends_with($path, '-delete');
                
                if ($isApiRequest) {
                    $response = json(['code' => $code, 'msg' => $msg, 'data' => []]);
                } else {
                    if ($code === 401) {
                      $response = admin_error_401_script();
                    } else {
                        $request->app = '';
                        $request->plugin = 'admin';
                        $response = view('common/error/403')->withStatus(403);
                    }
                }

            } else {
                $response = $request->method() == 'OPTIONS' ? response('') : $handler($request);
            }

            return $response;
        } catch (\Throwable $e) {
            \support\Log::error('AccessControl Lỗi: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
            return json(['code' => 500, 'msg' => 'Lỗi máy chủ: ' . $e->getMessage(), 'data' => []]);
        }
    }
    
    /**
     * 尝试从 JWT Token 解析Người dùng信息并存入 Session
     */
    protected function tryJwtAuth(Request $request): void
    {
        try {
            // 如果 Session 中已有管理员信息，跳过
            if ($request->session()->get('admin')) {
                return;
            }
            
            // Lấy Token
            $token = $this->getToken($request);
            if (empty($token)) {
                return;
            }
            
            // 验证 JWT
            $result = AdminJwtService::validateToken($token);
            if (!$result['valid']) {
                return;
            }
            
            $payload = $result['payload'];
            
            // 从dữ liệu库Lấy管理员信息
            $admin = Admin::find($payload->admin_id);
            if (!$admin || $admin->status != 0) {
                return;
            }
            
            // Lấy角色
            $roles = AdminRole::where('admin_id', $admin->id)->pluck('role_id')->toArray();
            
            // 存入 Session（兼容现有代码）
            $adminData = $admin->toArray();
            $adminData['roles'] = $roles;
            $adminData['password'] = md5($adminData['password']);
            
            $request->session()->set('admin', $adminData);
        } catch (\Throwable $e) {
            \support\Log::error('tryJwtAuth Lỗi: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
        }
    }
    
    /**
     * 从请求中Lấy Token
     */
    protected function getToken(Request $request): string
    {
        $token = $request->header('Authorization', '');
        if (str_starts_with($token, 'Bearer ')) {
            return substr($token, 7);
        }
        
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
}
