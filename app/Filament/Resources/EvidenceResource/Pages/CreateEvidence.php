<?php

namespace App\Filament\Resources\EvidenceResource\Pages;

use App\Filament\Resources\EvidenceResource;
use Filament\Resources\Pages\CreateRecord;

class CreateEvidence extends CreateRecord
{
    protected static string $resource = EvidenceResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();
        $data['version'] = 1;

        return $data;
    }
}
