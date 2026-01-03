<?php

namespace App\Filament\Resources\GapSnapshotResource\Pages;

use App\Filament\Resources\GapSnapshotResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListGapSnapshots extends ListRecords
{
    protected static string $resource = GapSnapshotResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
