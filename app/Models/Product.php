<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Services\QrService;

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
        'qr_code_path',
    ];

    protected static function boot()
    {
        parent::boot();

        static::created(function (Product $product) {
            if ($product->sku) {
                $qrService = app(QrService::class);
                $path = $qrService->generateForProduct($product);
                $product->updateQuietly(['qr_code_path' => $path]);
            }
        });

        static::updated(function (Product $product) {
            if ($product->isDirty('sku')) {
                $qrService = app(QrService::class);
                $path = $qrService->regenerateForProduct($product);
                $product->updateQuietly(['qr_code_path' => $path]);
            }
        });

        static::deleting(function (Product $product) {
            $qrService = app(QrService::class);
            $qrService->delete($product->qr_code_path);
        });
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function attachments()
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }
}
