<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'transaction_item_id',
        'qty_in',
        'qty_remaining',
        'unit_cost',
        'received_at',
    ];

    protected $casts = [
        'unit_cost' => 'decimal:2',
        'received_at' => 'datetime',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function transactionItem()
    {
        return $this->belongsTo(StockTransactionItem::class, 'transaction_item_id');
    }

    public function usages()
    {
        return $this->hasMany(InventoryBatchUsage::class);
    }
}
