<?php

namespace App\Filament\Widgets;

use App\Models\Audit;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

/**
 * Filament Widget for displaying the Audit Relationship Graph.
 *
 * This widget integrates the AuditGraph Livewire component into Filament's
 * ViewAudit page, providing an interactive visualization of relationships
 * between audits, controls, and evidences.
 */
class AuditGraphWidget extends Widget
{
    protected string $view = 'filament.widgets.audit-graph-widget';

    /**
     * Make the widget span the full width of the page.
     */
    protected int | string | array $columnSpan = 'full';

    /**
     * The audit record to display in the graph.
     * This property is set via the make() method when registering the widget.
     */
    public ?Audit $record = null;

    /**
     * Check if the widget can be viewed by the current user.
     *
     * Requires the user to be authenticated and have permission to view any audit.
     *
     * @return bool True if the widget can be viewed, false otherwise
     */
    public static function canView(): bool
    {
        return Auth::check() && Auth::user()->can('viewAny', Audit::class);
    }

    /**
     * Get the widget's view data.
     *
     * Returns the audit ID to be passed to the Livewire component.
     *
     * @return array<string, int> Array with 'auditId' key
     */
    protected function getViewData(): array
    {
        return [
            'auditId' => $this->record?->id ?? 0,
        ];
    }
}
