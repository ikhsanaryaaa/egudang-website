<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EoqCalculationResource\Pages;
use App\Models\EoqCalculation;
use App\Models\Product;
use App\Services\EoqService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class EoqCalculationResource extends Resource
{
    protected static ?string $model = EoqCalculation::class;

    protected static ?string $navigationIcon = 'heroicon-o-calculator';

    protected static ?string $navigationGroup = 'Perhitungan';

    protected static ?string $navigationLabel = 'Perhitungan EOQ';

    protected static ?string $pluralModelLabel = 'Perhitungan EOQ';

    protected static ?string $modelLabel = 'Perhitungan EOQ';

    protected static ?int $navigationSort = 4;

    /**
     * Auto-fill default biaya produk + recalculate hasil EOQ saat input berubah.
     */
    protected static function recalculate(Forms\Get $get, Forms\Set $set): void
    {
        $result = app(EoqService::class)->calculateAll([
            'demand' => (int) $get('demand'),
            'ordering_cost' => (float) $get('ordering_cost'),
            'holding_cost' => (float) $get('holding_cost'),
            'lead_time_days' => (int) $get('lead_time_days'),
            'period_type' => $get('period_type') ?? 'bulanan',
        ]);

        $set('eoq', $result['eoq']);
        $set('rop', $result['rop']);
        $set('order_frequency', $result['order_frequency']);
        $set('total_cost', $result['total_cost']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Data Perhitungan')
                    ->schema([
                        Forms\Components\Select::make('product_id')
                            ->label('Barang')
                            ->relationship('product', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                $product = Product::find($state);
                                if ($product) {
                                    $set('ordering_cost', (float) $product->ordering_cost);
                                    $set('holding_cost', (float) $product->holding_cost);
                                    $set('lead_time_days', (int) $product->lead_time_days);
                                }
                                self::recalculate($get, $set);
                            }),
                        Forms\Components\DatePicker::make('calculation_date')
                            ->label('Tanggal')
                            ->required()
                            ->default(now()),
                        Forms\Components\Select::make('period_type')
                            ->label('Basis Periode')
                            ->options([
                                'bulanan' => 'Bulanan',
                                'tahunan' => 'Tahunan',
                            ])
                            ->default('bulanan')
                            ->required()
                            ->native(false)
                            ->live()
                            ->afterStateUpdated(fn (Forms\Get $get, Forms\Set $set) => self::recalculate($get, $set)),
                        Forms\Components\TextInput::make('period_label')
                            ->label('Periode')
                            ->placeholder('Contoh: Januari 2026 atau 2026')
                            ->required()
                            ->maxLength(255),
                    ])->columns(2),

                Forms\Components\Section::make('Parameter EOQ')
                    ->description('Biaya otomatis terisi dari default Barang dan dapat diubah per perhitungan.')
                    ->schema([
                        Forms\Components\TextInput::make('demand')
                            ->label('Permintaan')
                            ->helperText('Total permintaan dalam basis periode terpilih.')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->default(0)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Forms\Get $get, Forms\Set $set) => self::recalculate($get, $set)),
                        Forms\Components\TextInput::make('ordering_cost')
                            ->label('Biaya Pemesanan')
                            ->prefix('Rp')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->default(0)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Forms\Get $get, Forms\Set $set) => self::recalculate($get, $set)),
                        Forms\Components\TextInput::make('holding_cost')
                            ->label('Biaya Penyimpanan')
                            ->helperText('Biaya simpan per unit (tahunan).')
                            ->prefix('Rp')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->default(0)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Forms\Get $get, Forms\Set $set) => self::recalculate($get, $set)),
                        Forms\Components\TextInput::make('lead_time_days')
                            ->label('Lead Time')
                            ->suffix('hari')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->default(0)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Forms\Get $get, Forms\Set $set) => self::recalculate($get, $set)),
                    ])->columns(2),

                Forms\Components\Section::make('Hasil Perhitungan')
                    ->schema([
                        Forms\Components\TextInput::make('eoq')
                            ->label('EOQ')
                            ->readOnly()
                            ->numeric(),
                        Forms\Components\TextInput::make('rop')
                            ->label('Reorder Point (ROP)')
                            ->readOnly()
                            ->numeric(),
                        Forms\Components\TextInput::make('order_frequency')
                            ->label('Frekuensi Pemesanan')
                            ->readOnly()
                            ->numeric(),
                        Forms\Components\TextInput::make('total_cost')
                            ->label('Total Biaya')
                            ->prefix('Rp')
                            ->readOnly()
                            ->numeric(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('calculation_date')
                    ->label('Tanggal')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('period_label')
                    ->label('Periode')
                    ->searchable(),
                Tables\Columns\TextColumn::make('period_type')
                    ->label('Basis')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->color(fn (string $state): string => $state === 'tahunan' ? 'info' : 'gray'),
                Tables\Columns\TextColumn::make('product.name')
                    ->label('Barang')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('demand')
                    ->label('Permintaan')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('eoq')
                    ->label('EOQ')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('rop')
                    ->label('ROP')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_cost')
                    ->label('Total Biaya')
                    ->money('IDR')
                    ->sortable(),
            ])
            ->defaultSort('calculation_date', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('product_id')
                    ->label('Barang')
                    ->relationship('product', 'name'),
                Tables\Filters\SelectFilter::make('period_type')
                    ->label('Basis Periode')
                    ->options([
                        'bulanan' => 'Bulanan',
                        'tahunan' => 'Tahunan',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEoqCalculations::route('/'),
            'create' => Pages\CreateEoqCalculation::route('/create'),
            'view' => Pages\ViewEoqCalculation::route('/{record}'),
            'edit' => Pages\EditEoqCalculation::route('/{record}/edit'),
        ];
    }
}
