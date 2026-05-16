<?php

namespace App\Filament\Resources\StockTransactionResource\Pages;

use App\Filament\Resources\StockTransactionResource;
use App\Models\Attachment;
use App\Services\StockService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Storage;

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

        $transaction = $stockService->processTransaction(
            $data['type'],
            $items,
            $data['notes'] ?? null,
            auth()->id(),
        );

        // Handle file attachments
        if (!empty($data['attachments_upload'])) {
            foreach ($data['attachments_upload'] as $filePath) {
                $fullPath = Storage::disk('public')->path($filePath);
                $fileName = basename($filePath);
                $extension = pathinfo($fileName, PATHINFO_EXTENSION);
                $fileSize = Storage::disk('public')->size($filePath);

                Attachment::create([
                    'attachable_id' => $transaction->id,
                    'attachable_type' => get_class($transaction),
                    'file_path' => $filePath,
                    'file_name' => $fileName,
                    'file_type' => strtolower($extension),
                    'file_size' => $fileSize,
                    'uploaded_by' => auth()->id(),
                ]);
            }
        }

        return $transaction;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
