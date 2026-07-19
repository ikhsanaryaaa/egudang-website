<?php

namespace App\Filament\Pages;

use App\Exports\EoqCalculationExport;
use App\Models\EoqCalculation;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Maatwebsite\Excel\Facades\Excel;

class EoqReport extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static ?string $navigationGroup = 'Reports';

    protected static ?string $navigationLabel = 'EOQ Report';

    protected static ?string $title = 'EOQ Calculation Report';

    protected static ?int $navigationSort = 3;

    protected static string $view = 'filament.pages.eoq-report';

    public ?string $date_from = null;
    public ?string $date_to = null;

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user && ($user->hasRole('Super Admin') || $user->can('view reports'));
    }

    /**
     * Display the EOQ chart at the top of the report page (moved from dashboard).
     */
    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Widgets\EoqChartWidget::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int | array
    {
        return 1;
    }

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('EOQ Calculation Data Filter')
                    ->schema([
                        Forms\Components\DatePicker::make('date_from')
                            ->label('Start Date')
                            ->required(),
                        Forms\Components\DatePicker::make('date_to')
                            ->label('End Date')
                            ->required(),
                    ])->columns(2),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportExcel')
                ->label('Export Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->visible(fn (): bool => auth()->user()?->can('export reports') ?? false)
                ->action(fn () => $this->exportExcel()),

            Action::make('exportPdf')
                ->label('Export PDF')
                ->icon('heroicon-o-document')
                ->color('danger')
                ->visible(fn (): bool => auth()->user()?->can('export reports') ?? false)
                ->action(fn () => $this->exportPdf()),
        ];
    }

    /**
     * Data hasil perhitungan dalam rentang tanggal terpilih.
     */
    public function getRecords()
    {
        $query = EoqCalculation::query()->with('product');

        if ($this->date_from) {
            $query->whereDate('calculation_date', '>=', $this->date_from);
        }
        if ($this->date_to) {
            $query->whereDate('calculation_date', '<=', $this->date_to);
        }

        return $query->orderBy('calculation_date', 'desc')->get();
    }

    public function exportExcel()
    {
        abort_unless(auth()->user()?->can('export reports'), 403);

        $filename = 'laporan_eoq_' . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(
            new EoqCalculationExport($this->date_from, $this->date_to),
            $filename
        );
    }

    public function exportPdf()
    {
        abort_unless(auth()->user()?->can('export reports'), 403);

        $filename = 'laporan_eoq_' . now()->format('Ymd_His') . '.pdf';

        $pdf = Pdf::loadView('reports.eoq', [
            'data' => $this->getRecords(),
            'filters' => [
                'date_from' => $this->date_from,
                'date_to' => $this->date_to,
                'generated_at' => now()->format('d M Y H:i'),
            ],
        ]);

        return response()->streamDownload(fn () => print($pdf->output()), $filename);
    }
}
