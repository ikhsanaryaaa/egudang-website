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
use App\Services\AttachmentService;
use Illuminate\Support\Facades\Storage;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';

    protected static ?string $pluralModelLabel = 'Products';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Product Information')
                    ->schema([
                        Forms\Components\Select::make('category_id')
                            ->label('Category')
                            ->relationship('category', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, ?string $state) {
                                $set('sku', app(ProductService::class)->generateSku(
                                    $state ? (int) $state : null,
                                    $get('name'),
                                    $get('brand'),
                                    $get('unit'),
                                ));
                            }),
                        Forms\Components\TextInput::make('name')
                            ->label('Name')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, ?string $state) {
                                $set('sku', app(ProductService::class)->generateSku(
                                    $get('category_id') ? (int) $get('category_id') : null,
                                    $state,
                                    $get('brand'),
                                    $get('unit'),
                                ));
                            }),
                        Forms\Components\TextInput::make('brand')
                            ->label('Brand')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, ?string $state) {
                                $set('sku', app(ProductService::class)->generateSku(
                                    $get('category_id') ? (int) $get('category_id') : null,
                                    $get('name'),
                                    $state,
                                    $get('unit'),
                                ));
                            }),
                        Forms\Components\TextInput::make('unit')
                            ->label('Unit')
                            ->required()
                            ->placeholder('e.g. Pcs, Box, Kg')
                            ->maxLength(50)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, ?string $state) {
                                $set('sku', app(ProductService::class)->generateSku(
                                    $get('category_id') ? (int) $get('category_id') : null,
                                    $get('name'),
                                    $get('brand'),
                                    $state,
                                ));
                            }),
                        Forms\Components\TextInput::make('sku')
                            ->label('SKU')
                            ->readonly()
                            ->required()
                            ->unique(ignoreRecord: true),
                    ])->columns(2),

                Forms\Components\Section::make('Inventory Details')
                    ->schema([
                        Forms\Components\TextInput::make('stock')
                            ->label('Stock')
                            ->numeric()
                            ->default(0)
                            ->required()
                            ->minValue(0),
                        Forms\Components\TextInput::make('minimum_stock')
                            ->label('Minimum Stock')
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
                            ->helperText('Max 10MB per file. Formats: PDF, DOC, DOCX, XLS, XLSX, JPG, PNG.'),
                        Forms\Components\Placeholder::make('existing_attachments')
                            ->label('Saved Documents')
                            ->content(function (?Product $record) {
                                if (!$record || $record->attachments->isEmpty()) {
                                    return 'No documents saved.';
                                }
                                $links = $record->attachments->map(function ($att) {
                                    $url = Storage::url($att->file_path);
                                    return '<a href="' . $url . '" target="_blank" style="color:#d97706;text-decoration:underline;">' . $att->file_name . '</a> (' . $att->formatted_size . ')';
                                })->implode('<br>');
                                return new \Illuminate\Support\HtmlString($links);
                            })
                            ->visible(fn (?Product $record) => $record !== null),
                    ]),
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
                    ->label('Name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('category.name')
                    ->label('Category')
                    ->badge()
                    ->sortable()
                    ->visibleFrom('md'),
                Tables\Columns\TextColumn::make('stock')
                    ->label('Stock')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('unit')
                    ->label('Unit')
                    ->sortable()
                    ->visibleFrom('md'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->label('Category')
                    ->relationship('category', 'name'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->iconButton(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
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
