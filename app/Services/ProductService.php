<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Str;

class ProductService
{
    /**
     * Generate a unique SKU for a product.
     * Format: AAA-productname-unit-000
     * Example: ELC-laptopasus-pcs-001
     *
     * @param int|null $categoryId
     * @param string|null $productName
     * @param string|null $unit
     * @return string|null
     */
    public function generateSku(?int $categoryId = null, ?string $productName = null, ?string $unit = null): ?string
    {
        if (!$categoryId || !$productName || !$unit) {
            return null;
        }

        $category = \App\Models\Category::find($categoryId);
        if (!$category || !$category->code) {
            return null;
        }

        $categoryCode = strtoupper($category->code);
        $namePart = Str::slug($productName, '');
        $unitPart = strtolower(trim($unit));

        $prefix = $categoryCode . '-' . $namePart . '-' . $unitPart . '-';

        $lastProduct = Product::where('sku', 'like', $prefix . '%')
            ->orderBy('sku', 'desc')
            ->first();

        if (!$lastProduct) {
            $number = 1;
        } else {
            $lastSku = $lastProduct->sku;
            $lastNumber = (int) substr($lastSku, -3);
            $number = $lastNumber + 1;
        }

        return $prefix . str_pad($number, 3, '0', STR_PAD_LEFT);
    }
}
