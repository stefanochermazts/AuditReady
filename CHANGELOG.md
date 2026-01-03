# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

#### Audit Relationship Graph Visualization
- **Interactive Graph Visualization**: Added interactive graph visualization feature to display relationships between audits, controls, and evidences
  - Graph widget integrated into Filament ViewAudit page
  - Livewire component (`AuditGraph`) for rendering Cytoscape.js graph
  - Click on nodes to navigate to detailed views (audit, control, or evidence)
  - Visual representation of audit-control-evidence relationships

- **Graph Data Service**: New `AuditService::getGraphData()` method
  - Generates structured JSON data for nodes and edges
  - Supports up to 500 nodes per graph (with truncation warning)
  - Automatic relationship mapping between evidences and controls
  - Eager loading to prevent N+1 queries

- **Caching System**: Performance optimization for graph data
  - 30-minute cache TTL for frequently accessed audits
  - Automatic cache invalidation via Observers (EvidenceObserver, ControlObserver, AuditObserver)
  - Support for tagged cache (Redis/Memcached) with fallback to regular cache
  - Tenant-aware cache keys for multi-tenant isolation

- **Dependencies**: Added Cytoscape.js 3.24+ for graph visualization
  - Installed via NPM as a dependency
  - Integrated with Vite build system
  - Exposed globally for use in Livewire components

- **Documentation**: Comprehensive documentation in `docs/visualization.md`
  - Overview of the graph visualization feature
  - Data model documentation (nodes, edges, types)
  - Installation and usage guides
  - Security considerations
  - Troubleshooting guide
  - API reference

### Changed

- **Audit Model**: Enhanced with direct `controls()` relationship via `audit_control` pivot table
- **Control Model**: Added inverse `audits()` relationship
- **Observer Pattern**: Extended existing observers to invalidate graph cache on data changes

### Technical Details

- **Files Added**:
  - `app/Services/AuditService.php` (enhanced with graph methods)
  - `app/Livewire/AuditGraph.php`
  - `app/Filament/Widgets/AuditGraphWidget.php`
  - `resources/views/livewire/audit-graph.blade.php`
  - `resources/views/filament/widgets/audit-graph-widget.blade.php`
  - `resources/js/graph.js`
  - `app/Observers/EvidenceObserver.php`
  - `app/Observers/ControlObserver.php`
  - `docs/visualization.md`
  - `tests/Unit/AuditServiceCacheTest.php`

- **Files Modified**:
  - `app/Models/Audit.php` (added `controls()` relationship)
  - `app/Models/Control.php` (added `audits()` relationship)
  - `app/Observers/AuditObserver.php` (added cache invalidation)
  - `app/Providers/AppServiceProvider.php` (registered new observers)
  - `app/Filament/Resources/AuditResource/Pages/ViewAudit.php` (added widget)
  - `package.json` (added cytoscape dependency)
  - `resources/js/app.js` (imported graph.js)
  - `README.md` (updated with graph visualization feature)

- **Database Changes**:
  - `audit_control` pivot table (already existed, now used for graph)

## [Previous Versions]

See git history for previous changes.
