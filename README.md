# AuditReady 🔒

**Piattaforma operativa di supporto all'audit con focus su sicurezza, privacy e compliance**

[![Laravel](https://img.shields.io/badge/Laravel-12.0-red.svg)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.2+-blue.svg)](https://php.net)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

## 📋 Panoramica

AuditReady è una piattaforma multi-tenant progettata per supportare organizzazioni nella gestione sicura di evidenze per processi di audit. La piattaforma garantisce isolamento completo dei dati, crittografia end-to-end e compliance con GDPR, DORA e NIS2.

### 🎯 Cosa fa AuditReady

AuditReady:
- ✅ **Archivia** evidenze in modo sicuro e strutturato
- ✅ **Versiona** documenti mantenendo storico completo
- ✅ **Struttura** dati per facilitare processi di audit
- ✅ **Esporta** evidenze in formati standard (PDF, CSV)
- ✅ **Isola** completamente i dati tra organizzazioni (multi-tenant)
- ✅ **Cifra** tutti i file con crittografia AES-256
- ✅ **Traccia** tutte le operazioni con audit trail immutabile

### ❌ Cosa NON fa AuditReady

AuditReady **NON**:
- ❌ Valuta la conformità
- ❌ Interpreta dati
- ❌ Fornisce consulenza legale
- ❌ Esegue scoring o rating

Questa distinzione è fondamentale per ridurre il rischio legale e semplificare la compliance.

## ✨ Caratteristiche Principali

### 🔐 Sicurezza e Privacy

- **Multi-Tenant Isolato**: Ogni organizzazione ha database separato (isolamento fisico)
- **Crittografia End-to-End**: File cifrati con AES-256 prima dello storage
- **2FA Obbligatoria**: Autenticazione a due fattori TOTP (compatibile Microsoft Authenticator)
- **RBAC Granulare**: 5 ruoli con permessi specifici (Least Privilege)
- **Audit Trail Immutabile**: Log append-only con firma HMAC per integrità
- **HTTPS Obbligatorio**: TLS 1.2+ con HSTS

### 📦 Gestione Evidenze

- **Versioning Completo**: Ogni modifica genera nuova versione, storico accessibile
- **Integrità Garantita**: Checksum SHA-256 per verifica integrità file
- **Upload Sicuro**: Validazione file, antivirus scanning, rate limiting
- **Export Asincrono**: Generazione PDF/CSV via queue system

### 🏢 Multi-Tenancy

- **Isolamento Completo**: Database separato per ogni tenant
- **Creazione Automatizzata**: Comando artisan per creare nuovo tenant (~30 secondi)
- **Migrazioni Centralizzate**: Esecuzione migrazioni su tutti i tenant
- **Backup Mirati**: Backup/restore per singolo tenant

### 💾 Storage Flessibile

- **Provider-Agnostic**: Supporto multipli provider S3-compatible
  - MinIO (self-hosted, default)
  - AWS S3
  - DigitalOcean Spaces
  - Wasabi
  - Backblaze B2
  - Storage locale (sviluppo)
- **Configurazione via Environment**: Cambio provider senza modificare codice

### 🎨 Interfaccia Moderna

- **Filament 3.x**: Admin panel moderno e intuitivo
- **Livewire**: Componenti reattivi senza JavaScript complesso
- **Responsive**: Interfaccia ottimizzata per desktop e mobile
- **Accessibile**: Componenti UI accessibili out-of-the-box

## 🛠 Stack Tecnologico

### Backend
- **Laravel 12.0** - Framework PHP
- **PHP 8.2+** - Linguaggio di programmazione
- **PostgreSQL/MySQL** - Database (produzione)
- **SQLite** - Database (sviluppo)

### Frontend
- **Filament 3.x** - Admin panel
- **Livewire 3.x** - Componenti reattivi
- **Tailwind CSS 4.0** - Framework CSS
- **Vite 7.0** - Build tool

### Sicurezza
- **Laravel Fortify** - Autenticazione
- **Google2FA** - 2FA TOTP (compatibile Microsoft Authenticator)
- **Spatie Permission** - RBAC
- **AES-256** - Crittografia file

### Multi-Tenancy
- **Tenancy for Laravel** - Gestione multi-database

### Storage
- **Flysystem** - Astrazione storage
- **MinIO** - Self-hosted S3-compatible (default)
- **AWS S3** - Cloud storage (opzionale)

## 📚 Documentazione

La documentazione completa è disponibile nella cartella `docs/`:

- **[Architettura](docs/architecture.md)** - Design architetturale completo
- **[Strategia Multi-Database](docs/multi-database-tenant-strategy.md)** - Approccio multi-tenant
- **[Strategia Storage](docs/storage-strategy.md)** - Storage provider-agnostic
- **[Configurazione Provider Storage](docs/storage-providers.md)** - Guida configurazione provider
- **[Integrazione Filament](docs/filament-integration.md)** - Setup interfaccia admin
- **[Strategia 2FA](docs/2fa-microsoft-authenticator-strategy.md)** - Autenticazione a due fattori
- **[Analisi Funzionale](documents/analisi-funzionale.md)** - Requisiti e specifiche

## 🚀 Requisiti

- PHP >= 8.2
- Composer
- Node.js >= 18.x e NPM
- Database: PostgreSQL 13+ / MySQL 8+ / SQLite (dev)
- Storage: MinIO (produzione) o provider S3-compatible

## 📦 Installazione

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
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=auditready
DB_USERNAME=root
DB_PASSWORD=
```

### 5. Esegui migrazioni

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

## 🔧 Configurazione

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

## 🧪 Testing

```bash
# Esegui tutti i test
php artisan test

# Test con coverage
php artisan test --coverage
```

## 📋 Ruoli Utente

AuditReady supporta 5 ruoli con permessi specifici:

| Ruolo | Permessi |
|-------|----------|
| **Organization Owner** | Gestione tenant, assegnazione ruoli, eliminazione organizzazione |
| **Audit Manager** | Creare/chiudere audit, visualizzare tutte evidenze, export |
| **Contributor** | Upload/modificare proprie evidenze, visualizzare stato audit |
| **Viewer** | Accesso read-only a evidenze |
| **External Uploader** | Upload limitato via API, nessuna visualizzazione |

## 🔒 Compliance e Sicurezza

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

## 📊 Stato del Progetto

🚧 **In Sviluppo** - Il progetto è attualmente in fase di sviluppo attivo.

Vedi [Piano di Sviluppo](.artiforge/plan-auditready-development.md) per lo stato attuale.

## 🤝 Contribuire

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

## 📝 License

Questo progetto è rilasciato sotto licenza [MIT](LICENSE).

## 👥 Autori

- **Stefano Chermaz** - [GitHub](https://github.com/stefanochermazts)

## 🙏 Ringraziamenti

- [Laravel](https://laravel.com) - Framework PHP
- [Filament](https://filamentphp.com) - Admin panel
- [Spatie](https://spatie.be) - Pacchetti Laravel di qualità
- [Tenancy for Laravel](https://tenancyforlaravel.com) - Multi-tenancy

## 📞 Supporto

Per domande o supporto:
- Apri una [Issue](https://github.com/stefanochermazts/AuditReady/issues)
- Consulta la [Documentazione](docs/)

---

**AuditReady** - Gestione sicura di evidenze per audit con focus su privacy e compliance.
