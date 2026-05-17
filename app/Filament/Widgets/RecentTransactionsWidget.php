<?php

namespace App\Filament\Widgets;

use App\Models\StockTransaction;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentTransactionsWidget extends BaseWidget
{
    protected static ?string $heading = 'Recent Transactions';

    protected static ?int $sort = 4;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                StockTransaction::query()
                    ->with(['creator', 'items'])
                    ->latest()
            )
            ->columns([
                Tables\Columns\TextColumn::make('transaction_number')
                    ->label('Transaction No.')
                    ->searchable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'IN' => 'Stock In',
                        'OUT' => 'Stock Out',
                        'ADJ' => 'Stock Adjustment',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'IN' => 'success',
                        'OUT' => 'danger',
                        'ADJ' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('items_count')
                    ->counts('items')
                    ->label('Items'),
                Tables\Columns\TextColumn::make('creator.name')
                    ->label('User'),
                Tables\Columns\TextColumn::make('notes')
                    ->label('Notes')
                    ->limit(30),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime()
                    ->sortable(),
            ])
            ->paginated([5]);
    }
}
