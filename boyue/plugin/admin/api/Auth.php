<?php
namespace plugin\admin\api;

use plugin\admin\app\model\Role;
use plugin\admin\app\model\Rule;
use support\exception\BusinessException;
use function admin;

class Auth
{
    
    public static function access(string $controller, string $action)
    {
        $code = 0;
        $msg = '';
        if (!static::canAccess($controller, $action, $code, $msg)) {
            throw new BusinessException($msg, $code);
        }
    }

    
    public static function canAccess(string $controller, string $action, int &$code = 0, string &$msg = ''): bool
    {
        
        if (!$controller) {
            return true;
        }
        
        $class = new \ReflectionClass($controller);
        $properties = $class->getDefaultProperties();
        $noNeedLogin = $properties['noNeedLogin'] ?? [];
        $noNeedAuth = $properties['noNeedAuth'] ?? [];

        
        if (in_array($action, $noNeedLogin)) {
            return true;
        }

        
        $admin = admin();
        if (!$admin) {
            $msg = '请登录';
            
            $code = 401;
            return false;
        }

        
        if (in_array($action, $noNeedAuth)) {
            return true;
        }

        
        $roles = $admin['roles'];
        if (!$roles) {
            $msg = '无权限';
            $code = 2;
            return false;
        }

        
        $rules = Role::whereIn('id', $roles)->pluck('rules');
        $rule_ids = [];
        foreach ($rules as $rule_string) {
            if (!$rule_string) {
                continue;
            }
            $rule_ids = array_merge($rule_ids, explode(',', $rule_string));
        }
        if (!$rule_ids) {
            $msg = '无权限';
            $code = 2;
            return false;
        }

        
        if (in_array('*', $rule_ids)){
            return true;
        }

        
        if (strtolower($action) === 'index') {
            $rule = Rule::where(function ($query) use ($controller, $action) {
                $controller_like = str_replace('\\', '\\\\', $controller);
                $query->where('key', 'like', "$controller_like@%")->orWhere('key', $controller);
            })->whereIn('id', $rule_ids)->first();
            if ($rule) {
                return true;
            }
            $msg = '无权限';
            $code = 2;
            return false;
        }

        
        $rule = Rule::where(function ($query) use ($controller, $action) {
            $query->where('key', "$controller@$action")->orWhere('key', $controller);
        })->whereIn('id', $rule_ids)->first();

        if (!$rule) {
            $msg = '无权限';
            $code = 2;
            return false;
        }

        return true;
    }

}