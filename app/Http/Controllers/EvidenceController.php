<?php

namespace App\Http\Controllers;

use App\Models\Evidence;
use App\Services\EvidenceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * EvidenceController - Handles evidence file downloads
 */
class EvidenceController extends Controller
{
    public function __construct(
        private EvidenceService $evidenceService
    ) {
    }

    /**
     * Download an evidence file
     *
     * @param Request $request
     * @param Evidence $evidence
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function download(Request $request, Evidence $evidence)
    {
        // Verify signed URL if signature is present (optional - allows both signed and direct authenticated access)
        // If signature is present in the request, verify it; otherwise, proceed with auth check only
        if ($request->has('signature')) {
            if (!$request->hasValidSignature()) {
                abort(403, 'Invalid or expired download link');
            }
        }

        // Authorize: Only Organization Owner or Audit Manager can download
        Gate::authorize('download', $evidence);

        // Get tenant ID
        $tenantId = tenant('id');
        if (!$tenantId) {
            abort(403, 'Tenant context not found');
        }

        // Download evidence using EvidenceService
        return $this->evidenceService->download($evidence, $tenantId);
    }
}
