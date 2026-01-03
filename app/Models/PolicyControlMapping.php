<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class PolicyControlMapping extends Pivot
{
    protected $table = 'policy_control_mappings';

    protected $fillable = [
        'policy_id',
        'control_id',
        'coverage_notes',
        'mapped_by',
    ];

    /**
     * Get the policy that this mapping belongs to.
     */
    public function policy(): BelongsTo
    {
        return $this->belongsTo(Policy::class);
    }

    /**
     * Get the control that this mapping belongs to.
     */
    public function control(): BelongsTo
    {
        return $this->belongsTo(Control::class);
    }

    /**
     * Get the user who created this mapping.
     */
    public function mappedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mapped_by');
    }
}
