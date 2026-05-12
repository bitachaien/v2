<?php

namespace plugin\admin\app\controller;

use support\Request;
use support\Db;
use plugin\admin\app\model\Admin;
use plugin\admin\app\model\Role;
use plugin\admin\app\model\AdminRole;
use plugin\admin\app\common\Auth;

/**
 * 管理员管理 API 控制器 (Art Design Pro 前端专用)
 */
class AdminApiController extends Base
{
    /**
     * 不需要鉴权的方法
     */
    protected $noNeedAuth = [];

    /**
     * ============================================
     * 管理员组（角色）管理
     * ============================================
     */

    /**
     * 角色列表
     * GET /app/admin/api/admin/role-list
     */
    public function roleList(Request $request)
    {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 20);
        $name = $request->get('name', '');

        $query = Db::table('wa_roles');

        if ($name) {
            $query->where('name', 'like', "%{$name}%");
        }

        // 获取总数
        $count = $query->count();

        // 分页
        $offset = ($page - 1) * $limit;
        $list = $query->orderBy('id', 'asc')
            ->offset($offset)
            ->limit($limit)
            ->get();

        $result = [];
        foreach ($list as $item) {
            $row = (array)$item;
            $row['is_super'] = $row['rules'] === '*';
            $row['created_at_text'] = $row['created_at'] ?? '';
            $result[] = $row;
        }

        return $this->json(0, 'ok', $result, $count);
    }

    /**
     * 角色详情
     * GET /app/admin/api/admin/role-detail
     */
    public function roleDetail(Request $request)
    {
        $id = $request->get('id');
        if (!$id) {
            return $this->json(1, '参数错误');
        }

        $role = Db::table('wa_roles')->where('id', $id)->first();
        if (!$role) {
            return $this->json(1, '角色不存在');
        }

        $data = (array)$role;
        $data['rule_ids'] = $data['rules'] === '*' ? [] : explode(',', $data['rules']);

        return $this->json(0, 'ok', $data);
    }

    /**
     * 添加角色
     * POST /app/admin/api/admin/role-add
     */
    public function roleAdd(Request $request)
    {
        $name = $request->post('name');
        $pid = $request->post('pid', 0);
        $rules = $request->post('rules', '');

        if (!$name) {
            return $this->json(1, '角色名称不能为空');
        }

        // 检查名称是否重复
        $exists = Db::table('wa_roles')->where('name', $name)->first();
        if ($exists) {
            return $this->json(1, '角色名称已存在');
        }

        $id = Db::table('wa_roles')->insertGetId([
            'name' => $name,
            'pid' => $pid,
            'rules' => is_array($rules) ? implode(',', $rules) : $rules,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return $this->json(0, '添加成功', ['id' => $id]);
    }

    /**
     * 编辑角色
     * POST /app/admin/api/admin/role-edit
     */
    public function roleEdit(Request $request)
    {
        $id = $request->post('id');
        $name = $request->post('name');
        $pid = $request->post('pid');
        $rules = $request->post('rules');

        if (!$id) {
            return $this->json(1, '参数错误');
        }

        $role = Db::table('wa_roles')->where('id', $id)->first();
        if (!$role) {
            return $this->json(1, '角色不存在');
        }

        // 超级管理员角色不允许修改权限
        if ($role->rules === '*') {
            $data = [
                'name' => $name ?: $role->name,
                'updated_at' => date('Y-m-d H:i:s'),
            ];
        } else {
            $data = [
                'name' => $name ?: $role->name,
                'pid' => $pid ?? $role->pid,
                'rules' => is_array($rules) ? implode(',', $rules) : ($rules ?? $role->rules),
                'updated_at' => date('Y-m-d H:i:s'),
            ];
        }

        Db::table('wa_roles')->where('id', $id)->update($data);

        return $this->json(0, '修改成功');
    }

    /**
     * 删除角色
     * POST /app/admin/api/admin/role-delete
     */
    public function roleDelete(Request $request)
    {
        $id = $request->post('id');

        if (!$id) {
            return $this->json(1, '参数错误');
        }

        if ($id == 1) {
            return $this->json(1, '超级管理员角色不能删除');
        }

        // 检查是否有管理员使用该角色
        $count = Db::table('wa_admin_roles')->where('role_id', $id)->count();
        if ($count > 0) {
            return $this->json(1, '该角色下有管理员，无法删除');
        }

        Db::table('wa_roles')->where('id', $id)->delete();

        return $this->json(0, '删除成功');
    }

    /**
     * 获取所有角色（下拉选择用）
     * GET /app/admin/api/admin/role-options
     */
    public function roleOptions(Request $request)
    {
        $list = Db::table('wa_roles')
            ->select('id', 'name', 'pid')
            ->orderBy('id', 'asc')
            ->get();

        $result = [];
        foreach ($list as $item) {
            $result[] = [
                'value' => $item->id,
                'label' => $item->name,
                'pid' => $item->pid,
            ];
        }

        return $this->json(0, 'ok', $result);
    }

    /**
     * ============================================
     * 管理员管理
     * ============================================
     */

    /**
     * 管理员列表
     * GET /app/admin/api/admin/list
     */
    public function list(Request $request)
    {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 20);
        $username = $request->get('username', '');
        $status = $request->get('status', '');

        $query = Db::table('wa_admins');

        if ($username) {
            $query->where('username', 'like', "%{$username}%");
        }

        if ($status !== '') {
            $query->where('status', $status);
        }

        // 获取总数
        $count = $query->count();

        // 分页
        $offset = ($page - 1) * $limit;
        $list = $query->orderBy('id', 'asc')
            ->offset($offset)
            ->limit($limit)
            ->get();

        // 获取角色关联
        $adminIds = array_column($list->toArray(), 'id');
        $roleMap = [];
        if (!empty($adminIds)) {
            $adminRoles = Db::table('wa_admin_roles')
                ->whereIn('admin_id', $adminIds)
                ->get();
            foreach ($adminRoles as $ar) {
                $roleMap[$ar->admin_id][] = $ar->role_id;
            }
        }

        // 获取角色名称
        $roles = Db::table('wa_roles')->pluck('name', 'id');

        $result = [];
        foreach ($list as $item) {
            $row = (array)$item;
            unset($row['password']); // 不返回密码

            $row['status_text'] = $row['status'] == 0 ? '正常' : '禁用';
            $row['role_ids'] = $roleMap[$row['id']] ?? [];
            $row['role_names'] = [];
            foreach ($row['role_ids'] as $roleId) {
                if (isset($roles[$roleId])) {
                    $row['role_names'][] = $roles[$roleId];
                }
            }
            $row['role_names_text'] = implode(', ', $row['role_names']);
            $row['created_at_text'] = $row['created_at'] ?? '';
            $row['login_at_text'] = $row['login_at'] ?? '';

            $result[] = $row;
        }

        return $this->json(0, 'ok', $result, $count);
    }

    /**
     * 管理员详情
     * GET /app/admin/api/admin/detail
     */
    public function detail(Request $request)
    {
        $id = $request->get('id');
        if (!$id) {
            return $this->json(1, '参数错误');
        }

        $admin = Db::table('wa_admins')->where('id', $id)->first();
        if (!$admin) {
            return $this->json(1, '管理员不存在');
        }

        $data = (array)$admin;
        unset($data['password']);

        // 获取角色
        $roleIds = Db::table('wa_admin_roles')
            ->where('admin_id', $id)
            ->pluck('role_id')
            ->toArray();
        $data['role_ids'] = $roleIds;

        return $this->json(0, 'ok', $data);
    }

    /**
     * 添加管理员
     * POST /app/admin/api/admin/add
     */
    public function add(Request $request)
    {
        $username = $request->post('username');
        $password = $request->post('password');
        $nickname = $request->post('nickname', '');
        $email = $request->post('email', '');
        $mobile = $request->post('mobile', '');
        $avatar = $request->post('avatar', '');
        $status = $request->post('status', 0);
        $roleIds = $request->post('role_ids', []);

        if (!$username) {
            return $this->json(1, '用户名不能为空');
        }

        if (!$password) {
            return $this->json(1, '密码不能为空');
        }

        if (empty($roleIds)) {
            return $this->json(1, '请选择角色');
        }

        // 检查用户名是否重复
        $exists = Db::table('wa_admins')->where('username', $username)->first();
        if ($exists) {
            return $this->json(1, '用户名已存在');
        }

        $now = date('Y-m-d H:i:s');
        $id = Db::table('wa_admins')->insertGetId([
            'username' => $username,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'nickname' => $nickname,
            'email' => $email,
            'mobile' => $mobile,
            'avatar' => $avatar,
            'status' => $status,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // 添加角色关联
        foreach ($roleIds as $roleId) {
            Db::table('wa_admin_roles')->insert([
                'admin_id' => $id,
                'role_id' => $roleId,
            ]);
        }

        return $this->json(0, '添加成功', ['id' => $id]);
    }

    /**
     * 编辑管理员
     * POST /app/admin/api/admin/edit
     */
    public function edit(Request $request)
    {
        $id = $request->post('id');
        $username = $request->post('username');
        $password = $request->post('password');
        $nickname = $request->post('nickname');
        $email = $request->post('email');
        $mobile = $request->post('mobile');
        $avatar = $request->post('avatar');
        $status = $request->post('status');
        $roleIds = $request->post('role_ids');

        if (!$id) {
            return $this->json(1, '参数错误');
        }

        $admin = Db::table('wa_admins')->where('id', $id)->first();
        if (!$admin) {
            return $this->json(1, '管理员不存在');
        }

        // 不能禁用自己
        if ($status == 1 && $id == admin_id()) {
            return $this->json(1, '不能禁用自己');
        }

        $data = ['updated_at' => date('Y-m-d H:i:s')];

        if ($username !== null) {
            // 检查用户名是否重复
            $exists = Db::table('wa_admins')
                ->where('username', $username)
                ->where('id', '!=', $id)
                ->first();
            if ($exists) {
                return $this->json(1, '用户名已存在');
            }
            $data['username'] = $username;
        }

        if ($password) {
            $data['password'] = password_hash($password, PASSWORD_DEFAULT);
        }

        if ($nickname !== null) $data['nickname'] = $nickname;
        if ($email !== null) $data['email'] = $email;
        if ($mobile !== null) $data['mobile'] = $mobile;
        if ($avatar !== null) $data['avatar'] = $avatar;
        if ($status !== null) $data['status'] = $status;

        Db::table('wa_admins')->where('id', $id)->update($data);

        // 更新角色关联
        if ($roleIds !== null && is_array($roleIds)) {
            Db::table('wa_admin_roles')->where('admin_id', $id)->delete();
            foreach ($roleIds as $roleId) {
                Db::table('wa_admin_roles')->insert([
                    'admin_id' => $id,
                    'role_id' => $roleId,
                ]);
            }
        }

        return $this->json(0, '修改成功');
    }

    /**
     * 删除管理员
     * POST /app/admin/api/admin/delete
     */
    public function delete(Request $request)
    {
        $id = $request->post('id');

        if (!$id) {
            return $this->json(1, '参数错误');
        }

        if ($id == admin_id()) {
            return $this->json(1, '不能删除自己');
        }

        // 删除管理员
        Db::table('wa_admins')->where('id', $id)->delete();
        // 删除角色关联
        Db::table('wa_admin_roles')->where('admin_id', $id)->delete();

        return $this->json(0, '删除成功');
    }

    /**
     * 更新管理员状态
     * POST /app/admin/api/admin/status
     */
    public function status(Request $request)
    {
        $id = $request->post('id');
        $status = $request->post('status');

        if (!$id || $status === null) {
            return $this->json(1, '参数错误');
        }

        if ($id == admin_id() && $status == 1) {
            return $this->json(1, '不能禁用自己');
        }

        Db::table('wa_admins')->where('id', $id)->update([
            'status' => $status,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return $this->json(0, '状态更新成功');
    }

    /**
     * ============================================
     * 管理员日志
     * ============================================
     */

    /**
     * 管理员操作日志列表
     * GET /app/admin/api/admin/log-list
     */
    public function logList(Request $request)
    {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 20);
        $username = $request->get('username', '');
        $ip = $request->get('ip', '');
        $startDate = $request->get('start_date', '');
        $endDate = $request->get('end_date', '');

        // 尝试查询 wa_admin_log 表，如果不存在则使用 caipiao_adminlog
        $tableName = 'wa_admin_log';
        try {
            Db::table($tableName)->first();
        } catch (\Exception $e) {
            $tableName = 'caipiao_adminlog';
            try {
                Db::table($tableName)->first();
            } catch (\Exception $e2) {
                // 两个表都不存在，返回空数据
                return $this->json(0, 'ok', [], 0);
            }
        }

        $query = Db::table($tableName);

        if ($username) {
            $query->where('username', 'like', "%{$username}%");
        }

        if ($ip) {
            $query->where('ip', 'like', "%{$ip}%");
        }

        if ($startDate) {
            if ($tableName === 'caipiao_adminlog') {
                $query->where('time', '>=', strtotime($startDate));
            } else {
                $query->where('created_at', '>=', $startDate);
            }
        }

        if ($endDate) {
            if ($tableName === 'caipiao_adminlog') {
                $query->where('time', '<=', strtotime($endDate) + 86400);
            } else {
                $query->where('created_at', '<=', $endDate . ' 23:59:59');
            }
        }

        // 获取总数
        $count = $query->count();

        // 分页
        $offset = ($page - 1) * $limit;
        $list = $query->orderBy('id', 'desc')
            ->offset($offset)
            ->limit($limit)
            ->get();

        $result = [];
        foreach ($list as $item) {
            $row = (array)$item;
            
            // 统一时间格式
            if (isset($row['time']) && is_numeric($row['time'])) {
                $row['created_at'] = date('Y-m-d H:i:s', $row['time']);
            }
            
            $result[] = $row;
        }

        return $this->json(0, 'ok', $result, $count);
    }

    /**
     * 删除管理员日志
     * POST /app/admin/api/admin/log-delete
     */
    public function logDelete(Request $request)
    {
        $id = $request->post('id');
        $ids = $request->post('ids', []);

        if (!$id && empty($ids)) {
            return $this->json(1, '参数错误');
        }

        $deleteIds = $id ? [$id] : $ids;

        // 尝试删除
        $tableName = 'wa_admin_log';
        try {
            Db::table($tableName)->whereIn('id', $deleteIds)->delete();
        } catch (\Exception $e) {
            try {
                Db::table('caipiao_adminlog')->whereIn('id', $deleteIds)->delete();
            } catch (\Exception $e2) {
                return $this->json(1, '删除失败');
            }
        }

        return $this->json(0, '删除成功');
    }

    /**
     * 清空管理员日志
     * POST /app/admin/api/admin/log-clear
     */
    public function logClear(Request $request)
    {
        $days = $request->post('days', 30); // 保留最近N天

        $tableName = 'wa_admin_log';
        try {
            if ($days > 0) {
                $time = strtotime("-{$days} days");
                Db::table($tableName)->where('created_at', '<', date('Y-m-d H:i:s', $time))->delete();
            } else {
                Db::table($tableName)->truncate();
            }
        } catch (\Exception $e) {
            try {
                $tableName = 'caipiao_adminlog';
                if ($days > 0) {
                    $time = strtotime("-{$days} days");
                    Db::table($tableName)->where('time', '<', $time)->delete();
                } else {
                    Db::table($tableName)->truncate();
                }
            } catch (\Exception $e2) {
                return $this->json(1, '清空失败');
            }
        }

        return $this->json(0, '清空成功');
    }

    /**
     * ============================================
     * 权限管理
     * ============================================
     */

    /**
     * 获取权限树（用于角色分配权限）
     * GET /app/admin/api/admin/rule-tree
     * 只返回业务权限（id >= 1000），不包含 webman-admin 框架自带权限
     */
    public function ruleTree(Request $request)
    {
        $roleId = $request->get('role_id'); // 可选，传入则返回该角色已选中的权限

        // 只获取业务权限（id >= 1000）
        $rules = Db::table('wa_rules')
            ->where('id', '>=', 1000)
            ->orderBy('weight', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        // 获取角色已有权限
        $checkedIds = [];
        if ($roleId) {
            $role = Db::table('wa_roles')->where('id', $roleId)->first();
            if ($role && $role->rules !== '*' && $role->rules !== '') {
                $ids = explode(',', $role->rules);
                // 转换为整数并只保留业务权限ID (>= 1000)
                foreach ($ids as $id) {
                    $id = (int)$id;
                    if ($id >= 1000) {
                        $checkedIds[] = $id;
                    }
                }
            } elseif ($role && $role->rules === '*') {
                // 超级管理员，全选业务权限
                $checkedIds = array_map('intval', array_column($rules->toArray(), 'id'));
            }
        }

        // 构建树形结构
        $tree = $this->buildRuleTree($rules->toArray(), null, $checkedIds);

        return $this->json(0, 'ok', [
            'tree' => $tree,
            'checkedIds' => $checkedIds,
        ]);
    }

    /**
     * 构建权限树（业务权限专用）
     * 顶级菜单 pid = 0 或 NULL
     */
    private function buildRuleTree(array $rules, $parentId = null, array $checkedIds = []): array
    {
        $tree = [];
        foreach ($rules as $rule) {
            $rule = (array)$rule;
            
            // 判断是否为顶级菜单（pid = 0, NULL, 或空字符串）
            $isTopLevel = ($parentId === null && (empty($rule['pid']) || $rule['pid'] == 0));
            $isChild = ($parentId !== null && $rule['pid'] == $parentId);
            
            if ($isTopLevel || $isChild) {
                $children = $this->buildRuleTree($rules, $rule['id'], $checkedIds);
                
                // 类型说明：0=目录, 1=菜单, 2=按钮/权限
                $typeMap = [0 => 'directory', 1 => 'menu', 2 => 'button'];
                
                $item = [
                    'id' => (int)$rule['id'],
                    'label' => $rule['title'],
                    'key' => $rule['key'],
                    'type' => $typeMap[$rule['type']] ?? 'menu',
                    'icon' => $rule['icon'] ?? '',
                    'checked' => in_array((int)$rule['id'], $checkedIds, true),
                ];

                if (!empty($children)) {
                    $item['children'] = $children;
                }

                $tree[] = $item;
            }
        }
        return $tree;
    }

    /**
     * 获取权限列表（扁平结构）
     * GET /app/admin/api/admin/rule-list
     * 只返回业务权限（id >= 1000）
     */
    public function ruleList(Request $request)
    {
        $type = $request->get('type', ''); // 0=目录, 1=菜单, 2=按钮

        $query = Db::table('wa_rules')
            ->where('id', '>=', 1000) // 只返回业务权限
            ->orderBy('weight', 'asc')
            ->orderBy('id', 'asc');

        if ($type !== '') {
            $query->where('type', $type);
        }

        $list = $query->get();

        $result = [];
        foreach ($list as $item) {
            $row = (array)$item;
            $typeMap = [0 => '目录', 1 => '菜单', 2 => '按钮'];
            $row['type_text'] = $typeMap[$row['type']] ?? '未知';
            $result[] = $row;
        }

        return $this->json(0, 'ok', $result);
    }

    /**
     * 保存角色权限
     * POST /app/admin/api/admin/role-rules
     */
    public function roleRules(Request $request)
    {
        $roleId = $request->post('role_id');
        $ruleIds = $request->post('rule_ids', []);

        if (!$roleId) {
            return $this->json(1, '参数错误');
        }

        $role = Db::table('wa_roles')->where('id', $roleId)->first();
        if (!$role) {
            return $this->json(1, '角色不存在');
        }

        // 超级管理员角色不允许修改权限
        if ($role->rules === '*') {
            return $this->json(1, '超级管理员角色权限不可修改');
        }

        // 验证权限 ID 是否存在
        if (!empty($ruleIds)) {
            $existIds = Db::table('wa_rules')
                ->whereIn('id', $ruleIds)
                ->pluck('id')
                ->toArray();
            
            if (count($existIds) !== count($ruleIds)) {
                return $this->json(1, '包含无效的权限ID');
            }
        }

        // 更新角色权限
        $rules = is_array($ruleIds) ? implode(',', $ruleIds) : '';
        
        Db::table('wa_roles')->where('id', $roleId)->update([
            'rules' => $rules,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return $this->json(0, '权限保存成功');
    }

    /**
     * 获取角色已分配的权限ID列表
     * GET /app/admin/api/admin/role-rule-ids
     * 只返回业务权限（id >= 1000）
     */
    public function roleRuleIds(Request $request)
    {
        $roleId = $request->get('role_id');

        if (!$roleId) {
            return $this->json(1, '参数错误');
        }

        $role = Db::table('wa_roles')->where('id', $roleId)->first();
        if (!$role) {
            return $this->json(1, '角色不存在');
        }

        $ruleIds = [];
        if ($role->rules === '*') {
            // 超级管理员，返回所有业务权限
            $ruleIds = Db::table('wa_rules')
                ->where('id', '>=', 1000)
                ->pluck('id')
                ->toArray();
        } elseif ($role->rules !== '') {
            $ruleIds = array_map('intval', explode(',', $role->rules));
            // 只保留业务权限
            $ruleIds = array_values(array_filter($ruleIds, fn($id) => $id >= 1000));
        }

        return $this->json(0, 'ok', [
            'rule_ids' => $ruleIds,
            'is_super' => $role->rules === '*',
        ]);
    }

    /**
     * ============================================
     * IP 归属地
     * ============================================
     */

    /**
     * 获取 IP 归属地
     * GET /app/admin/api/admin/ip-location
     */
    public function ipLocation(Request $request)
    {
        $ip = $request->get('ip');
        
        if (!$ip) {
            return $this->json(1, 'IP地址不能为空');
        }

        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            return $this->json(1, 'IP地址格式不正确');
        }

        $location = \plugin\admin\app\service\IpLocationService::getLocation($ip);

        return $this->json(0, 'ok', [
            'ip' => $ip,
            'location' => $location,
        ]);
    }

    /**
     * 批量更新会员归属地（修复历史数据）
     * POST /app/admin/api/admin/batch-update-location
     */
    public function batchUpdateLocation(Request $request)
    {
        $limit = $request->post('limit', 100);
        $limit = min(max((int)$limit, 10), 500); // 限制 10-500

        $result = \plugin\admin\app\service\IpLocationService::batchUpdateMemberLocation($limit);

        return $this->json(0, '处理完成', $result);
    }
}
