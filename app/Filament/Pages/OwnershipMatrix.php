<?php

namespace App\Filament\Pages;

use App\Models\Control;
use App\Models\User;
use App\Services\ControlService;
use App\Services\ExportService;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\URL;

class OwnershipMatrix extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected string $view = 'filament.pages.ownership-matrix';

    protected static ?int $navigationSort = 2;

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-table-cells';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Compliance';
    }

    public ?int $selectedControl = null;

    public function mount(?int $control = null): void
    {
        $this->selectedControl = $control;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('export_pdf')
                ->label('Export PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->action(function () {
                    $exportService = app(ExportService::class);
                    
                    // Get active filters from table
                    $filters = [];
                    if (isset($this->tableFilters['standard']['value']) && $this->tableFilters['standard']['value']) {
                        $filters['standard'] = $this->tableFilters['standard']['value'];
                    }
                    if (isset($this->tableFilters['without_owners']['value']) && $this->tableFilters['without_owners']['value']) {
                        $filters['without_owners'] = true;
                    }
                    
                    $filename = $exportService->exportOwnershipMatrixToPdf($filters);
                    
                    // Generate signed download URL
                    $url = URL::signedRoute('exports.download', [
                        'file' => base64_encode($filename),
                    ], now()->addHours(24));

                    Notification::make()
                        ->title('Export generated')
                        ->success()
                        ->body('Your ownership matrix PDF export has been generated.')
                        ->actions([
                            Actions\Action::make('download')
                                ->label('Download')
                                ->button()
                                ->url($url, shouldOpenInNewTab: true),
                        ])
                        ->send();
                }),
            Actions\Action::make('export_excel')
                ->label('Export Excel')
                ->icon('heroicon-o-table-cells')
                ->action(function () {
                    $exportService = app(ExportService::class);
                    
                    // Get active filters from table
                    $filters = [];
                    if (isset($this->tableFilters['standard']['value']) && $this->tableFilters['standard']['value']) {
                        $filters['standard'] = $this->tableFilters['standard']['value'];
                    }
                    if (isset($this->tableFilters['without_owners']['value']) && $this->tableFilters['without_owners']['value']) {
                        $filters['without_owners'] = true;
                    }
                    
                    $filename = $exportService->exportOwnershipMatrixToExcel($filters);
                    
                    // Generate signed download URL
                    $url = URL::signedRoute('exports.download', [
                        'file' => base64_encode($filename),
                    ], now()->addHours(24));

                    Notification::make()
                        ->title('Export generated')
                        ->success()
                        ->body('Your ownership matrix Excel export has been generated.')
                        ->actions([
                            Actions\Action::make('download')
                                ->label('Download')
                                ->button()
                                ->url($url, shouldOpenInNewTab: true),
                        ])
                        ->send();
                }),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Control::query())
            ->columns([
                Tables\Columns\TextColumn::make('standard')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'DORA' => 'primary',
                        'NIS2' => 'success',
                        'ISO27001' => 'warning',
                        default => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('article_reference')
                    ->label('Reference')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                Tables\Columns\TextColumn::make('category')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('owners_count')
                    ->label('Owners')
                    ->counts('owners')
                    ->badge()
                    ->color(fn ($state) => $state > 0 ? 'success' : 'warning')
                    ->sortable(),
                Tables\Columns\TextColumn::make('owners')
                    ->label('Owner Names')
                    ->formatStateUsing(function (Control $record) {
                        if ($record->owners->isEmpty()) {
                            return 'No owners assigned';
                        }
                        return $record->owners->pluck('name')->join(', ');
                    })
                    ->wrap()
                    ->searchable(false),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('standard')
                    ->options([
                        'DORA' => 'DORA',
                        'NIS2' => 'NIS2',
                        'ISO27001' => 'ISO 27001',
                        'custom' => 'Custom',
                    ]),
                Tables\Filters\Filter::make('without_owners')
                    ->label('Without Owners')
                    ->query(fn (Builder $query) => $query->doesntHave('owners')),
            ])
            ->recordActions([
                Action::make('assign_owner')
                    ->label('Assign Owner')
                    ->icon('heroicon-o-user-plus')
                    ->form([
                        Forms\Components\Select::make('user_id')
                            ->label('User')
                            ->options(User::query()->pluck('name', 'id'))
                            ->searchable()
                            ->required(),
                        Forms\Components\Select::make('responsibility_level')
                            ->label('Responsibility Level')
                            ->options([
                                'primary' => 'Primary',
                                'secondary' => 'Secondary',
                                'consultant' => 'Consultant',
                            ])
                            ->default('primary')
                            ->required(),
                        Forms\Components\TextInput::make('role_name')
                            ->label('Role Name')
                            ->placeholder('e.g., CISO, IT Manager')
                            ->maxLength(255),
                        Forms\Components\Textarea::make('notes')
                            ->label('Notes')
                            ->rows(3),
                    ])
                    ->action(function (Control $record, array $data) {
                        $controlService = app(ControlService::class);
                        $controlService->assignOwner(
                            $record->id,
                            $data['user_id'],
                            $data['responsibility_level'],
                            $data['role_name'] ?? null,
                            $data['notes'] ?? null
                        );

                        Notification::make()
                            ->title('Owner assigned successfully')
                            ->success()
                            ->send();
                    }),
                Action::make('remove_owner')
                    ->label('Remove Owner')
                    ->icon('heroicon-o-user-minus')
                    ->color('danger')
                    ->form([
                        Forms\Components\Select::make('user_id')
                            ->label('User to Remove')
                            ->options(fn (Control $record) => $record->owners->pluck('name', 'id'))
                            ->searchable()
                            ->required(),
                    ])
                    ->action(function (Control $record, array $data) {
                        $controlService = app(ControlService::class);
                        $controlService->removeOwner($record->id, $data['user_id']);

                        Notification::make()
                            ->title('Owner removed successfully')
                            ->success()
                            ->send();
                    })
                    ->visible(fn (Control $record) => $record->owners()->exists()),
            ])
            ->defaultSort('standard');
    }
}
