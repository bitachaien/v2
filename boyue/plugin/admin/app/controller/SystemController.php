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
     * Lấy菜单列表 (Art Design Pro 动态路由格式)
     * GET /app/admin/api/system/menus
     */
    public function menus(Request $request)
    {
        $admin = admin();
        if (!$admin) {
            return $this->json(401, '请先登录');
        }

        // LấyNgười dùng角色权限
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

        // Lấy菜单
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
                    // 有子菜单时Cài đặt重定向
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
     * Lấy允许输出的菜单 ID（根节点及其子节点）
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
     * Lấy系统Cài đặt信息 (Art Design Pro 前端)
     * GET /app/admin/system/info
     */
    public function info(Request $request)
    {
        // Lấy所有Cài đặt项
        $setlist = Db::table('caipiao_setting')->get();
        
        $settings = [];
        foreach ($setlist as $item) {
            $settings[$item->name] = $item->value;
        }
        
        // 返回系统信息
        return $this->json(0, 'ok', [
            'siteName' => $settings['webname'] ?? 'Xổ số系统',
            'siteTitle' => $settings['webname'] ?? 'Xổ số管理后台',
            'siteLogo' => $settings['logo'] ?? '',
            'copyright' => $settings['copyright'] ?? '',
            'version' => '1.0.0',
            'settings' => $settings,
        ]);
    }

    /**
     * 系统Cài đặt页面
     */
    public function setting(Request $request)
    {
        // Lấy所有Cài đặt项
        $setlist = Db::table('caipiao_setting')->get();
        
        $settings = [];
        foreach ($setlist as $item) {
            $settings[$item->name] = $item->value;
        }
        
        return view('system/setting', ['settings' => $settings]);
    }
    
    /**
     * Lưu系统Cài đặt
     */
    public function save(Request $request)
    {
        if ($request->method() !== 'POST') {
            return json(['code' => 0, 'msg' => '非法操作']);
        }
        
        $params = $request->post('info', []);
        
        if (empty($params)) {
            return json(['code' => 0, 'msg' => 'Tham số không hợp lệ']);
        }
        
        // 需要验证为数字的字段
        $validNums = [
            'xtclirun' => '系统彩利润格式Lỗi',
            'fanDianMax' => '返点最大值格式Lỗi',
            'fanDianMin' => '返点最小值格式Lỗi',
            'touzhuMax' => '最大Đặt cược格式Lỗi',
            'zhongjiangMax' => '最大Trúng thưởng格式Lỗi',
            'chongzhiMin' => 'Nạp tiền限制（最低Số tiền）格式Lỗi',
            'chongzhiMax' => 'Nạp tiền限制（最高Số tiền）格式Lỗi',
            'damaliang' => 'Rút tiền限制（Vòng cược量）格式Lỗi',
            'tikuanMin' => 'Rút tiền限制（最低Rút tiền）格式Lỗi',
            'tikuanMax' => 'Rút tiền限制（最高Rút tiền）格式Lỗi',
            'ritikuanxiane' => '日Rút tiền限额格式Lỗi',
            'tikuannum' => '日Rút tiền次数格式Lỗi',
            'tikuannumoverbilv' => '超出收取费用比例格式Lỗi',
            'tikuannumovermin' => '超出收取费用最低格式Lỗi',
            'tikuannumovermax' => '超出收取费用最高格式Lỗi',
            'paiduinum' => 'Rút tiền排队人数格式Lỗi',
            'sysBankMaxNum' => '绑卡数格式Lỗi',
            'pointchongzhi' => '积分Quy tắc（每Nạp tiềnN元）格式Lỗi',
            'pointchongzhiadd' => '积分Quy tắc（增加N积分）格式Lỗi',
            'pointtouzhu' => '积分Quy tắc（每Đặt cượcN元）格式Lỗi',
            'pointtouzhuadd' => '积分Quy tắc（Đặt cược增加N积分）格式Lỗi',
            'pointhuisun' => '积分Quy tắc（每亏损N元）格式Lỗi',
            'pointhuisunadd' => '积分Quy tắc（亏损增加N积分）格式Lỗi',
            'loginerrornum' => '后台Đăng nhập最大Thất bại次数格式Lỗi',
            'loginerrorclosetime' => '后台Đăng nhập禁止登陆Thời gian格式Lỗi',
            'loginerrornum_q' => '前台Đăng nhập最大Thất bại次数格式Lỗi',
            'loginerrorclosetime_q' => '前台Đăng nhập禁止登陆Thời gian格式Lỗi',
            'adminemailcodetime' => '后台邮件验证码过期Thời gian格式Lỗi',
            'czaudioplaytime' => 'Nạp tiềnLưu ý音Thời gian格式Lỗi',
            'czaudioqxtime' => 'Nạp tiềnLưu ý音关闭Thời gian格式Lỗi',
            'tkaudioplaytime' => 'Rút tiềnLưu ý音Thời gian格式Lỗi',
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
            
            return json(['code' => 0, 'msg' => '配置LưuThành công']);
            
        } catch (\Exception $e) {
            \support\Log::error('系统Cài đặtLưuThất bại: ' . $e->getMessage());
            return json(['code' => 0, 'msg' => '配置LưuThất bại：' . $e->getMessage()]);
        }
    }
}

