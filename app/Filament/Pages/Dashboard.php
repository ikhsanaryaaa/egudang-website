<?php

namespace App\Filament\Pages;

class Dashboard extends \Filament\Pages\Dashboard
{
    protected static ?string $title = 'Dashboard';

    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?string $slug = 'dashboard';

    protected static ?int $navigationSort = 1;

    /**
     * Explicitly list dashboard widgets. EoqChartWidget is intentionally
     * excluded here because it now lives on the EOQ Report page.
     */
    public function getWidgets(): array
    {
        return [
            \App\Filament\Widgets\InventoryStatsWidget::class,
            \App\Filament\Widgets\StockMovementStatsWidget::class,
            \App\Filament\Widgets\LowStockProductsWidget::class,
            \App\Filament\Widgets\RecentTransactionsWidget::class,
        ];
    }
}
