<?php

namespace App\Observers;

use App\Models\Control;
use App\Services\AuditService;
use Illuminate\Support\Facades\Log;

/**
 * Control Observer - Invalidates graph cache when control is modified or attached/detached from audits.
 */
class ControlObserver
{
    public function __construct(
        private AuditService $auditService
    ) {
    }

    /**
     * Handle the Control "updated" event.
     */
    public function updated(Control $control): void
    {
        $this->invalidateRelatedAudits($control);
    }

    /**
     * Handle the Control "deleted" event.
     */
    public function deleted(Control $control): void
    {
        $this->invalidateRelatedAudits($control);
    }

    /**
     * Handle the Control "restored" event.
     */
    public function restored(Control $control): void
    {
        $this->invalidateRelatedAudits($control);
    }

    /**
     * Handle the Control "pivotAttached" event (when control is attached to audit).
     */
    public function pivotAttached(Control $control, string $relationName, array $pivotIds, array $pivotIdsAttributes): void
    {
        if ($relationName === 'audits') {
            $this->invalidateRelatedAudits($control);
        }
    }

    /**
     * Handle the Control "pivotDetached" event (when control is detached from audit).
     */
    public function pivotDetached(Control $control, string $relationName, array $pivotIds): void
    {
        if ($relationName === 'audits') {
            $this->invalidateRelatedAudits($control);
        }
    }

    /**
     * Invalidate graph cache for all audits related to this control.
     */
    private function invalidateRelatedAudits(Control $control): void
    {
        try {
            // Get all audits that have this control attached
            $audits = $control->audits;

            foreach ($audits as $audit) {
                try {
                    $this->auditService->invalidateGraphCache($audit);
                } catch (\Exception $e) {
                    Log::warning("Failed to invalidate graph cache for audit {$audit->id}", [
                        'tenant_id' => tenant('id'),
                        'audit_id' => $audit->id,
                        'control_id' => $control->id,
                        'exception' => $e->getMessage(),
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::warning("Failed to get related audits for control {$control->id}", [
                'tenant_id' => tenant('id'),
                'control_id' => $control->id,
                'exception' => $e->getMessage(),
            ]);
        }
    }
}
