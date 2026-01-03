<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class GapSnapshotResponse extends Model
{
    protected $fillable = [
        'gap_snapshot_id',
        'control_id',
        'response',
        'notes',
        'evidence_ids',
    ];

    protected $casts = [
        'evidence_ids' => 'array',
        'response' => 'string',
    ];

    /**
     * Get the snapshot this response belongs to.
     */
    public function gapSnapshot(): BelongsTo
    {
        return $this->belongsTo(GapSnapshot::class);
    }

    /**
     * Get the control this response is for.
     */
    public function control(): BelongsTo
    {
        return $this->belongsTo(Control::class);
    }

    /**
     * Get the evidences linked to this response.
     */
    public function evidences(): BelongsToMany
    {
        return $this->belongsToMany(Evidence::class, 'evidence_gap_snapshot_response', 'gap_snapshot_response_id', 'evidence_id');
    }

    /**
     * Check if response has evidence linked.
     */
    public function hasEvidence(): bool
    {
        return !empty($this->evidence_ids) && count($this->evidence_ids) > 0;
    }

    /**
     * Check if response indicates a gap (no or partial).
     */
    public function indicatesGap(): bool
    {
        return in_array($this->response, ['no', 'partial']);
    }
}
