<?php

namespace App\Filament\Resources\AuditResource\RelationManagers;

use App\Filament\Resources\ControlResource;
use Filament\Actions\AttachAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Actions\ViewAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class ControlsRelationManager extends RelationManager
{
    protected static string $relationship = 'controls';

    public function form(Schema $schema): Schema
    {
        // This relation manager is intended for attaching existing controls (many-to-many),
        // so no create/edit form is necessary here.
        return $schema->schema([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                Tables\Columns\TextColumn::make('standard')
                    ->badge()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('article_reference')
                    ->label('Reference')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                Tables\Columns\TextColumn::make('category')
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('pivot.created_at')
                    ->label('Linked At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                AttachAction::make()
                    ->label('Link Controls')
                    ->multiple()
                    ->preloadRecordSelect()
                    ->recordSelectSearchColumns(['title', 'article_reference', 'standard'])
                    ->recordSelectOptionsQuery(function ($query) {
                        // Order primarily by reference, then title (null/empty refs last).
                        // Note: On PostgreSQL, the AttachAction query uses SELECT DISTINCT, which
                        // disallows ORDER BY expressions like CASE unless they appear in SELECT.
                        // So we only order by columns here.
                        return $query
                            ->orderBy('standard')
                            ->orderBy('article_reference')
                            ->orderBy('title');
                    })
                    ->recordTitle(function ($record): string {
                        $ref = $record->article_reference ?: $record->standard;
                        return trim(($ref ? "{$ref} — " : '') . $record->title);
                    })
                    ->modalHeading('Link controls to this audit'),
            ])
            ->actions([
                ViewAction::make()
                    ->url(fn ($record) => ControlResource::getUrl('view', ['record' => $record])),
                DetachAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DetachBulkAction::make(),
                ]),
            ]);
    }
}

