<?php

namespace App\Models;

use App\Models\Audit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Control extends Model
{
    protected $fillable = [
        'standard',
        'article_reference',
        'title',
        'description',
        'category',
        'tenant_id',
    ];

    protected $casts = [
        //
    ];

    /**
     * Get the users who own this control (through control_owners pivot)
     */
    public function owners(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'control_owners')
            ->withPivot('role_name', 'responsibility_level', 'notes')
            ->withTimestamps();
    }

    /**
     * Get the control owners (pivot records with additional data)
     */
    public function controlOwners(): HasMany
    {
        return $this->hasMany(ControlOwner::class);
    }

    /**
     * Get primary owners only
     */
    public function primaryOwners(): BelongsToMany
    {
        return $this->owners()->wherePivot('responsibility_level', 'primary');
    }

    /**
     * Check if control has any owners
     */
    public function hasOwners(): bool
    {
        return $this->owners()->exists();
    }

    /**
     * Get the policies mapped to this control.
     */
    public function policies(): BelongsToMany
    {
        return $this->belongsToMany(Policy::class, 'policy_control_mappings')
            ->withPivot('coverage_notes', 'mapped_by')
            ->withTimestamps()
            ->using(PolicyControlMapping::class);
    }

    /**
     * Get the policy-control mappings for this control.
     */
    public function policyMappings(): HasMany
    {
        return $this->hasMany(PolicyControlMapping::class);
    }

    /**
     * Get the audits that this control is linked to.
     */
    public function audits(): BelongsToMany
    {
        return $this->belongsToMany(Audit::class, 'audit_control')
            ->withTimestamps();
    }
}
