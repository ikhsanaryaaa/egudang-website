<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryBatchUsage extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_item_id',
        'inventory_batch_id',
        'qty_taken',
        'unit_cost',
    ];

    protected $casts = [
        'unit_cost' => 'decimal:2',
    ];

    public function transactionItem()
    {
        return $this->belongsTo(StockTransactionItem::class, 'transaction_item_id');
    }

    public function batch()
    {
        return $this->belongsTo(InventoryBatch::class, 'inventory_batch_id');
    }
}
