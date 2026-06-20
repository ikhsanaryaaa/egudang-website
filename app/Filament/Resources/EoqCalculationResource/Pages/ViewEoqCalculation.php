<?php

namespace App\Filament\Resources\EoqCalculationResource\Pages;

use App\Filament\Resources\EoqCalculationResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewEoqCalculation extends ViewRecord
{
    protected static string $resource = EoqCalculationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
