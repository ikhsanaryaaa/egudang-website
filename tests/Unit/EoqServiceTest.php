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

    public function test_compute_eoq_uses_classic_formula(): void
    {
        // D = 1000, S = 10000, H = 500 => EOQ = sqrt((2*1000*10000)/500) = sqrt(40000) = 200
        $this->assertEquals(200.0, $this->service->computeEoq(1000, 10000, 500));
    }

    public function test_compute_eoq_returns_zero_when_holding_cost_is_zero(): void
    {
        $this->assertEquals(0.0, $this->service->computeEoq(1000, 10000, 0));
    }

    public function test_compute_eoq_returns_zero_when_ordering_cost_is_zero(): void
    {
        $this->assertEquals(0.0, $this->service->computeEoq(1000, 0, 500));
    }

    public function test_compute_eoq_returns_zero_when_no_demand(): void
    {
        $this->assertEquals(0.0, $this->service->computeEoq(0, 10000, 500));
    }

    public function test_compute_eoq_is_rounded_up(): void
    {
        // D = 1200, S = 100, H = 7 => sqrt((2*1200*100)/7) = sqrt(34285.7) ≈ 185.16 => ceil = 186
        $this->assertEquals(186.0, $this->service->computeEoq(1200, 100, 7));
    }

    public function test_compute_average_daily_demand(): void
    {
        // D = 365 => avg daily = 1
        $this->assertEquals(1.0, $this->service->computeAverageDailyDemand(365));
        // D = 730 => avg daily = 2
        $this->assertEquals(2.0, $this->service->computeAverageDailyDemand(730));
    }

    public function test_compute_safety_stock(): void
    {
        // avg daily = 1, safety_stock_days = 5 => safety stock = 5
        $this->assertEquals(5.0, $this->service->computeSafetyStock(1.0, 5));
    }

    public function test_compute_safety_stock_is_rounded_up(): void
    {
        // avg daily = 1.5, safety_stock_days = 3 => 4.5 => ceil = 5
        $this->assertEquals(5.0, $this->service->computeSafetyStock(1.5, 3));
    }

    public function test_compute_reorder_point(): void
    {
        // avg daily = 1, lead_time = 7, safety stock = 3 => ROP = (1*7) + 3 = 10
        $this->assertEquals(10.0, $this->service->computeReorderPoint(1.0, 7, 3.0));
    }

    public function test_compute_reorder_point_is_rounded_up(): void
    {
        // avg daily = 1.2, lead_time = 5, safety stock = 2 => (1.2*5) + 2 = 8 => ceil = 8
        $this->assertEquals(8.0, $this->service->computeReorderPoint(1.2, 5, 2.0));
    }
}
