<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EvidenceRequest extends Model
{
    protected $fillable = [
        'audit_id',
        'control_id',
        'supplier_id',
        'requested_by',
        'public_token',
        'expires_at',
        'status',
        'requested_at',
        'completed_at',
        'message',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'requested_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Get the audit this request is linked to (optional).
     */
    public function audit(): BelongsTo
    {
        return $this->belongsTo(Audit::class);
    }

    /**
     * Get the control this request is for.
     */
    public function control(): BelongsTo
    {
        return $this->belongsTo(Control::class);
    }

    /**
     * Get the supplier this request is sent to.
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(ThirdPartySupplier::class, 'supplier_id');
    }

    /**
     * Get the user who created this request.
     */
    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /**
     * Get all logs for this request.
     */
    public function logs(): HasMany
    {
        return $this->hasMany(EvidenceRequestLog::class);
    }

    /**
     * Check if request is expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Check if request is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if request is pending.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }
}
