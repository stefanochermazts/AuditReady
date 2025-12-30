# AuditReady - Piano di Sviluppo Completo

## Panoramica
Questo documento descrive il piano di sviluppo completo per la piattaforma AuditReady, basato sull'analisi funzionale presente in `documents/analisi-funzionale.md`.

## Stack Tecnologico
- **Backend**: Laravel 12.0, PHP 8.2+
- **Database**: SQLite (dev) / PostgreSQL/MySQL (production)
- **Storage**: Provider-agnostic S3-compatible (MinIO, AWS S3, DigitalOcean Spaces, Wasabi, Backblaze B2, o locale) - configurabile via env
- **Frontend**: Filament 3.x (admin panel), Livewire 3.x, Tailwind CSS 4.0, Vite 7.0
- **Sicurezza**: AES-256, Argon2/bcrypt, TLS 1.2+, TOTP 2FA (compatibile Microsoft Authenticator)
- **Testing**: PHPUnit 11.5, Laravel Pint

## Principi di Sviluppo
- Security by Design
- Privacy by Design
- Defense in Depth
- Least Privilege
- Segregation of Duties

---

## Step 1: Design Architetturale di Alto Livello

**Azione**: Creare un documento di design architetturale per AuditReady.

**Ragionamento**: Definisce i confini, i moduli, i flussi di dati e i livelli di sicurezza prima di scrivere codice, garantendo allineamento con security-by-design, privacy-by-design e compliance GDPR/DORA/NIS2.

**Dettagli Implementazione**:
- Produrre un file markdown `docs/architecture.md` che descriva:
  - Strategia multi-tenant (multi-database: ogni tenant ha database separato per isolamento completo)
  - Flusso di autenticazione con Laravel Fortify, enforcement TOTP 2FA
  - Matrice RBAC per i cinque ruoli
  - Architettura storage evidenze (file cifrati in storage provider-agnostic: MinIO, AWS S3, DigitalOcean Spaces, Wasabi, Backblaze B2, o storage locale - configurabile via env, metadata in DB, tabella versioning)
  - Design audit-trail (tabella `audit_logs` append-only, entry firmate, tamper-evident)
  - Servizio upload third-party (API basata su token, queue worker separato)
  - Servizio export (generazione PDF/CSV, job asincroni)
  - Strategia Backup & DR (snapshot DB periodici, backup file cifrati, runbook disaster-recovery)
  - Cross-cutting concerns: TLS, CSP, CORS, rate limiting, logging, monitoring
- Includere diagrammi mermaid per:
  - Diagramma componenti sistema
  - Diagramma flusso dati (user → auth → tenant middleware → service)
- Elencare librerie esterne: `tenancy/tenancy` (per multi-database), `filament/filament` (admin panel), `spatie/laravel-permission`, `spatie/laravel-backup`, `spatie/laravel-encryptable`, `paragonie/constant_time_encoding` per audit logs firmati

**Testing**: Revisionare il design con un esperto di sicurezza; creare checklist per ogni componente da coprire con unit/feature tests.

---

## Step 2: Setup Base Laravel con Pacchetti Richiesti

**Azione**: Configurare il progetto Laravel base con i pacchetti necessari e configurare i servizi core.

**Ragionamento**: Fornisce una base pulita e conforme agli standard, installando gli strumenti necessari per multi-tenant, RBAC, crittografia, backup e 2FA.

**Dettagli Implementazione**:
- Aggiornare il repository esistente
- Aggiungere pacchetti:
  ```bash
  composer require tenancy/tenancy
  composer require filament/filament:"^3.0"
  composer require spatie/laravel-permission
  composer require laravel/fortify
  composer require spatie/laravel-backup
  composer require spatie/laravel-encryptable
  composer require pragmarx/google2fa-laravel
  ```
- Installare Filament:
  ```bash
  php artisan filament:install --panels
  ```
- Configurare Filament panel per AuditReady con:
  - Multi-tenant support (tenant context in ogni richiesta)
  - RBAC integration (Spatie Permission)
  - 2FA support (custom login page con verifica TOTP)
  - Custom theme/branding
- Pubblicare file di configurazione per ogni pacchetto
- Configurare `config/database.php` per supportare connessioni dinamiche multi-database
- Configurare database centrale (`tenants`) per gestire metadati tenant
- Configurare `AppServiceProvider` per enforcement TLS >1.2
- Installare Laravel Pint per code quality
- Inizializzare Vite e integrare Tailwind

**Testing**: Eseguire `php artisan migrate:fresh` e `php artisan test` per assicurarsi che il progetto compili e i test base passino.

---

## Step 3: Implementare Infrastruttura Multi-Tenant con Isolamento Multi-Database

**Azione**: Implementare l'infrastruttura multi-tenant con isolamento multi-database (un database per tenant).

**Ragionamento**: Garantisce isolamento completo dei dati tra organizzazioni, soddisfacendo GDPR/DORA/NIS2 e principio "Least Privilege" con massima sicurezza.

**Dettagli Implementazione**:
- Creare database centrale `tenants` per metadati tenant (id, name, domain, database_name, database_connection_config, created_at, updated_at)
- Configurare `tenancy/tenancy` per gestire connessioni dinamiche ai database tenant
- Implementare `TenantIdentificationMiddleware` per risolvere tenant da:
  - Sub-domain (es: `cliente1.auditready.com`)
  - Header `X-Tenant-ID` (per API)
  - Path parameter (opzionale)
- Creare comando artisan `php artisan tenant:create {name} {domain}` che:
  1. Genera UUID per tenant
  2. Crea nuovo database (es: `auditready_tenant_{uuid}`)
  3. Esegue tutte le migrazioni sul nuovo database
  4. Crea utente admin iniziale (Organization Owner)
  5. Configura connessione dinamica
  6. Registra tenant nel database centrale
  7. Crea directory storage dedicata per tenant (MinIO o filesystem locale)
  8. Genera credenziali iniziali e le invia via email
- Implementare `TenantConnectionResolver` che switcha automaticamente la connessione DB in base al tenant corrente
- Configurare `TenantBootstrapper` per inizializzare tenant context su ogni richiesta
- Creare seeder per tenant di sviluppo/test
- Implementare comando `php artisan tenant:migrate {tenant_id?}` per eseguire migrazioni su tutti i tenant o uno specifico
- Implementare comando `php artisan tenant:seed {tenant_id}` per seed dati iniziali

**Vantaggi Multi-Database**:
- Isolamento completo: impossibile accesso cross-tenant anche con bug SQL
- Compliance: allineato a GDPR/DORA/NIS2
- Scalabilità: possibilità di scalare per tenant
- Backup mirati: backup/restore per singolo cliente
- Performance: query più semplici (niente filtri tenant_id)

**Complessità Gestione**:
- Automatizzata con comando `tenant:create` (processo ~30 secondi)
- Migrazioni gestite con `tenant:migrate` (esegue su tutti i tenant)
- Backup automatizzati per tenant (Step 10)

**Testing**: 
- Feature test che tenta accesso cross-tenant (dovrebbe fallire a livello connessione DB)
- Test comando `tenant:create` verifica creazione database e migrazioni
- Test isolamento: query su tenant A non possono vedere dati tenant B

---

## Step 4: Configurare Autenticazione con 2FA TOTP Obbligatoria

**Azione**: Configurare autenticazione con 2FA TOTP obbligatoria usando Laravel Fortify e Google2FA (compatibile con Microsoft Authenticator).

**Ragionamento**: Fornisce forte assicurazione dell'identità, richiesta da DORA/NIS2, e soddisfa il mandato "Security by Design". Microsoft Authenticator supporta TOTP standard (RFC 6238), quindi è completamente compatibile.

**Dettagli Implementazione**:
- Pubblicare configurazione Fortify e abilitare features: registration, login, password reset, email verification
- Installare e configurare `pragmarx/google2fa-laravel` (compatibile con Microsoft Authenticator, Google Authenticator, Authy, ecc.)
- Creare `TwoFactorAuthenticationController` per gestione QR code, storage secret (cifrato), verifica durante login
- QR code generato sarà leggibile da Microsoft Authenticator (formato standard TOTP)
- Aggiornare login flow in `FortifyServiceProvider` per controllare `user->two_factor_secret` e reindirizzare a pagina verifica 2FA
- Creare middleware `RequireTwoFactor` che controlla flag sessione `2fa_verified`
- Applicare `RequireTwoFactor` globalmente dopo autenticazione
- Store TOTP secrets cifrati a riposo (AES-256) usando crittografia built-in Laravel
- Fornire istruzioni UI per configurazione con Microsoft Authenticator

**Testing**: Unit test generazione secret e cifratura. Feature test login con codici TOTP corretti/incorretti.

---

## Step 5: Implementare Role-Based Access Control (RBAC) per i Cinque Ruoli

**Azione**: Implementare RBAC per i cinque ruoli definiti.

**Ragionamento**: Gestione granulare dei permessi applica "Least Privilege" e "Segregation of Duties".

**Dettagli Implementazione**:
- Pubblicare tabelle Spatie Permission
- Definire ruoli in seeder: `Organization Owner`, `Audit Manager`, `Contributor`, `Viewer`, `External Uploader`
- Mappare permessi per ogni ruolo
- Attaccare ruoli a utenti via pivot `model_has_roles`
- Creare Policies per `Evidence`, `Audit`, `Export`, ecc.
- Registrare policies in `AuthServiceProvider`

**Testing**: Scrivere feature tests per ogni ruolo tentando azioni permesse e non permesse.

---

## Step 6: Implementare Interfaccia Filament con Integrazione Multi-Tenant, RBAC e 2FA

**Azione**: Implementare interfaccia admin con Filament 3.x integrata con multi-tenant, RBAC e 2FA.

**Ragionamento**: Filament offre UX moderna, sviluppo rapido e integrazione nativa con Laravel. Deve essere integrato con multi-tenant, RBAC e 2FA per garantire sicurezza e isolamento.

**Dettagli Implementazione**:
- Configurare Filament panel principale (`app/Providers/Filament/AdminPanelProvider.php`):
  - Custom domain/subdomain per tenant (se necessario)
  - Branding personalizzato (logo, colori AuditReady)
  - Navigation groups per organizzare risorse
- Implementare **Multi-Tenant Integration**:
  - Creare middleware `TenantFilamentMiddleware` che risolve tenant context prima di ogni richiesta Filament
  - Applicare tenant scope a tutte le query Filament automaticamente
  - Nascondere/abilitare risorse in base al tenant
  - Custom tenant switcher widget (se multi-tenant access)
- Implementare **RBAC Integration**:
  - Creare plugin Filament `SpatiePermissionPlugin` per integrare Spatie Permission
  - Configurare policies Filament per ogni risorsa (Evidence, Audit, User, ecc.)
  - Nascondere menu items in base ai permessi utente
  - Mostrare/ocultare azioni (create, edit, delete) in base ai ruoli
  - Custom authorization checks in Resource classes
- Implementare **2FA Integration**:
  - Creare custom login page Filament (`app/Filament/Pages/Auth/Login.php`)
  - Aggiungere step 2FA dopo login standard
  - Mostrare QR code per setup 2FA in user profile
  - Verifica TOTP durante login
  - Redirect a pagina verifica 2FA se non verificato
- Creare **Resource Classes** per:
  - `EvidenceResource` (gestione evidenze con upload, versioning, download)
  - `AuditResource` (gestione audit)
  - `UserResource` (gestione utenti con ruoli)
  - `AuditLogResource` (visualizzazione audit trail - read-only)
  - `ExportResource` (gestione export audit)
- Implementare **Custom Components**:
  - File upload component con crittografia automatica
  - Version viewer per evidenze
  - Export request form
  - Dashboard widgets (statistiche tenant, evidenze recenti, audit in corso)
- Configurare **Navigation**:
  - Menu items organizzati per ruolo
  - Badge per notifiche/conteggi
  - Custom icons per ogni sezione
- Implementare **Custom Pages**:
  - Dashboard tenant con overview
  - User profile con gestione 2FA
  - Settings tenant (se applicabile)
- Configurare **Filament Theming**:
  - Custom color scheme (brand AuditReady)
  - Custom fonts
  - Custom layout components

**Vantaggi Filament**:
- ✅ **UX Moderna**: Interfaccia responsive e intuitiva out-of-the-box
- ✅ **Sviluppo Rapido**: CRUD automatico, form builder, table builder
- ✅ **Livewire**: Interattività senza JavaScript complesso
- ✅ **Componenti Pronti**: Form, tabelle, modals, notifications
- ✅ **Estensibile**: Plugin system, custom components, theming
- ✅ **Community**: Ampia community e documentazione

**Testing**: 
- Feature tests per accesso Filament con diversi ruoli
- Test isolamento tenant in interfaccia
- Test permessi RBAC in risorse Filament
- Test flusso 2FA in login Filament

---

## Step 7: Progettare e Implementare Sottosistema Gestione Evidenze con Versioning e Crittografia End-to-End

**Azione**: Progettare e implementare il sottosistema di gestione evidenze con versioning e crittografia end-to-end.

**Ragionamento**: Funzionalità core business; deve garantire confidenzialità, integrità e tracciabilità dei documenti caricati.

**Dettagli Implementazione**:
- Creare modello `Evidence` e migration con campi: `id`, `tenant_id`, `audit_id`, `uploader_id`, `filename`, `mime_type`, `size`, `stored_path`, `checksum`, `version`, `encrypted_key`, `created_at`, `updated_at`, `deleted_at` (soft delete)
- Implementare architettura storage **provider-agnostic**:
  - Creare `App\Services\StorageService` che astrae il provider utilizzando Laravel Filesystem
  - Metodi del service: `disk()`, `put()`, `get()`, `delete()`, `exists()`, `url()`, `temporaryUrl()`
  - Supportare multipli provider S3-compatibili configurabili via environment:
    - **MinIO** (self-hosted, default produzione)
    - **AWS S3** (Amazon Web Services)
    - **DigitalOcean Spaces** (S3-compatible)
    - **Wasabi** (S3-compatible)
    - **Backblaze B2** (S3-compatible)
    - **Storage locale** (sviluppo/test)
  - Configurazione via `STORAGE_PROVIDER` env variable (es: `minio`, `s3`, `spaces`, `wasabi`, `b2`, `local`)
  - Configurare tutti i provider in `config/filesystems.php` (minio, s3, spaces, wasabi, b2, local)
  - Tutti i provider S3-compatibili usano driver `s3` di Laravel (tranne `local` che usa `local`)
  - Il codice applicativo usa sempre `StorageService`, mai direttamente `Storage::disk()`
  - Configurare crittografia lato applicazione (AES-256) indipendente dal provider
  - Documentare configurazione per ogni provider in `docs/storage-providers.md`
- Su upload:
  1. Generare chiave simmetrica random (AES-256) per file
  2. Cifrare file localmente usando `openssl_encrypt`
  3. Upload blob cifrato a storage (MinIO o locale) con path `tenants/{tenant_id}/evidences/{uuid}_v{version}`
  4. Store chiave cifrata in colonna `encrypted_key` (cifrata con app key Laravel)
  5. Calcolare checksum SHA-256 del plaintext per verifica integrità
  6. Creare nuovo record `Evidence`; se esiste versione precedente, incrementare `version`
- Implementare versioning: mantenere tutte le versioni; esporre API per listare versioni, scaricare versione specifica, o revert
- Decrittazione su download: recuperare chiave cifrata, decifrare con app key, poi decifrare file stream on-the-fly e inviare all'utente
- Assicurare streaming per evitare overload memoria per file grandi

**Testing**: Unit test funzioni encryption/decryption. Feature test upload, incremento versione, download, verifica integrità.

---

## Step 8: Implementare Audit Trail Immutabile usando Tabella `audit_logs` Append-Only

**Azione**: Implementare audit trail immutabile usando tabella `audit_logs` append-only.

**Ragionamento**: Fornisce tracking tamper-evident richiesto da framework di compliance e analisi forense.

**Dettagli Implementazione**:
- Creare migration per `audit_logs` con campi: `id`, `tenant_id`, `user_id`, `action`, `model_type`, `model_id`, `payload` (JSON), `ip_address`, `user_agent`, `created_at`
- Aggiungere trigger database o observer Laravel (`AuditObserver`) che si attiva su eventi `created`, `updated`, `deleted`, `restored` per tutti i modelli auditable
- Firmare ogni log entry usando HMAC e store signature in colonna separata `signature`
- Impostare engine tabella `InnoDB` con `ROW_FORMAT=COMPRESSED` e enforcement `created_at` immutabile
- Fornire servizio `AuditLogService::record($action, $model, $payload)` per centralizzare logging
- Esporre endpoint API per owners/managers per query audit logs con paginazione, filtri, export (CSV/JSON) rispettando isolamento tenant

**Testing**: Unit test `AuditLogService` verifica generazione signature. Feature test che crea, aggiorna, elimina Evidence e asserisce corrispondenti log entries.

---

## Step 9: Sviluppare Modulo Upload Third-Party (API Esterna Sicura)

**Azione**: Sviluppare modulo upload third-party (API esterna sicura).

**Ragionamento**: Permette a sistemi esterni di pushare evidenze senza interazione umana mantenendo controlli di sicurezza.

**Dettagli Implementazione**:
- Creare nuovo route group API in `routes/api.php` con prefix `/external` e middleware:
  - `auth:sanctum` per autenticazione token
  - `tenant.resolve` per risolvere tenant da header custom `X-Tenant-ID`
  - `role:External Uploader`
- Generare controller `ExternalEvidenceController` con `store(Request $request)`
- Richiedere JWT firmato (HS256) contenente `tenant_id`, `exp`, scope `upload:evidence`
- Riutilizzare servizio Evidence per cifratura e storage
- Enforcement rate limiting e size limits
- Ritornare risposta JSON con evidence ID e versione

**Testing**: Scrivere API feature tests usando JWT generati per upload file. Test rifiuto upload senza ruolo o token appropriato.

---

## Step 10: Implementare Funzionalità Export Audit (PDF/CSV) con Processing Asincrono

**Azione**: Implementare funzionalità export audit (PDF/CSV) con processing asincrono.

**Ragionamento**: Gli export possono essere grandi; usare code previene timeout richieste e migliora scalabilità.

**Dettagli Implementazione**:
- Creare `ExportAuditJob` (queued) che accetta `audit_id` e `format` (`pdf` o `csv`)
- Usare `barryvdh/laravel-dompdf` per generazione PDF e funzioni CSV native PHP
- Raccogliere tutti metadata evidenze, versioni, e metadata audit, poi renderizzare nel formato scelto
- Store file generati in storage provider-agnostic (configurato via env) cifrato sotto `exports/{tenant_id}/{audit_id}_{timestamp}.{ext}`
- Notificare utente via email con link download firmato (signed URL temporaneo, scade in 24h)
- Aggiungere componente UI in Blade per richiedere export e mostrare status export

**Testing**: Queue fake job in tests e asserire che è stato dispatchato. Integration test end-to-end.

---

## Step 11: Configurare Strategia Backup e Disaster Recovery

**Azione**: Configurare strategia backup e disaster recovery.

**Ragionamento**: Assicura durabilità dati e compliance con requisiti di resilienza DORA/NIS2.

**Dettagli Implementazione**:
- Usare `spatie/laravel-backup`:
  - Configurare destinazione backup usando `StorageService` (provider-agnostic)
  - Backup possono essere salvati su stesso provider storage o provider separato (configurabile)
  - Schedulare backup via Laravel Scheduler – daily database dump e incremental file sync
  - Supportare backup su storage separato per disaster recovery (es: MinIO per produzione, S3 per backup)
- Abilitare database dumps cifrati (cifratura applicata prima dello storage)
- Store encryption keys in secret manager locale o file cifrato; ruotare chiavi annualmente
- Creare runbook disaster-recovery documentando:
  - Restore da backup
  - Verifica integrità (confronto checksum)
  - Switch a storage secondario (backup location alternativo)
- Implementare health-check endpoints (`/health`) che reportano connettività DB, integrità storage, status queue worker

**Testing**: Eseguire backup job in ambiente test e verificare file appaiono nel backup bucket. Simulare restore.

---

## Step 12: Implementare Hardening Sicurezza Completo e Compliance Checks

**Azione**: Implementare hardening sicurezza completo e compliance checks.

**Ragionamento**: Allinea con security-by-design, privacy-by-design e mandati regolamentari.

**Dettagli Implementazione**:
- Enforcement HTTPS globalmente via middleware (`ForceHttps`)
- Impostare security headers usando middleware:
  - Content-Security-Policy, X-Content-Type-Options, X-Frame-Options, Referrer-Policy, Strict-Transport-Security
- Configurare protezione CSRF built-in Laravel
- Abilitare rate limiting su route login e verifica 2FA
- Usare `laravel/telescope` solo in development; disabilitare in production
- Eseguire security check e integrare con CI
- Documentare Data Processing Agreements (DPAs) e mappare data flows
- Preparare endpoint GDPR Data Subject Access Request (DSAR) che estrae tutti dati personali per un utente, cifrati, e invia via email

**Testing**: Scrivere security tests: tentativo accesso cross-tenant, test CSRF token mancante, verifica header HSTS presente.

---

## Step 13: Finalizzare Documentazione, Code Quality Checks e Pipeline CI/CD

**Azione**: Finalizzare documentazione, code quality checks e pipeline CI/CD.

**Ragionamento**: Assicura manutenibilità, facilità onboarding e continuous delivery compliance.

**Dettagli Implementazione**:
- Aggiungere blocchi PHPDoc a tutte le classi/metodi pubblici
- Scrivere README con istruzioni setup, overview architettura, linee guida contribuzione
- Aggiornare `composer.json` scripts per lint, test, security
- Configurare GitHub Actions workflow:
  1. Checkout
  2. Setup PHP 8.2, Composer
  3. Install dependencies
  4. Run `pint` per coding standard
  5. Run `phpunit`
  6. Run static analysis (`phpstan`)
  7. Deploy to staging
- Generare documentazione API usando `scribe`
- Aggiungere documentazione migration e seed data description

**Testing**: Verificare CI runs su fresh fork; assicurarsi tutti gli step passino. Revisionare manualmente API docs generati.

---

## Note Importanti

- Ogni step deve essere confermato dall'utente prima dell'esecuzione
- Dopo conferma, ogni step sarà eseguito usando gli strumenti Artiforge
- In caso di problemi o necessità di chiarimenti, chiedere feedback all'utente prima di procedere
- Seguire sempre le regole definite nel piano
