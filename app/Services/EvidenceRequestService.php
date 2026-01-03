<?php

namespace App\Services;

use App\Models\Audit;
use App\Models\Control;
use App\Models\Evidence;
use App\Models\EvidenceRequest;
use App\Models\EvidenceRequestLog;
use App\Models\ThirdPartySupplier;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class EvidenceRequestService
{
    public function __construct(
        private EvidenceService $evidenceService,
        private StorageService $storageService,
        private EncryptionService $encryptionService
    ) {
    }

    /**
     * Create a new evidence request.
     *
     * @param int $controlId
     * @param int $supplierId
     * @param int $requestedById
     * @param int|null $auditId
     * @param int $expirationDays
     * @param string|null $message
     * @return EvidenceRequest
     */
    public function createRequest(
        int $controlId,
        int $supplierId,
        int $requestedById,
        ?int $auditId = null,
        int $expirationDays = 30,
        ?string $message = null
    ): EvidenceRequest {
        $publicToken = $this->generatePublicToken();

        $request = EvidenceRequest::create([
            'audit_id' => $auditId,
            'control_id' => $controlId,
            'supplier_id' => $supplierId,
            'requested_by' => $requestedById,
            'public_token' => $publicToken,
            'expires_at' => now()->addDays($expirationDays),
            'status' => 'pending',
            'requested_at' => now(),
            'message' => $message,
        ]);

        // Log creation
        $this->logAction($request, 'created', [
            'requested_by' => $requestedById,
            'expiration_days' => $expirationDays,
        ]);

        Log::info("Evidence request created", [
            'request_id' => $request->id,
            'control_id' => $controlId,
            'supplier_id' => $supplierId,
        ]);

        return $request;
    }

    /**
     * Generate a unique public token for the request.
     *
     * @return string
     */
    public function generatePublicToken(): string
    {
        do {
            $token = Str::random(64);
        } while (EvidenceRequest::where('public_token', $token)->exists());

        return $token;
    }

    /**
     * Generate a signed public URL for the request.
     * 
     * Note: The token itself provides security (64 random characters).
     * Signed URLs could be added as an additional layer, but for public
     * access without authentication, the long random token is sufficient.
     *
     * @param EvidenceRequest $request
     * @return string
     */
    public function generatePublicUrl(EvidenceRequest $request): string
    {
        // For now, we use the token-based URL (secure enough with 64-char random token)
        // If additional security is needed, we could wrap this in a signed URL:
        // return URL::temporarySignedRoute('public.evidence-request.show', 
        //     $request->expires_at, ['token' => $request->public_token]);
        
        return route('public.evidence-request.show', [
            'token' => $request->public_token,
        ]);
    }

    /**
     * Handle public file upload.
     *
     * @param EvidenceRequest $request
     * @param array $files
     * @return array Array of created evidence IDs
     */
    public function handlePublicUpload(EvidenceRequest $request, array $files): array
    {
        if ($request->isExpired()) {
            throw new \Exception('This evidence request has expired.');
        }

        if ($request->isCompleted()) {
            throw new \Exception('This evidence request has already been completed.');
        }

        if ($request->status !== 'pending') {
            throw new \Exception('This evidence request is not in a valid state for upload.');
        }

        $evidenceIds = [];

        DB::transaction(function () use ($request, $files, &$evidenceIds) {
            foreach ($files as $file) {
                $evidence = $this->autoCreateEvidence($request, $file);
                $evidenceIds[] = $evidence->id;
            }

            // Mark request as completed
            $request->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            // Log completion
            $this->logAction($request, 'file_uploaded', [
                'files_count' => count($files),
                'evidence_ids' => $evidenceIds,
            ]);
        });

        Log::info("Evidence request completed via public upload", [
            'request_id' => $request->id,
            'evidence_count' => count($evidenceIds),
        ]);

        return $evidenceIds;
    }

    /**
     * Automatically create evidence from uploaded file.
     *
     * @param EvidenceRequest $request
     * @param \Illuminate\Http\UploadedFile $file
     * @return Evidence
     */
    public function autoCreateEvidence(EvidenceRequest $request, $file): Evidence
    {
        $audit = $request->audit;
        
        if (!$audit) {
            throw new \Exception('Evidence request must be linked to an audit to create evidence.');
        }

        $tenantId = tenant('id') ?? 'system';

        // Create a system user for uploads (or use the requested_by user)
        // For now, we'll use the requested_by user, but mark it as system-uploaded in notes
        $uploader = $request->requestedBy;

        // Use EvidenceService to handle the upload
        $evidence = $this->evidenceService->upload($file, $audit, $uploader, $tenantId);

        // Update evidence with additional metadata
        $controlReference = $request->control->article_reference ?? $request->control->title;
        $evidence->update([
            'evidence_request_id' => $request->id,
            'category' => 'Third-Party Evidence',
            'supplier' => $request->supplier->name, // Save supplier name for display
            'notes' => "Uploaded via evidence request #{$request->id} from supplier: {$request->supplier->name}. Control: {$controlReference}",
        ]);

        return $evidence;
    }

    /**
     * Log an action for an evidence request.
     *
     * @param EvidenceRequest $request
     * @param string $action
     * @param array|null $metadata
     * @return EvidenceRequestLog
     */
    public function logAction(EvidenceRequest $request, string $action, ?array $metadata = null): EvidenceRequestLog
    {
        // Enhanced metadata with additional security context
        $enhancedMetadata = array_merge($metadata ?? [], [
            'timestamp' => now()->toIso8601String(),
            'request_method' => request()->method(),
            'referer' => request()->header('referer'),
        ]);

        $log = EvidenceRequestLog::create([
            'evidence_request_id' => $request->id,
            'action' => $action,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'metadata' => $enhancedMetadata,
            'created_at' => now(),
        ]);

        // Also log to application log for security monitoring
        Log::info("Evidence request action logged", [
            'request_id' => $request->id,
            'action' => $action,
            'ip' => request()->ip(),
            'metadata' => $enhancedMetadata,
        ]);

        return $log;
    }

    /**
     * Expire old requests automatically.
     *
     * @return int Number of expired requests
     */
    public function expireRequests(): int
    {
        $expired = EvidenceRequest::where('status', 'pending')
            ->where('expires_at', '<', now())
            ->get();

        foreach ($expired as $request) {
            $request->update(['status' => 'expired']);
            $this->logAction($request, 'expired');
        }

        return $expired->count();
    }

    /**
     * Cancel an evidence request.
     *
     * @param EvidenceRequest $request
     * @param int $cancelledById
     * @return EvidenceRequest
     */
    public function cancelRequest(EvidenceRequest $request, int $cancelledById): EvidenceRequest
    {
        if ($request->isCompleted()) {
            throw new \Exception('Cannot cancel a completed request.');
        }

        $request->update(['status' => 'cancelled']);

        $this->logAction($request, 'cancelled', [
            'cancelled_by' => $cancelledById,
        ]);

        return $request;
    }

    /**
     * Get request by public token.
     *
     * @param string $token
     * @return EvidenceRequest|null
     */
    public function getRequestByToken(string $token): ?EvidenceRequest
    {
        return EvidenceRequest::where('public_token', $token)
            ->with(['control', 'supplier', 'audit'])
            ->first();
    }
}
