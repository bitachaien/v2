<?php

namespace Tests\Unit\Yuebao;

use Tests\TestCase;
use app\validator\YuebaoValidator;
use app\exception\YuebaoException;


class YuebaoValidatorTest extends TestCase
{
    
    public function testValidateUserIdValid(): void
    {
        
        YuebaoValidator::validateUserId(1);
        YuebaoValidator::validateUserId(100);
        $this->assertTrue(true);
    }

    
    public function testValidateUserIdInvalid(): void
    {
        $this->expectException(YuebaoException::class);
        YuebaoValidator::validateUserId(0);
    }

    
    public function testValidateUserIdNegative(): void
    {
        $this->expectException(YuebaoException::class);
        YuebaoValidator::validateUserId(-1);
    }

    
    public function testValidateAmountValid(): void
    {
        $this->assertEquals(100.0, YuebaoValidator::validateAmount(100));
        $this->assertEquals(99.99, YuebaoValidator::validateAmount(99.99));
        $this->assertEquals(0.01, YuebaoValidator::validateAmount(0.01));
    }

    
    public function testValidateAmountZero(): void
    {
        $this->expectException(YuebaoException::class);
        YuebaoValidator::validateAmount(0);
    }

    
    public function testValidateAmountNegative(): void
    {
        $this->expectException(YuebaoException::class);
        YuebaoValidator::validateAmount(-100);
    }

    
    public function testValidateAmountNonNumeric(): void
    {
        $this->expectException(YuebaoException::class);
        YuebaoValidator::validateAmount('abc');
    }

    
    public function testValidateTransferInValid(): void
    {
        
        YuebaoValidator::validateTransferIn([
            'product_id' => 1,
            'amount' => 100
        ]);
        
        
        YuebaoValidator::validateTransferIn([
            'productId' => 2,
            'amount' => 200
        ]);
        
        $this->assertTrue(true);
    }

    
    public function testValidateTransferInMissingProductId(): void
    {
        $this->expectException(YuebaoException::class);
        YuebaoValidator::validateTransferIn([
            'amount' => 100
        ]);
    }

    
    public function testValidateTransferInInvalidAmount(): void
    {
        $this->expectException(YuebaoException::class);
        YuebaoValidator::validateTransferIn([
            'product_id' => 1,
            'amount' => 0
        ]);
    }

    
    public function testValidateTransferOutValid(): void
    {
        YuebaoValidator::validateTransferOut([
            'amount' => 100
        ]);
        $this->assertTrue(true);
    }

    
    public function testValidateTransferOutInvalidAmount(): void
    {
        $this->expectException(YuebaoException::class);
        YuebaoValidator::validateTransferOut([
            'amount' => -100
        ]);
    }

    
    public function testValidatePaginationDefaults(): void
    {
        $result = YuebaoValidator::validatePagination([]);
        
        $this->assertEquals(1, $result['page']);
        $this->assertEquals(20, $result['page_size']);
        $this->assertEquals(0, $result['offset']);
    }

    
    public function testValidatePaginationCustom(): void
    {
        $result = YuebaoValidator::validatePagination([
            'page' => 3,
            'page_size' => 50
        ]);
        
        $this->assertEquals(3, $result['page']);
        $this->assertEquals(50, $result['page_size']);
        $this->assertEquals(100, $result['offset']); 
    }

    
    public function testValidatePaginationCompatibility(): void
    {
        $result = YuebaoValidator::validatePagination([
            'page' => 2,
            'pageSize' => 30
        ]);
        
        $this->assertEquals(2, $result['page']);
        $this->assertEquals(30, $result['page_size']);
    }

    
    public function testValidatePaginationMaxLimit(): void
    {
        $result = YuebaoValidator::validatePagination([
            'page_size' => 1000
        ]);
        
        $this->assertEquals(100, $result['page_size']); 
    }

    
    public function testValidatePaginationNegativePage(): void
    {
        $result = YuebaoValidator::validatePagination([
            'page' => -5
        ]);
        
        $this->assertEquals(1, $result['page']); 
    }

    
    public function testValidateRecordParams(): void
    {
        $result = YuebaoValidator::validateRecordParams([
            'page' => 1,
            'page_size' => 20,
            'type' => 'income',
            'product_type' => 'current',
            'date_range' => 'today'
        ]);
        
        $this->assertEquals(1, $result['page']);
        $this->assertEquals(20, $result['page_size']);
        $this->assertEquals('income', $result['type']);
        $this->assertEquals('current', $result['product_type']);
        $this->assertEquals('today', $result['date_range']);
    }

    
    public function testValidateRecordParamsInvalidType(): void
    {
        $result = YuebaoValidator::validateRecordParams([
            'type' => 'invalid_type'
        ]);
        
        $this->assertEquals('all', $result['type']);
    }

    
    public function testValidateRecordParamsCompatibility(): void
    {
        $result = YuebaoValidator::validateRecordParams([
            'dateRange' => 'week'
        ]);
        
        $this->assertEquals('week', $result['date_range']);
    }

    
    public function testValidateBalanceSufficient(): void
    {
        YuebaoValidator::validateBalance(100, 200);
        YuebaoValidator::validateBalance(100, 100);
        $this->assertTrue(true);
    }

    
    public function testValidateBalanceInsufficient(): void
    {
        $this->expectException(YuebaoException::class);
        YuebaoValidator::validateBalance(200, 100);
    }

    
    public function testValidateMinAmountSufficient(): void
    {
        YuebaoValidator::validateMinAmount(100, 50);
        YuebaoValidator::validateMinAmount(100, 100);
        $this->assertTrue(true);
    }

    
    public function testValidateMinAmountInsufficient(): void
    {
        $this->expectException(YuebaoException::class);
        YuebaoValidator::validateMinAmount(50, 100);
    }

    
    public function testValidateEmailValid(): void
    {
        $this->assertEquals('test@example.com', YuebaoValidator::validateEmail('test@example.com'));
        $this->assertEquals('user@domain.cn', YuebaoValidator::validateEmail(' user@domain.cn '));
    }

    
    public function testValidateEmailInvalid(): void
    {
        $this->expectException(YuebaoException::class);
        YuebaoValidator::validateEmail('invalid-email');
    }

    
    public function testValidateEmailEmpty(): void
    {
        $this->expectException(YuebaoException::class);
        YuebaoValidator::validateEmail('');
    }

    
    public function testValidateAnalysisDays(): void
    {
        $this->assertEquals(7, YuebaoValidator::validateAnalysisDays(7));
        $this->assertEquals(30, YuebaoValidator::validateAnalysisDays(30));
        $this->assertEquals(1, YuebaoValidator::validateAnalysisDays(0)); 
        $this->assertEquals(90, YuebaoValidator::validateAnalysisDays(100)); 
        $this->assertEquals(7, YuebaoValidator::validateAnalysisDays('invalid')); 
    }

    
    public function testValidateHoldingParams(): void
    {
        $result = YuebaoValidator::validateHoldingParams(['status' => 'active']);
        $this->assertEquals('active', $result['status']);
        
        $result = YuebaoValidator::validateHoldingParams(['status' => 'completed']);
        $this->assertEquals('completed', $result['status']);
        
        $result = YuebaoValidator::validateHoldingParams(['status' => 'invalid']);
        $this->assertEquals('active', $result['status']); 
    }
}
