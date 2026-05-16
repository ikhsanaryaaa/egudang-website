<?php

namespace App\Exports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class LowStockExport implements FromQuery, WithHeadings, WithMapping, WithTitle, ShouldAutoSize
{
    public function query()
    {
        return Product::query()
            ->with('category')
            ->whereColumn('stock', '<=', 'minimum_stock')
            ->orderBy('stock', 'asc');
    }

    public function headings(): array
    {
        return [
            'SKU',
            'Nama Produk',
            'Kategori',
            'Stok Saat Ini',
            'Minimum Stok',
            'Selisih',
            'Satuan',
        ];
    }

    public function map($product): array
    {
        return [
            $product->sku,
            $product->name,
            $product->category->name ?? '-',
            $product->stock,
            $product->minimum_stock,
            $product->stock - $product->minimum_stock,
            $product->unit,
        ];
    }

    public function title(): string
    {
        return 'Low Stock Report';
    }
}
