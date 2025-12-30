<?php

namespace App\Filament\Resources\EvidenceResource\Pages;

use App\Filament\Resources\EvidenceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEvidence extends EditRecord
{
    protected static string $resource = EvidenceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
