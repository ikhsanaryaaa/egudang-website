<?php

namespace App\Filament\Resources\EoqCalculationResource\Pages;

use App\Filament\Resources\EoqCalculationResource;
use App\Services\EoqService;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions;

class EditEoqCalculation extends EditRecord
{
    protected static string $resource = EoqCalculationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    /**
     * Recompute hasil EOQ di server-side sebelum update.
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $result = app(EoqService::class)->calculateAll($data);

        $data['eoq'] = $result['eoq'];
        $data['rop'] = $result['rop'];
        $data['order_frequency'] = $result['order_frequency'];
        $data['total_cost'] = $result['total_cost'];

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
