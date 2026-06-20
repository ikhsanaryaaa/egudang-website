<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EoqCalculation extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'calculation_date',
        'period_label',
        'period_type',
        'demand',
        'ordering_cost',
        'holding_cost',
        'lead_time_days',
        'eoq',
        'rop',
        'order_frequency',
        'total_cost',
        'created_by',
    ];

    protected $casts = [
        'calculation_date' => 'date',
        'demand' => 'integer',
        'ordering_cost' => 'decimal:2',
        'holding_cost' => 'decimal:2',
        'lead_time_days' => 'integer',
        'eoq' => 'decimal:2',
        'rop' => 'decimal:2',
        'order_frequency' => 'decimal:2',
        'total_cost' => 'decimal:2',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
