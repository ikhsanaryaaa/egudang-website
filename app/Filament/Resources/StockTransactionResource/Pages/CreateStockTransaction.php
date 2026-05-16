<?php

namespace App\Filament\Resources\StockTransactionResource\Pages;

use App\Filament\Resources\StockTransactionResource;
use App\Services\StockService;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateStockTransaction extends CreateRecord
{
    protected static string $resource = StockTransactionResource::class;

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        $stockService = app(StockService::class);

        $items = collect($data['items'] ?? [])->map(function ($item) {
            return [
                'product_id' => $item['product_id'],
                'qty' => (int) $item['qty'],
                'unit_cost' => 0,
            ];
        })->toArray();

        return $stockService->processTransaction(
            $data['type'],
            $items,
            $data['notes'] ?? null,
            auth()->id(),
        );
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
