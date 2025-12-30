# Strategia Storage per AuditReady

## Panoramica

AuditReady utilizza un'architettura storage **provider-agnostic** che supporta multipli provider S3-compatibili. Questo garantisce:
- **Flessibilità**: Possibilità di scegliere il provider più adatto (self-hosted o cloud)
- **Nessun vendor lock-in**: Facile migrazione tra provider senza cambiare codice
- **Controllo**: Scelta tra self-hosted (MinIO) o provider cloud
- **Compliance**: Supporto per requisiti di data residency specifici

## Architettura Storage Provider-Agnostic

### Provider Supportati

AuditReady supporta i seguenti provider storage (tutti S3-compatible):

1. **MinIO** (Self-hosted, default produzione) - `minio`
2. **AWS S3** (Amazon Web Services) - `s3`
3. **DigitalOcean Spaces** - `spaces`
4. **Wasabi** - `wasabi`
5. **Backblaze B2** - `b2`
6. **Storage Locale** (sviluppo/test) - `local`

### Selezione Provider

Il provider viene selezionato tramite variabile d'ambiente:

```env
STORAGE_PROVIDER=minio  # o s3, spaces, wasabi, b2, local
```

### Ambiente di Sviluppo
- **Provider**: `local` (default)
- **Driver**: `local` (Laravel Filesystem)
- **Path**: `storage/app/tenants/{tenant_id}/evidences/`
- **Crittografia**: Applicata a livello applicazione (AES-256)

### Ambiente di Produzione
- **Provider**: Configurabile via `STORAGE_PROVIDER` (default: `minio`)
- **Driver**: `s3` per tutti i provider S3-compatible, `local` per storage locale
- **Path**: `tenants/{tenant_id}/evidences/`
- **Crittografia**: Applicata a livello applicazione (AES-256) + opzionale server-side per provider cloud

## MinIO - Self-Hosted S3-Compatible Storage

### Perché MinIO?

1. **S3-Compatible API**: Mantiene compatibilità con API S3 standard
2. **Self-Hosted**: Nessuna dipendenza da provider cloud
3. **Scalabile**: Supporta distribuzione multi-node
4. **Crittografia**: Supporta server-side encryption
5. **Performance**: Alta performance per file storage
6. **Open Source**: Nessun vendor lock-in

### Configurazione MinIO

```php
// config/filesystems.php
'minio' => [
    'driver' => 's3',
    'key' => env('MINIO_ACCESS_KEY'),
    'secret' => env('MINIO_SECRET_KEY'),
    'region' => env('MINIO_REGION', 'us-east-1'),
    'bucket' => env('MINIO_BUCKET', 'auditready'),
    'endpoint' => env('MINIO_ENDPOINT', 'http://minio:9000'),
    'use_path_style_endpoint' => true,
    'throw' => false,
],
```

### Setup MinIO

```bash
# Docker Compose esempio
services:
  minio:
    image: minio/minio:latest
    command: server /data --console-address ":9001"
    environment:
      MINIO_ROOT_USER: admin
      MINIO_ROOT_PASSWORD: password
    volumes:
      - minio_data:/data
    ports:
      - "9000:9000"  # API
      - "9001:9001"  # Console
```

## Crittografia File

### Processo di Cifratura

1. **Upload File**:
   ```
   File Plaintext
        ↓
   Genera chiave AES-256 random
        ↓
   Cifra file con chiave
        ↓
   Cifra chiave con app key Laravel
        ↓
   Store file cifrato + chiave cifrata in DB
   ```

2. **Download File**:
   ```
   Recupera file cifrato + chiave cifrata
        ↓
   Decifra chiave con app key Laravel
        ↓
   Decifra file con chiave
        ↓
   Stream file plaintext all'utente
   ```

### Implementazione

```php
// Esempio servizio Evidence
class EvidenceService
{
    public function store(UploadedFile $file, Tenant $tenant): Evidence
    {
        // 1. Genera chiave per file
        $fileKey = random_bytes(32); // AES-256
        
        // 2. Cifra file
        $encryptedContent = openssl_encrypt(
            file_get_contents($file->getRealPath()),
            'AES-256-CBC',
            $fileKey,
            OPENSSL_RAW_DATA,
            $iv = random_bytes(16)
        );
        
        // 3. Cifra chiave con app key
        $encryptedKey = encrypt($fileKey);
        
        // 4. Store file cifrato
        $path = "tenants/{$tenant->id}/evidences/{$uuid}_v{$version}";
        Storage::disk('minio')->put($path, $encryptedContent);
        
        // 5. Store metadata in DB
        return Evidence::create([
            'stored_path' => $path,
            'encrypted_key' => $encryptedKey,
            'iv' => base64_encode($iv),
            // ...
        ]);
    }
}
```

## Isolamento per Tenant

### Directory Structure

```
storage/
├── tenants/
│   ├── {tenant-uuid-1}/
│   │   ├── evidences/
│   │   │   ├── {evidence-uuid}_v1
│   │   │   ├── {evidence-uuid}_v2
│   │   │   └── ...
│   │   └── exports/
│   │       └── {audit-id}_{timestamp}.pdf
│   ├── {tenant-uuid-2}/
│   │   └── ...
│   └── ...
└── backups/
    ├── {tenant-uuid-1}/
    │   └── {backup-timestamp}.sql.enc
    └── ...
```

### Access Control

- Ogni tenant può accedere solo alla propria directory
- Middleware verifica tenant context prima di ogni operazione storage
- Nessun accesso cross-tenant possibile

## Backup Storage

### Strategia Backup

1. **Database Backups**: 
   - Dump cifrato per ogni tenant database
   - Storage in `storage/backups/{tenant-id}/`

2. **File Backups**:
   - Backup incrementale dei file tenant
   - Retention configurabile (default: 30 giorni)

3. **Backup Location**:
   - Storage locale separato
   - O MinIO bucket dedicato `backups`

### Automazione Backup

```bash
# Backup automatico via scheduler Laravel
php artisan backup:run --tenant={uuid}

# Restore
php artisan backup:restore --tenant={uuid} --backup={backup-id}
```

## Performance e Scalabilità

### Ottimizzazioni

1. **Streaming**: File grandi vengono streamati, non caricati in memoria
2. **Caching**: Metadata in cache Redis (non file stessi)
3. **CDN**: Opzionale per file pubblici (se necessario in futuro)
4. **Compressione**: File compressi prima della cifratura (opzionale)

### Scalabilità

- **MinIO**: Supporta distribuzione multi-node
- **Load Balancing**: Possibile con più istanze MinIO
- **Storage Expansion**: Aggiunta dischi/volumi senza downtime

## Monitoring

### Metriche da Monitorare

- Spazio disco utilizzato per tenant
- Numero file per tenant
- Performance upload/download
- Errori storage
- Backup success rate

### Alerting

- Alert se spazio disco > 80%
- Alert se backup fallisce
- Alert se storage non raggiungibile

## Migrazione Futura (se necessario)

Se in futuro si volesse migrare a cloud provider:

1. **MinIO è S3-compatible**: Cambio endpoint e credenziali
2. **Nessun cambio codice**: API identiche
3. **Migrazione dati**: Script per copiare file da MinIO a S3

## Conclusione

La strategia storage self-hosted con MinIO offre:
- ✅ **Controllo completo** sui dati
- ✅ **Privacy** massima
- ✅ **Compliance** con data residency
- ✅ **Flessibilità** per migrazione futura (S3-compatible)
- ✅ **Costi** prevedibili
