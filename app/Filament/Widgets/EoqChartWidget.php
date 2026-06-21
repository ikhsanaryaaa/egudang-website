<?php

namespace App\Filament\Widgets;

use App\Models\EoqCalculation;
use Filament\Widgets\ChartWidget;

class EoqChartWidget extends ChartWidget
{
    protected static ?string $heading = 'EOQ Chart';

    protected static ?int $sort = 5;

    protected int | string | array $columnSpan = 'full';

    public ?string $filter = 'bulanan';

    protected function getFilters(): ?array
    {
        return [
            'bulanan' => 'Monthly',
            'tahunan' => 'Yearly',
        ];
    }

    protected function getData(): array
    {
        $periodType = $this->filter ?? 'bulanan';

        // Ambil 12 perhitungan terbaru pada basis periode terpilih.
        $records = EoqCalculation::query()
            ->where('period_type', $periodType)
            ->orderBy('calculation_date', 'desc')
            ->limit(12)
            ->get()
            ->reverse()
            ->values();

        $labels = $records->map(
            fn (EoqCalculation $r) => $r->period_label . ' - ' . ($r->product->name ?? '')
        )->toArray();

        return [
            'datasets' => [
                [
                    'label' => 'EOQ',
                    'data' => $records->pluck('eoq')->map(fn ($v) => (float) $v)->toArray(),
                    'backgroundColor' => 'rgba(245, 158, 11, 0.5)',
                    'borderColor' => 'rgb(245, 158, 11)',
                ],
                [
                    'label' => 'Reorder Point (ROP)',
                    'data' => $records->pluck('rop')->map(fn ($v) => (float) $v)->toArray(),
                    'backgroundColor' => 'rgba(59, 130, 246, 0.5)',
                    'borderColor' => 'rgb(59, 130, 246)',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
