<?php

namespace app\controller\api;

use support\Request;
use support\Db;
use plugin\admin\app\service\IpLocationService;
use app\service\JwtService;
use app\service\InviteCodeService;

class RegisterController
{
    public function register(Request $request)
    {
        $username = trim($request->post('username'));
        $password = $request->post('password');
        $cpassword = $request->post('cpassword');
        $reccode = $request->post('reccode', '');
        
        if (empty($username)) {
            return json([
                'code' => 400,
                'sign' => false,
                'message' => 'Tên người dùngkhông được để trống',
                'data' => null
            ]);
        }
        
        $pattern = "/\/|\~|\!|\@|\#|\\$|\%|\^|\&|\*|\(|\)|\_|\+|\{|\}|\:|\<|\>|\?|\[|\]|\,|\.|\/|\;|\'|\`|\-|\=|\\\|\|/";
        if (preg_match($pattern, $username) || mb_strlen($username, 'utf-8') < 2 || mb_strlen($username, 'utf-8') > 12) {
            return json([
                'code' => 400,
                'sign' => false,
                'message' => 'Tên người dùngtừ 2-12 ký tự chữ cái, số hoặc tiếng Việt!',
                'data' => null
            ]);
        }
        
        if (empty($password) || !preg_match("/^[\w\W]{6,16}$/", $password)) {
            return json([
                'code' => 400,
                'sign' => false,
                'message' => '请输入6-16位的Mật khẩu',
                'data' => null
            ]);
        }
        
        $hasUpper = preg_match('/[A-Z]/', $password);
        $hasLower = preg_match('/[a-z]/', $password);
        $hasNumber = preg_match('/\d/', $password);
        $hasSymbol = preg_match('/[^A-Za-z0-9]/', $password);
        $strengthScore = ($hasUpper ? 1 : 0) + ($hasLower ? 1 : 0) + ($hasNumber ? 1 : 0) + ($hasSymbol ? 1 : 0);
        
        if ($strengthScore < 2) {
            return json([
                'code' => 400,
                'sign' => false,
                'message' => 'Mật khẩu强度不足，需包含大写、小写、数字、符号中至少2种',
                'data' => null
            ]);
        }
        
        if (empty($cpassword) || !preg_match("/^[\w\W]{6,16}$/", $cpassword)) {
            return json([
                'code' => 400,
                'sign' => false,
                'message' => '请输入6-16位的重复Mật khẩu',
                'data' => null
            ]);
        }
        
        if ($password !== $cpassword) {
            return json([
                'code' => 400,
                'sign' => false,
                'message' => '两次Mật khẩu输入不一致',
                'data' => null
            ]);
        }
        
        $exists = Db::table('caipiao_member')
            ->where('username', $username)
            ->first();
        
        if ($exists) {
            return json([
                'code' => 400,
                'sign' => false,
                'message' => 'Tên người dùngđã được đăng ký',
                'data' => null
            ]);
        }
        
        $parentid = 0;
        if (!empty($reccode)) {
            $parent = null;
            
            if (!is_numeric($reccode)) {
                $decodedId = InviteCodeService::decode($reccode);
                if ($decodedId) {
                    $parent = Db::table('caipiao_member')
                        ->where('id', $decodedId)
                        ->first();
                }
            } else {
                $parent = Db::table('caipiao_member')
                    ->where('id', $reccode)
                    ->first();
            }
            
            if (!$parent) {
                return json([
                    'code' => 400,
                    'sign' => false,
                    'message' => '推荐码验证Thất bại',
                    'data' => null
                ]);
            }
            
            if ($parent->proxy != 1) {
                return json([
                    'code' => 400,
                    'sign' => false,
                    'message' => '推荐码无效',
                    'data' => null
                ]);
            }
            
            $parentid = $parent->id;
        } else {
            $defaulttjcode = Db::table('caipiao_setting')
                ->where('name', 'defaulttjcode')
                ->value('value');
            
            if ($defaulttjcode) {
                $parent = Db::table('caipiao_member')
                    ->where('id', $defaulttjcode)
                    ->first();
                if ($parent && $parent->proxy == 1) {
                    $parentid = $parent->id;
                }
            }
        }
        
        try {
            $now = time();
            $ip = $request->getRealIp();
            
            $data = [
                'username' => $username,
                'password' => password_hash($password, PASSWORD_BCRYPT),
                'parentid' => $parentid,
                'groupid' => 1,
                'nickname' => $username,
                'proxy' => 0,
                'isnb' => 0,
                'is_rebet' => 0,
                'email' => '',
                'tel' => '',
                'userbankname' => '',
                'sex' => 1,
                'balance' => 0,
                'loginip' => $ip,
                'iparea' => IpLocationService::getLocation($ip),
                'regtime' => $now,
                'regip' => $ip,
                'source' => 'mobile版Đăng ký',
                'logintime' => 0,
                'onlinetime' => 0,
                'islock' => 0,
            ];
            
            $userId = Db::table('caipiao_member')->insertGetId($data);
            
            if (!$userId) {
                return json([
                    'code' => 500,
                    'sign' => false,
                    'message' => 'Đăng kýThất bại，请稍后重试',
                    'data' => null
                ]);
            }
            
            $jwtConfig = config('jwt');
            $accessToken = JwtService::generateToken($userId, $username, ['groupid' => 1]);
            $refreshToken = JwtService::generateRefreshToken($userId, $username);
            
            Db::table('caipiao_member')->where('id', $userId)->update([
                'logintime' => $now,
                'onlinetime' => $now,
            ]);
            
            return json([
                'code' => 0,
                'sign' => true,
                'message' => 'Đăng kýThành công',
                'data' => [
                    'regisok' => 1,
                    'user_id' => $userId,
                    'username' => $username,
                    'token' => $accessToken,
                    'refreshToken' => $refreshToken,
                    'expiresIn' => $jwtConfig['ttl'],
                    'user' => [
                        'id' => $userId,
                        'username' => $username,
                        'nickname' => $username,
                        'balance' => 0,
                        'groupid' => 1,
                    ]
                ]
            ]);
            
        } catch (\Exception $e) {
            \support\Log::error('Người dùngĐăng ký异常: ' . $e->getMessage());
            return json([
                'code' => 500,
                'sign' => false,
                'message' => '系统繁忙，请稍后重试',
                'data' => null
            ]);
        }
    }
    
    public function checkUsername(Request $request)
    {
        $username = trim($request->post('username'));
        
        if (empty($username)) {
            return json([
                'code' => 400,
                'sign' => false,
                'message' => 'Tên người dùngkhông được để trống',
                'data' => null
            ]);
        }
        
        $exists = Db::table('caipiao_member')
            ->where('username', $username)
            ->first();
        
        return json([
            'code' => 0,
            'sign' => true,
            'message' => 'ok',
            'data' => [
                'ishas' => $exists ? 1 : 0,
                'available' => $exists ? false : true
            ]
        ]);
    }
    
    public function validateReccode(Request $request)
    {
        $reccode = $request->post('reccode');
        
        if (empty($reccode)) {
            return json([
                'code' => 400,
                'sign' => false,
                'message' => '推荐码không được để trống',
                'data' => null
            ]);
        }
        
        $user = null;
        if (!is_numeric($reccode)) {
            $decodedId = InviteCodeService::decode($reccode);
            if ($decodedId) {
                $user = Db::table('caipiao_member')->where('id', $decodedId)->first();
            }
        } else {
            $user = Db::table('caipiao_member')->where('id', $reccode)->first();
        }
        
        if (!$user) {
            return json([
                'code' => 404,
                'sign' => false,
                'message' => '推荐码không tồn tại',
                'data' => null
            ]);
        }
        
        if ($user->proxy != 1) {
            return json([
                'code' => 400,
                'sign' => false,
                'message' => '推荐码无效',
                'data' => null
            ]);
        }
        
        return json([
            'code' => 0,
            'sign' => true,
            'message' => '推荐码有效',
            'data' => [
                'uid' => $user->id,
                'username' => $user->username,
                'proxy' => $user->proxy
            ]
        ]);
    }
}
