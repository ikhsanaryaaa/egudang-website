<?php

namespace App\Exports;

use App\Models\InventoryBatch;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class InventoryBatchExport implements FromQuery, WithHeadings, WithMapping, WithTitle, ShouldAutoSize
{
    protected ?int $productId;

    public function __construct(?int $productId = null)
    {
        $this->productId = $productId;
    }

    public function query()
    {
        $query = InventoryBatch::query()
            ->with('product')
            ->where('qty_remaining', '>', 0);

        if ($this->productId) {
            $query->where('product_id', $this->productId);
        }

        return $query->orderBy('received_at', 'desc');
    }

    public function headings(): array
    {
        return [
            'Nama Produk',
            'Qty Masuk',
            'Qty Tersisa',
            'Harga Satuan',
            'Total Nilai',
            'Tanggal Diterima',
        ];
    }

    public function map($batch): array
    {
        return [
            $batch->product->name ?? '-',
            $batch->qty_in,
            $batch->qty_remaining,
            number_format($batch->unit_cost, 2),
            number_format($batch->qty_remaining * $batch->unit_cost, 2),
            $batch->received_at->format('Y-m-d H:i'),
        ];
    }

    public function title(): string
    {
        return 'Inventory Batch Report';
    }
}
