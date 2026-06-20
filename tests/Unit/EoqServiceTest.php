<?php

namespace Tests\Unit;

use App\Services\EoqService;
use PHPUnit\Framework\TestCase;

class EoqServiceTest extends TestCase
{
    private EoqService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new EoqService();
    }

    public function test_period_divisor(): void
    {
        $this->assertEquals(12, $this->service->periodDivisor('bulanan'));
        $this->assertEquals(1, $this->service->periodDivisor('tahunan'));
        // Default ke bulanan untuk nilai tak dikenal.
        $this->assertEquals(12, $this->service->periodDivisor('mingguan'));
    }

    public function test_compute_demand_per_period(): void
    {
        $this->assertEquals(100.0, $this->service->computeDemandPerPeriod(1200, 'bulanan'));
        $this->assertEquals(1200.0, $this->service->computeDemandPerPeriod(1200, 'tahunan'));
    }

    public function test_compute_holding_per_period(): void
    {
        $this->assertEquals(200.0, $this->service->computeHoldingPerPeriod(2400, 'bulanan'));
        $this->assertEquals(2400.0, $this->service->computeHoldingPerPeriod(2400, 'tahunan'));
    }

    public function test_compute_eoq_uses_classic_formula(): void
    {
        // Dp = 100, S = 10000, Hp = 200 => EOQ = sqrt((2*100*10000)/200) = sqrt(10000) = 100
        $this->assertEquals(100.0, $this->service->computeEoq(100, 10000, 200));
    }

    public function test_compute_eoq_returns_zero_when_holding_is_zero(): void
    {
        $this->assertEquals(0.0, $this->service->computeEoq(100, 10000, 0));
    }

    public function test_compute_eoq_returns_zero_when_ordering_is_zero(): void
    {
        $this->assertEquals(0.0, $this->service->computeEoq(100, 0, 200));
    }

    public function test_compute_eoq_returns_zero_when_no_demand(): void
    {
        $this->assertEquals(0.0, $this->service->computeEoq(0, 10000, 200));
    }

    public function test_compute_rop(): void
    {
        // Dp = 100, lead = 6 => ROP = 600
        $this->assertEquals(600.0, $this->service->computeRop(100, 6));
    }

    public function test_compute_order_frequency(): void
    {
        // D = 1200, EOQ = 100 => 12
        $this->assertEquals(12.0, $this->service->computeOrderFrequency(1200, 100));
    }

    public function test_compute_order_frequency_returns_zero_when_eoq_zero(): void
    {
        $this->assertEquals(0.0, $this->service->computeOrderFrequency(1200, 0));
    }

    public function test_compute_total_cost(): void
    {
        // D=1200, EOQ=100, S=10000, Hp=200
        // TIC = (1200/100)*10000 + (100/2)*200 = 120000 + 10000 = 130000
        $this->assertEquals(130000.0, $this->service->computeTotalCost(1200, 100, 10000, 200));
    }

    public function test_calculate_all_bulanan(): void
    {
        $result = $this->service->calculateAll([
            'demand' => 1200,
            'ordering_cost' => 10000,
            'holding_cost' => 2400, // per tahun => Hp = 200 (bulanan)
            'lead_time_days' => 6,
            'period_type' => 'bulanan',
        ]);

        $this->assertEquals(100.0, $result['eoq']);
        $this->assertEquals(600.0, $result['rop']);
        $this->assertEquals(12.0, $result['order_frequency']);
        $this->assertEquals(130000.0, $result['total_cost']);
    }

    public function test_calculate_all_tahunan(): void
    {
        $result = $this->service->calculateAll([
            'demand' => 1000,
            'ordering_cost' => 1000,
            'holding_cost' => 200, // tahunan => Hp = 200
            'lead_time_days' => 10,
            'period_type' => 'tahunan',
        ]);

        $this->assertEquals(100.0, $result['eoq']);
        $this->assertEquals(10000.0, $result['rop']);
        $this->assertEquals(10.0, $result['order_frequency']);
        $this->assertEquals(20000.0, $result['total_cost']);
    }
}
