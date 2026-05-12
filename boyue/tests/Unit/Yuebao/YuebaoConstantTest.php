<?php

namespace Tests\Unit\Yuebao;

use Tests\TestCase;
use app\constant\YuebaoConstant;


class YuebaoConstantTest extends TestCase
{
    
    public function testProductTypeConstants(): void
    {
        $this->assertEquals('current', YuebaoConstant::PRODUCT_TYPE_CURRENT);
        $this->assertEquals('fixed', YuebaoConstant::PRODUCT_TYPE_FIXED);
    }

    
    public function testProductTypeMap(): void
    {
        $map = YuebaoConstant::PRODUCT_TYPE_MAP;
        
        $this->assertArrayHasKey('current', $map);
        $this->assertArrayHasKey('fixed', $map);
        $this->assertEquals('活期', $map['current']);
        $this->assertEquals('定期', $map['fixed']);
    }

    
    public function testRecordTypeConstants(): void
    {
        $this->assertEquals('deposit', YuebaoConstant::RECORD_TYPE_DEPOSIT);
        $this->assertEquals('withdraw', YuebaoConstant::RECORD_TYPE_WITHDRAW);
        $this->assertEquals('income', YuebaoConstant::RECORD_TYPE_INCOME);
        $this->assertEquals('settle', YuebaoConstant::RECORD_TYPE_SETTLE);
    }

    
    public function testHoldingStatusConstants(): void
    {
        $this->assertEquals('running', YuebaoConstant::HOLDING_STATUS_RUNNING);
        $this->assertEquals('settled', YuebaoConstant::HOLDING_STATUS_SETTLED);
        $this->assertEquals('done', YuebaoConstant::HOLDING_STATUS_DONE);
        $this->assertEquals('canceled', YuebaoConstant::HOLDING_STATUS_CANCELED);
    }

    
    public function testGetProductTypeText(): void
    {
        $this->assertEquals('活期', YuebaoConstant::getProductTypeText('current'));
        $this->assertEquals('定期', YuebaoConstant::getProductTypeText('fixed'));
        $this->assertEquals('未知', YuebaoConstant::getProductTypeText('unknown'));
    }

    
    public function testGetRecordTypeText(): void
    {
        $this->assertEquals('转入', YuebaoConstant::getRecordTypeText('deposit'));
        $this->assertEquals('转出', YuebaoConstant::getRecordTypeText('withdraw'));
        $this->assertEquals('收益', YuebaoConstant::getRecordTypeText('income'));
        $this->assertEquals('定期结算', YuebaoConstant::getRecordTypeText('settle'));
        $this->assertEquals('未知', YuebaoConstant::getRecordTypeText('unknown'));
    }

    
    public function testIsCurrentProduct(): void
    {
        $this->assertTrue(YuebaoConstant::isCurrentProduct('current'));
        $this->assertFalse(YuebaoConstant::isCurrentProduct('fixed'));
        $this->assertFalse(YuebaoConstant::isCurrentProduct('other'));
    }

    
    public function testIsFixedProduct(): void
    {
        $this->assertTrue(YuebaoConstant::isFixedProduct('fixed'));
        $this->assertFalse(YuebaoConstant::isFixedProduct('current'));
        $this->assertFalse(YuebaoConstant::isFixedProduct('other'));
    }

    
    public function testGenerateOrderId(): void
    {
        $orderId1 = YuebaoConstant::generateOrderId('YBIN');
        $orderId2 = YuebaoConstant::generateOrderId('YBOUT');
        
        
        $this->assertStringStartsWith('YBIN', $orderId1);
        $this->assertStringStartsWith('YBOUT', $orderId2);
        
        
        $this->assertEquals(22, strlen($orderId1));
        $this->assertEquals(23, strlen($orderId2));
        
        
        $this->assertNotEquals($orderId1, $orderId2);
    }

    
    public function testPaginationDefaults(): void
    {
        $this->assertEquals(20, YuebaoConstant::DEFAULT_PAGE_SIZE);
        $this->assertEquals(100, YuebaoConstant::MAX_PAGE_SIZE);
        $this->assertEquals(10, YuebaoConstant::DEFAULT_API_LIMIT);
    }
}
