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
