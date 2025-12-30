<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Evidence extends Model
{
    protected $fillable = [
        'audit_id',
        'user_id',
        'name',
        'description',
        'file_path',
        'file_size',
        'mime_type',
        'checksum',
        'version',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'version' => 'integer',
    ];

    /**
     * Get the audit that owns the evidence.
     */
    public function audit(): BelongsTo
    {
        return $this->belongsTo(Audit::class);
    }

    /**
     * Get the user who uploaded the evidence.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
