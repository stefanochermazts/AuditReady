# Piano di Sviluppo: Moduli Aggiuntivi AuditReady

## Panoramica
Questo documento descrive il piano di sviluppo per 5 moduli aggiuntivi che completano l'ecosistema AuditReady, progettati per risolvere problemi reali degli audit & compliance manager durante audit DORA/NIS2.

## Contesto Business (Audit & Compliance Manager Perspective)

### Problemi Reali Identificati

1. **Accountability Gap**: Durante audit, non è chiaro chi è responsabile di quale controllo → ritardi, evidenze mancanti
2. **Self-Assessment Necessity**: Le aziende vogliono capire rapidamente il loro stato senza assessment costosi → bisogno di snapshot rapidi
3. **Supply Chain Complexity**: DORA/NIS2 richiedono evidenze dai fornitori → gestione manuale complessa, nessun tracciamento
4. **Audit Day Chaos**: Il giorno dell'audit, file sparsi, nessuna vista organizzata → stress, inefficienza
5. **Policy-Control Gap**: Difficile dimostrare che le policy coprono i controlli richiesti → rischio di non conformità

### Principi di Design

- **NON valutazione**: Nessun scoring, rating, o dichiarazione di conformità
- **NON consulenza**: Solo strumenti operativi, nessuna interpretazione
- **Supporto decisionale**: Forniscono informazioni, non giudizi
- **Legale-safe**: Riduzione rischio legale mantenendo utilità operativa
- **Integrazione naturale**: Si integrano con Evidence Collector esistente

---

## Modulo 1: DORA / NIS2 Control Ownership Mapper

### Priorità: ALTA (Foundation Module)
### Complessità: MEDIA
### Valore Business: MOLTO ALTO

### Problema Risolto
Durante audit e assessment, non è chiaro chi è responsabile di quale controllo. Le evidenze arrivano tardi perché nessuno sapeva di doverle fornire.

### Soluzione
Micro-tool che mappa **Controlli → Ruoli → Persone** senza gestire workflow o task. Serve solo a chiarire responsabilità.

### Requisiti Funzionali

1. **Gestione Controlli DORA/NIS2**:
   - Database controlli standard DORA (Art. 8-16) e NIS2 (Art. 21)
   - Struttura: `control_id`, `standard` (DORA/NIS2), `article_reference`, `title`, `description`, `category`
   - Import iniziale da framework standard (CSV/JSON)

2. **Mapping Ownership**:
   - Tabella pivot: `control_owners` (control_id, user_id, role_name, responsibility_level)
   - Responsabilità: `primary`, `secondary`, `consultant`
   - Multi-ownership supportato (un controllo può avere più owner)

3. **Visualizzazione Matrice**:
   - Tabella controlli × owner
   - Filtri per standard, categoria, owner
   - Vista "Controlli senza owner" per gap analysis

4. **Export**:
   - PDF: matrice formattata, exportabile
   - Excel: matrice editabile, con colonne controlli/owner/responsabilità
   - Allegabile all'audit come evidenza

### Modelli Database

```php
// controls table
- id
- standard (enum: DORA, NIS2, ISO27001, custom)
- article_reference (string) // es: "DORA Art. 8.1"
- title (string)
- description (text)
- category (string) // es: "Risk Management", "Incident Response"
- tenant_id (string) // per custom controls
- created_at, updated_at

// control_owners table (pivot)
- id
- control_id (foreign)
- user_id (foreign)
- role_name (string) // es: "CISO", "IT Manager"
- responsibility_level (enum: primary, secondary, consultant)
- notes (text, nullable)
- created_at, updated_at
```

### Integrazione con AuditReady

- **Link ad Audit**: Campo `control_ownership_matrix_id` in `audits` table (opzionale)
- **Export in Audit Export**: Include matrice ownership nel PDF/ZIP export
- **RBAC**: Solo Organization Owner e Audit Manager possono modificare ownership

### UI/UX

- **Filament Resource**: `ControlResource` per gestione controlli
- **Filament Resource**: `ControlOwnerResource` per mapping
- **Filament Page**: "Ownership Matrix" con vista tabella interattiva
- **Action in AuditResource**: "Assign Control Owners" → apre wizard mapping

### Testing

- Test import controlli standard
- Test mapping ownership
- Test export PDF/Excel
- Test integrazione con audit export

---

## Modulo 2: DORA / NIS2 Gap Snapshot

### Priorità: ALTA (Pre-Sales Tool)
### Complessità: MEDIA
### Valore Business: ALTO (legale-safe, pre-vendita)

### Problema Risolto
Le aziende vogliono sapere rapidamente "Siamo messi male o bene?" senza assessment costosi o Excel complessi.

### Soluzione
Checklist rapida (30-40 domande) con risposta **Sì / No / Parziale**. Output: "questi controlli non hanno evidenze", "qui rischi in audit". **NON assegna punteggi, NON dichiara conformità**.

### Requisiti Funzionali

1. **Questionario Controlli**:
   - Domande per ogni controllo DORA/NIS2
   - Risposta: `yes`, `no`, `partial`, `not_applicable`
   - Campo note opzionale per ogni risposta

2. **Gap Analysis**:
   - Identifica controlli senza evidenze collegate
   - Identifica controlli con risposta "no" o "partial"
   - Raggruppa per categoria per vista d'insieme

3. **Report Gap Snapshot**:
   - PDF con:
     - Summary: totale controlli, risposte yes/no/partial
     - Gap per categoria
     - Lista controlli senza evidenze
     - Lista rischi potenziali
   - **NON include**: scoring, rating, dichiarazione conformità

4. **Link ad Audit**:
   - Snapshot può essere collegato a un audit
   - Evidenze collegate al snapshot vengono incluse nell'audit

### Modelli Database

```php
// gap_snapshots table
- id
- audit_id (foreign, nullable) // opzionale: può essere standalone
- name (string) // es: "DORA Gap Snapshot Q1 2025"
- standard (enum: DORA, NIS2, both)
- completed_by (foreign user_id)
- completed_at (timestamp)
- created_at, updated_at

// gap_snapshot_responses table
- id
- gap_snapshot_id (foreign)
- control_id (foreign)
- response (enum: yes, no, partial, not_applicable)
- notes (text, nullable)
- evidence_ids (json, nullable) // array di evidence IDs collegati
- created_at, updated_at
```

### Integrazione con AuditReady

- **Link Evidence**: Evidenze possono essere collegate a risposte snapshot
- **Export**: Snapshot incluso in audit export come sezione separata
- **RBAC**: Tutti i ruoli possono creare snapshot, solo Owner/Manager possono modificarli

### UI/UX

- **Filament Page**: "Gap Snapshot Wizard" con wizard step-by-step
- **Filament Resource**: `GapSnapshotResource` per gestione snapshot
- **Vista Progress**: Barra progresso durante compilazione
- **Report Viewer**: Vista PDF integrata in Filament

### Testing

- Test creazione snapshot
- Test compilazione questionario
- Test gap analysis
- Test export report
- Test integrazione evidenze

---

## Modulo 3: Third-Party Evidence Requestor

### Priorità: MOLTO ALTA (Differenziante)
### Complessità: MEDIA-ALTA
### Valore Business: MOLTO ALTO (vendibile standalone)

### Problema Risolto
Per DORA/NIS2, devi chiedere evidenze ai fornitori. Mail infinite, file sbagliati, nessun tracciamento. Supply chain = punto più debole.

### Soluzione
Sistema per generare richieste evidenze a fornitori esterni con link upload pubblico temporaneo (no login). Tutto finisce automaticamente nell'Evidence Collector.

### Requisiti Funzionali

1. **Creazione Richiesta**:
   - Seleziona controllo DORA/NIS2
   - Seleziona fornitore (o crea nuovo)
   - Genera link pubblico temporaneo con scadenza configurabile
   - Email automatica al fornitore con link

2. **Upload Pubblico**:
   - Form pubblico senza autenticazione
   - Upload multipli file
   - Validazione file (tipo, dimensione)
   - Timestamp automatico
   - Log accessi

3. **Tracciamento**:
   - Dashboard richieste: pending, completed, expired
   - Notifiche quando fornitore carica file
   - Audit trail completo (chi, quando, cosa)

4. **Integrazione Evidence Collector**:
   - File caricati diventano automaticamente evidenze
   - Collegate al controllo richiesto
   - Collegate all'audit di riferimento

### Modelli Database

```php
// third_party_suppliers table
- id
- tenant_id (string)
- name (string)
- email (string)
- contact_person (string, nullable)
- notes (text, nullable)
- created_at, updated_at

// evidence_requests table
- id
- audit_id (foreign, nullable) // opzionale
- control_id (foreign)
- supplier_id (foreign)
- requested_by (foreign user_id)
- public_token (string, unique) // per link pubblico
- expires_at (timestamp)
- status (enum: pending, completed, expired, cancelled)
- requested_at (timestamp)
- completed_at (timestamp, nullable)
- created_at, updated_at

// evidence_request_logs table (audit trail)
- id
- evidence_request_id (foreign)
- action (enum: created, accessed, file_uploaded, expired)
- ip_address (string, nullable)
- user_agent (text, nullable)
- metadata (json, nullable) // file info, etc.
- created_at
```

### Integrazione con AuditReady

- **Auto-creazione Evidence**: File caricati diventano evidenze con `uploader_id = null` (sistema)
- **Link ad Audit**: Richieste possono essere collegate a audit
- **RBAC**: Solo Owner/Manager possono creare richieste

### UI/UX

- **Filament Resource**: `EvidenceRequestResource` per gestione richieste
- **Filament Resource**: `ThirdPartySupplierResource` per gestione fornitori
- **Public Route**: `/evidence-request/{token}` per upload pubblico
- **Public View**: Form semplice, mobile-friendly, multi-lingua support

### Testing

- Test creazione richiesta
- Test generazione link pubblico
- Test upload pubblico
- Test scadenza link
- Test auto-creazione evidenze
- Test audit trail

---

## Modulo 4: Audit Day Pack Generator

### Priorità: MEDIA (Premium Feature)
### Complessità: BASSA-MEDIA
### Valore Business: MEDIO-ALTO (one-shot ma utilissimo)

### Problema Risolto
Il giorno dell'audit: file sparsi, panic mode, nessuna vista chiara. Serve tutto organizzato in un unico pacchetto.

### Soluzione
Selezioni audit → genera pacchetto completo (ZIP/PDF) con: indice controlli, evidenze collegate, change log, tutto organizzato.

### Requisiti Funzionali

1. **Selezione Audit**:
   - Seleziona audit esistente
   - Opzioni: includi tutte evidenze o solo evidenze validate
   - Opzioni: includi audit trail completo o solo summary

2. **Generazione Pacchetto**:
   - **Indice Controlli**: Lista controlli DORA/NIS2 con evidenze collegate
   - **Evidenze**: Tutte le evidenze organizzate per controllo
   - **Change Log**: Audit trail formattato e leggibile
   - **Summary**: Overview audit (status, date, owner, etc.)

3. **Formato Output**:
   - **ZIP**: Struttura organizzata con cartelle per controllo
   - **PDF**: Documento unico con indice e riferimenti
   - Entrambi con timestamp e versione

### Modelli Database

```php
// audit_day_packs table
- id
- audit_id (foreign)
- generated_by (foreign user_id)
- format (enum: zip, pdf, both)
- include_all_evidences (boolean)
- include_full_audit_trail (boolean)
- file_path (string) // path al file generato
- generated_at (timestamp)
- created_at, updated_at
```

### Integrazione con AuditReady

- **Riutilizza ExportService**: Estende logica export esistente
- **Link Evidence**: Usa struttura evidenze esistente
- **Audit Trail**: Usa audit_logs esistente

### UI/UX

- **Filament Action**: "Generate Audit Day Pack" in AuditResource
- **Form Opzioni**: Checkbox per opzioni generazione
- **Download**: Link download diretto dopo generazione
- **Storico**: Lista pacchetti generati per audit

### Testing

- Test generazione ZIP
- Test generazione PDF
- Test struttura organizzata
- Test integrazione evidenze
- Test performance con molti file

---

## Modulo 5: Policy ↔ Control Linker

### Priorità: BASSA (Per utenti maturi)
### Complessità: BASSA
### Valore Business: MEDIO (valore alto per audit seri)

### Problema Risolto
Durante audit: "Mi mostri dove questa policy copre questo controllo?" → Silenzio. Difficile dimostrare coverage.

### Soluzione
Carica policy (documenti), collega manualmente ai controlli. Zero AI, zero rischio, mapping manuale semplice.

### Requisiti Funzionali

1. **Gestione Policy**:
   - Upload policy come evidenze speciali (tipo: "policy") OPPURE
   - Inserimento link alla intranet interna OPPURE
   - Entrambi (file + link)
   - Metadata: nome, versione, data approvazione, owner
   - Versioning supportato

2. **Mapping Manuale**:
   - Interfaccia drag-and-drop o checkbox
   - Policy → Controlli (many-to-many)
   - Campo "coverage_notes" per dettagli mapping

3. **Visualizzazione Coverage**:
   - Vista "Controlli senza policy" per gap
   - Vista "Policy senza controlli" per policy non utilizzate
   - Matrice Policy × Controlli

4. **Export**:
   - Report coverage: quali policy coprono quali controlli
   - Incluso in audit export

### Modelli Database

```php
// policies table
- id
- tenant_id (string)
- name (string)
- version (string)
- approval_date (date, nullable)
- owner_id (foreign user_id, nullable)
- evidence_id (foreign, nullable) // link all'evidenza (file policy) - opzionale
- internal_link (string, nullable) // link alla intranet interna - opzionale
- description (text, nullable)
- created_at, updated_at

// policy_control_mappings table (pivot)
- id
- policy_id (foreign)
- control_id (foreign)
- coverage_notes (text, nullable) // dettagli su come la policy copre il controllo
- mapped_by (foreign user_id)
- created_at, updated_at
```

### Integrazione con AuditReady

- **Riutilizza Evidence**: Policy sono evidenze speciali
- **Link Controlli**: Usa controlli da Modulo 1
- **Export**: Incluso in audit export come sezione coverage

### UI/UX

- **Filament Resource**: `PolicyResource` per gestione policy
- **Filament Page**: "Policy-Control Mapper" con vista matrice
- **Drag-and-Drop**: Interfaccia intuitiva per mapping
- **Coverage Report**: Vista report coverage

### Testing

- Test upload policy
- Test mapping controlli
- Test coverage analysis
- Test export report

---

## Strategia di Implementazione

### Ordine di Sviluppo Consigliato

1. **Modulo 1: Control Ownership Mapper** (Foundation)
   - Base per tutti gli altri moduli
   - Valore immediato per utenti
   - Complessità gestibile

2. **Modulo 3: Third-Party Evidence Requestor** (Differenziante)
   - Alto valore business
   - Vendibile standalone
   - Risolve problema critico supply chain

3. **Modulo 2: Gap Snapshot** (Pre-Sales)
   - Legale-safe
   - Utile per pre-vendita
   - Dipende da Modulo 1 (controlli)

4. **Modulo 4: Audit Day Pack Generator** (Premium)
   - One-shot ma utile
   - Riutilizza molto codice esistente
   - Può essere feature premium

5. **Modulo 5: Policy ↔ Control Linker** (Mature Users)
   - Per utenti più avanzati
   - Dipende da Modulo 1
   - Complessità bassa

### Considerazioni Tecniche

- **Multi-Tenant**: Tutti i moduli devono rispettare isolamento tenant
- **RBAC**: Integrazione con sistema ruoli esistente
- **Audit Trail**: Tutte le azioni devono essere tracciate
- **Export**: Integrazione con sistema export esistente
- **Security**: Link pubblici devono essere sicuri (signed URLs, scadenza)

### Database Schema

Tutti i moduli aggiungono tabelle al database tenant (non centrale), mantenendo isolamento completo.

### Testing Strategy

- Unit tests per ogni servizio
- Feature tests per ogni flusso utente
- Integration tests per integrazione moduli
- Security tests per link pubblici (Modulo 3)

---

## Note Importanti

- **NON valutazione**: Nessun modulo assegna punteggi o dichiara conformità
- **NON consulenza**: Solo strumenti operativi
- **Supporto decisionale**: Forniscono informazioni, non giudizi
- **Legale-safe**: Riduzione rischio legale mantenendo utilità
- **Integrazione naturale**: Si integrano con Evidence Collector esistente

---

## Step di Sviluppo Dettagliati

### Modulo 1: Control Ownership Mapper - Step di Implementazione

#### Step 1.1: Database Schema e Migrazioni
- Creare migration `create_controls_table` con struttura DORA/NIS2
- Creare migration `create_control_owners_table` (pivot)
- Aggiungere campo `control_ownership_matrix_id` a `audits` table (nullable)
- Seeder per import controlli standard DORA/NIS2 da CSV/JSON

#### Step 1.2: Modelli e Relazioni
- Creare modello `Control` con relazioni
- Creare modello `ControlOwner` (pivot)
- Aggiungere relazione `controls()` in `Audit` model
- Aggiungere relazione `owners()` in `Control` model

#### Step 1.3: ControlService
- Metodo `importStandardControls()` per import DORA/NIS2
- Metodo `assignOwner()` per mapping ownership
- Metodo `getOwnershipMatrix()` per generare matrice
- Metodo `getControlsWithoutOwner()` per gap analysis

#### Step 1.4: Filament Resources
- `ControlResource` per CRUD controlli
- `ControlOwnerResource` per gestione mapping
- Page `OwnershipMatrix` con vista tabella interattiva
- Action "Assign Control Owners" in `AuditResource`

#### Step 1.5: Export PDF/Excel
- Servizio export matrice ownership in PDF (DomPDF)
- Servizio export matrice ownership in Excel (PhpSpreadsheet)
- Template PDF con formato tabella professionale
- Integrazione export in `ExportService` esistente

#### Step 1.6: Testing
- Test import controlli standard
- Test mapping ownership
- Test export PDF/Excel
- Test integrazione audit export

---

### Modulo 2: Gap Snapshot - Step di Implementazione

#### Step 2.1: Database Schema e Migrazioni
- Creare migration `create_gap_snapshots_table`
- Creare migration `create_gap_snapshot_responses_table`
- Relazione con `controls` (Modulo 1)
- Relazione con `audits` (opzionale)

#### Step 2.2: Modelli e Relazioni
- Creare modello `GapSnapshot`
- Creare modello `GapSnapshotResponse`
- Relazioni con Control, Audit, User

#### Step 2.3: GapSnapshotService
- Metodo `createSnapshot()` per inizializzazione
- Metodo `addResponse()` per aggiungere risposta controllo
- Metodo `analyzeGaps()` per gap analysis
- Metodo `generateReport()` per PDF report

#### Step 2.4: Filament UI
- Page `GapSnapshotWizard` con wizard step-by-step
- Resource `GapSnapshotResource` per gestione snapshot
- Vista progress bar durante compilazione
- Report viewer PDF integrato

#### Step 2.5: Report Generation
- Template PDF gap snapshot report
- Sezioni: Summary, Gap per categoria, Controlli senza evidenze, Rischi
- **NON include**: scoring, rating, dichiarazione conformità

#### Step 2.6: Testing
- Test creazione snapshot
- Test compilazione questionario
- Test gap analysis
- Test export report

---

### Modulo 3: Third-Party Evidence Requestor - Step di Implementazione

#### Step 3.1: Database Schema e Migrazioni
- Creare migration `create_third_party_suppliers_table`
- Creare migration `create_evidence_requests_table`
- Creare migration `create_evidence_request_logs_table` (audit trail)
- Indici per `public_token` (unique) e `expires_at`

#### Step 3.2: Modelli e Relazioni
- Creare modello `ThirdPartySupplier`
- Creare modello `EvidenceRequest`
- Creare modello `EvidenceRequestLog`
- Relazioni con Control, Audit, User

#### Step 3.3: EvidenceRequestService
- Metodo `createRequest()` per creare richiesta e generare token
- Metodo `generatePublicUrl()` per signed URL pubblico
- Metodo `handlePublicUpload()` per gestire upload pubblico
- Metodo `autoCreateEvidence()` per creare evidenze da file caricati
- Metodo `expireRequests()` per scadenza automatica

#### Step 3.4: Public Controller e Routes
- Controller `PublicEvidenceRequestController` per upload pubblico
- Route pubblica `/evidence-request/{token}` (no auth)
- Validazione file (tipo, dimensione, virus scan opzionale)
- Logging accessi e upload

#### Step 3.5: Filament Resources
- `ThirdPartySupplierResource` per gestione fornitori
- `EvidenceRequestResource` per gestione richieste
- Dashboard con status: pending, completed, expired
- Notifiche quando fornitore carica file

#### Step 3.6: Email e Notifiche
- Template email richiesta evidenze al fornitore
- Notifica quando richiesta completata
- Notifica quando richiesta in scadenza

#### Step 3.7: Security
- Signed URLs con scadenza configurabile
- Rate limiting su upload pubblico
- Validazione file rigorosa
- Audit trail completo

#### Step 3.8: Testing
- Test creazione richiesta
- Test generazione link pubblico
- Test upload pubblico
- Test scadenza link
- Test auto-creazione evidenze
- Test security (tentativi accesso non autorizzato)

---

### Modulo 4: Audit Day Pack Generator - Step di Implementazione

#### Step 4.1: Database Schema e Migrazioni
- Creare migration `create_audit_day_packs_table`
- Relazione con `audits` table

#### Step 4.2: Modelli e Relazioni
- Creare modello `AuditDayPack`
- Relazione con Audit, User

#### Step 4.3: AuditDayPackService
- Metodo `generatePack()` per generazione pacchetto
- Riutilizza `ExportService` esistente
- Organizza evidenze per controllo
- Genera indice controlli
- Formatta audit trail

#### Step 4.4: Filament Integration
- Action "Generate Audit Day Pack" in `AuditResource`
- Form opzioni: include_all_evidences, include_full_audit_trail
- Download link dopo generazione
- Storico pacchetti generati

#### Step 4.5: Export Formats
- ZIP: struttura organizzata con cartelle per controllo
- PDF: documento unico con indice e riferimenti
- Entrambi con timestamp e versione

#### Step 4.6: Testing
- Test generazione ZIP
- Test generazione PDF
- Test struttura organizzata
- Test performance con molti file

---

### Modulo 5: Policy ↔ Control Linker - Step di Implementazione

#### Step 5.1: Database Schema e Migrazioni
- Creare migration `create_policies_table`
- Creare migration `create_policy_control_mappings_table` (pivot)
- Relazione con `controls` (Modulo 1)
- Relazione con `evidences` (policy come evidenze speciali)

#### Step 5.2: Modelli e Relazioni
- Creare modello `Policy`
- Creare modello `PolicyControlMapping` (pivot)
- Relazioni con Control, Evidence, User

#### Step 5.3: PolicyService
- Metodo `createPolicy()` per creare policy da evidenza
- Metodo `mapToControl()` per mapping manuale
- Metodo `getCoverageGaps()` per identificare gap
- Metodo `generateCoverageReport()` per report

#### Step 5.4: Filament UI
- `PolicyResource` per gestione policy
- Page "Policy-Control Mapper" con vista matrice
- Interfaccia drag-and-drop o checkbox per mapping
- Vista "Controlli senza policy" e "Policy senza controlli"

#### Step 5.5: Export e Report
- Report coverage: quali policy coprono quali controlli
- Incluso in audit export come sezione separata
- Template PDF coverage report

#### Step 5.6: Testing
- Test upload policy
- Test mapping controlli
- Test coverage analysis
- Test export report

---

## Dipendenze tra Moduli

```
Modulo 1 (Control Ownership Mapper)
  ↓
  ├─→ Modulo 2 (Gap Snapshot) - dipende da controlli
  ├─→ Modulo 3 (Third-Party Requestor) - dipende da controlli
  └─→ Modulo 5 (Policy Linker) - dipende da controlli

Modulo 4 (Audit Day Pack) - indipendente, riutilizza export esistente
```

---

## Considerazioni di Sicurezza

### Link Pubblici (Modulo 3)
- Signed URLs con HMAC-SHA256
- Scadenza configurabile (default 7 giorni)
- Rate limiting: max 10 upload per IP/ora
- Validazione file: whitelist MIME types, max size 100MB
- Virus scanning opzionale (ClamAV integration)

### Multi-Tenant Isolation
- Tutte le tabelle hanno isolamento tenant implicito (database separato)
- Link pubblici includono tenant context nel token
- Verifica tenant context su ogni richiesta pubblica

### RBAC per Moduli
- **Modulo 1**: Solo Owner/Manager possono modificare ownership
- **Modulo 2**: Tutti possono creare snapshot, solo Owner/Manager possono modificarli
- **Modulo 3**: Solo Owner/Manager possono creare richieste
- **Modulo 4**: Solo Owner/Manager possono generare pack
- **Modulo 5**: Solo Owner/Manager possono mappare policy

---

## Roadmap Temporale Stimata

| Modulo | Complessità | Tempo Stimato | Priorità |
|--------|-------------|---------------|----------|
| Modulo 1: Ownership Mapper | Media | 2-3 settimane | ALTA |
| Modulo 3: Third-Party Requestor | Media-Alta | 3-4 settimane | MOLTO ALTA |
| Modulo 2: Gap Snapshot | Media | 2-3 settimane | ALTA |
| Modulo 4: Audit Day Pack | Bassa-Media | 1-2 settimane | MEDIA |
| Modulo 5: Policy Linker | Bassa | 1-2 settimane | BASSA |

**Totale stimato**: 9-14 settimane per tutti i moduli

---

## Metriche di Successo

### Modulo 1: Ownership Mapper
- ✅ 100% controlli DORA/NIS2 mappati
- ✅ Export PDF/Excel funzionante
- ✅ Riduzione tempo identificazione responsabili del 80%

### Modulo 2: Gap Snapshot
- ✅ Checklist 30-40 domande completabile in <30 minuti
- ✅ Report generato in <5 secondi
- ✅ Zero dichiarazioni di conformità (legale-safe)

### Modulo 3: Third-Party Requestor
- ✅ Link pubblico funzionante senza login
- ✅ Upload automatico → Evidence Collector
- ✅ Tracciamento completo (audit trail)
- ✅ Riduzione tempo gestione fornitori del 70%

### Modulo 4: Audit Day Pack
- ✅ Pacchetto generato in <2 minuti
- ✅ Struttura organizzata e navigabile
- ✅ Tutte evidenze incluse correttamente

### Modulo 5: Policy Linker
- ✅ Mapping manuale intuitivo
- ✅ Coverage report chiaro
- ✅ Gap identification automatica

---

## Note Finali

Questi moduli completano l'ecosistema AuditReady trasformandolo da semplice Evidence Collector a piattaforma completa di supporto audit. Mantengono i principi fondamentali: **NON valutazione, NON consulenza, solo supporto operativo**.

Ogni modulo può essere sviluppato e rilasciato indipendentemente, permettendo un rollout incrementale e feedback continuo dagli utenti.
