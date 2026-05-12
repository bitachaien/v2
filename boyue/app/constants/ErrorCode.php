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
        
        self::SUCCESS => '操作成功',
        self::SYSTEM_ERROR => '系统错误，请稍后重试',
        self::INVALID_PARAMS => '参数错误',
        
        
        self::UNAUTHORIZED => '未登录',
        self::FORBIDDEN => '无权限访问',
        self::TOKEN_EXPIRED => 'Token已过期，请重新登录',
        self::TOKEN_INVALID => 'Token无效或已被注销',
        self::TOKEN_MISSING => '缺少认证信息',
        
        self::LOGIN_FAILED => '登录失败',
        self::PASSWORD_ERROR => '密码错误',
        self::ACCOUNT_LOCKED => '账号已被锁定',
        self::ACCOUNT_NOT_FOUND => '账号不存在',
        
        
        self::USER_NOT_FOUND => '用户不存在',
        self::INSUFFICIENT_BALANCE => '余额不足',
        self::OPERATION_FAILED => '操作失败',
        self::DATA_NOT_FOUND => '数据不存在',
        self::ALREADY_EXISTS => '数据已存在',
        self::OPERATION_TOO_FREQUENT => '操作过于频繁，请稍后再试',
        
        
        self::BET_INSUFFICIENT_BALANCE => '余额不足',
        self::BET_EXPECT_CLOSED => '期号已截止',
        self::BET_INVALID_DATA => '投注数据无效',
        self::BET_OVER_LIMIT => '超出限额',
    ];
    
    
    public static function getMessage(int $code): string
    {
        return self::$messages[$code] ?? '未知错误';
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
