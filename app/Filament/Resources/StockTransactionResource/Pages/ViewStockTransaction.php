<?php

namespace App\Filament\Resources\StockTransactionResource\Pages;

use App\Filament\Resources\StockTransactionResource;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewStockTransaction extends ViewRecord
{
    protected static string $resource = StockTransactionResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Transaction Info')
                    ->schema([
                        Infolists\Components\TextEntry::make('transaction_number')
                            ->label('No. Transaksi'),
                        Infolists\Components\TextEntry::make('type')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'IN' => 'success',
                                'OUT' => 'danger',
                                'ADJ' => 'warning',
                                default => 'gray',
                            }),
                        Infolists\Components\TextEntry::make('creator.name')
                            ->label('Created By'),
                        Infolists\Components\TextEntry::make('created_at')
                            ->dateTime(),
                        Infolists\Components\TextEntry::make('notes'),
                    ])->columns(2),

                Infolists\Components\Section::make('Transaction Items')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('items')
                            ->schema([
                                Infolists\Components\TextEntry::make('product.name')
                                    ->label('Product'),
                                Infolists\Components\TextEntry::make('qty')
                                    ->label('Qty'),
                                Infolists\Components\TextEntry::make('before_stock')
                                    ->label('Before'),
                                Infolists\Components\TextEntry::make('after_stock')
                                    ->label('After'),
                            ])
                            ->columns(4),
                    ]),
            ]);
    }
}
