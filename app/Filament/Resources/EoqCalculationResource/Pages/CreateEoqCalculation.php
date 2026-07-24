<?php

namespace App\Filament\Resources\EoqCalculationResource\Pages;

use App\Filament\Resources\EoqCalculationResource;
use App\Services\EoqService;
use Filament\Resources\Pages\CreateRecord;

class CreateEoqCalculation extends CreateRecord
{
    protected static string $resource = EoqCalculationResource::class;

    /**
     * Recompute hasil EOQ di server-side dan set created_by sebelum simpan.
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data = EoqCalculationResource::normalizePeriodData($data);
        $result = app(EoqService::class)->calculateAll($data);

        $data['eoq'] = $result['eoq'];
        $data['rop'] = $result['rop'];
        $data['order_frequency'] = $result['order_frequency'];
        $data['total_cost'] = $result['total_cost'];
        $data['created_by'] = auth()->id();

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
