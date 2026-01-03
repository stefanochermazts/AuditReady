<?php

namespace App\Filament\Resources\ThirdPartySupplierResource\Pages;

use App\Filament\Resources\ThirdPartySupplierResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditThirdPartySupplier extends EditRecord
{
    protected static string $resource = ThirdPartySupplierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
