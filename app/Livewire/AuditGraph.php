<?php

namespace App\Livewire;

use App\Models\Audit;
use App\Filament\Resources\AuditResource;
use App\Filament\Resources\ControlResource;
use App\Filament\Resources\EvidenceResource;
use App\Filament\Resources\PolicyResource;
use App\Services\AuditService;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

/**
 * Livewire component for rendering the interactive audit relationship graph.
 *
 * This component uses Cytoscape.js to visualize relationships between audits,
 * controls, and evidences. It handles data loading, caching, and user interactions
 * such as node clicks for navigation.
 */
class AuditGraph extends Component
{
    /**
     * The ID of the audit to display in the graph.
     */
    public int $auditId;

    /**
     * The graph data structure containing nodes and edges.
     *
     * Structure: ['nodes' => [...], 'edges' => [...]]
     * See AuditService::getGraphData() for detailed structure.
     */
    public array $graphData = [];

    /**
     * Mount the component with an audit ID.
     *
     * Performs RBAC check and loads initial graph data.
     *
     * @param int $auditId The ID of the audit to display
     * @return void
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException If audit not found
     * @throws \Illuminate\Auth\Access\AuthorizationException If user lacks permission
     */
    public function mount(int $auditId): void
    {
        // Verify RBAC: user must be able to view the audit
        $audit = Audit::findOrFail($auditId);
        
        if (!Auth::user()->can('view', $audit)) {
            abort(403, 'You do not have permission to view this audit.');
        }

        $this->auditId = $auditId;
        $this->loadGraph();
    }

    /**
     * Load graph data from AuditService.
     *
     * Fetches graph data (cached) and handles errors gracefully.
     * On error, sets empty graph data and displays a notification.
     *
     * @return void
     */
    public function loadGraph(): void
    {
        try {
            $audit = Audit::findOrFail($this->auditId);
            $auditService = app(AuditService::class);
            $this->graphData = $auditService->getGraphData($audit);

            // Debug logging (avoid huge payloads unless APP_DEBUG=true)
            $context = [
                'tenant_id' => tenant('id'),
                'audit_id' => $this->auditId,
                'nodes_count' => count($this->graphData['nodes'] ?? []),
                'edges_count' => count($this->graphData['edges'] ?? []),
            ];
            if (config('app.debug')) {
                $context['graph_data'] = $this->graphData;
            }
            Log::debug("Graph data loaded for audit {$this->auditId}", $context);
        } catch (\Exception $e) {
            Log::error("Failed to load graph data for audit {$this->auditId}: " . $e->getMessage(), [
                'tenant_id' => tenant('id'),
                'audit_id' => $this->auditId,
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
            ]);

            // Set empty graph data on error
            $this->graphData = [
                'nodes' => [],
                'edges' => [],
            ];

            Notification::make()
                ->title('Error Loading Graph')
                ->danger()
                ->body('Failed to load the relationship graph. Please try refreshing the page.')
                ->send();
        }
    }

    /**
     * Handle node click event from Cytoscape.js.
     *
     * Parses the node ID (format: "type_id") and redirects to the appropriate
     * Filament resource view page. Supports 'audit', 'control', and 'evidence' types.
     *
     * @param string $nodeId The ID of the clicked node (format: "type_id", e.g., "audit_1", "control_5")
     * @return void
     */
    public function nodeClicked(string $nodeId): void
    {
        // Parse node ID (format: "type_id")
        if (!str_contains($nodeId, '_')) {
            Log::warning("Invalid node ID format: {$nodeId}");
            return;
        }

        [$type, $id] = explode('_', $nodeId, 2);

        // Validate ID is numeric
        if (!is_numeric($id)) {
            Log::warning("Invalid node ID (non-numeric): {$nodeId}");
            return;
        }

        try {
            $url = match ($type) {
                'audit' => AuditResource::getUrl('view', ['record' => $id]),
                'control' => ControlResource::getUrl('view', ['record' => $id]),
                'evidence' => EvidenceResource::getUrl('view', ['record' => $id]),
                'policy' => PolicyResource::getUrl('view', ['record' => $id]),
                default => null,
            };

            if ($url) {
                $this->redirect($url);
            }
        } catch (\Exception $e) {
            Log::error("Failed to navigate to node: {$nodeId}", [
                'exception' => $e,
            ]);

            Notification::make()
                ->title('Navigation Error')
                ->danger()
                ->body('Failed to navigate to the selected item.')
                ->send();
        }
    }

    /**
     * Refresh the graph data.
     *
     * Forces a reload of graph data from the service, bypassing cache.
     * Useful for manual refresh after data changes.
     *
     * @return void
     */
    public function refreshGraph(): void
    {
        $this->loadGraph();
    }

    /**
     * Render the component.
     *
     * Returns the Blade view for the audit graph component.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function render()
    {
        return view('livewire.audit-graph');
    }
}
