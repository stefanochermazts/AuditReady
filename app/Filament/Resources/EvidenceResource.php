<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EvidenceResource\Pages;
use App\Filament\Support\StatusBadgeHelper;
use App\Models\Evidence;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EvidenceResource extends Resource
{
    protected static ?string $model = Evidence::class;

    protected static ?int $navigationSort = 1;

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-document';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Evidence Management';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                // Basic Information
                Forms\Components\Select::make('audit_id')
                    ->relationship('audit', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),
                Forms\Components\FileUpload::make('stored_path')
                    ->label('File')
                    ->required()
                    ->acceptedFileTypes(['application/pdf', 'image/*', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])
                    ->maxSize(10240) // 10MB
                    ->directory('evidences')
                    ->visibility('private')
                    ->storeFileNamesIn('original_filename')
                    ->downloadable()
                    ->previewable()
                    ->columnSpanFull(),
                
                // Document Classification
                Forms\Components\Select::make('category')
                    ->options([
                        'policy' => 'Policy',
                        'procedure' => 'Procedure',
                        'incident_report' => 'Incident Report',
                        'continuity_plan' => 'Continuity Plan',
                        'vendor_document' => 'Vendor Document',
                        'training_record' => 'Training Record',
                        'certificate' => 'Certificate',
                        'contract' => 'Contract',
                        'report' => 'Report',
                        'other' => 'Other',
                    ])
                    ->searchable(),
                Forms\Components\TextInput::make('document_type')
                    ->label('Document Type')
                    ->maxLength(255),
                Forms\Components\DatePicker::make('document_date')
                    ->label('Document Date')
                    ->helperText('Original document date (not upload date)'),
                Forms\Components\TextInput::make('supplier')
                    ->label('Supplier/Origin')
                    ->maxLength(255),
                
                // Compliance References
                Forms\Components\Textarea::make('regulatory_reference')
                    ->label('Regulatory Reference')
                    ->helperText('e.g., "ISO 27001:2022 A.5.1.1", "GDPR Art. 32"')
                    ->rows(2)
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('control_reference')
                    ->label('Control Reference')
                    ->helperText('Internal control reference, e.g., "CTRL-001"')
                    ->rows(2)
                    ->columnSpanFull(),
                
                // Validation
                Forms\Components\Select::make('validation_status')
                    ->label('Validation Status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                        'needs_revision' => 'Needs Revision',
                    ])
                    ->default('pending')
                    ->required(),
                Forms\Components\Select::make('validated_by')
                    ->label('Validated By')
                    ->relationship('validator', 'name')
                    ->searchable()
                    ->preload()
                    ->visible(fn ($get) => in_array($get('validation_status'), ['approved', 'rejected'])),
                Forms\Components\DateTimePicker::make('validated_at')
                    ->label('Validated At')
                    ->visible(fn ($get) => in_array($get('validation_status'), ['approved', 'rejected'])),
                Forms\Components\Textarea::make('validation_notes')
                    ->label('Validation Notes')
                    ->rows(3)
                    ->columnSpanFull()
                    ->visible(fn ($get) => in_array($get('validation_status'), ['approved', 'rejected', 'needs_revision'])),
                
                // Lifecycle and Security
                Forms\Components\DatePicker::make('expiry_date')
                    ->label('Expiry Date'),
                Forms\Components\Select::make('confidentiality_level')
                    ->label('Confidentiality Level')
                    ->options([
                        'public' => 'Public',
                        'internal' => 'Internal',
                        'confidential' => 'Confidential',
                        'restricted' => 'Restricted',
                    ])
                    ->default('internal')
                    ->required(),
                Forms\Components\TextInput::make('retention_period_months')
                    ->label('Retention Period (Months)')
                    ->numeric()
                    ->default(84)
                    ->helperText('Default: 84 months (7 years)')
                    ->minValue(1)
                    ->maxValue(1200),
                
                // Organization
                Forms\Components\TagsInput::make('tags')
                    ->label('Tags')
                    ->helperText('Tags for categorization and search')
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('notes')
                    ->label('Notes')
                    ->rows(3)
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('audit.name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('filename')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('category')
                    ->badge()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('validation_status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => StatusBadgeHelper::getValidationStatusLabel($state))
                    ->color(fn (?string $state): string => StatusBadgeHelper::getValidationStatusColor($state))
                    ->sortable(),
                Tables\Columns\TextColumn::make('validator.name')
                    ->label('Validated By')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('document_date')
                    ->label('Doc Date')
                    ->date()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('expiry_date')
                    ->label('Expires')
                    ->date()
                    ->sortable()
                    ->color(fn ($state) => $state ? (\Carbon\Carbon::parse($state)->isPast() ? 'danger' : (\Carbon\Carbon::parse($state)->isBefore(now()->addDays(30)) ? 'warning' : null)) : null)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('uploader.name')
                    ->label('Uploaded By')
                    ->sortable(),
                Tables\Columns\TextColumn::make('size')
                    ->label('Size')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state / 1024, 2) . ' KB' : '-')
                    ->sortable(),
                Tables\Columns\TextColumn::make('version')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('confidentiality_level')
                    ->label('Confidentiality')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'public' => 'success',
                        'internal' => 'info',
                        'confidential' => 'warning',
                        'restricted' => 'danger',
                    })
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('audit_id')
                    ->relationship('audit', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('category')
                    ->options([
                        'policy' => 'Policy',
                        'procedure' => 'Procedure',
                        'incident_report' => 'Incident Report',
                        'continuity_plan' => 'Continuity Plan',
                        'vendor_document' => 'Vendor Document',
                        'training_record' => 'Training Record',
                        'certificate' => 'Certificate',
                        'contract' => 'Contract',
                        'report' => 'Report',
                        'other' => 'Other',
                    ]),
                Tables\Filters\SelectFilter::make('validation_status')
                    ->label('Validation Status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                        'needs_revision' => 'Needs Revision',
                    ]),
                Tables\Filters\SelectFilter::make('confidentiality_level')
                    ->label('Confidentiality Level')
                    ->options([
                        'public' => 'Public',
                        'internal' => 'Internal',
                        'confidential' => 'Confidential',
                        'restricted' => 'Restricted',
                    ]),
                Tables\Filters\Filter::make('expiring_soon')
                    ->label('Expiring Soon (30 days)')
                    ->query(fn ($query) => $query->where('expiry_date', '<=', now()->addDays(30))
                        ->where('expiry_date', '>=', now())),
                Tables\Filters\Filter::make('expired')
                    ->label('Expired')
                    ->query(fn ($query) => $query->where('expiry_date', '<', now())),
            ])
            ->actions([
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
            'index' => Pages\ListEvidence::route('/'),
            'create' => Pages\CreateEvidence::route('/create'),
            'view' => Pages\ViewEvidence::route('/{record}'),
            'edit' => Pages\EditEvidence::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        // Apply tenant scope - tenant is already set by middleware
        // No need to filter by tenant_id as we're in tenant database

        // Apply ownership filter for Contributors
        $user = auth()->user();
        if ($user && $user->hasRole('Contributor')) {
            $query->where('uploader_id', auth()->id());
        }

        return $query;
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->can('viewAny', Evidence::class);
    }

    public static function canCreate(): bool
    {
        return auth()->user()->can('create', Evidence::class);
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
