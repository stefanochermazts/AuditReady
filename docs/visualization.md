# Audit Relationship Graph Visualization

## Overview

The Audit Relationship Graph is an interactive visualization feature that displays the relationships between audits, controls, and evidences in a graphical format. This feature helps users understand the complex interconnections within an audit structure at a glance.

### Key Features

- **Interactive Graph**: Click on nodes to navigate to detailed views
- **Real-time Updates**: Graph automatically refreshes when data changes
- **Performance Optimized**: Cached for 30 minutes with automatic invalidation
- **Multi-tenant Safe**: Tenant-aware caching and data isolation
- **RBAC Protected**: Respects user permissions for viewing audits

## Data Model

### Node Types

The graph consists of three types of nodes:

1. **Audit Node** (`type: 'audit'`)
   - Represents an audit record
   - Contains: `id`, `status`, `audit_type`
   - Label: Audit name or "Audit #{id}"

2. **Control Node** (`type: 'control'`)
   - Represents a compliance control
   - Contains: `id`, `standard`, `article_reference`, `category`
   - Label: Control title or "Control #{id}"

3. **Evidence Node** (`type: 'evidence'`)
   - Represents an evidence document
   - Contains: `id`, `category`, `validation_status`, `control_reference`
   - Label: Evidence filename or "Evidence #{id}"

### Edge Types

Edges represent relationships between nodes:

1. **Audit → Control** (`type: 'audit_control'`, `label: 'covers'`)
   - Direct link between audit and control via `audit_control` pivot table

2. **Audit → Evidence** (`type: 'audit_evidence'`, `label: 'has'`)
   - Evidence belongs to an audit

3. **Evidence → Control** (`type: 'evidence_control'`, `label: 'references'`)
   - Evidence references a control (via `control_reference` field)

### Graph Data Structure

```php
[
    'nodes' => [
        [
            'id' => 'audit_1',
            'label' => 'ISO 27001 Audit 2024',
            'type' => 'audit',
            'data' => [
                'id' => 1,
                'status' => 'in_progress',
                'audit_type' => 'internal',
            ],
        ],
        // ... more nodes
    ],
    'edges' => [
        [
            'from' => 'audit_1',
            'to' => 'control_5',
            'label' => 'covers',
            'type' => 'audit_control',
        ],
        // ... more edges
    ],
]
```

## Installation

### Prerequisites

- Laravel 12.x
- Filament 4.4+
- Node.js 18+ and NPM
- Cytoscape.js (installed via NPM)

### Steps

1. **Install NPM Dependencies**

   ```bash
   npm install
   ```

   This will install `cytoscape` (version ^3.24.0) as specified in `package.json`.

2. **Build Assets**

   ```bash
   npm run build
   ```

   Or for development with hot reload:

   ```bash
   npm run dev
   ```

3. **Verify Installation**

   - Navigate to an audit view page in Filament
   - The "Audit Relationship Graph" widget should appear in the header
   - The graph should render with nodes and edges

## Usage

### For End Users

1. **Viewing the Graph**
   - Navigate to any audit's detail page (`/admin/audits/{id}`)
   - The graph widget appears automatically in the header section
   - The graph displays all controls and evidences related to the audit

2. **Interacting with the Graph**
   - **Click a node**: Navigate to the detail page of that item (audit, control, or evidence)
   - **Hover**: See node details in tooltips (if configured)
   - **Refresh**: Click the refresh button to reload graph data

3. **Understanding the Visualization**
   - **Audit nodes** (center): The main audit being viewed
   - **Control nodes**: Compliance controls linked to the audit
   - **Evidence nodes**: Documents that support the audit
   - **Edges**: Lines connecting related items

### For Developers

#### Using AuditService

```php
use App\Services\AuditService;
use App\Models\Audit;

$auditService = app(AuditService::class);
$audit = Audit::find(1);

// Get graph data (cached for 30 minutes)
$graphData = $auditService->getGraphData($audit);

// Manually invalidate cache if needed
$auditService->invalidateGraphCache($audit);
```

#### Using Livewire Component

```blade
<livewire:audit-graph :audit-id="$audit->id" />
```

#### Using Filament Widget

```php
use App\Filament\Widgets\AuditGraphWidget;

protected function getHeaderWidgets(): array
{
    return [
        AuditGraphWidget::make([
            'record' => $this->record,
        ]),
    ];
}
```

## Caching Strategy

### Cache Configuration

- **TTL**: 30 minutes
- **Key Format**: `audit_graph_{audit_id}_tenant_{tenant_id}`
- **Tags**: `['audit_graph', "audit_{audit_id}"]` (if supported by cache driver)

### Automatic Cache Invalidation

The cache is automatically invalidated when:

1. **Evidence Events** (via `EvidenceObserver`):
   - Evidence created
   - Evidence updated
   - Evidence deleted
   - Evidence restored

2. **Control Events** (via `ControlObserver`):
   - Control updated
   - Control deleted
   - Control restored
   - Control attached to audit (pivot)
   - Control detached from audit (pivot)

3. **Audit Events** (via `AuditObserver`):
   - Audit updated
   - Audit deleted
   - Audit restored

### Manual Cache Invalidation

```php
use App\Services\AuditService;

$auditService = app(AuditService::class);
$auditService->invalidateGraphCache($audit);
```

### Cache Driver Compatibility

- **Tagged Cache** (Redis, Memcached): Uses cache tags for efficient invalidation
- **Non-tagged Cache** (File, Database): Falls back to regular cache with key-based invalidation
- **Array Cache**: No persistence (testing only)

## Performance Considerations

### Node Limit

- **Maximum Nodes**: 500 nodes per graph
- **Truncation**: If an audit exceeds 500 nodes, a truncation warning node is added
- **Reason**: Prevents browser performance issues with very large graphs

### Optimization Tips

1. **Use Tagged Cache**: Configure Redis or Memcached for better cache invalidation
2. **Eager Loading**: The service automatically eager loads relationships to avoid N+1 queries
3. **Cache Warm-up**: Consider pre-generating graph data for frequently accessed audits

## Security Considerations

### RBAC Enforcement

- The `AuditGraph` Livewire component checks permissions before loading data:
  ```php
  if (!Auth::user()->can('view', $audit)) {
      abort(403, 'You do not have permission to view this audit.');
  }
  ```

- The widget checks for `viewAny` permission on Audit model

### Tenant Isolation

- Cache keys include tenant ID to prevent cross-tenant data leakage
- Graph data is scoped to the current tenant's database
- All queries respect tenant context

### Data Exposure

- Only metadata is exposed in graph nodes (no sensitive file contents)
- Evidence filenames are shown, but actual files require separate download permissions
- Control references are visible, but full control details require proper permissions

## Troubleshooting

### Graph Not Displaying

1. **Check Browser Console**
   - Look for JavaScript errors
   - Verify Cytoscape.js is loaded: `console.log(window.cytoscape)`

2. **Check Asset Build**
   ```bash
   npm run build
   php artisan filament:optimize-clear
   ```

3. **Check Permissions**
   - Verify user has `view` permission on the audit
   - Check `canView()` method in `AuditGraphWidget`

### Graph Shows Empty

1. **Check Data**
   - Verify audit has controls or evidences attached
   - Check database relationships are correct

2. **Check Cache**
   ```bash
   php artisan cache:clear
   ```

3. **Check Logs**
   - Look for errors in `storage/logs/laravel.log`
   - Search for "Failed to load graph data"

### Performance Issues

1. **Large Graphs**
   - Consider splitting large audits into multiple audits
   - Use filters to reduce node count

2. **Cache Issues**
   - Verify cache driver is configured correctly
   - Check cache storage has sufficient space

3. **Database Queries**
   - Enable query logging to identify slow queries
   - Verify eager loading is working (check `AuditService::generateGraphData()`)

### Cytoscape Not Loading

1. **Check NPM Installation**
   ```bash
   npm list cytoscape
   ```

2. **Check Import**
   - Verify `resources/js/graph.js` exists
   - Check `resources/js/app.js` imports `./graph`

3. **Rebuild Assets**
   ```bash
   npm run build
   ```

## API Reference

### AuditService

#### `getGraphData(Audit $audit): array`

Generates graph data structure for an audit.

**Parameters:**
- `$audit` (Audit): The audit model instance

**Returns:**
- `array`: Graph data with `nodes` and `edges` keys

**Throws:**
- No exceptions (errors are logged)

**Example:**
```php
$graphData = $auditService->getGraphData($audit);
// Returns: ['nodes' => [...], 'edges' => [...]]
```

#### `invalidateGraphCache(Audit $audit): void`

Invalidates the graph cache for a specific audit.

**Parameters:**
- `$audit` (Audit): The audit model instance

**Returns:**
- `void`

**Example:**
```php
$auditService->invalidateGraphCache($audit);
```

### AuditGraph (Livewire Component)

#### Properties

- `public int $auditId`: The ID of the audit to display
- `public array $graphData`: The graph data structure

#### Methods

- `mount(int $auditId): void`: Initialize component with audit ID
- `loadGraph(): void`: Load graph data from service
- `nodeClicked(string $nodeId): void`: Handle node click event
- `refreshGraph(): void`: Refresh graph data

### AuditGraphWidget (Filament Widget)

#### Properties

- `public ?Audit $record`: The audit record to display

#### Methods

- `canView(): bool`: Check if widget can be viewed (static)
- `getViewData(): array`: Get view data for the widget

## Future Enhancements

Potential improvements for future versions:

1. **Filtering**: Filter nodes by type, status, or date range
2. **Search**: Search for specific nodes in the graph
3. **Export**: Export graph as image (PNG/SVG) or data (JSON)
4. **Layouts**: Different graph layouts (hierarchical, force-directed, etc.)
5. **Zoom/Pan**: Better navigation controls for large graphs
6. **Node Details**: Show more details in tooltips or side panels
7. **Edge Labels**: Show relationship types on edges
8. **Color Coding**: Color nodes by status, type, or other attributes

## Related Documentation

- [Architecture Overview](../docs/architecture.md)
- [Multi-Database Tenant Strategy](../docs/multi-database-tenant-strategy.md)
- [Filament Integration](../docs/filament-integration.md)
