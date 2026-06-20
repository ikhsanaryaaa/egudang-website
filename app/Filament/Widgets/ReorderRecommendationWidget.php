<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use App\Services\EoqService;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Collection;

class ReorderRecommendationWidget extends BaseWidget
{
    protected static ?string $heading = 'Reorder Recommendation';

    protected static ?int $sort = 4;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                // Hanya produk yang sudah dikonfigurasi parameter EOQ-nya.
                Product::query()
                    ->where('ordering_cost', '>', 0)
                    ->where('holding_cost', '>', 0)
                    ->orderBy('stock', 'asc')
            )
            ->columns([
                Tables\Columns\TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Product Name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('stock')
                    ->label('Current Stock')
                    ->numeric()
                    ->color('warning'),
                Tables\Columns\TextColumn::make('reorder_point')
                    ->label('Reorder Point')
                    ->state(fn (Product $record): string => number_format(
                        app(EoqService::class)->calculateReorderPoint($record)
                    )),
                Tables\Columns\TextColumn::make('eoq')
                    ->label('Suggested Order Qty (EOQ)')
                    ->state(fn (Product $record): string => number_format(
                        app(EoqService::class)->calculateEoq($record)
                    ))
                    ->badge()
                    ->color('primary'),
                Tables\Columns\TextColumn::make('unit')
                    ->label('Unit'),
            ])
            ->paginated([5, 10])
            ->emptyStateHeading('Tidak ada produk yang perlu di-reorder')
            ->emptyStateDescription('Semua produk dengan parameter EOQ masih di atas reorder point.');
    }

    /**
     * Filter koleksi agar hanya menampilkan produk yang stoknya
     * sudah mencapai atau di bawah reorder point.
     */
    public function getTableRecords(): Collection
    {
        $service = app(EoqService::class);

        return parent::getTableRecords()->filter(
            fn (Product $product): bool => $product->stock <= $service->calculateReorderPoint($product)
        );
    }
}
