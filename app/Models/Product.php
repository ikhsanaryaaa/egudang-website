<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'sku',
        'barcode',
        'name',
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
}
