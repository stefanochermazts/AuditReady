<?php

namespace App\Services;

use App\Models\Audit;
use App\Models\Evidence;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Evidence Service - Handles evidence upload, download, and versioning
 * 
 * This service manages evidence files with:
 * - Application-level encryption (AES-256)
 * - SHA-256 checksum for integrity verification
 * - Versioning support
 * - Tenant isolation
 */
class EvidenceService
{
    public function __construct(
        private StorageService $storageService,
        private EncryptionService $encryptionService
    ) {
    }

    /**
     * Upload and store evidence file
     *
     * @param UploadedFile $file
     * @param Audit $audit
     * @param User $uploader
     * @param string $tenantId
     * @return Evidence
     */
    public function upload(UploadedFile $file, Audit $audit, User $uploader, string $tenantId): Evidence
    {
        return DB::transaction(function () use ($file, $audit, $uploader, $tenantId) {
            // Read file content
            $content = file_get_contents($file->getRealPath());
            
            // Calculate checksum of plaintext
            $checksum = $this->encryptionService->checksum($content);
            
            // Check if this file already exists (same filename, same audit)
            $existingEvidence = Evidence::where('audit_id', $audit->id)
                ->where('filename', $file->getClientOriginalName())
                ->latest('version')
                ->first();
            
            // Determine version
            $version = $existingEvidence 
                ? $existingEvidence->getLatestVersion() + 1 
                : 1;
            
            // Encrypt file content
            $encryptionResult = $this->encryptionService->encrypt($content);
            
            // Generate evidence ID
            $evidenceId = Str::uuid()->toString();
            
            // Get storage path
            $storagePath = $this->storageService->getEvidencePath($tenantId, $evidenceId, $version);
            
            // Store encrypted file
            $stored = $this->storageService->put($storagePath, $encryptionResult['encrypted']);
            
            if (!$stored) {
                throw new RuntimeException('Failed to store encrypted file');
            }
            
            // Create evidence record
            $evidence = Evidence::create([
                'audit_id' => $audit->id,
                'uploader_id' => $uploader->id,
                'filename' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
                'stored_path' => $storagePath,
                'checksum' => $checksum,
                'version' => $version,
                'encrypted_key' => $encryptionResult['key'],
                'iv' => $encryptionResult['iv'],
            ]);
            
            return $evidence;
        });
    }

    /**
     * Download evidence file (decrypted)
     *
     * @param Evidence $evidence
     * @param string $tenantId
     * @return StreamedResponse
     */
    public function download(Evidence $evidence, string $tenantId): StreamedResponse
    {
        // Get content from storage
        $content = $this->storageService->get($evidence->stored_path);
        
        if ($content === null) {
            throw new RuntimeException('Evidence file not found in storage');
        }
        
        // Check if evidence is encrypted (has encrypted_key and iv)
        // If not, assume it's plaintext (legacy evidence created before encryption was implemented)
        if (empty($evidence->encrypted_key) || empty($evidence->iv)) {
            \Log::info("Evidence {$evidence->id} appears to be unencrypted (legacy), returning content as-is");
            
            // Verify checksum if available
            if (!empty($evidence->checksum)) {
                if (!$this->encryptionService->verifyChecksum($content, $evidence->checksum)) {
                    throw new RuntimeException('Checksum verification failed - file may be corrupted');
                }
            }
            
            $decryptedContent = $content;
        } else {
            // Decrypt content
            $decryptedContent = $this->encryptionService->decrypt(
                $content,
                $evidence->encrypted_key,
                $evidence->iv
            );
            
            // Verify checksum
            if (!empty($evidence->checksum) && !$this->encryptionService->verifyChecksum($decryptedContent, $evidence->checksum)) {
                throw new RuntimeException('Checksum verification failed - file may be corrupted');
            }
        }
        
        // Return streamed response
        return response()->streamDownload(function () use ($decryptedContent) {
            echo $decryptedContent;
        }, $evidence->filename, [
            'Content-Type' => $evidence->mime_type,
            'Content-Length' => strlen($decryptedContent), // Use actual decrypted content length
        ]);
    }

    /**
     * Get decrypted evidence content as string (for export purposes)
     *
     * @param Evidence $evidence
     * @return string Decrypted file content
     * @throws RuntimeException
     */
    public function getDecryptedContent(Evidence $evidence): string
    {
        // Get content from storage
        $content = $this->storageService->get($evidence->stored_path);
        
        if ($content === null) {
            throw new RuntimeException('Evidence file not found in storage');
        }
        
        // Check if evidence is encrypted (has encrypted_key and iv)
        // If not, assume it's plaintext (legacy evidence created before encryption was implemented)
        if (empty($evidence->encrypted_key) || empty($evidence->iv)) {
            \Log::info("Evidence {$evidence->id} appears to be unencrypted (legacy), returning content as-is");
            
            // Verify checksum if available
            if (!empty($evidence->checksum)) {
                if (!$this->encryptionService->verifyChecksum($content, $evidence->checksum)) {
                    throw new RuntimeException('Checksum verification failed - file may be corrupted');
                }
            }
            
            return $content;
        }
        
        // Decrypt content
        $decryptedContent = $this->encryptionService->decrypt(
            $content,
            $evidence->encrypted_key,
            $evidence->iv
        );
        
        // Verify checksum
        if (!empty($evidence->checksum) && !$this->encryptionService->verifyChecksum($decryptedContent, $evidence->checksum)) {
            throw new RuntimeException('Checksum verification failed - file may be corrupted');
        }
        
        return $decryptedContent;
    }

    /**
     * Delete evidence (soft delete)
     *
     * @param Evidence $evidence
     * @return bool
     */
    public function delete(Evidence $evidence): bool
    {
        return $evidence->delete();
    }

    /**
     * Permanently delete evidence and its file
     *
     * @param Evidence $evidence
     * @return bool
     */
    public function forceDelete(Evidence $evidence): bool
    {
        // Delete file from storage
        $this->storageService->delete($evidence->stored_path);
        
        // Permanently delete record
        return $evidence->forceDelete();
    }

    /**
     * Get all versions of an evidence
     *
     * @param Evidence $evidence
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getVersions(Evidence $evidence)
    {
        return $evidence->versions();
    }

    /**
     * Revert to a specific version
     *
     * @param Evidence $evidence
     * @param int $targetVersion
     * @return Evidence
     */
    public function revertToVersion(Evidence $evidence, int $targetVersion): Evidence
    {
        $targetEvidence = Evidence::where('audit_id', $evidence->audit_id)
            ->where('filename', $evidence->filename)
            ->where('version', $targetVersion)
            ->first();
        
        if (!$targetEvidence) {
            throw new RuntimeException("Version {$targetVersion} not found");
        }
        
        // Create new version from target version
        $latestVersion = $evidence->getLatestVersion();
        $newVersion = $latestVersion + 1;
        
        // Copy file from target version
        $targetContent = $this->storageService->get($targetEvidence->stored_path);
        
        if ($targetContent === null) {
            throw new RuntimeException('Target version file not found');
        }
        
        // Get tenant ID from stored path
        preg_match('/tenants\/([^\/]+)\//', $targetEvidence->stored_path, $matches);
        $tenantId = $matches[1] ?? null;
        
        if (!$tenantId) {
            throw new RuntimeException('Could not extract tenant ID from stored path');
        }
        
        // Generate new evidence ID
        $evidenceId = Str::uuid()->toString();
        $newStoragePath = $this->storageService->getEvidencePath($tenantId, $evidenceId, $newVersion);
        
        // Store file
        $this->storageService->put($newStoragePath, $targetContent);
        
        // Create new evidence record
        return Evidence::create([
            'audit_id' => $evidence->audit_id,
            'uploader_id' => auth()->id(),
            'filename' => $targetEvidence->filename,
            'mime_type' => $targetEvidence->mime_type,
            'size' => $targetEvidence->size,
            'stored_path' => $newStoragePath,
            'checksum' => $targetEvidence->checksum,
            'version' => $newVersion,
            'encrypted_key' => $targetEvidence->encrypted_key,
            'iv' => $targetEvidence->iv,
        ]);
    }
}
