<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Audit extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'status',
        'audit_type',
        'compliance_standards',
        'scope',
        'objectives',
        'start_date',
        'end_date',
        'closed_at',
        'created_by',
        'auditor_id',
        'reference_period_start',
        'reference_period_end',
        'findings',
        'corrective_actions',
        'risk_assessment',
        'gdpr_article_reference',
        'dora_requirement_reference',
        'nis2_requirement_reference',
        'certification_body',
        'certification_number',
        'next_audit_date',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'closed_at' => 'datetime',
        'reference_period_start' => 'date',
        'reference_period_end' => 'date',
        'next_audit_date' => 'date',
        'compliance_standards' => 'array',
        'findings' => 'array',
        'corrective_actions' => 'array',
        'risk_assessment' => 'array',
    ];

    /**
     * Get the user who created the audit.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the auditor responsible for the audit.
     */
    public function auditor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'auditor_id');
    }

    /**
     * Get the evidences for the audit.
     */
    public function evidences(): HasMany
    {
        return $this->hasMany(Evidence::class);
    }
}
