<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ControlOwner extends Model
{
    protected $table = 'control_owners';

    protected $fillable = [
        'control_id',
        'user_id',
        'role_name',
        'responsibility_level',
        'notes',
    ];

    protected $casts = [
        //
    ];

    /**
     * Get the control
     */
    public function control(): BelongsTo
    {
        return $this->belongsTo(Control::class);
    }

    /**
     * Get the user who owns the control
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
