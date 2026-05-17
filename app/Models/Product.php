<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Services\QrService;
use App\Services\BarcodeService;
use App\Traits\HasAuditLog;

class Product extends Model
{
    use HasFactory, HasAuditLog;

    protected $fillable = [
        'category_id',
        'sku',
        'barcode',
        'name',
        'brand',
        'description',
        'stock',
        'minimum_stock',
        'unit',
        'image_path',
        'qr_code_path',
        'barcode_image_path',
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
            if ($product->barcode) {
                $barcodeService = app(BarcodeService::class);
                $path = $barcodeService->generateForProduct($product);
                $product->updateQuietly(['barcode_image_path' => $path]);
            }
        });

        static::updated(function (Product $product) {
            if ($product->isDirty('sku')) {
                $qrService = app(QrService::class);
                $path = $qrService->regenerateForProduct($product);
                $product->updateQuietly(['qr_code_path' => $path]);
            }
            if ($product->isDirty('barcode')) {
                $barcodeService = app(BarcodeService::class);
                if ($product->barcode) {
                    $path = $barcodeService->regenerateForProduct($product);
                    $product->updateQuietly(['barcode_image_path' => $path]);
                } else {
                    $barcodeService->delete($product->barcode_image_path);
                    $product->updateQuietly(['barcode_image_path' => null]);
                }
            }
        });

        static::deleting(function (Product $product) {
            $qrService = app(QrService::class);
            $qrService->delete($product->qr_code_path);

            $barcodeService = app(BarcodeService::class);
            $barcodeService->delete($product->barcode_image_path);
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
