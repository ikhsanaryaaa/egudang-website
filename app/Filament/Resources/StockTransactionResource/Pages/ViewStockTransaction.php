<?php

namespace App\Filament\Resources\StockTransactionResource\Pages;

use App\Filament\Resources\StockTransactionResource;
use Filament\Actions;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;

class ViewStockTransaction extends ViewRecord
{
    protected static string $resource = StockTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('downloadPdf')
                ->label('Download PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('danger')
                ->action(function () {
                    $transaction = $this->record->load('items.product', 'creator');
                    $pdf = Pdf::loadView('pdf.stock-transaction', compact('transaction'));
                    return response()->streamDownload(
                        fn () => print($pdf->output()),
                        $transaction->transaction_number . '.pdf'
                    );
                }),
        ];
    }

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

                Infolists\Components\Section::make('Attachments')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('attachments')
                            ->schema([
                                Infolists\Components\TextEntry::make('file_name')
                                    ->label('File'),
                                Infolists\Components\TextEntry::make('file_type')
                                    ->label('Type')
                                    ->badge(),
                                Infolists\Components\TextEntry::make('formatted_size')
                                    ->label('Size'),
                                Infolists\Components\TextEntry::make('created_at')
                                    ->label('Uploaded')
                                    ->dateTime(),
                            ])
                            ->columns(4),
                    ])
                    ->visible(fn ($record) => $record->attachments->count() > 0),
            ]);
    }
}
