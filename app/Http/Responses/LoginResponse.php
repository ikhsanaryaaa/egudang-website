<?php

namespace App\Http\Responses;

use App\Filament\Pages\Dashboard;
use App\Filament\Pages\EoqReport;
use App\Filament\Pages\Reports;
use App\Filament\Resources\AuditLogResource;
use App\Filament\Resources\CategoryResource;
use App\Filament\Resources\EoqCalculationResource;
use App\Filament\Resources\ProductResource;
use App\Filament\Resources\RoleResource;
use App\Filament\Resources\StockTransactionResource;
use App\Filament\Resources\UserResource;
use Filament\Http\Responses\Auth\Contracts\LoginResponse as LoginResponseContract;
use Illuminate\Http\RedirectResponse;
use Livewire\Features\SupportRedirects\Redirector;

class LoginResponse implements LoginResponseContract
{
    /**
     * Send a custom role to the first module it is allowed to access.
     */
    public function toResponse($request): RedirectResponse | Redirector
    {
        $destinations = [
            Dashboard::class,
            Reports::class,
            EoqReport::class,
            ProductResource::class,
            CategoryResource::class,
            StockTransactionResource::class,
            EoqCalculationResource::class,
            AuditLogResource::class,
            UserResource::class,
            RoleResource::class,
        ];

        foreach ($destinations as $destination) {
            if ($destination::canAccess()) {
                return redirect()->to($destination::getUrl());
            }
        }

        abort(403, 'This account does not have access to any panel module.');
    }
}
