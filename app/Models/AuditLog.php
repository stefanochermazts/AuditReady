<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * AuditLog Model - Immutable Audit Trail
 * 
 * This model represents an immutable audit log entry. No updates or deletes
 * should be performed on this model - it's append-only.
 */
class AuditLog extends Model
{
    public $timestamps = false; // Only created_at, no updated_at
    
    protected $fillable = [
        'tenant_id',
        'user_id',
        'action',
        'model_type',
        'model_id',
        'payload',
        'ip_address',
        'user_agent',
        'signature',
        'created_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Get the user who performed the action
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the model instance that was audited
     */
    public function model()
    {
        return $this->morphTo('model');
    }

    /**
     * Prevent updates
     */
    public function update(array $attributes = [], array $options = [])
    {
        throw new \RuntimeException('AuditLog entries are immutable and cannot be updated');
    }

    /**
     * Prevent deletes
     */
    public function delete()
    {
        throw new \RuntimeException('AuditLog entries are immutable and cannot be deleted');
    }
}
