<?php

namespace App\Services;

use App\Models\Audit;
use App\Models\AuditLog;
use App\Models\Evidence;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;
use RuntimeException;

/**
 * ExportService - Handles export of audit data to PDF, CSV, and ZIP formats
 */
class ExportService
{
    public function __construct(
        private EvidenceService $evidenceService
    ) {
    }
    /**
     * Export audit data to PDF
     *
     * @param Audit $audit
     * @param string $linkMode Link mode: 'online' (signed URLs) or 'local' (relative paths for ZIP)
     * @return string Path to the exported file
     */
    public function exportToPdf(Audit $audit, string $linkMode = 'online'): string
    {
        // Load audit with relationships
        $audit->load(['evidences.uploader', 'evidences.validator', 'creator', 'auditor', 'evidences' => function ($query) {
            $query->orderBy('version', 'desc')->orderBy('created_at', 'desc');
        }]);

        // Get audit logs for this audit
        $auditLogs = AuditLog::where('model_type', Audit::class)
            ->where('model_id', $audit->id)
            ->orderBy('created_at', 'desc')
            ->get();

        // Generate evidence download links based on link mode
        $evidenceLinks = [];
        foreach ($audit->evidences as $evidence) {
            if ($linkMode === 'online') {
                // Generate signed URL for online access
                $evidenceLinks[$evidence->id] = $this->generateEvidenceDownloadUrl($evidence);
            } else {
                // Generate relative path for local ZIP file
                // Format: evidences/evidence_{id}_v{version}_{sanitized_filename}
                $sanitizedFilename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $evidence->filename);
                $evidenceLinks[$evidence->id] = "evidences/evidence_{$evidence->id}_v{$evidence->version}_{$sanitizedFilename}";
            }
        }

        // Prepare data for PDF
        $data = [
            'audit' => $audit,
            'evidences' => $audit->evidences,
            'auditLogs' => $auditLogs,
            'exportDate' => now(),
            'evidenceLinks' => $evidenceLinks,
            'linkMode' => $linkMode,
        ];

        // Generate PDF
        $pdf = Pdf::loadView('exports.audit-pdf', $data);
        $pdf->setPaper('a4', 'portrait');

        // Generate filename
        $tenantId = tenant('id') ?? 'system';
        $timestamp = now()->format('Y-m-d_His');
        $filename = "exports/{$tenantId}/audit_{$audit->id}_{$timestamp}.pdf";

        // Store PDF (encrypted)
        $encryptedContent = $this->encryptContent($pdf->output());
        $storageService = new StorageService();
        $storageService->put($filename, $encryptedContent);

        return $filename;
    }

    /**
     * Export audit data to CSV
     *
     * @param Audit $audit
     * @return string Path to the exported file
     */
    public function exportToCsv(Audit $audit): string
    {
        // Load audit with relationships
        $audit->load(['evidences.uploader', 'creator']);

        // Generate CSV content
        $csvData = [];
        
        // Header row
        $csvData[] = [
            'Audit ID',
            'Audit Name',
            'Audit Type',
            'Status',
            'Compliance Standards',
            'Scope',
            'Start Date',
            'End Date',
            'Reference Period Start',
            'Reference Period End',
            'Auditor',
            'Created By',
            'Created At',
        ];

        // Audit row
        $csvData[] = [
            $audit->id,
            $audit->name,
            $audit->audit_type ?? 'N/A',
            $audit->status,
            $audit->compliance_standards ? implode(', ', $audit->compliance_standards) : 'N/A',
            $audit->scope ?? 'N/A',
            $audit->start_date?->format('Y-m-d'),
            $audit->end_date?->format('Y-m-d'),
            $audit->reference_period_start?->format('Y-m-d'),
            $audit->reference_period_end?->format('Y-m-d'),
            $audit->auditor?->name ?? 'N/A',
            $audit->creator?->name ?? 'N/A',
            $audit->created_at->format('Y-m-d H:i:s'),
        ];

        // Empty row
        $csvData[] = [];

        // Evidences header
        $csvData[] = [
            'Evidence ID',
            'Filename',
            'Category',
            'Document Date',
            'Document Type',
            'Supplier',
            'Regulatory Reference',
            'Control Reference',
            'Validation Status',
            'Validated By',
            'Validated At',
            'Expiry Date',
            'Confidentiality Level',
            'MIME Type',
            'Size (bytes)',
            'Version',
            'Uploader',
            'Checksum',
            'Uploaded At',
        ];

        // Evidence rows
        foreach ($audit->evidences as $evidence) {
            $csvData[] = [
                $evidence->id,
                $evidence->filename,
                $evidence->category ?? 'N/A',
                $evidence->document_date?->format('Y-m-d'),
                $evidence->document_type ?? 'N/A',
                $evidence->supplier ?? 'N/A',
                $evidence->regulatory_reference ?? 'N/A',
                $evidence->control_reference ?? 'N/A',
                $evidence->validation_status ?? 'pending',
                $evidence->validator?->name ?? 'N/A',
                $evidence->validated_at?->format('Y-m-d H:i:s'),
                $evidence->expiry_date?->format('Y-m-d'),
                $evidence->confidentiality_level ?? 'internal',
                $evidence->mime_type,
                $evidence->size,
                $evidence->version,
                $evidence->uploader?->name ?? 'N/A',
                $evidence->checksum,
                $evidence->created_at->format('Y-m-d H:i:s'),
            ];
        }

        // Convert to CSV string
        $csvContent = $this->arrayToCsv($csvData);

        // Generate filename
        $tenantId = tenant('id') ?? 'system';
        $timestamp = now()->format('Y-m-d_His');
        $filename = "exports/{$tenantId}/audit_{$audit->id}_{$timestamp}.csv";

        // Encrypt and store CSV
        $encryptedContent = $this->encryptContent($csvContent);
        $storageService = new StorageService();
        $storageService->put($filename, $encryptedContent);

        return $filename;
    }

    /**
     * Export audit data to ZIP (contains PDF + all evidence files)
     *
     * @param Audit $audit
     * @return string Path to the exported ZIP file
     * @throws RuntimeException
     */
    public function exportToZip(Audit $audit): string
    {
        // Load audit with relationships
        $audit->load(['evidences.uploader', 'evidences.validator', 'creator', 'auditor', 'evidences' => function ($query) {
            $query->orderBy('version', 'desc')->orderBy('created_at', 'desc');
        }]);

        // Generate PDF with local links (for ZIP)
        $pdfPath = $this->exportToPdf($audit, 'local');

        // Verify evidences are loaded
        if ($audit->evidences->isEmpty()) {
            \Log::warning("No evidences found for audit {$audit->id} when creating ZIP");
        } else {
            \Log::info("Found {$audit->evidences->count()} evidences for audit {$audit->id}");
        }

        // Get tenant ID and generate ZIP filename
        $tenantId = tenant('id') ?? 'system';
        $timestamp = now()->format('Y-m-d_His');
        $zipFilename = "exports/{$tenantId}/audit_{$audit->id}_{$timestamp}.zip";

        // Create temporary ZIP file
        $tempZipPath = sys_get_temp_dir() . '/' . uniqid('audit_export_', true) . '.zip';
        $zip = new ZipArchive();

        if ($zip->open($tempZipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Failed to create ZIP file');
        }

        try {
            // Create directory structure in ZIP
            $zipDir = "audit_{$audit->id}_{$timestamp}";
            $zip->addEmptyDir($zipDir);
            $zip->addEmptyDir("{$zipDir}/evidences");

            // Add PDF to ZIP (decrypt it first)
            $storageService = new StorageService();
            $encryptedPdfContent = $storageService->get($pdfPath);
            if ($encryptedPdfContent === null) {
                throw new RuntimeException('PDF file not found in storage');
            }
            $decryptedPdfContent = $this->decryptContent($encryptedPdfContent);
            $zip->addFromString("{$zipDir}/audit_{$audit->id}_{$timestamp}.pdf", $decryptedPdfContent);

            // Add all evidence files to ZIP (decrypted)
            $evidencesCount = $audit->evidences->count();
            \Log::info("Starting to add {$evidencesCount} evidences to ZIP for audit {$audit->id}");
            
            // Verify evidences have required fields
            foreach ($audit->evidences as $evidence) {
                if (empty($evidence->stored_path)) {
                    \Log::warning("Evidence {$evidence->id} has no stored_path");
                }
                if (empty($evidence->encrypted_key)) {
                    \Log::warning("Evidence {$evidence->id} has no encrypted_key");
                }
                if (empty($evidence->iv)) {
                    \Log::warning("Evidence {$evidence->id} has no iv");
                }
            }
            
            $addedCount = 0;
            foreach ($audit->evidences as $evidence) {
                try {
                    \Log::info("Processing evidence {$evidence->id} for ZIP", [
                        'evidence_id' => $evidence->id,
                        'filename' => $evidence->filename,
                        'stored_path' => $evidence->stored_path,
                        'has_encrypted_key' => !empty($evidence->encrypted_key),
                        'has_iv' => !empty($evidence->iv),
                    ]);

                    // Get decrypted evidence content
                    $decryptedEvidenceContent = $this->evidenceService->getDecryptedContent($evidence);

                    if (empty($decryptedEvidenceContent)) {
                        \Log::warning("Evidence {$evidence->id} has empty decrypted content");
                        continue;
                    }

                    // Sanitize filename for ZIP
                    $sanitizedFilename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $evidence->filename);
                    if (empty($sanitizedFilename)) {
                        $sanitizedFilename = "evidence_{$evidence->id}";
                    }
                    $evidencePathInZip = "{$zipDir}/evidences/evidence_{$evidence->id}_v{$evidence->version}_{$sanitizedFilename}";

                    // Add evidence to ZIP
                    $result = $zip->addFromString($evidencePathInZip, $decryptedEvidenceContent);
                    if ($result === false) {
                        \Log::error("Failed to add evidence {$evidence->id} to ZIP using addFromString", [
                            'evidence_id' => $evidence->id,
                            'path_in_zip' => $evidencePathInZip,
                            'zip_status' => $zip->getStatusString(),
                        ]);
                        continue;
                    }

                    $addedCount++;
                    \Log::info("Successfully added evidence {$evidence->id} to ZIP", [
                        'evidence_id' => $evidence->id,
                        'path_in_zip' => $evidencePathInZip,
                        'content_size' => strlen($decryptedEvidenceContent),
                    ]);
                } catch (\Exception $e) {
                    // Log error but continue with other evidences
                    \Log::error("Failed to add evidence {$evidence->id} to ZIP", [
                        'evidence_id' => $evidence->id,
                        'filename' => $evidence->filename ?? 'N/A',
                        'stored_path' => $evidence->stored_path ?? 'N/A',
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                }
            }

            \Log::info("Added {$addedCount} out of {$evidencesCount} evidences to ZIP for audit {$audit->id}");

            // Verify ZIP contains files before closing
            $zipFileCount = $zip->numFiles;
            \Log::info("ZIP contains {$zipFileCount} files before closing");

            // Close ZIP
            $closeResult = $zip->close();
            if ($closeResult === false) {
                throw new RuntimeException('Failed to close ZIP file: ' . $zip->getStatusString());
            }
            \Log::info("ZIP closed successfully");

            // Read ZIP content
            $zipContent = file_get_contents($tempZipPath);
            if ($zipContent === false) {
                throw new RuntimeException('Failed to read ZIP file');
            }

            // Encrypt ZIP content
            $encryptedZipContent = $this->encryptContent($zipContent);

            // Store encrypted ZIP in storage
            $storageService = new StorageService();
            $storageService->put($zipFilename, $encryptedZipContent);

            // Clean up temporary file
            @unlink($tempZipPath);

            return $zipFilename;
        } catch (\Exception $e) {
            // Clean up on error
            $zip->close();
            @unlink($tempZipPath);
            throw $e;
        }
    }

    /**
     * Convert array to CSV string
     *
     * @param array $data
     * @return string
     */
    private function arrayToCsv(array $data): string
    {
        $output = fopen('php://temp', 'r+');
        
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
        
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        
        return $csv;
    }

    /**
     * Encrypt content using EncryptionService
     *
     * @param string $content
     * @return string Encrypted content (base64 encoded with metadata)
     */
    private function encryptContent(string $content): string
    {
        $encryptionService = new EncryptionService();
        $result = $encryptionService->encrypt($content);
        
        // Store encrypted key, IV, and encrypted content
        // Format: base64(encrypted_key):base64(iv):base64(encrypted_content)
        return base64_encode($result['key']) . ':' . $result['iv'] . ':' . base64_encode($result['encrypted']);
    }

    /**
     * Decrypt content
     *
     * @param string $encryptedContent
     * @return string Decrypted content
     */
    public function decryptContent(string $encryptedContent): string
    {
        $parts = explode(':', $encryptedContent);
        if (count($parts) !== 3) {
            throw new \RuntimeException('Invalid encrypted content format');
        }

        $encryptedKey = base64_decode($parts[0]);
        $iv = $parts[1];
        $encrypted = base64_decode($parts[2]);

        $encryptionService = new EncryptionService();
        return $encryptionService->decrypt($encrypted, $encryptedKey, $iv);
    }

    /**
     * Generate a signed URL for download (valid for 24 hours)
     *
     * @param string $filePath
     * @return string
     */
    public function generateDownloadUrl(string $filePath): string
    {
        // Use Laravel's signed URL feature
        return url()->temporarySignedRoute(
            'exports.download',
            now()->addHours(24),
            ['file' => base64_encode($filePath)]
        );
    }

    /**
     * Generate a signed URL for evidence download
     * 
     * Scadenza configurabile: per ora nessuna scadenza (null), ma può essere facilmente modificata
     * impostando un valore in ore (es: 24 per 24 ore, 168 per 7 giorni, null per nessuna scadenza)
     *
     * @param \App\Models\Evidence $evidence
     * @param int|null $expirationHours Hours until expiration (null = no expiration)
     * @return string
     */
    public function generateEvidenceDownloadUrl(\App\Models\Evidence $evidence, ?int $expirationHours = null): string
    {
        // Per ora nessuna scadenza (null), ma struttura permette di aggiungerla facilmente
        // Per abilitare scadenza, cambiare null con: now()->addHours($expirationHours ?? 24)
        
        if ($expirationHours === null) {
            // Signed URL senza scadenza
            return url()->signedRoute('evidence.download', ['evidence' => $evidence->id]);
        }
        
        // Signed URL con scadenza
        return url()->temporarySignedRoute(
            'evidence.download',
            now()->addHours($expirationHours),
            ['evidence' => $evidence->id]
        );
    }
}
