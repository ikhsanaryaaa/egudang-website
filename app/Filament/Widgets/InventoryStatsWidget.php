<?php

namespace App\Filament\Widgets;

use App\Models\Category;
use App\Models\Product;
use App\Models\StockTransaction;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class InventoryStatsWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '30s';

    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $totalProducts = Product::count();
        $totalCategories = Category::count();
        $totalStock = Product::sum('stock');
        $lowStockCount = Product::whereColumn('stock', '<=', 'minimum_stock')->count();

        return [
            Stat::make('Total Products', $totalProducts)
                ->description('Registered products')
                ->descriptionIcon('heroicon-m-cube', 'before')
                ->color('primary')
                ->chart([7, 3, 4, 5, 6, 3, 5]),

            Stat::make('Total Categories', $totalCategories)
                ->description('Product categories')
                ->descriptionIcon('heroicon-m-tag', 'before')
                ->color('info'),

            Stat::make('Total Stock', number_format($totalStock))
                ->description('Units across warehouse')
                ->descriptionIcon('heroicon-m-archive-box', 'before')
                ->color('success'),

            Stat::make('Low Stock', $lowStockCount)
                ->description('Products below minimum stock')
                ->descriptionIcon('heroicon-m-exclamation-triangle', 'before')
                ->color($lowStockCount > 0 ? 'danger' : 'success'),
        ];
    }
}
