<?php

namespace App\Http\Controllers;

use App\Jobs\ExportAuditJob;
use App\Models\Audit;
use App\Services\ExportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

/**
 * ExportController - Handles export requests and downloads
 */
class ExportController extends Controller
{
    public function __construct(
        private ExportService $exportService
    ) {
    }
    /**
     * Request an export of an audit
     *
     * @param Request $request
     * @param Audit $audit
     * @return \Illuminate\Http\RedirectResponse
     */
    public function requestExport(Request $request, Audit $audit)
    {
        Gate::authorize('export', $audit);

        $format = $request->input('format', 'pdf');
        
        if (!in_array($format, ['pdf', 'csv', 'zip'])) {
            return redirect()->back()->withErrors(['format' => 'Invalid export format']);
        }

        // Dispatch export job
        ExportAuditJob::dispatch($audit->id, $format, auth()->id(), tenant('id'));

        return redirect()->back()->with('success', "Export in {$format} format has been queued. You will receive an email when it's ready.");
    }

    /**
     * Download an exported file
     *
     * @param Request $request
     * @param string $file
     * @return \Illuminate\Http\Response
     */
    public function download(Request $request, string $file)
    {
        // Verify signed URL
        if (!$request->hasValidSignature()) {
            abort(403, 'Invalid or expired download link');
        }

        // Decode file path
        $filePath = base64_decode($file);
        
        if (!$filePath) {
            abort(404, 'File not found');
        }

        // Verify tenant context
        $tenantId = tenant('id');
        if ($tenantId && !str_starts_with($filePath, "exports/{$tenantId}/")) {
            abort(403, 'Access denied');
        }

        // Get file from storage
        $storageService = new \App\Services\StorageService();
        if (!$storageService->exists($filePath)) {
            abort(404, 'File not found');
        }

        // Decrypt and download
        $encryptedContent = $storageService->get($filePath);
        $decryptedContent = $this->exportService->decryptContent($encryptedContent);

        // Determine file extension and MIME type
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);
        $mimeTypes = [
            'pdf' => 'application/pdf',
            'csv' => 'text/csv',
            'zip' => 'application/zip',
        ];
        $mimeType = $mimeTypes[$extension] ?? 'application/octet-stream';

        // Generate filename
        $filename = 'audit_export_' . now()->format('Y-m-d_His') . '.' . $extension;

        return response($decryptedContent, 200)
            ->header('Content-Type', $mimeType)
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }
}
