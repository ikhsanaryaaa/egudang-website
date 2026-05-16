<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

use App\Services\ProductService;
use App\Services\QrService;
use App\Services\AttachmentService;
use Illuminate\Support\Facades\Storage;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Product Information')
                    ->schema([
                        Forms\Components\Select::make('category_id')
                            ->relationship('category', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(fn (Forms\Set $set, ?string $state) => $set('sku', app(ProductService::class)->generateSku($state))),
                        Forms\Components\TextInput::make('sku')
                            ->label('SKU')
                            ->readonly()
                            ->required()
                            ->unique(ignoreRecord: true),
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('barcode')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('unit')
                            ->required()
                            ->placeholder('e.g. Pcs, Box, Kg')
                            ->maxLength(50),
                    ])->columns(2),

                Forms\Components\Section::make('Inventory Details')
                    ->schema([
                        Forms\Components\TextInput::make('stock')
                            ->numeric()
                            ->default(0)
                            ->required()
                            ->minValue(0),
                        Forms\Components\TextInput::make('minimum_stock')
                            ->numeric()
                            ->default(0)
                            ->required()
                            ->minValue(0),
                    ])->columns(2),

                Forms\Components\Section::make('Media & Description')
                    ->schema([
                        Forms\Components\FileUpload::make('image_path')
                            ->label('Product Image')
                            ->image()
                            ->directory('products'),
                        Forms\Components\Textarea::make('description')
                            ->maxLength(65535)
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Documents')
                    ->schema([
                        Forms\Components\FileUpload::make('attachments_upload')
                            ->label('Product Documents')
                            ->multiple()
                            ->directory('attachments/product')
                            ->disk('public')
                            ->acceptedFileTypes(AttachmentService::getAcceptedFileTypes())
                            ->maxSize(10240)
                            ->maxFiles(5)
                            ->helperText('Maks 10MB per file. Format: PDF, DOC, DOCX, XLS, XLSX, JPG, PNG.'),
                    ]),

                Forms\Components\Section::make('QR Code')
                    ->schema([
                        Forms\Components\Placeholder::make('qr_preview')
                            ->label('QR Code Preview')
                            ->content(function (?Product $record) {
                                if (!$record || !$record->qr_code_path) {
                                    return 'QR Code akan dibuat otomatis setelah produk disimpan.';
                                }
                                $url = Storage::url($record->qr_code_path);
                                return new \Illuminate\Support\HtmlString(
                                    '<img src="' . $url . '" alt="QR Code" style="width: 200px; height: 200px;" />'
                                );
                            }),
                    ])
                    ->visible(fn (?Product $record) => $record !== null),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image_path')
                    ->label('Image')
                    ->circular(),
                Tables\Columns\TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('category.name')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('stock')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('unit')
                    ->sortable(),
                Tables\Columns\ImageColumn::make('qr_code_path')
                    ->label('QR')
                    ->disk('public')
                    ->width(40)
                    ->height(40),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->relationship('category', 'name'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('downloadQr')
                    ->label('QR')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->visible(fn (Product $record) => $record->qr_code_path !== null)
                    ->action(function (Product $record) {
                        $path = Storage::disk('public')->path($record->qr_code_path);
                        return response()->download($path, 'qr-' . $record->sku . '.png');
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
