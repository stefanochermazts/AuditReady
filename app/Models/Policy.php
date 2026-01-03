<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Policy extends Model
{
    protected $fillable = [
        'name',
        'version',
        'approval_date',
        'owner_id',
        'evidence_id',
        'internal_link',
        'description',
    ];

    protected $casts = [
        'approval_date' => 'date',
    ];

    /**
     * Get the user who owns this policy.
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * Get the evidence file for this policy (if uploaded).
     */
    public function evidence(): BelongsTo
    {
        return $this->belongsTo(Evidence::class);
    }

    /**
     * Get the controls mapped to this policy.
     */
    public function controls(): BelongsToMany
    {
        return $this->belongsToMany(Control::class, 'policy_control_mappings')
            ->withPivot('coverage_notes', 'mapped_by')
            ->withTimestamps()
            ->using(PolicyControlMapping::class);
    }

    /**
     * Get the policy-control mappings.
     */
    public function mappings(): HasMany
    {
        return $this->hasMany(PolicyControlMapping::class);
    }

    /**
     * Check if policy has a file (evidence) or link.
     */
    public function hasFile(): bool
    {
        return !empty($this->evidence_id);
    }

    /**
     * Check if policy has an internal link.
     */
    public function hasLink(): bool
    {
        return !empty($this->internal_link);
    }

    /**
     * Get the display URL for the policy (file download or internal link).
     */
    public function getDisplayUrl(): ?string
    {
        if ($this->hasLink()) {
            return $this->internal_link;
        }
        
        if ($this->hasFile() && $this->evidence) {
            return route('evidence.download', ['evidence' => $this->evidence->id]);
        }
        
        return null;
    }
}
