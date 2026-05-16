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
            Stat::make('Total Produk', $totalProducts)
                ->description('Produk terdaftar')
                ->descriptionIcon('heroicon-m-cube')
                ->color('primary')
                ->chart([7, 3, 4, 5, 6, 3, 5]),

            Stat::make('Total Kategori', $totalCategories)
                ->description('Kategori produk')
                ->descriptionIcon('heroicon-m-tag')
                ->color('info'),

            Stat::make('Total Stok', number_format($totalStock))
                ->description('Unit di seluruh gudang')
                ->descriptionIcon('heroicon-m-archive-box')
                ->color('success'),

            Stat::make('Low Stock', $lowStockCount)
                ->description('Produk di bawah minimum')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($lowStockCount > 0 ? 'danger' : 'success'),
        ];
    }
}
