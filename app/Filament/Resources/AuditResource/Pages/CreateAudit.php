<?php

namespace App\Filament\Resources\AuditResource\Pages;

use App\Filament\Resources\AuditResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAudit extends CreateRecord
{
    protected static string $resource = AuditResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();
        
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
