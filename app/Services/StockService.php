<?php

namespace App\Services;

use App\Models\InventoryBatch;
use App\Models\InventoryBatchUsage;
use App\Models\Product;
use App\Models\StockTransaction;
use App\Models\StockTransactionItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class StockService
{
    /**
     * Generate a unique transaction number.
     * Format: TRX-YYYYMMDD-XXXX
     *
     * @param string $type
     * @return string
     */
    public function generateTransactionNumber(string $type): string
    {
        $prefix = match ($type) {
            'IN' => 'IN',
            'OUT' => 'OUT',
            'ADJ' => 'ADJ',
            default => 'TRX',
        };

        $date = Carbon::now()->format('Ymd');
        $fullPrefix = $prefix . '-' . $date . '-';

        $last = StockTransaction::where('transaction_number', 'like', $fullPrefix . '%')
            ->orderBy('transaction_number', 'desc')
            ->first();

        if (!$last) {
            $number = 1;
        } else {
            $lastNumber = (int) substr($last->transaction_number, -4);
            $number = $lastNumber + 1;
        }

        return $fullPrefix . str_pad($number, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Process a stock transaction (IN, OUT, or ADJ).
     *
     * @param string $type 'IN', 'OUT', or 'ADJ'
     * @param array $items Array of ['product_id' => int, 'qty' => int, 'unit_cost' => float]
     * @param string|null $notes
     * @param int $userId
     * @return StockTransaction
     * @throws \Exception
     */
    public function processTransaction(string $type, array $items, ?string $notes, int $userId): StockTransaction
    {
        return DB::transaction(function () use ($type, $items, $notes, $userId) {
            $transaction = StockTransaction::create([
                'transaction_number' => $this->generateTransactionNumber($type),
                'type' => $type,
                'notes' => $notes,
                'created_by' => $userId,
            ]);

            foreach ($items as $item) {
                $product = Product::lockForUpdate()->findOrFail($item['product_id']);
                $qty = (int) $item['qty'];
                $unitCost = (float) ($item['unit_cost'] ?? 0);
                $beforeStock = $product->stock;

                if ($type === 'IN') {
                    $afterStock = $beforeStock + $qty;
                } elseif ($type === 'OUT') {
                    $afterStock = $beforeStock - $qty;
                    if ($afterStock < 0) {
                        throw new \Exception("Stok tidak mencukupi untuk produk: {$product->name}. Stok saat ini: {$beforeStock}, diminta: {$qty}");
                    }
                } elseif ($type === 'ADJ') {
                    // qty is the new absolute stock value for adjustment
                    $afterStock = $qty;
                    $qty = $afterStock - $beforeStock; // calculate difference
                }

                // Create transaction item
                $transactionItem = StockTransactionItem::create([
                    'stock_transaction_id' => $transaction->id,
                    'product_id' => $product->id,
                    'qty' => abs($qty),
                    'before_stock' => $beforeStock,
                    'after_stock' => $afterStock,
                ]);

                // Handle inventory batches
                if ($type === 'IN') {
                    $this->createBatch($product, abs($qty), $unitCost);
                } elseif ($type === 'OUT') {
                    $this->consumeBatchesLifo($product, abs($qty), $transactionItem);
                } elseif ($type === 'ADJ') {
                    if ($qty > 0) {
                        // Adjustment adds stock
                        $this->createBatch($product, abs($qty), $unitCost);
                    } elseif ($qty < 0) {
                        // Adjustment removes stock
                        $this->consumeBatchesLifo($product, abs($qty), $transactionItem);
                    }
                }

                // Update product stock
                $product->updateQuietly(['stock' => $afterStock]);
            }

            return $transaction->load('items.product');
        });
    }

    /**
     * Create a new inventory batch (for stock IN).
     *
     * @param Product $product
     * @param int $qty
     * @param float $unitCost
     * @return InventoryBatch
     */
    private function createBatch(Product $product, int $qty, float $unitCost): InventoryBatch
    {
        return InventoryBatch::create([
            'product_id' => $product->id,
            'qty_in' => $qty,
            'qty_remaining' => $qty,
            'unit_cost' => $unitCost,
            'received_at' => Carbon::now(),
        ]);
    }

    /**
     * Consume inventory batches using LIFO method (for stock OUT).
     * Takes from the most recently received batch first.
     *
     * @param Product $product
     * @param int $qtyNeeded
     * @param StockTransactionItem $transactionItem
     * @return void
     * @throws \Exception
     */
    private function consumeBatchesLifo(Product $product, int $qtyNeeded, StockTransactionItem $transactionItem): void
    {
        $batches = InventoryBatch::where('product_id', $product->id)
            ->where('qty_remaining', '>', 0)
            ->orderBy('received_at', 'desc') // LIFO: latest first
            ->lockForUpdate()
            ->get();

        $totalAvailable = $batches->sum('qty_remaining');

        if ($totalAvailable < $qtyNeeded) {
            throw new \Exception("Stok batch tidak mencukupi untuk produk: {$product->name}. Tersedia: {$totalAvailable}, diminta: {$qtyNeeded}");
        }

        $remaining = $qtyNeeded;

        foreach ($batches as $batch) {
            if ($remaining <= 0) {
                break;
            }

            $take = min($remaining, $batch->qty_remaining);

            // Record batch usage
            InventoryBatchUsage::create([
                'transaction_item_id' => $transactionItem->id,
                'inventory_batch_id' => $batch->id,
                'qty_taken' => $take,
                'unit_cost' => $batch->unit_cost,
            ]);

            // Update batch remaining
            $batch->update([
                'qty_remaining' => $batch->qty_remaining - $take,
            ]);

            $remaining -= $take;
        }
    }
}
