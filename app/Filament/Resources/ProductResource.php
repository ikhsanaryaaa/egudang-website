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
                            ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, ?string $state) {
                                $set('sku', app(ProductService::class)->generateSku(
                                    $state ? (int) $state : null,
                                    $get('name'),
                                    $get('unit'),
                                ));
                            }),
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, ?string $state) {
                                $set('sku', app(ProductService::class)->generateSku(
                                    $get('category_id') ? (int) $get('category_id') : null,
                                    $state,
                                    $get('unit'),
                                ));
                            }),
                        Forms\Components\TextInput::make('unit')
                            ->required()
                            ->placeholder('e.g. Pcs, Box, Kg')
                            ->maxLength(50)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, ?string $state) {
                                $set('sku', app(ProductService::class)->generateSku(
                                    $get('category_id') ? (int) $get('category_id') : null,
                                    $get('name'),
                                    $state,
                                ));
                            }),
                        Forms\Components\TextInput::make('sku')
                            ->label('SKU')
                            ->readonly()
                            ->required()
                            ->unique(ignoreRecord: true),
                        Forms\Components\TextInput::make('barcode')
                            ->maxLength(255),
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
                            ->directory('products')
                            ->preserveFilenames(),
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
                            ->preserveFilenames()
                            ->acceptedFileTypes(AttachmentService::getAcceptedFileTypes())
                            ->maxSize(10240)
                            ->maxFiles(5)
                            ->helperText('Maks 10MB per file. Format: PDF, DOC, DOCX, XLS, XLSX, JPG, PNG.'),
                        Forms\Components\Placeholder::make('existing_attachments')
                            ->label('Dokumen Tersimpan')
                            ->content(function (?Product $record) {
                                if (!$record || $record->attachments->isEmpty()) {
                                    return 'Belum ada dokumen.';
                                }
                                $links = $record->attachments->map(function ($att) {
                                    $url = Storage::url($att->file_path);
                                    return '<a href="' . $url . '" target="_blank" style="color:#d97706;text-decoration:underline;">' . $att->file_name . '</a> (' . $att->formatted_size . ')';
                                })->implode('<br>');
                                return new \Illuminate\Support\HtmlString($links);
                            })
                            ->visible(fn (?Product $record) => $record !== null),
                    ]),

                Forms\Components\Section::make('QR Code & Barcode')
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
                        Forms\Components\Placeholder::make('barcode_preview')
                            ->label('Barcode Preview')
                            ->content(function (?Product $record) {
                                if (!$record || !$record->barcode_image_path) {
                                    return 'Barcode akan dibuat otomatis jika field barcode diisi.';
                                }
                                $url = Storage::url($record->barcode_image_path);
                                return new \Illuminate\Support\HtmlString(
                                    '<img src="' . $url . '" alt="Barcode" style="height: 80px;" />'
                                );
                            }),
                    ])
                    ->columns(2)
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
                Tables\Columns\ImageColumn::make('barcode_image_path')
                    ->label('Barcode')
                    ->disk('public')
                    ->width(80)
                    ->height(40),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->relationship('category', 'name'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
