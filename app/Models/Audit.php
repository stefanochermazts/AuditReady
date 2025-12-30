<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Audit extends Model
{
    protected $fillable = [
        'name',
        'description',
        'status',
        'start_date',
        'end_date',
        'closed_at',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'closed_at' => 'datetime',
    ];

    /**
     * Get the evidences for the audit.
     */
    public function evidences(): HasMany
    {
        return $this->hasMany(Evidence::class);
    }
}
