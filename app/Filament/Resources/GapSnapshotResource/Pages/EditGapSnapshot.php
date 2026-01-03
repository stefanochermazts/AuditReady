<?php

namespace App\Filament\Resources\GapSnapshotResource\Pages;

use App\Filament\Resources\GapSnapshotResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditGapSnapshot extends EditRecord
{
    protected static string $resource = GapSnapshotResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
