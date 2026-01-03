<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PolicyResource\Pages;
use App\Models\Policy;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PolicyResource extends Resource
{
    protected static ?string $model = Policy::class;

    protected static ?int $navigationSort = 1;

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-document-text';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Policy Management';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('version')
                    ->default('1.0')
                    ->maxLength(255),
                Forms\Components\DatePicker::make('approval_date')
                    ->label('Approval Date'),
                Forms\Components\Select::make('owner_id')
                    ->label('Owner')
                    ->relationship('owner', 'name')
                    ->searchable()
                    ->preload()
                    ->nullable(),
                Forms\Components\Select::make('evidence_id')
                    ->label('Policy File (Evidence)')
                    ->relationship('evidence', 'filename')
                    ->searchable()
                    ->preload()
                    ->nullable()
                    ->helperText('Select an evidence file for this policy (optional if internal link is provided)')
                    ->live(),
                Forms\Components\TextInput::make('internal_link')
                    ->label('Internal Link')
                    ->url()
                    ->maxLength(2048)
                    ->helperText('Link to the policy in your internal intranet (optional if file is uploaded). At least one of file or link must be provided.')
                    ->live()
                    ->requiredWithout('evidence_id'),
                Forms\Components\Textarea::make('description')
                    ->rows(3)
                    ->columnSpanFull(),
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
                Tables\Columns\TextColumn::make('version')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('owner.name')
                    ->label('Owner')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('evidence.filename')
                    ->label('File')
                    ->placeholder('N/A')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('internal_link')
                    ->label('Link')
                    ->url(fn ($record) => $record->internal_link)
                    ->openUrlInNewTab()
                    ->placeholder('N/A')
                    ->toggleable()
                    ->limit(50),
                Tables\Columns\TextColumn::make('controls_count')
                    ->label('Mapped Controls')
                    ->counts('controls')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('approval_date')
                    ->date()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('has_file')
                    ->label('Has File')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('evidence_id')),
                Tables\Filters\Filter::make('has_link')
                    ->label('Has Internal Link')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('internal_link')),
                Tables\Filters\Filter::make('unmapped')
                    ->label('Unmapped Policies')
                    ->query(fn (Builder $query): Builder => 
                        $query->doesntHave('controls')
                    ),
            ])
            ->actions([
                Actions\Action::make('view_link')
                    ->label('View')
                    ->icon('heroicon-o-link')
                    ->url(fn (Policy $record) => $record->getDisplayUrl())
                    ->openUrlInNewTab()
                    ->visible(fn (Policy $record) => $record->getDisplayUrl() !== null),
                Actions\ViewAction::make(),
                Actions\EditAction::make(),
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
            'index' => Pages\ListPolicies::route('/'),
            'create' => Pages\CreatePolicy::route('/create'),
            'view' => Pages\ViewPolicy::route('/{record}'),
            'edit' => Pages\EditPolicy::route('/{record}/edit'),
        ];
    }
}
