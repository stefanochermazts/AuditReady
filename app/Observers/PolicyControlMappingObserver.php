<?php

namespace App\Observers;

use App\Models\PolicyControlMapping;
use App\Services\AuditService;

class PolicyControlMappingObserver
{
    public function created(PolicyControlMapping $mapping): void
    {
        $this->invalidateRelatedAudits($mapping);
    }

    public function updated(PolicyControlMapping $mapping): void
    {
        $this->invalidateRelatedAudits($mapping);
    }

    public function deleted(PolicyControlMapping $mapping): void
    {
        $this->invalidateRelatedAudits($mapping);
    }

    public function restored(PolicyControlMapping $mapping): void
    {
        $this->invalidateRelatedAudits($mapping);
    }

    private function invalidateRelatedAudits(PolicyControlMapping $mapping): void
    {
        $control = $mapping->control;
        if (! $control) {
            return;
        }

        $auditService = app(AuditService::class);

        foreach ($control->audits()->get() as $audit) {
            $auditService->invalidateGraphCache($audit);
        }
    }
}

