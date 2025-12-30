<?php

namespace App\Http\Controllers;

use App\Models\Audit;
use App\Services\AuditLogService;
use App\Services\EvidenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/**
 * External Evidence Controller - Third-party upload API
 * 
 * This controller handles file uploads from external systems via secure API.
 * Requires JWT token authentication and External Uploader role.
 */
class ExternalEvidenceController extends Controller
{
    /**
     * Maximum file size in bytes (100 MB)
     */
    private const MAX_FILE_SIZE = 100 * 1024 * 1024;

    /**
     * Allowed MIME types
     */
    private const ALLOWED_MIME_TYPES = [
        'application/pdf',
        'image/jpeg',
        'image/png',
        'image/gif',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'text/plain',
        'text/csv',
    ];

    public function __construct(
        private EvidenceService $evidenceService,
        private AuditLogService $auditLogService
    ) {
    }

    /**
     * Store uploaded evidence file
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        // Rate limiting: 60 requests per minute per IP
        $key = 'external_upload:' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 60)) {
            return response()->json([
                'error' => 'Too Many Requests',
                'message' => 'Rate limit exceeded. Please try again later.',
            ], 429);
        }
        RateLimiter::hit($key, 60); // 60 seconds

        // Validate request
        $validated = $request->validate([
            'file' => [
                'required',
                'file',
                'max:' . (self::MAX_FILE_SIZE / 1024), // Laravel expects KB
                'mimes:pdf,jpg,jpeg,png,gif,doc,docx,xls,xlsx,txt,csv',
            ],
            'audit_id' => [
                'required',
                'integer',
                'exists:audits,id',
            ],
        ]);

        $file = $request->file('file');
        
        // Additional MIME type validation
        if (!in_array($file->getMimeType(), self::ALLOWED_MIME_TYPES)) {
            return response()->json([
                'error' => 'Validation Error',
                'message' => 'File type not allowed. Allowed types: PDF, images, Office documents, text files.',
            ], 422);
        }

        // Get audit
        $audit = Audit::findOrFail($validated['audit_id']);

        // Get tenant ID
        $tenantId = tenant('id');
        if (!$tenantId) {
            return response()->json([
                'error' => 'Bad Request',
                'message' => 'Tenant context not found',
            ], 400);
        }

        // Get user from JWT payload
        $jwtPayload = $request->input('jwt_payload');
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'User not found',
            ], 401);
        }

        try {
            // Upload evidence using EvidenceService
            $evidence = $this->evidenceService->upload($file, $audit, $user, $tenantId);

            // Log audit trail
            $this->auditLogService->record('uploaded', $evidence, [
                'source' => 'external_api',
                'filename' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
            ], $request);

            return response()->json([
                'success' => true,
                'data' => [
                    'evidence_id' => $evidence->id,
                    'filename' => $evidence->filename,
                    'version' => $evidence->version,
                    'size' => $evidence->size,
                    'mime_type' => $evidence->mime_type,
                    'checksum' => $evidence->checksum,
                    'uploaded_at' => $evidence->created_at->toIso8601String(),
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Upload Failed',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
