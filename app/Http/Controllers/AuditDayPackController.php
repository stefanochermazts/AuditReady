<?php

namespace App\Http\Controllers;

use App\Models\AuditDayPack;
use App\Services\ExportService;
use App\Services\StorageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * AuditDayPackController - Handles audit day pack downloads
 */
class AuditDayPackController extends Controller
{
    public function __construct(
        private ExportService $exportService,
        private StorageService $storageService
    ) {
    }

    /**
     * Download an audit day pack
     *
     * @param Request $request
     * @param AuditDayPack $pack
     * @return \Illuminate\Http\Response
     */
    public function download(Request $request, AuditDayPack $pack)
    {
        // Verify user has access to the audit
        Gate::authorize('view', $pack->audit);

        // Verify tenant context
        $tenantId = tenant('id');
        if ($tenantId && !str_starts_with($pack->file_path, "exports/{$tenantId}/")) {
            abort(403, 'Access denied');
        }

        // Check if file exists
        if (!$this->storageService->exists($pack->file_path)) {
            abort(404, 'Pack file not found');
        }

        // Get encrypted content
        $encryptedContent = $this->storageService->get($pack->file_path);
        if ($encryptedContent === null) {
            abort(404, 'Pack file not found');
        }

        // Decrypt content
        $decryptedContent = $this->exportService->decryptContent($encryptedContent);

        // Determine file extension and MIME type
        $extension = pathinfo($pack->file_path, PATHINFO_EXTENSION);
        $mimeTypes = [
            'pdf' => 'application/pdf',
            'zip' => 'application/zip',
        ];
        $mimeType = $mimeTypes[$extension] ?? 'application/octet-stream';

        // Generate filename
        $auditName = str_replace([' ', '/', '\\'], '_', $pack->audit->name);
        $filename = "audit_day_pack_{$auditName}_{$pack->generated_at->format('Y-m-d_His')}.{$extension}";

        return response($decryptedContent, 200)
            ->header('Content-Type', $mimeType)
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }
}
