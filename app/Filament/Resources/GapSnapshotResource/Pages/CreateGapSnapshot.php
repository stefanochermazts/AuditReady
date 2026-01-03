<?php

namespace App\Filament\Resources\GapSnapshotResource\Pages;

use App\Filament\Resources\GapSnapshotResource;
use Filament\Resources\Pages\CreateRecord;

class CreateGapSnapshot extends CreateRecord
{
    protected static string $resource = GapSnapshotResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
