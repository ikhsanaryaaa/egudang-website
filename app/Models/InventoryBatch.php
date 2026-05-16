<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'qty_in',
        'qty_remaining',
        'unit_cost',
        'received_at',
    ];

    protected $casts = [
        'received_at' => 'datetime',
        'unit_cost' => 'decimal:2',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function usages()
    {
        return $this->hasMany(InventoryBatchUsage::class);
    }
}
