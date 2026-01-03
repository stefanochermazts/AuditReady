<?php

namespace App\Filament\Resources\EvidenceRequestResource\Pages;

use App\Filament\Resources\EvidenceRequestResource;
use App\Services\EvidenceRequestService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Mail;

class CreateEvidenceRequest extends CreateRecord
{
    protected static string $resource = EvidenceRequestResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $evidenceRequestService = app(EvidenceRequestService::class);
        
        $data['requested_by'] = auth()->id();
        $data['requested_at'] = now();
        
        // Generate token and expiration BEFORE creating the record
        $expirationDays = $data['expiration_days'] ?? 30;
        $data['public_token'] = $evidenceRequestService->generatePublicToken();
        $data['expires_at'] = now()->addDays($expirationDays);
        
        // Store expiration_days for afterCreate (for logging)
        $this->expirationDays = $expirationDays;
        unset($data['expiration_days']);

        return $data;
    }

    protected function afterCreate(): void
    {
        $evidenceRequestService = app(EvidenceRequestService::class);

        // Log creation
        $evidenceRequestService->logAction($this->record, 'created', [
            'requested_by' => $this->record->requested_by,
            'expiration_days' => $this->expirationDays,
        ]);

        // Generate public URL
        $publicUrl = $evidenceRequestService->generatePublicUrl($this->record);

        // Send email to supplier
        \Illuminate\Support\Facades\Mail::to($this->record->supplier->email)
            ->send(new \App\Mail\EvidenceRequestMail($this->record, $publicUrl));

        Notification::make()
            ->title('Evidence request created')
            ->success()
            ->body('The evidence request has been created. The supplier will receive an email with the upload link.')
            ->actions([
                \Filament\Actions\Action::make('view_link')
                    ->label('View Upload Link')
                    ->url($publicUrl, shouldOpenInNewTab: true),
            ])
            ->send();
    }

    protected $expirationDays = 30;
}
