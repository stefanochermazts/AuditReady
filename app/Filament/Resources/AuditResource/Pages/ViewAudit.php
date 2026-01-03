<?php

namespace App\Filament\Resources\AuditResource\Pages;

use App\Filament\Resources\AuditResource;
use App\Filament\Widgets\AuditGraphWidget;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewAudit extends ViewRecord
{
    protected static string $resource = AuditResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            AuditGraphWidget::make([
                'record' => $this->record,
            ]),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('generate_audit_day_pack')
                ->label('Generate Audit Day Pack')
                ->icon('heroicon-o-archive-box')
                ->color('info')
                ->form([
                    \Filament\Forms\Components\Select::make('format')
                        ->label('Format')
                        ->options([
                            'zip' => 'ZIP (Organized folders)',
                            'pdf' => 'PDF (Single document)',
                            'both' => 'Both (ZIP + PDF)',
                        ])
                        ->default('both')
                        ->required(),
                    \Filament\Forms\Components\Toggle::make('include_all_evidences')
                        ->label('Include All Evidences')
                        ->helperText('If disabled, only approved evidences will be included')
                        ->default(true),
                    \Filament\Forms\Components\Toggle::make('include_full_audit_trail')
                        ->label('Include Full Audit Trail')
                        ->helperText('If disabled, only summary will be included')
                        ->default(true),
                ])
                ->action(function (array $data) {
                    try {
                        $packService = app(\App\Services\AuditDayPackService::class);
                        $pack = $packService->generatePack($this->record, [
                            'format' => $data['format'],
                            'include_all_evidences' => $data['include_all_evidences'],
                            'include_full_audit_trail' => $data['include_full_audit_trail'],
                            'generated_by' => auth()->id(),
                        ]);

                        \Filament\Notifications\Notification::make()
                            ->title('Audit Day Pack Generated')
                            ->success()
                            ->body('The audit day pack has been generated successfully.')
                            ->actions([
                                \Filament\Actions\Action::make('download')
                                    ->label('Download')
                                    ->url(route('audit-day-pack.download', ['pack' => $pack->id]))
                                    ->openUrlInNewTab(),
                            ])
                            ->send();
                    } catch (\Exception $e) {
                        \Filament\Notifications\Notification::make()
                            ->title('Generation Failed')
                            ->danger()
                            ->body('Failed to generate audit day pack: ' . $e->getMessage())
                            ->send();
                    }
                })
                ->visible(fn () => auth()->user()->hasAnyRole(['Organization Owner', 'Audit Manager'])),
            Actions\EditAction::make(),
            Actions\Action::make('close')
                ->label('Close Audit')
                ->icon('heroicon-o-lock-closed')
                ->color('success')
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->update([
                        'status' => 'closed',
                        'closed_at' => now(),
                    ]);
                    $this->redirect(static::getResource()::getUrl('index'));
                })
                ->visible(fn () => $this->record->status !== 'closed' && auth()->user()->can('close', $this->record)),
        ];
    }
}
