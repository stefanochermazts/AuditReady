<?php

namespace App\Filament\Resources\PolicyResource\Pages;

use App\Filament\Resources\PolicyResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewPolicy extends ViewRecord
{
    protected static string $resource = PolicyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('view_link')
                ->label('View Policy')
                ->icon('heroicon-o-link')
                ->url(fn () => $this->record->getDisplayUrl())
                ->openUrlInNewTab()
                ->visible(fn () => $this->record->getDisplayUrl() !== null),
            Actions\EditAction::make(),
        ];
    }
}
