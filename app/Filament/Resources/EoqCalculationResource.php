<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EoqCalculationResource\Pages;
use App\Models\EoqCalculation;
use App\Models\Product;
use App\Services\EoqService;
use Carbon\CarbonImmutable;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class EoqCalculationResource extends Resource
{
    protected static ?string $model = EoqCalculation::class;

    protected static ?string $navigationIcon = 'heroicon-o-calculator';

    protected static ?string $navigationGroup = 'Calculation';

    protected static ?string $navigationLabel = 'EOQ Calculation';

    protected static ?string $pluralModelLabel = 'EOQ Calculations';

    protected static ?string $modelLabel = 'EOQ Calculation';

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
            'period_start' => $get('period_start'),
            'period_end' => $get('period_end'),
        ]);

        $set('eoq', $result['eoq']);
        $set('rop', $result['rop']);
        $set('order_frequency', $result['order_frequency']);
        $set('total_cost', $result['total_cost']);
    }

    public static function normalizePeriodData(array $data): array
    {
        if (($data['period_type'] ?? 'bulanan') !== 'custom') {
            $data['period_start'] = null;
            $data['period_end'] = null;

            return $data;
        }

        $data['period_label'] = self::formatCustomPeriodLabel(
            $data['period_start'] ?? null,
            $data['period_end'] ?? null,
        );

        return $data;
    }

    protected static function syncCustomPeriodLabel(Forms\Get $get, Forms\Set $set): void
    {
        if ($get('period_type') !== 'custom') {
            return;
        }

        $set('period_label', self::formatCustomPeriodLabel(
            $get('period_start'),
            $get('period_end'),
        ));
    }

    protected static function formatCustomPeriodLabel(?string $periodStart, ?string $periodEnd): string
    {
        if (! $periodStart || ! $periodEnd) {
            return '';
        }

        return CarbonImmutable::parse($periodStart)->format('d M Y')
            .' - '
            .CarbonImmutable::parse($periodEnd)->format('d M Y');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Calculation Data')
                    ->schema([
                        Forms\Components\Select::make('product_id')
                            ->label('Product')
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
                            ->label('Date')
                            ->required()
                            ->default(now()),
                        Forms\Components\Select::make('period_type')
                            ->label('Period Basis')
                            ->options([
                                'bulanan' => 'Monthly',
                                'tahunan' => 'Yearly',
                                'custom' => 'Custom',
                            ])
                            ->default('bulanan')
                            ->required()
                            ->native(false)
                            ->live()
                            ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set): void {
                                $set('period_label', null);
                                if ($get('period_type') !== 'custom') {
                                    $set('period_start', null);
                                    $set('period_end', null);
                                }
                                self::syncCustomPeriodLabel($get, $set);
                                self::recalculate($get, $set);
                            }),
                        Forms\Components\TextInput::make('period_label')
                            ->label('Period')
                            ->placeholder('e.g. January 2026 or 2026')
                            ->required(fn (Forms\Get $get): bool => $get('period_type') !== 'custom')
                            ->hidden(fn (Forms\Get $get): bool => $get('period_type') === 'custom')
                            ->maxLength(255),
                        Forms\Components\DatePicker::make('period_start')
                            ->label('Period Start')
                            ->visible(fn (Forms\Get $get): bool => $get('period_type') === 'custom')
                            ->required(fn (Forms\Get $get): bool => $get('period_type') === 'custom')
                            ->live()
                            ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set): void {
                                self::syncCustomPeriodLabel($get, $set);
                                self::recalculate($get, $set);
                            }),
                        Forms\Components\DatePicker::make('period_end')
                            ->label('Period End')
                            ->visible(fn (Forms\Get $get): bool => $get('period_type') === 'custom')
                            ->required(fn (Forms\Get $get): bool => $get('period_type') === 'custom')
                            ->afterOrEqual('period_start')
                            ->live()
                            ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set): void {
                                self::syncCustomPeriodLabel($get, $set);
                                self::recalculate($get, $set);
                            }),
                    ])->columns(2),

                Forms\Components\Section::make('EOQ Parameters')
                    ->description('Costs are auto-filled from the product defaults and can be adjusted per calculation.')
                    ->schema([
                        Forms\Components\TextInput::make('demand')
                            ->label('Demand')
                            ->helperText('Annual demand, prorated to the selected period basis.')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->default(0)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Forms\Get $get, Forms\Set $set) => self::recalculate($get, $set)),
                        Forms\Components\TextInput::make('ordering_cost')
                            ->label('Ordering Cost')
                            ->prefix('Rp')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->default(0)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Forms\Get $get, Forms\Set $set) => self::recalculate($get, $set)),
                        Forms\Components\TextInput::make('holding_cost')
                            ->label('Holding Cost')
                            ->helperText('Storage cost per unit (yearly).')
                            ->prefix('Rp')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->default(0)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Forms\Get $get, Forms\Set $set) => self::recalculate($get, $set)),
                        Forms\Components\TextInput::make('lead_time_days')
                            ->label('Lead Time')
                            ->suffix('days')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->default(0)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Forms\Get $get, Forms\Set $set) => self::recalculate($get, $set)),
                    ])->columns(2),

                Forms\Components\Section::make('Calculation Results')
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
                            ->label('Order Frequency')
                            ->readOnly()
                            ->numeric(),
                        Forms\Components\TextInput::make('total_cost')
                            ->label('Total Cost')
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
                    ->label('Date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('period_label')
                    ->label('Period')
                    ->searchable(),
                Tables\Columns\TextColumn::make('period_type')
                    ->label('Basis')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->color(fn (string $state): string => match ($state) {
                        'tahunan' => 'info',
                        'custom' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('product.name')
                    ->label('Product')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('demand')
                    ->label('Demand')
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
                    ->label('Total Cost')
                    ->money('IDR')
                    ->sortable(),
            ])
            ->defaultSort('calculation_date', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('product_id')
                    ->label('Product')
                    ->relationship('product', 'name'),
                Tables\Filters\SelectFilter::make('period_type')
                    ->label('Period Basis')
                    ->options([
                        'bulanan' => 'Monthly',
                        'tahunan' => 'Yearly',
                        'custom' => 'Custom',
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
