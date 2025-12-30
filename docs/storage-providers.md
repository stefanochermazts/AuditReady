# Configurazione Provider Storage per AuditReady

## Panoramica

AuditReady supporta multipli provider storage S3-compatibili. Questo documento descrive come configurare ciascun provider.

## Architettura Provider-Agnostic

Il sistema utilizza un `StorageService` che astrae il provider, permettendo di cambiare provider senza modificare il codice applicativo.

```php
// Il codice usa sempre StorageService, non direttamente il provider
StorageService::put($path, $content);
StorageService::get($path);
StorageService::delete($path);
```

## Configurazione Generale

### Variabile d'Ambiente Principale

```env
STORAGE_PROVIDER=minio  # minio, s3, spaces, wasabi, b2, local
```

### Configurazione in config/filesystems.php

Il sistema configura automaticamente il provider selezionato basandosi su `STORAGE_PROVIDER`.

---

## 1. MinIO (Self-Hosted) - Default Produzione

### Caratteristiche
- ✅ Self-hosted (controllo completo)
- ✅ Nessun costo variabile
- ✅ Privacy massima
- ✅ S3-compatible

### Setup MinIO

```bash
# Docker Compose
services:
  minio:
    image: minio/minio:latest
    command: server /data --console-address ":9001"
    environment:
      MINIO_ROOT_USER: admin
      MINIO_ROOT_PASSWORD: your-secure-password
    volumes:
      - minio_data:/data
    ports:
      - "9000:9000"  # API
      - "9001:9001"  # Console
```

### Configurazione .env

```env
STORAGE_PROVIDER=minio

# MinIO Configuration
MINIO_ENDPOINT=http://minio:9000
MINIO_ACCESS_KEY=admin
MINIO_SECRET_KEY=your-secure-password
MINIO_BUCKET=auditready
MINIO_REGION=us-east-1
MINIO_USE_PATH_STYLE=true
```

### Configurazione config/filesystems.php

```php
'minio' => [
    'driver' => 's3',
    'key' => env('MINIO_ACCESS_KEY'),
    'secret' => env('MINIO_SECRET_KEY'),
    'region' => env('MINIO_REGION', 'us-east-1'),
    'bucket' => env('MINIO_BUCKET', 'auditready'),
    'endpoint' => env('MINIO_ENDPOINT', 'http://minio:9000'),
    'use_path_style_endpoint' => env('MINIO_USE_PATH_STYLE', true),
    'throw' => false,
],
```

---

## 2. AWS S3

### Caratteristiche
- ✅ Scalabilità globale
- ✅ Alta disponibilità
- ✅ Integrazione con altri servizi AWS
- ⚠️ Costi variabili (per GB)
- ⚠️ Dati su infrastruttura AWS

### Configurazione .env

```env
STORAGE_PROVIDER=s3

# AWS S3 Configuration
AWS_ACCESS_KEY_ID=your-access-key
AWS_SECRET_ACCESS_KEY=your-secret-key
AWS_DEFAULT_REGION=eu-west-1
AWS_BUCKET=auditready-prod
AWS_URL=https://auditready-prod.s3.eu-west-1.amazonaws.com
AWS_USE_PATH_STYLE_ENDPOINT=false
```

### Configurazione config/filesystems.php

```php
's3' => [
    'driver' => 's3',
    'key' => env('AWS_ACCESS_KEY_ID'),
    'secret' => env('AWS_SECRET_ACCESS_KEY'),
    'region' => env('AWS_DEFAULT_REGION'),
    'bucket' => env('AWS_BUCKET'),
    'url' => env('AWS_URL'),
    'endpoint' => env('AWS_ENDPOINT'),
    'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
    'throw' => false,
],
```

### Note AWS S3
- Usa IAM roles per produzione (più sicuro di access keys)
- Configura bucket policy per accesso limitato
- Abilita versioning e lifecycle policies

---

## 3. DigitalOcean Spaces

### Caratteristiche
- ✅ S3-compatible
- ✅ Costi prevedibili
- ✅ CDN integrato
- ✅ Data center in Europa disponibili

### Configurazione .env

```env
STORAGE_PROVIDER=spaces

# DigitalOcean Spaces Configuration
DO_SPACES_KEY=your-spaces-key
DO_SPACES_SECRET=your-spaces-secret
DO_SPACES_ENDPOINT=https://fra1.digitaloceanspaces.com
DO_SPACES_REGION=fra1
DO_SPACES_BUCKET=auditready
DO_SPACES_URL=https://auditready.fra1.cdn.digitaloceanspaces.com
```

### Configurazione config/filesystems.php

```php
'spaces' => [
    'driver' => 's3',
    'key' => env('DO_SPACES_KEY'),
    'secret' => env('DO_SPACES_SECRET'),
    'endpoint' => env('DO_SPACES_ENDPOINT'),
    'region' => env('DO_SPACES_REGION'),
    'bucket' => env('DO_SPACES_BUCKET'),
    'url' => env('DO_SPACES_URL'),
    'use_path_style_endpoint' => false,
    'throw' => false,
],
```

---

## 4. Wasabi

### Caratteristiche
- ✅ S3-compatible
- ✅ Costi bassi (no egress fees)
- ✅ Performance elevate
- ✅ Data center in Europa disponibili

### Configurazione .env

```env
STORAGE_PROVIDER=wasabi

# Wasabi Configuration
WASABI_ACCESS_KEY=your-access-key
WASABI_SECRET_KEY=your-secret-key
WASABI_REGION=eu-central-1
WASABI_BUCKET=auditready
WASABI_ENDPOINT=https://s3.eu-central-1.wasabisys.com
```

### Configurazione config/filesystems.php

```php
'wasabi' => [
    'driver' => 's3',
    'key' => env('WASABI_ACCESS_KEY'),
    'secret' => env('WASABI_SECRET_KEY'),
    'region' => env('WASABI_REGION'),
    'bucket' => env('WASABI_BUCKET'),
    'endpoint' => env('WASABI_ENDPOINT'),
    'use_path_style_endpoint' => false,
    'throw' => false,
],
```

---

## 5. Backblaze B2

### Caratteristiche
- ✅ S3-compatible (via B2 S3 API)
- ✅ Costi molto bassi
- ✅ No egress fees
- ⚠️ Richiede configurazione S3 API

### Configurazione .env

```env
STORAGE_PROVIDER=b2

# Backblaze B2 Configuration
B2_APPLICATION_KEY_ID=your-key-id
B2_APPLICATION_KEY=your-application-key
B2_BUCKET_ID=your-bucket-id
B2_BUCKET_NAME=auditready
B2_ENDPOINT=https://s3.eu-central-003.backblazeb2.com
```

### Configurazione config/filesystems.php

```php
'b2' => [
    'driver' => 's3',
    'key' => env('B2_APPLICATION_KEY_ID'),
    'secret' => env('B2_APPLICATION_KEY'),
    'region' => env('B2_REGION', 'us-west-004'),
    'bucket' => env('B2_BUCKET_NAME'),
    'endpoint' => env('B2_ENDPOINT'),
    'use_path_style_endpoint' => true,
    'throw' => false,
],
```

### Note Backblaze B2
- Abilita S3 API nel bucket B2
- Usa endpoint S3-compatible (non endpoint B2 nativo)

---

## 6. Storage Locale (Sviluppo/Test)

### Caratteristiche
- ✅ Nessuna configurazione esterna
- ✅ Ideale per sviluppo
- ⚠️ Non scalabile
- ⚠️ Non adatto per produzione

### Configurazione .env

```env
STORAGE_PROVIDER=local
```

### Configurazione config/filesystems.php

```php
'local' => [
    'driver' => 'local',
    'root' => storage_path('app/tenants'),
    'serve' => true,
    'throw' => false,
],
```

---

## Implementazione StorageService

### Service Layer

```php
namespace App\Services;

use Illuminate\Support\Facades\Storage;

class StorageService
{
    public static function disk()
    {
        $provider = env('STORAGE_PROVIDER', 'local');
        
        // Mappa provider a disk name
        $diskMap = [
            'minio' => 'minio',
            's3' => 's3',
            'spaces' => 'spaces',
            'wasabi' => 'wasabi',
            'b2' => 'b2',
            'local' => 'local',
        ];
        
        $disk = $diskMap[$provider] ?? 'local';
        
        return Storage::disk($disk);
    }
    
    public static function put(string $path, $content): bool
    {
        return self::disk()->put($path, $content);
    }
    
    public static function get(string $path): string
    {
        return self::disk()->get($path);
    }
    
    public static function delete(string $path): bool
    {
        return self::disk()->delete($path);
    }
    
    public static function exists(string $path): bool
    {
        return self::disk()->exists($path);
    }
    
    public static function url(string $path): string
    {
        return self::disk()->url($path);
    }
    
    public static function temporaryUrl(string $path, int $expiration = 3600): string
    {
        return self::disk()->temporaryUrl($path, now()->addSeconds($expiration));
    }
}
```

### Utilizzo nel Codice

```php
// Invece di Storage::disk('minio')->put(...)
StorageService::put("tenants/{$tenantId}/evidences/file.pdf", $encryptedContent);

// Il provider è trasparente al codice applicativo
```

---

## Migrazione tra Provider

### Processo di Migrazione

1. **Configura nuovo provider** in `.env`
2. **Testa connessione**:
   ```bash
   php artisan tinker
   >>> StorageService::disk()->put('test.txt', 'test');
   ```
3. **Migra dati** (se necessario):
   ```bash
   php artisan storage:migrate --from=minio --to=s3
   ```
4. **Verifica integrità**:
   ```bash
   php artisan storage:verify
   ```

### Script di Migrazione

```php
// app/Console/Commands/MigrateStorage.php
class MigrateStorage extends Command
{
    public function handle()
    {
        $from = $this->option('from');
        $to = $this->option('to');
        
        $fromDisk = Storage::disk($from);
        $toDisk = Storage::disk($to);
        
        // Migra tutti i file
        $files = $fromDisk->allFiles('tenants');
        
        foreach ($files as $file) {
            $content = $fromDisk->get($file);
            $toDisk->put($file, $content);
            $this->info("Migrated: {$file}");
        }
    }
}
```

---

## Confronto Provider

| Provider | Costi | Performance | Privacy | Scalabilità | Setup |
|----------|-------|-------------|---------|-------------|-------|
| **MinIO** | Fissi | Alta | Massima | Alta | Media |
| **AWS S3** | Variabili | Alta | Media | Massima | Facile |
| **DO Spaces** | Prevedibili | Alta | Media | Alta | Facile |
| **Wasabi** | Bassi | Alta | Media | Alta | Facile |
| **B2** | Molto bassi | Media | Media | Alta | Media |
| **Local** | Nessuno | Media | Massima | Bassa | Facile |

---

## Best Practices

### 1. Selezione Provider

- **Sviluppo**: Usa `local`
- **Produzione Self-Hosted**: Usa `minio`
- **Produzione Cloud**: Valuta `spaces`, `wasabi`, o `s3` in base a costi/compliance

### 2. Sicurezza

- **Credenziali**: Mai committare in git, usa `.env`
- **IAM Roles**: Preferisci IAM roles per AWS (non access keys)
- **Bucket Policies**: Limita accesso solo all'applicazione
- **Crittografia**: Sempre abilitata a livello applicazione (AES-256)

### 3. Monitoring

- Monitora spazio utilizzato
- Alert se quota > 80%
- Monitora costi (per provider cloud)
- Verifica integrità file periodicamente

### 4. Backup

- Backup separati per ogni provider
- Test restore periodicamente
- Retention configurabile

---

## Troubleshooting

### Problema: Connessione fallita

**Soluzione**:
1. Verifica credenziali in `.env`
2. Verifica endpoint corretto
3. Verifica firewall/network
4. Testa connessione manuale:
   ```bash
   php artisan tinker
   >>> StorageService::disk()->exists('test');
   ```

### Problema: File non trovati dopo migrazione

**Soluzione**:
1. Verifica path corretti
2. Verifica permessi bucket
3. Esegui verifica integrità:
   ```bash
   php artisan storage:verify
   ```

---

## Conclusione

L'architettura provider-agnostic di AuditReady offre:
- ✅ **Flessibilità** nella scelta del provider
- ✅ **Nessun vendor lock-in**
- ✅ **Facile migrazione** tra provider
- ✅ **Codice unificato** per tutti i provider

Scegli il provider più adatto alle tue esigenze di privacy, costi e compliance!
