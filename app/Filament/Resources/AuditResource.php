<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AuditResource\Pages;
use App\Filament\Support\StatusBadgeHelper;
use App\Models\Audit;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AuditResource extends Resource
{
    protected static ?string $model = Audit::class;

    protected static ?int $navigationSort = 1;

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-clipboard-document-check';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Audit Management';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                // Basic Information
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('description')
                    ->maxLength(65535)
                    ->columnSpanFull(),
                Forms\Components\Select::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'in_progress' => 'In Progress',
                        'closed' => 'Closed',
                    ])
                    ->default('draft')
                    ->required(),
                Forms\Components\Select::make('audit_type')
                    ->label('Audit Type')
                    ->options([
                        'internal' => 'Internal',
                        'external' => 'External',
                        'certification' => 'Certification',
                        'compliance' => 'Compliance',
                    ])
                    ->default('internal')
                    ->required(),
                Forms\Components\Select::make('compliance_standards')
                    ->label('Compliance Standards')
                    ->multiple()
                    ->options([
                        'ISO 27001' => 'ISO 27001',
                        'ISO 27002' => 'ISO 27002',
                        'GDPR' => 'GDPR',
                        'DORA' => 'DORA',
                        'NIS2' => 'NIS2',
                        'SOC 2' => 'SOC 2',
                        'PCI-DSS' => 'PCI-DSS',
                        'HIPAA' => 'HIPAA',
                    ])
                    ->searchable()
                    ->columnSpanFull(),
                
                // Scope and Objectives
                Forms\Components\Textarea::make('scope')
                    ->label('Scope')
                    ->rows(3)
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('objectives')
                    ->label('Objectives')
                    ->rows(3)
                    ->columnSpanFull(),
                
                // Dates and Periods
                Forms\Components\DatePicker::make('start_date')
                    ->required(),
                Forms\Components\DatePicker::make('end_date')
                    ->nullable(),
                Forms\Components\DatePicker::make('reference_period_start')
                    ->label('Reference Period Start'),
                Forms\Components\DatePicker::make('reference_period_end')
                    ->label('Reference Period End'),
                Forms\Components\DatePicker::make('next_audit_date')
                    ->label('Next Audit Date'),
                
                // Responsible Parties
                Forms\Components\Select::make('auditor_id')
                    ->label('Auditor')
                    ->relationship('auditor', 'name')
                    ->searchable()
                    ->preload(),
                
                // Compliance References
                Forms\Components\Textarea::make('gdpr_article_reference')
                    ->label('GDPR Article Reference')
                    ->rows(2)
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('dora_requirement_reference')
                    ->label('DORA Requirement Reference')
                    ->rows(2)
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('nis2_requirement_reference')
                    ->label('NIS2 Requirement Reference')
                    ->rows(2)
                    ->columnSpanFull(),
                
                // Certification (External Audits)
                Forms\Components\TextInput::make('certification_body')
                    ->label('Certification Body')
                    ->visible(fn ($get) => in_array($get('audit_type'), ['external', 'certification'])),
                Forms\Components\TextInput::make('certification_number')
                    ->label('Certification Number')
                    ->visible(fn ($get) => in_array($get('audit_type'), ['external', 'certification'])),
            ])
            ->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('audit_type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => StatusBadgeHelper::getAuditTypeLabel($state))
                    ->color(fn (?string $state): string => StatusBadgeHelper::getAuditTypeColor($state))
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => StatusBadgeHelper::getAuditStatusLabel($state))
                    ->color(fn (?string $state): string => StatusBadgeHelper::getAuditStatusColor($state))
                    ->sortable(),
                Tables\Columns\TextColumn::make('compliance_standards')
                    ->label('Standards')
                    ->badge()
                    ->separator(',')
                    ->color('info')
                    ->getStateUsing(function ($record) {
                        $state = $record->compliance_standards;
                        if (!$state) {
                            return [];
                        }
                        // Ensure it's an array (handle both array and JSON string)
                        if (is_array($state)) {
                            return $state;
                        }
                        if (is_string($state)) {
                            $decoded = json_decode($state, true);
                            return is_array($decoded) ? $decoded : [];
                        }
                        return [];
                    })
                    ->toggleable(),
                Tables\Columns\TextColumn::make('evidences_count')
                    ->counts('evidences')
                    ->label('Evidences')
                    ->sortable(),
                Tables\Columns\TextColumn::make('auditor.name')
                    ->label('Auditor')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('start_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_date')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('next_audit_date')
                    ->label('Next Audit')
                    ->date()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('closed_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'in_progress' => 'In Progress',
                        'closed' => 'Closed',
                    ]),
                Tables\Filters\SelectFilter::make('audit_type')
                    ->label('Audit Type')
                    ->options([
                        'internal' => 'Internal',
                        'external' => 'External',
                        'certification' => 'Certification',
                        'compliance' => 'Compliance',
                    ]),
                Tables\Filters\SelectFilter::make('auditor_id')
                    ->label('Auditor')
                    ->relationship('auditor', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Actions\ViewAction::make(),
                Actions\EditAction::make(),
                Actions\Action::make('close')
                    ->label('Close Audit')
                    ->icon('heroicon-o-lock-closed')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (Audit $record) {
                        $record->update([
                            'status' => 'closed',
                            'closed_at' => now(),
                        ]);
                    })
                    ->visible(fn (Audit $record) => $record->status !== 'closed' && auth()->user()->can('close', $record)),
                Actions\Action::make('export')
                    ->label('Export')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->form([
                        \Filament\Forms\Components\Select::make('format')
                            ->label('Export Format')
                            ->options([
                                'pdf' => 'PDF',
                                'csv' => 'CSV',
                            ])
                            ->default('pdf')
                            ->required(),
                    ])
                    ->action(function (Audit $record, array $data) {
                        \App\Jobs\ExportAuditJob::dispatch(
                            $record->id,
                            $data['format'],
                            auth()->id()
                        );
                        
                        \Filament\Notifications\Notification::make()
                            ->title('Export Queued')
                            ->success()
                            ->body("Your export in {$data['format']} format has been queued. You will receive an email when it's ready.")
                            ->send();
                    })
                    ->visible(fn (Audit $record) => auth()->user()->can('export', $record)),
                Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAudits::route('/'),
            'create' => Pages\CreateAudit::route('/create'),
            'view' => Pages\ViewAudit::route('/{record}'),
            'edit' => Pages\EditAudit::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', '!=', 'closed')->count();
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->can('viewAny', Audit::class);
    }

    public static function canCreate(): bool
    {
        return auth()->user()->can('create', Audit::class);
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return auth()->user()->can('update', $record);
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return auth()->user()->can('delete', $record);
    }
}
