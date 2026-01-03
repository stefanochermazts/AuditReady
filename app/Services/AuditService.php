<?php

namespace App\Services;

use App\Models\Audit;
use App\Models\Control;
use App\Models\Evidence;
use App\Models\Policy;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AuditService
{
    /**
     * Maximum number of nodes allowed in the graph before truncation.
     */
    private const MAX_NODES = 500;

    /**
     * Cache TTL in minutes for graph data.
     */
    private const CACHE_TTL_MINUTES = 30;

    /**
     * Get graph data structure for an audit, including nodes (audit, controls, evidences)
     * and edges (relationships between them).
     *
     * Results are cached for 30 minutes to improve performance on frequently accessed audits.
     * Cache is automatically invalidated when evidences or controls are modified.
     *
     * @param Audit $audit The audit to generate graph data for
     * @return array{ nodes: array<int, array<string, mixed>>, edges: array<int, array<string, string>> }
     */
    public function getGraphData(Audit $audit): array
    {
        $tenantId = tenant('id') ?? 'system';
        $cacheKey = "audit_graph_{$audit->id}_tenant_{$tenantId}";
        $cacheTags = ['audit_graph', "audit_{$audit->id}"];

        // Tenancy may wrap the cache store. The most reliable way to detect tag support is to try.
        try {
            return Cache::tags($cacheTags)->remember($cacheKey, now()->addMinutes(self::CACHE_TTL_MINUTES), function () use ($audit) {
                return $this->generateGraphData($audit);
            });
        } catch (\BadMethodCallException $e) {
            // Stancl's CacheTenancyBootstrapper scopes cache via tags. If the store doesn't support tags,
            // ANY cache operation (including Cache::remember / Cache::forget) will fail. In that case,
            // bypass caching entirely.
            Log::debug("Cache tags not supported, bypassing cache for audit graph", [
                'tenant_id' => $tenantId,
                'audit_id' => $audit->id,
            ]);

            return $this->generateGraphData($audit);
        }
    }

    /**
     * Generate graph data structure for an audit (internal method, not cached).
     *
     * @param Audit $audit The audit to generate graph data for
     * @return array{ nodes: array<int, array<string, mixed>>, edges: array<int, array<string, string>> }
     */
    private function generateGraphData(Audit $audit): array
    {
        // Eager load relationships to avoid N+1 queries
        $audit->load(['evidences', 'controls.policies', 'creator', 'auditor']);

        // Get controls directly linked to this audit through pivot table
        $controls = $audit->controls;
        $evidences = $audit->evidences;

        $nodes = [];
        $edges = [];
        $nodeCount = 0;

        // Add audit node
        $auditNodeId = "audit_{$audit->id}";
        $nodes[] = [
            'id' => $auditNodeId,
            'label' => $audit->name ?? "Audit #{$audit->id}",
            'type' => 'audit',
            'data' => [
                'id' => $audit->id,
                'status' => $audit->status,
                'audit_type' => $audit->audit_type,
            ],
        ];
        $nodeCount++;

        // Add control nodes
        $controlMap = []; // Map control IDs to node IDs for edge creation
        $policyNodeMap = []; // Map policy IDs to node IDs (deduplicate across controls)

        foreach ($controls as $control) {
            if ($nodeCount >= self::MAX_NODES) {
                break;
            }

            $controlNodeId = "control_{$control->id}";
            $controlMap[$control->id] = $controlNodeId;
            if (! empty($control->article_reference)) {
                $controlMap[$control->article_reference] = $controlNodeId; // Also map by reference
            }

            $nodes[] = [
                'id' => $controlNodeId,
                'label' => $control->title ?? "Control #{$control->id}",
                'type' => 'control',
                'data' => [
                    'id' => $control->id,
                    'standard' => $control->standard,
                    'article_reference' => $control->article_reference,
                    'category' => $control->category,
                ],
            ];
            $nodeCount++;

            // Add edge: Audit → Control
            $edges[] = [
                'from' => $auditNodeId,
                'to' => $controlNodeId,
                'label' => 'covers',
                'type' => 'audit_control',
            ];

            // Add policy nodes connected to the control
            foreach ($control->policies ?? [] as $policy) {
                if ($nodeCount >= self::MAX_NODES) {
                    break 2; // exit policies loop and controls loop
                }

                $policyNodeId = $policyNodeMap[$policy->id] ?? "policy_{$policy->id}";

                if (! isset($policyNodeMap[$policy->id])) {
                    $policyNodeMap[$policy->id] = $policyNodeId;

                    $label = $policy->name ?? "Policy #{$policy->id}";
                    if (! empty($policy->version)) {
                        $label .= " (v{$policy->version})";
                    }

                    $nodes[] = [
                        'id' => $policyNodeId,
                        'label' => $label,
                        'type' => 'policy',
                        'data' => [
                            'id' => $policy->id,
                            'version' => $policy->version,
                            'approval_date' => optional($policy->approval_date)->format('Y-m-d'),
                            'has_file' => $policy->hasFile(),
                            'has_link' => $policy->hasLink(),
                        ],
                    ];
                    $nodeCount++;
                }

                $edges[] = [
                    'from' => $controlNodeId,
                    'to' => $policyNodeId,
                    'label' => 'mapped to',
                    'type' => 'control_policy',
                ];
            }
        }

        // Add evidence nodes
        foreach ($evidences as $evidence) {
            if ($nodeCount >= self::MAX_NODES) {
                break;
            }

            $evidenceNodeId = "evidence_{$evidence->id}";
            $nodes[] = [
                'id' => $evidenceNodeId,
                'label' => $evidence->filename ?? "Evidence #{$evidence->id}",
                'type' => 'evidence',
                'data' => [
                    'id' => $evidence->id,
                    'category' => $evidence->category,
                    'validation_status' => $evidence->validation_status,
                    'control_reference' => $evidence->control_reference,
                ],
            ];
            $nodeCount++;

            // Add edge: Audit → Evidence
            $edges[] = [
                'from' => $auditNodeId,
                'to' => $evidenceNodeId,
                'label' => 'has',
                'type' => 'audit_evidence',
            ];

            // Add edge: Evidence → Control (if control_reference matches)
            if (!empty($evidence->control_reference)) {
                $matchedControlNodeId = $this->findMatchingControlNode(
                    $evidence->control_reference,
                    $controlMap,
                    $controls
                );

                if ($matchedControlNodeId) {
                    $edges[] = [
                        'from' => $evidenceNodeId,
                        'to' => $matchedControlNodeId,
                        'label' => 'references',
                        'type' => 'evidence_control',
                    ];
                } else {
                    // Log warning for missing control reference (tenant-aware)
                    Log::warning("Evidence {$evidence->id} references control '{$evidence->control_reference}' which does not exist in audit {$audit->id}", [
                        'tenant_id' => tenant('id'),
                        'evidence_id' => $evidence->id,
                        'control_reference' => $evidence->control_reference,
                    ]);
                }
            }
        }

        // Handle truncation if we hit the limit
        if ($nodeCount >= self::MAX_NODES) {
            $nodes[] = [
                'id' => 'truncated',
                'label' => 'Graph truncated (max 500 nodes)',
                'type' => 'truncated',
                'data' => [
                    'message' => 'This audit contains more than 500 nodes. Only the first 500 are shown.',
                ],
            ];

            // Add edge from audit to truncated node
            $edges[] = [
                'from' => $auditNodeId,
                'to' => 'truncated',
                'label' => 'truncated',
                'type' => 'truncated',
            ];
        }

        return [
            'nodes' => $nodes,
            'edges' => $edges,
        ];
    }

    /**
     * Invalidate graph cache for a specific audit.
     *
     * @param Audit $audit The audit to invalidate cache for
     * @return void
     */
    public function invalidateGraphCache(Audit $audit): void
    {
        $tenantId = tenant('id') ?? 'system';
        $cacheKey = "audit_graph_{$audit->id}_tenant_{$tenantId}";
        $cacheTags = ['audit_graph', "audit_{$audit->id}"];

        try {
            Cache::tags($cacheTags)->flush();

            Log::debug("Invalidated graph cache for audit {$audit->id}", [
                'tenant_id' => $tenantId,
                'audit_id' => $audit->id,
            ]);

            return;
        } catch (\BadMethodCallException $e) {
            // If tags aren't supported, Stancl will also tag plain cache calls, so we cannot safely call
            // Cache::forget(). Best effort: do nothing and let the short TTL handle it (or disable caching).
            Log::debug("Cache tags not supported, skipping graph cache invalidation for audit {$audit->id}", [
                'tenant_id' => $tenantId,
                'audit_id' => $audit->id,
                'cache_key' => $cacheKey,
            ]);
        }
    }

    /**
     * Find matching control node ID for a given control reference.
     *
     * This method attempts to match a control reference from an evidence to an actual
     * control node in the graph. It tries multiple matching strategies:
     * 1. Direct lookup in controlMap (by ID or article_reference)
     * 2. Numeric ID matching
     * 3. Article reference or title matching (case-insensitive partial match)
     *
     * @param string $controlReference The control reference from evidence (can be ID, article_reference, or title)
     * @param array<string, string> $controlMap Map of control IDs/references to node IDs
     * @param Collection<int, Control> $controls Collection of controls to search
     * @return string|null The matching control node ID (format: "control_{id}") or null if not found
     */
    private function findMatchingControlNode(
        string $controlReference,
        array $controlMap,
        Collection $controls
    ): ?string {
        // First, try direct lookup in controlMap (by ID or article_reference)
        if (isset($controlMap[$controlReference])) {
            return $controlMap[$controlReference];
        }

        // Try to find by control ID (if reference is numeric)
        if (is_numeric($controlReference)) {
            $controlId = (int) $controlReference;
            if (isset($controlMap[$controlId])) {
                return $controlMap[$controlId];
            }
        }

        // Try to find by article_reference or title match
        $control = $controls->first(function (Control $control) use ($controlReference) {
            return $control->article_reference === $controlReference
                || $control->id == $controlReference
                || str_contains(strtolower($control->title ?? ''), strtolower($controlReference));
        });

        if ($control) {
            return "control_{$control->id}";
        }

        return null;
    }
}
