<?php

namespace Tests\Unit\Yuebao;

use Tests\TestCase;
use app\exception\YuebaoException;


class YuebaoExceptionTest extends TestCase
{
    
    public function testNotLoggedIn(): void
    {
        $exception = YuebaoException::notLoggedIn();
        
        $this->assertInstanceOf(YuebaoException::class, $exception);
        $this->assertEquals(401, $exception->getCode());
        $this->assertStringContainsString('登录', $exception->getMessage());
    }

    
    public function testUserNotFound(): void
    {
        $exception = YuebaoException::userNotFound(123);
        
        $this->assertInstanceOf(YuebaoException::class, $exception);
        $this->assertEquals(404, $exception->getCode());
        $this->assertStringContainsString('用户', $exception->getMessage());
    }

    
    public function testProductRequired(): void
    {
        $exception = YuebaoException::productRequired();
        
        $this->assertInstanceOf(YuebaoException::class, $exception);
        $this->assertStringContainsString('产品', $exception->getMessage());
    }

    
    public function testProductNotFound(): void
    {
        $exception = YuebaoException::productNotFound(1);
        
        $this->assertInstanceOf(YuebaoException::class, $exception);
        $this->assertEquals(404, $exception->getCode());
    }

    
    public function testInsufficientBalance(): void
    {
        $exception = YuebaoException::insufficientBalance(100, 50);
        
        $this->assertInstanceOf(YuebaoException::class, $exception);
        $this->assertStringContainsString('余额不足', $exception->getMessage());
    }

    
    public function testInvalidAmount(): void
    {
        $exception = YuebaoException::invalidAmount();
        
        $this->assertInstanceOf(YuebaoException::class, $exception);
        $this->assertStringContainsString('金额', $exception->getMessage());
    }

    
    public function testAmountMustPositive(): void
    {
        $exception = YuebaoException::amountMustPositive();
        
        $this->assertInstanceOf(YuebaoException::class, $exception);
        $this->assertStringContainsString('大于0', $exception->getMessage());
    }

    
    public function testAmountBelowMinimum(): void
    {
        $exception = YuebaoException::amountBelowMinimum(100);
        
        $this->assertInstanceOf(YuebaoException::class, $exception);
        $this->assertStringContainsString('100', $exception->getMessage());
    }

    
    public function testInsufficientCurrentBalance(): void
    {
        $exception = YuebaoException::insufficientCurrentBalance(100, 50);
        
        $this->assertInstanceOf(YuebaoException::class, $exception);
        $this->assertStringContainsString('活期', $exception->getMessage());
    }

    
    public function testTransferInFailed(): void
    {
        $exception = YuebaoException::transferInFailed('数据库错误');
        
        $this->assertInstanceOf(YuebaoException::class, $exception);
        $this->assertStringContainsString('转入', $exception->getMessage());
    }

    
    public function testTransferOutFailed(): void
    {
        $exception = YuebaoException::transferOutFailed('系统繁忙');
        
        $this->assertInstanceOf(YuebaoException::class, $exception);
        $this->assertStringContainsString('转出', $exception->getMessage());
    }

    
    public function testToResult(): void
    {
        $exception = YuebaoException::invalidAmount();
        $result = $exception->toResult();
        
        $this->assertIsArray($result->toArray());
        $this->assertArrayHasKey('code', $result->toArray());
        $this->assertArrayHasKey('msg', $result->toArray());
        $this->assertNotEquals(0, $result->toArray()['code']);
    }
}
