<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasAuditLog;

class Product extends Model
{
    use HasFactory, HasAuditLog;

    protected $fillable = [
        'category_id',
        'sku',
        'name',
        'brand',
        'description',
        'stock',
        'minimum_stock',
        'unit',
        'image_path',
        'ordering_cost',
        'holding_cost',
        'lead_time_days',
    ];

    protected $casts = [
        'ordering_cost' => 'decimal:2',
        'holding_cost' => 'decimal:2',
        'lead_time_days' => 'integer',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function attachments()
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }
}
