<?php

namespace App\Observers;

use App\Models\Evidence;
use App\Services\AuditService;
use Illuminate\Support\Facades\Log;

/**
 * Evidence Observer - Invalidates graph cache when evidence is modified.
 */
class EvidenceObserver
{
    public function __construct(
        private AuditService $auditService
    ) {
    }

    /**
     * Handle the Evidence "created" event.
     */
    public function created(Evidence $evidence): void
    {
        $this->invalidateCache($evidence);
    }

    /**
     * Handle the Evidence "updated" event.
     */
    public function updated(Evidence $evidence): void
    {
        $this->invalidateCache($evidence);
    }

    /**
     * Handle the Evidence "deleted" event.
     */
    public function deleted(Evidence $evidence): void
    {
        $this->invalidateCache($evidence);
    }

    /**
     * Handle the Evidence "restored" event.
     */
    public function restored(Evidence $evidence): void
    {
        $this->invalidateCache($evidence);
    }

    /**
     * Invalidate graph cache for the audit associated with this evidence.
     */
    private function invalidateCache(Evidence $evidence): void
    {
        if ($evidence->audit_id) {
            try {
                $audit = $evidence->audit;
                if ($audit) {
                    $this->auditService->invalidateGraphCache($audit);
                }
            } catch (\Exception $e) {
                Log::warning("Failed to invalidate graph cache for evidence {$evidence->id}", [
                    'tenant_id' => tenant('id'),
                    'evidence_id' => $evidence->id,
                    'audit_id' => $evidence->audit_id,
                    'exception' => $e->getMessage(),
                ]);
            }
        }
    }
}
