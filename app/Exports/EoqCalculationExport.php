<?php

namespace App\Exports;

use App\Models\EoqCalculation;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class EoqCalculationExport implements FromQuery, WithHeadings, WithMapping, WithTitle, ShouldAutoSize
{
    public function __construct(
        protected ?string $dateFrom = null,
        protected ?string $dateTo = null,
    ) {
    }

    public function query()
    {
        $query = EoqCalculation::query()->with('product');

        if ($this->dateFrom) {
            $query->whereDate('calculation_date', '>=', $this->dateFrom);
        }
        if ($this->dateTo) {
            $query->whereDate('calculation_date', '<=', $this->dateTo);
        }

        return $query->orderBy('calculation_date', 'desc');
    }

    public function headings(): array
    {
        return [
            'Date',
            'Period',
            'Basis',
            'Product',
            'Demand',
            'Ordering Cost',
            'Holding Cost',
            'Lead Time (days)',
            'EOQ',
            'ROP',
            'Order Frequency',
            'Total Cost',
        ];
    }

    public function map($eoq): array
    {
        return [
            $eoq->calculation_date?->format('d/m/Y'),
            $eoq->period_label,
            ucfirst($eoq->period_type),
            $eoq->product->name ?? '-',
            $eoq->demand,
            $eoq->ordering_cost,
            $eoq->holding_cost,
            $eoq->lead_time_days,
            $eoq->eoq,
            $eoq->rop,
            $eoq->order_frequency,
            $eoq->total_cost,
        ];
    }

    public function title(): string
    {
        return 'EOQ Report';
    }
}
