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
     * Lấy nạp tiềnphương thức列表dữ liệu
     */
    public function list(Request $request)
    {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 20);

        $query = Db::table('caipiao_payset');

        // Lấy总数
        $count = $query->count();

        // 分页Tra cứu
        $list = $query->orderBy('listorder', 'asc')
            ->orderBy('id', 'asc')
            ->offset(($page - 1) * $limit)
            ->limit($limit)
            ->get();

        // 处理dữ liệu
        $data = [];
        foreach ($list as $item) {
            $row = (array)$item;
            // 判断是否线上Thanh toán
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
     * ThêmNạp tiềnphương thức页面
     */
    public function add(Request $request)
    {
        return view('payset/add');
    }

    /**
     * Lưu新Nạp tiềnphương thức
     */
    public function save(Request $request)
    {
        $data = $request->post();

        // 验证必填字段
        if (empty($data['paytype']) || empty($data['paytypetitle'])) {
            return json(['code' => 1, 'msg' => '标识和Thanh toán名称không được để trống']);
        }

        // 检查标识是否đã tồn tại
        $exists = Db::table('caipiao_payset')
            ->where('paytype', $data['paytype'])
            ->exists();

        if ($exists) {
            return json(['code' => 1, 'msg' => '标识đã tồn tại']);
        }

        // 处理configs字段 - 支持对象hoặcJSON字符串
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
            return json(['code' => 0, 'msg' => 'ThêmThành công']);
        } else {
            return json(['code' => 1, 'msg' => 'ThêmThất bại']);
        }
    }

    /**
     * 编辑Nạp tiềnphương thức页面
     */
    public function edit(Request $request)
    {
        $id = $request->get('id');
        
        if (!$id) {
            return json(['code' => 1, 'msg' => 'Tham số không hợp lệ']);
        }

        $info = Db::table('caipiao_payset')->where('id', $id)->first();
        
        if (!$info) {
            return json(['code' => 1, 'msg' => 'Nạp tiềnphương thức không tồn tại']);
        }

        return view('payset/edit', ['info' => $info]);
    }

    /**
     * 更新Nạp tiềnphương thức信息
     */
    public function update(Request $request)
    {
        $id = $request->post('id');
        $data = $request->post();

        if (!$id) {
            return json(['code' => 1, 'msg' => 'Tham số không hợp lệ']);
        }

        // 验证必填字段
        if (empty($data['paytype']) || empty($data['paytypetitle'])) {
            return json(['code' => 1, 'msg' => '标识和Thanh toán名称không được để trống']);
        }

        // 检查标识是否与其他lịch sử重复
        $exists = Db::table('caipiao_payset')
            ->where('paytype', $data['paytype'])
            ->where('id', '!=', $id)
            ->exists();

        if ($exists) {
            return json(['code' => 1, 'msg' => '标识đã tồn tại']);
        }

        // 处理configs字段 - 支持对象hoặcJSON字符串
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
            return json(['code' => 0, 'msg' => '更新Thành công']);
        } else {
            return json(['code' => 1, 'msg' => '更新Thất bại']);
        }
    }

    /**
     * 删除存款方式
     */
    public function delete(Request $request)
    {
        $id = $request->post('id');
        
        if (!$id) {
            return json(['code' => 1, 'msg' => 'Tham số không hợp lệ']);
        }

        $result = Db::table('caipiao_payset')->where('id', $id)->delete();

        if ($result) {
            return json(['code' => 0, 'msg' => 'XóaThành công']);
        } else {
            return json(['code' => 1, 'msg' => 'XóaThất bại']);
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
            return json(['code' => 1, 'msg' => 'Tham số không hợp lệ']);
        }

        $result = Db::table('caipiao_payset')
            ->where('id', $id)
            ->update(['state' => $state ? 1 : 0]);

        if ($result !== false) {
            return json(['code' => 0, 'msg' => 'Thao tác thành công']);
        } else {
            return json(['code' => 1, 'msg' => 'Thao tác thất bại']);
        }
    }
}

