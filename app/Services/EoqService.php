<?php

namespace App\Services;

use DateTimeImmutable;
use DateTimeInterface;

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
     * Faktor nilai tahunan untuk basis periode terpilih.
     */
    public function periodFactor(
        string $periodType,
        DateTimeInterface|string|null $periodStart = null,
        DateTimeInterface|string|null $periodEnd = null,
    ): float {
        if ($periodType !== 'custom') {
            return 1 / $this->periodDivisor($periodType);
        }

        $start = $this->parseDate($periodStart);
        $end = $this->parseDate($periodEnd);

        if (! $start || ! $end || $end < $start) {
            return 0.0;
        }

        $factor = 0.0;
        $current = $start;

        while ($current <= $end) {
            $yearEnd = $current->setDate((int) $current->format('Y'), 12, 31);
            $segmentEnd = min($end, $yearEnd);
            $days = $current->diff($segmentEnd)->days + 1;
            $daysInYear = $current->format('L') === '1' ? 366 : 365;

            $factor += $days / $daysInYear;
            $current = $segmentEnd->modify('+1 day');
        }

        return $factor;
    }

    /**
     * Hitung demand per periode berdasarkan basis periode.
     */
    public function computeDemandPerPeriod(
        int $demand,
        string $periodType,
        DateTimeInterface|string|null $periodStart = null,
        DateTimeInterface|string|null $periodEnd = null,
    ): float {
        return $demand * $this->periodFactor($periodType, $periodStart, $periodEnd);
    }

    /**
     * Hitung holding cost per periode berdasarkan basis periode.
     */
    public function computeHoldingPerPeriod(
        float $holdingCost,
        string $periodType,
        DateTimeInterface|string|null $periodStart = null,
        DateTimeInterface|string|null $periodEnd = null,
    ): float {
        return $holdingCost * $this->periodFactor($periodType, $periodStart, $periodEnd);
    }

    private function parseDate(DateTimeInterface|string|null $date): ?DateTimeImmutable
    {
        if ($date instanceof DateTimeInterface) {
            return new DateTimeImmutable($date->format('Y-m-d'));
        }

        if (! is_string($date) || $date === '') {
            return null;
        }

        $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date);

        return $parsed && $parsed->format('Y-m-d') === $date ? $parsed : null;
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
     * @param  array{demand:int, ordering_cost:float, holding_cost:float, lead_time_days:int, period_type:string, period_start?:string, period_end?:string}  $input
     * @return array{demand_per_period:float, holding_per_period:float, eoq:float, rop:float, order_frequency:float, total_cost:float}
     */
    public function calculateAll(array $input): array
    {
        $demand = (int) ($input['demand'] ?? 0);
        $orderingCost = (float) ($input['ordering_cost'] ?? 0);
        $holdingCost = (float) ($input['holding_cost'] ?? 0);
        $leadTimeDays = (int) ($input['lead_time_days'] ?? 0);
        $periodType = $input['period_type'] ?? 'bulanan';
        $periodStart = $input['period_start'] ?? null;
        $periodEnd = $input['period_end'] ?? null;

        $demandPerPeriod = $this->computeDemandPerPeriod($demand, $periodType, $periodStart, $periodEnd);
        $holdingPerPeriod = $this->computeHoldingPerPeriod($holdingCost, $periodType, $periodStart, $periodEnd);
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
