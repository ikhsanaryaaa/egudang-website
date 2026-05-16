<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockTransactionItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'stock_transaction_id',
        'product_id',
        'qty',
        'before_stock',
        'after_stock',
    ];

    public function transaction()
    {
        return $this->belongsTo(StockTransaction::class, 'stock_transaction_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function batchUsages()
    {
        return $this->hasMany(InventoryBatchUsage::class, 'transaction_item_id');
    }
}
