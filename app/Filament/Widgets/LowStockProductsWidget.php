<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LowStockProductsWidget extends BaseWidget
{
    protected static ?string $heading = 'Low Stock Products';

    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Product::query()
                    ->whereColumn('stock', '<=', 'minimum_stock')
                    ->orderBy('stock', 'asc')
            )
            ->columns([
                Tables\Columns\TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Product Name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('category.name')
                    ->label('Category')
                    ->badge(),
                Tables\Columns\TextColumn::make('stock')
                    ->label('Stock')
                    ->numeric()
                    ->color('danger'),
                Tables\Columns\TextColumn::make('minimum_stock')
                    ->label('Minimum Stock')
                    ->numeric(),
                Tables\Columns\TextColumn::make('unit')
                    ->label('Unit'),
            ])
            ->paginated([5]);
    }
}
