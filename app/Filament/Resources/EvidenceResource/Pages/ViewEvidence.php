<?php

namespace App\Filament\Resources\EvidenceResource\Pages;

use App\Filament\Resources\EvidenceResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewEvidence extends ViewRecord
{
    protected static string $resource = EvidenceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
