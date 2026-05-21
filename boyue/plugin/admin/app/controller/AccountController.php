<?php

namespace plugin\admin\app\controller;

use plugin\admin\app\common\Auth;
use plugin\admin\app\common\Util;
use plugin\admin\app\model\Admin;
use plugin\admin\app\model\AdminRole;
use plugin\admin\app\service\AdminJwtService;
use support\exception\BusinessException;
use support\Request;
use support\Response;
use Throwable;
use Webman\Captcha\CaptchaBuilder;
use Webman\Captcha\PhraseBuilder;
use plugin\admin\app\controller\MaintenanceApiController;

class AccountController extends Crud
{
    protected $noNeedLogin = ['login', 'logout', 'refreshToken'];
    protected $noNeedAuth = ['info', 'update', 'password'];
    protected $model = null;

    public function __construct()
    {
        $this->model = new Admin;
    }

    public function index()
    {
        return raw_view('account/index');
    }

    public function login(Request $request): Response
    {
        $this->checkDatabaseAvailable();
        
        $username = $request->post('username', '');
        $password = $request->post('password', '');
        
        if (!$username) {
            return $this->json(1, 'Tên người dùngkhông được để trống');
        }
        if (!$password) {
            return $this->json(1, 'Mật khẩu không được để trống');
        }
        
        $this->checkLoginLimit($username);
        
        $admin = Admin::where('username', $username)->first();
        if (!$admin || !Util::passwordVerify($password, $admin->password)) {
            return $this->json(1, 'tài khoảnkhông tồn tạihoặcMật khẩu sai');
        }
        if ($admin->status != 0) {
            return $this->json(1, '当前tài khoản暂时无法Đăng nhập');
        }
        
        $admin->login_at = date('Y-m-d H:i:s');
        $admin->save();
        $this->removeLoginLimit($username);
        
        MaintenanceApiController::recordOnline($admin->id, $admin->username, $request);
        
        $roles = AdminRole::where('admin_id', $admin->id)->pluck('role_id')->toArray();
        
        $accessToken = AdminJwtService::generateToken($admin->id, $admin->username, $roles, [
            'nickname' => $admin->nickname,
            'avatar' => $admin->avatar,
        ]);
        $refreshToken = AdminJwtService::generateRefreshToken($admin->id, $admin->username);
        
        $config = config('jwt');
        
        return $this->json(0, 'Đăng nhập thành công', [
            'id' => $admin->id,
            'username' => $admin->username,
            'nickname' => $admin->nickname,
            'avatar' => $admin->avatar,
            'email' => $admin->email,
            'mobile' => $admin->mobile,
            'roles' => $roles,
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'token_type' => 'Bearer',
            'expires_in' => $config['ttl'],
        ]);
    }
    
    public function refreshToken(Request $request): Response
    {
        $refreshToken = $request->post('refresh_token', '');
        if (empty($refreshToken)) {
            return $this->json(1, 'Refresh Token không được để trống');
        }
        
        $result = AdminJwtService::refreshToken($refreshToken);
        if (!$result) {
            return $this->json(401, 'Refresh Token 无效hoặc已过期');
        }
        
        return $this->json(0, 'Làm mớiThành công', $result);
    }

    public function logout(Request $request): Response
    {
        $adminId = admin_id();
        if ($adminId) {
            MaintenanceApiController::recordOffline($adminId);
        }
        
        $request->session()->delete('admin');
        return $this->json(0);
    }

    public function info(Request $request): Response
    {
        $adminInfo = admin();
        if (!$adminInfo) {
            return $this->json(1);
        }
        
        $admin = Admin::find($adminInfo['id']);
        if (!$admin) {
            return $this->json(1);
        }
        
        $info = [
            'id' => $admin->id,
            'username' => $admin->username,
            'nickname' => $admin->nickname,
            'avatar' => $admin->avatar,
            'email' => $admin->email,
            'mobile' => $admin->mobile,
            'realName' => $admin->real_name ?? '',
            'sex' => (string)($admin->sex ?? '0'),
            'address' => $admin->address ?? '',
            'des' => $admin->des ?? '',
            'isSuperAdmin' => Auth::isSuperAdmin(),
            'token' => $request->sessionId(),
        ];
        return $this->json(0, 'ok', $info);
    }

    public function update(Request $request): Response
    {
        $allow_column = [
            'nickname' => 'nickname',
            'nickName' => 'nickname',
            'avatar' => 'avatar',
            'email' => 'email',
            'mobile' => 'mobile',
            'realName' => 'real_name',
            'sex' => 'sex',
            'address' => 'address',
            'des' => 'des',
        ];

        $data = $request->post();
        $update_data = [];
        foreach ($allow_column as $key => $column) {
            if (isset($data[$key])) {
                $update_data[$column] = $data[$key];
            }
        }
        
        if (empty($update_data)) {
            return $this->json(1, '没有需要更新的dữ liệu');
        }
        
        Admin::where('id', admin_id())->update($update_data);
        
        $admin = admin();
        foreach ($update_data as $key => $value) {
            $admin[$key] = $value;
        }
        $request->session()->set('admin', $admin);
        
        return $this->json(0, '更新Thành công');
    }

    public function password(Request $request): Response
    {
        $admin = Admin::find(admin_id());
        if (!$admin) {
            return $this->json(1, 'Người dùng không tồn tại');
        }
        
        $hash = $admin->password;
        
        $oldPassword = $request->post('oldPassword') ?? $request->post('old_password');
        $newPassword = $request->post('newPassword') ?? $request->post('password');
        $confirmPassword = $request->post('confirmPassword') ?? $request->post('password_confirm');
        
        if (!$oldPassword) {
            return $this->json(1, '原Mật khẩu không được để trống');
        }
        if (!$newPassword) {
            return $this->json(1, '新Mật khẩu không được để trống');
        }
        if ($confirmPassword && $confirmPassword !== $newPassword) {
            return $this->json(1, '两次Mật khẩu输入不一致');
        }
        if (!Util::passwordVerify($oldPassword, $hash)) {
            return $this->json(1, '原Mật khẩu不正确');
        }
        if (strlen($newPassword) < 6) {
            return $this->json(1, '新Mật khẩu长度不能少于6位');
        }
        
        $update_data = [
            'password' => Util::passwordHash($newPassword)
        ];
        Admin::where('id', admin_id())->update($update_data);
        
        return $this->json(0, 'Mật khẩuSửaThành công');
    }

    public function captcha(Request $request, string $type = 'login'): Response
    {
        $builder = new PhraseBuilder(4, 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ');
        $captcha = new CaptchaBuilder(null, $builder);
        $captcha->build(120);
        $request->session()->set("captcha-$type", strtolower($captcha->getPhrase()));
        $img_content = $captcha->get();
        return response($img_content, 200, ['Content-Type' => 'image/jpeg']);
    }

    protected function checkLoginLimit($username)
    {
        $limit_log_path = runtime_path() . '/login';
        if (!is_dir($limit_log_path)) {
            mkdir($limit_log_path, 0777, true);
        }
        $limit_file = $limit_log_path . '/' . md5($username) . '.limit';
        $time = date('YmdH') . ceil(date('i')/5);
        $limit_info = [];
        if (is_file($limit_file)) {
            $json_str = file_get_contents($limit_file);
            $limit_info = json_decode($json_str, true);
        }

        if (!$limit_info || $limit_info['time'] != $time) {
            $limit_info = [
                'username' => $username,
                'count' => 0,
                'time' => $time
            ];
        }
        $limit_info['count']++;
        file_put_contents($limit_file, json_encode($limit_info));
        if ($limit_info['count'] >= 5) {
            throw new BusinessException('Đăng nhậpThất bại次数过多，请5分钟后再试');
        }
    }

    protected function removeLoginLimit($username)
    {
        $limit_log_path = runtime_path() . '/login';
        $limit_file = $limit_log_path . '/' . md5($username) . '.limit';
        if (is_file($limit_file)) {
            unlink($limit_file);
        }
    }

    protected function checkDatabaseAvailable()
    {
        if (!config('plugin.admin.database')) {
            throw new BusinessException('请重启webman');
        }
    }
}
