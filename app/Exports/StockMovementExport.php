<?php

namespace App\Exports;

use App\Models\StockTransactionItem;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class StockMovementExport implements FromQuery, WithHeadings, WithMapping, WithTitle, ShouldAutoSize
{
    protected ?string $dateFrom;
    protected ?string $dateTo;
    protected ?int $productId;
    protected ?string $type;

    public function __construct(?string $dateFrom = null, ?string $dateTo = null, ?int $productId = null, ?string $type = null)
    {
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
        $this->productId = $productId;
        $this->type = $type;
    }

    public function query()
    {
        $query = StockTransactionItem::query()
            ->with(['transaction.creator', 'product']);

        if ($this->dateFrom) {
            $query->whereHas('transaction', fn ($q) => $q->whereDate('created_at', '>=', $this->dateFrom));
        }

        if ($this->dateTo) {
            $query->whereHas('transaction', fn ($q) => $q->whereDate('created_at', '<=', $this->dateTo));
        }

        if ($this->productId) {
            $query->where('product_id', $this->productId);
        }

        if ($this->type) {
            $query->whereHas('transaction', fn ($q) => $q->where('type', $this->type));
        }

        return $query->orderBy('created_at', 'desc');
    }

    public function headings(): array
    {
        return [
            'No. Transaksi',
            'Nama Produk',
            'Tipe',
            'Kuantitas',
            'Stok Sebelum',
            'Stok Sesudah',
            'User',
            'Tanggal',
        ];
    }

    public function map($item): array
    {
        return [
            $item->transaction->transaction_number ?? '-',
            $item->product->name ?? '-',
            $item->transaction->type ?? '-',
            $item->qty,
            $item->before_stock,
            $item->after_stock,
            $item->transaction->creator->name ?? '-',
            $item->created_at->format('Y-m-d H:i'),
        ];
    }

    public function title(): string
    {
        return 'Stock Movement Report';
    }
}
