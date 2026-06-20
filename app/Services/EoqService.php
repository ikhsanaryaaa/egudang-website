<?php

namespace App\Services;

class EoqService
{
    /**
     * Divisor periode untuk konversi nilai tahunan ke basis periode.
     * - bulanan => 12 (nilai tahunan dibagi 12)
     * - tahunan => 1  (dipakai apa adanya)
     */
    public function periodDivisor(string $periodType): int
    {
        return $periodType === 'tahunan' ? 1 : 12;
    }

    /**
     * Hitung demand per periode berdasarkan basis periode.
     */
    public function computeDemandPerPeriod(int $demand, string $periodType): float
    {
        return $demand / $this->periodDivisor($periodType);
    }

    /**
     * Hitung holding cost per periode berdasarkan basis periode.
     */
    public function computeHoldingPerPeriod(float $holdingCost, string $periodType): float
    {
        return $holdingCost / $this->periodDivisor($periodType);
    }

    /**
     * Hitung Economic Order Quantity (EOQ).
     *
     * Formula: EOQ = sqrt((2 * Dp * S) / Hp)
     * Dp = demand per periode, S = ordering cost, Hp = holding cost per periode.
     *
     * Mengembalikan 0 jika input tidak valid (guard pembagian nol).
     */
    public function computeEoq(float $demandPerPeriod, float $orderingCost, float $holdingPerPeriod): float
    {
        if ($demandPerPeriod <= 0 || $orderingCost <= 0 || $holdingPerPeriod <= 0) {
            return 0.0;
        }

        return round(sqrt((2 * $demandPerPeriod * $orderingCost) / $holdingPerPeriod), 2);
    }

    /**
     * Hitung Reorder Point (ROP).
     *
     * Formula: ROP = Demand per periode * Lead Time (hari).
     */
    public function computeRop(float $demandPerPeriod, int $leadTimeDays): float
    {
        return round($demandPerPeriod * $leadTimeDays, 2);
    }

    /**
     * Hitung frekuensi pemesanan dalam periode.
     *
     * Formula: F = Demand / EOQ.
     */
    public function computeOrderFrequency(int $demand, float $eoq): float
    {
        if ($eoq <= 0) {
            return 0.0;
        }

        return round($demand / $eoq, 2);
    }

    /**
     * Hitung Total Inventory Cost (TIC).
     *
     * Formula: TIC = (D/EOQ) * S + (EOQ/2) * Hp.
     */
    public function computeTotalCost(int $demand, float $eoq, float $orderingCost, float $holdingPerPeriod): float
    {
        if ($eoq <= 0) {
            return 0.0;
        }

        $orderingComponent = ($demand / $eoq) * $orderingCost;
        $holdingComponent = ($eoq / 2) * $holdingPerPeriod;

        return round($orderingComponent + $holdingComponent, 2);
    }

    /**
     * Hitung seluruh hasil EOQ dari input transaksi.
     *
     * @param array{demand:int, ordering_cost:float, holding_cost:float, lead_time_days:int, period_type:string} $input
     * @return array{demand_per_period:float, holding_per_period:float, eoq:float, rop:float, order_frequency:float, total_cost:float}
     */
    public function calculateAll(array $input): array
    {
        $demand = (int) ($input['demand'] ?? 0);
        $orderingCost = (float) ($input['ordering_cost'] ?? 0);
        $holdingCost = (float) ($input['holding_cost'] ?? 0);
        $leadTimeDays = (int) ($input['lead_time_days'] ?? 0);
        $periodType = $input['period_type'] ?? 'bulanan';

        $demandPerPeriod = $this->computeDemandPerPeriod($demand, $periodType);
        $holdingPerPeriod = $this->computeHoldingPerPeriod($holdingCost, $periodType);
        $eoq = $this->computeEoq($demandPerPeriod, $orderingCost, $holdingPerPeriod);
        $rop = $this->computeRop($demandPerPeriod, $leadTimeDays);
        $orderFrequency = $this->computeOrderFrequency($demand, $eoq);
        $totalCost = $this->computeTotalCost($demand, $eoq, $orderingCost, $holdingPerPeriod);

        return [
            'demand_per_period' => round($demandPerPeriod, 2),
            'holding_per_period' => round($holdingPerPeriod, 2),
            'eoq' => $eoq,
            'rop' => $rop,
            'order_frequency' => $orderFrequency,
            'total_cost' => $totalCost,
        ];
    }
}
