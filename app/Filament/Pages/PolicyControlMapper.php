<?php

namespace App\Filament\Pages;

use App\Models\Control;
use App\Models\Policy;
use App\Models\PolicyControlMapping;
use App\Services\ExportService;
use App\Services\PolicyService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\URL;

class PolicyControlMapper extends Page
{
    protected string $view = 'filament.pages.policy-control-mapper';
    protected static ?string $title = 'Policy-Control Mapper';

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-link';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Policy Management';
    }

    public static function getNavigationSort(): ?int
    {
        return 2;
    }

    public function editMappingAction(): Action
    {
        return Action::make('editMapping')
            ->label('Edit')
            ->icon('heroicon-o-pencil')
            ->color('primary')
            ->form([
                Select::make('policy_id')
                    ->label('Policy')
                    ->options(Policy::query()->pluck('name', 'id'))
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('control_id')
                    ->label('Control')
                    ->options(
                        Control::query()
                            ->select(['id', 'title', 'article_reference'])
                            ->orderBy('standard')
                            ->orderBy('article_reference')
                            ->orderBy('title')
                            ->get()
                            ->mapWithKeys(fn (Control $control): array => [
                                $control->id => trim(($control->article_reference ? $control->article_reference . ' - ' : '') . $control->title),
                            ])
                            ->all()
                    )
                    ->searchable()
                    ->preload()
                    ->required(),
                Textarea::make('coverage_notes')
                    ->label('Coverage Notes')
                    ->helperText('Describe how this policy covers the selected control')
                    ->rows(3),
            ])
            ->fillForm(function (array $arguments): array {
                $mappingId = $arguments['mappingId'] ?? null;

                if (! $mappingId) {
                    return [];
                }

                $mapping = PolicyControlMapping::findOrFail($mappingId);

                return [
                    'policy_id' => $mapping->policy_id,
                    'control_id' => $mapping->control_id,
                    'coverage_notes' => $mapping->coverage_notes ?? null,
                ];
            })
            ->action(function (array $data, array $arguments): void {
                $mappingId = $arguments['mappingId'] ?? null;

                if (! $mappingId) {
                    Notification::make()
                        ->title('Error')
                        ->danger()
                        ->body('Mapping ID is required.')
                        ->send();

                    return;
                }

                try {
                    $mapping = PolicyControlMapping::findOrFail($mappingId);

                    $oldPolicy = $mapping->policy;
                    $oldControl = $mapping->control;

                    $newPolicy = Policy::findOrFail($data['policy_id']);
                    $newControl = Control::findOrFail($data['control_id']);

                    $policyService = app(PolicyService::class);

                    // If policy or control changed, remove old mapping and create new one
                    if ($mapping->policy_id !== $data['policy_id'] || $mapping->control_id !== $data['control_id']) {
                        $policyService->unmapFromControl($oldPolicy, $oldControl);

                        $policyService->mapToControl(
                            $newPolicy,
                            $newControl,
                            $data['coverage_notes'] ?? null
                        );

                        Notification::make()
                            ->title('Mapping Updated')
                            ->success()
                            ->body("Mapping has been updated: Policy '{$newPolicy->name}' is now mapped to control '{$newControl->title}'.")
                            ->send();

                        return;
                    }

                    // Only update notes if policy and control haven't changed
                    $policyService->mapToControl(
                        $newPolicy,
                        $newControl,
                        $data['coverage_notes'] ?? null
                    );

                    Notification::make()
                        ->title('Mapping Updated')
                        ->success()
                        ->body("Coverage notes for policy '{$newPolicy->name}' and control '{$newControl->title}' have been updated.")
                        ->send();
                } catch (\Exception $e) {
                    Notification::make()
                        ->title('Error')
                        ->danger()
                        ->body($e->getMessage())
                        ->send();
                }
            });
    }

    public function removeMapping(int $mappingId): void
    {
        try {
            $mapping = PolicyControlMapping::findOrFail($mappingId);
            $policy = $mapping->policy;
            $control = $mapping->control;
            
            $policyService = app(PolicyService::class);
            $policyService->unmapFromControl($policy, $control);

            Notification::make()
                ->title('Mapping Removed')
                ->success()
                ->body("Policy '{$policy->name}' has been unmapped from control '{$control->title}'.")
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error')
                ->danger()
                ->body($e->getMessage())
                ->send();
        }
    }

    public function getPoliciesProperty(): Collection
    {
        return Policy::with(['controls', 'owner', 'evidence'])->get();
    }

    public function getControlsProperty(): Collection
    {
        return Control::with(['policies'])->get();
    }

    public function getCoverageGapsProperty(): array
    {
        $policyService = app(PolicyService::class);
        return $policyService->getCoverageGaps();
    }

    public function getStatisticsProperty(): array
    {
        $policyService = app(PolicyService::class);
        return $policyService->getCoverageStatistics();
    }

    public function getMappingsProperty(): Collection
    {
        return PolicyControlMapping::with(['policy.owner', 'policy.evidence', 'control', 'mappedBy'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('create_mapping')
                ->label('Create Mapping')
                ->icon('heroicon-o-plus')
                ->form([
                    Select::make('policy_id')
                        ->label('Policy')
                        ->options(Policy::query()->pluck('name', 'id'))
                        ->searchable()
                        ->preload()
                        ->required(),
                    Select::make('control_id')
                        ->label('Control')
                        ->options(
                            Control::query()
                                ->select(['id', 'title', 'article_reference'])
                                ->orderBy('standard')
                                ->orderBy('article_reference')
                                ->orderBy('title')
                                ->get()
                                ->mapWithKeys(fn (Control $control): array => [
                                    $control->id => trim(($control->article_reference ? $control->article_reference . ' - ' : '') . $control->title),
                                ])
                                ->all()
                        )
                        ->searchable()
                        ->preload()
                        ->required(),
                    Textarea::make('coverage_notes')
                        ->label('Coverage Notes')
                        ->helperText('Describe how this policy covers the selected control')
                        ->rows(3),
                ])
                ->action(function (array $data) {
                    try {
                        $policyService = app(PolicyService::class);
                        $policy = Policy::findOrFail($data['policy_id']);
                        $control = Control::findOrFail($data['control_id']);

                        $policyService->mapToControl(
                            $policy,
                            $control,
                            $data['coverage_notes'] ?? null
                        );

                        Notification::make()
                            ->title('Mapping Created')
                            ->success()
                            ->body("Policy '{$policy->name}' has been mapped to control '{$control->title}'.")
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Error')
                            ->danger()
                            ->body($e->getMessage())
                            ->send();
                    }
                }),
            Action::make('export_coverage_report')
                ->label('Export Coverage Report')
                ->icon('heroicon-o-document-arrow-down')
                ->action(function () {
                    $exportService = app(ExportService::class);

                    $filename = $exportService->exportPolicyCoverageReportToPdf();

                    $url = URL::signedRoute('exports.download', [
                        'file' => base64_encode($filename),
                    ], now()->addHours(24));

                    Notification::make()
                        ->title('Export generated')
                        ->success()
                        ->body('Your policy coverage PDF export has been generated.')
                        ->actions([
                            \Filament\Actions\Action::make('download')
                                ->label('Download')
                                ->button()
                                ->url($url, shouldOpenInNewTab: true),
                        ])
                        ->send();
                }),
        ];
    }
}
