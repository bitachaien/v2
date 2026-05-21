<?php

namespace plugin\admin\app\controller;

use support\Request;
use support\Db;

/**
 * 机器人管理 API 控制器 (Art Design Pro 前端专用)
 */
class RobotApiController extends Base
{
    /**
     * 不需要鉴权的方法
     */
    protected $noNeedAuth = [];

    /**
     * ============================================
     * 发单设置管理
     * ============================================
     */

    /**
     * 获取发单设置列表
     * GET /app/admin/api/robot/send-order/list
     */
    public function sendOrderList(Request $request)
    {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 20);

        // Lấy所有彩种
        $query = Db::table('caipiao_caipiao')
            ->where('isopen', 1)
            ->where('iswh', 0)
            ->orderBy('allsort', 'asc')
            ->orderBy('id', 'desc');

        $total = $query->count();

        $list = $query->offset(($page - 1) * $limit)
            ->limit($limit)
            ->get();

        // Lấy玩法配置
        $wanfaObj = new \Lib\wanfa_fadan();
        $wanfaConfig = [
            'ssc' => $wanfaObj->ssc(),
            'k3' => $wanfaObj->k3(),
            'keno' => $wanfaObj->keno(),
            'x5' => $wanfaObj->x5(),
            'lhc' => $wanfaObj->lhc(),
            'dpc' => $wanfaObj->dpc(),
            'xy28' => $wanfaObj->xy28(),
            'pk10' => $wanfaObj->pk10(),
        ];

        $result = [];
        foreach ($list as $item) {
            $item = (array)$item;
            
            // 处理玩法类型 - 转为数组
            $wanfaStr = $item['hemai_wanfa_type'] ?? '';
            $wanfaTypes = [];
            if (!empty($wanfaStr)) {
                $wanfaTypes = array_filter(explode(',', $wanfaStr));
            }

            $result[] = [
                'id' => $item['id'],
                'title' => $item['title'],
                'name' => $item['name'],
                'typeid' => $item['typeid'],
                'hemai_status' => (int)($item['hemai_status'] ?? 0),
                'hemai_danqi_sum' => (int)($item['hemai_danqi_sum'] ?? 0),
                'hemai_baomi_type' => (int)($item['hemai_baomi_type'] ?? 0),
                'hemai_touzhu_beishu' => (int)($item['hemai_touzhu_beishu'] ?? 1),
                'hemai_touzhe_bili' => (int)($item['hemai_touzhe_bili'] ?? 0),
                'hemai_baodi_status' => (int)($item['hemai_baodi_status'] ?? 0),
                'hemai_wanfa_type' => $wanfaTypes,
            ];
        }

        return $this->json(0, 'success', [
            'total' => $total,
            'list' => $result,
            'wanfa' => $wanfaConfig,
        ]);
    }

    /**
     * 更新发单配置
     * POST /app/admin/api/robot/send-order/update
     */
    public function sendOrderUpdate(Request $request)
    {
        $name = $request->post('name');
        
        if (empty($name)) {
            return $this->json(1, '彩种标识không được để trống');
        }

        // 检查彩种是否存在
        $lottery = Db::table('caipiao_caipiao')->where('name', $name)->first();
        if (!$lottery) {
            return $this->json(1, '彩种không tồn tại');
        }

        // Lấy所有需要更新的字段
        $allowFields = [
            'hemai_status',
            'hemai_danqi_sum',
            'hemai_baomi_type',
            'hemai_touzhu_beishu',
            'hemai_touzhe_bili',
            'hemai_baodi_status',
            'hemai_wanfa_type',
        ];

        $updateData = [];
        foreach ($allowFields as $field) {
            $value = $request->post($field);
            if ($value !== null) {
                // 玩法类型特殊处理 - 数组转逗号分隔字符串
                if ($field === 'hemai_wanfa_type' && is_array($value)) {
                    $value = implode(',', $value);
                    // 确保末尾有逗号（兼容原有格式）
                    if (!empty($value) && substr($value, -1) !== ',') {
                        $value .= ',';
                    }
                }
                $updateData[$field] = $value;
            }
        }

        if (empty($updateData)) {
            return $this->json(1, '没有需要更新的字段');
        }

        Db::table('caipiao_caipiao')
            ->where('name', $name)
            ->update($updateData);

        return $this->json(0, 'Cài đặtThành công');
    }

    /**
     * Lấy玩法选项
     * GET /app/admin/api/robot/send-order/wanfa-options
     */
    public function wanfaOptions(Request $request)
    {
        $typeid = $request->get('typeid', '');

        $wanfaObj = new \Lib\wanfa_fadan();
        
        $wanfaConfig = [
            'ssc' => $wanfaObj->ssc(),
            'k3' => $wanfaObj->k3(),
            'keno' => $wanfaObj->keno(),
            'x5' => $wanfaObj->x5(),
            'lhc' => $wanfaObj->lhc(),
            'dpc' => $wanfaObj->dpc(),
            'xy28' => $wanfaObj->xy28(),
            'pk10' => $wanfaObj->pk10(),
        ];

        if (!empty($typeid) && isset($wanfaConfig[$typeid])) {
            return $this->json(0, 'ok', $wanfaConfig[$typeid]);
        }

        return $this->json(0, 'ok', $wanfaConfig);
    }

    /**
     * Lấy保密类型选项
     * GET /app/admin/api/robot/send-order/baomi-options
     */
    public function baomiOptions(Request $request)
    {
        $options = [
            ['value' => 0, 'label' => '完全公开'],
            ['value' => 1, 'label' => 'Mở thưởng后公开'],
            ['value' => 2, 'label' => '仅跟单人可看'],
            ['value' => 3, 'label' => '完全保密'],
        ];

        return $this->json(0, 'ok', $options);
    }

    /**
     * ============================================
     * 合买管理
     * ============================================
     */

    /**
     * Lấy合买列表
     * GET /app/admin/api/robot/hemai/list
     */
    public function hemaiList(Request $request)
    {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 20);
        
        $cpname = $request->get('cpname', '');
        $username = $request->get('username', '');
        $trano = $request->get('trano', '');
        $qihao = $request->get('qihao', '');
        $status = $request->get('status', 999);
        $isnb = $request->get('isnb', 999);
        $sDate = $request->get('sDate', '');
        $eDate = $request->get('eDate', '');
        $listorder = $request->get('listorder', 0);

        $query = Db::table('caipiao_touzhu as t')
            ->leftJoin('caipiao_member as m', 't.uid', '=', 'm.id')
            ->where('t.ishemai', 1);

        // 筛选条件
        if (!empty($cpname)) {
            $query->where('t.cpname', $cpname);
        }

        if (!empty($username)) {
            $query->where('t.username', 'like', "%$username%");
        }

        if (!empty($trano)) {
            $query->where('t.trano', 'like', "%$trano%");
        }

        if (!empty($qihao)) {
            $query->where('t.expect', $qihao);
        }

        if ($status != 999) {
            $query->where('t.isdraw', $status);
        }

        if ($isnb != 999) {
            $query->where('m.isnb', $isnb);
        }

        if (!empty($sDate)) {
            // 支持 YYYYMMDD 格式
            $sTime = strtotime($sDate);
            $query->where('t.oddtime', '>=', $sTime);
        }

        if (!empty($eDate)) {
            $eTime = strtotime($eDate . ' 23:59:59');
            $query->where('t.oddtime', '<=', $eTime);
        }

        // 排序
        switch ((int)$listorder) {
            case 1:
                $query->orderBy('t.oddtime', 'desc');
                break;
            case 2:
                $query->orderBy('t.oddtime', 'asc');
                break;
            case 3:
                $query->orderBy('t.amount', 'desc');
                break;
            case 4:
                $query->orderBy('t.amount', 'asc');
                break;
            default:
                $query->orderBy('t.id', 'desc');
        }

        $total = $query->count();

        $list = $query->select('t.*', 'm.isnb')
            ->offset(($page - 1) * $limit)
            ->limit($limit)
            ->get();

        $result = [];
        foreach ($list as $item) {
            $item = (array)$item;

            // 状态值和文本
            $isdraw = (int)$item['isdraw'];
            $statusText = match($isdraw) {
                1 => '已Trúng thưởng',
                0 => '未Mở thưởng',
                -1 => '未Trúng thưởng',
                -2 => '撤单',
                default => '未知'
            };

            // 类型显示
            $typeText = $item['ishemai'] == 1 ? '合买' : '代购';

            // 号码显示
            $tzcode = $item['tzcode'] ?? '';
            $tzcodeDisplay = strlen($tzcode) <= 20 ? $tzcode : '查看';

            $result[] = [
                'id' => $item['id'],
                'trano' => $item['trano'],
                'username' => $item['username'],
                'cpname' => $item['cpname'] ?? '',
                'cptitle' => $item['cptitle'],
                'expect' => $item['expect'],
                'playtitle' => $item['playtitle'],
                'itemcount' => $item['itemcount'],
                'mode' => $item['mode'],
                'amount' => $item['amount'],
                'amountafter' => $item['amountafter'],
                'okamount' => $item['okamount'],
                'okcount' => $item['okcount'],
                'beishu' => $item['beishu'],
                'yjf' => $item['yjf'],
                'tzcode' => $tzcodeDisplay,
                'tzcode_full' => $tzcode,
                'opencode' => $item['opencode'] ?? '',
                'type' => $typeText,
                'status' => $statusText,           // 直接返回中文状态
                'status_code' => $isdraw,          // 状态码备用
                'oddtime' => date('Y-m-d H:i:s', $item['oddtime']),  // 直接返回格式化Thời gian
                'oddtime_ts' => $item['oddtime'],  // Thời gian戳备用
                'isnb' => (int)($item['isnb'] ?? 0),
                'isnb_text' => ($item['isnb'] ?? 0) == 1 ? '是' : '否',
            ];
        }

        return $this->json(0, 'success', [
            'total' => $total,
            'list' => $result,
        ]);
    }

    /**
     * Lấy合买Chi tiết
     * GET /app/admin/api/robot/hemai/detail
     */
    public function hemaiDetail(Request $request)
    {
        $id = $request->get('id');

        if (empty($id)) {
            return $this->json(1, 'Tham số không hợp lệ');
        }

        // Tra cứu主订单信息
        $mainOrder = Db::table('caipiao_touzhu')->where('id', $id)->first();

        if (!$mainOrder) {
            return $this->json(1, '订单không tồn tại');
        }

        $mainOrder = (array)$mainOrder;

        // 状态文本
        $isdraw = (int)$mainOrder['isdraw'];
        $statusText = match($isdraw) {
            1 => '已Trúng thưởng',
            0 => '未Mở thưởng',
            -1 => '未Trúng thưởng',
            -2 => '撤单',
            default => '未知'
        };

        // Tra cứu合买参与lịch sử
        $participants = Db::table('caipiao_touzhuhm')
            ->where('touzhuid', $id)
            ->get();

        $participantList = [];
        foreach ($participants as $p) {
            $p = (array)$p;
            $participantList[] = [
                'id' => $p['id'],
                'username' => $p['username'] ?? '',
                'amount' => $p['amount'] ?? 0,
                'bili' => $p['bili'] ?? 0,
                'win' => $p['win'] ?? 0,
                'addtime' => isset($p['addtime']) ? date('Y-m-d H:i:s', $p['addtime']) : '',
            ];
        }

        return $this->json(0, 'success', [
            'main_order' => [
                'id' => $mainOrder['id'],
                'trano' => $mainOrder['trano'],
                'username' => $mainOrder['username'],
                'cptitle' => $mainOrder['cptitle'],
                'cpname' => $mainOrder['cpname'] ?? '',
                'expect' => $mainOrder['expect'],
                'playtitle' => $mainOrder['playtitle'],
                'tzcode' => $mainOrder['tzcode'],
                'amount' => $mainOrder['amount'],
                'okamount' => $mainOrder['okamount'],
                'opencode' => $mainOrder['opencode'] ?? '',
                'status' => $isdraw,
                'status_text' => $statusText,
                'oddtime' => date('Y-m-d H:i:s', $mainOrder['oddtime']),
            ],
            'participants' => $participantList,
        ]);
    }

    /**
     * Lấy彩种选项（用于下拉框）
     * GET /app/admin/api/robot/hemai/lottery-options
     */
    public function lotteryOptions(Request $request)
    {
        $list = Db::table('caipiao_caipiao')
            ->select('name', 'title')
            ->orderBy('typeid', 'asc')
            ->orderBy('id', 'desc')
            ->get();

        $options = [];
        foreach ($list as $item) {
            $options[] = [
                'value' => $item->name,
                'label' => $item->title,
            ];
        }

        return $this->json(0, 'ok', $options);
    }

    /**
     * Lấy合买状态选项
     * GET /app/admin/api/robot/hemai/status-options
     */
    public function hemaiStatusOptions(Request $request)
    {
        $options = [
            ['value' => 999, 'label' => '全部'],
            ['value' => 0, 'label' => '未Mở thưởng'],
            ['value' => 1, 'label' => '已Trúng thưởng'],
            ['value' => -1, 'label' => '未Trúng thưởng'],
            ['value' => -2, 'label' => '撤单'],
        ];

        return $this->json(0, 'ok', $options);
    }
}
