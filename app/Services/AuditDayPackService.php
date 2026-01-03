<?php

namespace App\Services;

use App\Models\Audit;
use App\Models\AuditDayPack;
use App\Models\AuditLog;
use App\Models\Control;
use App\Models\Evidence;
use App\Services\ControlService;
use App\Services\EvidenceService;
use App\Services\ExportService;
use App\Services\StorageService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use ZipArchive;
use RuntimeException;

/**
 * AuditDayPackService - Generates organized audit day packs
 * 
 * Creates ZIP/PDF packages with:
 * - Control index with linked evidences
 * - Organized evidence files by control
 * - Formatted audit trail
 * - Summary overview
 */
class AuditDayPackService
{
    public function __construct(
        private ExportService $exportService,
        private EvidenceService $evidenceService,
        private ControlService $controlService,
        private StorageService $storageService
    ) {
    }

    /**
     * Generate an audit day pack
     *
     * @param Audit $audit
     * @param array $options
     * @return AuditDayPack
     * @throws RuntimeException
     */
    public function generatePack(Audit $audit, array $options = []): AuditDayPack
    {
        $format = $options['format'] ?? 'both';
        $includeAllEvidences = $options['include_all_evidences'] ?? true;
        $includeFullAuditTrail = $options['include_full_audit_trail'] ?? true;
        $generatedBy = $options['generated_by'] ?? auth()->id();

        // Load audit with relationships
        $audit->load([
            'evidences.uploader',
            'evidences.validator',
            'evidences.evidenceRequest.supplier',
            'creator',
            'auditor',
        ]);

        // Filter evidences if needed
        $evidences = $includeAllEvidences
            ? $audit->evidences
            : $audit->evidences->where('validation_status', 'approved');

        // Get controls related to this audit
        $controls = $this->getControlsForAudit($audit);

        // Get audit logs
        $auditLogs = $includeFullAuditTrail
            ? AuditLog::where('model_type', Audit::class)
                ->where('model_id', $audit->id)
                ->with('user')
                ->orderBy('created_at', 'asc')
                ->get()
            : collect();

        // Generate pack based on format
        $filePath = null;
        if ($format === 'zip' || $format === 'both') {
            $filePath = $this->generateZipPack($audit, $evidences, $controls, $auditLogs);
        } elseif ($format === 'pdf') {
            $filePath = $this->generatePdfPack($audit, $evidences, $controls, $auditLogs);
        }

        // Create pack record
        $pack = AuditDayPack::create([
            'audit_id' => $audit->id,
            'generated_by' => $generatedBy,
            'format' => $format,
            'include_all_evidences' => $includeAllEvidences,
            'include_full_audit_trail' => $includeFullAuditTrail,
            'file_path' => $filePath,
            'generated_at' => now(),
        ]);

        return $pack;
    }

    /**
     * Get controls related to the audit's compliance standards
     *
     * @param Audit $audit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    private function getControlsForAudit(Audit $audit)
    {
        $standards = $audit->compliance_standards ?? [];
        
        if (empty($standards)) {
            return collect();
        }

        return Control::whereIn('standard', $standards)
            ->orWhere('standard', 'custom')
            ->with('owners')
            ->get();
    }

    /**
     * Generate ZIP pack with organized structure
     *
     * @param Audit $audit
     * @param \Illuminate\Database\Eloquent\Collection $evidences
     * @param \Illuminate\Database\Eloquent\Collection $controls
     * @param \Illuminate\Database\Eloquent\Collection $auditLogs
     * @return string Path to ZIP file
     */
    private function generateZipPack(
        Audit $audit,
        $evidences,
        $controls,
        $auditLogs
    ): string {
        $tenantId = tenant('id') ?? 'system';
        $timestamp = now()->format('Y-m-d_His');
        $zipFilename = "exports/{$tenantId}/audit_day_pack_{$audit->id}_{$timestamp}.zip";

        // Create temporary ZIP file
        $tempZipPath = sys_get_temp_dir() . '/' . uniqid('audit_day_pack_', true) . '.zip';
        $zip = new ZipArchive();
        $zipOpened = false;

        if ($zip->open($tempZipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Failed to create ZIP file');
        }
        $zipOpened = true;

        try {
            // Create directory structure
            $zipDir = "audit_{$audit->id}_{$timestamp}";
            $zip->addEmptyDir($zipDir);
            $zip->addEmptyDir("{$zipDir}/evidences");
            $zip->addEmptyDir("{$zipDir}/controls");

            // Generate and add control index PDF
            $controlIndexPdf = $this->generateControlIndexPdf($audit, $controls, $evidences);
            $zip->addFromString("{$zipDir}/00_Control_Index.pdf", $controlIndexPdf);

            // Generate and add summary PDF
            $summaryPdf = $this->generateSummaryPdf($audit, $evidences, $auditLogs);
            $zip->addFromString("{$zipDir}/01_Audit_Summary.pdf", $summaryPdf);

            // Add audit trail if requested
            if ($auditLogs->isNotEmpty()) {
                $auditTrailPdf = $this->generateAuditTrailPdf($audit, $auditLogs);
                $zip->addFromString("{$zipDir}/02_Audit_Trail.pdf", $auditTrailPdf);
            }

            // Organize evidences by control
            $evidencesByControl = $this->organizeEvidencesByControl($evidences, $controls);

            // Add evidences organized by control
            foreach ($evidencesByControl as $controlId => $controlEvidences) {
                $control = $controls->firstWhere('id', $controlId);
                $controlDir = $control
                    ? $this->sanitizeFilename("{$control->article_reference}_{$control->id}")
                    : "control_{$controlId}";
                
                $zip->addEmptyDir("{$zipDir}/evidences/{$controlDir}");

                foreach ($controlEvidences as $evidence) {
                    try {
                        $decryptedContent = $this->evidenceService->getDecryptedContent($evidence);
                        $sanitizedFilename = $this->sanitizeFilename($evidence->filename);
                        $zipPath = "{$zipDir}/evidences/{$controlDir}/evidence_{$evidence->id}_v{$evidence->version}_{$sanitizedFilename}";
                        $zip->addFromString($zipPath, $decryptedContent);
                    } catch (\Exception $e) {
                        \Log::error("Failed to add evidence {$evidence->id} to ZIP: " . $e->getMessage());
                        // Continue with other evidences
                    }
                }
            }

            // Add unlinked evidences (if any)
            $unlinkedEvidences = $evidences->filter(function ($evidence) use ($controls) {
                $controlRef = $evidence->control_reference;
                if (empty($controlRef)) {
                    return true;
                }
                // Check if control reference matches any control
                return !$controls->contains(function ($control) use ($controlRef) {
                    return $control->article_reference === $controlRef || 
                           $control->id == $controlRef ||
                           str_contains($control->title, $controlRef);
                });
            });

            if ($unlinkedEvidences->isNotEmpty()) {
                $zip->addEmptyDir("{$zipDir}/evidences/unlinked");
                foreach ($unlinkedEvidences as $evidence) {
                    try {
                        $decryptedContent = $this->evidenceService->getDecryptedContent($evidence);
                        $sanitizedFilename = $this->sanitizeFilename($evidence->filename);
                        $zipPath = "{$zipDir}/evidences/unlinked/evidence_{$evidence->id}_v{$evidence->version}_{$sanitizedFilename}";
                        $zip->addFromString($zipPath, $decryptedContent);
                    } catch (\Exception $e) {
                        \Log::error("Failed to add unlinked evidence {$evidence->id} to ZIP: " . $e->getMessage());
                    }
                }
            }

            $zip->close();
            $zipOpened = false;

            // Encrypt and store ZIP
            $zipContent = file_get_contents($tempZipPath);
            if ($zipContent === false) {
                throw new RuntimeException('Failed to read generated ZIP file');
            }
            $encryptedContent = $this->encryptContent($zipContent);
            $this->storageService->put($zipFilename, $encryptedContent);

            // Clean up temp file
            @unlink($tempZipPath);

            return $zipFilename;
        } catch (\Exception $e) {
            // Only close ZIP if it was successfully opened
            if ($zipOpened && $zip instanceof ZipArchive) {
                try {
                    $zip->close();
                } catch (\Exception $closeException) {
                    \Log::error("Failed to close ZIP file: " . $closeException->getMessage());
                }
            }
            // Clean up temp file if it exists
            if (isset($tempZipPath) && file_exists($tempZipPath)) {
                @unlink($tempZipPath);
            }
            throw new RuntimeException("Failed to generate ZIP pack: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Generate PDF pack (single document)
     *
     * @param Audit $audit
     * @param \Illuminate\Database\Eloquent\Collection $evidences
     * @param \Illuminate\Database\Eloquent\Collection $controls
     * @param \Illuminate\Database\Eloquent\Collection $auditLogs
     * @return string Path to PDF file
     */
    private function generatePdfPack(
        Audit $audit,
        $evidences,
        $controls,
        $auditLogs
    ): string {
        // Use existing export service but with custom view
        $data = [
            'audit' => $audit,
            'evidences' => $evidences,
            'controls' => $controls,
            'auditLogs' => $auditLogs,
            'exportDate' => now(),
            'evidencesByControl' => $this->organizeEvidencesByControl($evidences, $controls),
        ];

        $pdf = Pdf::loadView('exports.audit-day-pack-pdf', $data);
        $pdf->setPaper('a4', 'portrait');

        $tenantId = tenant('id') ?? 'system';
        $timestamp = now()->format('Y-m-d_His');
        $filename = "exports/{$tenantId}/audit_day_pack_{$audit->id}_{$timestamp}.pdf";

        $encryptedContent = $this->encryptContent($pdf->output());
        $this->storageService->put($filename, $encryptedContent);

        return $filename;
    }

    /**
     * Generate control index PDF
     *
     * @param Audit $audit
     * @param \Illuminate\Database\Eloquent\Collection $controls
     * @param \Illuminate\Database\Eloquent\Collection $evidences
     * @return string PDF content
     */
    private function generateControlIndexPdf($audit, $controls, $evidences): string
    {
        $evidencesByControl = $this->organizeEvidencesByControl($evidences, $controls);

        $data = [
            'audit' => $audit,
            'controls' => $controls,
            'evidencesByControl' => $evidencesByControl,
            'exportDate' => now(),
        ];

        $pdf = Pdf::loadView('exports.control-index-pdf', $data);
        $pdf->setPaper('a4', 'portrait');
        return $pdf->output();
    }

    /**
     * Generate summary PDF
     *
     * @param Audit $audit
     * @param \Illuminate\Database\Eloquent\Collection $evidences
     * @param \Illuminate\Database\Eloquent\Collection $auditLogs
     * @return string PDF content
     */
    private function generateSummaryPdf($audit, $evidences, $auditLogs): string
    {
        $data = [
            'audit' => $audit,
            'evidences' => $evidences,
            'auditLogs' => $auditLogs,
            'exportDate' => now(),
            'statistics' => [
                'total_evidences' => $evidences->count(),
                'approved_evidences' => $evidences->where('validation_status', 'approved')->count(),
                'pending_evidences' => $evidences->where('validation_status', 'pending')->count(),
                'rejected_evidences' => $evidences->where('validation_status', 'rejected')->count(),
            ],
        ];

        $pdf = Pdf::loadView('exports.audit-summary-pdf', $data);
        $pdf->setPaper('a4', 'portrait');
        return $pdf->output();
    }

    /**
     * Generate audit trail PDF
     *
     * @param Audit $audit
     * @param \Illuminate\Database\Eloquent\Collection $auditLogs
     * @return string PDF content
     */
    private function generateAuditTrailPdf($audit, $auditLogs): string
    {
        $data = [
            'audit' => $audit,
            'auditLogs' => $auditLogs,
            'exportDate' => now(),
        ];

        $pdf = Pdf::loadView('exports.audit-trail-pdf', $data);
        $pdf->setPaper('a4', 'portrait');
        return $pdf->output();
    }

    /**
     * Organize evidences by control
     *
     * @param \Illuminate\Database\Eloquent\Collection $evidences
     * @param \Illuminate\Database\Eloquent\Collection $controls
     * @return array
     */
    private function organizeEvidencesByControl($evidences, $controls): array
    {
        $organized = [];

        foreach ($evidences as $evidence) {
            $controlRef = $evidence->control_reference;
            
            if (empty($controlRef)) {
                // Try to match by regulatory reference
                $regRef = $evidence->regulatory_reference;
                if (!empty($regRef)) {
                    $matchedControl = $controls->first(function ($control) use ($regRef) {
                        return str_contains($regRef, $control->article_reference) ||
                               str_contains($control->article_reference, $regRef);
                    });
                    if ($matchedControl) {
                        $organized[$matchedControl->id][] = $evidence;
                        continue;
                    }
                }
                continue; // Skip unlinked evidences (will be added separately)
            }

            // Try to find control by reference
            $matchedControl = $controls->first(function ($control) use ($controlRef) {
                return $control->article_reference === $controlRef ||
                       $control->id == $controlRef ||
                       str_contains($control->title, $controlRef);
            });

            if ($matchedControl) {
                $organized[$matchedControl->id][] = $evidence;
            }
        }

        return $organized;
    }

    /**
     * Sanitize filename for filesystem
     *
     * @param string $filename
     * @return string
     */
    private function sanitizeFilename(string $filename): string
    {
        return preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
    }

    /**
     * Encrypt content using app key
     * Uses the same format as ExportService: base64(key):iv:base64(encrypted)
     *
     * @param string $content
     * @return string
     */
    private function encryptContent(string $content): string
    {
        $encryptionService = app(\App\Services\EncryptionService::class);
        $result = $encryptionService->encrypt($content);
        
        // Store encrypted key, IV, and encrypted content
        // Format: base64(encrypted_key):base64(iv):base64(encrypted_content)
        return base64_encode($result['key']) . ':' . $result['iv'] . ':' . base64_encode($result['encrypted']);
    }

    /**
     * Decrypt content using app key
     *
     * @param string $encryptedContent
     * @return string
     */
    private function decryptContent(string $encryptedContent): string
    {
        // Use ExportService's decryptContent method
        return $this->exportService->decryptContent($encryptedContent);
    }
}
