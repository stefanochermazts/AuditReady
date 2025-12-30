<?php

namespace App\Http\Controllers;

use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * AuditLog Controller - API for querying audit logs
 * 
 * This controller provides endpoints for owners/managers to query
 * audit logs with pagination, filters, and export capabilities.
 */
class AuditLogController extends Controller
{
    public function __construct(
        private AuditLogService $auditLogService
    ) {
    }

    /**
     * List audit logs with filters
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        // Only Organization Owners and Audit Managers can view audit logs
        Gate::authorize('viewAuditLogs');
        
        $filters = $request->only([
            'user_id',
            'action',
            'model_type',
            'model_id',
            'date_from',
            'date_to',
        ]);
        
        $perPage = $request->input('per_page', 50);
        $perPage = min(max($perPage, 1), 100); // Limit between 1 and 100
        
        $logs = $this->auditLogService->query($filters, $perPage);
        
        return response()->json([
            'data' => $logs->items(),
            'meta' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
            ],
        ]);
    }

    /**
     * Show a specific audit log entry
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        Gate::authorize('viewAuditLogs');
        
        $log = \App\Models\AuditLog::findOrFail($id);
        
        // Verify signature
        $isValid = $this->auditLogService->verifySignature($log);
        
        return response()->json([
            'data' => $log,
            'signature_valid' => $isValid,
        ]);
    }

    /**
     * Export audit logs as CSV
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function exportCsv(Request $request)
    {
        Gate::authorize('exportAuditLogs');
        
        $filters = $request->only([
            'user_id',
            'action',
            'model_type',
            'model_id',
            'date_from',
            'date_to',
        ]);
        
        // Get all matching logs (no pagination for export)
        $query = \App\Models\AuditLog::query();
        $tenantId = tenant('id');
        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }
        
        // Apply filters (same logic as query method)
        foreach ($filters as $key => $value) {
            if ($value !== null) {
                $query->where($key, $value);
            }
        }
        
        $logs = $query->orderBy('created_at', 'desc')->get();
        
        $filename = 'audit_logs_' . now()->format('Y-m-d_His') . '.csv';
        
        return response()->streamDownload(function () use ($logs) {
            $handle = fopen('php://output', 'w');
            
            // CSV Header
            fputcsv($handle, [
                'ID',
                'Tenant ID',
                'User ID',
                'Action',
                'Model Type',
                'Model ID',
                'IP Address',
                'User Agent',
                'Created At',
                'Signature',
            ]);
            
            // CSV Rows
            foreach ($logs as $log) {
                fputcsv($handle, [
                    $log->id,
                    $log->tenant_id,
                    $log->user_id,
                    $log->action,
                    $log->model_type,
                    $log->model_id,
                    $log->ip_address,
                    $log->user_agent,
                    $log->created_at->toIso8601String(),
                    $log->signature,
                ]);
            }
            
            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Export audit logs as JSON
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function exportJson(Request $request): JsonResponse
    {
        Gate::authorize('exportAuditLogs');
        
        $filters = $request->only([
            'user_id',
            'action',
            'model_type',
            'model_id',
            'date_from',
            'date_to',
        ]);
        
        // Get all matching logs
        $query = \App\Models\AuditLog::query();
        $tenantId = tenant('id');
        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }
        
        foreach ($filters as $key => $value) {
            if ($value !== null) {
                $query->where($key, $value);
            }
        }
        
        $logs = $query->orderBy('created_at', 'desc')->get();
        
        $filename = 'audit_logs_' . now()->format('Y-m-d_His') . '.json';
        
        return response()->json($logs->toArray())
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"")
            ->header('Content-Type', 'application/json');
    }
}
