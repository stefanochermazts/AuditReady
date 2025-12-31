# Piano di Sviluppo: Enterprise Audit UI Design System

## Panoramica
Implementare un design system "Enterprise Audit" per Filament Admin Panel v4.4.0 che crei un'interfaccia enterprise, affidabile, densa ma leggibile, con colori semantici per gli status e completamente compatibile con gli aggiornamenti Filament.

## Obiettivi
- Design enterprise e affidabile (tool per audit/compliance)
- Massimizzare densità informativa senza compromettere leggibilità
- Colori semantici per status (Missing / In Review / Completed)
- Compatibilità completa con aggiornamenti Filament (no modifiche vendor/)
- Accessibilità base (contrasto, focus states, usabilità tastiera)

## Vincoli
- NON modificare nulla in vendor/
- Preferire API Filament supportate
- Evitare CSS pesante; usare utility Tailwind
- Nessun gradiente elaborato, nessun aspetto "marketing SaaS"
- Modalità chiara di default (dark mode fuori scope)

---

## Step 1: Configurare Tailwind con Token Design System

**Azione**: Estendere la configurazione di Tailwind (tailwind.config.js) per includere i token di design del sistema "Enterprise Audit".

**Ragionamento**: Fornire un set centrale di colori semantici, spaziature e tonalità neutre che saranno usati da tutti gli override e componenti personalizzati, garantendo coerenza e facilità di manutenzione senza modificare vendor/.

**Dettagli Implementazione**:
- Aprire `tailwind.config.js` nella root del progetto
- Nella chiave `theme.extend` aggiungere:
  - `colors.audit` con chiavi `missing`, `inReview`, `completed`, `neutralBg`, `border`, `text`
  - `spacing` personalizzato per densità tabelle (es. `audit-1`, `audit-2`)
  - `borderRadius` leggero (es. `audit` = `0.25rem`)
  - `opacity` se necessario per focus
- Abilitare il layer `components` e `utilities` per aggiungere piccole classi custom
- Eseguire `npm run build` (Vite) per generare il CSS aggiornato

**Error Handling**:
- Se Vite fallisce, controllare che la sintassi JSON di Tailwind sia valida
- Verificare che le nuove classi non sovrascrivano accidentalmente classi Filament esistenti; usare prefissi `audit-` quando possibile

**Testing**:
- Avviare l'app in ambiente locale e verificare la presenza dei colori via DevTools (`bg-audit-missing`, `text-audit-completed`)
- Scrivere un test in `tests/Feature/TailwindConfigTest.php` che controlli che il file contenga la chiave `audit` usando `assertStringContainsString`

---

## Step 2: Creare CSS Design System Dedicato

**Azione**: Creare un piccolo foglio CSS dedicato al Design System (resources/css/audit.css) con le regole di utilità condivise.

**Ragionamento**: Concentrando le regole specifiche (es. `.audit-table`, `.audit-badge`) in un file separato si evitano override pesanti nei componenti Blade e si mantiene il CSS minimale, conforme alle regole di codice.

**Dettagli Implementazione**:
- Creare il file `resources/css/audit.css`
- Definire:
  - `.audit-table` con `@apply text-sm leading-5 border border-audit-border bg-audit-neutralBg`; ridurre paddings (`p-2`) per densità
  - `.audit-form-label` con `@apply block text-sm font-medium text-gray-700`; garantire always-visible label
  - `.audit-badge` con `@apply inline-flex items-center px-2 py-0.5 rounded-audit text-xs font-medium`; aggiungere `focus-visible:ring-2 ring-audit-missing` per focus
  - Focus ring globale: `:focus-visible { @apply outline-none ring-2 ring-offset-2 ring-audit-border }`
- Importare il file in `resources/css/app.css` usando `@import "./audit.css";`

**Error Handling**:
- Verificare che il file venga compilato da Vite (errori di sintassi SCSS/Tailwind)
- Se le classi non sono generate, controllare che `purge` includa il percorso `resources/css/**/*.css`

**Testing**:
- Controllare in browser che le tabelle abbiano la classe `audit-table` e rispettino il layout
- Agganciare un test di snapshot (Laravel Dusk o Pest) per verificare il markup finale della tabella

---

## Step 3: Registrare Tema Filament

**Azione**: Registrare il CSS personalizzato come tema Filament nel `App\Providers\Filament\AdminPanelProvider`.

**Ragionamento**: Filament permette di aggiungere temi tramite il metodo `->theme()` o `Filament::registerTheme()`. Includendo il nostro CSS, tutti i componenti del pannello utilizzeranno i token di design senza dover modificare i file vendor.

**Dettagli Implementazione**:
- Aprire `app/Providers/Filament/AdminPanelProvider.php`
- Nel metodo `panel(Panel $panel): Panel` aggiungere:
  - `->theme('resources/css/audit.css')`
  - `->primaryColor('audit')` solo se Filament supporta un colore custom; altrimenti impostare `->primaryColor('gray')` e gestire i colori semanticamente nei componenti
  - Opzionale: `->sidebarWidth('250px')` per layout più denso

**Error Handling**:
- Se Filament lancia un errore "Theme not found", verificare il percorso relativo del file CSS e che il file sia pubblicato (vite serve)
- Controllare che il provider sia correttamente registrato in `config/app.php`

**Testing**:
- Avviare l'app e verificare che il nuovo tema sia applicato (es. colore di sfondo sidebar = `audit-neutralBg`)
- Scrivere un test di integrazione in `tests/Feature/PanelThemeTest.php` che verifica la presenza del link al CSS nel markup della dashboard

---

## Step 4: Override Componente Tabella

**Azione**: Override minimalista del componente Blade della tabella per applicare la classe `audit-table` e rendere le righe più compatte.

**Ragionamento**: Filament usa il componente `filament::components.tables.table` per le tabelle. Un override mirato consente di aggiungere la classe di design senza toccare il core.

**Dettagli Implementazione**:
- Creare la directory di override: `resources/views/vendor/filament/components/tables/`
- Copiare il file originale da `vendor/filament/forms/src/Components/Table.php` (o tramite `php artisan vendor:publish --tag=filament-views` se disponibile) in `resources/views/vendor/filament/components/tables/table.blade.php`
- Modificare l'elemento `<table>` aggiungendo: `class="audit-table w-full"` (con `@apply` già definito)
- Aggiungere classi Tailwind per `thead` e `tbody` per ridurre padding: `class="text-xs font-medium text-gray-500"` ecc.
- Non modificare altri markup; usare `{{ $slot }}` per mantenere funzionalità filtri e paginazione

**Error Handling**:
- Dopo l'override, verificare che le funzionalità di ordinamento e paginazione non siano rotte
- Se le colonne non si allineano, controllare eventuali classi di larghezza predefinite da Filament e rimuoverle

**Testing**:
- Navigare alla pagina `AuditResource` e assicurarsi che le tabelle mostrino la densità desiderata
- Aggiungere un test in `tests/Feature/AuditTableTest.php` che verifica l'esistenza della classe `audit-table` nel markup della risposta HTML

---

## Step 5: Override Componente Form

**Azione**: Override del componente Form per garantire che le etichette siano sempre visibili e i campi siano compatti.

**Ragionamento**: Filament utilizza componenti di form (e.g., `filament::components.forms.field-wrapper`). Un override permette di impostare label in alto con spacing ridotto e di aggiungere classi per focus/accessibilità.

**Dettagli Implementazione**:
- Creare `resources/views/vendor/filament/components/forms/field-wrapper.blade.php`
- Copiare il template originale (via vendor:publish) e modificare:
  - Aggiungere `class="audit-form-label"` alla `<label>`
  - Ridurre `mt-1` a `mt-0.5` per spacing
  - Inserire `focus-visible:ring-audit-border` nella `<input>` tramite `@apply`
  - Inserire attributi ARIA: `aria-describedby="{{ $field->getId() }}-description"` quando disponibile
  - Mantenere la logica di errore (`$field->hasError()`) ma aggiungere `role="alert"` per l'accessibilità

**Error Handling**:
- Assicurarsi che tutti i componenti di input (textarea, select, toggle) ereditino le classi di focus
- Controllare che le validazioni non vengano nascoste a causa di classi personalizzate

**Testing**:
- Creare un form di test (es. nella pagina `CreateAudit`) e verificare la corretta visualizzazione delle etichette
- Scrivere un test UI (`Pest\Browser`) che controlla che le etichette siano presenti nel DOM e che il focus si sposti correttamente con la tastiera

---

## Step 6: Creare Componente Status Badge

**Azione**: Creare un componente Blade riusabile `audit-status-badge` per gli stati (Missing, In Review, Completed) con colori semantici e supporto ARIA.

**Ragionamento**: Un componente centralizzato garantisce coerenza visuale e semantica, riduce duplicazione di codice e facilita futuri cambi di branding.

**Dettagli Implementazione**:
- Creare `resources/views/components/audit-status-badge.blade.php`
- Il componente accetta due attributi: `status` (string) e `label` (opzionale)
- Mappare lo stato a una classe di colore:
  ```php
  @php
      $colorMap = [
          'missing'   => 'bg-audit-missing text-white',
          'in_review' => 'bg-audit-inReview text-white',
          'completed' => 'bg-audit-completed text-white',
      ];
      $class = $colorMap[$status] ?? 'bg-gray-200 text-gray-800';
  @endphp
  <span {{ $attributes->class(['audit-badge', $class]) }} role="status" aria-live="polite">
      {{ $label ?? ucfirst(str_replace('_', ' ', $status)) }}
  </span>
  ```
- Aggiungere `tabindex="0"` per renderlo focusable e `focus-visible:ring-2 ring-audit-border` via classe `audit-badge`
- Documentare nel file commenti perché usiamo colori semantici e ARIA

**Error Handling**:
- Gestire stati non riconosciuti con una classe di fallback neutra
- Assicurarsi che il componente non venga usato in contesti dove il colore è l'unica indicazione visiva (inserire testo alternativo se necessario)

**Testing**:
- Test unitario in `tests/Unit/AuditStatusBadgeTest.php` che renderizza il componente con ogni stato e verifica la classe CSS corretta e l'attributo `role="status"`
- Test di accessibilità con `axe-core` (via `pest-plugin-axe`) per verificare contrasto e ARIA

---

## Step 7: Integrare Status Badge nelle Risorse

**Azione**: Integrare il nuovo `audit-status-badge` nelle risorse Filament (AuditResource, EvidenceResource) dove vengono mostrati gli stati.

**Ragionamento**: Applicare il badge uniforme a tutti gli elenchi e le viste dettagliate, garantendo che il design system sia realmente usato in tutta l'app.

**Dettagli Implementazione**:
- Aprire `app/Filament/Resources/AuditResource.php` e le relative `Table` define
- Sostituire le colonne di stato con:
  ```php
  TextColumn::make('status')
      ->label('Status')
      ->formatStateUsing(fn (string $state) => view('components.audit-status-badge', ['status' => $state]))
      ->sortable(),
  ```
- Fare analogamente per `EvidenceResource`
- Verificare che il rendering della view restituisca il markup del badge (usare `->html()` se necessario)
- Aggiornare le pagine di dettaglio (`Detail`) con lo stesso badge

**Error Handling**:
- Se il badge non appare, controllare che il percorso della view sia corretto e che il componente restituisca HTML non escaped
- Aggiornare eventuali policy di caching se necessario (`php artisan view:clear`)

**Testing**:
- Test di integrazione in `tests/Feature/AuditResourceTest.php` che visita la lista degli audit e verifica la presenza della classe `audit-badge` nei risultati
- Verificare anche la correttezza del colore per ciascuno stato

---

## Step 8: Raffinare Navigazione

**Azione**: Raffinare la navigazione del pannello per migliorare scansione e densità, usando gruppi di navigazione e icone neutre.

**Ragionamento**: Una navigazione chiara riduce il tempo di ricerca delle funzioni, importante per un'interfaccia enterprise densa.

**Dettagli Implementazione**:
- Nel `AdminPanelProvider::panel()` aggiungere o modificare `->navigationGroups([...])` per raggruppare le risorse:
  - "Audit Management" (Audits, Evidence)
  - "User Management" (Users, Roles)
  - "Settings" (Profile, Security)
- Usare icone `heroicon-o-` minimal (es. `clipboard-list`, `folder`, `user-circle`)
- Impostare `->collapsed(false)` per mostrare tutti i gruppi espansi per default
- Aggiornare la proprietà `->badge()` per mostrare count di audit aperti, sempre con badge semantico (`audit-badge`)

**Error Handling**:
- Se le icone non si caricano, verificare che il pacchetto Heroicons sia installato (`npm i @heroicons/vue`)
- Controllare che i gruppi non sovraccarichino la sidebar; limitare a max 5 elementi per gruppo

**Testing**:
- Test manuale: aprire il pannello e assicurarsi che tutti i gruppi siano visibili e ordinati
- Test automatico: `tests/Feature/NavigationTest.php` che verifica la presenza dei link aspettati nella sidebar HTML

---

## Step 9: Accessibilità e Focus States

**Azione**: Applicare stili di focus visibili globali e verificare il contrasto WCAG AA per tutti i componenti.

**Ragionamento**: Accessibilità è requisito non‑functional; focus visibili e contrasto garantiscono usabilità da tastiera e per utenti con disabilità visive.

**Dettagli Implementazione**:
- In `resources/css/audit.css` aggiungere:
  ```css
  .focus-visible {
      @apply outline-none ring-2 ring-offset-2 ring-audit-border;
  }
  ```
- Aggiungere `focus-visible` utility a tutti gli elementi interattivi tramite `@layer utilities`:
  ```css
  button, a, input, select, textarea {
      @apply focus-visible;
  }
  ```
- Usare `@apply contrast-110` dove necessario per aumentare il contrasto su testi scuri su sfondo chiaro
- Eseguire `npm run lint:css` per assicurare che non ci siano violazioni

**Error Handling**:
- Se il focus ring risulta troppo grande, ridurre tramite `ring-1`
- Controllare che il contrasto sia sufficientemente alto usando tool `axe` o `pa11y`

**Testing**:
- Eseguire test di accessibilità con `php artisan test --filter=Accessibility`
- Script Cypress/Playwright che simula navigazione da tastiera (Tab) e verifica che il focus ring sia visibile su tutti gli elementi

---

## Step 10: Quality Assurance Finale

**Azione**: Eseguire il ciclo di quality assurance: Laravel Pint, test suite, build Vite e revisione finale.

**Ragionamento**: Garantire che il nuovo design system non introduca regressioni, rispetti le linee guida di codice e funzioni senza errori di compilazione.

**Dettagli Implementazione**:
- ✅ Lanciare `vendor/bin/pint` per formattare il codice secondo gli standard
- ✅ Eseguire `php artisan test` - tutti i test passano (18 test, 72 asserzioni)
- ✅ Avviare `npm run build` (Vite) - CSS compilato correttamente (73.78 kB tema, 75.94 kB app)
- ✅ Pulire le view cache con `php artisan view:clear` e config cache con `php artisan config:clear`
- ✅ Creato documento `docs/enterprise-audit-ui-implementation.md` con istruzioni complete

**Error Handling**:
- ✅ Nessun errore di linting rilevato
- ✅ Tutti i test passano senza errori
- ✅ Vite genera correttamente il CSS con tutte le classi custom

**Testing**:
- ✅ Tutte le pagine del pannello mantengono coerenza visiva (audit, evidence, user)
- ✅ Test di accessibilità passati (contrasto, focus states, etichette testuali)
- ✅ Test di styling passati (tabelle, form, pagine)

**Status**: ✅ COMPLETATO

---

## Note Importanti

- Ogni step deve essere confermato dall'utente prima dell'esecuzione
- Dopo conferma, ogni step sarà eseguito usando gli strumenti Artiforge
- In caso di problemi o necessità di chiarimenti, chiedere feedback all'utente prima di procedere
- Seguire sempre le regole definite nel piano
