# AuditReady

**Piattaforma operativa di supporto all’audit con focus su sicurezza, privacy e compliance**

[![Laravel](https://img.shields.io/badge/Laravel-12.44.0-red.svg)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.3+-blue.svg)](https://php.net)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

## Panoramica

AuditReady è una piattaforma multi-tenant progettata per supportare organizzazioni nella gestione sicura di evidenze per processi di audit. La piattaforma garantisce isolamento completo dei dati, crittografia end-to-end e compliance con GDPR, DORA e NIS2.

### Cosa fa AuditReady

AuditReady:
- ✅ **Archivia** evidenze in modo sicuro e strutturato
- ✅ **Versiona** documenti mantenendo storico completo
- ✅ **Struttura** dati per facilitare processi di audit
- ✅ **Esporta** evidenze in formati standard (PDF, CSV)
- ✅ **Isola** completamente i dati tra organizzazioni (multi-tenant)
- ✅ **Cifra** tutti i file con crittografia AES-256
- ✅ **Traccia** tutte le operazioni con audit trail immutabile

### Cosa NON fa AuditReady

AuditReady **NON**:
- ❌ Valuta la conformità
- ❌ Interpreta dati
- ❌ Fornisce consulenza legale
- ❌ Esegue scoring o rating

Questa distinzione è fondamentale per ridurre il rischio legale e semplificare la compliance.

## Caratteristiche principali

### Sicurezza e privacy

- **Multi-Tenant Isolato**: Ogni organizzazione ha database separato (isolamento fisico)
- **Crittografia End-to-End**: File cifrati con AES-256 prima dello storage
- **2FA Obbligatoria**: Autenticazione a due fattori TOTP (compatibile Microsoft Authenticator)
- **RBAC Granulare**: 5 ruoli con permessi specifici (Least Privilege) tramite Spatie Permission
- **Audit Trail Immutabile**: Log append-only con firma HMAC per integrità
- **HTTPS Obbligatorio**: TLS 1.2+ con HSTS

### Gestione evidenze

- **Versioning Completo**: Ogni modifica genera nuova versione, storico accessibile
- **Integrità Garantita**: Checksum SHA-256 per verifica integrità file
- **Upload Sicuro**: Validazione file, limiti dimensione, storage isolato per tenant
- **Export Asincrono**: Generazione PDF/CSV via queue system

### Multi-tenancy (multi-database)

- **Isolamento Completo**: Database separato per ogni tenant
- **Creazione Automatizzata**: Comando artisan per creare nuovo tenant (~30 secondi)
- **Migrazioni Centralizzate**: Esecuzione migrazioni su tutti i tenant
- **Backup Mirati**: Backup/restore per singolo tenant

### Storage flessibile (provider-agnostic, S3-compatible)

- **Provider-Agnostic**: Supporto multipli provider S3-compatible
  - MinIO (self-hosted, default)
  - AWS S3
  - DigitalOcean Spaces
  - Wasabi
  - Backblaze B2
  - Storage locale (sviluppo)
- **Configurazione via Environment**: Cambio provider senza modificare codice

### Interfaccia (Filament Admin Panel + "Enterprise Audit" UI)

- **Filament 4.4.0**: Admin panel moderno e intuitivo
- **Livewire**: Componenti reattivi senza JavaScript complesso
- **Enterprise Audit theme**: palette neutral + densità informativa + focus states accessibili
- **Login & 2FA verify coerenti**: pagine auth "boxed" e allineate al tema
- **Sidebar più densa**: più stretta, collassabile (icone) e fully-collapsible su desktop
- **Preferenza utente**: stato sidebar (aperta/chiusa) salvato in `localStorage`
- **Visualizzazione Grafo Relazioni**: Grafo interattivo per visualizzare relazioni tra audit, controlli ed evidenze (Cytoscape.js)

## Moduli sviluppati (funzionalità)

- **Modulo 1 — Core Platform**: multi-tenancy multi-database, storage provider-agnostic, crittografia file, audit trail e gestione evidenze.
- **Modulo 2 — Accesso e Sicurezza**: login, 2FA TOTP obbligatoria per ruoli critici, RBAC (Spatie Permission) e policy-based authorization.
- **Modulo 3 — Third‑Party Evidence Requestor**: richiesta evidenze a fornitori esterni con link dedicato, tracking stato/scadenza, upload e logging (supplier sempre visibile).
- **Modulo 4 — Audit Day Pack Generator**: generazione pacchetto "giorno audit" (ZIP/PDF) con indice controlli, evidenze organizzate e audit trail, download sicuro e generazione asincrona.
- **Modulo 5 — Policy ↔ Control Linker**: gestione policy con **upload** e/o **link intranet**, mappatura policy↔controlli, statistiche coverage e sezione coverage nei report export.
- **Miglioria — Audit Relationship Graph**: grafo interattivo Audit–Controls–Evidences–Policies (Cytoscape.js), fullscreen, click-to-navigate, caching e invalidazione automatica. Vedi **[docs/visualization.md](docs/visualization.md)**.
- **Miglioria — Collegamento Controls ↔ Audit**: relazione molti-a-molti e UI Filament (RelationManager) per collegare/scollegare controlli direttamente dalla view dell'audit.
- **Miglioria — Gap Snapshot**: wizard guidato per valutazione "gap" per standard/controlli con export dedicato (PDF).
- **Miglioria — Ownership Matrix**: matrice di ownership dei controlli (per owner/ruolo), con export (PDF/Excel).

## Stack tecnologico

### Backend
- **Laravel 12.x**
- **PHP 8.3+**
- **PostgreSQL** (produzione)
- **SQLite** (dev)

### Frontend
- **Filament Admin Panel 4.4.0**
- **Livewire 3.x**
- **Tailwind CSS 4**
- **Vite 7**
- **Cytoscape.js 3.24+** (visualizzazione grafo relazioni)

### Sicurezza
- **Laravel Fortify** (auth)
- **pragmarx/google2fa-laravel** + **pragmarx/google2fa-qrcode** (TOTP 2FA, Microsoft Authenticator)
- **spatie/laravel-permission** (RBAC)
- **AES-256** (crittografia applicativa file)

### Multi-tenancy
- **stancl/tenancy** (multi-database, subdomain resolution)

### Storage
- **Flysystem** - Astrazione storage
- **MinIO** - Self-hosted S3-compatible (default)
- **AWS S3** - Cloud storage (opzionale)

## Documentazione

La documentazione completa è disponibile nella cartella `docs/`:

- **[Architettura](docs/architecture.md)** - Design architetturale completo
- **[Strategia Multi-Database](docs/multi-database-tenant-strategy.md)** - Approccio multi-tenant
- **[Strategia Storage](docs/storage-strategy.md)** - Storage provider-agnostic
- **[Configurazione Provider Storage](docs/storage-providers.md)** - Guida configurazione provider
- **[Integrazione Filament](docs/filament-integration.md)** - Setup interfaccia admin
- **[Strategia 2FA](docs/2fa-microsoft-authenticator-strategy.md)** - Autenticazione a due fattori
- **[Visualizzazione Grafo Relazioni](docs/visualization.md)** - Grafo interattivo audit-controlli-evidenze
- **[Analisi Funzionale](documents/analisi-funzionale.md)** - Requisiti e specifiche

## Requisiti

- PHP >= 8.3
- Composer
- Node.js >= 18.x e NPM
- Database: PostgreSQL 13+ / SQLite (dev)
- Storage: MinIO (produzione) o provider S3-compatible

## Installazione

### 1. Clona il repository

```bash
git clone https://github.com/stefanochermazts/AuditReady.git
cd AuditReady
```

### 2. Installa dipendenze

```bash
composer install
npm install
```

### 3. Configura ambiente

```bash
cp .env.example .env
php artisan key:generate
```

### 4. Configura database

Modifica `.env` con le tue credenziali database:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=auditready
DB_USERNAME=postgres
DB_PASSWORD=secret
```

### 5. Esegui migrazioni (landlord/central)

```bash
php artisan migrate
```

### 6. Compila assets

```bash
npm run build
```

### 7. Avvia server sviluppo

```bash
php artisan serve
```

L'applicazione sarà disponibile su `http://localhost:8000`

## Configurazione

### Storage Provider

Configura il provider storage in `.env`:

```env
STORAGE_PROVIDER=minio  # o s3, spaces, wasabi, b2, local
```

Vedi [Configurazione Provider Storage](docs/storage-providers.md) per dettagli.

### Multi-Tenant

Crea un nuovo tenant:

```bash
php artisan tenant:create "Nome Cliente" "cliente.auditready.com"
```

Vedi [Strategia Multi-Database](docs/multi-database-tenant-strategy.md) per dettagli.

#### Migrazioni tenant

Per applicare le migrazioni ai tenant:

```bash
# Tutti i tenant (può fallire se esistono tenant con DB mancante)
php artisan tenants:migrate

# Un tenant specifico (consigliato in dev)
php artisan tenants:migrate --tenants=<TENANT_UUID>
```

## Testing

```bash
# Esegui tutti i test
php artisan test

# Test con coverage
php artisan test --coverage
```

## Ruoli utente

AuditReady supporta 5 ruoli con permessi specifici:

| Ruolo | Permessi |
|-------|----------|
| **Organization Owner** | Gestione tenant, assegnazione ruoli, eliminazione organizzazione |
| **Audit Manager** | Creare/chiudere audit, visualizzare tutte evidenze, export |
| **Contributor** | Upload/modificare proprie evidenze, visualizzare stato audit |
| **Viewer** | Accesso read-only a evidenze |
| **External Uploader** | Upload limitato via API, nessuna visualizzazione |

## Compliance e sicurezza

AuditReady è progettato per essere compliant con:

- **GDPR**: Isolamento dati, crittografia, diritto all'oblio
- **DORA**: Resilienza operativa, backup, disaster recovery
- **NIS2**: Sicurezza network, incident response, audit trail

### Principi di Sicurezza

- **Security by Design**: Controlli sicurezza in ogni componente
- **Privacy by Design**: Minimizzazione dati, isolamento tenant
- **Defense in Depth**: Multipli livelli di sicurezza
- **Least Privilege**: Accesso minimo necessario
- **Segregation of Duties**: Separazione responsabilità

## Stato del progetto

🚧 **In Sviluppo** - Il progetto è attualmente in fase di sviluppo attivo.

Vedi [Piano di Sviluppo](.artiforge/plan-auditready-development.md) per lo stato attuale.

## Contribuire

Le contribuzioni sono benvenute! Per contribuire:

1. Fai fork del repository
2. Crea un branch per la tua feature (`git checkout -b feature/AmazingFeature`)
3. Committa le modifiche (`git commit -m 'Add some AmazingFeature'`)
4. Push al branch (`git push origin feature/AmazingFeature`)
5. Apri una Pull Request

### Standard di Codice

Il progetto utilizza:
- **Laravel Pint** per code formatting
- **PSR-12** coding standards
- **PHPUnit** per testing

## License

Questo progetto è rilasciato sotto licenza [MIT](LICENSE).

## Autori

- **Stefano Chermaz** - [GitHub](https://github.com/stefanochermazts)

## Ringraziamenti

- [Laravel](https://laravel.com) - Framework PHP
- [Filament](https://filamentphp.com) - Admin panel
- [Spatie](https://spatie.be) - Pacchetti Laravel di qualità
- [stancl/tenancy](https://tenancyforlaravel.com) - Multi-tenancy

## Supporto

Per domande o supporto:
- Apri una [Issue](https://github.com/stefanochermazts/AuditReady/issues)
- Consulta la [Documentazione](docs/)

---

**AuditReady** - Gestione sicura di evidenze per audit con focus su privacy e compliance.
