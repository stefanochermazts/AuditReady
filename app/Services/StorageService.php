<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Storage Service - Provider-Agnostic File Storage
 * 
 * This service abstracts the storage provider, allowing the application
 * to work with any S3-compatible provider or local storage without
 * changing the application code.
 */
class StorageService
{
    /**
     * Get the configured storage disk based on STORAGE_PROVIDER env variable
     *
     * @return \Illuminate\Contracts\Filesystem\Filesystem
     */
    public function disk()
    {
        $provider = env('STORAGE_PROVIDER', 'local');
        
        // For local storage, use the local disk
        if ($provider === 'local') {
            return Storage::disk('local');
        }
        
        // For S3-compatible providers, use the provider-specific disk
        $allowedProviders = ['minio', 's3', 'spaces', 'wasabi', 'b2'];
        
        if (!in_array($provider, $allowedProviders)) {
            throw new RuntimeException("Invalid storage provider: {$provider}. Allowed: " . implode(', ', $allowedProviders));
        }
        
        return Storage::disk($provider);
    }

    /**
     * Store encrypted file content
     *
     * @param string $path
     * @param string $content Encrypted content
     * @return bool
     */
    public function put(string $path, string $content): bool
    {
        return $this->disk()->put($path, $content);
    }

    /**
     * Get encrypted file content
     *
     * @param string $path
     * @return string|null
     */
    public function get(string $path): ?string
    {
        if (!$this->exists($path)) {
            return null;
        }
        
        return $this->disk()->get($path);
    }

    /**
     * Check if file exists
     *
     * @param string $path
     * @return bool
     */
    public function exists(string $path): bool
    {
        return $this->disk()->exists($path);
    }

    /**
     * Delete file
     *
     * @param string $path
     * @return bool
     */
    public function delete(string $path): bool
    {
        if (!$this->exists($path)) {
            return false;
        }
        
        return $this->disk()->delete($path);
    }

    /**
     * Get file URL (for public files)
     *
     * @param string $path
     * @return string
     */
    public function url(string $path): string
    {
        return $this->disk()->url($path);
    }

    /**
     * Get temporary URL for file (signed URL, expires after specified time)
     *
     * @param string $path
     * @param \DateTimeInterface|\DateInterval|int $expiration
     * @return string
     */
    public function temporaryUrl(string $path, $expiration): string
    {
        $disk = $this->disk();
        
        // For local storage, return regular URL
        if (env('STORAGE_PROVIDER', 'local') === 'local') {
            return $disk->url($path);
        }
        
        // For S3-compatible providers, return temporary signed URL
        return $disk->temporaryUrl($path, $expiration);
    }

    /**
     * Get the storage path for tenant evidence
     *
     * @param string $tenantId
     * @param string $evidenceId
     * @param int $version
     * @return string
     */
    public function getEvidencePath(string $tenantId, string $evidenceId, int $version = 1): string
    {
        $provider = env('STORAGE_PROVIDER', 'local');
        
        if ($provider === 'local') {
            return "tenants/{$tenantId}/evidences/{$evidenceId}_v{$version}";
        }
        
        // For S3-compatible providers, use the same path structure
        return "tenants/{$tenantId}/evidences/{$evidenceId}_v{$version}";
    }

    /**
     * Get the storage path for tenant exports
     *
     * @param string $tenantId
     * @param string $auditId
     * @param string $format
     * @param string $timestamp
     * @return string
     */
    public function getExportPath(string $tenantId, string $auditId, string $format, string $timestamp): string
    {
        return "tenants/{$tenantId}/exports/{$auditId}_{$timestamp}.{$format}";
    }

    /**
     * Get the current storage provider name
     *
     * @return string
     */
    public function getProvider(): string
    {
        return env('STORAGE_PROVIDER', 'local');
    }
}
