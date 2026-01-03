# Piano di Miglioramento: Visualizzazione Relazioni Audit-Controls-Evidences

## Obiettivo
Migliorare la visualizzazione delle relazioni tra Audit, Controls ed Evidences nella piattaforma AuditReady. Attualmente le relazioni sono mostrate principalmente tramite colonne nelle tabelle Filament, ma manca una visualizzazione grafica o interattiva delle connessioni.

## Requisiti
- Visualizzazione grafica/interattiva delle relazioni
- Vista rapida di quali controls hanno evidenze
- Migliorare ViewAudit per mostrare controls ed evidenze
- Navigazione intuitiva tra entità correlate

## Stack Tecnologico
- PHP 8.2+, Laravel 12.0
- Filament 4.x, Livewire 3.x
- Tailwind CSS 4.0, Vite 7.0
- Cytoscape.js per visualizzazione grafica
- PostgreSQL (production), SQLite (development)

## Step di Implementazione

### Step 1: Definire Struttura JSON e Metodo getGraphData()
**Azione**: Definire una struttura JSON standardizzata per il grafo (nodi e archi) e implementare un metodo `getGraphData()` in `app/Services/AuditService.php` che restituisce questa struttura per un Audit dato.

**Dettagli**:
- Aggiungere metodo `public function getGraphData(Audit $audit): array`
- Caricare relazioni con eager loading: `Audit::with(['evidences', 'relatedControls'])`
- Costruire array `nodes`:
  - Audit node: `{ id: "audit_{$audit->id}", label: "Audit #{$audit->id}", type: "audit" }`
  - Per ogni Control: `{ id: "control_{$control->id}", label: $control->name, type: "control" }`
  - Per ogni Evidence: `{ id: "evidence_{$evidence->id}", label: $evidence->title, type: "evidence" }`
- Costruire array `edges`:
  - Audit → Evidence: `{ from: "audit_{$audit->id}", to: "evidence_{$evidence->id}", label: "has" }`
  - Audit → Control: `{ from: "audit_{$audit->id}", to: "control_{$control->id}", label: "covers" }`
  - Evidence → Control (se `control_reference` corrisponde): `{ from: "evidence_{$evidence->id}", to: "control_{$control->id}", label: "references" }`
- Gestire limiti: se nodi totali > 500, troncare e aggiungere nodo "truncated"

**Testing**: Unit test per struttura corretta, gestione riferimenti mancanti, logica di troncamento.

---

### Step 2: Migliorare Relazione Audit-Control
**Azione**: Migliorare il modello `Audit` per esporre una relazione Eloquent appropriata per i controls invece del metodo logico attuale, creando un `belongsToMany` attraverso una tabella pivot `audit_control`.

**Dettagli**:
- Creare migration `create_audit_control_table` con colonne: `audit_id`, `control_id`, timestamps, chiave primaria composita
- In `Audit` model: `public function controls(): BelongsToMany`
- In `Control` model: relazione inversa `public function audits(): BelongsToMany`
- Aggiornare `relatedControls()` se necessario per compatibilità
- Eseguire migration rispettando tenant schemas

**Testing**: Feature test per attach/detach controls, verifica che `getGraphData` includa i nodi control.

---

### Step 3: Creare Componente Livewire AuditGraph
**Azione**: Creare un nuovo componente Livewire `AuditGraph` (`app/Livewire/AuditGraph.php`) che riceve un Audit ID, chiama `AuditService::getGraphData`, e renderizza una vista Blade con un container Cytoscape.

**Dettagli**:
- Generare componente: `php artisan make:livewire AuditGraph`
- Proprietà: `public int $auditId; public array $graphData = [];`
- In `mount($auditId)`, risolvere audit e assegnare `$this->graphData`
- Vista Blade con container Cytoscape e Alpine.js
- Listener per click sui nodi che naviga alle risorse Filament
- Verifica RBAC in `mount`

**Testing**: Livewire test per struttura dati, browser test per rendering e navigazione, test permessi.

---

### Step 4: Aggiungere Cytoscape.js
**Azione**: Aggiungere Cytoscape.js come dipendenza front-end via NPM e configurare Vite per bundlarlo.

**Dettagli**:
- Eseguire `npm install cytoscape@3.24.0`
- Configurare import in `resources/js/graph.js` o direttamente nella vista
- Verificare compatibilità con Vite 7
- Aggiungere fallback se `window.cytoscape` è undefined

**Testing**: Build production, verifica rendering senza errori console.

---

### Step 5: Integrare in ViewAudit
**Azione**: Integrare il componente `AuditGraph` Livewire nella pagina Filament `ViewAudit`.

**Dettagli**:
- Creare Filament widget `AuditGraphWidget`
- Aggiungere widget a `ViewAudit` tramite `getHeaderWidgets()` o `cards`
- Verificare autorizzazione widget
- Pulire cache Filament

**Testing**: Feature test per presenza grafo, test visual regression, test utente non autorizzato.

---

### Step 6: Implementare Caching
**Azione**: Implementare caching per i dati del grafo per migliorare le performance su audit frequentemente accessibili.

**Dettagli**:
- In `AuditService::getGraphData`, wrappare con `Cache::remember`
- Usare cache taggabile per invalidazione automatica
- Creare/aggiornare Observer per Evidence e Control per invalidare cache
- Documentare strategia caching

**Testing**: Test che verifica refresh cache dopo creazione Evidence, test performance.

---

### Step 7: Documentazione
**Azione**: Scrivere documentazione completa e aggiornare PHPDoc.

**Dettagli**:
- PHPDoc per `getGraphData` e componenti Livewire
- Creare/aggiornare `docs/visualization.md` con:
  - Overview feature grafo
  - Modello dati (nodes, edges, types)
  - Guida installazione
  - Guida utilizzo per sviluppatori
  - Considerazioni sicurezza
  - Troubleshooting
- Aggiornare README con dipendenze npm
- Changelog entry

**Testing**: Verifica compilazione documentazione, review manuale.

---

### Step 8: Testing End-to-End e Deploy
**Azione**: Eseguire test end-to-end su ambiente multi-tenant e deploy su staging.

**Dettagli**:
- Creare due tenant con audit, evidences e controls
- Verificare isolamento tenant, performance, RBAC
- Misurare tempi di caricamento (< 2 secondi)
- Eseguire test suite completa
- Deploy su staging con build assets e migrazioni
- Monitorare log per collisioni cache key

**Testing**: Smoke test automatizzato, load testing con 50 utenti concorrenti per tenant.

---

## Note Importanti
- Ogni step deve rispettare multi-tenancy e RBAC
- Performance: eager loading, caching, limiti su grandi dataset
- Sicurezza: tenant isolation, autorizzazioni, validazione input
- Testing: unit, feature, browser, performance

## Prossimi Passi
1. Confermare approvazione del piano
2. Procedere con Step 1 dopo conferma utente
3. Eseguire ogni step uno alla volta con testing appropriato
