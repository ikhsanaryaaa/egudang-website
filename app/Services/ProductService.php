<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Str;

class ProductService
{
    /**
     * Generate a unique SKU for a product.
     * Format: AAA-BRAND-NAME-UNIT-000
     * Example: FF-CHAMP-NUGGET-500GR-001
     *
     * @param int|null $categoryId
     * @param string|null $productName
     * @param string|null $brand
     * @param string|null $unit
     * @return string|null
     */
    public function generateSku(?int $categoryId = null, ?string $productName = null, ?string $brand = null, ?string $unit = null): ?string
    {
        if (!$categoryId || !$productName || !$brand || !$unit) {
            return null;
        }

        $category = \App\Models\Category::find($categoryId);
        if (!$category || !$category->code) {
            return null;
        }

        $categoryCode = strtoupper(trim($category->code));

        // Convert product name: "Champ Nugget" -> "CHAMP-NUGGET"
        $namePart = strtoupper(trim($productName));
        $namePart = preg_replace('/\s+/', '-', $namePart);
        $namePart = preg_replace('/[^A-Z0-9\-]/', '', $namePart);

        // Convert brand: "Samsung" -> "SAMSUNG"
        $brandPart = strtoupper(trim($brand));
        $brandPart = preg_replace('/\s+/', '-', $brandPart);
        $brandPart = preg_replace('/[^A-Z0-9\-]/', '', $brandPart);

        // Convert unit: "500 Gr" -> "500GR"
        $unitPart = strtoupper(trim($unit));
        $unitPart = preg_replace('/\s+/', '', $unitPart);
        $unitPart = preg_replace('/[^A-Z0-9]/', '', $unitPart);

        $prefix = $categoryCode . '-' . $brandPart . '-' . $namePart . '-' . $unitPart . '-';

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
