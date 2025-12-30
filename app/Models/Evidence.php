<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Evidence extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'audit_id',
        'uploader_id',
        'filename',
        'mime_type',
        'size',
        'stored_path',
        'checksum',
        'version',
        'encrypted_key',
        'iv',
    ];

    protected $casts = [
        'size' => 'integer',
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
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploader_id');
    }

    /**
     * Get all versions of this evidence (same filename, same audit)
     */
    public function versions()
    {
        return static::where('audit_id', $this->audit_id)
            ->where('filename', $this->filename)
            ->orderBy('version', 'desc')
            ->get();
    }

    /**
     * Get the latest version number for this evidence
     */
    public function getLatestVersion(): int
    {
        return static::where('audit_id', $this->audit_id)
            ->where('filename', $this->filename)
            ->max('version') ?? 0;
    }
}
