<?php

namespace App\Filament\Resources\EvidenceRequestResource\Pages;

use App\Filament\Resources\EvidenceRequestResource;
use App\Services\EvidenceRequestService;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewEvidenceRequest extends ViewRecord
{
    protected static string $resource = EvidenceRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('view_public_link')
                ->label('View Upload Link')
                ->icon('heroicon-o-link')
                ->color('info')
                ->url(fn () => app(EvidenceRequestService::class)->generatePublicUrl($this->record), shouldOpenInNewTab: true)
                ->visible(fn () => $this->record->isPending() && !$this->record->isExpired()),
            Actions\EditAction::make(),
        ];
    }
}
