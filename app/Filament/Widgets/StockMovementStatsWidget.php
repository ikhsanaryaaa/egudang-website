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
            Stat::make('Stock In Today', number_format($stockInToday))
                ->description('Units incoming')
                ->descriptionIcon('heroicon-m-arrow-down-tray', 'before')
                ->color('success'),

            Stat::make('Stock Out Today', number_format($stockOutToday))
                ->description('Units outgoing')
                ->descriptionIcon('heroicon-m-arrow-up-tray', 'before')
                ->color('danger'),

            Stat::make('Stock In This Month', number_format($stockInMonth))
                ->description('Total units incoming')
                ->descriptionIcon('heroicon-m-arrow-trending-up', 'before')
                ->color('success'),

            Stat::make('Stock Out This Month', number_format($stockOutMonth))
                ->description('Total units outgoing')
                ->descriptionIcon('heroicon-m-arrow-trending-down', 'before')
                ->color('danger'),
        ];
    }
}
