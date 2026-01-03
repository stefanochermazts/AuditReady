<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GapSnapshotResource\Pages;
use App\Models\GapSnapshot;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class GapSnapshotResource extends Resource
{
    protected static ?string $model = GapSnapshot::class;

    protected static ?int $navigationSort = 3;

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-clipboard-document-list';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Compliance';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Snapshot Name')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('e.g., DORA Gap Snapshot Q1 2025')
                    ->columnSpanFull(),
                Forms\Components\Select::make('audit_id')
                    ->label('Linked Audit')
                    ->relationship('audit', 'name')
                    ->searchable()
                    ->preload()
                    ->helperText('Optional: Link this snapshot to an audit'),
                Forms\Components\Select::make('standard')
                    ->label('Standard')
                    ->options([
                        'DORA' => 'DORA',
                        'NIS2' => 'NIS2',
                        'both' => 'Both (DORA + NIS2)',
                    ])
                    ->default('both')
                    ->required()
                    ->helperText('Select which compliance standard(s) to assess'),
                Forms\Components\Placeholder::make('completion_status')
                    ->label('Completion Status')
                    ->content(function (?GapSnapshot $record) {
                        if (!$record) {
                            return 'Not started';
                        }
                        return $record->isCompleted() 
                            ? 'Completed on ' . $record->completed_at->format('Y-m-d H:i:s')
                            : $record->getCompletionPercentage() . '% completed';
                    })
                    ->visible(fn (?GapSnapshot $record) => $record !== null),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                Tables\Columns\TextColumn::make('standard')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'DORA' => 'primary',
                        'NIS2' => 'success',
                        'both' => 'warning',
                        default => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('audit.name')
                    ->label('Linked Audit')
                    ->searchable()
                    ->sortable()
                    ->placeholder('Standalone'),
                Tables\Columns\TextColumn::make('completedBy.name')
                    ->label('Completed By')
                    ->sortable()
                    ->placeholder('Not completed'),
                Tables\Columns\TextColumn::make('completed_at')
                    ->label('Completed At')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('Not completed'),
                Tables\Columns\TextColumn::make('responses_count')
                    ->label('Responses')
                    ->counts('responses')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('standard')
                    ->options([
                        'DORA' => 'DORA',
                        'NIS2' => 'NIS2',
                        'both' => 'Both',
                    ]),
                Tables\Filters\Filter::make('completed')
                    ->label('Completed')
                    ->query(fn (Builder $query) => $query->whereNotNull('completed_at')),
                Tables\Filters\Filter::make('in_progress')
                    ->label('In Progress')
                    ->query(fn (Builder $query) => $query->whereNull('completed_at')),
            ])
            ->actions([
                Actions\ViewAction::make(),
                Actions\EditAction::make(),
                Actions\Action::make('complete_wizard')
                    ->label('Complete Wizard')
                    ->icon('heroicon-o-pencil-square')
                    ->url(fn (GapSnapshot $record) => \App\Filament\Pages\GapSnapshotWizard::getUrl(['snapshot' => $record->id]))
                    ->visible(fn (GapSnapshot $record) => !$record->isCompleted()),
                Actions\Action::make('view_report')
                    ->label('View Report')
                    ->icon('heroicon-o-document-text')
                    ->url(fn (GapSnapshot $record) => \App\Filament\Pages\ViewGapSnapshotReport::getUrl(['snapshot' => $record->id]))
                    ->visible(fn (GapSnapshot $record) => $record->isCompleted()),
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
            'index' => Pages\ListGapSnapshots::route('/'),
            'create' => Pages\CreateGapSnapshot::route('/create'),
            'view' => Pages\ViewGapSnapshot::route('/{record}'),
            'edit' => Pages\EditGapSnapshot::route('/{record}/edit'),
        ];
    }
}
