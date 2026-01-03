<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditDayPack extends Model
{
    protected $fillable = [
        'audit_id',
        'generated_by',
        'format',
        'include_all_evidences',
        'include_full_audit_trail',
        'file_path',
        'generated_at',
    ];

    protected $casts = [
        'include_all_evidences' => 'boolean',
        'include_full_audit_trail' => 'boolean',
        'generated_at' => 'datetime',
        'format' => 'string',
    ];

    /**
     * Get the audit that this pack belongs to.
     */
    public function audit(): BelongsTo
    {
        return $this->belongsTo(Audit::class);
    }

    /**
     * Get the user who generated this pack.
     */
    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }
}
