<?php

namespace App\Filament\Resources\ThirdPartySupplierResource\Pages;

use App\Filament\Resources\ThirdPartySupplierResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListThirdPartySuppliers extends ListRecords
{
    protected static string $resource = ThirdPartySupplierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
