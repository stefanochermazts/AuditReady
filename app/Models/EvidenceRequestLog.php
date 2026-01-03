<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EvidenceRequestLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'evidence_request_id',
        'action',
        'ip_address',
        'user_agent',
        'metadata',
        'created_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Get the evidence request this log belongs to.
     */
    public function evidenceRequest(): BelongsTo
    {
        return $this->belongsTo(EvidenceRequest::class);
    }
}
