<?php

namespace plugin\admin\app\controller;

use support\Request;
use support\Db;

class BankController extends Base
{
    /**
     * 银行信息列表页面
     */
    public function index(Request $request)
    {
        \support\Log::info('BankController::index 被访问');
        return view('bank/index');
    }
    
    /**
     * Lấy银行信息列表dữ liệu
     */
    public function list(Request $request)
    {
        try {
            $username = $request->get('username', '');
            $accountname = $request->get('accountname', '');
            $state = $request->get('state', '');
            $page = $request->get('page', 1);
            $limit = $request->get('limit', 20);
            
            $query = Db::table('caipiao_banklist');
        
        if ($username) {
            $query->where('username', $username);
        }
        
        if ($accountname) {
            $query->where('accountname', $accountname);
        }
        
        if ($state !== '') {
            $query->where('state', $state);
        }
        
        // Lấy总数
        $count = $query->count();
        
        // 分页
        $offset = ($page - 1) * $limit;
        $list = $query->orderBy('id', 'desc')
                     ->offset($offset)
                     ->limit($limit)
                     ->get();
        
        // 处理dữ liệu
        $result = [];
        foreach ($list as $item) {
            $row = (array)$item;
            
            // 状态文本
            $stateText = ['审核中', '已审', '驳回'];
            $row['state_text'] = $stateText[$row['state']] ?? '未知';
            
            $result[] = $row;
        }
        
        return json([
            'code' => 0,
            'msg' => 'success',
            'data' => $result,
            'count' => $count
        ]);
        } catch (\Exception $e) {
            \support\Log::error('BankController::list Lỗi: ' . $e->getMessage());
            return json(['code' => 1, 'msg' => 'Lấy列表Thất bại: ' . $e->getMessage()]);
        }
    }
    
    /**
     * 编辑银行信息
     */
    public function edit(Request $request)
    {
        $id = $request->get('id');
        
        if (!$id) {
            return '<script>alert("Tham số không hợp lệ");history.back();</script>';
        }
        
        $info = Db::table('caipiao_banklist')->where('id', $id)->first();
        
        if (!$info) {
            return '<script>alert("银行信息không tồn tại");history.back();</script>';
        }
        
        $info = (array)$info;
        
        // 解析银行地址
        if (isset($info['bankaddress'])) {
            $_bankaddress = explode('-', $info['bankaddress']);
            $info['sheng'] = $_bankaddress[0] ?? '';
            $info['city'] = $_bankaddress[1] ?? '';
        }
        
        // POST GửiLưu
        if ($request->method() === 'POST') {
            $data = [];
            $data['bankname'] = $request->post('bankname');
            $data['bankbranch'] = $request->post('bankbranch');
            $data['banknumber'] = $request->post('banknumber');
            $data['isdefault'] = $request->post('isdefault', 0);
            $data['state'] = $request->post('state');
            $sheng = $request->post('sheng');
            $city = $request->post('city');
            
            if (!$data['bankname'] || !$data['bankbranch'] || !$data['banknumber'] || !$sheng || !$city) {
                return json(['code' => 1, 'msg' => '银行信息请填写完整']);
            }
            
            // 如果是首次审核通过，且配置了绑卡奖励
            $bindcardamount = abs(floatval($this->getConfig('bindcardamount')));
            if ($data['state'] == 1 && $bindcardamount > 0) {
                $cardcount = Db::table('caipiao_banklist')
                    ->where('uid', $info['uid'])
                    ->where('state', 1)
                    ->count();
                    
                // 首次绑卡奖励
                if (!$cardcount) {
                    $balance = $bindcardamount;
                    $member = Db::table('caipiao_member')->where('id', $info['uid'])->first();
                    $amountbefor = $member->balance;
                    
                    // 增加Số dư
                    Db::table('caipiao_member')
                        ->where('id', $info['uid'])
                        ->increment('balance', $balance);
                    
                    // lịch sử账变
                    $fuddetaildata = [
                        'trano' => $this->generateTrano(4),
                        'uid' => $info['uid'],
                        'username' => $info['username'],
                        'type' => 'activity_bindcard',
                        'typename' => 'Liên kết银行赠送Hoạt động',
                        'amount' => abs($balance),
                        'amountbefor' => $amountbefor,
                        'amountafter' => $amountbefor + abs($balance),
                        'remark' => 'Liên kết银行赠送',
                        'oddtime' => time()
                    ];
                    Db::table('caipiao_fuddetail')->insert($fuddetaildata);
                }
            }
            
            $data['bankaddress'] = $sheng . '-' . $city;
            
            $result = Db::table('caipiao_banklist')
                ->where('id', $id)
                ->update($data);
            
            if ($result !== false) {
                return json(['code' => 0, 'msg' => '银行信息SửaThành công']);
            } else {
                return json(['code' => 1, 'msg' => '银行信息SửaThất bại']);
            }
        }
        
        return view('bank/edit', ['info' => $info]);
    }
    
    /**
     * Xóa银行信息
     */
    public function delete(Request $request)
    {
        $id = $request->post('id');
        
        if (!$id) {
            return json(['code' => 1, 'msg' => 'Tham số không hợp lệ']);
        }
        
        $result = Db::table('caipiao_banklist')->where('id', $id)->delete();
        
        if ($result) {
            return json(['code' => 0, 'msg' => 'XóaThành công']);
        } else {
            return json(['code' => 1, 'msg' => 'XóaThất bại']);
        }
    }
    
    /**
     * Lấy配置值
     */
    private function getConfig($name)
    {
        $config = Db::table('caipiao_setting')->where('name', $name)->value('value');
        return $config ?? '';
    }
    
    /**
     * 生成交易单号
     */
    private function generateTrano($type)
    {
        return date('YmdHis') . $type . rand(1000, 9999);
    }
}

