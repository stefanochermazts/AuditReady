<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * AuditLog Service - Centralized Audit Trail Logging
 * 
 * This service handles all audit logging with HMAC signature for tamper detection.
 * All log entries are immutable (append-only).
 */
class AuditLogService
{
    /**
     * Record an audit log entry
     *
     * @param string $action Action performed (created, updated, deleted, etc.)
     * @param Model|null $model Model instance that was affected
     * @param array|null $payload Additional data to log
     * @param Request|null $request Request object for IP and user agent
     * @return AuditLog
     */
    public function record(
        string $action,
        ?Model $model = null,
        ?array $payload = null,
        ?Request $request = null
    ): AuditLog {
        $user = Auth::user();
        $tenantId = tenant('id') ?? 'system';
        
        // Prepare log data
        $logData = [
            'tenant_id' => $tenantId,
            'user_id' => $user?->id,
            'action' => $action,
            'model_type' => $model ? get_class($model) : null,
            'model_id' => $model?->id,
            'payload' => $payload,
            'ip_address' => $request?->ip() ?? request()->ip(),
            'user_agent' => $request?->userAgent() ?? request()->userAgent(),
            'created_at' => now(),
        ];
        
        // Generate HMAC signature
        $signature = $this->generateSignature($logData);
        $logData['signature'] = $signature;
        
        // Create log entry (immutable)
        return AuditLog::create($logData);
    }

    /**
     * Generate HMAC-SHA256 signature for log entry
     *
     * @param array $data
     * @return string
     */
    private function generateSignature(array $data): string
    {
        // Create a canonical representation of the data
        $canonical = json_encode([
            'tenant_id' => $data['tenant_id'],
            'user_id' => $data['user_id'],
            'action' => $data['action'],
            'model_type' => $data['model_type'],
            'model_id' => $data['model_id'],
            'payload' => $data['payload'],
            'ip_address' => $data['ip_address'],
            'user_agent' => $data['user_agent'],
            'created_at' => $data['created_at']->toIso8601String(),
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        
        // Use app key as HMAC secret
        $secret = config('app.key');
        
        // Generate HMAC-SHA256
        return hash_hmac('sha256', $canonical, $secret);
    }

    /**
     * Verify signature of a log entry
     *
     * @param AuditLog $log
     * @return bool
     */
    public function verifySignature(AuditLog $log): bool
    {
        $data = [
            'tenant_id' => $log->tenant_id,
            'user_id' => $log->user_id,
            'action' => $log->action,
            'model_type' => $log->model_type,
            'model_id' => $log->model_id,
            'payload' => $log->payload,
            'ip_address' => $log->ip_address,
            'user_agent' => $log->user_agent,
            'created_at' => $log->created_at->toIso8601String(),
        ];
        
        $expectedSignature = $this->generateSignature($data);
        
        return hash_equals($expectedSignature, $log->signature);
    }

    /**
     * Query audit logs with filters
     *
     * @param array $filters
     * @param int $perPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function query(array $filters = [], int $perPage = 50)
    {
        $query = AuditLog::query();
        
        // Tenant isolation (should already be enforced by tenancy, but double-check)
        $tenantId = tenant('id');
        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }
        
        // Apply filters
        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }
        
        if (isset($filters['action'])) {
            $query->where('action', $filters['action']);
        }
        
        if (isset($filters['model_type'])) {
            $query->where('model_type', $filters['model_type']);
        }
        
        if (isset($filters['model_id'])) {
            $query->where('model_id', $filters['model_id']);
        }
        
        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }
        
        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }
        
        // Order by created_at descending (newest first)
        $query->orderBy('created_at', 'desc');
        
        return $query->paginate($perPage);
    }
}
