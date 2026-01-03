<?php

namespace App\Filament\Pages;

use App\Models\GapSnapshot;
use App\Services\ExportService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\URL;

class ViewGapSnapshotReport extends Page
{
    protected static ?string $slug = 'view-gap-snapshot-report';

    protected string $view = 'filament.pages.view-gap-snapshot-report';

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-document-text';
    }

    public ?GapSnapshot $snapshot = null;

    public function mount(?int $snapshot = null): void
    {
        // Get snapshot ID from query parameter if not provided as route parameter
        if (!$snapshot && request()->has('snapshot')) {
            $snapshot = (int) request()->query('snapshot');
        }

        if ($snapshot) {
            $this->snapshot = GapSnapshot::with(['responses.control', 'completedBy', 'audit'])->findOrFail($snapshot);
        } else {
            abort(404, 'Snapshot not found');
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('export_pdf')
                ->label('Export PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->action(function () {
                    $exportService = app(ExportService::class);
                    $filename = $exportService->exportGapSnapshotToPdf($this->snapshot);
                    
                    // Generate signed download URL
                    $url = URL::signedRoute('exports.download', [
                        'file' => base64_encode($filename),
                    ], now()->addHours(24));

                    Notification::make()
                        ->title('Export generated')
                        ->success()
                        ->body('Your gap snapshot PDF export has been generated.')
                        ->actions([
                            Actions\Action::make('download')
                                ->label('Download')
                                ->button()
                                ->url($url, shouldOpenInNewTab: true),
                        ])
                        ->send();
                }),
            Actions\Action::make('back')
                ->label('Back to Snapshot')
                ->icon('heroicon-o-arrow-left')
                ->url(fn () => \App\Filament\Resources\GapSnapshotResource::getUrl('view', ['record' => $this->snapshot->id])),
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false; // Hide from navigation, accessed via resource
    }
}
