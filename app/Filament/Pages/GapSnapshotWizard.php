<?php

namespace App\Filament\Pages;

use App\Models\GapSnapshot;
use App\Models\Control;
use App\Models\Evidence;
use App\Filament\Resources\GapSnapshotResource;
use App\Services\GapSnapshotService;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Illuminate\Support\Collection;

class GapSnapshotWizard extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $slug = 'gap-snapshot-wizard';

    protected string $view = 'filament.pages.gap-snapshot-wizard';

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-clipboard-document-list';
    }

    public ?GapSnapshot $snapshot = null;
    public array $responses = [];
    public int $currentStep = 0;
    public array $steps = [];

    protected function getGapSnapshotService(): GapSnapshotService
    {
        return app(GapSnapshotService::class);
    }

    public function mount(?int $snapshot = null): void
    {
        // Get snapshot ID from query parameter if not provided as route parameter
        if (!$snapshot && request()->has('snapshot')) {
            $snapshot = (int) request()->query('snapshot');
        }

        if ($snapshot) {
            $this->snapshot = GapSnapshot::with('responses.control')->findOrFail($snapshot);
            
            // Load existing responses
            foreach ($this->snapshot->responses as $response) {
                $this->responses[$response->control_id] = [
                    'response' => $response->response,
                    'notes' => $response->notes,
                    'evidence_ids' => $response->evidence_ids ?? [],
                ];
            }
        } else {
            abort(404, 'Snapshot not found');
        }
        
        // Build steps (one per category)
        $this->buildSteps();
    }

    protected function buildSteps(): void
    {
        $this->steps = [];
        $stepIndex = 0;

        // Get controls for the snapshot's standard
        $controls = $this->getGapSnapshotService()->getControlsForStandard($this->snapshot->standard);
        
        // Group controls by category and convert to array
        $controlsByCategory = $controls->groupBy(function ($control) {
            return $control->category ?? 'Uncategorized';
        });

        foreach ($controlsByCategory as $category => $categoryControls) {
            $this->steps[] = [
                'id' => $stepIndex,
                'label' => $category,
                'controls' => $categoryControls->map(function ($control) {
                    return [
                        'id' => $control->id,
                        'standard' => $control->standard,
                        'article_reference' => $control->article_reference,
                        'title' => $control->title,
                        'description' => $control->description,
                        'category' => $control->category,
                    ];
                })->values()->toArray(),
            ];
            $stepIndex++;
        }
    }

    public function getCompletionPercentage(): float
    {
        if ($this->snapshot) {
            return $this->snapshot->getCompletionPercentage();
        }
        return 0;
    }

    public function saveResponse(int $controlId, string $response, ?string $notes = null, ?array $evidenceIds = null): void
    {
        // Get existing notes if response already exists
        if (isset($this->responses[$controlId])) {
            $notes = $notes ?? $this->responses[$controlId]['notes'] ?? null;
            $evidenceIds = $evidenceIds ?? $this->responses[$controlId]['evidence_ids'] ?? [];
        }

        $this->responses[$controlId] = [
            'response' => $response,
            'notes' => $notes,
            'evidence_ids' => $evidenceIds,
        ];

        $this->getGapSnapshotService()->saveResponse(
            $this->snapshot,
            $controlId,
            $response,
            $notes,
            $evidenceIds
        );

        // Refresh snapshot to update completion percentage
        $this->snapshot->refresh();
    }

    public function saveNotes(int $controlId, string $notes): void
    {
        if (!isset($this->responses[$controlId])) {
            return; // Can't save notes without a response
        }

        $response = $this->responses[$controlId]['response'];
        $evidenceIds = $this->responses[$controlId]['evidence_ids'] ?? [];

        $this->saveResponse($controlId, $response, $notes, $evidenceIds);
    }

    public function saveEvidenceIds(int $controlId, $evidenceIds): void
    {
        if (!isset($this->responses[$controlId])) {
            return; // Can't save evidence without a response
        }

        // Convert to array if it's a string (from select multiple)
        if (is_string($evidenceIds)) {
            $evidenceIds = json_decode($evidenceIds, true) ?? [];
        }
        if (!is_array($evidenceIds)) {
            $evidenceIds = [];
        }

        $response = $this->responses[$controlId]['response'];
        $notes = $this->responses[$controlId]['notes'] ?? null;

        $this->saveResponse($controlId, $response, $notes, $evidenceIds);
    }

    public function getAvailableEvidences(): Collection
    {
        // Get evidences from the linked audit, or all evidences if no audit linked
        if ($this->snapshot->audit_id) {
            return Evidence::where('audit_id', $this->snapshot->audit_id)
                ->orderBy('created_at', 'desc')
                ->get();
        }
        
        // If no audit linked, return empty collection
        // In a real scenario, you might want to show all evidences or filter by standard
        return collect();
    }

    public function completeSnapshot(): void
    {
        $this->getGapSnapshotService()->completeSnapshot($this->snapshot, auth()->id());

        Notification::make()
            ->title('Snapshot completed')
            ->success()
            ->body('The gap snapshot has been marked as completed.')
            ->send();

        $this->redirect(GapSnapshotResource::getUrl('view', ['record' => $this->snapshot->id]));
    }

    public function getTotalControls(): int
    {
        $total = 0;
        foreach ($this->steps as $step) {
            $total += count($step['controls']);
        }
        return $total;
    }

    public function getAnsweredControls(): int
    {
        return count($this->responses);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false; // Hide from navigation, accessed via resource
    }
}
