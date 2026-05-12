<?php

namespace plugin\admin\app\controller;

use support\Request;
use support\Db;

class SystemController extends Base
{
    /**
     * 允许输出的菜单根 key 列表（空数组表示输出全部菜单）
     */
    private array $menuRootKeys = [];
    /**
     * 不需要鉴权的方法
     */
    protected $noNeedAuth = ['menus', 'info'];

    /**
     * 获取菜单列表 (Art Design Pro 动态路由格式)
     * GET /app/admin/api/system/menus
     */
    public function menus(Request $request)
    {
        $admin = admin();
        if (!$admin) {
            return $this->json(401, '请先登录');
        }

        // 获取用户角色权限
        $roleIds = $admin['roles'] ?? [];
        $ruleIds = [];
        $isSuper = false;

        if (!empty($roleIds)) {
            $roles = Db::table('wa_roles')->whereIn('id', $roleIds)->get();
            foreach ($roles as $role) {
                if ($role->rules === '*') {
                    $isSuper = true;
                    break;
                }
                $ruleIds = array_merge($ruleIds, explode(',', $role->rules));
            }
        }

        // 获取菜单
        $query = Db::table('wa_rules')
            ->whereIn('type', [0, 1]) // 0=目录, 1=菜单
            ->orderBy('weight', 'asc')
            ->orderBy('id', 'asc');

        // 如果配置了允许的根菜单，则过滤
        if (!empty($this->menuRootKeys)) {
            $allowedIds = $this->getAllowedMenuIds();
            if (empty($allowedIds)) {
                return $this->json(0, 'ok', []);
            }
            $query->whereIn('id', $allowedIds);
        }

        // 非超管按角色权限过滤
        if (!$isSuper && !empty($ruleIds)) {
            $query->whereIn('id', $ruleIds);
        }

        $menus = $query->get()->toArray();

        // 转换为 Art Design Pro 格式
        $result = $this->buildMenuTree($menus);

        return $this->json(0, 'ok', $result);
    }

    /**
     * 构建菜单树 (Art Design Pro 格式)
     */
    private function buildMenuTree(array $menus, $parentId = null): array
    {
        $tree = [];
        
        foreach ($menus as $menu) {
            if ($menu->pid == $parentId) {
                $children = $this->buildMenuTree($menus, $menu->id);
                
                $item = [
                    'path' => $this->convertPath($menu->key, $menu->pid),
                    'name' => $this->convertName($menu->key),
                    'meta' => [
                        'title' => $menu->title,
                        'icon' => $this->convertIcon($menu->icon),
                    ],
                ];

                // 顶级菜单使用 Layout 组件
                if ($parentId === null) {
                    $item['component'] = 'Layout';
                } else {
                    // 子菜单使用页面组件路径
                    $item['component'] = '/' . $menu->key;
                }

                if (!empty($children)) {
                    $item['children'] = $children;
                    // 有子菜单时设置重定向
                    $item['redirect'] = $item['path'] . '/' . $children[0]['path'];
                }

                $tree[] = $item;
            }
        }

        return $tree;
    }

    /**
     * 转换路径
     */
    private function convertPath(string $key, $parentId): string
    {
        if ($parentId === null) {
            // 顶级菜单：statistics-new -> /statistics
            $path = str_replace(['-new', '_'], ['', '-'], $key);
            return '/' . $path;
        }
        
        // 子菜单：statistics/overview/index -> overview
        $parts = explode('/', $key);
        return $parts[1] ?? $key;
    }

    /**
     * 转换组件名
     */
    private function convertName(string $key): string
    {
        // statistics/overview/index -> StatisticsOverview
        $parts = explode('/', $key);
        $name = '';
        foreach ($parts as $part) {
            if ($part !== 'index') {
                $name .= ucfirst(str_replace(['-', '_'], '', $part));
            }
        }
        return $name ?: 'Index';
    }

    /**
     * 转换图标 (Layui -> Remixicon)
     */
    private function convertIcon(?string $icon): string
    {
        $iconMap = [
            'layui-icon-chart' => 'ri:bar-chart-box-line',
            'layui-icon-home' => 'ri:dashboard-line',
            'layui-icon-user' => 'ri:user-line',
            'layui-icon-rmb' => 'ri:money-cny-circle-line',
            'layui-icon-chart-screen' => 'ri:pie-chart-line',
            'layui-icon-set' => 'ri:settings-line',
            'layui-icon-template' => 'ri:layout-line',
        ];

        return $iconMap[$icon] ?? 'ri:file-list-line';
    }

    /**
     * 获取允许输出的菜单 ID（根节点及其子节点）
     */
    private function getAllowedMenuIds(): array
    {
        if (empty($this->menuRootKeys)) {
            return [];
        }

        $rootIds = Db::table('wa_rules')
            ->whereIn('key', $this->menuRootKeys)
            ->pluck('id')
            ->toArray();

        if (empty($rootIds)) {
            return [];
        }

        $allowed = $rootIds;
        $queue = $rootIds;

        while (!empty($queue)) {
            $children = Db::table('wa_rules')
                ->whereIn('pid', $queue)
                ->pluck('id')
                ->toArray();

            $children = array_diff($children, $allowed);
            if (empty($children)) {
                break;
            }

            $allowed = array_merge($allowed, $children);
            $queue = $children;
        }

        return array_unique($allowed);
    }


    /**
     * 获取系统设置信息 (Art Design Pro 前端)
     * GET /app/admin/system/info
     */
    public function info(Request $request)
    {
        // 获取所有设置项
        $setlist = Db::table('caipiao_setting')->get();
        
        $settings = [];
        foreach ($setlist as $item) {
            $settings[$item->name] = $item->value;
        }
        
        // 返回系统信息
        return $this->json(0, 'ok', [
            'siteName' => $settings['webname'] ?? '彩票系统',
            'siteTitle' => $settings['webname'] ?? '彩票管理后台',
            'siteLogo' => $settings['logo'] ?? '',
            'copyright' => $settings['copyright'] ?? '',
            'version' => '1.0.0',
            'settings' => $settings,
        ]);
    }

    /**
     * 系统设置页面
     */
    public function setting(Request $request)
    {
        // 获取所有设置项
        $setlist = Db::table('caipiao_setting')->get();
        
        $settings = [];
        foreach ($setlist as $item) {
            $settings[$item->name] = $item->value;
        }
        
        return view('system/setting', ['settings' => $settings]);
    }
    
    /**
     * 保存系统设置
     */
    public function save(Request $request)
    {
        if ($request->method() !== 'POST') {
            return json(['code' => 0, 'msg' => '非法操作']);
        }
        
        $params = $request->post('info', []);
        
        if (empty($params)) {
            return json(['code' => 0, 'msg' => '参数错误']);
        }
        
        // 需要验证为数字的字段
        $validNums = [
            'xtclirun' => '系统彩利润格式错误',
            'fanDianMax' => '返点最大值格式错误',
            'fanDianMin' => '返点最小值格式错误',
            'touzhuMax' => '最大投注格式错误',
            'zhongjiangMax' => '最大中奖格式错误',
            'chongzhiMin' => '充值限制（最低金额）格式错误',
            'chongzhiMax' => '充值限制（最高金额）格式错误',
            'damaliang' => '提款限制（打码量）格式错误',
            'tikuanMin' => '提款限制（最低提款）格式错误',
            'tikuanMax' => '提款限制（最高提款）格式错误',
            'ritikuanxiane' => '日提款限额格式错误',
            'tikuannum' => '日提款次数格式错误',
            'tikuannumoverbilv' => '超出收取费用比例格式错误',
            'tikuannumovermin' => '超出收取费用最低格式错误',
            'tikuannumovermax' => '超出收取费用最高格式错误',
            'paiduinum' => '提款排队人数格式错误',
            'sysBankMaxNum' => '绑卡数格式错误',
            'pointchongzhi' => '积分规则（每充值N元）格式错误',
            'pointchongzhiadd' => '积分规则（增加N积分）格式错误',
            'pointtouzhu' => '积分规则（每投注N元）格式错误',
            'pointtouzhuadd' => '积分规则（投注增加N积分）格式错误',
            'pointhuisun' => '积分规则（每亏损N元）格式错误',
            'pointhuisunadd' => '积分规则（亏损增加N积分）格式错误',
            'loginerrornum' => '后台登录最大失败次数格式错误',
            'loginerrorclosetime' => '后台登录禁止登陆时间格式错误',
            'loginerrornum_q' => '前台登录最大失败次数格式错误',
            'loginerrorclosetime_q' => '前台登录禁止登陆时间格式错误',
            'adminemailcodetime' => '后台邮件验证码过期时间格式错误',
            'czaudioplaytime' => '充值提示音时间格式错误',
            'czaudioqxtime' => '充值提示音关闭时间格式错误',
            'tkaudioplaytime' => '提款提示音时间格式错误',
        ];
        
        $successCount = 0;
        
        try {
            foreach ($params as $key => $value) {
                $key = trim($key);
                
                // 验证数字字段
                if (isset($validNums[$key])) {
                    if (!is_numeric($value) && $value !== '') {
                        return json(['code' => 0, 'msg' => $validNums[$key]]);
                    }
                    $value = floatval($value);
                }
                
                // 宣传文案转换为JSON数组
                if ($key === 'promo_texts' && is_string($value)) {
                    $lines = array_filter(array_map('trim', explode("\n", $value)));
                    $value = json_encode(array_values($lines), JSON_UNESCAPED_UNICODE);
                }
                
                // 检查是否存在
                $exists = Db::table('caipiao_setting')
                    ->where('name', $key)
                    ->first();
                
                if ($exists) {
                    // 更新
                    Db::table('caipiao_setting')
                        ->where('name', $key)
                        ->update(['value' => $value]);
                } else {
                    // 插入
                    Db::table('caipiao_setting')->insert([
                        'name' => $key,
                        'value' => $value
                    ]);
                }
                
                $successCount++;
            }
            
            return json(['code' => 0, 'msg' => '配置保存成功']);
            
        } catch (\Exception $e) {
            \support\Log::error('系统设置保存失败: ' . $e->getMessage());
            return json(['code' => 0, 'msg' => '配置保存失败：' . $e->getMessage()]);
        }
    }
}

