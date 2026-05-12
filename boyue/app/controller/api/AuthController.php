<?php

namespace app\controller\api;

use support\Request;
use support\Db;
use app\constants\ErrorCode;
use app\service\JwtService;
use app\service\AdminWebSocketPusher;
use plugin\admin\app\service\IpLocationService;

class AuthController
{
    public function login(Request $request)
    {
        $username = $request->post('username');
        $password = $request->post('password');
        
        if (!$username || !$password) {
            return json(['code' => 400, 'message' => '用户名和密码不能为空', 'data' => null]);
        }
        
        $user = Db::table('caipiao_member as m')
            ->leftJoin('caipiao_membergroup as g', 'm.groupid', '=', 'g.groupid')
            ->where('m.username', $username)
            ->select('m.*', 'g.groupname')
            ->first();
        
        if (!$user) {
            return json(['code' => ErrorCode::ACCOUNT_NOT_FOUND, 'message' => '用户名或密码错误', 'data' => null]);
        }
        
        $passwordValid = false;
        $needsRehash = false;
        
        if (password_verify($password, $user->password)) {
            $passwordValid = true;
        } elseif (md5(sha1($password)) === $user->password) {
            $passwordValid = true;
            $needsRehash = true;
        }
        
        if (!$passwordValid) {
            return json(['code' => ErrorCode::PASSWORD_ERROR, 'message' => '用户名或密码错误', 'data' => null]);
        }
        
        if ($needsRehash) {
            Db::table('caipiao_member')->where('id', $user->id)->update(['password' => password_hash($password, PASSWORD_BCRYPT)]);
        }
        
        if ($user->islock == 1) {
            return json(['code' => ErrorCode::ACCOUNT_LOCKED, 'message' => '账号已被禁用', 'data' => null]);
        }
        
        $ip = $request->getRealIp();
        $now = time();
        $jwtConfig = config('jwt');
        
        $accessToken = JwtService::generateToken($user->id, $user->username, ['groupid' => $user->groupid]);
        $refreshToken = JwtService::generateRefreshToken($user->id, $user->username);
        
        UserController::recordDevice($user->id, $request);
        
        $iparea = IpLocationService::getLocation($ip);
        
        Db::table('caipiao_member')->where('id', $user->id)->update([
            'loginip' => $ip,
            'iparea' => $iparea,
            'logintime' => $now,
            'onlinetime' => $now,
        ]);
        
        try {
            $onlineCount = Db::table('caipiao_member')->where('isnb', 0)->where('onlinetime', '>=', time() - 1800)->count();
            AdminWebSocketPusher::pushToChannel('statistics', 'stats_online', ['count' => $onlineCount, 'change' => 1]);
        } catch (\Exception $e) {}
        
        return json([
            'code' => ErrorCode::SUCCESS,
            'message' => '登录成功',
            'data' => [
                'token' => $accessToken,
                'refreshToken' => $refreshToken,
                'expiresIn' => $jwtConfig['ttl'],
                'expires_in' => $jwtConfig['ttl'],
                'tokenType' => 'Bearer',
                'user' => [
                    'id' => $user->id,
                    'username' => $user->username,
                    'nickname' => $user->nickname ?? $user->username,
                    'userbankname' => $user->userbankname ?? '',
                    'balance' => $user->balance ?? 0,
                    'groupid' => $user->groupid,
                    'groupname' => $user->groupname ?? '',
                ]
            ]
        ]);
    }
    
    public function logout(Request $request)
    {
        $userId = $request->userId ?? 0;
        
        try {
            if ($userId) {
                Db::table('caipiao_member')->where('id', $userId)->update(['onlinetime' => 0]);
            }
            $onlineCount = Db::table('caipiao_member')->where('isnb', 0)->where('onlinetime', '>=', time() - 1800)->count();
            AdminWebSocketPusher::pushToChannel('statistics', 'stats_online', ['count' => $onlineCount, 'change' => -1]);
        } catch (\Exception $e) {}
        
        return json(['code' => ErrorCode::SUCCESS, 'message' => '退出成功', 'data' => null]);
    }
    
    public function heartbeat(Request $request)
    {
        $userId = $request->userId ?? 0;
        
        if (!$userId) {
            return json(['code' => 401, 'message' => '未登录', 'data' => null]);
        }
        
        Db::table('caipiao_member')->where('id', $userId)->update(['onlinetime' => time()]);
        
        return json(['code' => 0, 'message' => 'ok', 'data' => ['serverTime' => time()]]);
    }
    
    public function refresh(Request $request)
    {
        $refreshToken = $request->post('refreshToken') ?? $request->header('X-Refresh-Token') ?? '';
        
        if (empty($refreshToken)) {
            return json(['code' => ErrorCode::TOKEN_INVALID, 'message' => 'Refresh Token不能为空', 'data' => null]);
        }
        
        try {
            $result = JwtService::validateToken($refreshToken);
            
            if (!$result['valid']) {
                return json(['code' => ErrorCode::TOKEN_INVALID, 'message' => $result['error'] ?? 'Refresh Token无效', 'data' => null]);
            }
            
            $payload = $result['payload'];
            
            if (!JwtService::isRefreshToken($payload)) {
                return json(['code' => ErrorCode::TOKEN_INVALID, 'message' => '请使用Refresh Token', 'data' => null]);
            }
            
            $userId = $payload->uid ?? 0;
            
            $user = Db::table('caipiao_member')->where('id', $userId)->first();
            
            if (!$user) {
                return json(['code' => ErrorCode::USER_NOT_FOUND, 'message' => '用户不存在', 'data' => null]);
            }
            
            if ($user->islock == 1) {
                return json(['code' => ErrorCode::ACCOUNT_LOCKED, 'message' => '账号已被禁用', 'data' => null]);
            }
            
            $jwtConfig = config('jwt');
            
            $newAccessToken = JwtService::generateToken($user->id, $user->username, ['groupid' => $user->groupid]);
            
            $refreshTTL = JwtService::getTokenTTL($payload);
            $newRefreshToken = null;
            if ($refreshTTL < 86400) {
                $newRefreshToken = JwtService::generateRefreshToken($user->id, $user->username);
            }
            
            $responseData = [
                'token' => $newAccessToken,
                'expiresIn' => $jwtConfig['ttl'],
                'expires_in' => $jwtConfig['ttl'],
                'tokenType' => 'Bearer',
            ];
            
            if ($newRefreshToken) {
                $responseData['refreshToken'] = $newRefreshToken;
            }
            
            return json(['code' => ErrorCode::SUCCESS, 'message' => '刷新成功', 'data' => $responseData]);
            
        } catch (\Exception $e) {
            return json(['code' => ErrorCode::SYSTEM_ERROR, 'message' => '刷新失败，请稍后重试', 'data' => null]);
        }
    }
    
    public function profile(Request $request)
    {
        $userId = $request->userId ?? 0;
        
        if (!$userId) {
            return json(['code' => ErrorCode::UNAUTHORIZED, 'message' => '未登录或登录已过期', 'data' => null]);
        }
        
        $user = Db::table('caipiao_member as m')
            ->leftJoin('caipiao_membergroup as g', 'm.groupid', '=', 'g.groupid')
            ->where('m.id', $userId)
            ->select('m.*', 'g.groupname')
            ->first();
        
        if (!$user) {
            return json(['code' => ErrorCode::USER_NOT_FOUND, 'message' => '用户不存在', 'data' => null]);
        }
        
        return json([
            'code' => ErrorCode::SUCCESS,
            'message' => '获取成功',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'username' => $user->username,
                    'nickname' => $user->nickname ?? $user->username,
                    'avatar' => $user->face ?? '',
                    'email' => $user->email ?? '',
                    'phone' => $user->phone ?? '',
                    'balance' => $user->balance ?? 0,
                    'groupid' => $user->groupid,
                    'groupname' => $user->groupname ?? '',
                    'userbankname' => $user->userbankname ?? '',
                ]
            ]
        ]);
    }
    
    public function updateProfile(Request $request)
    {
        $userId = $request->userId ?? 0;
        
        if (!$userId) {
            return json(['code' => 401, 'message' => '未登录或登录已过期', 'data' => null]);
        }
        
        try {
            $updateData = [];
            
            $postData = $request->post();
            if (empty($postData)) {
                $postData = json_decode($request->rawBody(), true) ?: [];
            }
            
            $nickname = $postData['nickname'] ?? null;
            if ($nickname !== null && trim($nickname) !== '') {
                $updateData['nickname'] = trim($nickname);
            }
            
            $avatarUrl = $postData['avatar'] ?? null;
            if ($avatarUrl && is_string($avatarUrl) && trim($avatarUrl) !== '') {
                $updateData['face'] = trim($avatarUrl);
            } else {
                $file = $request->file('avatar');
                if ($file && $file->isValid()) {
                    $uploadMimeType = $file->getUploadMimeType();
                    $ext = $file->getUploadExtension();
                    
                    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                    if (!in_array($uploadMimeType, $allowedTypes)) {
                        return json(['code' => 400, 'message' => '不支持的图片格式', 'data' => null]);
                    }
                    
                    $filename = 'avatar/' . date('Ymd') . '/' . $userId . '_' . uniqid() . '.' . $ext;
                    $savePath = public_path() . '/uploads/' . $filename;
                    
                    $dir = dirname($savePath);
                    if (!is_dir($dir)) {
                        mkdir($dir, 0755, true);
                    }
                    
                    $file->move($savePath);
                    
                    $fileSize = filesize($savePath);
                    if ($fileSize > 5 * 1024 * 1024) {
                        @unlink($savePath);
                        return json(['code' => 400, 'message' => '头像文件过大，最大支持5MB', 'data' => null]);
                    }
                    
                    $updateData['face'] = '/uploads/' . $filename;
                }
            }
            
            if (empty($updateData)) {
                return json(['code' => 400, 'message' => '没有需要更新的数据', 'data' => null]);
            }
            
            Db::table('caipiao_member')->where('id', $userId)->update($updateData);
            
            $user = Db::table('caipiao_member')->where('id', $userId)->first();
            $group = Db::table('caipiao_membergroup')->where('groupid', $user->groupid)->first();
            
            return json([
                'code' => 0,
                'message' => '更新成功',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'username' => $user->username,
                        'nickname' => $user->nickname ?? $user->username,
                        'avatar' => $user->face ?? '',
                        'email' => $user->email ?? '',
                        'phone' => $user->phone ?? '',
                        'balance' => $user->balance ?? 0,
                        'groupid' => $user->groupid,
                        'groupname' => $group->groupname ?? '',
                        'userbankname' => $user->userbankname ?? '',
                    ]
                ]
            ]);
            
        } catch (\Exception $e) {
            return json(['code' => 500, 'message' => '更新失败：' . $e->getMessage(), 'data' => null]);
        }
    }
    
    public function changePassword(Request $request)
    {
        $userId = $request->userId ?? 0;
        
        if (!$userId) {
            return json(['code' => 401, 'message' => '未登录或登录已过期', 'data' => null]);
        }
        
        try {
            $oldPassword = $request->post('oldPassword', '');
            $newPassword = $request->post('newPassword', '');
            $confirmPassword = $request->post('confirmPassword', '');
            
            if (empty($oldPassword) || empty($newPassword) || empty($confirmPassword)) {
                return json(['code' => 1003, 'message' => '请填写完整的密码信息', 'data' => null]);
            }
            
            if (strlen($newPassword) < 6 || strlen($newPassword) > 20) {
                return json(['code' => 1003, 'message' => '新密码长度必须在6-20位之间', 'data' => null]);
            }
            
            if ($newPassword !== $confirmPassword) {
                return json(['code' => 1002, 'message' => '两次密码不一致', 'data' => null]);
            }
            
            $user = Db::table('caipiao_member')->where('id', $userId)->first();
            
            if (!$user) {
                return json(['code' => 1005, 'message' => '用户不存在', 'data' => null]);
            }
            
            $oldPasswordValid = password_verify($oldPassword, $user->password) || md5(sha1($oldPassword)) === $user->password;
            
            if (!$oldPasswordValid) {
                return json(['code' => 1001, 'message' => '原密码错误', 'data' => null]);
            }
            
            $newPasswordHash = password_hash($newPassword, PASSWORD_BCRYPT);
            Db::table('caipiao_member')->where('id', $userId)->update(['password' => $newPasswordHash]);
            
            return json(['code' => 0, 'message' => '密码修改成功', 'data' => null]);
            
        } catch (\Exception $e) {
            return json(['code' => 500, 'message' => '修改失败：' . $e->getMessage(), 'data' => null]);
        }
    }
    
    public function setFundPassword(Request $request)
    {
        $userId = $request->userId ?? 0;
        
        if (!$userId) {
            return json(['code' => 401, 'message' => '未登录或登录已过期', 'data' => null]);
        }
        
        try {
            $password = $request->post('password', '');
            $confirmPassword = $request->post('confirmPassword', '');
            
            if (empty($password) || empty($confirmPassword)) {
                return json(['code' => 1003, 'message' => '请填写完整的密码信息', 'data' => null]);
            }
            
            if (!preg_match('/^\d{6}$/', $password)) {
                return json(['code' => 1003, 'message' => '资金密码必须是6位数字', 'data' => null]);
            }
            
            if ($password !== $confirmPassword) {
                return json(['code' => 1002, 'message' => '两次密码不一致', 'data' => null]);
            }
            
            $user = Db::table('caipiao_member')->where('id', $userId)->first();
            
            if (!$user) {
                return json(['code' => 1005, 'message' => '用户不存在', 'data' => null]);
            }
            
            $defaultTradePassword = 'd93a5def7511da3d0f2d171d9c344e91';
            $hasSetFundPwd = !empty($user->tradepassword) && $user->tradepassword !== $defaultTradePassword;
            if ($hasSetFundPwd) {
                return json(['code' => 1007, 'message' => '已设置资金密码，请使用修改接口', 'data' => null]);
            }
            
            $passwordMd5 = md5($password);
            Db::table('caipiao_member')->where('id', $userId)->update(['tradepassword' => $passwordMd5]);
            
            return json(['code' => 0, 'message' => '资金密码设置成功', 'data' => null]);
            
        } catch (\Exception $e) {
            return json(['code' => 500, 'message' => '设置失败：' . $e->getMessage(), 'data' => null]);
        }
    }
    
    public function changeFundPassword(Request $request)
    {
        $userId = $request->userId ?? 0;
        
        if (!$userId) {
            return json(['code' => 401, 'message' => '未登录或登录已过期', 'data' => null]);
        }
        
        try {
            $oldPassword = $request->post('oldPassword', '');
            $newPassword = $request->post('newPassword', '');
            $confirmPassword = $request->post('confirmPassword', '');
            
            if (empty($oldPassword) || empty($newPassword) || empty($confirmPassword)) {
                return json(['code' => 1003, 'message' => '请填写完整的密码信息', 'data' => null]);
            }
            
            if (!preg_match('/^\d{6}$/', $newPassword)) {
                return json(['code' => 1003, 'message' => '资金密码必须是6位数字', 'data' => null]);
            }
            
            if ($newPassword !== $confirmPassword) {
                return json(['code' => 1002, 'message' => '两次密码不一致', 'data' => null]);
            }
            
            $user = Db::table('caipiao_member')->where('id', $userId)->first();
            
            if (!$user) {
                return json(['code' => 1005, 'message' => '用户不存在', 'data' => null]);
            }
            
            $oldPasswordMd5 = md5($oldPassword);
            if ($user->tradepassword !== $oldPasswordMd5) {
                return json(['code' => 1001, 'message' => '原资金密码错误', 'data' => null]);
            }
            
            $newPasswordMd5 = md5($newPassword);
            Db::table('caipiao_member')->where('id', $userId)->update(['tradepassword' => $newPasswordMd5]);
            
            return json(['code' => 0, 'message' => '资金密码修改成功', 'data' => null]);
            
        } catch (\Exception $e) {
            return json(['code' => 500, 'message' => '修改失败：' . $e->getMessage(), 'data' => null]);
        }
    }
}
