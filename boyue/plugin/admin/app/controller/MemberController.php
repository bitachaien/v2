<?php
namespace plugin\admin\app\controller;

use support\Request;
use support\Db;

class MemberController extends Base
{
    public function index(Request $request)
    {
        return view('member/index');
    }

    public function list(Request $request)
    {
        try {
            $groupid = $request->get('groupid', 0);
            $proxy = $request->get('proxy', 999);
            $isnb = $request->get('isnb', 999);
            $username = $request->get('username', '');
            $nickname = $request->get('nickname', '');
            $userbankname = $request->get('userbankname', '');
            $loginip = $request->get('loginip', '');
            $parentid = $request->get('parentid', 0);
            $isonline = $request->get('isonline', 0);
            $sDate = $request->get('sDate', '');
            $eDate = $request->get('eDate', '');
            $sAmount = $request->get('sAmount', '');
            $eAmount = $request->get('eAmount', '');
            $ordertype = $request->get('ordertype', 0);
            $page = $request->get('page', 1);
            $limit = $request->get('limit', 20);
            
            $query = Db::table('caipiao_member')->where('is_rebet', 0);
            
            $tonline = 30;
            $_t = time();
            if ($isonline) {
                $query->where('onlinetime', '>=', $_t - $tonline);
            }
            
            if ($groupid) {
                $query->where('groupid', $groupid);
            }
            
            if ($proxy != 999) {
                $query->where('proxy', $proxy);
            }
            
            if ($isnb != 999) {
                $query->where('isnb', $isnb);
            }
            
            if (!empty($username)) {
                $query->where('username', 'like', '%' . $username . '%');
            }
            
            if (!empty($nickname)) {
                $query->where('nickname', 'like', '%' . $nickname . '%');
            }
            
            if (!empty($userbankname)) {
                $query->where('userbankname', 'like', '%' . $userbankname . '%');
            }
            
            if (!empty($loginip)) {
                $query->where('loginip', $loginip);
            }
            
            if ($parentid) {
                $query->where('parentid', $parentid);
            }
            
            if (!empty($sDate)) {
                $query->where('regtime', '>=', strtotime($sDate));
            }
            if (!empty($eDate)) {
                $query->where('regtime', '<=', strtotime($eDate) + 86400);
            }
            
            if (!empty($sAmount)) {
                $query->where('balance', '>=', $sAmount);
            }
            if (!empty($eAmount)) {
                $query->where('balance', '<=', $eAmount);
            }
            
            switch ($ordertype) {
                case '1': $query->orderBy('regtime', 'asc'); break;
                case '2': $query->orderBy('fandian', 'desc'); break;
                case '3': $query->orderBy('fandian', 'asc'); break;
                case '4': $query->orderBy('balance', 'desc'); break;
                case '5': $query->orderBy('balance', 'asc'); break;
                case '6': $query->orderBy('point', 'desc'); break;
                case '7': $query->orderBy('point', 'asc'); break;
                case '8': $query->orderBy('xima', 'desc'); break;
                case '9': $query->orderBy('xima', 'asc'); break;
                case '16': $query->orderBy('logintime', 'desc'); break;
                case '17': $query->orderBy('logintime', 'asc'); break;
                case '18': $query->orderBy('onlinetime', 'desc'); break;
                case '19': $query->orderBy('onlinetime', 'asc'); break;
                default: $query->orderBy('id', 'desc'); break;
            }
            
            $count = $query->count();
            $offset = ($page - 1) * $limit;
            $list = $query->offset($offset)->limit($limit)->get();
            
            $groups = Db::table('caipiao_membergroup')->get();
            $groupMap = [];
            foreach ($groups as $group) {
                $groupMap[$group->groupid] = $group->groupname;
            }
            
            $parentIds = [];
            foreach ($list as $item) {
                if ($item->parentid > 0) {
                    $parentIds[] = $item->parentid;
                }
            }
            
            $parentMap = [];
            if (!empty($parentIds)) {
                $parents = Db::table('caipiao_member')
                    ->whereIn('id', array_unique($parentIds))
                    ->select('id', 'username')
                    ->get();
                foreach ($parents as $parent) {
                    $parentMap[$parent->id] = $parent->username;
                }
            }
            
            $result = [];
            foreach ($list as $item) {
                $row = (array)$item;
                $row['groupname'] = $groupMap[$row['groupid']] ?? '';
                $row['parent_username'] = '';
                if (isset($row['parentid']) && $row['parentid'] > 0) {
                    $row['parent_username'] = $parentMap[$row['parentid']] ?? '';
                }
                $row['proxy_text'] = $row['proxy'] == 1 ? 'Đại lý' : 'Thành viên';
                if (isset($row['jinjijilu']) && $row['jinjijilu'] > 0) {
                    $jinji_group = $groupMap[$row['jinjijilu']] ?? 'VIP' . $row['jinjijilu'];
                    $row['jinjijilu'] = $jinji_group;
                } else {
                    $row['jinjijilu'] = '';
                }
                $row['isonline_text'] = ($row['onlinetime'] >= $_t - $tonline) ? '在线' : '离线';
                $row['islock_text'] = $row['islock'] == 1 ? '锁定' : '正常';
                if (isset($row['logintime']) && $row['logintime']) {
                    $row['logintime_text'] = date('m-d H:i', $row['logintime']);
                } else {
                    $row['logintime_text'] = '';
                }
                if (isset($row['regtime']) && $row['regtime']) {
                    $row['regtime_text'] = date('Y-m-d H:i', $row['regtime']);
                } else {
                    $row['regtime_text'] = '';
                }
                $result[] = $row;
            }
            
            return $this->json(0, 'success', ['list' => $result, 'total' => $count]);
        } catch (\Exception $e) {
            return $this->json(1, 'Tra cứuThất bại: ' . $e->getMessage());
        }
    }

    public function info(Request $request)
    {
        $id = $request->get('id');
        if (!$id) {
            return '<script>alert("Tham số không hợp lệ");history.back();</script>';
        }
        $info = Db::table('caipiao_member')->where('id', $id)->first();
        if (!$info) {
            return '<script>alert("Thành viênkhông tồn tại");history.back();</script>';
        }
        return view('member/info', ['info' => (array)$info]);
    }

    public function balanceLog(Request $request)
    {
        $uid = $request->get('uid', '');
        $info = null;
        if ($uid) {
            $info = Db::table('caipiao_member')->where('id', $uid)->first();
            if (!$info) {
                return '<script>alert("Thành viênkhông tồn tại");history.back();</script>';
            }
            $info = (array)$info;
        }
        return view('member/balance-log', ['info' => $info]);
    }

    public function balanceLogList(Request $request)
    {
        $uid = $request->get('uid');
        $type = $request->get('type', '');
        $trano = $request->get('trano', '');
        $username = $request->get('username', '');
        $sDate = $request->get('sDate', '');
        $eDate = $request->get('eDate', '');
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 20);
        
        $query = Db::table('caipiao_fuddetail');
        
        if ($uid) { $query->where('uid', $uid); }
        if ($type) { $query->where('type', $type); }
        if ($trano) { $query->where('trano', $trano); }
        if ($username) { $query->where('username', $username); }
        if ($sDate) { $query->where('oddtime', '>=', strtotime($sDate)); }
        if ($eDate) { $query->where('oddtime', '<=', strtotime($eDate) + 86400); }
        
        $query->orderBy('id', 'desc');
        $count = $query->count();
        $offset = ($page - 1) * $limit;
        $list = $query->offset($offset)->limit($limit)->get();
        
        $result = [];
        foreach ($list as $item) {
            $row = (array)$item;
            if (isset($row['oddtime']) && is_numeric($row['oddtime'])) {
                $row['oddtime_text'] = date('Y-m-d H:i:s', $row['oddtime']);
            } else {
                $row['oddtime_text'] = '';
            }
            $result[] = $row;
        }
        
        return json(['code' => 0, 'msg' => 'success', 'data' => $result, 'count' => $count]);
    }

    public function children(Request $request)
    {
        $parentid = $request->get('parentid');
        if (!$parentid) {
            return '<script>alert("Tham số không hợp lệ");history.back();</script>';
        }
        $parent = Db::table('caipiao_member')->where('id', $parentid)->first();
        if (!$parent) {
            return '<script>alert("上级Thành viênkhông tồn tại");history.back();</script>';
        }
        return view('member/children', ['parent' => (array)$parent]);
    }
    
    public function edit(Request $request)
    {
        $id = $request->get('id');
        if (!$id) {
            return '<script>alert("Tham số không hợp lệ");history.back();</script>';
        }
        $info = Db::table('caipiao_member')->where('id', $id)->first();
        if (!$info) {
            return '<script>alert("Thành viênkhông tồn tại");history.back();</script>';
        }
        $grouplist = Db::table('caipiao_membergroup')->get();
        
        if ($request->method() === 'POST') {
            $data = $request->post();
            if (empty($data['groupid']) || $data['groupid'] == 0) {
                return json(['code' => 1, 'msg' => 'Vui lòng chọnThành viên组']);
            }
            $fandian = [];
            if (isset($data['fandian']) && is_array($data['fandian'])) {
                $fandian = $data['fandian'];
            }
            $updateData = [
                'groupid' => $data['groupid'],
                'proxy' => $data['proxy'] ?? 0,
                'fandian' => json_encode($fandian, JSON_UNESCAPED_UNICODE)
            ];
            $result = Db::table('caipiao_member')->where('id', $id)->update($updateData);
            if ($result !== false) {
                return json(['code' => 0, 'msg' => 'SửaThành công']);
            } else {
                return json(['code' => 1, 'msg' => 'SửaThất bại']);
            }
        }
        
        return view('member/edit', ['info' => (array)$info, 'grouplist' => $grouplist]);
    }
    
    public function editSave(Request $request)
    {
        $id = $request->post('id');
        if (!$id) {
            return $this->json(1, 'Tham số không hợp lệ');
        }
        $info = Db::table('caipiao_member')->where('id', $id)->first();
        if (!$info) {
            return $this->json(1, 'Thành viênkhông tồn tại');
        }
        
        $updateData = [];
        $groupid = $request->post('groupid');
        if ($groupid !== null) { $updateData['groupid'] = $groupid; }
        $proxy = $request->post('proxy');
        if ($proxy !== null) { $updateData['proxy'] = $proxy; }
        $nickname = $request->post('nickname');
        if ($nickname !== null) { $updateData['nickname'] = $nickname; }
        $userbankname = $request->post('userbankname');
        if ($userbankname !== null) { $updateData['userbankname'] = $userbankname; }
        $phone = $request->post('phone');
        if ($phone !== null) { $updateData['phone'] = $phone; }
        $password = $request->post('password');
        if (!empty($password)) { $updateData['password'] = password_hash($password, PASSWORD_BCRYPT); }
        $fandian = $request->post('fandian');
        if ($fandian !== null) {
            if (is_array($fandian)) {
                $updateData['fandian'] = json_encode($fandian, JSON_UNESCAPED_UNICODE);
            } else {
                $updateData['fandian'] = $fandian;
            }
        }
        
        if (empty($updateData)) {
            return $this->json(1, '没有需要更新的dữ liệu');
        }
        
        $result = Db::table('caipiao_member')->where('id', $id)->update($updateData);
        if ($result !== false) {
            return $this->json(0, 'SửaThành công');
        } else {
            return $this->json(1, 'SửaThất bại');
        }
    }
    
    public function delete(Request $request)
    {
        $id = $request->post('id');
        if (!$id) {
            return json(['code' => 1, 'msg' => 'Tham số không hợp lệ']);
        }
        $info = Db::table('caipiao_member')->where('id', $id)->first();
        if (!$info) {
            return json(['code' => 1, 'msg' => 'Thành viênkhông tồn tại']);
        }
        $result = Db::table('caipiao_member')->where('id', $id)->delete();
        if ($result) {
            return json(['code' => 0, 'msg' => 'XóaThành công']);
        } else {
            return json(['code' => 1, 'msg' => 'XóaThất bại']);
        }
    }
    
    public function kick(Request $request)
    {
        $id = $request->post('id');
        if (!$id) {
            return json(['code' => 1, 'msg' => 'Tham số không hợp lệ']);
        }
        Db::table('caipiao_membersession')->where('userid', $id)->delete();
        return json(['code' => 0, 'msg' => '已踢出']);
    }
    
    public function editBalance(Request $request)
    {
        $id = $request->get('id');
        if (!$id) {
            return '<script>alert("Tham số không hợp lệ");history.back();</script>';
        }
        $info = Db::table('caipiao_member')->where('id', $id)->first();
        if (!$info) {
            return '<script>alert("Thành viênkhông tồn tại");history.back();</script>';
        }
        
        if ($request->method() === 'POST') {
            $balance = floatval($request->post('balance', 0));
            $type = $request->post('type');
            $remark = $request->post('remark', '');
            
            if (!in_array($type, ['1', '-1'])) {
                return json(['code' => 1, 'msg' => 'Số tiền类型Lỗi']);
            }
            if ($balance <= 0) {
                return json(['code' => 1, 'msg' => 'Số tiền应大于0']);
            }
            
            $trano = 'CZ' . date('YmdHis') . rand(1000, 9999);
            $oldbalance = $info->balance;
            
            if ($type == '1') {
                $newbalance = $oldbalance + abs($balance);
                Db::table('caipiao_member')->where('id', $id)->update([
                    'balance' => Db::raw('balance + ' . abs($balance))
                ]);
                Db::table('caipiao_fuddetail')->insert([
                    'trano' => $trano, 'uid' => $info->id, 'username' => $info->username,
                    'type' => 'adminadd', 'typename' => '管理员加', 'amount' => abs($balance),
                    'amountbefor' => $oldbalance, 'amountafter' => $newbalance,
                    'remark' => $remark ?: '管理员手动增加', 'oddtime' => time()
                ]);
            } elseif ($type == '-1') {
                if ($oldbalance < abs($balance)) {
                    return json(['code' => 1, 'msg' => 'Số dư không đủ']);
                }
                $newbalance = $oldbalance - abs($balance);
                Db::table('caipiao_member')->where('id', $id)->update([
                    'balance' => Db::raw('balance - ' . abs($balance))
                ]);
                Db::table('caipiao_fuddetail')->insert([
                    'trano' => $trano, 'uid' => $info->id, 'username' => $info->username,
                    'type' => 'adminjian', 'typename' => '管理员减', 'amount' => -abs($balance),
                    'amountbefor' => $oldbalance, 'amountafter' => $newbalance,
                    'remark' => $remark ?: '管理员手动减少', 'oddtime' => time()
                ]);
            }
            return json(['code' => 0, 'msg' => 'Thao tác thành công']);
        }
        
        return view('member/edit-balance', ['info' => (array)$info]);
    }
    
    public function editXima(Request $request)
    {
        $id = $request->get('id');
        if (!$id) {
            return '<script>alert("Tham số không hợp lệ");history.back();</script>';
        }
        $info = Db::table('caipiao_member')->where('id', $id)->first();
        if (!$info) {
            return '<script>alert("Thành viênkhông tồn tại");history.back();</script>';
        }
        
        if ($request->method() === 'POST') {
            $xima = floatval($request->post('xima', 0));
            $type = $request->post('type');
            $remark = $request->post('remark', '');
            
            if (!in_array($type, ['1', '-1'])) {
                return json(['code' => 1, 'msg' => '类型Lỗi']);
            }
            if ($xima <= 0) {
                return json(['code' => 1, 'msg' => '洗码Số tiền应大于0']);
            }
            
            $trano = 'XM' . date('YmdHis') . rand(1000, 9999);
            $oldxima = $info->xima;
            
            if ($type == '1') {
                $newxima = $oldxima + abs($xima);
                Db::table('caipiao_member')->where('id', $id)->update([
                    'xima' => Db::raw('xima + ' . abs($xima))
                ]);
                Db::table('caipiao_fuddetail')->insert([
                    'trano' => $trano, 'uid' => $info->id, 'username' => $info->username,
                    'type' => 'xima', 'typename' => '洗码', 'amount' => abs($xima),
                    'amountbefor' => $oldxima, 'amountafter' => $newxima,
                    'remark' => $remark ?: '管理员增加洗码', 'oddtime' => time()
                ]);
            } elseif ($type == '-1') {
                if ($oldxima < abs($xima)) {
                    return json(['code' => 1, 'msg' => '洗码Số dư không đủ']);
                }
                $newxima = $oldxima - abs($xima);
                Db::table('caipiao_member')->where('id', $id)->update([
                    'xima' => Db::raw('xima - ' . abs($xima))
                ]);
                Db::table('caipiao_fuddetail')->insert([
                    'trano' => $trano, 'uid' => $info->id, 'username' => $info->username,
                    'type' => 'xima', 'typename' => '洗码', 'amount' => -abs($xima),
                    'amountbefor' => $oldxima, 'amountafter' => $newxima,
                    'remark' => $remark ?: '管理员减少洗码', 'oddtime' => time()
                ]);
            }
            return json(['code' => 0, 'msg' => 'Thao tác thành công']);
        }
        
        return view('member/edit-xima', ['info' => (array)$info]);
    }
    
    public function childList(Request $request)
    {
        $parentid = $request->get('parentid');
        $page = (int)$request->get('page', 1);
        $limit = (int)$request->get('limit', 20);
        
        if (!$parentid) {
            return $this->json(1, 'Tham số không hợp lệ');
        }
        
        $parent = Db::table('caipiao_member')->where('id', $parentid)->first();
        if (!$parent) {
            return $this->json(1, '上级Thành viênkhông tồn tại');
        }
        
        $query = Db::table('caipiao_member')->where('parentid', $parentid)->where('is_rebet', 0);
        $count = $query->count();
        $offset = ($page - 1) * $limit;
        $list = $query->orderBy('id', 'desc')->offset($offset)->limit($limit)->get();
        
        $groups = Db::table('caipiao_membergroup')->get();
        $groupMap = [];
        foreach ($groups as $group) {
            $groupMap[$group->groupid] = $group->groupname;
        }
        
        $result = [];
        foreach ($list as $item) {
            $row = (array)$item;
            $row['groupname'] = $groupMap[$row['groupid']] ?? '';
            $row['proxy_text'] = $row['proxy'] == 1 ? 'Đại lý' : 'Thành viên';
            $row['islock_text'] = $row['islock'] == 1 ? '锁定' : '正常';
            if (isset($row['regtime']) && $row['regtime']) {
                $row['regtime_text'] = date('Y-m-d H:i', $row['regtime']);
            }
            $result[] = $row;
        }
        
        return $this->json(0, 'success', [
            'list' => $result,
            'total' => $count,
            'parent' => ['id' => $parent->id, 'username' => $parent->username]
        ]);
    }
    
    public function lock(Request $request)
    {
        $id = $request->post('id');
        $islock = $request->post('islock');
        
        if (!$id) {
            return $this->json(1, 'Tham số không hợp lệ');
        }
        $info = Db::table('caipiao_member')->where('id', $id)->first();
        if (!$info) {
            return $this->json(1, 'Thành viênkhông tồn tại');
        }
        
        $result = Db::table('caipiao_member')->where('id', $id)->update(['islock' => $islock ? 1 : 0]);
        if ($result !== false) {
            return $this->json(0, $islock ? '已锁定' : '已解锁');
        } else {
            return $this->json(1, 'Thao tác thất bại');
        }
    }
    
    public function add(Request $request)
    {
        $username = $request->post('username');
        $password = $request->post('password');
        $groupid = $request->post('groupid', 1);
        $proxy = $request->post('proxy', 0);
        $parentid = $request->post('parentid', 0);
        
        if (empty($username)) { return $this->json(1, '请输入Tên người dùng'); }
        if (empty($password)) { return $this->json(1, '请输入Mật khẩu'); }
        if (strlen($username) < 4 || strlen($username) > 16) {
            return $this->json(1, 'Tên người dùng长度应为4-16位');
        }
        if (!preg_match('/^[a-zA-Z0-9]+$/', $username)) {
            return $this->json(1, 'Tên người dùngchỉ được chứa chữ cái và số');
        }
        
        $exists = Db::table('caipiao_member')->where('username', $username)->exists();
        if ($exists) {
            return $this->json(1, 'Tên người dùngđã tồn tại');
        }
        
        $data = [
            'username' => $username,
            'password' => password_hash($password, PASSWORD_BCRYPT),
            'groupid' => $groupid,
            'proxy' => $proxy,
            'parentid' => $parentid,
            'balance' => 0,
            'point' => 0,
            'xima' => 0,
            'regtime' => time(),
            'regip' => $request->getRealIp(),
            'islock' => 0,
            'is_rebet' => 0,
            'fandian' => '{}',
        ];
        
        $id = Db::table('caipiao_member')->insertGetId($data);
        if ($id) {
            return $this->json(0, 'ThêmThành công');
        } else {
            return $this->json(1, 'ThêmThất bại');
        }
    }
    
    public function editBalanceSave(Request $request)
    {
        $id = $request->post('id');
        $balance = floatval($request->post('balance', 0));
        $type = $request->post('type');
        $remark = $request->post('remark', '');
        
        if (!$id) { return $this->json(1, 'Tham số không hợp lệ'); }
        $info = Db::table('caipiao_member')->where('id', $id)->first();
        if (!$info) { return $this->json(1, 'Thành viênkhông tồn tại'); }
        if (!in_array($type, ['1', '-1', 1, -1])) { return $this->json(1, 'Số tiền类型Lỗi'); }
        if ($balance <= 0) { return $this->json(1, 'Số tiền应大于0'); }
        
        $trano = 'CZ' . date('YmdHis') . rand(1000, 9999);
        $oldbalance = $info->balance;
        
        if ($type == 1 || $type == '1') {
            $newbalance = $oldbalance + abs($balance);
            Db::table('caipiao_member')->where('id', $id)->update([
                'balance' => Db::raw('balance + ' . abs($balance))
            ]);
            Db::table('caipiao_fuddetail')->insert([
                'trano' => $trano, 'uid' => $info->id, 'username' => $info->username,
                'type' => 'adminadd', 'typename' => '管理员加', 'amount' => abs($balance),
                'amountbefor' => $oldbalance, 'amountafter' => $newbalance,
                'remark' => $remark ?: '管理员手动增加', 'oddtime' => time()
            ]);
        } else {
            if ($oldbalance < abs($balance)) { return $this->json(1, 'Số dư không đủ'); }
            $newbalance = $oldbalance - abs($balance);
            Db::table('caipiao_member')->where('id', $id)->update([
                'balance' => Db::raw('balance - ' . abs($balance))
            ]);
            Db::table('caipiao_fuddetail')->insert([
                'trano' => $trano, 'uid' => $info->id, 'username' => $info->username,
                'type' => 'adminsub', 'typename' => '管理员减', 'amount' => -abs($balance),
                'amountbefor' => $oldbalance, 'amountafter' => $newbalance,
                'remark' => $remark ?: '管理员手动减少', 'oddtime' => time()
            ]);
        }
        return $this->json(0, 'Thao tác thành công');
    }
    
    public function editXimaSave(Request $request)
    {
        $id = $request->post('id');
        $xima = floatval($request->post('xima', 0));
        $type = $request->post('type');
        $remark = $request->post('remark', '');
        
        if (!$id) { return $this->json(1, 'Tham số không hợp lệ'); }
        $info = Db::table('caipiao_member')->where('id', $id)->first();
        if (!$info) { return $this->json(1, 'Thành viênkhông tồn tại'); }
        if (!in_array($type, ['1', '-1', 1, -1])) { return $this->json(1, '洗码类型Lỗi'); }
        if ($xima <= 0) { return $this->json(1, '洗码应大于0'); }
        
        $trano = 'XM' . date('YmdHis') . rand(1000, 9999);
        $oldxima = $info->xima;
        
        if ($type == 1 || $type == '1') {
            $newxima = $oldxima + abs($xima);
            Db::table('caipiao_member')->where('id', $id)->update([
                'xima' => Db::raw('xima + ' . abs($xima))
            ]);
            Db::table('caipiao_fuddetail')->insert([
                'trano' => $trano, 'uid' => $info->id, 'username' => $info->username,
                'type' => 'xima', 'typename' => '洗码', 'amount' => abs($xima),
                'amountbefor' => $oldxima, 'amountafter' => $newxima,
                'remark' => $remark ?: '管理员增加洗码', 'oddtime' => time()
            ]);
        } else {
            if ($oldxima < abs($xima)) { return $this->json(1, '洗码Số dư không đủ'); }
            $newxima = $oldxima - abs($xima);
            Db::table('caipiao_member')->where('id', $id)->update([
                'xima' => Db::raw('xima - ' . abs($xima))
            ]);
            Db::table('caipiao_fuddetail')->insert([
                'trano' => $trano, 'uid' => $info->id, 'username' => $info->username,
                'type' => 'xima', 'typename' => '洗码', 'amount' => -abs($xima),
                'amountbefor' => $oldxima, 'amountafter' => $newxima,
                'remark' => $remark ?: '管理员减少洗码', 'oddtime' => time()
            ]);
        }
        return $this->json(0, 'Thao tác thành công');
    }
    
    public function agentLinkList(Request $request)
    {
        try {
            $page = $request->get('page', 1);
            $limit = $request->get('limit', 20);
            $username = $request->get('username', '');
            
            $query = Db::table('caipiao_agent_link');
            if ($username) {
                $query->where('username', 'like', "%{$username}%");
            }
            
            $count = $query->count();
            $offset = ($page - 1) * $limit;
            $list = $query->orderBy('id', 'desc')->offset($offset)->limit($limit)->get();
            
            $domain = Db::table('caipiao_setting')->where('name', 'site_url')->value('value') ?: 'http://example.com';
            
            $result = [];
            foreach ($list as $item) {
                $row = (array)$item;
                $row['created_at'] = $row['created_at'] ? date('Y-m-d H:i:s', $row['created_at']) : '';
                $row['url'] = rtrim($domain, '/') . '/register?code=' . $row['code'];
                $result[] = $row;
            }
            
            return json(['code' => 0, 'msg' => 'success', 'count' => $count, 'data' => $result]);
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => 'Lấy列表Thất bại: ' . $e->getMessage()]);
        }
    }
    
    public function agentLinkSave(Request $request)
    {
        try {
            $id = $request->post('id');
            $username = trim($request->post('username', ''));
            $type = (int)$request->post('type', 1);
            $totalCount = (int)$request->post('total_count', -1);
            $template = trim($request->post('template', 'default'));
            
            if (empty($username)) {
                return json(['code' => 1, 'msg' => 'Tên người dùngkhông được để trống']);
            }
            
            $member = Db::table('caipiao_member')->where('username', $username)->first();
            if (!$member) {
                return json(['code' => 1, 'msg' => 'Người dùng không tồn tại']);
            }
            
            $now = time();
            
            if ($id) {
                $exists = Db::table('caipiao_agent_link')->where('id', $id)->first();
                if (!$exists) {
                    return json(['code' => 1, 'msg' => 'lịch sửkhông tồn tại']);
                }
                Db::table('caipiao_agent_link')->where('id', $id)->update([
                    'uid' => $member->id, 'username' => $username, 'type' => $type,
                    'total_count' => $totalCount, 'template' => $template, 'updated_at' => $now
                ]);
            } else {
                $code = strtoupper(substr(md5($member->id . $now . rand(1000, 9999)), 0, 8));
                Db::table('caipiao_agent_link')->insert([
                    'uid' => $member->id, 'username' => $username, 'type' => $type,
                    'total_count' => $totalCount, 'used_count' => 0, 'template' => $template,
                    'code' => $code, 'created_at' => $now, 'updated_at' => $now
                ]);
            }
            
            return json(['code' => 0, 'msg' => 'Thao tác thành công']);
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => 'Thao tác thất bại: ' . $e->getMessage()]);
        }
    }
    
    public function resetFundPassword(Request $request)
    {
        $id = $request->post('id');
        $password = $request->post('password', '');
        
        if (!$id) { return $this->json(1, 'Tham số không hợp lệ'); }
        $info = Db::table('caipiao_member')->where('id', $id)->first();
        if (!$info) { return $this->json(1, 'Thành viênkhông tồn tại'); }
        
        if (empty($password)) {
            $result = Db::table('caipiao_member')->where('id', $id)->update(['tradepassword' => '']);
            $msg = '资金Mật khẩu已清除';
        } else {
            $result = Db::table('caipiao_member')->where('id', $id)->update(['tradepassword' => md5($password)]);
            $msg = '资金Mật khẩu已重置';
        }
        
        if ($result !== false) {
            return $this->json(0, $msg);
        } else {
            return $this->json(1, 'Thao tác thất bại');
        }
    }
    
    public function withdrawAccount(Request $request)
    {
        $uid = $request->get('uid');
        if (!$uid) {
            return '<script>alert("Tham số không hợp lệ");history.back();</script>';
        }
        $info = Db::table('caipiao_member')->where('id', $uid)->first();
        if (!$info) {
            return '<script>alert("Thành viênkhông tồn tại");history.back();</script>';
        }
        
        $accounts = Db::table('caipiao_withdraw_account')
            ->where('uid', $uid)
            ->where('status', 1)
            ->orderBy('is_default', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();
        
        $list = [];
        $typeMap = ['bank' => 'Thẻ ngân hàng', 'usdt' => 'USDT', 'alipay' => 'Alipay', 'wechat' => 'WeChat'];
        foreach ($accounts as $account) {
            $row = (array)$account;
            $row['created_at_text'] = $row['created_at'] ? date('Y-m-d H:i:s', $row['created_at']) : '';
            $row['type_text'] = $typeMap[$row['type']] ?? $row['type'];
            $list[] = $row;
        }
        
        return view('member/withdraw-account', ['info' => (array)$info, 'accounts' => $list]);
    }
    
    public function agentLinkDelete(Request $request)
    {
        try {
            $id = $request->post('id');
            if (!$id) { return json(['code' => 1, 'msg' => 'Tham số không hợp lệ']); }
            
            $exists = Db::table('caipiao_agent_link')->where('id', $id)->first();
            if (!$exists) { return json(['code' => 1, 'msg' => 'lịch sửkhông tồn tại']); }
            
            Db::table('caipiao_agent_link')->where('id', $id)->delete();
            return json(['code' => 0, 'msg' => 'XóaThành công']);
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => 'XóaThất bại: ' . $e->getMessage()]);
        }
    }
    
    public function securityInfo(Request $request)
    {
        $id = $request->get('id');
        if (!$id) { return $this->json(1, 'Tham số không hợp lệ'); }
        
        $user = Db::table('caipiao_member')
            ->where('id', $id)
            ->first(['id', 'username', 'phone', 'email', 'tradepassword', 'google_secret', 'google_bind', 'security_question']);
        
        if (!$user) { return $this->json(1, 'Thành viênkhông tồn tại'); }
        
        $defaultTradePwd = 'd93a5def7511da3d0f2d171d9c344e91';
        
        return $this->json(0, 'success', [
            'id' => $user->id,
            'username' => $user->username,
            'phone' => $user->phone ?: '',
            'phoneBind' => !empty($user->phone),
            'email' => $user->email ?: '',
            'emailBind' => !empty($user->email),
            'googleBind' => (bool)$user->google_bind,
            'hasFundPwd' => !empty($user->tradepassword) && $user->tradepassword !== $defaultTradePwd,
            'hasQuestion' => !empty($user->security_question)
        ]);
    }
    
    public function resetGoogle(Request $request)
    {
        $id = $request->post('id');
        if (!$id) { return $this->json(1, 'Tham số không hợp lệ'); }
        
        $info = Db::table('caipiao_member')->where('id', $id)->first();
        if (!$info) { return $this->json(1, 'Thành viênkhông tồn tại'); }
        
        $result = Db::table('caipiao_member')->where('id', $id)->update(['google_secret' => '', 'google_bind' => 0]);
        if ($result !== false) {
            return $this->json(0, 'Google验证器已重置');
        } else {
            return $this->json(1, 'Thao tác thất bại');
        }
    }
    
    public function unbindPhone(Request $request)
    {
        $id = $request->post('id');
        if (!$id) { return $this->json(1, 'Tham số không hợp lệ'); }
        
        $info = Db::table('caipiao_member')->where('id', $id)->first();
        if (!$info) { return $this->json(1, 'Thành viênkhông tồn tại'); }
        
        $result = Db::table('caipiao_member')->where('id', $id)->update(['phone' => '']);
        if ($result !== false) {
            return $this->json(0, '手机Liên kết已解除');
        } else {
            return $this->json(1, 'Thao tác thất bại');
        }
    }
    
    public function unbindEmail(Request $request)
    {
        $id = $request->post('id');
        if (!$id) { return $this->json(1, 'Tham số không hợp lệ'); }
        
        $info = Db::table('caipiao_member')->where('id', $id)->first();
        if (!$info) { return $this->json(1, 'Thành viênkhông tồn tại'); }
        
        $result = Db::table('caipiao_member')->where('id', $id)->update(['email' => '']);
        if ($result !== false) {
            return $this->json(0, '邮箱Liên kết已解除');
        } else {
            return $this->json(1, 'Thao tác thất bại');
        }
    }
    
    public function resetQuestion(Request $request)
    {
        $id = $request->post('id');
        if (!$id) { return $this->json(1, 'Tham số không hợp lệ'); }
        
        $info = Db::table('caipiao_member')->where('id', $id)->first();
        if (!$info) { return $this->json(1, 'Thành viênkhông tồn tại'); }
        
        $result = Db::table('caipiao_member')->where('id', $id)->update(['security_question' => '', 'security_answer' => '']);
        if ($result !== false) {
            return $this->json(0, '密保问题已重置');
        } else {
            return $this->json(1, 'Thao tác thất bại');
        }
    }

    public function devices(Request $request)
    {
        $id = $request->get('id');
        if (!$id) {
            return $this->json(1, '缺少Thành viênID');
        }

        $devices = Db::table('caipiao_user_device')
            ->where('user_id', $id)
            ->orderBy('last_login_at', 'desc')
            ->limit(20)
            ->get();

        $list = [];
        foreach ($devices as $d) {
            $list[] = [
                'id' => $d->id,
                'client_version' => $d->client_version,
                'browser_type' => $d->browser_type,
                'os_type' => $d->os_type,
                'os_version' => $d->os_version,
                'device_brand' => $d->device_brand,
                'device_model' => $d->device_model,
                'ip' => $d->ip,
                'ip_region' => $d->ip_region,
                'is_current' => $d->is_current,
                'last_login_at' => $d->last_login_at ? date('Y-m-d H:i:s', $d->last_login_at) : ''
            ];
        }

        return $this->json(0, 'success', ['list' => $list, 'total' => count($list)]);
    }
}
