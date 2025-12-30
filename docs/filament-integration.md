# Integrazione Filament per AuditReady

## Panoramica

AuditReady utilizza **Filament 3.x** come framework per l'interfaccia amministrativa. Filament offre una UX moderna, sviluppo rapido e integrazione nativa con Laravel.

## Perché Filament?

### Vantaggi Principali

1. **UX Moderna**
   - Interfaccia responsive e intuitiva
   - Componenti UI pre-costruiti e accessibili
   - Design system coerente

2. **Sviluppo Rapido**
   - CRUD automatico con Resource classes
   - Form builder e table builder potenti
   - Meno codice boilerplate

3. **Livewire Integration**
   - Interattività senza JavaScript complesso
   - Componenti reattivi
   - Performance ottimizzate

4. **Estensibilità**
   - Plugin system
   - Custom components
   - Theming personalizzabile

5. **Community e Documentazione**
   - Ampia community Laravel
   - Documentazione completa
   - Supporto attivo

## Architettura Filament in AuditReady

### Componenti Principali

```
Filament Panel
├── Multi-Tenant Middleware
│   └── Tenant Context Resolution
├── RBAC Integration
│   ├── Spatie Permission Plugin
│   └── Policy-based Authorization
├── 2FA Integration
│   ├── Custom Login Page
│   └── TOTP Verification
└── Resources
    ├── EvidenceResource
    ├── AuditResource
    ├── UserResource
    ├── AuditLogResource
    └── ExportResource
```

## Integrazione Multi-Tenant

### Tenant Middleware

```php
// app/Http/Middleware/TenantFilamentMiddleware.php
class TenantFilamentMiddleware
{
    public function handle($request, Closure $next)
    {
        // Risolvi tenant da subdomain o header
        $tenant = $this->resolveTenant($request);
        
        if (!$tenant) {
            abort(404, 'Tenant not found');
        }
        
        // Set tenant context
        Tenant::setCurrent($tenant);
        
        // Switch database connection
        $tenant->configure()->use();
        
        return $next($request);
    }
}
```

### Applicazione Middleware

```php
// app/Providers/Filament/AdminPanelProvider.php
public function panel(Panel $panel): Panel
{
    return $panel
        ->default()
        ->id('admin')
        ->path('admin')
        ->login()
        ->middleware([
            TenantFilamentMiddleware::class,
            // altri middleware...
        ]);
}
```

### Tenant Scope nelle Query

```php
// app/Filament/Resources/EvidenceResource.php
public static function getEloquentQuery(): Builder
{
    return parent::getEloquentQuery()
        ->where('tenant_id', Tenant::current()->id);
}
```

## Integrazione RBAC

### Spatie Permission Plugin

```php
// app/Providers/Filament/AdminPanelProvider.php
use Filament\Plugins\SpatiePermissionPlugin;

$panel->plugin(
    SpatiePermissionPlugin::make()
        ->rolesTable()
        ->permissionsTable()
);
```

### Policy-based Authorization

```php
// app/Filament/Resources/EvidenceResource.php
public static function canViewAny(): bool
{
    return auth()->user()->can('viewAny', Evidence::class);
}

public function canCreate(): bool
{
    return auth()->user()->can('create', Evidence::class);
}

public function canEdit(): bool
{
    return auth()->user()->can('update', $this->record);
}

public function canDelete(): bool
{
    return auth()->user()->can('delete', $this->record);
}
```

### Navigation basata su Permessi

```php
// app/Providers/Filament/AdminPanelProvider.php
public function navigationItems(): array
{
    return [
        NavigationItem::make('Evidenze')
            ->icon('heroicon-o-document')
            ->url(EvidenceResource::getUrl())
            ->visible(fn () => auth()->user()->can('viewAny', Evidence::class)),
        
        NavigationItem::make('Audit')
            ->icon('heroicon-o-clipboard-document-check')
            ->url(AuditResource::getUrl())
            ->visible(fn () => auth()->user()->can('viewAny', Audit::class)),
    ];
}
```

## Integrazione 2FA

### Custom Login Page

```php
// app/Filament/Pages/Auth/Login.php
class Login extends \Filament\Pages\Auth\Login
{
    protected function afterLogin(): void
    {
        $user = auth()->user();
        
        // Verifica se 2FA è abilitata e obbligatoria
        if ($user->hasRole(['owner', 'audit_manager', 'contributor'])) {
            if ($user->two_factor_secret && !session('2fa_verified')) {
                $this->redirect(route('filament.admin.pages.2fa-verify'));
                return;
            }
        }
    }
}
```

### Pagina Verifica 2FA

```php
// app/Filament/Pages/TwoFactorVerify.php
class TwoFactorVerify extends Page
{
    protected static string $view = 'filament.pages.two-factor-verify';
    
    public ?string $code = null;
    
    public function verify(): void
    {
        $this->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);
        
        $user = auth()->user();
        $google2fa = app('pragmarx.google2fa');
        
        if ($google2fa->verifyKey($user->two_factor_secret, $this->code)) {
            session(['2fa_verified' => true]);
            $this->redirect(route('filament.admin.pages.dashboard'));
        } else {
            $this->addError('code', 'Codice non valido');
        }
    }
}
```

### Setup 2FA in User Profile

```php
// app/Filament/Resources/UserResource/Pages/EditUser.php
protected function getHeaderActions(): array
{
    return [
        Action::make('setup2fa')
            ->label('Configura 2FA')
            ->icon('heroicon-o-shield-check')
            ->form([
                TextInput::make('code')
                    ->label('Codice da Microsoft Authenticator')
                    ->required()
                    ->length(6),
            ])
            ->action(function (array $data) {
                $user = $this->record;
                $google2fa = app('pragmarx.google2fa');
                
                if ($google2fa->verifyKey($user->two_factor_secret, $data['code'])) {
                    $user->update(['two_factor_enabled' => true]);
                    Notification::make()
                        ->title('2FA abilitata con successo')
                        ->success()
                        ->send();
                }
            })
            ->visible(fn () => !$this->record->two_factor_enabled),
    ];
}
```

## Resource Classes

### EvidenceResource

```php
// app/Filament/Resources/EvidenceResource.php
class EvidenceResource extends Resource
{
    protected static ?string $model = Evidence::class;
    
    public static function form(Form $form): Form
    {
        return $form->schema([
            FileUpload::make('file')
                ->label('File Evidenza')
                ->required()
                ->acceptedFileTypes(['application/pdf', 'image/*', 'application/msword'])
                ->maxSize(10240) // 10MB
                ->disk('minio')
                ->directory(fn () => "tenants/" . Tenant::current()->id . "/evidences")
                ->storeFileNamesIn('original_filename'),
            
            Select::make('audit_id')
                ->label('Audit')
                ->relationship('audit', 'name')
                ->required(),
            
            Textarea::make('description')
                ->label('Descrizione')
                ->rows(3),
        ]);
    }
    
    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('filename')
                ->label('File')
                ->searchable(),
            
            TextColumn::make('audit.name')
                ->label('Audit')
                ->sortable(),
            
            TextColumn::make('version')
                ->label('Versione')
                ->badge(),
            
            TextColumn::make('uploader.name')
                ->label('Caricato da')
                ->sortable(),
            
            TextColumn::make('created_at')
                ->label('Data')
                ->dateTime()
                ->sortable(),
        ])
        ->actions([
            Tables\Actions\Action::make('download')
                ->label('Scarica')
                ->icon('heroicon-o-arrow-down-tray')
                ->url(fn (Evidence $record) => route('evidence.download', $record))
                ->openUrlInNewTab(),
            
            Tables\Actions\Action::make('versions')
                ->label('Versioni')
                ->icon('heroicon-o-clock')
                ->modalContent(fn (Evidence $record) => view('filament.modals.evidence-versions', [
                    'versions' => $record->versions,
                ])),
        ]);
    }
}
```

### AuditResource

```php
// app/Filament/Resources/AuditResource.php
class AuditResource extends Resource
{
    protected static ?string $model = Audit::class;
    
    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('name')
                ->label('Nome Audit')
                ->required()
                ->maxLength(255),
            
            Textarea::make('description')
                ->label('Descrizione'),
            
            Select::make('status')
                ->label('Stato')
                ->options([
                    'draft' => 'Bozza',
                    'in_progress' => 'In Corso',
                    'completed' => 'Completato',
                ])
                ->required(),
            
            DatePicker::make('start_date')
                ->label('Data Inizio'),
            
            DatePicker::make('end_date')
                ->label('Data Fine'),
        ]);
    }
    
    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')
                ->label('Nome')
                ->searchable()
                ->sortable(),
            
            TextColumn::make('status')
                ->label('Stato')
                ->badge()
                ->color(fn (string $state): string => match ($state) {
                    'draft' => 'gray',
                    'in_progress' => 'warning',
                    'completed' => 'success',
                }),
            
            TextColumn::make('evidences_count')
                ->label('Evidenze')
                ->counts('evidences'),
            
            TextColumn::make('created_at')
                ->label('Creato')
                ->dateTime()
                ->sortable(),
        ])
        ->actions([
            Tables\Actions\Action::make('export')
                ->label('Esporta')
                ->icon('heroicon-o-arrow-down-on-square')
                ->requiresConfirmation()
                ->action(fn (Audit $record) => ExportAuditJob::dispatch($record->id)),
        ]);
    }
}
```

## Custom Components

### File Upload con Crittografia

```php
// app/Filament/Forms/Components/EncryptedFileUpload.php
class EncryptedFileUpload extends FileUpload
{
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->afterStateUpdated(function ($state, $set) {
            if ($state) {
                // Cifra file prima dello storage
                $encrypted = app(EvidenceService::class)->encryptFile($state);
                $set('encrypted_file', $encrypted);
            }
        });
    }
}
```

### Version Viewer

```php
// app/Filament/Infolists/Components/VersionList.php
class VersionList extends Component
{
    public function render(): View
    {
        return view('filament.components.version-list', [
            'versions' => $this->getVersions(),
        ]);
    }
}
```

## Dashboard Widgets

```php
// app/Filament/Widgets/EvidenceStatsWidget.php
class EvidenceStatsWidget extends Widget
{
    protected static string $view = 'filament.widgets.evidence-stats';
    
    protected function getStats(): array
    {
        $tenant = Tenant::current();
        
        return [
            'total' => Evidence::count(),
            'this_month' => Evidence::whereMonth('created_at', now()->month)->count(),
            'by_audit' => Evidence::groupBy('audit_id')->count(),
        ];
    }
}
```

## Theming

### Custom Theme

```php
// app/Providers/Filament/AdminPanelProvider.php
use Filament\Support\Colors\Color;

$panel->colors([
    'primary' => Color::Blue,
    'danger' => Color::Red,
    'success' => Color::Green,
    'warning' => Color::Orange,
]);
```

### Custom Branding

```php
$panel
    ->brandName('AuditReady')
    ->brandLogo(asset('images/logo.svg'))
    ->favicon(asset('images/favicon.ico'))
    ->darkMode(false); // o true per dark mode
```

## Best Practices

### 1. Performance

- Usa `lazy()` per tabelle con molti record
- Implementa paginazione appropriata
- Cache query pesanti

### 2. Sicurezza

- Sempre verificare permessi in Resource classes
- Validare input in form
- Sanitizzare output

### 3. UX

- Messaggi di errore chiari
- Loading states appropriati
- Notifiche per azioni importanti

### 4. Testing

- Test accesso con diversi ruoli
- Test isolamento tenant
- Test permessi RBAC

## Conclusione

Filament offre una base solida per l'interfaccia AuditReady con:
- ✅ **UX moderna** out-of-the-box
- ✅ **Sviluppo rapido** con Resource classes
- ✅ **Integrazione nativa** con Laravel
- ✅ **Estensibilità** per custom components
- ✅ **Community attiva** e documentazione

L'integrazione con multi-tenant, RBAC e 2FA garantisce sicurezza e isolamento mantenendo una UX eccellente.
