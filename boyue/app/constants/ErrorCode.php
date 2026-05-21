<?php

namespace app\constants;

class ErrorCode
{
    
    const SUCCESS = 0;                      
    const SYSTEM_ERROR = 500;               
    const INVALID_PARAMS = 400;             
    
    
    const UNAUTHORIZED = 401;               
    const FORBIDDEN = 403;                  
    const TOKEN_EXPIRED = 10001;            
    const TOKEN_INVALID = 10002;            
    const TOKEN_MISSING = 10003;            
    
    const LOGIN_FAILED = 1001;              
    const PASSWORD_ERROR = 1002;            
    const ACCOUNT_LOCKED = 1003;            
    const ACCOUNT_NOT_FOUND = 1004;         
    
    
    const USER_NOT_FOUND = 2001;            
    const INSUFFICIENT_BALANCE = 2002;      
    const OPERATION_FAILED = 2003;          
    const DATA_NOT_FOUND = 2004;            
    const ALREADY_EXISTS = 2005;            
    const OPERATION_TOO_FREQUENT = 2006;    
    
    
    const BET_INSUFFICIENT_BALANCE = 1001;  
    const BET_EXPECT_CLOSED = 1002;         
    const BET_INVALID_DATA = 1003;          
    const BET_OVER_LIMIT = 1004;            
    
    
    public static $messages = [
        
        self::SUCCESS => 'Thao tác thành công',
        self::SYSTEM_ERROR => 'Lỗi hệ thống，请稍后重试',
        self::INVALID_PARAMS => 'Tham số không hợp lệ',
        
        
        self::UNAUTHORIZED => '未Đăng nhập',
        self::FORBIDDEN => '无权限访问',
        self::TOKEN_EXPIRED => 'Token已过期，请重新Đăng nhập',
        self::TOKEN_INVALID => 'Token无效hoặc已被注销',
        self::TOKEN_MISSING => '缺少Xác thực信息',
        
        self::LOGIN_FAILED => 'Đăng nhậpThất bại',
        self::PASSWORD_ERROR => 'Mật khẩu sai',
        self::ACCOUNT_LOCKED => 'Tài khoản đã bị锁定',
        self::ACCOUNT_NOT_FOUND => 'Tài khoảnkhông tồn tại',
        
        
        self::USER_NOT_FOUND => 'Người dùng không tồn tại',
        self::INSUFFICIENT_BALANCE => 'Số dư không đủ',
        self::OPERATION_FAILED => 'Thao tác thất bại',
        self::DATA_NOT_FOUND => 'Dữ liệu không tồn tại',
        self::ALREADY_EXISTS => 'dữ liệuđã tồn tại',
        self::OPERATION_TOO_FREQUENT => '操作过于频繁，请稍后再试',
        
        
        self::BET_INSUFFICIENT_BALANCE => 'Số dư không đủ',
        self::BET_EXPECT_CLOSED => '期号已截止',
        self::BET_INVALID_DATA => 'Đặt cượcdữ liệu无效',
        self::BET_OVER_LIMIT => '超出限额',
    ];
    
    
    public static function getMessage(int $code): string
    {
        return self::$messages[$code] ?? '未知Lỗi';
    }
    
    
    public static function isAuthError(int $code): bool
    {
        return in_array($code, [
            self::UNAUTHORIZED,
            self::FORBIDDEN,
            self::TOKEN_EXPIRED,
            self::TOKEN_INVALID,
            self::TOKEN_MISSING,
        ]);
    }
}
