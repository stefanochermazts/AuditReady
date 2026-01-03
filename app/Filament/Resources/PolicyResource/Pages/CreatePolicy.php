<?php

namespace App\Filament\Resources\PolicyResource\Pages;

use App\Filament\Resources\PolicyResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePolicy extends CreateRecord
{
    protected static string $resource = PolicyResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Validate that at least one of evidence_id or internal_link is provided
        if (empty($data['evidence_id']) && empty($data['internal_link'])) {
            throw new \Filament\Support\Exceptions\Halt();
        }

        return $data;
    }
}
