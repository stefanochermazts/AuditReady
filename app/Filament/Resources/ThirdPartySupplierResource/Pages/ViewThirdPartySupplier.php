<?php

namespace App\Filament\Resources\ThirdPartySupplierResource\Pages;

use App\Filament\Resources\ThirdPartySupplierResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewThirdPartySupplier extends ViewRecord
{
    protected static string $resource = ThirdPartySupplierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
