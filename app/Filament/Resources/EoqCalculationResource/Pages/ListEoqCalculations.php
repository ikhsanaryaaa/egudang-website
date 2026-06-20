<?php

namespace App\Filament\Resources\EoqCalculationResource\Pages;

use App\Filament\Resources\EoqCalculationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEoqCalculations extends ListRecords
{
    protected static string $resource = EoqCalculationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Entri Data'),
        ];
    }
}
