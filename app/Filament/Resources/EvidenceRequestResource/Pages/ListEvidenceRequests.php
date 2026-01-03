<?php

namespace App\Filament\Resources\EvidenceRequestResource\Pages;

use App\Filament\Resources\EvidenceRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEvidenceRequests extends ListRecords
{
    protected static string $resource = EvidenceRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
