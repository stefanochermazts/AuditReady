<?php

namespace App\Services;

use App\Models\Audit;
use App\Models\AuditLog;
use App\Models\Evidence;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * ExportService - Handles export of audit data to PDF and CSV formats
 */
class ExportService
{
    /**
     * Export audit data to PDF
     *
     * @param Audit $audit
     * @return string Path to the exported file
     */
    public function exportToPdf(Audit $audit): string
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

        // Prepare data for PDF
        $data = [
            'audit' => $audit,
            'evidences' => $audit->evidences,
            'auditLogs' => $auditLogs,
            'exportDate' => now(),
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
}
