# Configurazione Database per AuditReady

## Panoramica

AuditReady supporta multipli database:
- **SQLite**: Sviluppo locale (default)
- **PostgreSQL**: Produzione (raccomandato)
- **MySQL/MariaDB**: Alternativa produzione

## PostgreSQL - Database di Produzione

PostgreSQL è il database raccomandato per la produzione grazie a:
- ✅ Performance elevate
- ✅ Affidabilità e stabilità
- ✅ Supporto avanzato per transazioni
- ✅ Compliance con standard SQL
- ✅ Ottimo per multi-tenant

## Configurazione PostgreSQL

### 1. Installazione PostgreSQL

#### Windows (Laragon)
Laragon include PostgreSQL. Verifica che sia installato e avviato.

#### Linux
```bash
# Ubuntu/Debian
sudo apt-get update
sudo apt-get install postgresql postgresql-contrib

# CentOS/RHEL
sudo yum install postgresql-server postgresql-contrib
```

#### macOS
```bash
brew install postgresql
brew services start postgresql
```

### 2. Creare Database e Utente

#### Opzione A: Via psql

```bash
# Accedi come utente postgres
sudo -u postgres psql

# Crea database
CREATE DATABASE auditready;

# Crea utente
CREATE USER auditready_user WITH PASSWORD 'your_secure_password';

# Assegna privilegi
GRANT ALL PRIVILEGES ON DATABASE auditready TO auditready_user;

# Per PostgreSQL 15+, assegna anche privilegi sullo schema
\c auditready
GRANT ALL ON SCHEMA public TO auditready_user;
ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL ON TABLES TO auditready_user;
ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL ON SEQUENCES TO auditready_user;

# Esci
\q
```

#### Opzione B: Via SQL Script

Crea file `database/setup-postgresql.sql`:

```sql
-- Crea database
CREATE DATABASE auditready;

-- Crea utente
CREATE USER auditready_user WITH PASSWORD 'your_secure_password';

-- Assegna privilegi
GRANT ALL PRIVILEGES ON DATABASE auditready TO auditready_user;

-- Connetti al database
\c auditready

-- Assegna privilegi schema (PostgreSQL 15+)
GRANT ALL ON SCHEMA public TO auditready_user;
ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL ON TABLES TO auditready_user;
ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL ON SEQUENCES TO auditready_user;
```

Esegui:
```bash
sudo -u postgres psql -f database/setup-postgresql.sql
```

### 3. Configurare .env

Aggiungi/modifica in `.env`:

```env
# Database Configuration
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=auditready
DB_USERNAME=auditready_user
DB_PASSWORD=your_secure_password

# PostgreSQL Specific
DB_CHARSET=utf8
DB_SEARCH_PATH=public
DB_SSLMODE=prefer
```

### 4. Test Connessione

```bash
# Test connessione
php artisan tinker
>>> DB::connection()->getPdo();
```

Se funziona, vedrai l'oggetto PDO.

### 5. Eseguire Migrazioni

```bash
# Esegui migrazioni
php artisan migrate

# Verifica tabelle create
php artisan tinker
>>> DB::select("SELECT tablename FROM pg_tables WHERE schemaname = 'public'");
```

## Database Multi-Tenant

### Database Centrale (Tenants Metadata)

Il database centrale contiene i metadati dei tenant:

```sql
-- Database centrale
CREATE DATABASE auditready_tenants;
CREATE USER auditready_tenants_user WITH PASSWORD 'secure_password';
GRANT ALL PRIVILEGES ON DATABASE auditready_tenants TO auditready_tenants_user;
```

Configurazione in `.env`:

```env
# Central Database (Tenants Metadata)
TENANTS_DB_CONNECTION=pgsql
TENANTS_DB_HOST=127.0.0.1
TENANTS_DB_PORT=5432
TENANTS_DB_DATABASE=auditready_tenants
TENANTS_DB_USERNAME=auditready_tenants_user
TENANTS_DB_PASSWORD=secure_password
```

### Database Tenant (Dinamici)

Ogni tenant avrà il proprio database:

```sql
-- Esempio: Database per tenant
CREATE DATABASE auditready_tenant_550e8400e29b41d4a716446655440000;
CREATE USER tenant_user WITH PASSWORD 'tenant_password';
GRANT ALL PRIVILEGES ON DATABASE auditready_tenant_550e8400e29b41d4a716446655440000 TO tenant_user;
```

**Nota**: I database tenant vengono creati automaticamente dal comando `php artisan tenant:create`.

## Configurazione Produzione

### Sicurezza

1. **Password Forti**:
   ```env
   DB_PASSWORD=your_very_secure_password_min_16_chars
   ```

2. **SSL/TLS**:
   ```env
   DB_SSLMODE=require
   DB_SSL_CA=/path/to/ca-certificate.crt
   ```

3. **Connection Pooling**:
   Considera l'uso di PgBouncer per connection pooling in produzione.

### Performance

1. **Connection Limits**:
   ```env
   # In config/database.php, aggiungi:
   'options' => [
       PDO::ATTR_PERSISTENT => false,
       PDO::ATTR_TIMEOUT => 5,
   ],
   ```

2. **Indexes**:
   Le migrazioni Laravel creeranno automaticamente gli indici necessari.

3. **Vacuum e Analyze**:
   Configura cron job per:
   ```bash
   # Esegui periodicamente
   psql -d auditready -c "VACUUM ANALYZE;"
   ```

## Backup PostgreSQL

### Backup Manuale

```bash
# Backup singolo database
pg_dump -U auditready_user -d auditready > backup_$(date +%Y%m%d).sql

# Backup con compressione
pg_dump -U auditready_user -d auditready -F c -f backup_$(date +%Y%m%d).dump

# Backup tutti i database
pg_dumpall -U postgres > all_databases_$(date +%Y%m%d).sql
```

### Restore

```bash
# Restore da SQL
psql -U auditready_user -d auditready < backup_20240101.sql

# Restore da dump compresso
pg_restore -U auditready_user -d auditready backup_20240101.dump
```

### Backup Automatico

Crea script `database/backup-postgresql.sh`:

```bash
#!/bin/bash
BACKUP_DIR="/path/to/backups"
DATE=$(date +%Y%m%d_%H%M%S)
DB_NAME="auditready"
DB_USER="auditready_user"

# Crea directory backup
mkdir -p $BACKUP_DIR

# Backup
pg_dump -U $DB_USER -d $DB_NAME -F c -f $BACKUP_DIR/${DB_NAME}_${DATE}.dump

# Mantieni solo ultimi 30 giorni
find $BACKUP_DIR -name "${DB_NAME}_*.dump" -mtime +30 -delete

echo "Backup completato: ${DB_NAME}_${DATE}.dump"
```

Aggiungi a crontab:
```bash
# Backup giornaliero alle 2:00 AM
0 2 * * * /path/to/backup-postgresql.sh
```

## Troubleshooting

### Errore: "could not connect to server"

**Causa**: PostgreSQL non è in esecuzione o non è raggiungibile.

**Soluzione**:
```bash
# Verifica se PostgreSQL è in esecuzione
sudo systemctl status postgresql  # Linux
# o
brew services list  # macOS

# Avvia PostgreSQL
sudo systemctl start postgresql  # Linux
# o
brew services start postgresql  # macOS
```

### Errore: "password authentication failed"

**Causa**: Credenziali errate in `.env`.

**Soluzione**:
1. Verifica username e password in `.env`
2. Verifica che l'utente esista:
   ```sql
   SELECT usename FROM pg_user;
   ```
3. Reimposta password se necessario:
   ```sql
   ALTER USER auditready_user WITH PASSWORD 'new_password';
   ```

### Errore: "database does not exist"

**Causa**: Database non creato.

**Soluzione**:
```sql
CREATE DATABASE auditready;
```

### Errore: "permission denied for schema public"

**Causa**: Utente non ha privilegi sullo schema (PostgreSQL 15+).

**Soluzione**:
```sql
\c auditready
GRANT ALL ON SCHEMA public TO auditready_user;
ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL ON TABLES TO auditready_user;
```

## Configurazione Laravel

### config/database.php

La configurazione PostgreSQL è già presente in `config/database.php`:

```php
'pgsql' => [
    'driver' => 'pgsql',
    'host' => env('DB_HOST', '127.0.0.1'),
    'port' => env('DB_PORT', '5432'),
    'database' => env('DB_DATABASE', 'auditready'),
    'username' => env('DB_USERNAME', 'postgres'),
    'password' => env('DB_PASSWORD', ''),
    'charset' => env('DB_CHARSET', 'utf8'),
    'search_path' => env('DB_SEARCH_PATH', 'public'),
    'sslmode' => env('DB_SSLMODE', 'prefer'),
],
```

### Estensioni PostgreSQL Richieste

AuditReady richiede le seguenti estensioni PostgreSQL:

```sql
-- UUID support (per tenant IDs)
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

-- Full-text search (opzionale, per ricerca avanzata)
CREATE EXTENSION IF NOT EXISTS "pg_trgm";
```

Aggiungi al seeder o migration iniziale.

## Best Practices

### 1. Connection Pooling

Usa PgBouncer per connection pooling in produzione:

```ini
[databases]
auditready = host=127.0.0.1 port=5432 dbname=auditready

[pgbouncer]
pool_mode = transaction
max_client_conn = 100
default_pool_size = 20
```

### 2. Monitoring

Monitora:
- Connection count
- Query performance
- Database size
- Lock contention

### 3. Maintenance

Esegui periodicamente:
- `VACUUM`: Rimuove tuple morte
- `ANALYZE`: Aggiorna statistiche
- `REINDEX`: Ricostruisce indici

### 4. Backup Strategy

- **Full backup**: Giornaliero
- **Incremental**: Ogni 6 ore (se possibile)
- **Retention**: 30 giorni
- **Test restore**: Mensile

## Migrazione da SQLite a PostgreSQL

Se stai migrando da SQLite a PostgreSQL:

```bash
# 1. Backup SQLite
cp database/database.sqlite database/database.sqlite.backup

# 2. Configura PostgreSQL in .env
DB_CONNECTION=pgsql
# ... altre configurazioni ...

# 3. Esegui migrazioni
php artisan migrate:fresh

# 4. (Opzionale) Importa dati da SQLite
# Usa tool come sqlite3dump o script personalizzato
```

## Riferimenti

- [PostgreSQL Documentation](https://www.postgresql.org/docs/)
- [Laravel Database Configuration](https://laravel.com/docs/database)
- [PostgreSQL Best Practices](https://wiki.postgresql.org/wiki/Don%27t_Do_This)

---

**Nota**: Per sviluppo locale, SQLite è più semplice. Per produzione, PostgreSQL è fortemente raccomandato.
