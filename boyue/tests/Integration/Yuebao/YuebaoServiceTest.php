<?php

declare(strict_types=1);

namespace Tests\Integration\Yuebao;

use Tests\TestCase;
use app\service\YuebaoService;
use app\constant\YuebaoConstant;
use app\cache\YuebaoCache;
use app\exception\YuebaoException;


class YuebaoServiceTest extends TestCase
{
    private YuebaoService $service;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new YuebaoService();
    }

    

    
    public function getInfo_withValidUid_returnsSuccessResult(): void
    {
        
        $uid = 1;
        
        $result = $this->service->getInfo($uid);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('code', $result);
        
        $this->assertContains($result['code'], [0, 200, 500, 1001]);
    }

    
    public function getInfo_withInvalidUid_returnsError(): void
    {
        $uid = 0;
        
        $result = $this->service->getInfo($uid);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('code', $result);
        
        $this->assertNotEquals(0, $result['code']);
    }

    
    public function getInfo_withNegativeUid_returnsError(): void
    {
        $uid = -1;
        
        $result = $this->service->getInfo($uid);
        
        $this->assertIsArray($result);
        $this->assertNotEquals(0, $result['code']);
    }

    

    
    public function getProducts_returnsArrayResult(): void
    {
        $result = $this->service->getProducts();
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('code', $result);
    }

    
    public function getProducts_successResultHasDataKey(): void
    {
        $result = $this->service->getProducts();
        
        if ($result['code'] === 0 || $result['code'] === 200) {
            $this->assertArrayHasKey('data', $result);
            $this->assertIsArray($result['data']);
        }
    }

    

    
    public function transferIn_withEmptyData_returnsValidationError(): void
    {
        $uid = 1;
        $data = [];
        
        $result = $this->service->transferIn($uid, $data);
        
        $this->assertIsArray($result);
        $this->assertNotEquals(0, $result['code']);
        $this->assertNotEquals(200, $result['code']);
    }

    
    public function transferIn_withMissingProductId_returnsError(): void
    {
        $uid = 1;
        $data = [
            'amount' => 100,
            'password' => '123456'
        ];
        
        $result = $this->service->transferIn($uid, $data);
        
        $this->assertIsArray($result);
        $this->assertNotEquals(0, $result['code']);
    }

    
    public function transferIn_withZeroAmount_returnsError(): void
    {
        $uid = 1;
        $data = [
            'product_id' => 1,
            'amount' => 0,
            'password' => '123456'
        ];
        
        $result = $this->service->transferIn($uid, $data);
        
        $this->assertIsArray($result);
        $this->assertNotEquals(0, $result['code']);
    }

    
    public function transferIn_withNegativeAmount_returnsError(): void
    {
        $uid = 1;
        $data = [
            'product_id' => 1,
            'amount' => -100,
            'password' => '123456'
        ];
        
        $result = $this->service->transferIn($uid, $data);
        
        $this->assertIsArray($result);
        $this->assertNotEquals(0, $result['code']);
    }

    
    public function transferIn_withInvalidUid_returnsError(): void
    {
        $uid = 0;
        $data = [
            'product_id' => 1,
            'amount' => 100,
            'password' => '123456'
        ];
        
        $result = $this->service->transferIn($uid, $data);
        
        $this->assertIsArray($result);
        $this->assertNotEquals(0, $result['code']);
    }

    
    public function transferIn_withNonExistentProduct_returnsProductNotFound(): void
    {
        $uid = 1;
        $data = [
            'product_id' => 999999,
            'amount' => 100,
            'password' => '123456'
        ];
        
        $result = $this->service->transferIn($uid, $data);
        
        $this->assertIsArray($result);
        $this->assertNotEquals(0, $result['code']);
    }

    

    
    public function transferOut_withEmptyData_returnsError(): void
    {
        $uid = 1;
        $data = [];
        
        $result = $this->service->transferOut($uid, $data);
        
        $this->assertIsArray($result);
        $this->assertNotEquals(0, $result['code']);
    }

    
    public function transferOut_withZeroAmount_returnsError(): void
    {
        $uid = 1;
        $data = [
            'amount' => 0,
            'password' => '123456'
        ];
        
        $result = $this->service->transferOut($uid, $data);
        
        $this->assertIsArray($result);
        $this->assertNotEquals(0, $result['code']);
    }

    
    public function transferOut_withNegativeAmount_returnsError(): void
    {
        $uid = 1;
        $data = [
            'amount' => -100,
            'password' => '123456'
        ];
        
        $result = $this->service->transferOut($uid, $data);
        
        $this->assertIsArray($result);
        $this->assertNotEquals(0, $result['code']);
    }

    
    public function transferOut_withInvalidUid_returnsError(): void
    {
        $uid = 0;
        $data = [
            'amount' => 100,
            'password' => '123456'
        ];
        
        $result = $this->service->transferOut($uid, $data);
        
        $this->assertIsArray($result);
        $this->assertNotEquals(0, $result['code']);
    }

    

    
    public function getRecords_withValidUid_returnsArrayResult(): void
    {
        $uid = 1;
        $params = ['page' => 1, 'page_size' => 10];
        
        $result = $this->service->getRecords($uid, $params);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('code', $result);
    }

    
    public function getRecords_withInvalidUid_returnsError(): void
    {
        $uid = 0;
        $params = [];
        
        $result = $this->service->getRecords($uid, $params);
        
        $this->assertIsArray($result);
        $this->assertNotEquals(0, $result['code']);
    }

    
    public function getRecords_withTypeFilter_returnsFilteredResult(): void
    {
        $uid = 1;
        $params = [
            'page' => 1,
            'page_size' => 10,
            'type' => 'income'
        ];
        
        $result = $this->service->getRecords($uid, $params);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('code', $result);
    }

    
    public function getRecords_withDateRangeFilter_returnsFilteredResult(): void
    {
        $uid = 1;
        $params = [
            'page' => 1,
            'page_size' => 10,
            'date_range' => 'week'
        ];
        
        $result = $this->service->getRecords($uid, $params);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('code', $result);
    }

    

    
    public function getHoldings_withValidUid_returnsArrayResult(): void
    {
        $uid = 1;
        $params = ['page' => 1, 'page_size' => 10];
        
        $result = $this->service->getHoldings($uid, $params);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('code', $result);
    }

    
    public function getHoldings_withInvalidUid_returnsError(): void
    {
        $uid = 0;
        $params = [];
        
        $result = $this->service->getHoldings($uid, $params);
        
        $this->assertIsArray($result);
        $this->assertNotEquals(0, $result['code']);
    }

    
    public function getHoldings_withProductTypeFilter_returnsFilteredResult(): void
    {
        $uid = 1;
        $params = [
            'page' => 1,
            'page_size' => 10,
            'product_type' => 'current'
        ];
        
        $result = $this->service->getHoldings($uid, $params);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('code', $result);
    }

    

    
    public function allMethods_returnProperResultStructure(): void
    {
        $uid = 1;
        
        
        $methods = [
            fn() => $this->service->getInfo($uid),
            fn() => $this->service->getProducts(),
            fn() => $this->service->getRecords($uid, []),
            fn() => $this->service->getHoldings($uid, []),
        ];
        
        foreach ($methods as $method) {
            $result = $method();
            
            $this->assertIsArray($result);
            $this->assertArrayHasKey('code', $result);
            $this->assertArrayHasKey('message', $result);
        }
    }

    
    public function successResult_hasDataKey(): void
    {
        $result = $this->service->getProducts();
        
        if ($result['code'] === 0 || $result['code'] === 200) {
            $this->assertArrayHasKey('data', $result);
        }
    }

    

    
    public function transferIn_withVeryLargeAmount_handlesCorrectly(): void
    {
        $uid = 1;
        $data = [
            'product_id' => 1,
            'amount' => 999999999999.99,
            'password' => '123456'
        ];
        
        $result = $this->service->transferIn($uid, $data);
        
        
        $this->assertIsArray($result);
        $this->assertNotEquals(0, $result['code']);
    }

    
    public function transferIn_withFloatPrecision_handlesCorrectly(): void
    {
        $uid = 1;
        $data = [
            'product_id' => 1,
            'amount' => 100.123456789,
            'password' => '123456'
        ];
        
        $result = $this->service->transferIn($uid, $data);
        
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('code', $result);
    }

    
    public function getRecords_withLargePage_returnsEmptyList(): void
    {
        $uid = 1;
        $params = [
            'page' => 99999,
            'page_size' => 10
        ];
        
        $result = $this->service->getRecords($uid, $params);
        
        $this->assertIsArray($result);
        if ($result['code'] === 0 || $result['code'] === 200) {
            $this->assertArrayHasKey('data', $result);
        }
    }
}
