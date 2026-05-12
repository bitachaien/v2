<?php

namespace plugin\admin\app\controller;

use support\Request;
use support\Db;

/**
 * 存款方式配置
 */
class PaysetController
{
    /**
     * 存款方式列表页面
     */
    public function index(Request $request)
    {
        return view('payset/index');
    }

    /**
     * 获取存款方式列表数据
     */
    public function list(Request $request)
    {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 20);

        $query = Db::table('caipiao_payset');

        // 获取总数
        $count = $query->count();

        // 分页查询
        $list = $query->orderBy('listorder', 'asc')
            ->orderBy('id', 'asc')
            ->offset(($page - 1) * $limit)
            ->limit($limit)
            ->get();

        // 处理数据
        $data = [];
        foreach ($list as $item) {
            $row = (array)$item;
            // 判断是否线上支付
            $row['isonline_text'] = $row['isonline'] == 1 ? '<span style="color: #e80808;">是</span>' : '<span style="color: #999999;">否</span>';
            // 解析configs
            $row['configs_data'] = !empty($row['configs']) ? @unserialize($row['configs']) : [];
            $data[] = $row;
        }

        return json([
            'code' => 0,
            'msg' => '',
            'count' => $count,
            'data' => $data
        ]);
    }

    /**
     * 添加存款方式页面
     */
    public function add(Request $request)
    {
        return view('payset/add');
    }

    /**
     * 保存新存款方式
     */
    public function save(Request $request)
    {
        $data = $request->post();

        // 验证必填字段
        if (empty($data['paytype']) || empty($data['paytypetitle'])) {
            return json(['code' => 1, 'msg' => '标识和支付名称不能为空']);
        }

        // 检查标识是否已存在
        $exists = Db::table('caipiao_payset')
            ->where('paytype', $data['paytype'])
            ->exists();

        if ($exists) {
            return json(['code' => 1, 'msg' => '标识已存在']);
        }

        // 处理configs字段 - 支持对象或JSON字符串
        $configs = [];
        if (!empty($data['configs_data'])) {
            if (is_array($data['configs_data'])) {
                $configs = $data['configs_data'];
            } elseif (is_string($data['configs_data'])) {
                $configs = json_decode($data['configs_data'], true) ?: [];
            }
        }
        // 合并单独的configs字段（如 configs_data.bankname -> configs_data[bankname])
        foreach ($data as $key => $value) {
            if (strpos($key, 'configs_data[') === 0) {
                $field = str_replace(['configs_data[', ']'], '', $key);
                $configs[$field] = $value;
            }
        }
        
        $insertData = [
            'paytype' => $data['paytype'],
            'paytypetitle' => $data['paytypetitle'],
            'ftitle' => $data['ftitle'] ?? '',
            'minmoney' => $data['minmoney'] ?? 50.00,
            'maxmoney' => $data['maxmoney'] ?? 50000.00,
            'remark' => $data['remark'] ?? '',
            'configs' => serialize($configs),
            'isonline' => $data['isonline'] ?? -1,
            'state' => $data['state'] ?? 1,
            'listorder' => $data['listorder'] ?? 0
        ];

        $result = Db::table('caipiao_payset')->insert($insertData);

        if ($result) {
            return json(['code' => 0, 'msg' => '添加成功']);
        } else {
            return json(['code' => 1, 'msg' => '添加失败']);
        }
    }

    /**
     * 编辑存款方式页面
     */
    public function edit(Request $request)
    {
        $id = $request->get('id');
        
        if (!$id) {
            return json(['code' => 1, 'msg' => '参数错误']);
        }

        $info = Db::table('caipiao_payset')->where('id', $id)->first();
        
        if (!$info) {
            return json(['code' => 1, 'msg' => '存款方式不存在']);
        }

        return view('payset/edit', ['info' => $info]);
    }

    /**
     * 更新存款方式信息
     */
    public function update(Request $request)
    {
        $id = $request->post('id');
        $data = $request->post();

        if (!$id) {
            return json(['code' => 1, 'msg' => '参数错误']);
        }

        // 验证必填字段
        if (empty($data['paytype']) || empty($data['paytypetitle'])) {
            return json(['code' => 1, 'msg' => '标识和支付名称不能为空']);
        }

        // 检查标识是否与其他记录重复
        $exists = Db::table('caipiao_payset')
            ->where('paytype', $data['paytype'])
            ->where('id', '!=', $id)
            ->exists();

        if ($exists) {
            return json(['code' => 1, 'msg' => '标识已存在']);
        }

        // 处理configs字段 - 支持对象或JSON字符串
        $configs = [];
        if (!empty($data['configs_data'])) {
            if (is_array($data['configs_data'])) {
                $configs = $data['configs_data'];
            } elseif (is_string($data['configs_data'])) {
                $configs = json_decode($data['configs_data'], true) ?: [];
            }
        }
        // 合并单独的configs字段（如 configs_data[bankname])
        foreach ($data as $key => $value) {
            if (strpos($key, 'configs_data[') === 0) {
                $field = str_replace(['configs_data[', ']'], '', $key);
                $configs[$field] = $value;
            }
        }
        
        $updateData = [
            'paytype' => $data['paytype'],
            'paytypetitle' => $data['paytypetitle'],
            'ftitle' => $data['ftitle'] ?? '',
            'minmoney' => $data['minmoney'] ?? 50.00,
            'maxmoney' => $data['maxmoney'] ?? 50000.00,
            'remark' => $data['remark'] ?? '',
            'configs' => serialize($configs),
            'isonline' => $data['isonline'] ?? -1,
            'state' => $data['state'] ?? 1,
            'listorder' => $data['listorder'] ?? 0
        ];

        $result = Db::table('caipiao_payset')->where('id', $id)->update($updateData);

        if ($result !== false) {
            return json(['code' => 0, 'msg' => '更新成功']);
        } else {
            return json(['code' => 1, 'msg' => '更新失败']);
        }
    }

    /**
     * 删除存款方式
     */
    public function delete(Request $request)
    {
        $id = $request->post('id');
        
        if (!$id) {
            return json(['code' => 1, 'msg' => '参数错误']);
        }

        $result = Db::table('caipiao_payset')->where('id', $id)->delete();

        if ($result) {
            return json(['code' => 0, 'msg' => '删除成功']);
        } else {
            return json(['code' => 1, 'msg' => '删除失败']);
        }
    }

    /**
     * 切换状态
     */
    public function setState(Request $request)
    {
        $id = $request->post('id');
        $state = $request->post('state');
        
        if (!$id) {
            return json(['code' => 1, 'msg' => '参数错误']);
        }

        $result = Db::table('caipiao_payset')
            ->where('id', $id)
            ->update(['state' => $state ? 1 : 0]);

        if ($result !== false) {
            return json(['code' => 0, 'msg' => '操作成功']);
        } else {
            return json(['code' => 1, 'msg' => '操作失败']);
        }
    }
}

