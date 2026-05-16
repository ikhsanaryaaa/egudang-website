<?php

namespace App\Services;

use App\Models\Product;
use Carbon\Carbon;

class ProductService
{
    /**
     * Generate a unique SKU for a product.
     * Format: [CATEGORY_CODE]-YYYYMMDD-XXXX
     *
     * @param int|null $categoryId
     * @return string|null
     */
    public function generateSku(?int $categoryId = null): ?string
    {
        if (!$categoryId) {
            return null;
        }

        $category = \App\Models\Category::find($categoryId);
        if (!$category || !$category->code) {
            return null;
        }

        $date = Carbon::now()->format('Ymd');
        $prefix = strtoupper($category->code) . '-' . $date . '-';
        
        $lastProduct = Product::where('sku', 'like', $prefix . '%')
            ->orderBy('sku', 'desc')
            ->first();

        if (!$lastProduct) {
            $number = 1;
        } else {
            $lastSku = $lastProduct->sku;
            $lastNumber = (int) substr($lastSku, -4);
            $number = $lastNumber + 1;
        }

        return $prefix . str_pad($number, 4, '0', STR_PAD_LEFT);
    }
}
