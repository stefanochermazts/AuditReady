<?php

namespace App\Observers;

use App\Models\Audit;
use App\Services\AuditLogService;
use App\Services\AuditService;
use Illuminate\Database\Eloquent\Model;

/**
 * Audit Observer - Automatically logs model events
 * 
 * This observer logs all create, update, delete, and restore events
 * for models that implement the Auditable interface or are registered
 * to use this observer.
 */
class AuditObserver
{
    public function __construct(
        private AuditLogService $auditLogService,
        private AuditService $auditService
    ) {
    }

    /**
     * Handle the model "created" event.
     */
    public function created(Model $model): void
    {
        $this->auditLogService->record('created', $model, [
            'attributes' => $model->getAttributes(),
        ]);
    }

    /**
     * Handle the model "updated" event.
     */
    public function updated(Model $model): void
    {
        $this->auditLogService->record('updated', $model, [
            'old' => $model->getOriginal(),
            'new' => $model->getChanges(),
        ]);

        // Invalidate graph cache if this is an Audit model
        if ($model instanceof Audit) {
            $this->auditService->invalidateGraphCache($model);
        }
    }

    /**
     * Handle the model "deleted" event.
     */
    public function deleted(Model $model): void
    {
        $this->auditLogService->record('deleted', $model, [
            'attributes' => $model->getAttributes(),
        ]);

        // Invalidate graph cache if this is an Audit model
        if ($model instanceof Audit) {
            $this->auditService->invalidateGraphCache($model);
        }
    }

    /**
     * Handle the model "restored" event.
     */
    public function restored(Model $model): void
    {
        $this->auditLogService->record('restored', $model, [
            'attributes' => $model->getAttributes(),
        ]);

        // Invalidate graph cache if this is an Audit model
        if ($model instanceof Audit) {
            $this->auditService->invalidateGraphCache($model);
        }
    }
}
