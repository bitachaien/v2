<?php

namespace app\controller\api;

use support\Request;
use support\Db;
use app\constants\ErrorCode;


class SecurityController
{
    
    public function info(Request $request)
    {
        $userId = $request->userId ?? 0;
        
        if (!$userId) {
            return json([
                'code' => ErrorCode::UNAUTHORIZED,
                'message' => '请先登录',
                'data' => null
            ]);
        }
        
        $user = Db::table('caipiao_member')
            ->where('id', $userId)
            ->first(['username', 'phone', 'email', 'tradepassword', 'google_bind', 'security_question']);
        
        if (!$user) {
            return json([
                'code' => ErrorCode::NOT_FOUND,
                'message' => '用户不存在',
                'data' => null
            ]);
        }
        
        
        $defaultTradePwd = 'd93a5def7511da3d0f2d171d9c344e91';
        
        
        $hasFundPwd = !empty($user->tradepassword) && $user->tradepassword !== $defaultTradePwd;
        
        return json([
            'code' => ErrorCode::SUCCESS,
            'message' => 'success',
            'data' => [
                'username' => $user->username,
                'phone' => $user->phone ? $this->maskPhone($user->phone) : '',
                'phoneBind' => !empty($user->phone),
                'email' => $user->email ? $this->maskEmail($user->email) : '',
                'emailBind' => !empty($user->email),
                'googleBind' => (bool)$user->google_bind,
                'hasFundPwd' => $hasFundPwd,
                'hasQuestion' => !empty($user->security_question)
            ]
        ]);
    }
    
    
    public function sendPhoneCode(Request $request)
    {
        $userId = $request->userId ?? 0;
        $phone = $request->post('phone');
        
        if (!$userId) {
            return json(['code' => ErrorCode::UNAUTHORIZED, 'message' => '请先登录', 'data' => null]);
        }
        
        if (!$phone || !preg_match('/^1[3-9]\d{9}$/', $phone)) {
            return json(['code' => ErrorCode::INVALID_PARAMS, 'message' => '请输入正确的手机号', 'data' => null]);
        }
        
        
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expireTime = time() + 300; 
        
        
        $cacheKey = "sms_code:{$userId}:{$phone}";
        
        
        Db::table('caipiao_verify_code')->updateOrInsert(
            ['user_id' => $userId, 'type' => 'phone', 'target' => $phone],
            ['code' => $code, 'expire_time' => $expireTime, 'created_at' => time()]
        );
        
        
        \support\Log::info("发送短信验证码", ['phone' => $phone, 'code' => $code]);
        
        return json([
            'code' => ErrorCode::SUCCESS,
            'message' => '验证码已发送',
            'data' => ['expireIn' => 300]
        ]);
    }
    
    
    public function bindPhone(Request $request)
    {
        $userId = $request->userId ?? 0;
        $phone = $request->post('phone');
        $code = $request->post('code');
        
        if (!$userId) {
            return json(['code' => ErrorCode::UNAUTHORIZED, 'message' => '请先登录', 'data' => null]);
        }
        
        if (!$phone || !$code) {
            return json(['code' => ErrorCode::INVALID_PARAMS, 'message' => '参数不完整', 'data' => null]);
        }
        
        
        $skipVerify = config('app.debug', false);
        if (!$skipVerify) {
            $verify = Db::table('caipiao_verify_code')
                ->where('user_id', $userId)
                ->where('type', 'phone')
                ->where('target', $phone)
                ->where('code', $code)
                ->where('expire_time', '>', time())
                ->first();
            
            if (!$verify) {
                return json(['code' => ErrorCode::INVALID_PARAMS, 'message' => '验证码错误或已过期', 'data' => null]);
            }
        }
        
        
        $exists = Db::table('caipiao_member')
            ->where('phone', $phone)
            ->where('id', '!=', $userId)
            ->exists();
        
        if ($exists) {
            return json(['code' => ErrorCode::INVALID_PARAMS, 'message' => '该手机号已被其他账号绑定', 'data' => null]);
        }
        
        
        Db::table('caipiao_member')
            ->where('id', $userId)
            ->update(['phone' => $phone]);
        
        
        Db::table('caipiao_verify_code')
            ->where('user_id', $userId)
            ->where('type', 'phone')
            ->delete();
        
        return json([
            'code' => ErrorCode::SUCCESS,
            'message' => '手机绑定成功',
            'data' => null
        ]);
    }
    
    
    public function sendEmailCode(Request $request)
    {
        $userId = $request->userId ?? 0;
        $email = $request->post('email');
        
        if (!$userId) {
            return json(['code' => ErrorCode::UNAUTHORIZED, 'message' => '请先登录', 'data' => null]);
        }
        
        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return json(['code' => ErrorCode::INVALID_PARAMS, 'message' => '请输入正确的邮箱地址', 'data' => null]);
        }
        
        
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expireTime = time() + 300;
        
        Db::table('caipiao_verify_code')->updateOrInsert(
            ['user_id' => $userId, 'type' => 'email', 'target' => $email],
            ['code' => $code, 'expire_time' => $expireTime, 'created_at' => time()]
        );
        
        
        \support\Log::info("发送邮箱验证码", ['email' => $email, 'code' => $code]);
        
        return json([
            'code' => ErrorCode::SUCCESS,
            'message' => '验证码已发送',
            'data' => ['expireIn' => 300]
        ]);
    }
    
    
    public function bindEmail(Request $request)
    {
        $userId = $request->userId ?? 0;
        $email = $request->post('email');
        $code = $request->post('code');
        
        if (!$userId) {
            return json(['code' => ErrorCode::UNAUTHORIZED, 'message' => '请先登录', 'data' => null]);
        }
        
        if (!$email || !$code) {
            return json(['code' => ErrorCode::INVALID_PARAMS, 'message' => '参数不完整', 'data' => null]);
        }
        
        
        $skipVerify = config('app.debug', false);
        if (!$skipVerify) {
            $verify = Db::table('caipiao_verify_code')
                ->where('user_id', $userId)
                ->where('type', 'email')
                ->where('target', $email)
                ->where('code', $code)
                ->where('expire_time', '>', time())
                ->first();
            
            if (!$verify) {
                return json(['code' => ErrorCode::INVALID_PARAMS, 'message' => '验证码错误或已过期', 'data' => null]);
            }
        }
        
        
        $exists = Db::table('caipiao_member')
            ->where('email', $email)
            ->where('id', '!=', $userId)
            ->exists();
        
        if ($exists) {
            return json(['code' => ErrorCode::INVALID_PARAMS, 'message' => '该邮箱已被其他账号绑定', 'data' => null]);
        }
        
        Db::table('caipiao_member')
            ->where('id', $userId)
            ->update(['email' => $email]);
        
        Db::table('caipiao_verify_code')
            ->where('user_id', $userId)
            ->where('type', 'email')
            ->delete();
        
        return json([
            'code' => ErrorCode::SUCCESS,
            'message' => '邮箱绑定成功',
            'data' => null
        ]);
    }
    
    
    public function getGoogleSecret(Request $request)
    {
        $userId = $request->userId ?? 0;
        
        if (!$userId) {
            return json(['code' => ErrorCode::UNAUTHORIZED, 'message' => '请先登录', 'data' => null]);
        }
        
        $user = Db::table('caipiao_member')
            ->where('id', $userId)
            ->first(['username', 'google_secret', 'google_bind']);
        
        if ($user->google_bind) {
            return json(['code' => ErrorCode::INVALID_PARAMS, 'message' => '已绑定Google验证器', 'data' => null]);
        }
        
        
        $secret = $this->generateGoogleSecret();
        
        
        Db::table('caipiao_member')
            ->where('id', $userId)
            ->update(['google_secret' => $secret]);
        
        
        $issuer = urlencode('彩票平台');
        $qrContent = "otpauth://totp/{$issuer}:{$user->username}?secret={$secret}&issuer={$issuer}";
        
        return json([
            'code' => ErrorCode::SUCCESS,
            'message' => 'success',
            'data' => [
                'secret' => $secret,
                'qrcode' => $qrContent
            ]
        ]);
    }
    
    
    public function bindGoogle(Request $request)
    {
        $userId = $request->userId ?? 0;
        $code = $request->post('code');
        
        if (!$userId) {
            return json(['code' => ErrorCode::UNAUTHORIZED, 'message' => '请先登录', 'data' => null]);
        }
        
        if (!$code || strlen($code) !== 6) {
            return json(['code' => ErrorCode::INVALID_PARAMS, 'message' => '请输入6位验证码', 'data' => null]);
        }
        
        $user = Db::table('caipiao_member')
            ->where('id', $userId)
            ->first(['google_secret', 'google_bind']);
        
        if ($user->google_bind) {
            return json(['code' => ErrorCode::INVALID_PARAMS, 'message' => '已绑定Google验证器', 'data' => null]);
        }
        
        if (!$user->google_secret) {
            return json(['code' => ErrorCode::INVALID_PARAMS, 'message' => '请先获取密钥', 'data' => null]);
        }
        
        
        if (!$this->verifyGoogleCode($user->google_secret, $code)) {
            return json(['code' => ErrorCode::INVALID_PARAMS, 'message' => '验证码错误', 'data' => null]);
        }
        
        Db::table('caipiao_member')
            ->where('id', $userId)
            ->update(['google_bind' => 1]);
        
        return json([
            'code' => ErrorCode::SUCCESS,
            'message' => 'Google验证器绑定成功',
            'data' => null
        ]);
    }
    
    
    public function getQuestionList(Request $request)
    {
        $questions = [
            ['id' => 1, 'question' => '您的出生地是？'],
            ['id' => 2, 'question' => '您母亲的名字是？'],
            ['id' => 3, 'question' => '您的小学名称是？'],
            ['id' => 4, 'question' => '您最喜欢的电影是？'],
            ['id' => 5, 'question' => '您最喜欢的运动是？'],
            ['id' => 6, 'question' => '您最好的朋友名字是？'],
            ['id' => 7, 'question' => '您的宠物名字是？'],
            ['id' => 8, 'question' => '您第一份工作是？']
        ];
        
        return json([
            'code' => ErrorCode::SUCCESS,
            'message' => 'success',
            'data' => $questions
        ]);
    }
    
    
    public function setQuestion(Request $request)
    {
        $userId = $request->userId ?? 0;
        $question = $request->post('question');
        $answer = $request->post('answer');
        
        if (!$userId) {
            return json(['code' => ErrorCode::UNAUTHORIZED, 'message' => '请先登录', 'data' => null]);
        }
        
        if (!$question || !$answer) {
            return json(['code' => ErrorCode::INVALID_PARAMS, 'message' => '请选择问题并输入答案', 'data' => null]);
        }
        
        if (mb_strlen($answer) < 2) {
            return json(['code' => ErrorCode::INVALID_PARAMS, 'message' => '答案不能少于2个字符', 'data' => null]);
        }
        
        Db::table('caipiao_member')
            ->where('id', $userId)
            ->update([
                'security_question' => $question,
                'security_answer' => md5($answer)
            ]);
        
        return json([
            'code' => ErrorCode::SUCCESS,
            'message' => '密保问题设置成功',
            'data' => null
        ]);
    }
    
    
    public function verifyFundPwd(Request $request)
    {
        $userId = $request->userId ?? 0;
        $password = $request->post('password');
        
        if (!$userId) {
            return json(['code' => ErrorCode::UNAUTHORIZED, 'message' => '请先登录', 'data' => null]);
        }
        
        if (!$password || strlen($password) !== 6) {
            return json(['code' => ErrorCode::INVALID_PARAMS, 'message' => '请输入6位资金密码', 'data' => null]);
        }
        
        $user = Db::table('caipiao_member')
            ->where('id', $userId)
            ->first(['tradepassword']);
        
        if (!$user) {
            return json(['code' => ErrorCode::NOT_FOUND, 'message' => '用户不存在', 'data' => null]);
        }
        
        
        $defaultTradePwd = 'd93a5def7511da3d0f2d171d9c344e91';
        
        
        if (empty($user->tradepassword) || $user->tradepassword === $defaultTradePwd) {
            return json(['code' => ErrorCode::INVALID_PARAMS, 'message' => '请先设置资金密码', 'data' => null]);
        }
        
        
        if (md5($password) !== $user->tradepassword) {
            return json(['code' => ErrorCode::INVALID_PARAMS, 'message' => '资金密码错误', 'data' => null]);
        }
        
        return json([
            'code' => ErrorCode::SUCCESS,
            'message' => '验证成功',
            'data' => null
        ]);
    }
    
    
    private function maskPhone($phone)
    {
        if (strlen($phone) >= 11) {
            return substr($phone, 0, 3) . '****' . substr($phone, -4);
        }
        return $phone;
    }
    
    
    private function maskEmail($email)
    {
        $parts = explode('@', $email);
        if (count($parts) === 2) {
            $name = $parts[0];
            $domain = $parts[1];
            $len = strlen($name);
            if ($len > 3) {
                $name = substr($name, 0, 2) . '***' . substr($name, -1);
            } else {
                $name = $name[0] . '***';
            }
            return $name . '@' . $domain;
        }
        return $email;
    }
    
    
    private function generateGoogleSecret($length = 16)
    {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = '';
        for ($i = 0; $i < $length; $i++) {
            $secret .= $chars[random_int(0, 31)];
        }
        return $secret;
    }
    
    
    private function verifyGoogleCode($secret, $code, $discrepancy = 2)
    {
        $timeSlice = floor(time() / 30);
        
        \support\Log::info('Google验证码校验', [
            'secret' => substr($secret, 0, 4) . '****',
            'input_code' => $code,
            'server_time' => date('Y-m-d H:i:s'),
            'time_slice' => $timeSlice
        ]);
        
        for ($i = -$discrepancy; $i <= $discrepancy; $i++) {
            $calculatedCode = $this->getGoogleCode($secret, $timeSlice + $i);
            \support\Log::info('Google验证码对比', [
                'offset' => $i,
                'calculated' => $calculatedCode,
                'match' => $calculatedCode === $code
            ]);
            if ($calculatedCode === $code) {
                return true;
            }
        }
        return false;
    }
    
    
    private function getGoogleCode($secret, $timeSlice = null)
    {
        if ($timeSlice === null) {
            $timeSlice = floor(time() / 30);
        }
        
        $secretKey = $this->base32Decode($secret);
        $time = chr(0) . chr(0) . chr(0) . chr(0) . pack('N*', $timeSlice);
        $hm = hash_hmac('SHA1', $time, $secretKey, true);
        $offset = ord(substr($hm, -1)) & 0x0F;
        $hashPart = substr($hm, $offset, 4);
        $value = unpack('N', $hashPart)[1];
        $value = $value & 0x7FFFFFFF;
        $modulo = pow(10, 6);
        
        return str_pad($value % $modulo, 6, '0', STR_PAD_LEFT);
    }
    
    
    private function base32Decode($secret)
    {
        $base32chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = strtoupper($secret);
        $n = 0;
        $j = 0;
        $binary = '';
        
        for ($i = 0; $i < strlen($secret); $i++) {
            $n = $n << 5;
            $n = $n + strpos($base32chars, $secret[$i]);
            $j += 5;
            if ($j >= 8) {
                $j -= 8;
                $binary .= chr(($n & (0xFF << $j)) >> $j);
            }
        }
        return $binary;
    }
}
