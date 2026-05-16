<?php

namespace App\Filament\Widgets;

use App\Models\StockTransaction;
use App\Models\StockTransactionItem;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Carbon\Carbon;

class StockMovementStatsWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '30s';

    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        $today = Carbon::today();
        $thisMonth = Carbon::now()->startOfMonth();

        // Stock In today
        $stockInToday = StockTransactionItem::whereHas('transaction', function ($q) use ($today) {
            $q->where('type', 'IN')->whereDate('created_at', $today);
        })->sum('qty');

        // Stock Out today
        $stockOutToday = StockTransactionItem::whereHas('transaction', function ($q) use ($today) {
            $q->where('type', 'OUT')->whereDate('created_at', $today);
        })->sum('qty');

        // Stock In this month
        $stockInMonth = StockTransactionItem::whereHas('transaction', function ($q) use ($thisMonth) {
            $q->where('type', 'IN')->where('created_at', '>=', $thisMonth);
        })->sum('qty');

        // Stock Out this month
        $stockOutMonth = StockTransactionItem::whereHas('transaction', function ($q) use ($thisMonth) {
            $q->where('type', 'OUT')->where('created_at', '>=', $thisMonth);
        })->sum('qty');

        return [
            Stat::make('Barang Masuk Hari Ini', number_format($stockInToday))
                ->description('Unit masuk')
                ->descriptionIcon('heroicon-m-arrow-down-tray')
                ->color('success'),

            Stat::make('Barang Keluar Hari Ini', number_format($stockOutToday))
                ->description('Unit keluar')
                ->descriptionIcon('heroicon-m-arrow-up-tray')
                ->color('danger'),

            Stat::make('Masuk Bulan Ini', number_format($stockInMonth))
                ->description('Total unit masuk')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),

            Stat::make('Keluar Bulan Ini', number_format($stockOutMonth))
                ->description('Total unit keluar')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color('danger'),
        ];
    }
}
