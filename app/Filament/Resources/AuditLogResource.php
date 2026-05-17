<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AuditLogResource\Pages;
use App\Models\AuditLog;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AuditLogResource extends Resource
{
    protected static ?string $model = AuditLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'Audit Log';

    protected static ?string $navigationGroup = 'System';

    protected static ?int $navigationSort = 100;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Audit Log Detail')
                    ->schema([
                        Forms\Components\TextInput::make('id')
                            ->label('Log ID')
                            ->disabled(),
                        Forms\Components\TextInput::make('user.name')
                            ->label('User')
                            ->disabled()
                            ->default('System'),
                        Forms\Components\TextInput::make('module')
                            ->label('Module')
                            ->disabled(),
                        Forms\Components\TextInput::make('action')
                            ->label('Action')
                            ->disabled(),
                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->disabled()
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('created_at')
                            ->label('Timestamp')
                            ->disabled(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Time')
                    ->dateTime('d M Y H:i:s')
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('User')
                    ->default('System')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('module')
                    ->label('Module')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Auth' => 'gray',
                        'Product' => 'info',
                        'Category' => 'info',
                        'Stock' => 'success',
                        'File' => 'warning',
                        default => 'gray',
                    })
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('action')
                    ->label('Action')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'Barang Masuk' => 'Stock In',
                        'Barang Keluar' => 'Stock Out',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'Login' => 'gray',
                        'Logout' => 'gray',
                        'Create Data' => 'success',
                        'Update Data' => 'warning',
                        'Delete Data' => 'danger',
                        'Barang Masuk', 'Stock In' => 'success',
                        'Barang Keluar', 'Stock Out' => 'danger',
                        'Upload File' => 'info',
                        'Delete File' => 'danger',
                        default => 'gray',
                    })
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('description')
                    ->label('Description')
                    ->wrap()
                    ->limit(80)
                    ->searchable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('module')
                    ->label('Module')
                    ->options([
                        'Auth' => 'Auth',
                        'Product' => 'Product',
                        'Category' => 'Category',
                        'Stock' => 'Stock',
                        'File' => 'File',
                    ]),
                Tables\Filters\SelectFilter::make('action')
                    ->label('Action')
                    ->options([
                        'Login' => 'Login',
                        'Logout' => 'Logout',
                        'Create Data' => 'Create Data',
                        'Update Data' => 'Update Data',
                        'Delete Data' => 'Delete Data',
                        'Barang Masuk' => 'Stock In',
                        'Barang Keluar' => 'Stock Out',
                        'Upload File' => 'Upload File',
                        'Delete File' => 'Delete File',
                    ]),
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('From Date'),
                        Forms\Components\DatePicker::make('until')
                            ->label('Until Date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['from'] ?? null) {
                            $indicators['from'] = 'From: ' . \Carbon\Carbon::parse($data['from'])->format('d M Y');
                        }
                        if ($data['until'] ?? null) {
                            $indicators['until'] = 'Until: ' . \Carbon\Carbon::parse($data['until'])->format('d M Y');
                        }
                        return $indicators;
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                // No bulk actions — audit logs are read-only
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAuditLogs::route('/'),
            'view' => Pages\ViewAuditLog::route('/{record}'),
        ];
    }

    /**
     * Disable create button — audit logs are system-generated only.
     */
    public static function canCreate(): bool
    {
        return false;
    }
}
