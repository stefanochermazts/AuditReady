<?php

namespace App\Filament\Resources\GapSnapshotResource\Pages;

use App\Filament\Resources\GapSnapshotResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewGapSnapshot extends ViewRecord
{
    protected static string $resource = GapSnapshotResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\Action::make('complete_wizard')
                ->label('Complete Wizard')
                ->icon('heroicon-o-pencil-square')
                ->url(fn () => \App\Filament\Pages\GapSnapshotWizard::getUrl(['snapshot' => $this->record->id]))
                ->visible(fn () => !$this->record->isCompleted()),
            Actions\Action::make('view_report')
                ->label('View Report')
                ->icon('heroicon-o-document-text')
                ->url(fn () => \App\Filament\Pages\ViewGapSnapshotReport::getUrl(['snapshot' => $this->record->id]))
                ->visible(fn () => $this->record->isCompleted()),
        ];
    }
}
