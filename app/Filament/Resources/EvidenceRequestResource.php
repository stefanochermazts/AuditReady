<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EvidenceRequestResource\Pages;
use App\Models\Audit;
use App\Models\Control;
use App\Models\EvidenceRequest;
use App\Models\ThirdPartySupplier;
use App\Services\EvidenceRequestService;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\URL;

class EvidenceRequestResource extends Resource
{
    protected static ?string $model = EvidenceRequest::class;

    protected static ?int $navigationSort = 2;

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-paper-clip';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Third-Party Management';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Forms\Components\Select::make('audit_id')
                    ->label('Related Audit')
                    ->relationship('audit', 'name')
                    ->searchable()
                    ->preload()
                    ->nullable()
                    ->helperText('Optional: Link this request to a specific audit'),
                Forms\Components\Select::make('control_id')
                    ->label('Control')
                    ->relationship(
                        'control',
                        'title',
                        fn (Builder $query) => $query->whereIn('standard', ['DORA', 'NIS2', 'ISO27001'])
                    )
                    ->getOptionLabelFromRecordUsing(fn (Control $record): string => trim(($record->article_reference ? $record->article_reference . ' - ' : '') . $record->title))
                    ->searchable(['title', 'article_reference'])
                    ->preload()
                    ->required()
                    ->helperText('Select the control that requires evidence'),
                Forms\Components\Select::make('supplier_id')
                    ->label('Supplier')
                    ->relationship('supplier', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->createOptionForm([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('contact_person')
                            ->maxLength(255),
                        Forms\Components\Textarea::make('notes')
                            ->rows(3),
                    ]),
                Forms\Components\Textarea::make('message')
                    ->label('Message to Supplier')
                    ->rows(4)
                    ->helperText('Optional message to include in the email sent to the supplier')
                    ->columnSpanFull(),
                Forms\Components\Select::make('expiration_days')
                    ->label('Expiration (Days)')
                    ->options([
                        7 => '7 days',
                        14 => '14 days',
                        30 => '30 days',
                        60 => '60 days',
                        90 => '90 days',
                    ])
                    ->default(30)
                    ->required()
                    ->helperText('How long the upload link will be valid'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('control.article_reference')
                    ->label('Control')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('control.title')
                    ->label('Control Title')
                    ->limit(50)
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('supplier.name')
                    ->label('Supplier')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'completed' => 'success',
                        'expired' => 'danger',
                        'cancelled' => 'gray',
                        default => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('expires_at')
                    ->label('Expires')
                    ->dateTime()
                    ->sortable()
                    ->color(fn ($record) => $record->isExpired() ? 'danger' : null),
                Tables\Columns\TextColumn::make('completed_at')
                    ->label('Completed')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('N/A'),
                Tables\Columns\TextColumn::make('requestedBy.name')
                    ->label('Requested By')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'completed' => 'Completed',
                        'expired' => 'Expired',
                        'cancelled' => 'Cancelled',
                    ]),
                Tables\Filters\Filter::make('expired')
                    ->label('Expired')
                    ->query(fn (Builder $query): Builder => $query->where('expires_at', '<', now())->where('status', 'pending')),
            ])
            ->actions([
                Actions\Action::make('view_public_link')
                    ->label('View Link')
                    ->icon('heroicon-o-link')
                    ->color('info')
                    ->url(fn (EvidenceRequest $record): string => app(EvidenceRequestService::class)->generatePublicUrl($record), shouldOpenInNewTab: true)
                    ->visible(fn (EvidenceRequest $record) => $record->isPending() && !$record->isExpired()),
                Actions\Action::make('copy_link')
                    ->label('Copy Link')
                    ->icon('heroicon-o-clipboard')
                    ->color('info')
                    ->action(function (EvidenceRequest $record) {
                        $url = app(EvidenceRequestService::class)->generatePublicUrl($record);
                        Notification::make()
                            ->title('Link copied to clipboard')
                            ->success()
                            ->body('The public upload link has been copied to your clipboard.')
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->visible(fn (EvidenceRequest $record) => $record->isPending() && !$record->isExpired()),
                Actions\ViewAction::make(),
                Actions\EditAction::make(),
                Actions\Action::make('cancel')
                    ->label('Cancel')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (EvidenceRequest $record) {
                        app(EvidenceRequestService::class)->cancelRequest($record, auth()->id());
                        Notification::make()
                            ->title('Request cancelled')
                            ->success()
                            ->send();
                    })
                    ->visible(fn (EvidenceRequest $record) => $record->isPending() && !$record->isExpired()),
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
            'index' => Pages\ListEvidenceRequests::route('/'),
            'create' => Pages\CreateEvidenceRequest::route('/create'),
            'view' => Pages\ViewEvidenceRequest::route('/{record}'),
            'edit' => Pages\EditEvidenceRequest::route('/{record}/edit'),
        ];
    }
}
