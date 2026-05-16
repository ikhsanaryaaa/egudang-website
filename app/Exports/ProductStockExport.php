<?php

namespace App\Exports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class ProductStockExport implements FromQuery, WithHeadings, WithMapping, WithTitle, ShouldAutoSize
{
    protected ?int $categoryId;

    public function __construct(?int $categoryId = null)
    {
        $this->categoryId = $categoryId;
    }

    public function query()
    {
        $query = Product::query()->with('category');

        if ($this->categoryId) {
            $query->where('category_id', $this->categoryId);
        }

        return $query->orderBy('name');
    }

    public function headings(): array
    {
        return [
            'SKU',
            'Nama Produk',
            'Kategori',
            'Stok Saat Ini',
            'Minimum Stok',
            'Satuan',
            'Status',
        ];
    }

    public function map($product): array
    {
        $status = $product->stock <= $product->minimum_stock ? 'LOW STOCK' : 'OK';

        return [
            $product->sku,
            $product->name,
            $product->category->name ?? '-',
            $product->stock,
            $product->minimum_stock,
            $product->unit,
            $status,
        ];
    }

    public function title(): string
    {
        return 'Product Stock Report';
    }
}
