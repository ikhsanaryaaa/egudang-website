<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockTransactionItem;
use Carbon\Carbon;

class EoqService
{
    /**
     * Jumlah hari dalam satu tahun untuk basis perhitungan demand.
     */
    private const DAYS_PER_YEAR = 365;

    /**
     * Hitung total annual demand berdasarkan histori transaksi OUT
     * dalam 12 bulan terakhir.
     */
    public function getAnnualDemand(Product $product): int
    {
        $oneYearAgo = Carbon::now()->subYear();

        return (int) StockTransactionItem::where('product_id', $product->id)
            ->whereHas('transaction', function ($query) use ($oneYearAgo) {
                $query->where('type', 'OUT')
                    ->where('created_at', '>=', $oneYearAgo);
            })
            ->sum('qty');
    }

    /**
     * Hitung rata-rata demand harian.
     */
    public function getAverageDailyDemand(Product $product): float
    {
        return $this->computeAverageDailyDemand($this->getAnnualDemand($product));
    }

    /**
     * Hitung Economic Order Quantity (EOQ) untuk sebuah produk.
     */
    public function calculateEoq(Product $product): float
    {
        return $this->computeEoq(
            $this->getAnnualDemand($product),
            (float) $product->ordering_cost,
            (float) $product->holding_cost,
        );
    }

    /**
     * Hitung Safety Stock untuk sebuah produk.
     */
    public function calculateSafetyStock(Product $product): float
    {
        return $this->computeSafetyStock(
            $this->getAverageDailyDemand($product),
            (int) $product->safety_stock_days,
        );
    }

    /**
     * Hitung Reorder Point (ROP) untuk sebuah produk.
     */
    public function calculateReorderPoint(Product $product): float
    {
        return $this->computeReorderPoint(
            $this->getAverageDailyDemand($product),
            (int) $product->lead_time_days,
            $this->calculateSafetyStock($product),
        );
    }

    // ---------------------------------------------------------------------
    // Pure calculation methods (tanpa akses database) — mudah di-unit-test.
    // ---------------------------------------------------------------------

    /**
     * Hitung rata-rata demand harian dari annual demand.
     */
    public function computeAverageDailyDemand(int $annualDemand): float
    {
        return $annualDemand / self::DAYS_PER_YEAR;
    }

    /**
     * Hitung Economic Order Quantity (EOQ).
     *
     * Formula: EOQ = sqrt((2 * D * S) / H)
     * D = annual demand, S = ordering cost, H = holding cost.
     *
     * Mengembalikan 0 jika demand, ordering cost, atau holding cost tidak
     * valid (guard pembagian nol / parameter belum dikonfigurasi).
     */
    public function computeEoq(int $demand, float $orderingCost, float $holdingCost): float
    {
        if ($demand <= 0 || $orderingCost <= 0 || $holdingCost <= 0) {
            return 0.0;
        }

        return (float) ceil(sqrt((2 * $demand * $orderingCost) / $holdingCost));
    }

    /**
     * Hitung Safety Stock.
     *
     * Formula: Safety Stock = Average Daily Demand * Safety Stock Days
     */
    public function computeSafetyStock(float $averageDailyDemand, int $safetyStockDays): float
    {
        return (float) ceil($averageDailyDemand * $safetyStockDays);
    }

    /**
     * Hitung Reorder Point (ROP).
     *
     * Formula: ROP = (Average Daily Demand * Lead Time) + Safety Stock
     */
    public function computeReorderPoint(float $averageDailyDemand, int $leadTimeDays, float $safetyStock): float
    {
        return (float) ceil(($averageDailyDemand * $leadTimeDays) + $safetyStock);
    }

    /**
     * Ringkasan seluruh hasil perhitungan EOQ untuk dipakai pada UI.
     *
     * @return array{annual_demand:int, average_daily_demand:float, eoq:float, safety_stock:float, reorder_point:float, is_configured:bool}
     */
    public function getSummary(Product $product): array
    {
        $orderingCost = (float) $product->ordering_cost;
        $holdingCost = (float) $product->holding_cost;

        return [
            'annual_demand' => $this->getAnnualDemand($product),
            'average_daily_demand' => round($this->getAverageDailyDemand($product), 2),
            'eoq' => $this->calculateEoq($product),
            'safety_stock' => $this->calculateSafetyStock($product),
            'reorder_point' => $this->calculateReorderPoint($product),
            'is_configured' => $orderingCost > 0 && $holdingCost > 0,
        ];
    }
}
