<?php

namespace App\Filament\Resources\EvidenceRequestResource\Pages;

use App\Filament\Resources\EvidenceRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEvidenceRequest extends EditRecord
{
    protected static string $resource = EvidenceRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
