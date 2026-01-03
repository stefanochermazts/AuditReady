<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ControlResource\Pages;
use App\Models\Control;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ControlResource extends Resource
{
    protected static ?string $model = Control::class;

    protected static ?int $navigationSort = 1;

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-shield-check';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Compliance';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Forms\Components\Select::make('standard')
                    ->options([
                        'DORA' => 'DORA',
                        'NIS2' => 'NIS2',
                        'ISO27001' => 'ISO 27001',
                        'custom' => 'Custom',
                    ])
                    ->required()
                    ->default('custom'),
                Forms\Components\TextInput::make('article_reference')
                    ->label('Article Reference')
                    ->placeholder('e.g., DORA Art. 8.1')
                    ->maxLength(255),
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('description')
                    ->rows(3)
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('category')
                    ->placeholder('e.g., Risk Management, Incident Response')
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
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
                        'ISO27001' => 'ISO 27001',
                        'custom' => 'Custom',
                    ]),
                Tables\Filters\SelectFilter::make('category')
                    ->options(fn () => Control::query()
                        ->whereNotNull('category')
                        ->distinct()
                        ->pluck('category', 'category')
                        ->toArray()),
                Tables\Filters\Filter::make('without_owners')
                    ->label('Without Owners')
                    ->query(fn (Builder $query) => $query->doesntHave('owners')),
            ])
            ->actions([
                Actions\ViewAction::make(),
                Actions\EditAction::make(),
                Actions\Action::make('assign_owners')
                    ->label('Assign Owners')
                    ->icon('heroicon-o-user-plus')
                    ->url(fn (Control $record) => \App\Filament\Pages\OwnershipMatrix::getUrl(['control' => $record->id])),
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
            'index' => Pages\ListControls::route('/'),
            'create' => Pages\CreateControl::route('/create'),
            'view' => Pages\ViewControl::route('/{record}'),
            'edit' => Pages\EditControl::route('/{record}/edit'),
        ];
    }
}
