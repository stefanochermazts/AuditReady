<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Evidence extends Model
{
    use SoftDeletes;

    protected $table = 'evidences';

    protected $fillable = [
        'audit_id',
        'evidence_request_id',
        'uploader_id',
        'filename',
        'category',
        'document_date',
        'document_type',
        'supplier',
        'regulatory_reference',
        'control_reference',
        'mime_type',
        'size',
        'stored_path',
        'checksum',
        'version',
        'encrypted_key',
        'iv',
        'validation_status',
        'validated_by',
        'validated_at',
        'validation_notes',
        'expiry_date',
        'tags',
        'notes',
        'confidentiality_level',
        'retention_period_months',
    ];

    protected $casts = [
        'size' => 'integer',
        'version' => 'integer',
        'document_date' => 'date',
        'expiry_date' => 'date',
        'validated_at' => 'datetime',
        'tags' => 'array',
        'retention_period_months' => 'integer',
    ];

    /**
     * Get the audit that owns the evidence.
     */
    public function audit(): BelongsTo
    {
        return $this->belongsTo(Audit::class);
    }

    /**
     * Get the evidence request (third-party upload) that created this evidence.
     */
    public function evidenceRequest(): BelongsTo
    {
        return $this->belongsTo(EvidenceRequest::class, 'evidence_request_id');
    }

    /**
     * Get the user who uploaded the evidence.
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploader_id');
    }

    /**
     * Get the user who validated the evidence.
     */
    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    /**
     * Get all versions of this evidence (same filename, same audit)
     */
    public function versions()
    {
        return static::where('audit_id', $this->audit_id)
            ->where('filename', $this->filename)
            ->orderBy('version', 'desc')
            ->get();
    }

    /**
     * Get the latest version number for this evidence
     */
    public function getLatestVersion(): int
    {
        return static::where('audit_id', $this->audit_id)
            ->where('filename', $this->filename)
            ->max('version') ?? 0;
    }

    /**
     * Get the policy that uses this evidence (if any).
     */
    public function policy(): BelongsTo
    {
        return $this->belongsTo(Policy::class, 'evidence_id');
    }
}
