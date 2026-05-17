<?php

namespace App\Filament\Pages;

use App\Exports\InventoryBatchExport;
use App\Exports\LowStockExport;
use App\Exports\ProductStockExport;
use App\Exports\StockMovementExport;
use App\Models\Category;
use App\Models\Product;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Maatwebsite\Excel\Facades\Excel;

class Reports extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static ?string $navigationLabel = 'Reports';

    protected static ?int $navigationSort = 2;

    protected static string $view = 'filament.pages.reports';

    public ?string $report_type = 'product_stock';
    public ?string $date_from = null;
    public ?string $date_to = null;
    public ?string $category_id = null;
    public ?string $product_id = null;
    public ?string $transaction_type = null;

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Report Filters')
                    ->schema([
                        Forms\Components\Select::make('report_type')
                            ->label('Report Type')
                            ->options([
                                'product_stock' => 'Product Stock Report',
                                'stock_movement' => 'Stock Movement Report',
                                'low_stock' => 'Low Stock Report',
                                'inventory_batch' => 'Inventory Batch Report',
                            ])
                            ->required()
                            ->live()
                            ->native(false),

                        Forms\Components\DatePicker::make('date_from')
                            ->label('Date From')
                            ->visible(fn ($get) => $get('report_type') === 'stock_movement'),

                        Forms\Components\DatePicker::make('date_to')
                            ->label('Date To')
                            ->visible(fn ($get) => $get('report_type') === 'stock_movement'),

                        Forms\Components\Select::make('category_id')
                            ->label('Category')
                            ->options(Category::pluck('name', 'id'))
                            ->searchable()
                            ->visible(fn ($get) => in_array($get('report_type'), ['product_stock'])),

                        Forms\Components\Select::make('product_id')
                            ->label('Product')
                            ->options(Product::pluck('name', 'id'))
                            ->searchable()
                            ->visible(fn ($get) => in_array($get('report_type'), ['stock_movement', 'inventory_batch'])),

                        Forms\Components\Select::make('transaction_type')
                            ->label('Transaction Type')
                            ->options([
                                'IN' => 'Stock In',
                                'OUT' => 'Stock Out',
                                'ADJ' => 'Stock Adjustment',
                            ])
                            ->visible(fn ($get) => $get('report_type') === 'stock_movement'),
                    ])->columns(3),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportExcel')
                ->label('Export Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(fn () => $this->exportExcel()),

            Action::make('exportPdf')
                ->label('Export PDF')
                ->icon('heroicon-o-document')
                ->color('danger')
                ->action(fn () => $this->exportPdf()),
        ];
    }

    public function exportExcel()
    {
        $filename = $this->report_type . '_' . now()->format('Ymd_His') . '.xlsx';

        return match ($this->report_type) {
            'product_stock' => Excel::download(
                new ProductStockExport($this->category_id ? (int) $this->category_id : null),
                $filename
            ),
            'stock_movement' => Excel::download(
                new StockMovementExport($this->date_from, $this->date_to, $this->product_id ? (int) $this->product_id : null, $this->transaction_type),
                $filename
            ),
            'low_stock' => Excel::download(new LowStockExport(), $filename),
            'inventory_batch' => Excel::download(
                new InventoryBatchExport($this->product_id ? (int) $this->product_id : null),
                $filename
            ),
            default => null,
        };
    }

    public function exportPdf()
    {
        $data = $this->getReportData();
        $view = 'reports.' . $this->report_type;
        $filename = $this->report_type . '_' . now()->format('Ymd_His') . '.pdf';

        $pdf = Pdf::loadView($view, ['data' => $data, 'filters' => $this->getFiltersInfo()]);
        return response()->streamDownload(fn () => print($pdf->output()), $filename);
    }

    private function getReportData(): \Illuminate\Support\Collection
    {
        return match ($this->report_type) {
            'product_stock' => $this->getProductStockData(),
            'stock_movement' => $this->getStockMovementData(),
            'low_stock' => $this->getLowStockData(),
            'inventory_batch' => $this->getInventoryBatchData(),
            default => collect(),
        };
    }

    private function getProductStockData()
    {
        $query = Product::with('category');
        if ($this->category_id) {
            $query->where('category_id', $this->category_id);
        }
        return $query->orderBy('name')->get();
    }

    private function getStockMovementData()
    {
        $query = \App\Models\StockTransactionItem::with(['transaction.creator', 'product']);
        if ($this->date_from) {
            $query->whereHas('transaction', fn ($q) => $q->whereDate('created_at', '>=', $this->date_from));
        }
        if ($this->date_to) {
            $query->whereHas('transaction', fn ($q) => $q->whereDate('created_at', '<=', $this->date_to));
        }
        if ($this->product_id) {
            $query->where('product_id', $this->product_id);
        }
        if ($this->transaction_type) {
            $query->whereHas('transaction', fn ($q) => $q->where('type', $this->transaction_type));
        }
        return $query->latest()->get();
    }

    private function getLowStockData()
    {
        return Product::with('category')
            ->whereColumn('stock', '<=', 'minimum_stock')
            ->orderBy('stock')
            ->get();
    }

    private function getInventoryBatchData()
    {
        $query = \App\Models\InventoryBatch::with('product')->where('qty_remaining', '>', 0);
        if ($this->product_id) {
            $query->where('product_id', $this->product_id);
        }
        return $query->orderBy('received_at', 'desc')->get();
    }

    private function getFiltersInfo(): array
    {
        return [
            'report_type' => $this->report_type,
            'date_from' => $this->date_from,
            'date_to' => $this->date_to,
            'generated_at' => now()->format('d M Y H:i'),
        ];
    }
}
