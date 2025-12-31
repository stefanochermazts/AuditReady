# Piano di Sviluppo: Export ZIP con PDF e Evidenze

## Panoramica
Aggiungere formato ZIP all'export audit che contenga:
1. Il PDF dell'audit con link cliccabili alle evidenze
2. Tutte le evidenze (file originali decriptati)

I link nel PDF devono essere dinamici:
- **PDF scaricato direttamente**: link puntano a URL online (tenant, autenticati con signed URLs)
- **PDF dentro il ZIP**: link puntano a file locali nel ZIP stesso

## Requisiti Tecnici

### Stack
- Laravel 12, PHP 8.3
- Filament 4.4.0
- DomPDF (barryvdh/laravel-dompdf)
- stancl/tenancy (multi-tenant)
- PostgreSQL
- Queue (database)
- Storage provider-agnostic (S3-compatible)
- EncryptionService (AES-256)
- PHP ZipArchive (nativo)

### Principi di Sicurezza
- Security by Design
- Privacy by Design
- Defense in Depth
- Least Privilege
- Tutti i file cifrati a riposo
- Evidenze decriptate solo al momento del download
- Link evidenze con signed URLs con scadenza
- ZIP generato in modo sicuro senza esporre file temporanei

## Step 1: Creare Route Autenticate per Download Evidenze

**Azione**: Creare route protetta per download evidenze con autenticazione e autorizzazione.

**Dettagli**:
- Aggiungere route in `routes/tenant.php` per download evidenze
- Route deve richiedere autenticazione (`auth:sanctum` o `auth:web`)
- Verificare autorizzazione tramite `EvidencePolicy`
- Generare signed URL con scadenza (24 ore)
- Usare `EvidenceService::download()` per decriptare e servire file

**File da modificare**:
- `routes/tenant.php` - aggiungere route `evidence.download`
- `app/Policies/EvidencePolicy.php` - verificare permessi download

**Testing**:
- Test accesso non autenticato (deve fallire)
- Test accesso con ruolo non autorizzato (deve fallire)
- Test download con signed URL valido
- Test scadenza signed URL

---

## Step 2: Aggiungere Link Evidenze nel Template PDF

**Azione**: Modificare template PDF per includere link cliccabili alle evidenze.

**Dettagli**:
- Modificare `resources/views/exports/audit-pdf.blade.php`
- Aggiungere parametro `$evidenceLinks` al template
- Generare link HTML `<a href="...">` per ogni evidenza
- Link devono essere assoluti (per PDF standalone) o relativi (per PDF in ZIP)
- Usare parametro `$linkMode` per distinguere tra 'online' e 'local'

**File da modificare**:
- `resources/views/exports/audit-pdf.blade.php` - aggiungere link evidenze

**Testing**:
- Verificare che link siano cliccabili nel PDF
- Verificare formato link (assoluti vs relativi)

---

## Step 3: Estendere ExportService per Supportare ZIP

**Azione**: Aggiungere metodo `exportToZip()` in `ExportService`.

**Dettagli**:
- Creare metodo `exportToZip(Audit $audit): string`
- Generare PDF con link locali (parametro `linkMode = 'local'`)
- Decriptare tutte le evidenze usando `EvidenceService`
- Creare ZIP usando `ZipArchive`:
  - Struttura: `audit_{id}_{timestamp}/`
    - `audit_{id}_{timestamp}.pdf`
    - `evidences/`
      - `evidence_{id}_v{version}_{filename}`
- Cifrare ZIP usando `encryptContent()`
- Salvare ZIP in storage
- Restituire path del file

**File da modificare**:
- `app/Services/ExportService.php` - aggiungere `exportToZip()`

**Testing**:
- Test generazione ZIP con evidenze multiple
- Test struttura ZIP
- Test link nel PDF dentro ZIP
- Test decriptazione evidenze

---

## Step 4: Aggiornare ExportAuditJob per Supportare ZIP

**Azione**: Estendere `ExportAuditJob` per gestire formato ZIP.

**Dettagli**:
- Modificare `match` statement per includere `'zip'`
- Chiamare `exportService->exportToZip($audit)` per formato ZIP
- Mantenere compatibilità con PDF e CSV

**File da modificare**:
- `app/Jobs/ExportAuditJob.php` - aggiungere case 'zip'

**Testing**:
- Test dispatch job con formato ZIP
- Test completamento job
- Test notifica email

---

## Step 5: Aggiornare ExportController e AuditResource per ZIP

**Azione**: Aggiungere opzione ZIP nel form export e gestire richieste.

**Dettagli**:
- Modificare `AuditResource` per includere 'zip' nelle opzioni formato
- Verificare che `ExportController::requestExport()` gestisca formato ZIP
- Aggiornare validazione per accettare 'zip'

**File da modificare**:
- `app/Filament/Resources/AuditResource.php` - aggiungere 'zip' alle opzioni
- `app/Http/Controllers/ExportController.php` - verificare validazione

**Testing**:
- Test selezione formato ZIP nel form
- Test dispatch job con formato ZIP
- Test validazione formato

---

## Step 6: Generare Signed URLs per Evidenze Online

**Azione**: Creare metodo per generare signed URLs per evidenze nel PDF standalone.

**Dettagli**:
- Aggiungere metodo `generateEvidenceDownloadUrl(Evidence $evidence): string` in `ExportService`
- Usare `url()->temporarySignedRoute()` con scadenza 24 ore
- Route deve essere `evidence.download` con parametro `evidence_id`
- Passare signed URLs al template PDF quando `linkMode = 'online'`

**File da modificare**:
- `app/Services/ExportService.php` - aggiungere `generateEvidenceDownloadUrl()`
- `app/Services/ExportService.php` - modificare `exportToPdf()` per generare link

**Testing**:
- Test generazione signed URL
- Test scadenza signed URL
- Test accesso con signed URL valido

---

## Step 7: Gestire Link Locali nel PDF per ZIP

**Azione**: Generare PDF con link relativi quando incluso nel ZIP.

**Dettagli**:
- Modificare `exportToPdf()` per accettare parametro `$linkMode = 'online'`
- Quando `$linkMode = 'local'`, generare link relativi: `evidences/evidence_{id}_v{version}_{filename}`
- Quando `$linkMode = 'online'`, generare signed URLs assoluti
- In `exportToZip()`, chiamare `exportToPdf()` con `$linkMode = 'local'`

**File da modificare**:
- `app/Services/ExportService.php` - modificare `exportToPdf()` per supportare link mode
- `resources/views/exports/audit-pdf.blade.php` - gestire link mode

**Testing**:
- Test PDF standalone con link online
- Test PDF in ZIP con link locali
- Test clic link nel PDF

---

## Step 8: Testing Completo e Documentazione

**Azione**: Test end-to-end e documentazione.

**Dettagli**:
- Test completo flusso export ZIP
- Test export PDF standalone con link
- Test download evidenze con autenticazione
- Verificare sicurezza (accesso non autorizzato, scadenza link)
- Documentare feature in README

**File da creare/modificare**:
- Test feature per export ZIP
- Test feature per download evidenze
- `README.md` - documentare export ZIP

**Testing**:
- Test end-to-end export ZIP
- Test sicurezza accesso evidenze
- Test performance con molte evidenze

---

## Note Importanti

- Il ZIP deve essere cifrato come gli altri export
- Le evidenze nel ZIP devono essere decriptate ma il ZIP stesso è cifrato
- I link nel PDF devono funzionare sia online che locali
- Le evidenze online devono essere protette da autenticazione
- Il formato ZIP deve essere aggiunto alle opzioni nel form Filament
- Mantenere compatibilità con export PDF e CSV esistenti
