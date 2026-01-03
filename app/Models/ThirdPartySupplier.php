<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ThirdPartySupplier extends Model
{
    protected $fillable = [
        'tenant_id',
        'name',
        'email',
        'contact_person',
        'notes',
    ];

    protected $casts = [
        //
    ];

    /**
     * Get all evidence requests for this supplier.
     */
    public function evidenceRequests(): HasMany
    {
        return $this->hasMany(EvidenceRequest::class, 'supplier_id');
    }
}
