<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Control;

class GapSnapshot extends Model
{
    protected $fillable = [
        'audit_id',
        'name',
        'standard',
        'completed_by',
        'completed_at',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
        'standard' => 'string',
    ];

    /**
     * Get the audit this snapshot belongs to (optional).
     */
    public function audit(): BelongsTo
    {
        return $this->belongsTo(Audit::class);
    }

    /**
     * Get the user who completed this snapshot.
     */
    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    /**
     * Get the responses for this snapshot.
     */
    public function responses(): HasMany
    {
        return $this->hasMany(GapSnapshotResponse::class);
    }

    /**
     * Check if snapshot is completed.
     */
    public function isCompleted(): bool
    {
        return $this->completed_at !== null;
    }

    /**
     * Get completion percentage.
     */
    public function getCompletionPercentage(): float
    {
        $totalControls = Control::where(function ($query) {
            if ($this->standard === 'DORA') {
                $query->where('standard', 'DORA');
            } elseif ($this->standard === 'NIS2') {
                $query->where('standard', 'NIS2');
            }
            // 'both' means all controls
        })->count();

        if ($totalControls === 0) {
            return 0;
        }

        $answeredControls = $this->responses()->count();
        return round(($answeredControls / $totalControls) * 100, 2);
    }
}
