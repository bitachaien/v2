<?php

declare(strict_types=1);

namespace Tests\Unit\Yuebao;

use Tests\TestCase;


class YuebaoInterestCalculationTest extends TestCase
{
    

    
    public function calculateDailyInterest_withBcmath_returnsCorrectResult(
        string $amount,
        string $annualRate,
        string $expected
    ): void {
        
        $dailyInterest = bcdiv(
            bcmul($amount, $annualRate, 10),
            '365',
            8
        );
        
        $this->assertEquals($expected, $dailyInterest);
    }

    public static function dailyInterestDataProvider(): array
    {
        return [
            '10000元年化3%' => ['10000', '0.03', '0.82191780'],
            '50000元年化3.5%' => ['50000', '0.035', '4.79452054'],
            '100000元年化4%' => ['100000', '0.04', '10.95890410'],
            '1元年化3%' => ['1', '0.03', '0.00008219'],
            '0.01元年化3%' => ['0.01', '0.03', '0.00000082'],
        ];
    }

    

    
    public function calculateFixedInterest_withPeriodRate_returnsCorrectResult(
        string $amount,
        string $periodRate,
        string $expected
    ): void {
        
        $interest = bcmul($amount, $periodRate, 2);
        
        $this->assertEquals($expected, $interest);
    }

    public static function fixedInterestDataProvider(): array
    {
        return [
            '10000元期间利率1%' => ['10000', '0.01', '100.00'],
            '50000元期间利率2%' => ['50000', '0.02', '1000.00'],
            '100000元期间利率3%' => ['100000', '0.03', '3000.00'],
            '999.99元期间利率1.5%' => ['999.99', '0.015', '14.99'],
        ];
    }

    

    
    public function calculateSevenDayAnnualRate_returnsCorrectResult(
        string $totalSevenDayInterest,
        string $totalAmount,
        string $expected
    ): void {
        if (bccomp($totalAmount, '0', 8) <= 0) {
            $sevenDayRate = '0.00000000';
        } else {
            
            $dailyAvg = bcdiv($totalSevenDayInterest, '7', 10);
            $dailyRate = bcdiv($dailyAvg, $totalAmount, 10);
            $sevenDayRate = bcmul(bcmul($dailyRate, '365', 10), '100', 8);
        }
        
        $this->assertEquals($expected, $sevenDayRate);
    }

    public static function sevenDayRateDataProvider(): array
    {
        return [
            '7天收益5.75元本金10000' => ['5.75', '10000', '3.00178571'],
            '7天收益0元本金10000' => ['0', '10000', '0.00000000'],
            '本金为0' => ['5.75', '0', '0.00000000'],
        ];
    }

    

    
    public function bcmath_avoidsFloatingPointErrors(): void
    {
        
        $floatResult = 0.1 + 0.2;
        $this->assertNotEquals('0.3', (string)$floatResult); 
        
        
        $bcResult = bcadd('0.1', '0.2', 1);
        $this->assertEquals('0.3', $bcResult);
    }

    
    public function bcmath_handlesLargeNumbers(): void
    {
        $largeAmount = '99999999999999.99';
        $rate = '0.035';
        
        $interest = bcmul($largeAmount, $rate, 2);
        
        $this->assertEquals('3499999999999.99', $interest);
    }

    
    public function bcmath_handlesSmallNumbers(): void
    {
        $smallAmount = '0.01';
        $rate = '0.03';
        
        $dailyInterest = bcdiv(bcmul($smallAmount, $rate, 10), '365', 8);
        
        
        $this->assertNotEquals('0.00000000', $dailyInterest);
        $this->assertEquals('0.00000082', $dailyInterest);
    }

    

    
    public function accumulatedInterest_sumsCorrectly(): void
    {
        $interests = ['0.82191780', '0.82191780', '0.82191780', '0.82191780', '0.82191780'];
        
        $total = '0';
        foreach ($interests as $interest) {
            $total = bcadd($total, $interest, 8);
        }
        
        $this->assertEquals('4.10958900', $total);
    }

    
    public function multipleUsersInterest_sumsCorrectly(): void
    {
        $userInterests = [
            ['amount' => '10000', 'rate' => '0.03'],
            ['amount' => '50000', 'rate' => '0.035'],
            ['amount' => '25000', 'rate' => '0.032'],
        ];
        
        $totalDailyInterest = '0';
        
        foreach ($userInterests as $user) {
            $dailyInterest = bcdiv(
                bcmul($user['amount'], $user['rate'], 10),
                '365',
                8
            );
            $totalDailyInterest = bcadd($totalDailyInterest, $dailyInterest, 8);
        }
        
        
        $this->assertEquals('7.80821916', $totalDailyInterest);
    }

    

    
    public function zeroAmount_returnsZeroInterest(): void
    {
        $amount = '0';
        $rate = '0.03';
        
        $interest = bcmul($amount, $rate, 8);
        
        $this->assertEquals('0.00000000', $interest);
    }

    
    public function zeroRate_returnsZeroInterest(): void
    {
        $amount = '10000';
        $rate = '0';
        
        $interest = bcmul($amount, $rate, 8);
        
        $this->assertEquals('0.00000000', $interest);
    }

    
    public function negativeValues_handledCorrectly(): void
    {
        $amount = '-10000';
        $rate = '0.03';
        
        $interest = bcmul($amount, $rate, 2);
        
        $this->assertEquals('-300.00', $interest);
    }

    

    
    public function roundingTo2Decimals_worksCorrectly(): void
    {
        
        $value = '100.126';
        $rounded = bcadd($value, '0', 2);
        
        
        $this->assertEquals('100.12', $rounded);
        
        
        $properRounded = $this->bcRound($value, 2);
        $this->assertEquals('100.13', $properRounded);
    }

    
    private function bcRound(string $number, int $precision): string
    {
        if ($precision < 0) {
            return $number;
        }
        
        $add = '0.' . str_repeat('0', $precision) . '5';
        
        if (bccomp($number, '0', 10) >= 0) {
            return bcadd($number, $add, $precision);
        } else {
            return bcsub($number, $add, $precision);
        }
    }
}
