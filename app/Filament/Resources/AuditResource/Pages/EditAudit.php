<?php

namespace App\Filament\Resources\AuditResource\Pages;

use App\Filament\Resources\AuditResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAudit extends EditRecord
{
    protected static string $resource = AuditResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Ensure compliance_standards is always an array
        if (isset($data['compliance_standards']) && !is_array($data['compliance_standards'])) {
            $data['compliance_standards'] = is_string($data['compliance_standards']) 
                ? json_decode($data['compliance_standards'], true) 
                : [];
        }
        if (empty($data['compliance_standards'])) {
            $data['compliance_standards'] = null;
        }

        return $data;
    }
}
